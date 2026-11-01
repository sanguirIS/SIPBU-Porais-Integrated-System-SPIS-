<?php

/* =========================================================
   PRODUCT LOAN - PRODUCT LIST - UPDATE PRODUCT
========================================================= */

if (
    session_status() ===
    PHP_SESSION_NONE
) {
    session_start();
}


date_default_timezone_set(
    "Asia/Manila"
);


error_reporting(
    E_ALL
);


ini_set(
    "display_errors",
    "0"
);


/* =========================================================
   SECURITY HEADERS
========================================================= */

header(
    "X-Content-Type-Options: nosniff"
);

header(
    "X-Frame-Options: SAMEORIGIN"
);

header(
    "Referrer-Policy: same-origin"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   RESPONSE HELPERS
========================================================= */

function loanProductUpdateIsAjax(): bool
{
    return strtolower(
        trim(
            (string) (
                $_SERVER[
                    "HTTP_X_REQUESTED_WITH"
                ]
                ?? ""
            )
        )
    ) ===
    "xmlhttprequest";
}


function loanProductUpdateRespond(
    bool $success,
    string $message,
    string $code,
    int $statusCode = 200,
    array $data = []
): never {

    if (
        loanProductUpdateIsAjax()
    ) {

        http_response_code(
            $statusCode
        );


        header(
            "Content-Type: application/json; charset=utf-8"
        );


        echo json_encode(
            array_merge(
                [
                    "success" =>
                        $success,

                    "message" =>
                        $message,

                    "code" =>
                        $code
                ],
                $data
            ),
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


        exit();
    }


    $parameter =
        $success
            ? "success"
            : "error";


    header(
        "Location: index.php?" .
        $parameter .
        "=" .
        rawurlencode(
            $code
        )
    );


    exit();
}


function loanProductUpdateNormalizeText(
    $value
): string {

    $value =
        trim(
            (string) $value
        );


    $value =
        preg_replace(
            '/[\x00-\x1F\x7F]/u',
            '',
            $value
        )
        ?? '';


    $value =
        preg_replace(
            '/\s+/u',
            ' ',
            $value
        )
        ?? $value;


    return trim(
        $value
    );
}


function loanProductUpdateTextLength(
    string $value
): int {

    return function_exists(
        "mb_strlen"
    )
        ? mb_strlen(
            $value,
            "UTF-8"
        )
        : strlen(
            $value
        );
}


function loanProductUpdateValidMoney(
    string $value
): ?float {

    if (
        !preg_match(
            '/^\d+(?:\.\d{1,2})?$/',
            $value
        )
    ) {

        return null;
    }


    $amount =
        round(
            (float) $value,
            2
        );


    if (
        !is_finite(
            $amount
        )
        ||
        $amount <= 0
        ||
        $amount >
            9999999999.99
    ) {

        return null;
    }


    return $amount;
}


function loanProductUpdateSafeImageBaseName(
    string $originalName
): string {

    $baseName =
        (string) pathinfo(
            basename(
                $originalName
            ),
            PATHINFO_FILENAME
        );


    $baseName =
        preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '-',
            $baseName
        )
        ?? '';


    $baseName =
        trim(
            $baseName,
            '-_'
        );


    if (
        $baseName === ''
    ) {

        $baseName =
            'product';
    }


    return substr(
        $baseName,
        0,
        80
    );
}


function loanProductUpdateValidStoredImagePath(
    string $imagePath
): bool {

    $normalized =
        str_replace(
            "\\",
            "/",
            trim(
                $imagePath
            )
        );


    if (
        $normalized === ""
    ) {

        return false;
    }


    if (
        str_contains(
            $normalized,
            ".."
        )
    ) {

        return false;
    }


    if (
        !str_starts_with(
            $normalized,
            "assets/lending/product_loan_products/"
        )
    ) {

        return false;
    }


    return basename(
        $normalized
    ) !== "";
}


function loanProductUpdatePhysicalImagePath(
    string $systemRoot,
    string $databaseImagePath
): ?string {

    if (
        !loanProductUpdateValidStoredImagePath(
            $databaseImagePath
        )
    ) {

        return null;
    }


    $normalized =
        str_replace(
            "\\",
            "/",
            trim(
                $databaseImagePath
            )
        );


    return
        $systemRoot .
        DIRECTORY_SEPARATOR .
        str_replace(
            "/",
            DIRECTORY_SEPARATOR,
            $normalized
        );
}


/* =========================================================
   REQUEST METHOD
========================================================= */

if (
    $_SERVER[
        "REQUEST_METHOD"
    ] !==
    "POST"
) {

    loanProductUpdateRespond(
        false,
        "Invalid request method.",
        "invalid_request",
        405
    );
}


/* =========================================================
   SESSION / ACCESS
========================================================= */

$userID =
    isset(
        $_SESSION[
            "user_id"
        ]
    )
        ? (int) $_SESSION[
            "user_id"
        ]
        : 0;


if (
    $userID <= 0
) {

    loanProductUpdateRespond(
        false,
        "Your session has expired. Please log in again.",
        "session_expired",
        401
    );
}


if (
    (int) (
        $_SESSION[
            "lending_access"
        ]
        ?? 0
    ) !== 1
) {

    loanProductUpdateRespond(
        false,
        "You do not have permission to access the Lending module.",
        "access_denied",
        403
    );
}


/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionToken =
    (string) (
        $_SESSION[
            "csrf_token"
        ]
        ?? ""
    );


$submittedToken =
    (string) (
        $_POST[
            "csrf_token"
        ]
        ?? ""
    );


if (
    $sessionToken === ""
    ||
    $submittedToken === ""
    ||
    !hash_equals(
        $sessionToken,
        $submittedToken
    )
) {

    loanProductUpdateRespond(
        false,
        "The security token is invalid or has expired. Refresh the page and try again.",
        "invalid_security_token",
        419
    );
}


/* =========================================================
   PRODUCT ID
========================================================= */

$productID =
    filter_var(
        $_POST[
            "loan_product_id"
        ]
        ?? null,
        FILTER_VALIDATE_INT
    );


if (
    $productID === false
    ||
    $productID === null
    ||
    $productID <= 0
) {

    loanProductUpdateRespond(
        false,
        "Invalid Product List record.",
        "invalid_product",
        422
    );
}


/* =========================================================
   FORM VALUES
========================================================= */

$productName =
    loanProductUpdateNormalizeText(
        $_POST[
            "product_name"
        ]
        ?? ""
    );


$loanPriceRaw =
    trim(
        (string) (
            $_POST[
                "loan_price"
            ]
            ?? ""
        )
    );


$removeImage =
    (
        (string) (
            $_POST[
                "remove_image"
            ]
            ?? "0"
        )
        === "1"
    );


/* =========================================================
   PRODUCT NAME VALIDATION
========================================================= */

if (
    $productName === ""
) {

    loanProductUpdateRespond(
        false,
        "Product name is required.",
        "product_name_required",
        422
    );
}


if (
    loanProductUpdateTextLength(
        $productName
    ) > 150
) {

    loanProductUpdateRespond(
        false,
        "Product name must not exceed 150 characters.",
        "product_name_too_long",
        422
    );
}


/* =========================================================
   PRICE VALIDATION
========================================================= */

$loanPrice =
    loanProductUpdateValidMoney(
        $loanPriceRaw
    );


if (
    $loanPrice === null
) {

    loanProductUpdateRespond(
        false,
        "Enter a valid Price greater than zero with up to two decimal places.",
        "invalid_loan_price",
        422
    );
}


/* =========================================================
   NEW IMAGE VALIDATION
========================================================= */

$newImagePath =
    null;


$newPhysicalImagePath =
    null;


$newImageWasMoved =
    false;


$imageFile =
    $_FILES[
        "image"
    ]
    ?? null;


$hasNewImage =
    is_array(
        $imageFile
    )
    &&
    isset(
        $imageFile[
            "error"
        ]
    )
    &&
    (int) $imageFile[
        "error"
    ] !== UPLOAD_ERR_NO_FILE;


if (
    $hasNewImage
) {

    $uploadError =
        (int) $imageFile[
            "error"
        ];


    if (
        $uploadError !==
        UPLOAD_ERR_OK
    ) {

        $message =
            in_array(
                $uploadError,
                [
                    UPLOAD_ERR_INI_SIZE,
                    UPLOAD_ERR_FORM_SIZE
                ],
                true
            )
                ? "The product image exceeds the server upload limit."
                : "The product image could not be uploaded.";


        loanProductUpdateRespond(
            false,
            $message,
            "image_upload_failed",
            422
        );
    }


    $imageSize =
        (int) (
            $imageFile[
                "size"
            ]
            ?? 0
        );


    if (
        $imageSize <= 0
        ||
        $imageSize >
            10 * 1024 * 1024
    ) {

        loanProductUpdateRespond(
            false,
            "The product image must be a valid file not larger than 10 MB.",
            "invalid_image_size",
            422
        );
    }


    $temporaryPath =
        (string) (
            $imageFile[
                "tmp_name"
            ]
            ?? ""
        );


    $originalName =
        basename(
            (string) (
                $imageFile[
                    "name"
                ]
                ?? ""
            )
        );


    if (
        $temporaryPath === ""
        ||
        $originalName === ""
        ||
        !is_uploaded_file(
            $temporaryPath
        )
    ) {

        loanProductUpdateRespond(
            false,
            "The selected product image is invalid.",
            "invalid_image",
            422
        );
    }


    $extension =
        strtolower(
            (string) pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );


    $allowedExtensions = [
        "jpg",
        "jpeg",
        "png",
        "webp"
    ];


    if (
        !in_array(
            $extension,
            $allowedExtensions,
            true
        )
    ) {

        loanProductUpdateRespond(
            false,
            "Only JPG, PNG, or WEBP product images are allowed.",
            "invalid_image_type",
            422
        );
    }


    if (
        !function_exists(
            "finfo_open"
        )
    ) {

        loanProductUpdateRespond(
            false,
            "The server cannot validate image files right now.",
            "image_validation_unavailable",
            500
        );
    }


    $fileInfo =
        finfo_open(
            FILEINFO_MIME_TYPE
        );


    if (
        !$fileInfo
    ) {

        loanProductUpdateRespond(
            false,
            "The server cannot validate image files right now.",
            "image_validation_unavailable",
            500
        );
    }


    $mimeType =
        finfo_file(
            $fileInfo,
            $temporaryPath
        );


    finfo_close(
        $fileInfo
    );


    $imageDetails =
        @getimagesize(
            $temporaryPath
        );


    $expectedMime =
        "";


    $storedExtension =
        "";


    if (
        $extension === "jpg"
        ||
        $extension === "jpeg"
    ) {

        $expectedMime =
            "image/jpeg";


        $storedExtension =
            "jpg";
    }
    elseif (
        $extension === "png"
    ) {

        $expectedMime =
            "image/png";


        $storedExtension =
            "png";
    }
    elseif (
        $extension === "webp"
    ) {

        $expectedMime =
            "image/webp";


        $storedExtension =
            "webp";
    }


    if (
        !is_array(
            $imageDetails
        )
        ||
        !is_string(
            $mimeType
        )
        ||
        $mimeType !==
            $expectedMime
        ||
        (string) (
            $imageDetails[
                "mime"
            ]
            ?? ""
        ) !==
            $expectedMime
    ) {

        loanProductUpdateRespond(
            false,
            "The selected file does not contain a valid JPG, PNG, or WEBP image.",
            "invalid_image_content",
            422
        );
    }


    $systemRoot =
        dirname(
            __DIR__,
            3
        );


    $uploadDirectory =
        $systemRoot .
        DIRECTORY_SEPARATOR .
        "assets" .
        DIRECTORY_SEPARATOR .
        "lending" .
        DIRECTORY_SEPARATOR .
        "product_loan_products";


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
        &&
        !is_dir(
            $uploadDirectory
        )
    ) {

        loanProductUpdateRespond(
            false,
            "Unable to create the Product List image folder.",
            "image_folder_error",
            500
        );
    }


    $safeBaseName =
        loanProductUpdateSafeImageBaseName(
            $originalName
        );


    $storedFileName =
        date(
            "YmdHis"
        ) .
        "_" .
        bin2hex(
            random_bytes(
                6
            )
        ) .
        "_" .
        $safeBaseName .
        "." .
        $storedExtension;


    $newPhysicalImagePath =
        $uploadDirectory .
        DIRECTORY_SEPARATOR .
        $storedFileName;


    $newImagePath =
        "assets/lending/product_loan_products/" .
        $storedFileName;
}


