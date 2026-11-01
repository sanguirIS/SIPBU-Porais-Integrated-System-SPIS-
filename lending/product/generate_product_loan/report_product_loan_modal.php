<div
class="modal fade"
id="generateProductLoanReportModal"
tabindex="-1"
aria-labelledby="generateProductLoanReportTitle"
aria-hidden="true"
data-server-date="<?php echo date("Y-m-d"); ?>">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content report-product-loan-window">

            <div class="report-product-loan-header">

                <h1 id="generateProductLoanReportTitle">
                    GENERATE REPORT
                </h1>

                <button
                type="button"
                class="report-product-loan-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close report options">
                    &times;
                </button>

            </div>

            <div class="modal-body report-product-loan-body">

                <hr>

                <div class="row">

                    <!-- INTEREST -->

                    <div class="col-md-6 mb-3">

                        <label for="generateProductLoanInterestBtn">
                            Interest Rate
                        </label>

                        <input
                        type="hidden"
                        id="generateProductLoanInterest"
                        value="all">

                        <div class="generate-product-loan-dropdown">

                            <button
                            type="button"
                            id="generateProductLoanInterestBtn"
                            class="generate-product-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                                <span id="generateProductLoanInterestText">
                                    All Interest Rates
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateProductLoanInterestMenu"
                            class="generate-product-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="all">
                                    All Interest Rates
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="10">
                                    10% - 180 Days
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="20">
                                    20% - 360 Days
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- STATUS -->

                    <div class="col-md-6 mb-3">

                        <label for="generateProductLoanStatusBtn">
                            Status
                        </label>

                        <input
                        type="hidden"
                        id="generateProductLoanStatus"
                        value="">

                        <div class="generate-product-loan-dropdown">

                            <button
                            type="button"
                            id="generateProductLoanStatusBtn"
                            class="generate-product-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                                <span id="generateProductLoanStatusText">
                                    Select Status
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateProductLoanStatusMenu"
                            class="generate-product-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="all">
                                    All Status
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="Pending">
                                    Pending
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="Delivered">
                                    Delivered
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="Rejected">
                                    Rejected
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- REMARKS -->

                    <div class="col-md-6 mb-3">

                        <label for="generateProductLoanRemarksBtn">
                            Remarks
                        </label>

                        <input
                        type="hidden"
                        id="generateProductLoanRemarks"
                        value="all">

                        <div class="generate-product-loan-dropdown">

                            <button
                            type="button"
                            id="generateProductLoanRemarksBtn"
                            class="generate-product-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false"
                            aria-disabled="true"
                            disabled>

                                <span id="generateProductLoanRemarksText">
                                    Select Status First
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateProductLoanRemarksMenu"
                            class="generate-product-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="all">
                                    All Remarks
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="On Going">
                                    On Going
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="Overdue">
                                    Overdue
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="Paid">
                                    Paid
                                </button>

                            </div>

                        </div>

                    </div>


                    <!-- APPLICATION DATE -->

                    <div class="col-md-6 mb-3">

                        <label for="generateProductLoanDateBtn">
                            Application Date Range
                        </label>

                        <input
                        type="hidden"
                        id="generateProductLoanDateOption"
                        value="all">

                        <div class="generate-product-loan-dropdown">

                            <button
                            type="button"
                            id="generateProductLoanDateBtn"
                            class="generate-product-loan-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">

                                <span id="generateProductLoanDateText">
                                    All Applications
                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateProductLoanDateMenu"
                            class="generate-product-loan-dropdown-menu"
                            role="listbox">

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
                                data-value="all">
                                    All Applications
                                </button>

                                <button
                                type="button"
                                class="generate-product-loan-dropdown-item"
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
                id="generateProductLoanCustomDateFields"
                hidden>

                    <div class="col-md-6 mb-3">

                        <label for="generateProductLoanDateFrom">
                            From
                        </label>

                        <input
                        type="date"
                        id="generateProductLoanDateFrom"
                        class="report-product-loan-input">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label for="generateProductLoanDateTo">
                            To
                        </label>

                        <input
                        type="date"
                        id="generateProductLoanDateTo"
                        class="report-product-loan-input">

                    </div>

                </div>

                <hr>

                <div class="report-product-loan-preview-container">

                    <button
                    type="button"
                    id="previewProductLoanReport"
                    class="report-product-loan-preview-btn">
                        PREVIEW
                    </button>

                </div>

            </div>

        </div>

    </div>

</div>