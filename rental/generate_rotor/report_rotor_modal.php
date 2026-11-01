<div class="modal fade"
id="generateRotorReportModal">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content report-rotor-window">

            <div class="report-rotor-header">

                <h1>

                    GENERATE REPORT

                </h1>

                <button
                type="button"
                class="report-rotor-close-btn"
                data-bs-dismiss="modal">

                    ×

                </button>

            </div>

            <div class="modal-body report-rotor-body">

                <hr>

                <div class="row">

                    <!-- Affiliate -->

                    <div class="col-md-6 mb-3">

                        <label>

                            Affiliate

                        </label>

                        <input
                        type="hidden"
                        id="generateRotorAffiliate"
                        value="all">

                        <div class="generate-rotor-dropdown">

                            <button
                            type="button"
                            id="generateRotorAffiliateBtn"
                            class="generate-rotor-dropdown-btn">

                                <span id="generateRotorAffiliateText">

                                    All Affiliates

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateRotorAffiliateMenu"
                            class="generate-rotor-dropdown-menu">

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="all">

                                    All Affiliates

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Member">

                                    Member

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Not Member">

                                    Not Member

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Status -->

                    <div class="col-md-6 mb-3">

                        <label>

                            Status

                        </label>

                        <input
                        type="hidden"
                        id="generateRotorStatus"
                        value="all">

                        <div class="generate-rotor-dropdown">

                            <button
                            type="button"
                            id="generateRotorStatusBtn"
                            class="generate-rotor-dropdown-btn">

                                <span id="generateRotorStatusText">

                                    All Status

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateRotorStatusMenu"
                            class="generate-rotor-dropdown-menu">

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="all">

                                    All Status

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Pending">

                                    Pending

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Ongoing">

                                    Ongoing

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Done">

                                    Done

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Maintenance">

                                    Maintenance

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Remarks -->

                    <div class="col-md-6 mb-3">

                        <label>

                            Remarks

                        </label>

                        <input
                        type="hidden"
                        id="generateRotorRemarks"
                        value="all">

                        <div class="generate-rotor-dropdown">

                            <button
                            type="button"
                            id="generateRotorRemarksBtn"
                            class="generate-rotor-dropdown-btn">

                                <span id="generateRotorRemarksText">

                                    All Remarks

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateRotorRemarksMenu"
                            class="generate-rotor-dropdown-menu">

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="all">

                                    All Remarks

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Paid">

                                    Paid

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="Unpaid">

                                    Unpaid

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Date Range -->

                    <div class="col-md-6 mb-3">

                        <label>

                            Date Range

                        </label>

                        <input
                        type="hidden"
                        id="generateRotorDateOption"
                        value="all">

                        <div class="generate-rotor-dropdown">

                            <button
                            type="button"
                            id="generateRotorDateBtn"
                            class="generate-rotor-dropdown-btn">

                                <span id="generateRotorDateText">

                                    All Transactions

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateRotorDateMenu"
                            class="generate-rotor-dropdown-menu">

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="all">

                                    All Transactions

                                </div>

                                <div
                                class="generate-rotor-dropdown-item"
                                data-value="custom">

                                    Custom Date

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div
                class="row"
                id="generateRotorCustomDateFields"
                style="display:none;">

                    <div class="col-md-6 mb-3">

                        <label>

                            From

                        </label>

                        <input
                        type="date"
                        id="generateRotorDateFrom"
                        class="report-rotor-input">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>

                            To

                        </label>

                        <input
                        type="date"
                        id="generateRotorDateTo"
                        class="report-rotor-input">

                    </div>

                </div>

                <hr>

                <div class="report-rotor-preview-container">

                    <button
                    id="previewRotorReport"
                    class="report-rotor-preview-btn">

                        PREVIEW

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>