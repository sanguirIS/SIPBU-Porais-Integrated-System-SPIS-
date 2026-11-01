<div
class="modal fade"
id="generateSalesReportModal"
tabindex="-1"
aria-labelledby="generateSalesReportTitle"
aria-hidden="true"
data-server-date="<?php echo date("Y-m-d"); ?>">


    <div
    class="modal-dialog modal-lg modal-dialog-centered">


        <div
        class="modal-content report-sales-window">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="report-sales-header">


                <h1 id="generateSalesReportTitle">

                    GENERATE REPORT

                </h1>


                <button
                type="button"
                class="report-sales-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close report options">

                    &times;

                </button>


            </div>


            <!-- =================================================
                 BODY
            ================================================== -->

            <div class="modal-body report-sales-body">


                <hr>


                <div class="row">


                    <!-- =========================================
                         CUSTOMER TYPE
                    ========================================== -->

                    <div class="col-md-6 mb-3">


                        <label for="generateSalesTypeBtn">

                            Customer Type

                        </label>


                        <input
                        type="hidden"
                        id="generateSalesType"
                        value="all">


                        <div class="generate-sales-dropdown">


                            <button
                            type="button"
                            id="generateSalesTypeBtn"
                            class="generate-sales-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">


                                <span id="generateSalesTypeText">

                                    All Customer Types

                                </span>


                                <i class="bi bi-chevron-down"></i>


                            </button>


                            <div
                            id="generateSalesTypeMenu"
                            class="generate-sales-dropdown-menu"
                            role="listbox">


                                <button
                                type="button"
                                class="generate-sales-dropdown-item"
                                data-value="all">

                                    All Customer Types

                                </button>


                                <button
                                type="button"
                                class="generate-sales-dropdown-item"
                                data-value="member">

                                    Member

                                </button>


                                <button
                                type="button"
                                class="generate-sales-dropdown-item"
                                data-value="not member">

                                    Not Member

                                </button>


                            </div>


                        </div>


                    </div>


                    <!-- =========================================
                         TRANSACTION DATE RANGE
                    ========================================== -->

                    <div class="col-md-6 mb-3">


                        <label for="generateSalesDateBtn">

                            Transaction Date Range

                        </label>


                        <input
                        type="hidden"
                        id="generateSalesDateOption"
                        value="all">


                        <div class="generate-sales-dropdown">


                            <button
                            type="button"
                            id="generateSalesDateBtn"
                            class="generate-sales-dropdown-btn"
                            aria-haspopup="listbox"
                            aria-expanded="false">


                                <span id="generateSalesDateText">

                                    All Transactions

                                </span>


                                <i class="bi bi-chevron-down"></i>


                            </button>


                            <div
                            id="generateSalesDateMenu"
                            class="generate-sales-dropdown-menu"
                            role="listbox">


                                <button
                                type="button"
                                class="generate-sales-dropdown-item"
                                data-value="all">

                                    All Transactions

                                </button>


                                <button
                                type="button"
                                class="generate-sales-dropdown-item"
                                data-value="custom">

                                    Custom Date

                                </button>


                            </div>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                     CUSTOM DATE FIELDS
                ================================================== -->

                <div
                class="row"
                id="generateSalesCustomDateFields"
                hidden>


                    <!-- FROM -->

                    <div class="col-md-6 mb-3">


                        <label for="generateSalesDateFrom">

                            From

                        </label>


                        <input
                        type="date"
                        id="generateSalesDateFrom"
                        class="report-sales-input"
                        max="<?php echo date("Y-m-d"); ?>">


                    </div>


                    <!-- TO -->

                    <div class="col-md-6 mb-3">


                        <label for="generateSalesDateTo">

                            To

                        </label>


                        <input
                        type="date"
                        id="generateSalesDateTo"
                        class="report-sales-input"
                        max="<?php echo date("Y-m-d"); ?>">


                    </div>


                </div>


                <hr>


                <!-- =================================================
                     PREVIEW
                ================================================== -->

                <div class="report-sales-preview-container">


                    <button
                    type="button"
                    id="previewSalesReport"
                    class="report-sales-preview-btn">

                        PREVIEW

                    </button>


                </div>


            </div>


        </div>


    </div>


</div>