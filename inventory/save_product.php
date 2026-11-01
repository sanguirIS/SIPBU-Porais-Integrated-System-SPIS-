<?php

/* =========================================================
   SPIS - SAVE INVENTORY PRODUCT
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


/*
 * Do not expose PHP / database errors
 * directly to the browser.
 */

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
   INVENTORY ACCESS
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


/* =========================================================
   MYSQL EXCEPTIONS
========================================================= */

mysqli_report(
    MYSQLI_REPORT_ERROR
    |
    MYSQLI_REPORT_STRICT
);


/* =========================================================
   CONSTANTS
========================================================= */

const INVENTORY_MAX_IMAGE_SIZE =
    20
    *
    1024
    *
    1024;


/*
 * Maximum decoded image size.
 *
 * This helps prevent extremely huge images
 * from exhausting PHP memory.
 *
 * 60 megapixels is still generous for
 * normal phone / camera product photos.
 */

const INVENTORY_MAX_IMAGE_PIXELS =
    60000000;


/*
 * Images larger than this will be resized.
 * Smaller images will never be enlarged.
 */

const INVENTORY_MAX_IMAGE_DIMENSION =
    1600;


/* =========================================================
   REDIRECT HELPER
========================================================= */

function inventoryRedirectError(
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
   SAFE STRING LENGTH
========================================================= */

function inventoryStringLength(
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

function inventoryDestroyImage(
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

function inventoryApplyOrientation(
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

                inventoryDestroyImage(
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

                inventoryDestroyImage(
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

                inventoryDestroyImage(
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

                inventoryDestroyImage(
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

                inventoryDestroyImage(
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
   CREATE OPTIMIZED PRODUCT IMAGE
========================================================= */

function inventoryCreateOptimizedImage(
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


    /*
     * Correct orientation from phone /
     * camera JPEG files before stripping
     * metadata during re-encoding.
     */

    $sourceImage =
        inventoryApplyOrientation(
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

        inventoryDestroyImage(
            $sourceImage
        );

        return false;

    }


    /*
     * Never enlarge smaller images.
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

        inventoryDestroyImage(
            $sourceImage
        );

        return false;

    }


    /*
     * Preserve transparency for
     * PNG and WEBP.
     */

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


    inventoryDestroyImage(
        $sourceImage
    );


    if (
        !$resampled
    ) {

        inventoryDestroyImage(
            $targetImage
        );

        return false;

    }


    $saved =
        false;


    switch (
        $mime
    ) {

        /* =============================================
           JPEG
        ============================================= */

        case "image/jpeg":

            if (
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

            break;


        /* =============================================
           PNG
        ============================================= */

        case "image/png":

            if (
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

            break;


        /* =============================================
           WEBP
        ============================================= */

        case "image/webp":

            if (
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

            break;

    }


    inventoryDestroyImage(
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
   POST REQUEST ONLY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    !==
    "POST"
) {

    inventoryRedirectError(
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

    inventoryRedirectError(
        "invalid_security_token"
    );

}


/* =========================================================
   GET FORM VALUES
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
   PRODUCT NAME VALIDATION
========================================================= */

if (
    $productName === ""
) {

    inventoryRedirectError(
        "product_name_required"
    );

}


/*
 * Match the 150-character limit
 * used by the Add Product form.
 */

if (
    inventoryStringLength(
        $productName
    ) > 150
) {

    inventoryRedirectError(
        "product_name_too_long"
    );

}


/*
 * Remove invisible control characters.
 */

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

    inventoryRedirectError(
        "invalid_product_name"
    );

}


/* =========================================================
   ACQUIRED PRICE VALIDATION
========================================================= */

if (
    !preg_match(
        '/^\d+(?:\.\d{1,2})?$/',
        $acquiredPriceRaw
    )
) {

    inventoryRedirectError(
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

    inventoryRedirectError(
        "invalid_acquired_price"
    );

}


/* =========================================================
   MARKUP VALIDATION
========================================================= */

if (
    !preg_match(
        '/^\d+(?:\.\d{1,2})?$/',
        $markupPercentRaw
    )
) {

    inventoryRedirectError(
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

    inventoryRedirectError(
        "invalid_markup"
    );

}


/* =========================================================
   QUANTITY VALIDATION
========================================================= */

if (
    !preg_match(
        '/^\d+$/',
        $quantityRaw
    )
) {

    inventoryRedirectError(
        "invalid_quantity"
    );

}


$quantity =
    (int) $quantityRaw;


if (
    $quantity < 1
) {

    inventoryRedirectError(
        "invalid_quantity"
    );

}


/* =========================================================
   COMPUTE SELLING PRICE ON SERVER

   Never trust the readonly field or JavaScript.
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

    inventoryRedirectError(
        "invalid_selling_price"
    );

}


/* =========================================================
   OPTIONAL IMAGE UPLOAD

   Product image is NOT required.
   If no image is selected, the product will still
   be saved with an empty image path.

   If an image IS selected, all existing security,
   MIME validation, size limits, dimension limits,
   resizing, compression, and metadata stripping
   still apply.
========================================================= */

$imageFile =
    $_FILES[
        "image"
    ]
    ??
    null;


/*
 * Default values when the user
 * does not upload an image.
 */

$databaseImagePath =
    "";

$physicalImagePath =
    null;


/* =========================================================
   VALIDATE FILE ARRAY WHEN PRESENT
========================================================= */

if (
    $imageFile !== null
    &&
    !is_array(
        $imageFile
    )
) {

    inventoryRedirectError(
        "invalid_image_upload"
    );

}


/*
 * A missing $_FILES entry or UPLOAD_ERR_NO_FILE
 * simply means the product has no image yet.
 */

if (
    is_array(
        $imageFile
    )
) {

    if (
        !array_key_exists(
            "error",
            $imageFile
        )
    ) {

        inventoryRedirectError(
            "invalid_image_upload"
        );

    }


    $uploadError =
        (int) $imageFile[
            "error"
        ];


    /* =====================================================
       NO IMAGE SELECTED
    ===================================================== */

    if (
        $uploadError
        ===
        UPLOAD_ERR_NO_FILE
    ) {

        $imageFile =
            null;

    }


    /* =====================================================
       ACTUAL UPLOAD ERROR
    ===================================================== */

    elseif (
        $uploadError
        !==
        UPLOAD_ERR_OK
    ) {

        if (
            $uploadError
            ===
            UPLOAD_ERR_INI_SIZE
            ||
            $uploadError
            ===
            UPLOAD_ERR_FORM_SIZE
        ) {

            inventoryRedirectError(
                "image_too_large"
            );

        }


        inventoryRedirectError(
            "image_upload_failed"
        );

    }

}


/* =========================================================
   PROCESS IMAGE ONLY IF USER SELECTED ONE
========================================================= */

if (
    is_array(
        $imageFile
    )
) {


    /* =====================================================
       TEMPORARY UPLOAD VALIDATION
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

        inventoryRedirectError(
            "invalid_image_upload"
        );

    }


    /* =====================================================
       HARD 20 MB SERVER LIMIT
    ===================================================== */

    $actualFileSize =
        @filesize(
            $tempName
        );


    if (
        $actualFileSize === false
        ||
        $actualFileSize <= 0
    ) {

        inventoryRedirectError(
            "invalid_image"
        );

    }


    if (
        $actualFileSize
        >
        INVENTORY_MAX_IMAGE_SIZE
    ) {

        inventoryRedirectError(
            "image_too_large"
        );

    }


    /* =====================================================
       VERIFY TRUE MIME TYPE
    ===================================================== */

    if (
        !function_exists(
            "finfo_open"
        )
    ) {

        inventoryRedirectError(
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

        inventoryRedirectError(
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

        inventoryRedirectError(
            "invalid_image_type"
        );

    }


    /* =====================================================
       VERIFY ACTUAL IMAGE
    ===================================================== */

    $imageInformation =
        @getimagesize(
            $tempName
        );


    if (
        $imageInformation === false
    ) {

        inventoryRedirectError(
            "invalid_image"
        );

    }


    $imageWidth =
        (int) (
            $imageInformation[
                0
            ]
            ??
            0
        );


    $imageHeight =
        (int) (
            $imageInformation[
                1
            ]
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

        inventoryRedirectError(
            "invalid_image"
        );

    }


    /* =====================================================
       IMAGE DIMENSION SAFETY
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

        inventoryRedirectError(
            "image_dimensions_too_large"
        );

    }


    /* =====================================================
       CHECK GD SUPPORT
    ===================================================== */

    if (
        !extension_loaded(
            "gd"
        )
    ) {

        inventoryRedirectError(
            "image_processing_unavailable"
        );

    }


    /* =====================================================
       PRODUCT IMAGE DIRECTORY
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
    ) {

        if (
            !mkdir(
                $uploadDirectory,
                0755,
                true
            )
        ) {

            inventoryRedirectError(
                "image_directory_error"
            );

        }

    }


    if (
        !is_writable(
            $uploadDirectory
        )
    ) {

        inventoryRedirectError(
            "image_directory_error"
        );

    }


    /* =====================================================
       SECURE RANDOM IMAGE NAME
    ===================================================== */

    $imageExtension =
        $allowedMimeTypes[
            $mimeType
        ];


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
            "Inventory random filename error: "
            .
            $error->getMessage()
        );


        inventoryRedirectError(
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
        $imageExtension;


    $physicalImagePath =
        $uploadDirectory
        .
        $filename;


    $databaseImagePath =
        "assets/products/"
        .
        $filename;


    /* =====================================================
       RESIZE / RE-ENCODE / COMPRESS IMAGE
    ===================================================== */

    $imageSaved =
        inventoryCreateOptimizedImage(

            $tempName,

            $mimeType,

            $physicalImagePath

        );


    if (
        !$imageSaved
    ) {

        if (
            is_string(
                $physicalImagePath
            )
            &&
            $physicalImagePath !== ""
            &&
            is_file(
                $physicalImagePath
            )
        ) {

            @unlink(
                $physicalImagePath
            );

        }


        inventoryRedirectError(
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
       BLOCK DUPLICATE PRODUCT NAMES (INCLUDING ARCHIVED)
    ===================================================== */

    $duplicateStmt = mysqli_prepare(
        $conn,
        "
        SELECT product_id
        FROM inventory
        WHERE LOWER(
            REGEXP_REPLACE(TRIM(product_name), '[[:space:]]+', ' ')
        ) = LOWER(
            REGEXP_REPLACE(TRIM(?), '[[:space:]]+', ' ')
        )
        LIMIT 1
        "
    );

    mysqli_stmt_bind_param(
        $duplicateStmt,
        "s",
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
       INSERT PRODUCT
    ===================================================== */

    $saveStmt =
        mysqli_prepare(

            $conn,

            "
            INSERT INTO inventory
            (
                product_name,
                acquired_price,
                markup_percent,
                selling_price,
                quantity,
                image
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
            "

        );


    mysqli_stmt_bind_param(

        $saveStmt,

        "sdddis",

        $productName,

        $acquiredPrice,

        $markupPercent,

        $sellingPrice,

        $quantity,

        $databaseImagePath

    );


    mysqli_stmt_execute(
        $saveStmt
    );


    $newProductID =
        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $saveStmt
    );


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    log_activity(

        $conn,

        "Inventory",

        "Added",

        'added the inventory product "'
        .
        $productName
        .
        '" with an initial quantity of '
        .
        $quantity
        .
        ' and selling price of ₱'
        .
        number_format(
            $sellingPrice,
            2
        )
        .
        ".",

        $newProductID

    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    $transactionStarted =
        false;


    /*
     * Rotate the token after a successful
     * consequential action.
     *
     * This also helps prevent accidental
     * repeated submission using the same form.
     */

    $_SESSION[
        "inventory_csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );


    /* =====================================================
       SUCCESS
    ===================================================== */

    header(
        "Location: inventory.php?success=added"
    );


    exit();

}
catch (
    Throwable $error
) {

    /* =====================================================
       ROLLBACK
    ===================================================== */

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
     * Remove the optimized image if
     * the database operation fails.
     */

    if (
        is_string(
            $physicalImagePath
        )
        &&
        $physicalImagePath !== ""
        &&
        is_file(
            $physicalImagePath
        )
    ) {

        @unlink(
            $physicalImagePath
        );

    }


    /*
     * Log technical details privately,
     * never display them to the user.
     */

    error_log(
        "Inventory Add Product error: "
        .
        $error->getMessage()
    );


    if (
        $error instanceof DomainException
        &&
        $error->getMessage() === "duplicate_product_name"
    ) {
        inventoryRedirectError("duplicate_product_name");
    }

    inventoryRedirectError(
        "save_failed"
    );

}