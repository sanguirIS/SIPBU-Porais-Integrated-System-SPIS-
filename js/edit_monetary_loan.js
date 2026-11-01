document.addEventListener("DOMContentLoaded", function () {
    "use strict";


    /* =========================================================
       ELEMENTS
    ========================================================= */

    const modalElement =
        document.getElementById("editMonetaryLoanModal");

    const editForm =
        document.getElementById("editMonetaryLoanForm");

    if (!modalElement || !editForm) {
        return;
    }

    const modalInstance =
        window.bootstrap
            ? bootstrap.Modal.getOrCreateInstance(modalElement)
            : null;

    const csrfTokenInput =
        document.getElementById("editMonetaryLoanCSRFToken");

    const loanIDInput =
        document.getElementById("editMonetaryLoanID");

    const memberIDInput =
        document.getElementById("editMonetaryLoanMemberID");

    const originalMemberIDInput =
        document.getElementById("editMonetaryLoanOriginalMemberID");

    const existingStatusInput =
        document.getElementById("editMonetaryLoanExistingStatus");

    const modalTitle =
        document.getElementById("editMonetaryLoanModalTitle");

    const statusBadge =
        document.getElementById("editMonetaryLoanStatusBadge");

    const informationText =
        document.getElementById("editMonetaryLoanInformationText");

    const displayIDInput =
        document.getElementById("editMonetaryLoanDisplayID");

    const applicationDateInput =
        document.getElementById("editMonetaryLoanApplicationDate");

    const releaseDateInput =
        document.getElementById("editMonetaryLoanReleaseDate");

    const dueDateInput =
        document.getElementById("editMonetaryLoanDueDate");

    const approvedByInput =
        document.getElementById("editMonetaryLoanApprovedBy");

    const borrowerNote =
        document.getElementById("editMonetaryLoanBorrowerNote");

    const memberNameInput =
        document.getElementById("editMonetaryLoanMemberName");

    const memberResults =
        document.getElementById("editMonetaryLoanMemberResults");

    const memberError =
        document.getElementById("editMonetaryLoanMemberError");

    const termInput =
        document.getElementById("editMonetaryLoanTerm");

    const termError =
        document.getElementById("editMonetaryLoanTermError");

    const statusInput =
        document.getElementById("editMonetaryLoanStatus");

    const statusPermission =
        document.getElementById("editMonetaryLoanStatusPermission");

    const purposeInput =
        document.getElementById("editMonetaryLoanPurpose");

    const purposeError =
        document.getElementById("editMonetaryLoanPurposeError");

    const loanYearsField =
        document.getElementById("editMonetaryLoanYearsField");

    const loanYearsInput =
        document.getElementById("editMonetaryLoanYears");

    const loanYearsError =
        document.getElementById("editMonetaryLoanYearsError");

    const principalInput =
        document.getElementById("editMonetaryLoanPrincipalAmount");

    const principalError =
        document.getElementById("editMonetaryLoanPrincipalError");

    const rejectionBlock =
        document.getElementById("editMonetaryLoanRejectionBlock");

    const rejectionReasonInput =
        document.getElementById("editMonetaryLoanRejectionReason");

    const rejectionError =
        document.getElementById("editMonetaryLoanRejectionError");


    /* =========================================================
       ATTACHMENTS
    ========================================================= */

    const attachmentInput =
        document.getElementById("editMonetaryLoanAttachments");

    const uploadBox =
        document.getElementById("editMonetaryLoanUploadBox");

    const attachmentHelp =
        document.getElementById("editMonetaryLoanAttachmentHelp");

    const attachmentList =
        document.getElementById("editMonetaryLoanAttachmentList");


    /* =========================================================
       FINANCIAL SUMMARY
    ========================================================= */

    const summaryPrincipal =
        document.getElementById("editMonetaryLoanSummaryPrincipal");

    const summaryInterestRate =
        document.getElementById("editMonetaryLoanSummaryInterestRate");

    const summaryTerm =
        document.getElementById("editMonetaryLoanSummaryTerm");

    const summaryScheduledInterest =
        document.getElementById("editMonetaryLoanSummaryScheduledInterest");

    const summaryScheduledTotal =
        document.getElementById("editMonetaryLoanSummaryScheduledTotal");

    const summaryDailyPayment =
        document.getElementById("editMonetaryLoanSummaryDailyPayment");

    const summaryMonthlyPayment =
        document.getElementById("editMonetaryLoanSummaryMonthlyPayment");

    const summaryPayments =

        document.getElementById("editMonetaryLoanSummaryPayments");

    const summaryAccruedInterest =
        document.getElementById("editMonetaryLoanSummaryAccruedInterest");

    const summaryBalance =
        document.getElementById("editMonetaryLoanSummaryBalance");


    /* =========================================================
       PAYMENT ELEMENTS
    ========================================================= */

    const paymentSection =
        document.getElementById("editMonetaryLoanPaymentSection");

    const paymentLongTermYearsItem =
        document.getElementById("editMonetaryPaymentLongTermYearsItem");

    const paymentLongTermYears =
        document.getElementById("editMonetaryPaymentLongTermYears");

    const paymentLongTermPlanItem =
        document.getElementById("editMonetaryPaymentLongTermPlanItem");

    const paymentLongTermPlan =
        document.getElementById("editMonetaryPaymentLongTermPlan");

    const paymentPrincipal =
        document.getElementById("editMonetaryPaymentPrincipal");

    const paymentUnpaidInterest =
        document.getElementById("editMonetaryPaymentUnpaidInterest");

    const paymentElapsedDays =
        document.getElementById("editMonetaryPaymentElapsedDays");

    const paymentNewInterest =
        document.getElementById("editMonetaryPaymentNewInterest");

    const paymentPayoffBalance =
        document.getElementById("editMonetaryPaymentPayoffBalance");

    const paymentDateInput =
        document.getElementById("editMonetaryLoanPaymentDate");

    const paymentAmountInput =
        document.getElementById("editMonetaryLoanPaymentAmount");

    const paymentNotesInput =
        document.getElementById("editMonetaryLoanPaymentNotes");

    const recordPaymentButton =
        document.getElementById("recordMonetaryLoanPaymentBtn");

    const paymentHistoryBody =
        document.getElementById("editMonetaryLoanPaymentHistory");

    const saveChangesButton =
        document.getElementById("saveMonetaryLoanChangesBtn");


    /* =========================================================
       STATE
    ========================================================= */

    let currentLoanData = null;

    function reloadAtCurrentMonetaryLoan() {
        const currentLoanID =
            Number(
                currentLoanData?.loan
                    ?.monetary_loan_id
            );

        if (
            Number.isInteger(currentLoanID) &&
            currentLoanID > 0
        ) {
            try {
                sessionStorage.setItem(
                    "spisMonetaryLoanFocusedRow",
                    String(currentLoanID)
                );
            } catch (error) {
                console.warn(
                    "Unable to remember the Monetary Loan row.",
                    error
                );
            }
        }

        window.location.reload();
    }

    let existingAttachments = [];

    let newAttachments = [];

    const removedAttachmentIDs = new Set();

    let memberSearchTimer = null;

    let memberSearchController = null;

    let isSubmitting = false;

    const MAX_NEW_ATTACHMENTS = 10;

    const MAX_FILE_SIZE =
        10 * 1024 * 1024;

    const MAX_TOTAL_FILE_SIZE =
        50 * 1024 * 1024;

    const ALLOWED_MONETARY_LOAN_TYPES = [
        "Crop Production Loan",
        "Dairy Farmers Loan",
        "Emergency Loan",
        "Long-Term Loan"
    ];

    const ALLOWED_EXTENSIONS = [
        "jpg",
        "jpeg",
        "png",
        "pdf",
        "doc",
        "docx"
    ];


    /* =========================================================
       BASIC HELPERS
    ========================================================= */

    function escapeHTML(value) {
        return String(value ?? "")
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }


    function formatMoney(value) {
        const amount =
            Number.parseFloat(value);

        if (!Number.isFinite(amount)) {
            return "₱0.00";
        }

        return new Intl.NumberFormat(
            "en-PH",
            {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(amount);
    }


    function formatFileSize(bytes) {
        const size =
            Number(bytes) || 0;

        if (size < 1024) {
            return size + " B";
        }

        if (size < 1024 * 1024) {
            return (
                size / 1024
            ).toFixed(1) + " KB";
        }

        return (
            size / (1024 * 1024)
        ).toFixed(1) + " MB";
    }


    function normalizeStatus(status) {
        const value =
            String(status || "")
                .trim()
                .toLowerCase();

        if (value === "accepted") {
            return "Accepted";
        }

        if (value === "rejected") {
            return "Rejected";
        }

        return "Pending";
    }


    function getFileExtension(fileName) {
        const parts =
            String(fileName || "")
                .toLowerCase()
                .split(".");

        return parts.length > 1
            ? parts.pop()
            : "";
    }


    function getCSRFToken() {
        if (csrfTokenInput?.value) {
            return csrfTokenInput.value;
        }

        const pageData =
            document.getElementById("monetaryLoanPageData");

        return pageData?.dataset.csrfToken || "";
    }


    function showFieldError(element, message) {
        if (!element) {
            return;
        }

        element.textContent = message || "";
        element.hidden = !message;
    }


    function clearFieldErrors() {
        showFieldError(memberError, "");
        showFieldError(termError, "");
        showFieldError(purposeError, "");
        showFieldError(loanYearsError, "");
        showFieldError(principalError, "");
        showFieldError(rejectionError, "");
    }


    function showError(title, message) {
        return Swal.fire({
            icon: "error",
            title: title,
            text: message,
            confirmButtonText: "OK",
            confirmButtonColor: "#91aa5b",
            customClass: {
                popup: "monetary-loan-alert-popup"
            }
        });
    }


    function showSuccess(title, message) {
        return Swal.fire({
            icon: "success",
            title: title,
            text: message,
            confirmButtonText: "OK",
            confirmButtonColor: "#91aa5b",
            customClass: {
                popup: "monetary-loan-alert-popup"
            }
        });
    }


    async function requestJSON(url, options = {}) {
        const response =
            await fetch(
                url,
                {
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        ...(options.headers || {})
                    },
                    ...options
                }
            );

        const responseText =
            await response.text();

        let data;

        try {
            data =
                JSON.parse(responseText);
        } catch (error) {
            throw new Error(
                "The server returned an invalid response."
            );
        }

        if (!response.ok) {
            throw new Error(
                data.message ||
                "The request could not be completed."
            );
        }

        return data;
    }


    function closeMemberResults() {
        if (!memberResults) {
            return;
        }

        memberResults.innerHTML = "";
        memberResults.hidden = true;
    }


    /* =========================================================
       RESET MODAL
    ========================================================= */

    function resetModal() {
        currentLoanData = null;

        existingAttachments = [];
        newAttachments = [];
        removedAttachmentIDs.clear();

        isSubmitting = false;

        editForm.reset();

        if (memberNameInput) {
            memberNameInput.dataset.activeLoanTypes =
                "";
        }

        if (loanYearsInput) {
            loanYearsInput.value = "";
            loanYearsInput.disabled = true;
            loanYearsInput.required = false;
        }

        if (loanYearsField) {
            loanYearsField.hidden = true;
        }

        clearFieldErrors();
        closeMemberResults();

        if (loanIDInput) {
            loanIDInput.value = "";
        }

        if (memberIDInput) {
            memberIDInput.value = "";
        }

        if (originalMemberIDInput) {
            originalMemberIDInput.value = "";
        }

        if (existingStatusInput) {
            existingStatusInput.value = "";
        }

        if (attachmentInput) {
            attachmentInput.value = "";
            attachmentInput.disabled = true;
        }

        if (attachmentList) {
            attachmentList.innerHTML = "";
        }

        if (paymentHistoryBody) {
            paymentHistoryBody.innerHTML = "";
        }

        if (paymentSection) {
            paymentSection.hidden = true;
        }

        if (paymentLongTermYearsItem) {
            paymentLongTermYearsItem.hidden = true;
        }

        if (paymentLongTermYears) {
            paymentLongTermYears.textContent = "—";
        }

        if (paymentLongTermPlanItem) {
            paymentLongTermPlanItem.hidden = true;
        }

        if (paymentLongTermPlan) {
            paymentLongTermPlan.textContent = "—";
        }

        if (rejectionBlock) {
            rejectionBlock.hidden = true;
        }

        if (saveChangesButton) {
            saveChangesButton.hidden = true;
            saveChangesButton.disabled = true;
        }

        if (recordPaymentButton) {
            recordPaymentButton.disabled = true;
        }

        showMonetaryPaymentEstimates(null, 0);
        updateStatusBadge("Pending");

    }


    /* =========================================================
       STATUS BADGE
    ========================================================= */

    function updateStatusBadge(status) {
        if (!statusBadge) {
            return;
        }

        const normalizedStatus =
            normalizeStatus(status);

        statusBadge.classList.remove(
            "monetary-status-pending",
            "monetary-status-accepted",
            "monetary-status-rejected"
        );

        if (normalizedStatus === "Accepted") {
            statusBadge.classList.add(
                "monetary-status-accepted"
            );

            statusBadge.innerHTML =
                '<i class="bi bi-check-circle"></i> ACCEPTED';

            return;
        }

        if (normalizedStatus === "Rejected") {
            statusBadge.classList.add(
                "monetary-status-rejected"
            );

            statusBadge.innerHTML =
                '<i class="bi bi-x-circle"></i> REJECTED';

            return;
        }

        statusBadge.classList.add(
            "monetary-status-pending"
        );

        statusBadge.innerHTML =
            '<i class="bi bi-clock"></i> PENDING';
    }


    /* =========================================================
       TERM AND FINANCIAL CALCULATION
    ========================================================= */

    function getTermCalculation() {
        const termDays =
            Number.parseInt(
                termInput?.value || "0",
                10
            );

        if (termDays === 180) {
            return {
                termDays: 180,
                interestRate: 10
            };
        }

        if (termDays === 360) {
            return {
                termDays: 360,
                interestRate: 20
            };
        }

        return {
            termDays: 0,
            interestRate: 0
        };
    }


    function longTermLoanIsSelected() {
        return (
            String(
                purposeInput?.value || ""
            ).trim() === "Long-Term Loan"
        );
    }


    function getLoanYears() {
        const years =
            Number(
                loanYearsInput?.value || 0
            );

        return (
            Number.isInteger(years) &&
            years > 0
        )
            ? years
            : 0;
    }


    function getLongTermPaymentPeriods() {
        if (!longTermLoanIsSelected()) {
            return 0;
        }

        const years =
            getLoanYears();

        const term =
            getTermCalculation();

        if (
            years <= 0 ||
            ![180, 360].includes(
                term.termDays
            )
        ) {
            return 0;
        }

        return (
            years *
            (
                term.termDays === 180
                    ? 2
                    : 1
            )
        );
    }


    function updateLoanYearsField() {
        if (
            !loanYearsField ||
            !loanYearsInput
        ) {
            return;
        }

        const isLongTerm =
            longTermLoanIsSelected();

        const originalStatus =
            normalizeStatus(
                existingStatusInput?.value
            );

        const canEditPending =
            originalStatus === "Pending" &&
            Boolean(
                currentLoanData?.permission
                    ?.can_edit_pending_details
            );

        loanYearsField.hidden =
            !isLongTerm;

        loanYearsInput.disabled =
            !isLongTerm ||
            !canEditPending;

        loanYearsInput.required =
            isLongTerm &&
            canEditPending;

        if (!isLongTerm) {
            loanYearsInput.value = "";

            showFieldError(
                loanYearsError,
                ""
            );
        }
    }

   
function showMonetaryPaymentEstimates(totalAmount, remainingDays) {
    const amount = Number(totalAmount);
    const days = Number(remainingDays);

    const valid =
        Number.isFinite(amount) &&
        amount >= 0 &&
        Number.isFinite(days) &&
        days > 0;

    const dailyText = valid
        ? formatMoney(amount / days)
        : "—";

    const monthlyText = valid
        ? formatMoney(amount / Math.max(1, days / 30))
        : "—";

    if (summaryDailyPayment) {
        summaryDailyPayment.textContent = dailyText;
    }

    if (summaryMonthlyPayment) {
        summaryMonthlyPayment.textContent = monthlyText;
    }
}


function remainingMonetaryLoanDays(today, dueDate) {
    const datePattern = /^\d{4}-\d{2}-\d{2}$/;

    if (
        !datePattern.test(String(today || "")) ||
        !datePattern.test(String(dueDate || ""))
    ) {
        return 0;
    }

    const start = Date.parse(today + "T00:00:00Z");
    const end = Date.parse(dueDate + "T00:00:00Z");

    return Number.isFinite(start) && Number.isFinite(end)
        ? Math.max(0, Math.round((end - start) / 86400000))
        : 0;
}
 

function updatePendingSummary() {
    if (!currentLoanData) {
        return;
    }

    const currentStatus =
        normalizeStatus(
            existingStatusInput?.value
        );

    if (currentStatus !== "Pending") {
        return;
    }

    const principal =
        Number.parseFloat(
            principalInput?.value || "0"
        ) || 0;

    const term =
        getTermCalculation();

    const loanYears =
        getLoanYears();

    const paymentPeriods =
        getLongTermPaymentPeriods();

    let scheduledInterest =
        principal *
        (
            term.interestRate / 100
        );

    if (
        longTermLoanIsSelected() &&
        paymentPeriods > 0
    ) {
        scheduledInterest =
            principal *
            (
                term.interestRate / 100
            ) *
            (
                (paymentPeriods + 1) / 2
            );
    }

    const scheduledTotal =
        principal +
        scheduledInterest;

    if (summaryPrincipal) {
        summaryPrincipal.textContent =
            formatMoney(principal);
    }

    if (summaryInterestRate) {
        summaryInterestRate.textContent =
            term.interestRate > 0
                ? term.interestRate + "%"
                : "—";
    }

    if (summaryTerm) {
        if (
            longTermLoanIsSelected() &&
            loanYears > 0 &&
            paymentPeriods > 0
        ) {
            summaryTerm.textContent =
                loanYears +
                (
                    loanYears === 1
                        ? " Year"
                        : " Years"
                ) +
                " · " +
                paymentPeriods +
                (
                    paymentPeriods === 1
                        ? " Payment"
                        : " Payments"
                ) +
                " · Every " +
                term.termDays +
                " Days";
        } else {
            summaryTerm.textContent =
                term.termDays > 0
                    ? term.termDays + " Days"
                    : "—";
        }
    }

    if (summaryScheduledInterest) {
        summaryScheduledInterest.textContent =
            formatMoney(scheduledInterest);
    }

    if (summaryScheduledTotal) {
        summaryScheduledTotal.textContent =
            formatMoney(scheduledTotal);
    }

    const estimatedDays = longTermLoanIsSelected()
        ? (paymentPeriods > 0 ? term.termDays * paymentPeriods : 0)
        : term.termDays;

    showMonetaryPaymentEstimates(scheduledTotal, estimatedDays);

    if (summaryPayments) {

        summaryPayments.textContent =
            formatMoney(0);
    }

    if (summaryAccruedInterest) {
        summaryAccruedInterest.textContent =
            formatMoney(0);
    }

    if (summaryBalance) {
        summaryBalance.textContent = "—";
    }
}


    function populateFinancialSummary(loan) {
        if (summaryPrincipal) {
            summaryPrincipal.textContent =
                formatMoney(
                    loan.principal_amount
                );
        }

        if (summaryInterestRate) {
            summaryInterestRate.textContent =
                Number(loan.interest_rate || 0) +
                "%";
        }

        if (summaryTerm) {
            const loanYears =
                Number(
                    loan.loan_term_years || 0
                );

            const paymentPeriods =
                Number(
                    loan.payment_periods || 0
                );

            if (
                String(
                    loan.loan_purpose || ""
                ).trim() ===
                    "Long-Term Loan" &&
                loanYears > 0 &&
                paymentPeriods > 0
            ) {
                summaryTerm.textContent =
                    loanYears +
                    (
                        loanYears === 1
                            ? " Year"
                            : " Years"
                    ) +
                    " · " +
                    paymentPeriods +
                    (
                        paymentPeriods === 1
                            ? " Payment"
                            : " Payments"
                    ) +
                    " · Every " +
                    Number(
                        loan.term_days || 0
                    ) +
                    " Days";
            } else {
                summaryTerm.textContent =
                    Number(
                        loan.term_days || 0
                    ) +
                    " Days";
            }
        }

        if (summaryScheduledInterest) {
            summaryScheduledInterest.textContent =
                formatMoney(
                    loan.scheduled_interest_amount
                );
        }

    if (summaryScheduledTotal) {
        summaryScheduledTotal.textContent =
            formatMoney(
                loan.scheduled_total_amount
            );
    }

    const loanStatus = normalizeStatus(loan.status);
    const termDays = Number(loan.term_days || 0);
    const paymentPeriods = Number(loan.payment_periods || 0);

    if (loanStatus === "Pending" || loanStatus === "Accepted") {
        const plannedDays = loan.loan_purpose === "Long-Term Loan"
            ? termDays * paymentPeriods
            : termDays;

        showMonetaryPaymentEstimates(
            Number(loan.scheduled_total_amount),
            plannedDays
        );
    } else {
        showMonetaryPaymentEstimates(null, 0);
    }

    if (summaryPayments) {

            summaryPayments.textContent =
                formatMoney(
                    loan.net_payments_amount
                );
        }

        if (summaryAccruedInterest) {
            summaryAccruedInterest.textContent =
                formatMoney(
                    loan.new_accrued_interest_amount
                );
        }

        if (summaryBalance) {
            const status =
                normalizeStatus(loan.status);

            summaryBalance.textContent =
                status === "Pending"
                    ? "—"
                    : formatMoney(
                        loan.current_balance
                    );
        }
    }


    /* =========================================================
    RELEASE AND DUE DATE PREVIEW
    ========================================================= */

    function getAcceptanceDueDateDisplay() {
        const serverDate =
            String(
                currentLoanData?.server
                    ?.date || ""
            ).trim();

        const match =
            serverDate.match(
                /^(\d{4})-(\d{2})-(\d{2})$/
            );

        if (!match) {
            return "";
        }

        const term =
            getTermCalculation();

        if (
            term.termDays !== 180 &&
            term.termDays !== 360
        ) {
            return "";
        }

        let totalDays =
            term.termDays;

        if (longTermLoanIsSelected()) {
            const paymentPeriods =
                getLongTermPaymentPeriods();

            if (paymentPeriods <= 0) {
                return "";
            }

            totalDays *=
                paymentPeriods;
        }

        const dueDate =
            new Date(
                Date.UTC(
                    Number(match[1]),
                    Number(match[2]) - 1,
                    Number(match[3])
                )
            );

        dueDate.setUTCDate(
            dueDate.getUTCDate() +
            totalDays
        );

        const month =
            String(
                dueDate.getUTCMonth() + 1
            ).padStart(2, "0");

        const day =
            String(
                dueDate.getUTCDate()
            ).padStart(2, "0");

        return (
            month +
            "-" +
            day +
            "-" +
            dueDate.getUTCFullYear()
        );
    }


    function updateAcceptancePreview() {
        if (!currentLoanData) {
            return;
        }

        const originalStatus =
            normalizeStatus(
                existingStatusInput?.value
            );

        const selectedStatus =
            normalizeStatus(
                statusInput?.value
            );

        if (
            originalStatus === "Pending" &&
            selectedStatus === "Accepted"
        ) {
            if (releaseDateInput) {
                releaseDateInput.value =
                    currentLoanData.server
                        ?.acceptance_date_preview_display ||
                    currentLoanData.server
                        ?.date_display ||
                    "Generated upon acceptance";
            }

            if (dueDateInput) {
                const computedDueDate =
                    getAcceptanceDueDateDisplay();

                dueDateInput.value =
                    computedDueDate ||
                    (
                        longTermLoanIsSelected()
                            ? "Complete loan term details"
                            : currentLoanData.server
                                ?.due_date_preview_display ||
                            "Generated upon acceptance"
                    );
            }

            return;
        }

        const loan =
            currentLoanData.loan;

        if (releaseDateInput) {
            releaseDateInput.value =
                loan.release_date_display ||
                "Not accepted";
        }

        if (dueDateInput) {
            dueDateInput.value =
                loan.due_date_display ||
                "Generated upon acceptance";
        }
    }


    /* =========================================================
       PERMISSIONS AND FIELD LOCKING
    ========================================================= */

    function setEditableState() {
        if (!currentLoanData) {
            return;
        }

        const loan =
            currentLoanData.loan;

        const permission =
            currentLoanData.permission || {};

        const originalStatus =
            normalizeStatus(loan.status);

        const isPending =
            originalStatus === "Pending";

        const canEditPending =
            isPending &&
            Boolean(
                permission.can_edit_pending_details
            );

        const canChangeStatus =
            isPending &&
            Boolean(
                permission.can_change_status
            );

        if (memberNameInput) {
            memberNameInput.readOnly =
                !canEditPending;
        }

        if (termInput) {
            termInput.disabled =
                !canEditPending;
        }

        if (purposeInput) {
            purposeInput.disabled =
                !canEditPending;
        }

        if (principalInput) {
            principalInput.readOnly =
                !canEditPending;
        }

        updateLoanYearsField();

        if (statusInput) {
            statusInput.disabled =
                !canChangeStatus;
        }

        if (attachmentInput) {
            attachmentInput.disabled =
                !canEditPending;
        }

        if (uploadBox) {
            uploadBox.classList.toggle(
                "monetary-loan-upload-locked",
                !canEditPending
            );

            uploadBox.setAttribute(
                "aria-disabled",
                String(!canEditPending)
            );
        }

        if (attachmentHelp) {
            attachmentHelp.textContent =
                canEditPending
                    ? "JPG, PNG, PDF, DOC, or DOCX · Maximum of 10 new files · 10 MB each · 50 MB total"
                    : "Attachments can no longer be changed after acceptance or rejection.";
        }

        if (borrowerNote) {
            borrowerNote.textContent =
                canEditPending
                    ? "Pending loan information remains editable until the loan is accepted or rejected."
                    : "Saved borrower and loan information is now read-only.";
        }

        if (informationText) {
            informationText.textContent =
                isPending
                    ? "Review and update this pending Monetary Loan application."
                    : "View the saved Monetary Loan information and transaction history.";
        }

        if (statusPermission) {
            if (!isPending) {
                statusPermission.textContent =
                    "The status is final and can no longer be changed.";
            } else if (canChangeStatus) {
                statusPermission.textContent =
                    "Full Lending Access: you may mark this application as Accepted or Rejected.";
            } else {
                statusPermission.textContent =
                    "Limited Lending Access: this application is locked to Pending.";
            }
        }

        if (saveChangesButton) {
            const canSave =
                isPending &&
                (
                    canEditPending ||
                    canChangeStatus
                );

            saveChangesButton.hidden =
                !canSave;

            saveChangesButton.disabled =
                !canSave;
        }

        paymentSection.hidden =
            originalStatus !== "Accepted";

        renderAttachments();
    }


    /* =========================================================
       REJECTION SECTION
    ========================================================= */

    function updateRejectionSection() {
        const selectedStatus =
            normalizeStatus(
                statusInput?.value
            );

        const originalStatus =
            normalizeStatus(
                existingStatusInput?.value
            );

        const showRejection =
            selectedStatus === "Rejected" ||
            originalStatus === "Rejected";

        if (rejectionBlock) {
            rejectionBlock.hidden =
                !showRejection;
        }

        if (rejectionReasonInput) {
            rejectionReasonInput.readOnly =
                originalStatus !== "Pending";
        }

        if (!showRejection) {
            showFieldError(
                rejectionError,
                ""
            );
        }
    }


    /* =========================================================
       MEMBER SEARCH
    ========================================================= */

    async function searchMembers(searchText) {
        if (!memberResults) {
            return;
        }

        const keyword =
            String(searchText || "")
                .trim();

        if (keyword.length < 3) {
            closeMemberResults();
            return;
        }

        if (memberSearchController) {
            memberSearchController.abort();
        }

        memberSearchController =
            new AbortController();

        try {
            const data =
                await requestJSON(
                    "search_monetary_loan_members.php?query=" +
                    encodeURIComponent(keyword) +
                    "&exclude_loan_id=" +
                    encodeURIComponent(
                        loanIDInput?.value || "0"
                    ),
                    {
                        signal:
                            memberSearchController.signal
                    }
                );

            const members =
                Array.isArray(data)
                    ? data
                    : (
                        data.members ||
                        data.results ||
                        data.data ||
                        []
                    );

            renderMemberResults(members);
        } catch (error) {
            if (error.name === "AbortError") {
                return;
            }

            memberResults.innerHTML =
                '<div class="monetary-loan-member-empty">' +
                escapeHTML(error.message) +
                "</div>";

            memberResults.hidden = false;
        }
    }


    function renderMemberResults(members) {
        if (!memberResults) {
            return;
        }

        if (!Array.isArray(members) || members.length === 0) {
            memberResults.innerHTML =
                '<div class="monetary-loan-member-empty">' +
                "No eligible Regular member found." +
                "</div>";

            memberResults.hidden = false;
            return;
        }

        memberResults.innerHTML =
            members.map(function (member) {
                const memberID =
                    member.member_id ??
                    member.id ??
                    "";

                const memberCode =
                    member.member_code ??
                    member.member_id_display ??
                    member.code ??
                    "";

                const memberName =
                    member.full_name ??
                    member.name ??
                    "";

                const activeLoanTypes =
                    Array.isArray(
                        member.active_loan_types
                    )
                        ? member.active_loan_types
                            .map(function (loanType) {
                                return String(
                                    loanType || ""
                                ).trim();
                            })
                            .filter(function (loanType) {
                                return loanType !== "";
                            })
                        : [];

                return (
                    '<button type="button" ' +
                    'class="monetary-loan-member-result" ' +
                    'data-member-id="' +
                    escapeHTML(memberID) +
                    '" data-member-name="' +
                    escapeHTML(memberName) +
                    '" data-active-loan-types="' +
                    escapeHTML(
                        activeLoanTypes.join("||")
                    ) +
                    '">' +
                    "<strong>" +
                    escapeHTML(memberName) +
                    "</strong>" +
                    "<small>" +
                    escapeHTML(memberCode) +
                    (
                        activeLoanTypes.length > 0
                            ? " • Active: " +
                            escapeHTML(
                                activeLoanTypes.join(", ")
                            )
                            : ""
                    ) +
                    "</small>" +
                    "</button>"
                );
            }).join("");

        memberResults.hidden = false;
    }


    memberNameInput?.addEventListener(
        "input",
        function () {
            if (
                memberNameInput.readOnly ||
                !currentLoanData
            ) {
                return;
            }

            if (memberIDInput) {
                memberIDInput.value = "";
            }

            showFieldError(memberError, "");

            window.clearTimeout(
                memberSearchTimer
            );

            memberSearchTimer =
                window.setTimeout(
                    function () {
                        searchMembers(
                            memberNameInput.value
                        );
                    },
                    350
                );
        }
    );


    memberResults?.addEventListener(
        "click",
        async function (event) {
            const resultButton =
                event.target.closest(
                    ".monetary-loan-member-result"
                );

            if (!resultButton) {
                return;
            }

            const activeLoanTypes =
                String(
                    resultButton.dataset.activeLoanTypes ||
                    ""
                )
                    .split("||")
                    .map(function (loanType) {
                        return String(
                            loanType || ""
                        ).trim();
                    })
                    .filter(function (loanType) {
                        return loanType !== "";
                    });

            const selectedLoanType =
                String(
                    purposeInput?.value || ""
                ).trim();

            if (
                selectedLoanType !== "" &&
                activeLoanTypes.includes(
                    selectedLoanType
                )
            ) {
                await showError(
                    "Existing " + selectedLoanType,
                    String(
                        resultButton.dataset.memberName ||
                        "This member"
                    ) +
                    " still has another active " +
                    selectedLoanType +
                    ". The existing " +
                    selectedLoanType +
                    " must be fully paid before another one can be created."
                );

                return;
            }

            if (memberIDInput) {
                memberIDInput.value =
                    resultButton.dataset.memberId || "";
            }

            if (memberNameInput) {
                memberNameInput.value =
                    resultButton.dataset.memberName || "";

                memberNameInput.dataset.activeLoanTypes =
                    activeLoanTypes.join("||");
            }

            showFieldError(memberError, "");
            closeMemberResults();
        }
    );


    document.addEventListener(
        "click",
        function (event) {
            if (
                memberResults &&
                !memberResults.contains(event.target) &&
                event.target !== memberNameInput
            ) {
                closeMemberResults();
            }
        }
    );


    /* =========================================================
       ATTACHMENT VALIDATION
    ========================================================= */

    function validateAttachment(file) {
        const extension =
            getFileExtension(file.name);

        if (
            !ALLOWED_EXTENSIONS.includes(
                extension
            )
        ) {
            return (
                file.name +
                " is not an allowed attachment."
            );
        }

        if (file.size > MAX_FILE_SIZE) {
            return (
                file.name +
                " exceeds the 10 MB file limit."
            );
        }

        const duplicateExisting =
            existingAttachments.some(
                function (attachment) {
                    return (
                        !removedAttachmentIDs.has(
                            Number(
                                attachment.attachment_id
                            )
                        ) &&
                        String(
                            attachment.original_file_name
                        ).toLowerCase() ===
                        file.name.toLowerCase()
                    );
                }
            );

        const duplicateNew =
            newAttachments.some(
                function (newFile) {
                    return (
                        newFile.name.toLowerCase() ===
                        file.name.toLowerCase()
                    );
                }
            );

        if (
            duplicateExisting ||
            duplicateNew
        ) {
            return (
                "This file is already attached: " +
                file.name
            );
        }

        return "";
    }


    async function addSelectedAttachments(files) {
        const selectedFiles =
            Array.from(files || []);

        if (selectedFiles.length === 0) {
            return;
        }

        if (
            newAttachments.length +
            selectedFiles.length >
            MAX_NEW_ATTACHMENTS
        ) {
            await showError(
                "Too many attachments",
                "You may add a maximum of 10 new attachments at a time."
            );

            return;
        }

        let totalNewSize =
            newAttachments.reduce(
                function (total, file) {
                    return total + file.size;
                },
                0
            );

        for (const file of selectedFiles) {
            const validationMessage =
                validateAttachment(file);

            if (validationMessage) {
                await showError(
                    "Invalid attachment",
                    validationMessage
                );

                continue;
            }

            if (
                totalNewSize + file.size >
                MAX_TOTAL_FILE_SIZE
            ) {
                await showError(
                    "Attachment limit exceeded",
                    "The combined size of new attachments cannot exceed 50 MB."
                );

                break;
            }

            newAttachments.push(file);
            totalNewSize += file.size;
        }

        if (attachmentInput) {
            attachmentInput.value = "";
        }

        renderAttachments();
    }


    function getAttachmentIcon(fileName) {
        const extension =
            getFileExtension(fileName);

        if (
            extension === "jpg" ||
            extension === "jpeg" ||
            extension === "png"
        ) {
            return "bi-file-earmark-image";
        }

        if (extension === "pdf") {
            return "bi-file-earmark-pdf";
        }

        return "bi-file-earmark-word";
    }


    function renderAttachments() {
        if (!attachmentList) {
            return;
        }

        const canEdit =
            normalizeStatus(
                existingStatusInput?.value
            ) === "Pending" &&
            Boolean(
                currentLoanData?.permission
                    ?.can_edit_pending_details
            );

        const activeExisting =
            existingAttachments.filter(
                function (attachment) {
                    return !removedAttachmentIDs.has(
                        Number(
                            attachment.attachment_id
                        )
                    );
                }
            );

        if (
            activeExisting.length === 0 &&
            newAttachments.length === 0
        ) {
            attachmentList.innerHTML =
                '<div class="monetary-loan-no-attachment">' +
                "No attachments added yet." +
                "</div>";

            return;
        }

        const existingHTML =
            activeExisting.map(
                function (attachment) {
                    const attachmentID =
                        Number(
                            attachment.attachment_id
                        );

                    const fileName =
                        attachment.original_file_name ||
                        "Attachment";

                    return (
                        '<div class="monetary-loan-existing-attachment">' +
                            '<div class="monetary-loan-existing-attachment-info">' +
                                '<i class="bi ' +
                                getAttachmentIcon(fileName) +
                                '"></i>' +
                                "<span>" +
                                    escapeHTML(fileName) +
                                    " (" +
                                    escapeHTML(
                                        formatFileSize(
                                            attachment.file_size
                                        )
                                    ) +
                                    ")" +
                                "</span>" +
                            "</div>" +
                            '<div class="monetary-loan-existing-attachment-actions">' +
                                '<button type="button" ' +
                                    'class="monetary-loan-view-attachment-btn" ' +
                                    'data-attachment-id="' +
                                    attachmentID +
                                    '" title="View attachment">' +
                                    '<i class="bi bi-eye-fill"></i>' +
                                "</button>" +
                                (
                                    canEdit
                                        ? (
                                            '<button type="button" ' +
                                            'class="monetary-loan-remove-existing-attachment-btn" ' +
                                            'data-attachment-id="' +
                                            attachmentID +
                                            '" title="Remove attachment">' +
                                            '<i class="bi bi-x-circle-fill"></i>' +
                                            "</button>"
                                        )
                                        : ""
                                ) +
                            "</div>" +
                        "</div>"
                    );
                }
            ).join("");

        const newHTML =
            newAttachments.map(
                function (file, index) {
                    return (
                        '<div class="monetary-loan-new-attachment">' +
                            '<div class="monetary-loan-new-attachment-info">' +
                                '<i class="bi ' +
                                getAttachmentIcon(file.name) +
                                '"></i>' +
                                "<span>" +
                                    escapeHTML(file.name) +
                                    " (" +
                                    escapeHTML(
                                        formatFileSize(file.size)
                                    ) +
                                    ")" +
                                "</span>" +
                                "<small>NEW</small>" +
                            "</div>" +
                            '<button type="button" ' +
                                'class="monetary-loan-remove-new-attachment-btn" ' +
                                'data-file-index="' +
                                index +
                                '" title="Remove new attachment">' +
                                '<i class="bi bi-x-circle-fill"></i>' +
                            "</button>" +
                        "</div>"
                    );
                }
            ).join("");

        attachmentList.innerHTML =
            existingHTML +
            newHTML;
    }


    function findExistingAttachment(attachmentID) {
        return existingAttachments.find(
            function (attachment) {
                return (
                    Number(
                        attachment.attachment_id
                    ) ===
                    Number(attachmentID)
                );
            }
        );
    }


        async function viewExistingAttachment(attachment) {
        if (!attachment) {
            return;
        }

        const fileName =
            String(
                attachment.original_file_name ||
                "Attachment"
            );

        const extension =
            getFileExtension(fileName);

        const viewURL =
            String(
                attachment.view_url || ""
            ).trim();

        if (!viewURL) {
            await showError(
                "Unable to view attachment",
                "The attachment link is unavailable."
            );

            return;
        }

        if (typeof Swal === "undefined") {
            window.open(
                viewURL,
                "_blank",
                "noopener,noreferrer"
            );

            return;
        }


        /* IMAGE PREVIEW */

        if (
            [
                "jpg",
                "jpeg",
                "png"
            ].includes(extension)
        ) {
            await Swal.fire({
                imageUrl: viewURL,
                imageAlt: fileName,
                imageWidth: 900,

                width: 1100,

                showCloseButton: true,
                showConfirmButton: false,

                allowOutsideClick: true,
                allowEscapeKey: true
            });

            return;
        }


        /* PDF PREVIEW */

        if (extension === "pdf") {
            const safeViewURL =
                viewURL
                    .replace(
                        /&/g,
                        "&amp;"
                    )
                    .replace(
                        /"/g,
                        "&quot;"
                    )
                    .replace(
                        /</g,
                        "&lt;"
                    )
                    .replace(
                        />/g,
                        "&gt;"
                    );

            await Swal.fire({
                width: 1000,

                showCloseButton: true,
                showConfirmButton: false,

                allowOutsideClick: true,
                allowEscapeKey: true,

                html: `
                    <iframe
                        src="${safeViewURL}"
                        title="PDF attachment preview"
                        style="
                            display: block;
                            width: 100%;
                            height: min(700px, 72vh);
                            border: none;
                            border-radius: 12px;
                            background: #ffffff;
                        "
                    ></iframe>
                `
            });

            return;
        }


        /* DOC AND DOCX DOWNLOAD */

        if (
            extension === "doc" ||
            extension === "docx"
        ) {
            const downloadResult =
                await Swal.fire({
                    icon: "info",

                    title: "Download attachment?",

                    text:
                        fileName +
                        " cannot be previewed directly while the system is offline.",

                    showCancelButton: true,

                    confirmButtonText:
                        "DOWNLOAD",

                    cancelButtonText:
                        "CANCEL",

                    reverseButtons: true,

                    confirmButtonColor:
                        "#8ca15b",

                    cancelButtonColor:
                        "#7d7d7d"
                });

            if (downloadResult.isConfirmed) {
                const downloadLink =
                    document.createElement(
                        "a"
                    );

                downloadLink.href =
                    viewURL;

                downloadLink.style.display =
                    "none";

                document.body.appendChild(
                    downloadLink
                );

                downloadLink.click();
                downloadLink.remove();
            }

            return;
        }


        await showError(
            "Unsupported attachment",
            "This attachment cannot be previewed."
        );
    }


    attachmentInput?.addEventListener(
        "change",
        function () {
            if (attachmentInput.disabled) {
                return;
            }

            addSelectedAttachments(
                attachmentInput.files
            );
        }
    );


    uploadBox?.addEventListener(
        "click",
        function () {
            if (
                attachmentInput &&
                !attachmentInput.disabled
            ) {
                attachmentInput.click();
            }
        }
    );


    uploadBox?.addEventListener(
        "keydown",
        function (event) {
            if (
                event.key !== "Enter" &&
                event.key !== " "
            ) {
                return;
            }

            event.preventDefault();

            if (
                attachmentInput &&
                !attachmentInput.disabled
            ) {
                attachmentInput.click();
            }
        }
    );


    uploadBox?.addEventListener(
        "dragover",
        function (event) {
            event.preventDefault();

            if (!attachmentInput?.disabled) {
                uploadBox.classList.add(
                    "monetary-loan-upload-dragging"
                );
            }
        }
    );


    uploadBox?.addEventListener(
        "dragleave",
        function () {
            uploadBox.classList.remove(
                "monetary-loan-upload-dragging"
            );
        }
    );


    uploadBox?.addEventListener(
        "drop",
        function (event) {
            event.preventDefault();

            uploadBox.classList.remove(
                "monetary-loan-upload-dragging"
            );

            if (attachmentInput?.disabled) {
                return;
            }

            addSelectedAttachments(
                event.dataTransfer.files
            );
        }
    );


    attachmentList?.addEventListener(
        "click",
        async function (event) {
            const viewButton =
                event.target.closest(
                    ".monetary-loan-view-attachment-btn"
                );

            if (viewButton) {
                const attachment =
                    findExistingAttachment(
                        viewButton.dataset.attachmentId
                    );

                await viewExistingAttachment(
                    attachment
                );

                return;
            }

            const removeExistingButton =
                event.target.closest(
                    ".monetary-loan-remove-existing-attachment-btn"
                );

            if (removeExistingButton) {
                const attachmentID =
                    Number(
                        removeExistingButton
                            .dataset.attachmentId
                    );

                const attachment =
                    findExistingAttachment(
                        attachmentID
                    );

                const result =
                await Swal.fire({
                    icon: "warning",
                    title: "Remove attachment?",
                    text:
                        attachment?.original_file_name ||
                        "This attachment will be removed after saving.",
                    showCancelButton: true,
                    confirmButtonText: "REMOVE",
                    cancelButtonText: "CANCEL",
                    confirmButtonColor: "#e95454",
                    cancelButtonColor: "#808080",
                    reverseButtons: true,
                    focusCancel: true,
                    allowOutsideClick: false,
                    customClass: {
                        popup: "monetary-loan-remove-attachment-popup",
                        confirmButton: "monetary-loan-remove-confirm-btn"
                    }
                });

                if (result.isConfirmed) {
                    removedAttachmentIDs.add(
                        attachmentID
                    );

                    renderAttachments();
                }

                return;
            }

            const removeNewButton =
                event.target.closest(
                    ".monetary-loan-remove-new-attachment-btn"
                );

            if (removeNewButton) {
                const fileIndex =
                    Number(
                        removeNewButton
                            .dataset.fileIndex
                    );

                newAttachments.splice(
                    fileIndex,
                    1
                );

                renderAttachments();
            }
        }
    );


    /* =========================================================
       PAYMENT HISTORY
    ========================================================= */

    function renderPaymentHistory(payments) {
        if (!paymentHistoryBody) {
            return;
        }

        if (
            !Array.isArray(payments) ||
            payments.length === 0
        ) {
            paymentHistoryBody.innerHTML =
                '<tr class="monetary-loan-payment-empty">' +
                    '<td colspan="10">' +
                        "No payments recorded yet." +
                    "</td>" +
                "</tr>";

            return;
        }

        paymentHistoryBody.innerHTML =
            payments.map(
                function (payment) {
                    const type =
                        String(
                            payment.transaction_type ||
                            "Payment"
                        );

                    const typeClass =
                        type.toLowerCase() === "reversal"
                            ? "reversal"
                            : "payment";

                    const notes =
                        String(
                            payment.notes || ""
                        ).trim();

                    let actionHTML = "—";

                    if (
                        typeClass === "payment" &&
                        payment.can_reverse
                    ) {
                        actionHTML =
                            '<button type="button" ' +
                            'class="monetary-loan-reverse-payment-btn" ' +
                            'data-payment-id="' +
                            escapeHTML(
                                payment.payment_id
                            ) +
                            '" title="Reverse payment">' +
                            '<i class="bi bi-arrow-counterclockwise"></i>' +
                            "</button>";
                    } else if (
                        typeClass === "payment" &&
                        payment.is_reversed
                    ) {
                        actionHTML = "Reversed";
                    }

                    return (
                        "<tr>" +
                            "<td>" +
                                escapeHTML(
                                    payment.payment_date_display ||
                                    payment.payment_date ||
                                    "—"
                                ) +
                            "</td>" +

                            "<td>" +
                                escapeHTML(
                                    Number(
                                        payment.elapsed_days || 0
                                    )
                                ) +
                                (
                                    Number(
                                        payment.elapsed_days || 0
                                    ) === 1
                                        ? " Day"
                                        : " Days"
                                ) +
                            "</td>" +

                            "<td>" +
                                '<span class="monetary-loan-payment-type ' +
                                typeClass +
                                '">' +
                                    escapeHTML(type) +
                                "</span>" +
                            "</td>" +

                            "<td>" +
                                escapeHTML(
                                    notes || "—"
                                ) +
                            "</td>" +

                            "<td>" +
                                formatMoney(
                                    payment.payment_amount
                                ) +
                            "</td>" +

                            "<td>" +
                                formatMoney(
                                    payment.interest_paid
                                ) +
                            "</td>" +

                            "<td>" +
                                formatMoney(
                                    payment.principal_paid
                                ) +
                            "</td>" +

                            "<td>" +
                                formatMoney(
                                    payment.balance_after
                                ) +
                            "</td>" +

                            "<td>" +
                                escapeHTML(
                                    payment.recorded_by_name ||
                                    "—"
                                ) +
                            "</td>" +

                            "<td>" +
                                actionHTML +
                            "</td>" +

                        "</tr>"
                    );
                }
            ).join("");
    }

/*   */
    function populatePaymentSection(data) {
        const loan =
            data.loan;

        const permission =
            data.permission || {};

        if (paymentSection) {
            paymentSection.hidden =
                normalizeStatus(loan.status) !==
                "Accepted";
        }

        const isLongTermLoan =
            String(
                loan.loan_purpose || ""
            ).trim() ===
            "Long-Term Loan";

        const loanYears =
            Number(
                loan.loan_term_years || 0
            );

        const termDays =
            Number(
                loan.term_days || 0
            );

        const paymentPeriods =
            Number(
                loan.payment_periods ||
                (
                    isLongTermLoan &&
                    loanYears > 0
                        ? loanYears *
                        (
                            termDays === 180
                                ? 2
                                : 1
                        )
                        : 0
                )
            );

        if (paymentLongTermYearsItem) {
            paymentLongTermYearsItem.hidden =
                !isLongTermLoan;
        }

        if (paymentLongTermYears) {
            paymentLongTermYears.textContent =
                isLongTermLoan &&
                loanYears > 0
                    ? loanYears +
                    (
                        loanYears === 1
                            ? " Year"
                            : " Years"
                    )
                    : "—";
        }

        if (paymentLongTermPlanItem) {
            paymentLongTermPlanItem.hidden =
                !isLongTermLoan;
        }

        if (paymentLongTermPlan) {
            paymentLongTermPlan.textContent =
                isLongTermLoan &&
                paymentPeriods > 0 &&
                [180, 360].includes(termDays)
                    ? paymentPeriods +
                    (
                        paymentPeriods === 1
                            ? " Payment"
                            : " Payments"
                    ) +
                    " · Every " +
                    termDays +
                    " Days · Diminishing Balance"
                    : "—";
        }

        if (paymentPrincipal) {
            paymentPrincipal.textContent =
                formatMoney(
                    loan.principal_amount
                );
        }

        if (paymentUnpaidInterest) {
            paymentUnpaidInterest.textContent =
                formatMoney(
                    loan.stored_unpaid_interest_amount
                );
        }

        if (paymentElapsedDays) {
            const days =
                Number(
                    loan.elapsed_days || 0
                );

            paymentElapsedDays.textContent =
                days +
                (
                    days === 1
                        ? " Day"
                        : " Days"
                );
        }

        if (paymentNewInterest) {
            paymentNewInterest.textContent =
                formatMoney(
                    loan.new_accrued_interest_amount
                );
        }

        if (paymentPayoffBalance) {
            paymentPayoffBalance.textContent =
                formatMoney(
                    loan.current_balance
                );
        }

        if (paymentDateInput) {
            paymentDateInput.value =
                data.server?.date || "";

            paymentDateInput.max =
                data.server?.date || "";
        }

        if (paymentAmountInput) {
            paymentAmountInput.value = "";
            paymentAmountInput.max =
                Number(
                    loan.current_balance || 0
                ).toFixed(2);
        }

        if (paymentNotesInput) {
            paymentNotesInput.value = "";
        }

        if (recordPaymentButton) {
            recordPaymentButton.disabled =
                !permission.can_record_payment ||
                Number(
                    loan.current_balance || 0
                ) <= 0;
        }

        renderPaymentHistory(
            data.payments || []
        );
    }


    /* =========================================================
       POPULATE MODAL
    ========================================================= */

    function populateModal(data) {
        currentLoanData = data;

        const loan =
            data.loan;

        existingAttachments =
            Array.isArray(data.attachments)
                ? data.attachments
                : [];

        newAttachments = [];
        removedAttachmentIDs.clear();

        const status =
            normalizeStatus(loan.status);

        if (loanIDInput) {
            loanIDInput.value =
                loan.monetary_loan_id;
        }

        if (memberIDInput) {
            memberIDInput.value =
                loan.member_id;
        }

        if (originalMemberIDInput) {
            originalMemberIDInput.value =
                loan.member_id;
        }

        if (existingStatusInput) {
            existingStatusInput.value =
                status;
        }

        if (modalTitle) {
            modalTitle.textContent =
                status === "Pending"
                    ? "EDIT MONETARY LOAN"
                    : "MONETARY LOAN DETAILS";
        }

        if (displayIDInput) {
            displayIDInput.value =
                loan.display_id || "";
        }

        if (applicationDateInput) {
            applicationDateInput.value =
                loan.application_date_display ||
                loan.application_date ||
                "";
        }

        if (releaseDateInput) {
            releaseDateInput.value =
                loan.release_date_display ||
                "Not accepted";
        }

        if (dueDateInput) {
            dueDateInput.value =
                loan.due_date_display ||
                "Generated upon acceptance";
        }

        if (approvedByInput) {
            approvedByInput.value =
                loan.approved_by_name ||
                "Not approved";
        }

        if (memberNameInput) {
            memberNameInput.value =
                loan.full_name || "";

            memberNameInput.dataset.activeLoanTypes =
                Array.isArray(
                    loan.active_loan_types
                )
                    ? loan.active_loan_types
                        .map(function (loanType) {
                            return String(
                                loanType || ""
                            ).trim();
                        })
                        .filter(function (loanType) {
                            return loanType !== "";
                        })
                        .join("||")
                    : "";
        }

        if (termInput) {
            termInput.value =
                String(
                    loan.term_days || ""
                );
        }

        if (statusInput) {
            statusInput.value =
                status;
        }

        if (purposeInput) {
            purposeInput.value =
                loan.loan_purpose || "";
        }

        if (loanYearsInput) {
            loanYearsInput.value =
                loan.loan_term_years !== null &&
                loan.loan_term_years !== undefined
                    ? String(
                        loan.loan_term_years
                    )
                    : "";
        }

        updateLoanYearsField();

        if (principalInput) {
            principalInput.value =
                Number(
                    loan.principal_amount || 0
                ).toFixed(2);
        }

        if (rejectionReasonInput) {
            rejectionReasonInput.value =
                loan.rejection_reason || "";
        }

        updateStatusBadge(status);
        populateFinancialSummary(loan);
        populatePaymentSection(data);
        updateRejectionSection();
        setEditableState();
        renderAttachments();

        if (saveChangesButton) {
            saveChangesButton.disabled = false;
        }
    }


    /* =========================================================
       LOAD MONETARY LOAN
    ========================================================= */

    async function loadMonetaryLoan(loanID) {
        if (!loanID) {
            return;
        }

        resetModal();

        if (loanIDInput) {
            loanIDInput.value = loanID;
        }

        if (saveChangesButton) {
            saveChangesButton.disabled = true;
        }

        try {
            const data =
                await requestJSON(
                    "get_monetary_loan.php?loan_id=" +
                    encodeURIComponent(loanID)
                );

            if (!data.success) {
                throw new Error(
                    data.message ||
                    "Unable to load the Monetary Loan."
                );
            }

            populateModal(data);
        } catch (error) {
            modalInstance?.hide();

            await showError(
                "Unable to load Monetary Loan",
                error.message
            );
        }
    }


    document.addEventListener(
        "click",
        function (event) {
            const editButton =
                event.target.closest(
                    ".monetary-loan-edit-btn"
                );

            if (!editButton) {
                return;
            }

            const loanID =
                editButton.dataset.loanId;

            if (!loanID) {
                showError(
                    "Unable to open",
                    "The Monetary Loan ID is missing."
                );

                return;
            }

            modalInstance?.show();
            loadMonetaryLoan(loanID);
        }
    );


    /* =========================================================
       STATUS AND CALCULATION EVENTS
    ========================================================= */

    termInput?.addEventListener(
        "change",
        function () {
            showFieldError(termError, "");
            updatePendingSummary();
            updateAcceptancePreview();
        }
    );


    principalInput?.addEventListener(
        "input",
        function () {
            showFieldError(principalError, "");
            updatePendingSummary();
        }
    );


    loanYearsInput?.addEventListener(
        "input",
        function () {
            showFieldError(
                loanYearsError,
                ""
            );

            updatePendingSummary();
            updateAcceptancePreview();
        }
    );


    purposeInput?.addEventListener(
        "change",
        async function () {
            showFieldError(purposeError, "");

            const selectedLoanType =
                String(
                    purposeInput.value || ""
                ).trim();

            const selectedMemberActiveLoanTypes =
                String(
                    memberNameInput?.dataset
                        .activeLoanTypes ||
                    ""
                )
                    .split("||")
                    .map(function (loanType) {
                        return String(
                            loanType || ""
                        ).trim();
                    })
                    .filter(function (loanType) {
                        return loanType !== "";
                    });

            if (
                selectedLoanType !== "" &&
                memberIDInput?.value &&
                selectedMemberActiveLoanTypes.includes(
                    selectedLoanType
                )
            ) {
                const selectedMemberName =
                    String(
                        memberNameInput?.value ||
                        "This member"
                    ).trim();

                const originalLoanType =
                    String(
                        currentLoanData?.loan
                            ?.loan_purpose ||
                        ""
                    ).trim();

                if (
                    purposeInput &&
                    originalLoanType !== ""
                ) {
                    purposeInput.value =
                        originalLoanType;
                }

                if (loanYearsInput) {
                    loanYearsInput.value =
                        currentLoanData?.loan
                            ?.loan_term_years !== null &&
                        currentLoanData?.loan
                            ?.loan_term_years !== undefined
                            ? String(
                                currentLoanData.loan
                                    .loan_term_years
                            )
                            : "";
                }

                updateLoanYearsField();
                updatePendingSummary();
                updateAcceptancePreview();

                showFieldError(
                    memberError,
                    selectedMemberName +
                    " already has another active " +
                    selectedLoanType +
                    ". Loan Type was restored to " +
                    originalLoanType +
                    "."
                );

                await showError(
                    "Existing " + selectedLoanType,
                    selectedMemberName +
                    " still has another active " +
                    selectedLoanType +
                    ". The Loan Type has been restored to " +
                    originalLoanType +
                    "."
                );

                return;
            }

            showFieldError(memberError, "");

            updateLoanYearsField();
            updatePendingSummary();
            updateAcceptancePreview();
        }
    );


    statusInput?.addEventListener(
        "change",
        function () {
            updateStatusBadge(
                statusInput.value
            );

            updateRejectionSection();
            updateAcceptancePreview();
        }
    );


    rejectionReasonInput?.addEventListener(
        "input",
        function () {
            showFieldError(rejectionError, "");
        }
    );


    /* =========================================================
       VALIDATE EDIT FORM
    ========================================================= */

    function validateEditForm() {
        clearFieldErrors();

        let valid = true;

        const status =
            normalizeStatus(
                statusInput?.value
            );

        if (!memberIDInput?.value) {
            showFieldError(
                memberError,
                "Please select an eligible Regular member from the search results."
            );

            valid = false;
        }

        const term =
            getTermCalculation();

        if (
            term.termDays !== 180 &&
            term.termDays !== 360
        ) {
            showFieldError(
                termError,
                "Please select a valid loan term."
            );

            valid = false;
        }

        const purpose =
            String(
                purposeInput?.value || ""
            ).trim();

        if (
            !ALLOWED_MONETARY_LOAN_TYPES.includes(
                purpose
            )
        ) {
            showFieldError(
                purposeError,
                "Please select a valid Monetary Loan type."
            );

            valid = false;
        }

        const loanYears =
            Number(
                loanYearsInput?.value || 0
            );

        if (
            purpose === "Long-Term Loan"
        ) {
            if (
                !Number.isInteger(
                    loanYears
                ) ||
                loanYears < 1 ||
                loanYears > 65535
            ) {
                showFieldError(
                    loanYearsError,
                    "Enter a valid whole number of years."
                );

                valid = false;
            }
        } else {
            showFieldError(
                loanYearsError,
                ""
            );
        }

        const principal =
            Number.parseFloat(
                principalInput?.value || "0"
            );

        if (
            !Number.isFinite(principal) ||
            principal <= 0
        ) {
            showFieldError(
                principalError,
                "Enter a valid principal amount."
            );

            valid = false;
        }

        if (status === "Rejected") {
            const rejectionReason =
                String(
                    rejectionReasonInput?.value ||
                    ""
                ).trim();

            if (!rejectionReason) {
                showFieldError(
                    rejectionError,
                    "Please enter the reason for rejection."
                );

                valid = false;
            }
        }

        if (!valid) {
            const firstError =
                editForm.querySelector(
                    ".monetary-loan-field-error:not([hidden])"
                );

            firstError?.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });
        }

        return valid;
    }


    /* =========================================================
       UPDATE MONETARY LOAN
    ========================================================= */

    editForm.addEventListener(
        "submit",
        async function (event) {
            event.preventDefault();

            if (
                isSubmitting ||
                !currentLoanData
            ) {
                return;
            }

            if (!validateEditForm()) {
                return;
            }

            const selectedStatus =
                normalizeStatus(
                    statusInput?.value
                );

            const confirmation =
                await Swal.fire({
                    icon: "question",
                    title: "Save Monetary Loan changes?",
                    text:
                        selectedStatus === "Accepted"
                            ? "The loan will be accepted. The release date and due date will be generated using the current Asia/Manila server date."
                            : selectedStatus === "Rejected"
                                ? "The Monetary Loan application will be marked as Rejected."
                                : "The pending Monetary Loan information will be updated.",
                    showCancelButton: true,
                    confirmButtonText: "YES, CONTINUE",
                    cancelButtonText: "CANCEL",
                    reverseButtons: true,
                    confirmButtonColor: "#91aa5b",
                    cancelButtonColor: "#808080"
                });

            if (!confirmation.isConfirmed) {
                return;
            }

            const formData =
                new FormData();

            formData.append(
                "csrf_token",
                getCSRFToken()
            );

            formData.append(
                "monetary_loan_id",
                loanIDInput?.value || ""
            );

            formData.append(
                "member_id",
                memberIDInput?.value || ""
            );

            formData.append(
                "loan_purpose",
                String(
                    purposeInput?.value || ""
                ).trim()
            );

            formData.append(
                "principal_amount",
                principalInput?.value || "0"
            );

            formData.append(
                "loan_term_years",
                longTermLoanIsSelected()
                    ? (
                        loanYearsInput?.value ||
                        ""
                    )
                    : ""
            );

            formData.append(
                "term_days",
                termInput?.value || ""
            );

            formData.append(
                "status",
                selectedStatus
            );

            formData.append(
                "rejection_reason",
                String(
                    rejectionReasonInput?.value ||
                    ""
                ).trim()
            );

            formData.append(
                "removed_attachment_ids",
                JSON.stringify(
                    Array.from(
                        removedAttachmentIDs
                    )
                )
            );

            newAttachments.forEach(
                function (file) {
                    formData.append(
                        "attachments[]",
                        file,
                        file.name
                    );
                }
            );

            isSubmitting = true;

            if (saveChangesButton) {
                saveChangesButton.disabled = true;
            }

            try {
                const data =
                    await requestJSON(
                        "update_monetary_loan.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    );

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        "Unable to update the Monetary Loan."
                    );
                }

                await showSuccess(
                    "Monetary Loan updated",
                    data.message ||
                    "The Monetary Loan was updated successfully."
                );

                reloadAtCurrentMonetaryLoan();

            } catch (error) {
                await showError(
                    "Unable to update",
                    error.message
                );
            } finally {
                isSubmitting = false;

                if (saveChangesButton) {
                    saveChangesButton.disabled = false;
                }
            }
        }
    );


    /* =========================================================
       RECORD PAYMENT
    ========================================================= */

    recordPaymentButton?.addEventListener(
        "click",
        async function () {
            if (
                !currentLoanData ||
                recordPaymentButton.disabled
            ) {
                return;
            }

            const paymentAmount =
                Number.parseFloat(
                    paymentAmountInput?.value ||
                    "0"
                );

            const currentBalance =
                Number.parseFloat(
                    currentLoanData.loan
                        .current_balance ||
                    "0"
                );

            if (
                !Number.isFinite(paymentAmount) ||
                paymentAmount <= 0
            ) {
                await showError(
                    "Invalid payment amount",
                    "Enter a payment amount greater than zero."
                );

                paymentAmountInput?.focus();
                return;
            }

            if (
                paymentAmount >
                currentBalance + 0.009
            ) {
                await showError(
                    "Payment exceeds balance",
                    "The payment cannot exceed the current payoff balance of " +
                    formatMoney(currentBalance) +
                    "."
                );

                paymentAmountInput?.focus();
                return;
            }

            const confirmation =
                await Swal.fire({
                    icon: "question",
                    title: "Record this payment?",
                    html:
                        "<strong>Payment:</strong> " +
                        escapeHTML(
                            formatMoney(paymentAmount)
                        ) +
                        "<br><br>" +
                        "The payment will be applied to interest first, then to principal.",
                    showCancelButton: true,
                    confirmButtonText: "YES, RECORD",
                    cancelButtonText: "CANCEL",
                    reverseButtons: true,
                    confirmButtonColor: "#91aa5b",
                    cancelButtonColor: "#808080"
                });

            if (!confirmation.isConfirmed) {
                return;
            }

            const formData =
                new FormData();

            formData.append(
                "csrf_token",
                getCSRFToken()
            );

            formData.append(
                "monetary_loan_id",
                loanIDInput?.value || ""
            );

            formData.append(
                "payment_date",
                paymentDateInput?.value || ""
            );

            formData.append(
                "payment_amount",
                paymentAmount.toFixed(2)
            );

            formData.append(
                "notes",
                String(
                    paymentNotesInput?.value ||
                    ""
                ).trim()
            );

            recordPaymentButton.disabled = true;

            try {
                const data =
                    await requestJSON(
                        "record_monetary_loan_payment.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    );

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        "Unable to record the payment."
                    );
                }

                await showSuccess(
                    "Payment recorded",
                    data.message ||
                    "The payment was recorded successfully."
                );

                reloadAtCurrentMonetaryLoan();

            } catch (error) {
                await showError(
                    "Unable to record payment",
                    error.message
                );
            } finally {
                if (
                    currentLoanData?.permission
                        ?.can_record_payment &&
                    Number(
                        currentLoanData.loan
                            .current_balance || 0
                    ) > 0
                ) {
                    recordPaymentButton.disabled = false;
                }
            }
        }
    );


    /* =========================================================
    REVERSE PAYMENT
    ========================================================= */

    paymentHistoryBody?.addEventListener(
        "click",
        async function (event) {
            const reverseButton =
                event.target.closest(
                    ".monetary-loan-reverse-payment-btn"
                );

            if (!reverseButton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            const paymentID =
                reverseButton.dataset.paymentId;

            if (!paymentID) {
                return;
            }

            const payment =
                currentLoanData?.payments?.find(
                    function (item) {
                        return String(item.payment_id) ===
                            String(paymentID);
                    }
                );

            if (!payment) {
                await showError(
                    "Payment not found",
                    "Unable to locate the selected payment."
                );

                return;
            }

            /*
            * Disable muna ang Bootstrap focus trap para
            * ma-click at ma-type ang SweetAlert textarea.
            */
            const bootstrapModal =
                window.bootstrap?.Modal
                    ? window.bootstrap.Modal.getInstance(
                        modalElement
                    )
                    : null;

            const modalFocusTrap =
                bootstrapModal?._focustrap || null;

            if (modalFocusTrap) {
                modalFocusTrap.deactivate();
            }

            const accruedDays =
                Number(payment.elapsed_days || 0);

            let confirmation;

            try {
                confirmation =
                    await Swal.fire({
                        target: modalElement,
                        heightAuto: false,
                        scrollbarPadding: false,
                        icon: "warning",
                        title: "Reverse this payment?",

                        html:
                            '<div class="monetary-loan-reversal-details">' +
                                "<p>" +
                                    "<strong>Payment:</strong> " +
                                    formatMoney(
                                        payment.payment_amount
                                    ) +
                                "</p>" +

                                "<p>" +
                                    "<strong>Accrued Days:</strong> " +
                                    accruedDays +
                                    (
                                        accruedDays === 1
                                            ? " Day"
                                            : " Days"
                                    ) +
                                "</p>" +

                                '<p class="monetary-loan-reversal-message">' +
                                    "The original payment will remain in the history. " +
                                    "A reversal record will be created." +
                                "</p>" +
                            "</div>",

                        input: "textarea",

                        inputLabel:
                            "Reason for reversal",

                        inputPlaceholder:
                            "Explain why this payment must be reversed",

                        inputAttributes: {
                            maxlength: "500",
                            autocomplete: "off",
                            spellcheck: "true"
                        },

                        showCancelButton: true,

                        confirmButtonText:
                            "REVERSE PAYMENT",

                        cancelButtonText:
                            "CANCEL",

                        confirmButtonColor:
                            "#91aa5b",

                        cancelButtonColor:
                            "#777777",

                        reverseButtons: true,
                        focusCancel: false,
                        focusConfirm: false,
                        returnFocus: false,
                        allowOutsideClick: false,

                        customClass: {
                            popup:
                                "monetary-loan-reversal-popup",

                            input:
                                "monetary-loan-reversal-input",

                            confirmButton:
                                "monetary-loan-reversal-confirm",

                            cancelButton:
                                "monetary-loan-reversal-cancel"
                        },

                        didOpen: function (popup) {
                            const container =
                                popup.closest(
                                    ".swal2-container"
                                );

                            const label =
                                popup.querySelector(
                                    ".swal2-input-label"
                                );

                            const textarea =
                                popup.querySelector(
                                    ".swal2-textarea"
                                );

                            if (container) {
                                container.style.setProperty(
                                    "pointer-events",
                                    "auto",
                                    "important"
                                );

                                container.style.setProperty(
                                    "overflow-x",
                                    "hidden",
                                    "important"
                                );

                                container.style.setProperty(
                                    "overflow-y",
                                    "hidden",
                                    "important"
                                );
                            }

                            popup.style.setProperty(
                                "pointer-events",
                                "auto",
                                "important"
                            );

                            if (label) {
                                label.style.setProperty(
                                    "display",
                                    "block",
                                    "important"
                                );

                                label.style.setProperty(
                                    "width",
                                    "100%",
                                    "important"
                                );

                                label.style.setProperty(
                                    "color",
                                    "#4f5148",
                                    "important"
                                );

                                label.style.setProperty(
                                    "font-weight",
                                    "700",
                                    "important"
                                );

                                label.style.setProperty(
                                    "text-align",
                                    "center",
                                    "important"
                                );

                                label.style.setProperty(
                                    "opacity",
                                    "1",
                                    "important"
                                );
                            }

                            if (textarea) {
                                textarea.disabled = false;
                                textarea.readOnly = false;
                                textarea.removeAttribute("disabled");
                                textarea.removeAttribute("readonly");

                                textarea.style.setProperty(
                                    "pointer-events",
                                    "auto",
                                    "important"
                                );

                                textarea.style.setProperty(
                                    "user-select",
                                    "text",
                                    "important"
                                );

                                textarea.style.setProperty(
                                    "color",
                                    "#22251f",
                                    "important"
                                );

                                textarea.style.setProperty(
                                    "background-color",
                                    "#fffed6",
                                    "important"
                                );

                                textarea.style.setProperty(
                                    "caret-color",
                                    "#22251f",
                                    "important"
                                );

                                window.setTimeout(
                                    function () {
                                        textarea.focus();
                                        textarea.click();
                                    },
                                    100
                                );
                            }
                        },

                        inputValidator:
                            function (value) {
                                const reason =
                                    String(value || "").trim();

                                if (!reason) {
                                    return "A reversal reason is required.";
                                }

                                return null;
                            }
                    });
            } finally {
                if (modalFocusTrap) {
                    modalFocusTrap.activate();
                }
            }

            if (
                !confirmation ||
                !confirmation.isConfirmed
            ) {
                return;
            }

            const reversalReason =
                String(
                    confirmation.value || ""
                ).trim();

            const formData =
                new FormData();

            formData.append(
                "csrf_token",
                getCSRFToken()
            );

            formData.append(
                "monetary_loan_id",
                loanIDInput?.value || ""
            );

            formData.append(
                "payment_id",
                paymentID
            );

            formData.append(
                "reversal_reason",
                reversalReason
            );

            reverseButton.disabled = true;

            try {
                const data =
                    await requestJSON(
                        "reverse_monetary_loan_payment.php",
                        {
                            method: "POST",
                            body: formData
                        }
                    );

                if (!data.success) {
                    throw new Error(
                        data.message ||
                        "Unable to reverse the payment."
                    );
                }

                await showSuccess(
                    "Payment reversed",
                    data.message ||
                    "The payment was reversed successfully."
                );

                reloadAtCurrentMonetaryLoan();

            } catch (error) {
                reverseButton.disabled = false;

                await showError(
                    "Unable to reverse payment",
                    error.message
                );
            }
        }
    );


    /* =========================================================
       MODAL CLEANUP
    ========================================================= */

    modalElement.addEventListener(
        "hidden.bs.modal",
        function () {
            if (memberSearchController) {
                memberSearchController.abort();
            }

            window.clearTimeout(
                memberSearchTimer
            );

            closeMemberResults();
            resetModal();
        }
    );
});