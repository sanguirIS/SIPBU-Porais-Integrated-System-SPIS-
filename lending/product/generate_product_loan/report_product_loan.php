<?php

/* =========================================================
   PRODUCT LOAN REPORT
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

error_reporting(E_ALL);
ini_set("display_errors", "0");

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   HELPERS
========================================================= */

function productLoanReportEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function productLoanReportMoney($value): string
{
    return "₱" . number_format(
        (float) $value,
        2
    );
}


function productLoanReportDate(
    $date,
    string $format = "m-d-Y"
): string {
    if (
        empty($date) ||
        $date === "0000-00-00"
    ) {
        return "—";
    }

    $timestamp = strtotime(
        (string) $date
    );

    if ($timestamp === false) {
        return "—";
    }

    return date(
        $format,
        $timestamp
    );
}


function productLoanReportFail(
    string $message,
    int $statusCode = 400
): void {
    http_response_code($statusCode);

    $safeMessage = productLoanReportEscape(
        $message
    );

    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Unable to generate report</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            color: #252525;
            background: #f5f2cf;
            font-family: Arial, sans-serif;
        }

        main {
            width: min(480px, 100%);
            padding: 34px;
            text-align: center;
            background: #ffffff;
            border-radius: 22px;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.16);
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        p {
            margin: 0;
            color: #666666;
            font-size: 17px;
            line-height: 1.6;
        }

        button {
            margin-top: 23px;
            padding: 11px 25px;
            color: #ffffff;
            background: #8ca15b;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>

<body>

    <main>
        <h1>Unable to generate report</h1>

        <p>{$safeMessage}</p>

        <button
        type="button"
        onclick="window.close()">
            CLOSE
        </button>
    </main>

</body>
</html>
HTML;

    exit;
}


function productLoanReportValidDate(
    string $date,
    DateTimeZone $timezone
): bool {
    if (
        preg_match(
            "/^\d{4}-\d{2}-\d{2}$/",
            $date
        ) !== 1
    ) {
        return false;
    }

    $dateObject = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        $date,
        $timezone
    );

    return (
        $dateObject !== false &&
        $dateObject->format("Y-m-d") === $date
    );
}


function productLoanReportDaysBetween(
    ?string $fromDate,
    DateTimeImmutable $toDate,
    DateTimeZone $timezone
): int {
    if (
        empty($fromDate) ||
        $fromDate === "0000-00-00"
    ) {
        return 0;
    }

    try {
        $fromDateObject = new DateTimeImmutable(
            $fromDate,
            $timezone
        );
    } catch (Throwable $exception) {
        return 0;
    }

    if ($toDate <= $fromDateObject) {
        return 0;
    }

    return (int) $fromDateObject
        ->diff($toDate)
        ->days;
}


function productLoanReportBindParameters(
    mysqli_stmt $statement,
    string $types,
    array &$parameters
): bool {
    if (
        $types === "" ||
        count($parameters) === 0
    ) {
        return true;
    }

    $arguments = [
        $statement,
        $types
    ];

    foreach ($parameters as $index => $value) {
        $arguments[] = &$parameters[$index];
    }

    return (bool) call_user_func_array(
        "mysqli_stmt_bind_param",
        $arguments
    );
}


/* =========================================================
   SESSION VALIDATION
========================================================= */

$userID = isset($_SESSION["user_id"])
    ? (int) $_SESSION["user_id"]
    : 0;

if ($userID <= 0) {
    productLoanReportFail(
        "Your session has expired. Please log in again.",
        401
    );
}

if (
    (int) (
        $_SESSION["lending_access"]
        ?? 0
    ) !== 1
) {

    productLoanReportFail(
        "You do not have permission to access the Lending module.",
        403
    );

}


/* =========================================================
   DATABASE
========================================================= */

require_once "../../../db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    productLoanReportFail(
        "Database connection is unavailable.",
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

mysqli_query(
    $conn,
    "SET SESSION group_concat_max_len = 20000"
);


/* =========================================================
   CURRENT USER
========================================================= */

$userStatement = mysqli_prepare(
    $conn,
    "
    SELECT
        name,
        role

    FROM users

    WHERE user_id = ?

    LIMIT 1
    "
);

