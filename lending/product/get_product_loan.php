<?php

/* =========================================================
   GET PRODUCT LOAN DETAILS
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

function getProductLoanResponse(
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
   DATE AND MONEY HELPERS
========================================================= */

function getProductLoanDisplayDate(
    $date
): string {

    if (
        empty($date) ||
        $date === "0000-00-00"
    ) {
        return "—";
    }

    $timestamp =
        strtotime($date);

    if ($timestamp === false) {
        return "—";
    }

    return date(
        "m-d-Y",
        $timestamp
    );

}


function getProductLoanDaysBetween(
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

    return (int) $from
        ->diff($to)
        ->days;

}


/* =========================================================
   REQUEST VALIDATION
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] !==
    "GET"
) {

    getProductLoanResponse(
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

    getProductLoanResponse(
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

    getProductLoanResponse(
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

    getProductLoanResponse(
        false,
        "You do not have permission to access the Lending module.",
        [],
        403
    );

}


/* =========================================================
   LOAN ID
========================================================= */

$productLoanID =
    isset($_GET["loan_id"])
        ? (int) $_GET["loan_id"]
        : 0;

if ($productLoanID <= 0) {

    getProductLoanResponse(
        false,
        "Invalid Product Loan ID.",
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

    getProductLoanResponse(
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
   CURRENT USER AND PERMISSION
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

    getProductLoanResponse(
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

    getProductLoanResponse(
        false,
        "The logged-in account could not be found.",
        [],
        401
    );

}

$lendingPermission =
    strtolower(
        trim(
            $currentUser[
                "lending_permission"
            ]
            ?? ""
        )
    ) === "full"
        ? "full"
        : "limited";


/* =========================================================
   LOAN INFORMATION
========================================================= */

$loanStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            loan.*,

            member.member_code,
            member.full_name,

            created_user.name
                AS created_by_name,

            updated_user.name
                AS updated_by_name,

            rejected_user.name
                AS rejected_by_name

        FROM product_loans AS loan

        INNER JOIN members AS member
            ON member.member_id =
               loan.member_id

        LEFT JOIN users AS created_user
            ON created_user.user_id =
               loan.created_by

        LEFT JOIN users AS updated_user
            ON updated_user.user_id =
               loan.updated_by

        LEFT JOIN users AS rejected_user
            ON rejected_user.user_id =
               loan.rejected_by

        WHERE
            loan.product_loan_id = ?
            AND loan.is_archived = 0

        LIMIT 1
        "
    );

if (!$loanStatement) {

    getProductLoanResponse(
        false,
        "Unable to prepare the Product Loan details.",
        [],
        500
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

    getProductLoanResponse(
        false,
        "The Product Loan record could not be found.",
        [],
        404
    );

}


/* =========================================================
   LIVE INTEREST AND BALANCE
========================================================= */

$today =
    date("Y-m-d");

$status =
    $loan["status"];

$outstandingPrincipal =
    round(
        (float)
        $loan[
            "outstanding_principal_amount"
        ],
        2
    );

$storedUnpaidInterest =
    round(
        (float)
        $loan[
            "unpaid_interest_amount"
        ],
        2
    );

$newAccruedInterest =
    0.00;

$totalInterestDue =
    0.00;

$currentBalance =
    null;

$elapsedDays = 0;

$remarks = "—";

if ($status === "Delivered") {

    $interestFromDate =
        $loan["last_interest_date"]
        ?:
        $loan["delivered_date"];

    $elapsedDays =
        getProductLoanDaysBetween(
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
                (
                    (
                        (float)
                        $loan[
                            "interest_rate"
                        ]
                    ) /
                    100
                ) *
                (
                    $elapsedDays /
                    (int)
                    $loan[
                        "term_days"
                    ]
                ),
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

    if (
        $outstandingPrincipal <= 0.009 &&
        $storedUnpaidInterest <= 0.009
    ) {

        $remarks =
            "Paid";

        $newAccruedInterest =
            0.00;

        $totalInterestDue =
            0.00;

        $currentBalance =
            0.00;

    } else {

        $isOverdue = false;

        if (
            !empty(
                $loan["due_date"]
            )
        ) {

            try {

                $dueDate =
                    new DateTimeImmutable(
                        $loan["due_date"],
                        new DateTimeZone(
                            "Asia/Manila"
                        )
                    );

                $todayObject =
                    new DateTimeImmutable(
                        $today,
                        new DateTimeZone(
                            "Asia/Manila"
                        )
                    );

                $isOverdue =
                    $todayObject >
                    $dueDate;

            } catch (
                Throwable $exception
            ) {

                $isOverdue =
                    false;

            }

        }

        $remarks =
            $isOverdue
                ? "Overdue"
                : "On Going";

    }

}


/* =========================================================
   DELIVERY PREVIEW

   Used when a Full-access user selects Delivered.
========================================================= */

$deliveryPreview =
    $today;

try {

    $previewDueDate =
        (
            new DateTimeImmutable(
                $deliveryPreview,
                new DateTimeZone(
                    "Asia/Manila"
                )
            )
        )->modify(
            "+" .
            (int) $loan["term_days"] .
            " days"
        );

    $dueDatePreview =
        $previewDueDate->format(
            "Y-m-d"
        );

} catch (Throwable $exception) {

    $dueDatePreview = null;

}


/* =========================================================
   PRODUCT ITEMS
========================================================= */

$itemStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            item.product_loan_item_id,
            catalog.loan_product_id,
            item.product_name,
            item.quantity,
            item.unit_price,
            item.line_total

        FROM product_loan_items AS item

        LEFT JOIN product_loan_products AS catalog
            ON catalog.archived = 0
            AND LOWER(TRIM(catalog.product_name)) =
                LOWER(TRIM(item.product_name))

        WHERE item.product_loan_id = ?

        ORDER BY
            item.product_loan_item_id ASC
        "
    );

if (!$itemStatement) {

    getProductLoanResponse(
        false,
        "Unable to prepare the requested products.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $itemStatement,
    "i",
    $productLoanID
);

mysqli_stmt_execute(
    $itemStatement
);

$itemResult =
    mysqli_stmt_get_result(
        $itemStatement
    );

$items = [];

while (
    $item =
        mysqli_fetch_assoc(
            $itemResult
        )
) {

    $items[] = [
        "product_loan_item_id" =>
            (int)
            $item[
                "product_loan_item_id"
            ],

        "loan_product_id" =>
            $item[
                "loan_product_id"
            ] !== null
                ? (int)
                    $item[
                        "loan_product_id"
                    ]
                : null,

        "product_name" =>
            $item[
                "product_name"
            ],

        "quantity" =>
            (int)
            $item[
                "quantity"
            ],

        "unit_price" =>
            round(
                (float)
                $item[
                    "unit_price"
                ],
                2
            ),

        "line_total" =>
            round(
                (float)
                $item[
                    "line_total"
                ],
                2
            )
    ];

}

mysqli_stmt_close(
    $itemStatement
);


/* =========================================================
   ATTACHMENTS
========================================================= */

$attachmentStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            product_loan_attachment_id,
            original_file_name,
            mime_type,
            file_size,
            uploaded_at

        FROM product_loan_attachments

        WHERE
            product_loan_id = ?
            AND is_removed = 0

        ORDER BY
            product_loan_attachment_id ASC
        "
    );

if (!$attachmentStatement) {

    getProductLoanResponse(
        false,
        "Unable to prepare Product Loan attachments.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $attachmentStatement,
    "i",
    $productLoanID
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
            "product_loan_attachment_id"
        ];

    $attachments[] = [
        "attachment_id" =>
            $attachmentID,

        "original_file_name" =>
            $attachment[
                "original_file_name"
            ],

        "mime_type" =>
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
            "/spis/lending/product/" .
            "view_product_loan_attachment.php" .
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

$latestReversiblePaymentID = 0;

$latestPaymentStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT product_loan_payment_id

        FROM product_loan_payments

        WHERE
            product_loan_id = ?
            AND transaction_type = 'Payment'
            AND is_reversed = 0

        ORDER BY
            payment_date DESC,
            product_loan_payment_id DESC

        LIMIT 1
        "
    );

if ($latestPaymentStatement) {

    mysqli_stmt_bind_param(
        $latestPaymentStatement,
        "i",
        $productLoanID
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
                "product_loan_payment_id"
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

        FROM product_loan_payments AS payment

        LEFT JOIN users AS recorder
            ON recorder.user_id =
               payment.recorded_by

        LEFT JOIN users AS reversal_user
            ON reversal_user.user_id =
               payment.reversed_by

        WHERE
            payment.product_loan_id = ?

        ORDER BY
            payment.payment_date DESC,
            payment.product_loan_payment_id DESC
        "
    );

if (!$paymentStatement) {

    getProductLoanResponse(
        false,
        "Unable to prepare the payment history.",
        [],
        500
    );

}

mysqli_stmt_bind_param(
    $paymentStatement,
    "i",
    $productLoanID
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
            "product_loan_payment_id"
        ];

    $canReversePayment =
        (
            $lendingPermission === "full" &&
            $payment[
                "transaction_type"
            ] === "Payment" &&
            (int)
            $payment[
                "is_reversed"
            ] === 0 &&
            $paymentID ===
                $latestReversiblePaymentID
        );

    $payments[] = [
        "payment_id" =>
            $paymentID,

        "transaction_type" =>
            $payment[
                "transaction_type"
            ],

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
            $payment[
                "payment_date"
            ],

        "payment_date_display" =>
            getProductLoanDisplayDate(
                $payment[
                    "payment_date"
                ]
            ),

        "payment_amount" =>
            round(
                (float)
                $payment[
                    "payment_amount"
                ],
                2
            ),

        "elapsed_days" =>
            (int)
            $payment[
                "elapsed_days"
            ],

        "newly_accrued_interest" =>
            round(
                (float)
                $payment[
                    "newly_accrued_interest_amount"
                ],
                2
            ),

        "total_interest_due" =>
            round(
                (float)
                $payment[
                    "total_interest_due_amount"
                ],
                2
            ),

        "interest_paid" =>
            round(
                (float)
                $payment[
                    "interest_paid_amount"
                ],
                2
            ),

        "principal_paid" =>
            round(
                (float)
                $payment[
                    "principal_paid_amount"
                ],
                2
            ),

        "balance_after" =>
            round(
                (float)
                $payment[
                    "balance_after_amount"
                ],
                2
            ),

        "is_reversed" =>
            (bool)
            $payment[
                "is_reversed"
            ],

        "reversal_reason" =>
            $payment[
                "reversal_reason"
            ],

        "notes" =>
            $payment[
                "notes"
            ],

        "recorded_by_name" =>
            $payment[
                "recorded_by_name"
            ]
            ?? "Unknown user",

        "reversed_by_name" =>
            $payment[
                "reversed_by_name"
            ],

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
            changer.name AS changed_by_name

        FROM product_loan_status_history
            AS history

        LEFT JOIN users AS changer
            ON changer.user_id =
               history.changed_by

        WHERE
            history.product_loan_id = ?

        ORDER BY
            history.product_loan_status_history_id
            DESC
        "
    );

$statusHistory = [];

if ($statusHistoryStatement) {

    mysqli_stmt_bind_param(
        $statusHistoryStatement,
        "i",
        $productLoanID
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
        $status === "Delivered" &&
        $currentBalance !== null &&
        $currentBalance > 0.009
    );

$canArchive =
    (
        $status === "Rejected" ||
        $remarks === "Paid"
    );


/* =========================================================
   RESPONSE DATA
========================================================= */

getProductLoanResponse(
    true,
    "Product Loan details retrieved.",
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
                (
                    $lendingPermission ===
                    "full"
                ),

            "can_archive" =>
                $canArchive
        ],

        "server" => [
            "date" =>
                $today,

            "date_display" =>
                getProductLoanDisplayDate(
                    $today
                ),

            "timezone" =>
                "Asia/Manila",

            "delivery_date_preview" =>
                $deliveryPreview,

            "delivery_date_preview_display" =>
                getProductLoanDisplayDate(
                    $deliveryPreview
                ),

            "due_date_preview" =>
                $dueDatePreview,

            "due_date_preview_display" =>
                getProductLoanDisplayDate(
                    $dueDatePreview
                )
        ],

        "loan" => [
            "product_loan_id" =>
                (int)
                $loan[
                    "product_loan_id"
                ],

            "display_id" =>
                "PL-" .
                str_pad(
                    (string)
                    $loan[
                        "product_loan_id"
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
                $loan[
                    "member_code"
                ],

            "full_name" =>
                $loan[
                    "full_name"
                ],

            "application_date" =>
                $loan[
                    "application_date"
                ],

            "application_date_display" =>
                getProductLoanDisplayDate(
                    $loan[
                        "application_date"
                    ]
                ),

            "status" =>
                $status,

            "delivered_date" =>
                $loan[
                    "delivered_date"
                ],

            "delivered_date_display" =>
                $loan[
                    "delivered_date"
                ]
                    ? getProductLoanDisplayDate(
                        $loan[
                            "delivered_date"
                        ]
                    )
                    : "Not delivered",

            "due_date" =>
                $loan[
                    "due_date"
                ],

            "due_date_display" =>
                $loan[
                    "due_date"
                ]
                    ? getProductLoanDisplayDate(
                        $loan[
                            "due_date"
                        ]
                    )
                    : (
                        $status === "Pending"
                            ? "Generated upon delivery"
                            : "—"
                    ),

            "interest_rate" =>
                round(
                    (float)
                    $loan[
                        "interest_rate"
                    ],
                    2
                ),

            "term_days" =>
                (int)
                $loan[
                    "term_days"
                ],

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
                    (float)
                    $loan[
                        "net_payments_amount"
                    ],
                    2
                ),

            "elapsed_days" =>
                $elapsedDays,

            "remarks" =>
                $remarks,

            "rejection_reason" =>
                $loan[
                    "rejection_reason"
                ],

            "created_by_name" =>
                $loan[
                    "created_by_name"
                ]
                ?? "Unknown user",

            "updated_by_name" =>
                $loan[
                    "updated_by_name"
                ],

            "rejected_by_name" =>
                $loan[
                    "rejected_by_name"
                ]
        ],

        "items" =>
            $items,

        "attachments" =>
            $attachments,

        "payments" =>
            $payments,

        "status_history" =>
            $statusHistory
    ]
);