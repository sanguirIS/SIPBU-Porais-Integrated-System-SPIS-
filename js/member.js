/* =========================================================
   SPIS MEMBERS MODULE
========================================================= */


/* =========================================================
   COMMON ELEMENTS
========================================================= */

const memberForm =
    document.getElementById(
        "memberForm"
    );

const profileInput =
    document.getElementById(
        "profile"
    );

const initialCbuInput =
    document.getElementById(
        "initialCbu"
    );

const viewAttachmentsBtn =
    document.getElementById(
        "viewAttachmentsBtn"
    );

const viewCbuHistoryBtn =
    document.getElementById(
        "viewCbuHistoryBtn"
    );    

const attachmentInput =
    document.getElementById(
        "attachment"
    );

const selectedFiles =
    document.getElementById(
        "selectedAttachments"
    );


const membershipDateInput =
    document.getElementById(
        "memberMembershipDate"
    );

const membershipDateDisplay =
    document.getElementById(
        "memberMembershipDateDisplay"
    );

const membershipDateBtn =
    document.getElementById(
        "memberMembershipDateBtn"
    );


const birthDateInput =
    document.getElementById(
        "memberBirthDate"
    );

const birthDateDisplay =
    document.getElementById(
        "memberBirthDateDisplay"
    );

const birthDateBtn =
    document.getElementById(
        "memberBirthDateBtn"
    );


/* =========================================================
   PROFILE PREVIEW
========================================================= */

if(profileInput){

    profileInput.addEventListener(
        "change",
        function(){

            const file =
                this.files?.[0];

            const preview =
                document.getElementById(
                    "previewImage"
                );

            const placeholder =
                document.getElementById(
                    "uploadPlaceholder"
                );


            if(
                !preview
                ||
                !placeholder
            ){
                return;
            }


            if(!file){

                preview.src =
                    "";

                preview.style.display =
                    "none";

                placeholder.style.display =
                    "flex";

                return;
            }


            const reader =
                new FileReader();


            reader.addEventListener(
                "load",
                function(event){

                    preview.src =
                        event.target?.result
                        ||
                        "";

                    preview.style.display =
                        "block";

                    placeholder.style.display =
                        "none";

                }
            );


            reader.readAsDataURL(
                file
            );

        }
    );

}


/* =========================================================
   CONTACT NUMBER
========================================================= */

function formatMemberContact(
    value
){

    let digits =
        String(
            value
            ||
            ""
        )
        .replace(
            /\D/g,
            ""
        )
        .substring(
            0,
            11
        );


    if(
        digits.length > 7
    ){

        return (
            digits.substring(
                0,
                4
            )
            +
            "-"
            +
            digits.substring(
                4,
                7
            )
            +
            "-"
            +
            digits.substring(
                7
            )
        );

    }


    if(
        digits.length > 4
    ){

        return (
            digits.substring(
                0,
                4
            )
            +
            "-"
            +
            digits.substring(
                4
            )
        );

    }


    return digits;

}


document.addEventListener(
    "input",
    function(event){

        if(
            event.target?.id
            !==
            "contact"
        ){
            return;
        }


        event.target.value =
            formatMemberContact(
                event.target.value
            );

    }
);


/* =========================================================
   TIN
========================================================= */

document.addEventListener(
    "input",
    function(event){

        if(
            event.target?.id
            !==
            "tin_no"
        ){
            return;
        }


        event.target.value =
            event.target.value
            .replace(
                /\D/g,
                ""
            )
            .substring(
                0,
                12
            );

    }
);


/* =========================================================
   RSBSA ID

   FORMAT:
   XX-XX-XX-XXX-REMAINDER

   RULES:
   - Letters and numbers only
   - Automatically uppercase
   - Minimum 14 alphanumeric characters
   - Maximum 26 alphanumeric characters
   - 4 hyphens are automatically inserted
   - Final stored length stays within 30 characters
========================================================= */

function formatMemberRsbsa(
    value
){

    const compact =
        String(
            value
            ||
            ""
        )
        .toUpperCase()
        .replace(
            /[^A-Z0-9]/g,
            ""
        )
        .substring(
            0,
            26
        );


    const parts =
        [];


    if(
        compact.length > 0
    ){

        parts.push(
            compact.substring(
                0,
                2
            )
        );

    }


    if(
        compact.length > 2
    ){

        parts.push(
            compact.substring(
                2,
                4
            )
        );

    }


    if(
        compact.length > 4
    ){

        parts.push(
            compact.substring(
                4,
                6
            )
        );

    }


    if(
        compact.length > 6
    ){

        parts.push(
            compact.substring(
                6,
                9
            )
        );

    }


    if(
        compact.length > 9
    ){

        parts.push(
            compact.substring(
                9
            )
        );

    }


    return parts.join(
        "-"
    );

}


document.addEventListener(
    "input",
    function(event){

        if(
            event.target?.id
            !==
            "rsbsa_no"
        ){
            return;
        }


        event.target.value =
            formatMemberRsbsa(
                event.target.value
            );


        validateAndClearMemberField(
            event.target
        );

    }
);


/* =========================================================
   DATE HELPERS

   DATABASE / NATIVE VALUE:
   YYYY-MM-DD

   VISIBLE / MANUAL VALUE:
   MM-DD-YYYY
========================================================= */


/* =========================================================
   ISO DATE -> DISPLAY DATE
========================================================= */

function memberDateToDisplay(
    isoDate
){

    const value =
        String(
            isoDate
            ||
            ""
        )
        .trim();


    if(
        !/^\d{4}-\d{2}-\d{2}$/
        .test(
            value
        )
    ){
        return "";
    }


    const [
        year,
        month,
        day
    ] =
        value.split(
            "-"
        );


    return (
        month
        +
        "-"
        +
        day
        +
        "-"
        +
        year
    );

}


/* =========================================================
   DISPLAY DATE -> ISO DATE
========================================================= */

function memberDisplayDateToIso(
    displayDate
){

    const value =
        String(
            displayDate
            ||
            ""
        )
        .trim();


    if(
        !/^\d{2}-\d{2}-\d{4}$/
        .test(
            value
        )
    ){
        return "";
    }


    const [
        month,
        day,
        year
    ] =
        value.split(
            "-"
        );


    const monthNumber =
        Number(
            month
        );


    const dayNumber =
        Number(
            day
        );


    const yearNumber =
        Number(
            year
        );


    if(
        monthNumber < 1
        ||
        monthNumber > 12
        ||
        dayNumber < 1
        ||
        dayNumber > 31
        ||
        yearNumber < 1000
    ){
        return "";
    }


    const isoDate =
        year
        +
        "-"
        +
        month
        +
        "-"
        +
        day;


    /*
     * Check impossible dates such as:
     * 02-31-2020
     * 04-31-2020
     */
    const testDate =
        new Date(
            yearNumber,
            monthNumber - 1,
            dayNumber
        );


    if(
        testDate.getFullYear()
        !==
        yearNumber
        ||
        testDate.getMonth()
        !==
        monthNumber - 1
        ||
        testDate.getDate()
        !==
        dayNumber
    ){
        return "";
    }


    return isoDate;

}


/* =========================================================
   FORMAT DATE WHILE TYPING

   FORMAT:
   MM-DD-YYYY

   RULES:
   - Month: 01 to 12
   - Day follows actual month
   - February supports leap years
   - Year must be at least 1000
   - Future dates cannot be completed
========================================================= */

function formatMemberDateDigits(
    digits
){

    if(
        digits.length <= 2
    ){
        return digits;
    }


    if(
        digits.length <= 4
    ){

        return (
            digits.substring(
                0,
                2
            )
            +
            "-"
            +
            digits.substring(
                2
            )
        );

    }


    return (
        digits.substring(
            0,
            2
        )
        +
        "-"
        +
        digits.substring(
            2,
            4
        )
        +
        "-"
        +
        digits.substring(
            4,
            8
        )
    );

}


function getMemberMaximumDay(
    month,
    year = null
){

    if(
        month === 2
    ){

        /*
         * Until a complete year exists,
         * February 29 is temporarily allowed.
         */
        if(
            year === null
            ||
            year < 1000
        ){
            return 29;
        }


        const leapYear =
            (
                year % 4 === 0
                &&
                year % 100 !== 0
            )
            ||
            year % 400 === 0;


        return leapYear
            ?
            29
            :
            28;

    }


    if(
        [
            4,
            6,
            9,
            11
        ]
        .includes(
            month
        )
    ){
        return 30;
    }


    return 31;

}


function formatMemberTypedDate(
    value,
    nativeInput = null
){

    const digits =
        String(
            value
            ||
            ""
        )
        .replace(
            /\D/g,
            ""
        )
        .substring(
            0,
            8
        );


    let accepted =
        "";


    for(
        const digit
        of
        digits
    ){

        const position =
            accepted.length;


        /* =============================================
           MONTH FIRST DIGIT
        ============================================= */

        if(
            position === 0
        ){

            if(
                digit !== "0"
                &&
                digit !== "1"
            ){
                break;
            }

        }


        /* =============================================
           MONTH SECOND DIGIT
        ============================================= */

        else if(
            position === 1
        ){

            const month =
                Number(
                    accepted
                    +
                    digit
                );


            if(
                month < 1
                ||
                month > 12
            ){
                break;
            }

        }


        /* =============================================
           DAY FIRST DIGIT
        ============================================= */

        else if(
            position === 2
        ){

            const month =
                Number(
                    accepted.substring(
                        0,
                        2
                    )
                );


            /*
             * February can only begin with
             * 0, 1 or 2.
             */
            if(
                month === 2
            ){

                if(
                    ![
                        "0",
                        "1",
                        "2"
                    ]
                    .includes(
                        digit
                    )
                ){
                    break;
                }

            }
            else{

                if(
                    ![
                        "0",
                        "1",
                        "2",
                        "3"
                    ]
                    .includes(
                        digit
                    )
                ){
                    break;
                }

            }

        }


        /* =============================================
           DAY SECOND DIGIT
        ============================================= */

        else if(
            position === 3
        ){

            const month =
                Number(
                    accepted.substring(
                        0,
                        2
                    )
                );


            const day =
                Number(
                    accepted.substring(
                        2
                    )
                    +
                    digit
                );


            const maximumDay =
                getMemberMaximumDay(
                    month
                );


            if(
                day < 1
                ||
                day > maximumDay
            ){
                break;
            }

        }


        /* =============================================
           YEAR FIRST DIGIT
        ============================================= */

        else if(
            position === 4
        ){

            /*
             * Prevent years such as 0000.
             */
            if(
                digit === "0"
            ){
                break;
            }

        }


        accepted +=
            digit;


        /* =============================================
           COMPLETE DATE VALIDATION
        ============================================= */

        if(
            accepted.length === 8
        ){

            const month =
                Number(
                    accepted.substring(
                        0,
                        2
                    )
                );


            const day =
                Number(
                    accepted.substring(
                        2,
                        4
                    )
                );


            const year =
                Number(
                    accepted.substring(
                        4,
                        8
                    )
                );


            const maximumDay =
                getMemberMaximumDay(
                    month,
                    year
                );


            const isoDate =
                accepted.substring(
                    4,
                    8
                )
                +
                "-"
                +
                accepted.substring(
                    0,
                    2
                )
                +
                "-"
                +
                accepted.substring(
                    2,
                    4
                );


            if(
                year < 1000
                ||
                day > maximumDay
                ||
                (
                    nativeInput?.max
                    &&
                    isoDate > nativeInput.max
                )
            ){

                /*
                 * Remove the last digit so an
                 * invalid complete date cannot
                 * remain inside the field.
                 */
                accepted =
                    accepted.substring(
                        0,
                        7
                    );

            }

        }

    }


    return formatMemberDateDigits(
        accepted
    );

}


