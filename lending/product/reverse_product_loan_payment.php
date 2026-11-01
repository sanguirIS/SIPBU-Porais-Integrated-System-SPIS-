<?php

/* =========================================================
   REVERSE PRODUCT LOAN PAYMENT

   Rules:
   - Full Lending Access only
   - Only the latest unreversed Payment may be reversed
   - The original Payment remains in the audit history
   - A separate Reversal row is created
   - The latest Payment is reversed by restoring its exact saved pre-payment snapshot
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
   EXCEPTION
========================================================= */

class ProductLoanReversalException extends RuntimeException
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

function productLoanReversalResponse(
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


/* =========================================================
   HELPERS
========================================================= */

function reversalNormalizeText($value): string
{
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


function reversalAmountToCents($value): int
{
    return (int) round(
        ((float) $value) * 100
    );
}


function reversalCentsToAmount(
    int $cents
): float {
    return round(
        $cents / 100,
        2
    );
}


function reversalElapsedDays(
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
        throw new ProductLoanReversalException(
            "Unable to recalculate the payment history.",
            500
        );
    }

    if ($to < $from) {
        throw new ProductLoanReversalException(
            "A saved payment date is earlier than the previous loan transaction date.",
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
    productLoanReversalResponse(
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
    productLoanReversalResponse(
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
    productLoanReversalResponse(
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
    productLoanReversalResponse(
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
    productLoanReversalResponse(
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
        ? (int) $_POST["product_loan_id"]
        : 0;

$paymentID =
    isset($_POST["payment_id"])
        ? (int) $_POST["payment_id"]
        : 0;

$reversalReason =
    reversalNormalizeText(
        $_POST["reversal_reason"]
        ?? ""
    );

if ($productLoanID <= 0) {
    productLoanReversalResponse(
        false,
        "Invalid Product Loan ID.",
        [],
        422
    );
}

if ($paymentID <= 0) {
    productLoanReversalResponse(
        false,
        "Invalid payment ID.",
        [],
        422
    );
}

if ($reversalReason === "") {
    productLoanReversalResponse(
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
    productLoanReversalResponse(
        false,
        "The reversal reason must not exceed 500 characters.",
        [],
        422
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
    productLoanReversalResponse(
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
       VERIFY FULL-ACCESS ACCOUNT
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
        throw new ProductLoanReversalException(
            "The logged-in account could not be found.",
            401
        );
    }

    if (
        strtolower(
            trim(
                (string)
                $account[
                    "lending_permission"
                ]
            )
        ) !==
        "full"
    ) {
        throw new ProductLoanReversalException(
            "Full Lending Access is required to reverse a payment.",
            403
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
                loan.member_id,
                loan.status,
                loan.interest_rate,
                loan.term_days,
                loan.principal_amount,
                loan.delivered_date,
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
        throw new ProductLoanReversalException(
            "The Product Loan record could not be found.",
            404
        );
    }

    if (
        (int)
        $loan["is_archived"] ===
        1
    ) {
        throw new ProductLoanReversalException(
            "A payment from an archived Product Loan cannot be reversed.",
            409
        );
    }

    if (
        $loan["status"] !==
        "Delivered"
    ) {
        throw new ProductLoanReversalException(
            "Payments can only be reversed from a Delivered Product Loan.",
            409
        );
    }

    if (
        empty(
            $loan["delivered_date"]
        )
    ) {
        throw new ProductLoanReversalException(
            "The Product Loan has no valid delivery date.",
            409
        );
    }

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
        throw new ProductLoanReversalException(
            "The saved loan term is invalid.",
            409
        );
    }

    $interestRate =
        (float)
        $loan["interest_rate"];


    /* -----------------------------------------------------
       LOCK TARGET PAYMENT
    ----------------------------------------------------- */

    $targetStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                product_loan_payment_id,
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
                is_reversed

            FROM product_loan_payments

            WHERE product_loan_payment_id = ?
              AND product_loan_id = ?

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
        $productLoanID
    );

    mysqli_stmt_execute(
        $targetStatement
    );

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
        throw new ProductLoanReversalException(
            "The selected payment could not be found.",
            404
        );
    }

    if (
        $targetPayment[
            "transaction_type"
        ] !==
        "Payment"
    ) {
        throw new ProductLoanReversalException(
            "Only an original Payment can be reversed.",
            409
        );
    }

    if (
        (int)
        $targetPayment[
            "is_reversed"
        ] ===
        1
    ) {
        throw new ProductLoanReversalException(
            "This payment has already been reversed.",
            409
        );
    }


    /* -----------------------------------------------------
       ONLY LATEST VALID PAYMENT MAY BE REVERSED
    ----------------------------------------------------- */

    $latestStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                product_loan_payment_id

            FROM product_loan_payments

            WHERE product_loan_id = ?
              AND transaction_type = 'Payment'
              AND is_reversed = 0

            ORDER BY
                payment_date DESC,
                product_loan_payment_id DESC

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
        $productLoanID
    );

    mysqli_stmt_execute(
        $latestStatement
    );

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
            "product_loan_payment_id"
        ] !==
        $paymentID
    ) {
        throw new ProductLoanReversalException(
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
            UPDATE product_loan_payments

            SET
                is_reversed = 1,
                reversed_by = ?,
                reversed_at = ?,
                reversal_reason = ?

            WHERE product_loan_payment_id = ?
              AND product_loan_id = ?
              AND transaction_type = 'Payment'
              AND is_reversed = 0
            "
        );

    if (!$markStatement) {
        throw new RuntimeException(
            "Unable to prepare the original payment reversal."
        );
    }

    mysqli_stmt_bind_param(
        $markStatement,
        "issii",
        $userID,
        $reversedAt,
        $reversalReason,
        $paymentID,
        $productLoanID
    );

    if (
        !mysqli_stmt_execute(
            $markStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to mark the original payment as reversed."
        );
    }

    if (
        mysqli_stmt_affected_rows(
            $markStatement
        ) !==
        1
    ) {
        throw new ProductLoanReversalException(
            "The payment was already changed by another transaction. Refresh and try again.",
            409
        );
    }

    mysqli_stmt_close(
        $markStatement
    );


    /* -----------------------------------------------------
       RESTORE THE EXACT PRE-PAYMENT SNAPSHOT

       Because only the latest unreversed Payment can be
       reversed, there are no later valid payments that need
       to be replayed. The original Payment row already stores
       the authoritative financial state immediately before
       that payment.

       This is important when a payment used:
       - previously unpaid interest
       - newly accrued interest
       - both interest and principal
       - a backdated calculation_from_date used for testing

       Replaying from the delivery date would lose those
       saved interest boundaries and can produce a wrong
       principal after reversal.
    ----------------------------------------------------- */

    $restorePrincipalCents =
        reversalAmountToCents(
            $targetPayment[
                "principal_before_amount"
            ]
        );

    $restoreUnpaidInterestCents =
        reversalAmountToCents(
            $targetPayment[
                "unpaid_interest_before_amount"
            ]
        );

    $restoreInterestDate =
        reversalNormalizeText(
            $targetPayment[
                "calculation_from_date"
            ]
            ?? ""
        );

    if ($restoreInterestDate === "") {
        $restoreInterestDate =
            (string)
            $loan["delivered_date"];
    }

    if ($restoreInterestDate === "") {
        throw new ProductLoanReversalException(
            "The selected payment has no valid interest calculation start date.",
            409
        );
    }


    /* -----------------------------------------------------
       REBUILD NET PAYMENTS ONLY

       Net payments can safely be recalculated as the sum of
       all remaining unreversed Payment rows. Financial
       allocation is NOT replayed here.
    ----------------------------------------------------- */

    $netPaymentsStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT
                COALESCE(
                    SUM(payment_amount),
                    0
                ) AS net_payments_amount

            FROM product_loan_payments

            WHERE product_loan_id = ?
              AND transaction_type = 'Payment'
              AND is_reversed = 0
            "
        );

    if (!$netPaymentsStatement) {
        throw new RuntimeException(
            "Unable to prepare the recalculated net payments."
        );
    }

    mysqli_stmt_bind_param(
        $netPaymentsStatement,
        "i",
        $productLoanID
    );

    mysqli_stmt_execute(
        $netPaymentsStatement
    );

    $netPaymentsResult =
        mysqli_stmt_get_result(
            $netPaymentsStatement
        );

    $netPaymentsRow =
        mysqli_fetch_assoc(
            $netPaymentsResult
        );

    mysqli_stmt_close(
        $netPaymentsStatement
    );

    $netPaymentsAfter =
        round(
            (float) (
                $netPaymentsRow[
                    "net_payments_amount"
                ]
                ?? 0
            ),
            2
        );


    /* -----------------------------------------------------
       CURRENT PAYOFF AFTER REVERSAL

       Restore the saved pre-payment principal and unpaid
       interest, then let interest continue from the exact
       calculation_from_date that existed before the payment.
    ----------------------------------------------------- */

    $elapsedDaysAfterReversal =
        reversalElapsedDays(
            $restoreInterestDate,
            $reversalDate
        );

    $newInterestAfterReversalCents = 0;

    if (
        $restorePrincipalCents > 0 &&
        $elapsedDaysAfterReversal > 0
    ) {
        $newInterestAfterReversalCents =
            (int) round(
                $restorePrincipalCents *
                (
                    $interestRate /
                    100
                ) *
                (
                    $elapsedDaysAfterReversal /
                    $termDays
                )
            );
    }

    $totalInterestAfterReversalCents =
        $restoreUnpaidInterestCents +
        $newInterestAfterReversalCents;

    $balanceAfterCents =
        $restorePrincipalCents +
        $totalInterestAfterReversalCents;


    /* -----------------------------------------------------
       UPDATED LOAN VALUES
    ----------------------------------------------------- */

    $principalAfter =
        reversalCentsToAmount(
            $restorePrincipalCents
        );

    $unpaidInterestAfter =
        reversalCentsToAmount(
            $restoreUnpaidInterestCents
        );

    $newInterestAfterReversal =
        reversalCentsToAmount(
            $newInterestAfterReversalCents
        );

    $totalInterestAfterReversal =
        reversalCentsToAmount(
            $totalInterestAfterReversalCents
        );

    $balanceAfter =
        reversalCentsToAmount(
            $balanceAfterCents
        );

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
                fully_paid_at = NULL,
                updated_by = ?

            WHERE product_loan_id = ?
            "
        );

    if (!$updateLoanStatement) {
        throw new RuntimeException(
            "Unable to prepare the restored loan balance."
        );
    }

    mysqli_stmt_bind_param(
        $updateLoanStatement,
        "dddsii",
        $principalAfter,
        $unpaidInterestAfter,
        $netPaymentsAfter,
        $restoreInterestDate,
        $userID,
        $productLoanID
    );

    if (
        !mysqli_stmt_execute(
            $updateLoanStatement
        )
    ) {
        throw new RuntimeException(
            "Unable to restore the Product Loan balance."
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
        $targetPayment[
            "payment_amount"
        ];

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
        $targetPayment[
            "payment_date"
        ];

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
            INSERT INTO product_loan_payments (
                product_loan_id,
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

    if (!$reversalStatement) {
        throw new RuntimeException(
            "Unable to prepare the reversal history."
        );
    }

    mysqli_stmt_bind_param(
        $reversalStatement,
        "iisdssidddddddddsi",
        $productLoanID,
        $paymentID,
        $reversalDate,
        $targetAmount,
        $restoreInterestDate,
        $reversalDate,
        $elapsedDaysAfterReversal,
        $principalBeforeReversal,
        $interestBeforeReversal,
        $newInterestAfterReversal,
        $totalInterestAfterReversal,
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
    SAVE REVERSAL HISTORY LOG
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

    $reversalReference =
        "REV-" .
        str_pad(
            (string) $reversalID,
            6,
            "0",
            STR_PAD_LEFT
        );

    $historyMessage =
        'reversed payment "' .
        $paymentReference .
        '" through reversal record "' .
        $reversalReference .
        '" for Product Loan "' .
        $loanReference .
        '" of member "' .
        $loan["full_name"] .
        '" (' .
        $loan["member_code"] .
        "). Original payment amount ₱" .
        number_format(
            $targetAmount,
            2
        ) .
        ", including ₱" .
        number_format(
            $targetInterestPaid,
            2
        ) .
        " applied to interest and ₱" .
        number_format(
            $targetPrincipalPaid,
            2
        ) .
        " applied to principal. Recalculated outstanding principal ₱" .
        number_format(
            $principalAfter,
            2
        ) .
        ", unpaid interest ₱" .
        number_format(
            $unpaidInterestAfter,
            2
        ) .
        ", and remaining balance ₱" .
        number_format(
            $balanceAfter,
            2
        ) .
        '. Reversal reason: "' .
        $reversalReason .
        '".';

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

    productLoanReversalResponse(
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
    ProductLoanReversalException $exception
) {
    if ($transactionStarted) {
        mysqli_rollback(
            $conn
        );
    }

    productLoanReversalResponse(
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
        "Reverse Product Loan payment error: " .
        $exception->getMessage()
    );

    productLoanReversalResponse(
        false,
        "Unable to reverse the payment. Please try again.",
        [],
        500
    );
}