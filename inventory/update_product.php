<?php

/* =========================================================
   SPIS - UPDATE INVENTORY PRODUCT
========================================================= */

if (
    session_status()
    !==
    PHP_SESSION_ACTIVE
) {

    session_start();

}


/* =========================================================
   TIMEZONE / ERROR HANDLING
========================================================= */

date_default_timezone_set(
    "Asia/Manila"
);

error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);


/* =========================================================
   REQUIRED FILES
========================================================= */

require_once "../db.php";

require_once "../includes/check_access.php";

require_once "../includes/history_logger.php";


/* =========================================================
   ACCESS CHECK
========================================================= */

checkAccess(
    "inventory_access"
);


/* =========================================================
   DATABASE SETTINGS
========================================================= */

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);

mysqli_report(
    MYSQLI_REPORT_ERROR
    |
    MYSQLI_REPORT_STRICT
);


/* =========================================================
   IMAGE LIMITS
========================================================= */

const INVENTORY_MAX_IMAGE_SIZE =
    20
    *
    1024
    *
    1024;

const INVENTORY_MAX_IMAGE_PIXELS =
    60000000;

const INVENTORY_MAX_IMAGE_DIMENSION =
    1600;


/* =========================================================
   ERROR REDIRECT
========================================================= */

function inventoryUpdateRedirectError(
    string $code
): never {

    header(
        "Location: inventory.php?error="
        .
        rawurlencode(
            $code
        )
    );

    exit();

}


/* =========================================================
   STRING LENGTH
========================================================= */

function inventoryUpdateStringLength(
    string $value
): int {

    if (
        function_exists(
            "mb_strlen"
        )
    ) {

        return mb_strlen(
            $value,
            "UTF-8"
        );

    }

    return strlen(
        $value
    );

}


/* =========================================================
   DESTROY GD IMAGE
========================================================= */

function inventoryUpdateDestroyImage(
    $image
): void {

    if (
        $image
        &&
        function_exists(
            "imagedestroy"
        )
    ) {

        @imagedestroy(
            $image
        );

    }

}


/* =========================================================
   APPLY JPEG CAMERA ORIENTATION
========================================================= */

function inventoryUpdateApplyOrientation(
    $image,
    string $tempFile,
    string $mime
) {

    if (
        $mime !== "image/jpeg"
        ||
        !function_exists(
            "exif_read_data"
        )
    ) {

        return $image;

    }


    $exif =
        @exif_read_data(
            $tempFile
        );


    if (
        !is_array(
            $exif
        )
    ) {

        return $image;

    }


    $orientation =
        (int) (
            $exif["Orientation"]
            ??
            1
        );


    switch (
        $orientation
    ) {

        /* MIRROR HORIZONTAL */
        case 2:

            if (
                function_exists(
                    "imageflip"
                )
            ) {

                imageflip(
                    $image,
                    IMG_FLIP_HORIZONTAL
                );

            }

            break;


        /* UPSIDE DOWN */
        case 3:

            $rotated =
                @imagerotate(
                    $image,
                    180,
                    0
                );


            if ($rotated) {

                inventoryUpdateDestroyImage(
                    $image
                );

                $image =
                    $rotated;

            }

            break;


        /* MIRROR VERTICAL */
        case 4:

            if (
                function_exists(
                    "imageflip"
                )
            ) {

                imageflip(
                    $image,
                    IMG_FLIP_VERTICAL
                );

            }

            break;


        /* MIRRORED + ROTATED */
        case 5:

            if (
                function_exists(
                    "imageflip"
                )
            ) {

                imageflip(
                    $image,
                    IMG_FLIP_VERTICAL
                );

            }


            $rotated =
                @imagerotate(
                    $image,
                    -90,
                    0
                );


            if ($rotated) {

                inventoryUpdateDestroyImage(
                    $image
                );

                $image =
                    $rotated;

            }

            break;


        /* ROTATE 90° CLOCKWISE */
        case 6:

            $rotated =
                @imagerotate(
                    $image,
                    -90,
                    0
                );


            if ($rotated) {

                inventoryUpdateDestroyImage(
                    $image
                );

                $image =
                    $rotated;

            }

            break;


        /* MIRRORED + ROTATED */
        case 7:

            if (
                function_exists(
                    "imageflip"
                )
            ) {

                imageflip(
                    $image,
                    IMG_FLIP_HORIZONTAL
                );

            }


            $rotated =
                @imagerotate(
                    $image,
                    -90,
                    0
                );


            if ($rotated) {

                inventoryUpdateDestroyImage(
                    $image
                );

                $image =
                    $rotated;

            }

            break;


        /* ROTATE 90° COUNTER-CLOCKWISE */
        case 8:

            $rotated =
                @imagerotate(
                    $image,
                    90,
                    0
                );


            if ($rotated) {

                inventoryUpdateDestroyImage(
                    $image
                );

                $image =
                    $rotated;

            }

            break;

    }


    return $image;

}


