<?php

/* =========================================================
   PRODUCT LOAN
========================================================= */

if (
    session_status() ===
    PHP_SESSION_NONE
) {
    session_start();
}

date_default_timezone_set(
    "Asia/Manila"
);

error_reporting(E_ALL);
ini_set(
    "display_errors",
    "0"
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

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   AUTHENTICATION AND MODULE ACCESS
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {

    header(
        "Location: /spis/login.php"
    );

    exit();

}

if (
    (int) (
        $_SESSION["lending_access"]
        ?? 0
    ) !== 1
) {

    header(
        "Location: /spis/dashboard.php?access=denied"
    );

    exit();

}


/* =========================================================
   CSRF TOKEN FOUNDATION
========================================================= */

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($_SESSION["csrf_token"])
) {

    $_SESSION["csrf_token"] =
        bin2hex(
            random_bytes(32)
        );

}

$csrfToken =
    $_SESSION["csrf_token"];


/* =========================================================
   DATABASE
========================================================= */

require_once "../../db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {

    http_response_code(500);

    exit(
        "Database connection is unavailable."
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
   HELPERS
========================================================= */

function productLoanEscape(
    $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );

}


function productLoanMoney(
    $value
): string {

    return "₱" .
        number_format(
            (float) $value,
            2
        );

}


function productLoanDisplayDate(
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


function productLoanElapsedDays(
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

        $from =
            new DateTimeImmutable(
                $fromDate,
                $timezone
            );

    } catch (Throwable $exception) {

        return 0;

    }

    if ($today <= $from) {
        return 0;
    }

    return (int) $from
        ->diff($today)
        ->days;

}


/* =========================================================
   LENDING PERMISSION
========================================================= */

$lendingPermission =
    "limited";

if ($userID > 0) {

    $permissionStatement =
        mysqli_prepare(
            $conn,
            "
            SELECT lending_permission

            FROM users

            WHERE user_id = ?

            LIMIT 1
            "
        );

    if ($permissionStatement) {

        mysqli_stmt_bind_param(
            $permissionStatement,
            "i",
            $userID
        );

        mysqli_stmt_execute(
            $permissionStatement
        );

        $permissionResult =
            mysqli_stmt_get_result(
                $permissionStatement
            );

        $permissionRow =
            mysqli_fetch_assoc(
                $permissionResult
            );

        if (
            strtolower(
                trim(
                    $permissionRow[
                        "lending_permission"
                    ]
                    ?? ""
                )
            ) === "full"
        ) {

            $lendingPermission =
                "full";

        }

        mysqli_stmt_close(
            $permissionStatement
        );

    }

}


/* =========================================================
   RETRIEVE PRODUCT LOANS
========================================================= */

$loanQuery =
    mysqli_query(
        $conn,
        "
        SELECT

            loan.product_loan_id,
            loan.member_id,
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
            loan.created_at,

            member.member_code,
            member.full_name,

            item_summary.product_names,
            item_summary.total_quantity

        FROM product_loans AS loan

        INNER JOIN members AS member
            ON member.member_id =
               loan.member_id

        LEFT JOIN (

            SELECT

                product_loan_id,

                GROUP_CONCAT(
                    product_name
                    ORDER BY
                        product_loan_item_id
                    SEPARATOR '|||'
                ) AS product_names,

                SUM(quantity) AS total_quantity

            FROM product_loan_items

            GROUP BY product_loan_id

        ) AS item_summary

            ON item_summary.product_loan_id =
               loan.product_loan_id

        WHERE
            loan.is_archived = 0

        ORDER BY
            loan.product_loan_id DESC
        "
    );


/* =========================================================
   SERVER DATE
========================================================= */

$manilaTimezone =
    new DateTimeZone(
        "Asia/Manila"
    );

$today =
    new DateTimeImmutable(
        date("Y-m-d"),
        $manilaTimezone
    );


/* =========================================================
   SIDEBAR
========================================================= */

require_once "../../includes/sidebar.php";

?>

<link
rel="stylesheet"
href="/spis/css/lending/product_loan.css?v=<?php echo time(); ?>">

