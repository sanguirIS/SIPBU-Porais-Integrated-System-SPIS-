<div class="modal fade"
id="generateReaperReportModal">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content report-reaper-window">

            <div class="report-reaper-header">

                <h1>

                    GENERATE REPORT

                </h1>

                <button
                type="button"
                class="report-reaper-close-btn"
                data-bs-dismiss="modal">

                    ×

                </button>

            </div>

            <div class="modal-body report-reaper-body">

                <hr>

                <div class="row">

                    <!-- Affiliate -->
                    <div class="col-md-6 mb-3">

                        <label>Affiliate</label>

                        <input
                        type="hidden"
                        id="generateReaperAffiliate"
                        value="all">

                        <div class="generate-reaper-dropdown">

                            <button
                            type="button"
                            id="generateReaperAffiliateBtn"
                            class="generate-reaper-dropdown-btn">

                                <span id="generateReaperAffiliateText">

                                    All Affiliates

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateReaperAffiliateMenu"
                            class="generate-reaper-dropdown-menu">

                                <div class="generate-reaper-dropdown-item" data-value="all">

                                    All Affiliates

                                </div>

                                <div class="generate-reaper-dropdown-item" data-value="Member">

                                    Member

                                </div>

                                <div class="generate-reaper-dropdown-item" data-value="Not Member">

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
                        id="generateReaperStatus"
                        value="">

                        <div class="generate-reaper-dropdown">

                            <button
                            type="button"
                            id="generateReaperStatusBtn"
                            class="generate-reaper-dropdown-btn">

                                <span id="generateReaperStatusText">

                                    Select Status

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateReaperStatusMenu"
                            class="generate-reaper-dropdown-menu">

                                <div
                                class="generate-reaper-dropdown-item"
                                data-value="all">

                                    All Status

                                </div>

                                <div
                                class="generate-reaper-dropdown-item"
                                data-value="Pending">

                                    Pending

                                </div>

                                <div
                                class="generate-reaper-dropdown-item"
                                data-value="Ongoing">

                                    Ongoing

                                </div>

                                <div
                                class="generate-reaper-dropdown-item"
                                data-value="Done">

                                    Done

                                </div>

                                <div
                                class="generate-reaper-dropdown-item"
                                data-value="Maintenance">

                                    Maintenance

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Remarks -->

                    <div class="col-md-6 mb-3">

                        <label>Remarks</label>

                        <input
                        type="hidden"
                        id="generateReaperRemarks"
                        value="all">

                        <div class="generate-reaper-dropdown">

                            <button
                            type="button"
                            id="generateReaperRemarksBtn"
                            class="generate-reaper-dropdown-btn"
                            aria-disabled="true"
                            disabled>

                                <span id="generateReaperRemarksText">

                                    Select Status First

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateReaperRemarksMenu"
                            class="generate-reaper-dropdown-menu">

                                <div class="generate-reaper-dropdown-item" data-value="all">

                                    All Remarks

                                </div>

                                <div class="generate-reaper-dropdown-item" data-value="Paid">

                                    Paid

                                </div>

                                <div class="generate-reaper-dropdown-item" data-value="Unpaid">

                                    Unpaid

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- Date -->

                    <div class="col-md-6 mb-3">

                        <label>Date Range</label>

                        <input
                        type="hidden"
                        id="generateReaperDateOption"
                        value="all">

                        <div class="generate-reaper-dropdown">

                            <button
                            type="button"
                            id="generateReaperDateBtn"
                            class="generate-reaper-dropdown-btn">

                                <span id="generateReaperDateText">

                                    All Transactions

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateReaperDateMenu"
                            class="generate-reaper-dropdown-menu">

                                <div class="generate-reaper-dropdown-item" data-value="all">

                                    All Transactions

                                </div>

                                <div class="generate-reaper-dropdown-item" data-value="custom">

                                    Custom Date

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div
                class="row"
                id="generateReaperCustomDateFields"
                style="display:none;">

                    <div class="col-md-6 mb-3">

                        <label>From</label>

                        <input
                        type="date"
                        id="generateReaperDateFrom"
                        class="report-reaper-input">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>To</label>

                        <input
                        type="date"
                        id="generateReaperDateTo"
                        class="report-reaper-input">

                    </div>

                </div>

                <hr>

                <div class="report-reaper-preview-container">

                    <button
                    id="previewReaperReport"
                    class="report-reaper-preview-btn">

                        PREVIEW

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>