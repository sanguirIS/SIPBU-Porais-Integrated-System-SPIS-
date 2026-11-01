<?php

/* =========================================================
   PRODUCT LOAN - PRODUCT LIST - SAVE PRODUCT
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");
error_reporting(E_ALL);
ini_set("display_errors", "0");

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


/* =========================================================
   RESPONSE HELPERS
========================================================= */

function loanProductSaveIsAjax(): bool
{
    return strtolower(
        trim(
            (string) (
                $_SERVER["HTTP_X_REQUESTED_WITH"]
                ?? ""
            )
        )
    ) === "xmlhttprequest";
}


function loanProductSaveRespond(
    bool $success,
    string $message,
    string $code,
    int $statusCode = 200,
    array $data = []
): never {

    if (loanProductSaveIsAjax()) {

        http_response_code($statusCode);

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


function loanProductSaveNormalizeText(
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


function loanProductSaveTextLength(
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


function loanProductSaveValidMoney(
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


function loanProductSaveSafeImageBaseName(
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


/* =========================================================
   REQUEST / SESSION SECURITY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    !==
    "POST"
) {

    loanProductSaveRespond(
        false,
        "Invalid request method.",
        "invalid_request",
        405
    );
}


$userID =
    isset(
        $_SESSION["user_id"]
    )
        ? (int) $_SESSION["user_id"]
        : 0;


if (
    $userID <= 0
) {

    loanProductSaveRespond(
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

    loanProductSaveRespond(
        false,
        "You do not have permission to access the Lending module.",
        "access_denied",
        403
    );
}


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

    loanProductSaveRespond(
        false,
        "The security token is invalid or has expired. Refresh the page and try again.",
        "invalid_security_token",
        419
    );
}


/* =========================================================
   FORM VALUES
========================================================= */

$productName =
    loanProductSaveNormalizeText(
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


/* =========================================================
   FIELD VALIDATION
========================================================= */

if (
    $productName === ""
) {

    loanProductSaveRespond(
        false,
        "Product name is required.",
        "product_name_required",
        422
    );
}


if (
    loanProductSaveTextLength(
        $productName
    ) > 150
) {

    loanProductSaveRespond(
        false,
        "Product name must not exceed 150 characters.",
        "product_name_too_long",
        422
    );
}


$loanPrice =
    loanProductSaveValidMoney(
        $loanPriceRaw
    );


if (
    $loanPrice === null
) {

    loanProductSaveRespond(
        false,
        "Enter a valid Price greater than zero with up to two decimal places.",
        "invalid_loan_price",
        422
    );
}


/* =========================================================
   OPTIONAL IMAGE VALIDATION
========================================================= */

$imagePath =
    "";


$physicalImagePath =
    null;


$imageWasMoved =
    false;


$imageFile =
    $_FILES["image"]
    ?? null;


if (
    is_array(
        $imageFile
    )
    &&
    isset(
        $imageFile["error"]
    )
    &&
    (int) $imageFile["error"]
        !== UPLOAD_ERR_NO_FILE
) {

    $uploadError =
        (int) $imageFile["error"];


    if (
        $uploadError
        !==
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


        loanProductSaveRespond(
            false,
            $message,
            "image_upload_failed",
            422
        );
    }


    $imageSize =
        (int) (
            $imageFile["size"]
            ?? 0
        );


    if (
        $imageSize <= 0
        ||
        $imageSize >
            10 * 1024 * 1024
    ) {

        loanProductSaveRespond(
            false,
            "The product image must be a valid file not larger than 10 MB.",
            "invalid_image_size",
            422
        );
    }


    $temporaryPath =
        (string) (
            $imageFile["tmp_name"]
            ?? ""
        );


    $originalName =
        basename(
            (string) (
                $imageFile["name"]
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

        loanProductSaveRespond(
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

        loanProductSaveRespond(
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

        loanProductSaveRespond(
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

        loanProductSaveRespond(
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
            $imageDetails["mime"]
            ?? ""
        ) !==
            $expectedMime
    ) {

        loanProductSaveRespond(
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

        loanProductSaveRespond(
            false,
            "Unable to create the Product List image folder.",
            "image_folder_error",
            500
        );
    }


    $safeBaseName =
        loanProductSaveSafeImageBaseName(
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


    $physicalImagePath =
        $uploadDirectory .
        DIRECTORY_SEPARATOR .
        $storedFileName;


    $imagePath =
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
    !isset($conn)
    ||
    !($conn instanceof mysqli)
) {

    loanProductSaveRespond(
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


$transactionStarted =
    false;


/* =========================================================
   SAVE PRODUCT
========================================================= */

try {

    mysqli_begin_transaction(
        $conn
    );


    $transactionStarted =
        true;


    /* =====================================================
       PREVENT DUPLICATE ACTIVE PRODUCT NAME
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
        "s",
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
            "Unable to validate duplicate Product List items."
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


        loanProductSaveRespond(
            false,
            "An active Product List item with this product name already exists.",
            "duplicate_product",
            409
        );
    }


    /* =====================================================
       STORE OPTIONAL IMAGE
    ===================================================== */

    if (
        $physicalImagePath !== null
    ) {

        $temporaryPath =
            (string) (
                $imageFile["tmp_name"]
                ?? ""
            );


        if (
            !move_uploaded_file(
                $temporaryPath,
                $physicalImagePath
            )
        ) {

            throw new RuntimeException(
                "Unable to store the product image."
            );
        }


        $imageWasMoved =
            true;
    }


    /* =====================================================
       INSERT PRODUCT

       acquired_price and markup_percent remain as zero-value
       compatibility fields in the current database schema.
       Product Loan pricing uses loan_price.
    ===================================================== */

    $legacyAcquiredPrice =
        0.00;


    $legacyMarkupPercent =
        0.00;

    $legacyQuantity =
        0;    


    $insertStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO product_loan_products (

                product_name,
                acquired_price,
                markup_percent,
                loan_price,
                quantity,
                image,
                archived

            ) VALUES (

                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                0

            )
            "
        );


    if (
        !$insertStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare the Product List record."
        );
    }


    mysqli_stmt_bind_param(
        $insertStatement,
        "sdddis",
        $productName,
        $legacyAcquiredPrice,
        $legacyMarkupPercent,
        $loanPrice,
        $legacyQuantity,
        $imagePath
    );


    if (
        !mysqli_stmt_execute(
            $insertStatement
        )
    ) {

        mysqli_stmt_close(
            $insertStatement
        );


        throw new RuntimeException(
            "Unable to save the Product List item."
        );
    }


    $loanProductID =
        (int) mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $insertStatement
    );


    /* =====================================================
       HISTORY
    ===================================================== */

    $historyMessage =
        'added Product Loan product "' .
        $productName .
        '" (LP-' .
        str_pad(
            (string) $loanProductID,
            4,
            '0',
            STR_PAD_LEFT
        ) .
        ') to the Product List with price ₱' .
        number_format(
            $loanPrice,
            2
        ) .
        '.';


    log_activity(
        $conn,
        "Lending",
        "Added",
        $historyMessage,
        $loanProductID
    );


    mysqli_commit(
        $conn
    );


    $transactionStarted =
        false;


    loanProductSaveRespond(
        true,
        "The Product List item was added successfully.",
        "added",
        201,
        [
            "loan_product_id" =>
                $loanProductID
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
        $imageWasMoved
        &&
        $physicalImagePath !== null
        &&
        is_file(
            $physicalImagePath
        )
    ) {

        @unlink(
            $physicalImagePath
        );
    }


    error_log(
        "Product List save error: " .
        $exception->getMessage()
    );


    loanProductSaveRespond(
        false,
        "Unable to save the Product List item. Please try again.",
        "save_failed",
        500
    );
}