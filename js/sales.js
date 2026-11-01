document.addEventListener(
    "DOMContentLoaded",
    function(){

        /* =====================================================
           STATE
        ===================================================== */

        let cart = [];

        /*
        Remember the last member that was legitimately
        selected from the search results.
        */
        let selectedMemberID = "";
        let selectedMemberName = "";
        let selectedMemberCode = "";

        let memberSearchTimer = null;
        let memberSearchController = null;

        /*
        Prevent duplicate confirmation dialogs / submissions.
        */
        let saleConfirmationOpen = false;
        let finalSubmissionStarted = false;

        /*
        Current month displayed by the custom
        Transaction Date calendar.
        */
        let transactionCalendarView = null;


        /* =====================================================
           ELEMENTS
        ===================================================== */

        const salesForm =
            document.getElementById(
                "salesForm"
            );

        const addSalesModal =
            document.getElementById(
                "addSalesModal"
            );


        /* =====================================================
           CUSTOMER
        ===================================================== */

        const affiliateButton =
            document.getElementById(
                "selectedAffiliate"
            );

        const affiliateText =
            document.getElementById(
                "salesAffiliateText"
            );

        const affiliateInput =
            document.getElementById(
                "affiliate"
            );

        const affiliatePopup =
            document.getElementById(
                "affiliatePopup"
            );

        const buyerInput =
            document.getElementById(
                "buyer_name"
            );

        const buyerHint =
            document.getElementById(
                "salesBuyerHint"
            );

        const buyerWrapper =
            buyerInput
            ?
            buyerInput.closest(
                ".sales-buyer-wrapper"
            )
            :
            null;

        const memberIDInput =
            document.getElementById(
                "salesMemberId"
            );

        const memberSuggestions =
            document.getElementById(
                "salesMemberSuggestion"
            );

        const memberStatus =
            document.getElementById(
                "salesMemberStatus"
            );

        const selectedMemberCodeElement =
            document.getElementById(
                "salesSelectedMemberCode"
            );

        const transactionDateInput =
            document.getElementById(
                "salesTransactionDate"
            );

        const transactionDateButton =
            document.getElementById(
                "salesTransactionDateBtn"
            );

        const transactionDateText =
            document.getElementById(
                "salesTransactionDateText"
            );

        const transactionDateCalendar =
            document.getElementById(
                "salesTransactionCalendar"
            );

        const transactionDateCalendarMonth =
            document.getElementById(
                "salesTransactionCalendarMonth"
            );

        const transactionDateCalendarDays =
            document.getElementById(
                "salesTransactionCalendarDays"
            );

        const transactionDateCalendarPrev =
            document.getElementById(
                "salesTransactionCalendarPrev"
            );

        const transactionDateCalendarNext =
            document.getElementById(
                "salesTransactionCalendarNext"
            );

        const transactionHistoricalNote =
            document.getElementById(
                "salesTransactionHistoricalNote"
            );

        const transactionDateHint =
            document.getElementById(
                "salesTransactionDateHint"
            );

        const transactionDateWrapper =
            transactionDateButton
            ?
            transactionDateButton.closest(
                ".sales-transaction-date-wrapper"
            )
            :
            null;


        /* =====================================================
           PRODUCTS
        ===================================================== */

        const productSearch =
            document.getElementById(
                "selectedProduct"
            );

        const productInput =
            document.getElementById(
                "product"
            );

        const productTiles =
            Array.from(
                document.querySelectorAll(
                    ".product-item"
                )
            );

        const productResultCount =
            document.getElementById(
                "salesProductResultCount"
            );

        const productSearchEmpty =
            document.getElementById(
                "salesProductSearchEmpty"
            );


        /* =====================================================
           QUANTITY
        ===================================================== */

        const quantityInput =
            document.getElementById(
                "salesQty"
            );

        const quantityMinus =
            document.getElementById(
                "salesQtyMinus"
            );

        const quantityPlus =
            document.getElementById(
                "salesQtyPlus"
            );

        const addItemButton =
            document.getElementById(
                "addItemBtn"
            );


        /* =====================================================
           CART
        ===================================================== */

        const salesItems =
            document.getElementById(
                "salesItems"
            );

        const cartInput =
            document.getElementById(
                "cartInput"
            );

        const cartCount =
            document.getElementById(
                "salesCartCount"
            );

        const totalQuantityElement =
            document.getElementById(
                "salesTotalQuantity"
            );

        const grandTotal =
            document.getElementById(
                "grandTotal"
            );

        const cashReceivedInput =
            document.getElementById(
                "salesCashReceived"
            );

        const salesChangeElement =
            document.getElementById(
                "salesChange"
            );

        const saveButton =
            document.getElementById(
                "saveSalesButton"
            );


        const originalSaveButtonHTML =
            saveButton
            ?
            saveButton.innerHTML
            :
            "";


        /* =====================================================
           GENERAL HELPERS
        ===================================================== */

        function showSalesAlert(
            icon,
            title,
            text
        ){

            if(
                typeof Swal
                ===
                "undefined"
            ){

                alert(
                    title
                    +
                    "\n\n"
                    +
                    text
                );

                return;
            }


            return Swal.fire({

                icon: icon,

                title: title,

                text: text,

                confirmButtonColor:
                    "#8CA15B"

            });

        }


        function formatMoney(value){

            const number =
                Number(value);


            return (
                "₱"
                +
                (
                    Number.isFinite(number)
                    ?
                    number
                    :
                    0
                )
                .toLocaleString(
                    "en-PH",
                    {
                        minimumFractionDigits:
                            2,

                        maximumFractionDigits:
                            2
                    }
                )
            );

        }


        function escapeHTML(value){

            return String(
                value || ""
            )
            .replace(
                /&/g,
                "&amp;"
            )
            .replace(
                /</g,
                "&lt;"
            )
            .replace(
                />/g,
                "&gt;"
            )
            .replace(
                /"/g,
                "&quot;"
            )
            .replace(
                /'/g,
                "&#039;"
            );

        }


        function hideBootstrapModal(
            element
        ){

            if(
                !element
                ||
                typeof bootstrap
                ===
                "undefined"
            ){

                return;
            }


            bootstrap.Modal
                .getOrCreateInstance(
                    element
                )
                .hide();

        }


        /* =====================================================
           CART SUMMARY HELPERS
        ===================================================== */

        function getCartTotal(){

            return cart.reduce(
                function(
                    total,
                    item
                ){

                    return (
                        total
                        +
                        (
                            Number(item.price)
                            *
                            Number(item.qty)
                        )
                    );

                },
                0
            );

        }


        function getCartTotalQuantity(){

            return cart.reduce(
                function(
                    total,
                    item
                ){

                    return (
                        total
                        +
                        Number(item.qty)
                    );

                },
                0
            );

        }


        function getConfirmationCustomer(){

            const affiliate =
                affiliateInput
                ?
                affiliateInput.value.trim()
                :
                "";


            if(
                affiliate
                ===
                "Member"
            ){

                return (
                    selectedMemberName
                    ||
                    (
                        buyerInput
                        ?
                        buyerInput.value.trim()
                        :
                        ""
                    )
                    ||
                    "Member"
                );

            }


            const buyer =
                buyerInput
                ?
                buyerInput.value.trim()
                :
                "";


            return (
                buyer !== ""
                ?
                buyer
                :
                "Walk-in Customer"
            );

        }


        /* =====================================================
           CASH RECEIVED AND CHANGE
        ===================================================== */

        function getSalesCashDetails(){

            const raw =
                cashReceivedInput
                ?
                cashReceivedInput.value.trim()
                :
                "";

            const valid =
                /^\d+(\.\d{1,2})?$/.test(raw)
                &&
                Number.isFinite(Number(raw));

            const totalCents =
                Math.round(getCartTotal() * 100);

            const cashCents =
                valid
                ?
                Math.round(Number(raw) * 100)
                :
                0;

            return {
                valid: valid,
                totalCents: totalCents,
                cashCents: cashCents,
                changeCents: Math.max(
                    0,
                    cashCents - totalCents
                )
            };

        }


        function updateSalesChange(){

            if(!salesChangeElement){
                return;
            }

            const details =
                getSalesCashDetails();

            salesChangeElement.textContent =
                formatMoney(
                    details.valid
                    ?
                    details.changeCents / 100
                    :
                    0
                );

        }


        if(cashReceivedInput){

            cashReceivedInput.addEventListener(
                "input",
                updateSalesChange
            );

        }


        /* =====================================================
           SAVE CONFIRMATION
        ===================================================== */

        async function confirmSalesTransaction(){

            const customer =
                getConfirmationCustomer();


            const totalQuantity =
                getCartTotalQuantity();


            const total =
                getCartTotal();


            if(
                typeof Swal
                ===
                "undefined"
            ){

                return window.confirm(
                    "Confirm Sales Transaction?\n\n"
                    +
                    "Customer: "
                    +
                    customer
                    +
                    "\n"
                    +
                    "Items: "
                    +
                    totalQuantity
                    +
                    " unit(s)\n"
                    +
                    "Total: "
                    +
                    formatMoney(total)
                    +
                    "\n\n"
                    +
                    "Save this transaction?"
                );

            }


            const result =
                await Swal.fire({

                    icon:
                        "question",

                    title:
                        "Confirm Sales Transaction?",

                    html:
                        `
                        <div
                        style="
                            text-align:left;
                            max-width:320px;
                            margin:8px auto 0;
                            font-size:14px;
                            line-height:1.7;
                        ">

                            <div>
                                <strong>Customer:</strong>
                                ${escapeHTML(customer)}
                            </div>

                            <div>
                                <strong>Items:</strong>
                                ${escapeHTML(totalQuantity)}
                                unit(s)
                            </div>

                            <div>
                                <strong>Total:</strong>
                                ${escapeHTML(
                                    formatMoney(total)
                                )}
                            </div>

                        </div>
                        `,

                    showCancelButton:
                        true,

                    confirmButtonText:
                        "Yes, Save Sale",

                    cancelButtonText:
                        "Cancel",

                    confirmButtonColor:
                        "#8CA15B",

                    cancelButtonColor:
                        "#6c757d",

                    reverseButtons:
                        true,

                    focusCancel:
                        true,

                    allowOutsideClick:
                        false,

                    allowEscapeKey:
                        true

                });


            return Boolean(
                result.isConfirmed
            );

        }


        /* =====================================================
           MEMBER TEXT NORMALIZATION
        ===================================================== */

        function normalizeMemberText(
            value
        ){

            return String(
                value || ""
            )
            .trim()
            .replace(
                /\s+/g,
                " "
            )
            .toLocaleLowerCase(
                "en-PH"
            );

        }


        function rememberedMemberExists(){

            return (
                selectedMemberID !== ""
                &&
                selectedMemberName !== ""
            );

        }


        /* =====================================================
           MANILA DATE
        ===================================================== */

        function getManilaDateISO(){

            const parts =
                new Intl.DateTimeFormat(
                    "en-US",
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
                )
                .formatToParts(
                    new Date()
                );


            const values = {};


            parts.forEach(
                function(part){

                    if(
                        part.type
                        !==
                        "literal"
                    ){

                        values[
                            part.type
                        ] =
                            part.value;

                    }

                }
            );


            return (
                values.year
                +
                "-"
                +
                values.month
                +
                "-"
                +
                values.day
            );

        }


        function isValidISODate(
            value
        ){

            if(
                !/^\d{4}-\d{2}-\d{2}$/
                    .test(value)
            ){

                return false;
            }


            const parts =
                value
                    .split("-")
                    .map(Number);


            const year =
                parts[0];

            const month =
                parts[1];

            const day =
                parts[2];


            const date =
                new Date(
                    Date.UTC(
                        year,
                        month - 1,
                        day
                    )
                );


            return (
                date.getUTCFullYear()
                ===
                year

                &&

                date.getUTCMonth()
                ===
                month - 1

                &&

                date.getUTCDate()
                ===
                day
            );

        }


        function salesPadNumber(
            value
        ){

            return String(
                value
            )
            .padStart(
                2,
                "0"
            );

        }


        function buildSalesDateKey(
            year,
            month,
            day
        ){

            return (
                String(year)
                +
                "-"
                +
                salesPadNumber(
                    month + 1
                )
                +
                "-"
                +
                salesPadNumber(
                    day
                )
            );

        }


        function parseSalesDateKey(
            value
        ){

            if(
                !isValidISODate(
                    value
                )
            ){

                return null;
            }


            const parts =
                value
                    .split("-")
                    .map(Number);


            return {
                year:
                    parts[0],

                month:
                    parts[1] - 1,

                day:
                    parts[2]
            };

        }


        function formatSalesTransactionDate(
            value
        ){

            const parts =
                parseSalesDateKey(
                    value
                );


            if(!parts){

                return "";
            }


            const date =
                new Date(
                    Date.UTC(
                        parts.year,
                        parts.month,
                        parts.day
                    )
                );


            return new Intl.DateTimeFormat(
                "en-US",
                {
                    timeZone:
                        "UTC",

                    month:
                        "long",

                    day:
                        "numeric",

                    year:
                        "numeric"
                }
            )
            .format(
                date
            );

        }


        function updateTransactionDateDisplay(){

            if(
                !transactionDateInput
            ){

                return;
            }


            const value =
                transactionDateInput
                    .value
                    .trim();


            const today =
                getManilaDateISO();


            if(
                transactionDateText
            ){

                transactionDateText.textContent =
                    formatSalesTransactionDate(
                        value
                    )
                    ||
                    "Select transaction date";

            }


            const isHistorical =
                isValidISODate(
                    value
                )
                &&
                value < today;


            if(
                transactionHistoricalNote
            ){

                transactionHistoricalNote.hidden =
                    !isHistorical;


                transactionHistoricalNote.textContent =
                    "Historical / Audit Entry • Past transaction date selected.";

            }


            if(
                transactionDateHint
            ){

                transactionDateHint.hidden =
                    isHistorical;


                transactionDateHint.textContent =
                    "Select when the sale occurred. Past dates are allowed for historical records.";

            }

        }


        function closeTransactionDateCalendar(){

            if(
                transactionDateCalendar
            ){

                transactionDateCalendar
                    .classList
                    .remove(
                        "show"
                    );


                transactionDateCalendar
                    .setAttribute(
                        "aria-hidden",
                        "true"
                    );

            }


            if(
                transactionDateButton
            ){

                transactionDateButton
                    .setAttribute(
                        "aria-expanded",
                        "false"
                    );

            }

        }


        function renderTransactionDateCalendar(){

            if(
                !transactionDateCalendarMonth
                ||
                !transactionDateCalendarDays
                ||
                !transactionDateInput
            ){

                return;
            }


            const today =
                getManilaDateISO();


            const todayParts =
                parseSalesDateKey(
                    today
                );


            const selectedParts =
                parseSalesDateKey(
                    transactionDateInput
                        .value
                        .trim()
                );


            if(
                !transactionCalendarView
            ){

                const source =
                    selectedParts
                    ||
                    todayParts;


                transactionCalendarView =
                    new Date(
                        source.year,
                        source.month,
                        1
                    );

            }


            const year =
                transactionCalendarView
                    .getFullYear();


            const month =
                transactionCalendarView
                    .getMonth();


            transactionDateCalendarMonth.textContent =
                new Intl.DateTimeFormat(
                    "en-US",
                    {
                        month:
                            "long",

                        year:
                            "numeric"
                    }
                )
                .format(
                    new Date(
                        year,
                        month,
                        1
                    )
                );


            transactionDateCalendarDays.innerHTML =
                "";


            const firstDay =
                new Date(
                    year,
                    month,
                    1
                )
                .getDay();


            const daysInMonth =
                new Date(
                    year,
                    month + 1,
                    0
                )
                .getDate();


            for(
                let blank = 0;
                blank < firstDay;
                blank++
            ){

                const empty =
                    document.createElement(
                        "span"
                    );


                empty.className =
                    "sales-transaction-calendar-empty";


                transactionDateCalendarDays
                    .appendChild(
                        empty
                    );

            }


            for(
                let day = 1;
                day <= daysInMonth;
                day++
            ){

                const dateKey =
                    buildSalesDateKey(
                        year,
                        month,
                        day
                    );


                const dayButton =
                    document.createElement(
                        "button"
                    );


                dayButton.type =
                    "button";


                dayButton.className =
                    "sales-transaction-calendar-day";


                dayButton.textContent =
                    String(
                        day
                    );


                dayButton.dataset.date =
                    dateKey;


                if(
                    dateKey > today
                ){

                    dayButton.disabled =
                        true;


                    dayButton.classList.add(
                        "sales-transaction-calendar-future"
                    );


                    dayButton.title =
                        "Future transaction dates are not allowed.";

                }
                else if(
                    dateKey < today
                ){

                    dayButton.classList.add(
                        "sales-transaction-calendar-history"
                    );


                    dayButton.title =
                        "Historical / Audit Entry";

                }


                if(
                    dateKey === today
                ){

                    dayButton.classList.add(
                        "sales-transaction-calendar-today"
                    );


                    dayButton.title =
                        "Today";

                }


                if(
                    dateKey
                    ===
                    transactionDateInput
                        .value
                        .trim()
                ){

                    dayButton.classList.add(
                        "sales-transaction-calendar-selected"
                    );

                }


                dayButton.addEventListener(
                    "click",
                    function(event){

                        event.preventDefault();
                        event.stopPropagation();


                        if(
                            this.disabled
                        ){

                            return;
                        }


                        const selectedDate =
                            this.dataset.date
                            ||
                            "";


                        if(
                            !isValidISODate(
                                selectedDate
                            )
                            ||
                            selectedDate > today
                        ){

                            return;
                        }


                        transactionDateInput.value =
                            selectedDate;


                        const selected =
                            parseSalesDateKey(
                                selectedDate
                            );


                        transactionCalendarView =
                            new Date(
                                selected.year,
                                selected.month,
                                1
                            );


                        updateTransactionDateDisplay();

                        renderTransactionDateCalendar();

                        closeTransactionDateCalendar();

                    }
                );


                transactionDateCalendarDays
                    .appendChild(
                        dayButton
                    );

            }


            if(
                transactionDateCalendarNext
                &&
                todayParts
            ){

                const viewMonthKey =
                    (
                        year * 12
                    )
                    +
                    month;


                const todayMonthKey =
                    (
                        todayParts.year * 12
                    )
                    +
                    todayParts.month;


                transactionDateCalendarNext.disabled =
                    viewMonthKey
                    >=
                    todayMonthKey;

            }

        }


        function configureTransactionDate(
            forceToday
        ){

            if(
                !transactionDateInput
            ){

                return;
            }


            const today =
                getManilaDateISO();


            let value =
                transactionDateInput
                    .value
                    .trim();


            if(
                forceToday
                ||
                !isValidISODate(
                    value
                )
                ||
                value > today
            ){

                value =
                    today;


                transactionDateInput.value =
                    today;

            }


            const selected =
                parseSalesDateKey(
                    value
                );


            if(selected){

                transactionCalendarView =
                    new Date(
                        selected.year,
                        selected.month,
                        1
                    );

            }


            updateTransactionDateDisplay();

            renderTransactionDateCalendar();

            closeTransactionDateCalendar();

        }


        /* =====================================================
           SAVE BUTTON
        ===================================================== */

        function setSaveButtonLoading(){

            if(!saveButton){

                return;
            }


            saveButton.disabled =
                true;


            const title =
                saveButton.querySelector(
                    "strong"
                );


            if(title){

                title.textContent =
                    "SAVING...";

            }

        }


        function resetSaveButton(){

            if(!saveButton){

                return;
            }


            saveButton.disabled =
                false;


            if(
                originalSaveButtonHTML
            ){

                saveButton.innerHTML =
                    originalSaveButtonHTML;

            }

        }


        /* =====================================================
           CUSTOMER TYPE
        ===================================================== */

        function closeAffiliatePopup(){

            if(!affiliatePopup){

                return;
            }


            affiliatePopup
                .classList
                .remove(
                    "show"
                );

        }


        /* =====================================================
           MEMBER SUGGESTIONS
        ===================================================== */

        function hideMemberSuggestions(){

            if(
                !memberSuggestions
            ){

                return;
            }


            memberSuggestions.innerHTML =
                "";


            memberSuggestions
                .classList
                .remove(
                    "show"
                );

        }


        /* =====================================================
           VERIFIED MEMBER UI
        ===================================================== */

        function removeVerifiedMemberUI(){

            if(memberStatus){

                memberStatus.hidden =
                    true;

            }


            if(
                selectedMemberCodeElement
            ){

                selectedMemberCodeElement
                    .textContent =
                    "";

            }


            if(buyerWrapper){

                buyerWrapper
                    .classList
                    .remove(
                        "has-verified-member"
                    );


                buyerWrapper
                    .removeAttribute(
                        "data-member-code"
                    );

            }


            if(buyerInput){

                buyerInput
                    .classList
                    .remove(
                        "is-verified-member"
                    );


                buyerInput.removeAttribute(
                    "title"
                );

            }

        }


        function showVerifiedMemberUI(){

            if(
                !rememberedMemberExists()
            ){

                return;
            }


            if(memberStatus){

                memberStatus.hidden =
                    true;

            }


            if(
                selectedMemberCodeElement
            ){

                selectedMemberCodeElement
                    .textContent =
                    "";

            }


            if(buyerWrapper){

                buyerWrapper
                    .classList
                    .add(
                        "has-verified-member"
                    );


                buyerWrapper.dataset
                    .memberCode =
                    selectedMemberCode
                    ||
                    "";

            }


            if(buyerInput){

                buyerInput
                    .classList
                    .add(
                        "is-verified-member"
                    );


                buyerInput.title =
                    selectedMemberCode
                    ?
                    "Verified Member · "
                    +
                    selectedMemberCode
                    :
                    "Verified Member";

            }

        }


        /* =====================================================
           MEMBER VERIFICATION STATE
        ===================================================== */

        function setMemberTemporarilyUnverified(){

            if(memberIDInput){

                memberIDInput.value =
                    "";

            }


            removeVerifiedMemberUI();

        }


        function restoreRememberedMember(
            useOfficialName
        ){

            if(
                !rememberedMemberExists()
            ){

                return false;
            }


            if(memberIDInput){

                memberIDInput.value =
                    selectedMemberID;

            }


            if(
                useOfficialName
                &&
                buyerInput
            ){

                buyerInput.value =
                    selectedMemberName;

            }


            showVerifiedMemberUI();

            clearBuyerInlineError();


            return true;

        }


        function clearRememberedMember(){

            selectedMemberID =
                "";

            selectedMemberName =
                "";

            selectedMemberCode =
                "";


            if(memberIDInput){

                memberIDInput.value =
                    "";

            }


            removeVerifiedMemberUI();

        }


        function syncRememberedMember(
            canonicalize
        ){

            if(
                !buyerInput
                ||
                !rememberedMemberExists()
            ){

                setMemberTemporarilyUnverified();

                return false;
            }


            const typedName =
                normalizeMemberText(
                    buyerInput.value
                );


            const officialName =
                normalizeMemberText(
                    selectedMemberName
                );


            if(
                typedName !== ""
                &&
                typedName === officialName
            ){

                restoreRememberedMember(
                    Boolean(
                        canonicalize
                    )
                );


                return true;

            }


            setMemberTemporarilyUnverified();


            return false;

        }


        /* =====================================================
           CANCEL MEMBER SEARCH
        ===================================================== */

        function cancelMemberSearch(){

            clearTimeout(
                memberSearchTimer
            );


            memberSearchTimer =
                null;


            if(
                memberSearchController
            ){

                memberSearchController
                    .abort();


                memberSearchController =
                    null;

            }

        }

                /* =====================================================
           MEMBER INLINE ERROR
        ===================================================== */

        function clearBuyerInlineError(){

            if(buyerInput){

                buyerInput
                    .classList
                    .remove(
                        "sales-input-error"
                    );


                buyerInput
                    .removeAttribute(
                        "aria-invalid"
                    );

            }


            if(buyerHint){

                buyerHint
                    .classList
                    .remove(
                        "sales-inline-error"
                    );


                const type =
                    affiliateInput
                    ?
                    affiliateInput.value.trim()
                    :
                    "";


                if(
                    type
                    ===
                    "Member"
                ){

                    buyerHint.textContent =
                        "Search and select a registered member.";

                }
                else if(
                    type
                    ===
                    "Not Member"
                ){

                    buyerHint.textContent =
                        "Optional for walk-in customers.";

                }
                else{

                    buyerHint.textContent =
                        "Choose Member or Not Member first.";

                }

            }

        }


        function showBuyerInlineError(
            message
        ){

            if(buyerInput){

                buyerInput
                    .classList
                    .add(
                        "sales-input-error"
                    );


                buyerInput
                    .setAttribute(
                        "aria-invalid",
                        "true"
                    );

            }


            if(buyerHint){

                buyerHint
                    .classList
                    .add(
                        "sales-inline-error"
                    );


                buyerHint.textContent =
                    message;

            }

        }


        /* =====================================================
           CONFIGURE BUYER FIELD
        ===================================================== */

        function configureBuyerField(
            type
        ){

            clearBuyerInlineError();

            clearRememberedMember();

            cancelMemberSearch();


            if(buyerInput){

                buyerInput.value =
                    "";

            }


            hideMemberSuggestions();


            if(
                !buyerInput
                ||
                !buyerHint
            ){

                return;
            }


            if(
                type
                ===
                "Member"
            ){

                buyerInput.disabled =
                    false;


                buyerInput.placeholder =
                    "Type at least 3 letters from name or Member ID";


                buyerHint.textContent =
                    "Search and select a registered member.";

            }
            else if(
                type
                ===
                "Not Member"
            ){

                buyerInput.disabled =
                    false;


                buyerInput.placeholder =
                    "Buyer name (optional)";


                buyerHint.textContent =
                    "Optional for walk-in customers.";

            }
            else{

                buyerInput.disabled =
                    true;


                buyerInput.placeholder =
                    "Select customer type first";


                buyerHint.textContent =
                    "Choose Member or Not Member first.";

            }

        }


        /* =====================================================
           CUSTOMER TYPE BUTTON
        ===================================================== */

        if(
            affiliateButton
            &&
            affiliatePopup
        ){

            affiliateButton
                .addEventListener(
                    "click",
                    function(event){

                        event
                            .stopPropagation();


                        affiliatePopup
                            .classList
                            .toggle(
                                "show"
                            );

                    }
                );

        }


        document
            .querySelectorAll(
                ".affiliate-item"
            )
            .forEach(
                function(item){

                    item.addEventListener(
                        "click",
                        function(){

                            const value =
                                this.dataset
                                    .value
                                ||
                                "";


                            if(
                                affiliateInput
                            ){

                                affiliateInput.value =
                                    value;

                            }


                            if(
                                affiliateText
                            ){

                                affiliateText.textContent =
                                    value;

                            }


                            closeAffiliatePopup();


                            configureBuyerField(
                                value
                            );


                            if(buyerInput){

                                buyerInput.focus();

                            }

                        }
                    );

                }
            );


        /* =====================================================
           MEMBER SEARCH MESSAGE
        ===================================================== */

        function showMemberMessage(
            message
        ){

            if(
                !memberSuggestions
            ){

                return;
            }


            memberSuggestions.innerHTML =
                "";


            const element =
                document.createElement(
                    "div"
                );


            element.className =
                "sales-member-message";


            element.textContent =
                message;


            memberSuggestions
                .appendChild(
                    element
                );


            memberSuggestions
                .classList
                .add(
                    "show"
                );

        }


        /* =====================================================
           MEMBER SEARCH REQUEST
        ===================================================== */

        function searchMember(
            keyword
        ){

            if(
                !affiliateInput
                ||
                affiliateInput.value
                !==
                "Member"

                ||

                keyword.length
                <
                3
            ){

                hideMemberSuggestions();

                return;
            }


            if(
                memberSearchController
            ){

                memberSearchController
                    .abort();

            }


            memberSearchController =
                new AbortController();


            showMemberMessage(
                "Searching members..."
            );


            fetch(
                "search_sales_member.php?keyword="
                +
                encodeURIComponent(
                    keyword
                ),
                {
                    credentials:
                        "same-origin",

                    signal:
                        memberSearchController
                            .signal,

                    cache:
                        "no-store"
                }
            )
            .then(
                function(response){

                    if(
                        !response.ok
                    ){

                        throw new Error(
                            "Unable to search members."
                        );

                    }


                    return response.text();

                }
            )
            .then(
                function(html){

                    if(
                        !affiliateInput
                        ||
                        affiliateInput.value
                        !==
                        "Member"
                    ){

                        return;
                    }


                    if(
                        !memberSuggestions
                    ){

                        return;
                    }


                    memberSuggestions.innerHTML =
                        html;


                    memberSuggestions
                        .classList
                        .add(
                            "show"
                        );

                }
            )
            .catch(
                function(error){

                    if(
                        error.name
                        ===
                        "AbortError"
                    ){

                        return;
                    }


                    showMemberMessage(
                        "Unable to search members."
                    );

                }
            );

        }


        /* =====================================================
           MEMBER INPUT
        ===================================================== */

        if(buyerInput){

            buyerInput
                .addEventListener(
                    "input",
                    function(){

                        if(
                            !affiliateInput
                            ||
                            affiliateInput.value
                            !==
                            "Member"
                        ){

                            return;
                        }


                        const isRememberedMatch =
                            syncRememberedMember(
                                true
                            );


                        const keyword =
                            this.value
                                .trim();


                        clearTimeout(
                            memberSearchTimer
                        );


                        if(
                            isRememberedMatch
                        ){

                            cancelMemberSearch();

                            hideMemberSuggestions();

                            return;
                        }


                        if(
                            keyword.length
                            <
                            3
                        ){

                            hideMemberSuggestions();

                            return;
                        }


                        memberSearchTimer =
                            setTimeout(
                                function(){

                                    searchMember(
                                        keyword
                                    );

                                },
                                250
                            );

                    }
                );


            buyerInput
                .addEventListener(
                    "blur",
                    function(){

                        if(
                            !affiliateInput
                            ||
                            affiliateInput.value
                            !==
                            "Member"
                        ){

                            return;
                        }


                        syncRememberedMember(
                            true
                        );

                    }
                );

        }


        /* =====================================================
           SELECT MEMBER FROM SEARCH
        ===================================================== */

        if(memberSuggestions){

            memberSuggestions
                .addEventListener(
                    "click",
                    function(event){

                        const item =
                            event.target.closest(
                                ".sales-member-item"
                            );


                        if(!item){

                            return;
                        }


                        const id =
                            item.dataset.id
                            ||
                            "";


                        const name =
                            item.dataset.name
                            ||
                            "";


                        const codeElement =
                            item.querySelector(
                                ".sales-member-code"
                            );


                        const code =
                            codeElement
                            ?
                            codeElement
                                .textContent
                                .trim()
                            :
                            "";


                        if(
                            id === ""
                            ||
                            name === ""
                        ){

                            return;
                        }


                        selectedMemberID =
                            id;


                        selectedMemberName =
                            name;


                        selectedMemberCode =
                            code;


                        if(buyerInput){

                            buyerInput.value =
                                selectedMemberName;

                        }


                        restoreRememberedMember(
                            true
                        );


                        cancelMemberSearch();

                        hideMemberSuggestions();

                    }
                );

        }


        /* =====================================================
           PRODUCT SEARCH
        ===================================================== */

        function updateProductCount(){

            const visible =
                productTiles.filter(
                    function(tile){

                        return !tile.hidden;

                    }
                )
                .length;


            if(
                productResultCount
            ){

                productResultCount.textContent =
                    visible
                    +
                    (
                        visible === 1
                        ?
                        " product"
                        :
                        " products"
                    );

            }


            if(
                productSearchEmpty
            ){

                productSearchEmpty.hidden =
                    productTiles.length === 0
                    || visible !== 0;

            }

        }


        function filterProducts(){

            if(!productSearch){

                return;
            }


            const keyword =
                productSearch
                    .value
                    .trim()
                    .toLowerCase();


            productTiles.forEach(
                function(tile){

                    const name =
                        (
                            tile.dataset.name
                            ||
                            ""
                        )
                        .toLowerCase();


                    tile.hidden =
                        !name.includes(
                            keyword
                        );

                }
            );


            updateProductCount();

        }


        if(productSearch){

            productSearch
                .addEventListener(
                    "input",
                    filterProducts
                );

        }


        /* =====================================================
           SELECT PRODUCT TILE
        ===================================================== */

        function getSelectedProduct(){

            if(!productInput){

                return null;
            }


            const id =
                productInput.value;


            if(!id){

                return null;
            }


            return (
                productTiles.find(
                    function(tile){

                        return (
                            String(
                                tile.dataset.id
                            )
                            ===
                            String(id)
                        );

                    }
                )
                ||
                null
            );

        }


        function clearProductSelection(){

            if(productInput){

                productInput.value =
                    "";

            }


            if(quantityInput){

                quantityInput.value =
                    "1";

            }


            productTiles.forEach(
                function(tile){

                    tile.classList
                        .remove(
                            "is-selected"
                        );

                }
            );

        }


        productTiles.forEach(
            function(tile){

                tile.addEventListener(
                    "click",
                    function(){

                        const productID =
                            this.dataset.id
                            ||
                            "";


                        const alreadyInCart =
                            cart.some(
                                function(item){

                                    return (
                                        String(
                                            item.id
                                        )
                                        ===
                                        String(
                                            productID
                                        )
                                    );

                                }
                            );


                        if(alreadyInCart){

                            showSalesAlert(
                                "warning",
                                "Product already added",
                                "This product is already in the Product Cart. Use the + or − controls in the cart to adjust its quantity."
                            );

                            return;

                        }


                        productTiles.forEach(
                            function(
                                otherTile
                            ){

                                otherTile
                                    .classList
                                    .remove(
                                        "is-selected"
                                    );

                            }
                        );


                        this.classList
                            .add(
                                "is-selected"
                            );


                        if(productInput){

                            productInput.value =
                                productID;

                        }


                        if(quantityInput){

                            quantityInput.value =
                                "1";

                        }

                    }
                );

            }
        );


        /* =====================================================
           QUANTITY HELPERS
        ===================================================== */

        function currentQuantity(){

            if(!quantityInput){

                return 1;
            }


            const quantity =
                Number.parseInt(
                    quantityInput.value,
                    10
                );


            return (
                Number.isInteger(
                    quantity
                )
                &&
                quantity >= 1
            )
            ?
            quantity
            :
            1;

        }


        function selectedStock(){

            const product =
                getSelectedProduct();


            if(!product){

                return null;
            }


            const stock =
                Number.parseInt(
                    product.dataset.stock,
                    10
                );


            return (
                Number.isInteger(stock)
                ?
                stock
                :
                null
            );

        }


        /* =====================================================
           QUANTITY MINUS
        ===================================================== */

        if(quantityMinus){

            quantityMinus
                .addEventListener(
                    "click",
                    function(){

                        if(quantityInput){

                            quantityInput.value =
                                Math.max(
                                    1,
                                    currentQuantity()
                                    -
                                    1
                                );

                        }

                    }
                );

        }


        /* =====================================================
           QUANTITY PLUS
        ===================================================== */

        if(quantityPlus){

            quantityPlus
                .addEventListener(
                    "click",
                    function(){

                        const stock =
                            selectedStock();


                        if(
                            stock === null
                        ){

                            showSalesAlert(
                                "warning",
                                "Select Product",
                                "Please select a product first."
                            );

                            return;
                        }


                        const quantity =
                            currentQuantity();


                        if(
                            quantity
                            >=
                            stock
                        ){

                            showSalesAlert(
                                "warning",
                                "Stock Limit Reached",
                                "Only "
                                +
                                stock
                                +
                                " unit(s) are available."
                            );

                            return;
                        }


                        if(quantityInput){

                            quantityInput.value =
                                quantity + 1;

                        }

                    }
                );

        }


        /* =====================================================
           MANUAL QUANTITY INPUT
        ===================================================== */

        if(quantityInput){

            quantityInput
                .addEventListener(
                    "change",
                    function(){

                        let quantity =
                            currentQuantity();


                        const stock =
                            selectedStock();


                        if(
                            stock !== null
                            &&
                            quantity > stock
                        ){

                            quantity =
                                stock;


                            showSalesAlert(
                                "warning",
                                "Stock Limit Reached",
                                "Quantity was adjusted to the available stock."
                            );

                        }


                        this.value =
                            Math.max(
                                1,
                                quantity
                            );

                    }
                );

        }


        /* =====================================================
           ADD TO CART
        ===================================================== */

        if(addItemButton){

            addItemButton
                .addEventListener(
                    "click",
                    function(){

                        const product =
                            getSelectedProduct();


                        if(!product){

                            showSalesAlert(
                                "warning",
                                "No Product Selected",
                                "Please select one of the product tiles first."
                            );

                            return;
                        }


                        const id =
                            product.dataset.id;


                        const name =
                            product.dataset.name;


                        const stock =
                            Number.parseInt(
                                product.dataset.stock,
                                10
                            );


                        const price =
                            Number.parseFloat(
                                product.dataset.price
                            );


                        const quantity =
                            currentQuantity();


                        if(
                            !Number.isInteger(
                                stock
                            )
                            ||
                            stock <= 0
                        ){

                            showSalesAlert(
                                "warning",
                                "Product Out of Stock",
                                "This product has no available stock."
                            );

                            return;
                        }


                        if(
                            !Number.isFinite(
                                price
                            )
                            ||
                            price < 0
                        ){

                            showSalesAlert(
                                "error",
                                "Invalid Product Price",
                                "Unable to add this product. Please refresh the page."
                            );

                            return;
                        }


                        if(
                            quantity > stock
                        ){

                            showSalesAlert(
                                "warning",
                                "Insufficient Stock",
                                "Only "
                                +
                                stock
                                +
                                " unit(s) are available."
                            );

                            return;
                        }


                        const existing =
                            cart.find(
                                function(item){

                                    return (
                                        String(
                                            item.id
                                        )
                                        ===
                                        String(id)
                                    );

                                }
                            );


                        if(existing){

                            showSalesAlert(
                                "warning",
                                "Product already added",
                                "This product is already in the Product Cart. Use the + or − controls in the cart to adjust its quantity."
                            );

                            return;

                        }
                        else{

                            cart.push({

                                id:
                                    id,

                                product:
                                    name,

                                qty:
                                    quantity,

                                stock:
                                    stock,

                                price:
                                    price,

                                amount:
                                    quantity
                                    *
                                    price

                            });

                        }


                        renderCart();


                        clearProductSelection();


                        if(productSearch){

                            productSearch.value =
                                "";

                        }


                        productTiles.forEach(
                            function(tile){

                                tile.hidden =
                                    false;

                            }
                        );


                        updateProductCount();

                    }
                );

        }


        /* =====================================================
           CART QUANTITY
        ===================================================== */

        function changeCartQuantity(
            index,
            difference
        ){

            const item =
                cart[index];


            if(!item){

                return;
            }


            const newQuantity =
                item.qty
                +
                difference;


            if(
                newQuantity < 1
            ){

                return;
            }


            if(
                newQuantity
                >
                item.stock
            ){

                showSalesAlert(
                    "warning",
                    "Stock Limit Reached",
                    "Only "
                    +
                    item.stock
                    +
                    " unit(s) of "
                    +
                    item.product
                    +
                    " are available."
                );

                return;
            }


            item.qty =
                newQuantity;


            item.amount =
                item.qty
                *
                item.price;


            renderCart();

        }


        function removeCartItem(
            index
        ){

            cart.splice(
                index,
                1
            );


            renderCart();

        }


        /* =====================================================
           EMPTY CART
        ===================================================== */

        function createEmptyCart(){

            const row =
                document.createElement(
                    "tr"
                );


            row.className =
                "sales-cart-empty-row";


            const cell =
                document.createElement(
                    "td"
                );


            cell.colSpan =
                4;


            cell.innerHTML =
                `
                <div class="sales-cart-empty">

                    <i class="bi bi-cart3"></i>

                    <strong>
                        Cart is empty
                    </strong>

                    <span>
                        Select a product then click Add to Cart.
                    </span>

                </div>
                `;


            row.appendChild(
                cell
            );


            return row;

        }

        function refreshProductTileCartState(){

            const cartProductIDs =
                new Set(
                    cart.map(
                        function(item){

                            return String(
                                item.id
                            );

                        }
                    )
                );


            productTiles.forEach(
                function(tile){

                    const productID =
                        String(
                            tile.dataset.id
                            ||
                            ""
                        );


                    const isInCart =
                        cartProductIDs.has(
                            productID
                        );


                    tile.classList.toggle(
                        "is-added",
                        isInCart
                    );


                    if(isInCart){

                        tile.classList.remove(
                            "is-selected"
                        );

                    }

                }
            );

        }

        
        /* =====================================================
        CART TOTALS AND HIDDEN PAYLOAD
        ===================================================== */

        function syncCartSummary(){

            if(cartCount){
                cartCount.textContent =
                    cart.length +
                    (cart.length === 1 ? " item" : " items");
            }

            const totalQuantity = getCartTotalQuantity();

            if(totalQuantityElement){
                totalQuantityElement.textContent =
                    totalQuantity +
                    (totalQuantity === 1 ? " unit" : " units");
            }

            if(grandTotal){
                grandTotal.textContent =
                    formatMoney(getCartTotal());
            }

            updateSalesChange();

            // Only send product ID and quantity to PHP.
            // The server calculates the official amounts.
            if(cartInput){
                cartInput.value = cart.length
                    ? JSON.stringify(
                        cart.map(function(item){
                            return {
                                id: item.id,
                                qty: item.qty
                            };
                        })
                    )
                    : "";
            }
        }

        /* =====================================================
           RENDER CART
        ===================================================== */

        function renderCart(){

            if(
                !salesItems
                ||
                !cartInput
            ){

                return;
            }

            refreshProductTileCartState();


            salesItems.innerHTML =
                "";


            let total =
                0;


            let totalQuantity =
                0;


            if(
                cart.length === 0
            ){

                salesItems.appendChild(
                    createEmptyCart()
                );

            }
            else{

                cart.forEach(
                    function(
                        item,
                        index
                    ){

                        item.amount =
                            item.qty
                            *
                            item.price;


                        total +=
                            item.amount;


                        totalQuantity +=
                            item.qty;


                        const row =
                            document.createElement(
                                "tr"
                            );


                        row.className =
                            "sales-cart-item-row";


                        /* PRODUCT */

                        const productCell =
                            document.createElement(
                                "td"
                            );


                        productCell.className =
                            "sales-cart-product-cell";


                        const productName =
                            document.createElement(
                                "strong"
                            );


                        productName.textContent =
                            item.product;


                        const productPrice =
                            document.createElement(
                                "small"
                            );


                        productPrice.textContent =
                            formatMoney(
                                item.price
                            )
                            +
                            " each";


                        productCell.appendChild(
                            productName
                        );


                        productCell.appendChild(
                            productPrice
                        );


                        /* QUANTITY */

                        const quantityCell =
                            document.createElement(
                                "td"
                            );


                        const quantityControl =
                            document.createElement(
                                "div"
                            );


                        quantityControl.className =
                            "sales-cart-qty-control";


                        const minusButton =
                            document.createElement(
                                "button"
                            );


                        minusButton.type =
                            "button";


                        minusButton.textContent =
                            "−";


                        minusButton.disabled =
                            item.qty <= 1;


                        minusButton
                            .addEventListener(
                                "click",
                                function(){

                                    changeCartQuantity(
                                        index,
                                        -1
                                    );

                                }
                            );

                            const quantityField = document.createElement("input");

                            quantityField.type = "text";
                            quantityField.inputMode = "numeric";
                            quantityField.autocomplete = "off";
                            quantityField.value = String(item.qty);

                            quantityField.setAttribute(
                                "aria-label",
                                "Quantity for " + item.product
                            );

                            quantityField.title =
                                "Enter a whole number from 1 to " + item.stock;

                            // Maintain the existing compact cart design.                     
                            quantityField.style.width = "30px";
                            quantityField.style.minWidth = "0";
                            quantityField.style.boxSizing = "border-box";
                            quantityField.style.height = "26px";
                            quantityField.style.padding = "0";
                            quantityField.style.textAlign = "center";
                            quantityField.style.font = "inherit";
                            quantityField.style.fontWeight = "700";
                            quantityField.style.color = "inherit";
                            quantityField.style.background = "rgba(255,255,255,0.4)";
                            quantityField.style.border = "1px solid #9AA876";
                            quantityField.style.borderRadius = "5px";

                            // Update quantity and totals while typing.
                            quantityField.addEventListener("input", function(){

                                const raw = this.value.trim();
                                const nextQuantity = Number(raw);

                                const valid =
                                    /^\d+$/.test(raw) &&
                                    Number.isSafeInteger(nextQuantity) &&
                                    nextQuantity >= 1 &&
                                    nextQuantity <= item.stock;

                                this.style.borderColor =
                                    valid ? "#9AA876" : "#D45B52";

                                if(!valid){
                                    return;
                                }

                                item.qty = nextQuantity;
                                item.amount = item.qty * item.price;

                                amountCell.textContent =
                                    formatMoney(item.amount);

                                minusButton.disabled = item.qty <= 1;
                                plusButton.disabled = item.qty >= item.stock;

                                syncCartSummary();
                            });

                            // Validate when the user finishes editing.
                            quantityField.addEventListener("change", function(){

                                const raw = this.value.trim();
                                const entered = Number(raw);

                                if(
                                    !/^\d+$/.test(raw) ||
                                    !Number.isSafeInteger(entered) ||
                                    entered < 1
                                ){
                                    showSalesAlert(
                                        "warning",
                                        "Invalid Quantity",
                                        "Enter a whole number of at least 1."
                                    );
                                }
                                else if(entered > item.stock){

                                    item.qty = item.stock;
                                    item.amount = item.qty * item.price;

                                    amountCell.textContent =
                                        formatMoney(item.amount);

                                    showSalesAlert(
                                        "warning",
                                        "Stock Limit Reached",
                                        "Only " + item.stock +
                                        " unit(s) of " + item.product +
                                        " are available."
                                    );
                                }

                                this.value = String(item.qty);
                                this.style.borderColor = "#9AA876";

                                minusButton.disabled = item.qty <= 1;
                                plusButton.disabled = item.qty >= item.stock;

                                syncCartSummary();
                            });

                            // Enter finishes editing instead of submitting the sale.
                            quantityField.addEventListener("keydown", function(event){

                                if(event.key === "Enter"){
                                    event.preventDefault();
                                    this.blur();
                                }
                            });



                        const plusButton =
                            document.createElement(
                                "button"
                            );


                        plusButton.type =
                            "button";


                        plusButton.textContent =
                            "+";


                        plusButton.disabled =
                            item.qty
                            >=
                            item.stock;


                        plusButton
                            .addEventListener(
                                "click",
                                function(){

                                    changeCartQuantity(
                                        index,
                                        1
                                    );

                                }
                            );


                        quantityControl.appendChild(
                            minusButton
                        );


                        quantityControl.appendChild(quantityField);


                        quantityControl.appendChild(
                            plusButton
                        );


                        quantityCell.appendChild(
                            quantityControl
                        );


                        /* AMOUNT */

                        const amountCell =
                            document.createElement(
                                "td"
                            );


                        amountCell.className =
                            "sales-cart-amount";


                        amountCell.textContent =
                            formatMoney(
                                item.amount
                            );


                        /* REMOVE */

                        const removeCell =
                            document.createElement(
                                "td"
                            );


                        const removeButton =
                            document.createElement(
                                "button"
                            );


                        removeButton.type =
                            "button";


                        removeButton.className =
                            "sales-cart-remove";


                        removeButton.title =
                            "Remove product";


                        removeButton.innerHTML =
                            '<i class="bi bi-trash3"></i>';


                        removeButton
                            .addEventListener(
                                "click",
                                function(){

                                    removeCartItem(
                                        index
                                    );

                                }
                            );


                        removeCell.appendChild(
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
                            removeCell
                        );


                        salesItems.appendChild(
                            row
                        );

                    }
                );

            }

            syncCartSummary();

        }


        /* =====================================================
           TRANSACTION DATE CALENDAR
        ===================================================== */

        if(
            transactionDateButton
            &&
            transactionDateCalendar
        ){

            transactionDateButton
                .addEventListener(
                    "click",
                    function(event){

                        event.preventDefault();
                        event.stopPropagation();


                        const willOpen =
                            !transactionDateCalendar
                                .classList
                                .contains(
                                    "show"
                                );


                        if(
                            !willOpen
                        ){

                            closeTransactionDateCalendar();

                            return;
                        }


                        const selected =
                            transactionDateInput
                            ?
                            parseSalesDateKey(
                                transactionDateInput
                                    .value
                                    .trim()
                            )
                            :
                            null;


                        if(selected){

                            transactionCalendarView =
                                new Date(
                                    selected.year,
                                    selected.month,
                                    1
                                );

                        }


                        renderTransactionDateCalendar();


                        transactionDateCalendar
                            .classList
                            .add(
                                "show"
                            );


                        transactionDateCalendar
                            .setAttribute(
                                "aria-hidden",
                                "false"
                            );


                        transactionDateButton
                            .setAttribute(
                                "aria-expanded",
                                "true"
                            );

                    }
                );


            transactionDateCalendar
                .addEventListener(
                    "click",
                    function(event){

                        event.stopPropagation();

                    }
                );

        }


        if(
            transactionDateCalendarPrev
        ){

            transactionDateCalendarPrev
                .addEventListener(
                    "click",
                    function(event){

                        event.preventDefault();
                        event.stopPropagation();


                        if(
                            !transactionCalendarView
                        ){

                            configureTransactionDate(
                                false
                            );

                        }


                        transactionCalendarView =
                            new Date(
                                transactionCalendarView
                                    .getFullYear(),
                                transactionCalendarView
                                    .getMonth() - 1,
                                1
                            );


                        renderTransactionDateCalendar();

                    }
                );

        }


        if(
            transactionDateCalendarNext
        ){

            transactionDateCalendarNext
                .addEventListener(
                    "click",
                    function(event){

                        event.preventDefault();
                        event.stopPropagation();


                        if(
                            this.disabled
                        ){

                            return;
                        }


                        if(
                            !transactionCalendarView
                        ){

                            configureTransactionDate(
                                false
                            );

                        }


                        transactionCalendarView =
                            new Date(
                                transactionCalendarView
                                    .getFullYear(),
                                transactionCalendarView
                                    .getMonth() + 1,
                                1
                            );


                        renderTransactionDateCalendar();

                    }
                );

        }


        /* =====================================================
           FORM VALIDATION + CONFIRMATION
        ===================================================== */

        if(salesForm){

            salesForm
                .addEventListener(
                    "submit",
                    async function(event){

                        /*
                        Never allow normal browser submission yet.

                        We validate first, show confirmation,
                        then explicitly submit after approval.
                        */

                        event.preventDefault();


                        if(
                            finalSubmissionStarted
                            ||
                            saleConfirmationOpen
                        ){

                            return;
                        }


                        const affiliate =
                            affiliateInput
                            ?
                            affiliateInput
                                .value
                                .trim()
                            :
                            "";


                        const transactionDate =
                            transactionDateInput
                            ?
                            transactionDateInput
                                .value
                                .trim()
                            :
                            "";


                        /* CUSTOMER TYPE */

                        if(
                            affiliate === ""
                        ){

                            showSalesAlert(
                                "warning",
                                "Customer Type Required",
                                "Please select Member or Not Member."
                            );


                            return;
                        }


                        /* MEMBER */

                        if(
                            affiliate
                            ===
                            "Member"
                        ){

                            /*
                            Give remembered member a final
                            re-verification before submission.
                            */

                            syncRememberedMember(
                                true
                            );


                            if(
                                !memberIDInput

                                ||

                                memberIDInput
                                    .value
                                    .trim()
                                ===
                                ""

                                ||

                                !rememberedMemberExists()
                            ){

                                showBuyerInlineError(
                                    "Please search and select a valid registered member."
                                );


                                if(buyerInput){

                                    buyerInput.focus();

                                }


                                return;
                            }

                            clearBuyerInlineError();

                        }


                        /* NOT MEMBER */

                        if(
                            affiliate
                            ===
                            "Not Member"
                        ){

                            clearRememberedMember();

                        }


                        /* TRANSACTION DATE */

                        if(
                            transactionDate
                            ===
                            ""
                        ){

                            showSalesAlert(
                                "warning",
                                "Transaction Date Required",
                                "Please select the transaction date."
                            );


                            if(
                                transactionDateInput
                            ){

                                transactionDateInput
                                    .focus();

                            }


                            return;
                        }


                        if(
                            !isValidISODate(
                                transactionDate
                            )
                        ){

                            showSalesAlert(
                                "warning",
                                "Invalid Transaction Date",
                                "Please select a valid transaction date."
                            );


                            return;
                        }


                        const today =
                            getManilaDateISO();


                        if(
                            transactionDate
                            >
                            today
                        ){

                            if(
                                transactionDateInput
                            ){

                                transactionDateInput.value =
                                    today;

                            }


                            showSalesAlert(
                                "warning",
                                "Future Date Not Allowed",
                                "The transaction date cannot be later than today."
                            );


                            return;
                        }


                        /* CART */

                        if(
                            cart.length === 0
                        ){

                            showSalesAlert(
                                "warning",
                                "Cart is Empty",
                                "Please add at least one product."
                            );


                            return;
                        }


                        /*
                        Rebuild hidden cart immediately before
                        confirmation.
                        */

                        renderCart();


                        if(
                            !cartInput
                            ||
                            cartInput.value
                                .trim()
                            ===
                            ""
                        ){

                            showSalesAlert(
                                "warning",
                                "Cart is Empty",
                                "Please add at least one product."
                            );


                            return;
                        }


                        /* =====================================
                           CASH PAYMENT VALIDATION
                        ===================================== */

                        const cashDetails =
                            getSalesCashDetails();

                        if(!cashDetails.valid){

                            showSalesAlert(
                                "warning",
                                "Invalid Cash Received",
                                "Please enter a valid cash amount with up to two decimal places."
                            );

                            cashReceivedInput?.focus();

                            return;
                        }


                        if(
                            cashDetails.cashCents
                            <
                            cashDetails.totalCents
                        ){

                            showSalesAlert(
                                "warning",
                                "Insufficient Cash",
                                "Cash received must be equal to or greater than the grand total."
                            );

                            cashReceivedInput?.focus();

                            return;
                        }


                        /* =====================================
                           CONFIRMATION
                        ===================================== */

                        saleConfirmationOpen =
                            true;


                        let confirmed =
                            false;


                        try{

                            confirmed =
                                await confirmSalesTransaction();

                        }
                        catch(error){

                            console.error(
                                "Sales confirmation error:",
                                error
                            );


                            showSalesAlert(
                                "error",
                                "Unable to Confirm",
                                "Something went wrong while opening the confirmation."
                            );

                        }
                        finally{

                            saleConfirmationOpen =
                                false;

                        }


                        if(
                            !confirmed
                        ){

                            /*
                            Cancel means:
                            - no request sent
                            - no database insert
                            - no stock deduction
                            */

                            resetSaveButton();

                            return;
                        }


                        /* =====================================
                           FINAL SUBMISSION
                        ===================================== */

                        finalSubmissionStarted =
                            true;


                        setSaveButtonLoading();


                        /*
                        Native submit bypasses this submit
                        event listener, preventing another
                        confirmation loop.
                        */

                        HTMLFormElement
                            .prototype
                            .submit
                            .call(
                                salesForm
                            );

                    }
                );

        }


        /* =====================================================
           CLICK OUTSIDE
        ===================================================== */

        document
            .addEventListener(
                "click",
                function(event){

                    if(
                        affiliatePopup
                        &&
                        affiliateButton
                        &&
                        !affiliatePopup
                            .contains(
                                event.target
                            )
                        &&
                        !affiliateButton
                            .contains(
                                event.target
                            )
                    ){

                        closeAffiliatePopup();

                    }


                    if(
                        transactionDateCalendar
                        &&
                        transactionDateWrapper
                        &&
                        !transactionDateWrapper
                            .contains(
                                event.target
                            )
                    ){

                        closeTransactionDateCalendar();

                    }


                    if(
                        memberSuggestions
                        &&
                        buyerInput
                        &&
                        !memberSuggestions
                            .contains(
                                event.target
                            )
                        &&
                        event.target
                        !==
                        buyerInput
                    ){

                        hideMemberSuggestions();

                    }

                }
            );


        /* =====================================================
           MODAL SHOWN
        ===================================================== */

        if(addSalesModal){

            addSalesModal
                .addEventListener(
                    "shown.bs.modal",
                    function(){

                        configureTransactionDate(
                            false
                        );

                    }
                );

        }


        /* =====================================================
           RESET MODAL
        ===================================================== */

        if(addSalesModal){

            addSalesModal
                .addEventListener(
                    "hidden.bs.modal",
                    function(){

                        cancelMemberSearch();


                        if(salesForm){

                            salesForm.reset();

                        }


                        cart = [];


                        saleConfirmationOpen =
                            false;


                        finalSubmissionStarted =
                            false;


                        if(affiliateInput){

                            affiliateInput.value =
                                "";

                        }


                        if(affiliateText){

                            affiliateText.textContent =
                                "Select Customer Type";

                        }


                        configureBuyerField(
                            ""
                        );


                        closeAffiliatePopup();


                        clearProductSelection();


                        if(productSearch){

                            productSearch.value =
                                "";

                        }


                        productTiles.forEach(
                            function(tile){

                                tile.hidden =
                                    false;

                            }
                        );


                        updateProductCount();


                        renderCart();


                        configureTransactionDate(
                            true
                        );


                        resetSaveButton();

                    }
                );

        }


        /* =====================================================
           BACK / FORWARD CACHE
        ===================================================== */

        window.addEventListener(
            "pageshow",
            function(){

                saleConfirmationOpen =
                    false;


                finalSubmissionStarted =
                    false;


                resetSaveButton();


                configureTransactionDate(
                    false
                );

            }
        );


        /* =====================================================
           SALES LEDGER FILTER
        ===================================================== */

        const searchSalesInput =
            document.getElementById(
                "searchSales"
            );

        const affiliateFilter =
            document.getElementById(
                "affiliateFilter"
            );


        function filterLedger(){

            const keyword =
                searchSalesInput
                ?
                searchSalesInput
                    .value
                    .toLowerCase()
                    .trim()
                :
                "";


            const selectedAffiliate =
                affiliateFilter
                ?
                affiliateFilter
                    .value
                    .toLowerCase()
                :
                "all";


            document
                .querySelectorAll(
                    ".ledger-row"
                )
                .forEach(
                    function(row){

                        const searchable =
                            (
                                row.dataset.search
                                ||
                                ""
                            )
                            .toLowerCase();


                        const affiliate =
                            (
                                row.dataset.affiliate
                                ||
                                ""
                            )
                            .toLowerCase();


                        const searchMatch =
                            searchable.includes(
                                keyword
                            );


                        const affiliateMatch =
                            selectedAffiliate
                            ===
                            "all"
                            ||
                            affiliate
                            ===
                            selectedAffiliate;


                        const dateMatch =
                            row.dataset
                                .datevisible
                            !==
                            "0";


                        row.style.display =
                            (
                                searchMatch
                                &&
                                affiliateMatch
                                &&
                                dateMatch
                            )
                            ?
                            "grid"
                            :
                            "none";

                    }
                );

        }


        if(searchSalesInput){

            searchSalesInput
                .addEventListener(
                    "input",
                    filterLedger
                );

        }


        if(affiliateFilter){

            affiliateFilter
                .addEventListener(
                    "change",
                    filterLedger
                );

        }


        document
            .querySelectorAll(
                ".ledger-row"
            )
            .forEach(
                function(row){

                    row.dataset.datevisible =
                        "1";

                }
            );


        /* =====================================================
           DATE FILTER
        ===================================================== */

        const applyDateFilter =
            document.getElementById(
                "applyDateFilter"
            );

        const clearDateFilter =
            document.getElementById(
                "clearDateFilter"
            );

        const startDate =
            document.getElementById(
                "startDate"
            );

        const endDate =
            document.getElementById(
                "endDate"
            );

        const dateFilterModal =
            document.getElementById(
                "dateFilterModal"
            );

        const salesFilterServerDate =
            dateFilterModal
                ?.dataset
                .serverDate
            ||
            "";    

        function updateSalesLedgerDateRestrictions(){

            if(!startDate || !endDate){
                return;
            }

            const selectedTo = endDate.value || "";

            startDate.max =
                selectedTo && (!salesFilterServerDate || selectedTo < salesFilterServerDate)
                    ? selectedTo
                    : salesFilterServerDate;

            endDate.min = startDate.value || "";
            endDate.max = salesFilterServerDate;
        }

        startDate?.addEventListener("change", updateSalesLedgerDateRestrictions);
        endDate?.addEventListener("change", updateSalesLedgerDateRestrictions);

        updateSalesLedgerDateRestrictions();    

        if(
            applyDateFilter
            &&
            startDate
            &&
            endDate
        ){

            applyDateFilter
                .addEventListener(
                    "click",
                    function(){

                        const from =
                            startDate.value;


                        const to =
                            endDate.value;


                        if(
                            salesFilterServerDate
                            &&
                            (
                                (
                                    from
                                    &&
                                    from > salesFilterServerDate
                                )
                                ||
                                (
                                    to
                                    &&
                                    to > salesFilterServerDate
                                )
                            )
                        ){

                            showSalesAlert(
                                "warning",
                                "Invalid Date",
                                "Future dates are not allowed."
                            );


                            return;
                        }


                        if(
                            from
                            &&
                            to
                            &&
                            from > to
                        ){

                            showSalesAlert(
                                "warning",
                                "Invalid Date Range",
                                "The From date cannot be later than the To date."
                            );


                            return;
                        }


                        document
                            .querySelectorAll(
                                ".ledger-row"
                            )
                            .forEach(
                                function(row){

                                    const date =
                                        row.dataset.date
                                        ||
                                        "";


                                    let visible =
                                        true;


                                    if(
                                        from
                                        &&
                                        date < from
                                    ){

                                        visible =
                                            false;

                                    }


                                    if(
                                        to
                                        &&
                                        date > to
                                    ){

                                        visible =
                                            false;

                                    }


                                    row.dataset
                                        .datevisible =
                                        visible
                                        ?
                                        "1"
                                        :
                                        "0";

                                }
                            );


                        filterLedger();


                        hideBootstrapModal(
                            dateFilterModal
                        );

                    }
                );

        }


        if(
            clearDateFilter
            &&
            startDate
            &&
            endDate
        ){

            clearDateFilter
                .addEventListener(
                    "click",
                    function(){

                        startDate.value =
                            "";

                        endDate.value =
                            "";

                        updateSalesLedgerDateRestrictions();


                        document
                            .querySelectorAll(
                                ".ledger-row"
                            )
                            .forEach(
                                function(row){

                                    row.dataset
                                        .datevisible =
                                        "1";

                                }
                            );


                        filterLedger();


                        hideBootstrapModal(
                            dateFilterModal
                        );

                    }
                );

        }


        /* =====================================================
           EXPORT
        ===================================================== */

        const exportButton =
            document.getElementById(
                "exportPDFBtn"
            );


        if(exportButton){

            exportButton
                .addEventListener(
                    "click",
                    function(event){

                        event.preventDefault();


                        const search =
                            searchSalesInput
                            ?
                            searchSalesInput
                                .value
                            :
                            "";


                        const affiliate =
                            affiliateFilter
                            ?
                            affiliateFilter
                                .value
                            :
                            "all";


                        const from =
                            startDate
                            ?
                            startDate.value
                            :
                            "";


                        const to =
                            endDate
                            ?
                            endDate.value
                            :
                            "";


                        const url =
                            "export_sales.php?"
                            +
                            "search="
                            +
                            encodeURIComponent(
                                search
                            )
                            +
                            "&affiliate="
                            +
                            encodeURIComponent(
                                affiliate
                            )
                            +
                            "&start="
                            +
                            encodeURIComponent(
                                from
                            )
                            +
                            "&end="
                            +
                            encodeURIComponent(
                                to
                            );


                        window.open(
                            url,
                            "_blank"
                        );

                    }
                );

        }


        /* =====================================================
           INITIAL STATE
        ===================================================== */

        if(memberStatus){

            memberStatus.hidden =
                true;

        }


        configureBuyerField(
            ""
        );


        configureTransactionDate(
            false
        );


        updateProductCount();


        renderCart();


        resetSaveButton();

    }
);