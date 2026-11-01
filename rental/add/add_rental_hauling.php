<?php

/* =========================================================
   SPIS - ADD HAULING RENTAL MODAL

   NEW RENTAL ASSIGNMENT FLOW

   1. Rental Date
   2. Primary Tractor
   3. Primary Driver
   4. Optional Secondary Tractor
   5. Optional Secondary Driver

   IMPORTANT:
   - Date-only scheduling
   - No Service Time
   - No manual Operator field
   - Tractor / Driver availability will be loaded by JS
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
     ADD HAULING RENTAL
========================================================= -->

<div
class="modal fade"
id="addhaulingModal"
tabindex="-1"
aria-labelledby="addHaulingModalTitle"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="
            modal-content
            hauling-window
            hauling-add-window
        ">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div
            class="
                hauling-header
                hauling-add-header
            ">

                <div class="hauling-add-heading">

                    <h1 id="addHaulingModalTitle">
                        ADD HAULING RENTAL
                    </h1>

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
            id="haulingForm"
            action="save/save_hauling.php"
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


                <div class="hauling-add-body">


                    <!-- =================================================
                        CUSTOMER DETAILS
                    ================================================== -->

                    <section class="hauling-form-section">

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


                        <div class="row">


                            <!-- =============================
                                AFFILIATE
                            ============================== -->

                            <div class="col-md-4 mb-3">

                                <label>
                                    Affiliate
                                </label>


                                <input
                                type="hidden"
                                id="haulingAffiliate"
                                name="affiliate"
                                required>


                                <div class="affiliate-dropdown">

                                    <button
                                    type="button"
                                    id="affiliateBtn"
                                    class="affiliate-btn">

                                        <span id="affiliateText">
                                            Select Affiliate
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="affiliateMenu"
                                    class="affiliate-menu">

                                        <div
                                        class="affiliate-item"
                                        data-value="Member">

                                            Member

                                        </div>


                                        <div
                                        class="affiliate-item"
                                        data-value="Not Member">

                                            Not Member

                                        </div>

                                    </div>

                                </div>

                            </div>


                            <!-- =============================
                                CUSTOMER NAME
                            ============================== -->

                            <div class="col-md-8 mb-3">

                                <div class="name-field-wrapper">

                                    <label>
                                        Customer Name
                                    </label>


                                    <input
                                    type="text"
                                    id="haulingCustomerName"
                                    name="customer_name"
                                    class="hauling-input"
                                    placeholder="Select affiliate first"
                                    autocomplete="off"
                                    disabled
                                    required>


                                    <input
                                    type="hidden"
                                    name="member_id"
                                    id="member_id">


                                    <div
                                    id="memberSuggestion"
                                    class="member-suggestion">
                                    </div>

                                </div>

                            </div>


                            <!-- =============================
                                SERVICE LOCATION
                            ============================== -->

                            <div class="col-md-8">

                                <label>
                                    Service Location
                                </label>


                                <input
                                type="text"
                                id="haulingLocation"
                                name="location"
                                class="hauling-input"
                                placeholder="Enter service location"
                                autocomplete="off"
                                required>

                            </div>


                            <!-- =============================
                                NO. OF HAULING
                            ============================== -->

                            <div class="col-md-4">

                                <label>
                                    No. of Hauling
                                </label>


                                <input
                                type="number"
                                id="haulingCount"
                                name="hauling_count"
                                class="hauling-input"
                                min="1"
                                step="1"
                                placeholder="Enter quantity"
                                required>

                            </div>


                        </div>

                    </section>



                    <!-- =================================================
                        RENTAL SCHEDULE & ASSIGNMENT
                    ================================================== -->

                    <section class="hauling-form-section">

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
                                id="haulingDate"
                                name="rental_date"
                                required>


                                <div class="hauling-date-dropdown">

                                    <button
                                    type="button"
                                    id="haulingDateBtn"
                                    class="
                                        hauling-input
                                        hauling-date-btn
                                    ">

                                        <span id="haulingDateText">
                                            Select Rental Date
                                        </span>

                                        <i class="bi bi-calendar3"></i>

                                    </button>


                                    <div
                                    id="haulingCalendar"
                                    class="hauling-calendar">

                                        <div class="hauling-calendar-header">

                                            <button
                                            type="button"
                                            id="haulingCalendarPrev"
                                            class="hauling-calendar-nav">

                                                <i class="bi bi-chevron-left"></i>

                                            </button>


                                            <div
                                            id="haulingCalendarMonth"
                                            class="hauling-calendar-month">
                                            </div>


                                            <button
                                            type="button"
                                            id="haulingCalendarNext"
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
                                        id="haulingCalendarDays"
                                        class="hauling-calendar-days">
                                        </div>


                                        <div class="hauling-calendar-legend">

                                            <span>

                                                <i
                                                class="
                                                    hauling-legend-dot
                                                    hauling-legend-historical
                                                ">
                                                </i>

                                                Historical

                                            </span>

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


                            <!-- =================================================
                                PRIMARY TRACTOR
                            ================================================== -->

                            <div class="col-md-4 mb-3">

                                <label>
                                    Primary Tractor
                                </label>


                                <input
                                type="hidden"
                                id="haulingTractorID"
                                name="tractor_id"
                                required>


                                <input
                                type="hidden"
                                id="haulingTractorNo"
                                name="tractor_no">


                                <div
                                class="
                                    hauling-tractor-dropdown
                                    hauling-assignment-dropdown
                                ">

                                    <button
                                    type="button"
                                    id="haulingTractorBtn"
                                    class="
                                        hauling-input
                                        hauling-tractor-btn
                                        hauling-assignment-btn
                                    "
                                    disabled>

                                        <span id="haulingTractorText">
                                            Select Rental Date First
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="haulingTractorMenu"
                                    class="
                                        hauling-tractor-menu
                                        hauling-assignment-menu
                                    ">

                                        <div class="hauling-tractor-empty">
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
                                id="haulingPrimaryDriverID"
                                name="primary_driver_id"
                                required>


                                <div
                                class="
                                    hauling-tractor-dropdown
                                    hauling-assignment-dropdown
                                ">

                                    <button
                                    type="button"
                                    id="haulingPrimaryDriverBtn"
                                    class="
                                        hauling-input
                                        hauling-tractor-btn
                                        hauling-assignment-btn
                                    "
                                    disabled>

                                        <span id="haulingPrimaryDriverText">
                                            Select Rental Date First
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="haulingPrimaryDriverMenu"
                                    class="
                                        hauling-tractor-menu
                                        hauling-assignment-menu
                                    ">

                                        <div class="hauling-tractor-empty">
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
                                id="haulingSecondaryToggle"
                                class="hauling-secondary-toggle">

                                    <span class="hauling-secondary-toggle-icon">

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
                            id="haulingSecondaryAssignment"
                            class="col-md-12 hauling-secondary-wrapper">

                                <div class="row">


                                    <!-- =============================================
                                        SECONDARY TRACTOR
                                    ============================================== -->

                                    <div class="col-md-6 mb-3">

                                        <label>
                                            Secondary Tractor
                                        </label>


                                        <input
                                        type="hidden"
                                        id="haulingSecondaryTractorID"
                                        name="secondary_tractor_id">


                                        <input
                                        type="hidden"
                                        id="haulingSecondaryTractorNo"
                                        name="secondary_tractor_no">


                                        <div
                                        class="
                                            hauling-tractor-dropdown
                                            hauling-assignment-dropdown
                                        ">

                                            <button
                                            type="button"
                                            id="haulingSecondaryTractorBtn"
                                            class="
                                                hauling-input
                                                hauling-tractor-btn
                                                hauling-assignment-btn
                                            "
                                            disabled>

                                                <span id="haulingSecondaryTractorText">
                                                    No Secondary Tractor
                                                </span>

                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="haulingSecondaryTractorMenu"
                                            class="
                                                hauling-tractor-menu
                                                hauling-assignment-menu
                                            ">

                                                <div class="hauling-tractor-empty">
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
                                        </label>


                                        <input
                                        type="hidden"
                                        id="haulingSecondaryDriverID"
                                        name="secondary_driver_id">


                                        <div
                                        class="
                                            hauling-tractor-dropdown
                                            hauling-assignment-dropdown
                                        ">

                                            <button
                                            type="button"
                                            id="haulingSecondaryDriverBtn"
                                            class="
                                                hauling-input
                                                hauling-tractor-btn
                                                hauling-assignment-btn
                                            "
                                            disabled>

                                                <span id="haulingSecondaryDriverText">
                                                    No Secondary Driver
                                                </span>

                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="haulingSecondaryDriverMenu"
                                            class="
                                                hauling-tractor-menu
                                                hauling-assignment-menu
                                            ">

                                                <div class="hauling-tractor-empty">
                                                    Select a Rental Date first.
                                                </div>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- =============================================
                                        NOTE
                                    ============================================== -->

                                    <div class="col-md-12">

                                        <div
                                        id="haulingSecondaryAssignmentNote"
                                        class="hauling-secondary-assignment-note">

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
                         RENTAL AMOUNT & STATUS
                    ================================================== -->

                    <section class="hauling-form-section">


                        <div class="hauling-section-heading">

                            <div class="hauling-section-icon">

                                <i class="bi bi-receipt"></i>

                            </div>


                            <div>

                                <h3>
                                    RENTAL AMOUNT & STATUS
                                </h3>

                            </div>

                        </div>



                        <div class="row align-items-end">


                            <!-- =============================
                                 PRICE
                            ============================== -->

                            <div class="col-md-3">

                                <label>
                                    Rental Price
                                </label>


                                <div class="hauling-money-field">


                                    <span class="hauling-money-prefix">
                                        ₱
                                    </span>


                                    <input
                                    type="number"
                                    id="haulingPrice"
                                    name="price"
                                    class="
                                        hauling-input
                                        hauling-money-input
                                    "
                                    min="0"
                                    step="0.01"
                                    placeholder="0.00"
                                    required>


                                </div>

                            </div>



                            <!-- =============================
                                 DISCOUNT
                            ============================== -->

                            <div class="col-md-3">

                                <label>
                                    Member Discount
                                </label>


                                <div class="hauling-money-field">


                                    <span class="hauling-money-prefix">
                                        ₱
                                    </span>


                                    <input
                                    type="number"
                                    id="haulingDiscount"
                                    name="discounted_price"
                                    class="
                                        hauling-input
                                        hauling-money-input
                                    "
                                    min="0"
                                    step="0.01"
                                    placeholder="Members only"
                                    disabled>


                                </div>

                            </div>



                            <!-- =============================
                                RENTAL STATUS
                            ============================== -->

                            <div class="col-md-3">

                                <label>
                                    Rental Status
                                </label>


                                <input
                                type="hidden"
                                id="haulingStatus"
                                name="status"
                                required>


                                <div class="hauling-status-dropdown">

                                    <button
                                    type="button"
                                    id="statusBtn"
                                    class="hauling-status-btn">

                                        <span id="statusText">
                                            Select Status
                                        </span>

                                        <i class="bi bi-chevron-down"></i>

                                    </button>


                                    <div
                                    id="statusMenu"
                                    class="hauling-status-menu">

                                        <div
                                        class="hauling-status-item"
                                        data-value="Pending">

                                            Pending

                                        </div>


                                        <div
                                        class="hauling-status-item"
                                        data-value="Ongoing">

                                            Ongoing

                                        </div>


                                        <div
                                        class="hauling-status-item"
                                        data-value="Done">

                                            Done

                                        </div>

                                    </div>

                                </div>

                            </div>



                            <!-- =============================
                                 TOTAL
                            ============================== -->

                            <div class="col-md-3">

                                <div class="hauling-total-card">


                                    <span>
                                        TOTAL RENTAL
                                    </span>


                                    <div class="hauling-total-value">


                                        <span>
                                            ₱
                                        </span>


                                        <input
                                        type="number"
                                        id="haulingTotal"
                                        name="total_price"
                                        value="0.00"
                                        step="0.01"
                                        readonly>


                                    </div>


                                </div>

                            </div>

                                <div class="col-md-12">

                                <div
                                id="haulingHistoricalStatusNote"
                                class="hauling-historical-status-note"
                                aria-live="polite"
                                hidden>

                                    <i class="bi bi-clock-history"></i>

                                    <span>
                                        <strong>
                                            Historical / Audit Entry
                                        </strong>

                                        Past Rental Dates are automatically
                                        marked as Done.
                                    </span>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>



                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div class="hauling-add-footer">


                    <div class="hauling-save-container">


                        <button
                        type="button"
                        class="hauling-cancel-btn"
                        data-bs-dismiss="modal">

                            CLOSE

                        </button>


                        <button
                        type="button"
                        id="saveHaulingBtn"
                        class="hauling-save-btn">

                            ADD RENTAL

                        </button>


                    </div>


                </div>


            </form>


        </div>

    </div>

</div>