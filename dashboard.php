<?php

/* =========================================================
   SPIS DASHBOARD
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

error_reporting(E_ALL);
ini_set("display_errors", "0");

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


/* =========================================================
   AUTHENTICATION
========================================================= */

$userID = isset($_SESSION["user_id"])
    ? (int) $_SESSION["user_id"]
    : 0;

if ($userID <= 0) {
    header("Location: /spis/login.php");
    exit();
}


/* =========================================================
   DATABASE
========================================================= */

require_once "db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    http_response_code(500);
    exit("Database connection is unavailable.");
}


mysqli_set_charset($conn, "utf8mb4");
mysqli_query($conn, "SET time_zone = '+08:00'");


/* =========================================================
   LIVE ACCOUNT AND PASSWORD-CHANGE CHECK
========================================================= */

try {

    $authStmt = mysqli_prepare(
        $conn,
        "SELECT status, archived, must_change_password,
                password_reset_access, password
         FROM users
         WHERE user_id = ?
         LIMIT 1"
    );


    mysqli_stmt_bind_param($authStmt, "i", $userID);
    mysqli_stmt_execute($authStmt);

    $authResult = mysqli_stmt_get_result($authStmt);
    $currentUser = mysqli_fetch_assoc($authResult);

    mysqli_stmt_close($authStmt);

} catch (Throwable $error) {

    error_log(
        "SPIS dashboard access verification: " .
        $error->getMessage()
    );

    http_response_code(503);

    exit("Unable to verify account access.");
}


/* =========================================================
   BLOCK INACTIVE / ARCHIVED ACCOUNTS
========================================================= */

if (
    !$currentUser ||
    (int) ($currentUser["archived"] ?? 1) !== 0 ||
    ($currentUser["status"] ?? "") !== "Active"
) {

    $_SESSION = [];

    session_destroy();

    header("Location: /spis/login.php");

    exit();
}



/* =========================================================
   VERIFY CURRENT PASSWORD VERSION
========================================================= */

$sessionPasswordVersion =
    $_SESSION["password_version"] ?? null;

$currentPasswordVersion = hash(
    "sha256",
    (string) ($currentUser["password"] ?? "")
);

if (
    !is_string($sessionPasswordVersion) ||
    !hash_equals(
        $currentPasswordVersion,
        $sessionPasswordVersion
    )
) {

    $_SESSION = [];
    session_destroy();

    header("Location: /spis/login.php", true, 303);
    exit();
}


/* =========================================================
   REFRESH PASSWORD RESET PERMISSION
========================================================= */


$_SESSION["password_reset_access"] =
    (int) (
        $currentUser["password_reset_access"]
        ?? 0
    );


/* =========================================================
   REQUIRE PASSWORD CHANGE
========================================================= */

if (
    (int) (
        $currentUser["must_change_password"]
        ?? 0
    ) === 1
) {

    header(
        "Location: /spis/change_password_required.php"
    );

    exit();
}



/* =========================================================
   HELPERS
========================================================= */

function dashboardEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

function dashboardMoney($value): string
{
    return "₱" . number_format((float) $value, 2);
}

function dashboardValidDate(?string $date): ?DateTimeImmutable
{
    if (empty($date)) {
        return null;
    }

    $timezone = new DateTimeZone("Asia/Manila");

    $parsed = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        $date,
        $timezone
    );

    $errors = DateTimeImmutable::getLastErrors();

    if (
        $parsed === false ||
        (
            is_array($errors) &&
            (
                $errors["warning_count"] > 0 ||
                $errors["error_count"] > 0
            )
        ) ||
        $parsed->format("Y-m-d") !== $date
    ) {
        return null;
    }

    return $parsed;
}

function dashboardElapsedDays(
    ?string $fromDate,
    DateTimeImmutable $today,
    DateTimeZone $timezone
): int {
    if (
        empty($fromDate) ||
        $fromDate === "0000-00-00"
    ) {
        return 0;
    }

    try {
        $from = new DateTimeImmutable(
            $fromDate,
            $timezone
        );
    } catch (Throwable $exception) {
        return 0;
    }

    if ($today <= $from) {
        return 0;
    }

    return (int) $from->diff($today)->days;
}

function dashboardSingleCount(
    mysqli $conn,
    string $sql
): int {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        error_log(
            "Dashboard count query error: " .
            mysqli_error($conn)
        );

        return 0;
    }

    $row = mysqli_fetch_assoc($result);

    return (int) ($row["total"] ?? 0);
}


/* =========================================================
   CURRENT USER AND MODULE ACCESS

   IMPORTANT:
   Module access is used only for Dashboard navigation.
   Dashboard summaries themselves are global/read-only.
========================================================= */

$userName = trim(
    (string) (
        $_SESSION["name"]
        ?? $_SESSION["first_name"]
        ?? "User"
    )
);

$userRole = trim(
    (string) ($_SESSION["role"] ?? "Authorized User")
);

if ($userName === "") {
    $userName = "User";
}

$hasSalesAccess =
    (int) ($_SESSION["sales_access"] ?? 0) === 1;

$hasLendingAccess =
    (int) ($_SESSION["lending_access"] ?? 0) === 1;

$hasMembersAccess =
    (int) ($_SESSION["members_access"] ?? 0) === 1;


/* =========================================================
   MANILA DATE AND DASHBOARD RANGE
========================================================= */

$manilaTimezone =
    new DateTimeZone("Asia/Manila");

$today =
    new DateTimeImmutable(
        "today",
        $manilaTimezone
    );

