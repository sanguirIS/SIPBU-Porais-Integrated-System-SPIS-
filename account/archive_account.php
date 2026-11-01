<?php

/* =========================================================
   ARCHIVE ACCOUNT
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

require_once "../includes/check_access.php";
checkAccess("account_access");

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

function accountArchiveRedirect(
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
        "Location: account.php?" .
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


    accountArchiveRedirect(
        "error",
        "invalid_request"
    );

}


/* =========================================================
   CURRENT SESSION
========================================================= */

$currentUserID =
    isset(
        $_SESSION["user_id"]
    )
        ? (int) $_SESSION["user_id"]
        : 0;


if ($currentUserID <= 0) {

    http_response_code(401);


    accountArchiveRedirect(
        "error",
        "session"
    );

}


/*
 * Initial session check only.
 *
 * The actual permission will also
 * be verified directly from the
 * database inside the transaction.
 */

if (
    (int) (
        $_SESSION[
            "account_access"
        ]
        ?? 0
    ) !== 1
) {

    http_response_code(403);


    accountArchiveRedirect(
        "error",
        "forbidden"
    );

}


/* =========================================================
   CSRF VALIDATION
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


    accountArchiveRedirect(
        "error",
        "csrf"
    );

}


/* =========================================================
   TARGET ACCOUNT
========================================================= */

$userID =
    isset(
        $_POST["user_id"]
    )
        ? (int) $_POST["user_id"]
        : 0;


if ($userID <= 0) {

    accountArchiveRedirect(
        "error",
        "invalid_account"
    );

}


/* =========================================================
   PREVENT SELF ARCHIVE
========================================================= */

if (
    $userID ===
    $currentUserID
) {

    accountArchiveRedirect(
        "error",
        "self_archive"
    );

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
       VERIFY CURRENT USER FROM DATABASE
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

                account_access,
                must_change_password,
                password

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


    /*
     * The actor must still exist,
     * remain active and unarchived,
     * and still have Account access.
     */

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


        accountArchiveRedirect(
            "error",
            "forbidden"
        );

    }



    /* =====================================================
       VERIFY LOGIN SESSION UNDER ACTOR ROW LOCK
    ===================================================== */

    $sessionPasswordVersion =
        $_SESSION["password_version"] ?? null;

    $currentPasswordVersion = hash(
        "sha256",
        (string) ($actor["password"] ?? "")
    );

    if (
        (int) ($actor["must_change_password"] ?? 1) !== 0
        || !is_string($sessionPasswordVersion)
        || !hash_equals(
            $currentPasswordVersion,
            $sessionPasswordVersion
        )
    ) {
        mysqli_rollback($conn);
        $transactionStarted = false;

        $_SESSION = [];
        session_destroy();

        header("Location: /spis/login.php", true, 303);
        exit;
    }


    $archivedBy =

        trim(
            (string) (
                $actor["name"]
                ?? "Unknown User"
            )
        );


    /* =====================================================
       GET + LOCK TARGET ACCOUNT
    ===================================================== */

    $targetStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                name,
                email,
                role,
                status,
                account_access,
                archived

            FROM users

            WHERE user_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );


    mysqli_stmt_bind_param(
        $targetStatement,
        "i",
        $userID
    );


    mysqli_stmt_execute(
        $targetStatement
    );


    $targetResult =
        mysqli_stmt_get_result(
            $targetStatement
        );


    $targetAccount =
        mysqli_fetch_assoc(
            $targetResult
        );


    mysqli_stmt_close(
        $targetStatement
    );


    if (
        !$targetAccount
        ||
        (int) (
            $targetAccount[
                "archived"
            ]
            ?? 1
        ) !== 0
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        accountArchiveRedirect(
            "error",
            "account_not_found"
        );

    }


    /*
     * Recheck inside the transaction,
     * even though this was already
     * checked before starting it.
     */

    if (
        (int) $targetAccount[
            "user_id"
        ] ===
        $currentUserID
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        accountArchiveRedirect(
            "error",
            "self_archive"
        );

    }


    /* =====================================================
       LAST ACTIVE ACCOUNT MANAGER PROTECTION

       If the target is currently an active
       Account manager, another active Account
       manager must exist before this account
       can be archived.
    ===================================================== */

    $targetIsActiveManager =
        (
            (
                $targetAccount[
                    "status"
                ]
                ?? ""
            ) === "Active"
            &&
            (int) (
                $targetAccount[
                    "account_access"
                ]
                ?? 0
            ) === 1
        );


    if ($targetIsActiveManager) {

        $managerStatement =
            mysqli_prepare(
                $conn,
                "
                SELECT
                    user_id

                FROM users

                WHERE
                    archived = 0

                AND status = 'Active'

                AND account_access = 1

                AND user_id <> ?

                LIMIT 1

                FOR UPDATE
                "
            );


        mysqli_stmt_bind_param(
            $managerStatement,
            "i",
            $userID
        );


        mysqli_stmt_execute(
            $managerStatement
        );


        $managerResult =
            mysqli_stmt_get_result(
                $managerStatement
            );


        $otherManager =
            mysqli_fetch_assoc(
                $managerResult
            );


        mysqli_stmt_close(
            $managerStatement
        );


        if (!$otherManager) {

            mysqli_rollback(
                $conn
            );


            $transactionStarted =
                false;


            accountArchiveRedirect(
                "error",
                "last_manager"
            );

        }

    }


    /* =====================================================
       TARGET DETAILS FOR HISTORY
    ===================================================== */

    $accountName =
        trim(
            (string) (
                $targetAccount[
                    "name"
                ]
                ?? "Unknown Account"
            )
        );


    $accountRole =
        trim(
            (string) (
                $targetAccount[
                    "role"
                ]
                ?? "Unknown Role"
            )
        );


    /* =====================================================
       ARCHIVE ACCOUNT
    ===================================================== */

    $archiveStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE users

            SET
                archived = 1,
                archived_by = ?

            WHERE user_id = ?

            AND archived = 0
            "
        );


    mysqli_stmt_bind_param(
        $archiveStatement,
        "si",
        $archivedBy,
        $userID
    );


    mysqli_stmt_execute(
        $archiveStatement
    );


    $affectedRows =
        mysqli_stmt_affected_rows(
            $archiveStatement
        );


    mysqli_stmt_close(
        $archiveStatement
    );


    if ($affectedRows !== 1) {

        throw new RuntimeException(
            "Account archive affected an unexpected number of rows."
        );

    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    log_activity(
        $conn,
        "Account",
        "Archived",
        'archived the account of "' .
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
       ROTATE ACCOUNT CSRF TOKEN
    ===================================================== */

    $_SESSION[
        "account_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );


    accountArchiveRedirect(
        "success",
        "archived"
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
             * Do not expose internal
             * database information.
             */

        }

    }


    error_log(
        "SPIS archive_account.php: " .
        $error->getMessage()
    );


    accountArchiveRedirect(
        "error",
        "update_failed"
    );

}