document.addEventListener(
    "DOMContentLoaded",
    function () {

        "use strict";


        /* =================================================
           ELEMENTS
        ================================================= */

        const modalElement = document.getElementById(
            "generateProductLoanReportModal"
        );

        const previewButton = document.getElementById(
            "previewProductLoanReport"
        );

        const interestInput = document.getElementById(
            "generateProductLoanInterest"
        );

        const statusInput = document.getElementById(
            "generateProductLoanStatus"
        );

        const statusButton = document.getElementById(
            "generateProductLoanStatusBtn"
        );

        const remarksInput = document.getElementById(
            "generateProductLoanRemarks"
        );

        const remarksButton = document.getElementById(
            "generateProductLoanRemarksBtn"
        );

        const remarksText = document.getElementById(
            "generateProductLoanRemarksText"
        );

        const remarksMenu = document.getElementById(
            "generateProductLoanRemarksMenu"
        );

        const dateOptionInput = document.getElementById(
            "generateProductLoanDateOption"
        );

        const customDateFields = document.getElementById(
            "generateProductLoanCustomDateFields"
        );

        const dateFromInput = document.getElementById(
            "generateProductLoanDateFrom"
        );

        const dateToInput = document.getElementById(
            "generateProductLoanDateTo"
        );

        if (
            !modalElement ||
            !previewButton
        ) {
            return;
        }


        /* =================================================
           ALERT
        ================================================= */

        function showReportAlert(
            icon,
            title,
            message
        ) {
            if (typeof Swal !== "undefined") {
                return Swal.fire({
                    icon: icon,
                    title: title,
                    text: message,
                    confirmButtonText: "OK",
                    confirmButtonColor: "#8ca15b",
                    allowOutsideClick: false
                });
            }

            window.alert(
                title + "\n\n" + message
            );

            return Promise.resolve();
        }


        /* =================================================
           DROPDOWN HELPERS
        ================================================= */

        function closeAllDropdowns(
            exceptMenu = null
        ) {
            document
                .querySelectorAll(
                    ".generate-product-loan-dropdown-menu"
                )
                .forEach(
                    function (menu) {
                        if (menu === exceptMenu) {
                            return;
                        }

                        menu.classList.remove(
                            "show"
                        );

                        const wrapper = menu.closest(
                            ".generate-product-loan-dropdown"
                        );

                        const button = wrapper
                            ? wrapper.querySelector(
                                ".generate-product-loan-dropdown-btn"
                            )
                            : null;

                        if (button) {
                            button.setAttribute(
                                "aria-expanded",
                                "false"
                            );
                        }
                    }
                );
        }


        function setupDropdown(
            buttonID,
            menuID,
            textID,
            inputID,
            onSelect = null
        ) {
            const button = document.getElementById(
                buttonID
            );

            const menu = document.getElementById(
                menuID
            );

            const text = document.getElementById(
                textID
            );

            const input = document.getElementById(
                inputID
            );

            if (
                !button ||
                !menu ||
                !text ||
                !input
            ) {
                return;
            }

            button.addEventListener(
                "click",
                function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (button.disabled) {
                        return;
                    }

                    const willOpen =
                        !menu.classList.contains(
                            "show"
                        );

                    closeAllDropdowns(
                        menu
                    );

                    menu.classList.toggle(
                        "show",
                        willOpen
                    );

                    button.setAttribute(
                        "aria-expanded",
                        willOpen
                            ? "true"
                            : "false"
                    );
                }
            );

            menu
                .querySelectorAll(
                    ".generate-product-loan-dropdown-item"
                )
                .forEach(
                    function (item) {
                        item.addEventListener(
                            "click",
                            function (event) {
                                event.preventDefault();
                                event.stopPropagation();

                                const value =
                                    this.dataset.value !== undefined
                                        ? this.dataset.value
                                        : "all";

                                input.value = value;

                                text.textContent =
                                    this.textContent.trim();

                                menu.classList.remove(
                                    "show"
                                );

                                button.setAttribute(
                                    "aria-expanded",
                                    "false"
                                );

                                if (
                                    inputID ===
                                    "generateProductLoanDateOption"
                                ) {
                                    updateCustomDateVisibility();
                                }

                                if (
                                    typeof onSelect ===
                                    "function"
                                ) {
                                    onSelect(value);
                                }
                            }
                        );
                    }
                );
        }


        /* =================================================
           DATE RANGE
        ================================================= */

        function updateCustomDateRestrictions() {
            if (dateFromInput) {
                const selectedTo = dateToInput
                    ? dateToInput.value
                    : "";

                dateFromInput.min = "";

                dateFromInput.max =
                    selectedTo &&
                    (!serverDate || selectedTo <= serverDate)
                        ? selectedTo
                        : serverDate;
            }

            if (dateToInput) {
                dateToInput.min = dateFromInput
                    ? dateFromInput.value
                    : "";

                dateToInput.max = serverDate;
            }
        }


        function updateCustomDateVisibility() {
            if (!customDateFields) {
                return;
            }

            const isCustom =
                dateOptionInput &&
                dateOptionInput.value === "custom";

            customDateFields.hidden = !isCustom;

            if (!isCustom) {
                if (dateFromInput) {
                    dateFromInput.value = "";
                }

                if (dateToInput) {
                    dateToInput.value = "";
                }
            }

            updateCustomDateRestrictions();
        }


        /* =================================================
           STATUS -> REMARKS RULE
        ================================================= */

        function setRemarksState(
            enabled,
            label
        ) {
            if (remarksInput) {
                remarksInput.value = "all";
            }

            if (remarksText) {
                remarksText.textContent = label;
            }

            if (remarksButton) {
                remarksButton.disabled = !enabled;

                remarksButton.setAttribute(
                    "aria-disabled",
                    enabled
                        ? "false"
                        : "true"
                );

                remarksButton.setAttribute(
                    "aria-expanded",
                    "false"
                );
            }

            if (remarksMenu) {
                remarksMenu.classList.remove(
                    "show"
                );
            }
        }


        function updateRemarksAvailability() {
            const status = statusInput
                ? String(
                    statusInput.value || ""
                )
                : "";

            if (!status) {
                setRemarksState(
                    false,
                    "Select Status First"
                );

                return;
            }

            if (
                status === "Pending" ||
                status === "Rejected"
            ) {
                setRemarksState(
                    false,
                    "Not Applicable"
                );

                return;
            }

            setRemarksState(
                true,
                "All Remarks"
            );
        }


        /* =================================================
           INITIALIZE
        ================================================= */

        setupDropdown(
            "generateProductLoanInterestBtn",
            "generateProductLoanInterestMenu",
            "generateProductLoanInterestText",
            "generateProductLoanInterest"
        );

        setupDropdown(
            "generateProductLoanStatusBtn",
            "generateProductLoanStatusMenu",
            "generateProductLoanStatusText",
            "generateProductLoanStatus",
            updateRemarksAvailability
        );

        setupDropdown(
            "generateProductLoanRemarksBtn",
            "generateProductLoanRemarksMenu",
            "generateProductLoanRemarksText",
            "generateProductLoanRemarks"
        );

        setupDropdown(
            "generateProductLoanDateBtn",
            "generateProductLoanDateMenu",
            "generateProductLoanDateText",
            "generateProductLoanDateOption"
        );


        /* =================================================
           SERVER DATE
        ================================================= */

        const serverDate = String(
            modalElement.dataset.serverDate || ""
        );

        if (dateFromInput) {
            dateFromInput.addEventListener(
                "change",
                updateCustomDateRestrictions
            );
        }

        if (dateToInput) {
            dateToInput.addEventListener(
                "change",
                updateCustomDateRestrictions
            );
        }

        updateCustomDateVisibility();

        updateRemarksAvailability();


        document.addEventListener(
            "click",
            function () {
                closeAllDropdowns();
            }
        );

        modalElement.addEventListener(
            "hidden.bs.modal",
            function () {
                closeAllDropdowns();
            }
        );


        /* =================================================
           PREVIEW REPORT
        ================================================= */

        previewButton.addEventListener(
            "click",
            async function (event) {

                event.preventDefault();


                const interest = interestInput
                    ? interestInput.value || "all"
                    : "all";


                const status = statusInput
                    ? statusInput.value || ""
                    : "";


                const remarks = remarksInput
                    ? remarksInput.value || "all"
                    : "all";


                const dateOption = dateOptionInput
                    ? dateOptionInput.value || "all"
                    : "all";


                const dateFrom = dateFromInput
                    ? dateFromInput.value || ""
                    : "";


                const dateTo = dateToInput
                    ? dateToInput.value || ""
                    : "";


                /* =========================================
                   STATUS REQUIRED
                ========================================= */

                if (!status) {

                    await showReportAlert(
                        "warning",
                        "Select a status",
                        "Please select a status before previewing the report."
                    );

                    if (statusButton) {
                        statusButton.focus();
                    }

                    return;
                }


                /* =========================================
                   INVALID STATUS + REMARKS COMBINATION
                ========================================= */

                if (
                    (
                        status === "Pending" ||
                        status === "Rejected"
                    ) &&
                    remarks !== "all"
                ) {

                    updateRemarksAvailability();

                    await showReportAlert(
                        "warning",
                        "Invalid remarks filter",
                        "Remarks are not applicable to Pending or Rejected Product Loans."
                    );

                    return;
                }


                /* =========================================
                   CUSTOM DATE VALIDATION
                ========================================= */

                if (dateOption === "custom") {

                    if (
                        !dateFrom ||
                        !dateTo
                    ) {

                        await showReportAlert(
                            "warning",
                            "Incomplete date range",
                            "Please select both From and To dates."
                        );

                        return;
                    }


                    if (dateFrom > dateTo) {

                        await showReportAlert(
                            "warning",
                            "Invalid date range",
                            "The From date must not be later than the To date."
                        );

                        return;
                    }


                    if (
                        serverDate &&
                        (
                            dateFrom > serverDate ||
                            dateTo > serverDate
                        )
                    ) {

                        await showReportAlert(
                            "warning",
                            "Invalid date range",
                            "Application dates cannot be later than the current Asia/Manila server date."
                        );

                        return;
                    }
                }


                /* =========================================
                   REPORT PARAMETERS
                ========================================= */

                const parameters =
                    new URLSearchParams({
                        interest: interest,
                        status: status,
                        remarks: remarks,
                        date_option: dateOption,
                        from: dateFrom,
                        to: dateTo
                    });


                const reportURL =
                    "generate_product_loan/" +
                    "report_product_loan.php?" +
                    parameters.toString();


                /* =========================================
                   OPEN PREVIEW
                ========================================= */

                const reportWindow =
                    window.open(
                        reportURL,
                        "_blank"
                    );


                if (!reportWindow) {

                    await showReportAlert(
                        "warning",
                        "Report window blocked",
                        "Please allow pop-ups for this local system, then try again."
                    );

                    return;
                }


                try {

                    reportWindow.opener =
                        null;

                } catch (error) {

                    /* No action needed. */

                }
            }
        );

    }
);