$allowedRanges = [
    "today",
    "month",
    "year",
    "all",
    "custom"
];

$selectedRange = strtolower(
    trim(
        (string) ($_GET["range"] ?? "month")
    )
);

if (
    !in_array(
        $selectedRange,
        $allowedRanges,
        true
    )
) {
    $selectedRange = "month";
}

$rangeStart =
    $today->modify("first day of this month");

$rangeEnd = $today;

$periodLabel = "This Month";

switch ($selectedRange) {

    case "today":

        $rangeStart = $today;
        $rangeEnd = $today;
        $periodLabel = "Today";

        break;


    case "year":

        $rangeStart = $today->setDate(
            (int) $today->format("Y"),
            1,
            1
        );

        $rangeEnd = $today;

        $periodLabel =
            "This Year";

        break;


    case "all":

        $rangeStart =
            new DateTimeImmutable(
                "2000-01-01",
                $manilaTimezone
            );

        $rangeEnd = $today;

        $periodLabel =
            "All Time";

        break;


    case "custom":

        $customFrom =
            dashboardValidDate(
                isset($_GET["from"])
                    ? (string) $_GET["from"]
                    : null
            );

        $customTo =
            dashboardValidDate(
                isset($_GET["to"])
                    ? (string) $_GET["to"]
                    : null
            );


        if (
            $customFrom !== null
            &&
            $customTo !== null
        ) {

            if (
                $customFrom <= $customTo
            ) {

                $rangeStart =
                    $customFrom;

                $rangeEnd =
                    $customTo;

            } else {

                $rangeStart =
                    $customTo;

                $rangeEnd =
                    $customFrom;

            }


            $periodLabel =
                $rangeStart->format(
                    "M d, Y"
                )
                .
                " – "
                .
                $rangeEnd->format(
                    "M d, Y"
                );

        } else {

            $selectedRange =
                "month";

        }

        break;

}


$rangeStartSQL =
    $rangeStart->format(
        "Y-m-d"
    );

$rangeEndSQL =
    $rangeEnd->format(
        "Y-m-d"
    );


/* =========================================================
   GLOBAL SALES SUMMARY AND TOP PRODUCTS

   This information is available as a read-only Dashboard
   overview to every authenticated SPIS account.
========================================================= */

$totalSales =
    0.00;

$totalTransactions =
    0;

$topProducts =
    [];

$salesDataError =
    false;


$salesSummaryStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            COUNT(*)
                AS total_transactions,

            COALESCE(
                SUM(grand_total),
                0
            ) AS total_sales

        FROM sales

        WHERE
            DATE(sale_date)
            BETWEEN ? AND ?
        "
    );


if (
    $salesSummaryStatement
) {

    mysqli_stmt_bind_param(
        $salesSummaryStatement,
        "ss",
        $rangeStartSQL,
        $rangeEndSQL
    );


    if (
        mysqli_stmt_execute(
            $salesSummaryStatement
        )
    ) {

        $salesSummaryResult =
            mysqli_stmt_get_result(
                $salesSummaryStatement
            );


        $salesSummary =
            mysqli_fetch_assoc(
                $salesSummaryResult
            );


        $totalSales =
            (float) (
                $salesSummary[
                    "total_sales"
                ]
                ??
                0
            );


        $totalTransactions =
            (int) (
                $salesSummary[
                    "total_transactions"
                ]
                ??
                0
            );

    } else {

        $salesDataError =
            true;

    }


    mysqli_stmt_close(
        $salesSummaryStatement
    );

} else {

    $salesDataError =
        true;

}


/* =========================================================
   TOP-SELLING PRODUCTS
========================================================= */

$topProductsStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            inventory.product_id,

            inventory.product_name,

            SUM(
                sales_items.quantity
            ) AS units_sold,

            SUM(
                sales_items.subtotal
            ) AS sales_amount

        FROM sales_items

        INNER JOIN sales
            ON sales.sale_id =
               sales_items.sale_id

        INNER JOIN inventory
            ON inventory.product_id =
               sales_items.product_id

        WHERE
            DATE(sales.sale_date)
            BETWEEN ? AND ?

        GROUP BY
            inventory.product_id,
            inventory.product_name

        ORDER BY
            units_sold DESC,
            sales_amount DESC,
            inventory.product_name ASC

        LIMIT 8
        "
    );


if (
    $topProductsStatement
) {

    mysqli_stmt_bind_param(
        $topProductsStatement,
        "ss",
        $rangeStartSQL,
        $rangeEndSQL
    );


    if (
        mysqli_stmt_execute(
            $topProductsStatement
        )
    ) {

        $topProductsResult =
            mysqli_stmt_get_result(
                $topProductsStatement
            );


        while (
            $product =
                mysqli_fetch_assoc(
                    $topProductsResult
                )
        ) {

            $topProducts[] = [

                "name" =>
                    (string) (
                        $product[
                            "product_name"
                        ]
                        ??
                        ""
                    ),

                "units" =>
                    (int) (
                        $product[
                            "units_sold"
                        ]
                        ??
                        0
                    ),

                "amount" =>
                    (float) (
                        $product[
                            "sales_amount"
                        ]
                        ??
                        0
                    )

            ];

        }

    } else {

        $salesDataError =
            true;

    }


    mysqli_stmt_close(
        $topProductsStatement
    );

} else {

    $salesDataError =
        true;

}


$topProductUnits =
    array_sum(
        array_column(
            $topProducts,
            "units"
        )
    );


