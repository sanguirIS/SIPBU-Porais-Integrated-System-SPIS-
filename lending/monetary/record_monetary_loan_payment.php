<?php

/* RECORD MONETARY LOAN PAYMENT
   Allocation: interest first, then principal.
   Interest is simple and never compounded. */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");
ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

class MonetaryLoanPaymentException extends RuntimeException
{
    private int $httpStatusCode;

    public function __construct(
        string $message,
        int $httpStatusCode = 422
    ) {
        parent::__construct($message);
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}

function monetaryLoanPaymentResponse(
    bool $success,
    string $message,
    array $data = [],
    int $statusCode = 200
): void {
    http_response_code($statusCode);

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

function monetaryLoanPaymentNormalizeText(
    $value
): string {
    $value = trim((string) $value);

    $normalized = preg_replace(
        "/\s+/u",
        " ",
        $value
    );

    return $normalized !== null
        ? $normalized
        : $value;
}

function monetaryLoanPaymentToCents(
    $value
): int {
    if (!is_numeric($value)) {
        throw new MonetaryLoanPaymentException(
            "Enter a valid payment amount."
        );
    }

    $number = (float) $value;

    if (
        !is_finite($number) ||
        $number <= 0
    ) {
        throw new MonetaryLoanPaymentException(
            "Payment amount must be greater than zero."
        );
    }

    if ($number > 9999999999.99) {
        throw new MonetaryLoanPaymentException(
            "The payment amount exceeds the allowed value."
        );
    }

    return (int) round(
        $number * 100
    );
}

function monetaryLoanPaymentFromCents(
    int $cents
): float {
    return round(
        $cents / 100,
        2
    );
}

function monetaryLoanPaymentElapsedDays(
    string $fromDate,
    string $toDate
): int {
    try {
        $timezone = new DateTimeZone(
            "Asia/Manila"
        );

        $from = new DateTimeImmutable(
            $fromDate,
            $timezone
        );

        $to = new DateTimeImmutable(
            $toDate,
            $timezone
        );
    } catch (Throwable $exception) {
        throw new MonetaryLoanPaymentException(
            "Unable to calculate the payment period.",
            500
        );
    }

    if ($to < $from) {
        throw new MonetaryLoanPaymentException(
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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    monetaryLoanPaymentResponse(
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
    monetaryLoanPaymentResponse(
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
    monetaryLoanPaymentResponse(
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
    monetaryLoanPaymentResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );
}


/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionCSRFToken =
    isset($_SESSION["csrf_token"])
        ? (string) $_SESSION["csrf_token"]
        : "";

$requestCSRFToken =
    isset($_POST["csrf_token"])
        ? (string) $_POST["csrf_token"]
        : "";

if (
    $sessionCSRFToken === "" ||
    $requestCSRFToken === "" ||
    !hash_equals(
        $sessionCSRFToken,
        $requestCSRFToken
    )
) {
    monetaryLoanPaymentResponse(
        false,
        "Your security token is invalid or has expired. Refresh the page and try again.",
        [],
        403
    );
}


/* =========================================================
   INPUT
========================================================= */

$monetaryLoanID =
    isset($_POST["monetary_loan_id"])
        ? (int) $_POST["monetary_loan_id"]
        : 0;

$paymentAmountRaw =
    $_POST["payment_amount"]
    ?? null;

$notes =
    monetaryLoanPaymentNormalizeText(
        $_POST["notes"]
        ?? ""
    );

if ($monetaryLoanID <= 0) {
    monetaryLoanPaymentResponse(
        false,
        "Invalid Monetary Loan ID.",
        [],
        422
    );
}

if (strlen($notes) > 500) {
    monetaryLoanPaymentResponse(
        false,
        "Payment notes must not exceed 500 characters.",
        [],
        422
    );
}

try {
    $paymentCents =
        monetaryLoanPaymentToCents(
            $paymentAmountRaw
        );
} catch (
    MonetaryLoanPaymentException $exception
) {
    monetaryLoanPaymentResponse(
        false,
        $exception->getMessage(),
        [],
        $exception->getHttpStatusCode()
    );
}


/* =========================================================
   DATABASE AND HISTORY LOGGER
========================================================= */

require_once "../../db.php";

require_once dirname(
    __DIR__,
    2
) . "/includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    monetaryLoanPaymentResponse(
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
    mysqli_begin_transaction($conn);

    $transactionStarted = true;


    /* -----------------------------------------------------
       VERIFY LOGGED-IN ACCOUNT
    ----------------------------------------------------- */

    $accountStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                user_id

            FROM users

            WHERE user_id = ?

            LIMIT 1
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

    if (
        !mysqli_stmt_execute(
            $accountStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to validate the logged-in account."
        );
    }

    $account =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $accountStatement
            )
        );

    mysqli_stmt_close(
        $accountStatement
    );

    if (!$account) {
        throw new MonetaryLoanPaymentException(
            "The logged-in account could not be found.",
            401
        );
    }


    /* -----------------------------------------------------
       LOCK MONETARY LOAN
    ----------------------------------------------------- */

    $loanStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                loan.monetary_loan_id,
                loan.status,
                loan.loan_purpose,
                loan.loan_term_years,
                loan.interest_rate,
                loan.term_days,
                loan.release_date,
                loan.due_date,
                loan.last_interest_date,
                loan.outstanding_principal_amount,
                loan.unpaid_interest_amount,
                loan.net_payments_amount,
                loan.is_archived,

                member.member_code,
                member.full_name

            FROM monetary_loans AS loan

            INNER JOIN members AS member
                ON member.member_id =
                   loan.member_id

            WHERE
                loan.monetary_loan_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$loanStatement) {
        throw new RuntimeException(
            "Unable to prepare the Monetary Loan."
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
        throw new RuntimeException(
            "Unable to retrieve the Monetary Loan."
        );
    }

    $loan =
        mysqli_fetch_assoc(
            mysqli_stmt_get_result(
                $loanStatement
            )
        );

    mysqli_stmt_close(
        $loanStatement
    );

    if (!$loan) {
        throw new MonetaryLoanPaymentException(
            "The Monetary Loan record could not be found.",
            404
        );
    }

    if (
        (int) $loan["is_archived"] === 1
    ) {
        throw new MonetaryLoanPaymentException(
            "An archived Monetary Loan cannot receive a payment.",
            409
        );
    }

    if (
        (string) $loan["status"] !==
        "Accepted"
    ) {
        throw new MonetaryLoanPaymentException(
            "Payments can only be recorded for an Accepted Monetary Loan.",
            409
        );
    }


    /* -----------------------------------------------------
       SERVER DATE AND INTEREST PERIOD
    ----------------------------------------------------- */

    $paymentDate =
        date("Y-m-d");

    $paymentDateTime =
        date("Y-m-d H:i:s");

    $calculationFromDate =
        !empty(
            $loan["last_interest_date"]
        )
            ? (string)
              $loan["last_interest_date"]
            : (string) (
                $loan["release_date"]
                ?? ""
            );

    if ($calculationFromDate === "") {
        throw new MonetaryLoanPaymentException(
            "The Monetary Loan has no valid release date.",
            409
        );
    }

    $elapsedDays =
        monetaryLoanPaymentElapsedDays(
            $calculationFromDate,
            $paymentDate
        );


    /* -----------------------------------------------------
       CURRENT BALANCE IN CENTS
    ----------------------------------------------------- */

    $principalBeforeCents =
        (int) round(
            (float)
            $loan[
                "outstanding_principal_amount"
            ] * 100
        );

    $unpaidInterestBeforeCents =
        (int) round(
            (float)
            $loan[
                "unpaid_interest_amount"
            ] * 100
        );

    $netPaymentsBeforeCents =
        (int) round(
            (float)
            $loan[
                "net_payments_amount"
            ] * 100
        );

    if (
        $principalBeforeCents <= 0 &&
        $unpaidInterestBeforeCents <= 0
    ) {
        throw new MonetaryLoanPaymentException(
            "This Monetary Loan is already fully paid.",
            409
        );
    }

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
        throw new MonetaryLoanPaymentException(
            "The saved loan term is invalid.",
            409
        );
    }

    $loanType =
        trim(
            (string) (
                $loan["loan_purpose"]
                ?? ""
            )
        );

    $isLongTermLoan =
        $loanType ===
        "Long-Term Loan";

    $loanTermYears = null;
    $paymentPeriods = 1;

    if ($isLongTermLoan) {
        $loanTermYears =
            isset(
                $loan["loan_term_years"]
            ) &&
            $loan["loan_term_years"] !== null
                ? (int)
                $loan["loan_term_years"]
                : 0;

        if (
            $loanTermYears < 1 ||
            $loanTermYears > 65535
        ) {
            throw new MonetaryLoanPaymentException(
                "The Long-Term Loan has an invalid saved loan term in years.",
                409
            );
        }

        $paymentPeriods =
            $loanTermYears *
            (
                $termDays === 180
                    ? 2
                    : 1
            );
    }


    /* -----------------------------------------------------
    NEW ACCRUED INTEREST

    Current outstanding principal × interest rate
    × elapsed days ÷ term.

    This already produces diminishing-balance interest
    for Long-Term Loans because every payment reduces
    outstanding_principal_amount before the next period.
    ----------------------------------------------------- */

    $newAccruedInterestCents = 0;

    if (
        $principalBeforeCents > 0 &&
        $elapsedDays > 0
    ) {
        $newAccruedInterestCents =
            (int) round(
                $principalBeforeCents *
                ($interestRate / 100) *
                ($elapsedDays / $termDays)
            );
    }

    $totalInterestDueCents =
        $unpaidInterestBeforeCents +
        $newAccruedInterestCents;

    $currentBalanceCents =
        $principalBeforeCents +
        $totalInterestDueCents;


    /* -----------------------------------------------------
       OVERPAYMENT VALIDATION
    ----------------------------------------------------- */

    if (
        $paymentCents >
        $currentBalanceCents
    ) {
        throw new MonetaryLoanPaymentException(
            "Payment cannot exceed the current payoff balance of ₱" .
            number_format(
                $currentBalanceCents / 100,
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

    $principalPaidCents =
        min(
            $paymentCents -
            $interestPaidCents,
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
        monetaryLoanPaymentFromCents(
            $paymentCents
        );

    $principalBefore =
        monetaryLoanPaymentFromCents(
            $principalBeforeCents
        );

    $unpaidInterestBefore =
        monetaryLoanPaymentFromCents(
            $unpaidInterestBeforeCents
        );

    $newAccruedInterest =
        monetaryLoanPaymentFromCents(
            $newAccruedInterestCents
        );

    $totalInterestDue =
        monetaryLoanPaymentFromCents(
            $totalInterestDueCents
        );

    $interestPaid =
        monetaryLoanPaymentFromCents(
            $interestPaidCents
        );

    $principalPaid =
        monetaryLoanPaymentFromCents(
            $principalPaidCents
        );

    $principalAfter =
        monetaryLoanPaymentFromCents(
            $principalAfterCents
        );

    $unpaidInterestAfter =
        monetaryLoanPaymentFromCents(
            $unpaidInterestAfterCents
        );

    $balanceAfter =
        monetaryLoanPaymentFromCents(
            $balanceAfterCents
        );

    $netPaymentsAfter =
        monetaryLoanPaymentFromCents(
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
       UPDATE MONETARY LOAN BALANCE
    ----------------------------------------------------- */

    $updateLoanStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE monetary_loans

            SET
                outstanding_principal_amount = ?,
                unpaid_interest_amount = ?,
                net_payments_amount = ?,
                last_interest_date = ?,
                fully_paid_at = ?,
                updated_by = ?

            WHERE monetary_loan_id = ?
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
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $updateLoanStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to update the Monetary Loan balance."
        );
    }

    mysqli_stmt_close(
        $updateLoanStatement
    );


    /* -----------------------------------------------------
       INSERT PAYMENT TRANSACTION
    ----------------------------------------------------- */

    $paymentStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO monetary_loan_payments (
                monetary_loan_id,
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
            )
            VALUES (
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
            "Unable to prepare the payment transaction."
        );
    }

    mysqli_stmt_bind_param(
        $paymentStatement,
        "isdssidddddddddsi",
        $monetaryLoanID,
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
            "Unable to record the payment transaction."
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
       COMMIT
    ----------------------------------------------------- */

    mysqli_commit($conn);

    $transactionStarted = false;


    /* -----------------------------------------------------
       CENTRAL HISTORY LOG
    ----------------------------------------------------- */

    $loanDisplayID =
        "ML-" .
        str_pad(
            (string) $monetaryLoanID,
            6,
            "0",
            STR_PAD_LEFT
        );

    $paymentDisplayID =
        "PAY-" .
        str_pad(
            (string) $paymentID,
            6,
            "0",
            STR_PAD_LEFT
        );

    $historyMessage =
        'recorded payment "' .
        $paymentDisplayID .
        '" amounting to ₱' .
        number_format(
            $paymentAmount,
            2
        ) .
        ' for Monetary Loan "' .
        $loanDisplayID .
        '" of member "' .
        $loan["full_name"] .
        '" (' .
        $loan["member_code"] .
        '). Loan Type: ' .
        ($loanType !== ""
            ? $loanType
            : "Monetary Loan") .
        (
            $isLongTermLoan
                ? ", term " .
                $loanTermYears .
                (
                    $loanTermYears === 1
                        ? " year"
                        : " years"
                ) .
                ", " .
                $paymentPeriods .
                (
                    $paymentPeriods === 1
                        ? " payment period"
                        : " payment periods"
                )
                : ""
        ) .
        '. Interest paid ₱' .
        number_format(
            $interestPaid,
            2
        ) .
        ', principal paid ₱' .
        number_format(
            $principalPaid,
            2
        ) .
        ', calculated for ' .
        $elapsedDays .
        (
            $elapsedDays === 1
                ? ' day'
                : ' days'
        ) .
        ', and remaining balance ₱' .
        number_format(
            $balanceAfter,
            2
        ) .
        '.';

    if ($balanceAfterCents <= 0) {
        $historyMessage .=
            " The Monetary Loan is now fully paid.";
    }

    $historyLogged =
        log_activity(
            $conn,
            "Lending",
            "Updated",
            $historyMessage,
            $loanDisplayID
        );

    if (!$historyLogged) {
        error_log(
            "Unable to save Monetary Loan payment history for " .
            $loanDisplayID
        );
    }


    /* -----------------------------------------------------
       SUCCESS RESPONSE
    ----------------------------------------------------- */

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
            " The Monetary Loan is now fully paid.";
    } else {
        $message .=
            " Remaining balance as of today: ₱" .
            number_format(
                $balanceAfter,
                2
            ) .
            ".";
    }

    monetaryLoanPaymentResponse(
        true,
        $message,
        [
            "payment_id" =>
                $paymentID,

            "payment_date" =>
                $paymentDate,

            "elapsed_days" =>
                $elapsedDays,

            "loan_type" =>
                $loanType,

            "loan_term_years" =>
                $isLongTermLoan
                    ? $loanTermYears
                    : null,

            "payment_periods" =>
                $isLongTermLoan
                    ? $paymentPeriods
                    : null,

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
    MonetaryLoanPaymentException $exception
) {
    if ($transactionStarted) {
        mysqli_rollback($conn);
    }

    monetaryLoanPaymentResponse(
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
        "Record Monetary Loan payment error: " .
        $exception->getMessage()
    );

    monetaryLoanPaymentResponse(
        false,
        "Unable to record the payment. Please try again.",
        [],
        500
    );
}