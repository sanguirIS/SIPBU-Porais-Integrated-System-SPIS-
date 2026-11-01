<?php

/* =========================================================
   RETRIEVE / RESTORE TRACTOR
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


error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);


mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


require_once "../db.php";
require_once "../includes/history_logger.php";


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
   REDIRECT HELPER
========================================================= */

function tractorRetrieveRedirect(
    string $type,
    string $value
): void {

    if (
        $type !== "success" &&
        $type !== "error"
    ) {

        $type =
            "error";

    }


    header(
        "Location: archive.php?module=tractor&" .
        $type .
        "=" .
        rawurlencode(
            $value
        )
    );


    exit;

}


/* =========================================================
   POST ONLY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "POST"
) {

    http_response_code(405);


    tractorRetrieveRedirect(
        "error",
        "invalid_request"
    );

}


/* =========================================================
   CURRENT USER
========================================================= */

$currentUserID =
    isset(
        $_SESSION["user_id"]
    )
        ? (int) $_SESSION["user_id"]
        : 0;


if ($currentUserID <= 0) {

    http_response_code(401);


    tractorRetrieveRedirect(
        "error",
        "session"
    );

}


/*
 * Restoring a tractor is a Rental Registry action
 * performed through the Archive module.
 *
 * Require both permissions.
 */

if (
    (int) (
        $_SESSION[
            "archives_access"
        ]
        ?? 0
    ) !== 1
    ||
    (int) (
        $_SESSION[
            "rental_access"
        ]
        ?? 0
    ) !== 1
) {

    http_response_code(403);


    tractorRetrieveRedirect(
        "error",
        "forbidden"
    );

}


/* =========================================================
   CSRF
========================================================= */

$sessionCsrfToken =
    (string) (
        $_SESSION[
            "tractor_csrf_token"
        ]
        ?? ""
    );


$requestCsrfToken =
    (string) (
        $_POST[
            "csrf_token"
        ]
        ?? ""
    );


if (
    $sessionCsrfToken === ""
    ||
    $requestCsrfToken === ""
    ||
    !hash_equals(
        $sessionCsrfToken,
        $requestCsrfToken
    )
) {

    http_response_code(419);


    tractorRetrieveRedirect(
        "error",
        "csrf"
    );

}


/* =========================================================
   TARGET TRACTOR ID
========================================================= */

$tractorID =
    isset(
        $_POST["tractor_id"]
    )
        ? filter_var(
            $_POST["tractor_id"],
            FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1
                ]
            ]
        )
        : false;


if ($tractorID === false) {

    tractorRetrieveRedirect(
        "error",
        "invalid_tractor"
    );

}