/* =========================================================
   DATABASE
========================================================= */

require_once
    "../../../db.php";


require_once
    "../../../includes/history_logger.php";


if (
    !isset(
        $conn
    )
    ||
    !(
        $conn instanceof mysqli
    )
) {

    loanProductUpdateRespond(
        false,
        "Database connection is unavailable.",
        "database_unavailable",
        500
    );
}


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   UPDATE VARIABLES
========================================================= */

$transactionStarted =
    false;


$oldImagePath =
    "";


$oldPhysicalImagePath =
    null;


$deleteOldImageAfterCommit =
    false;


/* =========================================================
   UPDATE PRODUCT
========================================================= */

try {

    mysqli_begin_transaction(
        $conn
    );


    $transactionStarted =
        true;


    /* =====================================================
       LOCK CURRENT PRODUCT
    ===================================================== */

    $currentStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                loan_product_id,
                product_name,
                loan_price,
                image

            FROM product_loan_products

            WHERE
                loan_product_id = ?
                AND archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );


    if (
        !$currentStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare the Product List record."
        );
    }


    mysqli_stmt_bind_param(
        $currentStatement,
        "i",
        $productID
    );


    if (
        !mysqli_stmt_execute(
            $currentStatement
        )
    ) {

        mysqli_stmt_close(
            $currentStatement
        );


        throw new RuntimeException(
            "Unable to load the Product List record."
        );
    }


    $currentResult =
        mysqli_stmt_get_result(
            $currentStatement
        );


    $currentProduct =
        $currentResult
            ? mysqli_fetch_assoc(
                $currentResult
            )
            : null;


    mysqli_stmt_close(
        $currentStatement
    );


    if (
        !$currentProduct
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        loanProductUpdateRespond(
            false,
            "The Product List item was not found or has already been archived.",
            "product_not_found",
            404
        );
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
                ?? ""
            )
        );


    $currentLoanPrice =
        round(
            (float) (
                $currentProduct[
                    "loan_price"
                ]
                ?? 0
            ),
            2
        );

    $oldImagePath =
        str_replace(
            "\\",
            "/",
            trim(
                (string) (
                    $currentProduct[
                        "image"
                    ]
                    ?? ""
                )
            )
        );


    /* =====================================================
       DUPLICATE ACTIVE PRODUCT NAME
    ===================================================== */

    $duplicateStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                loan_product_id

            FROM product_loan_products

            WHERE
                archived = 0
                AND loan_product_id <> ?
                AND LOWER(
                    TRIM(
                        product_name
                    )
                ) = LOWER(?)

            LIMIT 1

            FOR UPDATE
            "
        );


    if (
        !$duplicateStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare duplicate product validation."
        );
    }


    mysqli_stmt_bind_param(
        $duplicateStatement,
        "is",
        $productID,
        $productName
    );


    if (
        !mysqli_stmt_execute(
            $duplicateStatement
        )
    ) {

        mysqli_stmt_close(
            $duplicateStatement
        );


        throw new RuntimeException(
            "Unable to validate the product name."
        );
    }


    $duplicateResult =
        mysqli_stmt_get_result(
            $duplicateStatement
        );


    $duplicateProduct =
        $duplicateResult
            ? mysqli_fetch_assoc(
                $duplicateResult
            )
            : null;


    mysqli_stmt_close(
        $duplicateStatement
    );


    if (
        $duplicateProduct
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        loanProductUpdateRespond(
            false,
            "Another active Product List item already uses this product name.",
            "duplicate_product",
            409
        );
    }


    /* =====================================================
    PENDING PRODUCT LOAN RESERVATIONS

    Pending requests remain linked to the current
    product name.

    While a reservation exists:
    - the product name cannot be changed
    ===================================================== */

    $reservationStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                COALESCE(
                    SUM(
                        item.quantity
                    ),
                    0
                ) AS reserved_quantity

            FROM product_loan_items AS item

            INNER JOIN product_loans AS loan
                ON loan.product_loan_id =
                   item.product_loan_id

            WHERE
                loan.status = 'Pending'
                AND loan.is_archived = 0
                AND LOWER(
                    TRIM(
                        item.product_name
                    )
                ) =
                LOWER(
                    TRIM(?)
                )
            "
        );


    if (
        !$reservationStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare pending reservation validation."
        );
    }


    mysqli_stmt_bind_param(
        $reservationStatement,
        "s",
        $currentProductName
    );


    if (
        !mysqli_stmt_execute(
            $reservationStatement
        )
    ) {

        mysqli_stmt_close(
            $reservationStatement
        );


        throw new RuntimeException(
            "Unable to validate pending Product Loan reservations."
        );
    }


    $reservationResult =
        mysqli_stmt_get_result(
            $reservationStatement
        );


    $reservationRow =
        $reservationResult
            ? mysqli_fetch_assoc(
                $reservationResult
            )
            : null;


    mysqli_stmt_close(
        $reservationStatement
    );


    $reservedQuantity =
        max(
            0,
            (int) (
                $reservationRow[
                    "reserved_quantity"
                ]
                ?? 0
            )
        );


    if (
        $reservedQuantity > 0
    ) {

        $currentNormalizedName =
            function_exists(
                "mb_strtolower"
            )
                ? mb_strtolower(
                    trim(
                        $currentProductName
                    ),
                    "UTF-8"
                )
                : strtolower(
                    trim(
                        $currentProductName
                    )
                );


        $newNormalizedName =
            function_exists(
                "mb_strtolower"
            )
                ? mb_strtolower(
                    trim(
                        $productName
                    ),
                    "UTF-8"
                )
                : strtolower(
                    trim(
                        $productName
                    )
                );


        if (
            $currentNormalizedName !==
            $newNormalizedName
        ) {

            mysqli_rollback(
                $conn
            );


            $transactionStarted =
                false;


            loanProductUpdateRespond(
                false,
                "This product has " .
                number_format(
                    $reservedQuantity
                ) .
                " unit(s) reserved by Pending Product Loan request(s). The product name cannot be changed until those requests are Delivered or Rejected.",
                "product_has_pending_reservations",
                409
            );
        }

    }


    /* =====================================================
       MOVE NEW IMAGE
    ===================================================== */

    if (
        $hasNewImage
        &&
        $newPhysicalImagePath !== null
    ) {

        $temporaryPath =
            (string) (
                $imageFile[
                    "tmp_name"
                ]
                ?? ""
            );


        if (
            !move_uploaded_file(
                $temporaryPath,
                $newPhysicalImagePath
            )
        ) {

            throw new RuntimeException(
                "Unable to store the replacement product image."
            );
        }


        $newImageWasMoved =
            true;
    }


    /* =====================================================
       DETERMINE FINAL IMAGE

       New upload has priority over remove_image.
    ===================================================== */

    if (
        $hasNewImage
        &&
        $newImagePath !== null
    ) {

        $finalImagePath =
            $newImagePath;


        if (
            $oldImagePath !== ""
        ) {

            $deleteOldImageAfterCommit =
                true;
        }
    }
    elseif (
        $removeImage
    ) {

        $finalImagePath =
            "";


        if (
            $oldImagePath !== ""
        ) {

            $deleteOldImageAfterCommit =
                true;
        }
    }
    else {

        $finalImagePath =
            $oldImagePath;
    }


    /* =====================================================
       BUILD CHANGE LOG
    ===================================================== */

    $changes = [];


    if (
        $currentProductName !==
        $productName
    ) {

        $changes[] =
            'product name from "' .
            $currentProductName .
            '" to "' .
            $productName .
            '"';
    }


    if (
        abs(
            $currentLoanPrice -
            $loanPrice
        ) > 0.00001
    ) {

        $changes[] =
            'price from ₱' .
            number_format(
                $currentLoanPrice,
                2
            ) .
            ' to ₱' .
            number_format(
                $loanPrice,
                2
            );
    }


    if (
        $hasNewImage
    ) {

        $changes[] =
            $oldImagePath !== ""
                ? "replaced the product image"
                : "added a product image";
    }
    elseif (
        $removeImage
        &&
        $oldImagePath !== ""
    ) {

        $changes[] =
            "removed the product image";
    }


    /* =====================================================
       NO CHANGES
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


        loanProductUpdateRespond(
            true,
            "No Product List changes were needed.",
            "updated",
            200,
            [
                "loan_product_id" =>
                    $productID
            ]
        );
    }


    /* =====================================================
       UPDATE RECORD
    ===================================================== */

    $updateStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE product_loan_products

            SET
                product_name = ?,
                loan_price = ?,
                image = ?,
                updated_at = NOW()

            WHERE
                loan_product_id = ?
                AND archived = 0
            "
        );


    if (
        !$updateStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare the Product List update."
        );
    }


    mysqli_stmt_bind_param(
        $updateStatement,
        "sdsi",
        $productName,
        $loanPrice,
        $finalImagePath,
        $productID
    );


    if (
        !mysqli_stmt_execute(
            $updateStatement
        )
    ) {

        mysqli_stmt_close(
            $updateStatement
        );


        throw new RuntimeException(
            "Unable to update the Product List item."
        );
    }


    $affectedRows =
        mysqli_stmt_affected_rows(
            $updateStatement
        );


    mysqli_stmt_close(
        $updateStatement
    );


    if (
        $affectedRows < 0
    ) {

        throw new RuntimeException(
            "Unable to confirm the Product List update."
        );
    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    $displayProductID =
        "LP-" .
        str_pad(
            (string) $productID,
            4,
            "0",
            STR_PAD_LEFT
        );


    $historyMessage =
        'updated Product Loan product "' .
        $currentProductName .
        '" (' .
        $displayProductID .
        '): ' .
        implode(
            ', ',
            $changes
        ) .
        '.';


    log_activity(
        $conn,
        "Lending",
        "Updated",
        $historyMessage,
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
       DELETE OLD IMAGE AFTER SUCCESSFUL COMMIT
    ===================================================== */

    if (
        $deleteOldImageAfterCommit
        &&
        $oldImagePath !== ""
    ) {

        $systemRoot =
            dirname(
                __DIR__,
                3
            );


        $oldPhysicalImagePath =
            loanProductUpdatePhysicalImagePath(
                $systemRoot,
                $oldImagePath
            );


        if (
            $oldPhysicalImagePath !== null
            &&
            is_file(
                $oldPhysicalImagePath
            )
        ) {

            @unlink(
                $oldPhysicalImagePath
            );
        }
    }


    loanProductUpdateRespond(
        true,
        "The Product List item was updated successfully.",
        "updated",
        200,
        [
            "loan_product_id" =>
                $productID
        ]
    );
}


/* =========================================================
   ERROR / ROLLBACK
========================================================= */

catch (
    Throwable $exception
) {

    if (
        $transactionStarted
    ) {

        mysqli_rollback(
            $conn
        );
    }


    if (
        $newImageWasMoved
        &&
        $newPhysicalImagePath !== null
        &&
        is_file(
            $newPhysicalImagePath
        )
    ) {

        @unlink(
            $newPhysicalImagePath
        );
    }


    error_log(
        "Product List update error: " .
        $exception->getMessage()
    );


    loanProductUpdateRespond(
        false,
        "Unable to update the Product List item. Please try again.",
        "update_failed",
        500
    );
}