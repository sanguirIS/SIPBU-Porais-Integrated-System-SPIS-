<?php

/* =========================================================
   REVERSE MONETARY LOAN PAYMENT

   Rules:
   - Full Lending Access only
   - Only the latest unreversed Payment may be reversed
   - Original Payment remains in the audit trail
   - A separate Reversal record is created
   - Remaining valid payments are replayed
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

ini_set("display_errors", "0");
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


class MonetaryLoanReversalException extends RuntimeException
{
    private int $httpStatusCode;

    public function __construct(
        string $message,
        int $httpStatusCode = 422
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


function monetaryLoanReversalResponse(
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


function monetaryLoanReversalNormalizeText(
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


function monetaryLoanReversalToCents(
    $value
): int {
    return (int) round(
        ((float) $value) * 100
    );
}


function monetaryLoanReversalFromCents(
    int $cents
): float {
    return round(
        $cents / 100,
        2
    );
}


function monetaryLoanReversalElapsedDays(
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
        throw new MonetaryLoanReversalException(
            "Unable to recalculate the payment history.",
            500
        );
    }

    if ($to < $from) {
        throw new MonetaryLoanReversalException(
            "A saved payment date is earlier than the previous loan transaction date.",
            409
        );
    }

    return (int) $from
        ->diff($to)
        ->days;
}


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "POST"
) {
    monetaryLoanReversalResponse(
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
    monetaryLoanReversalResponse(
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
    monetaryLoanReversalResponse(
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
    monetaryLoanReversalResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );
}


/* =========================================================
   CSRF
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
    monetaryLoanReversalResponse(
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

$paymentID =
    isset($_POST["payment_id"])
        ? (int) $_POST["payment_id"]
        : 0;

$reversalReason =
    monetaryLoanReversalNormalizeText(
        $_POST["reversal_reason"]
        ?? ""
    );

if ($monetaryLoanID <= 0) {
    monetaryLoanReversalResponse(
        false,
        "Invalid Monetary Loan ID.",
        [],
        422
    );
}

if ($paymentID <= 0) {
    monetaryLoanReversalResponse(
        false,
        "Invalid payment ID.",
        [],
        422
    );
}

if ($reversalReason === "") {
    monetaryLoanReversalResponse(
        false,
        "A reversal reason is required.",
        [],
        422
    );
}

if (
    strlen($reversalReason) >
    500
) {
    monetaryLoanReversalResponse(
        false,
        "The reversal reason must not exceed 500 characters.",
        [],
        422
    );
}


/* =========================================================
   DATABASE AND HISTORY LOGGER
========================================================= */

require_once "../../db.php";

require_once dirname(__DIR__, 2) .
    "/includes/history_logger.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    monetaryLoanReversalResponse(
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
   REVERSAL TRANSACTION
========================================================= */

