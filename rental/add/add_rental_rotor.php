<?php

/* =========================================================
   SPIS - ADD ROTOR RENTAL MODAL

   NEW ROTOR RENTAL FLOW

   1. Customer Details
   2. Rental Date
   3. Primary Tractor
   4. Primary Driver
   5. Optional Secondary Tractor
   6. Optional Secondary Driver
   7. Rotor Pricing
   8. Rental Status

   IMPORTANT:
   - Date-only scheduling
   - No Service Time
   - No manual Operator field
   - Driver Registry is used
   - Tractor Registry is used
   - Secondary Tractor + Driver must be paired
   - Past Rental Date = Historical / Audit Entry
   - Historical / Audit Entry = Done automatically
========================================================= */

?>


<!-- =========================================================
     ADD ROTOR RENTAL
========================================================= -->

<div
class="modal fade"
id="addRotorModal"
tabindex="-1"
aria-labelledby="addRotorModalTitle"
aria-hidden="true">


    <div
    class="modal-dialog modal-xl modal-dialog-centered">


        <div
        class="
            modal-content
            rotor-window
            rotor-add-window
        ">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div
            class="
                rotor-header
                rotor-add-header
            ">


                <div class="rotor-add-heading">

                    <h1 id="addRotorModalTitle">

                        ADD ROTOR RENTAL

                    </h1>

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
            id="rotorForm"
            action="save/save_rotor.php"
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
                     CURRENT PRICE SNAPSHOT FOR UI COMPUTATION

                     Backend still reads the authoritative price
                     directly from rotor_price.
                ============================================== -->

                <input
                type="hidden"
                id="pricePerHectare"
                value="<?=
                    htmlspecialchars(
                        (string) (
                            $pricePerHectare
                            ??
                            '0'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ?>">


                <div class="rotor-add-body">


                    <!-- =================================================
                         CUSTOMER DETAILS
                    ================================================== -->

                    <section class="rotor-form-section">


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


                        <div class="row">


                            <!-- =========================================
                                 AFFILIATE
                            ========================================== -->

                            <div class="col-md-4 mb-3">


                                <label>

                                    Affiliate

                                </label>


                                <input
                                type="hidden"
                                id="rotorAffiliate"
                                name="affiliate"
                                required>


                                <div class="rotor-affiliate-dropdown">


                                    <button
                                    type="button"
                                    id="rotorAffiliateBtn"
                                    class="rotor-affiliate-btn">


                                        <span id="rotorAffiliateText">

                                            Select Affiliate

                                        </span>


                                        <i class="bi bi-chevron-down"></i>


                                    </button>


                                    <div
                                    id="rotorAffiliateMenu"
                                    class="rotor-affiliate-menu">


                                        <div
                                        class="rotor-affiliate-item"
                                        data-value="Member">

                                            Member

                                        </div>


                                        <div
                                        class="rotor-affiliate-item"
                                        data-value="Not Member">

                                            Not Member

                                        </div>


                                    </div>


                                </div>


                            </div>


                            <!-- =========================================
                                 CUSTOMER NAME
                            ========================================== -->

                            <div class="col-md-8 mb-3">


                                <div class="rotor-name-field-wrapper">


                                    <label>

                                        Customer Name

                                    </label>


                                    <input
                                    type="text"
                                    id="rotorCustomerName"
                                    name="customer_name"
                                    class="rotor-input"
                                    placeholder="Select affiliate first"
                                    autocomplete="off"
                                    disabled
                                    required>


                                    <input
                                    type="hidden"
                                    id="rotorMemberId"
                                    name="member_id">


                                    <div
                                    id="rotorMemberSuggestion"
                                    class="rotor-member-suggestion">
                                    </div>


                                </div>


                            </div>


                            <!-- =========================================
                                 SERVICE LOCATION
                            ========================================== -->

                            <div class="col-md-8">


                                <label>

                                    Service Location

                                </label>


                                <input
                                type="text"
                                id="rotorLocation"
                                name="location"
                                class="rotor-input"
                                placeholder="Enter service location"
                                autocomplete="off"
                                required>


                            </div>


                            <!-- =========================================
                                 AREA
                            ========================================== -->

                            <div class="col-md-4">


                                <label>

                                    Area (Ha)

                                </label>


                                <input
                                type="number"
                                id="rotorArea"
                                name="area"
                                class="rotor-input"
                                min="0.01"
                                step="0.01"
                                placeholder="Enter area in hectares"
                                required>


                            </div>


                        </div>


                    </section>



                    <!-- =================================================
                         RENTAL SCHEDULE & ASSIGNMENT
                    ================================================== -->

                    <section class="rotor-form-section">


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
                                id="rotorDate"
                                name="rental_date"
                                required>


                                <div class="rotor-date-dropdown">


                                    <button
                                    type="button"
                                    id="rotorDateBtn"
                                    class="
                                        rotor-input
                                        rotor-date-btn
                                    ">


                                        <span id="rotorDateText">

                                            Select Rental Date

                                        </span>


                                        <i class="bi bi-calendar3"></i>


                                    </button>


                                    <!-- =================================
                                         CUSTOM CALENDAR
                                    ================================== -->

                                    <div
                                    id="rotorCalendar"
                                    class="rotor-calendar">


                                        <div class="rotor-calendar-header">


                                            <button
                                            type="button"
                                            id="rotorCalendarPrev"
                                            class="rotor-calendar-nav">

                                                <i class="bi bi-chevron-left"></i>

                                            </button>


                                            <div
                                            id="rotorCalendarMonth"
                                            class="rotor-calendar-month">

                                                Month Year

                                            </div>


                                            <button
                                            type="button"
                                            id="rotorCalendarNext"
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
                                        id="rotorCalendarDays"
                                        class="rotor-calendar-days">
                                        </div>


                                        <div class="rotor-calendar-legend">


                                            <span>

                                                <i
                                                class="
                                                    rotor-legend-dot
                                                    rotor-legend-historical
                                                ">
                                                </i>

                                                Historical

                                            </span>


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
                                id="rotorTractorID"
                                name="tractor_id"
                                required>


                                <input
                                type="hidden"
                                id="rotorTractorNo"
                                name="tractor_no">


                                <div
                                class="
                                    rotor-tractor-dropdown
                                    rotor-assignment-dropdown
                                ">


                                    <button
                                    type="button"
                                    id="rotorTractorBtn"
                                    class="
                                        rotor-input
                                        rotor-tractor-btn
                                        rotor-assignment-btn
                                    "
                                    disabled>


                                        <span id="rotorTractorText">

                                            Select Rental Date First

                                        </span>


                                        <i class="bi bi-chevron-down"></i>


                                    </button>


                                    <div
                                    id="rotorTractorMenu"
                                    class="
                                        rotor-tractor-menu
                                        rotor-assignment-menu
                                    ">


                                        <div class="rotor-tractor-empty">

                                            Select a Rental Date first.

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
                                id="rotorPrimaryDriverID"
                                name="primary_driver_id"
                                required>


                                <div
                                class="
                                    rotor-driver-dropdown
                                    rotor-assignment-dropdown
                                ">


                                    <button
                                    type="button"
                                    id="rotorPrimaryDriverBtn"
                                    class="
                                        rotor-input
                                        rotor-driver-btn
                                        rotor-assignment-btn
                                    "
                                    disabled>


                                        <span id="rotorPrimaryDriverText">

                                            Select Rental Date First

                                        </span>


                                        <i class="bi bi-chevron-down"></i>


                                    </button>


                                    <div
                                    id="rotorPrimaryDriverMenu"
                                    class="
                                        rotor-driver-menu
                                        rotor-assignment-menu
                                    ">


                                        <div class="rotor-driver-empty">

                                            Select a Rental Date first.

                                        </div>


                                    </div>


                                </div>


                            </div>



                            <!-- =========================================
                                 SECONDARY ASSIGNMENT TOGGLE
                            ========================================== -->

                            <div class="col-md-12">


                                <button
                                type="button"
                                id="rotorSecondaryToggle"
                                class="rotor-secondary-toggle">


                                    <span class="rotor-secondary-toggle-icon">

                                        <i class="bi bi-plus-lg"></i>

                                    </span>


                                    <span>

                                        ADD SECONDARY ASSIGNMENT

                                    </span>


                                </button>


                            </div>



                            <!-- =========================================
                                 SECONDARY ASSIGNMENT
                            ========================================== -->

                            <div
                            id="rotorSecondaryAssignment"
                            class="
                                col-md-12
                                rotor-secondary-wrapper
                            ">


                                <div class="row">


                                    <!-- =================================
                                         SECONDARY TRACTOR
                                    ================================== -->

                                    <div class="col-md-6 mb-3">


                                        <label>

                                            Secondary Tractor

                                        </label>


                                        <input
                                        type="hidden"
                                        id="rotorSecondaryTractorID"
                                        name="secondary_tractor_id">


                                        <input
                                        type="hidden"
                                        id="rotorSecondaryTractorNo"
                                        name="secondary_tractor_no">


                                        <div
                                        class="
                                            rotor-tractor-dropdown
                                            rotor-assignment-dropdown
                                        ">


                                            <button
                                            type="button"
                                            id="rotorSecondaryTractorBtn"
                                            class="
                                                rotor-input
                                                rotor-tractor-btn
                                                rotor-assignment-btn
                                            "
                                            disabled>


                                                <span id="rotorSecondaryTractorText">

                                                    Select Secondary Tractor

                                                </span>


                                                <i class="bi bi-chevron-down"></i>


                                            </button>


                                            <div
                                            id="rotorSecondaryTractorMenu"
                                            class="
                                                rotor-tractor-menu
                                                rotor-assignment-menu
                                            ">


                                                <div class="rotor-tractor-empty">

                                                    Select a Rental Date first.

                                                </div>


                                            </div>


                                        </div>


                                    </div>



                                    <!-- =================================
                                         SECONDARY DRIVER
                                    ================================== -->

                                    <div class="col-md-6 mb-3">


                                        <label>

                                            Secondary Driver

                                        </label>


                                        <input
                                        type="hidden"
                                        id="rotorSecondaryDriverID"
                                        name="secondary_driver_id">


                                        <div
                                        class="
                                            rotor-driver-dropdown
                                            rotor-assignment-dropdown
                                        ">


                                            <button
                                            type="button"
                                            id="rotorSecondaryDriverBtn"
                                            class="
                                                rotor-input
                                                rotor-driver-btn
                                                rotor-assignment-btn
                                            "
                                            disabled>


                                                <span id="rotorSecondaryDriverText">

                                                    Select Secondary Driver

                                                </span>


                                                <i class="bi bi-chevron-down"></i>


                                            </button>


                                            <div
                                            id="rotorSecondaryDriverMenu"
                                            class="
                                                rotor-driver-menu
                                                rotor-assignment-menu
                                            ">


                                                <div class="rotor-driver-empty">

                                                    Select a Rental Date first.

                                                </div>


                                            </div>


                                        </div>


                                    </div>



                                    <!-- =================================
                                         SECONDARY NOTE
                                    ================================== -->

                                    <div class="col-md-12">


                                        <div
                                        id="rotorSecondaryAssignmentNote"
                                        class="rotor-secondary-assignment-note">


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

                    <section class="rotor-form-section">


                        <div class="rotor-section-heading">


                            <div class="rotor-section-icon">

                                <i class="bi bi-receipt"></i>

                            </div>


                            <div>

                                <h3>

                                    RENTAL AMOUNT & STATUS

                                </h3>

                            </div>


                        </div>


                        <div class="row align-items-end">


                            <!-- =========================================
                                 PRICE PER HECTARE
                            ========================================== -->

                            <div class="col-md mb-3">


                                <label>

                                    Price per Hectare

                                </label>


                                <div class="rotor-money-field">


                                    <span class="rotor-money-prefix">

                                        ₱

                                    </span>


                                    <input
                                    type="text"
                                    id="rotorPriceDisplay"
                                    class="
                                        rotor-input
                                        rotor-money-input
                                        rotor-readonly-input
                                    "
                                    value="<?=
                                        number_format(
                                            (float) (
                                                $pricePerHectare
                                                ??
                                                0
                                            ),
                                            2,
                                            '.',
                                            ''
                                        )
                                    ?>"
                                    readonly
                                    tabindex="-1">


                                </div>


                            </div>



                            <!-- =========================================
                                 MEMBER DISCOUNT
                            ========================================== -->

                            <div class="col-md mb-3">


                                <label>

                                    Member Discount

                                </label>


                                <div class="rotor-money-field">


                                    <span class="rotor-money-prefix">

                                        ₱

                                    </span>


                                    <input
                                    type="text"
                                    inputmode="decimal"
                                    id="rotorDiscount"
                                    name="discount"
                                    class="
                                        rotor-input
                                        rotor-money-input
                                        rotor-discount-disabled
                                    "
                                    placeholder="Members only"
                                    disabled>


                                </div>


                            </div>



                            <!-- =========================================
                                 RENTAL STATUS
                            ========================================== -->

                            <div class="col-md mb-3">


                                <label>

                                    Rental Status

                                </label>


                                <input
                                type="hidden"
                                id="rotorStatus"
                                name="status"
                                required>


                                <div class="rotor-status-dropdown">


                                    <button
                                    type="button"
                                    id="rotorStatusBtn"
                                    class="rotor-status-btn"
                                    disabled>


                                        <span id="rotorStatusText">

                                            Select Date First

                                        </span>


                                        <i class="bi bi-chevron-down"></i>


                                    </button>


                                    <div
                                    id="rotorStatusMenu"
                                    class="rotor-status-menu">


                                        <div
                                        class="rotor-status-item"
                                        data-value="Pending">

                                            Pending

                                        </div>


                                        <div
                                        class="rotor-status-item"
                                        data-value="Ongoing">

                                            Ongoing

                                        </div>


                                        <div
                                        class="rotor-status-item"
                                        data-value="Done">

                                            Done

                                        </div>


                                    </div>


                                </div>


                            </div>



                            <!-- =========================================
                                 DOWNPAYMENT
                            ========================================== -->

                            <div class="col-md mb-3">


                                <label>

                                    Downpayment

                                </label>


                                <div class="rotor-money-field">


                                    <span class="rotor-money-prefix">

                                        ₱

                                    </span>


                                    <input
                                    type="text"
                                    inputmode="decimal"
                                    id="rotorDownpayment"
                                    name="downpayment"
                                    class="
                                        rotor-input
                                        rotor-money-input
                                    "
                                    value="0.00"
                                    placeholder="0.00"
                                    autocomplete="off">


                                </div>


                            </div>



                            <!-- =========================================
                                 TOTAL
                            ========================================== -->

                            <div class="col-md mb-3">


                                <div class="rotor-total-card">


                                    <span>

                                        TOTAL RENTAL

                                    </span>


                                    <div class="rotor-total-value">


                                        <span>

                                            ₱

                                        </span>


                                        <input
                                        type="number"
                                        id="rotorTotal"
                                        name="total_price"
                                        value="0.00"
                                        step="0.01"
                                        readonly>


                                    </div>


                                </div>


                            </div>



                            <!-- =========================================
                                 PRICE WARNING
                            ========================================== -->

                            <div class="col-md-12">


                                <small
                                id="rotorPriceWarning"
                                class="rotor-price-warning d-none">


                                    <i class="bi bi-exclamation-circle"></i>


                                    Please set the Rotor price per hectare
                                    before adding a Rental.


                                </small>


                            </div>



                            <!-- =========================================
                                 HISTORICAL / AUDIT NOTE
                            ========================================== -->

                            <div class="col-md-12">


                                <div
                                id="rotorHistoricalStatusNote"
                                class="rotor-historical-status-note"
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

                <div class="rotor-add-footer">


                    <div class="rotor-save-container">


                        <button
                        type="button"
                        class="rotor-cancel-btn"
                        data-bs-dismiss="modal">

                            CLOSE

                        </button>


                        <button
                        type="button"
                        id="saveRotorBtn"
                        class="rotor-save-btn">

                            ADD RENTAL

                        </button>


                    </div>


                </div>


            </form>


        </div>


    </div>


</div>