$chartLabels =
    [];

$chartValues =
    [];

$chartAmounts =
    [];


foreach (
    $topProducts
    as
    $product
) {

    $chartLabels[] =
        $product[
            "name"
        ];

    $chartValues[] =
        $product[
            "units"
        ];

    $chartAmounts[] =
        $product[
            "amount"
        ];

}


/* =========================================================
   GLOBAL LEAST-SELLING PRODUCTS
========================================================= */

$leastSellingProducts =
    [];


$leastSellingStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT

            inventory.product_id,

            inventory.product_name,

            COALESCE(
                SUM(
                    CASE

                        WHEN
                            sales.sale_id
                            IS NOT NULL

                        THEN
                            sales_items.quantity

                        ELSE
                            0

                    END
                ),
                0
            ) AS units_sold,

            COALESCE(
                SUM(
                    CASE

                        WHEN
                            sales.sale_id
                            IS NOT NULL

                        THEN
                            sales_items.subtotal

                        ELSE
                            0

                    END
                ),
                0
            ) AS sales_amount

        FROM inventory

        LEFT JOIN sales_items

            ON
                sales_items.product_id =
                inventory.product_id

        LEFT JOIN sales

            ON
                sales.sale_id =
                sales_items.sale_id

            AND
                DATE(
                    sales.sale_date
                )
                BETWEEN ? AND ?

        WHERE
            inventory.archived = 0

        GROUP BY
            inventory.product_id,
            inventory.product_name

        ORDER BY
            units_sold ASC,
            sales_amount ASC,
            inventory.product_name ASC

        LIMIT 8
        "
    );


if (
    $leastSellingStatement
) {

    mysqli_stmt_bind_param(
        $leastSellingStatement,
        "ss",
        $rangeStartSQL,
        $rangeEndSQL
    );


    if (
        mysqli_stmt_execute(
            $leastSellingStatement
        )
    ) {

        $leastSellingResult =
            mysqli_stmt_get_result(
                $leastSellingStatement
            );


        while (
            $product =
                mysqli_fetch_assoc(
                    $leastSellingResult
                )
        ) {

            $leastSellingProducts[] = [

                "name" =>
                    (string) (
                        $product[
                            "product_name"
                        ]
                        ??
                        ""
                    ),

                "units" =>
                    (int) (
                        $product[
                            "units_sold"
                        ]
                        ??
                        0
                    ),

                "amount" =>
                    (float) (
                        $product[
                            "sales_amount"
                        ]
                        ??
                        0
                    )

            ];

        }

    } else {

        $salesDataError =
            true;

    }


    mysqli_stmt_close(
        $leastSellingStatement
    );

} else {

    $salesDataError =
        true;

}


/* =========================================================
   SALES TREND
========================================================= */

$salesTrendLabels =
    [];

$salesTrendValues =
    [];

$salesTrendGrouping =
    "day";

$salesTrendDescription =
    "Daily sales";


/* =========================================================
   DETERMINE TREND GROUPING

   Sales records intentionally use transaction DATE only.

   Today / short ranges = daily
   Year / long ranges   = monthly
========================================================= */

$rangeDayCount =
    max(
        1,
        (int) $rangeStart
            ->diff(
                $rangeEnd
            )
            ->days
        +
        1
    );


if (
    $selectedRange === "year"
    ||
    $selectedRange === "all"
    ||
    $rangeDayCount > 62
) {

    $salesTrendGrouping =
        "month";

    $salesTrendDescription =
        "Monthly sales";

} else {

    $salesTrendGrouping =
        "day";


    if (
        $rangeDayCount === 1
    ) {

        $salesTrendDescription =
            "Sales total for the selected day";

    } else {

        $salesTrendDescription =
            "Daily sales";

    }

}


/* =========================================================
   LOAD GLOBAL SALES TREND
========================================================= */