if (!$userStatement) {
    productLoanReportFail(
        "Unable to verify the current account.",
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

$userResult = mysqli_stmt_get_result(
    $userStatement
);

$currentUser = $userResult
    ? mysqli_fetch_assoc($userResult)
    : null;

mysqli_stmt_close(
    $userStatement
);

if (!$currentUser) {
    productLoanReportFail(
        "The logged-in account could not be found.",
        401
    );
}

$preparedBy = trim(
    (string) (
        $currentUser["name"]
        ?? "Unknown User"
    )
);

if ($preparedBy === "") {
    $preparedBy = "Unknown User";
}

$preparedByRole = ucwords(
    str_replace(
        "_",
        " ",
        trim(
            (string) (
                $currentUser["role"]
                ?? "User"
            )
        )
    )
);

if ($preparedByRole === "") {
    $preparedByRole = "User";
}


/* =========================================================
   FILTERS
========================================================= */

$interest = trim(
    (string) (
        $_GET["interest"]
        ?? "all"
    )
);

$status = trim(
    (string) (
        $_GET["status"]
        ?? "all"
    )
);

$remarksFilter = trim(
    (string) (
        $_GET["remarks"]
        ?? "all"
    )
);

$dateOption = trim(
    (string) (
        $_GET["date_option"]
        ?? "all"
    )
);

$dateFrom = trim(
    (string) (
        $_GET["from"]
        ?? ""
    )
);

$dateTo = trim(
    (string) (
        $_GET["to"]
        ?? ""
    )
);

$allowedInterest = [
    "all",
    "10",
    "20"
];

$allowedStatus = [
    "all",
    "Pending",
    "Delivered",
    "Rejected"
];

$allowedRemarks = [
    "all",
    "On Going",
    "Overdue",
    "Paid"
];

$allowedDateOptions = [
    "all",
    "custom"
];

if (
    !in_array(
        $interest,
        $allowedInterest,
        true
    ) ||
    !in_array(
        $status,
        $allowedStatus,
        true
    ) ||
    !in_array(
        $remarksFilter,
        $allowedRemarks,
        true
    ) ||
    !in_array(
        $dateOption,
        $allowedDateOptions,
        true
    )
) {
    productLoanReportFail(
        "One or more report filters are invalid.",
        422
    );
}


if (
    in_array(
        $status,
        [
            "Pending",
            "Rejected"
        ],
        true
    ) &&
    $remarksFilter !== "all"
) {
    productLoanReportFail(
        "Remarks are not applicable to Pending or Rejected Product Loans.",
        422
    );
}


/* =========================================================
   ASIA/MANILA SERVER DATE
========================================================= */

$manilaTimezone = new DateTimeZone(
    "Asia/Manila"
);

$today = new DateTimeImmutable(
    date("Y-m-d"),
    $manilaTimezone
);

if ($dateOption === "custom") {
    if (
        !productLoanReportValidDate(
            $dateFrom,
            $manilaTimezone
        ) ||
        !productLoanReportValidDate(
            $dateTo,
            $manilaTimezone
        )
    ) {
        productLoanReportFail(
            "Please provide a valid From and To date.",
            422
        );
    }

    if ($dateFrom > $dateTo) {
        productLoanReportFail(
            "The From date must not be later than the To date.",
            422
        );
    }

    if (
        $dateFrom > $today->format("Y-m-d") ||
        $dateTo > $today->format("Y-m-d")
    ) {
        productLoanReportFail(
            "Application dates cannot be later than the current Asia/Manila server date.",
            422
        );
    }
}


/* =========================================================
   PRODUCT LOAN QUERY
========================================================= */

$sql = "
    SELECT
        loan.product_loan_id,
        loan.application_date,
        loan.interest_rate,
        loan.term_days,
        loan.principal_amount,
        loan.scheduled_interest_amount,
        loan.scheduled_total_amount,
        loan.outstanding_principal_amount,
        loan.unpaid_interest_amount,
        loan.net_payments_amount,
        loan.status,
        loan.delivered_date,
        loan.due_date,
        loan.last_interest_date,
        loan.fully_paid_at,

        member.member_code,
        member.full_name,

        item_summary.product_names,
        item_summary.product_quantities

    FROM product_loans AS loan

    INNER JOIN members AS member
        ON member.member_id = loan.member_id

    LEFT JOIN (
        SELECT
            product_loan_id,

            GROUP_CONCAT(
                product_name
                ORDER BY product_loan_item_id
                SEPARATOR '|||'
            ) AS product_names,

            GROUP_CONCAT(
                quantity
                ORDER BY product_loan_item_id
                SEPARATOR '|||'
            ) AS product_quantities

        FROM product_loan_items

        GROUP BY product_loan_id
    ) AS item_summary
        ON item_summary.product_loan_id =
           loan.product_loan_id

    WHERE loan.is_archived = 0
";

$parameterTypes = "";
$parameters = [];

if ($interest !== "all") {
    $sql .= "
        AND loan.interest_rate = ?
    ";

    $parameterTypes .= "d";
    $parameters[] = (float) $interest;
}

if ($status !== "all") {
    $sql .= "
        AND loan.status = ?
    ";

    $parameterTypes .= "s";
    $parameters[] = $status;
}

if ($dateOption === "custom") {
    $sql .= "
        AND loan.application_date
            BETWEEN ? AND ?
    ";

    $parameterTypes .= "ss";
    $parameters[] = $dateFrom;
    $parameters[] = $dateTo;
}

$sql .= "
    ORDER BY
        loan.product_loan_id ASC
";

$loanStatement = mysqli_prepare(
    $conn,
    $sql
);

if (!$loanStatement) {
    productLoanReportFail(
        "Unable to prepare the Product Loan report.",
        500
    );
}

if (
    !productLoanReportBindParameters(
        $loanStatement,
        $parameterTypes,
        $parameters
    )
) {
    mysqli_stmt_close(
        $loanStatement
    );

    productLoanReportFail(
        "Unable to apply the selected report filters.",
        500
    );
}

if (
    !mysqli_stmt_execute(
        $loanStatement
    )
) {
    mysqli_stmt_close(
        $loanStatement
    );

    productLoanReportFail(
        "Unable to generate the Product Loan report.",
        500
    );
}

$loanResult = mysqli_stmt_get_result(
    $loanStatement
);

if ($loanResult === false) {
    mysqli_stmt_close(
        $loanStatement
    );

    productLoanReportFail(
        "Unable to read the generated Product Loan report.",
        500
    );
}


/* =========================================================
   REPORT DATA AND SUMMARY
========================================================= */

$reportRows = [];

$pendingCount = 0;
$deliveredCount = 0;
$rejectedCount = 0;

$totalDeliveredPrincipal = 0.00;
$totalScheduledInterest = 0.00;
$totalScheduledAmount = 0.00;
$totalPayments = 0.00;
$totalCurrentReceivables = 0.00;

while (
    $loan = mysqli_fetch_assoc(
        $loanResult
    )
) {
    $loanStatus = trim(
        (string) $loan["status"]
    );

    $principalBalance = round(
        (float) $loan[
            "outstanding_principal_amount"
        ],
        2
    );

    $unpaidInterest = round(
        (float) $loan[
            "unpaid_interest_amount"
        ],
        2
    );

    $newAccruedInterest = 0.00;
    $currentBalance = null;
    $remarks = "—";

    /*
     * Interest starts only after the Product Loan
     * has been marked as Delivered.
     */
    if ($loanStatus === "Delivered") {
        $interestFromDate = !empty(
            $loan["last_interest_date"]
        )
            ? $loan["last_interest_date"]
            : $loan["delivered_date"];

        $elapsedDays = productLoanReportDaysBetween(
            $interestFromDate,
            $today,
            $manilaTimezone
        );

        $termDays = max(
            1,
            (int) $loan["term_days"]
        );

        if (
            $principalBalance > 0 &&
            $elapsedDays > 0
        ) {
            $newAccruedInterest = round(
                $principalBalance *
                (
                    (
                        (float) $loan[
                            "interest_rate"
                        ] / 100
                    ) *
                    (
                        $elapsedDays /
                        $termDays
                    )
                ),
                2
            );
        }

        $currentBalance = round(
            $principalBalance +
            $unpaidInterest +
            $newAccruedInterest,
            2
        );

        if (
            $principalBalance <= 0.009 &&
            $unpaidInterest <= 0.009
        ) {
            $remarks = "Paid";
            $currentBalance = 0.00;
        } else {
            $dueDateObject = null;

            if (!empty($loan["due_date"])) {
                try {
                    $dueDateObject =
                        new DateTimeImmutable(
                            $loan["due_date"],
                            $manilaTimezone
                        );
                } catch (Throwable $exception) {
                    $dueDateObject = null;
                }
            }

            $remarks = (
                $dueDateObject !== null &&
                $today > $dueDateObject
            )
                ? "Overdue"
                : "On Going";
        }
    }

    /*
     * Remarks are calculated by the system,
     * so this filter is applied after calculation.
     */
    if (
        $remarksFilter !== "all" &&
        $remarks !== $remarksFilter
    ) {
        continue;
    }

    $productNames = !empty(
        $loan["product_names"]
    )
        ? explode(
            "|||",
            $loan["product_names"]
        )
        : [];

    $productQuantities = !empty(
        $loan["product_quantities"]
    )
        ? explode(
            "|||",
            $loan["product_quantities"]
        )
        : [];

    $products = [];

    foreach (
        $productNames as
        $index => $productName
    ) {
        $quantity = isset(
            $productQuantities[$index]
        )
            ? (int) $productQuantities[$index]
            : 0;

        $products[] = [
            "name" => trim(
                (string) $productName
            ),
            "quantity" => $quantity
        ];
    }

    $loan["computed_remarks"] = $remarks;
    $loan["computed_balance"] = $currentBalance;
    $loan["products"] = $products;

    $reportRows[] = $loan;

    switch ($loanStatus) {
        case "Pending":
            $pendingCount++;
            break;

        case "Delivered":
            $deliveredCount++;

            $totalDeliveredPrincipal +=
                (float) $loan[
                    "principal_amount"
                ];

            $totalScheduledInterest +=
                (float) $loan[
                    "scheduled_interest_amount"
                ];

            $totalScheduledAmount +=
                (float) $loan[
                    "scheduled_total_amount"
                ];

            $totalPayments +=
                (float) $loan[
                    "net_payments_amount"
                ];

            $totalCurrentReceivables +=
                (float) (
                    $currentBalance
                    ?? 0
                );

            break;

        case "Rejected":
            $rejectedCount++;
            break;
    }
}

mysqli_stmt_close(
    $loanStatement
);

mysqli_close(
    $conn
);

$totalRecords = count(
    $reportRows
);


/* =========================================================
   FILTER LABELS
========================================================= */

$interestLabel = $interest === "all"
    ? "All Interest Rates"
    : (
        $interest === "10"
            ? "10% - 180 Days"
            : "20% - 360 Days"
    );

$statusLabel = $status === "all"
    ? "All Status"
    : $status;

$remarksLabel = $remarksFilter === "all"
    ? "All Remarks"
    : $remarksFilter;

$dateRangeLabel = $dateOption === "all"
    ? "All Applications"
    : productLoanReportDate(
        $dateFrom,
        "M d, Y"
    ) .
    " - " .
    productLoanReportDate(
        $dateTo,
        "M d, Y"
    );

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Product Loan Report</title>

    <style>

        :root {
            --report-green: #8ca15b;
            --report-green-dark: #6f8347;
            --report-text: #222222;
            --report-muted: #666666;
            --report-red: #c84444;
            --report-orange: #bb7a00;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 40px;
            color: var(--report-text);
            background: #ffffff;
            font-family: Arial, sans-serif;
        }


        /* =====================================================
        REPORT HEADER
        ===================================================== */

        .report-header {
            text-align: center;
        }

        .report-logo {
            width: 90px;
            height: auto;
            margin-bottom: 8px;
        }

        .report-header h1 {
            margin: 0 0 5px;
            font-size: 26px;
        }

        .report-header h2 {
            margin: 0;
            color: var(--report-muted);
            font-size: 21px;
        }

        .report-header p {
            margin: 7px 0 0;
            color: var(--report-muted);
            font-size: 14px;
        }


        /* =====================================================
        REPORT BUTTONS
        ===================================================== */

        .report-actions {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            margin: 20px 0;
        }

        .report-actions button {
            padding: 10px 18px;
            color: #ffffff;
            background: var(--report-green);
            border: none;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition:
                background-color 0.2s ease,
                transform 0.2s ease;
        }

        .report-actions button:hover {
            background: var(--report-green-dark);
            transform: translateY(-1px);
        }

        .report-actions .close-btn {
            background: var(--report-red);
        }

        .report-actions .close-btn:hover {
            background: #ad3333;
        }


        /* =====================================================
        REPORT INFORMATION
        Two compact columns
        ===================================================== */

        .report-information {
            display: grid;
            grid-template-columns: max-content max-content;
            column-gap: 90px;
            row-gap: 14px;
            justify-content: start;
            width: 100%;
            margin: 20px 0;
            padding: 0;
            background: transparent;
            border: none;
        }

        .report-information p {
            margin: 0;
            font-size: 14px;
            line-height: 1.4;
        }

        .report-scope-note {
            margin: 12px 0 0;
            color: var(--report-muted);
            font-size: 11px;
        }


        /* =====================================================
        REPORT TABLE
        ===================================================== */

        .report-table-wrapper {
            width: 100%;
            margin-top: 17px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            min-width: 1150px;
            margin-top: 20px;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th {
            padding: 9px 6px;
            color: #ffffff;
            background: var(--report-green);
            border: 1px solid #000000;
            font-size: 10px;
            text-transform: uppercase;
        }

        td {
            padding: 8px 6px;
            border: 1px solid #000000;
            font-size: 10px;
            line-height: 1.35;
            text-align: center;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        .left-cell {
            text-align: left;
        }

        .money-cell {
            text-align: right;
            white-space: nowrap;
        }

        .primary-text {
            display: block;
            font-weight: 700;
        }

        .secondary-text {
            display: block;
            margin-top: 3px;
            color: var(--report-muted);
            font-size: 9px;
        }

        .product-entry {
            display: block;
            margin-bottom: 3px;
        }

        .product-entry:last-child {
            margin-bottom: 0;
        }

        .empty-row td {
            padding: 28px;
            color: var(--report-muted);
            font-size: 13px;
            text-align: center;
        }


        /* =====================================================
        STATUS AND REMARKS
        ===================================================== */

        .status-text,
        .remarks-text {
            font-weight: 700;
        }

        .status-pending {
            color: var(--report-orange);
        }

        .status-delivered,
        .remarks-paid {
            color: #168354;
        }

        .status-rejected,
        .remarks-overdue {
            color: var(--report-red);
        }

        .remarks-ongoing {
            color: var(--report-orange);
        }


        /* =====================================================
        SUMMARY REPORT
        ===================================================== */

        .summary-title {
            margin: 18px 0 12px;
            font-size: 22px;
            font-weight: 700;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(320px, 380px));
            column-gap: 70px;
            row-gap: 12px;
            justify-content: start;
        }

        .summary-item {
            display: grid;
            grid-template-columns: minmax(170px, 1fr) max-content;
            align-items: baseline;
            column-gap: 12px;
            min-height: 0;
            margin: 0;
            padding: 0;
            background: transparent;
            border: none;
        }

        .summary-item span {
            min-width: 0;
            color: var(--report-text);
            font-size: 14px;
            font-weight: 700;
            text-transform: none;
        }

        .summary-item span::after {
            content: ":";
        }

        .summary-item strong {
            margin: 0;
            color: var(--report-text);
            font-size: 14px;
            font-weight: 700;
            white-space: nowrap;
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .calculation-note {
            margin: 14px 0 0;
            color: var(--report-muted);
            font-size: 10px;
            line-height: 1.5;
        }


        /* =====================================================
        PREPARED BY
        ===================================================== */

        .prepared-by {
            margin-top: 60px;
            text-align: right;
            break-inside: avoid;
        }

        .prepared-by p {
            margin: 0;
        }

        .signature-space {
            height: 40px;
        }

        .prepared-by strong {
            display: inline-block;
            min-width: 190px;
            padding: 0;
            border: none;
            text-align: center;
        }

        .prepared-by span {
            display: inline-block;
            min-width: 190px;
            margin-top: 3px;
            color: var(--report-muted);
            font-size: 12px;
            text-align: center;
        }


        /* =====================================================
        PRINT SETTINGS
        ===================================================== */

        @page {

            margin:
                10mm;

        }


        @media print {

            body {

                margin:
                    0;

                print-color-adjust:
                    exact;

                -webkit-print-color-adjust:
                    exact;

            }


            .report-actions {

                display:
                    none
                    !important;

            }


            .report-information {

                display:
                    grid;

                grid-template-columns:
                    max-content
                    max-content;

                column-gap:
                    55px;

                row-gap:
                    9px;

                justify-content:
                    start;

            }


            .report-table-wrapper {

                width:
                    100%;

                overflow:
                    visible;

            }


            table {

                width:
                    100%;

                min-width:
                    0;

                table-layout:
                    fixed;

            }


            thead {

                display:
                    table-header-group;

            }


            tr,
            .summary-item,
            .prepared-by {

                break-inside:
                    avoid;

                page-break-inside:
                    avoid;

            }

        }


        /* =====================================================
        PORTRAIT PRINT
        ===================================================== */

        @media print and (orientation: portrait) {

            .report-logo {

                width:
                    72px;

            }


            .report-header h1 {

                font-size:
                    21px;

            }


            .report-header h2 {

                font-size:
                    17px;

            }


            .report-header p {

                font-size:
                    11px;

            }


            .report-information {

                column-gap:
                    32px;

                row-gap:
                    7px;

                margin:
                    13px 0;

            }


            .report-information p {

                font-size:
                    9.5px;

                line-height:
                    1.3;

            }


            .report-scope-note {

                margin-top:
                    7px;

                font-size:
                    8.5px;

                line-height:
                    1.35;

            }


            .report-table-wrapper {

                margin-top:
                    11px;

            }


            table {

                margin-top:
                    10px;

            }


            /*
            11 PRODUCT LOAN COLUMNS

            Loan
            Member
            Products
            Principal
            Interest
            Scheduled Total
            Payments
            Balance
            Delivery / Due
            Status
            Remarks
            */

            col:nth-child(1) {

                width:
                    8%
                    !important;

            }


            col:nth-child(2) {

                width:
                    10%
                    !important;

            }


            col:nth-child(3) {

                width:
                    14%
                    !important;

            }


            col:nth-child(4) {

                width:
                    8%
                    !important;

            }


            col:nth-child(5) {

                width:
                    7%
                    !important;

            }


            col:nth-child(6) {

                width:
                    9%
                    !important;

            }


            col:nth-child(7) {

                width:
                    8%
                    !important;

            }


            col:nth-child(8) {

                width:
                    9%
                    !important;

            }


            col:nth-child(9) {

                width:
                    10%
                    !important;

            }


            col:nth-child(10) {

                width:
                    8%
                    !important;

            }


            col:nth-child(11) {

                width:
                    9%
                    !important;

            }


            th {

                padding:
                    5px 2px;

                font-size:
                    7.8px;

                line-height:
                    1.15;

                white-space:
                    normal;

                overflow-wrap:
                    break-word;

            }


            td {

                padding:
                    6px 3px;

                font-size:
                    8.5px;

                line-height:
                    1.28;

                overflow-wrap:
                    break-word;

                word-break:
                    normal;

            }


            .primary-text {

                line-height:
                    1.22;

            }


            .secondary-text {

                margin-top:
                    2px;

                font-size:
                    7.5px;

                line-height:
                    1.2;

            }


            .product-entry {

                margin-bottom:
                    2px;

                white-space:
                    normal;

                overflow:
                    visible;

                text-overflow:
                    clip;

                overflow-wrap:
                    break-word;

                line-height:
                    1.25;

            }


            .money-cell {

                white-space:
                    normal;

                text-align:
                    right;

                font-variant-numeric:
                    tabular-nums;

            }


            .status-text,
            .remarks-text {

                font-size:
                    8px;

                line-height:
                    1.2;

            }


            .empty-row td {

                padding:
                    20px 8px;

                font-size:
                    10px;

            }


            .summary-title {

                margin:
                    13px 0 9px;

                font-size:
                    17px;

            }


            .summary-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(0, 1fr)
                    );

                column-gap:
                    25px;

                row-gap:
                    7px;

            }


            .summary-item {

                grid-template-columns:
                    minmax(0, 1fr)
                    max-content;

                column-gap:
                    8px;

            }


            .summary-item span,
            .summary-item strong {

                font-size:
                    9.5px;

            }


            .calculation-note {

                margin-top:
                    10px;

                font-size:
                    8px;

                line-height:
                    1.35;

            }


            .prepared-by {

                margin-top:
                    35px;

            }


            .signature-space {

                height:
                    28px;

            }


            .prepared-by strong {

                min-width:
                    155px;

                font-size:
                    10px;

            }


            .prepared-by span {

                min-width:
                    155px;

                font-size:
                    9px;

            }

        }


        /* =====================================================
        LANDSCAPE PRINT
        ===================================================== */

        @media print and (orientation: landscape) {

            .report-information {

                column-gap:
                    90px;

                row-gap:
                    14px;

            }


            th {

                padding:
                    9px 6px;

                font-size:
                    10px;

            }


            td {

                padding:
                    8px 6px;

                font-size:
                    10px;

            }


            .secondary-text {

                font-size:
                    9px;

            }


            .product-entry {

                white-space:
                    normal;

                overflow-wrap:
                    break-word;

            }


            .money-cell {

                white-space:
                    nowrap;

            }

        }


        /* =====================================================
        SMALL SCREEN
        ===================================================== */

        @media (max-width: 700px) {

            body {
                margin: 18px;
            }

            .report-information {
                grid-template-columns: 1fr;
                row-gap: 12px;
            }
        }

    </style>

</head>

<body>

    <!-- REPORT HEADER -->

    <header class="report-header">

        <img
        src="../../../assets/logo/triangle-logo.png"
        alt="SIPBU-MPC logo"
        class="report-logo">

        <h1>
            SIPBU-MPC PORAIS INTEGRATED SYSTEM
        </h1>

        <h2>
            Product Loan Report
        </h2>

        <p>
            Lending Management System
        </p>

    </header>


    <!-- REPORT ACTIONS -->

    <div class="report-actions">

        <button
        type="button"
        onclick="window.print()">
            🖨 Print / Save as PDF
        </button>

        <button
        type="button"
        class="close-btn"
        onclick="window.close()">
            ✖ Close
        </button>

    </div>


    <!-- REPORT FILTER INFORMATION -->

    <section class="report-information">

        <p>
            <strong>Date Generated:</strong><br>
            <?php echo date("F d, Y"); ?>
        </p>

        <p>
            <strong>Time Generated:</strong><br>
            <?php echo date("h:i A"); ?>
        </p>

        <p>
            <strong>Interest Rate:</strong><br>

            <?php
            echo productLoanReportEscape(
                $interestLabel
            );
            ?>
        </p>

        <p>
            <strong>Status:</strong><br>

            <?php
            echo productLoanReportEscape(
                $statusLabel
            );
            ?>
        </p>

        <p>
            <strong>Remarks:</strong><br>

            <?php
            echo productLoanReportEscape(
                $remarksLabel
            );
            ?>
        </p>

        <p>
            <strong>Application Date:</strong><br>

            <?php
            echo productLoanReportEscape(
                $dateRangeLabel
            );
            ?>
        </p>

        <p>
            <strong>Record Scope:</strong><br>
            Current Product Loan Ledger
        </p>

        <p>
            <strong>Balance As Of:</strong><br>
            <?php echo date("F d, Y"); ?>
        </p>

    </section>

    <p class="report-scope-note">
        Archived Product Loans are excluded because this
        report represents the current Product Loan ledger.
    </p>


    <!-- PRODUCT LOAN TABLE -->

    <div class="report-table-wrapper">

        <table>

            <colgroup>
                <col style="width: 8%;">
                <col style="width: 10%;">
                <col style="width: 14%;">
                <col style="width: 8%;">
                <col style="width: 7%;">
                <col style="width: 9%;">
                <col style="width: 8%;">
                <col style="width: 9%;">
                <col style="width: 10%;">
                <col style="width: 8%;">
                <col style="width: 9%;">
            </colgroup>

            <thead>

                <tr>
                    <th>Loan</th>
                    <th>Member</th>
                    <th>Products</th>
                    <th>Principal</th>
                    <th>Interest Rate</th>
                    <th>Payoff Total</th>
                    <th>Payments</th>
                    <th>Outstanding Balance</th>
                    <th>Delivery / Due</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>

            </thead>

            <tbody>

                <?php if ($totalRecords === 0) { ?>

                    <tr class="empty-row">

                        <td colspan="11">
                            No Product Loan records matched
                            the selected report filters.
                        </td>

                    </tr>

                <?php } else { ?>

                    <?php foreach ($reportRows as $loan) { ?>

                        <?php

                        $loanID = "PL-" . str_pad(
                            (string) $loan[
                                "product_loan_id"
                            ],
                            6,
                            "0",
                            STR_PAD_LEFT
                        );

                        $statusClass =
                            "status-" .
                            strtolower(
                                (string) $loan["status"]
                            );

                        $remarksClass =
                            $loan[
                                "computed_remarks"
                            ] === "—"
                                ? ""
                                : "remarks-" .
                                  strtolower(
                                      str_replace(
                                          " ",
                                          "",
                                          $loan[
                                              "computed_remarks"
                                          ]
                                      )
                                  );

                        ?>

                        <tr>

                            <!-- LOAN -->

                            <td>

                                <span class="primary-text">
                                    <?php
                                    echo productLoanReportEscape(
                                        $loanID
                                    );
                                    ?>
                                </span>

                                <span class="secondary-text">
                                    Applied:

                                    <?php
                                    echo productLoanReportDate(
                                        $loan[
                                            "application_date"
                                        ]
                                    );
                                    ?>
                                </span>

                            </td>


                            <!-- MEMBER -->

                            <td>

                                <span class="primary-text">
                                    <?php
                                    echo productLoanReportEscape(
                                        $loan[
                                            "full_name"
                                        ]
                                    );
                                    ?>
                                </span>

                                <span class="secondary-text">
                                    <?php
                                    echo productLoanReportEscape(
                                        $loan[
                                            "member_code"
                                        ]
                                    );
                                    ?>
                                </span>

                            </td>


                            <!-- PRODUCTS -->

                            <td class="left-cell">

                                <?php
                                if (
                                    count(
                                        $loan["products"]
                                    ) === 0
                                ) {
                                ?>

                                    —

                                <?php } else { ?>

                                    <?php
                                    foreach (
                                        $loan["products"]
                                        as $product
                                    ) {
                                    ?>

                                        <span class="product-entry">

                                            <?php
                                            echo productLoanReportEscape(
                                                $product[
                                                    "name"
                                                ]
                                            );
                                            ?>

                                            ×

                                            <?php
                                            echo number_format(
                                                (int) $product[
                                                    "quantity"
                                                ]
                                            );
                                            ?>

                                        </span>

                                    <?php } ?>

                                <?php } ?>

                            </td>


                            <!-- PRINCIPAL -->

                            <td class="money-cell">

                                <?php
                                echo productLoanReportMoney(
                                    $loan[
                                        "principal_amount"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- INTEREST -->

                            <td>

                                <span class="primary-text">

                                    <?php
                                    echo number_format(
                                        (float) $loan[
                                            "interest_rate"
                                        ],
                                        0
                                    );
                                    ?>%

                                </span>

                                <span class="secondary-text">

                                    <?php
                                    echo number_format(
                                        (int) $loan[
                                            "term_days"
                                        ]
                                    );
                                    ?>

                                    Days

                                </span>

                            </td>


                            <!-- SCHEDULED TOTAL -->

                            <td class="money-cell">

                                <?php
                                echo productLoanReportMoney(
                                    $loan[
                                        "scheduled_total_amount"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- PAYMENTS -->

                            <td class="money-cell">

                                <?php
                                echo productLoanReportMoney(
                                    $loan[
                                        "net_payments_amount"
                                    ]
                                );
                                ?>

                            </td>


                            <!-- CURRENT BALANCE -->

                            <td class="money-cell">

                                <?php

                                echo $loan[
                                    "computed_balance"
                                ] === null
                                    ? "—"
                                    : productLoanReportMoney(
                                        $loan[
                                            "computed_balance"
                                        ]
                                    );

                                ?>

                            </td>


                            <!-- DELIVERY AND DUE DATE -->

                            <td>

                                <span class="primary-text">

                                    Delivered:

                                    <?php
                                    echo productLoanReportDate(
                                        $loan[
                                            "delivered_date"
                                        ]
                                    );
                                    ?>

                                </span>

                                <span class="secondary-text">

                                    Due:

                                    <?php
                                    echo productLoanReportDate(
                                        $loan[
                                            "due_date"
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span
                                class="status-text <?php echo productLoanReportEscape($statusClass); ?>">

                                    <?php
                                    echo productLoanReportEscape(
                                        $loan["status"]
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- REMARKS -->

                            <td>

                                <span
                                class="remarks-text <?php echo productLoanReportEscape($remarksClass); ?>">

                                    <?php
                                    echo productLoanReportEscape(
                                        $loan[
                                            "computed_remarks"
                                        ]
                                    );
                                    ?>

                                </span>

                            </td>

                        </tr>

                    <?php } ?>

                <?php } ?>

            </tbody>

        </table>

    </div>


    <!-- SUMMARY -->

    <hr>
    <hr>

    <h3 class="summary-title">
        SUMMARY REPORT
    </h3>

    <section class="summary-grid">

        <div class="summary-item">
            <span>Total Records</span>

            <strong>
                <?php echo number_format($totalRecords); ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Pending</span>

            <strong>
                <?php echo number_format($pendingCount); ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Delivered</span>

            <strong>
                <?php echo number_format($deliveredCount); ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Rejected</span>

            <strong>
                <?php echo number_format($rejectedCount); ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Delivered Principal</span>

            <strong>
                <?php
                echo productLoanReportMoney(
                    $totalDeliveredPrincipal
                );
                ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Interest Payable</span>

            <strong>
                <?php
                echo productLoanReportMoney(
                    $totalScheduledInterest
                );
                ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Payoff Total</span>

            <strong>
                <?php
                echo productLoanReportMoney(
                    $totalScheduledAmount
                );
                ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Net Payments</span>

            <strong>
                <?php
                echo productLoanReportMoney(
                    $totalPayments
                );
                ?>
            </strong>
        </div>

        <div class="summary-item">
            <span>Current Receivables</span>

            <strong>
                <?php
                echo productLoanReportMoney(
                    $totalCurrentReceivables
                );
                ?>
            </strong>
        </div>

    </section>

    <p class="calculation-note">
        Financial totals include Delivered Product Loans only.
        Current receivables include unpaid principal, unpaid
        interest, and prorated interest accrued as of the
        Asia/Manila server date.
    </p>


    <!-- PREPARED BY -->

    <hr>
    <hr>

    <section class="prepared-by">

        <p>
            <strong>Prepared by:</strong>
        </p>

        <div class="signature-space"></div>

        <strong>
            <?php
            echo productLoanReportEscape(
                $preparedBy
            );
            ?>
        </strong>

        <br>

        <span>
            <?php
            echo productLoanReportEscape(
                $preparedByRole
            );
            ?>
        </span>

    </section>

</body>

</html>