<div class="modal fade"
id="generateHaulingReportModal">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content report-hauling-window">

            <div class="report-hauling-header">

                <h1>

                    GENERATE REPORT

                </h1>

                <button
                type="button"
                class="report-hauling-close-btn"
                data-bs-dismiss="modal">

                    ×

                </button>

            </div>

            <div class="modal-body report-hauling-body">

                <hr>

                <div class="row">

                    <!-- Affiliate -->

                    <div class="col-md-6 mb-3">

                        <label>

                            Affiliate

                        </label>

                        <input
                        type="hidden"
                        id="generateHaulingAffiliate"
                        value="all">

                        <div class="generate-hauling-dropdown">

                            <button
                            type="button"
                            id="generateHaulingAffiliateBtn"
                            class="generate-hauling-dropdown-btn">

                                <span id="generateHaulingAffiliateText">

                                    All Affiliates

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateHaulingAffiliateMenu"
                            class="generate-hauling-dropdown-menu">

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="all">

                                    All Affiliates

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="Member">

                                    Member

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
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
                        id="generateHaulingStatus"
                        value="all">

                        <div class="generate-hauling-dropdown">

                            <button
                            type="button"
                            id="generateHaulingStatusBtn"
                            class="generate-hauling-dropdown-btn">

                                <span id="generateHaulingStatusText">

                                    All Status

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateHaulingStatusMenu"
                            class="generate-hauling-dropdown-menu">

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="all">

                                    All Status

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="Pending">

                                    Pending

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="Ongoing">

                                    Ongoing

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="Done">

                                    Done

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
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
                        id="generateHaulingRemarks"
                        value="all">

                        <div class="generate-hauling-dropdown">

                            <button
                            type="button"
                            id="generateHaulingRemarksBtn"
                            class="generate-hauling-dropdown-btn">

                                <span id="generateHaulingRemarksText">

                                    All Remarks

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateHaulingRemarksMenu"
                            class="generate-hauling-dropdown-menu">

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="all">

                                    All Remarks

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="Paid">

                                    Paid

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
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
                        id="generateHaulingDateOption"
                        value="all">

                        <div class="generate-hauling-dropdown">

                            <button
                            type="button"
                            id="generateHaulingDateBtn"
                            class="generate-hauling-dropdown-btn">

                                <span id="generateHaulingDateText">

                                    All Transactions

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="generateHaulingDateMenu"
                            class="generate-hauling-dropdown-menu">

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="all">

                                    All Transactions

                                </div>

                                <div
                                class="generate-hauling-dropdown-item"
                                data-value="custom">

                                    Custom Date

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div
                class="row"
                id="generateHaulingCustomDateFields"
                style="display:none;">

                    <div class="col-md-6 mb-3">

                        <label>

                            From

                        </label>

                        <input
                        type="date"
                        id="generateHaulingDateFrom"
                        class="report-hauling-input">

                    </div>

                    <div class="col-md-6 mb-3">

                        <label>

                            To

                        </label>

                        <input
                        type="date"
                        id="generateHaulingDateTo"
                        class="report-hauling-input">

                    </div>

                </div>

                <div class="report-hauling-preview-container">

                    <button
                    id="previewHaulingReport"
                    class="report-hauling-preview-btn">

                        PREVIEW

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>