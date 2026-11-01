<?php

/* =========================================================
   SALES REPORT
========================================================= */

if (
    session_status() ===
    PHP_SESSION_NONE
) {
    session_start();
}

require_once "../includes/check_access.php";

checkAccess(
    "sales_access"
);


date_default_timezone_set(
    "Asia/Manila"
);


error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);


header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   HELPERS
========================================================= */

function salesReportEscape(
    $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );

}


function salesReportMoney(
    $value
): string {

    return "₱" .
        number_format(
            (float) $value,
            2
        );

}


function salesReportDate(
    $date,
    string $format = "m-d-Y"
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


    if (
        $timestamp === false
    ) {
        return "—";
    }


    return date(
        $format,
        $timestamp
    );

}


function salesReportValidDate(
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


    $dateObject =
        DateTimeImmutable::createFromFormat(
            "!Y-m-d",
            $date,
            $timezone
        );


    return (
        $dateObject !== false
        &&
        $dateObject->format(
            "Y-m-d"
        ) === $date
    );

}


function salesReportFail(
    string $message,
    int $statusCode = 400
): void {

    http_response_code(
        $statusCode
    );


    $safeMessage =
        salesReportEscape(
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

<title>
    Unable to generate report
</title>

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
    onclick="window.close();">

        CLOSE

    </button>

</main>

</body>

</html>
HTML;

    exit();

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

    salesReportFail(
        "Your session has expired. Please log in again.",
        401
    );

}


/* =========================================================
   DATABASE
========================================================= */

require_once "../db.php";


if (
    !isset(
        $conn
    )
    ||
    !(
        $conn instanceof mysqli
    )
) {

    salesReportFail(
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

    salesReportFail(
        "Unable to verify the current account.",
        500
    );

}


mysqli_stmt_bind_param(
    $userStatement,
    "i",
    $userID
);


if (
    !mysqli_stmt_execute(
        $userStatement
    )
) {

    mysqli_stmt_close(
        $userStatement
    );


    salesReportFail(
        "Unable to verify the current account.",
        500
    );

}


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

    salesReportFail(
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
            "Unknown User"
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
                    "User"
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

$search =
    trim(
        (string) (
            $_GET[
                "search"
            ]
            ??
            ""
        )
    );


$affiliate =
    strtolower(
        trim(
            (string) (
                $_GET[
                    "affiliate"
                ]
                ??
                "all"
            )
        )
    );


$startDate =
    trim(
        (string) (
            $_GET[
                "start"
            ]
            ??
            ""
        )
    );


$endDate =
    trim(
        (string) (
            $_GET[
                "end"
            ]
            ??
            ""
        )
    );


if (
    strlen(
        $search
    ) > 200
) {

    $search =
        substr(
            $search,
            0,
            200
        );

}


/* =========================================================
   VALIDATE FILTERS
========================================================= */

$allowedAffiliate = [

    "all",
    "member",
    "not member"

];


if (
    !in_array(
        $affiliate,
        $allowedAffiliate,
        true
    )
) {

    salesReportFail(
        "The selected customer type filter is invalid.",
        422
    );

}


$manilaTimezone =
    new DateTimeZone(
        "Asia/Manila"
    );


if (
    $startDate !== ""
    &&
    !salesReportValidDate(
        $startDate,
        $manilaTimezone
    )
) {

    salesReportFail(
        "The selected start date is invalid.",
        422
    );

}


if (
    $endDate !== ""
    &&
    !salesReportValidDate(
        $endDate,
        $manilaTimezone
    )
) {

    salesReportFail(
        "The selected end date is invalid.",
        422
    );

}


$todayManila =
    new DateTimeImmutable(
        "today",
        $manilaTimezone
    );


$todayManilaDate =
    $todayManila->format(
        "Y-m-d"
    );


if (
    (
        $startDate !== ""
        &&
        $startDate > $todayManilaDate
    )
    ||
    (
        $endDate !== ""
        &&
        $endDate > $todayManilaDate
    )
) {

    salesReportFail(
        "Future dates are not allowed.",
        422
    );

}


if (
    $startDate !== ""
    &&
    $endDate !== ""
    &&
    $startDate > $endDate
) {

    salesReportFail(
        "The start date must not be later than the end date.",
        422
    );

}


/* =========================================================
   SALES QUERY
========================================================= */

$salesSql = "
    SELECT

        sale.sale_id,
        sale.transaction_id,
        sale.buyer_name,
        sale.affiliate,
        sale.member_id,
        sale.sale_date,
        sale.grand_total,
        sale.cash_received,
        sale.change_amount,

        member.member_code

    FROM sales AS sale

    LEFT JOIN members AS member
        ON member.member_id =
           sale.member_id

    WHERE 1 = 1
";


$parameterTypes =
    "";


$parameters =
    [];


/* CUSTOMER TYPE */

if (
    $affiliate !==
    "all"
) {

    $salesSql .= "
        AND LOWER(
            sale.affiliate
        ) = ?
    ";


    $parameterTypes .=
        "s";


    $parameters[] =
        $affiliate;

}


/* START DATE */

if (
    $startDate !== ""
) {

    $salesSql .= "
        AND DATE(
            sale.sale_date
        ) >= ?
    ";


    $parameterTypes .=
        "s";


    $parameters[] =
        $startDate;

}


/* END DATE */

if (
    $endDate !== ""
) {

    $salesSql .= "
        AND DATE(
            sale.sale_date
        ) <= ?
    ";


    $parameterTypes .=
        "s";


    $parameters[] =
        $endDate;

}


$salesSql .= "
    ORDER BY
        sale.sale_id ASC
";


$salesStatement =
    mysqli_prepare(
        $conn,
        $salesSql
    );


if (
    !$salesStatement
) {

    salesReportFail(
        "Unable to prepare the Sales report.",
        500
    );

}


/* =========================================================
   BIND DYNAMIC PARAMETERS
========================================================= */

if (
    $parameterTypes !== ""
    &&
    count(
        $parameters
    ) > 0
) {

    $bindArguments = [

        $salesStatement,
        $parameterTypes

    ];


    foreach (
        $parameters
        as $parameterIndex =>
        $parameterValue
    ) {

        $bindArguments[] =
            &$parameters[
                $parameterIndex
            ];

    }


    $bindResult =
        call_user_func_array(
            "mysqli_stmt_bind_param",
            $bindArguments
        );


    if (
        !$bindResult
    ) {

        mysqli_stmt_close(
            $salesStatement
        );


        salesReportFail(
            "Unable to apply the selected Sales report filters.",
            500
        );

    }

}


/* =========================================================
   EXECUTE SALES QUERY
========================================================= */

if (
    !mysqli_stmt_execute(
        $salesStatement
    )
) {

    mysqli_stmt_close(
        $salesStatement
    );


    salesReportFail(
        "Unable to generate the Sales report.",
        500
    );

}


$salesResult =
    mysqli_stmt_get_result(
        $salesStatement
    );


if (
    $salesResult === false
) {

    mysqli_stmt_close(
        $salesStatement
    );


    salesReportFail(
        "Unable to read the generated Sales report.",
        500
    );

}


/* =========================================================
   LOAD ALL SALES ITEMS ONCE
========================================================= */

$itemsBySale =
    [];


$itemSql = "
    SELECT

        item.sale_id,
        item.product_id,
        item.quantity,
        item.subtotal,

        inventory.product_name

    FROM sales_items AS item

    LEFT JOIN inventory AS inventory
        ON inventory.product_id =
           item.product_id

    ORDER BY

        item.sale_id ASC,
        inventory.product_name ASC
";


$itemResult =
    mysqli_query(
        $conn,
        $itemSql
    );


if (
    !$itemResult
) {

    mysqli_stmt_close(
        $salesStatement
    );


    salesReportFail(
        "Unable to load the Sales report items.",
        500
    );

}


while (
    $item =
        mysqli_fetch_assoc(
            $itemResult
        )
) {

    $saleID =
        (int) (
            $item[
                "sale_id"
            ]
            ??
            0
        );


    if (
        $saleID <= 0
    ) {

        continue;

    }


    if (
        !isset(
            $itemsBySale[
                $saleID
            ]
        )
    ) {

        $itemsBySale[
            $saleID
        ] = [];

    }


    $productName =
        trim(
            (string) (
                $item[
                    "product_name"
                ]
                ??
                ""
            )
        );


    if (
        $productName === ""
    ) {

        $productName =
            "Unavailable Product";

    }


    $itemsBySale[
        $saleID
    ][] = [

        "product_name" =>
            $productName,

        "quantity" =>
            max(
                0,
                (int) (
                    $item[
                        "quantity"
                    ]
                    ??
                    0
                )
            ),

        "subtotal" =>
            (float) (
                $item[
                    "subtotal"
                ]
                ??
                0
            )

    ];

}


/* =========================================================
   BUILD REPORT DATA
========================================================= */

$reportRows =
    [];


$totalRecords =
    0;


$memberTransactions =
    0;


$notMemberTransactions =
    0;


$totalUnits =
    0;


$totalSales =
    0.00;


$normalizedSearch =
    strtolower(
        $search
    );


while (
    $sale =
        mysqli_fetch_assoc(
            $salesResult
        )
) {

    $saleID =
        (int) (
            $sale[
                "sale_id"
            ]
            ??
            0
        );


    if (
        $saleID <= 0
    ) {

        continue;

    }


    /* =====================================================
       SALES DISPLAY ID
    ===================================================== */

    $displayID =
        "SL-" .
        str_pad(
            (string) $saleID,
            6,
            "0",
            STR_PAD_LEFT
        );


    $originalTransactionID =
        trim(
            (string) (
                $sale[
                    "transaction_id"
                ]
                ??
                ""
            )
        );


    /* =====================================================
       CUSTOMER TYPE
    ===================================================== */

    $typeKey =
        strtolower(
            trim(
                (string) (
                    $sale[
                        "affiliate"
                    ]
                    ??
                    ""
                )
            )
        );


    if (
        $typeKey ===
        "member"
    ) {

        $typeLabel =
            "Member";

    } elseif (
        $typeKey ===
        "not member"
    ) {

        $typeLabel =
            "Not Member";

    } else {

        $typeLabel =
            "Unknown";

    }


    /* =====================================================
       BUYER
    ===================================================== */

    $buyerName =
        trim(
            (string) (
                $sale[
                    "buyer_name"
                ]
                ??
                ""
            )
        );


    $memberID =
        (int) (
            $sale[
                "member_id"
            ]
            ??
            0
        );


    $memberCode =
        trim(
            (string) (
                $sale[
                    "member_code"
                ]
                ??
                ""
            )
        );


    $buyerPrimary =
        $buyerName;


    $buyerSecondary =
        "";


    if (
        $typeKey ===
        "member"
    ) {

        if (
            $buyerPrimary === ""
        ) {

            $buyerPrimary =
                "Member";

        }


        if (
            $memberID > 0
            &&
            $memberCode !== ""
        ) {

            $buyerSecondary =
                $memberCode;

        }

    } else {

        if (
            $buyerPrimary === ""
        ) {

            $buyerPrimary =
                "Walk-in Customer";

        }


        $buyerSecondary =
            "";

    }


    /* =====================================================
       DATE
    ===================================================== */

    $saleDateRaw =
        (string) (
            $sale[
                "sale_date"
            ]
            ??
            ""
        );


    $saleTimestamp =
        strtotime(
            $saleDateRaw
        );


    if (
        $saleTimestamp === false
    ) {

        $saleDateISO =
            "";

        $saleDateDisplay =
            "Unknown Date";

        $saleDateSearch =
            "";

    } else {

        $saleDateISO =
            date(
                "Y-m-d",
                $saleTimestamp
            );


        $saleDateDisplay =
            date(
                "M d, Y",
                $saleTimestamp
            );


        $saleDateSearch =
            date(
                "m-d-Y",
                $saleTimestamp
            );

    }


    /* =====================================================
       SALE ITEMS
    ===================================================== */

    $saleItems =
        $itemsBySale[
            $saleID
        ]
        ??
        [];


    $productNames =
        [];


    $saleQuantity =
        0;


    foreach (
        $saleItems
        as $saleItem
    ) {

        $currentProductName =
            trim(
                (string) (
                    $saleItem[
                        "product_name"
                    ]
                    ??
                    ""
                )
            );


        if (
            $currentProductName !== ""
        ) {

            $productNames[] =
                $currentProductName;

        }


        $saleQuantity +=
            (int) (
                $saleItem[
                    "quantity"
                ]
                ??
                0
            );

    }


    /* =====================================================
       SEARCH MATCH
    ===================================================== */

    $searchText =
        strtolower(
            trim(
                implode(
                    " ",
                    [

                        $displayID,

                        $originalTransactionID,

                        $buyerPrimary,

                        $buyerSecondary,

                        $memberCode,

                        implode(
                            " ",
                            $productNames
                        ),

                        $saleDateDisplay,

                        $saleDateSearch,

                        $saleDateISO,

                        (string) $saleQuantity,

                        (string) (
                            $sale[
                                "grand_total"
                            ]
                            ??
                            0
                        ),

                        $typeLabel

                    ]
                )
            )
        );


    if (
        $normalizedSearch !== ""
        &&
        strpos(
            $searchText,
            $normalizedSearch
        ) === false
    ) {

        continue;

    }


    /* =====================================================
       TYPE CLASS
    ===================================================== */

    if (
        $typeKey ===
        "member"
    ) {

        $typeClass =
            "type-member";

    } elseif (
        $typeKey ===
        "not member"
    ) {

        $typeClass =
            "type-not-member";

    } else {

        $typeClass =
            "type-other";

    }


    /* =====================================================
       REPORT ROW
    ===================================================== */

    $reportRows[] = [

        "sale_id" =>
            $saleID,

        "display_id" =>
            $displayID,

        "sale_date" =>
            $saleDateRaw,

        "buyer" =>
            $buyerPrimary,

        "buyer_secondary" =>
            $buyerSecondary,

        "items" =>
            $saleItems,

        "total_quantity" =>
            $saleQuantity,

        "grand_total" =>
            (float) (
                $sale[
                    "grand_total"
                ]
                ??
                0
            ),

        "cash_received" =>
            $sale["cash_received"] === null
            ? null
            : (float) $sale["cash_received"],

        "change_amount" =>
            $sale["change_amount"] === null
            ? null
            : (float) $sale["change_amount"],

        "type_key" =>
            $typeKey,

        "type_label" =>
            $typeLabel,

        "type_class" =>
            $typeClass

    ];


    /* =====================================================
       SUMMARY
    ===================================================== */

    $totalRecords++;


    $totalUnits +=
        $saleQuantity;


    $totalSales +=
        (float) (
            $sale[
                "grand_total"
            ]
            ??
            0
        );


    if (
        $typeKey ===
        "member"
    ) {

        $memberTransactions++;

    } elseif (
        $typeKey ===
        "not member"
    ) {

        $notMemberTransactions++;

    }

}


mysqli_stmt_close(
    $salesStatement
);


mysqli_close(
    $conn
);


/* =========================================================
   FILTER LABELS
========================================================= */

if (
    $affiliate ===
    "member"
) {

    $affiliateLabel =
        "Member";

} elseif (
    $affiliate ===
    "not member"
) {

    $affiliateLabel =
        "Not Member";

} else {

    $affiliateLabel =
        "All Customer Types";

}


/* =========================================================
   DATE RANGE LABEL
========================================================= */

if (
    $startDate !== ""
    &&
    $endDate !== ""
) {

    $dateRangeLabel =
        salesReportDate(
            $startDate,
            "M d, Y"
        )
        .
        " - "
        .
        salesReportDate(
            $endDate,
            "M d, Y"
        );

} elseif (
    $startDate !== ""
) {

    $dateRangeLabel =
        "From " .
        salesReportDate(
            $startDate,
            "M d, Y"
        );

} elseif (
    $endDate !== ""
) {

    $dateRangeLabel =
        "Up to " .
        salesReportDate(
            $endDate,
            "M d, Y"
        );

} else {

    $dateRangeLabel =
        "All Transaction Dates";

}


/* =========================================================
   SEARCH LABEL
========================================================= */

$searchLabel =
    $search !== ""
    ?
    $search
    :
    "None";

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
    Sales Report
</title>


<style>

/* =========================================================
   VARIABLES
========================================================= */

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

}


/* =========================================================
   BASE
========================================================= */

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


/* =========================================================
   REPORT HEADER
========================================================= */

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


/* =========================================================
   REPORT BUTTONS
========================================================= */

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


/* =========================================================
   REPORT INFORMATION
========================================================= */

.report-information {

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

    width:
        100%;

    margin:
        20px 0;

    padding:
        0;

    background:
        transparent;

    border:
        none;

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


/* =========================================================
   REPORT TABLE
========================================================= */

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
        1100px;

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
        11px;

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
        anywhere;

}


.left-cell {

    text-align:
        left;

}


.money-cell {

    text-align:
        right;

    white-space:
        nowrap;

}


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

}


/* =========================================================
   PRODUCT / QUANTITY LINES
========================================================= */

.product-entry,
.quantity-entry {

    display:
        block;

    min-height:
        16px;

    margin-bottom:
        3px;

    line-height:
        16px;

}


.product-entry:last-child,
.quantity-entry:last-child {

    margin-bottom:
        0;

}


.product-entry {

    overflow:
        hidden;

    text-overflow:
        ellipsis;

    white-space:
        nowrap;

}


.quantity-entry {

    text-align:
        center;

    font-weight:
        700;

}


/* =========================================================
   CUSTOMER TYPE
========================================================= */

.type-text {

    font-weight:
        700;

}


.type-member {

    color:
        #168354;

}


.type-not-member {

    color:
        #606060;

}


.type-other {

    color:
        #bb7a00;

}


/* =========================================================
   EMPTY ROW
========================================================= */

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


/* =========================================================
   SUMMARY REPORT
========================================================= */

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
            180px,
            1fr
        )
        max-content;

    align-items:
        baseline;

    column-gap:
        12px;

    min-height:
        0;

    margin:
        0;

    padding:
        0;

    background:
        transparent;

    border:
        none;

}


