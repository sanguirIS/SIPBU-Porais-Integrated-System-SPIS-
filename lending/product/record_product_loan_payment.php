<?php

/* =========================================================
   RECORD PRODUCT LOAN PAYMENT

   Payment allocation:
   1. Unpaid and newly accrued interest
   2. Outstanding principal

   Interest is never compounded.
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

ini_set("display_errors", "0");
error_reporting(E_ALL);

header(
    "Content-Type: application/json; charset=utf-8"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   EXCEPTION
========================================================= */

class ProductLoanPaymentException extends RuntimeException
{
    private $responseStatus;

    public function __construct(
        string $message,
        int $responseStatus = 422
    ) {

        parent::__construct($message);

        $this->responseStatus =
            $responseStatus;

    }

    public function getResponseStatus(): int
    {

        return $this->responseStatus;

    }
}


/* =========================================================
   RESPONSE
========================================================= */

function productLoanPaymentResponse(
    bool $success,
    string $message,
    array $data = [],
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
            $data
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    exit();

}


/* =========================================================
   HELPERS
========================================================= */

function paymentNormalizeText(
    $value
): string {

    $value =
        trim(
            (string) $value
        );

    $normalized =
        preg_replace(
            "/\s+/u",
            " ",
            $value
        );

    return $normalized !== null
        ? $normalized
        : $value;

}


function paymentToCents(
    $value
): int {

    if (!is_numeric($value)) {

        throw new ProductLoanPaymentException(
            "Enter a valid payment amount."
        );

    }

    $number =
        (float) $value;

    if (
        !is_finite($number) ||
        $number <= 0
    ) {

        throw new ProductLoanPaymentException(
            "Payment amount must be greater than zero."
        );

    }

    if ($number > 9999999999.99) {

        throw new ProductLoanPaymentException(
            "The payment amount exceeds the allowed value."
        );

    }

    return (int) round(
        $number * 100
    );

}


function paymentCentsToAmount(
    int $cents
): float {

    return round(
        $cents / 100,
        2
    );

}


function paymentElapsedDays(
    string $fromDate,
    string $toDate
): int {

    try {

        $timezone =
            new DateTimeZone(
                "Asia/Manila"
            );

        $from =
            new DateTimeImmutable(
                $fromDate,
                $timezone
            );

        $to =
            new DateTimeImmutable(
                $toDate,
                $timezone
            );

    } catch (Throwable $exception) {

        throw new ProductLoanPaymentException(
            "Unable to calculate the payment period.",
            500
        );

    }

    if ($to < $from) {

        throw new ProductLoanPaymentException(
            "The server date is earlier than the previous loan transaction date. Check the server clock before recording a payment.",
            409
        );

    }

    return (int) $from
        ->diff($to)
        ->days;

}


/* =========================================================
   REQUEST
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "POST"
) {

    productLoanPaymentResponse(
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

    productLoanPaymentResponse(
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
        ? (int)
            $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {

    productLoanPaymentResponse(
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
    productLoanPaymentResponse(
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
    productLoanPaymentResponse(
        false,
        "The security token is invalid or has expired. Refresh the page and try again.",
        [],
        419
    );
}


/* =========================================================
   INPUT
========================================================= */

$productLoanID =
    isset($_POST["product_loan_id"])
        ? (int)
            $_POST["product_loan_id"]
        : 0;

$paymentAmountRaw =
    $_POST["payment_amount"]
    ?? null;

$notes =
    paymentNormalizeText(
        $_POST["notes"]
        ?? ""
    );

if ($productLoanID <= 0) {

    productLoanPaymentResponse(
        false,
        "Invalid Product Loan ID.",
        [],
        422
    );

}

if (
    strlen($notes) > 500
) {

    productLoanPaymentResponse(
        false,
        "Payment notes must not exceed 500 characters.",
        [],
        422
    );

}

try {

    $paymentCents =
        paymentToCents(
            $paymentAmountRaw
        );

} catch (
    ProductLoanPaymentException $exception
) {

    productLoanPaymentResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getResponseStatus()
    );

}


/* =========================================================
   DATABASE
========================================================= */

require_once "../../db.php";
require_once "../../includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    productLoanPaymentResponse(
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
   PAYMENT TRANSACTION
