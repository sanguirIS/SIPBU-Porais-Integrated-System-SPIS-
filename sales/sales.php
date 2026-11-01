<?php

/* =========================================================
   SALES
========================================================= */

error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);


date_default_timezone_set(
    "Asia/Manila"
);


/* =========================================================
   DATABASE
========================================================= */

require_once "../db.php";


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);

/* =========================================================
   ACCESS
========================================================= */

require_once "../includes/check_access.php";

checkAccess(
    "sales_access"
);


/* =========================================================
   SALES CSRF TOKEN
========================================================= */

if (
    empty(
        $_SESSION[
            "sales_csrf_token"
        ]
    )
) {

    $_SESSION[
        "sales_csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );

}


/* =========================================================
   HELPERS
========================================================= */

function salesEscape(
    mixed $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );

}


function salesMoney(
    mixed $value
): string {

    return "₱" .
        number_format(
            (float) $value,
            2
        );

}


/* =========================================================
   SALES RECORDS
========================================================= */

$salesSql = "
    SELECT

        s.sale_id,
        s.transaction_id,
        s.buyer_name,
        s.affiliate,
        s.member_id,
        s.sale_date,
        s.is_historical,
        s.grand_total,
        s.cash_received,
        s.change_amount,

        m.member_code

    FROM sales AS s

    LEFT JOIN members AS m
        ON m.member_id = s.member_id

    ORDER BY
        s.sale_id DESC
";


$salesResult =
    mysqli_query(
        $conn,
        $salesSql
    );


if (!$salesResult) {

    die(
        "Unable to load sales records."
    );

}


/* =========================================================
   LOAD SALES ITEMS
========================================================= */

$itemsBySale = [];


$itemsSql = "
    SELECT

        si.sale_id,
        si.product_id,
        si.quantity,
        si.subtotal,

        i.product_name

    FROM sales_items AS si

    LEFT JOIN inventory AS i
        ON i.product_id = si.product_id

    ORDER BY

        si.sale_id DESC,
        i.product_name ASC
";


$itemsResult =
    mysqli_query(
        $conn,
        $itemsSql
    );


if (!$itemsResult) {

    die(
        "Unable to load sales items."
    );

}