/* =========================================================
   OPTIMIZE / RESIZE IMAGE
========================================================= */

function inventoryUpdateCreateOptimizedImage(
    string $tempFile,
    string $mime,
    string $destination
): bool {

    if (
        !function_exists(
            "imagecreatefromstring"
        )
        ||
        !function_exists(
            "imagecreatetruecolor"
        )
        ||
        !function_exists(
            "imagecopyresampled"
        )
    ) {

        return false;

    }


    $rawImage =
        @file_get_contents(
            $tempFile
        );


    if (
        $rawImage === false
    ) {

        return false;

    }


    $sourceImage =
        @imagecreatefromstring(
            $rawImage
        );


    unset(
        $rawImage
    );


    if (
        !$sourceImage
    ) {

        return false;

    }


    $sourceImage =
        inventoryUpdateApplyOrientation(

            $sourceImage,

            $tempFile,

            $mime

        );


    $sourceWidth =
        imagesx(
            $sourceImage
        );


    $sourceHeight =
        imagesy(
            $sourceImage
        );


    if (
        $sourceWidth <= 0
        ||
        $sourceHeight <= 0
    ) {

        inventoryUpdateDestroyImage(
            $sourceImage
        );

        return false;

    }


    /*
     * Smaller images stay at their
     * original size.
     */

    $scale =
        min(

            1,

            INVENTORY_MAX_IMAGE_DIMENSION
            /
            $sourceWidth,

            INVENTORY_MAX_IMAGE_DIMENSION
            /
            $sourceHeight

        );


    $targetWidth =
        max(

            1,

            (int) round(
                $sourceWidth
                *
                $scale
            )

        );


    $targetHeight =
        max(

            1,

            (int) round(
                $sourceHeight
                *
                $scale
            )

        );


    $targetImage =
        imagecreatetruecolor(

            $targetWidth,

            $targetHeight

        );


    if (
        !$targetImage
    ) {

        inventoryUpdateDestroyImage(
            $sourceImage
        );

        return false;

    }


    /* Preserve PNG / WEBP transparency */

    if (
        $mime === "image/png"
        ||
        $mime === "image/webp"
    ) {

        imagealphablending(
            $targetImage,
            false
        );

        imagesavealpha(
            $targetImage,
            true
        );


        $transparent =
            imagecolorallocatealpha(

                $targetImage,

                0,
                0,
                0,

                127

            );


        imagefilledrectangle(

            $targetImage,

            0,
            0,

            $targetWidth,
            $targetHeight,

            $transparent

        );

    }


    $resampled =
        imagecopyresampled(

            $targetImage,

            $sourceImage,

            0,
            0,

            0,
            0,

            $targetWidth,
            $targetHeight,

            $sourceWidth,
            $sourceHeight

        );


    inventoryUpdateDestroyImage(
        $sourceImage
    );


    if (
        !$resampled
    ) {

        inventoryUpdateDestroyImage(
            $targetImage
        );

        return false;

    }


    $saved =
        false;


    if (
        $mime === "image/jpeg"
        &&
        function_exists(
            "imagejpeg"
        )
    ) {

        $saved =
            imagejpeg(

                $targetImage,

                $destination,

                84

            );

    }

    elseif (
        $mime === "image/png"
        &&
        function_exists(
            "imagepng"
        )
    ) {

        $saved =
            imagepng(

                $targetImage,

                $destination,

                7

            );

    }

    elseif (
        $mime === "image/webp"
        &&
        function_exists(
            "imagewebp"
        )
    ) {

        $saved =
            imagewebp(

                $targetImage,

                $destination,

                84

            );

    }


    inventoryUpdateDestroyImage(
        $targetImage
    );


    return
        $saved
        &&
        is_file(
            $destination
        )
        &&
        filesize(
            $destination
        ) > 0;

}


/* =========================================================
   REMOVE OLD IMAGE SAFELY

   Only removes the old file if no other
   Inventory record is still using it.
========================================================= */