.summary-item span {

    min-width:
        0;

    color:
        var(--report-text);

    font-size:
        14px;

    font-weight:
        700;

}


.summary-item span::after {

    content:
        ":";

}


.summary-item strong {

    margin:
        0;

    color:
        var(--report-text);

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


/* =========================================================
   PREPARED BY
========================================================= */

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


.prepared-by strong {

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

    display:
        inline-block;

    min-width:
        190px;

    margin-top:
        3px;

    color:
        var(--report-muted);

    font-size:
        12px;

    text-align:
        center;

}


/* =========================================================
   PRINT SETTINGS
========================================================= */

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
            60px;

        row-gap:
            10px;

        justify-content:
            start;

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


/* =========================================================
   PORTRAIT PRINT
========================================================= */

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

        column-gap:
            38px;

        row-gap:
            8px;

        margin:
            14px 0;

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


    table {

        margin-top:
            12px;

    }


    
    /* EIGHT REPORT COLUMNS */

    col:nth-child(1) {
        width: 13% !important;
    }

    col:nth-child(2) {
        width: 14% !important;
    }

    col:nth-child(3) {
        width: 20% !important;
    }

    col:nth-child(4) {
        width: 6% !important;
    }

    col:nth-child(5) {
        width: 12% !important;
    }

    col:nth-child(6) {
        width: 14% !important;
    }

    col:nth-child(7) {
        width: 11% !important;
    }

    col:nth-child(8) {
        width: 10% !important;
    }



    th {

        padding:
            6px 3px;

        font-size:
            8.5px;

        line-height:
            1.2;

    }


    td {

        padding:
            6px 4px;

        font-size:
            9px;

        line-height:
            1.3;

        overflow-wrap:
            break-word;

    }


    .primary-text {

        line-height:
            1.25;

    }


    .secondary-text {

        margin-top:
            2px;

        font-size:
            8px;

        line-height:
            1.2;

    }


    .product-entry,
    .quantity-entry {

        min-height:
            15px;

        margin-bottom:
            2px;

        line-height:
            15px;

    }


    .product-entry {

        white-space:
            normal;

        overflow:
            visible;

        text-overflow:
            clip;

        overflow-wrap:
            break-word;

        word-break:
            normal;

    }


    .money-cell {

        white-space:
            normal;

    }


    .type-text {

        font-size:
            8.5px;

        line-height:
            1.2;

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
                minmax(0, 1fr)
            );

        column-gap:
            26px;

        row-gap:
            8px;

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
            10.5px;

    }


    .prepared-by {

        margin-top:
            38px;

    }


    .signature-space {

        height:
            30px;

    }


    .prepared-by strong {

        min-width:
            165px;

        font-size:
            10.5px;

    }


    .prepared-by span {

        min-width:
            165px;

        font-size:
            9.5px;

    }

}


