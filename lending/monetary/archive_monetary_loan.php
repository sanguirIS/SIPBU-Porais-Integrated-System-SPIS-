<?php

/* =========================================================
   ARCHIVE MONETARY LOAN
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


/* =========================================================
   RESPONSE
========================================================= */

function archiveMonetaryLoanResponse(
    bool $success,
    string $message,
    array $additionalData = [],
    int $statusCode = 200
): void {

    http_response_code($statusCode);

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


class MonetaryLoanArchiveException extends RuntimeException
{
    private int $httpStatusCode;

    public function __construct(
        string $message,
        int $httpStatusCode = 400
    ) {

        parent::__construct($message);

        $this->httpStatusCode =
            $httpStatusCode;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "POST"
) {

    archiveMonetaryLoanResponse(
        false,
        "Invalid request method.",
        [],
        405
    );
}


$requestedWith =
    $_SERVER["HTTP_X_REQUESTED_WITH"]
    ?? "";

if (
    strtolower($requestedWith) !==
    "xmlhttprequest"
) {

    archiveMonetaryLoanResponse(
        false,
        "Invalid request.",
        [],
        400
    );
}


/* =========================================================
   SESSION ACCESS
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {

    archiveMonetaryLoanResponse(
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

    archiveMonetaryLoanResponse(
        false,
        "You do not have access to the Lending module.",
        [],
        403
    );
}


/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionCsrfToken =
    $_SESSION["csrf_token"]
    ?? "";

$requestCsrfToken =
    $_POST["csrf_token"]
    ?? "";

if (
    !is_string($sessionCsrfToken) ||
    !is_string($requestCsrfToken) ||
    $sessionCsrfToken === "" ||
    !hash_equals(
        $sessionCsrfToken,
        $requestCsrfToken
    )
) {

    archiveMonetaryLoanResponse(
        false,
        "Invalid security token. Please refresh the page and try again.",
        [],
        419
    );
}


/* =========================================================
   MONETARY LOAN ID
========================================================= */

$monetaryLoanID =
    isset($_POST["loan_id"])
        ? filter_var(
            $_POST["loan_id"],
            FILTER_VALIDATE_INT,
            [
                "options" => [
                    "min_range" => 1
                ]
            ]
        )
        : false;

if ($monetaryLoanID === false) {

    archiveMonetaryLoanResponse(
        false,
        "Invalid Monetary Loan ID.",
        [],
        422
    );
}

$monetaryLoanID =
    (int) $monetaryLoanID;


/* =========================================================
   DATABASE AND HISTORY LOGGER
========================================================= */

require_once "../../db.php";
require_once "../../includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    archiveMonetaryLoanResponse(
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

$transactionStarted = false;


/* =========================================================
   ARCHIVE TRANSACTION
========================================================= */

try {

    mysqli_begin_transaction($conn);

    $transactionStarted = true;


    /* -----------------------------------------------------
       VERIFY LOGGED-IN USER
    ----------------------------------------------------- */

    $accountStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                lending_permission

            FROM users

            WHERE user_id = ?

            LIMIT 1
            "
        );

    if (!$accountStatement) {

        throw new RuntimeException(
            "Unable to verify the logged-in account."
        );
    }

    mysqli_stmt_bind_param(
        $accountStatement,
        "i",
        $userID
    );

    if (
        !mysqli_stmt_execute(
            $accountStatement
        )
    ) {

        mysqli_stmt_close(
            $accountStatement
        );

        throw new RuntimeException(
            "Unable to verify the logged-in account."
        );
    }

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

        throw new MonetaryLoanArchiveException(
            "The logged-in account could not be found.",
            401
        );
    }

    $lendingPermission =
        strtolower(
            trim(
                (string) (
                    $account["lending_permission"]
                    ?? "limited"
                )
            )
        );

    if ($lendingPermission !== "full") {

        throw new MonetaryLoanArchiveException(
            "Full Lending Access is required to archive a Monetary Loan.",
            403
        );
    }


    /* -----------------------------------------------------
       LOCK AND RECHECK MONETARY LOAN
    ----------------------------------------------------- */

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                loan.monetary_loan_id,
                loan.status,
                loan.outstanding_principal_amount,
                loan.unpaid_interest_amount,
                loan.fully_paid_at,
                loan.is_archived,
                member.member_code,
                member.full_name

            FROM monetary_loans AS loan

            INNER JOIN members AS member
                ON member.member_id =
                   loan.member_id

            WHERE loan.monetary_loan_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$loanStatement) {

        throw new RuntimeException(
            "Unable to prepare the Monetary Loan record."
        );
    }

    mysqli_stmt_bind_param(
        $loanStatement,
        "i",
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $loanStatement
        )
    ) {

        mysqli_stmt_close(
            $loanStatement
        );

        throw new RuntimeException(
            "Unable to read the Monetary Loan record."
        );
    }

    $loanResult =
        mysqli_stmt_get_result(
            $loanStatement
        );

    $loan =
        mysqli_fetch_assoc(
            $loanResult
        );

    mysqli_stmt_close(
        $loanStatement
    );

    if (!$loan) {

        throw new MonetaryLoanArchiveException(
            "The Monetary Loan record could not be found.",
            404
        );
    }

    if (
        (int) $loan["is_archived"] === 1
    ) {

        throw new MonetaryLoanArchiveException(
            "This Monetary Loan has already been archived.",
            409
        );
    }


    /* -----------------------------------------------------
       ARCHIVE ELIGIBILITY
    ----------------------------------------------------- */

    $status =
        trim(
            (string) $loan["status"]
        );

    $principalBalance =
        round(
            (float) $loan[
                "outstanding_principal_amount"
            ],
            2
        );

    $unpaidInterest =
        round(
            (float) $loan[
                "unpaid_interest_amount"
            ],
            2
        );

    $isRejected =
        $status === "Rejected";

    $isFullyPaid =
        (
            $status === "Accepted" &&
            $principalBalance <= 0.009 &&
            $unpaidInterest <= 0.009 &&
            !empty($loan["fully_paid_at"])
        );

    if (
        !$isRejected &&
        !$isFullyPaid
    ) {

        throw new MonetaryLoanArchiveException(
            "Only Rejected or fully paid Monetary Loans can be archived.",
            409
        );
    }


    /* -----------------------------------------------------
       SOFT ARCHIVE
    ----------------------------------------------------- */

    $archiveStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE monetary_loans

            SET
                is_archived = 1,
                archived_by = ?,
                archived_at = NOW(),
                updated_by = ?

            WHERE
                monetary_loan_id = ?
                AND is_archived = 0
            "
        );

    if (!$archiveStatement) {

        throw new RuntimeException(
            "Unable to prepare the archive operation."
        );
    }

    mysqli_stmt_bind_param(
        $archiveStatement,
        "iii",
        $userID,
        $userID,
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $archiveStatement
        )
    ) {

        mysqli_stmt_close(
            $archiveStatement
        );

        throw new RuntimeException(
            "Unable to archive the Monetary Loan."
        );
    }

    $affectedRows =
        mysqli_stmt_affected_rows(
            $archiveStatement
        );

    mysqli_stmt_close(
        $archiveStatement
    );

    if ($affectedRows !== 1) {

        throw new MonetaryLoanArchiveException(
            "The Monetary Loan could not be archived because its record has changed.",
            409
        );
    }


    /* -----------------------------------------------------
       HISTORY LOG
    ----------------------------------------------------- */

    $loanDisplayID =
        "ML-" .
        str_pad(
            (string) $monetaryLoanID,
            6,
            "0",
            STR_PAD_LEFT
        );

    $finalRemarks =
        $isRejected
            ? "—"
            : "Paid";

    $finalBalance =
        round(
            $principalBalance +
            $unpaidInterest,
            2
        );

    $historyMessage =
        'archived Monetary Loan "' .
        $loanDisplayID .
        '" for member "' .
        $loan["full_name"] .
        '" (' .
        $loan["member_code"] .
        "). Final status: " .
        $status .
        ", final remarks: " .
        $finalRemarks .
        ", and final balance: ₱" .
        number_format(
            $finalBalance,
            2
        ) .
        ".";

    $historyLogged =
        log_activity(
            $conn,
            "Lending",
            "Archived",
            $historyMessage,
            $monetaryLoanID
        );

    if (!$historyLogged) {

        error_log(
            "Unable to save Monetary Loan archive history for " .
            $loanDisplayID
        );
    }


    /* -----------------------------------------------------
       COMMIT
    ----------------------------------------------------- */

    mysqli_commit($conn);

    $transactionStarted = false;

    $archiveReason =
        $isRejected
            ? "Rejected"
            : "Paid";


    /* -----------------------------------------------------
       SUCCESS
    ----------------------------------------------------- */

    archiveMonetaryLoanResponse(
        true,
        "Monetary Loan " .
        $loanDisplayID .
        " was archived successfully.",
        [
            "loan_id" =>
                $monetaryLoanID,

            "loan_display_id" =>
                $loanDisplayID,

            "archive_reason" =>
                $archiveReason
        ]
    );

} catch (
    MonetaryLoanArchiveException $exception
) {

    if ($transactionStarted) {
        mysqli_rollback($conn);
    }

    archiveMonetaryLoanResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getHttpStatusCode()
    );

} catch (Throwable $exception) {

    if ($transactionStarted) {
        mysqli_rollback($conn);
    }

    error_log(
        "Archive Monetary Loan error: " .
        $exception->getMessage()
    );

    archiveMonetaryLoanResponse(
        false,
        "Unable to archive the Monetary Loan. Please try again.",
        [],
        500
    );
}