function inventoryDeleteOldImageIfUnused(
    mysqli $conn,
    string $relativePath
): void {

    $normalizedPath =
        str_replace(
            "\\",
            "/",
            $relativePath
        );


    /*
     * Only files directly inside
     * assets/products are eligible.
     */

    if (
        $normalizedPath === ""
        ||
        dirname(
            $normalizedPath
        ) !== "assets/products"
    ) {

        return;

    }


    $checkStmt =
        mysqli_prepare(

            $conn,

            "
            SELECT
                COUNT(*) AS total

            FROM inventory

            WHERE image = ?
            "

        );


    mysqli_stmt_bind_param(

        $checkStmt,

        "s",

        $relativePath

    );


    mysqli_stmt_execute(
        $checkStmt
    );


    $result =
        mysqli_stmt_get_result(
            $checkStmt
        );


    $row =
        $result
        ?
        mysqli_fetch_assoc(
            $result
        )
        :
        null;


    mysqli_stmt_close(
        $checkStmt
    );


    /*
     * Do not delete if another record
     * still references the image.
     */

    if (
        (int) (
            $row["total"]
            ??
            0
        ) > 0
    ) {

        return;

    }


    $filename =
        basename(
            $normalizedPath
        );


    if (
        $filename === ""
        ||
        $filename === "."
        ||
        $filename === ".."
    ) {

        return;

    }


    $physicalPath =
        dirname(
            __DIR__
        )
        .
        DIRECTORY_SEPARATOR
        .
        "assets"
        .
        DIRECTORY_SEPARATOR
        .
        "products"
        .
        DIRECTORY_SEPARATOR
        .
        $filename;


    if (
        is_file(
            $physicalPath
        )
        &&
        !@unlink(
            $physicalPath
        )
    ) {

        error_log(
            "Inventory: unable to remove old image: "
            .
            $physicalPath
        );

    }

}


/* =========================================================
   POST REQUEST ONLY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    !==
    "POST"
) {

    inventoryUpdateRedirectError(
        "invalid_request"
    );

}


/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionToken =
    $_SESSION[
        "inventory_csrf_token"
    ]
    ??
    "";


$formToken =
    $_POST[
        "csrf_token"
    ]
    ??
    "";


if (
    !is_string(
        $sessionToken
    )
    ||
    !is_string(
        $formToken
    )
    ||
    $sessionToken === ""
    ||
    $formToken === ""
    ||
    !hash_equals(
        $sessionToken,
        $formToken
    )
) {

    inventoryUpdateRedirectError(
        "invalid_security_token"
    );

}


/* =========================================================
   PRODUCT ID
========================================================= */

$productID =
    filter_var(

        $_POST[
            "product_id"
        ]
        ??
        null,

        FILTER_VALIDATE_INT

    );


if (
    $productID === false
    ||
    $productID <= 0
) {

    inventoryUpdateRedirectError(
        "invalid_product"
    );

}


/* =========================================================
   PRODUCT NAME
========================================================= */

$productName =
    trim(
        (string) (
            $_POST[
                "product_name"
            ]
            ??
            ""
        )
    );


$productName =
    preg_replace(

        '/[\x00-\x1F\x7F]/u',

        "",

        $productName

    )
    ??
    "";


$productName =
    trim(
        $productName
    );


if (
    $productName === ""
) {

    inventoryUpdateRedirectError(
        "product_name_required"
    );

}


if (
    inventoryUpdateStringLength(
        $productName
    ) > 150
) {

    inventoryUpdateRedirectError(
        "product_name_too_long"
    );

}


/* =========================================================
   NUMERIC VALUES
========================================================= */

$acquiredPriceRaw =
    trim(
        (string) (
            $_POST[
                "acquired_price"
            ]
            ??
            ""
        )
    );


$markupPercentRaw =
    trim(
        (string) (
            $_POST[
                "markup_percent"
            ]
            ??
            ""
        )
    );


$quantityRaw =
    trim(
        (string) (
            $_POST[
                "quantity"
            ]
            ??
            ""
        )
    );


/* =========================================================
   ACQUIRED PRICE
========================================================= */

if (
    !preg_match(
        '/^\d+(?:\.\d{1,2})?$/',
        $acquiredPriceRaw
    )
) {

    inventoryUpdateRedirectError(
        "invalid_acquired_price"
    );

}


