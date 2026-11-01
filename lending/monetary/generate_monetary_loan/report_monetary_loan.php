<?php

/* =========================================================
   MONETARY LOAN REPORT
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

error_reporting(E_ALL);
ini_set("display_errors", "0");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: same-origin");


/* =========================================================
   HELPERS
========================================================= */

function monetaryLoanReportEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function monetaryLoanReportMoney($value): string
{
    return "₱" . number_format(
        (float) $value,
        2
    );
}

function monetaryLoanReportDate(
    $date,
    string $format = "m-d-Y"
): string {
    if (
        empty($date) ||
        $date === "0000-00-00" ||
        $date === "0000-00-00 00:00:00"
    ) {
        return "—";
    }

    $timestamp = strtotime((string) $date);

    if ($timestamp === false) {
        return "—";
    }

    return date($format, $timestamp);
}

function monetaryLoanReportValidDate(
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

function monetaryLoanReportDaysBetween(
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

function monetaryLoanReportBindParameters(
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

function monetaryLoanReportFail(
    string $message,
    int $statusCode = 400
): void {
    http_response_code($statusCode);

    $safeMessage = monetaryLoanReportEscape(
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
            padding: 24px;

            display: grid;
            place-items: center;

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

            box-shadow:
                0 18px 45px
                rgba(0, 0, 0, 0.16);
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

        <h1>
            Unable to generate report
        </h1>

        <p>
            {$safeMessage}
        </p>

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


/* =========================================================
   SESSION VALIDATION
========================================================= */

$userID =
    isset(
        $_SESSION[
            "user_id"
        ]
    )
    ?
    (int) $_SESSION[
        "user_id"
    ]
    :
    0;


if (
    $userID <= 0
) {

    monetaryLoanReportFail(
        "Your session has expired. Please log in again.",
        401
    );

}


if (
    (int) (
        $_SESSION[
            "lending_access"
        ]
        ??
        0
    ) !== 1
) {

    monetaryLoanReportFail(
        "You do not have permission to access Lending reports.",
        403
    );

}


/* =========================================================
   DATABASE
========================================================= */

require_once "../../../db.php";


if (
    !isset(
        $conn
    )
    ||
    !(
        $conn instanceof mysqli
    )
) {

    monetaryLoanReportFail(
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


/* =========================================================
   CURRENT USER
========================================================= */

$userStatement =
    mysqli_prepare(
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


if (
    !$userStatement
) {

    monetaryLoanReportFail(
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


$userResult =
    mysqli_stmt_get_result(
        $userStatement
    );


$currentUser =
    $userResult
    ?
    mysqli_fetch_assoc(
        $userResult
    )
    :
    null;


mysqli_stmt_close(
    $userStatement
);


if (
    !$currentUser
) {

    monetaryLoanReportFail(
        "The logged-in account could not be found.",
        401
    );

}


$preparedBy =
    trim(
        (string) (
            $currentUser[
                "name"
            ]
            ??
            ""
        )
    );


if (
    $preparedBy === ""
) {

    $preparedBy =
        "Unknown User";

}


$preparedByRole =
    ucwords(
        str_replace(
            "_",
            " ",
            trim(
                (string) (
                    $currentUser[
                        "role"
                    ]
                    ??
                    ""
                )
            )
        )
    );


if (
    $preparedByRole === ""
) {

    $preparedByRole =
        "User";

}


/* =========================================================
   FILTERS
========================================================= */

$loanType =
    trim(
        (string) (
            $_GET[
                "loan_type"
            ]
            ??
            "all"
        )
    );

$interest =
    trim(
        (string) (
            $_GET[
                "interest"
            ]
            ??
            "all"
        )
    );


$status =
    trim(
        (string) (
            $_GET[
                "status"
            ]
            ??
            "all"
        )
    );


$remarksFilter =
    trim(
        (string) (
            $_GET[
                "remarks"
            ]
            ??
            "all"
        )
    );


$dateOption =
    trim(
        (string) (
            $_GET[
                "date_option"
            ]
            ??
            "all"
        )
    );


$dateFrom =
    trim(
        (string) (
            $_GET[
                "from"
            ]
            ??
            ""
        )
    );


$dateTo =
    trim(
        (string) (
            $_GET[
                "to"
            ]
            ??
            ""
        )
    );

$allowedLoanTypes = [

    "all",
    "Crop Production Loan",
    "Dairy Farmers Loan",
    "Emergency Loan",
    "Long-Term Loan"

];

$allowedInterest = [

    "all",
    "10",
    "20"

];


$allowedStatus = [

    "all",
    "Pending",
    "Accepted",
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
        $loanType,
        $allowedLoanTypes,
        true
    )
    ||
    !in_array(
        $interest,
        $allowedInterest,
        true
    )
    ||
    !in_array(
        $status,
        $allowedStatus,
        true
    )
    ||
    !in_array(
        $remarksFilter,
        $allowedRemarks,
        true
    )
    ||
    !in_array(
        $dateOption,
        $allowedDateOptions,
        true
    )
) {

    monetaryLoanReportFail(
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
    monetaryLoanReportFail(
        "Remarks are not applicable to Pending or Rejected Monetary Loans.",
        422
    );
}


/* =========================================================
   ASIA/MANILA DATE
========================================================= */

$manilaTimezone =
    new DateTimeZone(
        "Asia/Manila"
    );


$now =
    new DateTimeImmutable(
        "now",
        $manilaTimezone
    );


$today =
    new DateTimeImmutable(
        $now->format(
            "Y-m-d"
        ),
        $manilaTimezone
    );


if (
    $dateOption ===
    "custom"
) {

    if (
        !monetaryLoanReportValidDate(
            $dateFrom,
            $manilaTimezone
        )
        ||
        !monetaryLoanReportValidDate(
            $dateTo,
            $manilaTimezone
        )
    ) {

        monetaryLoanReportFail(
            "Please provide a valid From and To date.",
            422
        );

    }


    if (
        $dateFrom >
        $dateTo
    ) {

        monetaryLoanReportFail(
            "The From date must not be later than the To date.",
            422
        );

    }


    if (
        $dateFrom >
        $today->format(
            "Y-m-d"
        )
        ||
        $dateTo >
        $today->format(
            "Y-m-d"
        )
    ) {

        monetaryLoanReportFail(
            "Application dates cannot be later than the current Asia/Manila server date.",
            422
        );

    }

}


/* =========================================================
   MONETARY LOAN QUERY
========================================================= */

$sql = "
    SELECT
        loan.monetary_loan_id,
        loan.application_date,
        loan.loan_purpose,
        loan.loan_term_years,
        loan.interest_rate,
        loan.term_days,
        loan.principal_amount,
        loan.scheduled_interest_amount,
        loan.scheduled_total_amount,
        loan.outstanding_principal_amount,
        loan.unpaid_interest_amount,
        loan.net_payments_amount,
        loan.status,
        loan.release_date,
        loan.due_date,
        loan.last_interest_date,
        loan.fully_paid_at,

        member.member_code,
        member.full_name,

        approver.name AS approved_by_name

    FROM monetary_loans AS loan

    INNER JOIN members AS member
        ON member.member_id =
           loan.member_id

    LEFT JOIN users AS approver
        ON approver.user_id =
           loan.approved_by

    WHERE loan.is_archived = 0
";


$parameterTypes =
    "";


$parameters =
    [];

/* =========================================================
   LOAN TYPE FILTER
========================================================= */

if (
    $loanType !==
    "all"
) {

    $sql .= "
        AND loan.loan_purpose = ?
    ";


    $parameterTypes .=
        "s";


    $parameters[] =
        $loanType;

}

/* =========================================================
   INTEREST FILTER
========================================================= */

if (
    $interest !==
    "all"
) {

    $sql .= "
        AND loan.interest_rate = ?
    ";


    $parameterTypes .=
        "d";


    $parameters[] =
        (float) $interest;

}


/* =========================================================
   STATUS FILTER
========================================================= */

if (
    $status !==
    "all"
) {

    $sql .= "
        AND loan.status = ?
    ";


    $parameterTypes .=
        "s";


    $parameters[] =
        $status;

}


/* =========================================================
   DATE FILTER
========================================================= */

if (
    $dateOption ===
    "custom"
) {

    $sql .= "
        AND DATE(
            loan.application_date
        ) BETWEEN ? AND ?
    ";


    $parameterTypes .=
        "ss";


    $parameters[] =
        $dateFrom;


    $parameters[] =
        $dateTo;

}


/* =========================================================
   ORDER
========================================================= */

$sql .= "
    ORDER BY
        loan.application_date ASC,
        loan.monetary_loan_id ASC
";


$loanStatement =
    mysqli_prepare(
        $conn,
        $sql
    );


if (
    !$loanStatement
) {

    monetaryLoanReportFail(
        "Unable to prepare the Monetary Loan report.",
        500
    );

}


if (
    !monetaryLoanReportBindParameters(
        $loanStatement,
        $parameterTypes,
        $parameters
    )
) {

    mysqli_stmt_close(
        $loanStatement
    );


    monetaryLoanReportFail(
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


    monetaryLoanReportFail(
        "Unable to generate the Monetary Loan report.",
        500
    );

}


$loanResult =
    mysqli_stmt_get_result(
        $loanStatement
    );


if (
    $loanResult === false
) {

    mysqli_stmt_close(
        $loanStatement
    );


    monetaryLoanReportFail(
        "Unable to read the generated Monetary Loan report.",
        500
    );

}


/* =========================================================
   REPORT DATA AND SUMMARY
========================================================= */

$reportRows =
    [];


$pendingCount =
    0;


$acceptedCount =
    0;


$rejectedCount =
    0;


$totalAcceptedPrincipal =
    0.00;


$totalScheduledInterest =
    0.00;


$totalScheduledAmount =
    0.00;


$totalPayments =
    0.00;


$totalCurrentReceivables =
    0.00;


while (
    $loan =
        mysqli_fetch_assoc(
            $loanResult
        )
) {

    $loanStatus =
        trim(
            (string) $loan[
                "status"
            ]
        );


    $outstandingPrincipal =
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


    $newAccruedInterest =
        0.00;


    $currentBalance =
        null;


    $computedRemarks =
        "—";


    /* =====================================================
       ACCEPTED LOAN LIVE BALANCE
    ===================================================== */

    if (
        $loanStatus ===
        "Accepted"
    ) {

        $calculationFromDate =
            !empty(
                $loan[
                    "last_interest_date"
                ]
            )
            ?
            $loan[
                "last_interest_date"
            ]
            :
            $loan[
                "release_date"
            ];


        $elapsedDays =
            monetaryLoanReportDaysBetween(
                $calculationFromDate,
                $today,
                $manilaTimezone
            );


        $termDays =
            max(
                1,
                (int) $loan[
                    "term_days"
                ]
            );


        if (
            $outstandingPrincipal > 0
            &&
            $elapsedDays > 0
        ) {

            $newAccruedInterest =
                round(
                    $outstandingPrincipal
                    *
                    (
                        (float) $loan[
                            "interest_rate"
                        ]
                        /
                        100
                    )
                    *
                    $elapsedDays
                    /
                    $termDays,
                    2
                );

        }


        $currentBalance =
            round(
                $outstandingPrincipal
                +
                $unpaidInterest
                +
                $newAccruedInterest,
                2
            );


        $isPaid =
            $outstandingPrincipal <= 0.009
            &&
            $unpaidInterest <= 0.009
            &&
            $newAccruedInterest <= 0.009
            &&
            !empty(
                $loan[
                    "fully_paid_at"
                ]
            );


        if (
            $isPaid
        ) {

            $computedRemarks =
                "Paid";


            $currentBalance =
                0.00;

        } else {

            $dueDateObject =
                null;


            if (
                !empty(
                    $loan[
                        "due_date"
                    ]
                )
            ) {

                try {

                    $dueDateObject =
                        new DateTimeImmutable(
                            $loan[
                                "due_date"
                            ],
                            $manilaTimezone
                        );

                } catch (
                    Throwable $exception
                ) {

                    $dueDateObject =
                        null;

                }

            }


            $computedRemarks =
                (
                    $dueDateObject !==
                    null
                    &&
                    $today >
                    $dueDateObject
                )
                ?
                "Overdue"
                :
                "On Going";

        }

    }


    /* =====================================================
       REMARKS FILTER
    ===================================================== */

    if (
        $remarksFilter !==
        "all"
        &&
        $computedRemarks !==
        $remarksFilter
    ) {

        continue;

    }


    $loan[
        "computed_balance"
    ] =
        $currentBalance;


    $loan[
        "computed_remarks"
    ] =
        $computedRemarks;


    $reportRows[] =
        $loan;


    /* =====================================================
       SUMMARY COUNTS
    ===================================================== */

    switch (
        $loanStatus
    ) {

        case "Pending":

            $pendingCount++;

            break;


        case "Accepted":

            $acceptedCount++;


            $totalAcceptedPrincipal +=
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
                    ??
                    0
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


$totalRecords =
    count(
        $reportRows
    );


/* =========================================================
   FILTER LABELS
========================================================= */

$loanTypeLabel =
    $loanType ===
    "all"
    ?
    "All Loan Types"
    :
    $loanType;

$interestLabel =
    $interest ===
    "all"
    ?
    "All Interest Rates"
    :
    (
        $interest ===
        "10"
        ?
        "10% - 180 Days"
        :
        "20% - 360 Days"
    );


$statusLabel =
    $status ===
    "all"
    ?
    "All Status"
    :
    $status;


$remarksLabel =
    $remarksFilter ===
    "all"
    ?
    "All Remarks"
    :
    $remarksFilter;


$dateRangeLabel =
    $dateOption ===
    "all"
    ?
    "All Applications"
    :
    (
        monetaryLoanReportDate(
            $dateFrom,
            "M d, Y"
        )
        .
        " - "
        .
        monetaryLoanReportDate(
            $dateTo,
            "M d, Y"
        )
    );

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>
        Monetary Loan Report
    </title>


    <style>

        :root {

            --report-green:
                #8ca15b;

            --report-green-dark:
                #6f8347;

            --report-text:
                #222222;

            --report-muted:
                #666666;

            --report-red:
                #c84444;

            --report-orange:
                #bb7a00;

        }


        * {

            box-sizing:
                border-box;

        }


        body {

            margin:
                40px;

            color:
                var(--report-text);

            background:
                #ffffff;

            font-family:
                Arial,
                sans-serif;

        }


        /* =====================================================
           REPORT HEADER
        ===================================================== */

        .report-header {

            text-align:
                center;

        }


        .report-logo {

            width:
                90px;

            height:
                auto;

            margin-bottom:
                8px;

        }


        .report-header h1 {

            margin:
                0 0 5px;

            font-size:
                26px;

        }


        .report-header h2 {

            margin:
                0;

            color:
                var(--report-muted);

            font-size:
                21px;

        }


        .report-header p {

            margin:
                7px 0 0;

            color:
                var(--report-muted);

            font-size:
                14px;

        }


        /* =====================================================
           REPORT ACTIONS
        ===================================================== */

        .report-actions {

            display:
                flex;

            justify-content:
                flex-start;

            gap:
                10px;

            margin:
                20px 0;

        }


        .report-actions button {

            padding:
                10px 18px;

            color:
                #ffffff;

            background:
                var(--report-green);

            border:
                none;

            border-radius:
                9px;

            font-size:
                14px;

            font-weight:
                700;

            cursor:
                pointer;

            transition:
                background-color 0.2s ease,
                transform 0.2s ease;

        }


        .report-actions button:hover {

            background:
                var(--report-green-dark);

            transform:
                translateY(-1px);

        }


        .report-actions
        .close-btn {

            background:
                var(--report-red);

        }


        .report-actions
        .close-btn:hover {

            background:
                #ad3333;

        }


        /* =====================================================
           REPORT INFORMATION
        ===================================================== */

        .report-information {

            width:
                100%;

            margin:
                20px 0;

            padding:
                0;

            display:
                grid;

            grid-template-columns:
                max-content
                max-content;

            column-gap:
                90px;

            row-gap:
                14px;

            justify-content:
                start;

        }


        .report-information p {

            margin:
                0;

            font-size:
                14px;

            line-height:
                1.4;

        }


        .report-scope-note {

            margin:
                12px 0 0;

            color:
                var(--report-muted);

            font-size:
                11px;

        }


        /* =====================================================
           REPORT TABLE
        ===================================================== */

        .report-table-wrapper {

            width:
                100%;

            margin-top:
                17px;

            overflow-x:
                auto;

        }


        table {

            width:
                100%;

            min-width:
                900px;

            margin-top:
                20px;

            border-collapse:
                collapse;

            table-layout:
                fixed;

        }


        th {

            padding:
                10px 7px;

            color:
                #ffffff;

            background:
                var(--report-green);

            border:
                1px solid
                #000000;

            font-size:
                10px;

            line-height:
                1.2;

            text-transform:
                uppercase;

        }


        td {

            padding:
                9px 7px;

            border:
                1px solid
                #000000;

            font-size:
                10px;

            line-height:
                1.35;

            text-align:
                center;

            vertical-align:
                middle;

            overflow-wrap:
                break-word;

        }


        .left-cell {

            text-align:
                left;

        }


        /* =====================================================
           PRIMARY / SECONDARY TEXT
        ===================================================== */

        .primary-text {

            display:
                block;

            font-weight:
                700;

        }


        .secondary-text {

            display:
                block;

            margin-top:
                3px;

            color:
                var(--report-muted);

            font-size:
                9px;

            line-height:
                1.3;

        }


        /* =====================================================
        LOAN TYPE
        ===================================================== */

        .purpose-text {

            display:
                block;

            margin-top:
                7px;

            padding-top:
                7px;

            border-top:
                1px solid
                #d5d5d5;

            color:
                #333333;

            font-size:
                9.5px;

            line-height:
                1.35;

        }


        /* =====================================================
           GROUPED CELL DETAILS
        ===================================================== */

        .detail-group {

            display:
                grid;

            gap:
                6px;

            text-align:
                left;

        }


        .detail-line {

            display:
                grid;

            grid-template-columns:
                minmax(
                    0,
                    1fr
                )
                max-content;

            align-items:
                baseline;

            gap:
                7px;

        }


        .detail-label {

            color:
                var(--report-muted);

            font-size:
                8.5px;

            font-weight:
                700;

        }


        .detail-value {

            color:
                var(--report-text);

            font-size:
                9.5px;

            font-weight:
                700;

            text-align:
                right;

            overflow-wrap:
                anywhere;

        }

        .term-detail-line {

            grid-template-columns:
                38px
                minmax(
                    0,
                    1fr
                );

            align-items:
                start;

        }


        .term-detail-line
        .detail-value {

            min-width:
                0;

            max-width:
                100%;

            line-height:
                1.25;

            white-space:
                normal;

            overflow-wrap:
                anywhere;

            word-break:
                normal;

        }


        /* =====================================================
           SCHEDULE / APPROVAL
        ===================================================== */

        .schedule-group
        .detail-line {

            grid-template-columns:
                64px
                minmax(
                    0,
                    1fr
                );

        }


        .schedule-group
        .detail-value {

            text-align:
                left;

        }


        /* =====================================================
           STATUS / REMARKS
        ===================================================== */

        .status-stack {

            display:
                grid;

            gap:
                8px;

            justify-items:
                center;

        }


        .status-block {

            width:
                100%;

        }


        .status-label {

            display:
                block;

            margin-bottom:
                2px;

            color:
                var(--report-muted);

            font-size:
                8px;

            font-weight:
                700;

            text-transform:
                uppercase;

        }


        .status-text,
        .remarks-text {

            font-weight:
                700;

        }


        .status-pending {

            color:
                var(--report-orange);

        }


        .status-accepted,
        .remarks-paid {

            color:
                #168354;

        }


        .status-rejected,
        .remarks-overdue {

            color:
                var(--report-red);

        }


        .remarks-ongoing {

            color:
                var(--report-orange);

        }


        /* =====================================================
           EMPTY
        ===================================================== */

        .empty-row td {

            padding:
                28px;

            color:
                var(--report-muted);

            font-size:
                13px;

            text-align:
                center;

        }


        /* =====================================================
           DIVIDERS
        ===================================================== */

        hr {

            margin:
                10px 0 0;

            border:
                0;

            border-top:
                1px solid
                #999999;

        }


        /* =====================================================
           SUMMARY
        ===================================================== */

        .summary-title {

            margin:
                18px 0 12px;

            font-size:
                22px;

            font-weight:
                700;

        }


        .summary-grid {

            display:
                grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(
                        320px,
                        380px
                    )
                );

            column-gap:
                70px;

            row-gap:
                12px;

            justify-content:
                start;

        }


        .summary-item {

            display:
                grid;

            grid-template-columns:
                minmax(
                    170px,
                    1fr
                )
                max-content;

            align-items:
                baseline;

            column-gap:
                12px;

        }


        .summary-item span {

            min-width:
                0;

            font-size:
                14px;

            font-weight:
                700;

        }


        .summary-item
        span::after {

            content:
                ":";

        }


        .summary-item strong {

            margin:
                0;

            font-size:
                14px;

            font-weight:
                700;

            white-space:
                nowrap;

            text-align:
                right;

            font-variant-numeric:
                tabular-nums;

        }


        .calculation-note {

            margin:
                14px 0 0;

            color:
                var(--report-muted);

            font-size:
                10px;

            line-height:
                1.5;

        }


        /* =====================================================
           PREPARED BY
        ===================================================== */

        .prepared-by {

            margin-top:
                60px;

            text-align:
                right;

            break-inside:
                avoid;

        }


        .prepared-by p {

            margin:
                0;

        }


        .signature-space {

            height:
                40px;

        }


        .prepared-by strong,
        .prepared-by span {

            display:
                inline-block;

            min-width:
                190px;

            padding:
                0;

            border:
                none;

            text-align:
                center;

        }


        .prepared-by span {

            margin-top:
                3px;

            color:
                var(--report-muted);

            font-size:
                12px;

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


            .report-table-wrapper {

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
                    76px;

            }


            .report-header h1 {

                font-size:
                    22px;

            }


            .report-header h2 {

                font-size:
                    18px;

            }


            .report-header p {

                font-size:
                    11.5px;

            }


            .report-information {

                margin:
                    14px 0;

                grid-template-columns:
                    max-content
                    max-content;

                column-gap:
                    38px;

                row-gap:
                    8px;

            }


            .report-information p {

                font-size:
                    10.5px;

                line-height:
                    1.35;

            }


            .report-scope-note {

                margin-top:
                    8px;

                font-size:
                    9px;

                line-height:
                    1.35;

            }


            .report-table-wrapper {

                margin-top:
                    12px;

            }


            table {

                margin-top:
                    12px;

            }


            th {

                padding:
                    7px 4px;

                font-size:
                    8.5px;

            }


            td {

                padding:
                    7px 5px;

                font-size:
                    9.3px;

                line-height:
                    1.3;

            }


            .primary-text {

                font-size:
                    9.7px;

            }


            .secondary-text {

                font-size:
                    8.3px;

            }


            .purpose-text {

                margin-top:
                    6px;

                padding-top:
                    6px;

                font-size:
                    8.7px;

            }


            .detail-group {

                gap:
                    5px;

            }


            .detail-line {

                gap:
                    5px;

            }


            .detail-label {

                font-size:
                    7.6px;

            }


            .detail-value {

                font-size:
                    8.7px;

            }


            .schedule-group
            .detail-line {

                grid-template-columns:
                    53px
                    minmax(
                        0,
                        1fr
                    );

            }


            .status-label {

                font-size:
                    7.3px;

            }


            .status-text,
            .remarks-text {

                font-size:
                    8.7px;

            }


            .summary-title {

                margin:
                    14px 0 9px;

                font-size:
                    17px;

            }


            .summary-grid {

                grid-template-columns:
                    repeat(
                        2,
                        minmax(
                            0,
                            1fr
                        )
                    );

                column-gap:
                    26px;

                row-gap:
                    8px;

            }


            .summary-item {

                grid-template-columns:
                    minmax(
                        0,
                        1fr
                    )
                    max-content;

                column-gap:
                    8px;

            }


            .summary-item span,
            .summary-item strong {

                font-size:
                    10.5px;

            }


            .calculation-note {

                margin-top:
                    10px;

                font-size:
                    8.5px;

                line-height:
                    1.4;

            }


            .prepared-by {

                margin-top:
                    38px;

            }


            .signature-space {

                height:
                    30px;

            }


            .prepared-by strong,
            .prepared-by span {

                min-width:
                    165px;

            }


            .prepared-by strong {

                font-size:
                    10.5px;

            }


            .prepared-by span {

                font-size:
                    9.5px;

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
                    9px 7px;

                font-size:
                    10px;

            }


            .primary-text {

                font-size:
                    10.5px;

            }


            .secondary-text {

                font-size:
                    9px;

            }


            .purpose-text {

                font-size:
                    9.5px;

            }


            .detail-label {

                font-size:
                    8.5px;

            }


            .detail-value {

                font-size:
                    9.5px;

            }


            .status-text,
            .remarks-text {

                font-size:
                    9.5px;

            }

        }


        /* =====================================================
           SMALL SCREEN
        ===================================================== */

        @media (
            max-width:
            700px
        ) {

            body {

                margin:
                    18px;

            }


            .report-information,
            .summary-grid {

                grid-template-columns:
                    1fr;

                row-gap:
                    12px;

            }

        }

    </style>

</head>


<body>


    <!-- =========================================================
         REPORT HEADER
    ========================================================= -->

    <header class="report-header">

        <img
        src="../../../assets/logo/triangle-logo.png"
        alt="SIPBU-MPC logo"
        class="report-logo">


        <h1>
            SIPBU-MPC PORAIS INTEGRATED SYSTEM
        </h1>


        <h2>
            Monetary Loan Report
        </h2>


        <p>
            Lending Management System
        </p>

    </header>


    <!-- =========================================================
         REPORT ACTIONS
    ========================================================= -->

    <section class="report-actions">

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

    </section>


    <!-- =========================================================
         REPORT INFORMATION
    ========================================================= -->

    <section class="report-information">


        <p>

            <strong>
                Date Generated:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $now->format(
                    "F d, Y"
                )
            );

            ?>

        </p>


        <p>

            <strong>
                Time Generated:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $now->format(
                    "h:i A"
                )
            );

            ?>

        </p>

        <p>

            <strong>
                Monetary Loan Type:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $loanTypeLabel
            );

            ?>

        </p>


        <p>

            <strong>
                Interest Rate:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $interestLabel
            );

            ?>

        </p>


        <p>

            <strong>
                Status:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $statusLabel
            );

            ?>

        </p>


        <p>

            <strong>
                Remarks:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $remarksLabel
            );

            ?>

        </p>


        <p>

            <strong>
                Application Date:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $dateRangeLabel
            );

            ?>

        </p>


        <p>

            <strong>
                Record Scope:
            </strong>

            <br>

            Current Monetary Loan Ledger

        </p>


        <p>

            <strong>
                Balance As Of:
            </strong>

            <br>

            <?php

            echo monetaryLoanReportEscape(
                $today->format(
                    "F d, Y"
                )
            );

            ?>

        </p>


    </section>


    <p class="report-scope-note">

        Archived Monetary Loans are excluded because this
        report represents the current Monetary Loan ledger.

    </p>


    <!-- =========================================================
         MONETARY LOAN TABLE
    ========================================================= -->

    <div class="report-table-wrapper">

        <table>


            <colgroup>

                <col style="width: 13%;">

                <col style="width: 22%;">

                <col style="width: 16%;">

                <col style="width: 18%;">

                <col style="width: 19%;">

                <col style="width: 12%;">

            </colgroup>


            <thead>

                <tr>

                    <th>
                        Loan
                    </th>

                    <th>
                        Member / Loan Type
                    </th>

                    <th>
                        Loan Details
                    </th>

                    <th>
                        Financials
                    </th>

                    <th>
                        Schedule / Approval
                    </th>

                    <th>
                        Status / Remarks
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php

            if (
                $totalRecords === 0
            ) {

            ?>


                <tr class="empty-row">

                    <td colspan="6">

                        No Monetary Loan records matched
                        the selected report filters.

                    </td>

                </tr>


            <?php

            } else {

            ?>


                <?php

                foreach (
                    $reportRows
                    as $loan
                ) {

                ?>


                    <?php


                    $loanDisplayID =

                        "ML-" .

                        str_pad(
                            (string) $loan[
                                "monetary_loan_id"
                            ],
                            6,
                            "0",
                            STR_PAD_LEFT
                        );


                    $statusClass =

                        "status-" .

                        strtolower(
                            (string) $loan[
                                "status"
                            ]
                        );


                    $remarksClass =

                        $loan[
                            "computed_remarks"
                        ] === "—"

                        ?

                        ""

                        :

                        (
                            "remarks-"
                            .
                            strtolower(
                                str_replace(
                                    " ",
                                    "",
                                    $loan[
                                        "computed_remarks"
                                    ]
                                )
                            )
                        );


                    $approvedByName =

                        trim(
                            (string) (
                                $loan[
                                    "approved_by_name"
                                ]
                                ??
                                ""
                            )
                        );


                    ?>


                    <tr>


                        <!-- =====================================
                             LOAN
                        ====================================== -->

                        <td>


                            <span class="primary-text">

                                <?php

                                echo monetaryLoanReportEscape(
                                    $loanDisplayID
                                );

                                ?>

                            </span>


                            <span class="secondary-text">

                                Applied:

                                <?php

                                echo monetaryLoanReportDate(
                                    $loan[
                                        "application_date"
                                    ]
                                );

                                ?>

                            </span>


                        </td>


                        <!-- =====================================
                            MEMBER / LOAN TYPE
                        ====================================== -->

                        <td class="left-cell">


                            <span class="primary-text">

                                <?php

                                echo monetaryLoanReportEscape(
                                    $loan[
                                        "full_name"
                                    ]
                                );

                                ?>

                            </span>


                            <span class="secondary-text">

                                <?php

                                echo monetaryLoanReportEscape(
                                    $loan[
                                        "member_code"
                                    ]
                                );

                                ?>

                            </span>


                            <span class="purpose-text">

                                <strong>
                                    Loan Type:
                                </strong>

                                <?php

                                echo monetaryLoanReportEscape(
                                    $loan[
                                        "loan_purpose"
                                    ]
                                );

                                ?>

                            </span>


                        </td>


                        <!-- =====================================
                             LOAN DETAILS
                        ====================================== -->

                        <td>


                            <div class="detail-group">


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Principal
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo monetaryLoanReportMoney(
                                            $loan[
                                                "principal_amount"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Interest Rate
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo number_format(
                                            (float) $loan[
                                                "interest_rate"
                                            ],
                                            0
                                        );

                                        ?>%

                                    </span>

                                </div>


                                <div class="detail-line term-detail-line">

                                    <span class="detail-label">
                                        Term
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        $isLongTermLoan =
                                            trim(
                                                (string) (
                                                    $loan[
                                                        "loan_purpose"
                                                    ]
                                                    ??
                                                    ""
                                                )
                                            ) ===
                                            "Long-Term Loan";

                                        $loanTermYears =
                                            isset(
                                                $loan[
                                                    "loan_term_years"
                                                ]
                                            ) &&
                                            $loan[
                                                "loan_term_years"
                                            ] !== null
                                                ? (int) $loan[
                                                    "loan_term_years"
                                                ]
                                                : 0;

                                        $termDays =
                                            (int) $loan[
                                                "term_days"
                                            ];

                                        $paymentPeriods =
                                            $isLongTermLoan &&
                                            $loanTermYears > 0
                                                ? $loanTermYears *
                                                (
                                                    $termDays === 180
                                                        ? 2
                                                        : 1
                                                )
                                                : 0;

                                        if (
                                            $isLongTermLoan &&
                                            $loanTermYears > 0 &&
                                            $paymentPeriods > 0
                                        ) {
                                            echo monetaryLoanReportEscape(
                                                $loanTermYears .
                                                (
                                                    $loanTermYears === 1
                                                        ? " Year"
                                                        : " Years"
                                                ) .
                                                " · " .
                                                $paymentPeriods .
                                                (
                                                    $paymentPeriods === 1
                                                        ? " Payment"
                                                        : " Payments"
                                                ) .
                                                " · Every " .
                                                number_format(
                                                    $termDays
                                                ) .
                                                " Days"
                                            );
                                        } else {
                                            echo monetaryLoanReportEscape(
                                                number_format(
                                                    $termDays
                                                ) .
                                                " Days"
                                            );
                                        }

                                        ?>

                                    </span>

                                </div>


                            </div>


                        </td>


                        <!-- =====================================
                             FINANCIALS
                        ====================================== -->

                        <td>


                            <div class="detail-group">


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Payoff Total
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo monetaryLoanReportMoney(
                                            $loan[
                                                "scheduled_total_amount"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Payments
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo monetaryLoanReportMoney(
                                            $loan[
                                                "net_payments_amount"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Outstanding Balance
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo $loan[
                                            "computed_balance"
                                        ] === null

                                            ?

                                            "—"

                                            :

                                            monetaryLoanReportMoney(
                                                $loan[
                                                    "computed_balance"
                                                ]
                                            );

                                        ?>

                                    </span>

                                </div>


                            </div>


                        </td>


                        <!-- =====================================
                             SCHEDULE / APPROVAL
                        ====================================== -->

                        <td>


                            <div
                            class="
                                detail-group
                                schedule-group
                            ">


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Accepted
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo monetaryLoanReportDate(
                                            $loan[
                                                "release_date"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Due
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo monetaryLoanReportDate(
                                            $loan[
                                                "due_date"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="detail-line">

                                    <span class="detail-label">
                                        Approved By
                                    </span>


                                    <span class="detail-value">

                                        <?php

                                        echo $approvedByName === ""

                                            ?

                                            "—"

                                            :

                                            monetaryLoanReportEscape(
                                                $approvedByName
                                            );

                                        ?>

                                    </span>

                                </div>


                            </div>


                        </td>


                        <!-- =====================================
                             STATUS / REMARKS
                        ====================================== -->

                        <td>


                            <div class="status-stack">


                                <div class="status-block">

                                    <span class="status-label">
                                        Status
                                    </span>


                                    <span
                                    class="
                                        status-text
                                        <?php
                                        echo monetaryLoanReportEscape(
                                            $statusClass
                                        );
                                        ?>
                                    ">

                                        <?php

                                        echo monetaryLoanReportEscape(
                                            $loan[
                                                "status"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                                <div class="status-block">

                                    <span class="status-label">
                                        Remarks
                                    </span>


                                    <span
                                    class="
                                        remarks-text
                                        <?php
                                        echo monetaryLoanReportEscape(
                                            $remarksClass
                                        );
                                        ?>
                                    ">

                                        <?php

                                        echo monetaryLoanReportEscape(
                                            $loan[
                                                "computed_remarks"
                                            ]
                                        );

                                        ?>

                                    </span>

                                </div>


                            </div>


                        </td>


                    </tr>


                <?php

                }

                ?>


            <?php

            }

            ?>


            </tbody>


        </table>


    </div>


    <!-- =========================================================
         SUMMARY
    ========================================================= -->

    <hr>

    <hr>


    <h3 class="summary-title">

        SUMMARY REPORT

    </h3>


    <section class="summary-grid">


        <div class="summary-item">

            <span>
                Total Records
            </span>

            <strong>

                <?php

                echo number_format(
                    $totalRecords
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Pending
            </span>

            <strong>

                <?php

                echo number_format(
                    $pendingCount
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Accepted
            </span>

            <strong>

                <?php

                echo number_format(
                    $acceptedCount
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Rejected
            </span>

            <strong>

                <?php

                echo number_format(
                    $rejectedCount
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Accepted Principal
            </span>

            <strong>

                <?php

                echo monetaryLoanReportMoney(
                    $totalAcceptedPrincipal
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Interest Payable
            </span>

            <strong>

                <?php

                echo monetaryLoanReportMoney(
                    $totalScheduledInterest
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Payoff Total
            </span>

            <strong>

                <?php

                echo monetaryLoanReportMoney(
                    $totalScheduledAmount
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Net Payments
            </span>

            <strong>

                <?php

                echo monetaryLoanReportMoney(
                    $totalPayments
                );

                ?>

            </strong>

        </div>


        <div class="summary-item">

            <span>
                Current Receivables
            </span>

            <strong>

                <?php

                echo monetaryLoanReportMoney(
                    $totalCurrentReceivables
                );

                ?>

            </strong>

        </div>


    </section>


    <p class="calculation-note">

        Financial totals include Accepted Monetary Loans only.
        Current receivables include unpaid principal, unpaid
        interest, and prorated interest accrued as of the
        Asia/Manila server date.

    </p>


    <!-- =========================================================
         PREPARED BY
    ========================================================= -->

    <hr>

    <hr>


    <section class="prepared-by">


        <p>

            <strong>
                Prepared by:
            </strong>

        </p>


        <div class="signature-space"></div>


        <strong>

            <?php

            echo monetaryLoanReportEscape(
                $preparedBy
            );

            ?>

        </strong>


        <br>


        <span>

            <?php

            echo monetaryLoanReportEscape(
                $preparedByRole
            );

            ?>

        </span>


    </section>


</body>

</html>