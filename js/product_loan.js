document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const modalElement =
        document.getElementById("addProductLoanModal");

    const loanForm =
        document.getElementById("addProductLoanForm");

    const pageData =
        document.getElementById(
            "productLoanPageData"
        );

    const csrfToken =
        pageData?.dataset.csrfToken
        || "";

    if (!modalElement || !loanForm) {
        return;
    }

    const memberIDInput =
        document.getElementById("productLoanMemberID");

    const memberNameInput =
        document.getElementById("productLoanMemberName");

    const memberSuggestions =
        document.getElementById("productLoanMemberSuggestions");

    const loanTermInput =
        document.getElementById("productLoanTerm");

    const applicationDateInput =
        document.getElementById("productLoanApplicationDate");

    const dueDateInput =
        document.getElementById("productLoanDueDate");

    const productSearchInput =
        document.getElementById("productLoanProductSearch");

    const productCatalog =
        document.getElementById("productLoanProductCatalog");

    const productCatalogCount =
        document.getElementById("productLoanCatalogCount");

    const productCatalogNoMatch =
        document.getElementById("productLoanCatalogNoMatch");

    const productCatalogTiles =
        Array.from(
            document.querySelectorAll(
                ".product-loan-catalog-tile"
            )
        );

    const selectedProductPanel =
        document.getElementById("productLoanSelectedProductPanel");

    const selectedProductIDInput =
        document.getElementById("productLoanSelectedProductID");

    const selectedProductNameDisplay =
        document.getElementById("productLoanSelectedProductName");

    const selectedProductPriceDisplay =
        document.getElementById("productLoanSelectedProductPrice");

    const selectedProductReservedDisplay =
        document.getElementById("productLoanSelectedProductReserved");

    const quantityInput =
        document.getElementById("productLoanQuantity");

    const quantityMessage =
        document.getElementById("productLoanQuantityMessage");    

    const quantityMinusButton =
        document.getElementById("productLoanQuantityMinus");

    const quantityPlusButton =
        document.getElementById("productLoanQuantityPlus");

    const addItemButton =
        document.getElementById("addProductLoanItemBtn");

    const itemsTableBody =
        document.getElementById("productLoanItemsBody");

    const itemsHiddenInput =
        document.getElementById("productLoanItemsInput");

    const requestCountDisplay =
        document.getElementById("productLoanRequestCount");

    const totalQuantityDisplay =
        document.getElementById("productLoanTotalQuantity");

    const attachmentInput =
        document.getElementById("productLoanAttachments");

    const attachmentList =
        document.getElementById("productLoanAttachmentList");

    const attachmentUploadBox =
        loanForm.querySelector(
            'label[for="productLoanAttachments"]'
        );

    const principalDisplay =
        document.getElementById("productLoanPrincipal");

    const interestRateDisplay =
        document.getElementById("productLoanInterestRate");

    const loanPeriodDisplay =
        document.getElementById("productLoanPeriod");

    const scheduledInterestDisplay =
        document.getElementById("productLoanScheduledInterest");

    const scheduledTotalDisplay =
        document.getElementById("productLoanScheduledTotal");

    const dailyPaymentDisplay =
        document.getElementById("productLoanDailyPayment");

    const monthlyPaymentDisplay =
        document.getElementById("productLoanMonthlyPayment");

    const saveButton =
        document.getElementById("saveProductLoanBtn");


    /* =====================================================
       STATE AND LIMITS
    ===================================================== */

    let loanItems = [];

    let selectedProduct = null;

    let selectedAttachments = [];

    let memberSearchTimer = null;

    let memberSearchController = null;

    let formIsSubmitting = false;


    const MAX_PRODUCTS = 50;

    const MAX_ATTACHMENTS = 10;

    const MAX_FILE_SIZE =
        10 * 1024 * 1024;

    const MAX_TOTAL_FILE_SIZE =
        50 * 1024 * 1024;


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
        "application/CDFV2",
        "application/zip",
        "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
    ];


    /* =====================================================
       GENERAL HELPERS
    ===================================================== */

    function showAlert(
        icon,
        title,
        text
    ) {
        if (typeof Swal !== "undefined") {
            return Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: "OK",
                confirmButtonColor: "#93a95d",
                allowOutsideClick: false
            });
        }

        window.alert(
            title + "\n\n" + text
        );

        return Promise.resolve();
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


    function setButtonLoading(
        button,
        loading,
        loadingText = "SAVING..."
    ) {
        if (!button) {
            return;
        }

        if (loading) {
            button.dataset.originalText =
                button.innerHTML;

            button.disabled = true;

            button.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                loadingText;

            return;
        }

        button.disabled = false;

        if (button.dataset.originalText) {
            button.innerHTML =
                button.dataset.originalText;
        }
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
            input.closest(".product-loan-field");

        const oldError =
            field
                ? field.querySelector(
                    ".product-loan-field-error"
                )
                : null;

        if (oldError) {
            oldError.remove();
        }
    }


    function setFieldError(
        input,
        message
    ) {
        if (!input) {
            return;
        }

        clearFieldValidation(input);

        input.classList.add(
            "is-invalid"
        );

        const field =
            input.closest(".product-loan-field");

        if (!field) {
            return;
        }

        const error =
            document.createElement("small");

        error.className =
            "product-loan-field-error";

        error.textContent =
            message;

        field.appendChild(error);
    }


    function setFieldValid(input) {
        if (!input) {
            return;
        }

        clearFieldValidation(input);

        input.classList.add(
            "is-valid"
        );
    }


    function clearAllValidation() {
        loanForm
            .querySelectorAll(
                ".product-loan-input"
            )
            .forEach(
                clearFieldValidation
            );
    }


    /* =====================================================
       SERVER APPLICATION DATE
    ===================================================== */

    async function loadServerApplicationDate() {
        applicationDateInput.value =
            "Loading...";

        try {
            const response =
                await fetch(
                    "get_product_loan_form_data.php",
                    {
                        method: "GET",
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
                    JSON.parse(responseText);
            } catch (jsonError) {
                throw new Error(
                    "The server returned an invalid date response."
                );
            }

            if (
                !response.ok ||
                !result.success
            ) {
                throw new Error(
                    result.message ||
                    "Unable to retrieve server date."
                );
            }

            applicationDateInput.value =
                result.application_date_display ||
                result.application_date ||
                "";
        } catch (error) {

            /*
             * Temporary display fallback only.
             * PHP remains authoritative.
             */

            applicationDateInput.value =
                new Intl.DateTimeFormat(
                    "en-CA",
                    {
                        timeZone:
                            "Asia/Manila",
                        year:
                            "numeric",
                        month:
                            "2-digit",
                        day:
                            "2-digit"
                    }
                ).format(
                    new Date()
                );

            console.error(
                "Application date error:",
                error
            );
        }
    }


    /* =====================================================
       MEMBER SEARCH
    ===================================================== */

    function clearSelectedMember() {
        memberIDInput.value = "";

        memberNameInput.dataset.selectedName =
            "";
    }


    function hideMemberSuggestions() {
        memberSuggestions.innerHTML = "";

        memberSuggestions.classList.remove(
            "show"
        );
    }


    function showMemberMessage(message) {
        memberSuggestions.innerHTML = "";

        const messageElement =
            document.createElement("div");

        messageElement.className =
            "product-loan-member-suggestion";

        messageElement.style.cursor =
            "default";

        messageElement.textContent =
            message;

        memberSuggestions.appendChild(
            messageElement
        );

        memberSuggestions.classList.add(
            "show"
        );
    }


    function selectMember(member) {
        const hasActiveLoan =
            member.has_active_loan === true ||
            Number(
                member.has_active_loan
            ) === 1;

        if (hasActiveLoan) {
            hideMemberSuggestions();

            clearSelectedMember();

            memberNameInput.value = "";

            showAlert(
                "warning",
                "Existing product loan",
                member.full_name +
                " still has an active product loan. " +
                "The current loan must be fully paid before another application can be created."
            );

            return;
        }

        memberIDInput.value =
            member.member_id;

        memberNameInput.value =
            member.full_name;

        memberNameInput.dataset.selectedName =
            member.full_name;

        setFieldValid(
            memberNameInput
        );

        hideMemberSuggestions();
    }


    function renderMemberSuggestions(members) {
        memberSuggestions.innerHTML = "";

        if (
            !Array.isArray(members) ||
            members.length === 0
        ) {
            showMemberMessage(
                "No eligible Regular member found"
            );

            return;
        }

        members.forEach(
            function (member) {
                const suggestion =
                    document.createElement("button");

                suggestion.type =
                    "button";

                suggestion.className =
                    "product-loan-member-suggestion";

                const hasActiveLoan =
                    member.has_active_loan === true ||
                    Number(
                        member.has_active_loan
                    ) === 1;

                const memberCode =
                    member.member_code ||
                    "No member code";

                suggestion.innerHTML =
                    "<div>" +
                        "<strong>" +
                            escapeHTML(
                                member.full_name
                            ) +
                        "</strong>" +
                        "<span>" +
                            escapeHTML(
                                memberCode
                            ) +
                            (
                                hasActiveLoan
                                    ? " • Has active product loan"
                                    : " • Eligible for product loan"
                            ) +
                        "</span>" +
                    "</div>";

                if (hasActiveLoan) {
                    suggestion.style.opacity =
                        "0.72";
                }

                suggestion.addEventListener(
                    "click",
                    function () {
                        selectMember(
                            member
                        );
                    }
                );

                memberSuggestions.appendChild(
                    suggestion
                );
            }
        );

        memberSuggestions.classList.add(
            "show"
        );
    }


    async function searchMembers(searchValue) {
        if (memberSearchController) {
            memberSearchController.abort();
        }

        memberSearchController =
            new AbortController();

        showMemberMessage(
            "Searching members..."
        );

        try {
            const response =
                await fetch(
                    "search_product_loan_members.php?query=" +
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
                            memberSearchController.signal
                    }
                );

            const responseText =
                await response.text();

            let result;

            try {
                result =
                    JSON.parse(responseText);
            } catch (jsonError) {
                throw new Error(
                    "The server returned an invalid member response."
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
        } catch (error) {
            if (
                error.name ===
                "AbortError"
            ) {
                return;
            }

            console.error(
                "Member search error:",
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
            clearFieldValidation(
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
                currentValue.toLowerCase() !==
                selectedName.toLowerCase()
            ) {
                clearSelectedMember();
            }

            window.clearTimeout(
                memberSearchTimer
            );

            if (currentValue.length < 3) {
                hideMemberSuggestions();

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


    memberNameInput.addEventListener(
        "focus",
        function () {
            const currentValue =
                normalizeText(
                    memberNameInput.value
                );

            if (
                currentValue.length >= 3 &&
                !memberIDInput.value
            ) {
                searchMembers(
                    currentValue
                );
            }
        }
    );


    document.addEventListener(
        "click",
        function (event) {
            if (
                !event.target.closest(
                    ".product-loan-member-wrapper"
                )
            ) {
                hideMemberSuggestions();
            }
        }
    );


    /* =====================================================
       LOAN TERM AND SUMMARY
    ===================================================== */

    function getSelectedLoanTerm() {
        const selectedOption =
            loanTermInput.options[
                loanTermInput.selectedIndex
            ];

        if (
            !selectedOption ||
            !selectedOption.value
        ) {
            return {
                days: 0,
                interestRate: 0
            };
        }

        return {
            days:
                Number(
                    selectedOption.value
                ),

            interestRate:
                Number(
                    selectedOption.dataset
                        .interest
                )
        };
    }


    function calculatePrincipal() {
        return roundMoney(
            loanItems.reduce(
                function (
                    total,
                    item
                ) {
                    return total +
                        Number(
                            item.line_total
                        );
                },
                0
            )
        );
    }


    function updateLoanSummary() {
        const principal =
            calculatePrincipal();

        const loanTerm =
            getSelectedLoanTerm();

        const scheduledInterest =
            roundMoney(
                principal *
                (
                    loanTerm.interestRate /
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
            loanTerm.interestRate > 0
                ? loanTerm.interestRate + "%"
                : "—";

        loanPeriodDisplay.textContent =
            loanTerm.days > 0
                ? loanTerm.days + " Days"
                : "—";

        scheduledInterestDisplay.textContent =
            formatMoney(
                scheduledInterest
            );

        scheduledTotalDisplay.textContent =
            formatMoney(
                scheduledTotal
            );

        const validPaymentEstimate =
            principal > 0 &&
            loanTerm.days > 0 &&
            Number.isFinite(scheduledTotal) &&
            scheduledTotal > 0;

        if (dailyPaymentDisplay) {
            dailyPaymentDisplay.textContent =
                validPaymentEstimate
                    ? formatMoney(scheduledTotal / loanTerm.days)
                    : "—";
        }

        if (monthlyPaymentDisplay) {
            monthlyPaymentDisplay.textContent =
                validPaymentEstimate
                    ? formatMoney(scheduledTotal / (loanTerm.days / 30))
                    : "—";
        }

        itemsHiddenInput.value =
            JSON.stringify(
                loanItems
            );
    }


    loanTermInput.addEventListener(
        "change",
        function () {
            clearFieldValidation(
                loanTermInput
            );

            if (loanTermInput.value) {
                setFieldValid(
                    loanTermInput
                );
            }

            dueDateInput.value =
                "Generated upon delivery";

            updateLoanSummary();
        }
    );


    /* =====================================================
       PRODUCT LIST CATALOG AND REQUESTED ITEMS
    ===================================================== */

    function getCatalogTileData(tile) {
        if (!tile) {
            return null;
        }

        const loanProductID =
            Number(
                tile.dataset.loanProductId
            );

        const productName =
            normalizeText(
                tile.dataset.productName
            );

        const loanPrice =
            roundMoney(
                Number(
                    tile.dataset.loanPrice
                )
            );

        const reservedQuantity =
            Number(
                tile.dataset.reservedQuantity
            );

        if (
            !Number.isInteger(loanProductID) ||
            loanProductID <= 0 ||
            !productName ||
            !Number.isFinite(loanPrice) ||
            loanPrice <= 0 ||
            !Number.isInteger(reservedQuantity) ||
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
        productCatalogTiles.forEach(
            function (tile) {
                tile.classList.remove(
                    "is-selected"
                );
            }
        );
    }


    function resetProductSelection(
        focusSearch = false
    ) {
        selectedProduct = null;

        if (selectedProductIDInput) {
            selectedProductIDInput.value =
                "";
        }

        if (
            selectedProductNameDisplay
        ) {

            selectedProductNameDisplay.textContent =
                "No product selected";

        }

        if (selectedProductPriceDisplay) {
            selectedProductPriceDisplay.textContent =
                "₱0.00";
        }

        if (selectedProductReservedDisplay) {
            selectedProductReservedDisplay.textContent =
                "0";
        }

        if (quantityInput) {
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
                false;

        }

        clearSelectedCatalogTile();

        refreshQuantityValidation();

        if (
            focusSearch &&
            productSearchInput
        ) {
            productSearchInput.focus();
        }
    }


    function selectCatalogProduct(tile) {
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
            loanItems.some(
                function (item) {
                    return (
                        Number(
                            item.loan_product_id
                        ) ===
                        product.loan_product_id
                    );
                }
            );

        if (alreadyAdded) {
            showAlert(
                "warning",
                "Product already added",
                "Remove the existing requested product first if you need to change its quantity."
            );

            return;
        }

        selectedProduct =
            product;

        clearSelectedCatalogTile();

        tile.classList.add(
            "is-selected"
        );

        if (selectedProductIDInput) {
            selectedProductIDInput.value =
                String(
                    product.loan_product_id
                );
        }

        if (selectedProductNameDisplay) {
            selectedProductNameDisplay.textContent =
                product.product_name;
        }

        if (selectedProductPriceDisplay) {
            selectedProductPriceDisplay.textContent =
                formatMoney(
                    product.loan_price
                );
        }

        if (selectedProductReservedDisplay) {
            selectedProductReservedDisplay.textContent =
                String(
                    product.reserved_quantity
                );
        }

        if (quantityInput) {
            quantityInput.min =
                "1";

            quantityInput.removeAttribute(
                "max"
            );

            quantityInput.value =
                "1";
        }

        if (selectedProductPanel) {
            selectedProductPanel.hidden =
                false;
        }

        refreshQuantityValidation();
    }


    function updateCatalogSearch() {
        const query =
            normalizeText(
                productSearchInput
                    ? productSearchInput.value
                    : ""
            ).toLowerCase();

        let visibleCount =
            0;

        productCatalogTiles.forEach(
            function (tile) {
                const productName =
                    normalizeText(
                        tile.dataset.productName
                    ).toLowerCase();

                const matches =
                    !query ||
                    productName.includes(
                        query
                    );

                tile.hidden =
                    !matches;

                if (matches) {
                    visibleCount +=
                        1;
                }
            }
        );

        if (productCatalogCount) {
            productCatalogCount.textContent =
                String(
                    visibleCount
                );
        }

        if (productCatalogNoMatch) {
            productCatalogNoMatch.hidden =
                !(
                    productCatalogTiles.length > 0 &&
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


        if (rawValue === "") {

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
                ? quantityInput.closest(
                    ".product-loan-quantity-stepper"
                )
                : null;


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
                ? Number(
                    quantityInput.value
                )
                : 0;


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


    function showEmptyItemsRow() {
        itemsTableBody.innerHTML = "";

        const emptyRow =
            document.createElement("tr");

        emptyRow.className =
            "product-loan-items-empty";

        emptyRow.innerHTML =
            '<td colspan="4">' +
                '<i class="bi bi-cart3"></i>' +
                '<strong>Request is empty</strong>' +
                '<span>Select a product then click Add to Request.</span>' +
            "</td>";

        itemsTableBody.appendChild(
            emptyRow
        );
    }

    function changeLoanItemQuantity(
        index,
        change
    ) {
        if (
            index < 0 ||
            index >= loanItems.length
        ) {
            return;
        }

        const item =
            loanItems[index];

        const currentQuantity =
            Number(
                item.quantity
            ) || 1;

        const newQuantity =
            currentQuantity +
            change;

        if (newQuantity < 1) {
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

        renderLoanItems();
    }


    function refreshCatalogAddedState() {
        const addedIDs =
            new Set(
                loanItems.map(
                    function (item) {
                        return Number(
                            item.loan_product_id
                        );
                    }
                )
            );

        productCatalogTiles.forEach(
            function (tile) {
                const productID =
                    Number(
                        tile.dataset.loanProductId
                    );

                tile.classList.toggle(
                    "is-added",
                    addedIDs.has(
                        productID
                    )
                );
            }
        );
    }


    function updateRequestTotals() {
        const itemCount =
            loanItems.length;

        const totalQuantity =
            loanItems.reduce(
                function (
                    total,
                    item
                ) {
                    return (
                        total +
                        Number(
                            item.quantity || 0
                        )
                    );
                },
                0
            );


        if (requestCountDisplay) {
            requestCountDisplay.textContent =
                itemCount +
                (
                    itemCount === 1
                        ? " item"
                        : " items"
                );
        }


        if (totalQuantityDisplay) {
            totalQuantityDisplay.textContent =
                totalQuantity +
                (
                    totalQuantity === 1
                        ? " unit"
                        : " units"
                );
        }
    }


    function renderLoanItems() {
        itemsTableBody.innerHTML = "";


        if (loanItems.length === 0) {
            showEmptyItemsRow();

            refreshCatalogAddedState();

            updateRequestTotals();

            updateLoanSummary();

            return;
        }


        loanItems.forEach(
            function (
                item,
                index
            ) {

                const row =
                    document.createElement("tr");


                /* =============================================
                PRODUCT
                ============================================== */

                const productCell =
                    document.createElement("td");

                productCell.className =
                    "product-loan-cart-product-cell";


                const productName =
                    document.createElement("strong");

                productName.textContent =
                    item.product_name;


                const productPrice =
                    document.createElement("small");

                productPrice.textContent =
                    formatMoney(
                        item.unit_price
                    ) +
                    " each";


                productCell.appendChild(
                    productName
                );

                productCell.appendChild(
                    productPrice
                );


                /* =============================================
                QUANTITY
                ============================================== */

                const quantityCell =
                    document.createElement("td");

                quantityCell.className =
                    "product-loan-cart-qty-cell";


                const quantityControl =
                    document.createElement("div");

                quantityControl.className =
                    "product-loan-cart-qty-control";


                const minusButton =
                    document.createElement("button");

                minusButton.type =
                    "button";

                minusButton.textContent =
                    "−";

                minusButton.disabled =
                    Number(
                        item.quantity
                    ) <= 1;


                minusButton.addEventListener(
                    "click",
                    function () {
                        changeLoanItemQuantity(
                            index,
                            -1
                        );
                    }
                );


                const quantityText =
                    document.createElement("span");

                quantityText.textContent =
                    item.quantity;


                const plusButton =
                    document.createElement("button");

                plusButton.type =
                    "button";

                plusButton.textContent =
                    "+";

                plusButton.addEventListener(
                    "click",
                    function () {
                        changeLoanItemQuantity(
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


                /* =============================================
                AMOUNT
                ============================================== */

                const amountCell =
                    document.createElement("td");

                amountCell.className =
                    "product-loan-cart-amount";

                amountCell.textContent =
                    formatMoney(
                        item.line_total
                    );


                /* =============================================
                REMOVE
                ============================================== */

                const actionCell =
                    document.createElement("td");


                const removeButton =
                    document.createElement("button");

                removeButton.type =
                    "button";

                removeButton.className =
                    "product-loan-remove-item-btn";

                removeButton.title =
                    "Remove product";

                removeButton.setAttribute(
                    "aria-label",
                    "Remove " +
                    item.product_name
                );

                removeButton.innerHTML =
                    '<i class="bi bi-trash3"></i>';


                removeButton.addEventListener(
                    "click",
                    function () {
                        removeLoanItem(
                            index
                        );
                    }
                );


                actionCell.appendChild(
                    removeButton
                );


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


                itemsTableBody.appendChild(
                    row
                );

            }
        );


        refreshCatalogAddedState();

        updateRequestTotals();

        updateLoanSummary();
    }


    function removeLoanItem(index) {
        if (
            index < 0 ||
            index >= loanItems.length
        ) {
            return;
        }


        loanItems.splice(
            index,
            1
        );


        renderLoanItems();
    }


    function addLoanItem() {

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
            loanItems.length >=
            MAX_PRODUCTS
        ) {

            showAlert(
                "warning",
                "Product limit reached",
                "A maximum of 50 products is allowed per application."
            );


            return;

        }


        const duplicateItem =
            loanItems.some(
                function (item) {

                    return (
                        Number(
                            item.loan_product_id
                        )
                        ===
                        selectedProduct.loan_product_id
                    );

                }
            );


        if (
            duplicateItem
        ) {

            showAlert(
                "warning",
                "Product already added",
                "This product is already in Requested Products. Use the + or − controls in the cart to adjust its quantity."
            );


            return;

        }


        loanItems.push({

            loan_product_id:
                selectedProduct.loan_product_id,

            product_name:
                selectedProduct.product_name,

            quantity:
                quantity,

            unit_price:
                selectedProduct.loan_price,

            line_total:
                roundMoney(
                    quantity *
                    selectedProduct.loan_price
                )

        });


        renderLoanItems();


        resetProductSelection();

    }


    productCatalogTiles.forEach(
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


    if (productSearchInput) {
        productSearchInput.addEventListener(
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

                        addLoanItem();

                    }

                }

            }
        );

    }


    if (addItemButton) {
        addItemButton.addEventListener(
            "click",
            addLoanItem
        );
    }


    updateCatalogSearch();


    /* =====================================================
       ATTACHMENTS
    ===================================================== */

    function formatFileSize(bytes) {
        if (bytes < 1024) {
            return bytes + " B";
        }

        if (
            bytes <
            1024 * 1024
        ) {
            return (
                bytes / 1024
            ).toFixed(1) + " KB";
        }

        return (
            bytes /
            (
                1024 * 1024
            )
        ).toFixed(1) + " MB";
    }


    function getFileExtension(fileName) {
        const normalizedName =
            String(fileName || "")
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


    function getAttachmentIconClass(file) {
        const extension =
            getFileExtension(
                file.name
            );

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
            getFileExtension(
                file.name
            );

        if (
            !ALLOWED_FILE_EXTENSIONS
                .includes(extension)
        ) {
            return false;
        }

        /*
         * Some browsers leave file.type empty or use
         * application/octet-stream for Word files.
         * PHP still verifies the actual file contents.
         */

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


    function isDuplicateAttachment(
        file,
        fileList = selectedAttachments
    ) {
        return fileList.some(
            function (selectedFile) {
                return (
                    selectedFile.name ===
                        file.name &&
                    selectedFile.size ===
                        file.size &&
                    selectedFile.lastModified ===
                        file.lastModified
                );
            }
        );
    }


    function calculateSelectedAttachmentSize() {
        return selectedAttachments.reduce(
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


    function rebuildAttachmentInput() {
        const dataTransfer =
            new DataTransfer();

        selectedAttachments.forEach(
            function (file) {
                dataTransfer.items.add(
                    file
                );
            }
        );

        attachmentInput.files =
            dataTransfer.files;
    }


    function clearAttachmentValidation() {
        if (!attachmentUploadBox) {
            return;
        }

        attachmentUploadBox.classList.remove(
            "is-invalid"
        );

        attachmentInput.removeAttribute(
            "aria-invalid"
        );

        const oldError =
            loanForm.querySelector(
                ".product-loan-attachment-error"
            );

        if (oldError) {
            oldError.remove();
        }
    }


    function setAttachmentError(message) {
        if (!attachmentUploadBox) {
            return;
        }

        clearAttachmentValidation();

        attachmentUploadBox.classList.add(
            "is-invalid"
        );

        attachmentInput.setAttribute(
            "aria-invalid",
            "true"
        );

        const error =
            document.createElement("small");

        error.className =
            "product-loan-attachment-error product-loan-field-error";

        error.textContent =
            message;

        attachmentUploadBox.insertAdjacentElement(
            "afterend",
            error
        );
    }


    function renderAttachments() {
        attachmentList.innerHTML = "";

        if (
            selectedAttachments.length ===
            0
        ) {
            const noAttachment =
                document.createElement("div");

            noAttachment.className =
                "product-loan-no-attachment";

            noAttachment.textContent =
                "No attachments selected";

            attachmentList.appendChild(
                noAttachment
            );

            return;
        }

        selectedAttachments.forEach(
            function (
                file,
                index
            ) {
                const item =
                    document.createElement("div");

                item.className =
                    "product-loan-attachment-item";


                const information =
                    document.createElement("div");

                information.className =
                    "product-loan-attachment-info";


                const icon =
                    document.createElement("i");

                icon.className =
                    getAttachmentIconClass(
                        file
                    );


                const details =
                    document.createElement("span");

                details.textContent =
                    file.name +
                    " (" +
                    formatFileSize(
                        file.size
                    ) +
                    ")";


                const removeButton =
                    document.createElement("button");

                removeButton.type =
                    "button";

                removeButton.className =
                    "product-loan-remove-attachment";

                removeButton.title =
                    "Remove attachment";

                removeButton.setAttribute(
                    "aria-label",
                    "Remove " +
                    file.name
                );

                removeButton.innerHTML =
                    '<i class="bi bi-x-circle-fill"></i>';


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


                information.appendChild(
                    icon
                );

                information.appendChild(
                    details
                );

                item.appendChild(
                    information
                );

                item.appendChild(
                    removeButton
                );

                attachmentList.appendChild(
                    item
                );
            }
        );
    }


    attachmentInput.addEventListener(
        "change",
        function () {
            const newFiles =
                Array.from(
                    attachmentInput.files
                );

            const validFiles = [];


            for (const file of newFiles) {
                if (
                    !isAllowedAttachment(
                        file
                    )
                ) {
                    showAlert(
                        "warning",
                        "Unsupported file",
                        file.name +
                        " is not supported. Only JPG, PNG, PDF, DOC, and DOCX files are allowed."
                    );

                    continue;
                }


                if (file.size <= 0) {
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
                        " exceeds the 10 MB file limit."
                    );

                    continue;
                }


                if (
                    isDuplicateAttachment(
                        file
                    ) ||
                    isDuplicateAttachment(
                        file,
                        validFiles
                    )
                ) {
                    continue;
                }


                validFiles.push(
                    file
                );
            }


            const availableSlots =
                MAX_ATTACHMENTS -
                selectedAttachments.length;


            if (
                validFiles.length >
                availableSlots
            ) {
                showAlert(
                    "warning",
                    "Attachment limit reached",
                    "A maximum of " +
                    MAX_ATTACHMENTS +
                    " attachments is allowed per upload."
                );
            }


            const filesWithinCountLimit =
                validFiles.slice(
                    0,
                    Math.max(
                        availableSlots,
                        0
                    )
                );


            let combinedSize =
                calculateSelectedAttachmentSize();

            const acceptedFiles = [];

            const skippedForTotalSize = [];


            for (
                const file of
                filesWithinCountLimit
            ) {
                if (
                    combinedSize +
                    file.size >
                    MAX_TOTAL_FILE_SIZE
                ) {
                    skippedForTotalSize.push(
                        file.name
                    );

                    continue;
                }

                acceptedFiles.push(
                    file
                );

                combinedSize +=
                    file.size;
            }


            if (
                skippedForTotalSize.length >
                0
            ) {
                showAlert(
                    "warning",
                    "Combined size limit reached",
                    "Some files were not added because all selected attachments must not exceed 50 MB in total."
                );
            }


            selectedAttachments =
                selectedAttachments.concat(
                    acceptedFiles
                );

            if (
                selectedAttachments.length >
                0
            ) {
                clearAttachmentValidation();
            }

            rebuildAttachmentInput();

            renderAttachments();
        }
    );


    /* =====================================================
       FORM VALIDATION
    ===================================================== */

    function validateLoanForm() {
        clearAllValidation();
        clearAttachmentValidation();

        let firstInvalidField = null;


        if (!memberIDInput.value) {
            setFieldError(
                memberNameInput,
                "Select an eligible Regular member from the suggestions."
            );

            firstInvalidField =
                memberNameInput;
        } else {
            setFieldValid(
                memberNameInput
            );
        }


        if (!loanTermInput.value) {
            setFieldError(
                loanTermInput,
                "Select the agreed loan term."
            );

            firstInvalidField =
                firstInvalidField ||
                loanTermInput;
        } else {
            setFieldValid(
                loanTermInput
            );
        }


        if (loanItems.length === 0) {
            showAlert(
                "warning",
                "No requested products",
                "Add at least one product before saving the application."
            );

            firstInvalidField =
                firstInvalidField ||
                productSearchInput ||
                addItemButton;
        }


        const attachmentMissing =
            selectedAttachments.length === 0;

        if (attachmentMissing) {
            setAttachmentError(
                "At least one supporting document is required."
            );
        }


        if (
            selectedAttachments.length >
            MAX_ATTACHMENTS
        ) {
            showAlert(
                "warning",
                "Attachment limit exceeded",
                "Remove attachments until only 10 files remain."
            );

            return false;
        }


        if (
            calculateSelectedAttachmentSize() >
            MAX_TOTAL_FILE_SIZE
        ) {
            showAlert(
                "warning",
                "Combined size limit exceeded",
                "All selected attachments must not exceed 50 MB in total."
            );

            return false;
        }


        if (firstInvalidField) {
            firstInvalidField.focus();

            return false;
        }

        if (attachmentMissing) {
            if (attachmentUploadBox) {
                attachmentUploadBox.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });
            }

            return false;
        }

        return true;
    }


    /* =====================================================
       SAVE APPLICATION
    ===================================================== */

    async function submitLoanApplication() {
        if (formIsSubmitting) {
            return;
        }

        formIsSubmitting = true;

        setButtonLoading(
            saveButton,
            true
        );

        itemsHiddenInput.value =
            JSON.stringify(
                loanItems
            );

        try {
            const formData =
                new FormData(
                    loanForm
                );

            formData.set(
                "csrf_token",
                csrfToken
            );

            const response =
                await fetch(
                    "save_product_loan.php",
                    {
                        method: "POST",
                        body: formData,
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
            } catch (jsonError) {
                console.error(
                    "Invalid server response:",
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
                    "Unable to save the Product Loan application."
                );
            }


            if (
                typeof Swal !==
                "undefined"
            ) {
                await Swal.fire({
                    icon: "success",
                    title:
                        "Application saved",
                    text:
                        result.message ||
                        "The Product Loan application was saved as Pending.",
                    confirmButtonText:
                        "OK",
                    confirmButtonColor:
                        "#93a95d",
                    allowOutsideClick:
                        false
                });
            } else {
                window.alert(
                    result.message ||
                    "The Product Loan application was saved."
                );
            }


            const modalInstance =
                bootstrap.Modal.getInstance(
                    modalElement
                );

            if (modalInstance) {
                modalInstance.hide();
            }

            window.location.reload();
        } catch (error) {
            console.error(
                "Save Product Loan error:",
                error
            );

            showAlert(
                "error",
                "Unable to save",
                error.message ||
                "Something went wrong while saving the application."
            );
        } finally {
            formIsSubmitting = false;

            setButtonLoading(
                saveButton,
                false
            );
        }
    }


    loanForm.addEventListener(
        "submit",
        async function (event) {
            event.preventDefault();

            if (!validateLoanForm()) {
                return;
            }

            const principal =
                calculatePrincipal();

            const loanTerm =
                getSelectedLoanTerm();

            const scheduledInterest =
                roundMoney(
                    principal *
                    (
                        loanTerm.interestRate /
                        100
                    )
                );

            const scheduledTotal =
                roundMoney(
                    principal +
                    scheduledInterest
                );

            let confirmed = true;


            if (
                typeof Swal !==
                "undefined"
            ) {
                const result =
                    await Swal.fire({
                        icon:
                            "question",

                        title:
                            "Are you sure the details are final?",

                        html:
                            '<div style="text-align:left; line-height:1.6;">' +

                                '<p style="margin:0 0 10px;">' +
                                    "Please review the member, products, prices, and loan term before saving." +
                                "</p>" +

                                '<p style="margin:0 0 6px;">' +
                                    "<strong>Principal:</strong> " +
                                    escapeHTML(
                                        formatMoney(
                                            principal
                                        )
                                    ) +
                                "</p>" +

                                '<p style="margin:0 0 6px;">' +
                                    "<strong>Interest Rate:</strong> " +
                                    loanTerm.interestRate +
                                    "% for " +
                                    loanTerm.days +
                                    " days" +
                                "</p>" +

                                '<p style="margin:0 0 12px;">' +
                                    "<strong>Payoff Total:</strong> " +
                                    escapeHTML(
                                        formatMoney(
                                            scheduledTotal
                                        )
                                    ) +
                                "</p>" +

                                "<small>" +
                                    "The application may still be edited while Pending. " +
                                    "Once marked Delivered, its financial details will be locked and interest will begin." +
                                "</small>" +

                            "</div>",

                        showCancelButton:
                            true,

                        confirmButtonText:
                            "YES, SAVE",

                        cancelButtonText:
                            "REVIEW AGAIN",

                        confirmButtonColor:
                            "#93a95d",

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
                    result.isConfirmed;
            } else {
                confirmed =
                    window.confirm(
                        "Are you sure the details are final?"
                    );
            }


            if (confirmed) {
                submitLoanApplication();
            }
        }
    );


    /* =====================================================
       RESET MODAL
    ===================================================== */

    function resetLoanForm() {
        loanForm.reset();

        clearSelectedMember();

        hideMemberSuggestions();

        clearAllValidation();
        clearAttachmentValidation();

        loanItems = [];

        selectedProduct = null;

        selectedAttachments = [];

        itemsHiddenInput.value = "";

        if (productSearchInput) {
            productSearchInput.value =
                "";
        }

        resetProductSelection();

        updateCatalogSearch();

        applicationDateInput.value = "";

        dueDateInput.value =
            "Generated upon delivery";

        renderLoanItems();

        rebuildAttachmentInput();

        renderAttachments();

        updateLoanSummary();

        formIsSubmitting = false;

        setButtonLoading(
            saveButton,
            false
        );
    }


    modalElement.addEventListener(
        "show.bs.modal",
        function () {
            resetLoanForm();

            loadServerApplicationDate();
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

    renderLoanItems();

    renderAttachments();

    updateLoanSummary();
});