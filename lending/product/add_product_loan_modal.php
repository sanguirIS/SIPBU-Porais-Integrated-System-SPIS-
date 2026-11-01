<?php

/* =========================================================
   ADD PRODUCT LOAN - PRODUCT CATALOG WITH PENDING RESERVATIONS
========================================================= */

$addProductLoanSelectableProducts = [];

$addProductLoanProductQuery =
    mysqli_query(
        $conn,
        "
        SELECT
            product.loan_product_id,
            product.product_name,
            product.loan_price,

            COALESCE(
                reservation.reserved_quantity,
                0
            ) AS reserved_quantity

        FROM product_loan_products AS product

        LEFT JOIN (
            SELECT
                LOWER(
                    TRIM(
                        item.product_name
                    )
                ) AS product_key,

                SUM(
                    item.quantity
                ) AS reserved_quantity

            FROM product_loan_items AS item

            INNER JOIN product_loans AS loan
                ON loan.product_loan_id =
                   item.product_loan_id

            WHERE
                loan.status = 'Pending'
                AND loan.is_archived = 0

            GROUP BY
                LOWER(
                    TRIM(
                        item.product_name
                    )
                )
        ) AS reservation
            ON reservation.product_key =
               LOWER(
                   TRIM(
                       product.product_name
                   )
               )

        WHERE
            product.archived = 0

        ORDER BY
            product.product_name ASC,
            product.loan_product_id ASC
        "
    );

if ($addProductLoanProductQuery) {

    while (
        $addProductLoanProductRow =
            mysqli_fetch_assoc(
                $addProductLoanProductQuery
            )
    ) {

        $addProductLoanSelectableProducts[] =
            $addProductLoanProductRow;
    }
}

?>


