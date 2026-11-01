<?php

/* =========================================================
   ARCHIVE PRODUCT LOAN

   Rules:
   - Full Lending Access only
   - Rejected loans may be archived
   - Delivered loans may be archived only when fully paid
   - Records are soft archived and remain recoverable
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
   RESPONSE
========================================================= */

function archiveProductLoanResponse(
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


class ProductLoanArchiveException extends RuntimeException
{
    private int $httpStatusCode;

    public function __construct(
        string $message,
        int $httpStatusCode = 400
    ) {

        parent::__construct(
            $message
        );

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

    archiveProductLoanResponse(
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

    archiveProductLoanResponse(
        false,
        "Invalid request.",
        [],
        400
    );

}


/* =========================================================
   SESSION
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {

    archiveProductLoanResponse(
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
    archiveProductLoanResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );
}

$sessionCsrfToken =
    (string) (
        $_SESSION["csrf_token"]
        ?? ""
    );

$submittedCsrfToken =
    (string) (
        $_POST["csrf_token"]
        ?? ""
    );

if (
    $sessionCsrfToken === "" ||
    $submittedCsrfToken === "" ||
    !hash_equals(
        $sessionCsrfToken,
        $submittedCsrfToken
    )
) {
    archiveProductLoanResponse(
        false,
        "The security token is invalid or has expired. Refresh the page and try again.",
        [],
        419
    );
}


/* =========================================================
   PRODUCT LOAN ID
========================================================= */

$productLoanID =
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

if ($productLoanID === false) {

    archiveProductLoanResponse(
        false,
        "Invalid Product Loan ID.",
        [],
        422
    );

}

$productLoanID =
    (int) $productLoanID;


/* =========================================================
   DATABASE
========================================================= */

require_once "../../db.php";
require_once "../../includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    archiveProductLoanResponse(
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
   ARCHIVE TRANSACTION
========================================================= */

$transactionStarted = false;

try {

    mysqli_begin_transaction(
        $conn
    );

    $transactionStarted = true;


    /* -----------------------------------------------------
       VERIFY CURRENT USER AND LENDING PERMISSION
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

        throw new ProductLoanArchiveException(
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

        throw new ProductLoanArchiveException(
            "Full Lending Access is required to archive a Product Loan.",
            403
        );

    }


    /* -----------------------------------------------------
       LOCK AND RECHECK PRODUCT LOAN
    ----------------------------------------------------- */

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                loan.product_loan_id,
                loan.status,
                loan.outstanding_principal_amount,
                loan.unpaid_interest_amount,
                loan.fully_paid_at,
                loan.is_archived,
                member.member_code,
                member.full_name

            FROM product_loans AS loan

            INNER JOIN members AS member
                ON member.member_id =
                   loan.member_id

            WHERE loan.product_loan_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$loanStatement) {

        throw new RuntimeException(
            "Unable to prepare the Product Loan record."
        );

    }

    mysqli_stmt_bind_param(
        $loanStatement,
        "i",
        $productLoanID
    );

    mysqli_stmt_execute(
        $loanStatement
    );

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

        throw new ProductLoanArchiveException(
            "The Product Loan record could not be found.",
            404
        );

    }

    if (
        (int) $loan["is_archived"] === 1
    ) {

        throw new ProductLoanArchiveException(
            "This Product Loan has already been archived.",
            409
        );

    }

    $status =
        (string) $loan["status"];

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
            $status === "Delivered" &&
            $principalBalance <= 0.009 &&
            $unpaidInterest <= 0.009 &&
            !empty($loan["fully_paid_at"])
        );

    if (
        !$isRejected &&
        !$isFullyPaid
    ) {

        throw new ProductLoanArchiveException(
            "Only Rejected or fully paid Product Loans can be archived.",
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
            UPDATE product_loans

            SET
                is_archived = 1,
                archived_by = ?,
                archived_at = NOW(),
                updated_by = ?

            WHERE
                product_loan_id = ?
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
        $productLoanID
    );

    if (
        !mysqli_stmt_execute(
            $archiveStatement
        )
    ) {

        throw new RuntimeException(
            "Unable to archive the Product Loan."
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

        throw new ProductLoanArchiveException(
            "The Product Loan could not be archived because its record has changed.",
            409
        );

    }

    /* -----------------------------------------------------
    CENTRAL HISTORY LOG
    ----------------------------------------------------- */

    $loanDisplayID =
        "PL-" .
        str_pad(
            (string) $productLoanID,
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
        'archived Product Loan "' .
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

    log_activity(
        $conn,
        "Lending",
        "Archived",
        $historyMessage,
        $productLoanID
    );


    /* -----------------------------------------------------
    COMMIT
    ----------------------------------------------------- */

    mysqli_commit(
        $conn
    );

    $transactionStarted = false;

    $archiveReason =
        $isRejected
            ? "Rejected"
            : "Paid";


    /* -----------------------------------------------------
       SUCCESS
    ----------------------------------------------------- */

    archiveProductLoanResponse(
        true,
        "Product Loan " .
        $loanDisplayID .
        " was archived successfully.",
        [
            "loan_id" =>
                $productLoanID,

            "loan_display_id" =>
                $loanDisplayID,

            "archive_reason" =>
                $archiveReason
        ]
    );

} catch (ProductLoanArchiveException $exception) {

    if ($transactionStarted) {

        mysqli_rollback(
            $conn
        );

    }

    archiveProductLoanResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getHttpStatusCode()
    );

} catch (Throwable $exception) {

    if ($transactionStarted) {

        mysqli_rollback(
            $conn
        );

    }

    error_log(
        "Archive Product Loan error: " .
        $exception->getMessage()
    );

    archiveProductLoanResponse(
        false,
        "Unable to archive the Product Loan. Please try again.",
        [],
        500
    );

}