<link
rel="stylesheet"
href="/spis/css/lending/edit_product_loan.css?v=<?php echo time(); ?>">

<link
rel="stylesheet"
href="generate_product_loan/report_product_loan.css?v=<?php echo time(); ?>">


<!-- =======================================================
     PAGE HEADER
======================================================== -->

<div class="page-header">

    <h2>
        LENDING - PRODUCT LOAN
    </h2>


    <div class="product-loan-header-actions">

        <!-- PRODUCT LIST -->

        <a
        href="product_list/index.php"
        class="btn-product-loan-list">

            <i class="bi bi-box-seam-fill"></i>

            PRODUCT LIST

        </a>


        <!-- GENERATE REPORT -->

        <button
        type="button"
        id="generateProductLoanReportBtn"
        class="btn-generate-product-loan-report"
        data-bs-toggle="modal"
        data-bs-target="#generateProductLoanReportModal">

            <i class="bi bi-printer-fill"></i>

            GENERATE REPORT

        </button>

    </div>

</div>

<hr>


<!-- =======================================================
     TOOLBAR
======================================================== -->

<div class="product-loan-toolbar">

    <div class="product-loan-toolbar-left">

        <a
        href="../lending.php"
        class="product-loan-back-btn"
        title="Back to Lending">

            <i class="bi bi-arrow-left"></i>

        </a>


        <div class="product-loan-search-box">

            <i class="bi bi-search"></i>

            <input
            type="text"
            id="searchProductLoan"
            placeholder="Search"
            autocomplete="off">

        </div>

    </div>


    <div class="product-loan-toolbar-right">

        <button
        type="button"
        class="product-loan-date-filter-btn"
        data-bs-toggle="modal"
        data-bs-target="#productLoanDateFilterModal"
        title="Filter application date">

            <i class="bi bi-calendar3"></i>

        </button>


        <select
        id="productLoanInterestFilter"
        class="product-loan-filter">

            <option value="all">
                All Interest
            </option>

            <option value="10">
                10% - 180 Days
            </option>

            <option value="20">
                20% - 360 Days
            </option>

        </select>


        <select
        id="productLoanStatusFilter"
        class="product-loan-filter">

            <option value="all">
                All Status
            </option>

            <option value="Pending">
                Pending
            </option>

            <option value="Delivered">
                Delivered
            </option>

            <option value="Rejected">
                Rejected
            </option>

        </select>


        <select
        id="productLoanRemarksFilter"
        class="product-loan-filter">

            <option value="all">
                All Remarks
            </option>

            <option value="On Going">
                On Going
            </option>

            <option value="Overdue">
                Overdue
            </option>

            <option value="Paid">
                Paid
            </option>

        </select>


        <button
        type="button"
        class="btn-add-product-loan"
        data-bs-toggle="modal"
        data-bs-target="#addProductLoanModal">

            ADD PRODUCT LOAN

        </button>

    </div>

</div>


<!-- =======================================================
     PRODUCT LOAN LEDGER
======================================================== -->

