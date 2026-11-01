<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}


date_default_timezone_set(
    'Asia/Manila'
);


error_reporting(E_ALL);

ini_set(
    'display_errors',
    '0'
);


require_once '../../db.php';


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   VERIFY LIVE RENTAL ACCESS
========================================================= */

$userID =
isset($_SESSION['user_id'])
?
(int)$_SESSION['user_id']
:
0;


if(
    $userID <= 0
){

    http_response_code(401);

    exit(
        'Your session has expired. Please sign in again.'
    );
}


$userStmt =
mysqli_prepare(
    $conn,
    "
    SELECT
        user_id

    FROM users

    WHERE user_id = ?

    AND archived = 0

    AND status = 'Active'

    AND rental_access = 1

    LIMIT 1
    "
);


if(!$userStmt){

    http_response_code(500);

    exit(
        'Unable to verify Rental access.'
    );
}


mysqli_stmt_bind_param(
    $userStmt,
    'i',
    $userID
);


if(
    !mysqli_stmt_execute(
        $userStmt
    )
){

    mysqli_stmt_close(
        $userStmt
    );

    http_response_code(500);

    exit(
        'Unable to verify Rental access.'
    );
}


mysqli_stmt_store_result(
    $userStmt
);


$hasRentalAccess =
mysqli_stmt_num_rows(
    $userStmt
)
===
1;


mysqli_stmt_close(
    $userStmt
);


if(!$hasRentalAccess){

    http_response_code(403);

    exit(
        'You do not have permission to access Rental reports.'
    );
}


/* =========================================================
   HELPERS
========================================================= */

function reaperReportEscape(
    $value
): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );

}


function reaperReportValidDate(
    string $date
): bool {

    $dateObject =
    DateTime::createFromFormat(
        "Y-m-d",
        $date
    );


    return
        $dateObject
        &&
        $dateObject->format(
            "Y-m-d"
        ) === $date;

}


function reaperReportFormatNumber(
    $value
): string {

    if(
        !is_numeric(
            $value
        )
    ){

        return "0";

    }


    $number =
    (float)$value;


    if(
        floor(
            $number
        ) ===
        $number
    ){

        return number_format(
            $number,
            0
        );

    }


    return
    rtrim(
        rtrim(
            number_format(
                $number,
                2,
                ".",
                ","
            ),
            "0"
        ),
        "."
    );

}


/* =========================================================
   PREPARED BY
========================================================= */

$preparedBy =
trim(
    (string)(
        $_SESSION["name"]
        ??
        ""
    )
);


$role =
ucwords(
    trim(
        (string)(
            $_SESSION["role"]
            ??
            ""
        )
    )
);


/* =========================================================
   REPORT FILTERS
========================================================= */

$affiliate =
$_GET["affiliate"]
??
"all";


$status =
$_GET["status"]
??
"all";


$remarks =
$_GET["remarks"]
??
"all";


$dateOption =
$_GET["dateOption"]
??
"all";


$from =
$_GET["from"]
??
"";


$to =
$_GET["to"]
??
"";


/* =========================================================
   ALLOWED FILTER VALUES
========================================================= */

$allowedAffiliates =
[
    "all",
    "Member",
    "Not Member"
];


$allowedStatuses =
[
    "all",
    "Pending",
    "Ongoing",
    "Done",
    "Maintenance"
];


$allowedRemarks =
[
    "all",
    "Paid",
    "Unpaid"
];


$allowedDateOptions =
[
    "all",
    "custom"
];


if(
    !in_array(
        $affiliate,
        $allowedAffiliates,
        true
    )
){

    $affiliate =
    "all";

}


if(
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
){

    $status =
    "all";

}


if(
    !in_array(
        $remarks,
        $allowedRemarks,
        true
    )
){

    $remarks =
    "all";

}


if(
    !in_array(
        $dateOption,
        $allowedDateOptions,
        true
    )
){

    $dateOption =
    "all";

}


/* =========================================================
   PAYMENT COMBINATION GUARD

   Pending / Ongoing / Maintenance
   are always Unpaid.
========================================================= */

if(
    in_array(
        $status,
        [
            "Pending",
            "Ongoing",
            "Maintenance"
        ],
        true
    )
){

    $remarks =
    "Unpaid";

}


