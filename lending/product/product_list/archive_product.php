<?php

/* =========================================================
   PRODUCT LOAN - PRODUCT LIST - ARCHIVE PRODUCT
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

function loanProductArchiveIsAjax(): bool
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


function loanProductArchiveRespond(
    bool $success,
    string $message,
    string $code,
    int $statusCode = 200,
    array $data = []
): never {

    if (
        loanProductArchiveIsAjax()
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


/* =========================================================
   REQUEST METHOD
========================================================= */

if (
    $_SERVER[
        "REQUEST_METHOD"
    ] !==
    "POST"
) {

    loanProductArchiveRespond(
        false,
        "Invalid request method.",
        "invalid_request",
        405
    );
}


/* =========================================================
   AUTHENTICATION
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

    loanProductArchiveRespond(
        false,
        "Your session has expired. Please log in again.",
        "session_expired",
        401
    );
}


/* =========================================================
   LENDING ACCESS
========================================================= */

if (
    (int) (
        $_SESSION[
            "lending_access"
        ]
        ?? 0
    ) !== 1
) {

    loanProductArchiveRespond(
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

    loanProductArchiveRespond(
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

    loanProductArchiveRespond(
        false,
        "Invalid Product List record.",
        "invalid_product",
        422
    );
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

    loanProductArchiveRespond(
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
   ARCHIVE PRODUCT
========================================================= */

try {

    mysqli_begin_transaction(
        $conn
    );


    $transactionStarted =
        true;


    /* =====================================================
       LOCK CURRENT ACTIVE PRODUCT
    ===================================================== */

    $productStatement =
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
        !$productStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare the Product List record."
        );
    }


    mysqli_stmt_bind_param(
        $productStatement,
        "i",
        $productID
    );


    if (
        !mysqli_stmt_execute(
            $productStatement
        )
    ) {

        mysqli_stmt_close(
            $productStatement
        );


        throw new RuntimeException(
            "Unable to load the Product List record."
        );
    }


    $productResult =
        mysqli_stmt_get_result(
            $productStatement
        );


    $product =
        $productResult
            ? mysqli_fetch_assoc(
                $productResult
            )
            : null;


    mysqli_stmt_close(
        $productStatement
    );


    if (
        !$product
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        loanProductArchiveRespond(
            false,
            "The Product List item was not found or has already been archived.",
            "product_not_found",
            404
        );
    }


    /* =====================================================
       CURRENT PRODUCT INFORMATION
    ===================================================== */

    $productName =
        trim(
            (string) (
                $product[
                    "product_name"
                ]
                ?? ""
            )
        );


    if (
        $productName === ""
    ) {

        $productName =
            "Unnamed Product";
    }


    $loanPrice =
        round(
            (float) (
                $product[
                    "loan_price"
                ]
                ?? 0
            ),
            2
        );

    /* =====================================================
       PENDING PRODUCT LOAN RESERVATION CHECK

       A product with Pending requests must stay active so
       those reservations remain traceable and deliverable.
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
        $productName
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

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        loanProductArchiveRespond(
            false,
            $productName .
            " still has " .
            number_format(
                $reservedQuantity
            ) .
            " unit(s) reserved by Pending Product Loan request(s). Deliver or reject those requests before archiving this product.",
            "product_has_pending_reservations",
            409
        );
    }


    /* =====================================================
       SOFT ARCHIVE

       Do not delete the image or Product List record.
       The Archive module will later retrieve this record.
    ===================================================== */

    $archiveStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE product_loan_products

            SET
                archived = 1,
                updated_at = NOW()

            WHERE
                loan_product_id = ?
                AND archived = 0
            "
        );


    if (
        !$archiveStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare the Product List archive request."
        );
    }


    mysqli_stmt_bind_param(
        $archiveStatement,
        "i",
        $productID
    );


    if (
        !mysqli_stmt_execute(
            $archiveStatement
        )
    ) {

        mysqli_stmt_close(
            $archiveStatement
        );


        throw new RuntimeException(
            "Unable to archive the Product List item."
        );
    }


    $affectedRows =
        mysqli_stmt_affected_rows(
            $archiveStatement
        );


    mysqli_stmt_close(
        $archiveStatement
    );


    if (
        $affectedRows !== 1
    ) {

        throw new RuntimeException(
            "The Product List item was not archived."
        );
    }


    /* =====================================================
       HISTORY
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
        'archived Product Loan product "' .
        $productName .
        '" (' .
        $displayProductID .
        ') from the Product List. Price ₱' .
        number_format(
            $loanPrice,
            2
        ) .
        '.';


    log_activity(
        $conn,
        "Lending",
        "Archived",
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


    loanProductArchiveRespond(
        true,
        $productName .
        " was moved to Archive.",
        "archived",
        200,
        [
            "loan_product_id" =>
                $productID,

            "product_name" =>
                $productName
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


    error_log(
        "Product List archive error: " .
        $exception->getMessage()
    );


    loanProductArchiveRespond(
        false,
        "Unable to archive the Product List item. Please try again.",
        "archive_failed",
        500
    );
}