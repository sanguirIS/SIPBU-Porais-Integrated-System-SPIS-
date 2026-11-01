document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    /* =====================================================
       ELEMENTS AND PAGE STATE
    ===================================================== */

    const searchInput =
        document.getElementById("searchProductLoan");

    const interestFilter =
        document.getElementById("productLoanInterestFilter");

    const statusFilter =
        document.getElementById("productLoanStatusFilter");

    const remarksFilter =
        document.getElementById("productLoanRemarksFilter");

    const startDateInput =
        document.getElementById("productLoanStartDateFilter");

    const endDateInput =
        document.getElementById("productLoanEndDateFilter");

    const applyDateButton =
        document.getElementById("productLoanApplyDateFilter");

    const clearDateButton =
        document.getElementById("productLoanClearDateFilter");

    const dateFilterButton =
        document.querySelector(".product-loan-date-filter-btn");

    const dateFilterModal =
        document.getElementById("productLoanDateFilterModal");

    const filteredEmptyState =
        document.getElementById("productLoanFilteredEmpty");

    const pageData =
        document.getElementById("productLoanPageData");

    let loanRows =
        Array.from(
            document.querySelectorAll(".product-loan-row")
        );

    let focusedProductLoanID = "";

    try {
        focusedProductLoanID =
            sessionStorage.getItem(
                "spisProductLoanFocusedRow"
            ) || "";

        sessionStorage.removeItem(
            "spisProductLoanFocusedRow"
        );
    } catch (error) {
        console.warn(
            "Unable to restore the Product Loan row.",
            error
        );
    }

    if (focusedProductLoanID !== "") {
        const focusedRow =
            loanRows.find(function (row) {
                return (
                    String(row.dataset.loanId) ===
                    focusedProductLoanID
                );
            });

        if (focusedRow) {
            window.requestAnimationFrame(
                function () {
                    focusedRow.scrollIntoView({
                        behavior: "smooth",
                        block: "center",
                        inline: "nearest"
                    });

                    focusedRow.classList.add(
                        "product-loan-row-focused"
                    );

                    window.setTimeout(
                        function () {
                            focusedRow.classList.remove(
                                "product-loan-row-focused"
                            );
                        },
                        2200
                    );
                }
            );
        }
    }    

    const hadInitialLoanRows =
        loanRows.length > 0;

    const lendingPermission =
        String(
            pageData?.dataset.lendingPermission ||
            "limited"
        )
            .trim()
            .toLowerCase();

    const csrfToken =
    String(
        pageData?.dataset
            .csrfToken || ""
    );        


    /* =====================================================
       HELPERS
    ===================================================== */

    function normalizeText(value) {
        return String(value || "")
            .trim()
            .toLowerCase();
    }


    function refreshLoanRows() {
        loanRows =
            Array.from(
                document.querySelectorAll(
                    ".product-loan-row"
                )
            );
    }


    function showAlert(
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


    function updateDateButtonState() {
        if (!dateFilterButton) {
            return;
        }

        const hasDateFilter =
            Boolean(
                startDateInput?.value ||
                endDateInput?.value
            );

        dateFilterButton.classList.toggle(
            "active",
            hasDateFilter
        );

        dateFilterButton.title =
            hasDateFilter
                ? "Application date filter is active"
                : "Filter application date";
    }


    /* =====================================================
       LEDGER FILTERS
    ===================================================== */

    function applyLedgerFilters() {
        refreshLoanRows();

        if (loanRows.length === 0) {
            if (filteredEmptyState) {
                filteredEmptyState.textContent =
                    "No Product Loan records found.";

                filteredEmptyState.style.display =
                    hadInitialLoanRows
                        ? "block"
                        : "none";
            }

            updateDateButtonState();
            return;
        }

        const searchValue =
            normalizeText(
                searchInput?.value
            );

        const interestValue =
            interestFilter?.value ||
            "all";

        const statusValue =
            statusFilter?.value ||
            "all";

        const remarksValue =
            remarksFilter?.value ||
            "all";

        const startDate =
            startDateInput?.value ||
            "";

        const endDate =
            endDateInput?.value ||
            "";

        let visibleRows = 0;

        loanRows.forEach(function (row) {
            const rowSearch =
                normalizeText(
                    row.dataset.search
                );

            const rowInterest =
                row.dataset.interest ||
                "";

            const rowStatus =
                row.dataset.status ||
                "";

            const rowRemarks =
                row.dataset.remarks ||
                "";

            const rowDate =
                row.dataset.applicationDate ||
                "";

            const matchesSearch =
                searchValue === "" ||
                rowSearch.includes(
                    searchValue
                );

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
            filteredEmptyState.textContent =
                "No Product Loan records match the selected filters.";

            filteredEmptyState.style.display =
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
    ].forEach(function (filter) {
        if (filter) {
            filter.addEventListener(
                "change",
                applyLedgerFilters
            );
        }
    });


    /* =====================================================
       DATE FILTER
    ===================================================== */

    const productLoanServerDate =
        String(pageData?.dataset.serverDate || "");

    function updateProductLoanDateRestrictions() {
        if (!startDateInput || !endDateInput) {
            return;
        }

        const selectedEnd = endDateInput.value || "";

        startDateInput.max =
            selectedEnd &&
            (!productLoanServerDate || selectedEnd < productLoanServerDate)
                ? selectedEnd
                : productLoanServerDate;

        endDateInput.min = startDateInput.value || "";
        endDateInput.max = productLoanServerDate;
    }

    if (applyDateButton) {
        applyDateButton.addEventListener(
            "click",
            function (event) {
                const startDate =
                    startDateInput?.value ||
                    "";

                const endDate =
                    endDateInput?.value ||
                    "";

                if (
                    startDate !== "" &&
                    endDate !== "" &&
                    startDate > endDate
                ) {
                    event.preventDefault();
                    event.stopPropagation();

                    showAlert(
                        "warning",
                        "Invalid date range",
                        "The start date cannot be later than the end date."
                    );

                    return;
                }

                if (
                    productLoanServerDate &&
                    (
                        startDate > productLoanServerDate ||
                        endDate > productLoanServerDate
                    )
                ) {
                    event.preventDefault();
                    event.stopPropagation();

                    showAlert(
                        "warning",
                        "Invalid date range",
                        "Application dates cannot be later than the current Asia/Manila server date."
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
                    startDateInput.value = "";
                }

                if (endDateInput) {
                    endDateInput.value = "";
                }

                updateProductLoanDateRestrictions();
                applyLedgerFilters();
            }
        );
    }


    startDateInput?.addEventListener(
        "change",
        updateProductLoanDateRestrictions
    );

    endDateInput?.addEventListener(
        "change",
        updateProductLoanDateRestrictions
    );

    updateProductLoanDateRestrictions();


    /* =====================================================
       DATE MODAL BACKDROP CLEANUP
    ===================================================== */

    if (dateFilterModal) {
        dateFilterModal.addEventListener(
            "hidden.bs.modal",
            function () {
                window.setTimeout(
                    function () {
                        if (
                            document.querySelector(
                                ".modal.show"
                            )
                        ) {
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
                            .forEach(
                                function (backdrop) {
                                    backdrop.remove();
                                }
                            );
                    },
                    50
                );
            }
        );
    }


    /* =====================================================
       ARCHIVE PRODUCT LOAN
    ===================================================== */

    const archiveButtons =
        Array.from(
            document.querySelectorAll(
                ".product-loan-archive-btn"
            )
        );

    archiveButtons.forEach(
        function (archiveButton) {
            if (
                lendingPermission !==
                "full"
            ) {
                archiveButton.disabled = true;

                archiveButton.title =
                    "Full Lending Access is required to archive a Product Loan";

                return;
            }

            if (archiveButton.disabled) {
                return;
            }

            archiveButton.addEventListener(
                "click",
                async function () {
                    if (
                        archiveButton.dataset.archiving ===
                        "true"
                    ) {
                        return;
                    }

                    const loanID =
                        Number(
                            archiveButton.dataset.loanId
                        );

                    const loanRow =
                        archiveButton.closest(
                            ".product-loan-row"
                        );

                    if (
                        !Number.isInteger(loanID) ||
                        loanID <= 0 ||
                        !loanRow
                    ) {
                        await showAlert(
                            "error",
                            "Unable to archive",
                            "The selected Product Loan is invalid."
                        );

                        return;
                    }

                    const loanDisplayID =
                        loanRow.querySelector(
                            ".product-loan-id-cell strong"
                        )?.textContent.trim() ||
                        "this Product Loan";

                    const memberName =
                        loanRow.querySelector(
                            ".product-loan-member-cell strong"
                        )?.textContent.trim() ||
                        "the selected member";

                    let confirmed = true;

                    if (
                        typeof Swal !==
                        "undefined"
                    ) {
                        const confirmation =
                            await Swal.fire({
                                icon: "warning",
                                title:
                                    "Archive this Product Loan?",
                                text:
                                    loanDisplayID +
                                    " for " +
                                    memberName +
                                    " will be moved to the Lending Archive. " +
                                    "Its payments, reversals, attachments, and audit records will be preserved.",
                                showCancelButton: true,
                                confirmButtonText:
                                    "YES, ARCHIVE",
                                cancelButtonText:
                                    "CANCEL",

                                customClass: {
                                    popup:"product-loan-archive-popup"
                                },

                                confirmButtonColor:
                                    "#D85C5C",
                                cancelButtonColor:
                                    "#777777",
                                reverseButtons: true,
                                focusCancel: true,
                                allowOutsideClick: false
                            });

                        confirmed =
                            confirmation.isConfirmed;
                    } else {
                        confirmed =
                            window.confirm(
                                "Archive " +
                                loanDisplayID +
                                " for " +
                                memberName +
                                "?"
                            );
                    }

                    if (!confirmed) {
                        return;
                    }

                    archiveButton.dataset.archiving =
                        "true";

                    archiveButton.disabled = true;

                    let archiveSucceeded = false;

                    try {
                        const formData =
                            new FormData();

                        formData.append(
                            "loan_id",
                            String(loanID)
                        );

                        formData.append(
                            "csrf_token",
                            csrfToken
                        );

                        const response =
                            await fetch(
                                "archive_product_loan.php",
                                {
                                    method: "POST",
                                    body: formData,
                                    headers: {
                                        "X-Requested-With":
                                            "XMLHttpRequest"
                                    },
                                    cache: "no-store"
                                }
                            );

                        const responseText =
                            await response.text();

                        let result;

                        try {
                            result =
                                JSON.parse(
                                    responseText
                                );
                        } catch (jsonError) {
                            console.error(
                                "Invalid archive response:",
                                responseText
                            );

                            throw new Error(
                                "The server returned an invalid response."
                            );
                        }

                        if (
                            !response.ok ||
                            !result.success
                        ) {
                            throw new Error(
                                result.message ||
                                "Unable to archive the Product Loan."
                            );
                        }

                        archiveSucceeded = true;

                        await showAlert(
                            "success",
                            "Product Loan archived",
                            result.message ||
                            loanDisplayID +
                            " was moved to the Lending Archive."
                        );

                        loanRow.remove();

                        applyLedgerFilters();
                    } catch (error) {
                        console.error(
                            "Archive Product Loan error:",
                            error
                        );

                        await showAlert(
                            "error",
                            "Unable to archive",
                            error.message ||
                            "Something went wrong while archiving the Product Loan."
                        );
                    } finally {
                        archiveButton.dataset.archiving =
                            "false";

                        if (
                            !archiveSucceeded &&
                            archiveButton.isConnected
                        ) {
                            archiveButton.disabled =
                                false;
                        }
                    }
                }
            );
        }
    );


    /* =====================================================
       INITIAL DISPLAY
    ===================================================== */

    applyLedgerFilters();
});