if (
    $salesTrendGrouping ===
    "day"
) {

    /* =====================================================
       DAILY SALES
    ===================================================== */

    $trendStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT

                DATE(
                    sale_date
                ) AS sales_bucket,

                COALESCE(
                    SUM(grand_total),
                    0
                ) AS sales_total

            FROM sales

            WHERE
                DATE(sale_date)
                BETWEEN ? AND ?

            GROUP BY
                DATE(sale_date)

            ORDER BY
                sales_bucket ASC
            "
        );


    if (
        $trendStatement
    ) {

        mysqli_stmt_bind_param(
            $trendStatement,
            "ss",
            $rangeStartSQL,
            $rangeEndSQL
        );


        if (
            mysqli_stmt_execute(
                $trendStatement
            )
        ) {

            $trendResult =
                mysqli_stmt_get_result(
                    $trendStatement
                );


            $trendBuckets =
                [];


            while (
                $row =
                    mysqli_fetch_assoc(
                        $trendResult
                    )
            ) {

                $bucket =
                    (string) (
                        $row[
                            "sales_bucket"
                        ]
                        ??
                        ""
                    );


                if (
                    $bucket === ""
                ) {
                    continue;
                }


                $trendBuckets[
                    $bucket
                ] =
                    (float) (
                        $row[
                            "sales_total"
                        ]
                        ??
                        0
                    );

            }


            $cursor =
                $rangeStart;


            $sameYear =
                $rangeStart->format(
                    "Y"
                )
                ===
                $rangeEnd->format(
                    "Y"
                );


            while (
                $cursor <=
                $rangeEnd
            ) {

                $bucket =
                    $cursor->format(
                        "Y-m-d"
                    );


                if (
                    $rangeDayCount === 1
                ) {

                    $salesTrendLabels[] =
                        $cursor->format(
                            "M j, Y"
                        );

                } else {

                    $salesTrendLabels[] =
                        $cursor->format(
                            $sameYear
                                ? "M j"
                                : "M j, Y"
                        );

                }


                $salesTrendValues[] =
                    round(
                        (float) (
                            $trendBuckets[
                                $bucket
                            ]
                            ??
                            0
                        ),
                        2
                    );


                $cursor =
                    $cursor->modify(
                        "+1 day"
                    );

            }

        } else {

            $salesDataError =
                true;

        }


        mysqli_stmt_close(
            $trendStatement
        );

    } else {

        $salesDataError =
            true;

    }

} else {

    /* =====================================================
       MONTHLY SALES
    ===================================================== */

    $trendStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT

                DATE_FORMAT(
                    sale_date,
                    '%Y-%m'
                ) AS sales_bucket,

                COALESCE(
                    SUM(grand_total),
                    0
                ) AS sales_total

            FROM sales

            WHERE
                DATE(sale_date)
                BETWEEN ? AND ?

            GROUP BY

                DATE_FORMAT(
                    sale_date,
                    '%Y-%m'
                )

            ORDER BY
                sales_bucket ASC
            "
        );


    if (
        $trendStatement
    ) {

        mysqli_stmt_bind_param(
            $trendStatement,
            "ss",
            $rangeStartSQL,
            $rangeEndSQL
        );


        if (
            mysqli_stmt_execute(
                $trendStatement
            )
        ) {

            $trendResult =
                mysqli_stmt_get_result(
                    $trendStatement
                );


            $trendBuckets =
                [];


            while (
                $row =
                    mysqli_fetch_assoc(
                        $trendResult
                    )
            ) {

                $bucket =
                    (string) (
                        $row[
                            "sales_bucket"
                        ]
                        ??
                        ""
                    );


                if (
                    $bucket === ""
                ) {
                    continue;
                }


                $trendBuckets[
                    $bucket
                ] =
                    (float) (
                        $row[
                            "sales_total"
                        ]
                        ??
                        0
                    );

            }


            $trendCursor =
                null;


            /*
             * All Time starts from the first month
             * containing a recorded Sales transaction.
             */

            if (
                $selectedRange ===
                "all"
            ) {

                if (
                    count(
                        $trendBuckets
                    ) > 0
                ) {

                    $firstBucket =
                        array_key_first(
                            $trendBuckets
                        );


                    $trendCursor =
                        DateTimeImmutable::
                        createFromFormat(
                            "!Y-m",
                            $firstBucket,
                            $manilaTimezone
                        );

                }

            } else {

                $trendCursor =
                    $rangeStart
                        ->modify(
                            "first day of this month"
                        );

            }


            $trendEnd =
                $rangeEnd
                    ->modify(
                        "first day of this month"
                    );


            if (
                $trendCursor
                instanceof
                DateTimeImmutable
            ) {

                while (
                    $trendCursor <=
                    $trendEnd
                ) {

                    $bucket =
                        $trendCursor
                            ->format(
                                "Y-m"
                            );


                    $salesTrendLabels[] =
                        $trendCursor
                            ->format(
                                "M Y"
                            );


                    $salesTrendValues[] =
                        round(
                            (float) (
                                $trendBuckets[
                                    $bucket
                                ]
                                ??
                                0
                            ),
                            2
                        );


                    $trendCursor =
                        $trendCursor
                            ->modify(
                                "+1 month"
                            );

                }

            }

        } else {

            $salesDataError =
                true;

        }


        mysqli_stmt_close(
            $trendStatement
        );

    } else {

        $salesDataError =
            true;

    }

}


/* =========================================================
   GLOBAL MEMBERS SUMMARY
========================================================= */

$activeMembers =
    0;

$inactiveMembers =
    0;

$totalMembers =
    0;


$memberSummaryResult =
    mysqli_query(
        $conn,
        "
        SELECT

            COUNT(*)
                AS total_members,

            SUM(
                CASE
                    WHEN LOWER(status) = 'active'
                    THEN 1
                    ELSE 0
                END
            ) AS active_members,

            SUM(
                CASE
                    WHEN LOWER(status) = 'inactive'
                    THEN 1
                    ELSE 0
                END
            ) AS inactive_members

        FROM members

        WHERE
            archived = 0
        "
    );


if (
    $memberSummaryResult
) {

    $memberSummary =
        mysqli_fetch_assoc(
            $memberSummaryResult
        );


    $totalMembers =
        (int) (
            $memberSummary[
                "total_members"
            ]
            ??
            0
        );


    $activeMembers =
        (int) (
            $memberSummary[
                "active_members"
            ]
            ??
            0
        );


    $inactiveMembers =
        (int) (
            $memberSummary[
                "inactive_members"
            ]
            ??
            0
        );

}


/* =========================================================
   GLOBAL LENDING SUMMARY
========================================================= */

$totalReceivable =
    0.00;

$pendingLoans =
    0;

$overdueLoans =
    0;


/* =========================================================
   PENDING LOANS
========================================================= */

$pendingLoans =

    dashboardSingleCount(
        $conn,
        "
        SELECT COUNT(*) AS total

        FROM monetary_loans

        WHERE
            is_archived = 0
            AND status = 'Pending'
        "
    )

    +

    dashboardSingleCount(
        $conn,
        "
        SELECT COUNT(*) AS total

        FROM product_loans

        WHERE
            is_archived = 0
            AND status = 'Pending'
        "
    );