/* =========================================================
   NATIVE -> DISPLAY
========================================================= */

function syncMemberDateDisplay(
    nativeInput,
    displayInput
){

    if(
        !nativeInput
        ||
        !displayInput
    ){
        return;
    }


    displayInput.value =
        memberDateToDisplay(
            nativeInput.value
        );

}


/* =========================================================
   DISPLAY -> NATIVE
========================================================= */

function syncMemberTypedDate(
    nativeInput,
    displayInput
){

    if(
        !nativeInput
        ||
        !displayInput
    ){
        return false;
    }


    const isoDate =
        memberDisplayDateToIso(
            displayInput.value
        );


    /*
     * Incomplete / invalid typed value.
     */
    if(
        isoDate === ""
    ){

        nativeInput.value =
            "";

        return false;

    }


    /*
     * Respect the server-supplied maximum date.
     */
    if(
        nativeInput.max
        &&
        isoDate > nativeInput.max
    ){

        nativeInput.value =
            "";

        return false;

    }


    nativeInput.value =
        isoDate;


    return true;

}


/* =========================================================
   OPEN NATIVE CALENDAR
========================================================= */

function openMemberDatePicker(
    nativeInput
){

    if(
        !nativeInput
    ){
        return;
    }


    try{

        if(
            typeof nativeInput.showPicker
            ===
            "function"
        ){

            nativeInput.showPicker();

            return;

        }

    }
    catch(error){

        /*
         * Browser fallback below.
         */

    }


    nativeInput.focus();

    nativeInput.click();

}


/* =========================================================
   SETUP DATE FIELD

   User may:
   1. Type MM-DD-YYYY
   2. Use calendar button
========================================================= */

function setupMemberDatePicker(
    nativeInput,
    displayInput,
    button
){

    if(
        !nativeInput
        ||
        !displayInput
    ){
        return;
    }


    /*
     * Initial value from native input.
     */
    syncMemberDateDisplay(
        nativeInput,
        displayInput
    );


    /*
     * Calendar selection.
     */
    nativeInput.addEventListener(
        "change",
        function(){

            syncMemberDateDisplay(
                nativeInput,
                displayInput
            );


            validateAndClearMemberField(
                nativeInput
            );

        }
    );


    /*
     * Manual typing.
     */
    displayInput.addEventListener(
        "input",
        function(){

            this.value =
            formatMemberTypedDate(
                this.value,
                nativeInput
            );


            syncMemberTypedDate(
                nativeInput,
                displayInput
            );


            /*
             * Once complete and valid,
             * clear an existing inline error.
             */
            if(
                this.value.length
                ===
                10
                &&
                nativeInput.value !== ""
            ){

                validateAndClearMemberField(
                    nativeInput
                );

            }

        }
    );


    /*
     * Final validation when leaving
     * the manually typed field.
     */
    displayInput.addEventListener(
        "blur",
        function(){

            if(
                this.value.trim()
                ===
                ""
            ){

                nativeInput.value =
                    "";

                return;

            }


            const valid =
                syncMemberTypedDate(
                    nativeInput,
                    displayInput
                );


            if(
                valid
            ){

                /*
                * Normalize display.
                */
                syncMemberDateDisplay(
                    nativeInput,
                    displayInput
                );


                validateAndClearMemberField(
                    nativeInput
                );

            }
            else{

                showMemberFieldError(
                    nativeInput,
                    nativeInput === membershipDateInput
                    ?
                    "Please enter a valid membership date in MM-DD-YYYY format."
                    :
                    "Please enter a valid birthday in MM-DD-YYYY format."
                );

            }

        }
    );


    /*
     * Calendar opens ONLY from the
     * calendar button.
     *
     * Clicking the text field now allows
     * the user to type normally.
     */
    if(
        button
    ){

        button.addEventListener(
            "click",
            function(){

                openMemberDatePicker(
                    nativeInput
                );

            }
        );

    }

}


/* =========================================================
   ACTIVATE DATE FIELDS
========================================================= */

setupMemberDatePicker(
    membershipDateInput,
    membershipDateDisplay,
    membershipDateBtn
);


setupMemberDatePicker(
    birthDateInput,
    birthDateDisplay,
    birthDateBtn
);


/*
 * Use the Manila server date supplied
 * by PHP as the maximum Birthday too.
 */

if(
    birthDateInput
    &&
    membershipDateInput?.max
){

    birthDateInput.max =
        membershipDateInput.max;

}


/* =========================================================
   CBU UI / LIVE PREVIEW
========================================================= */

const memberClassificationInput =
    document.getElementById(
        "memberClassification"
    );

const memberClassificationText =
    document.getElementById(
        "memberClassificationText"
    );

const memberClassificationDisplayText =
    document.getElementById(
        "memberClassificationDisplayText"
    );

let memberStoredClassification =
    "Irregular";

const memberCurrentCbuInput =
    document.getElementById(
        "memberCurrentCbu"
    );

const memberCbuSummaryValue =
    document.getElementById(
        "memberCbuSummaryValue"
    );

const memberCbuEditGroup =
    document.getElementById(
        "memberCbuEditGroup"
    );

const memberCurrentCbuDisplay =
    document.getElementById(
        "memberCurrentCbuDisplay"
    );

const memberCbuAction =
    document.getElementById(
        "memberCbuAction"
    );

const memberCbuActionBtn =
    document.getElementById(
        "memberCbuActionBtn"
    );

const memberCbuActionText =
    document.getElementById(
        "memberCbuActionText"
    );

const memberCbuActionMenu =
    document.getElementById(
        "memberCbuActionMenu"
    );

const memberCbuActionDropdown =
    document.querySelector(
        ".member-cbu-action-dropdown"
    );

const memberCbuAdjustmentAmount =
    document.getElementById(
        "memberCbuAdjustmentAmount"
    );

const memberCbuBalanceAfter =
    document.getElementById(
        "memberCbuBalanceAfter"
    );

const memberCbuRemarks =
    document.getElementById(
        "memberCbuRemarks"
    );


function parseMemberMoney(
    value
){

    const number =
        Number(
            String(
                value
                ??
                ""
            )
            .replace(
                /,/g,
                ""
            )
        );


    return Number.isFinite(number)
        ?
        number
        :
        0;

}


function formatMemberMoney(
    value
){

    const amount =
        Number.isFinite(
            Number(value)
        )
        ?
        Number(value)
        :
        0;


    return amount.toLocaleString(
        "en-PH",
        {
            minimumFractionDigits:
                2,

            maximumFractionDigits:
                2
        }
    );

}


function setMemberClassificationPreview(
    classification
){

    const safeClassification =
        String(
            classification
            ||
            "Irregular"
        );


    if(memberClassificationInput){

        memberClassificationInput.value =
            safeClassification;

    }


    if(memberClassificationText){

        memberClassificationText.textContent =
            safeClassification.toUpperCase();

    }


    if(memberClassificationDisplayText){

        memberClassificationDisplayText.textContent =
            safeClassification.toUpperCase();

    }

}


function setMemberCurrentCbu(
    value
){

    let amount =
        parseMemberMoney(
            value
        );


    if(amount < 0){
        amount = 0;
    }


    const fixed =
        amount.toFixed(2);


    if(memberCurrentCbuInput){

        memberCurrentCbuInput.value =
            fixed;

    }


    if(memberCurrentCbuDisplay){

        memberCurrentCbuDisplay.value =
            formatMemberMoney(
                amount
            );

    }


    if(memberCbuSummaryValue){

        memberCbuSummaryValue.textContent =
            "₱"
            +
            formatMemberMoney(
                amount
            );

    }

}


function refreshMemberCbuPreview(){

    const editMode =
        isMemberEditMode();


    if(!editMode){

        let initialAmount =
            parseMemberMoney(
                initialCbuInput?.value
                ||
                0
            );


        if(initialAmount < 0){
            initialAmount = 0;
        }


        setMemberCurrentCbu(
            initialAmount
        );


        setMemberClassificationPreview(
            initialAmount >= 10000
            ?
            "Regular"
            :
            "Irregular"
        );


        return;
    }


    const currentBalance =
        Math.max(
            0,
            parseMemberMoney(
                memberCurrentCbuInput?.value
                ||
                0
            )
        );


    const action =
        String(
            memberCbuAction?.value
            ||
            ""
        )
        .toUpperCase();


    let amount =
        parseMemberMoney(
            memberCbuAdjustmentAmount?.value
            ||
            0
        );


    if(amount < 0){
        amount = 0;
    }


    let balanceAfter =
        currentBalance;


    if(
        action === "ADD"
        &&
        amount > 0
    ){

        balanceAfter =
            currentBalance
            +
            amount;

    }
    else if(
        action === "DEDUCT"
        &&
        amount > 0
        &&
        amount <= currentBalance
    ){

        balanceAfter =
            currentBalance
            -
            amount;

    }


    if(memberCurrentCbuDisplay){

        memberCurrentCbuDisplay.value =
            formatMemberMoney(
                currentBalance
            );

    }


    if(memberCbuSummaryValue){

        memberCbuSummaryValue.textContent =
            "₱"
            +
            formatMemberMoney(
                currentBalance
            );

    }


    if(memberCbuBalanceAfter){

        memberCbuBalanceAfter.value =
            formatMemberMoney(
                balanceAfter
            );

    }


    const storedClassification =
        String(
            memberStoredClassification
            ||
            "Irregular"
        )
        .toLowerCase();


    const classification =
        storedClassification === "regular"
        ||
        balanceAfter >= 10000
        ?
        "Regular"
        :
        "Irregular";


    setMemberClassificationPreview(
        classification
    );

}