/* =========================================================
   LANDSCAPE PRINT
========================================================= */

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


    .product-entry {

        white-space:
            nowrap;

        overflow:
            hidden;

        text-overflow:
            ellipsis;

    }

}


/* =========================================================
   SMALL SCREEN
========================================================= */

@media (
    max-width:
    700px
) {

    body {

        margin:
            18px;

    }


    .report-information {

        grid-template-columns:
            1fr;

        row-gap:
            12px;

    }


    .summary-grid {

        grid-template-columns:
            1fr;

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
    src="../assets/logo/triangle-logo.png"
    alt="SIPBU-MPC logo"
    class="report-logo">


    <h1>
        SIPBU-MPC PORAIS INTEGRATED SYSTEM
    </h1>


    <h2>
        Sales Report
    </h2>


    <p>
        Sales and Inventory Management System
    </p>


</header>


<!-- =========================================================
     REPORT ACTIONS
========================================================= -->

<div class="report-actions">


    <button
    type="button"
    onclick="window.print();">

        🖨 Print / Save as PDF

    </button>


    <button
    type="button"
    class="close-btn"
    onclick="window.close();">

        ✖ Close

    </button>


</div>


<!-- =========================================================
     REPORT INFORMATION
========================================================= -->

<section class="report-information">


    <p>

        <strong>
            Date Generated:
        </strong>

        <br>

        <?=
            salesReportEscape(
                date(
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

        <?=
            salesReportEscape(
                date(
                    "h:i A"
                )
            );
        ?>

    </p>


    <p>

        <strong>
            Customer Type:
        </strong>

        <br>

        <?=
            salesReportEscape(
                $affiliateLabel
            );
        ?>

    </p>


    <p>

        <strong>
            Transaction Date:
        </strong>

        <br>

        <?=
            salesReportEscape(
                $dateRangeLabel
            );
        ?>

    </p>


    <p>

        <strong>
            Search Filter:
        </strong>

        <br>

        <?=
            salesReportEscape(
                $searchLabel
            );
        ?>

    </p>


    <p>

        <strong>
            Record Scope:
        </strong>

        <br>

        Current Sales Ledger

    </p>


</section>


<p class="report-scope-note">

    This report includes Sales transactions that match
    the active Sales ledger filters at the time the report
    was generated.

</p>


<!-- =========================================================
     SALES TABLE
========================================================= -->

<div class="report-table-wrapper">


    <table>


        <colgroup>

            <col style="width: 13%;">

            <col style="width: 15%;">

            <col style="width: 20%;">

            <col style="width: 6%;">

            <col style="width: 12%;">

            <col style="width: 14%;">

            <col style="width: 11%;">

            <col style="width: 9%;">

        </colgroup>


        <thead>


            <tr>

                <th>
                    Transaction
                </th>

                <th>
                    Buyer
                </th>

                <th>
                    Products
                </th>

                <th>
                    Qty
                </th>

                <th>
                    Total
                </th>

                <th>
                    Cash Received
                </th>

                <th>
                    Change
                </th>

                <th>
                    Type
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

                <td colspan="8">

                    No Sales records matched
                    the selected report filters.

                </td>

            </tr>


        <?php

        } else {

        ?>


            <?php

            foreach (
                $reportRows
                as $sale
            ) {

            ?>


                <tr>


                    <!-- TRANSACTION -->

                    <td>

                        <span class="primary-text">

                            <?=
                                salesReportEscape(
                                    $sale[
                                        "display_id"
                                    ]
                                );
                            ?>

                        </span>


                        <span class="secondary-text">

                            <?=
                                salesReportEscape(
                                    salesReportDate(
                                        $sale[
                                            "sale_date"
                                        ]
                                    )
                                );
                            ?>

                        </span>

                    </td>


                    <!-- BUYER -->

                    <td>

                        <span class="primary-text">

                            <?=
                                salesReportEscape(
                                    $sale[
                                        "buyer"
                                    ]
                                );
                            ?>

                        </span>


                        <?php

                        if (
                            $sale[
                                "buyer_secondary"
                            ]
                            !==
                            ""
                        ) {

                        ?>


                            <span class="secondary-text">

                                <?=
                                    salesReportEscape(
                                        $sale[
                                            "buyer_secondary"
                                        ]
                                    );
                                ?>

                            </span>


                        <?php

                        }

                        ?>

                    </td>


                    <!-- PRODUCTS -->

                    <td class="left-cell">


                    <?php

                    if (
                        count(
                            $sale[
                                "items"
                            ]
                        ) === 0
                    ) {

                    ?>


                        —


                    <?php

                    } else {

                    ?>


                        <?php

                        foreach (
                            $sale[
                                "items"
                            ]
                            as $item
                        ) {

                        ?>


                            <span
                            class="product-entry"
                            title="<?=
                                salesReportEscape(
                                    $item[
                                        "product_name"
                                    ]
                                );
                            ?>">

                                <?=
                                    salesReportEscape(
                                        $item[
                                            "product_name"
                                        ]
                                    );
                                ?>

                            </span>


                        <?php

                        }

                        ?>


                    <?php

                    }

                    ?>


                    </td>


                    <!-- QUANTITY -->

                    <td>


                    <?php

                    if (
                        count(
                            $sale[
                                "items"
                            ]
                        ) === 0
                    ) {

                    ?>


                        —


                    <?php

                    } else {

                    ?>


                        <?php

                        foreach (
                            $sale[
                                "items"
                            ]
                            as $item
                        ) {

                        ?>


                            <span class="quantity-entry">

                                <?=
                                    number_format(
                                        (int) (
                                            $item[
                                                "quantity"
                                            ]
                                            ??
                                            0
                                        )
                                    );
                                ?>

                            </span>


                        <?php

                        }

                        ?>


                    <?php

                    }

                    ?>


                    </td>


                    <!-- TOTAL -->

                    <td class="money-cell">

                        <span class="primary-text">

                            <?=
                                salesReportEscape(
                                    salesReportMoney(
                                        $sale[
                                            "grand_total"
                                        ]
                                    )
                                );
                            ?>

                        </span>


                        <span class="secondary-text">

                            <?=
                                number_format(
                                    (int) $sale[
                                        "total_quantity"
                                    ]
                                );
                            ?>

                            <?=
                                (
                                    (int) $sale[
                                        "total_quantity"
                                    ]
                                    ===
                                    1
                                )
                                ?
                                "unit"
                                :
                                "units";
                            ?>

                        </span>

                    </td>



                    <!-- CASH RECEIVED -->

                    <td class="money-cell">
                        <?=
                            $sale["cash_received"] === null
                            ? "—"
                            : salesReportEscape(
                                salesReportMoney($sale["cash_received"])
                            );
                        ?>
                    </td>


                    <!-- CHANGE -->

                    <td class="money-cell">
                        <?=
                            $sale["change_amount"] === null
                            ? "—"
                            : salesReportEscape(
                                salesReportMoney($sale["change_amount"])
                            );
                        ?>
                    </td>


                    <!-- TYPE -->

                    <td>

                        <span
                        class="
                            type-text

                            <?=
                                salesReportEscape(
                                    $sale[
                                        "type_class"
                                    ]
                                );
                            ?>
                        ">

                            <?=
                                salesReportEscape(
                                    $sale[
                                        "type_label"
                                    ]
                                );
                            ?>

                        </span>

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

            <?=
                number_format(
                    $totalRecords
                );
            ?>

        </strong>

    </div>


    <div class="summary-item">

        <span>
            Total Units Sold
        </span>

        <strong>

            <?=
                number_format(
                    $totalUnits
                );
            ?>

        </strong>

    </div>


    <div class="summary-item">

        <span>
            Member Transactions
        </span>

        <strong>

            <?=
                number_format(
                    $memberTransactions
                );
            ?>

        </strong>

    </div>


    <div class="summary-item">

        <span>
            Not Member Transactions
        </span>

        <strong>

            <?=
                number_format(
                    $notMemberTransactions
                );
            ?>

        </strong>

    </div>


    <div class="summary-item">

        <span>
            Total Sales
        </span>

        <strong>

            <?=
                salesReportEscape(
                    salesReportMoney(
                        $totalSales
                    )
                );
            ?>

        </strong>

    </div>


</section>


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

        <?=
            salesReportEscape(
                $preparedBy
            );
        ?>

    </strong>


    <br>


    <span>

        <?=
            salesReportEscape(
                $preparedByRole
            );
        ?>

    </span>


</section>


</body>

</html>