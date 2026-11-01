<?php

/* =========================================================
   UPDATE ACCOUNT
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

function accountUpdateRedirect(
    string $type,
    string $value
): void {

    $allowedTypes = [
        "success",
        "error"
    ];


    if (
        !in_array(
            $type,
            $allowedTypes,
            true
        )
    ) {

        $type = "error";

    }


    header(
        "Location: account.php?" .
        $type .
        "=" .
        rawurlencode($value)
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


    accountUpdateRedirect(
        "error",
        "invalid_request"
    );

}


/* =========================================================
   CURRENT SESSION
========================================================= */

$currentUserID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;


if ($currentUserID <= 0) {

    http_response_code(401);


    accountUpdateRedirect(
        "error",
        "session"
    );

}


/*
 * Quick session-level access check.
 *
 * We will verify this again against
 * the database inside the transaction.
 */

if (
    (int) (
        $_SESSION["account_access"]
        ?? 0
    ) !== 1
) {

    http_response_code(403);


    accountUpdateRedirect(
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


$submittedCsrfToken =
    (string) (
        $_POST["csrf_token"]
        ?? ""
    );


if (
    $sessionCsrfToken === ""
    ||
    $submittedCsrfToken === ""
    ||
    !hash_equals(
        $sessionCsrfToken,
        $submittedCsrfToken
    )
) {

    http_response_code(419);


    accountUpdateRedirect(
        "error",
        "csrf"
    );

}


/* =========================================================
   INPUT
========================================================= */

$userID =
    isset($_POST["user_id"])
        ? (int) $_POST["user_id"]
        : 0;


$name =
    trim(
        (string) (
            $_POST["name"]
            ?? ""
        )
    );


$role =
    trim(
        (string) (
            $_POST["role"]
            ?? ""
        )
    );


$status =
    trim(
        (string) (
            $_POST["status"]
            ?? ""
        )
    );


$submittedModules =
    $_POST["modules"]
    ?? [];


/* =========================================================
   BASIC VALIDATION
========================================================= */

if ($userID <= 0) {

    accountUpdateRedirect(
        "error",
        "invalid_account"
    );

}


if (
    $name === ""
    ||
    $role === ""
) {

    accountUpdateRedirect(
        "error",
        "required"
    );

}


if (
    strlen($name) > 100
    ||
    strlen($role) > 80
) {

    accountUpdateRedirect(
        "error",
        "invalid_input"
    );

}


/* =========================================================
   STATUS ALLOWLIST
========================================================= */

$allowedStatuses = [
    "Active",
    "Inactive"
];


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    accountUpdateRedirect(
        "error",
        "invalid_status"
    );

}


/* =========================================================
   MODULE ALLOWLIST
========================================================= */

$allowedModules = [
    "Inventory",
    "Sales",
    "Members",
    "Lending",
    "Rental",
    "Archives",
    "Account",
    "Password Reset"
];



if (
    !is_array(
        $submittedModules
    )
    ||
    empty(
        $submittedModules
    )
) {

    accountUpdateRedirect(
        "error",
        "modules"
    );

}


/*
 * Normalize duplicate values.
 */

$submittedModules =
    array_values(
        array_unique(
            array_map(
                "strval",
                $submittedModules
            )
        )
    );


/*
 * Reject forged / unknown module names.
 */

foreach (
    $submittedModules
    as
    $submittedModule
) {

    if (
        !in_array(
            $submittedModule,
            $allowedModules,
            true
        )
    ) {

        accountUpdateRedirect(
            "error",
            "modules"
        );

    }

}


/* =========================================================
   ACCESS FLAGS
========================================================= */

$inventory =
    in_array(
        "Inventory",
        $submittedModules,
        true
    )
        ? 1
        : 0;


$sales =
    in_array(
        "Sales",
        $submittedModules,
        true
    )
        ? 1
        : 0;


$members =
    in_array(
        "Members",
        $submittedModules,
        true
    )
        ? 1
        : 0;


$lending =
    in_array(
        "Lending",
        $submittedModules,
        true
    )
        ? 1
        : 0;


$rental =
    in_array(
        "Rental",
        $submittedModules,
        true
    )
        ? 1
        : 0;


$archives =
    in_array(
        "Archives",
        $submittedModules,
        true
    )
        ? 1
        : 0;

$accountAccess =
    in_array(
        "Account",
        $submittedModules,
        true
    )
        ? 1
        : 0;

$passwordReset =
    in_array(
        "Password Reset",
        $submittedModules,
        true
    )
        ? 1
        : 0;


/* =========================================================
   LENDING PERMISSION
========================================================= */

$lendingPermission =
    "None";


if ($lending === 1) {

    $lendingPermission =
        trim(
            (string) (
                $_POST[
                    "lending_permission"
                ]
                ?? ""
            )
        );


    if (
        !in_array(
            $lendingPermission,
            [
                "Limited",
                "Full"
            ],
            true
        )
    ) {

        accountUpdateRedirect(
            "error",
            "lending_permission"
        );

    }

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
       VERIFY ACTOR FROM DATABASE

       Session values alone are not trusted.
    ===================================================== */

    $actorStatement =
        mysqli_prepare(
            $conn,
            "           
            SELECT
                user_id,
                status,
                archived,

                account_access,
                password_reset_access,
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
            $actor["account_access"]
            ?? 0
        ) !== 1
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        http_response_code(403);


        accountUpdateRedirect(
            "error",
            "forbidden"
        );

    }



    /* =====================================================
       RECHECK PASSWORD SESSION UNDER ACTOR ROW LOCK
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

                inventory_access,
                sales_access,
                members_access,
                lending_access,
                rental_access,
                
                archives_access,
                account_access,
                password_reset_access,

                lending_permission,
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


    $oldAccount =
        mysqli_fetch_assoc(
            $targetResult
        );


    mysqli_stmt_close(
        $targetStatement
    );


    if (
        !$oldAccount
        ||
        (int) (
            $oldAccount["archived"]
            ?? 1
        ) !== 0
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        accountUpdateRedirect(
            "error",
            "invalid_account"
        );

    }

    /* =====================================================
       ORIGINAL PASSWORD RESET ACCESS

       Account Management access allows authorized users
       to assign or remove module permissions.

       The actor's Account access is already verified above.
    ===================================================== */

    $oldPasswordReset =
        (int) (
            $oldAccount["password_reset_access"]
            ?? 0
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
        $name
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


        accountUpdateRedirect(
            "error",
            "duplicate_name"
        );

    }


    /* =====================================================
       SELF-LOCKOUT PROTECTION
    ===================================================== */

    if (
        $userID ===
        $currentUserID
    ) {

        /*
         * A user currently managing Accounts
         * may not remove their own Account
         * access or deactivate themselves.
         */

        if (
            $accountAccess !== 1
            ||
            $status !== "Active"
        ) {

            mysqli_rollback(
                $conn
            );


            $transactionStarted =
                false;


            accountUpdateRedirect(
                "error",
                "self_lockout"
            );

        }

    }


    /* =====================================================
       LAST ACTIVE ACCOUNT MANAGER PROTECTION
    ===================================================== */

    $currentlyActiveManager =
        (
            (int) (
                $oldAccount[
                    "account_access"
                ]
                ?? 0
            ) === 1
            &&
            (
                $oldAccount[
                    "status"
                ]
                ?? ""
            ) === "Active"
        );


    $willRemainActiveManager =
        (
            $accountAccess === 1
            &&
            $status === "Active"
        );


    if (
        $currentlyActiveManager
        &&
        !$willRemainActiveManager
    ) {

        $otherManagerStatement =
            mysqli_prepare(
                $conn,
                "
                SELECT user_id

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
            $otherManagerStatement,
            "i",
            $userID
        );


        mysqli_stmt_execute(
            $otherManagerStatement
        );


        $otherManagerResult =
            mysqli_stmt_get_result(
                $otherManagerStatement
            );


        $otherManager =
            mysqli_fetch_assoc(
                $otherManagerResult
            );


        mysqli_stmt_close(
            $otherManagerStatement
        );


        if (!$otherManager) {

            mysqli_rollback(
                $conn
            );


            $transactionStarted =
                false;


            accountUpdateRedirect(
                "error",
                "last_manager"
            );

        }

    }


    /* =====================================================
       OLD VALUES
    ===================================================== */

    $oldName =
        trim(
            (string) (
                $oldAccount["name"]
                ?? ""
            )
        );


    $oldRole =
        trim(
            (string) (
                $oldAccount["role"]
                ?? ""
            )
        );


    $oldStatus =
        trim(
            (string) (
                $oldAccount["status"]
                ?? ""
            )
        );


    $oldLendingPermission =
        trim(
            (string) (
                $oldAccount[
                    "lending_permission"
                ]
                ?? "None"
            )
        );


    /* =====================================================
       MODULE ACCESS CHANGE MAP
    ===================================================== */

    $moduleAccessMap = [

        "Inventory" => [
            "old" =>
                (int) $oldAccount[
                    "inventory_access"
                ],

            "new" =>
                $inventory
        ],

        "Sales" => [
            "old" =>
                (int) $oldAccount[
                    "sales_access"
                ],

            "new" =>
                $sales
        ],

        "Members" => [
            "old" =>
                (int) $oldAccount[
                    "members_access"
                ],

            "new" =>
                $members
        ],

        "Lending" => [
            "old" =>
                (int) $oldAccount[
                    "lending_access"
                ],

            "new" =>
                $lending
        ],

        "Rental" => [
            "old" =>
                (int) $oldAccount[
                    "rental_access"
                ],

            "new" =>
                $rental
        ],

        "Archives" => [
            "old" =>
                (int) $oldAccount[
                    "archives_access"
                ],

            "new" =>
                $archives
        ],

        "Account" => [
            "old" =>
                (int) $oldAccount[
                    "account_access"
                ],

            "new" =>
                $accountAccess
        ],

        "Password Reset" => [
            "old" =>
                $oldPasswordReset,

            "new" =>
                $passwordReset
        ]

    ];


    /* =====================================================
       HISTORY CHANGE DESCRIPTION
    ===================================================== */

    $changes =
        [];


    if (
        $oldName !==
        $name
    ) {

        $changes[] =
            'name from "' .
            $oldName .
            '" to "' .
            $name .
            '"';

    }


    if (
        $oldRole !==
        $role
    ) {

        $changes[] =
            'role from "' .
            $oldRole .
            '" to "' .
            $role .
            '"';

    }


    if (
        $oldStatus !==
        $status
    ) {

        $changes[] =
            "status from " .
            $oldStatus .
            " to " .
            $status;

    }


    $grantedModules =
        [];


    $removedModules =
        [];


    foreach (
        $moduleAccessMap
        as
        $moduleName =>
        $accessValues
    ) {

        if (
            $accessValues["old"] === 0
            &&
            $accessValues["new"] === 1
        ) {

            $grantedModules[] =
                $moduleName;

        }


        if (
            $accessValues["old"] === 1
            &&
            $accessValues["new"] === 0
        ) {

            $removedModules[] =
                $moduleName;

        }

    }


    if (!empty($grantedModules)) {

        $changes[] =
            "granted access to " .
            implode(
                ", ",
                $grantedModules
            );

    }


    if (!empty($removedModules)) {

        $changes[] =
            "removed access to " .
            implode(
                ", ",
                $removedModules
            );

    }


    if (
        $oldLendingPermission !==
        $lendingPermission
    ) {

        $changes[] =
            "Lending permission from " .
            $oldLendingPermission .
            " to " .
            $lendingPermission;

    }


    /* =====================================================
       NO ACTUAL CHANGE
    ===================================================== */

    if (empty($changes)) {

        mysqli_commit(
            $conn
        );


        $transactionStarted =
            false;


        /*
         * Rotate token after a valid
         * consequential request.
         */

        $_SESSION[
            "account_csrf_token"
        ] =
            bin2hex(
                random_bytes(32)
            );


        accountUpdateRedirect(
            "success",
            "updated"
        );

    }


    /* =====================================================
       UPDATE ACCOUNT
    ===================================================== */

    $updateStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE users

            SET
                name = ?,
                role = ?,
                status = ?,

                inventory_access = ?,
                sales_access = ?,
                members_access = ?,
                lending_access = ?,
                rental_access = ?,
                
                archives_access = ?,
                account_access = ?,
                password_reset_access = ?,

                lending_permission = ?

            WHERE user_id = ?

            AND archived = 0
            "
        );
 
    mysqli_stmt_bind_param(
        $updateStatement,
        "sssiiiiiiiisi",
        $name,
        $role,
        $status,

        $inventory,
        $sales,
        $members,
        $lending,
        $rental,
        $archives,
        $accountAccess,
        $passwordReset,

        $lendingPermission,

        $userID
    );

    mysqli_stmt_execute(
        $updateStatement
    );


    if (
        mysqli_stmt_affected_rows(
            $updateStatement
        ) !== 1
    ) {

        mysqli_stmt_close(
            $updateStatement
        );


        throw new RuntimeException(
            "Account update affected an unexpected number of rows."
        );

    }


    mysqli_stmt_close(
        $updateStatement
    );


    /* =====================================================
       HISTORY
    ===================================================== */

    log_activity(
        $conn,
        "Account",
        "Updated",
        'updated the account of "' .
        $oldName .
        '": ' .
        implode(
            ", ",
            $changes
        ) .
        ".",
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
       IF USER UPDATED THEIR OWN ACCOUNT

       Refresh their current session access immediately.
    ===================================================== */

    if (
        $userID ===
        $currentUserID
    ) {

        $_SESSION["name"] =
            $name;

        $_SESSION["role"] =
            $role;

        $_SESSION[
            "inventory_access"
        ] =
            $inventory;

        $_SESSION[
            "sales_access"
        ] =
            $sales;

        $_SESSION[
            "members_access"
        ] =
            $members;

        $_SESSION[
            "lending_access"
        ] =
            $lending;

        $_SESSION[
            "rental_access"
        ] =
            $rental;

        $_SESSION[
            "archives_access"
        ] =
            $archives;

        $_SESSION[
            "account_access"
        ] =
            $accountAccess;
        
        $_SESSION[
            "password_reset_access"
        ] =
            $passwordReset;

        $_SESSION[
            "lending_permission"
        ] =
            $lendingPermission;

    }


    /* =====================================================
       ROTATE CSRF TOKEN
    ===================================================== */

    $_SESSION[
        "account_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );


    accountUpdateRedirect(
        "success",
        "updated"
    );

}
catch (Throwable $error) {

    if (
        $transactionStarted
    ) {

        try {

            mysqli_rollback(
                $conn
            );

        }
        catch (Throwable $rollbackError) {

            /*
             * Do not expose database
             * details to the browser.
             */

        }

    }


    error_log(
        "SPIS update_account.php: " .
        $error->getMessage()
    );


    accountUpdateRedirect(
        "error",
        "update_failed"
    );

}