function resetMemberCbuEditFields(){

    if(memberCbuAction){

        memberCbuAction.value =
            "";

    }


    if(memberCbuActionText){

        memberCbuActionText.textContent =
            "Select Action";

    }


    memberCbuActionMenu
        ?.classList
        .remove(
            "show"
        );


    memberCbuActionDropdown
        ?.classList
        .remove(
            "open"
        );


    if(memberCbuAdjustmentAmount){

        memberCbuAdjustmentAmount.value =
            "";

    }


    if(memberCbuRemarks){

        memberCbuRemarks.value =
            "";

    }


    if(memberCbuBalanceAfter){

        memberCbuBalanceAfter.value =
            formatMemberMoney(
                parseMemberMoney(
                    memberCurrentCbuInput?.value
                    ||
                    0
                )
            );

    }

}


if(initialCbuInput){

    initialCbuInput.addEventListener(
        "input",
        function(){

            refreshMemberCbuPreview();

            validateAndClearMemberField(
                initialCbuInput
            );

        }
    );

}


/* =========================================================
   CURRENT PAGE & SEARCH
========================================================= */

const params =
    new URLSearchParams(
        window.location.search
    );


const currentPage =
    document.getElementById(
        "current_page"
    );


const currentSearch =
    document.getElementById(
        "current_search"
    );


if(currentPage){

    currentPage.value =
        params.get(
            "page"
        )
        ||
        1;

}


if(currentSearch){

    currentSearch.value =
        params.get(
            "search"
        )
        ||
        "";

}


/* =========================================================
   MEMBER SEARCH
   SERVER-SIDE FILTERING + PAGINATION
========================================================= */

const searchInput =
    document.getElementById(
        "searchMember"
    );


function openMemberList(
    overrides = {}
){

    const keyword =
        overrides.search
        ??
        document
        .getElementById(
            "searchMember"
        )
        ?.value
        .trim()
        ??
        "";


    const status =
        overrides.status
        ??
        document
        .getElementById(
            "statusFilter"
        )
        ?.value
        ??
        "all";


    const classification =
        overrides.classification
        ??
        document
        .getElementById(
            "classificationFilter"
        )
        ?.value
        ??
        "all";


    const query =
        new URLSearchParams();


    query.set(
        "page",
        "1"
    );


    if(
        keyword !== ""
    ){

        query.set(
            "search",
            keyword
        );

    }


    query.set(
        "status",
        status
    );


    query.set(
        "classification",
        classification
    );


    window.location.href =
        "member.php?"
        +
        query.toString();

}


if(searchInput){

    let debounce;


    searchInput.addEventListener(
        "input",
        function(){

            clearTimeout(
                debounce
            );


            const keyword =
                this.value.trim();


            debounce =
                setTimeout(
                    function(){

                        openMemberList({
                            search:keyword
                        });

                    },
                    300
                );

        }
    );

}


/* =========================================================
   INLINE MEMBER VALIDATION
========================================================= */

function isMemberEditMode(){

    return (
        String(
            document.getElementById(
                "member_id"
            )?.value
            ||
            ""
        )
        .trim()
        !==
        ""
    );

}


function getMemberErrorVisualTarget(
    input
){

    if(!input){
        return null;
    }

    if(
        input.id
        ===
        "memberGender"
    ){

        return document.getElementById(
            "memberGenderBtn"
        );

    }


    if(
        input.id
        ===
        "memberMembershipDate"
    ){

        return membershipDateDisplay;

    }


    if(
        input.id
        ===
        "memberBirthDate"
    ){

        return birthDateDisplay;

    }


    if(
        input.id
        ===
        "memberStatus"
    ){

        return document.getElementById(
            "memberStatusBtn"
        );

    }


    if(
        input.id
        ===
        "memberCbuAction"
    ){

        return memberCbuActionBtn;

    }


    return input;

}


function getMemberErrorAnchor(
    input
){

    const visualTarget =
        getMemberErrorVisualTarget(
            input
        );


    if(!visualTarget){
        return null;
    }


    if(
        input?.id
        ===
        "memberStatus"
    ){

        return (
            document.querySelector(
                ".member-status-dropdown"
            )
            ||
            visualTarget
        );

    }


    if(
        input?.id
        ===
        "memberCbuAction"
    ){

        return (
            memberCbuActionDropdown
            ||
            visualTarget
        );

    }


    return (
        visualTarget.closest(
            ".member-date-wrapper"
        )
        ||
        visualTarget.closest(
            ".member-select-wrapper"
        )
        ||
        visualTarget.closest(
            ".member-money-input"
        )
        ||
        visualTarget
    );

}


function clearMemberFieldError(
    input
){

    if(!input){
        return;
    }


    const visualTarget =
        getMemberErrorVisualTarget(
            input
        );


    if(visualTarget){

        visualTarget.classList.remove(
            "is-invalid"
        );

    }


    if(
        input._memberErrorElement
    ){

        input
        ._memberErrorElement
        .remove();


        input._memberErrorElement =
            null;

    }

}


function showMemberFieldError(
    input,
    message
){

    if(
        !input
        ||
        !message
    ){
        return;
    }


    clearMemberFieldError(
        input
    );


    const visualTarget =
        getMemberErrorVisualTarget(
            input
        );


    const anchor =
        getMemberErrorAnchor(
            input
        );


    if(
        !visualTarget
        ||
        !anchor
    ){
        return;
    }


    visualTarget.classList.add(
        "is-invalid"
    );


    const error =
        document.createElement(
            "small"
        );


    error.className =
        "member-field-error";


    error.textContent =
        message;


    anchor.insertAdjacentElement(
        "afterend",
        error
    );


    input._memberErrorElement =
        error;

}


function getMemberServerToday(){

    return (
        membershipDateInput?.max
        ||
        ""
    );

}


