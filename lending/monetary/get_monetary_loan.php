<?php

declare(strict_types=1);


/* =========================================================
   GET MONETARY LOAN DETAILS
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   SETTINGS
========================================================= */

date_default_timezone_set(
    "Asia/Manila"
);

error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);

header(
    "Content-Type: application/json; charset=utf-8"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);

header(
    "X-Content-Type-Options: nosniff"
);

header(
    "X-Frame-Options: SAMEORIGIN"
);

header(
    "Referrer-Policy: same-origin"
);


/* =========================================================
   RESPONSE
========================================================= */

function getMonetaryLoanResponse(
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
   DATE HELPERS
========================================================= */

function getMonetaryLoanDisplayDate(
    $date
): string {

    if (
        empty($date) ||
        $date === "0000-00-00"
    ) {
        return "—";
    }

    $timestamp =
        strtotime(
            (string) $date
        );

    if ($timestamp === false) {
        return "—";
    }

    return date(
        "m-d-Y",
        $timestamp
    );

}


function getMonetaryLoanDaysBetween(
    ?string $fromDate,
    string $toDate
): int {

    if (
        empty($fromDate) ||
        $fromDate === "0000-00-00"
    ) {
        return 0;
    }

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

        return 0;

    }

    if ($to <= $from) {
        return 0;
    }

    return (int)
        $from
            ->diff($to)
            ->days;

}


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if (
    ($_SERVER["REQUEST_METHOD"] ?? "") !==
    "GET"
) {

    getMonetaryLoanResponse(
        false,
        "Invalid request method.",
        [],
        405
    );

}

$requestedWith =
    strtolower(
        trim(
            (string) (
                $_SERVER[
                    "HTTP_X_REQUESTED_WITH"
                ]
                ?? ""
            )
        )
    );

