<?php

/* =========================================================
   RETRIEVE PRODUCT LIST ITEM
========================================================= */

if (
    session_status()
    ===
    PHP_SESSION_NONE
) {
    session_start();
}


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set(
    "Asia/Manila"
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
   REDIRECT HELPER
========================================================= */

function productListRetrieveRedirect(
    string $error = ""
): never {

    $location =
        "archive.php?module=product_list";


    if (
        $error !== ""
    ) {

        $location .=
            "&error="
            .
            rawurlencode(
                $error
            );

    }


    header(
        "Location: "
        .
        $location,
        true,
        303
    );


    exit();
}


/* =========================================================
   ACCESS CONTROL
========================================================= */

require "../includes/check_access.php";

checkAccess(
    "archives_access"
);


$userID =
    (int) (
        $_SESSION[
            "user_id"
        ]
        ??
        0
    );


if (
    $userID <= 0
) {

    productListRetrieveRedirect(
        "session"
    );

}


if (
    (int) (
        $_SESSION[
            "lending_access"
        ]
        ??
        0
    ) !== 1
) {

    productListRetrieveRedirect(
        "forbidden"
    );

}


/* =========================================================
   POST REQUEST ONLY
========================================================= */

if (
    (
        $_SERVER[
            "REQUEST_METHOD"
        ]
        ??
        ""
    ) !==
    "POST"
) {

    productListRetrieveRedirect(
        "invalid_request"
    );

}


/* =========================================================
   CSRF VALIDATION
========================================================= */

$submittedToken =
    trim(
        (string) (
            $_POST[
                "csrf_token"
            ]
            ??
            ""
        )
    );


$sessionToken =
    trim(
        (string) (
            $_SESSION[
                "archive_product_list_csrf_token"
            ]
            ??
            ""
        )
    );


if (
    $submittedToken === ""
    ||
    $sessionToken === ""
    ||
    !hash_equals(
        $sessionToken,
        $submittedToken
    )
) {

    productListRetrieveRedirect(
        "csrf"
    );

}


/* =========================================================
   PRODUCT LIST ID
========================================================= */

$productID =
    filter_var(
        $_POST[
            "loan_product_id"
        ]
        ??
        null,
        FILTER_VALIDATE_INT,
        [
            "options" => [
                "min_range" => 1
            ]
        ]
    );


if (
    $productID === false
) {

    productListRetrieveRedirect(
        "invalid_product_list_product"
    );

}


$productID =
    (int) $productID;


/* =========================================================
   DATABASE + HISTORY
========================================================= */

include "../db.php";
include "../includes/history_logger.php";


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   START TRANSACTION
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
       GET AND LOCK ARCHIVED PRODUCT LIST ITEM
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
                AND archived = 1
            LIMIT 1
            FOR UPDATE
            "
        );


    if (
        !$productStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare archived Product List item check."
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
            "Unable to check archived Product List item."
        );

    }


    $productResult =
        mysqli_stmt_get_result(
            $productStatement
        );


    if (
        !$productResult
        ||
        mysqli_num_rows(
            $productResult
        ) !== 1
    ) {

        mysqli_stmt_close(
            $productStatement
        );


        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        productListRetrieveRedirect(
            "product_list_product_not_found"
        );

    }


    $product =
        mysqli_fetch_assoc(
            $productResult
        );


    mysqli_stmt_close(
        $productStatement
    );


    $productName =
        trim(
            (string) (
                $product[
                    "product_name"
                ]
                ??
                ""
            )
        );


    if (
        $productName === ""
    ) {

        $productName =
            "Unnamed Product";

    }


    $loanPrice =
        max(
            0,
            (float) (
                $product[
                    "loan_price"
                ]
                ??
                0
            )
        );


    /* =====================================================
       PREVENT DUPLICATE ACTIVE PRODUCT NAME

       Product List names are treated as the catalog identity
       in important Product Loan paths, so restoring an item
       must not create two active products with the same name.
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
                ) = LOWER(
                    TRIM(?)
                )
            LIMIT 1
            FOR UPDATE
            "
        );


    if (
        !$duplicateStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare Product List duplicate validation."
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
            "Unable to validate active Product List duplicates."
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


        productListRetrieveRedirect(
            "product_list_duplicate"
        );

    }


    /* =====================================================
       RESTORE PRODUCT LIST ITEM

       Wide-system source-of-truth rule:
       restore only the canonical Product List record.
       Do not rewrite Product Loan transaction snapshots and
       do not touch the legacy physical quantity column.
    ===================================================== */

    $restoreStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE product_loan_products
            SET
                archived = 0,
                updated_at = NOW()
            WHERE
                loan_product_id = ?
                AND archived = 1
            "
        );


    if (
        !$restoreStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare Product List restore."
        );

    }


    mysqli_stmt_bind_param(
        $restoreStatement,
        "i",
        $productID
    );


    if (
        !mysqli_stmt_execute(
            $restoreStatement
        )
    ) {

        mysqli_stmt_close(
            $restoreStatement
        );


        throw new RuntimeException(
            "Unable to restore Product List item."
        );

    }


    $affectedRows =
        mysqli_stmt_affected_rows(
            $restoreStatement
        );


    mysqli_stmt_close(
        $restoreStatement
    );


    if (
        $affectedRows !== 1
    ) {

        throw new RuntimeException(
            "The archived Product List item was not restored."
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
        'restored Product Loan product "' .
        $productName .
        '" (' .
        $displayProductID .
        ') to the Product List. Price ₱' .
        number_format(
            $loanPrice,
            2
        ) .
        '.';


    log_activity(
        $conn,
        "Lending",
        "Restored",
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
       ROTATE CSRF TOKEN
    ===================================================== */

    $_SESSION[
        "archive_product_list_csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );


    /* =====================================================
       SUCCESS REDIRECT
    ===================================================== */

    header(
        "Location: archive.php?module=product_list&success=restored",
        true,
        303
    );


    exit();

}


/* =========================================================
   FAILURE / ROLLBACK
========================================================= */

catch (
    Throwable $error
) {

    if (
        $transactionStarted
    ) {

        mysqli_rollback(
            $conn
        );

    }


    error_log(
        "Product List retrieve error: "
        .
        $error->getMessage()
    );


    productListRetrieveRedirect(
        "retrieve_failed"
    );

}

?>