/* =========================================================
   MONETARY RECEIVABLE
========================================================= */

$monetaryResult =
    mysqli_query(
        $conn,
        "
        SELECT

            interest_rate,
            term_days,
            outstanding_principal_amount,
            unpaid_interest_amount,
            last_interest_date,
            release_date,
            due_date,
            fully_paid_at

        FROM monetary_loans

        WHERE
            is_archived = 0
            AND status = 'Accepted'
        "
    );


if (
    $monetaryResult
) {

    while (
        $loan =
            mysqli_fetch_assoc(
                $monetaryResult
            )
    ) {

        $principal =
            round(
                (float) (
                    $loan[
                        "outstanding_principal_amount"
                    ]
                    ??
                    0
                ),
                2
            );


        $unpaidInterest =
            round(
                (float) (
                    $loan[
                        "unpaid_interest_amount"
                    ]
                    ??
                    0
                ),
                2
            );


        $interestFrom =

            !empty(
                $loan[
                    "last_interest_date"
                ]
            )

            ?

            (string) $loan[
                "last_interest_date"
            ]

            :

            (string) (
                $loan[
                    "release_date"
                ]
                ??
                ""
            );


        $elapsedDays =
            dashboardElapsedDays(
                $interestFrom,
                $today,
                $manilaTimezone
            );


        $termDays =
            max(
                1,
                (int) (
                    $loan[
                        "term_days"
                    ]
                    ??
                    1
                )
            );


        $newInterest =
            0.00;


        if (
            $principal > 0
            &&
            $elapsedDays > 0
        ) {

            $newInterest =
                round(
                    $principal
                    *
                    (
                        (float) (
                            $loan[
                                "interest_rate"
                            ]
                            ??
                            0
                        )
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


        $balance =
            round(
                $principal
                +
                $unpaidInterest
                +
                $newInterest,
                2
            );


        if (
            $balance > 0.009
        ) {

            $totalReceivable +=
                $balance;


            if (
                !empty(
                    $loan[
                        "due_date"
                    ]
                )
                &&
                $today->format(
                    "Y-m-d"
                )
                >
                $loan[
                    "due_date"
                ]
            ) {

                $overdueLoans++;

            }

        }

    }

}


/* =========================================================
   PRODUCT LOAN RECEIVABLE
========================================================= */

$productResult =
    mysqli_query(
        $conn,
        "
        SELECT

            interest_rate,
            term_days,
            outstanding_principal_amount,
            unpaid_interest_amount,
            last_interest_date,
            delivered_date,
            due_date,
            fully_paid_at

        FROM product_loans

        WHERE
            is_archived = 0
            AND status = 'Delivered'
        "
    );


if (
    $productResult
) {

    while (
        $loan =
            mysqli_fetch_assoc(
                $productResult
            )
    ) {

        $principal =
            round(
                (float) (
                    $loan[
                        "outstanding_principal_amount"
                    ]
                    ??
                    0
                ),
                2
            );


        $unpaidInterest =
            round(
                (float) (
                    $loan[
                        "unpaid_interest_amount"
                    ]
                    ??
                    0
                ),
                2
            );


        $interestFrom =

            !empty(
                $loan[
                    "last_interest_date"
                ]
            )

            ?

            (string) $loan[
                "last_interest_date"
            ]

            :

            (string) (
                $loan[
                    "delivered_date"
                ]
                ??
                ""
            );


        $elapsedDays =
            dashboardElapsedDays(
                $interestFrom,
                $today,
                $manilaTimezone
            );


        $termDays =
            max(
                1,
                (int) (
                    $loan[
                        "term_days"
                    ]
                    ??
                    1
                )
            );


        $newInterest =
            0.00;


        if (
            $principal > 0
            &&
            $elapsedDays > 0
        ) {

            $newInterest =
                round(
                    $principal
                    *
                    (
                        (float) (
                            $loan[
                                "interest_rate"
                            ]
                            ??
                            0
                        )
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


        $balance =
            round(
                $principal
                +
                $unpaidInterest
                +
                $newInterest,
                2
            );


        if (
            $balance > 0.009
        ) {

            $totalReceivable +=
                $balance;


            if (
                !empty(
                    $loan[
                        "due_date"
                    ]
                )
                &&
                $today->format(
                    "Y-m-d"
                )
                >
                $loan[
                    "due_date"
                ]
            ) {

                $overdueLoans++;

            }

        }

    }

}


$totalReceivable =
    round(
        $totalReceivable,
        2
    );


/* =========================================================
   SIDEBAR
========================================================= */

require_once
    "includes/sidebar.php";

?>


<link
rel="stylesheet"
href="/spis/css/dashboard.css?v=<?= time(); ?>">


<!-- =========================================================
     DASHBOARD READ-ONLY CARD BEHAVIOR

     Cards stay visually identical.
     They simply become non-interactive when the account
     does not have access to the corresponding module.
========================================================= -->

<style>

.dashboard-summary-card.dashboard-readonly {

    cursor:
        default;

    pointer-events:
        none;

}

</style>


<main
class="dashboard-page"
id="dashboardPage"

data-chart-labels='<?= dashboardEscape(
    json_encode(
        $chartLabels,
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    )
); ?>'

data-chart-values='<?= dashboardEscape(
    json_encode(
        $chartValues,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    )
); ?>'

data-chart-amounts='<?= dashboardEscape(
    json_encode(
        $chartAmounts,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    )
); ?>'

data-trend-labels='<?= dashboardEscape(
    json_encode(
        $salesTrendLabels,
        JSON_UNESCAPED_UNICODE |
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    )
); ?>'

data-trend-values='<?= dashboardEscape(
    json_encode(
        $salesTrendValues,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    )
); ?>'

data-trend-grouping="<?= dashboardEscape(
    $salesTrendGrouping
); ?>"

