document.addEventListener(
    "DOMContentLoaded",
    function () {

        "use strict";


        /* =================================================
           ELEMENTS
        ================================================= */

        const modalElement =
            document.getElementById(
                "generateSalesReportModal"
            );


        const previewButton =
            document.getElementById(
                "previewSalesReport"
            );


        const customerTypeInput =
            document.getElementById(
                "generateSalesType"
            );


        const dateOptionInput =
            document.getElementById(
                "generateSalesDateOption"
            );


        const customDateFields =
            document.getElementById(
                "generateSalesCustomDateFields"
            );


        const dateFromInput =
            document.getElementById(
                "generateSalesDateFrom"
            );


        const dateToInput =
            document.getElementById(
                "generateSalesDateTo"
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

            if (
                typeof Swal !==
                "undefined"
            ) {

                return Swal.fire({

                    icon:
                        icon,

                    title:
                        title,

                    text:
                        message,

                    confirmButtonText:
                        "OK",

                    confirmButtonColor:
                        "#8ca15b",

                    allowOutsideClick:
                        false

                });

            }


            window.alert(
                title +
                "\n\n" +
                message
            );


            return Promise.resolve();

        }


        /* =================================================
           CLOSE ALL CUSTOM DROPDOWNS
        ================================================= */

        function closeAllDropdowns(
            exceptMenu = null
        ) {

            document
                .querySelectorAll(
                    ".generate-sales-dropdown-menu"
                )
                .forEach(
                    function (menu) {

                        if (
                            menu ===
                            exceptMenu
                        ) {

                            return;

                        }


                        menu.classList.remove(
                            "show"
                        );


                        const dropdown =
                            menu.closest(
                                ".generate-sales-dropdown"
                            );


                        const button =
                            dropdown
                                ?.querySelector(
                                    ".generate-sales-dropdown-btn"
                                );


                        button
                            ?.setAttribute(
                                "aria-expanded",
                                "false"
                            );

                    }
                );

        }


        /* =================================================
           CUSTOM DATE VISIBILITY
        ================================================= */

        function updateCustomDateVisibility() {

            const customSelected =
                dateOptionInput
                    ?.value ===
                "custom";


            if (
                customDateFields
            ) {

                customDateFields.hidden =
                    !customSelected;

            }


            /*
            If user returns to All Transactions,
            clear unused custom dates.
            */

            if (
                !customSelected
            ) {

                if (
                    dateFromInput
                ) {

                    dateFromInput.value =
                        "";

                }


                if (
                    dateToInput
                ) {

                    dateToInput.value =
                        "";

                }

            }

            updateCustomDateRestrictions();

        }


        /* =================================================
           CUSTOM DROPDOWN SETUP
        ================================================= */

        function setupDropdown(
            buttonID,
            menuID,
            textID,
            inputID
        ) {

            const button =
                document.getElementById(
                    buttonID
                );


            const menu =
                document.getElementById(
                    menuID
                );


            const text =
                document.getElementById(
                    textID
                );


            const input =
                document.getElementById(
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


            /* =============================================
               OPEN / CLOSE
            ============================================= */

            button.addEventListener(
                "click",
                function (event) {

                    event.stopPropagation();


                    const willOpen =
                        !menu
                            .classList
                            .contains(
                                "show"
                            );


                    closeAllDropdowns(
                        menu
                    );


                    menu
                        .classList
                        .toggle(
                            "show",
                            willOpen
                        );


                    button.setAttribute(
                        "aria-expanded",
                        willOpen
                            ?
                            "true"
                            :
                            "false"
                    );

                }
            );


            /* =============================================
               SELECT OPTION
            ============================================= */

            menu
                .querySelectorAll(
                    ".generate-sales-dropdown-item"
                )
                .forEach(
                    function (item) {

                        item.addEventListener(
                            "click",
                            function (event) {

                                event.stopPropagation();


                                input.value =
                                    this.dataset.value
                                    ||
                                    "all";


                                text.textContent =
                                    this.textContent
                                        .trim();


                                menu
                                    .classList
                                    .remove(
                                        "show"
                                    );


                                button.setAttribute(
                                    "aria-expanded",
                                    "false"
                                );


                                if (
                                    inputID ===
                                    "generateSalesDateOption"
                                ) {

                                    updateCustomDateVisibility();

                                }

                            }
                        );

                    }
                );

        }


        /* =================================================
           SETUP DROPDOWNS
        ================================================= */

        setupDropdown(

            "generateSalesTypeBtn",

            "generateSalesTypeMenu",

            "generateSalesTypeText",

            "generateSalesType"

        );


        setupDropdown(

            "generateSalesDateBtn",

            "generateSalesDateMenu",

            "generateSalesDateText",

            "generateSalesDateOption"

        );


        /* =================================================
           SERVER DATE
        ================================================= */

        const serverDate =
            String(
                modalElement
                    .dataset
                    .serverDate
                ||
                ""
            );


        if (
            serverDate
        ) {

            if (
                dateFromInput
            ) {

                dateFromInput.max =
                    serverDate;

            }


            if (
                dateToInput
            ) {

                dateToInput.max =
                    serverDate;

            }

        }


        function updateCustomDateRestrictions() {

            if (dateFromInput) {
                const selectedTo = dateToInput?.value || "";

                dateFromInput.max =
                    selectedTo && (!serverDate || selectedTo < serverDate)
                        ? selectedTo
                        : serverDate;
            }

            if (dateToInput) {
                dateToInput.min = dateFromInput?.value || "";
                dateToInput.max = serverDate;
            }
        }

        dateFromInput?.addEventListener("change", updateCustomDateRestrictions);
        dateToInput?.addEventListener("change", updateCustomDateRestrictions);

        updateCustomDateVisibility();


        /* =================================================
           CLICK OUTSIDE DROPDOWN
        ================================================= */

        document.addEventListener(
            "click",
            function () {

                closeAllDropdowns();

            }
        );


        /* =================================================
           MODAL CLOSED
        ================================================= */

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
            async function () {


                /* =========================================
                   CUSTOMER TYPE
                ========================================== */

                const customerType =
                    customerTypeInput
                        ?.value
                    ||
                    "all";


                /* =========================================
                   DATE OPTION
                ========================================== */

                const dateOption =
                    dateOptionInput
                        ?.value
                    ||
                    "all";


                let dateFrom =
                    dateFromInput
                        ?.value
                    ||
                    "";


                let dateTo =
                    dateToInput
                        ?.value
                    ||
                    "";


                /* =========================================
                   VALID CUSTOMER TYPE
                ========================================== */

                const allowedCustomerTypes = [

                    "all",

                    "member",

                    "not member"

                ];


                if (
                    !allowedCustomerTypes
                        .includes(
                            customerType
                        )
                ) {

                    await showReportAlert(

                        "warning",

                        "Invalid customer type",

                        "Please select a valid customer type."

                    );


                    return;

                }


                /* =========================================
                   CUSTOM DATE VALIDATION
                ========================================== */

                if (
                    dateOption ===
                    "custom"
                ) {

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


                    if (
                        dateFrom >
                        dateTo
                    ) {

                        await showReportAlert(

                            "warning",

                            "Invalid date range",

                            "The From date must not be later than the To date."

                        );


                        return;

                    }


                    if (
                        serverDate
                        &&
                        (
                            dateFrom >
                            serverDate
                            ||
                            dateTo >
                            serverDate
                        )
                    ) {

                        await showReportAlert(

                            "warning",

                            "Invalid date range",

                            "Transaction dates cannot be later than the current Asia/Manila server date."

                        );


                        return;

                    }

                }


                /*
                All Transactions means no date
                restriction is sent to the report.
                */

                else {

                    dateFrom =
                        "";

                    dateTo =
                        "";

                }


                /* =========================================
                   BUILD REPORT URL
                ========================================== */

                const parameters =
                    new URLSearchParams({

                        search:
                            "",

                        affiliate:
                            customerType,

                        start:
                            dateFrom,

                        end:
                            dateTo

                    });


                const reportURL =
                    "export_sales.php?" +
                    parameters.toString();


                /* =========================================
                   OPEN PREVIEW
                ========================================== */

                const reportWindow =
                    window.open(
                        reportURL,
                        "_blank"
                    );


                if (
                    !reportWindow
                ) {

                    await showReportAlert(

                        "warning",

                        "Report window blocked",

                        "Please allow pop-ups for this local system, then try again."

                    );


                    return;

                }


                reportWindow.opener =
                    null;

            }
        );

    }
);