<div
class="modal fade"
id="addProductLoanModal"
tabindex="-1"
aria-labelledby="addProductLoanModalTitle"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="modal-content product-loan-modal-window">

            <form
            id="addProductLoanForm"
            enctype="multipart/form-data"
            novalidate>


                <!-- =====================================
                     HIDDEN VALUES
                ====================================== -->

                <input
                type="hidden"
                id="productLoanMemberID"
                name="member_id">


                <input
                type="hidden"
                id="productLoanStatus"
                name="status"
                value="Pending">


                <input
                type="hidden"
                id="productLoanItemsInput"
                name="loan_items">


                <!-- =====================================
                     MODAL HEADER
                ====================================== -->

                <div class="product-loan-modal-header">

                    <div class="product-loan-modal-title-group">

                        <h1 id="addProductLoanModalTitle">
                            ADD PRODUCT LOAN
                        </h1>


                        <span class="product-loan-pending-badge">

                            <i class="bi bi-clock"></i>

                            PENDING

                        </span>

                    </div>


                    <button
                    type="button"
                    class="product-loan-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                        ×

                    </button>

                </div>


                <hr>


                <!-- =====================================
                     SCROLLABLE MODAL BODY
                ====================================== -->

                <div class="product-loan-modal-body">


                    <!-- =================================
                         BORROWER AND LOAN TERMS
                    ================================== -->

                    <section class="product-loan-section">

                        <div class="product-loan-section-heading">

                            <i class="bi bi-person-vcard"></i>

                            <div>

                                <h2>
                                    BORROWER AND LOAN TERMS
                                </h2>

                                <p>
                                    Select a registered member and the agreed loan period.
                                </p>

                            </div>

                        </div>


                        <div class="product-loan-details-grid">


                            <!-- MEMBER -->

                            <div class="product-loan-field product-loan-member-field">

                                <label for="productLoanMemberName">

                                    MEMBER NAME

                                    <span>*</span>

                                </label>


                                <div class="product-loan-member-wrapper">

                                    <i class="bi bi-search"></i>


                                    <input
                                    type="text"
                                    id="productLoanMemberName"
                                    name="member_name"
                                    class="product-loan-input product-loan-member-input"
                                    placeholder="Type at least 3 letters"
                                    autocomplete="off">


                                    <div
                                    id="productLoanMemberSuggestions"
                                    class="product-loan-member-suggestions">
                                    </div>

                                </div>

                            </div>


                            <!-- LOAN TERM -->

                            <div class="product-loan-field">

                                <label for="productLoanTerm">

                                    LOAN TERM

                                    <span>*</span>

                                </label>


                                <select
                                id="productLoanTerm"
                                name="loan_term"
                                class="product-loan-input">

                                    <option
                                    value=""
                                    selected
                                    disabled
                                    hidden>

                                        Select loan term

                                    </option>


                                    <option
                                    value="180"
                                    data-interest="10">

                                        10% - 180 Days

                                    </option>


                                    <option
                                    value="360"
                                    data-interest="20">

                                        20% - 360 Days

                                    </option>

                                </select>

                            </div>


                            <!-- APPLICATION DATE -->

                            <div class="product-loan-field">

                                <label for="productLoanApplicationDate">
                                    APPLICATION DATE
                                </label>


                                <div class="product-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="productLoanApplicationDate"
                                    name="application_date"
                                    class="product-loan-input"
                                    placeholder="Set automatically"
                                    readonly>


                                    <i class="bi bi-calendar3"></i>

                                </div>

                            </div>


                            <!-- DUE DATE -->

                            <div class="product-loan-field">

                                <label for="productLoanDueDate">
                                    DUE DATE
                                </label>


                                <div class="product-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="productLoanDueDate"
                                    name="due_date"
                                    class="product-loan-input"
                                    value="Generated upon delivery"
                                    readonly>


                                    <i class="bi bi-calendar-check"></i>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- =================================
                         PRODUCT DETAILS
                    ================================== -->

                    <section class="product-loan-section">

                        <div class="product-loan-section-heading">

                            <i class="bi bi-box-seam"></i>

                            <div>

                                <h2>
                                    REQUESTED PRODUCTS
                                </h2>

                                <p>
                                    Select products from Product List. Pending requests keep the requested quantities reserved until they are Delivered or Rejected.
                                </p>

                            </div>

                        </div>


                        <div class="product-loan-request-workspace">


                            <!-- =============================
                                 LEFT: PRODUCT CATALOG
                            ============================== -->

                            <div class="product-loan-catalog-panel">


                                <div class="product-loan-product-search">

                                    <i class="bi bi-search"></i>

                                    <input
                                    type="text"
                                    id="productLoanProductSearch"
                                    placeholder="Search product..."
                                    autocomplete="off"
                                    aria-label="Search Product List">

                                </div>


                                <div class="product-loan-product-meta">

                                    <strong>
                                        PRODUCT LIST
                                    </strong>

                                    <span>

                                        <strong id="productLoanCatalogCount">
                                            <?php
                                            echo count(
                                                $addProductLoanSelectableProducts
                                            );
                                            ?>
                                        </strong>

                                        product(s)

                                    </span>

                                </div>


                                <div
                                id="productLoanProductCatalog"
                                class="product-loan-product-catalog">


                                    <?php
                                    if (
                                        !empty(
                                            $addProductLoanSelectableProducts
                                        )
                                    ) {

                                        foreach (
                                            $addProductLoanSelectableProducts
                                            as
                                            $addSelectableProduct
                                        ) {

                                            $addSelectableProductID =
                                                (int) (
                                                    $addSelectableProduct[
                                                        "loan_product_id"
                                                    ]
                                                    ?? 0
                                                );

                                            $addSelectableProductName =
                                                trim(
                                                    (string) (
                                                        $addSelectableProduct[
                                                            "product_name"
                                                        ]
                                                        ?? ""
                                                    )
                                                );

                                            $addSelectableProductPrice =
                                                (float) (
                                                    $addSelectableProduct[
                                                        "loan_price"
                                                    ]
                                                    ?? 0
                                                );

                                            $addReservedQuantity =
                                                max(
                                                    0,
                                                    (int) (
                                                        $addSelectableProduct[
                                                            "reserved_quantity"
                                                        ]
                                                        ?? 0
                                                    )
                                                );

                                    ?>


                                        <button
                                        type="button"
                                        class="product-loan-catalog-tile"
                                        data-loan-product-id="<?php
                                        echo $addSelectableProductID;
                                        ?>"
                                        data-product-name="<?php
                                        echo htmlspecialchars(
                                            $addSelectableProductName,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>"
                                        data-loan-price="<?php
                                        echo htmlspecialchars(
                                            number_format(
                                                $addSelectableProductPrice,
                                                2,
                                                ".",
                                                ""
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );
                                        ?>"
                                        data-reserved-quantity="<?php
                                        echo $addReservedQuantity;
                                        ?>">


                                            <div class="product-loan-tile-top">

                                                <span class="product-loan-tile-icon">

                                                    <i class="bi bi-box"></i>

                                                </span>


                                                <strong>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $addSelectableProductName,
                                                        ENT_QUOTES,
                                                        "UTF-8"
                                                    );
                                                    ?>

                                                </strong>


                                                <span class="product-loan-selected-check">

                                                    <i class="bi bi-check-lg"></i>

                                                </span>

                                            </div>


                                            <div class="product-loan-tile-bottom">

                                                <?php
                                                if (
                                                    $addReservedQuantity > 0
                                                ) {
                                                ?>

                                                    <span class="product-loan-reserved-pill">

                                                        <i class="bi bi-hourglass-split"></i>

                                                        <span class="product-loan-tile-reserved-value">
                                                            <?php
                                                            echo $addReservedQuantity;
                                                            ?>
                                                        </span>

                                                        reserved

                                                    </span>

                                                <?php
                                                }
                                                else {
                                                ?>

                                                    <span
                                                    class="product-loan-reserved-pill"
                                                    hidden>

                                                        <i class="bi bi-hourglass-split"></i>

                                                        <span class="product-loan-tile-reserved-value">
                                                            0
                                                        </span>

                                                        reserved

                                                    </span>

                                                <?php
                                                }
                                                ?>


                                                <strong class="product-loan-tile-price">

                                                    ₱<?php
                                                    echo number_format(
                                                        $addSelectableProductPrice,
                                                        2
                                                    );
                                                    ?>

                                                </strong>

                                            </div>

                                        </button>


                                    <?php
                                        }
                                    }
                                    else {
                                    ?>


                                        <div class="product-loan-catalog-empty">

                                            <i class="bi bi-box-seam"></i>

                                            <strong>
                                                No products found
                                            </strong>

                                            <span>
                                                Add products in Product List first.
                                            </span>

                                        </div>


                                    <?php
                                    }
                                    ?>


                                    <div
                                    id="productLoanCatalogNoMatch"
                                    class="product-loan-catalog-empty"
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


                                <!-- SELECTED PRODUCT SUMMARY -->

                                <div
                                id="productLoanSelectedProductPanel"
                                class="product-loan-selected-product-panel">

                                    <input
                                    type="hidden"
                                    id="productLoanSelectedProductID">


                                    <div class="product-loan-selected-product-name">

                                        <span>
                                            SELECTED PRODUCT
                                        </span>

                                        <strong id="productLoanSelectedProductName">
                                            No product selected
                                        </strong>

                                    </div>


                                    <div class="product-loan-selected-product-stats">

                                        <span>
                                            Price
                                            <strong id="productLoanSelectedProductPrice">
                                                ₱0.00
                                            </strong>
                                        </span>

                                        <span>
                                            Reserved
                                            <strong id="productLoanSelectedProductReserved">
                                                0
                                            </strong>
                                        </span>

                                    </div>

                                </div>


                                <div class="product-loan-add-row">


                                    <div class="product-loan-quantity-block">

                                        <label for="productLoanQuantity">
                                            QUANTITY
                                        </label>


                                        <div class="product-loan-quantity-stepper">

                                            <button
                                            type="button"
                                            id="productLoanQuantityMinus"
                                            aria-label="Decrease quantity">

                                                −

                                            </button>


                                            <input
                                            type="number"
                                            id="productLoanQuantity"
                                            value="1"
                                            min="1"
                                            step="1"
                                            inputmode="numeric">


                                            <button
                                            type="button"
                                            id="productLoanQuantityPlus"
                                            aria-label="Increase quantity">

                                                +

                                            </button>

                                        </div>

                                        <small
                                        id="productLoanQuantityMessage"
                                        class="product-loan-quantity-message product-loan-field-error"
                                        aria-live="polite">
                                        </small>

                                    </div>


                                    <button
                                    type="button"
                                    id="addProductLoanItemBtn"
                                    class="product-loan-add-item-btn">

                                        <i class="bi bi-cart-plus-fill"></i>

                                        ADD TO REQUEST

                                    </button>

                                </div>

                            </div>


                            <!-- =============================
                                 RIGHT: REQUEST CART
                            ============================== -->

                            <div class="product-loan-request-panel">


                                <div class="product-loan-request-title">

                                    <div>

                                        <h3>
                                            REQUEST CART
                                        </h3>

                                        <p>
                                            Reserved quantities are checked again when the application is saved.
                                        </p>

                                    </div>


                                    <span id="productLoanRequestCount">
                                        0 items
                                    </span>

                                </div>


                                <div class="product-loan-request-box">


                                    <div class="product-loan-items-table-wrapper">

                                        <table class="product-loan-items-table">

                                            <thead>

                                                <tr>

                                                    <th>
                                                        PRODUCT
                                                    </th>

                                                    <th>
                                                        QUANTITY
                                                    </th>

                                                    <th>
                                                        AMOUNT
                                                    </th>

                                                    <th aria-label="Remove">
                                                    </th>

                                                </tr>

                                            </thead>


                                            <tbody id="productLoanItemsBody">

                                                <tr class="product-loan-items-empty">

                                                    <td colspan="4">

                                                        <i class="bi bi-cart3"></i>

                                                        <strong>
                                                            Request is empty
                                                        </strong>

                                                        <span>
                                                            Select a product then click Add to Request.
                                                        </span>

                                                    </td>

                                                </tr>

                                            </tbody>

                                        </table>

                                    </div>


                                    <div class="product-loan-request-total-quantity">

                                        <span>
                                            TOTAL REQUESTED QUANTITY
                                        </span>

                                        <strong id="productLoanTotalQuantity">
                                            0 units
                                        </strong>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- =================================
                         ATTACHMENTS AND SUMMARY
                    ================================== -->

                    <div class="product-loan-bottom-grid">


                        <!-- ATTACHMENTS -->

                        <section class="product-loan-section product-loan-attachments-section">

                            <div class="product-loan-section-heading">

                                <i class="bi bi-paperclip"></i>

                                <div>

                                    <h2>
                                        ATTACHMENTS
                                        <span class="product-loan-required-mark">*</span>
                                    </h2>

                                    <p>
                                        At least one supporting document is required for this application.
                                    </p>

                                </div>

                            </div>


                            <input
                            type="file"
                            id="productLoanAttachments"
                            name="attachments[]"
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            aria-describedby="productLoanAttachmentHelp"
                            aria-required="true"
                            multiple
                            hidden>


                            <label
                            for="productLoanAttachments"
                            class="product-loan-upload-box">

                                <i class="bi bi-cloud-arrow-up"></i>

                                <strong>
                                    SELECT FILES
                                </strong>

                                <span id="productLoanAttachmentHelp">
                                    JPG, PNG, PDF, DOC, or DOCX · Maximum of 10 files · 10 MB each
                                </span>

                            </label>


                            <div
                            id="productLoanAttachmentList"
                            class="product-loan-attachment-list">

                                <div class="product-loan-no-attachment">

                                    No attachments selected

                                </div>

                            </div>

                        </section>


                        <!-- LOAN SUMMARY -->

                        <section class="product-loan-section product-loan-summary-section">

                            <div class="product-loan-section-heading">

                                <i class="bi bi-calculator"></i>

                                <div>

                                    <h2>
                                        LOAN SUMMARY
                                    </h2>

                                    <p>
                                        Amounts are calculated automatically.
                                    </p>

                                </div>

                            </div>


                            <div class="product-loan-summary-list">

                                <div class="product-loan-summary-row">
                                    <span>LOAN PERIOD</span>
                                    <strong id="productLoanPeriod">—</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span style="font-size: 13px; font-weight: 800;">PAYOFF TOTAL</span>
                                    <strong id="productLoanScheduledTotal" style="font-size: 17px; font-weight: 800;">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>PRINCIPAL</span>
                                    <strong id="productLoanPrincipal">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>INTEREST RATE</span>
                                    <strong id="productLoanInterestRate">—</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>INTEREST PAYABLE</span>
                                    <strong id="productLoanScheduledInterest">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>DAILY PAYMENT</span>
                                    <strong id="productLoanDailyPayment">—</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>MONTHLY PAYMENT</span>
                                    <strong id="productLoanMonthlyPayment">—</strong>
                                </div>

                            </div>

                        </section>

                    </div>

                </div>


                <!-- =====================================
                     MODAL FOOTER
                ====================================== -->

                <div class="product-loan-modal-footer">

                    <button
                    type="button"
                    class="product-loan-cancel-btn"
                    data-bs-dismiss="modal">

                        CANCEL

                    </button>


                    <button
                    type="submit"
                    id="saveProductLoanBtn"
                    class="product-loan-save-btn">

                        SAVE APPLICATION

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>