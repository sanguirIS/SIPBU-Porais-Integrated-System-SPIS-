<?php

/* =========================================================
   RETRIEVE / RESTORE ACCOUNT
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

function accountRetrieveRedirect(
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
        "Location: archive.php?module=account&" .
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


    accountRetrieveRedirect(
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


    accountRetrieveRedirect(
        "error",
        "session"
    );

}


/*
 * Restoring an account is an Account
 * Management action performed through
 * the Archive module.
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
            "account_access"
        ]
        ?? 0
    ) !== 1
) {

    http_response_code(403);


    accountRetrieveRedirect(
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
            "account_csrf_token"
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


    accountRetrieveRedirect(
        "error",
        "csrf"
    );

}


/* =========================================================
   TARGET ACCOUNT ID
========================================================= */

$userID =
    isset(
        $_POST["user_id"]
    )
        ? filter_var(
            $_POST["user_id"],
            FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1
                ]
            ]
        )
        : false;


if ($userID === false) {

    accountRetrieveRedirect(
        "error",
        "invalid_account"
    );

}


$userID =
    (int) $userID;


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
                account_access

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
                "account_access"
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


        accountRetrieveRedirect(
            "error",
            "forbidden"
        );

    }


    /* =====================================================
       LOCK ARCHIVED ACCOUNT
    ===================================================== */

    $accountStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                name,
                email,
                role,
                archived

            FROM users

            WHERE user_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );


    mysqli_stmt_bind_param(
        $accountStatement,
        "i",
        $userID
    );


    mysqli_stmt_execute(
        $accountStatement
    );


    $accountResult =
        mysqli_stmt_get_result(
            $accountStatement
        );


    $account =
        mysqli_fetch_assoc(
            $accountResult
        );


    mysqli_stmt_close(
        $accountStatement
    );


    if (!$account) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        accountRetrieveRedirect(
            "error",
            "account_not_found"
        );

    }


    if (
        (int) (
            $account["archived"]
            ?? 0
        ) !== 1
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        accountRetrieveRedirect(
            "error",
            "not_archived"
        );

    }


    /* =====================================================
       ACCOUNT SNAPSHOT
    ===================================================== */

    $accountName =
        trim(
            (string) (
                $account["name"]
                ?? "Unknown Account"
            )
        );


    $accountRole =
    trim(
        (string) (
            $account["role"]
            ?? "Unknown Role"
        )
    );


/* =====================================================
   PREVENT DUPLICATE ACTIVE ACCOUNT NAME
===================================================== */

$duplicateNameStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT
            user_id

        FROM users

        WHERE
            archived = 0
            AND user_id <> ?
            AND LOWER(
                TRIM(
                    name
                )
            ) = LOWER(
                TRIM(?)
            )

        LIMIT 1

        FOR UPDATE
        "
    );


mysqli_stmt_bind_param(
    $duplicateNameStatement,
    "is",
    $userID,
    $accountName
);


mysqli_stmt_execute(
    $duplicateNameStatement
);


$duplicateNameResult =
    mysqli_stmt_get_result(
        $duplicateNameStatement
    );


$nameExists =
    $duplicateNameResult
    &&
    mysqli_num_rows(
        $duplicateNameResult
    ) > 0;


mysqli_stmt_close(
    $duplicateNameStatement
);


if (
    $nameExists
) {

    mysqli_rollback(
        $conn
    );


    $transactionStarted =
        false;


    accountRetrieveRedirect(
        "error",
        "account_duplicate"
    );

}


/* =====================================================
   RESTORE
===================================================== */

    $restoreStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE users

            SET
                archived = 0,
                archived_by = NULL

            WHERE user_id = ?

            AND archived = 1
            "
        );


    mysqli_stmt_bind_param(
        $restoreStatement,
        "i",
        $userID
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
            "Account restore affected an unexpected number of rows."
        );

    }


    /* =====================================================
       HISTORY
    ===================================================== */

    log_activity(
        $conn,
        "Account",
        "Restored",
        'restored the account of "' .
        $accountName .
        '" with the ' .
        $accountRole .
        " role.",
        $userID
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
        "account_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );


    accountRetrieveRedirect(
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
        "SPIS retrieve_account.php: " .
        $error->getMessage()
    );


    accountRetrieveRedirect(
        "error",
        "retrieve_failed"
    );

}