if ($requestedWith !== "xmlhttprequest") {

    getMonetaryLoanResponse(
        false,
        "Invalid request.",
        [],
        400
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

    getMonetaryLoanResponse(
        false,
        "Your session has expired. Please log in again.",
        [],
        401
    );

}

if (
    (int) (
        $_SESSION[
            "lending_access"
        ]
        ?? 0
    ) !== 1
) {

    getMonetaryLoanResponse(
        false,
        "You do not have access to the Lending module.",
        [],
        403
    );

}


/* =========================================================
   LOAN ID
========================================================= */

$monetaryLoanID =
    isset($_GET["loan_id"])
        ? (int) $_GET["loan_id"]
        : 0;

if ($monetaryLoanID <= 0) {

    getMonetaryLoanResponse(
        false,
        "Invalid Monetary Loan ID.",
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

    getMonetaryLoanResponse(
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
   CURRENT USER AND LENDING PERMISSION
========================================================= */

$userStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT
            user_id,
            name,
            role,
            lending_permission

        FROM users

        WHERE user_id = ?

        LIMIT 1
        "
    );

if (!$userStatement) {

    getMonetaryLoanResponse(
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

$currentUser =
    mysqli_fetch_assoc(
        $userResult
    );

mysqli_stmt_close(
    $userStatement
);

if (!$currentUser) {

    getMonetaryLoanResponse(
        false,
        "The logged-in account could not be found.",
        [],
        401
    );

}

$lendingPermission =
    strtolower(
        trim(
            (string) (
                $currentUser[
                    "lending_permission"
                ]
                ?? "limited"
            )
        )
    ) === "full"
        ? "full"
        : "limited";


/* =========================================================
   MONETARY LOAN INFORMATION
========================================================= */

$loanStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            loan.*,

            member.member_code,
            member.full_name,

            creator.name
                AS created_by_name,

            approver.name
            AS approved_by_name,

        (
            SELECT GROUP_CONCAT(
                DISTINCT other_loan.loan_purpose
                ORDER BY other_loan.loan_purpose
                SEPARATOR '||'
            )

            FROM monetary_loans AS other_loan

            WHERE
                other_loan.member_id =
                    loan.member_id

                AND other_loan.monetary_loan_id <>
                    loan.monetary_loan_id

                AND other_loan.is_archived = 0

                AND other_loan.loan_purpose IN (
                    'Crop Production Loan',
                    'Dairy Farmers Loan',
                    'Emergency Loan',
                    'Long-Term Loan'
                )

                AND (
                    other_loan.status = 'Pending'

                    OR (
                        other_loan.status = 'Accepted'

                        AND (
                            other_loan.outstanding_principal_amount > 0.009
                            OR other_loan.unpaid_interest_amount > 0.009
                        )
                    )
                )
        ) AS active_loan_types

        FROM monetary_loans AS loan

        INNER JOIN members AS member
            ON member.member_id =
               loan.member_id

        LEFT JOIN users AS creator
            ON creator.user_id =
               loan.created_by

        LEFT JOIN users AS approver
            ON approver.user_id =
               loan.approved_by

        WHERE
            loan.monetary_loan_id = ?
            AND loan.is_archived = 0

        LIMIT 1
        "
    );

if (!$loanStatement) {

    error_log(
        "Get Monetary Loan prepare error: " .
        mysqli_error($conn)
    );

    getMonetaryLoanResponse(
        false,
        "Unable to prepare the Monetary Loan details.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $loanStatement,
    "i",
    $monetaryLoanID
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

    getMonetaryLoanResponse(
        false,
        "The Monetary Loan record could not be found.",
        [],
        404
    );

}


/* =========================================================
   CURRENT DATE
========================================================= */

$today =
    date(
        "Y-m-d"
    );

$status =
    trim(
        (string) (
            $loan["status"]
            ?? ""
        )
    );


/* =========================================================
   LIVE PRORATED INTEREST AND BALANCE
========================================================= */

$outstandingPrincipal =
    round(
        (float) (
            $loan[
                "outstanding_principal_amount"
            ]
            ?? 0
        ),
        2
    );

$storedUnpaidInterest =
    round(
        (float) (
            $loan[
                "unpaid_interest_amount"
            ]
            ?? 0
        ),
        2
    );

$interestRate =
    round(
        (float) (
            $loan[
                "interest_rate"
            ]
            ?? 0
        ),
        2
    );

$termDays =
    max(
        1,
        (int) (
            $loan[
                "term_days"
            ]
            ?? 1
        )
    );

$loanTermYears =
    isset(
        $loan[
            "loan_term_years"
        ]
    ) &&
    $loan[
        "loan_term_years"
    ] !== null
        ? (int)
          $loan[
              "loan_term_years"
          ]
        : null;

$paymentPeriods =
    (
        (string) (
            $loan[
                "loan_purpose"
            ]
            ?? ""
        ) === "Long-Term Loan" &&
        $loanTermYears !== null &&
        $loanTermYears > 0
    )
        ? $loanTermYears *
          (
              $termDays === 180
                  ? 2
                  : 1
          )
        : 1;

$newAccruedInterest =
    0.00;

$totalInterestDue =
    0.00;

$currentBalance =
    null;

$elapsedDays =
    0;

$remarks =
    "—";

if ($status === "Accepted") {

    $interestFromDate =
        !empty(
            $loan[
                "last_interest_date"
            ]
        )
            ? (string)
              $loan[
                  "last_interest_date"
              ]
            : (string) (
                $loan[
                    "release_date"
                ]
                ?? ""
            );

    $elapsedDays =
        getMonetaryLoanDaysBetween(
            $interestFromDate,
            $today
        );

    if (
        $outstandingPrincipal > 0 &&
        $elapsedDays > 0
    ) {

        $newAccruedInterest =
            round(
                $outstandingPrincipal *
                ($interestRate / 100) *
                ($elapsedDays / $termDays),
                2
            );

    }

    $totalInterestDue =
        round(
            $storedUnpaidInterest +
            $newAccruedInterest,
            2
        );

    $currentBalance =
        round(
            $outstandingPrincipal +
            $totalInterestDue,
            2
        );

    $isPaid =
        (
            $outstandingPrincipal <= 0.009 &&
            $storedUnpaidInterest <= 0.009 &&
            $newAccruedInterest <= 0.009 &&
            !empty(
                $loan[
                    "fully_paid_at"
                ]
            )
        );

    if ($isPaid) {

        $remarks =
            "Paid";

        $newAccruedInterest =
            0.00;

        $totalInterestDue =
            0.00;

        $currentBalance =
            0.00;

    } elseif (
        !empty(
            $loan[
                "due_date"
            ]
        ) &&
        $today >
        (string)
        $loan[
            "due_date"
        ]
    ) {

        $remarks =
            "Overdue";

    } else {

        $remarks =
            "On Going";

    }

}


/* =========================================================
   ACCEPTANCE DATE AND DUE DATE PREVIEW
========================================================= */

$acceptanceDatePreview =
    $today;

$dueDatePreview =
    null;

try {

    $previewTermDays =
        (
            (string) (
                $loan[
                    "loan_purpose"
                ]
                ?? ""
            ) === "Long-Term Loan" &&
            $loanTermYears !== null &&
            $loanTermYears > 0
        )
            ? $termDays *
            $paymentPeriods
            : $termDays;

    $previewDueDate =
        (
            new DateTimeImmutable(
                $acceptanceDatePreview,
                new DateTimeZone(
                    "Asia/Manila"
                )
            )
        )->modify(
            "+" .
            $previewTermDays .
            " days"
        );

    $dueDatePreview =
        $previewDueDate->format(
            "Y-m-d"
        );

} catch (Throwable $exception) {

    $dueDatePreview =
        null;

}


/* =========================================================
   ATTACHMENTS
========================================================= */

$attachmentStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            monetary_loan_attachment_id,
            original_file_name,
            mime_type,
            file_size,
            uploaded_at

        FROM monetary_loan_attachments

        WHERE
            monetary_loan_id = ?
            AND is_removed = 0

        ORDER BY
            monetary_loan_attachment_id ASC
        "
    );

if (!$attachmentStatement) {

    error_log(
        "Get Monetary Loan attachment prepare error: " .
        mysqli_error($conn)
    );

    getMonetaryLoanResponse(
        false,
        "Unable to prepare Monetary Loan attachments.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $attachmentStatement,
    "i",
    $monetaryLoanID
);

mysqli_stmt_execute(
    $attachmentStatement
);

$attachmentResult =
    mysqli_stmt_get_result(
        $attachmentStatement
    );

$attachments = [];

while (
    $attachment =
        mysqli_fetch_assoc(
            $attachmentResult
        )
) {

    $attachmentID =
        (int)
        $attachment[
            "monetary_loan_attachment_id"
        ];

    $attachments[] = [

        "attachment_id" =>
            $attachmentID,

        "original_file_name" =>
            (string)
            $attachment[
                "original_file_name"
            ],

        "mime_type" =>
            (string)
            $attachment[
                "mime_type"
            ],

        "file_size" =>
            (int)
            $attachment[
                "file_size"
            ],

        "uploaded_at" =>
            $attachment[
                "uploaded_at"
            ],

        "view_url" =>
            "/spis/lending/monetary/" .
            "view_monetary_loan_attachment.php" .
            "?attachment_id=" .
            $attachmentID
    ];

}

mysqli_stmt_close(
    $attachmentStatement
);


/* =========================================================
   LATEST REVERSIBLE PAYMENT
========================================================= */

$latestReversiblePaymentID =
    0;

$latestPaymentStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT
            monetary_loan_payment_id

        FROM monetary_loan_payments

        WHERE
            monetary_loan_id = ?
            AND transaction_type = 'Payment'
            AND is_reversed = 0

        ORDER BY
            payment_date DESC,
            monetary_loan_payment_id DESC

        LIMIT 1
        "
    );

if ($latestPaymentStatement) {

    mysqli_stmt_bind_param(
        $latestPaymentStatement,
        "i",
        $monetaryLoanID
    );

    mysqli_stmt_execute(
        $latestPaymentStatement
    );

    $latestPaymentResult =
        mysqli_stmt_get_result(
            $latestPaymentStatement
        );

    $latestPayment =
        mysqli_fetch_assoc(
            $latestPaymentResult
        );

    if ($latestPayment) {

        $latestReversiblePaymentID =
            (int)
            $latestPayment[
                "monetary_loan_payment_id"
            ];

    }

    mysqli_stmt_close(
        $latestPaymentStatement
    );

}


/* =========================================================
   PAYMENT HISTORY
========================================================= */

$paymentStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            payment.*,

            recorder.name
                AS recorded_by_name,

            reversal_user.name
                AS reversed_by_name

        FROM monetary_loan_payments
            AS payment

        LEFT JOIN users AS recorder
            ON recorder.user_id =
               payment.recorded_by

        LEFT JOIN users AS reversal_user
            ON reversal_user.user_id =
               payment.reversed_by

        WHERE
            payment.monetary_loan_id = ?

        ORDER BY
            payment.payment_date DESC,
            payment.monetary_loan_payment_id DESC
        "
    );

if (!$paymentStatement) {

    error_log(
        "Get Monetary Loan payments prepare error: " .
        mysqli_error($conn)
    );

    getMonetaryLoanResponse(
        false,
        "Unable to prepare the Monetary Loan payment history.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $paymentStatement,
    "i",
    $monetaryLoanID
);

mysqli_stmt_execute(
    $paymentStatement
);

$paymentResult =
    mysqli_stmt_get_result(
        $paymentStatement
    );

$payments = [];

while (
    $payment =
        mysqli_fetch_assoc(
            $paymentResult
        )
) {

    $paymentID =
        (int)
        $payment[
            "monetary_loan_payment_id"
        ];

    $transactionType =
        (string)
        $payment[
            "transaction_type"
        ];

    $isReversed =
        (int) (
            $payment[
                "is_reversed"
            ]
            ?? 0
        ) === 1;

    $canReversePayment =
        (
            $lendingPermission === "full" &&
            $transactionType === "Payment" &&
            !$isReversed &&
            $paymentID ===
            $latestReversiblePaymentID
        );

    $payments[] = [

        "payment_id" =>
            $paymentID,

        "transaction_type" =>
            $transactionType,

        "reversed_payment_id" =>
            $payment[
                "reversed_payment_id"
            ] !== null
                ? (int)
                  $payment[
                      "reversed_payment_id"
                  ]
                : null,

        "payment_date" =>
            (string)
            $payment[
                "payment_date"
            ],

        "payment_date_display" =>
            getMonetaryLoanDisplayDate(
                $payment[
                    "payment_date"
                ]
            ),

        "payment_amount" =>
            round(
                (float) (
                    $payment[
                        "payment_amount"
                    ]
                    ?? 0
                ),
                2
            ),

        "elapsed_days" =>
            (int) (
                $payment[
                    "elapsed_days"
                ]
                ?? 0
            ),

        "newly_accrued_interest" =>
            round(
                (float) (
                    $payment[
                        "newly_accrued_interest_amount"
                    ]
                    ?? 0
                ),
                2
            ),

        "total_interest_due" =>
            round(
                (float) (
                    $payment[
                        "total_interest_due_amount"
                    ]
                    ?? 0
                ),
                2
            ),

        "interest_paid" =>
            round(
                (float) (
                    $payment[
                        "interest_paid_amount"
                    ]
                    ?? 0
                ),
                2
            ),

        "principal_paid" =>
            round(
                (float) (
                    $payment[
                        "principal_paid_amount"
                    ]
                    ?? 0
                ),
                2
            ),

        "balance_after" =>
            round(
                (float) (
                    $payment[
                        "balance_after_amount"
                    ]
                    ?? 0
                ),
                2
            ),

        "is_reversed" =>
            $isReversed,

        "reversal_reason" =>
            $payment[
                "reversal_reason"
            ]
            ?? null,

        "notes" =>
            $payment[
                "notes"
            ]
            ?? null,

        "recorded_by_name" =>
            $payment[
                "recorded_by_name"
            ]
            ?? "Unknown user",

        "reversed_by_name" =>
            $payment[
                "reversed_by_name"
            ]
            ?? null,

        "can_reverse" =>
            $canReversePayment
    ];

}

mysqli_stmt_close(
    $paymentStatement
);


/* =========================================================
   STATUS HISTORY
========================================================= */

$statusHistoryStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            history.previous_status,
            history.new_status,
            history.changed_at,
            history.reason,

            changer.name
                AS changed_by_name

        FROM monetary_loan_status_history
            AS history

        LEFT JOIN users AS changer
            ON changer.user_id =
               history.changed_by

        WHERE
            history.monetary_loan_id = ?

        ORDER BY
            history.monetary_loan_status_history_id
            DESC
        "
    );

$statusHistory = [];

if ($statusHistoryStatement) {

    mysqli_stmt_bind_param(
        $statusHistoryStatement,
        "i",
        $monetaryLoanID
    );

    mysqli_stmt_execute(
        $statusHistoryStatement
    );

    $statusHistoryResult =
        mysqli_stmt_get_result(
            $statusHistoryStatement
        );

    while (
        $history =
            mysqli_fetch_assoc(
                $statusHistoryResult
            )
    ) {

        $statusHistory[] = [

            "previous_status" =>
                $history[
                    "previous_status"
                ],

            "new_status" =>
                $history[
                    "new_status"
                ],

            "changed_at" =>
                $history[
                    "changed_at"
                ],

            "reason" =>
                $history[
                    "reason"
                ],

            "changed_by_name" =>
                $history[
                    "changed_by_name"
                ]
                ?? "Unknown user"
        ];

    }

    mysqli_stmt_close(
        $statusHistoryStatement
    );

}


/* =========================================================
   PERMISSION FLAGS
========================================================= */

$canEditPendingDetails =
    $status === "Pending";

$canChangeStatus =
    (
        $status === "Pending" &&
        $lendingPermission === "full"
    );

$canRecordPayment =
    (
        $status === "Accepted" &&
        $currentBalance !== null &&
        $currentBalance > 0.009
    );

$canReverseLatestPayment =
    $lendingPermission === "full";

$canArchive =
    (
        $lendingPermission === "full" &&
        (
            $status === "Rejected" ||
            (
                $status === "Accepted" &&
                $remarks === "Paid"
            )
        )
    );


/* =========================================================
   RESPONSE
========================================================= */

getMonetaryLoanResponse(
    true,
    "Monetary Loan details retrieved.",
    [

        "permission" => [

            "lending_permission" =>
                $lendingPermission,

            "can_edit_pending_details" =>
                $canEditPendingDetails,

            "can_change_status" =>
                $canChangeStatus,

            "can_record_payment" =>
                $canRecordPayment,

            "can_reverse_latest_payment" =>
                $canReverseLatestPayment,

            "can_archive" =>
                $canArchive
        ],


        "server" => [

            "date" =>
                $today,

            "date_display" =>
                getMonetaryLoanDisplayDate(
                    $today
                ),

            "timezone" =>
                "Asia/Manila",

            "acceptance_date_preview" =>
                $acceptanceDatePreview,

            "acceptance_date_preview_display" =>
                getMonetaryLoanDisplayDate(
                    $acceptanceDatePreview
                ),

            "due_date_preview" =>
                $dueDatePreview,

            "due_date_preview_display" =>
                getMonetaryLoanDisplayDate(
                    $dueDatePreview
                )
        ],


        "loan" => [

            "monetary_loan_id" =>
                (int)
                $loan[
                    "monetary_loan_id"
                ],

            "display_id" =>
                "ML-" .
                str_pad(
                    (string)
                    $loan[
                        "monetary_loan_id"
                    ],
                    6,
                    "0",
                    STR_PAD_LEFT
                ),

            "member_id" =>
                (int)
                $loan[
                    "member_id"
                ],

            "member_code" =>
                (string)
                $loan[
                    "member_code"
                ],

            "full_name" =>
                (string)
                $loan[
                    "full_name"
                ],

            "active_loan_types" =>
                trim(
                    (string) (
                        $loan[
                            "active_loan_types"
                        ] ?? ""
                    )
                ) !== ""
                    ? explode(
                        "||",
                        (string)
                        $loan[
                            "active_loan_types"
                        ]
                    )
                    : [],

            "loan_purpose" =>
                (string)
                $loan[
                    "loan_purpose"
                ],

            "application_date" =>
                (string)
                $loan[
                    "application_date"
                ],

            "application_date_display" =>
                getMonetaryLoanDisplayDate(
                    $loan[
                        "application_date"
                    ]
                ),

            "status" =>
                $status,

            "release_date" =>
                $loan[
                    "release_date"
                ]
                ?? null,

            "release_date_display" =>
                !empty(
                    $loan[
                        "release_date"
                    ]
                )
                    ? getMonetaryLoanDisplayDate(
                        $loan[
                            "release_date"
                        ]
                    )
                    : "Not accepted",

            "due_date" =>
                $loan[
                    "due_date"
                ]
                ?? null,

            "due_date_display" =>
                !empty(
                    $loan[
                        "due_date"
                    ]
                )
                    ? getMonetaryLoanDisplayDate(
                        $loan[
                            "due_date"
                        ]
                    )
                    : (
                        $status === "Pending"
                            ? "Generated upon acceptance"
                            : "—"
                    ),

            "approved_by" =>
                $loan[
                    "approved_by"
                ] !== null
                    ? (int)
                      $loan[
                          "approved_by"
                      ]
                    : null,

            "approved_by_name" =>
                !empty(
                    $loan[
                        "approved_by_name"
                    ]
                )
                    ? (string)
                      $loan[
                          "approved_by_name"
                      ]
                    : "—",

            "interest_rate" =>
                $interestRate,

            "term_days" =>
                $termDays,

            "loan_term_years" =>
                $loanTermYears,

            "payment_periods" =>
                (string) (
                    $loan[
                        "loan_purpose"
                    ]
                    ?? ""
                ) === "Long-Term Loan"
                    ? $paymentPeriods
                    : null,

            "principal_amount" =>
                round(
                    (float)
                    $loan[
                        "principal_amount"
                    ],
                    2
                ),

            "scheduled_interest_amount" =>
                round(
                    (float)
                    $loan[
                        "scheduled_interest_amount"
                    ],
                    2
                ),

            "scheduled_total_amount" =>
                round(
                    (float)
                    $loan[
                        "scheduled_total_amount"
                    ],
                    2
                ),

            "outstanding_principal_amount" =>
                $outstandingPrincipal,

            "stored_unpaid_interest_amount" =>
                $storedUnpaidInterest,

            "new_accrued_interest_amount" =>
                $newAccruedInterest,

            "total_interest_due_amount" =>
                $totalInterestDue,

            "current_balance" =>
                $currentBalance,

            "net_payments_amount" =>
                round(
                    (float) (
                        $loan[
                            "net_payments_amount"
                        ]
                        ?? 0
                    ),
                    2
                ),

            "elapsed_days" =>
                $elapsedDays,

            "remarks" =>
                $remarks,

            "rejection_reason" =>
                $loan[
                    "rejection_reason"
                ]
                ?? null,

            "fully_paid_at" =>
                $loan[
                    "fully_paid_at"
                ]
                ?? null,

            "created_by_name" =>
                $loan[
                    "created_by_name"
                ]
                ?? "Unknown user"
        ],


        "attachments" =>
            $attachments,


        "payments" =>
            $payments,


        "status_history" =>
            $statusHistory
    ]
);