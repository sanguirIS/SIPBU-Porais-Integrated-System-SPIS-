<?php

/* =========================================================
   SPIS - GET ACCOUNT
   Secure account data endpoint
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
   RESPONSE HEADERS
========================================================= */

header(
    "Content-Type: application/json; charset=UTF-8"
);

header(
    "X-Content-Type-Options: nosniff"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   JSON RESPONSE HELPER
========================================================= */

function accountJsonError(
    string $message,
    int $statusCode
): never {

    http_response_code(
        $statusCode
    );

    echo json_encode(
        [
            "error" => $message
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit();
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


if ($currentUserID <= 0) {

    accountJsonError(
        "Your session has expired. Please log in again.",
        401
    );

}


/* =========================================================
   DATABASE
========================================================= */

require_once "../db.php";


if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    accountJsonError(
        "Database connection is unavailable.",
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


/* =========================================================
   VERIFY LIVE ACCOUNT ACCESS
========================================================= */

$accessStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT user_id, password, must_change_password

        FROM users

        WHERE user_id = ?

        AND archived = 0

        AND status = 'Active'

        AND account_access = 1

        LIMIT 1
        "
    );


if (!$accessStatement) {

    accountJsonError(
        "Unable to verify Account Management access.",
        500
    );
}


mysqli_stmt_bind_param(
    $accessStatement,
    "i",
    $currentUserID
);


if (!mysqli_stmt_execute($accessStatement)) {

    mysqli_stmt_close(
        $accessStatement
    );

    accountJsonError(
        "Unable to verify Account Management access.",
        500
    );
}

$accessResult = mysqli_stmt_get_result($accessStatement);

$actor = $accessResult
    ? mysqli_fetch_assoc($accessResult)
    : null;

mysqli_stmt_close($accessStatement);

$hasLiveAccountAccess = is_array($actor);

if (!$hasLiveAccountAccess) {

    $_SESSION["account_access"] = 0;

    accountJsonError(
        "You do not have permission to access Account Management.",
        403
    );
}

$_SESSION["account_access"] = 1;


/* =========================================================
   VERIFY PASSWORD VERSION AND REQUIRED CHANGE
   Keep JSON responses for the Edit Account interface.
========================================================= */

$sessionPasswordVersion = $_SESSION["password_version"] ?? null;
$currentPasswordVersion = hash(
    "sha256",
    (string) ($actor["password"] ?? "")
);

if (
    !is_string($sessionPasswordVersion) ||
    !hash_equals($currentPasswordVersion, $sessionPasswordVersion)
) {
    $_SESSION = [];
    session_destroy();

    accountJsonError(
        "Your login session is no longer valid. Please log in again.",
        401
    );
}

if ((int) ($actor["must_change_password"] ?? 1) !== 0) {
    accountJsonError(
        "You must change your temporary password before continuing.",
        403
    );
}


/* =========================================================
   VALIDATE ACCOUNT ID
========================================================= */

$userID =
    filter_input(
        INPUT_GET,
        "id",
        FILTER_VALIDATE_INT
    );


if (
    $userID === false ||
    $userID === null ||
    $userID <= 0
) {

    accountJsonError(
        "Invalid account record.",
        422
    );

}


/* =========================================================
   LOAD ACCOUNT

   IMPORTANT:
   Never SELECT * here.

   Password hashes and internal archive information
   must never be returned to the browser.
========================================================= */

$statement =
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

            lending_permission

        FROM users

        WHERE user_id = ?
          AND archived = 0

        LIMIT 1
        "
    );


if (!$statement) {

    accountJsonError(
        "Unable to prepare the account request.",
        500
    );

}


mysqli_stmt_bind_param(
    $statement,
    "i",
    $userID
);


if (
    !mysqli_stmt_execute(
        $statement
    )
) {

    mysqli_stmt_close(
        $statement
    );

    accountJsonError(
        "Unable to load the account.",
        500
    );

}


$result =
    mysqli_stmt_get_result(
        $statement
    );


if (
    !$result ||
    mysqli_num_rows(
        $result
    ) !== 1
) {

    mysqli_stmt_close(
        $statement
    );

    accountJsonError(
        "Account record was not found.",
        404
    );

}


$user =
    mysqli_fetch_assoc(
        $result
    );


mysqli_stmt_close(
    $statement
);


/* =========================================================
   NORMALIZE RESPONSE VALUES
========================================================= */

$user["user_id"] =
    (int) $user["user_id"];

$user["inventory_access"] =
    (int) $user["inventory_access"];

$user["sales_access"] =
    (int) $user["sales_access"];

$user["members_access"] =
    (int) $user["members_access"];

$user["lending_access"] =
    (int) $user["lending_access"];

$user["rental_access"] =
    (int) $user["rental_access"];

$user["archives_access"] =
    (int) $user["archives_access"];

$user["account_access"] =
    (int) $user["account_access"];

$user["password_reset_access"] =
    (int) $user["password_reset_access"];


/* =========================================================
   SEND SAFE ACCOUNT DATA
========================================================= */

echo json_encode(
    $user,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);

exit();

?>