$acquiredPrice =
    round(
        (float) $acquiredPriceRaw,
        2
    );


if (
    !is_finite(
        $acquiredPrice
    )
    ||
    $acquiredPrice <= 0
) {

    inventoryUpdateRedirectError(
        "invalid_acquired_price"
    );

}


/* =========================================================
   MARK UP
========================================================= */

if (
    !preg_match(
        '/^\d+(?:\.\d{1,2})?$/',
        $markupPercentRaw
    )
) {

    inventoryUpdateRedirectError(
        "invalid_markup"
    );

}


$markupPercent =
    round(
        (float) $markupPercentRaw,
        2
    );


if (
    !is_finite(
        $markupPercent
    )
    ||
    $markupPercent < 0
) {

    inventoryUpdateRedirectError(
        "invalid_markup"
    );

}


/* =========================================================
   QUANTITY
========================================================= */

if (
    !preg_match(
        '/^\d+$/',
        $quantityRaw
    )
) {

    inventoryUpdateRedirectError(
        "invalid_quantity"
    );

}


$quantity =
    (int) $quantityRaw;


/*
 * Edit allows zero because an existing
 * product can legitimately be Out of Stock.
 */

if (
    $quantity < 0
) {

    inventoryUpdateRedirectError(
        "invalid_quantity"
    );

}


/* =========================================================
   COMPUTE SELLING PRICE SERVER-SIDE
========================================================= */

$sellingPrice =
    $acquiredPrice
    +
    (
        $acquiredPrice
        *
        $markupPercent
        /
        100
    );


$sellingPrice =
    round(
        $sellingPrice,
        2
    );


if (
    !is_finite(
        $sellingPrice
    )
    ||
    $sellingPrice <= 0
) {

    inventoryUpdateRedirectError(
        "invalid_selling_price"
    );

}


/* =========================================================
   OPTIONAL NEW IMAGE
========================================================= */

$newImagePath =
    null;


$newPhysicalImagePath =
    null;


$imageFile =
    $_FILES[
        "image"
    ]
    ??
    null;


$imageError =
    is_array(
        $imageFile
    )
    ?
    (int) (
        $imageFile[
            "error"
        ]
        ??
        UPLOAD_ERR_NO_FILE
    )
    :
    UPLOAD_ERR_NO_FILE;


/* =========================================================
   PROCESS NEW IMAGE ONLY IF USER SELECTED ONE
========================================================= */

