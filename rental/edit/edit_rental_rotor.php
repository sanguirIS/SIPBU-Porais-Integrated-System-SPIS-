<?php

/* =========================================================
   SPIS - EDIT ROTOR RENTAL MODAL

   EDITING RULES

   READ ONLY:
   - Transaction ID
   - Affiliate
   - Customer
   - Service Location
   - Area
   - Price per Hectare
   - Discount
   - Total
   - Rental Status
   - Payment Remarks

   PENDING:
   - Rental Date editable
   - Primary Tractor editable
   - Primary Driver editable
   - Secondary Assignment editable

   ONGOING:
   - Primary Assignment normally locked
   - Secondary Assignment may still be managed

   MISSED / DELAYED:
   - Edit flow may allow rescheduling
   - Exact lifecycle rules handled by JS + backend

   DONE:
   - Entire rental is read-only

   IMPORTANT:
   - No manual Operator field
   - Tractor Registry is authoritative
   - Driver Registry is authoritative
   - Secondary Tractor + Driver must be paired
   - No "None" dropdown option
========================================================= */

?>


<!-- =========================================================
     EDIT ROTOR RENTAL
========================================================= -->

<div
class="modal fade"
id="editRotorModal"
tabindex="-1"
aria-labelledby="editRotorModalTitle"
aria-hidden="true">


    <div
    class="modal-dialog modal-xl modal-dialog-centered">


        <div
        class="
            modal-content
            rotor-window
            rotor-add-window
            rotor-edit-window
        ">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div
            class="
                rotor-header
                rotor-add-header
                rotor-edit-header
            ">


                <div class="rotor-add-heading">


                    <div class="rotor-edit-title-row">


                        <div>


                            <h1 id="editRotorModalTitle">

                                EDIT ROTOR RENTAL

                            </h1>


                            <div class="rotor-edit-header-sub">

                                Review the rental record and update
                                its schedule or assignment when allowed.

                            </div>


                        </div>


                        <span
                        id="editRotorTransactionBadge"
                        class="rotor-edit-transaction-badge">

                            RTR-0000

                        </span>


                    </div>


                </div>


                <button
                type="button"
                class="rotor-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close">

                    &times;

                </button>


            </div>


            <hr>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
            id="editRotorForm"
            method="POST"
            novalidate>

                <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $rentalCsrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">


                <!-- =============================================
                     IDENTIFIERS
                ============================================== -->

                <input
                type="hidden"
                id="editRotorID"
                name="rotor_id">


                <input
                type="hidden"
                id="editRotorTransactionID"
                name="transaction_id">


                <input
                type="hidden"
                id="editRotorAffiliate"
                name="affiliate">


                <input
                type="hidden"
                id="editRotorMemberID"
                name="member_id">


                <input
                type="hidden"
                id="editRotorStatus"
                name="status">


                <!-- =================================================
                     BODY
                ================================================== -->

                <div class="rotor-add-body rotor-edit-body">


                    <!-- =================================================
                         CUSTOMER DETAILS
                    ================================================== -->

                    <section
                    class="
                        rotor-form-section
                        rotor-edit-section
                        rotor-edit-summary-section
                    ">


                        <div class="rotor-section-heading">


                            <div class="rotor-section-icon">

                                <i class="bi bi-person-vcard"></i>

                            </div>


                            <div>

                                <h3>
                                    CUSTOMER DETAILS
                                </h3>

                            </div>


                        </div>


                        <div class="rotor-edit-summary-grid">


                            <!-- CUSTOMER -->

                            <div
                            class="
                                rotor-edit-summary-item
                                rotor-edit-summary-item-wide
                            ">


                                <div class="rotor-edit-summary-label">

                                    <i class="bi bi-person"></i>

                                    Customer

                                </div>


                                <input
                                type="text"
                                id="editRotorCustomerName"
                                name="customer_name"
                                class="rotor-edit-summary-input"
                                readonly
                                tabindex="-1">


                            </div>


                            <!-- AFFILIATE -->

                            <div class="rotor-edit-summary-item">


                                <div class="rotor-edit-summary-label">

                                    <i class="bi bi-people"></i>

                                    Affiliate

                                </div>


                                <div
                                id="editRotorAffiliateText"
                                class="rotor-edit-summary-value">

                                    —

                                </div>


                            </div>


                            <!-- LOCATION -->

                            <div
                            class="
                                rotor-edit-summary-item
                                rotor-edit-summary-item-wide
                            ">


                                <div class="rotor-edit-summary-label">

                                    <i class="bi bi-geo-alt"></i>

                                    Service Location

                                </div>


                                <input
                                type="text"
                                id="editRotorLocation"
                                name="location"
                                class="rotor-edit-summary-input"
                                readonly
                                tabindex="-1">


                            </div>


                            <!-- AREA -->

                            <div class="rotor-edit-summary-item">


                                <div class="rotor-edit-summary-label">

                                    <i class="bi bi-bounding-box"></i>

                                    Area

                                </div>


                                <div class="rotor-edit-area-value">


                                    <input
                                    type="number"
                                    id="editRotorArea"
                                    name="area"
                                    class="rotor-edit-summary-input"
                                    step="0.01"
                                    readonly
                                    tabindex="-1">


                                    <span>
                                        ha
                                    </span>


                                </div>


                            </div>


                        </div>


                    </section>


                    <!-- =================================================
                         RENTAL SCHEDULE & ASSIGNMENT
                    ================================================== -->

                    <section
                    class="
                        rotor-form-section
                        rotor-edit-section
                        rotor-edit-working-section
                    ">


                        <div class="rotor-edit-section-top">


                            <div class="rotor-section-heading">


                                <div class="rotor-section-icon">

                                    <i class="bi bi-calendar2-check"></i>

                                </div>


                                <div>

                                    <h3>

                                        RENTAL SCHEDULE & ASSIGNMENT

                                    </h3>

                                </div>


                            </div>


                            <div
                            id="editRotorLifecycleHint"
                            class="rotor-edit-lifecycle-hint">


                                <i class="bi bi-info-circle"></i>


                                <span>

                                    Editing availability depends on
                                    the current Rental status.

                                </span>


                            </div>


                        </div>


                        <div class="row">


                            <!-- =========================================
                                 RENTAL DATE
                            ========================================== -->

                            <div class="col-md-4 mb-3">


                                <label>

                                    Rental Date

                                </label>


                                <input
                                type="hidden"
                                id="editRotorDate"
                                name="rental_date"
                                required>


                                <div class="rotor-date-dropdown">


                                    <button
                                    type="button"
                                    id="editRotorDateBtn"
                                    class="
                                        rotor-input
                                        rotor-date-btn
                                        rotor-edit-control
                                    ">


                                        <span id="editRotorDateText">

                                            Select Rental Date

                                        </span>


                                        <i class="bi bi-calendar3"></i>


                                    </button>


                                    <div
                                    id="editRotorCalendar"
                                    class="rotor-calendar">


                                        <div class="rotor-calendar-header">


                                            <button
                                            type="button"
                                            id="editRotorCalendarPrev"
                                            class="rotor-calendar-nav">

                                                <i class="bi bi-chevron-left"></i>

                                            </button>


                                            <div
                                            id="editRotorCalendarMonth"
                                            class="rotor-calendar-month">

                                                Month Year

                                            </div>


                                            <button
                                            type="button"
                                            id="editRotorCalendarNext"
                                            class="rotor-calendar-nav">

                                                <i class="bi bi-chevron-right"></i>

                                            </button>


                                        </div>


                                        <div class="rotor-calendar-weekdays">

                                            <span>Sun</span>
                                            <span>Mon</span>
                                            <span>Tue</span>
                                            <span>Wed</span>
                                            <span>Thu</span>
                                            <span>Fri</span>
                                            <span>Sat</span>

                                        </div>


                                        <div
                                        id="editRotorCalendarDays"
                                        class="rotor-calendar-days">
                                        </div>


                                        <div class="rotor-calendar-legend">


                                            <span>

                                                <i
                                                class="
                                                    rotor-legend-dot
                                                    rotor-legend-available
                                                ">
                                                </i>

                                                Available

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    rotor-legend-dot
                                                    rotor-legend-limited
                                                ">
                                                </i>

                                                Limited

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    rotor-legend-dot
                                                    rotor-legend-booked
                                                ">
                                                </i>

                                                Fully Booked

                                            </span>


                                        </div>


                                    </div>


                                </div>


                            </div>


                            <!-- =========================================
                                 PRIMARY TRACTOR
                            ========================================== -->

                            <div class="col-md-4 mb-3">


                                <label>

                                    Primary Tractor

                                </label>


                                <input
                                type="hidden"
                                id="editRotorTractorID"
                                name="tractor_id"
                                required>


                                <input
                                type="hidden"
                                id="editRotorTractorNo"
                                name="tractor_no">


                                <div
                                class="
                                    rotor-tractor-dropdown
                                    rotor-assignment-dropdown
                                ">


                                    <button
                                    type="button"
                                    id="editRotorTractorBtn"
                                    class="
                                        rotor-input
                                        rotor-tractor-btn
                                        rotor-assignment-btn
                                        rotor-edit-control
                                    ">


                                        <span id="editRotorTractorText">

                                            Select Primary Tractor

                                        </span>


                                        <i class="bi bi-chevron-down"></i>


                                    </button>


                                    <div
                                    id="editRotorTractorMenu"
                                    class="
                                        rotor-tractor-menu
                                        rotor-assignment-menu
                                    ">


                                        <div class="rotor-tractor-empty">

                                            Loading tractors...

                                        </div>


                                    </div>


                                </div>


                            </div>


                            <!-- =========================================
                                 PRIMARY DRIVER
                            ========================================== -->

                            <div class="col-md-4 mb-3">


                                <label>

                                    Primary Driver

                                </label>


                                <input
                                type="hidden"
                                id="editRotorPrimaryDriverID"
                                name="primary_driver_id"
                                required>


                                <div
                                class="
                                    rotor-driver-dropdown
                                    rotor-assignment-dropdown
                                ">


                                    <button
                                    type="button"
                                    id="editRotorPrimaryDriverBtn"
                                    class="
                                        rotor-input
                                        rotor-driver-btn
                                        rotor-assignment-btn
                                        rotor-edit-control
                                    ">


                                        <span id="editRotorPrimaryDriverText">

                                            Select Primary Driver

                                        </span>


                                        <i class="bi bi-chevron-down"></i>


                                    </button>


                                    <div
                                    id="editRotorPrimaryDriverMenu"
                                    class="
                                        rotor-driver-menu
                                        rotor-assignment-menu
                                    ">


                                        <div class="rotor-driver-empty">

                                            Loading drivers...

                                        </div>


                                    </div>


                                </div>


                            </div>


                            <!-- =========================================
                                 SECONDARY TOGGLE
                            ========================================== -->

                            <div class="col-md-12">


                                <button
                                type="button"
                                id="editRotorSecondaryToggle"
                                class="
                                    rotor-secondary-toggle
                                    rotor-edit-secondary-toggle
                                ">


                                    <span class="rotor-secondary-toggle-icon">

                                        <i class="bi bi-plus-lg"></i>

                                    </span>


                                    <span id="editRotorSecondaryToggleText">

                                        ADD SECONDARY ASSIGNMENT

                                    </span>


                                </button>


                            </div>


                            <!-- =========================================
                                 SECONDARY ASSIGNMENT
                            ========================================== -->

                            <div
                            id="editRotorSecondaryAssignment"
                            class="
                                col-md-12
                                rotor-secondary-wrapper
                                rotor-edit-secondary-wrapper
                            ">


                                <div class="row">


                                    <!-- SECONDARY TRACTOR -->

                                    <div class="col-md-6 mb-3">


                                        <label>

                                            Secondary Tractor

                                        </label>


                                        <input
                                        type="hidden"
                                        id="editRotorSecondaryTractorID"
                                        name="secondary_tractor_id">


                                        <input
                                        type="hidden"
                                        id="editRotorSecondaryTractorNo"
                                        name="secondary_tractor_no">


                                        <div
                                        class="
                                            rotor-tractor-dropdown
                                            rotor-assignment-dropdown
                                        ">


                                            <button
                                            type="button"
                                            id="editRotorSecondaryTractorBtn"
                                            class="
                                                rotor-input
                                                rotor-tractor-btn
                                                rotor-assignment-btn
                                                rotor-edit-control
                                            ">


                                                <span id="editRotorSecondaryTractorText">

                                                    Select Secondary Tractor

                                                </span>


                                                <i class="bi bi-chevron-down"></i>


                                            </button>


                                            <div
                                            id="editRotorSecondaryTractorMenu"
                                            class="
                                                rotor-tractor-menu
                                                rotor-assignment-menu
                                            ">


                                                <div class="rotor-tractor-empty">

                                                    Loading tractors...

                                                </div>


                                            </div>


                                        </div>


                                    </div>


                                    <!-- SECONDARY DRIVER -->

                                    <div class="col-md-6 mb-3">


                                        <label>

                                            Secondary Driver

                                        </label>


                                        <input
                                        type="hidden"
                                        id="editRotorSecondaryDriverID"
                                        name="secondary_driver_id">


                                        <div
                                        class="
                                            rotor-driver-dropdown
                                            rotor-assignment-dropdown
                                        ">


                                            <button
                                            type="button"
                                            id="editRotorSecondaryDriverBtn"
                                            class="
                                                rotor-input
                                                rotor-driver-btn
                                                rotor-assignment-btn
                                                rotor-edit-control
                                            ">


                                                <span id="editRotorSecondaryDriverText">

                                                    Select Secondary Driver

                                                </span>


                                                <i class="bi bi-chevron-down"></i>


                                            </button>


                                            <div
                                            id="editRotorSecondaryDriverMenu"
                                            class="
                                                rotor-driver-menu
                                                rotor-assignment-menu
                                            ">


                                                <div class="rotor-driver-empty">

                                                    Loading drivers...

                                                </div>


                                            </div>


                                        </div>


                                    </div>


                                    <!-- SECONDARY RULE -->

                                    <div class="col-md-12">


                                        <div
                                        class="
                                            rotor-secondary-assignment-note
                                            rotor-edit-secondary-note
                                        ">


                                            <i class="bi bi-link-45deg"></i>


                                            <span>

                                                Secondary Tractor and
                                                Secondary Driver must
                                                always be selected together.

                                            </span>


                                        </div>


                                    </div>


                                </div>


                            </div>


                        </div>


                    </section>


                    <!-- =================================================
                         RENTAL SUMMARY
                    ================================================== -->

                    <section
                    class="
                        rotor-form-section
                        rotor-edit-section
                        rotor-edit-financial-section
                    ">


                        <div class="rotor-section-heading">


                            <div class="rotor-section-icon">

                                <i class="bi bi-receipt"></i>

                            </div>


                            <div>

                                <h3>

                                    RENTAL SUMMARY

                                </h3>

                            </div>


                        </div>


                        <div class="rotor-edit-rental-summary">


                            <!-- PRICE / HECTARE -->

                            <div class="rotor-edit-money-summary">


                                <span class="rotor-edit-money-label">

                                    Price per Hectare

                                </span>


                                <div class="rotor-edit-money-value">


                                    <span class="rotor-edit-currency">

                                        ₱

                                    </span>


                                    <input
                                    type="number"
                                    id="editRotorPricePerHectare"
                                    name="price"
                                    value="0.00"
                                    step="0.01"
                                    readonly
                                    tabindex="-1">


                                </div>


                            </div>


                            <!-- DISCOUNT -->

                            <div class="rotor-edit-money-summary">


                                <span class="rotor-edit-money-label">

                                    Member Discount

                                </span>


                                <div class="rotor-edit-money-value">


                                    <span class="rotor-edit-currency">

                                        ₱

                                    </span>


                                    <input
                                    type="number"
                                    id="editRotorDiscount"
                                    name="discounted_price"
                                    value="0.00"
                                    step="0.01"
                                    readonly
                                    tabindex="-1">


                                </div>


                            </div>


                            <!-- STATUS -->

                            <div class="rotor-edit-money-summary">


                                <span class="rotor-edit-money-label">

                                    Rental Status

                                </span>


                                <div class="rotor-edit-status-display">


                                    <span id="editRotorStatusText">

                                        —

                                    </span>


                                </div>


                            </div>


                            <!-- REMARKS -->

                            <div class="rotor-edit-money-summary">


                                <span class="rotor-edit-money-label">

                                    Payment Remarks

                                </span>


                                <div class="rotor-edit-status-display">


                                    <span id="editRotorRemarksText">

                                        —

                                    </span>


                                </div>


                            </div>


                            <!-- TOTAL -->

                            <div
                            class="
                                rotor-edit-money-summary
                                rotor-edit-money-total
                            ">


                                <span class="rotor-edit-money-label">

                                    Total Rental

                                </span>


                                <div class="rotor-edit-money-value">


                                    <span class="rotor-edit-currency">

                                        ₱

                                    </span>


                                    <input
                                    type="number"
                                    id="editRotorTotal"
                                    name="total_price"
                                    value="0.00"
                                    step="0.01"
                                    readonly
                                    tabindex="-1">


                                </div>


                            </div>


                        </div>


                    </section>


                    <!-- =================================================
                         PAYMENT
                    ================================================== -->

                    <section
                    class="
                        rotor-form-section
                        rotor-edit-section
                        rotor-edit-payment-section
                    ">


                        <div class="rotor-section-heading">


                            <div class="rotor-section-icon">

                                <i class="bi bi-cash-coin"></i>

                            </div>


                            <div>

                                <h3>

                                    PAYMENT

                                </h3>

                            </div>


                        </div>


                        <div class="row">


                            <!-- AMOUNT PAID -->

                            <div class="col-md-4 mb-3">


                                <label>

                                    Amount Paid

                                </label>


                                <div class="rotor-edit-money-summary">


                                    <div class="rotor-edit-money-value">


                                        <span class="rotor-edit-currency">

                                            ₱

                                        </span>


                                        <input
                                        type="number"
                                        id="editRotorAmountPaid"
                                        name="amount_paid"
                                        value="0.00"
                                        step="0.01"
                                        readonly
                                        tabindex="-1">


                                    </div>


                                </div>


                            </div>


                            <!-- CURRENT BALANCE -->

                            <div class="col-md-4 mb-3">


                                <label>

                                    Current Balance

                                </label>


                                <div class="rotor-edit-money-summary">


                                    <div class="rotor-edit-money-value">


                                        <span class="rotor-edit-currency">

                                            ₱

                                        </span>


                                        <input
                                        type="number"
                                        id="editRotorBalance"
                                        name="balance"
                                        value="0.00"
                                        step="0.01"
                                        readonly
                                        tabindex="-1">


                                    </div>


                                </div>


                            </div>


                            <!-- ADDITIONAL PAYMENT -->

                            <div class="col-md-4 mb-3">


                                <label>

                                    Additional Payment (₱)

                                </label>


                                <input
                                type="text"
                                inputmode="decimal"
                                id="editRotorAdditionalPayment"
                                name="additional_payment"
                                class="
                                    rotor-input
                                    rotor-money-input
                                    rotor-edit-control
                                "
                                value=""
                                placeholder="0.00"
                                autocomplete="off">


                            </div>


                        </div>


                        <div class="rotor-edit-payment-note rotor-edit-payment-note-compact">


                            <i class="bi bi-info-circle"></i>


                            <span>

                                Enter an additional payment to reduce the
                                current balance. Payment cannot exceed the
                                remaining balance.

                            </span>


                        </div>

                        <!-- =================================================
                            ROTOR PAYMENT HISTORY
                        ================================================== -->

                        <div class="rotor-edit-payment-history">

                            <div class="rotor-edit-payment-history-heading">

                                <h4>
                                    <i class="bi bi-clock-history"></i>
                                    PAYMENT HISTORY
                                </h4>

                                <small>
                                    Review recorded downpayments and additional payments.
                                </small>

                            </div>

                            <div class="rotor-edit-payment-table-wrapper">

                                <table class="rotor-edit-payment-table">

                                    <thead>
                                        <tr>
                                            <th>DATE</th>
                                            <th>TYPE</th>
                                            <th>AMOUNT</th>
                                            <th>BALANCE AFTER</th>
                                            <th>NOTES</th>
                                            <th>RECORDED BY</th>
                                        </tr>
                                    </thead>

                                    <tbody id="editRotorPaymentHistory">

                                        <tr class="rotor-edit-payment-empty">
                                            <td colspan="6">
                                                No payment records available.
                                            </td>
                                        </tr>

                                    </tbody>

                                </table>

                            </div>

                        </div>

                    </section>


                </div>


                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div
                class="
                    rotor-add-footer
                    rotor-edit-footer
                ">


                    <div class="rotor-save-container">


                        <button
                        type="button"
                        class="rotor-cancel-btn"
                        data-bs-dismiss="modal">

                            CLOSE

                        </button>


                        <button
                        type="button"
                        id="updateRotorBtn"
                        class="rotor-save-btn">

                            SAVE CHANGES

                        </button>


                    </div>


                </div>


            </form>


        </div>


    </div>


</div>