/* =========================================================
   DATE VALIDATION
========================================================= */

if(
    $dateOption ===
    "custom"
){

    if(
        !reaperReportValidDate(
            $from
        )
        ||
        !reaperReportValidDate(
            $to
        )
        ||
        $from > $to
    ){

        http_response_code(400);

        header(
            "Content-Type: text/plain; charset=UTF-8"
        );

        exit(
            "Invalid custom date range. Please select valid From and To dates, with From not later than To."
        );

    }

}


/* =========================================================
   EFFECTIVE REMARKS

   Pending / Ongoing can never appear as Paid.
========================================================= */

$effectiveRemarksSql = "
    CASE

        WHEN
            r.status IN (
                'Pending',
                'Ongoing'
            )

        THEN 'Unpaid'


        WHEN
            TRIM(
                COALESCE(
                    r.remarks,
                    ''
                )
            ) = ''

        THEN 'Unpaid'


        ELSE r.remarks

    END
";


/* =========================================================
   REPORT QUERY
========================================================= */

$sql = "
    SELECT

        r.*,

        $effectiveRemarksSql
            AS effective_remarks,


        COALESCE(
            NULLIF(
                TRIM(
                    t.tractor_no
                ),
                ''
            ),
            NULLIF(
                TRIM(
                    r.tractor_no
                ),
                ''
            )
        )
            AS report_primary_tractor_no,


        NULLIF(
            TRIM(
                t.tractor_name
            ),
            ''
        )
            AS report_primary_tractor_name,


        COALESCE(
            NULLIF(
                TRIM(
                    st.tractor_no
                ),
                ''
            ),
            NULLIF(
                TRIM(
                    r.secondary_tractor_no
                ),
                ''
            )
        )
            AS report_secondary_tractor_no,


        NULLIF(
            TRIM(
                st.tractor_name
            ),
            ''
        )
            AS report_secondary_tractor_name,


        CASE

            WHEN
                (
                    t.status = 'Maintenance'

                    OR

                    st.status = 'Maintenance'
                )

                AND

                r.status IN (
                    'Pending',
                    'Ongoing'
                )

            THEN 1

            ELSE 0

        END
            AS maintenance_affected


    FROM reaper r


    LEFT JOIN tractors t

        ON t.tractor_id =
        r.tractor_id


    LEFT JOIN tractors st

        ON st.tractor_id =
        r.secondary_tractor_id


    WHERE 1=1
";


/* =========================================================
   AFFILIATE FILTER
========================================================= */

if(
    $affiliate !==
    "all"
){

    $safeAffiliate =
    mysqli_real_escape_string(
        $conn,
        $affiliate
    );


    $sql .= "
        AND r.affiliate =
        '$safeAffiliate'
    ";

}


/* =========================================================
   STATUS FILTER
========================================================= */

if(
    $status ===
    "Maintenance"
){

    $sql .= "
        AND
        (
            t.status = 'Maintenance'

            OR

            st.status = 'Maintenance'
        )

        AND r.status IN (
            'Pending',
            'Ongoing'
        )
    ";

}
elseif(
    $status !==
    "all"
){

    $safeStatus =
    mysqli_real_escape_string(
        $conn,
        $status
    );


    $sql .= "
        AND r.status =
        '$safeStatus'
    ";

}


/* =========================================================
   REMARKS FILTER
========================================================= */

if(
    $remarks !==
    "all"
){

    $safeRemarks =
    mysqli_real_escape_string(
        $conn,
        $remarks
    );


    $sql .= "
        AND (
            $effectiveRemarksSql
        ) =
        '$safeRemarks'
    ";

}


/* =========================================================
   DATE FILTER
========================================================= */

if(
    $dateOption ===
    "custom"
){

    $safeFrom =
    mysqli_real_escape_string(
        $conn,
        $from
    );


    $safeTo =
    mysqli_real_escape_string(
        $conn,
        $to
    );


    $sql .= "
        AND DATE(
            r.rental_date
        )
        BETWEEN
            '$safeFrom'
        AND
            '$safeTo'
    ";

}