if (
    $imageError
    !==
    UPLOAD_ERR_NO_FILE
) {

    /* =====================================================
       UPLOAD ERROR
    ===================================================== */

    if (
        $imageError
        !==
        UPLOAD_ERR_OK
    ) {

        if (
            $imageError
            ===
            UPLOAD_ERR_INI_SIZE
            ||
            $imageError
            ===
            UPLOAD_ERR_FORM_SIZE
        ) {

            inventoryUpdateRedirectError(
                "image_too_large"
            );

        }


        inventoryUpdateRedirectError(
            "image_upload_failed"
        );

    }


    /* =====================================================
       TEMP FILE
    ===================================================== */

    $tempName =
        (string) (
            $imageFile[
                "tmp_name"
            ]
            ??
            ""
        );


    if (
        $tempName === ""
        ||
        !is_uploaded_file(
            $tempName
        )
    ) {

        inventoryUpdateRedirectError(
            "invalid_image_upload"
        );

    }


    /* =====================================================
       HARD 20 MB LIMIT
    ===================================================== */

    $fileSize =
        @filesize(
            $tempName
        );


    if (
        $fileSize === false
        ||
        $fileSize <= 0
    ) {

        inventoryUpdateRedirectError(
            "invalid_image"
        );

    }


    if (
        $fileSize
        >
        INVENTORY_MAX_IMAGE_SIZE
    ) {

        inventoryUpdateRedirectError(
            "image_too_large"
        );

    }


    /* =====================================================
       TRUE MIME CHECK
    ===================================================== */

    if (
        !function_exists(
            "finfo_open"
        )
    ) {

        inventoryUpdateRedirectError(
            "image_validation_unavailable"
        );

    }


    $fileInfo =
        finfo_open(
            FILEINFO_MIME_TYPE
        );


    if (
        $fileInfo === false
    ) {

        inventoryUpdateRedirectError(
            "image_validation_unavailable"
        );

    }


    $mimeType =
        finfo_file(

            $fileInfo,

            $tempName

        );


    finfo_close(
        $fileInfo
    );


    $allowedMimeTypes = [

        "image/jpeg"
            =>
        "jpg",

        "image/png"
            =>
        "png",

        "image/webp"
            =>
        "webp"

    ];


    if (
        !is_string(
            $mimeType
        )
        ||
        !isset(
            $allowedMimeTypes[
                $mimeType
            ]
        )
    ) {

        inventoryUpdateRedirectError(
            "invalid_image_type"
        );

    }


    /* =====================================================
       VERIFY REAL IMAGE
    ===================================================== */

    $imageInformation =
        @getimagesize(
            $tempName
        );


    if (
        $imageInformation === false
    ) {

        inventoryUpdateRedirectError(
            "invalid_image"
        );

    }


    $imageWidth =
        (int) (
            $imageInformation[0]
            ??
            0
        );


    $imageHeight =
        (int) (
            $imageInformation[1]
            ??
            0
        );


    $imageMime =
        (string) (
            $imageInformation[
                "mime"
            ]
            ??
            ""
        );


    if (
        $imageWidth <= 0
        ||
        $imageHeight <= 0
        ||
        $imageMime !== $mimeType
    ) {

        inventoryUpdateRedirectError(
            "invalid_image"
        );

    }


    /* =====================================================
       DIMENSION SAFETY
    ===================================================== */

    $imagePixels =
        $imageWidth
        *
        $imageHeight;


    if (
        $imagePixels <= 0
        ||
        $imagePixels
        >
        INVENTORY_MAX_IMAGE_PIXELS
    ) {

        inventoryUpdateRedirectError(
            "image_dimensions_too_large"
        );

    }


    /* =====================================================
       GD
    ===================================================== */

    if (
        !extension_loaded(
            "gd"
        )
    ) {

        inventoryUpdateRedirectError(
            "image_processing_unavailable"
        );

    }


    /* =====================================================
       IMAGE DIRECTORY
    ===================================================== */

    $uploadDirectory =
        dirname(
            __DIR__
        )
        .
        DIRECTORY_SEPARATOR
        .
        "assets"
        .
        DIRECTORY_SEPARATOR
        .
        "products"
        .
        DIRECTORY_SEPARATOR;


    if (
        !is_dir(
            $uploadDirectory
        )
        &&
        !mkdir(
            $uploadDirectory,
            0755,
            true
        )
    ) {

        inventoryUpdateRedirectError(
            "image_directory_error"
        );

    }


    if (
        !is_writable(
            $uploadDirectory
        )
    ) {

        inventoryUpdateRedirectError(
            "image_directory_error"
        );

    }


    /* =====================================================
       SECURE RANDOM NAME
    ===================================================== */

    try {

        $randomName =
            bin2hex(
                random_bytes(
                    16
                )
            );

    }
    catch (
        Throwable $error
    ) {

        error_log(
            "Inventory image filename error: "
            .
            $error->getMessage()
        );


        inventoryUpdateRedirectError(
            "image_processing_failed"
        );

    }


    $filename =
        "product_"
        .
        $randomName
        .
        "."
        .
        $allowedMimeTypes[
            $mimeType
        ];


    $newPhysicalImagePath =
        $uploadDirectory
        .
        $filename;


    $newImagePath =
        "assets/products/"
        .
        $filename;


    /* =====================================================
       RESIZE / COMPRESS
    ===================================================== */

    if (
        !inventoryUpdateCreateOptimizedImage(

            $tempName,

            $mimeType,

            $newPhysicalImagePath

        )
    ) {

        if (
            is_file(
                $newPhysicalImagePath
            )
        ) {

            @unlink(
                $newPhysicalImagePath
            );

        }


        inventoryUpdateRedirectError(
            "image_processing_failed"
        );

    }

}


/* =========================================================
   DATABASE TRANSACTION
========================================================= */

$transactionStarted =
    false;