function getMemberFieldError(
    input
){

    if(!input){
        return "";
    }


    const value =
        String(
            input.value
            ||
            ""
        )
        .trim();


    const serverToday =
        getMemberServerToday();


    switch(
        input.id
    ){

        case "memberMembershipDate":

            if(
                value
                ===
                ""
            ){

                return (
                    "Please select the membership date."
                );

            }


            if(
                serverToday
                &&
                value > serverToday
            ){

                return (
                    "Membership date cannot be in the future."
                );

            }


            return "";


        case "memberBirthDate":

            if(
                value
                ===
                ""
            ){

                return (
                    "Please select the member's birthday."
                );

            }


            if(
                serverToday
                &&
                value > serverToday
            ){

                return (
                    "Birthday cannot be in the future."
                );

            }


            return "";


        case "memberGender":

            if(
                value !== "Male"
                &&
                value !== "Female"
            ){

                return (
                    "Please select the member's gender."
                );

            }


            return "";


        case "tin_no": {

            const digits =
                value.replace(
                    /\D/g,
                    ""
                );


            if(
                digits.length !== 9
                &&
                digits.length !== 12
            ){

                return (
                    "TIN must contain exactly 9 or 12 digits."
                );

            }


            return "";

        }


        case "rsbsa_no": {

        if(
            value === ""
        ){

            return (
                "Please enter the RSBSA ID."
            );

        }


        const compactRsbsa =
            value.replace(
                /-/g,
                ""
            );


        if(
            !/^[A-Z0-9-]+$/
            .test(
                value
            )
            ||
            !/^[A-Z0-9]+$/
            .test(
                compactRsbsa
            )
        ){

            return (
                "RSBSA ID may contain letters and numbers only."
            );

        }


        if(
            compactRsbsa.length < 14
        ){

            return (
                "RSBSA ID must contain at least 14 letters or numbers."
            );

        }


        if(
            compactRsbsa.length > 26
            ||
            value.length > 30
        ){

            return (
                "RSBSA ID is too long."
            );

        }


        if(
            value
            !==
            formatMemberRsbsa(
                compactRsbsa
            )
        ){

            return (
                "RSBSA ID must follow the XX-XX-XX-XXX-REMAINDER format."
            );

        }


        return "";

    }


        case "contact": {

            const digits =
                value.replace(
                    /\D/g,
                    ""
                );


            /*
             * Contact Number is optional.
             */

            if(
                digits
                ===
                ""
            ){

                return "";

            }


            if(
                digits.length
                !==
                11
            ){

                return (
                    "Contact number must contain exactly 11 digits."
                );

            }


            if(
                !digits.startsWith(
                    "08"
                )
                &&
                !digits.startsWith(
                    "09"
                )
            ){

                return (
                    "Contact number must start with 08 or 09."
                );

            }


            return "";

        }


        case "memberSocialMedia":

            if(
                value
                ===
                ""
            ){

                return (
                    "Please enter the Facebook or Messenger name."
                );

            }


            if(
                value.length > 150
            ){

                return (
                    "Facebook or Messenger name is too long."
                );

            }


            return "";


        case "memberFarmField":

            if(
                value
                ===
                ""
            ){

                return (
                    "Farm field is required."
                );

            }


            if(
                !Number.isFinite(
                    Number(
                        value
                    )
                )
                ||
                Number(
                    value
                ) < 0
            ){

                return (
                    "Farm field must be a valid non-negative hectare value."
                );

            }


            return "";

        case "attachment":

            /*
             * Supporting document is required
             * when registering a NEW member.
             *
             * Existing members do not need to
             * upload another attachment every
             * time their information is edited.
             */

            if(
                isMemberEditMode()
            ){
                return "";
            }


            if(
                !attachmentInput?.files
                ||
                attachmentInput.files.length === 0
            ){

                return (
                    "Please attach at least one supporting document."
                );

            }


            return "";    


        case "initialCbu":

            if(
                isMemberEditMode()
                ||
                value === ""
            ){

                return "";

            }


            if(
                !Number.isFinite(
                    Number(
                        value
                    )
                )
                ||
                Number(
                    value
                ) < 0
            ){

                return (
                    "Initial CBU cannot be negative."
                );

            }


            return "";


        case "memberCbuAction": {

            if(!isMemberEditMode()){
                return "";
            }


            const amountValue =
                String(
                    memberCbuAdjustmentAmount?.value
                    ||
                    ""
                )
                .trim();


            if(
                value === ""
                &&
                amountValue === ""
            ){
                return "";
            }


            if(
                value !== "ADD"
                &&
                value !== "DEDUCT"
            ){

                return (
                    "Please select ADD CBU or DEDUCT CBU."
                );

            }


            return "";

        }


        case "memberCbuAdjustmentAmount": {

            if(!isMemberEditMode()){
                return "";
            }


            const action =
                String(
                    memberCbuAction?.value
                    ||
                    ""
                )
                .toUpperCase();


            if(
                value === ""
                &&
                action === ""
            ){
                return "";
            }


            if(action === ""){

                return (
                    "Please select the CBU action first."
                );

            }


            if(value === ""){

                return (
                    "Please enter the CBU amount."
                );

            }


            const amount =
                Number(value);


            if(
                !Number.isFinite(amount)
                ||
                amount <= 0
            ){

                return (
                    "CBU amount must be greater than zero."
                );

            }


            if(
                action === "DEDUCT"
                &&
                amount >
                    parseMemberMoney(
                        memberCurrentCbuInput?.value
                        ||
                        0
                    )
            ){

                return (
                    "Deduction cannot be greater than the current CBU balance."
                );

            }


            return "";

        }


        case "memberStatus":

            if(
                value !== "ACTIVE"
                &&
                value !== "INACTIVE"
            ){

                return (
                    "Please select the member status."
                );

            }


            return "";

    }


    if(
        input.name
        ===
        "fullname"
    ){

        if(
            value
            ===
            ""
        ){

            return (
                "Please enter the member's full name."
            );

        }


        const namePattern =
            /^[A-Za-zÀ-ÿ\s.'-]+$/;


        if(
            !namePattern.test(
                value
            )
        ){

            return (
                "Please enter a valid full name."
            );

        }


        return "";

    }


    if(
        input.name
        ===
        "address"
    ){

        if(
            value
            ===
            ""
        ){

            return (
                "Please enter the member's address."
            );

        }


        return "";

    }


    return "";

}


function getMemberValidationFields(){

    if(!memberForm){
        return [];
    }


    return [

        memberForm.querySelector(
            '[name="fullname"]'
        ),

        document.getElementById(
            "memberGender"
        ),

        membershipDateInput,

        birthDateInput,

        memberForm.querySelector(
            '[name="address"]'
        ),

        document.getElementById(
            "contact"
        ),

        document.getElementById(
            "memberSocialMedia"
        ),

        document.getElementById(
            "tin_no"
        ),

        document.getElementById(
            "rsbsa_no"
        ),

        document.getElementById(
            "memberFarmField"
        ),

        document.getElementById(
            "memberStatus"
        ),

        memberCbuAction,

        memberCbuAdjustmentAmount,

        attachmentInput,

        initialCbuInput

    ]
    .filter(
        Boolean
    );

}


function validateAndClearMemberField(
    input
){

    if(!input){
        return;
    }


    const message =
        getMemberFieldError(
            input
        );


    if(
        message
        ===
        ""
    ){

        clearMemberFieldError(
            input
        );

    }
    else if(
        input._memberErrorElement
    ){

        showMemberFieldError(
            input,
            message
        );

    }

}


function clearAllMemberFieldErrors(){

    getMemberValidationFields()
    .forEach(
        clearMemberFieldError
    );

}


function focusFirstMemberError(
    input
){

    const visualTarget =
        getMemberErrorVisualTarget(
            input
        );


    if(!visualTarget){
        return;
    }


    visualTarget.scrollIntoView({

        behavior:
            "smooth",

        block:
            "center"

    });


    window.setTimeout(
        function(){

            if(
                input?.id
                ===
                "memberStatus"
            ){

                document
                .getElementById(
                    "memberStatusBtn"
                )
                ?.focus();

                return;

            }


            if(
                input?.id
                ===
                "memberCbuAction"
            ){

                memberCbuActionBtn
                    ?.focus();

                return;

            }


            if(
                input?.id
                ===
                "memberMembershipDate"
            ){

                membershipDateDisplay
                    ?.focus();

                return;

            }


            if(
                input?.id
                ===
                "memberBirthDate"
            ){

                birthDateDisplay
                    ?.focus();

                return;

            }


            visualTarget.focus?.();

        },
        250
    );

}


/* =========================================================
   FORM SUBMISSION
========================================================= */

if(memberForm){

    memberForm.noValidate =
        true;


    getMemberValidationFields()
    .forEach(
        function(input){

            const eventName =
                (
                    input.tagName
                    ===
                    "SELECT"
                    ||
                    input.type
                    ===
                    "date"
                    ||
                    input.id
                    ===
                    "memberStatus"
                )
                ?
                "change"
                :
                "input";


            input.addEventListener(
                eventName,
                function(){

                    validateAndClearMemberField(
                        input
                    );

                }
            );

        }
    );


    memberForm.addEventListener(
        "submit",
        function(event){

            event.preventDefault();


            clearAllMemberFieldErrors();


            const errors =
                [];


            getMemberValidationFields()
            .forEach(
                function(input){

                    const message =
                        getMemberFieldError(
                            input
                        );


                    if(
                        message
                        !==
                        ""
                    ){

                        errors.push({

                            input:
                                input,

                            message:
                                message

                        });


                        showMemberFieldError(
                            input,
                            message
                        );

                    }

                }
            );


            /*
             * No SweetAlert for ordinary
             * field validation.
             */

            if(
                errors.length > 0
            ){

                focusFirstMemberError(
                    errors[0].input
                );

                return;

            }


            const isEdit =
                isMemberEditMode();


            /*
             * SweetAlert remains for the
             * final consequential confirmation.
             */

            Swal.fire({

                title:
                    isEdit
                    ?
                    "Save Changes?"
                    :
                    "Add Member?",

                text:
                    isEdit
                    ?
                    "Do you want to update this member?"
                    :
                    "Do you want to add this member?",

                icon:
                    "question",

                showCancelButton:
                    true,

                confirmButtonText:
                    isEdit
                    ?
                    "Save"
                    :
                    "Add",

                cancelButtonText:
                    "Cancel",

                confirmButtonColor:
                    "#8CA15B",

                cancelButtonColor:
                    "#C62828",

                reverseButtons:
                    true

            })
            .then(
                async function(
                    result
                ){

                    if(
                        !result.isConfirmed
                    ){
                        return;
                    }


                    const saveButton =
                        document.getElementById(
                            "memberSaveBtn"
                        );


                    if(saveButton){
                        saveButton.disabled =
                            true;
                    }


                    try{

                        const formData =
                            new FormData(
                                memberForm
                            );


                        const response =
                            await fetch(
                                memberForm.action,
                                {
                                    method:
                                        "POST",

                                    body:
                                        formData,

                                    headers:
                                    {
                                        "X-Requested-With":
                                            "XMLHttpRequest"
                                    },

                                    credentials:
                                        "same-origin"
                                }
                            );


                        if(
                            !response.ok
                        ){

                            const errorMessage =
                                (
                                    await response.text()
                                ).trim();


                            await Swal.fire({

                                icon:
                                    "error",

                                title:
                                    isEdit
                                    ?
                                    "Unable to Update Member"
                                    :
                                    "Unable to Add Member",

                                text:
                                    errorMessage
                                    ||
                                    "Please check the member information and try again.",

                                confirmButtonText:
                                    "OK",

                                confirmButtonColor:
                                    "#8CA15B",

                                allowOutsideClick:
                                    false

                            });


                            return;

                        }


                        /*
                        * add_member.php / update_member.php
                        * redirects to member.php after success.
                        *
                        * fetch follows that redirect, then we
                        * navigate to the resulting URL so the
                        * existing success SweetAlert and scroll
                        * restoration continue to work.
                        */

                        if(
                            response.redirected
                            &&
                            response.url
                        ){

                            window.location.href =
                                response.url;

                            return;

                        }


                        window.location.reload();

                    }
                    catch(error){

                        console.error(
                            "Member save error:",
                            error
                        );


                        await Swal.fire({

                            icon:
                                "error",

                            title:
                                isEdit
                                ?
                                "Unable to Update Member"
                                :
                                "Unable to Add Member",

                            text:
                                "Something went wrong while saving the member. Please try again.",

                            confirmButtonText:
                                "OK",

                            confirmButtonColor:
                                "#8CA15B",

                            allowOutsideClick:
                                false

                        });

                    }
                    finally{

                        if(saveButton){
                            saveButton.disabled =
                                false;
                        }

                    }

                }
            );

        }
    );

}


/* =========================================================
   SAVE BUTTON LABEL
========================================================= */

function setMemberSaveButtonLabel(
    label
){

    const button =
        document.getElementById(
            "memberSaveBtn"
        );


    if(!button){
        return;
    }


    const text =
        button.querySelector(
            "span"
        );


    if(text){

        text.textContent =
            label;

    }
    else{

        button.textContent =
            label;

    }

}


/* =========================================================
   EDIT MEMBER
========================================================= */

document.addEventListener(
    "click",
    function(event){

        const button =
            event.target.closest(
                ".member-edit-btn"
            );


        if(!button){
            return;
        }


        clearAllMemberFieldErrors();


        const modalElement =
            document.getElementById(
                "addMemberModal"
            );


        if(
            !modalElement
            ||
            !memberForm
        ){
            return;
        }


        const modal =
            bootstrap.Modal
            .getOrCreateInstance(
                modalElement
            );


        modal.show();


        const title =
            document.querySelector(
                ".member-window-header h1"
            );


        if(title){

            title.textContent =
                "EDIT MEMBER";

        }


        memberForm.action =
            "update_member.php";


        setMemberSaveButtonLabel(
            "UPDATE"
        );


        const archiveButton =
            document.getElementById(
                "archiveMemberBtn"
            );


        if(archiveButton){

            archiveButton.style.display =
                "flex";

        }


        if(viewAttachmentsBtn){

            viewAttachmentsBtn.style.display =
                "flex";


            viewAttachmentsBtn.dataset.id =
                button.dataset.id
                ||
                "";

        }

        if(viewCbuHistoryBtn){

            viewCbuHistoryBtn.style.display =
                "flex";

        }


        const memberIDInput =
            document.getElementById(
                "member_id"
            );


        if(memberIDInput){

            memberIDInput.value =
                button.dataset.id
                ||
                "";

        }


        /* =============================================
           PROFILE
        ============================================= */

        const preview =
            document.getElementById(
                "previewImage"
            );


        const placeholder =
            document.getElementById(
                "uploadPlaceholder"
            );


        if(
            preview
            &&
            placeholder
        ){

            if(
                button.dataset.image
            ){

                preview.src =
                button.dataset.image
                +
                "&v="
                +
                Date.now();


                preview.style.display =
                    "block";


                placeholder.style.display =
                    "none";

            }
            else{

                preview.src =
                    "";


                preview.style.display =
                    "none";


                placeholder.style.display =
                    "flex";

            }

        }


        /* =============================================
           BASIC DATA
        ============================================= */

        const fullnameInput =
            memberForm.querySelector(
                '[name="fullname"]'
            );


        const addressInput =
            memberForm.querySelector(
                '[name="address"]'
            );


        const genderInput =
            document.getElementById(
                "memberGender"
            );


        const tinInput =
            document.getElementById(
                "tin_no"
            );


        const rsbsaInput =
            document.getElementById(
                "rsbsa_no"
            );


        const contactInput =
            document.getElementById(
                "contact"
            );


        const socialInput =
            document.getElementById(
                "memberSocialMedia"
            );


        const farmInput =
            document.getElementById(
                "memberFarmField"
            );


        if(fullnameInput){

            fullnameInput.value =
                button.dataset.name
                ||
                "";

        }


        if(genderInput){

            genderInput.value =
                button.dataset.gender
                ||
                "";

        }


        if(memberGenderText){

            memberGenderText.textContent =
                button.dataset.gender
                ?
                button.dataset.gender.toUpperCase()
                :
                "Select Gender";

        }


        /* =============================================
           MEMBERSHIP DATE
        ============================================= */

        if(membershipDateInput){

            membershipDateInput.value =
                button.dataset.membershipDate
                ||
                button.dataset.membership
                ||
                "";


            syncMemberDateDisplay(
                membershipDateInput,
                membershipDateDisplay
            );

        }


        /* =============================================
           BIRTHDAY
        ============================================= */

        if(birthDateInput){

            birthDateInput.value =
                button.dataset.birth
                ||
                "";


            syncMemberDateDisplay(
                birthDateInput,
                birthDateDisplay
            );

        }


        if(addressInput){

            addressInput.value =
                button.dataset.address
                ||
                "";

        }


        if(tinInput){

            tinInput.value =
                button.dataset.tin
                ||
                "";

        }


        if(rsbsaInput){

            rsbsaInput.value =
                formatMemberRsbsa(
                    button.dataset.rsbsa
                    ||
                    ""
                );

        }


        if(contactInput){

            contactInput.value =
                formatMemberContact(
                    button.dataset.contact
                    ||
                    ""
                );

        }


        if(socialInput){

            socialInput.value =
                button.dataset.social
                ||
                "";

        }


        if(farmInput){

            farmInput.value =
                button.dataset.farmField
                ||
                "";

        }


        /* =============================================
           STATUS
        ============================================= */

        const memberStatusValue =
            button.dataset.status
            ||
            "";


        const statusInput =
            document.getElementById(
                "memberStatus"
            );


        const statusText =
            document.getElementById(
                "memberStatusText"
            );


        if(statusInput){

            statusInput.value =
                memberStatusValue;

        }


        if(statusText){

            statusText.textContent =
                memberStatusValue
                ||
                "Select Status";

        }


        /* =============================================
           PERMANENT CLASSIFICATION
        ============================================= */

        const memberClassification =
            button.dataset.classification
            ||
            "Irregular";


        memberStoredClassification =
            memberClassification;


        const classificationInput =
            document.getElementById(
                "memberClassification"
            );


        const classificationText =
            document.getElementById(
                "memberClassificationText"
            );


        if(
            classificationInput
            &&
            memberClassification
        ){

            classificationInput.value =
                memberClassification;

        }


        if(
            classificationText
            &&
            memberClassification
        ){

            classificationText.textContent =
                memberClassification
                .toUpperCase();

        }

        const classificationDisplayText =
            document.getElementById(
                "memberClassificationDisplayText"
            );


        if(
            classificationDisplayText
            &&
            memberClassification
        ){

            classificationDisplayText.textContent =
                memberClassification.toUpperCase();

        }


        /* =============================================
           EDIT MODE CBU
        ============================================= */

        const initialCbuGroup =
            document.getElementById(
                "initialCbuGroup"
            );


        if(initialCbuGroup){

            initialCbuGroup.style.display =
                "none";

        }


        if(initialCbuInput){

            initialCbuInput.value =
                "";

        }


        if(memberCbuEditGroup){

            memberCbuEditGroup.style.display =
                "";

        }


        setMemberCurrentCbu(
            button.dataset.cbu
            ||
            "0.00"
        );


        resetMemberCbuEditFields();


        refreshMemberCbuPreview();


        /* =============================================
           CLEAR NEW ATTACHMENT INPUT
        ============================================= */

        if(attachmentInput){

            attachmentInput.value =
                "";

        }


        if(selectedFiles){

            selectedFiles.textContent =
                "No files selected.";

        }

    }
);


/* =========================================================
   VIEW MEMBER ATTACHMENTS
========================================================= */

if(viewAttachmentsBtn){

    viewAttachmentsBtn.addEventListener(
        "click",
        function(){

            const editModal =
                bootstrap.Modal.getInstance(
                    document.getElementById(
                        "addMemberModal"
                    )
                );


            if(editModal){

                editModal.hide();

            }


            loadAttachments(
                this.dataset.id,
                true
            );

        }
    );

}


/* =========================================================
   ATTACHMENT ROW
========================================================= */

function createMemberAttachmentRow(
    file
){

    const filename =
        String(
            file.file_path
            ||
            ""
        )
        .split(
            "/"
        )
        .pop()
        ||
        "Attachment";


    const extension =
        filename
        .split(
            "."
        )
        .pop()
        .toLowerCase();


    let icon =
        "bi-file-earmark";


    if(
        [
            "jpg",
            "jpeg",
            "png",
            "gif",
            "webp"
        ]
        .includes(
            extension
        )
    ){

        icon =
            "bi-image";

    }
    else if(
        extension
        ===
        "pdf"
    ){

        icon =
            "bi-file-earmark-pdf";

    }


    const row =
        document.createElement(
            "div"
        );


    row.className =
        "attachment-item";


    const left =
        document.createElement(
            "div"
        );


    left.className =
        "attachment-left";


    const iconElement =
        document.createElement(
            "i"
        );


    iconElement.className =
        "bi "
        +
        icon;


    const filenameElement =
        document.createElement(
            "span"
        );


    filenameElement.textContent =
        filename;


    filenameElement.title =
        filename;


    left.append(
        iconElement,
        filenameElement
    );


    const actions =
        document.createElement(
            "div"
        );


    actions.className =
        "attachment-actions";


    const previewButton =
        document.createElement(
            "button"
        );


    previewButton.type =
        "button";


    previewButton.className =
        "preview-file-btn";


    previewButton.innerHTML =
        '<i class="bi bi-eye-fill"></i> VIEW';


    previewButton.addEventListener(
        "click",
        function(){

            const fileUrl =
                "serve_member_file.php?type=attachment&attachment_id="
                +
                encodeURIComponent(
                    String(
                        file.attachment_id
                        ||
                        ""
                    )
                )
                +
                "&v="
                +
                Date.now();


            previewAttachment(
                fileUrl,
                filename
            );

        }
    );


    const removeButton =
        document.createElement(
            "button"
        );


    removeButton.type =
        "button";


    removeButton.className =
        "remove-file-btn";


    removeButton.dataset.id =
        String(
            file.attachment_id
            ||
            ""
        );


    removeButton.innerHTML =
        '<i class="bi bi-trash-fill"></i> REMOVE';


    actions.append(
        previewButton,
        removeButton
    );


    row.append(
        left,
        actions
    );


    return row;

}


/* =========================================================
   LOAD ATTACHMENTS
========================================================= */

function loadAttachments(
    memberID,
    showModal = false
){

    if(!memberID){
        return;
    }


    fetch(
        "get_member_attachments.php?member_id="
        +
        encodeURIComponent(
            memberID
        )
    )

    .then(
        function(
            response
        ){

            return response.json();

        }
    )

    .then(
        function(
            data
        ){

            const list =
                document.getElementById(
                    "attachmentList"
                );


            if(!list){
                return;
            }


            list.replaceChildren();


            if(
                !Array.isArray(
                    data
                )
                ||
                data.length === 0
            ){

                const empty =
                    document.createElement(
                        "p"
                    );


                empty.className =
                    "member-empty-attachment";


                empty.textContent =
                    "No attachments found.";


                list.appendChild(
                    empty
                );

            }
            else{

                data.forEach(
                    function(
                        file
                    ){

                        list.appendChild(
                            createMemberAttachmentRow(
                                file
                            )
                        );

                    }
                );

            }


            if(showModal){

                const attachmentModalElement =
                    document.getElementById(
                        "attachmentModal"
                    );


                if(
                    attachmentModalElement
                ){

                    bootstrap.Modal
                    .getOrCreateInstance(
                        attachmentModalElement
                    )
                    .show();

                }

            }

        }
    )

    .catch(
        function(){

            Swal.fire({

                icon:
                    "error",

                title:
                    "Unable to Load Attachments",

                text:
                    "Please try again.",

                confirmButtonColor:
                    "#8CA15B"

            });

        }
    );

}


/* =========================================================
   SELECTED ATTACHMENTS
========================================================= */

if(
    attachmentInput
    &&
    selectedFiles
){

    attachmentInput.addEventListener(
        "change",
        function(){

            validateAndClearMemberField(
                attachmentInput
            );


            selectedFiles
                .replaceChildren();


            if(
                !this.files
                ||
                this.files.length === 0
            ){

                selectedFiles.textContent =
                    "No files selected.";

                return;

            }


            Array
            .from(
                this.files
            )
            .forEach(
                function(
                    file
                ){

                    const item =
                        document.createElement(
                            "div"
                        );


                    item.textContent =
                        "📄 "
                        +
                        file.name;


                    selectedFiles
                        .appendChild(
                            item
                        );

                }
            );

        }
    );

}


/* =========================================================
   RESET / ADD MEMBER MODE
========================================================= */

const addMemberBtn =
    document.querySelector(
        ".btn-add-member"
    );


if(addMemberBtn){

    addMemberBtn.addEventListener(
        "click",
        function(){

            if(!memberForm){
                return;
            }


            memberForm.reset();


            clearAllMemberFieldErrors();

            const genderInput =
                document.getElementById(
                    "memberGender"
                );

            const genderText =
                document.getElementById(
                    "memberGenderText"
                );


            if(genderInput){

                genderInput.value =
                    "";

            }


            if(genderText){

                genderText.textContent =
                    "Select Gender";

            }


            /* =============================================
               CLASSIFICATION
            ============================================= */

            const classificationInput =
                document.getElementById(
                    "memberClassification"
                );


            const classificationText =
                document.getElementById(
                    "memberClassificationText"
                );


            memberStoredClassification =
                "Irregular";


            if(classificationInput){

                classificationInput.value =
                    "Irregular";

            }


            if(classificationText){

                classificationText.textContent =
                    "IRREGULAR";

            }

            const classificationDisplayText =
                document.getElementById(
                    "memberClassificationDisplayText"
                );


            if(classificationDisplayText){

                classificationDisplayText.textContent =
                    "IRREGULAR";

            }


            /* =============================================
               STATUS
            ============================================= */

            const status =
                document.getElementById(
                    "memberStatus"
                );


            const statusText =
                document.getElementById(
                    "memberStatusText"
                );


            if(status){

                status.value =
                    "";

            }


            if(statusText){

                statusText.textContent =
                    "Select Status";

            }


            /* =============================================
               INITIAL CBU
            ============================================= */

            const initialGroup =
                document.getElementById(
                    "initialCbuGroup"
                );


            if(initialGroup){

                initialGroup.style.display =
                    "";

            }


            if(initialCbuInput){

                initialCbuInput.value =
                    "";

            }


            if(memberCbuEditGroup){

                memberCbuEditGroup.style.display =
                    "none";

            }


            setMemberCurrentCbu(
                0
            );


            resetMemberCbuEditFields();


            /* =============================================
               ADD MODE
            ============================================= */

            const title =
                document.querySelector(
                    ".member-window-header h1"
                );


            if(title){

                title.textContent =
                    "ADD MEMBER";

            }


            memberForm.action =
                "add_member.php";


            setMemberSaveButtonLabel(
                "SAVE"
            );


            const archiveButton =
                document.getElementById(
                    "archiveMemberBtn"
                );


            if(archiveButton){

                archiveButton.style.display =
                    "none";

            }


            if(viewAttachmentsBtn){

                viewAttachmentsBtn.style.display =
                    "none";


                viewAttachmentsBtn.dataset.id =
                    "";

            }

            if(viewCbuHistoryBtn){

                viewCbuHistoryBtn.style.display =
                    "none";

            }


            const memberIDInput =
                document.getElementById(
                    "member_id"
                );


            if(memberIDInput){

                memberIDInput.value =
                    "";

            }


            /* =============================================
               PROFILE
            ============================================= */

            const preview =
                document.getElementById(
                    "previewImage"
                );


            const placeholder =
                document.getElementById(
                    "uploadPlaceholder"
                );


            if(preview){

                preview.src =
                    "";


                preview.style.display =
                    "none";

            }


            if(placeholder){

                placeholder.style.display =
                    "flex";

            }


            if(profileInput){

                profileInput.value =
                    "";

            }


            /* =============================================
               ATTACHMENTS
            ============================================= */

            if(attachmentInput){

                attachmentInput.value =
                    "";

            }


            if(selectedFiles){

                selectedFiles.textContent =
                    "No files selected.";

            }


            /*
             * form.reset() restores the PHP
             * generated current Manila date.
             */

            syncMemberDateDisplay(
                membershipDateInput,
                membershipDateDisplay
            );


            syncMemberDateDisplay(
                birthDateInput,
                birthDateDisplay
            );


            refreshMemberCbuPreview();

        }
    );

}


/* =========================================================
   ARCHIVE MEMBER
========================================================= */

const archiveMemberBtn =
    document.getElementById(
        "archiveMemberBtn"
    );


if(archiveMemberBtn){

    archiveMemberBtn.addEventListener(
        "click",
        function(){

            Swal.fire({

                title:
                    "Archive Member?",

                text:
                    "This member will be moved to the archive.",

                icon:
                    "warning",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Archive",

                cancelButtonText:
                    "Cancel",

                confirmButtonColor:
                    "#B71C1C",

                cancelButtonColor:
                    "#8CA15B",

                reverseButtons:
                    true

            })
            .then(
                function(
                    result
                ){

                    if(
                        !result.isConfirmed
                        ||
                        !memberForm
                    ){
                        return;
                    }


                    memberForm.action =
                        "archive_member.php";


                    memberForm.submit();

                }
            );

        }
    );

}


/* =========================================================
   ATTACHMENT FILE PREVIEW
========================================================= */

function previewAttachment(
    fileUrl,
    filename = ""
){

    const cleanFilename =
        String(
            filename
            ||
            ""
        )
        .trim();


    const extension =
        cleanFilename
        .includes(
            "."
        )
        ?
        cleanFilename
        .split(
            "."
        )
        .pop()
        .toLowerCase()
        :
        "";


    if(
        extension === "pdf"
    ){

        Swal.fire({

            width:
                1000,

            showCloseButton:
                true,

            showConfirmButton:
                false,

            html:
                `
                <iframe
                    src="${fileUrl}"
                    style="
                        width:100%;
                        height:700px;
                        border:none;
                    "
                ></iframe>
                `

        });


        return;

    }


    Swal.fire({

        imageUrl:
            fileUrl,

        imageWidth:
            700,

        showCloseButton:
            true,

        showConfirmButton:
            false

    });

}


/* =========================================================
   REMOVE ATTACHMENT
========================================================= */

document.addEventListener(
    "click",
    function(event){

        const removeButton =
            event.target.closest(
                ".remove-file-btn"
            );


        if(!removeButton){
            return;
        }


        const attachmentID =
            removeButton.dataset.id;


        const memberID =
            viewAttachmentsBtn?.dataset.id
            ||
            "";


        Swal.fire({

            title:
                "Remove Attachment?",

            text:
                "This file will be permanently deleted.",

            icon:
                "warning",

            showCancelButton:
                true,

            confirmButtonColor:
                "#B71C1C",

            cancelButtonColor:
                "#8CA15B",

            confirmButtonText:
                "Remove",

            cancelButtonText:
                "Cancel",

            reverseButtons:
                true

        })
        .then(
            function(
                result
            ){

                if(
                    !result.isConfirmed
                ){
                    return;
                }


                fetch(
                    "remove_attachment.php",
                    {

                        method:
                            "POST",

                        headers:
                        {

                            "Content-Type":
                                "application/x-www-form-urlencoded"

                        },

                        body:
                            "attachment_id="
                            +
                            encodeURIComponent(
                                attachmentID
                            )
                            +
                            "&csrf_token="
                            +
                            encodeURIComponent(
                                memberForm
                                    ?.querySelector(
                                        '[name="csrf_token"]'
                                    )
                                    ?.value
                                ||
                                ""
                            )

                    }
                )

                .then(
                    function(
                        response
                    ){

                        return response.text();

                    }
                )

                .then(
                    function(
                        response
                    ){

                        if(
                            response
                            ===
                            "success"
                        ){

                            loadAttachments(
                                memberID
                            );


                            Swal.fire({

                                icon:
                                    "success",

                                title:
                                    "Attachment Removed!",

                                text:
                                    "The attachment has been deleted successfully.",

                                showConfirmButton:
                                    false,

                                timer:
                                    1700,

                                timerProgressBar:
                                    true

                            });


                            return;

                        }


                        Swal.fire({

                            icon:
                                "error",

                            title:
                                "Unable to Remove Attachment",

                            text:
                                response
                                ||
                                "Please try again.",

                            confirmButtonColor:
                                "#8CA15B"

                        });

                    }
                )

                .catch(
                    function(){

                        Swal.fire({

                            icon:
                                "error",

                            title:
                                "Unable to Remove Attachment",

                            text:
                                "Please try again.",

                            confirmButtonColor:
                                "#8CA15B"

                        });

                    }
                );

            }
        );

    }
);


/* =========================================================
   RETURN TO EDIT MODAL AFTER ATTACHMENT VIEWER
========================================================= */

const attachmentModalElement =
    document.getElementById(
        "attachmentModal"
    );


if(attachmentModalElement){

    attachmentModalElement.addEventListener(
        "hidden.bs.modal",
        function(){

            if(
                !isMemberEditMode()
            ){
                return;
            }


            const editModalElement =
                document.getElementById(
                    "addMemberModal"
                );


            if(editModalElement){

                bootstrap.Modal
                .getOrCreateInstance(
                    editModalElement
                )
                .show();

            }

        }
    );

}


/* =========================================================
   VIEW CBU HISTORY
========================================================= */

if(viewCbuHistoryBtn){

    viewCbuHistoryBtn.addEventListener(
        "click",
        function(){

            const memberID =
                String(
                    document.getElementById(
                        "member_id"
                    )?.value
                    ||
                    ""
                )
                .trim();


            if(!memberID){

                Swal.fire({

                    icon:
                        "warning",

                    title:
                        "Member Not Found",

                    text:
                        "Unable to identify the member record.",

                    confirmButtonColor:
                        "#8CA15B"

                });

                return;
            }


            const editModalElement =
                document.getElementById(
                    "addMemberModal"
                );


            const editModal =
                editModalElement
                ?
                bootstrap.Modal.getInstance(
                    editModalElement
                )
                :
                null;


            if(editModal){

                editModal.hide();

            }


            loadMemberCbuHistory(
                memberID,
                true
            );

        }
    );

}


/* =========================================================
   CBU HISTORY MONEY FORMAT
========================================================= */

function formatMemberCbuHistoryMoney(
    value
){

    const amount =
        Number(
            value
        );


    const safeAmount =
        Number.isFinite(
            amount
        )
        ?
        amount
        :
        0;


    return (
        "₱"
        +
        safeAmount.toLocaleString(
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


/* =========================================================
   CBU HISTORY DATE FORMAT
========================================================= */

function formatMemberCbuHistoryDate(
    value
){

    const raw =
        String(
            value
            ||
            ""
        )
        .trim();


    if(!raw){

        return "—";

    }


    /*
     * MySQL:
     * YYYY-MM-DD HH:MM:SS
     */

    const match =
        raw.match(
            /^(\d{4})-(\d{2})-(\d{2})(?:\s+(\d{2}):(\d{2}):(\d{2}))?$/
        );


    if(!match){

        return raw;

    }


    const year =
        match[1];

    const month =
        match[2];

    const day =
        match[3];


    if(
        !match[4]
        ||
        !match[5]
    ){

        return (
            month
            +
            "-"
            +
            day
            +
            "-"
            +
            year
        );

    }


    let hour =
        Number(
            match[4]
        );


    const minute =
        match[5];


    const period =
        hour >= 12
        ?
        "PM"
        :
        "AM";


    hour =
        hour % 12;


    if(hour === 0){

        hour =
            12;

    }


    return (
        month
        +
        "-"
        +
        day
        +
        "-"
        +
        year
        +
        " "
        +
        hour
        +
        ":"
        +
        minute
        +
        " "
        +
        period
    );

}


/* =========================================================
   CREATE CBU HISTORY CELL
========================================================= */

function createMemberCbuHistoryCell(
    label,
    text,
    className = ""
){

    const cell =
        document.createElement(
            "div"
        );


    cell.dataset.label =
        label;


    cell.textContent =
        text;


    if(className){

        cell.className =
            className;

    }


    return cell;

}


/* =========================================================
   CREATE CBU HISTORY ROW
========================================================= */

function createMemberCbuHistoryRow(
    transaction
){

    const row =
        document.createElement(
            "div"
        );


    row.className =
        "member-cbu-history-row";


    const type =
        String(
            transaction.display_type
            ||
            transaction.transaction_type
            ||
            ""
        )
        .trim()
        .toUpperCase();


    /* DATE */

    const dateCell =
        createMemberCbuHistoryCell(
            "DATE",
            formatMemberCbuHistoryDate(
                transaction.transaction_date
            )
        );


    /* TYPE */

    const typeCell =
        createMemberCbuHistoryCell(
            "TYPE",
            type || "—",
            "member-cbu-history-type"
        );


    if(type === "INITIAL"){

        typeCell.classList.add(
            "initial"
        );

    }
    else if(type === "ADD"){

        typeCell.classList.add(
            "add"
        );

    }
    else if(type === "DEDUCT"){

        typeCell.classList.add(
            "deduct"
        );

    }


    /* AMOUNT */

    let amountText =
        formatMemberCbuHistoryMoney(
            transaction.amount
        );


    if(
        type === "ADD"
        ||
        type === "INITIAL"
    ){

        amountText =
            "+"
            +
            amountText;

    }
    else if(
        type === "DEDUCT"
    ){

        amountText =
            "-"
            +
            amountText;

    }


    const amountCell =
        createMemberCbuHistoryCell(
            "AMOUNT",
            amountText,
            "member-cbu-history-amount"
        );


    /* PREVIOUS BALANCE */

    const previousCell =
        createMemberCbuHistoryCell(
            "PREVIOUS",
            formatMemberCbuHistoryMoney(
                transaction.previous_balance
            ),
            "member-cbu-history-previous"
        );


    /* BALANCE AFTER */

    const balanceCell =
        createMemberCbuHistoryCell(
            "BALANCE AFTER",
            formatMemberCbuHistoryMoney(
                transaction.new_balance
            ),
            "member-cbu-history-balance"
        );


    /* REMARKS */

    const remarks =
        String(
            transaction.remarks
            ||
            ""
        )
        .trim();


    const remarksCell =
        createMemberCbuHistoryCell(
            "REMARKS",
            remarks || "—",
            "member-cbu-history-remarks"
        );


    /* RECORDED BY */

    const recordedBy =
        String(
            transaction.recorded_by
            ||
            ""
        )
        .trim();


    const userCell =
        createMemberCbuHistoryCell(
            "RECORDED BY",
            recordedBy || "Unknown User",
            "member-cbu-history-user"
        );


    row.append(
        dateCell,
        typeCell,
        amountCell,
        previousCell,
        balanceCell,
        remarksCell,
        userCell
    );


    return row;

}


/* =========================================================
   CBU HISTORY EMPTY STATE
========================================================= */

function createMemberCbuHistoryEmpty(){

    const empty =
        document.createElement(
            "div"
        );


    empty.className =
        "member-cbu-history-empty";


    const icon =
        document.createElement(
            "i"
        );


    icon.className =
        "bi bi-clock-history";


    const text =
        document.createElement(
            "p"
        );


    text.textContent =
        "No CBU transactions found.";


    empty.append(
        icon,
        text
    );


    return empty;

}


/* =========================================================
   LOAD CBU HISTORY
========================================================= */

function loadMemberCbuHistory(
    memberID,
    showModal = false
){

    if(!memberID){

        return;

    }


    const list =
        document.getElementById(
            "cbuHistoryList"
        );


    const memberNameElement =
        document.getElementById(
            "cbuHistoryMemberName"
        );


    const currentBalanceElement =
        document.getElementById(
            "cbuHistoryCurrentBalance"
        );


    if(list){

        list.replaceChildren();


        const loading =
            document.createElement(
                "div"
            );


        loading.className =
            "member-cbu-history-empty";


        const icon =
            document.createElement(
                "i"
            );


        icon.className =
            "bi bi-arrow-repeat";


        const text =
            document.createElement(
                "p"
            );


        text.textContent =
            "Loading CBU history...";


        loading.append(
            icon,
            text
        );


        list.appendChild(
            loading
        );

    }


    fetch(
        "get_member_cbu_history.php?member_id="
        +
        encodeURIComponent(
            memberID
        ),
        {
            method:
                "GET",

            headers:
            {
                "Accept":
                    "application/json"
            },

            cache:
                "no-store"
        }
    )

    .then(
        async function(
            response
        ){

            let data;


            try{

                data =
                    await response.json();

            }
            catch(error){

                throw new Error(
                    "The server returned an invalid response."
                );

            }


            if(
                !response.ok
                ||
                !data.success
            ){

                throw new Error(
                    data.message
                    ||
                    "Unable to load CBU history."
                );

            }


            return data;

        }
    )

    .then(
        function(
            data
        ){

            if(memberNameElement){

                const fullName =
                    String(
                        data.member?.full_name
                        ||
                        ""
                    )
                    .trim();


                const memberCode =
                    String(
                        data.member?.member_code
                        ||
                        ""
                    )
                    .trim();


                memberNameElement.textContent =
                    memberCode
                    ?
                    (
                        fullName
                        +
                        " ("
                        +
                        memberCode
                        +
                        ")"
                    )
                    :
                    (
                        fullName
                        ||
                        "—"
                    );

            }


            if(currentBalanceElement){

                currentBalanceElement.textContent =
                    formatMemberCbuHistoryMoney(
                        data.member?.current_cbu
                        ||
                        0
                    );

            }


            if(list){

                list.replaceChildren();


                if(
                    !Array.isArray(
                        data.transactions
                    )
                    ||
                    data.transactions.length === 0
                ){

                    list.appendChild(
                        createMemberCbuHistoryEmpty()
                    );

                }
                else{

                    data.transactions.forEach(
                        function(
                            transaction
                        ){

                            list.appendChild(
                                createMemberCbuHistoryRow(
                                    transaction
                                )
                            );

                        }
                    );

                }

            }


            if(showModal){

                const modalElement =
                    document.getElementById(
                        "cbuHistoryModal"
                    );


                if(modalElement){

                    bootstrap.Modal
                    .getOrCreateInstance(
                        modalElement
                    )
                    .show();

                }

            }

        }
    )

    .catch(
        function(
            error
        ){

            if(list){

                list.replaceChildren();

                list.appendChild(
                    createMemberCbuHistoryEmpty()
                );

            }


            /*
             * If the Edit Member modal was hidden before
             * loading and the request failed, restore it.
             */

            if(
                showModal
                &&
                isMemberEditMode()
            ){

                const editModalElement =
                    document.getElementById(
                        "addMemberModal"
                    );


                if(editModalElement){

                    bootstrap.Modal
                    .getOrCreateInstance(
                        editModalElement
                    )
                    .show();

                }

            }


            Swal.fire({

                icon:
                    "error",

                title:
                    "Unable to Load CBU History",

                text:
                    error.message
                    ||
                    "Please try again.",

                confirmButtonColor:
                    "#8CA15B"

            });

        }
    );

}


/* =========================================================
   RETURN TO EDIT MODAL AFTER CBU HISTORY
========================================================= */

const cbuHistoryModalElement =
    document.getElementById(
        "cbuHistoryModal"
    );


if(cbuHistoryModalElement){

    cbuHistoryModalElement.addEventListener(
        "hidden.bs.modal",
        function(){

            if(
                !isMemberEditMode()
            ){

                return;

            }


            const editModalElement =
                document.getElementById(
                    "addMemberModal"
                );


            if(editModalElement){

                bootstrap.Modal
                .getOrCreateInstance(
                    editModalElement
                )
                .show();

            }

        }
    );

}


/* =========================================================
   MEMBER PAGE FILTERS
========================================================= */

const statusFilterInput =
    document.getElementById(
        "selectedStatus"
    );


const statusPopup =
    document.getElementById(
        "statusPopup"
    );


const classFilterInput =
    document.getElementById(
        "selectedClassification"
    );


const classPopup =
    document.getElementById(
        "classificationPopup"
    );


const statusFilterWrapper =
    statusFilterInput
        ?.closest(
            ".filter-wrapper"
        );


const classFilterWrapper =
    classFilterInput
        ?.closest(
            ".filter-wrapper"
        );


/* =========================================================
   STATUS DROPDOWN TOGGLE
========================================================= */

if(
    statusFilterWrapper
    &&
    statusPopup
){

    statusFilterWrapper.addEventListener(
        "click",
        function(event){

            /*
             * If an actual option inside the popup
             * was clicked, let its own handler work.
             */
            if(
                statusPopup.contains(
                    event.target
                )
            ){
                return;
            }


            event.stopPropagation();


            const isOpen =
                window
                .getComputedStyle(
                    statusPopup
                )
                .display
                !==
                "none";


            /*
             * Close classification popup first.
             */
            if(
                classPopup
            ){

                classPopup.style.display =
                    "none";

            }


            /*
             * Toggle status popup.
             */
            statusPopup.style.display =
                isOpen
                    ?
                    "none"
                    :
                    "grid";

        }
    );

}


/* =========================================================
   CLASSIFICATION DROPDOWN TOGGLE
========================================================= */

if(
    classFilterWrapper
    &&
    classPopup
){

    classFilterWrapper.addEventListener(
        "click",
        function(event){

            /*
             * Let option click handler work normally.
             */
            if(
                classPopup.contains(
                    event.target
                )
            ){
                return;
            }


            event.stopPropagation();


            const isOpen =
                window
                .getComputedStyle(
                    classPopup
                )
                .display
                !==
                "none";


            /*
             * Close status popup first.
             */
            if(
                statusPopup
            ){

                statusPopup.style.display =
                    "none";

            }


            /*
             * Toggle classification popup.
             */
            classPopup.style.display =
                isOpen
                    ?
                    "none"
                    :
                    "grid";

        }
    );

}


/* =========================================================
   CLOSE FILTER POPUPS WHEN CLICKING OUTSIDE
========================================================= */

document.addEventListener(
    "click",
    function(event){

        if(
            statusFilterWrapper
            &&
            statusPopup
            &&
            !statusFilterWrapper.contains(
                event.target
            )
        ){

            statusPopup.style.display =
                "none";

        }


        if(
            classFilterWrapper
            &&
            classPopup
            &&
            !classFilterWrapper.contains(
                event.target
            )
        ){

            classPopup.style.display =
                "none";

        }

    }
);


/* STATUS FILTER */

document
.querySelectorAll(
    ".status-item"
)
.forEach(
    function(
        item
    ){

        item.addEventListener(
            "click",
            function(){

                if(
                    !statusFilterInput
                    ||
                    !statusPopup
                ){
                    return;
                }


                statusFilterInput.value =
                    this.innerText.trim();


                const statusValue =
                    this.dataset.value
                    ||
                    "all";


                const hiddenStatus =
                    document.getElementById(
                        "statusFilter"
                    );


                if(hiddenStatus){

                    hiddenStatus.value =
                        statusValue;

                }


                statusPopup.style.display =
                    "none";


                openMemberList({
                    status:statusValue
                });

            }
        );

    }
);


/* CLASSIFICATION FILTER */

document
.querySelectorAll(
    ".classification-item"
)
.forEach(
    function(
        item
    ){

        item.addEventListener(
            "click",
            function(){

                if(
                    !classFilterInput
                    ||
                    !classPopup
                ){
                    return;
                }


                classFilterInput.value =
                    this.innerText.trim();


                const classificationValue =
                    this.dataset.value
                    ||
                    "all";


                const hiddenClassification =
                    document.getElementById(
                        "classificationFilter"
                    );


                if(hiddenClassification){

                    hiddenClassification.value =
                        classificationValue;

                }


                classPopup.style.display =
                    "none";


                openMemberList({
                    classification:classificationValue
                });

            }
        );

    }
);

/* =========================================================
   MEMBER GENDER DROPDOWN
========================================================= */

const memberGender =
    document.getElementById(
        "memberGender"
    );


const memberGenderBtn =
    document.getElementById(
        "memberGenderBtn"
    );


const memberGenderText =
    document.getElementById(
        "memberGenderText"
    );


const memberGenderMenu =
    document.getElementById(
        "memberGenderMenu"
    );


const memberGenderDropdown =
    document.querySelector(
        ".member-gender-dropdown"
    );


if(
    memberGenderBtn
    &&
    memberGenderMenu
){

    memberGenderBtn.addEventListener(
        "click",
        function(event){

            event.preventDefault();

            event.stopPropagation();


            /*
             * Close Status if open.
             */

            document
            .getElementById(
                "memberStatusMenu"
            )
            ?.classList
            .remove(
                "show"
            );


            memberCbuActionMenu
                ?.classList
                .remove(
                    "show"
                );


            memberCbuActionDropdown
                ?.classList
                .remove(
                    "open"
                );


            memberGenderMenu
                .classList
                .toggle(
                    "show"
                );


            memberGenderDropdown
                ?.classList
                .toggle(
                    "open",
                    memberGenderMenu
                        .classList
                        .contains(
                            "show"
                        )
                );

        }
    );

}


/* GENDER ITEMS */

document
.querySelectorAll(
    ".member-gender-item"
)
.forEach(
    function(item){

        item.addEventListener(
            "click",
            function(event){

                event.preventDefault();

                event.stopPropagation();


                const value =
                    this.dataset.value
                    ||
                    "";


                if(memberGender){

                    memberGender.value =
                        value;


                    validateAndClearMemberField(
                        memberGender
                    );

                }


                if(memberGenderText){

                    memberGenderText.textContent =
                        value.toUpperCase();

                }


                memberGenderMenu
                    ?.classList
                    .remove(
                        "show"
                    );


                memberGenderDropdown
                    ?.classList
                    .remove(
                        "open"
                    );

            }
        );

    }
);


/* CLOSE GENDER WHEN CLICKING OUTSIDE */

document.addEventListener(
    "click",
    function(){

        memberGenderMenu
            ?.classList
            .remove(
                "show"
            );


        memberGenderDropdown
            ?.classList
            .remove(
                "open"
            );

    }
);


/* =========================================================
   MEMBER STATUS DROPDOWN
========================================================= */

const memberStatusBtn =
    document.getElementById(
        "memberStatusBtn"
    );


const memberStatusMenu =
    document.getElementById(
        "memberStatusMenu"
    );


const memberStatusText =
    document.getElementById(
        "memberStatusText"
    );


const memberStatus =
    document.getElementById(
        "memberStatus"
    );


if(
    memberStatusBtn
    &&
    memberStatusMenu
){

    memberStatusBtn.addEventListener(
        "click",
        function(event){

            event.preventDefault();

            event.stopPropagation();


            /* Close Gender dropdown if open */

            memberGenderMenu
                ?.classList
                .remove(
                    "show"
                );


            memberGenderDropdown
                ?.classList
                .remove(
                    "open"
                );


            memberCbuActionMenu
                ?.classList
                .remove(
                    "show"
                );


            memberCbuActionDropdown
                ?.classList
                .remove(
                    "open"
                );


            memberStatusMenu
                .classList
                .toggle(
                    "show"
                );

        }
    );

}


/* STATUS ITEMS */

document
.querySelectorAll(
    "#memberStatusMenu .member-status-item"
)
.forEach(
    function(
        item
    ){

        item.addEventListener(
            "click",
            function(event){

                event.preventDefault();

                event.stopPropagation();


                if(memberStatusText){

                    memberStatusText.textContent =
                        this.textContent.trim();

                }


                if(memberStatus){

                    memberStatus.value =
                        this.dataset.value
                        ||
                        "";


                    validateAndClearMemberField(
                        memberStatus
                    );

                }


                memberStatusMenu
                    ?.classList
                    .remove(
                        "show"
                    );

            }
        );

    }
);


/* CLOSE STATUS MENU */

document.addEventListener(
    "click",
    function(){

        memberStatusMenu
            ?.classList
            .remove(
                "show"
            );

    }
);


/* =========================================================
   MEMBER CBU ACTION DROPDOWN
========================================================= */

if(
    memberCbuActionBtn
    &&
    memberCbuActionMenu
){

    memberCbuActionBtn.addEventListener(
        "click",
        function(event){

            event.preventDefault();
            event.stopPropagation();


            memberGenderMenu
                ?.classList
                .remove(
                    "show"
                );


            memberGenderDropdown
                ?.classList
                .remove(
                    "open"
                );


            memberStatusMenu
                ?.classList
                .remove(
                    "show"
                );


            memberCbuActionMenu
                .classList
                .toggle(
                    "show"
                );


            memberCbuActionDropdown
                ?.classList
                .toggle(
                    "open",
                    memberCbuActionMenu
                        .classList
                        .contains(
                            "show"
                        )
                );

        }
    );

}


document
.querySelectorAll(
    ".member-cbu-action-item"
)
.forEach(
    function(item){

        item.addEventListener(
            "click",
            function(event){

                event.preventDefault();
                event.stopPropagation();


                const value =
                    String(
                        this.dataset.value
                        ||
                        ""
                    )
                    .toUpperCase();


                if(memberCbuAction){

                    memberCbuAction.value =
                        value;

                }


                if(memberCbuActionText){

                    memberCbuActionText.textContent =
                        value === "ADD"
                        ?
                        "ADD CBU"
                        :
                        value === "DEDUCT"
                        ?
                        "DEDUCT CBU"
                        :
                        "Select Action";

                }


                memberCbuActionMenu
                    ?.classList
                    .remove(
                        "show"
                    );


                memberCbuActionDropdown
                    ?.classList
                    .remove(
                        "open"
                    );


                validateAndClearMemberField(
                    memberCbuAction
                );


                validateAndClearMemberField(
                    memberCbuAdjustmentAmount
                );


                refreshMemberCbuPreview();

            }
        );

    }
);


if(memberCbuAdjustmentAmount){

    memberCbuAdjustmentAmount.addEventListener(
        "input",
        function(){

            refreshMemberCbuPreview();


            validateAndClearMemberField(
                memberCbuAction
            );


            validateAndClearMemberField(
                memberCbuAdjustmentAmount
            );

        }
    );

}


document.addEventListener(
    "click",
    function(){

        memberCbuActionMenu
            ?.classList
            .remove(
                "show"
            );


        memberCbuActionDropdown
            ?.classList
            .remove(
                "open"
            );

    }
);


/* =========================================================
   INITIAL DATE DISPLAY
========================================================= */

syncMemberDateDisplay(
    membershipDateInput,
    membershipDateDisplay
);


syncMemberDateDisplay(
    birthDateInput,
    birthDateDisplay
);

/* =========================================================
   MEMBER LEDGER POSITION MEMORY

   Keeps the current ledger position after:
   - Page refresh
   - Member update
   - Member add
   - Member archive

   Normal navigation to the Members module
   will still open normally.
========================================================= */

(function(){

    const memberBody =
        document.getElementById(
            "memberBody"
        );


    if(!memberBody){

        return;

    }


    /* =====================================================
       CURRENT MEMBERS PAGE STATE
    ===================================================== */

    const currentUrl =
        new URL(
            window.location.href
        );


    const currentMemberPage =
        currentUrl.searchParams.get(
            "page"
        )
        ||
        "1";


    const currentMemberSearch =
        currentUrl.searchParams.get(
            "search"
        )
        ||
        "";


    /*
     * Use page + search in the key.
     *
     * This prevents Page 1 scroll position
     * from being mixed with Page 2 / Page 3.
     */

    const memberScrollKey =
        "spis_member_scroll:"
        +
        currentMemberPage
        +
        ":"
        +
        encodeURIComponent(
            currentMemberSearch
        );


    /* =====================================================
       DETECT PAGE REFRESH
    ===================================================== */

    const navigationEntry =
        performance
        .getEntriesByType(
            "navigation"
        )[0];


    const isPageReload =
        navigationEntry
        ?
        navigationEntry.type === "reload"
        :
        (
            performance.navigation
            &&
            performance.navigation.type === 1
        );


    /* =====================================================
       DETECT RETURN FROM MEMBER ACTION
    ===================================================== */

    const memberSuccessAction =
        String(
            currentUrl.searchParams.get(
                "success"
            )
            ||
            ""
        )
        .toLowerCase();


    const shouldRestoreAfterAction =
        [
            "updated",
            "added",
            "archived"
        ]
        .includes(
            memberSuccessAction
        );


    /* =====================================================
       RESTORE SAVED LEDGER POSITION
    ===================================================== */

    if(
        isPageReload
        ||
        shouldRestoreAfterAction
    ){

        const savedScroll =
            Number(
                sessionStorage.getItem(
                    memberScrollKey
                )
            );


        if(
            Number.isFinite(
                savedScroll
            )
            &&
            savedScroll >= 0
        ){

            /*
             * Wait until the member ledger
             * has completed its layout.
             */

            requestAnimationFrame(
                function(){

                    requestAnimationFrame(
                        function(){

                            const maximumScroll =
                                Math.max(
                                    0,
                                    memberBody.scrollHeight
                                    -
                                    memberBody.clientHeight
                                );


                            memberBody.scrollTop =
                                Math.min(
                                    savedScroll,
                                    maximumScroll
                                );

                        }
                    );

                }
            );

        }

    }


    /* =====================================================
       SAVE CURRENT LEDGER POSITION
    ===================================================== */

    function saveMemberLedgerPosition(){

        sessionStorage.setItem(
            memberScrollKey,
            String(
                memberBody.scrollTop
            )
        );

    }


    /*
     * Keep the latest position while
     * the user scrolls.
     */

    memberBody.addEventListener(
        "scroll",
        saveMemberLedgerPosition,
        {
            passive:true
        }
    );


    /*
     * Important for:
     * - Refresh
     * - Update submit
     * - Add submit
     * - Archive submit
     */

    window.addEventListener(
        "pagehide",
        saveMemberLedgerPosition
    );

})();