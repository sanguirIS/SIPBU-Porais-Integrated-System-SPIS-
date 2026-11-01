<?php

/* =========================================================
   SPIS - SAVE ACCOUNT
========================================================= */


/* =========================================================
   SESSION
========================================================= */

if (
    session_status() === PHP_SESSION_NONE
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


mysqli_report(
    MYSQLI_REPORT_ERROR |
    MYSQLI_REPORT_STRICT
);


/* =========================================================
   REDIRECT HELPER
========================================================= */

function accountCreateRedirect(
    string $type,
    string $code
): never {

    header(
        "Location: account.php?"
        .
        rawurlencode(
            $type
        )
        .
        "="
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
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    accountCreateRedirect(
        "error",
        "invalid_request"
    );

}


/* =========================================================
   SESSION / ACCESS CHECK
========================================================= */

$currentUserID =
    isset(
        $_SESSION["user_id"]
    )
        ? (int) $_SESSION["user_id"]
        : 0;


if (
    $currentUserID <= 0
) {

    header(
        "Location: ../login.php"
    );

    exit();

}


/* =========================================================
   VERIFY LIVE ACCOUNT ACCESS
========================================================= */

$actorStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT user_id

        FROM users

        WHERE user_id = ?

        AND archived = 0

        AND status = 'Active'

        AND account_access = 1

        LIMIT 1
        "
    );


if (!$actorStatement) {

    accountCreateRedirect(
        "error",
        "invalid_request"
    );
}


mysqli_stmt_bind_param(
    $actorStatement,
    "i",
    $currentUserID
);


if (!mysqli_stmt_execute($actorStatement)) {

    mysqli_stmt_close(
        $actorStatement
    );

    accountCreateRedirect(
        "error",
        "invalid_request"
    );
}


mysqli_stmt_store_result(
    $actorStatement
);


$hasLiveAccountAccess =
    mysqli_stmt_num_rows(
        $actorStatement
    ) === 1;


mysqli_stmt_close(
    $actorStatement
);


if (!$hasLiveAccountAccess) {

    $_SESSION["account_access"] = 0;

    accountCreateRedirect(
        "error",
        "invalid_request"
    );
}


$_SESSION["account_access"] = 1;


/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionToken =
    $_SESSION[
        "account_csrf_token"
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

    accountCreateRedirect(
        "error",
        "invalid_security_token"
    );

}


/* =========================================================
   ACCOUNT INFORMATION
========================================================= */

$name =
    trim(
        (string) (
            $_POST["name"]
            ??
            ""
        )
    );


$emailName =
    trim(
        (string) (
            $_POST["email_name"]
            ??
            ""
        )
    );



$password =
    (string) (
        $_POST["password"]
        ??
        ""
    );


$confirmPassword =
    (string) (
        $_POST["confirm_password"]
        ??
        ""
    );


$role =
    trim(
        (string) (
            $_POST["role"]
            ??
            ""
        )
    );


$status = "Active";


/* =========================================================
   REQUIRED FIELDS
========================================================= */

if (
    $name === ""
    ||
    $emailName === ""
    ||
    $password === ""
    ||
    $confirmPassword === ""
    ||
    $role === ""
    ||
    $status === ""
) {

    accountCreateRedirect(
        "error",
        "required"
    );

}


/* =========================================================
   FIELD LENGTH VALIDATION
========================================================= */

if (
    strlen(
        $name
    ) > 100
    ||
    strlen(
        $role
    ) > 80
) {

    accountCreateRedirect(
        "error",
        "invalid_request"
    );

}


/* =========================================================
   EMAIL USERNAME VALIDATION

   Final email format:
   username@spis.com
========================================================= */

if (
    strlen(
        $emailName
    ) < 3
    ||
    strlen(
        $emailName
    ) > 50
    ||
    
    !preg_match(
        "/^[A-Za-z0-9_-]+$/",
        $emailName
    )

) {

    accountCreateRedirect(
        "error",
        "email"
    );

}


$email =
    $emailName
    .
    "@spis.com";


/* =========================================================
   PASSWORD VALIDATION
========================================================= */

if (
    strlen(
        $password
    ) < 8
) {

    accountCreateRedirect(
        "error",
        "password"
    );

}


if (
    strlen(
        $password
    ) > 255
) {

    accountCreateRedirect(
        "error",
        "password"
    );

}


if (
    !hash_equals(
        $password,
        $confirmPassword
    )
) {

    accountCreateRedirect(
        "error",
        "password_match"
    );

}


/* =========================================================
   STATUS VALIDATION
========================================================= */

$allowedStatuses =
    [
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

    accountCreateRedirect(
        "error",
        "invalid_request"
    );

}


/* =========================================================
   MODULE VALIDATION
========================================================= */

$submittedModules =
    $_POST[
        "modules"
    ]
    ??
    [];


if (
    !is_array(
        $submittedModules
    )
    ||
    empty(
        $submittedModules
    )
) {

    accountCreateRedirect(
        "error",
        "modules"
    );

}

$allowedModules =
    [
        "Inventory",
        "Sales",
        "Members",
        "Lending",
        "Rental",
        "Archives",
        "Account",
        "Password Reset"
    ];

foreach (
    $submittedModules
    as
    $submittedModule
) {

    if (
        !is_string(
            $submittedModule
        )
        ||
        !in_array(
            $submittedModule,
            $allowedModules,
            true
        )
    ) {

        accountCreateRedirect(
            "error",
            "invalid_request"
        );

    }

}


/* =========================================================
   MODULE ACCESS VALUES
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


$account =
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


if (
    $lending === 1
) {

    $submittedPermission =
        trim(
            (string) (
                $_POST[
                    "lending_permission"
                ]
                ??
                ""
            )
        );


    if (
        !in_array(
            $submittedPermission,
            [
                "Limited",
                "Full"
            ],
            true
        )
    ) {

        accountCreateRedirect(
            "error",
            "invalid_request"
        );

    }


    $lendingPermission =
        $submittedPermission;

}


/* =========================================================
   PASSWORD HASH
========================================================= */

$passwordHash =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


if (
    $passwordHash === false
) {

    accountCreateRedirect(
        "error",
        "invalid_request"
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
       LOCK AND RECHECK ACTOR BEFORE CREATING AN ACCOUNT
       Prevent stale sessions and permissions changed mid-request.
    ===================================================== */

    $actorLockStatement = mysqli_prepare(
        $conn,
        "SELECT password, account_access, password_reset_access,
                must_change_password, status, archived
         FROM users
         WHERE user_id = ?
         LIMIT 1
         FOR UPDATE"
    );

    mysqli_stmt_bind_param(
        $actorLockStatement,
        "i",
        $currentUserID
    );

    mysqli_stmt_execute($actorLockStatement);

    $lockedActor = mysqli_fetch_assoc(
        mysqli_stmt_get_result($actorLockStatement)
    );

    mysqli_stmt_close($actorLockStatement);

    $sessionPasswordVersion = $_SESSION["password_version"] ?? null;

    if (
        !$lockedActor ||
        (int) $lockedActor["archived"] !== 0 ||
        $lockedActor["status"] !== "Active" ||
        (int) $lockedActor["account_access"] !== 1 ||
        (int) $lockedActor["must_change_password"] !== 0 ||
        !is_string($sessionPasswordVersion) ||
        !hash_equals(
            hash("sha256", (string) $lockedActor["password"]),
            $sessionPasswordVersion
        )
    ) {
        throw new RuntimeException(
            "Account creation blocked: actor session is no longer authorized."
        );
    }

    if (
        $passwordReset === 1 &&
        (int) $lockedActor["password_reset_access"] !== 1
    ) {
        throw new RuntimeException(
            "Not permitted to grant Password Reset access."
        );
    }


/* =====================================================
   CHECK DUPLICATE ACTIVE ACCOUNT NAME
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
    "s",
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


    accountCreateRedirect(
        "error",
        "duplicate_name"
    );

}        

    /* =====================================================
       CHECK DUPLICATE EMAIL
    ===================================================== */

    $duplicateStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id

            FROM users

            WHERE email = ?

            LIMIT 1

            FOR UPDATE
            "
        );


    mysqli_stmt_bind_param(
        $duplicateStatement,
        "s",
        $email
    );


    mysqli_stmt_execute(
        $duplicateStatement
    );


    $duplicateResult =
        mysqli_stmt_get_result(
            $duplicateStatement
        );


    $emailExists =
        $duplicateResult
        &&
        mysqli_num_rows(
            $duplicateResult
        ) > 0;


    mysqli_stmt_close(
        $duplicateStatement
    );


    if (
        $emailExists
    ) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        accountCreateRedirect(
            "error",
            "duplicate"
        );

    }


    /* =====================================================
       INSERT ACCOUNT
    ===================================================== */

    $insertStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO users
            (
                name,
                email,
                password,
                role,

                inventory_access,
                sales_access,
                members_access,
                lending_access,
                rental_access,
                archives_access,
                account_access,
                password_reset_access,

                lending_permission,
                status,
                created_at
            )

            VALUES
            (
                ?,
                ?,
                ?,
                ?,

                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,

                ?,
                ?,
                NOW()
            )
            "
        );


    mysqli_stmt_bind_param(
        $insertStatement,
        "ssssiiiiiiiiss",
        $name,
        $email,
        $passwordHash,
        $role,

        $inventory,
        $sales,
        $members,
        $lending,
        $rental,
        $archives,
        $account,
        $passwordReset,

        $lendingPermission,
        $status
    );

    mysqli_stmt_execute(
        $insertStatement
    );


    $newUserID =
        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $insertStatement
    );


    if (
        $newUserID <= 0
    ) {

        throw new RuntimeException(
            "Unable to create the account record."
        );

    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    $accessSummary =
        implode(
            ", ",
            $submittedModules
        );


    $historyMessage =
        'created an account for "'
        .
        $name
        .
        '" with the '
        .
        $role
        .
        ' role and access to '
        .
        $accessSummary;


    if (
        $lending === 1
    ) {

        $historyMessage .=
            ' with '
            .
            $lendingPermission
            .
            ' Lending permission';

    }


    $historyMessage .=
        '.';


    log_activity(
        $conn,
        "Account",
        "Added",
        $historyMessage,
        $newUserID
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
        "account_csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );


    accountCreateRedirect(
        "success",
        "added"
    );


}
catch (
    mysqli_sql_exception $error
) {


    if (
        $transactionStarted
    ) {

        mysqli_rollback(
            $conn
        );

    }


    /*
     * MySQL duplicate-key error.
     * Useful if the email column already has
     * a UNIQUE constraint in the database.
     */

    if (
        (int) $error->getCode()
        === 1062
    ) {

        accountCreateRedirect(
            "error",
            "duplicate"
        );

    }


    accountCreateRedirect(
        "error",
        "invalid_request"
    );


}
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


    accountCreateRedirect(
        "error",
        "invalid_request"
    );

}

?>