try {

    mysqli_begin_transaction(
        $conn
    );


    $transactionStarted =
        true;


    /* =====================================================
       GET AND LOCK CURRENT PRODUCT
    ===================================================== */

    $checkStmt =
        mysqli_prepare(

            $conn,

            "
            SELECT
                product_id,
                product_name,
                acquired_price,
                markup_percent,
                selling_price,
                quantity,
                image

            FROM inventory

            WHERE product_id = ?

            AND archived = 0

            LIMIT 1

            FOR UPDATE
            "

        );


    mysqli_stmt_bind_param(

        $checkStmt,

        "i",

        $productID

    );


    mysqli_stmt_execute(
        $checkStmt
    );


    $productResult =
        mysqli_stmt_get_result(
            $checkStmt
        );


    $currentProduct =
        $productResult
        ?
        mysqli_fetch_assoc(
            $productResult
        )
        :
        null;


    mysqli_stmt_close(
        $checkStmt
    );


    if (
        !$currentProduct
    ) {

        throw new RuntimeException(
            "Product record was not found."
        );

    }

    /* =====================================================
       BLOCK DUPLICATE PRODUCT NAMES (EXCLUDE CURRENT ID)
    ===================================================== */

    $duplicateStmt = mysqli_prepare(
        $conn,
        "
        SELECT product_id
        FROM inventory
        WHERE product_id <> ?
        AND LOWER(
            REGEXP_REPLACE(TRIM(product_name), '[[:space:]]+', ' ')
        ) = LOWER(
            REGEXP_REPLACE(TRIM(?), '[[:space:]]+', ' ')
        )
        LIMIT 1
        "
    );

    mysqli_stmt_bind_param(
        $duplicateStmt,
        "is",
        $productID,
        $productName
    );

    mysqli_stmt_execute($duplicateStmt);
    mysqli_stmt_store_result($duplicateStmt);

    $isDuplicate = mysqli_stmt_num_rows($duplicateStmt) > 0;

    mysqli_stmt_close($duplicateStmt);

    if ($isDuplicate) {
        throw new DomainException("duplicate_product_name");
    }

    /* =====================================================
       CURRENT VALUES
    ===================================================== */

    $currentProductName =
        trim(
            (string) (
                $currentProduct[
                    "product_name"
                ]
                ??
                ""
            )
        );


    $currentAcquiredPrice =
        round(
            (float) (
                $currentProduct[
                    "acquired_price"
                ]
                ??
                0
            ),
            2
        );


    $currentMarkupPercent =
        round(
            (float) (
                $currentProduct[
                    "markup_percent"
                ]
                ??
                0
            ),
            2
        );


    $currentSellingPrice =
        round(
            (float) (
                $currentProduct[
                    "selling_price"
                ]
                ??
                0
            ),
            2
        );


    $currentQuantity =
        (int) (
            $currentProduct[
                "quantity"
            ]
            ??
            0
        );


    $currentImagePath =
        trim(
            (string) (
                $currentProduct[
                    "image"
                ]
                ??
                ""
            )
        );


    /* =====================================================
       BUILD HISTORY CHANGES
    ===================================================== */

    $changes = [];


    if (
        $currentProductName
        !==
        $productName
    ) {

        $changes[] =
            'product name from "'
            .
            $currentProductName
            .
            '" to "'
            .
            $productName
            .
            '"';

    }


    if (
        abs(
            $currentAcquiredPrice
            -
            $acquiredPrice
        ) > 0.001
    ) {

        $changes[] =
            "acquired price from ₱"
            .
            number_format(
                $currentAcquiredPrice,
                2
            )
            .
            " to ₱"
            .
            number_format(
                $acquiredPrice,
                2
            );

    }


    if (
        abs(
            $currentMarkupPercent
            -
            $markupPercent
        ) > 0.001
    ) {

        $changes[] =
            "markup from "
            .
            number_format(
                $currentMarkupPercent,
                2
            )
            .
            "% to "
            .
            number_format(
                $markupPercent,
                2
            )
            .
            "%";

    }


    if (
        abs(
            $currentSellingPrice
            -
            $sellingPrice
        ) > 0.001
    ) {

        $changes[] =
            "selling price from ₱"
            .
            number_format(
                $currentSellingPrice,
                2
            )
            .
            " to ₱"
            .
            number_format(
                $sellingPrice,
                2
            );

    }


    if (
        $currentQuantity
        !==
        $quantity
    ) {

        $changes[] =
            "quantity from "
            .
            $currentQuantity
            .
            " to "
            .
            $quantity;

    }


    if (
        $newImagePath
        !==
        null
    ) {

        $changes[] =
            "product image";

    }


    /* =====================================================
       NO ACTUAL CHANGES
    ===================================================== */

    if (
        empty(
            $changes
        )
    ) {

        mysqli_commit(
            $conn
        );


        $transactionStarted =
            false;


        header(
            "Location: inventory.php?success=updated"
        );


        exit();

    }


    /* =====================================================
       UPDATE WITH NEW IMAGE
    ===================================================== */

    if (
        $newImagePath
        !==
        null
    ) {

        $updateStmt =
            mysqli_prepare(

                $conn,

                "
                UPDATE inventory

                SET
                    product_name = ?,
                    acquired_price = ?,
                    markup_percent = ?,
                    selling_price = ?,
                    quantity = ?,
                    image = ?

                WHERE product_id = ?

                AND archived = 0
                "

            );


        mysqli_stmt_bind_param(

            $updateStmt,

            "sdddisi",

            $productName,

            $acquiredPrice,

            $markupPercent,

            $sellingPrice,

            $quantity,

            $newImagePath,

            $productID

        );

    }


    /* =====================================================
       UPDATE WITHOUT NEW IMAGE
    ===================================================== */

    else {

        $updateStmt =
            mysqli_prepare(

                $conn,

                "
                UPDATE inventory

                SET
                    product_name = ?,
                    acquired_price = ?,
                    markup_percent = ?,
                    selling_price = ?,
                    quantity = ?

                WHERE product_id = ?

                AND archived = 0
                "

            );


        mysqli_stmt_bind_param(

            $updateStmt,

            "sdddii",

            $productName,

            $acquiredPrice,

            $markupPercent,

            $sellingPrice,

            $quantity,

            $productID

        );

    }


    /* =====================================================
       EXECUTE UPDATE
    ===================================================== */

    mysqli_stmt_execute(
        $updateStmt
    );


    if (
        mysqli_stmt_affected_rows(
            $updateStmt
        ) !== 1
    ) {

        mysqli_stmt_close(
            $updateStmt
        );


        throw new RuntimeException(
            "Product update did not affect the expected record."
        );

    }


    mysqli_stmt_close(
        $updateStmt
    );


    /* =====================================================
       HISTORY
    ===================================================== */

    log_activity(

        $conn,

        "Inventory",

        "Updated",

        'updated the inventory product "'
        .
        $currentProductName
        .
        '": '
        .
        implode(
            ", ",
            $changes
        )
        .
        ".",

        $productID

    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    $transactionStarted =
        false;


    /* =====================================================
       ROTATE CSRF TOKEN
    ===================================================== */

    try {

        $_SESSION[
            "inventory_csrf_token"
        ] =
            bin2hex(
                random_bytes(
                    32
                )
            );

    }
    catch (
        Throwable $tokenError
    ) {

        error_log(
            "Inventory CSRF rotation error: "
            .
            $tokenError->getMessage()
        );

    }


    /* =====================================================
       REMOVE OLD IMAGE AFTER SUCCESSFUL UPDATE
    ===================================================== */

    if (
        $newImagePath
        !==
        null
        &&
        $currentImagePath
        !==
        ""
        &&
        $currentImagePath
        !==
        $newImagePath
    ) {

        try {

            inventoryDeleteOldImageIfUnused(

                $conn,

                $currentImagePath

            );

        }
        catch (
            Throwable $cleanupError
        ) {

            error_log(
                "Inventory old image cleanup error: "
                .
                $cleanupError->getMessage()
            );

        }

    }


    /* =====================================================
       SUCCESS
    ===================================================== */

    header(
        "Location: inventory.php?success=updated"
    );


    exit();

}


/* =========================================================
   FAILURE
========================================================= */

catch (
    Throwable $error
) {

    if (
        $transactionStarted
    ) {

        try {

            mysqli_rollback(
                $conn
            );

        }
        catch (
            Throwable $rollbackError
        ) {

            error_log(
                "Inventory rollback error: "
                .
                $rollbackError->getMessage()
            );

        }

    }


    /*
     * If a replacement image was already
     * created but DB update failed,
     * remove only that NEW image.
     *
     * The current/old image stays untouched.
     */

    if (
        $newPhysicalImagePath
        !==
        null
        &&
        is_file(
            $newPhysicalImagePath
        )
    ) {

        @unlink(
            $newPhysicalImagePath
        );

    }


    /*
     * Technical details go to the
     * PHP error log only.
     */

    error_log(
        "Inventory Update Product error: "
        .
        $error->getMessage()
    );


    if (
        $error instanceof DomainException
        &&
        $error->getMessage() === "duplicate_product_name"
    ) {
        inventoryUpdateRedirectError("duplicate_product_name");
    }

    inventoryUpdateRedirectError(
        "update_failed"
    );

}