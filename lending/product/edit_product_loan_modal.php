<?php

$editProductLoanSelectableProducts = [];

$editProductLoanProductQuery =
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

if ($editProductLoanProductQuery) {

    while (
        $editProductLoanProductRow =
            mysqli_fetch_assoc(
                $editProductLoanProductQuery
            )
    ) {

        $editProductLoanSelectableProducts[] =
            $editProductLoanProductRow;
    }
}

?>


<div
class="modal fade"
id="editProductLoanModal"
tabindex="-1"
aria-labelledby="editProductLoanModalTitle"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="
            modal-content
            product-loan-modal-window
            product-loan-edit-window
        ">

            <form
            id="editProductLoanForm"
            novalidate>


                <!-- =========================================
                     HIDDEN VALUES
                ========================================== -->

                <input
                type="hidden"
                id="editProductLoanID"
                name="product_loan_id">


                <input
                type="hidden"
                id="editProductLoanMemberID"
                name="member_id">


                <input
                type="hidden"
                id="editProductLoanOriginalMemberID">


                <input
                type="hidden"
                id="editProductLoanExistingStatus">


                <input
                type="hidden"
                id="editProductLoanItemsInput"
                name="loan_items">


                <!-- =========================================
                     HEADER
                ========================================== -->

                <div class="product-loan-modal-header">

                    <div
                    class="product-loan-modal-title-group">

                        <h1 id="editProductLoanModalTitle">

                            EDIT PRODUCT LOAN

                        </h1>


                        <span
                        id="editProductLoanStatusBadge"
                        class="
                            product-loan-edit-status-badge
                            product-loan-status-pending
                        ">

                            <i class="bi bi-clock"></i>

                            <span>
                                PENDING
                            </span>

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


                <!-- =========================================
                     SCROLLABLE BODY
                ========================================== -->

                <div class="product-loan-modal-body">


                    <!-- =====================================
                         LOAN INFORMATION
                    ====================================== -->

                    <section class="product-loan-section">

                        <div
                        class="product-loan-section-heading">

                            <i
                            class="bi bi-file-earmark-text">
                            </i>


                            <div>

                                <h2>
                                    LOAN INFORMATION
                                </h2>


                                <p
                                id="editProductLoanInformationText">

                                    Review and update this pending
                                    Product Loan application.

                                </p>

                            </div>

                        </div>


                        <div
                        class="product-loan-details-grid">


                            <!-- LOAN ID -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanDisplayID">

                                    LOAN ID

                                </label>


                                <input
                                type="text"
                                id="editProductLoanDisplayID"
                                class="product-loan-input"
                                readonly>

                            </div>


                            <!-- APPLICATION DATE -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanApplicationDate">

                                    APPLICATION DATE

                                </label>


                                <div
                                class="product-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editProductLoanApplicationDate"
                                    class="product-loan-input"
                                    readonly>


                                    <i class="bi bi-calendar3"></i>

                                </div>

                            </div>


                            <!-- DELIVERED DATE -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanDeliveredDate">

                                    DELIVERED DATE

                                </label>


                                <div
                                class="product-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editProductLoanDeliveredDate"
                                    class="product-loan-input"
                                    value="Not delivered"
                                    readonly>


                                    <i class="bi bi-box-seam"></i>

                                </div>

                            </div>


                            <!-- DUE DATE -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanDueDate">

                                    DUE DATE

                                </label>


                                <div
                                class="product-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editProductLoanDueDate"
                                    class="product-loan-input"
                                    value="Generated upon delivery"
                                    readonly>


                                    <i
                                    class="bi bi-calendar-check">
                                    </i>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- =====================================
                         BORROWER / TERM / STATUS
                    ====================================== -->

                    <section class="product-loan-section">

                        <div
                        class="product-loan-section-heading">

                            <i class="bi bi-person-vcard"></i>


                            <div>

                                <h2>
                                    BORROWER, TERM, AND STATUS
                                </h2>


                                <p>

                                    Pending details remain editable
                                    until the products are delivered.

                                </p>

                            </div>

                        </div>


                        <div
                        class="product-loan-edit-borrower-grid">


                            <!-- MEMBER -->

                            <div
                            class="
                                product-loan-field
                                product-loan-member-field
                            ">

                                <label
                                for="editProductLoanMemberName">

                                    MEMBER NAME

                                    <span>*</span>

                                </label>


                                <div
                                class="product-loan-member-wrapper">

                                    <i class="bi bi-search"></i>


                                    <input
                                    type="text"
                                    id="editProductLoanMemberName"
                                    class="
                                        product-loan-input
                                        product-loan-member-input
                                    "
                                    placeholder="Type at least 3 letters"
                                    autocomplete="off">


                                    <div
                                    id="editProductLoanMemberSuggestions"
                                    class="
                                        product-loan-member-suggestions
                                    ">
                                    </div>

                                </div>

                            </div>


                            <!-- TERM -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanTerm">

                                    LOAN TERM

                                    <span>*</span>

                                </label>


                                <select
                                id="editProductLoanTerm"
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


                            <!-- STATUS -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanStatus">

                                    STATUS

                                    <span>*</span>

                                </label>


                                <select
                                id="editProductLoanStatus"
                                name="status"
                                class="product-loan-input">

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


                                <small
                                id="editProductLoanStatusPermission"
                                class="product-loan-field-note">

                                    Only users with Full Lending
                                    Access may change a pending status.

                                </small>

                            </div>

                        </div>


                        <!-- REJECTION -->

                        <div
                        id="editProductLoanRejectionBlock"
                        class="product-loan-rejection-block"
                        style="display:none;">

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanRejectionReason">

                                    REJECTION REASON

                                    <span>*</span>

                                </label>


                                <textarea
                                id="editProductLoanRejectionReason"
                                name="rejection_reason"
                                class="
                                    product-loan-input
                                    product-loan-textarea
                                "
                                maxlength="1000"
                                placeholder="Enter the reason for rejecting this application"></textarea>

                            </div>

                        </div>

                    </section>


                    <!-- =====================================
                         PRODUCT SELECTION + REQUEST CART
                    ====================================== -->

                    <div
                    id="editProductLoanProductArea"
                    class="edit-product-loan-main-grid">


                        <!-- =================================
                             LEFT: PRODUCT SELECTION
                        ================================== -->

                        <section
                        class="
                            product-loan-section
                            edit-product-loan-selection-section
                        ">

                            <div
                            class="product-loan-section-heading">

                                <i
                                class="bi bi-box-seam-fill">
                                </i>


                                <div>

                                    <h2>
                                        PRODUCT SELECTION
                                    </h2>


                                    <p
                                    id="editProductLoanProductNote">

                                        Select products from Product List.

                                    </p>

                                </div>

                            </div>


                            <div
                            id="editProductLoanProductBuilder"
                            class="
                                edit-product-loan-product-builder
                            ">


                                <!-- SEARCH -->

                                <div
                                class="
                                    edit-product-loan-product-search
                                ">

                                    <i class="bi bi-search"></i>


                                    <input
                                    type="text"
                                    id="editProductLoanProductSearch"
                                    placeholder="Search product"
                                    autocomplete="off">

                                </div>


                                <!-- META -->

                                <div
                                class="
                                    edit-product-loan-product-meta
                                ">

                                    <strong>
                                        PRODUCTS
                                    </strong>


                                    <span>

                                        <strong
                                        id="editProductLoanCatalogCount">

                                            <?php
                                            echo count(
                                                $editProductLoanSelectableProducts
                                            );
                                            ?>

                                        </strong>

                                        product(s)

                                    </span>

                                </div>


                                <!-- PRODUCT GRID -->

                                <div
                                id="editProductLoanProductCatalog"
                                class="
                                    edit-product-loan-product-catalog
                                ">


                                    <?php
                                    if (
                                        !empty(
                                            $editProductLoanSelectableProducts
                                        )
                                    ) {
                                    ?>


                                        <?php

                                        foreach (
                                            $editProductLoanSelectableProducts
                                            as
                                            $editSelectableProduct
                                        ) {

                                            $editSelectableProductID =
                                                (int) (
                                                    $editSelectableProduct[
                                                        "loan_product_id"
                                                    ]
                                                    ?? 0
                                                );


                                            $editSelectableProductName =
                                                trim(
                                                    (string) (
                                                        $editSelectableProduct[
                                                            "product_name"
                                                        ]
                                                        ?? ""
                                                    )
                                                );


                                            $editSelectableProductPrice =
                                                (float) (
                                                    $editSelectableProduct[
                                                        "loan_price"
                                                    ]
                                                    ?? 0
                                                );

                                            $editReservedQuantity =
                                                max(
                                                    0,
                                                    (int) (
                                                        $editSelectableProduct[
                                                            "reserved_quantity"
                                                        ]
                                                        ?? 0
                                                    )
                                                );

                                        ?>


                                            <button
                                            type="button"
                                            class="edit-product-loan-catalog-tile"
                                            data-loan-product-id="<?php
                                            echo $editSelectableProductID;
                                            ?>"
                                            data-product-name="<?php
                                            echo htmlspecialchars(
                                                $editSelectableProductName,
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>"
                                            data-loan-price="<?php
                                            echo htmlspecialchars(
                                                number_format(
                                                    $editSelectableProductPrice,
                                                    2,
                                                    ".",
                                                    ""
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );
                                            ?>"
                                            data-reserved-quantity="<?php
                                            echo $editReservedQuantity;
                                            ?>">


                                                <div
                                                class="
                                                    edit-product-loan-tile-top
                                                ">

                                                    <span
                                                    class="
                                                        edit-product-loan-tile-icon
                                                    ">

                                                        <i
                                                        class="bi bi-box">
                                                        </i>

                                                    </span>


                                                    <strong>

                                                        <?php
                                                        echo htmlspecialchars(
                                                            $editSelectableProductName,
                                                            ENT_QUOTES,
                                                            "UTF-8"
                                                        );
                                                        ?>

                                                    </strong>


                                                    <span
                                                    class="
                                                        edit-product-loan-selected-check
                                                    ">

                                                        <i
                                                        class="bi bi-check-lg">
                                                        </i>

                                                    </span>

                                                </div>


                                                <div
                                                class="
                                                    edit-product-loan-tile-bottom
                                                ">

                                                    <span
                                                    class="
                                                        edit-product-loan-reserved-pill
                                                    "
                                                    <?php
                                                    if (
                                                        $editReservedQuantity <= 0
                                                    ) {
                                                        echo "hidden";
                                                    }
                                                    ?>>

                                                        <i
                                                        class="
                                                            bi
                                                            bi-hourglass-split
                                                        ">
                                                        </i>

                                                        <span
                                                        class="
                                                            edit-product-loan-tile-reserved-value
                                                        ">
                                                            <?php
                                                            echo
                                                            $editReservedQuantity;
                                                            ?>
                                                        </span>

                                                        reserved

                                                    </span>


                                                    <strong
                                                    class="
                                                        edit-product-loan-tile-price
                                                    ">

                                                        ₱<?php
                                                        echo number_format(
                                                            $editSelectableProductPrice,
                                                            2
                                                        );
                                                        ?>

                                                    </strong>

                                                </div>

                                            </button>


                                        <?php
                                        }
                                        ?>


                                    <?php
                                    }
                                    else {
                                    ?>


                                        <div
                                        class="
                                            edit-product-loan-catalog-empty
                                        ">

                                            <i
                                            class="bi bi-box-seam">
                                            </i>


                                            <strong>
                                                No products found
                                            </strong>


                                            <span>

                                            Add products
                                            in Product List first.

                                            </span>

                                        </div>


                                    <?php
                                    }
                                    ?>


                                    <!-- SEARCH EMPTY -->

                                    <div
                                    id="editProductLoanCatalogNoMatch"
                                    class="
                                        edit-product-loan-catalog-empty
                                    "
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


                                <!-- HIDDEN SELECTED PRODUCT INFO -->

                                <div
                                id="editProductLoanSelectedProductPanel"
                                class="
                                    edit-product-loan-selected-product
                                "
                                hidden>

                                    <input
                                    type="hidden"
                                    id="editProductLoanSelectedProductID">


                                    <span
                                    id="editProductLoanSelectedProductName">

                                        —

                                    </span>


                                    <span
                                    id="editProductLoanSelectedProductPrice">

                                        ₱0.00

                                    </span>

                                    <span
                                    id="editProductLoanSelectedProductReserved">

                                        0

                                    </span>

                                </div>


                                <!-- QUANTITY + ADD -->

                                <div
                                class="edit-product-loan-add-row">


                                    <div
                                    class="
                                        edit-product-loan-quantity-block
                                    ">

                                        <label
                                        for="editProductLoanQuantity">

                                            QUANTITY

                                        </label>


                                        <div
                                        class="
                                            edit-product-loan-quantity-stepper
                                        ">

                                            <button
                                            type="button"
                                            id="editProductLoanQuantityMinus"
                                            aria-label="Decrease quantity">

                                                −

                                            </button>


                                            <input
                                            type="number"
                                            id="editProductLoanQuantity"
                                            value="1"
                                            min="1"
                                            step="1"
                                            inputmode="numeric">


                                            <button
                                            type="button"
                                            id="editProductLoanQuantityPlus"
                                            aria-label="Increase quantity">

                                                +

                                            </button>

                                        </div>


                                        <small
                                        id="editProductLoanQuantityMessage"
                                        class="edit-product-loan-quantity-message"
                                        aria-live="polite">
                                        </small>

                                    </div>


                                    <button
                                    type="button"
                                    id="editProductLoanAddItemBtn"
                                    class="
                                        edit-product-loan-add-item-btn
                                    ">

                                        <i
                                        class="bi bi-cart-plus-fill">
                                        </i>

                                        ADD TO REQUEST

                                    </button>

                                </div>

                            </div>

                        </section>


                        <!-- =================================
                             RIGHT: REQUESTED PRODUCTS
                        ================================== -->

                        <section
                        class="
                            product-loan-section
                            edit-product-loan-request-section
                        ">


                            <div
                            class="
                                edit-product-loan-request-title
                            ">

                                <div>

                                    <h2>
                                        REQUESTED PRODUCTS
                                    </h2>


                                    <p>
                                        Review selected products
                                        before saving.
                                    </p>

                                </div>


                                <span
                                id="editProductLoanRequestCount">

                                    0 items

                                </span>

                            </div>


                            <div
                            class="
                                edit-product-loan-request-box
                            ">


                                <div
                                class="
                                    edit-product-loan-request-table-wrap
                                ">

                                    <table
                                    class="
                                        edit-product-loan-request-table
                                    ">

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

                                                <th
                                                aria-label="Remove">
                                                </th>

                                            </tr>

                                        </thead>


                                        <tbody
                                        id="editProductLoanItemsBody">

                                            <tr
                                            class="
                                                edit-product-loan-items-empty
                                            ">

                                                <td colspan="4">

                                                    <i
                                                    class="bi bi-cart3">
                                                    </i>


                                                    <strong>

                                                        Request is empty

                                                    </strong>


                                                    <span>

                                                        Select a product
                                                        then click
                                                        Add to Request.

                                                    </span>

                                                </td>

                                            </tr>

                                        </tbody>

                                    </table>

                                </div>


                                <!-- TOTAL QUANTITY -->

                                <div
                                class="
                                    edit-product-loan-request-total-quantity
                                ">

                                    <span>
                                        TOTAL QUANTITY
                                    </span>


                                    <strong
                                    id="editProductLoanTotalQuantity">

                                        0 units

                                    </strong>

                                </div>


                                <!-- PRINCIPAL -->

                                <div
                                class="
                                    edit-product-loan-request-principal
                                ">

                                    <span>
                                        PRINCIPAL
                                    </span>


                                    <strong
                                    id="editProductLoanCartPrincipal">

                                        ₱0.00

                                    </strong>

                                </div>

                            </div>

                        </section>

                    </div>


                    <!-- =====================================
                         ATTACHMENTS + SUMMARY
                    ====================================== -->

                    <div class="product-loan-bottom-grid">


                        <!-- ATTACHMENTS -->

                        <section
                        class="
                            product-loan-section
                            product-loan-attachments-section
                        ">

                            <div
                            class="product-loan-section-heading">

                                <i class="bi bi-paperclip"></i>


                                <div>

                                    <h2>
                                        ATTACHMENTS
                                    </h2>


                                    <p>

                                        View existing files or add
                                        supporting documents.

                                    </p>

                                </div>

                            </div>


                            <input
                            type="file"
                            id="editProductLoanAttachments"
                            name="attachments[]"
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            aria-describedby="editProductLoanAttachmentHelp"
                            multiple
                            hidden>


                            <label
                            for="editProductLoanAttachments"
                            id="editProductLoanUploadBox"
                            class="product-loan-upload-box">

                                <i
                                class="bi bi-cloud-arrow-up">
                                </i>


                                <strong>
                                    SELECT FILES
                                </strong>


                                <span
                                id="editProductLoanAttachmentHelp">

                                    JPG, PNG, PDF, DOC, or DOCX
                                    · Maximum of 10 new files
                                    · 10 MB each
                                    · 50 MB total

                                </span>

                            </label>


                            <div
                            id="editProductLoanAttachmentList"
                            class="product-loan-attachment-list">

                                <div
                                class="product-loan-no-attachment">

                                    No attachments found

                                </div>

                            </div>

                        </section>


                        <!-- FINANCIAL SUMMARY -->

                        <section
                        class="
                            product-loan-section
                            product-loan-summary-section
                        ">

                            <div
                            class="product-loan-section-heading">

                                <i class="bi bi-calculator"></i>


                                <div>

                                    <h2>
                                        FINANCIAL SUMMARY
                                    </h2>


                                    <p>

                                        Values are calculated using
                                        the saved loan records.

                                    </p>

                                </div>

                            </div>


                            <div class="product-loan-summary-list">

                                <div class="product-loan-summary-row">
                                    <span>LOAN PERIOD</span>
                                    <strong id="editProductLoanPeriod">—</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span style="font-size: 13px; font-weight: 800;">PAYOFF TOTAL</span>
                                    <strong id="editProductLoanScheduledTotal" style="font-size: 17px; font-weight: 800;">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>PRINCIPAL</span>
                                    <strong id="editProductLoanPrincipal">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>INTEREST RATE</span>
                                    <strong id="editProductLoanInterestRate">—</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>INTEREST PAYABLE</span>
                                    <strong id="editProductLoanScheduledInterest">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>DAILY PAYMENT</span>
                                    <strong id="editProductLoanDailyPayment">—</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>MONTHLY PAYMENT</span>
                                    <strong id="editProductLoanMonthlyPayment">—</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>CURRENT ACCRUED INTEREST</span>
                                    <strong id="editProductLoanCurrentInterest">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row">
                                    <span>TOTAL PAYMENTS</span>
                                    <strong id="editProductLoanTotalPayments">₱0.00</strong>
                                </div>

                                <div class="product-loan-summary-row product-loan-summary-total">
                                    <span>OUTSTANDING BALANCE</span>
                                    <strong id="editProductLoanCurrentBalance">—</strong>
                                </div>

                            </div>

                        </section>

                    </div>


                    <!-- =====================================
                         PAYMENT SECTION
                    ====================================== -->

                    <section
                    id="editProductLoanPaymentSection"
                    class="
                        product-loan-section
                        product-loan-payment-section
                    "
                    style="display:none;">


                        <div
                        class="product-loan-section-heading">

                            <i class="bi bi-wallet2"></i>


                            <div>

                                <h2>
                                    RECORD PAYMENT
                                </h2>


                                <p>

                                    Interest is calculated up to
                                    the current Asia/Manila
                                    server date.

                                </p>

                            </div>

                        </div>


                        <!-- PAYMENT BREAKDOWN -->

                        <div
                        class="product-loan-payment-breakdown">


                            <div>

                                <span>
                                    INITIAL LOAN PRINCIPAL
                                </span>


                                <strong
                                id="editPaymentPrincipal">

                                    ₱0.00

                                </strong>

                            </div>


                            <div>

                                <span>
                                    UNPAID INTEREST
                                </span>


                                <strong
                                id="editPaymentUnpaidInterest">

                                    ₱0.00

                                </strong>

                            </div>


                            <div>

                                <span>
                                    ACCRUED DAYS
                                </span>


                                <strong
                                id="editPaymentElapsedDays">

                                    0 Days

                                </strong>

                            </div>


                            <div>

                                <span>
                                    NEW ACCRUED INTEREST
                                </span>


                                <strong
                                id="editPaymentNewInterest">

                                    ₱0.00

                                </strong>

                            </div>


                            <div
                            class="
                                product-loan-payment-payoff
                            ">

                                <span>
                                    CURRENT PAYOFF BALANCE
                                </span>


                                <strong
                                id="editPaymentPayoffBalance">

                                    ₱0.00

                                </strong>

                            </div>

                        </div>


                        <!-- PAYMENT ENTRY -->

                        <div
                        class="product-loan-payment-entry">


                            <!-- PAYMENT DATE -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanPaymentDate">

                                    PAYMENT DATE

                                </label>


                                <div
                                class="
                                    product-loan-readonly-wrapper
                                ">

                                    <input
                                    type="text"
                                    id="editProductLoanPaymentDate"
                                    class="product-loan-input"
                                    readonly>


                                    <i class="bi bi-calendar3"></i>

                                </div>

                            </div>


                            <!-- AMOUNT -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanPaymentAmount">

                                    PAYMENT AMOUNT

                                    <span>*</span>

                                </label>


                                <div
                                class="product-loan-money-input">

                                    <span>
                                        ₱
                                    </span>


                                    <input
                                    type="number"
                                    id="editProductLoanPaymentAmount"
                                    class="product-loan-input"
                                    min="0.01"
                                    step="0.01"
                                    placeholder="0.00"
                                    inputmode="decimal">

                                </div>

                            </div>


                            <!-- NOTES -->

                            <div class="product-loan-field">

                                <label
                                for="editProductLoanPaymentNotes">

                                    NOTES

                                </label>


                                <input
                                type="text"
                                id="editProductLoanPaymentNotes"
                                class="product-loan-input"
                                maxlength="500"
                                placeholder="Optional payment note">

                            </div>


                            <!-- BUTTON -->

                            <button
                            type="button"
                            id="recordProductLoanPaymentBtn"
                            class="
                                product-loan-record-payment-btn
                            ">

                                <i
                                class="bi bi-check2-circle">
                                </i>

                                RECORD PAYMENT

                            </button>

                        </div>


                        <!-- PAYMENT HISTORY -->

                        <div
                        class="
                            product-loan-payment-history-heading
                        ">

                            <h3>
                                PAYMENT HISTORY
                            </h3>


                            <small>

                                Recorded payments cannot be
                                edited or deleted directly.

                            </small>

                        </div>


                        <div
                        class="
                            product-loan-payment-table-wrapper
                        ">

                            <table
                            class="product-loan-payment-table">

                                <thead>
                                    <tr>
                                        <th>DATE</th>
                                        <th>DAYS</th>
                                        <th>TYPE</th>
                                        <th>AMOUNT</th>
                                        <th>INTEREST</th>
                                        <th>PRINCIPAL</th>
                                        <th>BALANCE</th>
                                        <th>NOTES</th>
                                        <th>RECORDED BY</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>

                                <tbody id="editProductLoanPaymentHistory">

                                    <tr class="product-loan-payment-empty">

                                        <td colspan="10">
                                            No payments recorded yet
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </section>

                </div>


                <!-- =========================================
                     FOOTER
                ========================================== -->

                <div
                class="product-loan-modal-footer">

                    <button
                    type="button"
                    class="product-loan-cancel-btn"
                    data-bs-dismiss="modal">

                        CLOSE

                    </button>


                    <button
                    type="submit"
                    id="saveProductLoanChangesBtn"
                    class="product-loan-save-btn">

                        SAVE CHANGES

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>