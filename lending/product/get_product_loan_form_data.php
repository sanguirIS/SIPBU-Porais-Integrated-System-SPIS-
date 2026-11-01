<?php

/* =========================================================
   PRODUCT LOAN FORM DATA
   Returns the authoritative Asia/Manila server date
   and current user's Lending Permission.
========================================================= */

if (
    session_status() ===
    PHP_SESSION_NONE
) {
    session_start();
}


/* =========================================================
   SETTINGS
========================================================= */

date_default_timezone_set(
    "Asia/Manila"
);

ini_set(
    "display_errors",
    "0"
);

error_reporting(E_ALL);

header(
    "Content-Type: application/json; charset=utf-8"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   JSON RESPONSE
========================================================= */

function productLoanFormResponse(
    bool $success,
    string $message,
    array $additionalData = [],
    int $statusCode = 200
): void {

    http_response_code(
        $statusCode
    );

    echo json_encode(
        array_merge(
            [
                "success" => $success,
                "message" => $message
            ],
            $additionalData
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit();

}


/* =========================================================
   GET REQUEST ONLY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "GET"
) {

    productLoanFormResponse(
        false,
        "Invalid request method.",
        [],
        405
    );

}


/* =========================================================
   AJAX REQUEST CHECK
========================================================= */

$requestedWith =
    $_SERVER["HTTP_X_REQUESTED_WITH"]
    ?? "";

if (
    strtolower($requestedWith) !==
    "xmlhttprequest"
) {

    productLoanFormResponse(
        false,
        "Invalid request.",
        [],
        400
    );

}


/* =========================================================
   DATABASE
========================================================= */

require_once "../../db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    productLoanFormResponse(
        false,
        "Database connection is unavailable.",
        [],
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
   USER SESSION
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {

    productLoanFormResponse(
        false,
        "Your session has expired. Please log in again.",
        [],
        401
    );

}

if (
    (int) (
        $_SESSION["lending_access"]
        ?? 0
    ) !== 1
) {

    productLoanFormResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );

}


/* =========================================================
   RETRIEVE LENDING PERMISSION

   Permission is independent from the user's role.
========================================================= */

$lendingPermission =
    "limited";

$userStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT lending_permission
        FROM users
        WHERE user_id = ?
        LIMIT 1
        "
    );

if (!$userStatement) {

    error_log(
        "Product loan permission prepare error: " .
        mysqli_error($conn)
    );

    productLoanFormResponse(
        false,
        "Unable to retrieve account permission.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $userStatement,
    "i",
    $userID
);

mysqli_stmt_execute(
    $userStatement
);

$userResult =
    mysqli_stmt_get_result(
        $userStatement
    );

$user =
    mysqli_fetch_assoc(
        $userResult
    );

mysqli_stmt_close(
    $userStatement
);

if (!$user) {

    productLoanFormResponse(
        false,
        "The logged-in account could not be found.",
        [],
        401
    );

}

if (
    strtolower(
        trim(
            $user["lending_permission"]
            ?? ""
        )
    ) === "full"
) {

    $lendingPermission =
        "full";

}


/* =========================================================
   SERVER DATE

   This date comes from PHP using Asia/Manila.
   The device calendar input is not trusted.
========================================================= */

$applicationDate =
    date("Y-m-d");

$applicationDateDisplay =
    date("m-d-Y");

$serverDateTime =
    date("Y-m-d H:i:s");


/* =========================================================
   RESPONSE
========================================================= */

productLoanFormResponse(
    true,
    "Product loan form data retrieved.",
    [
        "application_date" =>
            $applicationDate,

        "application_date_display" =>
            $applicationDateDisplay,

        "server_datetime" =>
            $serverDateTime,

        "timezone" =>
            "Asia/Manila",

        "lending_permission" =>
            $lendingPermission
    ]
);