<?php

/* =========================================================
   SEARCH PRODUCT LOAN MEMBERS

   - Active, Regular, and non-archived members only
   - Minimum of three search characters
   - Reports whether the member has an active Product Loan
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

function productLoanMemberResponse(
    bool $success,
    string $message,
    array $members = [],
    int $statusCode = 200
): void {

    http_response_code(
        $statusCode
    );

    echo json_encode(
        [
            "success" => $success,
            "message" => $message,
            "members" => $members
        ],
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

    productLoanMemberResponse(
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

    productLoanMemberResponse(
        false,
        "Invalid request.",
        [],
        400
    );

}


/* =========================================================
   SESSION CHECK
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {

    productLoanMemberResponse(
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

    productLoanMemberResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );

}


/* =========================================================
   SEARCH VALUE
========================================================= */

$search =
    trim(
        $_GET["query"]
        ?? ""
    );

$search =
    preg_replace(
        "/\s+/",
        " ",
        $search
    );

if (
    strlen($search) < 3
) {

    productLoanMemberResponse(
        true,
        "Enter at least three letters.",
        []
    );

}

if (
    strlen($search) > 80
) {

    productLoanMemberResponse(
        false,
        "The search value is too long.",
        [],
        422
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

    productLoanMemberResponse(
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
   MEMBER SEARCH

   Active Product Loan means:

   1. Pending application
   2. Delivered loan with remaining principal
   3. Delivered loan with unpaid accrued interest

   Rejected and completely Paid loans do not block
   another application.
========================================================= */

$searchPattern =
    "%" . $search . "%";

$sql =
    "
    SELECT

        m.member_id,
        m.member_code,
        m.full_name,

        CASE

            WHEN EXISTS (

                SELECT 1

                FROM product_loans AS active_loan

                WHERE
                    active_loan.member_id =
                    m.member_id

                    AND (

                        active_loan.status =
                        'Pending'

                        OR

                        (
                            active_loan.status =
                            'Delivered'

                            AND (

                                active_loan
                                .outstanding_principal_amount
                                > 0.009

                                OR

                                active_loan
                                .unpaid_interest_amount
                                > 0.009

                            )

                        )

                    )

            )

            THEN 1

            ELSE 0

        END AS has_active_loan,

        (

            SELECT latest_loan.status

            FROM product_loans AS latest_loan

            WHERE
                latest_loan.member_id =
                m.member_id

                AND (

                    latest_loan.status =
                    'Pending'

                    OR

                    (
                        latest_loan.status =
                        'Delivered'

                        AND (

                            latest_loan
                            .outstanding_principal_amount
                            > 0.009

                            OR

                            latest_loan
                            .unpaid_interest_amount
                            > 0.009

                        )

                    )

                )

            ORDER BY
                latest_loan.product_loan_id
                DESC

            LIMIT 1

        ) AS active_loan_status

    FROM members AS m

    WHERE
    m.archived = 0

    AND UPPER(
        TRIM(
            COALESCE(
                m.status,
                ''
            )
        )
    ) = 'ACTIVE'

    AND UPPER(
        TRIM(
            COALESCE(
                m.classification,
                ''
            )
        )
    ) = 'REGULAR'

    AND (

            m.full_name LIKE ?

            OR

            m.member_code LIKE ?

        )

    ORDER BY
        m.full_name ASC

    LIMIT 10
    ";

$statement =
    mysqli_prepare(
        $conn,
        $sql
    );

if (!$statement) {

    error_log(
        "Product loan member search prepare error: " .
        mysqli_error($conn)
    );

    productLoanMemberResponse(
        false,
        "Unable to prepare the member search.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $statement,
    "ss",
    $searchPattern,
    $searchPattern
);

if (
    !mysqli_stmt_execute(
        $statement
    )
) {

    error_log(
        "Product loan member search execute error: " .
        mysqli_stmt_error($statement)
    );

    mysqli_stmt_close(
        $statement
    );

    productLoanMemberResponse(
        false,
        "Unable to search members.",
        [],
        500
    );

}

$result =
    mysqli_stmt_get_result(
        $statement
    );

$members = [];

while (
    $member =
        mysqli_fetch_assoc($result)
) {

    $members[] = [
        "member_id" =>
            (int) $member["member_id"],

        "member_code" =>
            $member["member_code"]
            ?? "",

        "full_name" =>
            $member["full_name"]
            ?? "",

        "has_active_loan" =>
            (bool) $member["has_active_loan"],

        "active_loan_status" =>
            $member["active_loan_status"]
            ?? null
    ];

}

mysqli_stmt_close(
    $statement
);


/* =========================================================
   RESPONSE
========================================================= */

productLoanMemberResponse(
    true,
    count($members) > 0
        ? "Members retrieved."
        : "No eligible Regular member found.",
    $members
);