$tractorID =
    (int) $tractorID;


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
       VERIFY ACTOR FROM DATABASE
    ===================================================== */

    $actorStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                name,
                status,
                archived,
                archives_access,
                rental_access

            FROM users

            WHERE user_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );


    mysqli_stmt_bind_param(
        $actorStatement,
        "i",
        $currentUserID
    );


    mysqli_stmt_execute(
        $actorStatement
    );


    $actorResult =
        mysqli_stmt_get_result(
            $actorStatement
        );


    $actor =
        mysqli_fetch_assoc(
            $actorResult
        );


    mysqli_stmt_close(
        $actorStatement
    );


    if (
        !$actor
        ||
        (int) (
            $actor["archived"]
            ?? 1
        ) !== 0
        ||
        (
            $actor["status"]
            ?? ""
        ) !== "Active"
        ||
        (int) (
            $actor[
                "archives_access"
            ]
            ?? 0
        ) !== 1
        ||
        (int) (
            $actor[
                "rental_access"
            ]
            ?? 0
        ) !== 1
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        http_response_code(403);


        tractorRetrieveRedirect(
            "error",
            "forbidden"
        );

    }


    /* =====================================================
       LOCK ARCHIVED TRACTOR
    ===================================================== */

    $tractorStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                tractor_id,
                tractor_no,
                tractor_name,
                tractor_type,
                status,
                archived

            FROM tractors

            WHERE tractor_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );


    mysqli_stmt_bind_param(
        $tractorStatement,
        "i",
        $tractorID
    );


    mysqli_stmt_execute(
        $tractorStatement
    );


    $tractorResult =
        mysqli_stmt_get_result(
            $tractorStatement
        );


    $tractor =
        mysqli_fetch_assoc(
            $tractorResult
        );


    mysqli_stmt_close(
        $tractorStatement
    );


    if (!$tractor) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        tractorRetrieveRedirect(
            "error",
            "tractor_not_found"
        );

    }


    if (
        (int) (
            $tractor["archived"]
            ?? 0
        ) !== 1
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        tractorRetrieveRedirect(
            "error",
            "tractor_not_archived"
        );

    }


    /* =====================================================
       TRACTOR SNAPSHOT
    ===================================================== */

    $tractorNumber =
        trim(
            (string) (
                $tractor["tractor_no"]
                ?? "Unknown Tractor"
            )
        );


    $tractorName =
        trim(
            (string) (
                $tractor["tractor_name"]
                ?? ""
            )
        );


    $tractorType =
        trim(
            (string) (
                $tractor["tractor_type"]
                ?? "Unknown Type"
            )
        );


    /* =====================================================
       OPTIONAL ARCHIVE COLUMNS
    ===================================================== */

    $hasArchivedBy =
        false;

    $hasArchivedAt =
        false;


    $columnQuery =
        mysqli_query(
            $conn,
            "SHOW COLUMNS FROM tractors"
        );


    while (
        $column =
            mysqli_fetch_assoc(
                $columnQuery
            )
    ) {

        if (
            (
                $column["Field"]
                ??
                ""
            ) ===
            "archived_by"
        ) {

            $hasArchivedBy =
                true;

        }


        if (
            (
                $column["Field"]
                ??
                ""
            ) ===
            "archived_at"
        ) {

            $hasArchivedAt =
                true;

        }

    }


    /* =====================================================
       BUILD RESTORE QUERY

       Preserve physical status:
       Available stays Available.
       Maintenance stays Maintenance.
    ===================================================== */

    $restoreFields = [

        "archived = 0"

    ];


    if ($hasArchivedBy) {

        $restoreFields[] =
            "archived_by = NULL";

    }


    if ($hasArchivedAt) {

        $restoreFields[] =
            "archived_at = NULL";

    }


    $restoreSQL =
        "
        UPDATE tractors

        SET
        "
        .
        implode(
            ",\n",
            $restoreFields
        )
        .
        "

        WHERE tractor_id = ?

        AND archived = 1
        ";


    /* =====================================================
       RESTORE TRACTOR
    ===================================================== */

    $restoreStatement =
        mysqli_prepare(
            $conn,
            $restoreSQL
        );


    mysqli_stmt_bind_param(
        $restoreStatement,
        "i",
        $tractorID
    );


    mysqli_stmt_execute(
        $restoreStatement
    );


    $affectedRows =
        mysqli_stmt_affected_rows(
            $restoreStatement
        );


    mysqli_stmt_close(
        $restoreStatement
    );


    if ($affectedRows !== 1) {

        throw new RuntimeException(
            "Tractor restore affected an unexpected number of rows."
        );

    }


    /* =====================================================
       HISTORY
    ===================================================== */

    log_activity(
        $conn,
        "Rental",
        "Restored",
        'restored the ' .
        $tractorType .
        ' tractor "' .
        $tractorNumber .
        (
            $tractorName !== ""
                ? " - " . $tractorName
                : ""
        ) .
        '".',
        $tractorID
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
       ROTATE CSRF
    ===================================================== */

    $_SESSION[
        "tractor_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );


    tractorRetrieveRedirect(
        "success",
        "restored"
    );

}
catch (Throwable $error) {

    if ($transactionStarted) {

        try {

            mysqli_rollback(
                $conn
            );

        }
        catch (Throwable $rollbackError) {

            /*
             * Never expose database
             * information to the user.
             */

        }

    }


    error_log(
        "SPIS retrieve_tractor.php: " .
        $error->getMessage()
    );


    tractorRetrieveRedirect(
        "error",
        "tractor_retrieve_failed"
    );

}