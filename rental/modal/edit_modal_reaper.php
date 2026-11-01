<?php

/* =========================================================
   SPIS - EDIT REAPER RENTAL MODAL

   REAPER EDIT FLOW

   - Customer / Affiliate / Rental Date / Location
     remain read-only.

   - Assignment may be updated:
     Primary Tractor
     Primary Driver
     Optional Secondary Tractor + Driver pair

   - Driver comes from Driver Registry.
   - No manual Operator field.

   - Reaper lifecycle remains:
     Pending / Ongoing = Unpaid only
     Done = Unpaid or Paid
     Done requires Harvest + Commodity
     Paid = finalized
========================================================= */

?>


<!-- =========================================================
     EDIT REAPER RENTAL
========================================================= -->

<div
class="modal fade"
id="editReaperModal"
tabindex="-1"
aria-labelledby="editReaperModalTitle"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="
            modal-content
            reaper-window
            reaper-edit-window
        ">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div
            class="
                reaper-header
                reaper-edit-header
            ">

                <div class="reaper-edit-heading">

                    <div class="reaper-edit-title-row">

                        <h1 id="editReaperModalTitle">
                            EDIT REAPER RENTAL
                        </h1>


                        <span
                        id="editReaperTransactionBadge"
                        class="reaper-edit-transaction-badge">

                            RPR-0000

                        </span>

                    </div>


                    <p class="reaper-edit-header-sub">

                        Review the rental assignment and completed
                        service details before saving any changes.

                    </p>

                </div>


                <button
                type="button"
                class="reaper-close-btn"
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
            id="editReaperForm"
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


                <!-- RECORD ID -->

                <input
                type="hidden"
                id="editReaperID"
                name="reaper_id">


                <!-- AFFILIATE -->

                <input
                type="hidden"
                id="editReaperAffiliate"
                name="affiliate">


                <div class="reaper-edit-body">


                    <!-- =================================================
                         CUSTOMER / RENTAL DETAILS
                    ================================================== -->

                    <section
                    class="
                        reaper-edit-section
                        reaper-edit-summary-section
                    ">


                        <div
                        class="
                            reaper-section-heading
                            reaper-edit-section-heading
                        ">

                            <div class="reaper-section-icon">

                                <i class="bi bi-person-vcard"></i>

                            </div>


                            <div>

                                <h3>
                                    CUSTOMER &amp; RENTAL DETAILS
                                </h3>

                            </div>

                        </div>


                        <div class="reaper-edit-summary-grid">


                            <!-- CUSTOMER -->

                            <div
                            class="
                                reaper-edit-summary-item
                                reaper-edit-summary-item-wide
                            ">

                                <span class="reaper-edit-summary-label">

                                    <i class="bi bi-person"></i>

                                    Customer

                                </span>


                                <input
                                type="text"
                                id="editReaperCustomerName"
                                name="customer_name"
                                class="reaper-edit-summary-input"
                                readonly
                                tabindex="-1">

                            </div>


                            <!-- AFFILIATE -->

                            <div class="reaper-edit-summary-item">

                                <span class="reaper-edit-summary-label">

                                    <i class="bi bi-people"></i>

                                    Affiliate

                                </span>


                                <span
                                id="editReaperAffiliateText"
                                class="reaper-edit-summary-value">

                                    —

                                </span>

                            </div>


                            <!-- RENTAL DATE / RESCHEDULE -->

                            <div class="reaper-edit-summary-item">

                                <span class="reaper-edit-summary-label">

                                    <i class="bi bi-calendar3"></i>

                                    Rental Date

                                </span>


                                <input
                                type="hidden"
                                id="editReaperDate"
                                name="rental_date">


                                <div
                                class="
                                    reaper-date-dropdown
                                    reaper-edit-date-dropdown
                                ">

                                    <button
                                    type="button"
                                    id="editReaperDateBtn"
                                    class="
                                        reaper-date-btn
                                        reaper-edit-date-btn
                                    ">

                                        <span id="editReaperDateText">
                                            Select Rental Date
                                        </span>

                                        <i class="bi bi-calendar3"></i>

                                    </button>


                                    <div
                                    id="editReaperCalendar"
                                    class="reaper-calendar">


                                        <div class="reaper-calendar-header">

                                            <button
                                            type="button"
                                            id="editReaperCalendarPrev"
                                            class="reaper-calendar-nav">

                                                <i class="bi bi-chevron-left"></i>

                                            </button>


                                            <div
                                            id="editReaperCalendarMonth"
                                            class="reaper-calendar-month">
                                            </div>


                                            <button
                                            type="button"
                                            id="editReaperCalendarNext"
                                            class="reaper-calendar-nav">

                                                <i class="bi bi-chevron-right"></i>

                                            </button>

                                        </div>


                                        <div class="reaper-calendar-weekdays">

                                            <span>Sun</span>
                                            <span>Mon</span>
                                            <span>Tue</span>
                                            <span>Wed</span>
                                            <span>Thu</span>
                                            <span>Fri</span>
                                            <span>Sat</span>

                                        </div>


                                        <div
                                        id="editReaperCalendarDays"
                                        class="reaper-calendar-days">
                                        </div>


                                        <div class="reaper-calendar-legend">

                                            <span>

                                                <i
                                                class="
                                                    reaper-legend-dot
                                                    reaper-legend-available
                                                ">
                                                </i>

                                                Available

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    reaper-legend-dot
                                                    reaper-legend-limited
                                                ">
                                                </i>

                                                Limited

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    reaper-legend-dot
                                                    reaper-legend-booked
                                                ">
                                                </i>

                                                Fully Booked

                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- LOCATION -->

                            <div
                            class="
                                reaper-edit-summary-item
                                reaper-edit-summary-item-wide
                            ">

                                <span class="reaper-edit-summary-label">

                                    <i class="bi bi-geo-alt"></i>

                                    Service Location

                                </span>


                                <input
                                type="text"
                                id="editReaperLocation"
                                name="location"
                                class="reaper-edit-summary-input"
                                readonly
                                tabindex="-1">

                            </div>

                        </div>

                    </section>


                    <!-- =================================================
                         RENTAL ASSIGNMENT
                    ================================================== -->

                    <section
                    class="
                        reaper-edit-section
                        reaper-edit-assignment-section
                    ">


                        <div
                        class="
                            reaper-section-heading
                            reaper-edit-section-heading
                        ">

                            <div class="reaper-section-icon">

                                <i class="bi bi-truck"></i>

                            </div>


                            <div>

                                <h3>
                                    RENTAL ASSIGNMENT
                                </h3>

                            </div>

                        </div>


                        <div class="row">


                            <!-- =================================================
                                 PRIMARY TRACTOR
                            ================================================== -->

                            <div class="col-md-6 mb-3">

                                <label>
                                    Primary Tractor
                                </label>


                                <input
                                type="hidden"
                                id="editReaperTractorID"
                                name="tractor_id"
                                required>


                                <input
                                type="hidden"
                                id="editReaperTractorNo"
                                name="tractor_no">


                                <div
                                class="
                                    reaper-tractor-dropdown
                                    reaper-assignment-dropdown
                                ">

                                    <button
                                    type="button"
                                    id="editReaperTractorBtn"
                                    class="
                                        reaper-input
                                        reaper-tractor-btn
                                        reaper-assignment-btn
                                    ">

                                        <span id="editReaperTractorText">
                                            Select Primary Tractor
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="editReaperTractorMenu"
                                    class="
                                        reaper-tractor-menu
                                        reaper-assignment-menu
                                    ">

                                        <div class="reaper-tractor-empty">
                                            Loading tractors...
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                                 PRIMARY DRIVER
                            ================================================== -->

                            <div class="col-md-6 mb-3">

                                <label>
                                    Primary Driver
                                </label>


                                <input
                                type="hidden"
                                id="editReaperPrimaryDriverID"
                                name="primary_driver_id"
                                required>


                                <div
                                class="
                                    reaper-tractor-dropdown
                                    reaper-assignment-dropdown
                                ">

                                    <button
                                    type="button"
                                    id="editReaperPrimaryDriverBtn"
                                    class="
                                        reaper-input
                                        reaper-tractor-btn
                                        reaper-assignment-btn
                                    ">

                                        <span id="editReaperPrimaryDriverText">
                                            Select Primary Driver
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="editReaperPrimaryDriverMenu"
                                    class="
                                        reaper-tractor-menu
                                        reaper-assignment-menu
                                    ">

                                        <div class="reaper-tractor-empty">
                                            Loading drivers...
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                                 SECONDARY ASSIGNMENT TOGGLE
                            ================================================== -->

                            <div class="col-md-12">

                                <button
                                type="button"
                                id="editReaperSecondaryToggle"
                                class="reaper-secondary-toggle">

                                    <span class="reaper-secondary-toggle-icon">

                                        <i class="bi bi-plus-lg"></i>

                                    </span>


                                    <span id="editReaperSecondaryToggleText">

                                        ADD SECONDARY ASSIGNMENT

                                    </span>

                                </button>

                            </div>


                            <!-- =================================================
                                 SECONDARY ASSIGNMENT
                            ================================================== -->

                            <div
                            id="editReaperSecondaryAssignment"
                            class="
                                col-md-12
                                reaper-secondary-wrapper
                            ">

                                <div class="row">


                                    <!-- =============================================
                                         SECONDARY TRACTOR
                                    ============================================== -->

                                    <div class="col-md-6 mb-3">

                                        <label>

                                            Secondary Tractor

                                            <span class="reaper-optional-label">
                                                (Optional)
                                            </span>

                                        </label>


                                        <input
                                        type="hidden"
                                        id="editReaperSecondaryTractorID"
                                        name="secondary_tractor_id">


                                        <input
                                        type="hidden"
                                        id="editReaperSecondaryTractorNo"
                                        name="secondary_tractor_no">


                                        <div
                                        class="
                                            reaper-tractor-dropdown
                                            reaper-assignment-dropdown
                                        ">

                                            <button
                                            type="button"
                                            id="editReaperSecondaryTractorBtn"
                                            class="
                                                reaper-input
                                                reaper-tractor-btn
                                                reaper-assignment-btn
                                            ">

                                                <span id="editReaperSecondaryTractorText">
                                                    No Secondary Tractor
                                                </span>

                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="editReaperSecondaryTractorMenu"
                                            class="
                                                reaper-tractor-menu
                                                reaper-assignment-menu
                                            ">

                                                <div
                                                class="
                                                    reaper-tractor-item
                                                    reaper-assignment-clear
                                                "
                                                data-action="clear">

                                                    <div class="reaper-tractor-details">

                                                        <span class="reaper-tractor-number">
                                                            NONE
                                                        </span>

                                                        <span class="reaper-tractor-name">
                                                            No Secondary Tractor
                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- =============================================
                                         SECONDARY DRIVER
                                    ============================================== -->

                                    <div class="col-md-6 mb-3">

                                        <label>

                                            Secondary Driver

                                            <span class="reaper-optional-label">
                                                (Optional)
                                            </span>

                                        </label>


                                        <input
                                        type="hidden"
                                        id="editReaperSecondaryDriverID"
                                        name="secondary_driver_id">


                                        <div
                                        class="
                                            reaper-tractor-dropdown
                                            reaper-assignment-dropdown
                                        ">

                                            <button
                                            type="button"
                                            id="editReaperSecondaryDriverBtn"
                                            class="
                                                reaper-input
                                                reaper-tractor-btn
                                                reaper-assignment-btn
                                            ">

                                                <span id="editReaperSecondaryDriverText">
                                                    No Secondary Driver
                                                </span>

                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="editReaperSecondaryDriverMenu"
                                            class="
                                                reaper-tractor-menu
                                                reaper-assignment-menu
                                            ">

                                                <div
                                                class="
                                                    reaper-tractor-item
                                                    reaper-assignment-clear
                                                "
                                                data-action="clear">

                                                    <div class="reaper-tractor-details">

                                                        <span class="reaper-tractor-number">
                                                            NONE
                                                        </span>

                                                        <span class="reaper-tractor-name">
                                                            No Secondary Driver
                                                        </span>

                                                    </div>

                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- =============================================
                                         SECONDARY NOTE
                                    ============================================== -->

                                    <div class="col-md-12">

                                        <div
                                        id="editReaperSecondaryAssignmentNote"
                                        class="reaper-secondary-assignment-note">

                                            <i class="bi bi-info-circle"></i>

                                            <span>

                                                Secondary Tractor and Secondary Driver
                                                must be selected together.

                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- =================================================
                         SERVICE COMPLETION
                    ================================================== -->

                    <section
                    class="
                        reaper-edit-section
                        reaper-edit-working-section
                    ">


                        <div class="reaper-edit-section-top">


                            <div
                            class="
                                reaper-section-heading
                                reaper-edit-section-heading
                            ">

                                <div class="reaper-section-icon">

                                    <i class="bi bi-clipboard2-check"></i>

                                </div>


                                <div>

                                    <h3>
                                        SERVICE COMPLETION
                                    </h3>

                                </div>

                            </div>


                            <div
                            id="editReaperLifecycleHint"
                            class="reaper-edit-lifecycle-hint">

                                <i class="bi bi-info-circle"></i>

                                <span>

                                    Complete Harvest and Commodity before
                                    marking the rental Done.

                                </span>

                            </div>

                        </div>


                        <div class="reaper-edit-control-grid">


                            <!-- =================================================
                                 NO. OF HARVEST
                            ================================================== -->

                            <div class="reaper-edit-field">

                                <label for="editReaperHarvest">
                                    No. of Harvest
                                </label>


                                <input
                                type="number"
                                id="editReaperHarvest"
                                name="no_of_harvest"
                                class="
                                    reaper-input
                                    reaper-edit-control
                                "
                                min="1"
                                step="1"
                                placeholder="Enter harvest count">

                            </div>


                            <!-- =================================================
                                 COMMODITY
                            ================================================== -->

                            <div class="reaper-edit-field">

                                <label for="editReaperCommodity">
                                    Commodity
                                </label>


                                <input
                                type="number"
                                id="editReaperCommodity"
                                name="commodity"
                                class="
                                    reaper-input
                                    reaper-edit-control
                                "
                                min="0"
                                step="0.01"
                                placeholder="Enter commodity">

                            </div>


                            <!-- =================================================
                                 STATUS
                            ================================================== -->

                            <div class="reaper-edit-field">

                                <label>
                                    Rental Status
                                </label>


                                <input
                                type="hidden"
                                id="editReaperStatus"
                                name="status"
                                required>


                                <div class="reaper-status-dropdown">

                                    <button
                                    type="button"
                                    id="editReaperStatusBtn"
                                    class="
                                        reaper-status-btn
                                        reaper-edit-control
                                    ">

                                        <span id="editReaperStatusText">
                                            Select Status
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="editReaperStatusMenu"
                                    class="reaper-status-menu">


                                        <div
                                        class="reaper-status-item"
                                        data-value="Pending">

                                            Pending

                                        </div>


                                        <div
                                        class="reaper-status-item"
                                        data-value="Ongoing">

                                            Ongoing

                                        </div>


                                        <div
                                        class="reaper-status-item"
                                        data-value="Done">

                                            Done

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- =================================================
                         PAYMENT STATUS
                    ================================================== -->

                    <section
                    class="
                        reaper-edit-section
                        reaper-edit-payment-section
                    ">


                        <div
                        class="
                            reaper-section-heading
                            reaper-edit-section-heading
                        ">

                            <div class="reaper-section-icon">

                                <i class="bi bi-cash-coin"></i>

                            </div>


                            <div>

                                <h3>
                                    PAYMENT STATUS
                                </h3>

                            </div>

                        </div>


                        <div class="reaper-edit-payment-grid">


                            <div class="reaper-edit-field">

                                <label>
                                    Payment Remarks
                                </label>


                                <input
                                type="hidden"
                                id="editReaperRemarks"
                                name="remarks"
                                required>


                                <div class="reaper-remarks-dropdown">

                                    <button
                                    type="button"
                                    id="editReaperRemarksBtn"
                                    class="
                                        reaper-remarks-btn
                                        reaper-edit-control
                                    ">

                                        <span id="editReaperRemarksText">
                                            Select Remarks
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="editReaperRemarksMenu"
                                    class="reaper-remarks-menu">


                                        <div
                                        class="reaper-remarks-item"
                                        data-value="Unpaid">

                                            Unpaid

                                        </div>


                                        <div
                                        class="reaper-remarks-item"
                                        data-value="Paid">

                                            Paid

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <div class="reaper-edit-payment-note">

                                <i class="bi bi-shield-check"></i>


                                <div>

                                    <strong>

                                        Payment is finalized at the end
                                        of service.

                                    </strong>


                                    <span>

                                        Pending and Ongoing rentals stay
                                        Unpaid. A Done rental may remain
                                        Unpaid until payment is collected.
                                        Once Paid, the transaction is locked.

                                    </span>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>


                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div class="reaper-edit-footer">

                    <div class="reaper-edit-actions">


                        <button
                        type="button"
                        class="reaper-edit-cancel-btn"
                        data-bs-dismiss="modal">

                            CLOSE

                        </button>


                        <button
                        type="button"
                        id="updateReaperBtn"
                        class="
                            reaper-save-btn
                            reaper-edit-save-btn
                        ">

                            SAVE CHANGES

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>