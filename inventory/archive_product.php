<?php

/* =========================================================
   SPIS - ARCHIVE INVENTORY PRODUCT
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
   ERROR REDIRECT
========================================================= */

function inventoryArchiveError(
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
   POST REQUEST ONLY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"]
    !==
    "POST"
) {

    inventoryArchiveError(
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

    inventoryArchiveError(
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

    inventoryArchiveError(
        "invalid_product"
    );

}


/* =========================================================
   USER WHO ARCHIVED THE PRODUCT
========================================================= */

$archivedBy =
    trim(
        (string) (
            $_SESSION[
                "name"
            ]
            ??
            "System User"
        )
    );


if (
    $archivedBy === ""
) {

    $archivedBy =
        "System User";

}


/* =========================================================
   TRANSACTION
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
       LOCK CURRENT PRODUCT RECORD

       Prevents archiving an already archived,
       missing, or concurrently changed record.
    ===================================================== */

    $productStmt =
        mysqli_prepare(

            $conn,

            "
            SELECT
                product_id,
                product_name

            FROM inventory

            WHERE product_id = ?

            AND archived = 0

            LIMIT 1

            FOR UPDATE
            "

        );


    mysqli_stmt_bind_param(

        $productStmt,

        "i",

        $productID

    );


    mysqli_stmt_execute(
        $productStmt
    );


    $productResult =
        mysqli_stmt_get_result(
            $productStmt
        );


    $product =
        $productResult
        ?
        mysqli_fetch_assoc(
            $productResult
        )
        :
        null;


    mysqli_stmt_close(
        $productStmt
    );


    if (
        !$product
    ) {

        throw new RuntimeException(
            "Product was not found or is already archived."
        );

    }


    $productName =
        trim(
            (string) (
                $product[
                    "product_name"
                ]
                ??
                "Unknown Product"
            )
        );


    /* =====================================================
       ARCHIVE PRODUCT

       The product image is intentionally NOT deleted.
       It remains available when the record is restored.
    ===================================================== */

    $archiveStmt =
        mysqli_prepare(

            $conn,

            "
            UPDATE inventory

            SET
                archived = 1,
                archived_by = ?

            WHERE product_id = ?

            AND archived = 0
            "

        );


    mysqli_stmt_bind_param(

        $archiveStmt,

        "si",

        $archivedBy,

        $productID

    );


    mysqli_stmt_execute(
        $archiveStmt
    );


    $affectedRows =
        mysqli_stmt_affected_rows(
            $archiveStmt
        );


    mysqli_stmt_close(
        $archiveStmt
    );


    if (
        $affectedRows !== 1
    ) {

        throw new RuntimeException(
            "Archive action did not affect the expected record."
        );

    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    log_activity(

        $conn,

        "Inventory",

        "Archived",

        'archived the inventory product "'
        .
        $productName
        .
        '".',

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
            "Inventory archive CSRF rotation error: "
            .
            $tokenError->getMessage()
        );

    }


    /* =====================================================
       SUCCESS
    ===================================================== */

    header(
        "Location: inventory.php?success=archived"
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
                "Inventory archive rollback error: "
                .
                $rollbackError->getMessage()
            );

        }

    }


    /*
     * Technical error stays in PHP log,
     * not exposed to the user.
     */

    error_log(
        "Inventory Archive Product error: "
        .
        $error->getMessage()
    );


    inventoryArchiveError(
        "archive_failed"
    );

}