data-sales-access="1">


    <!-- =====================================================
         DASHBOARD HEADER
    ====================================================== -->

    <header class="dashboard-header">

        <div>

            <p class="dashboard-eyebrow">
                SPIS OVERVIEW
            </p>

            <h1>
                DASHBOARD
            </h1>

            <p class="dashboard-welcome">

                Welcome back,

                <strong>
                    <?= dashboardEscape(
                        $userName
                    ); ?>
                </strong>.

                Here is your cooperative overview.

            </p>

        </div>


        <div class="dashboard-header-meta">

            <span class="dashboard-role-chip">

                <i class="bi bi-person-check-fill"></i>

                <?= dashboardEscape(
                    ucwords(
                        $userRole
                    )
                ); ?>

            </span>


            <span class="dashboard-date-chip">

                <i class="bi bi-calendar3"></i>

                <?= dashboardEscape(
                    $today->format(
                        "F d, Y"
                    )
                ); ?>

            </span>

        </div>

    </header>


    <!-- =====================================================
         DATE RANGE FILTER
    ====================================================== -->

    <section class="dashboard-filter-card">

        <div class="dashboard-filter-heading">

            <div class="dashboard-filter-icon">

                <i class="bi bi-calendar-range"></i>

            </div>


            <div>

                <h2>
                    Sales period
                </h2>

                <p>

                    <?= dashboardEscape(
                        $periodLabel
                    ); ?>

                </p>

            </div>

        </div>


        <form
        method="GET"
        class="dashboard-filter-form"
        id="dashboardFilterForm">


            <div class="dashboard-range-buttons">

                <?php

                $rangeButtons = [

                    "today" =>
                        "Today",

                    "month" =>
                        "This Month",

                    "year" =>
                        "This Year",

                    "all" =>
                        "All Time"

                ];

                ?>


                <?php

                foreach (
                    $rangeButtons
                    as
                    $value
                    =>
                    $label
                ) {

                ?>

                    <a
                    href="?range=<?= dashboardEscape(
                        $value
                    ); ?>"

                    class="dashboard-range-btn <?=
                        $selectedRange
                        ===
                        $value
                            ? "active"
                            : "";
                    ?>">

                        <?= dashboardEscape(
                            $label
                        ); ?>

                    </a>

                <?php

                }

                ?>

            </div>


            <input
            type="hidden"
            name="range"
            id="dashboardRangeValue"
            value="custom">


            <details
            class="dashboard-custom-range-wrap <?=
                $selectedRange === "custom"
                    ? "is-active"
                    : "";
            ?>">

                <summary
                class="dashboard-custom-range-btn">

                    <i class="bi bi-calendar-range"></i>

                    <span>
                        Custom Range
                    </span>

                </summary>


                <div
                class="dashboard-custom-range-panel"
                id="dashboardCustomRangePanel">

                    <label>

                        <span>
                            From
                        </span>

                        <input
                        type="date"
                        name="from"
                        id="dashboardFromDate"

                        value="<?= dashboardEscape(
                            $selectedRange === "custom"
                                ? $rangeStartSQL
                                : ""
                        ); ?>"

                        max="<?= dashboardEscape(
                            $today->format(
                                "Y-m-d"
                            )
                        ); ?>"

                        autocomplete="off"
                        required>

                    </label>


                    <label>

                        <span>
                            To
                        </span>

                        <input
                        type="date"
                        name="to"
                        id="dashboardToDate"

                        value="<?= dashboardEscape(
                            $selectedRange === "custom"
                                ? $rangeEndSQL
                                : ""
                        ); ?>"

                        max="<?= dashboardEscape(
                            $today->format(
                                "Y-m-d"
                            )
                        ); ?>"

                        autocomplete="off"
                        required>

                    </label>


                    <button
                    type="submit"
                    class="dashboard-apply-range-btn">

                        Apply

                    </button>

                </div>

            </details>

        </form>

    </section>


    <!-- =====================================================
         TOP PRODUCTS AND PIE CHART
    ====================================================== -->

    <section class="dashboard-insights-grid">


        <!-- TOP PRODUCTS -->

        <article class="dashboard-ranking-card">

            <div class="dashboard-section-header">

                <div>

                    <p class="dashboard-section-kicker">
                        PRODUCT INSIGHTS
                    </p>

                    <h2>
                        Top-selling products
                    </h2>

                    <p>
                        Ranked by total units sold.
                    </p>

                </div>


                <span class="dashboard-period-pill">

                    <?= dashboardEscape(
                        $periodLabel
                    ); ?>

                </span>

            </div>


            <?php

            if (
                $salesDataError
            ) {

            ?>

                <div class="dashboard-empty-state error">

                    <i class="bi bi-exclamation-triangle"></i>

                    <strong>
                        Unable to load Sales data
                    </strong>

                    <span>
                        Please check the database connection.
                    </span>

                </div>


            <?php

            } elseif (
                count(
                    $topProducts
                )
                ===
                0
            ) {

            ?>

                <div class="dashboard-empty-state">

                    <i class="bi bi-pie-chart"></i>

                    <strong>
                        No sales recorded
                    </strong>

                    <span>
                        Try another date range.
                    </span>

                </div>


            <?php

            } else {

            ?>

                <div class="dashboard-ranking-list">

                    <?php

                    foreach (
                        $topProducts
                        as
                        $index
                        =>
                        $product
                    ) {

                        $share =

                            $topProductUnits > 0

                            ?

                            round(
                                (
                                    $product[
                                        "units"
                                    ]
                                    /
                                    $topProductUnits
                                )
                                *
                                100,
                                1
                            )

                            :

                            0;

                    ?>

                        <div class="dashboard-ranking-row">

                            <span class="dashboard-rank-number">

                                <?= $index + 1; ?>

                            </span>


                            <span
                            class="dashboard-rank-color"
                            data-chart-color-index="<?= $index; ?>">
                            </span>


                            <div class="dashboard-rank-product">

                                <strong>

                                    <?= dashboardEscape(
                                        $product[
                                            "name"
                                        ]
                                    ); ?>

                                </strong>

                                <span>

                                    <?= number_format(
                                        $product[
                                            "units"
                                        ]
                                    ); ?>

                                    units ·

                                    <?= dashboardEscape(
                                        (string) $share
                                    ); ?>%

                                </span>

                            </div>


                            <strong class="dashboard-rank-amount">

                                <?= dashboardMoney(
                                    $product[
                                        "amount"
                                    ]
                                ); ?>

                            </strong>

                        </div>

                    <?php

                    }

                    ?>

                </div>

            <?php

            }

            ?>

        </article>


        <!-- PIE CHART -->

        <article class="dashboard-chart-card">

            <div class="dashboard-section-header compact">

                <div>

                    <p class="dashboard-section-kicker">
                        SALES DISTRIBUTION
                    </p>

                    <h2>
                        Product sales share
                    </h2>

                    <p>
                        Hover over each slice for details.
                    </p>

                </div>


                <div class="dashboard-chart-total">

                    <span>
                        Total units
                    </span>

                    <strong>

                        <?= number_format(
                            $topProductUnits
                        ); ?>

                    </strong>

                </div>

            </div>


            <div class="dashboard-chart-wrap">

                <canvas
                id="dashboardSalesChart"
                aria-label="Top-selling products pie chart"
                role="img">
                </canvas>


                <div
                class="dashboard-chart-fallback"
                id="dashboardChartFallback"
                hidden>

                    No chart data available.

                </div>

            </div>

        </article>

    </section>


    <!-- =====================================================
         SUMMARY CARDS
    ====================================================== -->

    <section class="dashboard-summary-grid">


        <!-- =================================================
             SALES CARD
        ================================================== -->

        <?php

        if (
            $hasSalesAccess
        ) {

        ?>

            <a
            href="/spis/sales/sales.php"
            class="dashboard-summary-card sales">

        <?php

        } else {

        ?>

            <div
            class="
                dashboard-summary-card
                sales
                dashboard-readonly
            "
            aria-label="Sales summary">

        <?php

        }

        ?>


                <div class="dashboard-summary-icon">

                    <i class="bi bi-cart-check-fill"></i>

                </div>


                <div class="dashboard-summary-content">

                    <span>
                        Total Sales
                    </span>

                    <strong>

                        <?= dashboardMoney(
                            $totalSales
                        ); ?>

                    </strong>

                    <small>

                        <?= number_format(
                            $totalTransactions
                        ); ?>

                        transactions ·

                        <?= dashboardEscape(
                            $periodLabel
                        ); ?>

                    </small>

                </div>


                <?php

                if (
                    $hasSalesAccess
                ) {

                ?>

                    <i
                    class="
                        bi
                        bi-arrow-up-right
                        dashboard-card-arrow
                    ">
                    </i>

                <?php

                }

                ?>


        <?php

        if (
            $hasSalesAccess
        ) {

        ?>

            </a>

        <?php

        } else {

        ?>

            </div>

        <?php

        }

        ?>


        <!-- =================================================
             LENDING CARD
        ================================================== -->

        <?php

        if (
            $hasLendingAccess
        ) {

        ?>

            <a
            href="/spis/lending/lending.php"

            class="
                dashboard-summary-card
                lending
                <?= $overdueLoans > 0
                    ? "has-alert"
                    : "";
                ?>
            ">

        <?php

        } else {

        ?>

            <div
            class="
                dashboard-summary-card
                lending
                dashboard-readonly
                <?= $overdueLoans > 0
                    ? "has-alert"
                    : "";
                ?>
            "
            aria-label="Lending summary">

        <?php

        }

        ?>


                <div class="dashboard-summary-icon">

                    <i class="bi bi-cash-coin"></i>

                </div>


                <div class="dashboard-summary-content">

                    <span>
                        Amount Receivable
                    </span>

                    <strong>

                        <?= dashboardMoney(
                            $totalReceivable
                        ); ?>

                    </strong>

                    <small>

                        <?= number_format(
                            $pendingLoans
                        ); ?>

                        pending ·

                        <?= number_format(
                            $overdueLoans
                        ); ?>

                        overdue

                    </small>

                </div>


                <?php

                if (
                    $overdueLoans > 0
                ) {

                ?>

                    <span
                    class="dashboard-alert-signal"
                    title="Overdue loans">
                    </span>

                <?php

                }

                ?>


                <?php

                if (
                    $hasLendingAccess
                ) {

                ?>

                    <i
                    class="
                        bi
                        bi-arrow-up-right
                        dashboard-card-arrow
                    ">
                    </i>

                <?php

                }

                ?>


        <?php

        if (
            $hasLendingAccess
        ) {

        ?>

            </a>

        <?php

        } else {

        ?>

            </div>

        <?php

        }

        ?>


        <!-- =================================================
             MEMBERS CARD
        ================================================== -->

        <?php

        if (
            $hasMembersAccess
        ) {

        ?>

            <a
            href="/spis/member/member.php"
            class="dashboard-summary-card members">

        <?php

        } else {

        ?>

            <div
            class="
                dashboard-summary-card
                members
                dashboard-readonly
            "
            aria-label="Members summary">

        <?php

        }

        ?>


                <div class="dashboard-summary-icon">

                    <i class="bi bi-people-fill"></i>

                </div>


                <div class="dashboard-summary-content">

                    <span>
                        Active Members
                    </span>

                    <strong>

                        <?= number_format(
                            $activeMembers
                        ); ?>

                    </strong>

                    <small>

                        <?= number_format(
                            $totalMembers
                        ); ?>

                        registered ·

                        <?= number_format(
                            $inactiveMembers
                        ); ?>

                        inactive

                    </small>

                </div>


                <?php

                if (
                    $hasMembersAccess
                ) {

                ?>

                    <i
                    class="
                        bi
                        bi-arrow-up-right
                        dashboard-card-arrow
                    ">
                    </i>

                <?php

                }

                ?>


        <?php

        if (
            $hasMembersAccess
        ) {

        ?>

            </a>

        <?php

        } else {

        ?>

            </div>

        <?php

        }

        ?>


    </section>


    <!-- =====================================================
         ADDITIONAL SALES INSIGHTS
    ====================================================== -->

    <section class="dashboard-secondary-insights-grid">


        <!-- =================================================
             LEAST-SELLING PRODUCTS
        ================================================== -->

        <article class="dashboard-slow-card">

            <div class="dashboard-section-header">

                <div>

                    <p class="dashboard-section-kicker">
                        SALES INSIGHTS
                    </p>

                    <h2>
                        Least-selling products
                    </h2>

                    <p>
                        Products with the lowest units sold.
                    </p>

                </div>


                <span class="dashboard-period-pill">

                    <?= dashboardEscape(
                        $periodLabel
                    ); ?>

                </span>

            </div>


            <?php

            if (
                $salesDataError
            ) {

            ?>

                <div class="dashboard-empty-state error">

                    <i class="bi bi-exclamation-triangle"></i>

                    <strong>
                        Unable to load Sales data
                    </strong>

                    <span>
                        Please check the database connection.
                    </span>

                </div>


            <?php

            } elseif (
                count(
                    $leastSellingProducts
                )
                ===
                0
            ) {

            ?>

                <div class="dashboard-empty-state">

                    <i class="bi bi-box-seam"></i>

                    <strong>
                        No products available
                    </strong>

                    <span>
                        No active Inventory products were found.
                    </span>

                </div>


            <?php

            } else {

            ?>

                <div class="dashboard-slow-list">

                    <?php

                    foreach (
                        $leastSellingProducts
                        as
                        $index
                        =>
                        $product
                    ) {

                    ?>

                        <div class="dashboard-slow-row">

                            <div class="dashboard-slow-position">

                                <?= $index + 1; ?>

                            </div>


                            <div class="dashboard-slow-product">

                                <strong>

                                    <?= dashboardEscape(
                                        $product[
                                            "name"
                                        ]
                                    ); ?>

                                </strong>


                                <span>

                                    <?= number_format(
                                        $product[
                                            "units"
                                        ]
                                    ); ?>

                                    <?= $product["units"] === 1
                                        ? "unit sold"
                                        : "units sold";
                                    ?>

                                </span>

                            </div>


                            <strong class="dashboard-slow-amount">

                                <?= dashboardMoney(
                                    $product[
                                        "amount"
                                    ]
                                ); ?>

                            </strong>

                        </div>

                    <?php

                    }

                    ?>

                </div>

            <?php

            }

            ?>

        </article>


        <!-- =================================================
             SALES TREND
        ================================================== -->

        <article class="dashboard-trend-card">

            <div class="dashboard-section-header compact">

                <div>

                    <p class="dashboard-section-kicker">
                        SALES PERFORMANCE
                    </p>

                    <h2>
                        Sales trend
                    </h2>

                    <p>

                        <?= dashboardEscape(
                            $salesTrendDescription
                        ); ?>

                        based on recorded Sales transactions.

                    </p>

                </div>


                <span class="dashboard-period-pill">

                    <?= dashboardEscape(
                        $periodLabel
                    ); ?>

                </span>

            </div>


            <div class="dashboard-trend-wrap">

                <canvas
                id="dashboardSalesTrendChart"
                aria-label="Sales trend chart"
                role="img">
                </canvas>


                <div
                class="dashboard-trend-fallback"
                id="dashboardTrendFallback"
                hidden>

                    No Sales trend data available.

                </div>

            </div>

        </article>


    </section>


</main>


<!-- =========================================================
     OFFLINE DASHBOARD SCRIPTS
========================================================= -->

<script src="/spis/assets/chartjs/chart.umd.min.js"></script>

<script src="/spis/js/dashboard.js?v=<?= time(); ?>"></script>


<!-- CLOSE SIDEBAR MAIN CONTENT AND WRAPPER -->

</div>

</div>

</body>

</html>