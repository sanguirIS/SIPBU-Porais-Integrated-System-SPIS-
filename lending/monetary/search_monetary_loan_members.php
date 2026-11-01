<?php

declare(strict_types=1);

/* =========================================================
   SEARCH MEMBERS FOR MONETARY LOAN
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

error_reporting(E_ALL);
ini_set("display_errors", "0");

header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


/* =========================================================
   JSON RESPONSE
========================================================= */

function monetaryLoanMemberResponse(
    bool $success,
    string $message,
    array $members = [],
    int $statusCode = 200
): void {

    http_response_code($statusCode);

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
   REQUEST METHOD
========================================================= */

if (
    strtoupper(
        (string) ($_SERVER["REQUEST_METHOD"] ?? "")
    ) !== "GET"
) {
    monetaryLoanMemberResponse(
        false,
        "Invalid request method.",
        [],
        405
    );
}


/* =========================================================
   AUTHENTICATION AND MODULE ACCESS
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {
    monetaryLoanMemberResponse(
        false,
        "Your session has expired. Please log in again.",
        [],
        401
    );
}

if (
    (int) ($_SESSION["lending_access"] ?? 0)
    !== 1
) {
    monetaryLoanMemberResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );
}


/* =========================================================
   SEARCH INPUT
========================================================= */

$search =
    trim(
        (string) ($_GET["query"] ?? "")
    );

$search =
    preg_replace(
        "/\s+/u",
        " ",
        $search
    ) ?? "";

$searchLength =
    function_exists("mb_strlen")
        ? mb_strlen($search, "UTF-8")
        : strlen($search);

if ($searchLength < 3) {
    monetaryLoanMemberResponse(
        false,
        "Enter at least 3 characters to search for a member.",
        [],
        422
    );
}

if ($searchLength > 100) {
    monetaryLoanMemberResponse(
        false,
        "The member search is too long.",
        [],
        422
    );
}

$excludeLoanID =
    isset($_GET["exclude_loan_id"])
        ? (int) $_GET["exclude_loan_id"]
        : 0;


/* =========================================================
   DATABASE
========================================================= */

require_once "../../db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    monetaryLoanMemberResponse(
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
   MEMBER SEARCH QUERY

   Active Monetary Loan type:
   1. Pending application; or
   2. Accepted loan with unpaid principal or interest.

   Active loan types are returned so the interface can block
   only the same Monetary Loan type while keeping other types
   available.
========================================================= */

try {

    $searchPattern =
        "%" . $search . "%";

    $statement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                member.member_id,
                member.member_code,
                member.full_name,
                member.status,
                member.classification,

                (
    SELECT GROUP_CONCAT(
        DISTINCT loan.loan_purpose
        ORDER BY loan.loan_purpose
        SEPARATOR '||'
    )

    FROM monetary_loans AS loan

        WHERE
            loan.member_id =
                member.member_id

            AND loan.is_archived = 0

            AND loan.loan_purpose IN (
                'Crop Production Loan',
                'Dairy Farmers Loan',
                'Emergency Loan',
                'Long-Term Loan'
            )

            AND (
                ? <= 0

                OR

                loan.monetary_loan_id <> ?
            )

            AND (
                loan.status = 'Pending'

                OR

                (
                    loan.status = 'Accepted'

                    AND (
                        loan.outstanding_principal_amount
                            > 0.009

                        OR

                        loan.unpaid_interest_amount
                            > 0.009
                    )
                )
            )
    ) AS active_loan_types

            FROM members AS member

            WHERE
                member.archived = 0

                AND UPPER(
                    TRIM(member.status)
                ) = 'ACTIVE'

                AND UPPER(
                    TRIM(member.classification)
                ) = 'REGULAR'

                AND (
                    member.member_code LIKE ?

                    OR

                    member.full_name LIKE ?
                )

            ORDER BY
                member.full_name ASC,
                member.member_code ASC

            LIMIT 15
            "
        );

    if (!$statement) {
        throw new RuntimeException(
            "Unable to prepare the member search."
        );
    }

    mysqli_stmt_bind_param(
        $statement,
        "iiss",
        $excludeLoanID,
        $excludeLoanID,
        $searchPattern,
        $searchPattern
    );

    if (!mysqli_stmt_execute($statement)) {
        throw new RuntimeException(
            "Unable to execute the member search."
        );
    }

    $result =
        mysqli_stmt_get_result(
            $statement
        );

    if (!$result) {
        throw new RuntimeException(
            "Unable to retrieve the member search results."
        );
    }

    $members = [];

    while (
        $member =
            mysqli_fetch_assoc($result)
    ) {
        $members[] = [
            "member_id" =>
                (int) $member["member_id"],

            "member_code" =>
                (string) $member["member_code"],

            "full_name" =>
                (string) $member["full_name"],

            "status" =>
                (string) $member["status"],

            "classification" =>
    (string) $member["classification"],

    "active_loan_types" =>
        trim(
            (string) (
                $member["active_loan_types"]
                ?? ""
            )
        ) !== ""
            ? explode(
                "||",
                (string) $member["active_loan_types"]
            )
            : []
        ];
    }

    mysqli_free_result($result);

    mysqli_stmt_close($statement);

    monetaryLoanMemberResponse(
        true,
        count($members) > 0
            ? "Members retrieved successfully."
            : "No registered member was found.",
        $members
    );

} catch (Throwable $exception) {

    error_log(
        "Monetary Loan member search error: " .
        $exception->getMessage()
    );

    monetaryLoanMemberResponse(
        false,
        "Unable to search members. Please try again.",
        [],
        500
    );
}