try {
    mysqli_begin_transaction(
        $conn
    );

    $transactionStarted = true;


    /* -----------------------------------------------------
       VERIFY FULL LENDING ACCESS
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

    if (
        !mysqli_stmt_execute(
            $accountStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to validate the logged-in account."
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
        throw new MonetaryLoanReversalException(
            "The logged-in account could not be found.",
            401
        );
    }

    $lendingPermission =
        strtolower(
            trim(
                (string)
                $account["lending_permission"]
            )
        );

    if ($lendingPermission !== "full") {
        throw new MonetaryLoanReversalException(
            "Full Lending Access is required to reverse a payment.",
            403
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
                loan.principal_amount,
                loan.release_date,
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

            WHERE loan.monetary_loan_id = ?

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
        throw new MonetaryLoanReversalException(
            "The Monetary Loan record could not be found.",
            404
        );
    }

    if (
        (int) $loan["is_archived"] ===
        1
    ) {
        throw new MonetaryLoanReversalException(
            "A payment from an archived Monetary Loan cannot be reversed.",
            409
        );
    }

    if (
        (string) $loan["status"] !==
        "Accepted"
    ) {
        throw new MonetaryLoanReversalException(
            "Payments can only be reversed from an Accepted Monetary Loan.",
            409
        );
    }

    $releaseDate =
        (string) (
            $loan["release_date"]
            ?? ""
        );

    if ($releaseDate === "") {
        throw new MonetaryLoanReversalException(
            "The Monetary Loan has no valid release date.",
            409
        );
    }

    $termDays =
        (int) $loan["term_days"];

    if (
        !in_array(
            $termDays,
            [180, 360],
            true
        )
    ) {
        throw new MonetaryLoanReversalException(
            "The saved loan term is invalid.",
            409
        );
    }

    $interestRate =
        (float) $loan["interest_rate"];

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
            throw new MonetaryLoanReversalException(
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
    LOCK SELECTED PAYMENT
    ----------------------------------------------------- */

    $targetStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                monetary_loan_payment_id,
                monetary_loan_id,
                transaction_type,
                payment_date,
                payment_amount,
                interest_paid_amount,
                principal_paid_amount,
                is_reversed

            FROM monetary_loan_payments

            WHERE monetary_loan_payment_id = ?
              AND monetary_loan_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$targetStatement) {
        throw new RuntimeException(
            "Unable to prepare the selected payment."
        );
    }

    mysqli_stmt_bind_param(
        $targetStatement,
        "ii",
        $paymentID,
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $targetStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to retrieve the selected payment."
        );
    }

    $targetResult =
        mysqli_stmt_get_result(
            $targetStatement
        );

    $targetPayment =
        mysqli_fetch_assoc(
            $targetResult
        );

    mysqli_stmt_close(
        $targetStatement
    );

    if (!$targetPayment) {
        throw new MonetaryLoanReversalException(
            "The selected payment could not be found.",
            404
        );
    }

    if (
        (string)
        $targetPayment["transaction_type"] !==
        "Payment"
    ) {
        throw new MonetaryLoanReversalException(
            "Only an original Payment can be reversed.",
            409
        );
    }

    if (
        (int)
        $targetPayment["is_reversed"] ===
        1
    ) {
        throw new MonetaryLoanReversalException(
            "This payment has already been reversed.",
            409
        );
    }


    /* -----------------------------------------------------
       VALIDATE LATEST UNREVERSED PAYMENT
    ----------------------------------------------------- */

    $latestStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                monetary_loan_payment_id

            FROM monetary_loan_payments

            WHERE monetary_loan_id = ?
              AND transaction_type = 'Payment'
              AND is_reversed = 0

            ORDER BY
                payment_date DESC,
                monetary_loan_payment_id DESC

            LIMIT 1

            FOR UPDATE
            "
        );

    if (!$latestStatement) {
        throw new RuntimeException(
            "Unable to validate the latest payment."
        );
    }

    mysqli_stmt_bind_param(
        $latestStatement,
        "i",
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $latestStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to validate the latest payment."
        );
    }

    $latestResult =
        mysqli_stmt_get_result(
            $latestStatement
        );

    $latestPayment =
        mysqli_fetch_assoc(
            $latestResult
        );

    mysqli_stmt_close(
        $latestStatement
    );

    if (
        !$latestPayment ||
        (int)
        $latestPayment[
            "monetary_loan_payment_id"
        ] !==
        $paymentID
    ) {
        throw new MonetaryLoanReversalException(
            "Only the latest unreversed payment can be reversed.",
            409
        );
    }


    /* -----------------------------------------------------
       MARK ORIGINAL PAYMENT AS REVERSED
    ----------------------------------------------------- */

    $reversedAt =
        date("Y-m-d H:i:s");

    $reversalDate =
        date("Y-m-d");

    $markStatement =
        mysqli_prepare(
            $conn,
            "
            UPDATE monetary_loan_payments

            SET
                is_reversed = 1,
                reversed_by = ?,
                reversed_at = ?,
                reversal_reason = ?

            WHERE monetary_loan_payment_id = ?
              AND monetary_loan_id = ?
              AND transaction_type = 'Payment'
              AND is_reversed = 0
            "
        );

    if (!$markStatement) {
        throw new RuntimeException(
            "Unable to prepare the payment reversal."
        );
    }

    mysqli_stmt_bind_param(
        $markStatement,
        "issii",
        $userID,
        $reversedAt,
        $reversalReason,
        $paymentID,
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $markStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to mark the payment as reversed."
        );
    }

    if (
        mysqli_stmt_affected_rows(
            $markStatement
        ) !==
        1
    ) {
        throw new MonetaryLoanReversalException(
            "The payment was already changed by another transaction. Refresh and try again.",
            409
        );
    }

    mysqli_stmt_close(
        $markStatement
    );


    /* -----------------------------------------------------
    REPLAY REMAINING VALID PAYMENTS

    Formula:
    current outstanding principal × rate
    × elapsed days ÷ term

    This also preserves diminishing-balance behavior for
    Long-Term Loans because principal is reduced after
    each replayed payment.
    ----------------------------------------------------- */

    $paymentsStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                monetary_loan_payment_id,
                payment_date,
                payment_amount

            FROM monetary_loan_payments

            WHERE monetary_loan_id = ?
              AND transaction_type = 'Payment'
              AND is_reversed = 0

            ORDER BY
                payment_date ASC,
                monetary_loan_payment_id ASC

            FOR UPDATE
            "
        );

    if (!$paymentsStatement) {
        throw new RuntimeException(
            "Unable to prepare the remaining payment history."
        );
    }

    mysqli_stmt_bind_param(
        $paymentsStatement,
        "i",
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $paymentsStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to retrieve the remaining payment history."
        );
    }

    $paymentsResult =
        mysqli_stmt_get_result(
            $paymentsStatement
        );

    $principalCents =
        monetaryLoanReversalToCents(
            $loan["principal_amount"]
        );

    $unpaidInterestCents = 0;
    $netPaymentsCents = 0;

    $lastInterestDate =
        $releaseDate;

    while (
        $activePayment =
            mysqli_fetch_assoc(
                $paymentsResult
            )
    ) {
        $activePaymentDate =
            (string)
            $activePayment["payment_date"];

        $elapsedDays =
            monetaryLoanReversalElapsedDays(
                $lastInterestDate,
                $activePaymentDate
            );

        $newInterestCents = 0;

        if (
            $principalCents > 0 &&
            $elapsedDays > 0
        ) {
            $newInterestCents =
                (int) round(
                    $principalCents *
                    ($interestRate / 100) *
                    ($elapsedDays / $termDays)
                );
        }

        $totalInterestDueCents =
            $unpaidInterestCents +
            $newInterestCents;

        $activePaymentCents =
            monetaryLoanReversalToCents(
                $activePayment[
                    "payment_amount"
                ]
            );

        $availableBalanceCents =
            $principalCents +
            $totalInterestDueCents;

        if (
            $activePaymentCents >
            $availableBalanceCents
        ) {
            throw new MonetaryLoanReversalException(
                "The remaining payment history is inconsistent and cannot be recalculated safely.",
                409
            );
        }

        $interestPaidCents =
            min(
                $activePaymentCents,
                $totalInterestDueCents
            );

        $principalPaidCents =
            min(
                $activePaymentCents -
                $interestPaidCents,
                $principalCents
            );

        $unpaidInterestCents =
            $totalInterestDueCents -
            $interestPaidCents;

        $principalCents -=
            $principalPaidCents;

        $netPaymentsCents +=
            $activePaymentCents;

        $lastInterestDate =
            $activePaymentDate;
    }

    mysqli_stmt_close(
        $paymentsStatement
    );

    $balanceCents =
        $principalCents +
        $unpaidInterestCents;


    /* -----------------------------------------------------
       RECALCULATED VALUES
    ----------------------------------------------------- */

    $principalAfter =
        monetaryLoanReversalFromCents(
            $principalCents
        );

    $unpaidInterestAfter =
        monetaryLoanReversalFromCents(
            $unpaidInterestCents
        );

    $netPaymentsAfter =
        monetaryLoanReversalFromCents(
            $netPaymentsCents
        );

    $balanceAfter =
        monetaryLoanReversalFromCents(
            $balanceCents
        );


    /* -----------------------------------------------------
       UPDATE MONETARY LOAN
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
                fully_paid_at = NULL,
                updated_by = ?

            WHERE monetary_loan_id = ?
            "
        );

    if (!$updateLoanStatement) {
        throw new RuntimeException(
            "Unable to prepare the recalculated loan balance."
        );
    }

    mysqli_stmt_bind_param(
        $updateLoanStatement,
        "dddsii",
        $principalAfter,
        $unpaidInterestAfter,
        $netPaymentsAfter,
        $lastInterestDate,
        $userID,
        $monetaryLoanID
    );

    if (
        !mysqli_stmt_execute(
            $updateLoanStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to update the recalculated Monetary Loan balance."
        );
    }

    mysqli_stmt_close(
        $updateLoanStatement
    );


    /* -----------------------------------------------------
       INSERT REVERSAL AUDIT ROW
    ----------------------------------------------------- */

    $targetAmount =
        (float)
        $targetPayment["payment_amount"];

    $targetInterestPaid =
        (float)
        $targetPayment[
            "interest_paid_amount"
        ];

    $targetPrincipalPaid =
        (float)
        $targetPayment[
            "principal_paid_amount"
        ];

    $targetPaymentDate =
        (string)
        $targetPayment["payment_date"];

    $principalBeforeReversal =
        (float)
        $loan[
            "outstanding_principal_amount"
        ];

    $interestBeforeReversal =
        (float)
        $loan[
            "unpaid_interest_amount"
        ];

    $reversalNotes =
        "Reversal of payment #" .
        $paymentID .
        ": " .
        $reversalReason;

    $reversalStatement =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO monetary_loan_payments (
                monetary_loan_id,
                transaction_type,
                reversed_payment_id,
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
                'Reversal',
                ?,
                ?,
                ?,
                ?,
                ?,
                0,
                ?,
                ?,
                0.00,
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

    if (!$reversalStatement) {
        throw new RuntimeException(
            "Unable to prepare the reversal history."
        );
    }

    mysqli_stmt_bind_param(
        $reversalStatement,
        "iisdssddddddddsi",
        $monetaryLoanID,
        $paymentID,
        $reversalDate,
        $targetAmount,
        $targetPaymentDate,
        $reversalDate,
        $principalBeforeReversal,
        $interestBeforeReversal,
        $interestBeforeReversal,
        $targetInterestPaid,
        $targetPrincipalPaid,
        $principalAfter,
        $unpaidInterestAfter,
        $balanceAfter,
        $reversalNotes,
        $userID
    );

    if (
        !mysqli_stmt_execute(
            $reversalStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to record the reversal history."
        );
    }

    $reversalID =
        (int)
        mysqli_insert_id(
            $conn
        );

    mysqli_stmt_close(
        $reversalStatement
    );


    /* -----------------------------------------------------
       COMMIT
    ----------------------------------------------------- */

    mysqli_commit(
        $conn
    );

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
        'reversed payment "' .
        $paymentDisplayID .
        '" amounting to ₱' .
        number_format(
            $targetAmount,
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
        '. Reason: ' .
        $reversalReason .
        '. Recalculated balance ₱' .
        number_format(
            $balanceAfter,
            2
        ) .
        '.';

    try {
        $historyLogged =
            log_activity(
                $conn,
                "Lending",
                "Updated",
                $historyMessage,
                $loanDisplayID
            );
    } catch (Throwable $historyException) {
        $historyLogged = false;

        error_log(
            "Monetary Loan reversal history logger error: " .
            $historyException->getMessage()
        );
    }

    if (!$historyLogged) {
        error_log(
            "Unable to save Monetary Loan payment reversal history for " .
            $loanDisplayID
        );
    }


    /* -----------------------------------------------------
       SUCCESS
    ----------------------------------------------------- */

    monetaryLoanReversalResponse(
        true,
        "Payment of ₱" .
        number_format(
            $targetAmount,
            2
        ) .
        " was reversed for " .
        $loan["full_name"] .
        ". Recalculated balance: ₱" .
        number_format(
            $balanceAfter,
            2
        ) .
        ".",
        [
            "reversal_id" =>
                $reversalID,

            "reversed_payment_id" =>
                $paymentID,

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
                )
        ],
        201
    );
} catch (
    MonetaryLoanReversalException $exception
) {
    if ($transactionStarted) {
        mysqli_rollback(
            $conn
        );
    }

    monetaryLoanReversalResponse(
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
        "Reverse Monetary Loan payment error: " .
        $exception->getMessage()
    );

    monetaryLoanReversalResponse(
        false,
        "Unable to reverse the payment. Please try again.",
        [],
        500
    );
}