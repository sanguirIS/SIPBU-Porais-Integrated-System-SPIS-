<div
class="modal fade"
id="addMonetaryLoanModal"
tabindex="-1"
aria-labelledby="addMonetaryLoanModalTitle"
aria-hidden="true">

    <div class="modal-dialog modal-xl modal-dialog-centered">

        <div class="modal-content monetary-loan-modal-window">

            <form
            id="addMonetaryLoanForm"
            method="post"
            enctype="multipart/form-data"
            novalidate>

                <!-- HIDDEN VALUES -->

                <input
                type="hidden"
                id="monetaryLoanMemberID"
                name="member_id">

                <input
                type="hidden"
                name="status"
                value="Pending">

                <input
                type="hidden"
                name="csrf_token"
                value="<?php echo monetaryLoanEscape($csrfToken); ?>">


                <!-- MODAL HEADER -->

                <div class="monetary-loan-modal-header">

                    <div class="monetary-loan-modal-title-group">

                        <h1 id="addMonetaryLoanModalTitle">
                            ADD MONETARY LOAN
                        </h1>

                        <span class="monetary-loan-pending-badge">

                            <i class="bi bi-clock"></i>

                            PENDING

                        </span>

                    </div>

                    <button
                    type="button"
                    class="monetary-loan-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                        &times;

                    </button>

                </div>

                <hr class="monetary-loan-modal-divider">


                <!-- SCROLLABLE BODY -->

                <div class="monetary-loan-modal-body">


                    <!-- BORROWER INFORMATION -->

                    <section class="monetary-loan-form-section">

                        <div class="monetary-loan-section-heading">

                            <div class="monetary-loan-section-icon">

                                <i class="bi bi-person-vcard"></i>

                            </div>

                            <div class="monetary-loan-section-title">

                                <h3>
                                    BORROWER INFORMATION
                                </h3>

                                <p>
                                    Select a registered member for this Monetary Loan application.
                                </p>

                            </div>

                        </div>


                        <div class="monetary-loan-borrower-grid">


                            <!-- MEMBER -->

                            <div class="monetary-loan-field monetary-loan-member-wrapper">

                                <label for="monetaryLoanMemberName">

                                    MEMBER NAME

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>

                                <div class="monetary-loan-member-input-wrap">

                                    <i class="bi bi-search"></i>

                                    <input
                                    type="text"
                                    id="monetaryLoanMemberName"
                                    class="monetary-loan-input"
                                    placeholder="Type at least 3 letters"
                                    autocomplete="off">

                                </div>

                                <div
                                id="monetaryLoanMemberResults"
                                class="monetary-loan-member-results"
                                role="listbox">
                                </div>

                            </div>


                            <!-- APPLICATION DATE -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanApplicationDate">
                                    APPLICATION DATE
                                </label>

                                <input
                                type="text"
                                id="monetaryLoanApplicationDate"
                                class="monetary-loan-input"
                                value="<?php
                                echo monetaryLoanEscape(
                                    monetaryLoanDisplayDate(
                                        $serverDate
                                    )
                                );
                                ?>"
                                readonly>

                            </div>


                            <!-- DUE DATE -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanDueDate">
                                    DUE DATE
                                </label>

                                <input
                                type="text"
                                id="monetaryLoanDueDate"
                                class="monetary-loan-input"
                                value="Generated upon acceptance"
                                readonly>

                            </div>

                        </div>

                    </section>


                    <!-- LOAN DETAILS -->

                    <section class="monetary-loan-form-section">

                        <div class="monetary-loan-section-heading">

                            <div class="monetary-loan-section-icon">

                                <i class="bi bi-cash-stack"></i>

                            </div>

                            <div class="monetary-loan-section-title">

                                <h3>
                                    LOAN DETAILS AND TERMS
                                </h3>

                                <p>
                                    Select the Monetary Loan type, enter the requested amount, and choose the agreed loan period.
                                </p>

                            </div>

                        </div>


                                                <div class="monetary-loan-add-details-grid">


                            <!-- MONETARY LOAN TYPE -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanPurpose">

                                    MONETARY LOAN TYPE

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>

                                <select
                                id="monetaryLoanPurpose"
                                name="loan_purpose"
                                class="monetary-loan-select"
                                required>

                                    <option
                                    value=""
                                    selected
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

                            </div>


                            <!-- PRINCIPAL -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanPrincipalAmount">

                                    PRINCIPAL AMOUNT

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>

                                <input
                                type="number"
                                id="monetaryLoanPrincipalAmount"
                                name="principal_amount"
                                class="monetary-loan-input"
                                min="0.01"
                                max="9999999999.99"
                                step="0.01"
                                inputmode="decimal"
                                placeholder="0.00">

                            </div>


                            <!-- LONG-TERM LOAN DURATION -->

                            <div
                            id="monetaryLoanYearsField"
                            class="monetary-loan-field"
                            hidden>

                                <label for="monetaryLoanYears">

                                    LOAN TERM (YEARS)

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>

                                <input
                                type="number"
                                id="monetaryLoanYears"
                                name="loan_term_years"
                                class="monetary-loan-input"
                                min="1"
                                step="1"
                                inputmode="numeric"
                                placeholder="Enter number of years">

                                <small class="monetary-loan-field-note">
                                    Required only for Long-Term Loan.
                                </small>

                            </div>

                        </div>


                        <div class="monetary-loan-terms-grid">

                            <!-- TERM -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanTerm">

                                    LOAN TERM

                                    <span class="monetary-loan-required">
                                        *
                                    </span>

                                </label>

                                <select
                                id="monetaryLoanTerm"
                                name="loan_term"
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

                            </div>


                            <!-- INTEREST -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanInterestRate">
                                    INTEREST RATE
                                </label>

                                <input
                                type="text"
                                id="monetaryLoanInterestRate"
                                class="monetary-loan-input"
                                value="—"
                                readonly>

                            </div>


                            <!-- SCHEDULED INTEREST -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanScheduledInterest">
                                    INTEREST PAYABLE
                                </label>

                                <input
                                type="text"
                                id="monetaryLoanScheduledInterest"
                                class="monetary-loan-input"
                                value="₱0.00"
                                readonly>

                            </div>


                            <!-- SCHEDULED TOTAL -->

                            <div class="monetary-loan-field">

                                <label for="monetaryLoanScheduledTotal">
                                    PAYOFF TOTAL
                                </label>

                                <input
                                type="text"
                                id="monetaryLoanScheduledTotal"
                                class="monetary-loan-input"
                                value="₱0.00"
                                readonly>

                            </div>

                        </div>

                    </section>


                    <!-- ATTACHMENTS AND SUMMARY -->

                    <section class="monetary-loan-form-section">

                        <div class="monetary-loan-bottom-grid">


                            <!-- ATTACHMENTS -->

                            <div>

                                <div class="monetary-loan-section-heading">

                                    <div class="monetary-loan-section-icon">

                                        <i class="bi bi-paperclip"></i>

                                    </div>

                                    <div class="monetary-loan-section-title">

                                        <h3>
                                            ATTACHMENTS

                                            <span class="monetary-loan-required">
                                                *
                                            </span>
                                        </h3>

                                        <p>
                                            At least one supporting document is required for this application.
                                        </p>

                                    </div>

                                </div>

                                <label
                                for="monetaryLoanAttachments"
                                class="monetary-loan-upload-box">

                                    <i class="bi bi-cloud-arrow-up"></i>

                                    <strong>
                                        SELECT FILES
                                    </strong>

                                    <small>
                                        JPG, PNG, PDF, DOC, or DOCX · Maximum of 10 files · 10 MB each · 50 MB total
                                    </small>

                                </label>

                                <input
                                type="file"
                                id="monetaryLoanAttachments"
                                name="attachments[]"
                                class="monetary-loan-file-input"
                                accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                aria-required="true"
                                multiple>

                                <div
                                id="monetaryLoanAttachmentList"
                                class="monetary-loan-attachment-list">
                                </div>

                            </div>


                            <!-- FINANCIAL SUMMARY -->

                            <div>

                                <div class="monetary-loan-section-heading">

                                    <div class="monetary-loan-section-icon">

                                        <i class="bi bi-calculator"></i>

                                    </div>

                                    <div class="monetary-loan-section-title">

                                        <h3>
                                            FINANCIAL SUMMARY
                                        </h3>

                                        <p>
                                            Scheduled values are based on the complete agreed term.
                                        </p>

                                    </div>

                                </div>

                                <div class="monetary-loan-summary">

                                    <div class="monetary-loan-summary-row">
                                        <span>LOAN PERIOD</span>
                                        <strong id="monetaryLoanSummaryPeriod">—</strong>
                                    </div>

                                    <div class="monetary-loan-summary-row">
                                        <span style="font-size: 13px; font-weight: 800;">
                                            PAYOFF TOTAL
                                        </span>
                                        <strong
                                            id="monetaryLoanSummaryScheduledTotal"
                                            style="font-size: 17px; font-weight: 800;">
                                            ₱0.00
                                        </strong>
                                    </div>

                                    <div class="monetary-loan-summary-row">
                                        <span>PRINCIPAL</span>
                                        <strong id="monetaryLoanSummaryPrincipal">₱0.00</strong>
                                    </div>

                                    <div class="monetary-loan-summary-row">
                                        <span>INTEREST RATE</span>
                                        <strong id="monetaryLoanSummaryInterest">—</strong>
                                    </div>

                                    <div class="monetary-loan-summary-row">
                                        <span>INTEREST PAYABLE</span>
                                        <strong id="monetaryLoanSummaryScheduledInterest">₱0.00</strong>
                                    </div>

                                    <div class="monetary-loan-summary-row">
                                        <span>DAILY PAYMENT</span>
                                        <strong id="monetaryLoanSummaryDailyPayment">—</strong>
                                    </div>

                                    <div class="monetary-loan-summary-row">
                                        <span>MONTHLY PAYMENT</span>
                                        <strong id="monetaryLoanSummaryMonthlyPayment">—</strong>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </section>

                </div>


                <!-- MODAL FOOTER -->

                <div class="monetary-loan-modal-footer">

                    <button
                    type="button"
                    class="monetary-loan-cancel-btn"
                    data-bs-dismiss="modal">

                        CLOSE

                    </button>

                    <button
                    type="submit"
                    id="saveMonetaryLoanBtn"
                    class="monetary-loan-save-btn">

                        SAVE APPLICATION

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>