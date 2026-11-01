<?php

if (
    session_status()
    ===
    PHP_SESSION_NONE
) {
    session_start();
}


date_default_timezone_set(
    'Asia/Manila'
);


$todayMembershipDate =
    date('Y-m-d');


if (
    empty(
        $_SESSION[
            'member_form_csrf_token'
        ]
    )
) {

    $_SESSION[
        'member_form_csrf_token'
    ] =
        bin2hex(
            random_bytes(32)
        );

}

?>


<!-- REQUIRED FIELD ASTERISKS -->
<style>
    #addMemberModal .member-required-asterisk {
        color: #E56E68;
        margin-left: 3px;
        font-size: 12px;
        font-weight: 800;
    }
    
    #addMemberModal #memberForm[action="update_member.php"]
    .member-document-heading .member-required-asterisk {
        display: none;
    }

</style>


<!-- =========================================================
     ADD / EDIT MEMBER MODAL
========================================================= -->

<div
class="modal fade"
id="addMemberModal"
tabindex="-1"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="modal-content add-member-window">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="member-window-header">

                <div class="member-window-heading">

                    <h1>
                        ADD MEMBER
                    </h1>

                </div>


                <div class="member-window-header-actions">

                    <button
                    type="button"
                    id="archiveMemberBtn"
                    class="member-archive-btn"
                    style="display:none;">

                        <i class="bi bi-archive-fill"></i>

                        ARCHIVE MEMBER

                    </button>


                    <button
                    type="button"
                    class="member-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                        ×

                    </button>

                </div>

            </div>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
            id="memberForm"
            action="add_member.php"
            method="POST"
            enctype="multipart/form-data"
            autocomplete="off"
            novalidate>


                <!-- MEMBER ID -->

                <input
                type="hidden"
                id="member_id"
                name="member_id">


                <!-- CURRENT PAGE -->

                <input
                type="hidden"
                id="current_page"
                name="page">


                
                <!-- CURRENT SEARCH -->

                <input
                type="hidden"
                id="current_search"
                name="search">


                <!-- CURRENT STATUS FILTER -->

                <input
                type="hidden"
                name="status_filter"
                value="<?= htmlspecialchars((string)($statusFilter ?? 'all'), ENT_QUOTES, 'UTF-8'); ?>">


                <!-- CURRENT CLASSIFICATION FILTER -->

                <input
                type="hidden"
                name="classification_filter"
                value="<?= htmlspecialchars((string)($classificationFilter ?? 'all'), ENT_QUOTES, 'UTF-8'); ?>">


                <!-- CSRF -->


                <input
                type="hidden"
                name="csrf_token"
                value="<?=
                    htmlspecialchars(
                        $_SESSION[
                            'member_form_csrf_token'
                        ],
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>">


                <!--
                    Classification is system controlled.

                    New member:
                    CBU below ₱10,000 = Irregular
                    CBU reaches ₱10,000 = Regular

                    Once Regular, classification stays
                    Regular even if CBU is deducted later.
                -->

                <input
                type="hidden"
                id="memberClassification"
                name="classification"
                value="Irregular">


                <!-- CURRENT CBU FOR UI PREVIEW ONLY -->

                <input
                type="hidden"
                id="memberCurrentCbu"
                value="0.00">


                <!-- =================================================
                     SCROLLABLE BODY
                ================================================== -->

                <div class="member-modal-body">

                    <div class="row g-4">


                        <!-- =========================================
                             LEFT SIDE
                        ========================================== -->

                        <div class="col-lg-4">

                            <div class="member-side-panel">


                                <!-- PHOTO HEADING -->

                                <div class="member-panel-heading">

                                    <span class="member-panel-icon">

                                        <i class="bi bi-person-bounding-box"></i>

                                    </span>


                                    <div>

                                        <h3>
                                            MEMBER PHOTO
                                        </h3>

                                        <p>
                                            Optional profile image
                                        </p>

                                    </div>

                                </div>


                                <!-- PROFILE -->

                                <label
                                for="profile"
                                class="member-profile-label">

                                    <div class="member-profile-upload">

                                        <img
                                        id="previewImage"
                                        alt="Member profile preview"
                                        style="display:none;">


                                        <div
                                        id="uploadPlaceholder"
                                        class="upload-placeholder">

                                            <i class="bi bi-cloud-arrow-up-fill"></i>

                                            <span>
                                                UPLOAD IMAGE
                                            </span>

                                            <small>
                                                JPG, PNG or WEBP
                                            </small>

                                        </div>


                                        <div class="upload-overlay">

                                            <i class="bi bi-camera-fill"></i>

                                        </div>

                                    </div>

                                </label>


                                <input
                                type="file"
                                id="profile"
                                name="profile"
                                accept=".jpg,.jpeg,.png,.webp"
                                hidden>


                                <!-- =================================
                                     MEMBERSHIP SUMMARY
                                ================================== -->

                                <div class="member-summary-box">

                                    <div class="member-summary-heading">

                                        <i class="bi bi-person-check-fill"></i>

                                        <span>
                                            MEMBERSHIP
                                        </span>

                                    </div>


                                    <!-- CLASSIFICATION -->

                                    <div class="member-summary-row">

                                        <span>
                                            Classification
                                        </span>

                                        <strong
                                        id="memberClassificationText"
                                        class="member-classification-value">

                                            IRREGULAR

                                        </strong>

                                    </div>


                                    <!-- CURRENT CBU -->

                                    <div class="member-summary-row">

                                        <span>
                                            Current CBU
                                        </span>

                                        <strong
                                        id="memberCbuSummaryValue">

                                            ₱0.00

                                        </strong>

                                    </div>


                                    <!-- REQUIREMENT -->

                                    <div class="member-summary-row">

                                        <span>
                                            CBU Requirement
                                        </span>

                                        <strong>
                                            ₱10,000.00
                                        </strong>

                                    </div>


                                    <p class="member-summary-note">

                                        Once the required CBU is reached,
                                        the member becomes Regular permanently.

                                    </p>

                                </div>


                                <!-- =================================================
                                    MEMBER RECORD ACTIONS
                                ================================================== -->

                                <div class="member-record-actions">


                                    <!-- VIEW ATTACHMENTS -->

                                    <button
                                    type="button"
                                    id="viewAttachmentsBtn"
                                    class="member-view-attachment-btn"
                                    style="display:none;">

                                        <i class="bi bi-paperclip"></i>

                                        VIEW ATTACHMENTS

                                    </button>


                                    <!-- VIEW CBU HISTORY -->

                                    <button
                                    type="button"
                                    id="viewCbuHistoryBtn"
                                    class="member-view-cbu-btn"
                                    style="display:none;">

                                        <i class="bi bi-clock-history"></i>

                                        VIEW CBU HISTORY

                                    </button>


                                </div>

                            </div>

                        </div>


                        <!-- =========================================
                             RIGHT SIDE
                        ========================================== -->

                                                <div class="col-lg-8">

                            <div class="member-form-panel">


                                <!-- =================================
                                     PERSONAL INFORMATION
                                ================================== -->

                                <div class="member-section-heading">

                                    <span class="member-section-icon">

                                        <i class="bi bi-person-vcard-fill"></i>

                                    </span>


                                    <div>

                                        <h3>
                                            PERSONAL INFORMATION
                                        </h3>

                                        <p>
                                            Basic personal and contact details
                                        </p>

                                    </div>

                                </div>


                                <div class="row g-3">


                                    <!-- FULL NAME -->

                                    <div class="col-md-6">

                                        
                                        <label class="member-field-label">
                                            FULL NAME <span class="member-required-asterisk" aria-hidden="true">*</span>
                                        </label>



                                        <input
                                        type="text"
                                        name="fullname"
                                        class="form-control member-input"
                                        maxlength="255"
                                        placeholder="Enter full name"
                                        required>

                                    </div>


                                    <!-- GENDER -->

                                    <div class="col-md-6">

                                        <label class="member-field-label">

                                            GENDER <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>


                                        <input
                                        type="hidden"
                                        id="memberGender"
                                        name="gender">


                                        <div class="member-gender-dropdown">

                                            <button
                                            type="button"
                                            id="memberGenderBtn"
                                            class="member-status-btn">

                                                <span id="memberGenderText">

                                                    Select Gender

                                                </span>


                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="memberGenderMenu"
                                            class="member-status-menu">


                                                <button
                                                type="button"
                                                class="member-gender-item member-status-item"
                                                data-value="Male">

                                                    MALE

                                                </button>


                                                <button
                                                type="button"
                                                class="member-gender-item member-status-item"
                                                data-value="Female">

                                                    FEMALE

                                                </button>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- MEMBERSHIP DATE -->

                                    <div class="col-md-6">

                                        <label class="member-field-label">

                                            MEMBERSHIP DATE <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>


                                        <div class="member-date-wrapper">

                                            <input
                                            type="text"
                                            id="memberMembershipDateDisplay"
                                            class="form-control member-input"
                                            placeholder="MM-DD-YYYY"
                                            inputmode="numeric"
                                            maxlength="10"
                                            autocomplete="off">


                                            <input
                                            type="date"
                                            id="memberMembershipDate"
                                            name="membership_date"
                                            class="member-native-date"
                                            value="<?=
                                                htmlspecialchars(
                                                    $todayMembershipDate,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                            ?>"
                                            max="<?=
                                                htmlspecialchars(
                                                    $todayMembershipDate,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                            ?>"
                                            required>


                                            <button
                                            type="button"
                                            id="memberMembershipDateBtn"
                                            class="member-date-btn"
                                            aria-label="Select membership date">

                                                <i class="bi bi-calendar3"></i>

                                            </button>

                                        </div>

                                    </div>


                                    <!-- BIRTHDAY -->

                                    <div class="col-md-6">

                                        <label class="member-field-label">

                                            BIRTHDAY <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>


                                        <div class="member-date-wrapper">

                                            <input
                                            type="text"
                                            id="memberBirthDateDisplay"
                                            class="form-control member-input"
                                            placeholder="MM-DD-YYYY"
                                            inputmode="numeric"
                                            maxlength="10"
                                            autocomplete="off">


                                            <input
                                            type="date"
                                            id="memberBirthDate"
                                            name="birth_date"
                                            class="member-native-date"
                                            required>


                                            <button
                                            type="button"
                                            id="memberBirthDateBtn"
                                            class="member-date-btn"
                                            aria-label="Select birthday">

                                                <i class="bi bi-calendar3"></i>

                                            </button>

                                        </div>

                                    </div>


                                    <!-- ADDRESS -->

                                    <div class="col-md-12">

                                       
                                        <label class="member-field-label">

                                            ADDRESS <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>



                                        <input
                                        type="text"
                                        name="address"
                                        class="form-control member-input"
                                        placeholder="Enter complete address"
                                        required>

                                    </div>


                                    <!-- CONTACT -->

                                    <div class="col-md-6">

                                        <label class="member-field-label">

                                            CONTACT NUMBER

                                            <span class="member-optional">

                                                OPTIONAL

                                            </span>

                                        </label>


                                        <input
                                        type="text"
                                        id="contact"
                                        name="contact"
                                        class="form-control member-input"
                                        inputmode="numeric"
                                        maxlength="13"
                                        placeholder="Enter 08 or 09 contact number">

                                    </div>


                                    <!-- FACEBOOK / MESSENGER -->

                                    <div class="col-md-6">

                                        <label class="member-field-label">

                                            FACEBOOK / MESSENGER NAME <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>


                                        <input
                                        type="text"
                                        id="memberSocialMedia"
                                        name="social_media_name"
                                        class="form-control member-input"
                                        maxlength="150"
                                        placeholder="Enter Facebook or Messenger name"
                                        required>

                                    </div>

                                </div>


                                <!-- DIVIDER -->

                                <div class="member-form-divider"></div>


                                <!-- =================================
                                     COOPERATIVE INFORMATION
                                ================================== -->

                                <div class="member-section-heading">

                                    <span class="member-section-icon">

                                        <i class="bi bi-building-fill-check"></i>

                                    </span>


                                    <div>

                                        <h3>
                                            COOPERATIVE INFORMATION
                                        </h3>

                                        <p>
                                            Registration, farm and membership details
                                        </p>

                                    </div>

                                </div>


                                <div class="row g-3">


                                    <!-- TIN -->

                                    <div class="col-md-4">

                                        <label class="member-field-label">

                                            TIN NUMBER <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>


                                        <input
                                        type="text"
                                        id="tin_no"
                                        name="tin"
                                        class="form-control member-input"
                                        inputmode="numeric"
                                        maxlength="12"
                                        placeholder="9 or 12 digits"
                                        required>

                                    </div>


                                    <!-- RSBSA -->

                                    <div class="col-md-4">

                                        <label class="member-field-label">

                                            RSBSA ID <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>


                                        <input
                                        type="text"
                                        id="rsbsa_no"
                                        name="rsbsa"
                                        class="form-control member-input"
                                        maxlength="30"
                                        autocapitalize="characters"
                                        spellcheck="false"
                                        placeholder="XX-XX-XX-XXX-REMAINDER"
                                        required>

                                    </div>


                                    <!-- FARM FIELD -->

                                    <div class="col-md-4">

                                        <label class="member-field-label">

                                            FARM FIELD <span class="member-required-asterisk" aria-hidden="true">*</span>

                                            <span class="member-field-unit">

                                            HECTARES

                                            </span>

                                        </label>


                                        <input
                                        type="number"
                                        id="memberFarmField"
                                        name="farm_field_hectares"
                                        class="form-control member-input"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                        required>

                                    </div>


                                    <!-- STATUS -->

                                    <div class="col-md-6">

                                        <label class="member-field-label">

                                            STATUS <span class="member-required-asterisk" aria-hidden="true">*</span>

                                        </label>


                                        <input
                                        type="hidden"
                                        name="status"
                                        id="memberStatus">


                                        <div class="member-status-dropdown">

                                            <button
                                            type="button"
                                            id="memberStatusBtn"
                                            class="member-status-btn">

                                                <span id="memberStatusText">

                                                    Select Status

                                                </span>


                                                <i class="bi bi-chevron-down"></i>

                                            </button>


                                            <div
                                            id="memberStatusMenu"
                                            class="member-status-menu">


                                                <button
                                                type="button"
                                                class="member-status-item"
                                                data-value="ACTIVE">

                                                    ACTIVE

                                                </button>


                                                <button
                                                type="button"
                                                class="member-status-item"
                                                data-value="INACTIVE">

                                                    INACTIVE

                                                </button>

                                            </div>

                                        </div>

                                    </div>


                                    <!-- CLASSIFICATION -->

                                    <div class="col-md-6">

                                        <label class="member-field-label">

                                            CLASSIFICATION

                                        </label>


                                        <div class="member-classification-display">

                                            <span
                                            id="memberClassificationDisplayText">

                                                IRREGULAR

                                            </span>


                                            <i class="bi bi-lock-fill"></i>

                                        </div>

                                    </div>


                                    <!-- =================================
                                         ADD MODE: INITIAL CBU
                                    ================================== -->

                                    <div
                                    class="col-md-12"
                                    id="initialCbuGroup">

                                        <label class="member-field-label">

                                            INITIAL CBU

                                        </label>


                                        <div class="member-money-input">

                                            <span>
                                                ₱
                                            </span>


                                            <input
                                            type="number"
                                            id="initialCbu"
                                            name="initial_cbu"
                                            class="form-control member-input"
                                            min="0"
                                            step="0.01"
                                            inputmode="decimal"
                                            placeholder="0.00">

                                        </div>

                                    </div>


                                    <!-- =================================
                                         EDIT MODE: CBU MANAGEMENT
                                    ================================== -->

                                    <div
                                    class="col-md-12"
                                    id="memberCbuEditGroup"
                                    style="display:none;">


                                        <div class="member-form-divider"></div>


                                        <div class="member-section-heading">

                                            <span class="member-section-icon">

                                                <i class="bi bi-cash-stack"></i>

                                            </span>


                                            <div>

                                                <h3>
                                                    CAPITAL BUILD-UP
                                                </h3>

                                                <p>
                                                    Record CBU additions or deductions
                                                </p>

                                            </div>

                                        </div>


                                        <div class="row g-3">


                                            <!-- AMOUNT -->

                                            <div class="col-md-6">
                                                
                                                <label class="member-field-label">

                                                    AMOUNT

                                                </label>

                                                <div class="member-money-input">

                                                    <span>
                                                        ₱
                                                    </span>


                                                    <input
                                                    type="number"
                                                    id="memberCbuAdjustmentAmount"
                                                    name="cbu_adjustment_amount"
                                                    class="form-control member-input"
                                                    min="0.01"
                                                    step="0.01"
                                                    inputmode="decimal"
                                                    placeholder="0.00">

                                                </div>

                                            </div>


                                            <!-- CBU ACTION -->

                                            <div class="col-md-6">

                                                <label class="member-field-label">

                                                    CBU ACTION

                                                </label>


                                                <input
                                                type="hidden"
                                                id="memberCbuAction"
                                                name="cbu_action">


                                                <div class="member-cbu-action-dropdown">

                                                    <button
                                                    type="button"
                                                    id="memberCbuActionBtn"
                                                    class="member-status-btn">

                                                        <span
                                                        id="memberCbuActionText">

                                                            Select Action

                                                        </span>


                                                        <i class="bi bi-chevron-down"></i>

                                                    </button>


                                                    <div
                                                    id="memberCbuActionMenu"
                                                    class="member-status-menu">


                                                        <button
                                                        type="button"
                                                        class="member-cbu-action-item member-status-item"
                                                        data-value="ADD">

                                                            ADD CBU

                                                        </button>


                                                        <button
                                                        type="button"
                                                        class="member-cbu-action-item member-status-item"
                                                        data-value="DEDUCT">

                                                            DEDUCT CBU

                                                        </button>

                                                    </div>

                                                </div>

                                            </div>


                                            <!-- CURRENT CBU -->

                                            <div class="col-md-6">

                                                <label class="member-field-label">

                                                    CURRENT CBU

                                                </label>


                                                <div
                                                class="member-money-input member-readonly-money"
                                                title="This value is calculated by the system and cannot be edited.">

                                                    <span>
                                                        ₱
                                                    </span>


                                                    <input
                                                    type="text"
                                                    id="memberCurrentCbuDisplay"
                                                    class="form-control member-input member-readonly-input"
                                                    value="0.00"
                                                    readonly
                                                    aria-readonly="true">


                                                    <i
                                                    class="bi bi-lock-fill member-readonly-lock"
                                                    aria-hidden="true">
                                                    </i>

                                                </div>

                                            </div>


                                            <!-- BALANCE AFTER -->

                                            <div class="col-md-6">

                                                <label class="member-field-label">

                                                    BALANCE AFTER

                                                </label>


                                                <div
                                                class="member-money-input member-readonly-money"
                                                title="This value is calculated by the system and cannot be edited.">

                                                    <span>
                                                        ₱
                                                    </span>


                                                    <input
                                                    type="text"
                                                    id="memberCbuBalanceAfter"
                                                    class="form-control member-input member-readonly-input"
                                                    value="0.00"
                                                    readonly
                                                    aria-readonly="true">


                                                    <i
                                                    class="bi bi-lock-fill member-readonly-lock"
                                                    aria-hidden="true">
                                                    </i>

                                                </div>

                                            </div>

                                            <!-- REMARKS -->

                                            <div class="col-md-12">

                                                <label class="member-field-label">

                                                    REMARKS

                                                </label>


                                                <input
                                                type="text"
                                                id="memberCbuRemarks"
                                                name="cbu_adjustment_remarks"
                                                class="form-control member-input"
                                                maxlength="255"
                                                placeholder="Enter CBU transaction remarks">

                                            </div>

                                        </div>


                                        <p class="member-summary-note mt-3">

                                            CBU changes are recorded as separate
                                            transactions and do not overwrite the
                                            member's contribution history.

                                        </p>

                                    </div>

                                </div>


                                <!-- DIVIDER -->

                                <div class="member-form-divider"></div>


                                <!-- =================================
                                     SUPPORTING DOCUMENTS
                                ================================== -->

                                                                <div
                                class="member-section-heading member-document-heading">

                                    <span class="member-section-icon">

                                        <i class="bi bi-paperclip"></i>

                                    </span>


                                    <div>

                                        
                                        <h3>
                                            SUPPORTING DOCUMENTS <span class="member-required-asterisk" aria-hidden="true" 
                                            title="Required for new members only">*</span>
                                        </h3>


                                        <p>
                                            Required for new members · PDF, JPG, PNG or WEBP
                                        </p>

                                    </div>

                                </div>


                                <div class="member-document-upload">

                                    <input
                                    type="file"
                                    id="attachment"
                                    name="attachment[]"
                                    class="form-control member-input member-file-input"
                                    accept=".pdf,.jpg,.jpeg,.png,.webp"
                                    multiple>


                                    <div
                                    id="selectedAttachments"
                                    class="member-selected-files">

                                        No files selected.

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         FOOTER
                    ================================================== -->

                    <div class="member-save-container">

                        <button
                        id="memberSaveBtn"
                        type="submit"
                        class="member-save-btn">

                            <i class="bi bi-check2-circle"></i>

                            <span>
                                SAVE
                            </span>

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


<!-- =========================================================
     MEMBER ATTACHMENT VIEWER
========================================================= -->

<div
class="modal fade"
id="attachmentModal"
tabindex="-1"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="modal-content attachment-window">


            <!-- HEADER -->

            <div class="member-attachment-header">

                <div>

                    <h2>
                        MEMBER ATTACHMENTS
                    </h2>

                    <p>
                        View or remove the member's supporting documents
                    </p>

                </div>


                <button
                type="button"
                class="member-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close">

                    ×

                </button>

            </div>


            <!-- BODY -->

            <div class="member-attachment-body">

                <div id="attachmentList">

                    <p class="member-empty-attachment">

                        No attachments found.

                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

<!-- =========================================================
     MEMBER CBU HISTORY VIEWER
========================================================= -->

<div
class="modal fade"
id="cbuHistoryModal"
tabindex="-1"
aria-hidden="true">

    <div
    class="modal-dialog modal-xl modal-dialog-centered">

        <div
        class="modal-content attachment-window">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="member-attachment-header">

                <div>

                    <h2>
                        CBU HISTORY
                    </h2>

                    <p>
                        Capital Build-Up transaction history of the member
                    </p>

                </div>


                <button
                type="button"
                class="member-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close">

                    ×

                </button>

            </div>


            <!-- =================================================
                 BODY
            ================================================== -->

            <div class="member-attachment-body">


                <!-- =============================================
                     MEMBER SUMMARY
                ============================================== -->

                <div class="member-cbu-history-summary">

                    <div>

                        <span>
                            MEMBER
                        </span>

                        <strong
                        id="cbuHistoryMemberName">

                            —

                        </strong>

                    </div>


                    <div>

                        <span>
                            CURRENT CBU
                        </span>

                        <strong
                        id="cbuHistoryCurrentBalance">

                            ₱0.00

                        </strong>

                    </div>

                </div>


                <!-- =============================================
                     HISTORY HEADER
                ============================================== -->

                <div class="member-cbu-history-header">

                    <div>
                        DATE
                    </div>

                    <div>
                        TYPE
                    </div>

                    <div>
                        AMOUNT
                    </div>

                    <div>
                        PREVIOUS
                    </div>

                    <div>
                        BALANCE AFTER
                    </div>

                    <div>
                        REMARKS
                    </div>

                    <div>
                        RECORDED BY
                    </div>

                </div>


                <!-- =============================================
                     HISTORY LIST
                ============================================== -->

                <div
                id="cbuHistoryList"
                class="member-cbu-history-list">

                    <div class="member-cbu-history-empty">

                        <i class="bi bi-clock-history"></i>

                        <p>
                            No CBU transactions found.
                        </p>

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>