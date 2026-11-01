<div
class="modal fade"
id="generateMonetaryLoanReportModal"
tabindex="-1"
aria-labelledby="generateMonetaryLoanReportTitle"
aria-hidden="true"
data-server-date="<?php echo date("Y-m-d"); ?>">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content report-monetary-loan-window">

            <div class="report-monetary-loan-header">

                <h1 id="generateMonetaryLoanReportTitle">
                    GENERATE REPORT
                </h1>

                <button
                type="button"
                class="report-monetary-loan-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close report options">
                    &times;
                </button>

            </div>

            <div class="modal-body report-monetary-loan-body">

                <hr>

                                <div class="row">


                    <!-- MONETARY LOAN TYPE -->

                    <div class="col-md-6 mb-3">

                        <label for="generateMonetaryLoanTypeBtn">
                            Monetary Loan Type
                        </label>

                        <input
                        type="hidden"
                        id="generateMonetaryLoanType"
                        value="all">

                        <div class="generate-monetary-loan-dropdown">

                            <button
                            type="button"
                            id="generateMonetaryLoanTypeBtn"
                            class="generate-monetary-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                                <span id="generateMonetaryLoanTypeText">
                                    All Loan Types
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateMonetaryLoanTypeMenu"
                            class="generate-monetary-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="all">
                                    All Loan Types
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Crop Production Loan">
                                    Crop Production Loan
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Dairy Farmers Loan">
                                    Dairy Farmers Loan
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Emergency Loan">
                                    Emergency Loan
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Long-Term Loan">
                                    Long-Term Loan
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- INTEREST -->

                    <div class="col-md-6 mb-3">

                        <label for="generateMonetaryLoanInterestBtn">
                            Interest Rate
                        </label>

                        <input
                        type="hidden"
                        id="generateMonetaryLoanInterest"
                        value="all">

                        <div class="generate-monetary-loan-dropdown">

                            <button
                            type="button"
                            id="generateMonetaryLoanInterestBtn"
                            class="generate-monetary-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                                <span id="generateMonetaryLoanInterestText">
                                    All Interest Rates
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateMonetaryLoanInterestMenu"
                            class="generate-monetary-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="all">
                                    All Interest Rates
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="10">
                                    10% - 180 Days
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="20">
                                    20% - 360 Days
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-6 mb-3">

                        <label for="generateMonetaryLoanStatusBtn">
                            Status
                        </label>

                        <input
                        type="hidden"
                        id="generateMonetaryLoanStatus"
                        value="">

                        <div class="generate-monetary-loan-dropdown">

                            <button
                            type="button"
                            id="generateMonetaryLoanStatusBtn"
                            class="generate-monetary-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                                <span id="generateMonetaryLoanStatusText">
                                    Select Status
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateMonetaryLoanStatusMenu"
                            class="generate-monetary-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="all">
                                    All Status
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Pending">
                                    Pending
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Accepted">
                                    Accepted
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Rejected">
                                    Rejected
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- REMARKS -->

                    <div class="col-md-6 mb-3">

                        <label for="generateMonetaryLoanRemarksBtn">
                            Remarks
                        </label>

                        <input
                        type="hidden"
                        id="generateMonetaryLoanRemarks"
                        value="all">

                        <div class="generate-monetary-loan-dropdown">

                            <button
                            type="button"
                            id="generateMonetaryLoanRemarksBtn"
                            class="generate-monetary-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-disabled="true"
                            disabled>

                                <span id="generateMonetaryLoanRemarksText">
                                    Select Status First
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateMonetaryLoanRemarksMenu"
                            class="generate-monetary-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="all">
                                    All Remarks
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="On Going">
                                    On Going
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Overdue">
                                    Overdue
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="Paid">
                                    Paid
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- APPLICATION DATE -->

                    <div class="col-md-6 mb-3 mx-auto">

                        <label for="generateMonetaryLoanDateBtn">
                            Application Date Range
                        </label>

                        <input
                        type="hidden"
                        id="generateMonetaryLoanDateOption"
                        value="all">

                        <div class="generate-monetary-loan-dropdown">

                            <button
                            type="button"
                            id="generateMonetaryLoanDateBtn"
                            class="generate-monetary-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                                <span id="generateMonetaryLoanDateText">
                                    All Applications
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateMonetaryLoanDateMenu"
                            class="generate-monetary-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="all">
                                    All Applications
                                </button>

                                <button
                                type="button"
                                class="generate-monetary-loan-dropdown-item"
                                data-value="custom">
                                    Custom Date
                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- CUSTOM DATE FIELDS -->

                <div
                class="row"
                id="generateMonetaryLoanCustomDateFields"
                hidden>

                    <div class="col-md-6 mb-3">

                        <label for="generateMonetaryLoanDateFrom">
                            From
                        </label>

                        <input
                        type="date"
                        id="generateMonetaryLoanDateFrom"
                        class="report-monetary-loan-input">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label for="generateMonetaryLoanDateTo">
                            To
                        </label>

                        <input
                        type="date"
                        id="generateMonetaryLoanDateTo"
                        class="report-monetary-loan-input">

                    </div>

                </div>

                <hr>

                <div class="report-monetary-loan-preview-container">

                    <button
                    type="button"
                    id="previewMonetaryLoanReport"
                    class="report-monetary-loan-preview-btn">
                        PREVIEW
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>