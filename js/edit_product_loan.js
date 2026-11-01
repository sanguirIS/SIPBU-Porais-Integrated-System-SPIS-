document.addEventListener("DOMContentLoaded", function () {

    "use strict";


    const modalElement =
        document.getElementById(
            "editProductLoanModal"
        );


    const editForm =
        document.getElementById(
            "editProductLoanForm"
        );


    if (
        !modalElement ||
        !editForm
    ) {

        return;

    }


    const productLoanPageData =
        document.getElementById(
            "productLoanPageData"
        );


    const csrfToken =
        String(
            productLoanPageData?.dataset
                .csrfToken || ""
        );


    function reloadAtCurrentProductLoan() {

        const currentLoanID =
            Number(
                currentLoanData?.loan?.product_loan_id
            );


        if (
            Number.isInteger(currentLoanID) &&
            currentLoanID > 0
        ) {

            try {

                sessionStorage.setItem(
                    "spisProductLoanFocusedRow",
                    String(currentLoanID)
                );

            }
            catch (error) {

                console.warn(
                    "Unable to remember the Product Loan row.",
                    error
                );

            }

        }


        window.location.reload();

    }


    /* =====================================================
       MODAL AND FORM ELEMENTS
    ===================================================== */

    const loanIDInput =
        document.getElementById(
            "editProductLoanID"
        );


    const displayIDInput =
        document.getElementById(
            "editProductLoanDisplayID"
        );


    const memberIDInput =
        document.getElementById(
            "editProductLoanMemberID"
        );


    const originalMemberIDInput =
        document.getElementById(
            "editProductLoanOriginalMemberID"
        );


    const memberNameInput =
        document.getElementById(
            "editProductLoanMemberName"
        );


    const memberSuggestions =
        document.getElementById(
            "editProductLoanMemberSuggestions"
        );


    const existingStatusInput =
        document.getElementById(
            "editProductLoanExistingStatus"
        );


    const statusInput =
        document.getElementById(
            "editProductLoanStatus"
        );


    const statusBadge =
        document.getElementById(
            "editProductLoanStatusBadge"
        );


    const statusPermissionNote =
        document.getElementById(
            "editProductLoanStatusPermission"
        );


    const applicationDateInput =
        document.getElementById(
            "editProductLoanApplicationDate"
        );


    const deliveredDateInput =
        document.getElementById(
            "editProductLoanDeliveredDate"
        );


    const dueDateInput =
        document.getElementById(
            "editProductLoanDueDate"
        );


    const termInput =
        document.getElementById(
            "editProductLoanTerm"
        );


    const rejectionBlock =
        document.getElementById(
            "editProductLoanRejectionBlock"
        );


    const rejectionReasonInput =
        document.getElementById(
            "editProductLoanRejectionReason"
        );


    const informationText =
        document.getElementById(
            "editProductLoanInformationText"
        );


    const modalTitle =
        document.getElementById(
            "editProductLoanModalTitle"
        );


    /* =====================================================
       PRODUCT ELEMENTS
    ===================================================== */

    const productArea =
        document.getElementById(
            "editProductLoanProductArea"
        );


    const productBuilder =
        document.getElementById(
            "editProductLoanProductBuilder"
        );


    const productSelectionSection =
        modalElement.querySelector(
            ".edit-product-loan-selection-section"
        );


    const productRequestSection =
        modalElement.querySelector(
            ".edit-product-loan-request-section"
        );


    const productRequestNote =
        modalElement.querySelector(
            ".edit-product-loan-request-title p"
        );


    const productNote =
        document.getElementById(
            "editProductLoanProductNote"
        );


    const productSearchInput =
        document.getElementById(
            "editProductLoanProductSearch"
        );


    const productCatalogCount =
        document.getElementById(
            "editProductLoanCatalogCount"
        );


    const productCatalogNoMatch =
        document.getElementById(
            "editProductLoanCatalogNoMatch"
        );


    const productCatalogTiles =
        Array.from(
            modalElement.querySelectorAll(
                ".edit-product-loan-catalog-tile"
            )
        );


    const selectedProductPanel =
        document.getElementById(
            "editProductLoanSelectedProductPanel"
        );


    const selectedProductIDInput =
        document.getElementById(
            "editProductLoanSelectedProductID"
        );


    const selectedProductNameDisplay =
        document.getElementById(
            "editProductLoanSelectedProductName"
        );


    const selectedProductPriceDisplay =
        document.getElementById(
            "editProductLoanSelectedProductPrice"
        );

    const selectedProductReservedDisplay =
        document.getElementById(
            "editProductLoanSelectedProductReserved"
        );


    const quantityInput =
        document.getElementById(
            "editProductLoanQuantity"
        );


    const quantityMinusButton =
        document.getElementById(
            "editProductLoanQuantityMinus"
        );


    const quantityPlusButton =
        document.getElementById(
            "editProductLoanQuantityPlus"
        );


    const quantityMessage =
        document.getElementById(
            "editProductLoanQuantityMessage"
        );


    const addItemButton =
        document.getElementById(
            "editProductLoanAddItemBtn"
        );


    const itemsBody =
        document.getElementById(
            "editProductLoanItemsBody"
        );


    const requestCountDisplay =
        document.getElementById(
            "editProductLoanRequestCount"
        );


    const totalQuantityDisplay =
        document.getElementById(
            "editProductLoanTotalQuantity"
        );


    const cartPrincipalDisplay =
        document.getElementById(
            "editProductLoanCartPrincipal"
        );


    const itemsHiddenInput =
        document.getElementById(
            "editProductLoanItemsInput"
        );


    /* =====================================================
       ATTACHMENT ELEMENTS
    ===================================================== */

    const attachmentInput =
        document.getElementById(
            "editProductLoanAttachments"
        );


    const attachmentList =
        document.getElementById(
            "editProductLoanAttachmentList"
        );


    const uploadBox =
        document.getElementById(
            "editProductLoanUploadBox"
        );


    /* =====================================================
       FINANCIAL SUMMARY ELEMENTS
    ===================================================== */

    const principalDisplay =
        document.getElementById(
            "editProductLoanPrincipal"
        );


    const interestRateDisplay =
        document.getElementById(
            "editProductLoanInterestRate"
        );


    const periodDisplay =
        document.getElementById(
            "editProductLoanPeriod"
        );


    const scheduledInterestDisplay =
        document.getElementById(
            "editProductLoanScheduledInterest"
        );


    const scheduledTotalDisplay =
        document.getElementById(
            "editProductLoanScheduledTotal"
        );


    const dailyPaymentDisplay =
        document.getElementById(
            "editProductLoanDailyPayment"
        );


    const monthlyPaymentDisplay =
        document.getElementById(
            "editProductLoanMonthlyPayment"
        );


    const totalPaymentsDisplay =
        document.getElementById(
            "editProductLoanTotalPayments"
        );


    const currentInterestDisplay =
        document.getElementById(
            "editProductLoanCurrentInterest"
        );


    const currentBalanceDisplay =
        document.getElementById(
            "editProductLoanCurrentBalance"
        );


    /* =====================================================
       PAYMENT ELEMENTS
    ===================================================== */

    const paymentSection =
        document.getElementById(
            "editProductLoanPaymentSection"
        );


    const paymentEntry =
        paymentSection?.querySelector(
            ".product-loan-payment-entry"
        );


    const paymentPrincipalDisplay =
        document.getElementById(
            "editPaymentPrincipal"
        );


    const paymentUnpaidInterestDisplay =
        document.getElementById(
            "editPaymentUnpaidInterest"
        );


    const paymentElapsedDaysDisplay =
        document.getElementById(
            "editPaymentElapsedDays"
        );


    const paymentNewInterestDisplay =
        document.getElementById(
            "editPaymentNewInterest"
        );


    const paymentPayoffDisplay =
        document.getElementById(
            "editPaymentPayoffBalance"
        );


    const paymentDateInput =
        document.getElementById(
            "editProductLoanPaymentDate"
        );


    const paymentAmountInput =
        document.getElementById(
            "editProductLoanPaymentAmount"
        );


    const paymentNotesInput =
        document.getElementById(
            "editProductLoanPaymentNotes"
        );


    const recordPaymentButton =
        document.getElementById(
            "recordProductLoanPaymentBtn"
        );


    const paymentHistoryBody =
        document.getElementById(
            "editProductLoanPaymentHistory"
        );


    const saveChangesButton =
        document.getElementById(
            "saveProductLoanChangesBtn"
        );


    /* =====================================================
       STATE
    ===================================================== */

    let currentLoanData =
        null;


    let editItems =
        [];


    let selectedProduct =
        null;

    let existingAttachments =
        [];


    let newAttachments =
        [];


    let removedAttachmentIDs =
        [];


    let memberSearchTimer =
        null;


    let memberSearchController =
        null;


    let isSubmitting =
        false;


    const MAX_PRODUCTS =
        50;


    const MAX_ATTACHMENTS =
        10;


    const MAX_FILE_SIZE =
        10 * 1024 * 1024;


    const MAX_TOTAL_FILE_SIZE =
        50 * 1024 * 1024;


    const ALLOWED_FILE_TYPES = [

        "image/jpeg",

        "image/png",

        "application/pdf",

        "application/x-pdf",

        "application/msword",

        "application/x-ole-storage",

        "application/CDFV2",

        "application/zip",

        "application/vnd.openxmlformats-officedocument.wordprocessingml.document"

    ];


    const ALLOWED_FILE_EXTENSIONS = [

        "jpg",

        "jpeg",

        "png",

        "pdf",

        "doc",

        "docx"

    ];


    /* =====================================================
       GENERAL HELPERS
    ===================================================== */

    function formatMoney(value) {

        return new Intl.NumberFormat(
            "en-PH",
            {

                style:
                    "currency",

                currency:
                    "PHP",

                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2

            }
        ).format(
            Number(value) || 0
        );

    }


    function roundMoney(value) {

        return Math.round(
            (
                Number(value) +
                Number.EPSILON
            ) * 100
        ) / 100;

    }


    function normalizeText(value) {

        return String(
            value || ""
        )
        .trim()
        .replace(
            /\s+/g,
            " "
        );

    }


    function formatFileSize(bytes) {

        const fileSize =
            Number(bytes) || 0;


        if (
            fileSize < 1024
        ) {

            return fileSize +
                " B";

        }


        if (
            fileSize <
            1024 * 1024
        ) {

            return (
                fileSize / 1024
            ).toFixed(
                1
            ) +
            " KB";

        }


        return (
            fileSize /
            (
                1024 * 1024
            )
        ).toFixed(
            1
        ) +
        " MB";

    }


    function formatDays(value) {

        const days =
            Math.max(
                0,
                Number(value) || 0
            );


        return days +
        (
            days === 1
                ?
                " Day"
                :
                " Days"
        );

    }


    function getFileExtension(fileName) {

        const normalizedName =
            String(
                fileName || ""
            )
            .trim()
            .toLowerCase();


        const lastDotIndex =
            normalizedName.lastIndexOf(
                "."
            );


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


    function getAttachmentIconClass(
        fileName,
        mimeType = ""
    ) {

        const extension =
            getFileExtension(
                fileName
            );


        if (
            extension ===
                "pdf" ||
            mimeType ===
                "application/pdf"
        ) {

            return "bi bi-file-earmark-pdf";

        }


        if (
            extension ===
                "doc" ||
            extension ===
                "docx" ||
            mimeType ===
                "application/msword" ||
            mimeType ===
                "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
        ) {

            return "bi bi-file-earmark-word";

        }


        return "bi bi-file-earmark-image";

    }


    function isAllowedAttachment(file) {

        const extension =
            getFileExtension(
                file.name
            );


        if (
            !ALLOWED_FILE_EXTENSIONS
                .includes(
                    extension
                )
        ) {

            return false;

        }


        if (
            !file.type ||
            file.type ===
                "application/octet-stream"
        ) {

            return true;

        }


        return ALLOWED_FILE_TYPES
            .includes(
                file.type
            );

    }


    function calculateNewAttachmentSize() {

        return newAttachments.reduce(
            function (
                total,
                file
            ) {

                return total +
                    Number(
                        file.size || 0
                    );

            },
            0
        );

    }


    function getRemainingExistingAttachmentCount() {

        return existingAttachments.reduce(
            function (
                total,
                attachment
            ) {

                const attachmentID =
                    Number(
                        attachment
                            .attachment_id
                    );


                return removedAttachmentIDs
                    .includes(
                        attachmentID
                    )
                    ?
                    total
                    :
                    total + 1;

            },
            0
        );

    }


    function getFinalAttachmentCount() {

        return (
            getRemainingExistingAttachmentCount()
            +
            newAttachments.length
        );

    }


    function showAlert(
        icon,
        title,
        text
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
                    text,

                confirmButtonText:
                    "OK",

                confirmButtonColor:
                    "#8ca15b"

            });

        }


        window.alert(
            title +
            "\n\n" +
            text
        );


        return Promise.resolve();

    }


    function setButtonBusy(
        button,
        busy
    ) {

        if (
            !button
        ) {

            return;

        }


        button.disabled =
            Boolean(
                busy
            );


        button.setAttribute(
            "aria-busy",
            busy
                ?
                "true"
                :
                "false"
        );

    }


    function clearValidation(input) {

        if (
            !input
        ) {

            return;

        }


        input.classList.remove(
            "is-invalid",
            "is-valid"
        );


        const field =
            input.closest(
                ".product-loan-field"
            );


        const oldError =
            field?.querySelector(
                ".product-loan-field-error"
            );


        if (
            oldError
        ) {

            oldError.remove();

        }

    }


    function setFieldError(
        input,
        message
    ) {

        if (
            !input
        ) {

            return;

        }


        clearValidation(
            input
        );


        input.classList.add(
            "is-invalid"
        );


        const field =
            input.closest(
                ".product-loan-field"
            );


        if (
            !field
        ) {

            return;

        }


        const error =
            document.createElement(
                "small"
            );


        error.className =
            "product-loan-field-error";


        error.textContent =
            message;


        field.appendChild(
            error
        );

    }


    function clearAllValidation() {

        editForm
            .querySelectorAll(
                ".product-loan-input"
            )
            .forEach(
                function (input) {

                    clearValidation(
                        input
                    );

                }
            );

    }


    function closeMemberSuggestions() {

        memberSuggestions.innerHTML =
            "";


        memberSuggestions.classList
            .remove(
                "show"
            );

    }


    /* =====================================================
       STATUS BADGE
    ===================================================== */

    function updateStatusBadge(status) {

        statusBadge.classList.remove(
            "product-loan-status-pending",
            "product-loan-status-delivered",
            "product-loan-status-rejected"
        );


        const icon =
            statusBadge.querySelector(
                "i"
            );


        const text =
            statusBadge.querySelector(
                "span"
            );


        if (
            status ===
            "Delivered"
        ) {

            statusBadge.classList.add(
                "product-loan-status-delivered"
            );


            icon.className =
                "bi bi-box-seam";

        }
        else if (
            status ===
            "Rejected"
        ) {

            statusBadge.classList.add(
                "product-loan-status-rejected"
            );


            icon.className =
                "bi bi-x-circle";

        }
        else {

            statusBadge.classList.add(
                "product-loan-status-pending"
            );


            icon.className =
                "bi bi-clock";

        }


        text.textContent =
            status.toUpperCase();

    }


    /* =====================================================
       MEMBER SEARCH
    ===================================================== */

    function renderMemberSuggestions(
        members
    ) {

        memberSuggestions.innerHTML =
            "";


        if (
            !Array.isArray(members) ||
            members.length === 0
        ) {

            const empty =
                document.createElement(
                    "div"
                );


            empty.className =
                "product-loan-member-suggestion";


            empty.style.cursor =
                "default";


            empty.textContent =
                "No eligible Regular member found";


            memberSuggestions.appendChild(
                empty
            );


            memberSuggestions.classList
                .add(
                    "show"
                );


            return;

        }


        members.forEach(
            function (member) {

                const suggestion =
                    document.createElement(
                        "button"
                    );


                suggestion.type =
                    "button";


                suggestion.className =
                    "product-loan-member-suggestion";


                const memberIsCurrent =
                    Number(
                        member.member_id
                    ) ===
                    Number(
                        originalMemberIDInput
                            .value
                    );


                const hasAnotherActiveLoan =
                (
                    member.has_active_loan ===
                        true
                    ||
                    Number(
                        member.has_active_loan
                    ) === 1
                )
                &&
                !memberIsCurrent;


                const details =
                    document.createElement(
                        "div"
                    );


                const name =
                    document.createElement(
                        "strong"
                    );


                const memberCode =
                    document.createElement(
                        "span"
                    );


                name.textContent =
                    member.full_name;


                memberCode.textContent =
                    (
                        member.member_code ||
                        "No member code"
                    )
                    +
                    (
                        hasAnotherActiveLoan
                            ?
                            " • Has active Product Loan"
                            :
                            " • Eligible"
                    );


                details.appendChild(
                    name
                );


                details.appendChild(
                    memberCode
                );


                suggestion.appendChild(
                    details
                );


                if (
                    hasAnotherActiveLoan
                ) {

                    suggestion.style.opacity =
                        "0.72";

                }


                suggestion.addEventListener(
                    "click",
                    function () {

                        if (
                            hasAnotherActiveLoan
                        ) {

                            closeMemberSuggestions();


                            showAlert(
                                "warning",
                                "Existing Product Loan",
                                member.full_name +
                                " still has an active Product Loan."
                            );


                            return;

                        }


                        memberIDInput.value =
                            member.member_id;


                        memberNameInput.value =
                            member.full_name;


                        memberNameInput
                            .dataset
                            .selectedName =
                            member.full_name;


                        clearValidation(
                            memberNameInput
                        );


                        closeMemberSuggestions();

                    }
                );


                memberSuggestions
                    .appendChild(
                        suggestion
                    );

            }
        );


        memberSuggestions.classList
            .add(
                "show"
            );

    }


    async function searchMembers(
        searchValue
    ) {

        if (
            memberSearchController
        ) {

            memberSearchController
                .abort();

        }


        memberSearchController =
            new AbortController();


        try {

            const response =
                await fetch(
                    "search_product_loan_members.php?query="
                    +
                    encodeURIComponent(
                        searchValue
                    ),
                    {
                        method: "GET",

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                        cache: "no-store",

                        signal:
                            memberSearchController
                                .signal
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

            }
            catch (jsonError) {

                throw new Error(
                    "The member search returned an invalid response."
                );

            }


            if (
                !response.ok ||
                !result.success
            ) {

                throw new Error(
                    result.message ||
                    "Unable to search members."
                );

            }


            renderMemberSuggestions(
                result.members
            );

        }
        catch (error) {

            if (
                error.name ===
                "AbortError"
            ) {

                return;

            }


            console.error(
                "Edit member search error:",
                error
            );


            closeMemberSuggestions();

        }

    }


    memberNameInput.addEventListener(
        "input",
        function () {

            clearValidation(
                memberNameInput
            );


            const currentValue =
                normalizeText(
                    memberNameInput.value
                );


            const selectedName =
                normalizeText(
                    memberNameInput.dataset
                        .selectedName
                );


            if (
                currentValue.toLowerCase()
                !==
                selectedName.toLowerCase()
            ) {

                memberIDInput.value =
                    "";

            }


            window.clearTimeout(
                memberSearchTimer
            );


            if (
                currentValue.length <
                3
            ) {

                closeMemberSuggestions();

                return;

            }


            memberSearchTimer =
                window.setTimeout(
                    function () {

                        searchMembers(
                            currentValue
                        );

                    },
                    300
                );

        }
    );


    document.addEventListener(
        "click",
        function (event) {

            if (
                !event.target.closest(
                    "#editProductLoanModal "
                    +
                    ".product-loan-member-wrapper"
                )
            ) {

                closeMemberSuggestions();

            }

        }
    );


    /* =====================================================
       PRODUCT ITEMS
    ===================================================== */

    function getSelectedTerm() {

        const option =
            termInput.options[
                termInput.selectedIndex
            ];


        if (
            !option ||
            !option.value
        ) {

            return {
                days: 0,
                rate: 0
            };

        }


        return {

            days:
                Number(
                    option.value
                ),

            rate:
                Number(
                    option.dataset
                        .interest
                )

        };

    }


    function calculateEditPrincipal() {

        return roundMoney(

            editItems.reduce(

                function (
                    total,
                    item
                ) {

                    return total +
                        Number(
                            item.line_total ||
                            0
                        );

                },

                0

            )

        );

    }


    function updatePendingSummary() {

        if (
            !currentLoanData ||
            currentLoanData.loan.status
            !==
            "Pending"
        ) {

            return;

        }


        const principal =
            calculateEditPrincipal();


        const term =
            getSelectedTerm();


        const scheduledInterest =
            roundMoney(
                principal *
                (
                    term.rate /
                    100
                )
            );


        const scheduledTotal =
            roundMoney(
                principal +
                scheduledInterest
            );


        principalDisplay.textContent =
            formatMoney(
                principal
            );


        interestRateDisplay.textContent =
            term.rate > 0
                ?
                term.rate + "%"
                :
                "—";


        periodDisplay.textContent =
            term.days > 0
                ?
                term.days +
                " Days"
                :
                "—";


        scheduledInterestDisplay
            .textContent =
            formatMoney(
                scheduledInterest
            );


        scheduledTotalDisplay
            .textContent =
            formatMoney(
                scheduledTotal
            );


        const validPaymentEstimate =
            principal > 0 &&
            term.days > 0 &&
            Number.isFinite(scheduledTotal) &&
            scheduledTotal > 0;


        dailyPaymentDisplay.textContent =
            validPaymentEstimate
                ? formatMoney(scheduledTotal / term.days)
                : "—";


        monthlyPaymentDisplay.textContent =
            validPaymentEstimate
                ? formatMoney(scheduledTotal / (term.days / 30))
                : "—";


        totalPaymentsDisplay
            .textContent =
            formatMoney(
                0
            );


        currentInterestDisplay
            .textContent =
            formatMoney(
                0
            );


        currentBalanceDisplay
            .textContent =
            "—";


        itemsHiddenInput.value =
            JSON.stringify(
                editItems
            );

    }


    function getCatalogTileData(tile) {

        if (!tile) {
            return null;
        }


        const loanProductID =
            Number(
                tile.dataset
                    .loanProductId
            );


        const productName =
            normalizeText(
                tile.dataset
                    .productName
            );


        const loanPrice =
            roundMoney(
                Number(
                    tile.dataset
                        .loanPrice
                )
            );


        const reservedQuantity =
            Number(
                tile.dataset
                    .reservedQuantity
            );


        if (
            !Number.isInteger(
                loanProductID
            )
            ||
            loanProductID <= 0
            ||
            !productName
            ||
            !Number.isFinite(
                loanPrice
            )
            ||
            loanPrice <= 0
            ||
            !Number.isInteger(
                reservedQuantity
            )
            ||
            reservedQuantity < 0
        ) {

            return null;
        }


        return {

            loan_product_id:
                loanProductID,

            product_name:
                productName,

            loan_price:
                loanPrice,

            reserved_quantity:
                reservedQuantity

        };
    }


    function clearSelectedCatalogTile() {

        productCatalogTiles
            .forEach(
                function (tile) {

                    tile.classList
                        .remove(
                            "is-selected"
                        );

                }
            );

    }


    function resetProductSelection() {

        selectedProduct =
            null;


        if (
            selectedProductIDInput
        ) {

            selectedProductIDInput.value =
                "";
        }


        if (
            selectedProductNameDisplay
        ) {

            selectedProductNameDisplay
                .textContent =
                "—";
        }


        if (
            selectedProductPriceDisplay
        ) {

            selectedProductPriceDisplay
                .textContent =
                "₱0.00";
        }


        if (
            selectedProductReservedDisplay
        ) {

            selectedProductReservedDisplay
                .textContent =
                "0";
        }


        if (
            quantityInput
        ) {

            quantityInput.value =
                "1";

            quantityInput.removeAttribute(
                "max"
            );
        }


        if (
            selectedProductPanel
        ) {

            selectedProductPanel.hidden =
                true;
        }


        clearSelectedCatalogTile();

        refreshQuantityValidation();
    }


    function selectCatalogProduct(
        tile
    ) {

        const product =
            getCatalogTileData(
                tile
            );


        if (!product) {

            showAlert(
                "warning",
                "Product unavailable",
                "This Product List item could not be selected."
            );

            return;
        }


        const alreadyAdded =
            editItems.some(
                function (item) {

                    return (
                        Number(
                            item.loan_product_id
                        )
                        ===
                        product.loan_product_id
                    );
                }
            );


        if (
            alreadyAdded
        ) {

            showAlert(
                "warning",
                "Product already added",
                "This product is already in Requested Products. Use the + or − controls in the cart to adjust its quantity."
            );

            return;
        }


        selectedProduct =
            product;


        clearSelectedCatalogTile();


        tile.classList.add(
            "is-selected"
        );


        if (
            selectedProductIDInput
        ) {

            selectedProductIDInput.value =
                String(
                    product.loan_product_id
                );
        }


        if (
            selectedProductNameDisplay
        ) {

            selectedProductNameDisplay
                .textContent =
                product.product_name;
        }


        if (
            selectedProductPriceDisplay
        ) {

            selectedProductPriceDisplay
                .textContent =
                formatMoney(
                    product.loan_price
                );
        }


        if (
            selectedProductReservedDisplay
        ) {

            selectedProductReservedDisplay
                .textContent =
                String(
                    product.reserved_quantity
                );
        }


        if (
            quantityInput
        ) {

            quantityInput.min =
                "1";

            quantityInput.removeAttribute(
                "max"
            );

            quantityInput.value =
                "1";
        }


        if (
            selectedProductPanel
        ) {

            selectedProductPanel.hidden =
                false;
        }


        refreshQuantityValidation();
    }


    function updateCatalogSearch() {

        const query =
            normalizeText(
                productSearchInput
                    ?
                    productSearchInput.value
                    :
                    ""
            )
            .toLowerCase();


        let visibleCount =
            0;


        productCatalogTiles
            .forEach(
                function (tile) {

                    const productName =
                        normalizeText(
                            tile.dataset
                                .productName
                        )
                        .toLowerCase();


                    const matches =
                        !query
                        ||
                        productName
                            .includes(
                                query
                            );


                    tile.hidden =
                        !matches;


                    if (
                        matches
                    ) {

                        visibleCount +=
                            1;

                    }

                }
            );


        if (
            productCatalogCount
        ) {

            productCatalogCount
                .textContent =
                String(
                    visibleCount
                );

        }


        if (
            productCatalogNoMatch
        ) {

            productCatalogNoMatch
                .hidden =
                !(
                    productCatalogTiles
                        .length > 0
                    &&
                    visibleCount === 0
                );

        }

    }


    function getSelectedQuantityState() {

        if (
            !quantityInput ||
            !selectedProduct
        ) {

            return {
                valid: false,
                quantity: 0,
                message: ""
            };
        }


        const rawValue =
            String(
                quantityInput.value ?? ""
            ).trim();


        if (
            rawValue === ""
        ) {

            return {
                valid: false,
                quantity: 0,
                message: "Enter a quantity."
            };
        }


        const quantity =
            Number(
                rawValue
            );


        if (
            !Number.isInteger(
                quantity
            )
            ||
            quantity <= 0
        ) {

            return {
                valid: false,
                quantity: quantity,
                message:
                    "Enter a whole number greater than 0."
            };
        }


        return {
            valid: true,
            quantity: quantity,
            message: ""
        };
    }


    function refreshQuantityValidation() {

        const state =
            getSelectedQuantityState();


        const quantityStepper =
            quantityInput
                ?
                quantityInput.closest(
                    ".edit-product-loan-quantity-stepper"
                )
                :
                null;


        if (
            quantityMessage
        ) {

            quantityMessage.textContent =
                state.message;

        }


        if (
            quantityStepper
        ) {

            quantityStepper.classList.toggle(
                "has-error",
                Boolean(
                    state.message
                )
            );

        }


        const numericQuantity =
            quantityInput
                ?
                Number(
                    quantityInput.value
                )
                :
                0;


        if (
            quantityMinusButton
        ) {

            quantityMinusButton.disabled =
                !selectedProduct
                ||
                !Number.isFinite(
                    numericQuantity
                )
                ||
                numericQuantity <= 1;

        }


        if (
            quantityPlusButton
        ) {

            quantityPlusButton.disabled =
                !selectedProduct
                ||
                !state.valid;
        }


        if (
            addItemButton
        ) {

            addItemButton.disabled =
                !selectedProduct
                ||
                !state.valid;

        }


        return state;

    }


    function changeEditItemQuantity(
        index,
        change
    ) {

        if (
            index < 0
            ||
            index >=
                editItems.length
            ||
            !currentLoanData
                ?.permission
                ?.can_edit_pending_details
        ) {

            return;
        }


        const item =
            editItems[
                index
            ];


        const itemProductID =
            Number(
                item.loan_product_id
            );


        if (
            !Number.isInteger(
                itemProductID
            )
            ||
            itemProductID <= 0
        ) {

            showAlert(
                "info",
                "Legacy requested product",
                "This older requested product is preserved as recorded. Remove it and select a Product List item if you need to replace it."
            );

            return;
        }


        const productExists =
            productCatalogTiles.some(
                function (tile) {

                    return (
                        Number(
                            tile.dataset
                                .loanProductId
                        )
                        ===
                        itemProductID
                    );
                }
            );


        if (!productExists) {

            showAlert(
                "warning",
                "Product unavailable",
                "This Product List item is no longer available for quantity changes."
            );

            return;
        }


        const currentQuantity =
            Number(
                item.quantity
            ) || 1;


        const newQuantity =
            currentQuantity +
            change;


        if (
            newQuantity < 1
        ) {

            return;
        }


        item.quantity =
            newQuantity;


        item.line_total =
            roundMoney(
                newQuantity *
                Number(
                    item.unit_price
                )
            );


        renderEditItems();
    }


    function refreshCatalogAddedState() {

        const addedIDs =
            new Set(

                editItems
                    .map(
                        function (item) {

                            return Number(
                                item.loan_product_id
                            );

                        }
                    )
                    .filter(
                        function (
                            productID
                        ) {

                            return (
                                Number.isInteger(
                                    productID
                                )
                                &&
                                productID > 0
                            );

                        }
                    )

            );


        productCatalogTiles
            .forEach(
                function (tile) {

                    const productID =
                        Number(
                            tile.dataset
                                .loanProductId
                        );


                    const isAdded =
                        addedIDs.has(
                            productID
                        );


                    tile.classList.toggle(
                        "is-added",
                        isAdded
                    );


                    if (
                        isAdded
                    ) {

                        tile.classList.remove(
                            "is-selected"
                        );

                    }

                }
            );

    }


    function updateRequestTotals() {

        const itemCount =
            editItems.length;


        const totalQuantity =
            editItems.reduce(
                function (
                    total,
                    item
                ) {

                    return (
                        total +
                        Number(
                            item.quantity ||
                            0
                        )
                    );

                },
                0
            );


        const principal =
            calculateEditPrincipal();


        if (
            requestCountDisplay
        ) {

            requestCountDisplay
                .textContent =
                itemCount
                +
                (
                    itemCount === 1
                        ?
                        " item"
                        :
                        " items"
                );

        }


        if (
            totalQuantityDisplay
        ) {

            totalQuantityDisplay
                .textContent =
                totalQuantity
                +
                (
                    totalQuantity === 1
                        ?
                        " unit"
                        :
                        " units"
                );

        }


        if (
            cartPrincipalDisplay
        ) {

            cartPrincipalDisplay
                .textContent =
                formatMoney(
                    principal
                );

        }

    }


    function showEmptyEditItemsRow() {

        const emptyRow =
            document.createElement(
                "tr"
            );


        emptyRow.className =
            "edit-product-loan-items-empty";


        emptyRow.innerHTML =
            '<td colspan="4">'
            +
                '<i class="bi bi-cart3"></i>'
            +
                '<strong>Request is empty</strong>'
            +
                '<span>Select a product then click Add to Request.</span>'
            +
            "</td>";


        itemsBody.appendChild(
            emptyRow
        );

    }


    function renderEditItems() {

        itemsBody.innerHTML =
            "";


        const editable =
            Boolean(
                currentLoanData
                    ?.permission
                    ?.can_edit_pending_details
            );


        if (
            editItems.length ===
            0
        ) {

            showEmptyEditItemsRow();

            refreshCatalogAddedState();

            updateRequestTotals();

            updatePendingSummary();

            return;

        }


        editItems.forEach(
            function (
                item,
                index
            ) {

                const row =
                    document.createElement(
                        "tr"
                    );


                /* PRODUCT */

                const productCell =
                    document.createElement(
                        "td"
                    );


                productCell.className =
                    "edit-product-loan-cart-product";


                const productName =
                    document.createElement(
                        "strong"
                    );


                productName.textContent =
                    item.product_name;


                const unitPrice =
                    document.createElement(
                        "small"
                    );


                unitPrice.textContent =
                    formatMoney(
                        item.unit_price
                    )
                    +
                    " each";


                productCell.appendChild(
                    productName
                );


                productCell.appendChild(
                    unitPrice
                );


                const itemProductID =
                    Number(
                        item.loan_product_id
                    );


                if (
                    !Number.isInteger(
                        itemProductID
                    )
                    ||
                    itemProductID <= 0
                ) {

                    const legacyBadge =
                        document.createElement(
                            "span"
                        );


                    legacyBadge.className =
                        "edit-product-loan-legacy-badge";


                    legacyBadge.textContent =
                        "Saved record";


                    productCell.appendChild(
                        legacyBadge
                    );

                }


                /* QUANTITY */

                const quantityCell =
                    document.createElement(
                        "td"
                    );


                quantityCell.className =
                    "edit-product-loan-cart-quantity";


                if (
                    editable
                    &&
                    Number.isInteger(
                        itemProductID
                    )
                    &&
                    itemProductID > 0
                ) {

                    const quantityControl =
                        document.createElement(
                            "div"
                        );


                    quantityControl.className =
                        "edit-product-loan-cart-quantity-control";


                    const minusButton =
                        document.createElement(
                            "button"
                        );


                    minusButton.type =
                        "button";


                    minusButton.textContent =
                        "−";


                    minusButton.disabled =
                        Number(
                            item.quantity
                        )
                        <=
                        1;


                    minusButton.addEventListener(
                        "click",
                        function () {

                            changeEditItemQuantity(
                                index,
                                -1
                            );

                        }
                    );


                    const quantityText =
                        document.createElement(
                            "span"
                        );


                    quantityText.textContent =
                        String(
                            item.quantity
                        );


                    const plusButton =
                        document.createElement(
                            "button"
                        );


                    plusButton.type =
                        "button";


                    plusButton.textContent =
                        "+";


                    const productExists =
                        productCatalogTiles.some(
                            function (tile) {

                                return (
                                    Number(
                                        tile.dataset
                                            .loanProductId
                                    )
                                    ===
                                    itemProductID
                                );
                            }
                        );


                    plusButton.disabled =
                        !productExists;


                    plusButton.addEventListener(
                        "click",
                        function () {

                            changeEditItemQuantity(
                                index,
                                1
                            );

                        }
                    );


                    quantityControl.appendChild(
                        minusButton
                    );


                    quantityControl.appendChild(
                        quantityText
                    );


                    quantityControl.appendChild(
                        plusButton
                    );


                    quantityCell.appendChild(
                        quantityControl
                    );

                }
                else {

                    quantityCell.textContent =
                        String(
                            item.quantity
                        );

                }


                /* AMOUNT */

                const amountCell =
                    document.createElement(
                        "td"
                    );


                amountCell.className =
                    "edit-product-loan-cart-amount";


                amountCell.textContent =
                    formatMoney(
                        item.line_total
                    );


                /* REMOVE */

                const actionCell =
                    document.createElement(
                        "td"
                    );


                if (
                    editable
                ) {

                    const removeButton =
                        document.createElement(
                            "button"
                        );


                    removeButton.type =
                        "button";


                    removeButton.className =
                        "edit-product-loan-remove-item-btn";


                    removeButton.title =
                        "Remove product";


                    removeButton.setAttribute(
                        "aria-label",
                        "Remove "
                        +
                        item.product_name
                    );


                    removeButton.innerHTML =
                        '<i class="bi bi-trash3"></i>';


                    removeButton.addEventListener(
                        "click",
                        function () {

                            editItems.splice(
                                index,
                                1
                            );


                            renderEditItems();

                        }
                    );


                    actionCell.appendChild(
                        removeButton
                    );

                }
                else {

                    actionCell.textContent =
                        "—";

                }


                row.appendChild(
                    productCell
                );


                row.appendChild(
                    quantityCell
                );


                row.appendChild(
                    amountCell
                );


                row.appendChild(
                    actionCell
                );


                itemsBody.appendChild(
                    row
                );

            }
        );


        itemsHiddenInput.value =
            JSON.stringify(
                editItems
            );


        refreshCatalogAddedState();

        updateRequestTotals();

        updatePendingSummary();

    }


    function addEditItem() {

        if (
            !currentLoanData
                ?.permission
                ?.can_edit_pending_details
        ) {

            return;

        }


        if (
            !selectedProduct
        ) {

            showAlert(
                "warning",
                "Select a product",
                "Choose a Product List item before adding it to the request."
            );


            return;

        }


        const quantityState =
            refreshQuantityValidation();


        if (
            !quantityState.valid
        ) {

            if (
                quantityInput
            ) {

                quantityInput.focus();

            }


            return;

        }


        const quantity =
            quantityState.quantity;


        if (
            editItems.length >=
            MAX_PRODUCTS
        ) {

            showAlert(
                "warning",
                "Product limit reached",
                "A maximum of 50 products is allowed per application."
            );


            return;

        }


        const duplicate =
            editItems.some(
                function (item) {

                    return (
                        Number(
                            item.loan_product_id
                        )
                        ===
                        selectedProduct
                            .loan_product_id
                    );

                }
            );


        if (
            duplicate
        ) {

            showAlert(
                "warning",
                "Product already added",
                "This product is already in Requested Products. Use the + or − controls in the cart to adjust its quantity."
            );


            return;

        }


        editItems.push({

            loan_product_id:
                selectedProduct
                    .loan_product_id,

            product_name:
                selectedProduct
                    .product_name,

            quantity:
                quantity,

            unit_price:
                selectedProduct
                    .loan_price,

            line_total:
                roundMoney(
                    quantity *
                    selectedProduct
                        .loan_price
                )

        });


        renderEditItems();


        /*
         * Do not focus the search field here.
         * Keeping focus unchanged prevents the modal
         * from jumping back to the top.
         */

        resetProductSelection();

    }


    productCatalogTiles
        .forEach(
            function (tile) {

                tile.addEventListener(
                    "click",
                    function () {

                        selectCatalogProduct(
                            tile
                        );

                    }
                );

            }
        );


    if (
        productSearchInput
    ) {

        productSearchInput
            .addEventListener(
                "input",
                updateCatalogSearch
            );

    }


    if (
        quantityMinusButton
    ) {

        quantityMinusButton.addEventListener(
            "click",
            function () {

                if (
                    !selectedProduct ||
                    !quantityInput
                ) {

                    return;
                }


                const currentQuantity =
                    Number(
                        quantityInput.value
                    );


                const nextQuantity =
                    (
                        Number.isInteger(
                            currentQuantity
                        )
                        &&
                        currentQuantity > 1
                    )
                        ?
                        currentQuantity - 1
                        :
                        1;


                quantityInput.value =
                    String(
                        nextQuantity
                    );


                refreshQuantityValidation();
            }
        );
    }


    if (
        quantityPlusButton
    ) {

        quantityPlusButton.addEventListener(
            "click",
            function () {

                if (
                    !selectedProduct ||
                    !quantityInput
                ) {

                    return;
                }


                const state =
                    refreshQuantityValidation();


                if (!state.valid) {

                    return;
                }


                quantityInput.value =
                    String(
                        state.quantity + 1
                    );


                refreshQuantityValidation();
            }
        );
    }


    if (
        quantityInput
    ) {

        quantityInput.addEventListener(
            "input",
            refreshQuantityValidation
        );


        quantityInput.addEventListener(
            "change",
            refreshQuantityValidation
        );


        quantityInput.addEventListener(
            "blur",
            refreshQuantityValidation
        );


        quantityInput.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key ===
                    "Enter"
                ) {

                    event.preventDefault();


                    const state =
                        refreshQuantityValidation();


                    if (
                        state.valid
                    ) {

                        addEditItem();

                    }

                }

            }
        );

    }


    if (
        addItemButton
    ) {

        addItemButton.addEventListener(
            "click",
            addEditItem
        );

    }


    updateCatalogSearch();


    termInput.addEventListener(
        "change",
        function () {

            clearValidation(
                termInput
            );


            updatePendingSummary();


            if (
                statusInput.value ===
                "Delivered"
            ) {

                dueDateInput.value =
                    currentLoanData
                        ?.server
                        ?.due_date_preview_display
                    ||
                    "Generated upon delivery";

            }

        }
    );

        /* =====================================================
       ATTACHMENTS
    ===================================================== */

    function rebuildAttachmentInput() {

        const dataTransfer =
            new DataTransfer();


        newAttachments
            .forEach(
                function (file) {

                    dataTransfer.items
                        .add(
                            file
                        );

                }
            );


        attachmentInput.files =
            dataTransfer.files;

    }


    function renderEditAttachments() {

        attachmentList.innerHTML =
            "";


        const canEditAttachments =
            Boolean(
                currentLoanData
                    ?.permission
                    ?.can_edit_pending_details
            );


        let visibleAttachmentCount =
            0;


        existingAttachments
            .forEach(
                function (attachment) {

                    const attachmentID =
                        Number(
                            attachment
                                .attachment_id
                        );


                    if (
                        removedAttachmentIDs
                            .includes(
                                attachmentID
                            )
                    ) {

                        return;

                    }


                    visibleAttachmentCount++;


                    const item =
                        document.createElement(
                            "div"
                        );


                    item.className =
                        "product-loan-existing-attachment";


                    const info =
                        document.createElement(
                            "div"
                        );


                    info.className =
                        "product-loan-existing-attachment-info";


                    const icon =
                        document.createElement(
                            "i"
                        );


                    icon.className =
                        getAttachmentIconClass(
                            attachment
                                .original_file_name,
                            attachment
                                .mime_type
                        );


                    const name =
                        document.createElement(
                            "span"
                        );


                    name.textContent =
                        attachment
                            .original_file_name
                        +
                        " ("
                        +
                        formatFileSize(
                            attachment
                                .file_size
                        )
                        +
                        ")";


                    info.appendChild(
                        icon
                    );


                    info.appendChild(
                        name
                    );


                    const actions =
                        document.createElement(
                            "div"
                        );


                    actions.className =
                        "product-loan-existing-attachment-actions";


                    const viewButton =
                        document.createElement(
                            "button"
                        );


                    viewButton.type =
                        "button";


                    viewButton.className =
                        "product-loan-view-attachment-btn";


                    viewButton.title =
                        "View attachment";


                    viewButton.innerHTML =
                        '<i class="bi bi-eye-fill"></i>';


                    viewButton.addEventListener(
                        "click",
                        async function () {

                            const fileName =
                                String(
                                    attachment
                                        .original_file_name
                                    ||
                                    "Attachment"
                                );


                            const extension =
                                getFileExtension(
                                    fileName
                                );


                            const viewURL =
                                String(
                                    attachment
                                        .view_url
                                    ||
                                    ""
                                )
                                .trim();


                            if (
                                !viewURL
                            ) {

                                await showAlert(
                                    "error",
                                    "Unable to view attachment",
                                    "The attachment link is unavailable."
                                );


                                return;

                            }


                            if (
                                typeof Swal ===
                                "undefined"
                            ) {

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
                                ].includes(
                                    extension
                                )
                            ) {

                                await Swal.fire({

                                    imageUrl:
                                        viewURL,

                                    imageAlt:
                                        fileName,

                                    imageWidth:
                                        700,

                                    width:
                                        900,

                                    showCloseButton:
                                        true,

                                    showConfirmButton:
                                        false,

                                    allowOutsideClick:
                                        true,

                                    allowEscapeKey:
                                        true

                                });


                                return;

                            }


                            /* PDF PREVIEW */

                            if (
                                extension ===
                                "pdf"
                            ) {

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

                                    width:
                                        1000,

                                    showCloseButton:
                                        true,

                                    showConfirmButton:
                                        false,

                                    allowOutsideClick:
                                        true,

                                    allowEscapeKey:
                                        true,

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
                                extension ===
                                    "doc"
                                ||
                                extension ===
                                    "docx"
                            ) {

                                const downloadResult =
                                    await Swal.fire({

                                        icon:
                                            "info",

                                        title:
                                            "Download attachment?",

                                        text:
                                            fileName
                                            +
                                            " cannot be previewed directly while the system is offline.",

                                        showCancelButton:
                                            true,

                                        confirmButtonText:
                                            "DOWNLOAD",

                                        cancelButtonText:
                                            "CANCEL",

                                        confirmButtonColor:
                                            "#8ca15b",

                                        cancelButtonColor:
                                            "#7d7d7d"

                                    });


                                if (
                                    downloadResult
                                        .isConfirmed
                                ) {

                                    const downloadLink =
                                        document
                                            .createElement(
                                                "a"
                                            );


                                    downloadLink.href =
                                        viewURL;


                                    downloadLink.style
                                        .display =
                                        "none";


                                    document.body
                                        .appendChild(
                                            downloadLink
                                        );


                                    downloadLink.click();


                                    downloadLink.remove();

                                }


                                return;

                            }


                            await showAlert(
                                "error",
                                "Unsupported attachment",
                                "This attachment cannot be previewed."
                            );

                        }
                    );


                    actions.appendChild(
                        viewButton
                    );


                    if (
                        canEditAttachments
                    ) {

                        const removeButton =
                            document.createElement(
                                "button"
                            );


                        removeButton.type =
                            "button";


                        removeButton.className =
                            "product-loan-remove-existing-attachment-btn";


                        removeButton.title =
                            "Remove attachment";


                        removeButton.innerHTML =
                            '<i class="bi bi-x-circle-fill"></i>';


                        removeButton.addEventListener(
                            "click",
                            function () {

                                if (
                                    !removedAttachmentIDs
                                        .includes(
                                            attachmentID
                                        )
                                ) {

                                    removedAttachmentIDs
                                        .push(
                                            attachmentID
                                        );

                                }


                                renderEditAttachments();

                            }
                        );


                        actions.appendChild(
                            removeButton
                        );

                    }


                    item.appendChild(
                        info
                    );


                    item.appendChild(
                        actions
                    );


                    attachmentList
                        .appendChild(
                            item
                        );

                }
            );


        newAttachments.forEach(
            function (
                file,
                index
            ) {

                visibleAttachmentCount++;


                const item =
                    document.createElement(
                        "div"
                    );


                item.className =
                    "product-loan-attachment-item";


                const info =
                    document.createElement(
                        "div"
                    );


                info.className =
                    "product-loan-attachment-info";


                const icon =
                    document.createElement(
                        "i"
                    );


                icon.className =
                    getAttachmentIconClass(
                        file.name,
                        file.type
                    );


                const name =
                    document.createElement(
                        "span"
                    );


                name.textContent =
                    file.name
                    +
                    " ("
                    +
                    formatFileSize(
                        file.size
                    )
                    +
                    ")";


                const removeButton =
                    document.createElement(
                        "button"
                    );


                removeButton.type =
                    "button";


                removeButton.className =
                    "product-loan-remove-attachment";


                removeButton.title =
                    "Remove attachment";


                removeButton.innerHTML =
                    '<i class="bi bi-x-circle-fill"></i>';


                removeButton.addEventListener(
                    "click",
                    function () {

                        newAttachments.splice(
                            index,
                            1
                        );


                        rebuildAttachmentInput();


                        renderEditAttachments();

                    }
                );


                info.appendChild(
                    icon
                );


                info.appendChild(
                    name
                );


                item.appendChild(
                    info
                );


                item.appendChild(
                    removeButton
                );


                attachmentList.appendChild(
                    item
                );

            }
        );


        if (
            visibleAttachmentCount ===
            0
        ) {

            const empty =
                document.createElement(
                    "div"
                );


            empty.className =
                "product-loan-no-attachment";


            empty.textContent =
                "No attachments found";


            attachmentList.appendChild(
                empty
            );

        }

    }


    attachmentInput.addEventListener(
        "change",
        function () {

            const selectedFiles =
                Array.from(
                    attachmentInput.files
                );


            const remainingSlots =
                MAX_ATTACHMENTS -
                newAttachments.length;


            if (
                remainingSlots <=
                0
            ) {

                showAlert(
                    "warning",
                    "Attachment limit reached",
                    "A maximum of 10 new attachments is allowed per update."
                );


                rebuildAttachmentInput();


                return;

            }


            const validFiles =
                [];


            for (
                const file
                of
                selectedFiles
            ) {

                if (
                    !isAllowedAttachment(
                        file
                    )
                ) {

                    showAlert(
                        "warning",
                        "Unsupported file",
                        "Only JPG, PNG, PDF, DOC, and DOCX files are allowed."
                    );


                    continue;

                }


                if (
                    file.size <= 0
                ) {

                    showAlert(
                        "warning",
                        "Empty file",
                        file.name +
                        " is empty and cannot be attached."
                    );


                    continue;

                }


                if (
                    file.size >
                    MAX_FILE_SIZE
                ) {

                    showAlert(
                        "warning",
                        "File is too large",
                        file.name +
                        " exceeds the 10 MB limit."
                    );


                    continue;

                }


                const duplicate =
                    newAttachments
                        .concat(
                            validFiles
                        )
                        .some(
                            function (
                                existingFile
                            ) {

                                return (
                                    existingFile.name
                                    ===
                                    file.name
                                    &&
                                    existingFile.size
                                    ===
                                    file.size
                                    &&
                                    existingFile.lastModified
                                    ===
                                    file.lastModified
                                );

                            }
                        );


                if (
                    !duplicate
                ) {

                    validFiles.push(
                        file
                    );

                }

            }


            const filesWithinCountLimit =
                validFiles.slice(
                    0,
                    Math.max(
                        remainingSlots,
                        0
                    )
                );


            if (
                validFiles.length >
                remainingSlots
            ) {

                showAlert(
                    "warning",
                    "Attachment limit reached",
                    "A maximum of 10 new attachments is allowed per update."
                );

            }


            let combinedSize =
                calculateNewAttachmentSize();


            const acceptedFiles =
                [];


            let skippedForTotalSize =
                false;


            for (
                const file
                of
                filesWithinCountLimit
            ) {

                if (
                    combinedSize +
                    file.size >
                    MAX_TOTAL_FILE_SIZE
                ) {

                    skippedForTotalSize =
                        true;


                    continue;

                }


                acceptedFiles.push(
                    file
                );


                combinedSize +=
                    file.size;

            }


            if (
                skippedForTotalSize
            ) {

                showAlert(
                    "warning",
                    "Combined size limit reached",
                    "Some files were not added because the new attachments must not exceed 50 MB in total."
                );

            }


            newAttachments =
                newAttachments.concat(
                    acceptedFiles
                );


            rebuildAttachmentInput();


            renderEditAttachments();

        }
    );


    /* =====================================================
       PAYMENT HISTORY
    ===================================================== */

    function renderPaymentHistory(
        payments
    ) {

        paymentHistoryBody.innerHTML =
            "";


        if (
            !Array.isArray(
                payments
            )
            ||
            payments.length ===
            0
        ) {

            const empty =
                document.createElement(
                    "tr"
                );


            empty.className =
                "product-loan-payment-empty";


            empty.innerHTML =
                '<td colspan="10">'
                +
                "No payments recorded yet"
                +
                "</td>";


            paymentHistoryBody
                .appendChild(
                    empty
                );


            return;

        }


        payments.forEach(
            function (payment) {

                const row =
                    document.createElement(
                        "tr"
                    );


                const values = [

                    payment
                        .payment_date_display,

                    formatDays(
                        payment
                            .elapsed_days
                    ),

                    payment
                        .transaction_type,

                    formatMoney(
                        payment
                            .payment_amount
                    ),

                    formatMoney(
                        payment
                            .interest_paid
                    ),

                    formatMoney(
                        payment
                            .principal_paid
                    ),

                    formatMoney(
                        payment
                            .balance_after
                    ),

                    normalizeText(
                        payment.notes
                    ) || "—",

                    payment
                        .recorded_by_name

                ];


                values.forEach(
                    function (
                        value,
                        index
                    ) {

                        const cell =
                            document
                                .createElement(
                                    "td"
                                );


                        if (
                            index === 2
                        ) {

                            const badge =
                                document
                                    .createElement(
                                        "span"
                                    );


                            badge.className =
                                "product-loan-payment-type "
                                +
                                (
                                    payment
                                        .transaction_type
                                    ===
                                    "Reversal"
                                        ?
                                        "reversal"
                                        :
                                        "payment"
                                );


                            badge.textContent =
                                value;


                            cell.appendChild(
                                badge
                            );

                        }
                        else {

                            cell.textContent =
                                value
                                ??
                                "—";

                        }


                        row.appendChild(
                            cell
                        );

                    }
                );


                const actionCell =
                    document.createElement(
                        "td"
                    );


                if (
                    payment.can_reverse
                ) {

                    const reverseButton =
                        document.createElement(
                            "button"
                        );


                    reverseButton.type =
                        "button";


                    reverseButton.className =
                        "product-loan-reverse-payment-btn";


                    reverseButton.title =
                        "Reverse payment";


                    reverseButton.innerHTML =
                        '<i class="bi bi-arrow-counterclockwise"></i>';


                    reverseButton.addEventListener(
                        "click",
                        function () {

                            reversePayment(
                                payment
                            );

                        }
                    );


                    actionCell.appendChild(
                        reverseButton
                    );

                }
                else {

                    actionCell.textContent =
                        payment.is_reversed
                            ?
                            "Reversed"
                            :
                            "—";

                }


                row.appendChild(
                    actionCell
                );


                paymentHistoryBody
                    .appendChild(
                        row
                    );

            }
        );

    }


    /* =====================================================
       LOAN DISPLAY MODE
    ===================================================== */

    function applyLoanMode() {

        if (
            !currentLoanData
        ) {

            return;

        }


        const loan =
            currentLoanData.loan;


        const permission =
            currentLoanData.permission;


        const isPending =
            loan.status ===
            "Pending";


        const isDelivered =
            loan.status ===
            "Delivered";


        const canEdit =
            Boolean(
                permission
                    .can_edit_pending_details
            );


        memberNameInput.disabled =
            !canEdit;


        termInput.disabled =
            !canEdit;


        statusInput.disabled =
            !permission
                .can_change_status;


        if (
            productBuilder
        ) {

            productBuilder.style.display =
                canEdit
                    ?
                    ""
                    :
                    "none";

        }


        if (
            productSelectionSection
        ) {

            productSelectionSection
                .style
                .display =
                canEdit
                    ?
                    ""
                    :
                    "none";

        }


        if (
            productRequestSection
        ) {

            productRequestSection
                .style
                .display =
                "";

        }


        if (
            productRequestNote
        ) {

            if (
                isDelivered
            ) {

                productRequestNote.textContent =
                    "These are the products delivered to the member. Product details are now permanently locked.";

            }
            else if (
                loan.status ===
                "Rejected"
            ) {

                productRequestNote.textContent =
                    "These are the products recorded when the application was rejected. Product details are locked.";

            }
            else {

                productRequestNote.textContent =
                    "Review selected products before saving.";

            }

        }


        if (
            productArea
        ) {

            productArea.classList
                .toggle(
                    "is-read-only",
                    !canEdit
                );

        }


        uploadBox.classList.toggle(
            "product-loan-upload-locked",
            !canEdit
        );


        attachmentInput.disabled =
            !canEdit;


        if (
            permission
                .can_change_status
        ) {

            statusPermissionNote
                .textContent =
                "Full Lending Access: you may mark this Pending application as Delivered or Rejected.";

        }
        else if (
            isPending
        ) {

            statusPermissionNote
                .textContent =
                "Limited Lending Access: this application is locked to Pending.";

        }
        else {

            statusPermissionNote
                .textContent =
                "This status is final and can no longer be changed.";

        }


        paymentSection.style.display =
            isDelivered
                ?
                "block"
                :
                "none";


        if (
            paymentEntry
        ) {

            paymentEntry.style.display =
                permission
                    .can_record_payment
                    ?
                    "grid"
                    :
                    "none";

        }


        if (
            isPending
        ) {

            modalTitle.textContent =
                "EDIT PRODUCT LOAN";


            informationText
                .textContent =
                "Review and update this pending Product Loan application.";


            productNote.textContent =
                "Products may be added or removed while the application is Pending.";


            saveChangesButton
                .style
                .display =
                "inline-block";

        }
        else {

            modalTitle.textContent =
                "PRODUCT LOAN DETAILS";


            informationText
                .textContent =
                isDelivered
                    ?
                    "Financial details are locked because the products have already been delivered."
                    :
                    "This rejected application is read-only and remains recorded in the ledger.";


            productNote.textContent =
                "Product details are locked and cannot be changed.";


            saveChangesButton
                .style
                .display =
                "none";

        }


        renderEditItems();


        renderEditAttachments();

    }


    /* =====================================================
       STATUS SELECTION
    ===================================================== */

    function handleStatusSelection() {

        if (
            !currentLoanData
        ) {

            return;

        }


        const selectedStatus =
            statusInput.value;


        updateStatusBadge(
            selectedStatus
        );


        rejectionBlock.style.display =
            selectedStatus ===
            "Rejected"
                ?
                "block"
                :
                "none";


        if (
            currentLoanData
                .loan
                .status !==
            "Pending"
        ) {

            return;

        }


        if (
            selectedStatus ===
            "Delivered"
        ) {

            deliveredDateInput.value =
                currentLoanData
                    .server
                    .delivery_date_preview_display;


            dueDateInput.value =
                currentLoanData
                    .server
                    .due_date_preview_display;

        }
        else {

            deliveredDateInput.value =
                "Not delivered";


            dueDateInput.value =
                selectedStatus ===
                "Pending"
                    ?
                    "Generated upon delivery"
                    :
                    "—";

        }

    }


    statusInput.addEventListener(
        "change",
        handleStatusSelection
    );


    /* =====================================================
       POPULATE MODAL
    ===================================================== */

        function populateEditModal(
        result
    ) {

        currentLoanData =
            result;


        const loan =
            result.loan;


        loanIDInput.value =
            loan.product_loan_id;


        displayIDInput.value =
            loan.display_id;


        memberIDInput.value =
            loan.member_id;


        originalMemberIDInput.value =
            loan.member_id;


        memberNameInput.value =
            loan.full_name;


        memberNameInput.dataset
            .selectedName =
            loan.full_name;


        existingStatusInput.value =
            loan.status;


        statusInput.value =
            loan.status;


        applicationDateInput.value =
            loan.application_date_display;


        deliveredDateInput.value =
            loan.delivered_date_display;


        dueDateInput.value =
            loan.due_date_display;


        termInput.value =
            String(
                loan.term_days
            );


        rejectionReasonInput.value =
            loan.rejection_reason
            ||
            "";


        editItems =
            Array.isArray(
                result.items
            )
            ?
            result.items.map(
                function (item) {

                    const loanProductID =
                        Number(
                            item.loan_product_id
                        );


                    return {

                        loan_product_id:
                            Number.isInteger(
                                loanProductID
                            )
                            &&
                            loanProductID > 0
                                ?
                                loanProductID
                                :
                                null,

                        product_name:
                            normalizeText(
                                item.product_name
                            ),

                        quantity:
                            Number(
                                item.quantity
                            ),

                        unit_price:
                            roundMoney(
                                Number(
                                    item.unit_price
                                )
                            ),

                        line_total:
                            roundMoney(
                                Number(
                                    item.line_total
                                )
                            )

                    };

                }
            )
            :
            [];


        resetProductSelection();


        if (
            productSearchInput
        ) {

            productSearchInput.value =
                "";

        }


        updateCatalogSearch();


        existingAttachments =
            Array.isArray(
                result.attachments
            )
                ?
                result.attachments
                :
                [];


        newAttachments =
            [];


        removedAttachmentIDs =
            [];


        attachmentInput.value =
            "";


        principalDisplay.textContent =
            formatMoney(
                loan.principal_amount
            );


        interestRateDisplay
            .textContent =
            Number(
                loan.interest_rate
            )
            .toFixed(
                0
            )
            +
            "%";


        periodDisplay.textContent =
            loan.term_days
            +
            " Days";


        scheduledInterestDisplay
            .textContent =
            formatMoney(
                loan
                    .scheduled_interest_amount
            );


        scheduledTotalDisplay
            .textContent =
            formatMoney(
                loan
                    .scheduled_total_amount
            );


        const originalPayoffTotal =
            Number(loan.scheduled_total_amount);


        const originalTermDays =
            Number(loan.term_days);


        const showPaymentEstimate =
            (loan.status === "Pending" || loan.status === "Delivered") &&
            Number(loan.principal_amount) > 0 &&
            Number.isFinite(originalPayoffTotal) &&
            originalPayoffTotal > 0 &&
            Number.isFinite(originalTermDays) &&
            originalTermDays > 0;


        dailyPaymentDisplay.textContent =
            showPaymentEstimate
                ? formatMoney(originalPayoffTotal / originalTermDays)
                : "—";


        monthlyPaymentDisplay.textContent =
            showPaymentEstimate
                ? formatMoney(originalPayoffTotal / (originalTermDays / 30))
                : "—";


        totalPaymentsDisplay
            .textContent =
            formatMoney(
                loan
                    .net_payments_amount
            );


        currentInterestDisplay
            .textContent =
            formatMoney(
                loan
                    .total_interest_due_amount
            );


        currentBalanceDisplay
            .textContent =
            loan.current_balance ===
            null
                ?
                "—"
                :
                formatMoney(
                    loan.current_balance
                );


        paymentPrincipalDisplay
            .textContent =
            formatMoney(
                loan
                    .principal_amount
            );


        paymentUnpaidInterestDisplay
            .textContent =
            formatMoney(
                loan
                    .stored_unpaid_interest_amount
            );


        paymentElapsedDaysDisplay
            .textContent =
            formatDays(
                loan.elapsed_days
            );


        paymentNewInterestDisplay
            .textContent =
            formatMoney(
                loan
                    .new_accrued_interest_amount
            );


        paymentPayoffDisplay
            .textContent =
            formatMoney(
                loan.current_balance
                ||
                0
            );


        paymentDateInput.value =
            result.server
                .date_display;


        paymentAmountInput.value =
            "";


        paymentNotesInput.value =
            "";


        updateStatusBadge(
            loan.status
        );


        rejectionBlock.style.display =
            loan.status ===
            "Rejected"
                ?
                "block"
                :
                "none";


        renderPaymentHistory(
            result.payments
        );


        applyLoanMode();


        updatePendingSummary();

    }


    /* =====================================================
       LOAD PRODUCT LOAN
    ===================================================== */

    async function loadProductLoan(
        loanID
    ) {

        if (
            !Number.isInteger(
                Number(
                    loanID
                )
            )
            ||
            Number(
                loanID
            ) <= 0
        ) {

            showAlert(
                "error",
                "Invalid Product Loan",
                "The selected Product Loan ID is invalid."
            );


            return;

        }


        try {

            const response =
                await fetch(

                    "get_product_loan.php?loan_id="
                    +
                    encodeURIComponent(
                        loanID
                    ),

                    {
                        method: "GET",

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        },

                        cache:
                            "no-store"
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

            }
            catch (
                jsonError
            ) {

                console.error(
                    "Invalid loan response:",
                    responseText
                );


                throw new Error(
                    "The server returned an invalid response."
                );

            }


            if (
                !response.ok
                ||
                !result.success
            ) {

                throw new Error(
                    result.message
                    ||
                    "Unable to load the Product Loan."
                );

            }


            populateEditModal(
                result
            );


            const modalInstance =
                bootstrap.Modal
                    .getOrCreateInstance(
                        modalElement
                    );


            modalInstance.show();

        }
        catch (
            error
        ) {

            showAlert(
                "error",
                "Unable to load",
                error.message
            );

        }

    }


    document
        .querySelectorAll(
            ".product-loan-edit-btn"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function () {

                        loadProductLoan(
                            button.dataset
                                .loanId
                        );

                    }
                );

            }
        );


    /* =====================================================
       FORM VALIDATION
    ===================================================== */

    function validateEditForm() {

        clearAllValidation();


        let firstInvalid =
            null;


        if (
            !memberIDInput.value
        ) {

            setFieldError(
                memberNameInput,
                "Select a registered member."
            );


            firstInvalid =
                memberNameInput;

        }


        if (
            !termInput.value
        ) {

            setFieldError(
                termInput,
                "Select the loan term."
            );


            firstInvalid =
                firstInvalid
                ||
                termInput;

        }


        if (
            editItems.length ===
            0
        ) {

            showAlert(
                "warning",
                "No requested products",
                "The application must contain at least one product."
            );


            firstInvalid =
                firstInvalid
                ||
                productSearchInput;

        }


        if (
            statusInput.value ===
                "Rejected"
            &&
            normalizeText(
                rejectionReasonInput.value
            ) ===
                ""
        ) {

            setFieldError(
                rejectionReasonInput,
                "Enter the rejection reason."
            );


            firstInvalid =
                firstInvalid
                ||
                rejectionReasonInput;

        }


        if (
            getFinalAttachmentCount()
            <
            1
        ) {

            showAlert(
                "warning",
                "Supporting document required",
                "At least one supporting document must remain attached to the Product Loan application."
            );


            return false;

        }


        if (
            newAttachments.length >
            MAX_ATTACHMENTS
        ) {

            showAlert(
                "warning",
                "Attachment limit exceeded",
                "Remove new attachments until only 10 files remain."
            );


            return false;

        }


        for (
            const file
            of
            newAttachments
        ) {

            if (
                !isAllowedAttachment(
                    file
                )
            ) {

                showAlert(
                    "warning",
                    "Unsupported file",
                    "Only JPG, PNG, PDF, DOC, and DOCX files are allowed."
                );


                return false;

            }


            if (
                file.size <= 0
            ) {

                showAlert(
                    "warning",
                    "Empty file",
                    file.name +
                    " is empty and cannot be attached."
                );


                return false;

            }


            if (
                file.size >
                MAX_FILE_SIZE
            ) {

                showAlert(
                    "warning",
                    "File is too large",
                    file.name +
                    " exceeds the 10 MB limit."
                );


                return false;

            }

        }


        if (
            calculateNewAttachmentSize()
            >
            MAX_TOTAL_FILE_SIZE
        ) {

            showAlert(
                "warning",
                "Combined size limit exceeded",
                "The new attachments must not exceed 50 MB in total."
            );


            return false;

        }


        if (
            firstInvalid
        ) {

            firstInvalid.focus();


            return false;

        }


        return true;

    }


    /* =====================================================
       SAVE PRODUCT LOAN
    ===================================================== */

    editForm.addEventListener(
        "submit",
        async function (event) {

            event.preventDefault();


            if (
                isSubmitting
                ||
                !currentLoanData
            ) {

                return;

            }


            if (
                !validateEditForm()
            ) {

                return;

            }


            const selectedStatus =
                statusInput.value;


            let title =
                "Save Product Loan changes?";


            let message =
                "The pending application details will be updated.";


            if (
                selectedStatus ===
                "Delivered"
            ) {

                title =
                    "Mark products as Delivered?";


                message =
                    "Once delivered, the member, products, prices, and loan term will be permanently locked. Interest will start today and the due date will be generated automatically.";

            }
            else if (
                selectedStatus ===
                "Rejected"
            ) {

                title =
                    "Reject this application?";


                message =
                    "The rejected application will remain in the ledger and can no longer be delivered or edited.";

            }


            let confirmed =
                true;


            if (
                typeof Swal !==
                "undefined"
            ) {

                const confirmation =
                    await Swal.fire({

                        icon:
                            "question",

                        title:
                            title,

                        text:
                            message,

                        showCancelButton:
                            true,

                        confirmButtonText:
                            "YES, CONTINUE",

                        cancelButtonText:
                            "CANCEL",

                        confirmButtonColor:
                            "#8ca15b",

                        cancelButtonColor:
                            "#777777",

                        reverseButtons:
                            true,

                        focusCancel:
                            true,

                        allowOutsideClick:
                            false

                    });


                confirmed =
                    confirmation
                        .isConfirmed;

            }
            else {

                confirmed =
                    window.confirm(
                        message
                    );

            }


            if (
                !confirmed
            ) {

                return;

            }


            isSubmitting =
                true;


            setButtonBusy(
                saveChangesButton,
                true
            );


            try {

                itemsHiddenInput.value =
                    JSON.stringify(
                        editItems
                    );


                const formData =
                    new FormData(
                        editForm
                    );


                formData.set(
                    "member_id",
                    memberIDInput.value
                );


                formData.set(
                    "loan_items",
                    JSON.stringify(
                        editItems
                    )
                );


                formData.set(
                    "removed_attachment_ids",
                    JSON.stringify(
                        removedAttachmentIDs
                    )
                );


                formData.set(
                    "csrf_token",
                    csrfToken
                );


                const response =
                    await fetch(

                        "update_product_loan.php",

                        {
                            method:
                                "POST",

                            body:
                                formData,

                            headers: {
                                "X-Requested-With":
                                    "XMLHttpRequest"
                            }
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

                }
                catch (
                    jsonError
                ) {

                    console.error(
                        "Invalid update response:",
                        responseText
                    );


                    throw new Error(
                        "The server returned an invalid response."
                    );

                }


                if (
                    !response.ok
                    ||
                    !result.success
                ) {

                    throw new Error(
                        result.message
                        ||
                        "Unable to update the Product Loan."
                    );

                }


                await showAlert(
                    "success",
                    "Product Loan updated",
                    result.message
                );


                reloadAtCurrentProductLoan();

            }
            catch (
                error
            ) {

                await showAlert(
                    "error",
                    "Unable to update",
                    error.message
                );

            }
            finally {

                isSubmitting =
                    false;


                setButtonBusy(
                    saveChangesButton,
                    false
                );

            }

        }
    );


    /* =====================================================
       RECORD PAYMENT
    ===================================================== */

    recordPaymentButton.addEventListener(
        "click",
        async function () {

            if (
                !currentLoanData
                ||
                isSubmitting
            ) {

                return;

            }


            const paymentAmount =
                roundMoney(
                    Number(
                        paymentAmountInput
                            .value
                    )
                );


            const currentBalance =
                Number(
                    currentLoanData
                        .loan
                        .current_balance
                );


            clearValidation(
                paymentAmountInput
            );


            if (
                !Number.isFinite(
                    paymentAmount
                )
                ||
                paymentAmount <=
                0
            ) {

                setFieldError(
                    paymentAmountInput,
                    "Enter a valid payment amount."
                );


                paymentAmountInput
                    .focus();


                return;

            }


            if (
                paymentAmount
                >
                currentBalance
                +
                0.009
            ) {

                await showAlert(
                    "error",
                    "Payment exceeds balance",
                    "The payment cannot exceed the current payoff balance of "
                    +
                    formatMoney(
                        currentBalance
                    )
                    +
                    "."
                );


                paymentAmountInput
                    .focus();


                return;

            }


            let confirmed =
                true;


            if (
                typeof Swal !==
                "undefined"
            ) {

                const confirmation =
                    await Swal.fire({

                        icon:
                            "question",

                        title:
                            "Record this payment?",

                        html:
                            "<p><strong>Accrued Days:</strong> "
                            +
                            formatDays(
                                currentLoanData
                                    .loan
                                    .elapsed_days
                            )
                            +
                            "</p>"
                            +
                            "<p><strong>Payment:</strong> "
                            +
                            formatMoney(
                                paymentAmount
                            )
                            +
                            "</p>"
                            +
                            "<p>The server will recalculate interest and apply the payment to interest first, then to principal.</p>",

                        showCancelButton:
                            true,

                        confirmButtonText:
                            "YES, RECORD",

                        cancelButtonText:
                            "CANCEL",

                        confirmButtonColor:
                            "#8ca15b",

                        cancelButtonColor:
                            "#777777",

                        reverseButtons:
                            true,

                        focusCancel:
                            true,

                        allowOutsideClick:
                            false

                    });


                confirmed =
                    confirmation
                        .isConfirmed;

            }
            else {

                confirmed =
                    window.confirm(
                        "Record this payment?"
                    );

            }


            if (
                !confirmed
            ) {

                return;

            }


            isSubmitting =
                true;


            setButtonBusy(
                recordPaymentButton,
                true
            );


            try {

                const formData =
                    new FormData();


                formData.append(
                    "product_loan_id",
                    currentLoanData
                        .loan
                        .product_loan_id
                );


                formData.append(
                    "payment_amount",
                    paymentAmount
                        .toFixed(
                            2
                        )
                );


                formData.append(
                    "notes",
                    normalizeText(
                        paymentNotesInput
                            .value
                    )
                );


                formData.set(
                    "csrf_token",
                    csrfToken
                );


                const response =
                    await fetch(

                        "record_product_loan_payment.php",

                        {
                            method:
                                "POST",

                            body:
                                formData,

                            headers: {
                                "X-Requested-With":
                                    "XMLHttpRequest"
                            }
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

                }
                catch (
                    jsonError
                ) {

                    console.error(
                        "Invalid payment response:",
                        responseText
                    );


                    throw new Error(
                        "The server returned an invalid response."
                    );

                }


                if (
                    !response.ok
                    ||
                    !result.success
                ) {

                    throw new Error(
                        result.message
                        ||
                        "Unable to record the payment."
                    );

                }


                await showAlert(
                    "success",
                    "Payment recorded",
                    result.message
                );


                reloadAtCurrentProductLoan();

            }
            catch (
                error
            ) {

                await showAlert(
                    "error",
                    "Unable to record payment",
                    error.message
                );

            }
            finally {

                isSubmitting =
                    false;


                setButtonBusy(
                    recordPaymentButton,
                    false
                );

            }

        }
    );


    /* =====================================================
       REVERSE PAYMENT
    ===================================================== */

    async function reversePayment(
        payment
    ) {

        if (
            !payment.can_reverse
            ||
            isSubmitting
        ) {

            return;

        }


        if (
            typeof Swal ===
            "undefined"
        ) {

            return;

        }


        const bootstrapModal =
            bootstrap.Modal
                .getInstance(
                    modalElement
                );


        const modalFocusTrap =
            bootstrapModal
                ?._focustrap
            ||
            null;


        if (
            modalFocusTrap
        ) {

            modalFocusTrap
                .deactivate();

        }


        let confirmation;


        try {

            confirmation =
                await Swal.fire({

                    target:
                        modalElement,

                    icon:
                        "warning",

                    title:
                        "Reverse this payment?",

                    html:
                        "<p><strong>Payment:</strong> "
                        +
                        formatMoney(
                            payment
                                .payment_amount
                        )
                        +
                        "</p>"
                        +
                        "<p><strong>Accrued Days:</strong> "
                        +
                        formatDays(
                            payment
                                .elapsed_days
                        )
                        +
                        "</p>"
                        +
                        "<p>The original payment will remain in the history. A reversal record will be created.</p>",

                    input:
                        "textarea",

                    inputLabel:
                        "Reason for reversal",

                    inputPlaceholder:
                        "Explain why this payment must be reversed",

                    inputAttributes: {

                        maxlength:
                            "500",

                        autocomplete:
                            "off"

                    },

                    showCancelButton:
                        true,

                    confirmButtonText:
                        "REVERSE PAYMENT",

                    cancelButtonText:
                        "CANCEL",

                    confirmButtonColor:
                        "#dc5151",

                    cancelButtonColor:
                        "#777777",

                    reverseButtons:
                        true,

                    focusCancel:
                        false,

                    focusConfirm:
                        false,

                    returnFocus:
                        false,

                    allowOutsideClick:
                        false,

                    didOpen:
                        function (
                            popup
                        ) {

                            const label =
                                popup.querySelector(
                                    ".swal2-input-label"
                                );


                            const textarea =
                                popup.querySelector(
                                    ".swal2-textarea"
                                );


                            if (
                                label
                            ) {

                                label.style
                                    .setProperty(
                                        "color",
                                        "#4f5148",
                                        "important"
                                    );


                                label.style
                                    .setProperty(
                                        "opacity",
                                        "1",
                                        "important"
                                    );


                                label.style
                                    .setProperty(
                                        "font-weight",
                                        "700",
                                        "important"
                                    );


                                label.style
                                    .setProperty(
                                        "visibility",
                                        "visible",
                                        "important"
                                    );

                            }


                            if (
                                textarea
                            ) {

                                textarea.disabled =
                                    false;


                                textarea.readOnly =
                                    false;


                                textarea.style
                                    .setProperty(
                                        "color",
                                        "#22251f",
                                        "important"
                                    );


                                textarea.style
                                    .setProperty(
                                        "background-color",
                                        "#fffed6",
                                        "important"
                                    );


                                textarea.style
                                    .setProperty(
                                        "caret-color",
                                        "#22251f",
                                        "important"
                                    );


                                textarea.style
                                    .setProperty(
                                        "pointer-events",
                                        "auto",
                                        "important"
                                    );


                                textarea.style
                                    .setProperty(
                                        "user-select",
                                        "text",
                                        "important"
                                    );


                                window.setTimeout(
                                    function () {

                                        textarea.focus();

                                    },
                                    50
                                );

                            }

                        },

                    inputValidator:
                        function (
                            value
                        ) {

                            if (
                                normalizeText(
                                    value
                                ) ===
                                ""
                            ) {

                                return "A reversal reason is required.";

                            }


                            return null;

                        }

                });

        }
        finally {

            if (
                modalFocusTrap
            ) {

                modalFocusTrap
                    .activate();

            }

        }


        if (
            !confirmation
            ||
            !confirmation
                .isConfirmed
        ) {

            return;

        }


        isSubmitting =
            true;


        try {

            const formData =
                new FormData();


            formData.append(
                "product_loan_id",
                currentLoanData
                    .loan
                    .product_loan_id
            );


            formData.append(
                "payment_id",
                payment.payment_id
            );


            formData.append(
                "reversal_reason",
                normalizeText(
                    confirmation.value
                )
            );


            formData.set(
                "csrf_token",
                csrfToken
            );


            const response =
                await fetch(

                    "reverse_product_loan_payment.php",

                    {
                        method:
                            "POST",

                        body:
                            formData,

                        headers: {
                            "X-Requested-With":
                                "XMLHttpRequest"
                        }
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

            }
            catch (
                jsonError
            ) {

                console.error(
                    "Invalid reversal response:",
                    responseText
                );


                throw new Error(
                    "The server returned an invalid response."
                );

            }


            if (
                !response.ok
                ||
                !result.success
            ) {

                throw new Error(
                    result.message
                    ||
                    "Unable to reverse the payment."
                );

            }


            await showAlert(
                "success",
                "Payment reversed",
                result.message
            );


            reloadAtCurrentProductLoan();

        }
        catch (
            error
        ) {

            await showAlert(
                "error",
                "Unable to reverse payment",
                error.message
            );

        }
        finally {

            isSubmitting =
                false;

        }

    }


    /* =====================================================
       MODAL RESET
    ===================================================== */

    modalElement.addEventListener(
        "hidden.bs.modal",
        function () {

            currentLoanData =
                null;


            editItems =
                [];


            selectedProduct =
                null;


            existingAttachments =
                [];


            newAttachments =
                [];


            removedAttachmentIDs =
                [];


            if (
                memberSearchController
            ) {

                memberSearchController
                    .abort();


                memberSearchController =
                    null;

            }


            window.clearTimeout(
                memberSearchTimer
            );


            closeMemberSuggestions();


            clearAllValidation();


            editForm.reset();


            if (
                productSearchInput
            ) {

                productSearchInput.value =
                    "";

            }


            resetProductSelection();


            refreshCatalogAddedState();


            updateCatalogSearch();


            updateRequestTotals();


            itemsBody.innerHTML =
                "";


            attachmentList.innerHTML =
                "";


            paymentHistoryBody.innerHTML =
                "";


            if (
                paymentElapsedDaysDisplay
            ) {

                paymentElapsedDaysDisplay
                    .textContent =
                    "0 Days";

            }


            isSubmitting =
                false;


            setButtonBusy(
                saveChangesButton,
                false
            );


            setButtonBusy(
                recordPaymentButton,
                false
            );

        }
    );

});