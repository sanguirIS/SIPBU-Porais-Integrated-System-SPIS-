document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const ledger =
        document.getElementById("monetaryLoanPageData");

    if (!ledger) {
        return;
    }

    const searchInput =
        document.getElementById("searchMonetaryLoan");

    const interestFilter =
        document.getElementById("monetaryLoanInterestFilter");

    const statusFilter =
        document.getElementById("monetaryLoanStatusFilter");

    const remarksFilter =
        document.getElementById("monetaryLoanRemarksFilter");

    const startDateInput =
        document.getElementById("monetaryLoanStartDateFilter");

    const endDateInput =
        document.getElementById("monetaryLoanEndDateFilter");

    const applyDateButton =
        document.getElementById("monetaryLoanApplyDateFilter");

    const clearDateButton =
        document.getElementById("monetaryLoanClearDateFilter");

    const dateFilterButton =
        document.querySelector(".monetary-loan-date-filter-btn");

    const dateFilterModal =
        document.getElementById("monetaryLoanDateFilterModal");

    const filteredEmptyState =
        document.getElementById("monetaryLoanFilteredEmpty");

    const serverDate =
        String(
            ledger.dataset.serverDate
            ||
            ""
        ).trim();


    if (serverDate) {

        if (startDateInput) {
            startDateInput.max =
                serverDate;
        }

        if (endDateInput) {
            endDateInput.max =
                serverDate;
        }

    }    


    function getLoanRows() {
        return Array.from(
            document.querySelectorAll(".monetary-loan-row")
        );
    }

    function restoreFocusedMonetaryLoanRow() {
        let focusedLoanID = "";

        try {
            focusedLoanID =
                sessionStorage.getItem(
                    "spisMonetaryLoanFocusedRow"
                ) || "";

            sessionStorage.removeItem(
                "spisMonetaryLoanFocusedRow"
            );
        } catch (error) {
            console.warn(
                "Unable to restore the Monetary Loan row.",
                error
            );

            return;
        }

        if (focusedLoanID === "") {
            return;
        }

        const focusedRow =
            getLoanRows().find(function (row) {
                return (
                    String(row.dataset.loanId) ===
                    focusedLoanID
                );
            });

        if (!focusedRow) {
            return;
        }

        window.requestAnimationFrame(
            function () {
                focusedRow.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                    inline: "nearest"
                });

                focusedRow.classList.add(
                    "monetary-loan-row-focused"
                );

                window.setTimeout(
                    function () {
                        focusedRow.classList.remove(
                            "monetary-loan-row-focused"
                        );
                    },
                    2200
                );
            }
        );
    }


    function normalizeText(value) {
        return String(value || "")
            .trim()
            .toLowerCase();
    }


    function showWarning(title, message) {
        if (typeof Swal !== "undefined") {
            return Swal.fire({
                icon: "warning",
                title: title,
                text: message,
                confirmButtonText: "OK",
                confirmButtonColor: "#91aa5b",
                allowOutsideClick: false
            });
        }

        window.alert(title + "\n\n" + message);

        return Promise.resolve();
    }


    function updateDateButtonState() {
        if (!dateFilterButton) {
            return;
        }

        const hasDateFilter = Boolean(
            startDateInput?.value ||
            endDateInput?.value
        );

        dateFilterButton.classList.toggle(
            "active",
            hasDateFilter
        );

        dateFilterButton.title = hasDateFilter
            ? "Application date filter is active"
            : "Filter application date";
    }


    function applyLedgerFilters() {
        const loanRows = getLoanRows();

        const searchValue =
            normalizeText(searchInput?.value);

        const interestValue =
            interestFilter?.value || "all";

        const statusValue =
            statusFilter?.value || "all";

        const remarksValue =
            remarksFilter?.value || "all";

        const startDate =
            startDateInput?.value || "";

        const endDate =
            endDateInput?.value || "";

        let visibleRows = 0;

        loanRows.forEach(function (row) {
            const rowSearch =
                normalizeText(row.dataset.search);

            const rowInterest =
                row.dataset.interest || "";

            const rowStatus =
                row.dataset.status || "";

            const rowRemarks =
                row.dataset.remarks || "";

            const rowDate =
                row.dataset.applicationDate || "";

            const matchesSearch =
                searchValue === "" ||
                rowSearch.includes(searchValue);

            const matchesInterest =
                interestValue === "all" ||
                rowInterest === interestValue;

            const matchesStatus =
                statusValue === "all" ||
                rowStatus === statusValue;

            const matchesRemarks =
                remarksValue === "all" ||
                rowRemarks === remarksValue;

            const matchesStartDate =
                startDate === "" ||
                (
                    rowDate !== "" &&
                    rowDate >= startDate
                );

            const matchesEndDate =
                endDate === "" ||
                (
                    rowDate !== "" &&
                    rowDate <= endDate
                );

            const shouldDisplay =
                matchesSearch &&
                matchesInterest &&
                matchesStatus &&
                matchesRemarks &&
                matchesStartDate &&
                matchesEndDate;

            row.style.display =
                shouldDisplay
                    ? "grid"
                    : "none";

            if (shouldDisplay) {
                visibleRows++;
            }
        });

        if (filteredEmptyState) {
            filteredEmptyState.style.display =
                loanRows.length > 0 &&
                visibleRows === 0
                    ? "block"
                    : "none";
        }

        updateDateButtonState();
    }


    if (searchInput) {
        searchInput.addEventListener(
            "input",
            applyLedgerFilters
        );
    }


    [
        interestFilter,
        statusFilter,
        remarksFilter
    ].forEach(function (filterElement) {
        if (!filterElement) {
            return;
        }

        filterElement.addEventListener(
            "change",
            applyLedgerFilters
        );
    });


    if (startDateInput) {
        startDateInput.addEventListener(
            "change",
            function () {
                if (!endDateInput) {
                    return;
                }

                if (startDateInput.value) {
                    endDateInput.min =
                        startDateInput.value;
                } else {
                    endDateInput.removeAttribute("min");
                }
            }
        );
    }


    if (endDateInput) {
        endDateInput.addEventListener(
            "change",
            function () {
                if (!startDateInput) {
                    return;
                }

                if (endDateInput.value) {

                    startDateInput.max =
                        (
                            serverDate &&
                            endDateInput.value > serverDate
                        )
                        ?
                        serverDate
                        :
                        endDateInput.value;

                } else if (serverDate) {

                    startDateInput.max =
                        serverDate;

                } else {

                    startDateInput.removeAttribute("max");

                }
            }
        );
    }


    if (applyDateButton) {
        applyDateButton.addEventListener(
            "click",
            function (event) {
                const startDate =
                    startDateInput?.value || "";

                const endDate =
                    endDateInput?.value || "";


                if (
                    serverDate !== "" &&
                    (
                        (
                            startDate !== "" &&
                            startDate > serverDate
                        )
                        ||
                        (
                            endDate !== "" &&
                            endDate > serverDate
                        )
                    )
                ) {
                    event.preventDefault();
                    event.stopPropagation();

                    showWarning(
                        "Invalid date",
                        "Future dates are not allowed."
                    );

                    return;
                }


                if (
                    startDate !== "" &&
                    endDate !== "" &&
                    startDate > endDate
                ) {
                    event.preventDefault();
                    event.stopPropagation();

                    showWarning(
                        "Invalid date range",
                        "The start date cannot be later than the end date."
                    );

                    return;
                }

                applyLedgerFilters();
            }
        );
    }


    if (clearDateButton) {
        clearDateButton.addEventListener(
            "click",
            function () {
                if (startDateInput) {

                    startDateInput.value =
                        "";

                    if (serverDate) {

                        startDateInput.max =
                            serverDate;

                    } else {

                        startDateInput.removeAttribute(
                            "max"
                        );

                    }
                }


                if (endDateInput) {

                    endDateInput.value =
                        "";

                    endDateInput.removeAttribute(
                        "min"
                    );

                    if (serverDate) {

                        endDateInput.max =
                            serverDate;

                    } else {

                        endDateInput.removeAttribute(
                            "max"
                        );

                    }
                }

                applyLedgerFilters();
            }
        );
    }


    if (dateFilterModal) {
        dateFilterModal.addEventListener(
            "hidden.bs.modal",
            function () {
                window.setTimeout(
                    function () {
                        const openModal =
                            document.querySelector(
                                ".modal.show"
                            );

                        if (openModal) {
                            return;
                        }

                        document.body.classList.remove(
                            "modal-open"
                        );

                        document.body.style.removeProperty(
                            "overflow"
                        );

                        document.body.style.removeProperty(
                            "padding-right"
                        );

                        document
                            .querySelectorAll(
                                ".modal-backdrop"
                            )
                            .forEach(function (backdrop) {
                                backdrop.remove();
                            });
                    },
                    50
                );
            }
        );
    }


    applyLedgerFilters();

    restoreFocusedMonetaryLoanRow();
    
});