/* =========================================================
   ORDER

   Transaction / record sequence:
   lowest ID -> highest ID
========================================================= */

$sql .= "
    ORDER BY
        r.reaper_id ASC
";


/* =========================================================
   FETCH REPORT DATA
========================================================= */

$query =
mysqli_query(
    $conn,
    $sql
);


$reportRows =
[];


$totalHarvest =
0;


$totalCommodity =
0;


$reportLoadError =
false;


if(
    $query
){

    while(
        $row =
        mysqli_fetch_assoc(
            $query
        )
    ){

        $reportRows[] =
        $row;


        if(
            is_numeric(
                $row["no_of_harvest"]
                ??
                null
            )
        ){

            $totalHarvest +=
            (float)$row["no_of_harvest"];

        }


        if(
            is_numeric(
                $row["commodity"]
                ??
                null
            )
        ){

            $totalCommodity +=
            (float)$row["commodity"];

        }

    }

}
else{

    $reportLoadError =
    true;


    error_log(
        "Reaper Report SQL Error: "
        .
        mysqli_error(
            $conn
        )
    );

}


$totalTransactions =
count(
    $reportRows
);


/* =========================================================
   DISPLAY LABELS
========================================================= */

$affiliateLabel =
$affiliate ===
"all"
?
"All Affiliates"
:
$affiliate;


$statusLabel =
$status ===
"all"
?
"All Status"
:
$status;


$remarksLabel =
$remarks ===
"all"
?
"All Remarks"
:
$remarks;


$dateRangeLabel =
"All Transactions";


