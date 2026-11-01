<?php

/* =========================================================
   RETRIEVE ARCHIVED PRODUCT LOAN

   Rules:
   - POST/AJAX request only
   - Full Lending Access only
   - Only archived Rejected or fully paid loans can return
   - Payments, reversals, attachments, and history remain
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

function retrieveProductLoanResponse(
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


class ProductLoanRetrieveException extends RuntimeException
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

    retrieveProductLoanResponse(
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

    retrieveProductLoanResponse(
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

    retrieveProductLoanResponse(
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
    retrieveProductLoanResponse(
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
    retrieveProductLoanResponse(
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

    retrieveProductLoanResponse(
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

require_once "../db.php";
require_once "../includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    retrieveProductLoanResponse(
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
   RESTORE TRANSACTION
========================================================= */

$transactionStarted = false;

try {

    mysqli_begin_transaction(
        $conn
    );

    $transactionStarted = true;


    /* -----------------------------------------------------
       VERIFY CURRENT USER AND PERMISSION
    ----------------------------------------------------- */

    $accountStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                status,
                archived,
                archives_access,
                lending_access,
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

    if (
        !$account
        ||
        (int) (
            $account["archived"]
            ?? 1
        ) !== 0
        ||
        (
            $account["status"]
            ?? ""
        ) !== "Active"
        ||
        (int) (
            $account["archives_access"]
            ?? 0
        ) !== 1
        ||
        (int) (
            $account["lending_access"]
            ?? 0
        ) !== 1
    ) {

        throw new ProductLoanRetrieveException(
            "You do not have permission to retrieve archived Product Loans.",
            403
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

        throw new ProductLoanRetrieveException(
            "Full Lending Access is required to retrieve a Product Loan.",
            403
        );

    }


    /* -----------------------------------------------------
       LOCK AND RECHECK ARCHIVED PRODUCT LOAN
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
            "Unable to prepare the archived Product Loan."
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

        throw new ProductLoanRetrieveException(
            "The Product Loan record could not be found.",
            404
        );

    }

    if (
        (int) $loan["is_archived"] !== 1
    ) {

        throw new ProductLoanRetrieveException(
            "This Product Loan is not currently archived.",
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

        throw new ProductLoanRetrieveException(
            "Only archived Rejected or fully paid Product Loans can be retrieved.",
            409
        );

    }


    /* -----------------------------------------------------
       RESTORE PRODUCT LOAN
    ----------------------------------------------------- */

    $restoreStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE product_loans

            SET
                is_archived = 0,
                archived_by = NULL,
                archived_at = NULL,
                updated_by = ?

            WHERE
                product_loan_id = ?
                AND is_archived = 1
            "
        );

    if (!$restoreStatement) {

        throw new RuntimeException(
            "Unable to prepare the retrieve operation."
        );

    }

    mysqli_stmt_bind_param(
        $restoreStatement,
        "ii",
        $userID,
        $productLoanID
    );

    if (
        !mysqli_stmt_execute(
            $restoreStatement
        )
    ) {

        throw new RuntimeException(
            "Unable to retrieve the Product Loan."
        );

    }

    $affectedRows =
        mysqli_stmt_affected_rows(
            $restoreStatement
        );

    mysqli_stmt_close(
        $restoreStatement
    );

    if ($affectedRows !== 1) {

        throw new ProductLoanRetrieveException(
            "The Product Loan could not be retrieved because its record has changed.",
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
            'retrieved archived Product Loan "' .
            $loanDisplayID .
            '" for member "' .
            $loan["full_name"] .
            '" (' .
            $loan["member_code"] .
            "). Restored status: " .
            $status .
            ", remarks: " .
            $finalRemarks .
            ", and balance: ₱" .
            number_format(
                $finalBalance,
                2
            ) .
            ".";

        log_activity(
            $conn,
            "Lending",
            "Retrieved",
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


    /* -----------------------------------------------------
       SUCCESS
    ----------------------------------------------------- */

    retrieveProductLoanResponse(
        true,
        "Product Loan " .
        $loanDisplayID .
        " was retrieved successfully.",
        [
            "loan_id" =>
                $productLoanID,

            "loan_display_id" =>
                $loanDisplayID,

            "redirect_url" =>
                "archive.php?module=lending&success=restored"
        ]
    );

} catch (ProductLoanRetrieveException $exception) {

    if ($transactionStarted) {

        mysqli_rollback(
            $conn
        );

    }

    retrieveProductLoanResponse(
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
        "Retrieve Product Loan error: " .
        $exception->getMessage()
    );

    retrieveProductLoanResponse(
        false,
        "Unable to retrieve the Product Loan. Please try again.",
        [],
        500
    );

}