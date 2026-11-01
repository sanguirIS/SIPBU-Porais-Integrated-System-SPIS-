<?php

/* =========================================================
   RETRIEVE INVENTORY PRODUCT
========================================================= */

if (
    session_status()
    ===
    PHP_SESSION_NONE
) {
    session_start();
}


/* =========================================================
   ACCESS CONTROL
========================================================= */

require "../includes/check_access.php";

checkAccess(
    "archives_access"
);

checkAccess(
    "inventory_access"
);


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set(
    "Asia/Manila"
);


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
   REDIRECT HELPER
========================================================= */

function inventoryRetrieveRedirect(
    string $error = ""
): void {

    $location =
        "archive.php?module=inventory";


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
   POST REQUEST ONLY
========================================================= */

if (
    (
        $_SERVER[
            "REQUEST_METHOD"
        ]
        ??
        ""
    )
    !==
    "POST"
) {

    inventoryRetrieveRedirect(
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
                "archive_inventory_csrf_token"
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

    inventoryRetrieveRedirect(
        "invalid_token"
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

    inventoryRetrieveRedirect(
        "invalid_product"
    );

}


$productID =
    (int) $productID;


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {

    /* =====================================================
       GET AND LOCK ARCHIVED PRODUCT
    ===================================================== */

    $productStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                product_id,
                product_name,
                archived
            FROM inventory
            WHERE product_id = ?
            AND archived = 1
            LIMIT 1
            FOR UPDATE
            "
        );


    if (
        !$productStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare archived product check."
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
            "Unable to check archived product."
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


        inventoryRetrieveRedirect(
            "product_not_found"
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


    /* =====================================================
       RESTORE PRODUCT
    ===================================================== */

    $restoreStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE inventory

            SET
                archived = 0,
                archived_by = NULL

            WHERE product_id = ?
            AND archived = 1
            "
        );


    if (
        !$restoreStatement
    ) {

        throw new RuntimeException(
            "Unable to prepare product restore."
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
            "Unable to restore product."
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
            "The archived product was not restored."
        );

    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    log_activity(
        $conn,
        "Archive",
        "Restored",
        'restored the inventory product "'
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


    /* =====================================================
       ROTATE CSRF TOKEN
    ===================================================== */

    $_SESSION[
        "archive_inventory_csrf_token"
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
        "Location: archive.php?module=inventory&success=restored",
        true,
        303
    );

    exit();

}


/* =========================================================
   FAILURE
========================================================= */

catch (
    Throwable $error
) {

    mysqli_rollback(
        $conn
    );


    error_log(
        "Inventory product retrieve error: "
        .
        $error->getMessage()
    );


    inventoryRetrieveRedirect(
        "restore_failed"
    );

}

?>