document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const modalElement =
        document.getElementById("addMonetaryLoanModal");

    const loanForm =
        document.getElementById("addMonetaryLoanForm");

    if (!modalElement || !loanForm) {
        return;
    }

    const memberIDInput =
        document.getElementById("monetaryLoanMemberID");

    const memberNameInput =
        document.getElementById("monetaryLoanMemberName");

    const memberResults =
        document.getElementById("monetaryLoanMemberResults");

    const purposeInput =
        document.getElementById("monetaryLoanPurpose");

    const loanYearsField =
        document.getElementById("monetaryLoanYearsField");

    const loanYearsInput =
        document.getElementById("monetaryLoanYears");

    const principalInput =
        document.getElementById("monetaryLoanPrincipalAmount");

    const loanTermInput =
        document.getElementById("monetaryLoanTerm");

    const interestRateInput =
        document.getElementById("monetaryLoanInterestRate");

    const scheduledInterestInput =
        document.getElementById("monetaryLoanScheduledInterest");

    const scheduledTotalInput =
        document.getElementById("monetaryLoanScheduledTotal");

    const attachmentInput =
        document.getElementById("monetaryLoanAttachments");

    const attachmentList =
        document.getElementById("monetaryLoanAttachmentList");

    const attachmentUploadBox =
        loanForm.querySelector(
            'label[for="monetaryLoanAttachments"]'
        );

    const summaryPrincipal =
        document.getElementById("monetaryLoanSummaryPrincipal");

    const summaryInterestRate =
        document.getElementById("monetaryLoanSummaryInterest");

    const summaryLoanPeriod =
        document.getElementById("monetaryLoanSummaryPeriod");

    const summaryScheduledInterest =
        document.getElementById(
            "monetaryLoanSummaryScheduledInterest"
        );

    const summaryScheduledTotal =
        document.getElementById("monetaryLoanSummaryScheduledTotal");

    const summaryDailyPayment =
        document.getElementById("monetaryLoanSummaryDailyPayment");

    const summaryMonthlyPayment =
        document.getElementById("monetaryLoanSummaryMonthlyPayment");

    const saveButton =
        document.getElementById("saveMonetaryLoanBtn");

    const requiredElements = [
        memberIDInput,
        memberNameInput,
        memberResults,
        purposeInput,
        loanYearsField,
        loanYearsInput,
        principalInput,
        loanTermInput,
        interestRateInput,
        scheduledInterestInput,
        scheduledTotalInput,
        attachmentInput,
        attachmentList,
        attachmentUploadBox,
        saveButton
    ];

    if (
        requiredElements.some(function (element) {
            return !element;
        })
    ) {
        console.error(
            "The Add Monetary Loan modal is missing one or more required elements."
        );

        return;
    }

    /* =====================================================
       STATE AND LIMITS
    ===================================================== */

    let selectedAttachments = [];

    let memberSearchTimer = null;

    let memberSearchController = null;

    let formIsSubmitting = false;

    const MAX_ATTACHMENTS = 10;

    const MAX_FILE_SIZE =
        10 * 1024 * 1024;

    const MAX_TOTAL_FILE_SIZE =
        50 * 1024 * 1024;

    const MAX_PRINCIPAL =
    9999999999.99;

    const ALLOWED_MONETARY_LOAN_TYPES = [
        "Crop Production Loan",
        "Dairy Farmers Loan",
        "Emergency Loan",
        "Long-Term Loan"
    ];

    const ALLOWED_FILE_EXTENSIONS = [
        "jpg",
        "jpeg",
        "png",
        "pdf",
        "doc",
        "docx"
    ];

    const ALLOWED_FILE_TYPES = [
        "image/jpeg",
        "image/png",
        "application/pdf",
        "application/x-pdf",
        "application/msword",
        "application/x-ole-storage",
        "application/cdfv2",
        "application/zip",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
    ];

    /* =====================================================
       GENERAL HELPERS
    ===================================================== */

    function normalizeText(value) {
        return String(value || "")
            .trim()
            .replace(/\s+/g, " ");
    }

    function escapeHTML(value) {
        const temporaryElement =
            document.createElement("div");

        temporaryElement.textContent =
            String(value || "");

        return temporaryElement.innerHTML;
    }

    function roundMoney(value) {
        return Math.round(
            (Number(value) + Number.EPSILON) * 100
        ) / 100;
    }

    function parseMoney(value) {
        const normalizedValue =
            String(value || "")
                .replace(/,/g, "")
                .trim();

        const numericValue =
            Number(normalizedValue);

        return Number.isFinite(numericValue)
            ? numericValue
            : 0;
    }

    function formatMoney(value) {
        return new Intl.NumberFormat(
            "en-PH",
            {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(Number(value) || 0);
    }

    function showAlert(icon, title, message) {
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

    function setButtonLoading(button, isLoading) {
        if (!button) {
            return;
        }

        if (isLoading) {
            if (!button.dataset.originalContent) {
                button.dataset.originalContent =
                    button.innerHTML;
            }

            button.disabled = true;

            button.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' +
                "SAVING...";

            return;
        }

        button.disabled = false;

        if (button.dataset.originalContent) {
            button.innerHTML =
                button.dataset.originalContent;
        }
    }

    async function readJSONResponse(response) {
        const responseText =
            await response.text();

        try {
            return JSON.parse(responseText);
        } catch (error) {
            console.error(
                "Invalid server response:",
                responseText
            );

            throw new Error(
                "The server returned an invalid response. Check the PHP error log."
            );
        }
    }

    /* =====================================================
       FIELD VALIDATION DISPLAY
    ===================================================== */

    function getFieldContainer(input) {
        return input
            ? input.closest(".monetary-loan-field")
            : null;
    }

    function clearFieldValidation(input) {
        if (!input) {
            return;
        }

        input.classList.remove(
            "is-invalid",
            "is-valid"
        );

        const field =
            getFieldContainer(input);

        if (!field) {
            return;
        }

        field
            .querySelectorAll(".monetary-loan-field-error")
            .forEach(function (errorElement) {
                errorElement.remove();
            });
    }

    function setFieldError(input, message) {
        if (!input) {
            return;
        }

        clearFieldValidation(input);

        input.classList.add("is-invalid");

        const field =
            getFieldContainer(input);

        if (!field) {
            return;
        }

        const errorElement =
            document.createElement("small");

        errorElement.className =
            "monetary-loan-field-error";

        errorElement.textContent =
            message;

        field.appendChild(errorElement);
    }

    function setFieldValid(input) {
        if (!input) {
            return;
        }

        clearFieldValidation(input);

        input.classList.add("is-valid");
    }

    function clearAllValidation() {
        loanForm
            .querySelectorAll(
                ".monetary-loan-input, " +
                ".monetary-loan-select, " +
                ".monetary-loan-textarea"
            )
            .forEach(function (input) {
                clearFieldValidation(input);
            });
    }

    /* =====================================================
       MEMBER SEARCH
    ===================================================== */

    function clearSelectedMember() {
        memberIDInput.value = "";

        memberNameInput.dataset.selectedName = "";

        memberNameInput.dataset.selectedCode = "";

        memberNameInput.dataset.activeLoanTypes = "";
    }

    function hideMemberResults() {
        memberResults.innerHTML = "";

        memberResults.classList.remove("show");
    }

    function showMemberMessage(message) {
        memberResults.innerHTML = "";

        const messageElement =
            document.createElement("div");

        messageElement.className =
            "monetary-loan-member-result";

        messageElement.style.cursor = "default";

        messageElement.textContent = message;

        memberResults.appendChild(messageElement);

        memberResults.classList.add("show");
    }

    function getMemberActiveLoanTypes(member) {
        if (
            !member ||
            !Array.isArray(member.active_loan_types)
        ) {
            return [];
        }

        return member.active_loan_types
            .map(function (loanType) {
                return normalizeText(loanType);
            })
            .filter(function (loanType) {
                return loanType !== "";
            });
    }

    function getSelectedLoanType() {
        return normalizeText(
            purposeInput.value
        );
    }

    function activeLoanTypeIsSelected(activeLoanTypes) {
        const selectedLoanType =
            getSelectedLoanType();

        return (
            selectedLoanType !== "" &&
            activeLoanTypes.includes(
                selectedLoanType
            )
        );
    }

    function selectMember(member) {
        const activeLoanTypes =
            getMemberActiveLoanTypes(member);

        const selectedLoanType =
            getSelectedLoanType();

        if (
            activeLoanTypeIsSelected(
                activeLoanTypes
            )
        ) {
            hideMemberResults();

            clearSelectedMember();

            memberNameInput.value = "";

            clearFieldValidation(memberNameInput);

            showAlert(
                "warning",
                "Existing " + selectedLoanType,
                String(member.full_name || "This member") +
                " still has an active " +
                selectedLoanType +
                ". It must be fully paid before another one can be created."
            );

            return;
        }

        memberIDInput.value =
            String(member.member_id || "");

        memberNameInput.value =
            String(member.full_name || "");

        memberNameInput.dataset.selectedName =
            String(member.full_name || "");

        memberNameInput.dataset.selectedCode =
            String(member.member_code || "");

        memberNameInput.dataset.activeLoanTypes =
            activeLoanTypes.join("||");

        setFieldValid(memberNameInput);

        hideMemberResults();
    }

    function renderMemberResults(members) {
        memberResults.innerHTML = "";

        if (!Array.isArray(members) || members.length === 0) {
            showMemberMessage(
                "No eligible Regular member found"
            );

            return;
        }

        members.forEach(function (member) {
            const resultButton =
                document.createElement("button");

            const activeLoanTypes =
                getMemberActiveLoanTypes(member);

            const blockedForSelectedType =
                activeLoanTypeIsSelected(
                    activeLoanTypes
                );

            const memberCode =
                normalizeText(member.member_code) ||
                "No member ID";

            resultButton.type = "button";

            resultButton.className =
                "monetary-loan-member-result";

            resultButton.innerHTML =
                "<strong>" +
                    escapeHTML(member.full_name) +
                "</strong>" +
                "<small>" +
                    escapeHTML(memberCode) +
                    (
                        activeLoanTypes.length > 0
                            ? " • Active: " +
                            escapeHTML(
                                activeLoanTypes.join(", ")
                            )
                            : " • Eligible for Monetary Loan"
                    ) +
                "</small>";

            if (blockedForSelectedType) {
                resultButton.style.opacity = "0.7";
            }

            resultButton.addEventListener(
                "click",
                function () {
                    selectMember(member);
                }
            );

            memberResults.appendChild(resultButton);
        });

        memberResults.classList.add("show");
    }

    async function searchMembers(searchValue) {
        if (memberSearchController) {
            memberSearchController.abort();
        }

        memberSearchController =
            new AbortController();

        showMemberMessage("Searching members...");

        try {
            const response =
                await fetch(
                    "search_monetary_loan_members.php?query=" +
                    encodeURIComponent(searchValue),
                    {
                        method: "GET",
                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        },
                        cache: "no-store",
                        signal:
                            memberSearchController.signal
                    }
                );

            const result =
                await readJSONResponse(response);

            if (!response.ok || !result.success) {
                throw new Error(
                    result.message ||
                    "Unable to search members."
                );
            }

            renderMemberResults(
                result.members || []
            );
        } catch (error) {
            if (error.name === "AbortError") {
                return;
            }

            console.error(
                "Monetary Loan member search error:",
                error
            );

            showMemberMessage(
                "Unable to search members"
            );
        }
    }

    memberNameInput.addEventListener(
        "input",
        function () {
            const searchValue =
                normalizeText(memberNameInput.value);

            const selectedName =
                normalizeText(
                    memberNameInput.dataset.selectedName
                );

            if (searchValue !== selectedName) {
                clearSelectedMember();

                clearFieldValidation(memberNameInput);
            }

            window.clearTimeout(memberSearchTimer);

            if (memberSearchController) {
                memberSearchController.abort();
            }

            if (searchValue.length < 3) {
                hideMemberResults();

                return;
            }

            memberSearchTimer =
                window.setTimeout(
                    function () {
                        if (
                            normalizeText(memberNameInput.value) ===
                            searchValue
                        ) {
                            searchMembers(searchValue);
                        }
                    },
                    350
                );
        }
    );

    memberNameInput.addEventListener(
        "keydown",
        function (event) {
            if (event.key === "Escape") {
                hideMemberResults();
            }
        }
    );

    document.addEventListener(
        "click",
        function (event) {
            if (
                event.target !== memberNameInput &&
                !memberResults.contains(event.target)
            ) {
                hideMemberResults();
            }
        }
    );

    /* =====================================================
       LOAN CALCULATION
    ===================================================== */

    function getSelectedLoanTerm() {
        const selectedOption =
            loanTermInput.options[
                loanTermInput.selectedIndex
            ];

        const days =
            Number(loanTermInput.value) || 0;

        let interestRate =
            selectedOption
                ? Number(selectedOption.dataset.interest)
                : 0;

        if (
            !Number.isFinite(interestRate) ||
            interestRate <= 0
        ) {
            if (days === 180) {
                interestRate = 10;
            } else if (days === 360) {
                interestRate = 20;
            } else {
                interestRate = 0;
            }
        }

        return {
            days: days,
            interestRate: interestRate
        };
    }

    function longTermLoanIsSelected() {
    return (
        normalizeText(purposeInput.value) ===
        "Long-Term Loan"
    );
}

function getLoanYears() {
    const years =
        Number(loanYearsInput.value);

    return (
        Number.isInteger(years) &&
        years > 0
    )
        ? years
        : 0;
}

function updateLoanYearsField() {
    const isLongTerm =
        longTermLoanIsSelected();

    loanYearsField.hidden =
        !isLongTerm;

    loanYearsInput.disabled =
        !isLongTerm;

    loanYearsInput.required =
        isLongTerm;

    if (!isLongTerm) {
        loanYearsInput.value = "";
        clearFieldValidation(
            loanYearsInput
        );
    }
}

function calculateLoanValues() {
    const principal =
        roundMoney(
            parseMoney(principalInput.value)
        );

    const loanTerm =
        getSelectedLoanTerm();

    const loanYears =
        getLoanYears();

    const isLongTerm =
        longTermLoanIsSelected();

    const paymentPeriods =
        isLongTerm &&
        loanYears > 0 &&
        [180, 360].includes(
            loanTerm.days
        )
            ? loanYears *
                (
                    loanTerm.days === 180
                        ? 2
                        : 1
                )
            : 0;

    let scheduledInterest =
        roundMoney(
            principal *
            (
                loanTerm.interestRate /
                100
            )
        );

    /*
     * LONG-TERM LOAN:
     *
     * Equal principal installments.
     * Interest is charged on the remaining
     * principal before every installment.
     *
     * Example:
     * 3 years + 180 days = 6 periods.
     */
    if (
        isLongTerm &&
        paymentPeriods > 0
    ) {
        scheduledInterest =
            roundMoney(
                principal *
                (
                    loanTerm.interestRate /
                    100
                ) *
                (
                    (paymentPeriods + 1) /
                    2
                )
            );
    }

    const scheduledTotal =
        roundMoney(
            principal +
            scheduledInterest
        );

    return {
        principal: principal,
        days: loanTerm.days,
        interestRate:
            loanTerm.interestRate,
        loanYears: loanYears,
        paymentPeriods:
            paymentPeriods,
        scheduledInterest:
            scheduledInterest,
        scheduledTotal:
            scheduledTotal
    };
}

    function updateLoanSummary() {
        const values =
            calculateLoanValues();

        interestRateInput.value =
            values.interestRate > 0
                ? values.interestRate.toFixed(2)
                : "";

        scheduledInterestInput.value =
            values.scheduledInterest.toFixed(2);

        scheduledTotalInput.value =
            values.scheduledTotal.toFixed(2);

        if (summaryPrincipal) {
            summaryPrincipal.textContent =
                formatMoney(values.principal);
        }

        if (summaryInterestRate) {
            summaryInterestRate.textContent =
                values.interestRate > 0
                    ? values.interestRate + "%"
                    : "—";
        }

        if (summaryLoanPeriod) {
            if (
                longTermLoanIsSelected() &&
                values.loanYears > 0 &&
                values.paymentPeriods > 0
            ) {
                summaryLoanPeriod.textContent =
                    values.loanYears +
                    (
                        values.loanYears === 1
                            ? " Year"
                            : " Years"
                    ) +
                    " · " +
                    values.paymentPeriods +
                    (
                        values.paymentPeriods === 1
                            ? " Payment"
                            : " Payments"
                    ) +
                    " · Every " +
                    values.days +
                    " Days";
            } else {
                summaryLoanPeriod.textContent =
                    values.days > 0
                        ? values.days + " Days"
                        : "—";
            }
        }

        if (summaryScheduledInterest) {
            summaryScheduledInterest.textContent =
                formatMoney(values.scheduledInterest);
        }

        if (summaryScheduledTotal) {
            summaryScheduledTotal.textContent =
                formatMoney(values.scheduledTotal);
        }

        const plannedDays = longTermLoanIsSelected()
            ? values.days * values.paymentPeriods
            : values.days;

        const validPaymentEstimate =
            values.principal > 0 &&
            Number.isFinite(values.scheduledTotal) &&
            values.scheduledTotal > 0 &&
            Number.isFinite(plannedDays) &&
            plannedDays > 0;

        if (summaryDailyPayment) {
            summaryDailyPayment.textContent = validPaymentEstimate
                ? formatMoney(values.scheduledTotal / plannedDays)
                : "—";
        }

        if (summaryMonthlyPayment) {
            summaryMonthlyPayment.textContent = validPaymentEstimate
                ? formatMoney(values.scheduledTotal / (plannedDays / 30))
                : "—";
        }
    }

    principalInput.addEventListener(
        "input",
        function () {
            clearFieldValidation(principalInput);

            updateLoanSummary();
        }
    );

    principalInput.addEventListener(
        "blur",
        function () {
            const principal =
                parseMoney(principalInput.value);

            if (
                principal > 0 &&
                principal <= MAX_PRINCIPAL
            ) {
                principalInput.value =
                    roundMoney(principal).toFixed(2);
            }

            updateLoanSummary();
        }
    );

    loanTermInput.addEventListener(
        "change",
        function () {
            clearFieldValidation(loanTermInput);

            updateLoanSummary();
        }
    );

    loanYearsInput.addEventListener(
        "input",
        function () {
            clearFieldValidation(
                loanYearsInput
            );

            updateLoanSummary();
        }
    );


    purposeInput.addEventListener(
        "change",
        async function () {
            clearFieldValidation(
                purposeInput
            );

            updateLoanYearsField();
            updateLoanSummary();

            hideMemberResults();

            const selectedLoanType =
                getSelectedLoanType();

            const selectedMemberActiveLoanTypes =
                (
                    memberNameInput.dataset.activeLoanTypes ||
                    ""
                )
                    .split("||")
                    .map(function (loanType) {
                        return normalizeText(loanType);
                    })
                    .filter(function (loanType) {
                        return loanType !== "";
                    });

            if (
                memberIDInput.value &&
                selectedLoanType !== "" &&
                selectedMemberActiveLoanTypes.includes(
                    selectedLoanType
                )
            ) {
                const selectedMemberName =
                    normalizeText(memberNameInput.value) ||
                    "This member";

                clearSelectedMember();

                memberNameInput.value = "";

                clearFieldValidation(memberNameInput);

                await showAlert(
                    "warning",
                    "Existing " + selectedLoanType,
                    selectedMemberName +
                    " still has an active " +
                    selectedLoanType +
                    ". Select another member or choose a different Monetary Loan type."
                );
            }
        }
    );

    /* =====================================================
       ATTACHMENTS
    ===================================================== */

    function formatFileSize(bytes) {
        if (bytes < 1024) {
            return bytes + " B";
        }

        if (bytes < 1024 * 1024) {
            return (
                bytes / 1024
            ).toFixed(1) + " KB";
        }

        return (
            bytes /
            (1024 * 1024)
        ).toFixed(1) + " MB";
    }

    function getFileExtension(fileName) {
        const normalizedName =
            String(fileName || "")
                .trim()
                .toLowerCase();

        const lastDotIndex =
            normalizedName.lastIndexOf(".");

        if (
            lastDotIndex < 0 ||
            lastDotIndex ===
                normalizedName.length - 1
        ) {
            return "";
        }

        return normalizedName.slice(
            lastDotIndex + 1
        );
    }

    function getAttachmentIconClass(file) {
        const extension =
            getFileExtension(file.name);

        if (extension === "pdf") {
            return "bi bi-file-earmark-pdf";
        }

        if (
            extension === "doc" ||
            extension === "docx"
        ) {
            return "bi bi-file-earmark-word";
        }

        return "bi bi-file-earmark-image";
    }

    function isAllowedAttachment(file) {
        const extension =
            getFileExtension(file.name);

        if (
            !ALLOWED_FILE_EXTENSIONS.includes(
                extension
            )
        ) {
            return false;
        }

        /*
         * Interface check lamang ito.
         * Uulitin sa PHP ang real MIME/content validation.
         */

        if (
            !file.type ||
            file.type.toLowerCase() ===
                "application/octet-stream"
        ) {
            return true;
        }

        return ALLOWED_FILE_TYPES.includes(
            file.type.toLowerCase()
        );
    }

    function selectedAttachmentSize() {
        return selectedAttachments.reduce(
            function (total, file) {
                return total +
                    Number(file.size || 0);
            },
            0
        );
    }

    function rebuildAttachmentInput() {
        if (typeof DataTransfer === "undefined") {
            return;
        }

        const dataTransfer =
            new DataTransfer();

        selectedAttachments.forEach(
            function (file) {
                dataTransfer.items.add(file);
            }
        );

        attachmentInput.files =
            dataTransfer.files;
    }

    function clearAttachmentValidation() {
        attachmentUploadBox.classList.remove(
            "is-invalid"
        );

        attachmentInput.removeAttribute(
            "aria-invalid"
        );

        const oldError =
            loanForm.querySelector(
                ".monetary-loan-attachment-error"
            );

        if (oldError) {
            oldError.remove();
        }
    }

    function setAttachmentError(message) {
        clearAttachmentValidation();

        attachmentUploadBox.classList.add(
            "is-invalid"
        );

        attachmentInput.setAttribute(
            "aria-invalid",
            "true"
        );

        const errorElement =
            document.createElement("small");

        errorElement.className =
            "monetary-loan-attachment-error monetary-loan-field-error";

        errorElement.textContent =
            message;

        attachmentUploadBox.insertAdjacentElement(
            "afterend",
            errorElement
        );
    }

    function renderAttachments() {
        attachmentList.innerHTML = "";

        if (selectedAttachments.length === 0) {
            return;
        }

        selectedAttachments.forEach(
            function (file, index) {
                const item =
                    document.createElement("div");

                const nameContainer =
                    document.createElement("div");

                const icon =
                    document.createElement("i");

                const fileName =
                    document.createElement("span");

                const removeButton =
                    document.createElement("button");

                item.className =
                    "monetary-loan-attachment-item";

                nameContainer.className =
                    "monetary-loan-attachment-name";

                icon.className =
                    getAttachmentIconClass(file);

                icon.style.marginRight = "8px";

                fileName.textContent =
                    file.name +
                    " (" +
                    formatFileSize(file.size) +
                    ")";

                removeButton.type = "button";

                removeButton.className =
                    "monetary-loan-remove-attachment";

                removeButton.title =
                    "Remove attachment";

                removeButton.setAttribute(
                    "aria-label",
                    "Remove " + file.name
                );

                removeButton.innerHTML =
                    '<i class="bi bi-x"></i>';

                removeButton.addEventListener(
                    "click",
                    function () {
                        selectedAttachments.splice(
                            index,
                            1
                        );

                        rebuildAttachmentInput();

                        renderAttachments();
                    }
                );

                nameContainer.appendChild(icon);
                nameContainer.appendChild(fileName);

                item.appendChild(nameContainer);
                item.appendChild(removeButton);

                attachmentList.appendChild(item);
            }
        );
    }

    attachmentInput.addEventListener(
        "change",
        async function () {
            const newFiles =
                Array.from(
                    attachmentInput.files || []
                );

            const acceptedFiles = [];

            let runningTotal =
                selectedAttachmentSize();

            let availableSlots =
                MAX_ATTACHMENTS -
                selectedAttachments.length;

            for (const file of newFiles) {
                const duplicateFile =
                    selectedAttachments
                        .concat(acceptedFiles)
                        .some(
                            function (selectedFile) {
                                return (
                                    selectedFile.name.toLowerCase() ===
                                        file.name.toLowerCase() &&
                                    selectedFile.size ===
                                        file.size &&
                                    selectedFile.lastModified ===
                                        file.lastModified
                                );
                            }
                        );

                if (duplicateFile) {
                    await showAlert(
                        "warning",
                        "Duplicate attachment",
                        file.name +
                        " is already in the attachment list."
                    );

                    continue;
                }

                if (availableSlots <= 0) {
                    await showAlert(
                        "warning",
                        "Attachment limit reached",
                        "A maximum of 10 attachments is allowed per application."
                    );

                    break;
                }

                if (!isAllowedAttachment(file)) {
                    await showAlert(
                        "warning",
                        "Unsupported file",
                        file.name +
                        " is not supported. Only JPG, PNG, PDF, DOC, and DOCX files are allowed."
                    );

                    continue;
                }

                if (file.size <= 0) {
                    await showAlert(
                        "warning",
                        "Empty file",
                        file.name +
                        " is empty and cannot be uploaded."
                    );

                    continue;
                }

                if (file.size > MAX_FILE_SIZE) {
                    await showAlert(
                        "warning",
                        "File is too large",
                        file.name +
                        " exceeds the 10 MB limit per file."
                    );

                    continue;
                }

                if (
                    runningTotal + file.size >
                    MAX_TOTAL_FILE_SIZE
                ) {
                    await showAlert(
                        "warning",
                        "Combined size limit reached",
                        file.name +
                        " was not added because all attachments must not exceed 50 MB in total."
                    );

                    continue;
                }

                acceptedFiles.push(file);

                runningTotal += file.size;

                availableSlots -= 1;
            }

            selectedAttachments =
                selectedAttachments.concat(
                    acceptedFiles
                );

            if (selectedAttachments.length > 0) {
                clearAttachmentValidation();
            }

            rebuildAttachmentInput();

            renderAttachments();
        }
    );

    /* =====================================================
       COMPLETE FORM VALIDATION
    ===================================================== */

    function hasMoreThanTwoDecimals(value) {
        const normalizedValue =
            String(value || "")
                .replace(/,/g, "")
                .trim();

        return /^\d+(?:\.\d{3,})$/.test(
            normalizedValue
        );
    }

    function validateLoanForm() {
        clearAllValidation();

        let firstInvalidField = null;

        const memberName =
            normalizeText(memberNameInput.value);

        const selectedMemberName =
            normalizeText(
                memberNameInput.dataset.selectedName
            );

        const purpose =
            normalizeText(purposeInput.value);

        const principal =
            parseMoney(principalInput.value);

        const term =
            getSelectedLoanTerm();

        const loanYears =
            Number(
                loanYearsInput.value
            );

        if (
            !memberIDInput.value ||
            !memberName ||
            memberName !== selectedMemberName
        ) {
            setFieldError(
                memberNameInput,
                "Select an eligible Regular member from the search results."
            );

            firstInvalidField =
                firstInvalidField ||
                memberNameInput;
        } else {
            setFieldValid(memberNameInput);
        }

        if (
            !ALLOWED_MONETARY_LOAN_TYPES.includes(
                purpose
            )
        ) {
            setFieldError(
                purposeInput,
                "Select a valid Monetary Loan type."
            );

            firstInvalidField =
                firstInvalidField ||
                purposeInput;
        } else {
            purposeInput.value = purpose;

            setFieldValid(purposeInput);
        }

        if (longTermLoanIsSelected()) {
            if (
                !Number.isInteger(
                    loanYears
                ) ||
                loanYears < 1 ||
                loanYears > 65535
            ) {
                setFieldError(
                    loanYearsInput,
                    "Enter a valid whole number of years."
                );

                firstInvalidField =
                    firstInvalidField ||
                    loanYearsInput;
            } else {
                setFieldValid(
                    loanYearsInput
                );
            }
        } else {
            loanYearsInput.value = "";

            clearFieldValidation(
                loanYearsInput
            );
        }

        if (
            !Number.isFinite(principal) ||
            principal <= 0
        ) {
            setFieldError(
                principalInput,
                "Enter a principal amount greater than zero."
            );

            firstInvalidField =
                firstInvalidField ||
                principalInput;
        } else if (principal > MAX_PRINCIPAL) {
            setFieldError(
                principalInput,
                "Principal amount must not exceed ₱9,999,999,999.99."
            );

            firstInvalidField =
                firstInvalidField ||
                principalInput;
        } else if (
            hasMoreThanTwoDecimals(
                principalInput.value
            )
        ) {
            setFieldError(
                principalInput,
                "Principal amount may contain only two decimal places."
            );

            firstInvalidField =
                firstInvalidField ||
                principalInput;
        } else {
            principalInput.value =
                roundMoney(principal).toFixed(2);

            setFieldValid(principalInput);
        }

        if (
            ![180, 360].includes(term.days) ||
            ![10, 20].includes(term.interestRate)
        ) {
            setFieldError(
                loanTermInput,
                "Select a valid loan term."
            );

            firstInvalidField =
                firstInvalidField ||
                loanTermInput;
        } else {
            setFieldValid(loanTermInput);
        }

        updateLoanSummary();

        const attachmentMissing =
            selectedAttachments.length === 0;

        if (attachmentMissing) {
            setAttachmentError(
                "At least one supporting document is required."
            );
        } else {
            clearAttachmentValidation();
        }

        if (firstInvalidField) {
            firstInvalidField.focus();

            firstInvalidField.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            return false;
        }

        if (attachmentMissing) {
            attachmentUploadBox.scrollIntoView({
                behavior: "smooth",
                block: "center"
            });

            return false;
        }

        return true;
    }

    /* =====================================================
       CONFIRMATION
    ===================================================== */

    async function confirmApplication() {
        const values =
            calculateLoanValues();

        const memberName =
            normalizeText(memberNameInput.value);

        const memberCode =
            normalizeText(
                memberNameInput.dataset.selectedCode
            );

        const purpose =
            normalizeText(purposeInput.value);

        if (typeof Swal === "undefined") {
            return window.confirm(
                "Are you sure the Monetary Loan details are final?"
            );
        }

        const confirmation =
            await Swal.fire({
                icon: "question",
                title:
                    "Are you sure the details are final?",
                html:
                    '<div style="text-align:left; line-height:1.55;">' +
                        '<p style="margin:0 0 7px;">' +
                            "<strong>Member:</strong> " +
                            escapeHTML(memberName) +
                            (
                                memberCode
                                    ? " (" +
                                        escapeHTML(memberCode) +
                                        ")"
                                    : ""
                            ) +
                        "</p>" +

                        '<p style="margin:0 0 7px;">' +
                            "<strong>Loan Type:</strong> " +
                            escapeHTML(purpose) +
                        "</p>" +

                        (
                            longTermLoanIsSelected()
                                ? '<p style="margin:0 0 7px;">' +
                                    "<strong>Loan Term:</strong> " +
                                    values.loanYears +
                                    (
                                        values.loanYears === 1
                                            ? " Year"
                                            : " Years"
                                    ) +
                                    " · " +
                                    values.paymentPeriods +
                                    (
                                        values.paymentPeriods === 1
                                            ? " Payment Period"
                                            : " Payment Periods"
                                    ) +
                                "</p>"
                                : ""
                        ) +

                        '<p style="margin:0 0 7px;">' +
                            "<strong>Principal:</strong> " +
                            escapeHTML(
                                formatMoney(values.principal)
                            ) +
                        "</p>" +

                        '<p style="margin:0 0 7px;">' +
                            "<strong>Interest Rate:</strong> " +
                            values.interestRate +
                            (
                                longTermLoanIsSelected()
                                    ? "% every "
                                    : "% for "
                            ) +
                            values.days +
                            " days" +
                        "</p>" +

                        '<p style="margin:0 0 12px;">' +
                            "<strong>Payoff Total:</strong> " +
                            escapeHTML(
                                formatMoney(
                                    values.scheduledTotal
                                )
                            ) +
                        "</p>" +

                        '<small style="display:block;">' +
                            "This application will be saved as Pending. " +
                            "Only a Full Lending Access user can Accept or Reject it. " +
                            "Prorated interest and the due date will begin on acceptance." +
                        "</small>" +
                    "</div>",
                showCancelButton: true,
                confirmButtonText: "YES, SAVE",
                cancelButtonText: "REVIEW AGAIN",
                confirmButtonColor: "#8ca15b",
                cancelButtonColor: "#777777",
                reverseButtons: true,
                focusCancel: true,
                allowOutsideClick: false
            });

        return confirmation.isConfirmed;
    }

    /* =====================================================
       SAVE APPLICATION
    ===================================================== */

    async function submitLoanApplication() {
        if (formIsSubmitting) {
            return;
        }

        formIsSubmitting = true;

        setButtonLoading(saveButton, true);

        try {
            /*
            * Isabay ang validated attachments sa tunay na file input.
            * Kapareho ito ng working upload flow ng Product Loan.
            */
            rebuildAttachmentInput();

            const formData =
                new FormData(loanForm);

            /*
             * PHP pa rin ang final authority.
             * Uulitin nito ang calculations at pipilitin
             * nitong Pending ang initial status.
             */

            formData.set("status", "Pending");

            const response =
                await fetch(
                    "save_monetary_loan.php",
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

            const result =
                await readJSONResponse(response);

            if (
                !response.ok ||
                !result.success
            ) {
                throw new Error(
                    result.message ||
                    "Unable to save the Monetary Loan application."
                );
            }

            if (typeof Swal !== "undefined") {
                await Swal.fire({
                    icon: "success",
                    title: "Application saved",
                    text:
                        result.message ||
                        "The Monetary Loan application was saved as Pending.",
                    confirmButtonText: "OK",
                    confirmButtonColor: "#8ca15b",
                    allowOutsideClick: false
                });
            } else {
                window.alert(
                    result.message ||
                    "The Monetary Loan application was saved."
                );
            }

            if (
                typeof bootstrap !== "undefined" &&
                bootstrap.Modal
            ) {
                const modalInstance =
                    bootstrap.Modal.getInstance(
                        modalElement
                    );

                if (modalInstance) {
                    modalInstance.hide();
                }
            }

            window.location.reload();
        } catch (error) {
            console.error(
                "Save Monetary Loan error:",
                error
            );

            await showAlert(
                "error",
                "Unable to save",
                error.message ||
                "Something went wrong while saving the application."
            );
        } finally {
            formIsSubmitting = false;

            setButtonLoading(saveButton, false);
        }
    }

    loanForm.addEventListener(
        "submit",
        async function (event) {
            event.preventDefault();

            if (!validateLoanForm()) {
                return;
            }

            const confirmed =
                await confirmApplication();

            if (!confirmed) {
                return;
            }

            await submitLoanApplication();
        }
    );

    /* =====================================================
       RESET AND MODAL EVENTS
    ===================================================== */

    function resetLoanForm() {
        loanForm.reset();

        clearSelectedMember();

        hideMemberResults();

        clearAllValidation();

        clearAttachmentValidation();

        selectedAttachments = [];

        rebuildAttachmentInput();

        renderAttachments();

        interestRateInput.value = "";

        scheduledInterestInput.value = "0.00";

        scheduledTotalInput.value = "0.00";

        formIsSubmitting = false;

        setButtonLoading(saveButton, false);

        updateLoanYearsField();
        updateLoanSummary();
    }

    modalElement.addEventListener(
        "show.bs.modal",
        function () {
            resetLoanForm();
        }
    );

    modalElement.addEventListener(
        "shown.bs.modal",
        function () {
            memberNameInput.focus();
        }
    );

    modalElement.addEventListener(
        "hidden.bs.modal",
        function () {
            if (memberSearchController) {
                memberSearchController.abort();
            }

            window.clearTimeout(
                memberSearchTimer
            );

            resetLoanForm();
        }
    );

    /* =====================================================
       INITIAL DISPLAY
    ===================================================== */

    renderAttachments();

    updateLoanYearsField();
    updateLoanSummary();
});

/* =========================================================
   MONETARY LOAN ARCHIVE HANDLER
========================================================= */

document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    const pageData =
        document.getElementById(
            "monetaryLoanPageData"
        );

    if (!pageData) {
        return;
    }

    const csrfToken =
        String(
            pageData.dataset.csrfToken
            || ""
        );

    const lendingPermission =
        String(
            pageData.dataset.lendingPermission
            || "limited"
        ).toLowerCase();


    /* =====================================================
       HELPERS
    ===================================================== */

    function formatLoanDisplayID(loanID) {

        return (
            "ML-" +
            String(loanID).padStart(
                6,
                "0"
            )
        );
    }


    async function readArchiveResponse(
        response
    ) {

        const responseText =
            await response.text();

        if (!responseText.trim()) {

            throw new Error(
                "The server returned an empty response."
            );
        }

        try {

            return JSON.parse(
                responseText
            );

        } catch (error) {

            console.error(
                "Invalid archive response:",
                responseText
            );

            throw new Error(
                "The server returned an invalid response."
            );
        }
    }


    function setArchiveButtonLoading(
        button,
        isLoading
    ) {

        if (!button) {
            return;
        }

        if (isLoading) {

            button.dataset.originalHtml =
                button.innerHTML;

            button.disabled = true;

            button.classList.add(
                "is-loading"
            );

            button.innerHTML =
                '<span ' +
                'class="spinner-border spinner-border-sm" ' +
                'role="status" ' +
                'aria-hidden="true">' +
                "</span>";

            return;
        }

        button.classList.remove(
            "is-loading"
        );

        if (
            button.dataset.originalHtml
        ) {

            button.innerHTML =
                button.dataset.originalHtml;

            delete button.dataset.originalHtml;
        }

        button.disabled = false;
    }


    async function showArchiveError(
        message
    ) {

        if (
            typeof Swal !==
            "undefined"
        ) {

            await Swal.fire({
                icon: "error",
                title: "Unable to archive",
                text: message,
                confirmButtonText: "OK",
                confirmButtonColor: "#91aa5b",
                allowOutsideClick: false
            });

            return;
        }

        window.alert(message);
    }


        async function confirmArchive(
        loanDisplayID,
        archiveReason
    ) {

        const reasonMessage =
            archiveReason === "Rejected"
                ? "This rejected application will be moved to the Archive module."
                : "This fully paid loan will be moved to the Archive module.";

        if (
            typeof Swal ===
            "undefined"
        ) {

            return window.confirm(
                "Archive " +
                loanDisplayID +
                "?\n\n" +
                reasonMessage +
                "\nAttachments and payment history will remain linked."
            );
        }

        const result =
            await Swal.fire({
                icon: "warning",
                title: "Archive Monetary Loan?",
                html:
                    "<strong>" +
                    loanDisplayID +
                    "</strong><br>" +
                    reasonMessage +
                    "<br>" +
                    "Attachments and payment history will remain linked.",

                showCancelButton: true,
                reverseButtons: true,

                cancelButtonText: "CANCEL",
                confirmButtonText: "ARCHIVE LOAN",

                customClass: {
                    popup: "monetary-loan-archive-popup"
                },

                cancelButtonColor: "#858585",
                confirmButtonColor: "#dc5151",

                allowOutsideClick: false,
                focusCancel: true
            });

        return result.isConfirmed;
    }


    /* =====================================================
       ARCHIVE REQUEST
    ===================================================== */

    async function archiveMonetaryLoan(
        button,
        row,
        loanID
    ) {

        const loanDisplayID =
            formatLoanDisplayID(
                loanID
            );

        const status =
            String(
                row.dataset.status
                || ""
            );

        const remarks =
            String(
                row.dataset.remarks
                || ""
            );

        const archiveReason =
            status === "Rejected"
                ? "Rejected"
                : remarks;

        const confirmed =
            await confirmArchive(
                loanDisplayID,
                archiveReason
            );

        if (!confirmed) {
            return;
        }

        setArchiveButtonLoading(
            button,
            true
        );

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
                    "archive_monetary_loan.php",
                    {
                        method: "POST",

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                        body: formData,

                        credentials:
                            "same-origin"
                    }
                );

            const result =
                await readArchiveResponse(
                    response
                );

            if (
                !response.ok ||
                !result.success
            ) {

                throw new Error(
                    result.message ||
                    "The Monetary Loan could not be archived."
                );
            }

            if (
                typeof Swal !==
                "undefined"
            ) {

                await Swal.fire({
                    icon: "success",
                    title:
                        "Monetary Loan archived",
                    text:
                        result.message,
                    confirmButtonText:
                        "OK",
                    confirmButtonColor:
                        "#91aa5b",
                    allowOutsideClick:
                        false
                });

            } else {

                window.alert(
                    result.message
                );
            }

            window.location.reload();

        } catch (error) {

            console.error(
                "Archive Monetary Loan error:",
                error
            );

            await showArchiveError(
                error.message ||
                "Something went wrong while archiving the Monetary Loan."
            );

            setArchiveButtonLoading(
                button,
                false
            );
        }
    }


    /* =====================================================
       ARCHIVE BUTTON EVENT
    ===================================================== */

    document.addEventListener(
        "click",
        async function (event) {

            const archiveButton =
                event.target.closest(
                    ".monetary-loan-archive-btn"
                );

            if (!archiveButton) {
                return;
            }

            event.preventDefault();

            if (
                archiveButton.disabled ||
                archiveButton.classList.contains(
                    "is-loading"
                )
            ) {
                return;
            }

            if (
                lendingPermission !==
                "full"
            ) {

                await showArchiveError(
                    "Full Lending Access is required to archive a Monetary Loan."
                );

                return;
            }

            const row =
                archiveButton.closest(
                    ".monetary-loan-row"
                );

            const loanID =
                Number.parseInt(
                    archiveButton.dataset.loanId
                    || "",
                    10
                );

            if (
                !row ||
                !Number.isInteger(loanID) ||
                loanID <= 0
            ) {

                await showArchiveError(
                    "The selected Monetary Loan is invalid."
                );

                return;
            }

            await archiveMonetaryLoan(
                archiveButton,
                row,
                loanID
            );
        }
    );
});