if(
    $dateOption ===
    "custom"
){

    $fromTimestamp =
    strtotime(
        $from
    );


    $toTimestamp =
    strtotime(
        $to
    );


    if(
        $fromTimestamp !==
        false
        &&
        $toTimestamp !==
        false
    ){

        $dateRangeLabel =
        date(
            "M d, Y",
            $fromTimestamp
        )
        .
        " - "
        .
        date(
            "M d, Y",
            $toTimestamp
        );

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
    Reaper Report
</title>


<style>

/* =========================================================
   DOCUMENT
========================================================= */

*{
    box-sizing:border-box;
}


body{
    margin:0;
}


.reaper-report-document{

    margin:0;

    padding:
        38px 46px;

    background:#FFFFFF;

    color:#111;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    font-size:14px;

    -webkit-print-color-adjust:
        exact;

    print-color-adjust:
        exact;
}


.reaper-report-content{

    width:100%;

    margin:
        0 auto;
}


/* =========================================================
   HEADER
========================================================= */

.reaper-report-header{

    text-align:center;

    margin-bottom:20px;
}


.reaper-report-logo{

    display:block;

    width:80px;

    height:auto;

    margin:
        0 auto 8px;
}


.reaper-report-header h1{

    margin:
        0 0 5px;

    font-size:28px;

    font-weight:700;

    color:#000;
}


.reaper-report-header h2{

    margin:
        0 0 8px;

    font-size:22px;

    font-weight:700;

    color:#555;
}


.reaper-report-header p{

    margin:0;

    color:#666;

    font-size:14px;
}


/* =========================================================
   ACTION BUTTONS
========================================================= */

.reaper-report-actions{

    display:flex;

    gap:7px;

    margin:
        18px 0;
}


.reaper-report-print-button,
.reaper-report-close-button{

    border:none;

    border-radius:8px;

    padding:
        10px 16px;

    color:#FFFFFF;

    font-size:13px;

    font-weight:700;

    cursor:pointer;
}


.reaper-report-print-button{

    background:#8CA15B;
}


.reaper-report-print-button:hover{

    background:#75864C;
}


.reaper-report-close-button{

    background:#D9534F;
}


.reaper-report-close-button:hover{

    background:#C74440;
}


/* =========================================================
   REPORT INFORMATION
========================================================= */

.reaper-report-info{

    margin:
        0 0 18px;
}


.reaper-report-info p{

    margin:
        0 0 10px;

    line-height:1.3;
}


.reaper-report-info hr{

    margin:
        14px 0;

    border:0;

    border-top:
        1px solid
        #B8B8B8;
}


.reaper-report-divider{

    margin:
        12px 0 18px;

    border:0;

    border-top:
        1px solid
        #B8B8B8;
}


/* =========================================================
   TABLE
========================================================= */

.reaper-report-table-container{

    width:100%;

    overflow-x:auto;
}


.reaper-report-table{

    width:100%;

    border-collapse:collapse;

    table-layout:fixed;

    font-size:10px;
}


.reaper-report-table thead{

    display:
        table-header-group;
}


.reaper-report-table th{

    background:#8CA15B;

    color:#FFFFFF;

    border:
        1px solid
        #222;

    padding:
        9px 4px;

    text-align:center;

    vertical-align:middle;

    line-height:1.15;

    font-weight:700;
}


.reaper-report-table td{

    border:
        1px solid
        #444;

    padding:
        8px 5px;

    vertical-align:middle;

    line-height:1.2;

    text-align:center;

    word-break:normal;

    overflow-wrap:break-word;
}


.reaper-report-table tbody tr{

    break-inside:avoid;

    page-break-inside:avoid;
}


/* =========================================================
   COLUMN WIDTHS
   TOTAL = 100%
========================================================= */

.reaper-report-table th:nth-child(1),
.reaper-report-table td:nth-child(1){
    width:12%;
}


.reaper-report-table th:nth-child(2),
.reaper-report-table td:nth-child(2){
    width:11%;
}


.reaper-report-table th:nth-child(3),
.reaper-report-table td:nth-child(3){
    width:7%;
}


.reaper-report-table th:nth-child(4),
.reaper-report-table td:nth-child(4){
    width:11%;
}


.reaper-report-table th:nth-child(5),
.reaper-report-table td:nth-child(5){
    width:10%;
}


.reaper-report-table th:nth-child(6),
.reaper-report-table td:nth-child(6){
    width:9%;
}


.reaper-report-table th:nth-child(7),
.reaper-report-table td:nth-child(7){
    width:7%;
}


.reaper-report-table th:nth-child(8),
.reaper-report-table td:nth-child(8){
    width:7%;
}


.reaper-report-table th:nth-child(9),
.reaper-report-table td:nth-child(9){
    width:7%;
}


.reaper-report-table th:nth-child(10),
.reaper-report-table td:nth-child(10){
    width:7%;
}


.reaper-report-table th:nth-child(11),
.reaper-report-table td:nth-child(11){
    width:12%;
}


/* =========================================================
   CUSTOMER
========================================================= */

.reaper-report-table td:nth-child(2){

    text-align:center;
}


/* =========================================================
   ASSIGNMENTS
========================================================= */

.reaper-report-assignment{

    text-align:center;
}


.reaper-report-assignment strong{

    display:block;

    margin:0;

    font-weight:700;

    line-height:1.15;

    white-space:nowrap;
}


.reaper-report-assignment span{

    display:block;

    margin-top:3px;

    line-height:1.2;

    word-break:normal;

    overflow-wrap:break-word;
}


/* =========================================================
   LOCATION
========================================================= */

.reaper-report-location{

    white-space:normal;

    word-break:normal;

    overflow-wrap:normal;

    hyphens:none;
}


/* =========================================================
   STATUS / REMARKS
========================================================= */

.reaper-report-status,
.reaper-report-remarks{

    white-space:normal;

    word-break:normal;

    overflow-wrap:normal;
}


.reaper-report-maintenance-label{

    width:fit-content;

    margin:
        4px auto 0;

    padding:
        3px 6px;

    border:
        1px solid
        #E59D9D;

    border-radius:10px;

    background:#FFE8E8;

    color:#C62828;

    font-size:8px;

    font-weight:700;
}


/* =========================================================
   RENTAL DATE
========================================================= */

.reaper-report-rental-date{

    white-space:nowrap;
}


/* =========================================================
   EMPTY RESULT
========================================================= */

.reaper-report-no-records{

    padding:
        24px !important;

    text-align:
        center !important;

    color:#666;
}


/* =========================================================
   SUMMARY
========================================================= */

.reaper-report-summary{

    margin:
        0 0 18px;
}


.reaper-report-summary h3{

    margin:
        0 0 14px;

    font-size:21px;

    font-weight:700;
}


.reaper-report-summary p{

    margin:
        0 0 9px;
}


/* =========================================================
   PREPARED BY
========================================================= */

.reaper-report-prepared{

    width:280px;

    margin:
        55px 0 0 auto;

    text-align:center;
}


.reaper-report-prepared p{

    margin:
        0 0 5px;
}


.reaper-report-signature-space{

    height:35px;
}


.reaper-report-prepared > strong{

    display:block;

    padding-top:6px;
}


.reaper-report-prepared span{

    display:block;

    margin-top:3px;

    color:#555;

    font-size:12px;
}


/* =========================================================
   PRINT
========================================================= */

@page{

    margin:7mm;
}


@media print{

    .reaper-report-document{

        margin:0;

        padding:0;

        background:#FFFFFF;

        font-size:10pt;
    }


    .reaper-report-content{

        width:100%;

        max-width:none;
    }


    .reaper-report-actions{

        display:none !important;
    }


    .reaper-report-header{

        margin-bottom:12px;
    }


    .reaper-report-logo{

        width:60px;

        margin:
            0 auto 5px;
    }


    .reaper-report-header h1{

        margin:
            0 0 3px;

        font-size:17pt;

        line-height:1.1;
    }


    .reaper-report-header h2{

        margin:
            0 0 4px;

        font-size:13pt;

        line-height:1.1;
    }


    .reaper-report-header p{

        margin:0;

        font-size:8.5pt;
    }


    .reaper-report-info{

        margin-bottom:10px;

        font-size:8.5pt;
    }


    .reaper-report-info p{

        margin:
            0 0 4px;

        line-height:1.25;
    }


    .reaper-report-info hr{

        margin:
            7px 0;

        border-top:
            1px solid
            #999;
    }


    .reaper-report-divider{

        margin:
            8px 0 10px;
    }


    .reaper-report-table-container{

        width:100%;

        overflow:visible;
    }


    .reaper-report-table{

        width:100%;

        table-layout:fixed;

        border-collapse:collapse;
    }


    .reaper-report-table th{

        padding:
            5px 2px;

        font-weight:700;

        line-height:1.15;

        vertical-align:middle;
    }


    .reaper-report-table td{

        padding:
            5px 3px;

        line-height:1.2;

        vertical-align:middle;
    }


    .reaper-report-table tbody tr{

        break-inside:avoid;

        page-break-inside:avoid;
    }


    .reaper-report-summary{

        margin:
            0 0 10px;

        break-inside:avoid;

        page-break-inside:avoid;
    }


    .reaper-report-summary h3{

        margin:
            0 0 8px;

        font-size:13pt;
    }


    .reaper-report-summary p{

        margin:
            0 0 4px;

        font-size:9pt;
    }


    .reaper-report-prepared{

        width:230px;

        margin:
            38px 0 0 auto;

        font-size:9pt;

        break-inside:avoid;

        page-break-inside:avoid;
    }


    .reaper-report-signature-space{

        height:28px;
    }


    .reaper-report-prepared span{

        font-size:8pt;
    }

}


/* =========================================================
   PORTRAIT
========================================================= */

@media print and (orientation:portrait){

    .reaper-report-table{

        font-size:7.3pt;
    }


    .reaper-report-table th{

        padding:
            4px 2px;

        font-size:6.8pt;

        white-space:normal;

        word-break:normal;

        overflow-wrap:normal;
    }


    .reaper-report-table td{

        padding:
            5px 2px;

        font-size:7.3pt;
    }


    .reaper-report-assignment strong{

        font-size:7.2pt;
    }


    .reaper-report-assignment span{

        margin-top:2px;

        font-size:7pt;
    }


    .reaper-report-maintenance-label{

        padding:
            2px 3px;

        font-size:5.8pt;
    }

}


/* =========================================================
   LANDSCAPE
========================================================= */

@media print and (orientation:landscape){

    .reaper-report-table{

        font-size:8.7pt;
    }


    .reaper-report-table th{

        padding:
            6px 4px;

        font-size:8.2pt;
    }


    .reaper-report-table td{

        padding:
            6px 4px;

        font-size:8.7pt;
    }


    .reaper-report-maintenance-label{

        padding:
            2px 4px;

        font-size:7pt;
    }


    .reaper-report-prepared{

        margin-top:10px;
    }


    .reaper-report-signature-space{

        height:18px;
    }

}


/* =========================================================
   SCREEN
========================================================= */

@media(max-width:900px){

    .reaper-report-document{

        padding:
            25px 20px;
    }

}

</style>

</head>


<body class="reaper-report-document">


<div class="reaper-report-content">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="reaper-report-header">

        <img
        src="../../assets/logo/triangle-logo.png"
        alt="SIPBU-MPC Logo"
        class="reaper-report-logo">


        <h1>
            SIPBU-MPC PORAIS INTEGRATED SYSTEM
        </h1>


        <h2>
            Reaper Report
        </h2>


        <p>
            Rental Management System
        </p>

    </div>


    <!-- =====================================================
         ACTIONS
    ====================================================== -->

    <div class="reaper-report-actions">

        <button
        type="button"
        class="reaper-report-print-button"
        onclick="window.print()">

            <span>
                🖨
            </span>

            Print / Save as PDF

        </button>


        <button
        type="button"
        class="reaper-report-close-button"
        onclick="window.close()">

            ✖ Close

        </button>

    </div>


    <!-- =====================================================
         REPORT INFORMATION
    ====================================================== -->

    <div class="reaper-report-info">


        <p>

            <strong>
                Date Generated:
            </strong>

            <?= reaperReportEscape(
                date(
                    "F d, Y"
                )
            ) ?>

        </p>


        <p>

            <strong>
                Time Generated:
            </strong>

            <?= reaperReportEscape(
                date(
                    "h:i A"
                )
            ) ?>

        </p>


        <hr>


        <p>

            <strong>
                Affiliate:
            </strong>

            <?= reaperReportEscape(
                $affiliateLabel
            ) ?>

        </p>


        <p>

            <strong>
                Status:
            </strong>

            <?= reaperReportEscape(
                $statusLabel
            ) ?>

        </p>


        <p>

            <strong>
                Remarks:
            </strong>

            <?= reaperReportEscape(
                $remarksLabel
            ) ?>

        </p>


        <p>

            <strong>
                Date Range:
            </strong>

            <?= reaperReportEscape(
                $dateRangeLabel
            ) ?>

        </p>


    </div>


    <hr class="reaper-report-divider">


    <!-- =====================================================
         REPORT TABLE
    ====================================================== -->

    <div class="reaper-report-table-container">

        <table class="reaper-report-table">


            <thead>

                <tr>

                    <th>
                        Transaction ID
                    </th>

                    <th>
                        Customer
                    </th>

                    <th>
                        Affiliate
                    </th>

                    <th>
                        Primary Assignment
                    </th>

                    <th>
                        Secondary Assignment
                    </th>

                    <th>
                        Location
                    </th>

                    <th>
                        Commodity
                    </th>

                    <th>
                        No. of Harvest
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Remarks
                    </th>

                    <th>
                        Rental Date
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php

            if(
                $reportLoadError
            ){

            ?>

                <tr>

                    <td
                    colspan="11"
                    class="reaper-report-no-records">

                        Unable to load the report.

                    </td>

                </tr>


            <?php

            }
            elseif(
                count(
                    $reportRows
                ) === 0
            ){

            ?>

                <tr>

                    <td
                    colspan="11"
                    class="reaper-report-no-records">

                        No Reaper records found.

                    </td>

                </tr>


            <?php

            }
            else{

                foreach(
                    $reportRows
                    as
                    $row
                ){

                    /* =========================================
                       PRIMARY ASSIGNMENT
                    ========================================= */

                    $primaryTractorNo =
                    trim(
                        (string)(
                            $row["report_primary_tractor_no"]
                            ??
                            $row["tractor_no"]
                            ??
                            ""
                        )
                    );


                    $primaryTractorName =
                    trim(
                        (string)(
                            $row["report_primary_tractor_name"]
                            ??
                            ""
                        )
                    );


                    $primaryDriver =
                    trim(
                        (string)(
                            $row["operator"]
                            ??
                            ""
                        )
                    );


                    /* =========================================
                       SECONDARY ASSIGNMENT
                    ========================================= */

                    $secondaryTractorNo =
                    trim(
                        (string)(
                            $row["report_secondary_tractor_no"]
                            ??
                            $row["secondary_tractor_no"]
                            ??
                            ""
                        )
                    );


                    $secondaryTractorName =
                    trim(
                        (string)(
                            $row["report_secondary_tractor_name"]
                            ??
                            ""
                        )
                    );


                    $secondaryDriver =
                    trim(
                        (string)(
                            $row["secondary_operator"]
                            ??
                            ""
                        )
                    );


                    $hasSecondaryAssignment =
                    (
                        (int)(
                            $row["secondary_tractor_id"]
                            ??
                            0
                        ) > 0
                    )
                    ||
                    (
                        (int)(
                            $row["secondary_driver_id"]
                            ??
                            0
                        ) > 0
                    )
                    ||
                    $secondaryTractorNo !==
                    ""
                    ||
                    $secondaryTractorName !==
                    ""
                    ||
                    $secondaryDriver !==
                    "";


                    /* =========================================
                       STATUS / REMARKS
                    ========================================= */

                    $rowStatus =
                    trim(
                        (string)(
                            $row["status"]
                            ??
                            ""
                        )
                    );


                    $rowRemarks =
                    trim(
                        (string)(
                            $row["effective_remarks"]
                            ??
                            "Unpaid"
                        )
                    );


                    if(
                        $rowRemarks ===
                        ""
                    ){

                        $rowRemarks =
                        "Unpaid";

                    }


                    $maintenanceAffected =
                    (
                        (int)(
                            $row["maintenance_affected"]
                            ??
                            0
                        )
                        ===
                        1
                    );


                    /* =========================================
                       RENTAL DATE
                    ========================================= */

                    $rentalDateDisplay =
                    "—";


                    $rentalDate =
                    trim(
                        (string)(
                            $row["rental_date"]
                            ??
                            ""
                        )
                    );


                    if(
                        $rentalDate !==
                        ""
                    ){

                        $rentalTimestamp =
                        strtotime(
                            $rentalDate
                        );


                        if(
                            $rentalTimestamp !==
                            false
                        ){

                            $rentalDateDisplay =
                            date(
                                "m-d-Y",
                                $rentalTimestamp
                            );

                        }

                    }


                    /* =========================================
                       SERVICE VALUES
                    ========================================= */

                    $commodityDisplay =
                    $row["commodity"]
                    ===
                    null
                    ||
                    $row["commodity"]
                    ===
                    ""
                    ?
                    "—"
                    :
                    (string)$row["commodity"];


                    $harvestDisplay =
                    $row["no_of_harvest"]
                    ===
                    null
                    ||
                    $row["no_of_harvest"]
                    ===
                    ""
                    ?
                    "—"
                    :
                    (string)$row["no_of_harvest"];

            ?>


                <tr>


                    <!-- ======================================
                         TRANSACTION ID
                    ======================================= -->

                    <td>

                        <?= reaperReportEscape(
                            $row[
                                "transaction_id"
                            ]
                            ??
                            ""
                        ) ?>

                    </td>


                    <!-- ======================================
                         CUSTOMER
                    ======================================= -->

                    <td>

                        <?= reaperReportEscape(
                            $row[
                                "customer_name"
                            ]
                            ??
                            ""
                        ) ?>

                    </td>


                    <!-- ======================================
                         AFFILIATE
                    ======================================= -->

                    <td>

                        <?= reaperReportEscape(
                            $row[
                                "affiliate"
                            ]
                            ??
                            ""
                        ) ?>

                    </td>


                    <!-- ======================================
                         PRIMARY ASSIGNMENT
                    ======================================= -->

                    <td class="reaper-report-assignment">

                        <strong>

                            <?= reaperReportEscape(
                                $primaryTractorNo !==
                                ""
                                ?
                                $primaryTractorNo
                                :
                                "—"
                            ) ?>

                        </strong>


                        <span>

                            <?= reaperReportEscape(
                                $primaryTractorName !==
                                ""
                                ?
                                $primaryTractorName
                                :
                                "—"
                            ) ?>

                        </span>


                        <span>

                            <?= reaperReportEscape(
                                $primaryDriver !==
                                ""
                                ?
                                $primaryDriver
                                :
                                "—"
                            ) ?>

                        </span>

                    </td>


                    <!-- ======================================
                         SECONDARY ASSIGNMENT
                    ======================================= -->

                    <td class="reaper-report-assignment">


                        <?php

                        if(
                            !$hasSecondaryAssignment
                        ){

                        ?>

                            <span>
                                —
                            </span>


                        <?php

                        }
                        else{

                        ?>

                            <strong>

                                <?= reaperReportEscape(
                                    $secondaryTractorNo !==
                                    ""
                                    ?
                                    $secondaryTractorNo
                                    :
                                    "—"
                                ) ?>

                            </strong>


                            <span>

                                <?= reaperReportEscape(
                                    $secondaryTractorName !==
                                    ""
                                    ?
                                    $secondaryTractorName
                                    :
                                    "—"
                                ) ?>

                            </span>


                            <span>

                                <?= reaperReportEscape(
                                    $secondaryDriver !==
                                    ""
                                    ?
                                    $secondaryDriver
                                    :
                                    "—"
                                ) ?>

                            </span>


                        <?php

                        }

                        ?>


                    </td>


                    <!-- ======================================
                         LOCATION
                    ======================================= -->

                    <td class="reaper-report-location">

                        <?= reaperReportEscape(
                            $row[
                                "location"
                            ]
                            ??
                            ""
                        ) ?>

                    </td>


                    <!-- ======================================
                         COMMODITY
                    ======================================= -->

                    <td>

                        <?= reaperReportEscape(
                            $commodityDisplay
                        ) ?>

                    </td>


                    <!-- ======================================
                         NO. OF HARVEST
                    ======================================= -->

                    <td>

                        <?= reaperReportEscape(
                            $harvestDisplay
                        ) ?>

                    </td>


                    <!-- ======================================
                         STATUS
                    ======================================= -->

                    <td class="reaper-report-status">

                        <?= reaperReportEscape(
                            $rowStatus
                        ) ?>


                        <?php

                        if(
                            $maintenanceAffected
                        ){

                        ?>

                            <div class="reaper-report-maintenance-label">

                                Maintenance

                            </div>

                        <?php

                        }

                        ?>

                    </td>


                    <!-- ======================================
                         REMARKS
                    ======================================= -->

                    <td class="reaper-report-remarks">

                        <?= reaperReportEscape(
                            $rowRemarks
                        ) ?>

                    </td>


                    <!-- ======================================
                         RENTAL DATE
                    ======================================= -->

                    <td class="reaper-report-rental-date">

                        <?= reaperReportEscape(
                            $rentalDateDisplay
                        ) ?>

                    </td>


                </tr>


            <?php

                }

            }

            ?>


            </tbody>


        </table>

    </div>


    <hr class="reaper-report-divider">


    <!-- =====================================================
         SUMMARY
    ====================================================== -->

    <div class="reaper-report-summary">

        <hr>


        <h3>
            SUMMARY REPORT
        </h3>


        <p>

            <strong>
                Total Transactions:
            </strong>

            <?= number_format(
                $totalTransactions
            ) ?>

        </p>


        <p>

            <strong>
                Total No. of Harvest:
            </strong>

            <?= reaperReportEscape(
                reaperReportFormatNumber(
                    $totalHarvest
                )
            ) ?>

        </p>


        <p>

            <strong>
                Total Commodity:
            </strong>

            <?= reaperReportEscape(
                reaperReportFormatNumber(
                    $totalCommodity
                )
            ) ?>

        </p>


    </div>


    <hr class="reaper-report-divider">

    <hr>


    <!-- =====================================================
         PREPARED BY
    ====================================================== -->

    <div class="reaper-report-prepared">


        <p>

            <strong>
                Prepared by:
            </strong>

        </p>


        <div class="reaper-report-signature-space"></div>


        <strong>

            <?= reaperReportEscape(
                $preparedBy
            ) ?>

        </strong>


        <span>

            <?= reaperReportEscape(
                $role
            ) ?>

        </span>


    </div>


</div>


</body>

</html>