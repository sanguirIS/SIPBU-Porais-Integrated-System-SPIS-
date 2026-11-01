<?php

/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set(
    'Asia/Manila'
);


/* =========================================================
   AVAILABLE SALES PRODUCTS
========================================================= */

$products = [];

$productQuery =
mysqli_query(
    $conn,
    "
    SELECT
        product_id,
        product_name,
        selling_price,
        quantity

    FROM inventory

    WHERE archived = 0
    AND quantity > 0

    ORDER BY product_name ASC
    "
);


if($productQuery){

    while(
        $product =
        mysqli_fetch_assoc(
            $productQuery
        )
    ){

        $products[] =
        $product;

    }

}


$todayManila =
date('Y-m-d');

?>


<div
class="modal fade"
id="addSalesModal"
tabindex="-1"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered sales-modal-dialog">

        <div class="modal-content sales-modal-window">


            <!-- =====================================================
                 HEADER
            ====================================================== -->

            <div class="sales-modal-header">

                <h1>
                    ADD SALES TRANSACTION
                </h1>


                <button
                type="button"
                class="sales-modal-close"
                data-bs-dismiss="modal"
                aria-label="Close">

                    ×

                </button>

            </div>


            <hr>


            <!-- =====================================================
                 FORM
            ====================================================== -->

            <form
            id="salesForm"
            action="save_sales.php"
            method="POST"
            novalidate>

            <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION["sales_csrf_token"],
                ENT_QUOTES,
                "UTF-8"
            ); ?>">


                <!-- CART DATA -->

                <input
                type="hidden"
                id="cartInput"
                name="cart">


                <!-- CUSTOMER TYPE -->

                <input
                type="hidden"
                id="affiliate"
                name="affiliate">


                <!-- SELECTED MEMBER -->

                <input
                type="hidden"
                id="salesMemberId"
                name="member_id">


                <!-- SELECTED PRODUCT -->

                <input
                type="hidden"
                id="product">


                <!-- =================================================
                     SCROLLABLE BODY
                ================================================== -->

                <div class="sales-modal-body">


                    <!-- =============================================
                         CUSTOMER INFORMATION
                    ============================================== -->

                    <section class="sales-modal-section">

                        <div class="sales-section-title">

                            <span class="sales-section-icon">

                                <i class="bi bi-person-vcard-fill"></i>

                            </span>


                            <div>

                                <h2>
                                    CUSTOMER INFORMATION
                                </h2>

                                <p>
                                    Select the customer type and transaction details.
                                </p>

                            </div>

                        </div>


                        <div class="sales-customer-grid">


                            <!-- =====================================
                                 CUSTOMER TYPE
                            ====================================== -->

                            <div class="sales-field">

                                <label>
                                    CUSTOMER TYPE
                                </label>


                                <div class="sales-affiliate-wrapper">

                                    <button
                                    type="button"
                                    id="selectedAffiliate"
                                    class="sales-control sales-select-control">

                                        <span id="salesAffiliateText">
                                            Select customer type
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="affiliatePopup"
                                    class="sales-affiliate-popup">


                                        <!-- MEMBER -->

                                        <button
                                        type="button"
                                        class="affiliate-item"
                                        data-value="Member">

                                            <i class="bi bi-person-badge-fill"></i>


                                            <span>

                                                <strong>
                                                    Member
                                                </strong>

                                                <small>
                                                    Registered cooperative member
                                                </small>

                                            </span>

                                        </button>


                                        <!-- NOT MEMBER -->

                                        <button
                                        type="button"
                                        class="affiliate-item"
                                        data-value="Not Member">

                                            <i class="bi bi-person-fill"></i>


                                            <span>

                                                <strong>
                                                    Not Member
                                                </strong>

                                                <small>
                                                    Walk-in customer
                                                </small>

                                            </span>

                                        </button>

                                    </div>

                                </div>

                            </div>


                            <!-- =====================================
                                 BUYER
                            ====================================== -->

                            <div class="sales-field">

                                <label for="buyer_name">
                                    BUYER
                                </label>


                                <div class="sales-buyer-wrapper">

                                    <i class="bi bi-search"></i>


                                    <input
                                    type="text"
                                    id="buyer_name"
                                    name="buyer_name"
                                    class="sales-control sales-buyer-input"
                                    placeholder="Select customer type first"
                                    autocomplete="off"
                                    disabled>


                                    <!-- MEMBER SEARCH RESULT -->

                                    <div
                                    id="salesMemberSuggestion"
                                    class="sales-member-suggestions">
                                    </div>

                                </div>


                                <small
                                id="salesBuyerHint"
                                class="sales-field-hint">

                                    Choose Member or Not Member first.

                                </small>


                                <!-- MEMBER FOUND -->

                                <div
                                id="salesMemberStatus"
                                class="sales-member-found"
                                hidden>

                                    <i class="bi bi-check-circle-fill"></i>


                                    <span>

                                        <strong>
                                            Member found
                                        </strong>

                                        <small id="salesSelectedMemberCode">
                                        </small>

                                    </span>

                                </div>

                            </div>


                            <!-- =====================================
                                TRANSACTION DATE
                            ====================================== -->

                            <div class="sales-field">

                                <label>
                                    TRANSACTION DATE
                                </label>


                                <!-- ACTUAL VALUE SENT TO BACKEND -->

                                <input
                                type="hidden"
                                id="salesTransactionDate"
                                name="sale_date"
                                value="<?= htmlspecialchars(
                                    $todayManila,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>">


                                <!-- CUSTOM DATE FIELD -->

                                <div class="sales-transaction-date-wrapper">

                                    <button
                                    type="button"
                                    id="salesTransactionDateBtn"
                                    class="
                                        sales-control
                                        sales-transaction-date-btn
                                    "
                                    aria-haspopup="dialog"
                                    aria-expanded="false">

                                        <span id="salesTransactionDateText">

                                            <?= htmlspecialchars(
                                                date(
                                                    'F j, Y',
                                                    strtotime(
                                                        $todayManila
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>

                                        </span>


                                        <i class="bi bi-calendar3"></i>

                                    </button>


                                    <!-- CUSTOM CALENDAR -->

                                    <div
                                    id="salesTransactionCalendar"
                                    class="sales-transaction-calendar"
                                    aria-hidden="true">


                                        <!-- CALENDAR HEADER -->

                                        <div class="sales-transaction-calendar-header">

                                            <button
                                            type="button"
                                            id="salesTransactionCalendarPrev"
                                            class="sales-transaction-calendar-nav"
                                            aria-label="Previous month">

                                                <i class="bi bi-chevron-left"></i>

                                            </button>


                                            <strong
                                            id="salesTransactionCalendarMonth"
                                            class="sales-transaction-calendar-month">

                                            </strong>


                                            <button
                                            type="button"
                                            id="salesTransactionCalendarNext"
                                            class="sales-transaction-calendar-nav"
                                            aria-label="Next month">

                                                <i class="bi bi-chevron-right"></i>

                                            </button>

                                        </div>


                                        <!-- WEEKDAYS -->

                                        <div class="sales-transaction-calendar-weekdays">

                                            <span>SUN</span>
                                            <span>MON</span>
                                            <span>TUE</span>
                                            <span>WED</span>
                                            <span>THU</span>
                                            <span>FRI</span>
                                            <span>SAT</span>

                                        </div>


                                        <!-- DAYS -->

                                        <div
                                        id="salesTransactionCalendarDays"
                                        class="sales-transaction-calendar-days">
                                        </div>


                                        <!-- LEGEND -->

                                        <div class="sales-transaction-calendar-legend">

                                            <span>

                                                <i
                                                class="
                                                    sales-transaction-calendar-dot
                                                    sales-transaction-calendar-dot-today
                                                ">
                                                </i>

                                                Today

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    sales-transaction-calendar-dot
                                                    sales-transaction-calendar-dot-history
                                                ">
                                                </i>

                                                Historical

                                            </span>

                                        </div>

                                    </div>

                                </div>


                                <!-- HISTORICAL / AUDIT NOTE -->

                                <small
                                id="salesTransactionHistoricalNote"
                                class="
                                    sales-field-hint
                                    sales-transaction-historical-note
                                "
                                hidden>

                                    Historical / Audit Entry — this sale occurred before today.

                                </small>


                                <small
                                id="salesTransactionDateHint"
                                class="sales-field-hint">

                                    Select when the sale occurred. Past dates are allowed for historical records.

                                </small>

                            </div>

                        </div>

                    </section>


                    <!-- =============================================
                         PRODUCT + CART
                    ============================================== -->

                    <div class="sales-main-grid">


                        <!-- =========================================
                             PRODUCT SELECTION
                        ========================================== -->

                        <section
                        class="sales-modal-section sales-product-section">


                            <div class="sales-section-title">

                                <span class="sales-section-icon">

                                    <i class="bi bi-box-seam-fill"></i>

                                </span>


                                <div>

                                    <h2>
                                        PRODUCT SELECTION
                                    </h2>

                                    <p>
                                        Select products currently available in Inventory.
                                    </p>

                                </div>

                            </div>


                            <!-- =====================================
                                 PRODUCT SEARCH
                            ====================================== -->

                            <div class="sales-product-search">

                                <i class="bi bi-search"></i>


                                <input
                                type="text"
                                id="selectedProduct"
                                class="sales-control"
                                placeholder="Search product"
                                autocomplete="off">

                            </div>


                            <!-- =====================================
                                 PRODUCT COUNT
                            ====================================== -->

                            <div class="sales-product-meta">

                                <strong>
                                    AVAILABLE PRODUCTS
                                </strong>


                                <span id="salesProductResultCount">

                                    <?= count($products) ?>

                                    <?= count($products) === 1
                                        ? 'product'
                                        : 'products'
                                    ?>

                                </span>

                            </div>


                            <!-- =====================================
                                 PRODUCT TILES
                            ====================================== -->

                            <div
                            id="productPopup"
                            class="sales-product-grid">


                                <?php if(!empty($products)): ?>


                                    <?php

                                    foreach(
                                        $products
                                        as
                                        $product
                                    ):

                                    ?>


                                        <?php

                                        $productID =
                                        (int)$product[
                                            'product_id'
                                        ];


                                        $productName =
                                        trim(
                                            (string)$product[
                                                'product_name'
                                            ]
                                        );


                                        $sellingPrice =
                                        round(
                                            (float)$product[
                                                'selling_price'
                                            ],
                                            2
                                        );


                                        $stock =
                                        (int)$product[
                                            'quantity'
                                        ];


                                        if($stock <= 5){

                                            $stockClass =
                                            'sales-stock-low';

                                        }
                                        elseif($stock <= 10){

                                            $stockClass =
                                            'sales-stock-medium';

                                        }
                                        else{

                                            $stockClass =
                                            'sales-stock-good';

                                        }

                                        ?>


                                        <button
                                        type="button"
                                        class="product-item sales-product-tile"
                                        data-id="<?= $productID ?>"
                                        data-name="<?= htmlspecialchars(
                                            $productName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        data-price="<?= htmlspecialchars(
                                            number_format(
                                                $sellingPrice,
                                                2,
                                                '.',
                                                ''
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>"
                                        data-stock="<?= $stock ?>">


                                            <!-- PRODUCT NAME -->

                                            <div class="sales-product-tile-name">

                                                <span class="sales-product-tile-icon">

                                                    <i class="bi bi-box"></i>

                                                </span>


                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $productName,
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>

                                                </strong>

                                            </div>


                                            <!-- PRODUCT INFO -->

                                            <div class="sales-product-tile-info">


                                                <!-- STOCK -->

                                                <span
                                                class="
                                                    sales-stock-indicator
                                                    <?= $stockClass ?>
                                                ">

                                                    <i class="bi bi-circle-fill"></i>

                                                    <?= $stock ?>
                                                    available

                                                </span>


                                                <!-- PRICE -->

                                                <strong>

                                                    ₱<?= number_format(
                                                        $sellingPrice,
                                                        2
                                                    ) ?>

                                                </strong>

                                            </div>

                                        </button>


                                    <?php endforeach; ?>


                                <?php else: ?>


                                    <div class="sales-product-empty">

                                        <i class="bi bi-box-seam"></i>

                                        <strong>
                                            No available products
                                        </strong>

                                        <span>
                                            Restock products in Inventory first.
                                        </span>

                                    </div>


                                <?php endif; ?>


                                <!-- SEARCH EMPTY RESULT -->

                                <div
                                id="salesProductSearchEmpty"
                                class="sales-product-empty"
                                hidden>

                                    <i class="bi bi-search"></i>

                                    <strong>
                                        No matching product
                                    </strong>

                                    <span>
                                        Try another product name.
                                    </span>

                                </div>

                            </div>


                            <!-- =====================================
                                 SELECTED PRODUCT DETAILS
                            ====================================== -->

                            <div
                            id="salesSelectedProduct"
                            class="sales-selected-product"
                            hidden>


                                <div>

                                    <small>
                                        SELECTED PRODUCT
                                    </small>

                                    <strong
                                    id="salesSelectedProductName">

                                        -

                                    </strong>

                                </div>


                                <div class="sales-selected-meta">


                                    <!-- PRICE -->

                                    <span>

                                        <small>
                                            PRICE
                                        </small>

                                        <strong
                                        id="salesSelectedProductPrice">

                                            ₱0.00

                                        </strong>

                                    </span>


                                    <!-- STOCK -->

                                    <span>

                                        <small>
                                            AVAILABLE
                                        </small>

                                        <strong
                                        id="salesSelectedProductStock">

                                            0 units

                                        </strong>

                                    </span>

                                </div>

                            </div>


                            <!-- =====================================
                                 QUANTITY + ADD TO CART
                            ====================================== -->

                            <div class="sales-add-product-row">


                                <!-- QUANTITY -->

                                <div class="sales-quantity-group">

                                    <label for="salesQty">
                                        QUANTITY
                                    </label>


                                    <div class="sales-quantity-stepper">

                                        <button
                                        type="button"
                                        id="salesQtyMinus"
                                        aria-label="Decrease quantity">

                                            −

                                        </button>


                                        <input
                                        type="number"
                                        id="salesQty"
                                        value="1"
                                        min="1"
                                        step="1"
                                        inputmode="numeric">


                                        <button
                                        type="button"
                                        id="salesQtyPlus"
                                        aria-label="Increase quantity">

                                            +

                                        </button>

                                    </div>

                                </div>


                                <!-- ADD TO CART -->

                                <button
                                type="button"
                                id="addItemBtn"
                                class="sales-add-cart-btn">

                                    <i class="bi bi-cart-plus-fill"></i>

                                    ADD TO CART

                                </button>

                            </div>

                        </section>


                        <!-- =========================================
                             PRODUCT CART
                        ========================================== -->

                        <section
                        class="sales-modal-section sales-cart-section">


                            <div class="sales-cart-title">

                                <div>

                                    <h2>
                                        PRODUCT CART
                                    </h2>

                                    <p>
                                        Review selected products before saving.
                                    </p>

                                </div>


                                <span id="salesCartCount">
                                    0 items
                                </span>

                            </div>


                            <div class="sales-cart-box">


                                <!-- CART ROWS -->

                                <div class="sales-cart-scroll">

                                    <table class="sales-cart-table">

                                        <colgroup>

                                            <col style="width:38%">

                                            <col style="width:28%">

                                            <col style="width:24%">

                                            <col style="width:10%">

                                        </colgroup>


                                        <thead>

                                            <tr>

                                                <th>
                                                    PRODUCT
                                                </th>

                                                <th>
                                                    QTY
                                                </th>

                                                <th>
                                                    AMOUNT
                                                </th>

                                                <th></th>

                                            </tr>

                                        </thead>


                                        <tbody id="salesItems">

                                            <tr class="sales-cart-empty-row">

                                                <td colspan="4">

                                                    <div class="sales-cart-empty">

                                                        <i class="bi bi-cart3"></i>

                                                        <strong>
                                                            Cart is empty
                                                        </strong>

                                                        <span>
                                                            Add a product to begin.
                                                        </span>

                                                    </div>

                                                </td>

                                            </tr>

                                        </tbody>

                                    </table>

                                </div>

                                <!-- CART SUMMARY -->

                                <div class="sales-cart-summary">

                                    <div>

                                        <span>
                                            TOTAL QUANTITY
                                        </span>

                                        <strong id="salesTotalQuantity">
                                            0 units
                                        </strong>

                                    </div>

                                </div>

                            </div>

                        </section>

                    </div>


                    <!-- =============================================
                         PAYMENT SUMMARY
                    ============================================== -->

                    <section class="sales-modal-section sales-payment-section">

                        <div class="sales-section-title">

                            <span class="sales-section-icon">

                                <i class="bi bi-wallet2"></i>

                            </span>

                            <div>

                                <h2>
                                    PAYMENT SUMMARY
                                </h2>

                                <p>
                                    Review the total amount and customer payment.
                                </p>

                            </div>

                        </div>


                        <div class="sales-payment-details">


                            <!-- GRAND TOTAL -->

                            <div class="sales-payment-row sales-payment-total">

                                <span>
                                    GRAND TOTAL
                                </span>

                                <strong id="grandTotal">
                                    ₱0.00
                                </strong>

                            </div>


                            <!-- CASH RECEIVED -->

                            <div class="sales-payment-row sales-payment-cash">

                                <label for="salesCashReceived">
                                    CASH RECEIVED
                                </label>

                                <input
                                    type="number"
                                    id="salesCashReceived"
                                    name="cash_received"
                                    class="sales-control"
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    inputmode="decimal"
                                    required>

                            </div>


                            <!-- CHANGE -->

                            <div class="sales-payment-row sales-payment-change">

                                <span>
                                    CHANGE
                                </span>

                                <strong id="salesChange">
                                    ₱0.00
                                </strong>

                            </div>

                        </div>

                    </section>

                </div>

                <!-- =================================================
                     FIXED FOOTER
                ================================================== -->

                <div class="sales-modal-footer">


                    <!-- SAVE -->

                    <button
                    type="submit"
                    id="saveSalesButton"
                    class="sales-footer-save">

                        <i class="bi bi-check-circle-fill"></i>

                        <strong>
                            SAVE SALE
                        </strong>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>