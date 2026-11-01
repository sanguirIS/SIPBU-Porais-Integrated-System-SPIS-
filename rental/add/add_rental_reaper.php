<?php

/* =========================================================
   SPIS - ADD REAPER RENTAL MODAL

   RENTAL ASSIGNMENT FLOW

   1. Rental Date
   2. Primary Tractor
   3. Primary Driver
   4. Optional Secondary Tractor
   5. Optional Secondary Driver

   IMPORTANT:
   - Date-only scheduling
   - No Service Time
   - No manual Operator input
   - Drivers come from Driver Registry
   - Tractor / Driver availability is loaded by JS
   - Secondary Tractor + Driver must be selected as a pair
========================================================= */

?>


<!-- =========================================================
     ADD REAPER RENTAL
========================================================= -->

<div
class="modal fade"
id="addReaperModal"
tabindex="-1"
aria-labelledby="addReaperModalTitle"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="
            modal-content
            reaper-window
            reaper-add-window
        ">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div
            class="
                reaper-header
                reaper-add-header
            ">

                <div class="reaper-add-heading">

                    <h1 id="addReaperModalTitle">
                        ADD REAPER RENTAL
                    </h1>

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
            id="reaperForm"
            action="save/save_reaper.php"
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


                <div class="reaper-add-body">


                    <!-- =================================================
                         CUSTOMER DETAILS
                    ================================================== -->

                    <section class="reaper-form-section">


                        <div class="reaper-section-heading">

                            <div class="reaper-section-icon">

                                <i class="bi bi-person-vcard"></i>

                            </div>


                            <div>

                                <h3>
                                    CUSTOMER DETAILS
                                </h3>

                            </div>

                        </div>


                        <div class="row">


                            <!-- =============================================
                                 AFFILIATE
                            ============================================== -->

                            <div class="col-md-4 mb-3">

                                <label>
                                    Affiliate
                                </label>


                                <input
                                type="hidden"
                                id="reaperAffiliate"
                                name="affiliate"
                                required>


                                <div class="reaper-affiliate-dropdown">

                                    <button
                                    type="button"
                                    id="reaperAffiliateBtn"
                                    class="reaper-affiliate-btn">

                                        <span id="reaperAffiliateText">
                                            Select Affiliate
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="reaperAffiliateMenu"
                                    class="reaper-affiliate-menu">

                                        <div
                                        class="reaper-affiliate-item"
                                        data-value="Member">

                                            Member

                                        </div>


                                        <div
                                        class="reaper-affiliate-item"
                                        data-value="Not Member">

                                            Not Member

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =============================================
                                 CUSTOMER NAME
                            ============================================== -->

                            <div class="col-md-8 mb-3">

                                <div class="reaper-name-field-wrapper">

                                    <label>
                                        Customer Name
                                    </label>


                                    <input
                                    type="text"
                                    id="reaperCustomerName"
                                    name="customer_name"
                                    class="reaper-input"
                                    placeholder="Select affiliate first"
                                    autocomplete="off"
                                    disabled
                                    required>


                                    <input
                                    type="hidden"
                                    id="reaperMemberId"
                                    name="member_id">


                                    <div
                                    id="reaperMemberSuggestion"
                                    class="reaper-member-suggestion">
                                    </div>

                                </div>

                            </div>


                            <!-- =============================================
                                 SERVICE LOCATION
                            ============================================== -->

                            <div class="col-md-12">

                                <label>
                                    Service Location
                                </label>


                                <input
                                type="text"
                                id="reaperLocation"
                                name="location"
                                class="reaper-input"
                                placeholder="Enter service location"
                                autocomplete="off"
                                required>

                            </div>


                        </div>

                    </section>


                    <!-- =================================================
                         RENTAL SCHEDULE & ASSIGNMENT
                    ================================================== -->

                    <section class="reaper-form-section">


                        <div class="reaper-section-heading">

                            <div class="reaper-section-icon">

                                <i class="bi bi-calendar2-check"></i>

                            </div>


                            <div>

                                <h3>
                                    RENTAL SCHEDULE &amp; ASSIGNMENT
                                </h3>

                            </div>

                        </div>


                        <div class="row">


                            <!-- =================================================
                                 RENTAL DATE
                            ================================================== -->

                            <div class="col-md-4 mb-3">

                                <label>
                                    Rental Date
                                </label>


                                <input
                                type="hidden"
                                id="reaperDate"
                                name="rental_date"
                                required>


                                <div class="reaper-date-dropdown">

                                    <button
                                    type="button"
                                    id="reaperDateBtn"
                                    class="
                                        reaper-input
                                        reaper-date-btn
                                    ">

                                        <span id="reaperDateText">
                                            Select Rental Date
                                        </span>

                                        <i class="bi bi-calendar3"></i>

                                    </button>


                                    <div
                                    id="reaperCalendar"
                                    class="reaper-calendar">

                                        <div class="reaper-calendar-header">

                                            <button
                                            type="button"
                                            id="reaperCalendarPrev"
                                            class="reaper-calendar-nav">

                                                <i class="bi bi-chevron-left"></i>

                                            </button>


                                            <div
                                            id="reaperCalendarMonth"
                                            class="reaper-calendar-month">
                                            </div>


                                            <button
                                            type="button"
                                            id="reaperCalendarNext"
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
                                        id="reaperCalendarDays"
                                        class="reaper-calendar-days">
                                        </div>


                                        <div class="reaper-calendar-legend">

                                            <span>

                                                <i
                                                class="
                                                    reaper-legend-dot
                                                    reaper-legend-booked
                                                ">
                                                </i>

                                                Unavailable

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
                                                    reaper-legend-historical
                                                ">
                                                </i>

                                                Historical

                                            </span>


                                            <span>

                                                <i
                                                class="
                                                    reaper-legend-dot
                                                    reaper-legend-available
                                                ">
                                                </i>

                                                Available

                                            </span>

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                                 PRIMARY TRACTOR
                            ================================================== -->

                            <div class="col-md-4 mb-3">

                                <label>
                                    Primary Tractor
                                </label>


                                <input
                                type="hidden"
                                id="reaperTractorID"
                                name="tractor_id"
                                required>


                                <input
                                type="hidden"
                                id="reaperTractorNo"
                                name="tractor_no">


                                <div
                                class="
                                    reaper-tractor-dropdown
                                    reaper-assignment-dropdown
                                ">

                                    <button
                                    type="button"
                                    id="reaperTractorBtn"
                                    class="
                                        reaper-input
                                        reaper-tractor-btn
                                        reaper-assignment-btn
                                    "
                                    disabled>

                                        <span id="reaperTractorText">
                                            Select Rental Date First
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="reaperTractorMenu"
                                    class="
                                        reaper-tractor-menu
                                        reaper-assignment-menu
                                    ">

                                        <div class="reaper-tractor-empty">
                                            Select a Rental Date first.
                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =================================================
                                 PRIMARY DRIVER
                            ================================================== -->

                            <div class="col-md-4 mb-3">

                                <label>
                                    Primary Driver
                                </label>


                                <input
                                type="hidden"
                                id="reaperPrimaryDriverID"
                                name="primary_driver_id"
                                required>


                                <div
                                class="
                                    reaper-tractor-dropdown
                                    reaper-assignment-dropdown
                                ">

                                    <button
                                    type="button"
                                    id="reaperPrimaryDriverBtn"
                                    class="
                                        reaper-input
                                        reaper-tractor-btn
                                        reaper-assignment-btn
                                    "
                                    disabled>

                                        <span id="reaperPrimaryDriverText">
                                            Select Rental Date First
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="reaperPrimaryDriverMenu"
                                    class="
                                        reaper-tractor-menu
                                        reaper-assignment-menu
                                    ">

                                        <div class="reaper-tractor-empty">
                                            Select a Rental Date first.
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
                                id="reaperSecondaryToggle"
                                class="reaper-secondary-toggle">

                                    <span class="reaper-secondary-toggle-icon">

                                        <i class="bi bi-plus-lg"></i>

                                    </span>


                                    <span>
                                        ADD SECONDARY ASSIGNMENT
                                    </span>

                                </button>

                            </div>


                            <!-- =================================================
                                 SECONDARY ASSIGNMENT
                            ================================================== -->

                            <div
                            id="reaperSecondaryAssignment"
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
                                        id="reaperSecondaryTractorID"
                                        name="secondary_tractor_id">


                                        <input
                                        type="hidden"
                                        id="reaperSecondaryTractorNo"
                                        name="secondary_tractor_no">


                                        <div
                                        class="
                                            reaper-tractor-dropdown
                                            reaper-assignment-dropdown
                                        ">

                                            <button
                                            type="button"
                                            id="reaperSecondaryTractorBtn"
                                            class="
                                                reaper-input
                                                reaper-tractor-btn
                                                reaper-assignment-btn
                                            "
                                            disabled>

                                                <span id="reaperSecondaryTractorText">
                                                    No Secondary Tractor
                                                </span>

                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="reaperSecondaryTractorMenu"
                                            class="
                                                reaper-tractor-menu
                                                reaper-assignment-menu
                                            ">

                                                <div class="reaper-tractor-empty">
                                                    Select a Rental Date first.
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
                                        id="reaperSecondaryDriverID"
                                        name="secondary_driver_id">


                                        <div
                                        class="
                                            reaper-tractor-dropdown
                                            reaper-assignment-dropdown
                                        ">

                                            <button
                                            type="button"
                                            id="reaperSecondaryDriverBtn"
                                            class="
                                                reaper-input
                                                reaper-tractor-btn
                                                reaper-assignment-btn
                                            "
                                            disabled>

                                                <span id="reaperSecondaryDriverText">
                                                    No Secondary Driver
                                                </span>

                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="reaperSecondaryDriverMenu"
                                            class="
                                                reaper-tractor-menu
                                                reaper-assignment-menu
                                            ">

                                                <div class="reaper-tractor-empty">
                                                    Select a Rental Date first.
                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- =============================================
                                         SECONDARY NOTE
                                    ============================================== -->

                                    <div class="col-md-12">

                                        <div
                                        id="reaperSecondaryAssignmentNote"
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
                         SERVICE COMPLETION & STATUS
                    ================================================== -->

                    <section class="reaper-form-section">


                        <div class="reaper-section-heading">

                            <div class="reaper-section-icon">

                                <i class="bi bi-clipboard2-check"></i>

                            </div>


                            <div>

                                <h3>
                                    SERVICE COMPLETION &amp; STATUS
                                </h3>

                            </div>

                        </div>


                        <div class="row align-items-start">


                            <!-- =============================================
                                 NO. OF HARVEST
                            ============================================== -->

                            <div class="col-md-3 mb-3">

                                <label>
                                    No. of Harvest
                                </label>


                                <input
                                type="number"
                                id="reaperHarvest"
                                name="no_of_harvest"
                                class="reaper-input"
                                min="1"
                                step="1"
                                placeholder="Optional"
                                disabled>

                            </div>


                            <!-- =============================================
                                 COMMODITY
                            ============================================== -->

                            <div class="col-md-3 mb-3">

                                <label>
                                    Commodity
                                </label>


                                <input
                                    type="number"
                                    id="reaperCommodity"
                                    name="commodity"
                                    class="reaper-input"
                                    min="1"
                                    step="1"
                                    placeholder="Enter No. of Harvest first"
                                    disabled
                                >

                            </div>


                            <!-- =============================================
                                 RENTAL STATUS
                            ============================================== -->

                            <div class="col-md-3 mb-3">

                                <label>
                                    Rental Status
                                </label>


                                <input
                                type="hidden"
                                id="reaperStatus"
                                name="status"
                                required>


                                <div class="reaper-status-dropdown">

                                    <button
                                    type="button"
                                    id="reaperStatusBtn"
                                    class="reaper-status-btn"
                                    disabled>

                                        <span id="reaperStatusText">
                                            Select Date First
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="reaperStatusMenu"
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


                            <!-- =============================================
                                 PAYMENT REMARKS
                            ============================================== -->

                            <div class="col-md-3 mb-3">

                                <label>
                                    Payment Remarks
                                </label>


                                <input
                                type="hidden"
                                id="reaperRemarks"
                                name="remarks"
                                value="Unpaid"
                                required>


                                <div class="reaper-remarks-dropdown">

                                    <button
                                    type="button"
                                    id="reaperRemarksBtn"
                                    class="reaper-remarks-btn"
                                    disabled>

                                        <span id="reaperRemarksText">
                                            Unpaid
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="reaperRemarksMenu"
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


                        </div>

                    </section>


                </div>


                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div class="reaper-add-footer">

                    <div class="reaper-save-container">


                        <button
                        type="button"
                        class="reaper-cancel-btn"
                        data-bs-dismiss="modal">

                            CLOSE

                        </button>


                        <button
                        type="button"
                        id="saveReaperBtn"
                        class="reaper-save-btn">

                            ADD RENTAL

                        </button>


                    </div>

                </div>


            </form>


        </div>

    </div>

</div>