<div
class="product-loan-ledger"
id="productLoanPageData"
data-lending-permission="<?php
echo productLoanEscape(
    $lendingPermission
);
?>"
data-server-date="<?php
echo date("Y-m-d");
?>"
data-csrf-token="<?php
echo productLoanEscape(
    $csrfToken
);
?>">


    <!-- ===================================================
         LEDGER HEADER
    ==================================================== -->

    <div class="product-loan-ledger-header">

        <div>
            LOAN ID
        </div>

        <div>
            MEMBER
        </div>

        <div>
            PRODUCTS
        </div>

        <div>
            PRINCIPAL
        </div>

        <div>
            INTEREST
        </div>

        <div>
            TOTAL AMOUNT
        </div>

        <div>
            BALANCE
        </div>

        <div>
            DUE DATE
        </div>

        <div>
            STATUS
        </div>

        <div>
            REMARKS
        </div>

        <div>
            ACTIONS
        </div>

    </div>


    <!-- ===================================================
         LEDGER BODY
    ==================================================== -->

    <div class="product-loan-ledger-body">

        <?php

        if (
            $loanQuery &&
            mysqli_num_rows(
                $loanQuery
            ) > 0
        ) {

            $rowNumber = 0;

            while (
                $loan =
                    mysqli_fetch_assoc(
                        $loanQuery
                    )
            ) {

                $rowNumber++;

                $status =
                    $loan["status"];

                $principalBalance =
                    round(
                        (float)
                        $loan[
                            "outstanding_principal_amount"
                        ],
                        2
                    );

                $unpaidInterest =
                    round(
                        (float)
                        $loan[
                            "unpaid_interest_amount"
                        ],
                        2
                    );

                $newAccruedInterest =
                    0.00;

                $currentBalance =
                    null;

                $remarks =
                    "—";

                if (
                    $status ===
                    "Delivered"
                ) {

                    $interestFromDate =
                        $loan[
                            "last_interest_date"
                        ]
                        ?:
                        $loan[
                            "delivered_date"
                        ];

                    $elapsedDays =
                        productLoanElapsedDays(
                            $interestFromDate,
                            $today,
                            $manilaTimezone
                        );

                    if (
                        $principalBalance > 0 &&
                        $elapsedDays > 0
                    ) {

                        $newAccruedInterest =
                            round(
                                $principalBalance *
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

                    $currentBalance =
                        round(
                            $principalBalance +
                            $unpaidInterest +
                            $newAccruedInterest,
                            2
                        );

                    if (
                        $principalBalance <= 0.009 &&
                        $unpaidInterest <= 0.009
                    ) {

                        $remarks =
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

                        if (
                            $dueDateObject !== null &&
                            $today > $dueDateObject
                        ) {

                            $remarks =
                                "Overdue";

                        } else {

                            $remarks =
                                "On Going";

                        }

                    }

                }

                if (
                    $status === "Pending" ||
                    $status === "Rejected"
                ) {

                    $currentBalance =
                        null;

                    $remarks =
                        "—";

                }


                /* -----------------------------------------
                   PRODUCT NAMES
                ----------------------------------------- */

                $productNames = [];

                if (
                    !empty(
                        $loan[
                            "product_names"
                        ]
                    )
                ) {

                    $productNames =
                        explode(
                            "|||",
                            $loan[
                                "product_names"
                            ]
                        );

                }


                /* -----------------------------------------
                   CLASSES
                ----------------------------------------- */

                $statusClass =
                    "product-loan-status ";

                switch ($status) {

                    case "Pending":

                        $statusClass .=
                            "product-loan-status-pending";

                        break;

                    case "Delivered":

                        $statusClass .=
                            "product-loan-status-delivered";

                        break;

                    case "Rejected":

                        $statusClass .=
                            "product-loan-status-rejected";

                        break;

                }

                $remarksClass =
                    "product-loan-remarks ";

                switch ($remarks) {

                    case "Paid":

                        $remarksClass .=
                            "product-loan-remarks-paid";

                        break;

                    case "On Going":

                        $remarksClass .=
                            "product-loan-remarks-ongoing";

                        break;

                    case "Overdue":

                        $remarksClass .=
                            "product-loan-remarks-overdue";

                        break;

                    default:

                        $remarksClass .=
                            "product-loan-remarks-none";

                        break;

                }

                $rowStateClass = "";

                if ($remarks === "Paid") {

                    $rowStateClass =
                        "product-loan-row-paid";

                } elseif (
                    $remarks === "Overdue"
                ) {

                    $rowStateClass =
                        "product-loan-row-overdue";

                } elseif (
                    $status === "Rejected"
                ) {

                    $rowStateClass =
                        "product-loan-row-rejected";

                }

                $canArchive =
                    (
                        $status === "Rejected" ||
                        $remarks === "Paid"
                    );

                $loanDisplayID =
                    "PL-" .
                    str_pad(
                        (string)
                        $loan[
                            "product_loan_id"
                        ],
                        6,
                        "0",
                        STR_PAD_LEFT
                    );

                $searchProductText =
                    implode(
                        " ",
                        $productNames
                    );

                $searchData =
                    strtolower(
                        $loanDisplayID .
                        " " .
                        (
                            $loan[
                                "member_code"
                            ]
                            ?? ""
                        ) .
                        " " .
                        (
                            $loan[
                                "full_name"
                            ]
                            ?? ""
                        ) .
                        " " .
                        $searchProductText .
                        " " .
                        $status .
                        " " .
                        $remarks
                    );

        ?>

            <div
            class="
                product-loan-row
                <?php
                echo $rowNumber === 1
                    ? "first-row"
                    : "";
                ?>
                <?php
                echo productLoanEscape(
                    $rowStateClass
                );
                ?>
            "
            data-loan-id="<?php
            echo (int)
                $loan[
                    "product_loan_id"
                ];
            ?>"
            data-search="<?php
            echo productLoanEscape(
                $searchData
            );
            ?>"
            data-interest="<?php
            echo productLoanEscape(
                number_format(
                    (float)
                    $loan[
                        "interest_rate"
                    ],
                    0,
                    ".",
                    ""
                )
            );
            ?>"
            data-status="<?php
            echo productLoanEscape(
                $status
            );
            ?>"
            data-remarks="<?php
            echo productLoanEscape(
                $remarks
            );
            ?>"
            data-application-date="<?php
            echo productLoanEscape(
                $loan[
                    "application_date"
                ]
            );
            ?>">


                <!-- LOAN ID -->

                <div class="product-loan-id-cell">

                    <strong>

                        <?php
                        echo productLoanEscape(
                            $loanDisplayID
                        );
                        ?>

                    </strong>

                    <small>

                        <?php
                        echo productLoanDisplayDate(
                            $loan[
                                "application_date"
                            ]
                        );
                        ?>

                    </small>

                    <?php

                    if (
                        $remarks ===
                        "Overdue"
                    ) {

                    ?>

                        <span class="product-loan-id-overdue-badge">

                            <i class="bi bi-calendar-x"></i>

                            Overdue

                        </span>

                    <?php

                    }

                    ?>

                </div>


                <!-- MEMBER -->

                <div class="product-loan-member-cell">

                    <strong>

                        <?php
                        echo productLoanEscape(
                            $loan[
                                "full_name"
                            ]
                        );
                        ?>

                    </strong>

                    <?php

                    if (
                        !empty(
                            $loan[
                                "member_code"
                            ]
                        )
                    ) {

                    ?>

                        <small>

                            <?php
                            echo productLoanEscape(
                                $loan[
                                    "member_code"
                                ]
                            );
                            ?>

                        </small>

                    <?php

                    }

                    ?>

                </div>


                <!-- PRODUCTS -->

                <div class="product-loan-products-cell">

                    <?php

                    if (
                        count(
                            $productNames
                        ) === 0
                    ) {

                        echo "—";

                    } else {

                        foreach (
                            $productNames
                            as $productName
                        ) {

                    ?>

                            <span>

                                <?php
                                echo productLoanEscape(
                                    $productName
                                );
                                ?>

                            </span>

                    <?php

                        }

                    }

                    ?>

                </div>


                <!-- PRINCIPAL -->

                <div>

                    <?php
                    echo productLoanMoney(
                        $loan[
                            "principal_amount"
                        ]
                    );
                    ?>

                </div>


                <!-- INTEREST -->

                <div class="product-loan-interest-cell">

                    <strong>

                        <?php
                        echo number_format(
                            (float)
                            $loan[
                                "interest_rate"
                            ],
                            0
                        );
                        ?>%

                    </strong>

                    <small>

                        <?php
                        echo (int)
                            $loan[
                                "term_days"
                            ];
                        ?> Days

                    </small>

                </div>


                <!-- TOTAL AMOUNT -->

                <div>

                    <?php
                    echo productLoanMoney(
                        $loan[
                            "scheduled_total_amount"
                        ]
                    );
                    ?>

                </div>


                <!-- BALANCE -->

                <div>

                    <?php

                    if (
                        $currentBalance ===
                        null
                    ) {

                        echo "—";

                    } else {

                        echo productLoanMoney(
                            $currentBalance
                        );

                    }

                    ?>

                </div>


                <!-- DUE DATE -->

                <div>

                    <?php

                    if (
                        $status ===
                        "Pending"
                    ) {

                        echo
                            "Upon delivery";

                    } elseif (
                        $status ===
                        "Rejected"
                    ) {

                        echo "—";

                    } else {

                        echo productLoanDisplayDate(
                            $loan[
                                "due_date"
                            ]
                        );

                    }

                    ?>

                </div>


                <!-- STATUS -->

                <div>

                    <span class="<?php
                    echo productLoanEscape(
                        $statusClass
                    );
                    ?>">

                        <?php
                        echo productLoanEscape(
                            $status
                        );
                        ?>

                    </span>

                </div>


                <!-- REMARKS -->

                <div>

                    <span class="<?php
                    echo productLoanEscape(
                        $remarksClass
                    );
                    ?>">

                        <?php
                        echo productLoanEscape(
                            $remarks
                        );
                        ?>

                    </span>

                </div>


                <!-- ACTIONS -->

                <div class="product-loan-actions-cell">

                    <button
                    type="button"
                    class="product-loan-row-action product-loan-edit-btn"
                    data-loan-id="<?php
                    echo (int)
                        $loan[
                            "product_loan_id"
                        ];
                    ?>"
                    title="<?php
                    echo $status === "Pending"
                        ? "Edit Product Loan"
                        : "View Product Loan";
                    ?>">

                        <i class="bi bi-pencil-fill"></i>

                    </button>


                    <button
                    type="button"
                    class="product-loan-row-action product-loan-archive-btn"
                    data-loan-id="<?php
                    echo (int)
                        $loan[
                            "product_loan_id"
                        ];
                    ?>"
                    title="<?php
                    echo $canArchive
                        ? "Archive Product Loan"
                        : "Only Paid or Rejected loans can be archived";
                    ?>"
                    <?php
                    echo $canArchive
                        ? ""
                        : "disabled";
                    ?>>

                        <i class="bi bi-archive-fill"></i>

                    </button>

                </div>

            </div>

        <?php

            }

        } else {

        ?>

            <div class="product-loan-empty">

                No Product Loan records found.

            </div>

        <?php

        }

        ?>


        <div
        id="productLoanFilteredEmpty"
        class="product-loan-empty"
        style="display:none;">

            No Product Loan records match the selected filters.

        </div>

    </div>

</div>


<!-- =======================================================
     DATE FILTER MODAL
======================================================== -->

<div
class="modal fade"
id="productLoanDateFilterModal"
tabindex="-1"
aria-hidden="true">

    <div
    class="modal-dialog modal-dialog-centered">

        <div
        class="modal-content product-loan-date-window">

            <div class="product-loan-date-header">

                <h1>
                    FILTER APPLICATION DATE
                </h1>


                <button
                type="button"
                class="product-loan-date-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close">

                    ×

                </button>

            </div>

            <hr>


            <div class="product-loan-date-field">

                <label for="productLoanStartDateFilter">

                    START DATE

                </label>


                <input
                type="date"
                id="productLoanStartDateFilter"
                class="product-loan-date-input">

            </div>


            <div class="product-loan-date-field">

                <label for="productLoanEndDateFilter">

                    END DATE

                </label>


                <input
                type="date"
                id="productLoanEndDateFilter"
                class="product-loan-date-input">

            </div>


            <div class="product-loan-date-actions">

                <button
                type="button"
                id="productLoanClearDateFilter"
                class="product-loan-clear-date-btn"
                data-bs-dismiss="modal">

                    CLEAR FILTER

                </button>


                <button
                type="button"
                id="productLoanApplyDateFilter"
                class="product-loan-apply-date-btn"
                data-bs-dismiss="modal">

                    APPLY

                </button>

            </div>

        </div>

    </div>

</div>


<?php

require_once
    "add_product_loan_modal.php";

require_once
    "edit_product_loan_modal.php";

require_once
    "generate_product_loan/report_product_loan_modal.php";

?>

<script src="generate_product_loan/report_product_loan.js?v=<?php echo time(); ?>"></script>

<script src="../../js/product_loan.js?v=<?php echo time(); ?>"></script>

<script src="../../js/edit_product_loan.js?v=<?php echo time(); ?>"></script>

<script src="../../js/product_loan_ledger.js?v=<?php echo time(); ?>"></script>

</body>
</html>