========================================================= */

try {

    mysqli_begin_transaction(
        $conn
    );

    $transactionStarted = true;


    /* -----------------------------------------------------
       VERIFY ACCOUNT
    ----------------------------------------------------- */

    $accountStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id,
                name,
                lending_permission

            FROM users

            WHERE user_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$accountStatement) {

        throw new RuntimeException(
            "Unable to prepare account validation."
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

        throw new ProductLoanPaymentException(
            "The logged-in account could not be found.",
            401
        );

    }


    /* -----------------------------------------------------
       LOCK PRODUCT LOAN
    ----------------------------------------------------- */

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT

                loan.product_loan_id,
                loan.status,
                loan.interest_rate,
                loan.term_days,
                loan.delivered_date,
                loan.due_date,
                loan.last_interest_date,
                loan.outstanding_principal_amount,
                loan.unpaid_interest_amount,
                loan.net_payments_amount,
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
            "Unable to prepare the Product Loan."
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

        throw new ProductLoanPaymentException(
            "The Product Loan record could not be found.",
            404
        );

    }

    if (
        (int) $loan["is_archived"] === 1
    ) {

        throw new ProductLoanPaymentException(
            "An archived Product Loan cannot receive a payment.",
            409
        );

    }

    if (
        $loan["status"] !==
        "Delivered"
    ) {

        throw new ProductLoanPaymentException(
            "Payments can only be recorded after the products are Delivered.",
            409
        );

    }


    /* -----------------------------------------------------
       SERVER DATE
    ----------------------------------------------------- */

    $paymentDate =
        date("Y-m-d");

    $paymentDateTime =
        date("Y-m-d H:i:s");

    $calculationFromDate =
        $loan["last_interest_date"]
        ?:
        $loan["delivered_date"];

    if (empty($calculationFromDate)) {

        throw new ProductLoanPaymentException(
            "The Product Loan has no valid delivery date.",
            409
        );

    }

    $elapsedDays =
        paymentElapsedDays(
            $calculationFromDate,
            $paymentDate
        );


    /* -----------------------------------------------------
       CURRENT LOAN AMOUNTS IN CENTS
    ----------------------------------------------------- */

    $principalBeforeCents =
        (int) round(
            (float)
            $loan[
                "outstanding_principal_amount"
            ] *
            100
        );

    $unpaidInterestBeforeCents =
        (int) round(
            (float)
            $loan[
                "unpaid_interest_amount"
            ] *
            100
        );

    $netPaymentsBeforeCents =
        (int) round(
            (float)
            $loan[
                "net_payments_amount"
            ] *
            100
        );

    if (
        $principalBeforeCents <= 0 &&
        $unpaidInterestBeforeCents <= 0
    ) {

        throw new ProductLoanPaymentException(
            "This Product Loan is already fully paid.",
            409
        );

    }


    /* -----------------------------------------------------
       NEW ACCRUED INTEREST

       principal × rate × elapsed days ÷ term
    ----------------------------------------------------- */

    $interestRate =
        (float)
        $loan["interest_rate"];

    $termDays =
        (int)
        $loan["term_days"];

    if (
        !in_array(
            $termDays,
            [180, 360],
            true
        )
    ) {

        throw new ProductLoanPaymentException(
            "The saved loan term is invalid.",
            409
        );

    }

    $newAccruedInterestCents =
        0;

    if (
        $principalBeforeCents > 0 &&
        $elapsedDays > 0
    ) {

        $newAccruedInterestCents =
            (int) round(
                $principalBeforeCents *
                (
                    $interestRate /
                    100
                ) *
                (
                    $elapsedDays /
                    $termDays
                )
            );

    }

    $totalInterestDueCents =
        $unpaidInterestBeforeCents +
        $newAccruedInterestCents;

    $currentBalanceCents =
        $principalBeforeCents +
        $totalInterestDueCents;


    /* -----------------------------------------------------
       OVERPAYMENT CHECK
    ----------------------------------------------------- */

    if (
        $paymentCents >
        $currentBalanceCents
    ) {

        throw new ProductLoanPaymentException(
            "Payment cannot exceed the current payoff balance of " .
            "₱" .
            number_format(
                $currentBalanceCents /
                100,
                2
            ) .
            ".",
            422
        );

    }


    /* -----------------------------------------------------
       PAYMENT ALLOCATION

       Interest first, then principal.
    ----------------------------------------------------- */

    $interestPaidCents =
        min(
            $paymentCents,
            $totalInterestDueCents
        );

    $remainingPaymentCents =
        $paymentCents -
        $interestPaidCents;

    $principalPaidCents =
        min(
            $remainingPaymentCents,
            $principalBeforeCents
        );

    $unpaidInterestAfterCents =
        $totalInterestDueCents -
        $interestPaidCents;

    $principalAfterCents =
        $principalBeforeCents -
        $principalPaidCents;

    $balanceAfterCents =
        $principalAfterCents +
        $unpaidInterestAfterCents;

    $netPaymentsAfterCents =
        $netPaymentsBeforeCents +
        $paymentCents;


    /* -----------------------------------------------------
       CONVERT TO DATABASE AMOUNTS
    ----------------------------------------------------- */

    $paymentAmount =
        paymentCentsToAmount(
            $paymentCents
        );

    $principalBefore =
        paymentCentsToAmount(
            $principalBeforeCents
        );

    $unpaidInterestBefore =
        paymentCentsToAmount(
            $unpaidInterestBeforeCents
        );

    $newAccruedInterest =
        paymentCentsToAmount(
            $newAccruedInterestCents
        );

    $totalInterestDue =
        paymentCentsToAmount(
            $totalInterestDueCents
        );

    $interestPaid =
        paymentCentsToAmount(
            $interestPaidCents
        );

    $principalPaid =
        paymentCentsToAmount(
            $principalPaidCents
        );

    $principalAfter =
        paymentCentsToAmount(
            $principalAfterCents
        );

    $unpaidInterestAfter =
        paymentCentsToAmount(
            $unpaidInterestAfterCents
        );

    $balanceAfter =
        paymentCentsToAmount(
            $balanceAfterCents
        );

    $netPaymentsAfter =
        paymentCentsToAmount(
            $netPaymentsAfterCents
        );

    $fullyPaidAt =
        $balanceAfterCents <= 0
            ? $paymentDateTime
            : null;

    $storedNotes =
        $notes !== ""
            ? $notes
            : null;


    /* -----------------------------------------------------
       UPDATE LOAN
    ----------------------------------------------------- */

    $updateLoanStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE product_loans

            SET
                outstanding_principal_amount = ?,
                unpaid_interest_amount = ?,
                net_payments_amount = ?,
                last_interest_date = ?,
                fully_paid_at = ?,
                updated_by = ?

            WHERE product_loan_id = ?
            "
        );

    if (!$updateLoanStatement) {

        throw new RuntimeException(
            "Unable to prepare the updated loan balance."
        );

    }

    mysqli_stmt_bind_param(
        $updateLoanStatement,
        "dddssii",
        $principalAfter,
        $unpaidInterestAfter,
        $netPaymentsAfter,
        $paymentDate,
        $fullyPaidAt,
        $userID,
        $productLoanID
    );

    if (
        !mysqli_stmt_execute(
            $updateLoanStatement
        )
    ) {

        throw new RuntimeException(
            "Unable to update the Product Loan balance."
        );

    }

    mysqli_stmt_close(
        $updateLoanStatement
    );


    /* -----------------------------------------------------
       INSERT PAYMENT HISTORY
    ----------------------------------------------------- */

    $paymentStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO product_loan_payments (

                product_loan_id,
                transaction_type,
                payment_date,
                payment_amount,
                calculation_from_date,
                calculation_to_date,
                elapsed_days,
                principal_before_amount,
                unpaid_interest_before_amount,
                newly_accrued_interest_amount,
                total_interest_due_amount,
                interest_paid_amount,
                principal_paid_amount,
                principal_after_amount,
                unpaid_interest_after_amount,
                balance_after_amount,
                notes,
                recorded_by

            ) VALUES (
                ?,
                'Payment',
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
                ?,
                ?
            )
            "
        );

    if (!$paymentStatement) {

        throw new RuntimeException(
            "Unable to prepare the payment history."
        );

    }

    mysqli_stmt_bind_param(
        $paymentStatement,
        "isdssidddddddddsi",
        $productLoanID,
        $paymentDate,
        $paymentAmount,
        $calculationFromDate,
        $paymentDate,
        $elapsedDays,
        $principalBefore,
        $unpaidInterestBefore,
        $newAccruedInterest,
        $totalInterestDue,
        $interestPaid,
        $principalPaid,
        $principalAfter,
        $unpaidInterestAfter,
        $balanceAfter,
        $storedNotes,
        $userID
    );

    if (
        !mysqli_stmt_execute(
            $paymentStatement
        )
    ) {

        throw new RuntimeException(
            "Unable to record the payment history."
        );

    }

    $paymentID =
        (int)
        mysqli_insert_id(
            $conn
        );

    mysqli_stmt_close(
        $paymentStatement
    );


    /* -----------------------------------------------------
    SAVE PAYMENT HISTORY LOG
    ----------------------------------------------------- */

    $loanReference =
        "PL-" .
        str_pad(
            (string) $productLoanID,
            6,
            "0",
            STR_PAD_LEFT
        );

    $paymentReference =
        "PAY-" .
        str_pad(
            (string) $paymentID,
            6,
            "0",
            STR_PAD_LEFT
        );

    $historyMessage =
        'recorded payment "' .
        $paymentReference .
        '" amounting to ₱' .
        number_format(
            $paymentAmount,
            2
        ) .
        ' for Product Loan "' .
        $loanReference .
        '" of member "' .
        $loan["full_name"] .
        '" (' .
        $loan["member_code"] .
        "). Interest paid ₱" .
        number_format(
            $interestPaid,
            2
        ) .
        ", principal paid ₱" .
        number_format(
            $principalPaid,
            2
        ) .
        ", calculated for " .
        number_format(
            $elapsedDays
        ) .
        (
            $elapsedDays === 1
                ? " day"
                : " days"
        ) .
        ", and remaining balance ₱" .
        number_format(
            $balanceAfter,
            2
        ) .
        ".";

    if ($balanceAfterCents <= 0) {
        $historyMessage .=
            " The Product Loan is now fully paid.";
    }

    if ($notes !== "") {
        $historyMessage .=
            ' Notes: "' .
            $notes .
            '".';
    }

    log_activity(
        $conn,
        "Lending",
        "Updated",
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

    $message =
        "Payment of ₱" .
        number_format(
            $paymentAmount,
            2
        ) .
        " was recorded for " .
        $loan["full_name"] .
        ".";

    if ($balanceAfterCents <= 0) {

        $message .=
            " The Product Loan is now fully paid.";

    } else {

        $message .=
            " Remaining balance as of today: ₱" .
            number_format(
                $balanceAfter,
                2
            ) .
            ".";

    }

    productLoanPaymentResponse(
        true,
        $message,
        [
            "payment_id" =>
                $paymentID,

            "payment_date" =>
                $paymentDate,

            "elapsed_days" =>
                $elapsedDays,

            "payment_amount" =>
                number_format(
                    $paymentAmount,
                    2,
                    ".",
                    ""
                ),

            "interest_paid" =>
                number_format(
                    $interestPaid,
                    2,
                    ".",
                    ""
                ),

            "principal_paid" =>
                number_format(
                    $principalPaid,
                    2,
                    ".",
                    ""
                ),

            "remaining_principal" =>
                number_format(
                    $principalAfter,
                    2,
                    ".",
                    ""
                ),

            "remaining_unpaid_interest" =>
                number_format(
                    $unpaidInterestAfter,
                    2,
                    ".",
                    ""
                ),

            "remaining_balance" =>
                number_format(
                    $balanceAfter,
                    2,
                    ".",
                    ""
                ),

            "fully_paid" =>
                $balanceAfterCents <= 0
        ],
        201
    );

} catch (
    ProductLoanPaymentException $exception
) {

    if ($transactionStarted) {

        mysqli_rollback(
            $conn
        );

    }

    productLoanPaymentResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getResponseStatus()
    );

} catch (Throwable $exception) {

    if ($transactionStarted) {

        mysqli_rollback(
            $conn
        );

    }

    error_log(
        "Record Product Loan payment error: " .
        $exception->getMessage()
    );

    productLoanPaymentResponse(
        false,
        "Unable to record the payment. Please try again.",
        [],
        500
    );

}