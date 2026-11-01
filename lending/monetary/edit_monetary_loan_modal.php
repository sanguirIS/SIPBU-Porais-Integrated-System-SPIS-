<?php

/* =========================================================
   EDIT MONETARY LOAN MODAL
========================================================= */

?>

<div
class="modal fade"
id="editMonetaryLoanModal"
tabindex="-1"
aria-labelledby="editMonetaryLoanModalTitle"
aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content monetary-loan-modal-window monetary-loan-edit-window">

            <form
            id="editMonetaryLoanForm"
            autocomplete="off"
            novalidate>


                <!-- =====================================
                     HIDDEN VALUES
                ====================================== -->

                <input
                type="hidden"
                id="editMonetaryLoanCSRFToken"
                name="csrf_token"
                value="<?php
                echo monetaryLoanEscape(
                    $csrfToken
                );
                ?>">


                <input
                type="hidden"
                id="editMonetaryLoanID"
                name="monetary_loan_id">


                <input
                type="hidden"
                id="editMonetaryLoanMemberID"
                name="member_id">


                <input
                type="hidden"
                id="editMonetaryLoanOriginalMemberID">


                <input
                type="hidden"
                id="editMonetaryLoanExistingStatus">


                <!-- =====================================
                     MODAL HEADER
                ====================================== -->

                <div class="monetary-loan-modal-header">

                    <div class="monetary-loan-modal-title-group">

                        <h1 id="editMonetaryLoanModalTitle">
                            EDIT MONETARY LOAN
                        </h1>


                        <span
                        id="editMonetaryLoanStatusBadge"
                        class="monetary-loan-edit-status-badge monetary-status-pending">

                            <i class="bi bi-clock"></i>

                            <span>
                                PENDING
                            </span>

                        </span>

                    </div>


                    <button
                    type="button"
                    class="monetary-loan-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                        ×

                    </button>

                </div>


                <hr class="monetary-loan-modal-divider">


                <!-- =====================================
                     SCROLLABLE BODY
                ====================================== -->

                <div class="monetary-loan-modal-body">


                    <!-- =================================
                         LOAN INFORMATION
                    ================================== -->

                    <section class="monetary-loan-form-section">

                        <div class="monetary-loan-section-heading">

                            <div class="monetary-loan-section-icon">

                                <i class="bi bi-file-earmark-text"></i>

                            </div>


                            <div class="monetary-loan-section-title">

                                <h3>
                                    LOAN INFORMATION
                                </h3>

                                <p id="editMonetaryLoanInformationText">
                                    Review and update this Pending Monetary Loan application.
                                </p>

                            </div>

                        </div>


                        <div class="monetary-loan-edit-information-grid">


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanDisplayID">
                                    LOAN ID
                                </label>

                                <input
                                type="text"
                                id="editMonetaryLoanDisplayID"
                                class="monetary-loan-input"
                                readonly>

                            </div>


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanApplicationDate">
                                    APPLICATION DATE
                                </label>

                                <div class="monetary-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editMonetaryLoanApplicationDate"
                                    class="monetary-loan-input"
                                    readonly>

                                    <i class="bi bi-calendar3"></i>

                                </div>

                            </div>


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanReleaseDate">
                                    ACCEPTED DATE
                                </label>

                                <div class="monetary-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editMonetaryLoanReleaseDate"
                                    class="monetary-loan-input"
                                    value="Not accepted"
                                    readonly>

                                    <i class="bi bi-check2-circle"></i>

                                </div>

                            </div>


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanDueDate">
                                    DUE DATE
                                </label>

                                <div class="monetary-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editMonetaryLoanDueDate"
                                    class="monetary-loan-input"
                                    value="Generated upon acceptance"
                                    readonly>

                                    <i class="bi bi-calendar-check"></i>

                                </div>

                            </div>


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanApprovedBy">
                                    APPROVED BY
                                </label>

                                <div class="monetary-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editMonetaryLoanApprovedBy"
                                    class="monetary-loan-input"
                                    value="—"
                                    readonly>

                                    <i class="bi bi-person-check"></i>

                                </div>

                            </div>

                        </div>

                    </section>


                    <!-- =================================
                         BORROWER, TERMS, AND STATUS
                    ================================== -->

                    <section class="monetary-loan-form-section">

                        <div class="monetary-loan-section-heading">

                            <div class="monetary-loan-section-icon">

                                <i class="bi bi-person-vcard"></i>

                            </div>


                            <div class="monetary-loan-section-title">

                                <h3>
                                    BORROWER, TERMS, AND STATUS
                                </h3>

                                <p id="editMonetaryLoanBorrowerNote">
                                    Pending details remain editable until the application is accepted or rejected.
                                </p>

                            </div>

                        </div>


                        <div class="monetary-loan-edit-borrower-grid">


                            <!-- MEMBER -->

                            <div class="monetary-loan-field monetary-loan-edit-member-field">

                                <label for="editMonetaryLoanMemberName">

                                    MEMBER NAME

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <div class="monetary-loan-member-wrapper">

                                    <div class="monetary-loan-member-input-wrap">

                                        <i class="bi bi-search"></i>

                                        <input
                                        type="text"
                                        id="editMonetaryLoanMemberName"
                                        class="monetary-loan-input"
                                        placeholder="Type at least 3 letters"
                                        maxlength="150"
                                        autocomplete="off">

                                    </div>


                                    <div
                                    id="editMonetaryLoanMemberResults"
                                    class="monetary-loan-member-results"
                                    role="listbox">
                                    </div>

                                </div>


                                <small
                                id="editMonetaryLoanMemberError"
                                class="monetary-loan-field-error">
                                </small>

                            </div>


                            <!-- LOAN TERM -->

                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanTerm">

                                    LOAN TERM

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <select
                                id="editMonetaryLoanTerm"
                                name="term_days"
                                class="monetary-loan-select">

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


                                <small
                                id="editMonetaryLoanTermError"
                                class="monetary-loan-field-error">
                                </small>

                            </div>


                            <!-- STATUS -->

                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanStatus">

                                    STATUS

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <select
                                id="editMonetaryLoanStatus"
                                name="status"
                                class="monetary-loan-select">

                                    <option value="Pending">
                                        Pending
                                    </option>

                                    <option value="Accepted">
                                        Accepted
                                    </option>

                                    <option value="Rejected">
                                        Rejected
                                    </option>

                                </select>


                                <small
                                id="editMonetaryLoanStatusPermission"
                                class="monetary-loan-field-note">

                                    Only users with Full Lending Access may accept or reject an application.

                                </small>

                            </div>

                        </div>


                        <div class="monetary-loan-edit-details-grid">


                            <!-- MONETARY LOAN TYPE -->

                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanPurpose">

                                    MONETARY LOAN TYPE

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <select
                                id="editMonetaryLoanPurpose"
                                name="loan_purpose"
                                class="monetary-loan-select">

                                    <option
                                    value=""
                                    disabled
                                    hidden>
                                        Select Monetary Loan Type
                                    </option>

                                    <option value="Crop Production Loan">
                                        Crop Production Loan
                                    </option>

                                    <option value="Dairy Farmers Loan">
                                        Dairy Farmers Loan
                                    </option>

                                    <option value="Emergency Loan">
                                        Emergency Loan
                                    </option>

                                    <option value="Long-Term Loan">
                                        Long-Term Loan
                                    </option>

                                </select>


                                <small
                                id="editMonetaryLoanPurposeError"
                                class="monetary-loan-field-error">
                                </small>

                            </div>


                            <!-- PRINCIPAL -->

                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanPrincipalAmount">

                                    PRINCIPAL AMOUNT

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <div class="monetary-loan-money-input">

                                    <span>
                                        ₱
                                    </span>

                                    <input
                                    type="number"
                                    id="editMonetaryLoanPrincipalAmount"
                                    name="principal_amount"
                                    class="monetary-loan-input"
                                    min="0.01"
                                    max="9999999999.99"
                                    step="0.01"
                                    placeholder="0.00"
                                    inputmode="decimal">

                                </div>


                                <small
                                id="editMonetaryLoanPrincipalError"
                                class="monetary-loan-field-error">
                                </small>

                                                        </div>


                            <!-- LONG-TERM LOAN DURATION -->

                            <div
                            id="editMonetaryLoanYearsField"
                            class="monetary-loan-field"
                            hidden>

                                <label for="editMonetaryLoanYears">

                                    LOAN TERM (YEARS)

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <input
                                type="number"
                                id="editMonetaryLoanYears"
                                name="loan_term_years"
                                class="monetary-loan-input"
                                min="1"
                                max="65535"
                                step="1"
                                inputmode="numeric"
                                placeholder="Enter number of years">


                                <small class="monetary-loan-field-note">
                                    Required only for Long-Term Loan.
                                </small>


                                <small
                                id="editMonetaryLoanYearsError"
                                class="monetary-loan-field-error">
                                </small>

                            </div>

                        </div>


                        <!-- REJECTION REASON -->

                        <div
                        id="editMonetaryLoanRejectionBlock"
                        class="monetary-loan-rejection-block"
                        hidden>

                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanRejectionReason">

                                    REJECTION REASON

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <textarea
                                id="editMonetaryLoanRejectionReason"
                                name="rejection_reason"
                                class="monetary-loan-textarea"
                                maxlength="1000"
                                placeholder="Enter the reason for rejecting this application"></textarea>


                                <small
                                id="editMonetaryLoanRejectionError"
                                class="monetary-loan-field-error">
                                </small>

                            </div>

                        </div>

                    </section>


                    <!-- =================================
                         ATTACHMENTS AND SUMMARY
                    ================================== -->

                    <div class="monetary-loan-bottom-grid">


                        <!-- ATTACHMENTS -->

                        <section class="monetary-loan-form-section monetary-loan-edit-attachments-section">

                            <div class="monetary-loan-section-heading">

                                <div class="monetary-loan-section-icon">

                                    <i class="bi bi-paperclip"></i>

                                </div>


                                <div class="monetary-loan-section-title">

                                    <h3>
                                        ATTACHMENTS
                                    </h3>

                                    <p>
                                        View existing files or add supporting documents.
                                    </p>

                                </div>

                            </div>


                            <input
                            type="file"
                            id="editMonetaryLoanAttachments"
                            name="attachments[]"
                            class="monetary-loan-file-input"
                            accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            aria-describedby="editMonetaryLoanAttachmentHelp"
                            multiple>


                            <label
                            for="editMonetaryLoanAttachments"
                            id="editMonetaryLoanUploadBox"
                            class="monetary-loan-upload-box">

                                <i class="bi bi-cloud-arrow-up"></i>

                                <strong>
                                    SELECT FILES
                                </strong>

                                <small id="editMonetaryLoanAttachmentHelp">

                                    JPG, PNG, PDF, DOC, or DOCX · Maximum of 10 new files · 10 MB each · 50 MB total

                                </small>

                            </label>


                            <div
                            id="editMonetaryLoanAttachmentList"
                            class="monetary-loan-attachment-list">

                                <div class="monetary-loan-no-attachment">
                                    No attachments found
                                </div>

                            </div>

                        </section>


                        <!-- FINANCIAL SUMMARY -->

                        <section class="monetary-loan-form-section monetary-loan-edit-summary-section">

                            <div class="monetary-loan-section-heading">

                                <div class="monetary-loan-section-icon">

                                    <i class="bi bi-calculator"></i>

                                </div>


                                <div class="monetary-loan-section-title">

                                    <h3>
                                        FINANCIAL SUMMARY
                                    </h3>

                                    <p>
                                        Values are calculated using the saved loan records.
                                    </p>

                                </div>

                            </div>


                                                        <div class="monetary-loan-summary">

                                <div class="monetary-loan-summary-row">
                                    <span>LOAN PERIOD</span>
                                    <strong id="editMonetaryLoanSummaryTerm">—</strong>
                                </div>

                                <div class="monetary-loan-summary-row">
                                    <span style="font-size: 13px; font-weight: 800;">
                                        PAYOFF TOTAL
                                    </span>
                                    <strong
                                        id="editMonetaryLoanSummaryScheduledTotal"
                                        style="font-size: 17px; font-weight: 800;">
                                        ₱0.00
                                    </strong>
                                </div>

                                <div class="monetary-loan-summary-row">
                                    <span>PRINCIPAL</span>
                                    <strong id="editMonetaryLoanSummaryPrincipal">
                                        ₱0.00
                                    </strong>
                                </div>

                                <div class="monetary-loan-summary-row">
                                    <span>INTEREST RATE</span>
                                    <strong id="editMonetaryLoanSummaryInterestRate">
                                        —
                                    </strong>
                                </div>

                                <div class="monetary-loan-summary-row">
                                    <span>INTEREST PAYABLE</span>
                                    <strong id="editMonetaryLoanSummaryScheduledInterest">
                                        ₱0.00
                                    </strong>
                                </div>

                                <!-- ESTIMATED DAILY PAYMENT -->

                                <div class="monetary-loan-summary-row">

                                    <span>
                                        DAILY PAYMENT
                                    </span>

                                    <strong id="editMonetaryLoanSummaryDailyPayment">
                                        —
                                    </strong>

                                </div>

                                <!-- ESTIMATED MONTHLY PAYMENT -->

                                <div class="monetary-loan-summary-row">

                                    <span>
                                        MONTHLY PAYMENT
                                    </span>

                                    <strong id="editMonetaryLoanSummaryMonthlyPayment">
                                        —
                                    </strong>

                                </div>
                               
                                <div class="monetary-loan-summary-row">

                                    <span>
                                        CURRENT ACCRUED INTEREST
                                    </span>

                                    <strong id="editMonetaryLoanSummaryAccruedInterest">
                                        ₱0.00
                                    </strong>

                                </div>


                                <div class="monetary-loan-summary-row">

                                    <span>
                                        TOTAL PAYMENTS
                                    </span>

                                    <strong id="editMonetaryLoanSummaryPayments">
                                        ₱0.00
                                    </strong>

                                </div>

                                <div class="monetary-loan-summary-row monetary-loan-summary-total">

                                    <span>
                                        OUTSTANDING BALANCE
                                    </span>

                                    <strong id="editMonetaryLoanSummaryBalance">
                                        —
                                    </strong>

                                </div>

                            </div>

                        </section>

                    </div>


                    <!-- =================================
                         RECORD PAYMENT
                    ================================== -->

                    <section
                    id="editMonetaryLoanPaymentSection"
                    class="monetary-loan-form-section monetary-loan-payment-section"
                    hidden>

                        <div class="monetary-loan-section-heading">

                            <div class="monetary-loan-section-icon">

                                <i class="bi bi-wallet2"></i>

                            </div>


                            <div class="monetary-loan-section-title">

                                <h3>
                                    RECORD PAYMENT
                                </h3>

                                <p>
                                    Interest is calculated up to the current Asia/Manila server date.
                                </p>

                            </div>

                        </div>


                        <!-- PAYMENT BREAKDOWN -->

                        <div class="monetary-loan-payment-breakdown">


                            <div
                            id="editMonetaryPaymentLongTermYearsItem"
                            hidden>

                                <span>
                                    LOAN TERM
                                </span>

                                <strong id="editMonetaryPaymentLongTermYears">
                                    —
                                </strong>

                            </div>


                            <div
                            id="editMonetaryPaymentLongTermPlanItem"
                            hidden>

                                <span>
                                    PAYMENT PLAN
                                </span>

                                <strong id="editMonetaryPaymentLongTermPlan">
                                    —
                                </strong>

                            </div>


                            <div>

                                <span>
                                    INITIAL LOAN PRINCIPAL
                                </span>

                                <strong id="editMonetaryPaymentPrincipal">
                                    ₱0.00
                                </strong>

                            </div>


                            <div>

                                <span>
                                    UNPAID INTEREST
                                </span>

                                <strong id="editMonetaryPaymentUnpaidInterest">
                                    ₱0.00
                                </strong>

                            </div>


                            <div>

                                <span>
                                    ACCRUED DAYS
                                </span>

                                <strong id="editMonetaryPaymentElapsedDays">
                                    0 Days
                                </strong>

                            </div>


                            <div>

                                <span>
                                    NEW ACCRUED INTEREST
                                </span>

                                <strong id="editMonetaryPaymentNewInterest">
                                    ₱0.00
                                </strong>

                            </div>


                            <div class="monetary-loan-payment-payoff">

                                <span>
                                    CURRENT PAYOFF BALANCE
                                </span>

                                <strong id="editMonetaryPaymentPayoffBalance">
                                    ₱0.00
                                </strong>

                            </div>

                        </div>


                        <!-- PAYMENT FORM -->

                        <div class="monetary-loan-payment-entry">


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanPaymentDate">
                                    PAYMENT DATE
                                </label>


                                <div class="monetary-loan-readonly-wrapper">

                                    <input
                                    type="text"
                                    id="editMonetaryLoanPaymentDate"
                                    class="monetary-loan-input"
                                    readonly>

                                    <i class="bi bi-calendar3"></i>

                                </div>

                            </div>


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanPaymentAmount">

                                    PAYMENT AMOUNT

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>


                                <div class="monetary-loan-money-input">

                                    <span>
                                        ₱
                                    </span>

                                    <input
                                    type="number"
                                    id="editMonetaryLoanPaymentAmount"
                                    class="monetary-loan-input"
                                    min="0.01"
                                    step="0.01"
                                    placeholder="0.00"
                                    inputmode="decimal">

                                </div>

                            </div>


                            <div class="monetary-loan-field">

                                <label for="editMonetaryLoanPaymentNotes">
                                    NOTES
                                </label>

                                <input
                                type="text"
                                id="editMonetaryLoanPaymentNotes"
                                class="monetary-loan-input"
                                maxlength="500"
                                placeholder="Optional payment note">

                            </div>


                            <button
                            type="button"
                            id="recordMonetaryLoanPaymentBtn"
                            class="monetary-loan-record-payment-btn">

                                <i class="bi bi-check2-circle"></i>

                                RECORD PAYMENT

                            </button>

                        </div>


                        <!-- PAYMENT HISTORY -->

                        <div class="monetary-loan-payment-history-heading">

                            <h3>
                                PAYMENT HISTORY
                            </h3>

                            <small>
                                Recorded payments cannot be edited or deleted directly.
                            </small>

                        </div>


                        <div class="monetary-loan-payment-table-wrapper">

                            <table class="monetary-loan-payment-table">

                                <thead>

                                    <tr>

                                        <th>
                                            DATE
                                        </th>

                                        <th>
                                            DAYS
                                        </th>

                                        <th>
                                            TYPE
                                        </th>

                                        <th>
                                            NOTES
                                        </th>

                                        <th>
                                            AMOUNT
                                        </th>

                                        <th>
                                            INTEREST
                                        </th>

                                        <th>
                                            PRINCIPAL
                                        </th>

                                        <th>
                                            BALANCE
                                        </th>

                                        <th>
                                            RECORDED BY
                                        </th>

                                        <th>
                                            ACTION
                                        </th>

                                    </tr>

                                </thead>


                                <tbody id="editMonetaryLoanPaymentHistory">

                                    <tr class="monetary-loan-payment-empty">

                                        <td colspan="10">
                                            No payments recorded yet
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </section>

                </div>


                <!-- =====================================
                     MODAL FOOTER
                ====================================== -->

                <div class="monetary-loan-modal-footer">

                    <button
                    type="button"
                    class="monetary-loan-cancel-btn"
                    data-bs-dismiss="modal">

                        CLOSE

                    </button>


                    <button
                    type="submit"
                    id="saveMonetaryLoanChangesBtn"
                    class="monetary-loan-save-btn">

                        SAVE CHANGES

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>