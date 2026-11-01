<?php

/* =========================================================
   SPIS - EDIT HAULING RENTAL MODAL

   EDITING PRINCIPLES

   ALWAYS READ-ONLY:
   - Transaction ID
   - Affiliate
   - Customer Name
   - Service Location
   - No. of Hauling
   - Rental Price
   - Member Discount
   - Total Rental
   - Rental Status
   - Payment Remarks

   EDITABLE DEPENDING ON RENTAL STATUS:

   PENDING
   - Rental Date
   - Primary Tractor
   - Primary Driver
   - Secondary Tractor
   - Secondary Driver

   ONGOING
   - Primary assignment becomes locked
   - Secondary assignment may still be managed

   DONE
   - Entire rental becomes read-only

   IMPORTANT:
   - Date-only scheduling
   - No Service Time
   - No manual Operator field
   - Secondary Tractor + Driver must be paired
   - Payment is managed from the ledger
========================================================= */


/* =========================================================
   CSRF TOKEN
========================================================= */

if (
    empty($_SESSION['rental_csrf_token'])
) {

    $_SESSION['rental_csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}


$rentalCsrfToken =
    (string) $_SESSION['rental_csrf_token'];

?>


<!-- =========================================================
     EDIT HAULING RENTAL MODAL
========================================================= -->

<div
class="modal fade"
id="editHaulingModal"
tabindex="-1"
aria-labelledby="editHaulingModalTitle"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="
            modal-content
            hauling-window
            hauling-add-window
            hauling-edit-window
        ">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div
            class="
                hauling-header
                hauling-add-header
                hauling-edit-header
            ">

                <div class="hauling-add-heading">

                    <div class="hauling-edit-title-row">

                        <div>

                            <h1 id="editHaulingModalTitle">
                                EDIT HAULING RENTAL
                            </h1>


                            <div class="hauling-edit-header-sub">

                                Review the rental record and update
                                its assignment when allowed.

                            </div>

                        </div>


                        <span
                        id="editHaulingTransactionBadge"
                        class="hauling-edit-transaction-badge">

                            HLG-0000

                        </span>

                    </div>

                </div>


                <button
                type="button"
                class="hauling-close-btn"
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
            id="editHaulingForm"
            method="POST"
            novalidate>


                <input
                type="hidden"
                id="editHaulingCsrfToken"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $rentalCsrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">


                <!-- =============================================
                     RECORD IDENTIFIERS
                ============================================== -->

                <input
                type="hidden"
                id="editHaulingID"
                name="hauling_id">


                <input
                type="hidden"
                id="editHaulingTransactionID"
                name="transaction_id">


                <input
                type="hidden"
                id="editHaulingAffiliate"
                name="affiliate">


                <input
                type="hidden"
                id="editHaulingMemberID"
                name="member_id">


                <input
                type="hidden"
                id="editHaulingStatus"
                name="status">


                <!-- =================================================
                     SCROLLABLE BODY
                ================================================== -->

                <div class="hauling-add-body hauling-edit-body">


                    <!-- =================================================
                         CUSTOMER INFORMATION
                         READ ONLY
                    ================================================== -->

                    <section
                    class="
                        hauling-form-section
                        hauling-edit-section
                        hauling-edit-summary-section
                    ">


                        <div class="hauling-section-heading">

                            <div class="hauling-section-icon">

                                <i class="bi bi-person-vcard"></i>

                            </div>


                            <div>

                                <h3>
                                    CUSTOMER DETAILS
                                </h3>

                            </div>

                        </div>


                        <div class="hauling-edit-summary-grid">


                            <!-- =========================================
                                 CUSTOMER NAME
                            ========================================== -->

                            <div
                            class="
                                hauling-edit-summary-item
                                hauling-edit-summary-item-wide
                            ">

                                <div class="hauling-edit-summary-label">

                                    <i class="bi bi-person"></i>

                                    Customer

                                </div>


                                <input
                                type="text"
                                id="editHaulingCustomerName"
                                name="customer_name"
                                class="hauling-edit-summary-input"
                                readonly
                                tabindex="-1">

                            </div>


                            <!-- =========================================
                                 AFFILIATE
                            ========================================== -->

                            <div class="hauling-edit-summary-item">

                                <div class="hauling-edit-summary-label">

                                    <i class="bi bi-people"></i>

                                    Affiliate

                                </div>


                                <div
                                id="editAffiliateText"
                                class="hauling-edit-summary-value">

                                    —

                                </div>

                            </div>


                            <!-- =========================================
                                 LOCATION
                            ========================================== -->

                            <div
                            class="
                                hauling-edit-summary-item
                                hauling-edit-summary-item-wide
                            ">

                                <div class="hauling-edit-summary-label">

                                    <i class="bi bi-geo-alt"></i>

                                    Service Location

                                </div>


                                <input
                                type="text"
                                id="editHaulingLocation"
                                name="location"
                                class="hauling-edit-summary-input"
                                readonly
                                tabindex="-1">

                            </div>


                            <!-- =========================================
                                 HAULING COUNT
                            ========================================== -->

                            <div class="hauling-edit-summary-item">

                                <div class="hauling-edit-summary-label">

                                    <i class="bi bi-arrow-repeat"></i>

                                    No. of Hauling

                                </div>


                                <input
                                type="number"
                                id="editHaulingCount"
                                name="hauling_count"
                                class="hauling-edit-summary-input"
                                readonly
                                tabindex="-1">

                            </div>


                        </div>

                    </section>



                    <!-- =================================================
                         RENTAL SCHEDULE & ASSIGNMENT
                    ================================================== -->

                    <section
                    class="
                        hauling-form-section
                        hauling-edit-section
                        hauling-edit-working-section
                    ">


                        <div class="hauling-edit-section-top">


                            <div class="hauling-section-heading">

                                <div class="hauling-section-icon">

                                    <i class="bi bi-calendar2-check"></i>

                                </div>


                                <div>

                                    <h3>
                                        RENTAL SCHEDULE & ASSIGNMENT
                                    </h3>

                                </div>

                            </div>


                            <!-- =========================================
                                 LIFECYCLE NOTICE

                                 JS will later change its text / state
                                 depending on Pending / Ongoing / Done.
                            ========================================== -->

                            <div
                            id="editHaulingLifecycleHint"
                            class="hauling-edit-lifecycle-hint">

                                <i class="bi bi-info-circle"></i>

                                <span>
                                    Editing availability depends on
                                    the rental status.
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
                                id="editHaulingDate"
                                name="rental_date"
                                required>


                                <div class="hauling-date-dropdown">


                                    <button
                                    type="button"
                                    id="editHaulingDateBtn"
                                    class="
                                        hauling-input
                                        hauling-date-btn
                                        hauling-edit-control
                                    ">

                                        <span id="editHaulingDateText">
                                            Select Rental Date
                                        </span>


                                        <i class="bi bi-calendar3"></i>

                                    </button>



                                    <!-- =================================
                                         CALENDAR
                                    ================================== -->

                                    <div
                                    id="editHaulingCalendar"
                                    class="hauling-calendar">


                                        <div class="hauling-calendar-header">


                                            <button
                                            type="button"
                                            id="editHaulingCalendarPrev"
                                            class="hauling-calendar-nav">

                                                <i class="bi bi-chevron-left"></i>

                                            </button>


                                            <div
                                            id="editHaulingCalendarMonth"
                                            class="hauling-calendar-month">
                                            </div>


                                            <button
                                            type="button"
                                            id="editHaulingCalendarNext"
                                            class="hauling-calendar-nav">

                                                <i class="bi bi-chevron-right"></i>

                                            </button>


                                        </div>



                                        <div class="hauling-calendar-weekdays">

                                            <span>Sun</span>
                                            <span>Mon</span>
                                            <span>Tue</span>
                                            <span>Wed</span>
                                            <span>Thu</span>
                                            <span>Fri</span>
                                            <span>Sat</span>

                                        </div>



                                        <div
                                        id="editHaulingCalendarDays"
                                        class="hauling-calendar-days">
                                        </div>



                                        <div class="hauling-calendar-legend">

                                            <span>

                                                <i
                                                class="
                                                    hauling-legend-dot
                                                    hauling-legend-available
                                                ">
                                                </i>

                                                Available

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    hauling-legend-dot
                                                    hauling-legend-limited
                                                ">
                                                </i>

                                                Limited

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    hauling-legend-dot
                                                    hauling-legend-booked
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
                                id="editHaulingTractorID"
                                name="tractor_id"
                                required>


                                <input
                                type="hidden"
                                id="editHaulingTractorNo"
                                name="tractor_no">


                                <div
                                class="
                                    hauling-tractor-dropdown
                                    hauling-assignment-dropdown
                                ">


                                    <button
                                    type="button"
                                    id="editHaulingTractorBtn"
                                    class="
                                        hauling-input
                                        hauling-tractor-btn
                                        hauling-assignment-btn
                                        hauling-edit-control
                                    ">

                                        <span id="editHaulingTractorText">
                                            Select Primary Tractor
                                        </span>


                                        <i class="bi bi-chevron-down"></i>

                                    </button>



                                    <div
                                    id="editHaulingTractorMenu"
                                    class="
                                        hauling-tractor-menu
                                        hauling-assignment-menu
                                    ">

                                        <div class="hauling-tractor-empty">

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
                                id="editHaulingPrimaryDriverID"
                                name="primary_driver_id"
                                required>


                                <div
                                class="
                                    hauling-tractor-dropdown
                                    hauling-assignment-dropdown
                                ">


                                    <button
                                    type="button"
                                    id="editHaulingPrimaryDriverBtn"
                                    class="
                                        hauling-input
                                        hauling-tractor-btn
                                        hauling-assignment-btn
                                        hauling-edit-control
                                    ">

                                        <span id="editHaulingPrimaryDriverText">
                                            Select Primary Driver
                                        </span>


                                        <i class="bi bi-chevron-down"></i>

                                    </button>



                                    <div
                                    id="editHaulingPrimaryDriverMenu"
                                    class="
                                        hauling-tractor-menu
                                        hauling-assignment-menu
                                    ">

                                        <div class="hauling-tractor-empty">

                                            Loading drivers...

                                        </div>

                                    </div>


                                </div>

                            </div>



                            <!-- =========================================
                                 SECONDARY ASSIGNMENT
                            ========================================== -->

                            <div class="col-md-12">


                                <button
                                type="button"
                                id="editHaulingSecondaryToggle"
                                class="
                                    hauling-secondary-toggle
                                    hauling-edit-secondary-toggle
                                ">


                                    <span class="hauling-secondary-toggle-icon">

                                        <i class="bi bi-plus-lg"></i>

                                    </span>


                                    <span id="editHaulingSecondaryToggleText">

                                        ADD SECONDARY ASSIGNMENT

                                    </span>


                                </button>

                            </div>



                            <!-- =========================================
                                 SECONDARY CONTENT
                            ========================================== -->

                            <div
                            id="editHaulingSecondaryAssignment"
                            class="
                                col-md-12
                                hauling-secondary-wrapper
                                hauling-edit-secondary-wrapper
                            ">


                                <div class="row">


                                    <!-- =============================
                                         SECONDARY TRACTOR
                                    ============================== -->

                                    <div class="col-md-6 mb-3">

                                        <label>

                                            Secondary Tractor

                                        </label>


                                        <input
                                        type="hidden"
                                        id="editHaulingSecondaryTractorID"
                                        name="secondary_tractor_id">


                                        <input
                                        type="hidden"
                                        id="editHaulingSecondaryTractorNo"
                                        name="secondary_tractor_no">


                                        <div
                                        class="
                                            hauling-tractor-dropdown
                                            hauling-assignment-dropdown
                                        ">


                                            <button
                                            type="button"
                                            id="editHaulingSecondaryTractorBtn"
                                            class="
                                                hauling-input
                                                hauling-tractor-btn
                                                hauling-assignment-btn
                                                hauling-edit-control
                                            ">

                                                <span id="editHaulingSecondaryTractorText">

                                                    No Secondary Tractor

                                                </span>


                                                <i class="bi bi-chevron-down"></i>

                                            </button>



                                            <div
                                            id="editHaulingSecondaryTractorMenu"
                                            class="
                                                hauling-tractor-menu
                                                hauling-assignment-menu
                                            ">


                                                <div
                                                class="
                                                    hauling-tractor-item
                                                    hauling-assignment-clear
                                                "
                                                data-action="clear">


                                                    <div class="hauling-tractor-details">

                                                        <span class="hauling-tractor-number">
                                                            NONE
                                                        </span>


                                                        <span class="hauling-tractor-name">
                                                            No Secondary Tractor
                                                        </span>

                                                    </div>


                                                </div>

                                            </div>


                                        </div>

                                    </div>



                                    <!-- =============================
                                         SECONDARY DRIVER
                                    ============================== -->

                                    <div class="col-md-6 mb-3">

                                        <label>

                                            Secondary Driver

                                        </label>


                                        <input
                                        type="hidden"
                                        id="editHaulingSecondaryDriverID"
                                        name="secondary_driver_id">


                                        <div
                                        class="
                                            hauling-tractor-dropdown
                                            hauling-assignment-dropdown
                                        ">


                                            <button
                                            type="button"
                                            id="editHaulingSecondaryDriverBtn"
                                            class="
                                                hauling-input
                                                hauling-tractor-btn
                                                hauling-assignment-btn
                                                hauling-edit-control
                                            ">

                                                <span id="editHaulingSecondaryDriverText">

                                                    No Secondary Driver

                                                </span>


                                                <i class="bi bi-chevron-down"></i>

                                            </button>



                                            <div
                                            id="editHaulingSecondaryDriverMenu"
                                            class="
                                                hauling-tractor-menu
                                                hauling-assignment-menu
                                            ">


                                                <div
                                                class="
                                                    hauling-tractor-item
                                                    hauling-assignment-clear
                                                "
                                                data-action="clear">


                                                    <div class="hauling-tractor-details">

                                                        <span class="hauling-tractor-number">
                                                            NONE
                                                        </span>


                                                        <span class="hauling-tractor-name">
                                                            No Secondary Driver
                                                        </span>

                                                    </div>


                                                </div>

                                            </div>


                                        </div>

                                    </div>



                                    <!-- =============================
                                         SECONDARY PAIR RULE
                                    ============================== -->

                                    <div class="col-md-12">

                                        <div
                                        class="
                                            hauling-secondary-assignment-note
                                            hauling-edit-secondary-note
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
                         READ ONLY
                    ================================================== -->

                    <section
                    class="
                        hauling-form-section
                        hauling-edit-section
                        hauling-edit-financial-section
                    ">


                        <div class="hauling-section-heading">

                            <div class="hauling-section-icon">

                                <i class="bi bi-receipt"></i>

                            </div>


                            <div>

                                <h3>
                                    RENTAL SUMMARY
                                </h3>

                            </div>

                        </div>



                        <div class="hauling-edit-rental-summary">


                            <!-- =========================================
                                 RENTAL PRICE
                            ========================================== -->

                            <div class="hauling-edit-money-summary">

                                <span class="hauling-edit-money-label">

                                    Rental Price

                                </span>


                                <div class="hauling-edit-money-value">

                                    <span class="hauling-edit-currency">
                                        ₱
                                    </span>


                                    <input
                                    type="number"
                                    id="editHaulingPrice"
                                    name="price"
                                    value="0.00"
                                    step="0.01"
                                    readonly
                                    tabindex="-1">

                                </div>

                            </div>



                            <!-- =========================================
                                 DISCOUNT
                            ========================================== -->

                            <div class="hauling-edit-money-summary">

                                <span class="hauling-edit-money-label">

                                    Member Discount

                                </span>


                                <div class="hauling-edit-money-value">

                                    <span class="hauling-edit-currency">
                                        ₱
                                    </span>


                                    <input
                                    type="number"
                                    id="editHaulingDiscount"
                                    name="discounted_price"
                                    value="0.00"
                                    step="0.01"
                                    readonly
                                    tabindex="-1">

                                </div>

                            </div>



                            <!-- =========================================
                                 STATUS
                            ========================================== -->

                            <div class="hauling-edit-money-summary">

                                <span class="hauling-edit-money-label">

                                    Rental Status

                                </span>


                                <div
                                class="
                                    hauling-edit-status-display
                                ">

                                    <span
                                    id="editHaulingStatusText">

                                        —

                                    </span>

                                </div>

                            </div>



                            <!-- =========================================
                                 TOTAL
                            ========================================== -->

                            <div
                            class="
                                hauling-edit-money-summary
                                hauling-edit-money-total
                            ">

                                <span class="hauling-edit-money-label">

                                    Total Rental

                                </span>


                                <div class="hauling-edit-money-value">

                                    <span class="hauling-edit-currency">
                                        ₱
                                    </span>


                                    <input
                                    type="number"
                                    id="editHaulingTotal"
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
                         PAYMENT NOTICE
                    ================================================== -->

                    <div
                    class="
                        hauling-edit-payment-note
                        hauling-edit-payment-note-compact
                    ">


                        <i class="bi bi-shield-check"></i>


                        <span>

                            Payment is managed separately through
                            <strong>Remarks</strong> in the Hauling ledger.

                        </span>


                    </div>


                </div>



                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div class="hauling-add-footer hauling-edit-footer">


                    <div class="hauling-save-container">


                        <button
                        type="button"
                        class="hauling-cancel-btn"
                        data-bs-dismiss="modal">

                            CLOSE

                        </button>


                        <button
                        type="button"
                        id="updateHaulingBtn"
                        class="hauling-save-btn">

                            SAVE CHANGES

                        </button>


                    </div>

                </div>


            </form>

        </div>

    </div>

</div>