while (
    $item =
        mysqli_fetch_assoc(
            $itemsResult
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


    if ($saleID <= 0) {

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
   PAGE LAYOUT
========================================================= */

include "../includes/sidebar.php";

include "add_sales_modal.php";

include "date_filter_modal.php";


/*
Automatically load the Generate Sales Report modal
once the file exists.

This prevents warnings while we are building the
report feature one file at a time.
*/

$generateSalesReportModalFile =
    __DIR__ .
    "/generate_sales_report_modal.php";


if (
    is_file(
        $generateSalesReportModalFile
    )
) {

    include
        $generateSalesReportModalFile;

}

?>


<link
rel="stylesheet"
href="../css/sales.css?v=<?= time(); ?>">


<link
rel="stylesheet"
href="../css/sales_modal.css?v=<?= time(); ?>">


<?php

/*
Automatically load the upcoming Sales Report modal CSS.
*/

$generateSalesReportCSSFile =
    dirname(
        __DIR__
    ) .
    "/css/sales_report_modal.css";


if (
    is_file(
        $generateSalesReportCSSFile
    )
) {

?>

<link
rel="stylesheet"
href="../css/sales_report_modal.css?v=<?= time(); ?>">

<?php

}

?>


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="page-header">


    <h2>
        SALES
    </h2>


    <!-- =====================================================
         GENERATE REPORT
    ====================================================== -->

    <button
    type="button"
    id="generateSalesReportBtn"
    class="
        btn-add-sales
        d-flex
        justify-content-center
        align-items-center
        gap-2
    "
    data-bs-toggle="modal"
    data-bs-target="#generateSalesReportModal">

        <i class="bi bi-printer-fill"></i>

        <span>
            GENERATE REPORT
        </span>

    </button>


</div>


<hr>


<!-- =========================================================
     TOOLBAR
========================================================= -->

<div class="sales-toolbar">


    <!-- LEFT SIDE -->

    <div class="toolbar-left">


        <!-- SEARCH -->

        <div class="search-box">

            <i class="bi bi-search"></i>

            <input
            type="text"
            id="searchSales"
            placeholder="Search"
            autocomplete="off">

        </div>


        <!-- LEGACY HIDDEN DATE -->

        <input
        type="date"
        id="salesDateFilter"
        style="display:none;">


        <!-- TYPE FILTER -->

        <select
        id="affiliateFilter"
        class="affiliate-filter">

            <option value="all">
                All
            </option>

            <option value="member">
                Member
            </option>

            <option value="not member">
                Not Member
            </option>

        </select>


        <!-- DATE FILTER -->

        <button
        type="button"
        class="btn-calendar"
        data-bs-toggle="modal"
        data-bs-target="#dateFilterModal"
        aria-label="Filter sales by date"
        title="Filter by date">

            <i class="bi bi-calendar3"></i>

        </button>

    </div>


    <!-- RIGHT SIDE -->

    <div class="sales-toolbar-actions">


        <!-- ADD SALE -->

        <button
        type="button"
        class="
            btn-add-sales
            d-flex
            justify-content-center
            align-items-center
            gap-2
        "
        data-bs-toggle="modal"
        data-bs-target="#addSalesModal">

            <i class="bi bi-cart-plus-fill"></i>

            <span>
                ADD SALES
            </span>

        </button>


    </div>

</div>


<!-- =========================================================
     LEGACY EXPORT HOOK

     Keep this hidden for now so the existing sales.js
     remains compatible while Generate Report is being built.
     It is no longer visible to the user.
========================================================= -->

<a
id="exportPDFBtn"
href="#"
hidden
tabindex="-1"
aria-hidden="true">
</a>


<!-- =========================================================
     SALES LEDGER
========================================================= -->

<div class="ledger">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="ledger-header sales-ledger-grid">

        <div>
            TRANSACTION
        </div>

        <div>
            BUYER
        </div>

        <div>
            PRODUCTS
        </div>

        <div>
            QTY
        </div>

        <div>
            TOTAL
        </div>

        <div>
            CASH RECEIVED
        </div>

        <div>
            CHANGE
        </div>

        <div>
            TYPE
        </div>

    </div>


    <!-- =====================================================
         BODY
    ====================================================== -->

    <div class="ledger-body">


        <?php

        if (
            mysqli_num_rows(
                $salesResult
            ) === 0
        ) {

        ?>


            <div class="sales-ledger-empty">

                <div class="sales-ledger-empty-icon">

                    <i class="bi bi-receipt"></i>

                </div>


                <strong>
                    No sales transactions yet
                </strong>


                <span>
                    Recorded sales will appear here.
                </span>

            </div>


        <?php

        } else {

        ?>


            <?php

            while (
                $sale =
                    mysqli_fetch_assoc(
                        $salesResult
                    )
            ) {

            ?>


                <?php

                /* =============================================
                   BASIC SALE DATA
                ============================================= */

                $saleID =
                    (int) (
                        $sale[
                            "sale_id"
                        ]
                        ??
                        0
                    );


                /*
                UI ID follows the same compact convention
                used by Lending:

                PL-000001
                ML-000001

                Sales:
                SL-000001
                */

                $salesDisplayID =
                    "SL-" .
                    str_pad(
                        (string) $saleID,
                        6,
                        "0",
                        STR_PAD_LEFT
                    );


                /*
                Keep original transaction ID internally.
                */

                $transactionID =
                    trim(
                        (string) (
                            $sale[
                                "transaction_id"
                            ]
                            ??
                            ""
                        )
                    );


                $affiliate =
                    trim(
                        (string) (
                            $sale[
                                "affiliate"
                            ]
                            ??
                            ""
                        )
                    );


                $affiliateKey =
                    strtolower(
                        $affiliate
                    );


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


                /* =============================================
                   DATE
                ============================================= */

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


                /* =============================================
                   HISTORICAL / AUDIT ENTRY

                   Permanent flag saved when the transaction
                   was originally recorded.
                ============================================= */

                $isHistoricalEntry =
                    (
                        (int) (
                            $sale[
                                "is_historical"
                            ]
                            ??
                            0
                        )
                        ===
                        1
                    );


                /* =============================================
                   SALE ITEMS
                ============================================= */

                $saleItems =
                    $itemsBySale[
                        $saleID
                    ]
                    ??
                    [];


                $productNames =
                    [];


                $totalQty =
                    0;


                foreach (
                    $saleItems
                    as $saleItem
                ) {

                    $productNames[] =
                        (string) (
                            $saleItem[
                                "product_name"
                            ]
                            ??
                            ""
                        );


                    $totalQty +=
                        (int) (
                            $saleItem[
                                "quantity"
                            ]
                            ??
                            0
                        );

                }


                /* =============================================
                   BUYER DISPLAY
                ============================================= */

                $buyerPrimary =
                    $buyerName;


                $buyerSecondary =
                    "";


                /*
                MEMBER

                Show member code only when this sale has
                an actual member_id connection.

                Old legacy records without member_id
                will simply show the stored member name.
                */

                if (
                    $affiliateKey
                    ===
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

                }


                /*
                NOT MEMBER

                Since TYPE already identifies the customer
                as Not Member, we no longer put:

                "Not Member"
                or
                "Walk-in Customer"

                underneath a named buyer.

                Blank buyer still displays Walk-in Customer
                as the primary name.
                */

                else {

                    if (
                        $buyerPrimary === ""
                    ) {

                        $buyerPrimary =
                            "Walk-in Customer";

                    }


                    $buyerSecondary =
                        "";

                }


                /* =============================================
                   TYPE
                ============================================= */

                if (
                    $affiliateKey
                    ===
                    "member"
                ) {

                    $typeClass =
                        "member";

                    $typeLabel =
                        "Member";

                }

                elseif (
                    $affiliateKey
                    ===
                    "not member"
                ) {

                    $typeClass =
                        "not-member";

                    $typeLabel =
                        "Not Member";

                }

                else {

                    $typeClass =
                        "other";


                    $typeLabel =
                        $affiliate !== ""
                        ?
                        $affiliate
                        :
                        "Unknown";

                }


                /* =============================================
                   SEARCH DATA
                ============================================= */

                $searchText =
                    strtolower(
                        trim(
                            implode(
                                " ",
                                [

                                    /*
                                    Search works using both the
                                    new short ID and old stored ID.
                                    */

                                    $salesDisplayID,

                                    $transactionID,

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

                                    $isHistoricalEntry
                                    ?
                                    "historical audit entry"
                                    :
                                    "",

                                    (string) $totalQty,

                                    (string) (
                                        $sale[
                                            "grand_total"
                                        ]
                                        ??
                                        0
                                    ),

                                    $affiliate

                                ]
                            )
                        )
                    );

                ?>


                <!-- =========================================
                     LEDGER ROW
                ========================================== -->

                <div
                class="
                    ledger-row
                    sales-ledger-grid
                    <?=
                    $isHistoricalEntry
                    ?
                    'sales-ledger-historical'
                    :
                    '';
                    ?>
                "

                data-historical="<?=
                    $isHistoricalEntry
                    ?
                    '1'
                    :
                    '0';
                ?>"

                data-affiliate="<?=
                    salesEscape(
                        $affiliateKey
                    );
                ?>"

                data-date="<?=
                    salesEscape(
                        $saleDateISO
                    );
                ?>"

                data-datevisible="1"

                data-search="<?=
                    salesEscape(
                        $searchText
                    );
                ?>">


                    <!-- =====================================
                         TRANSACTION
                    ====================================== -->

                    <div class="sales-ledger-transaction">

                        <strong
                        class="
                            sales-ledger-primary
                            transaction-id
                        ">

                            <?=
                            salesEscape(
                                $salesDisplayID
                            );
                            ?>

                        </strong>


                        <span class="sales-ledger-secondary">

                            <?=
                            salesEscape(
                                $saleDateDisplay
                            );
                            ?>

                        </span>


                        <?php
                        if (
                            $isHistoricalEntry
                        ) {
                        ?>

                            <span class="sales-historical-badge">

                                <i class="bi bi-clock-history"></i>

                                HISTORICAL / AUDIT

                            </span>

                        <?php
                        }
                        ?>

                    </div>


                    <!-- =====================================
                         BUYER
                    ====================================== -->

                    <div class="sales-ledger-buyer">

                        <strong class="sales-ledger-primary">

                            <?=
                            salesEscape(
                                $buyerPrimary
                            );
                            ?>

                        </strong>


                        <?php

                        if (
                            $buyerSecondary !== ""
                        ) {

                        ?>

                            <span class="sales-ledger-secondary">

                                <?=
                                salesEscape(
                                    $buyerSecondary
                                );
                                ?>

                            </span>

                        <?php

                        }

                        ?>

                    </div>


                    <!-- =====================================
                         PRODUCTS
                    ====================================== -->

                    <div class="sales-ledger-products">


                        <?php

                        if (
                            count(
                                $saleItems
                            ) === 0
                        ) {

                        ?>

                            <span class="sales-ledger-item-line">

                                <span class="sales-ledger-secondary">
                                    —
                                </span>

                            </span>


                        <?php

                        } else {

                        ?>


                            <?php

                            foreach (
                                $saleItems
                                as $saleItem
                            ) {

                                $currentProductName =
                                    (string) (
                                        $saleItem[
                                            "product_name"
                                        ]
                                        ??
                                        "Unavailable Product"
                                    );

                            ?>

                                <span
                                class="
                                    sales-ledger-item-line
                                    sales-ledger-product-line
                                "
                                title="<?=
                                    salesEscape(
                                        $currentProductName
                                    );
                                ?>">

                                    <?=
                                    salesEscape(
                                        $currentProductName
                                    );
                                    ?>

                                </span>


                            <?php

                            }

                            ?>


                        <?php

                        }

                        ?>


                    </div>


                    <!-- =====================================
                         QUANTITY
                    ====================================== -->

                    <div class="sales-ledger-quantities">


                        <?php

                        if (
                            count(
                                $saleItems
                            ) === 0
                        ) {

                        ?>

                            <span class="sales-ledger-item-line">

                                <span class="sales-ledger-secondary">
                                    —
                                </span>

                            </span>


                        <?php

                        } else {

                        ?>


                            <?php

                            foreach (
                                $saleItems
                                as $saleItem
                            ) {

                            ?>

                                <span
                                class="
                                    sales-ledger-item-line
                                    sales-ledger-qty-line
                                ">

                                    <?=
                                    number_format(
                                        (int) (
                                            $saleItem[
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


                    </div>


                    <!-- =====================================
                         TOTAL
                    ====================================== -->

                    <div class="sales-ledger-total">

                        <strong>

                            <?=
                            salesEscape(
                                salesMoney(
                                    $sale[
                                        "grand_total"
                                    ]
                                    ??
                                    0
                                )
                            );
                            ?>

                        </strong>


                        <span class="sales-ledger-secondary">

                            <?=
                            number_format(
                                $totalQty
                            );
                            ?>

                            <?=
                            $totalQty === 1
                            ?
                            "unit"
                            :
                            "units";
                            ?>

                        </span>

                    </div>



                    <!-- =====================================
                         CASH RECEIVED
                    ====================================== -->

                    <div class="sales-ledger-cash">

                        <strong class="sales-ledger-primary">

                            <?=
                            $sale["cash_received"] === null
                            ?
                            "—"
                            :
                            salesEscape(
                                salesMoney(
                                    $sale["cash_received"]
                                )
                            );
                            ?>

                        </strong>

                    </div>


                    <!-- =====================================
                         CHANGE
                    ====================================== -->

                    <div class="sales-ledger-change">

                        <strong class="sales-ledger-primary">

                            <?=
                            $sale["change_amount"] === null
                            ?
                            "—"
                            :
                            salesEscape(
                                salesMoney(
                                    $sale["change_amount"]
                                )
                            );
                            ?>

                        </strong>

                    </div>


                    <!-- =====================================
                         TYPE
                    ====================================== -->

                    <div class="sales-ledger-type">


                        <span
                        class="sales-type-badge <?=
                            salesEscape(
                                $typeClass
                            );
                        ?>">

                            <?=
                            salesEscape(
                                $typeLabel
                            );
                            ?>

                        </span>

                    </div>


                </div>


            <?php

            }

            ?>


        <?php

        }

        ?>


    </div>

</div>


<!-- =========================================================
     SUCCESS ALERT
========================================================= -->

<?php

if (
    (
        $_GET[
            "success"
        ]
        ??
        ""
    )
    ===
    "added"
) {

?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function(){

        Swal.fire({

            icon:
                "success",

            title:
                "Sale Added Successfully!",

            text:
                "The transaction has been recorded.",

            showConfirmButton:
                false,

            timer:
                1800,

            timerProgressBar:
                true

        });

    }
);

</script>

<?php

}

?>


<!-- =========================================================
     ERROR ALERT
========================================================= -->

<?php

$salesError =
    trim(
        (string) (
            $_GET[
                "error"
            ]
            ??
            ""
        )
    );

?>


<?php

if (
    $salesError !== ""
) {

?>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function(){

        Swal.fire({

            icon:
                "error",

            title:
                "Unable to Save Sale",

            text:
                <?= json_encode(
                    $salesError,
                    JSON_UNESCAPED_UNICODE
                    |
                    JSON_UNESCAPED_SLASHES
                ); ?>,

            confirmButtonColor:
                "#8CA15B"

        });

    }
);

</script>

<?php

}

?>


<!-- =========================================================
     SALES SCRIPT
========================================================= -->

<script
src="../js/sales.js?v=<?= time(); ?>">
</script>


<?php

/*
Automatically load the upcoming Generate Sales Report JS
once the file exists.
*/

$generateSalesReportJSFile =
    dirname(
        __DIR__
    ) .
    "/js/sales_report.js";


if (
    is_file(
        $generateSalesReportJSFile
    )
) {

?>

<script
src="../js/sales_report.js?v=<?= time(); ?>">
</script>

<?php

}

?>


</body>

</html>