/* ======================================================
   ROTOR MODULE
====================================================== */

const rotorForm =
document.getElementById("rotorForm");

const pricePerHectareInput =
document.getElementById("pricePerHectare");

const rotorArea =
document.getElementById("rotorArea");

const rotorAffiliate =
document.getElementById("rotorAffiliate");

const rotorDiscount =
document.getElementById("rotorDiscount");

const rotorTotal =
document.getElementById("rotorTotal");

const rotorDownpayment =
document.getElementById("rotorDownpayment");

const rotorPriceWarning =
document.getElementById("rotorPriceWarning");

const saveRotorBtn =
document.getElementById("saveRotorBtn");

const rotorCustomerInput =
document.getElementById("rotorCustomerName");

const rotorMemberId =
document.getElementById("rotorMemberId");

const rotorMemberSuggestion =
document.getElementById("rotorMemberSuggestion");


/* ======================================================
   TOTAL / DISCOUNT
====================================================== */

function computeRotorTotal(){

    if(
        !pricePerHectareInput ||
        !rotorArea ||
        !rotorDiscount ||
        !rotorTotal
    ){

        return;

    }


    const pricePerHectare =
    parseFloat(
        pricePerHectareInput.value
    ) || 0;


    const hectare =
    parseFloat(
        rotorArea.value
    ) || 0;


    let discount =
    parseFloat(
        rotorDiscount.value
    ) || 0;


    if(pricePerHectare <= 0){

        rotorTotal.value = "";


        if(rotorPriceWarning){

            rotorPriceWarning
            .classList
            .remove("d-none");

        }


        return;

    }


    if(rotorPriceWarning){

        rotorPriceWarning
        .classList
        .add("d-none");

    }


    const subtotal =
    hectare * pricePerHectare;


    if(discount < 0){

        discount = 0;

    }


    if(discount > subtotal){

        setRotorInlineError(

            rotorDiscount,

            "Discount cannot exceed the computed rental amount."

        );


        discount =
        subtotal;

    }


    rotorTotal.value =
    Math.max(
        0,
        subtotal - discount
    ).toFixed(2);


    validateRotorDownpayment();

}

/* ======================================================
   DOWNPAYMENT VALIDATION
====================================================== */

function validateRotorDownpayment(){

    if(
        !rotorDownpayment
    ){

        return true;

    }


    clearRotorInlineError(
        rotorDownpayment
    );


    const value =
    rotorDownpayment.value.trim();


    if(
        value === ""
    ){

        return true;

    }


    if(
        !/^\d+(\.\d{0,2})?$/
        .test(
            value
        )
    ){

        setRotorInlineError(

            rotorDownpayment,

            "Please enter numbers only, up to 2 decimal places."

        );


        return false;

    }


    const downpayment =
    parseFloat(
        value
    ) || 0;


    const total =
    parseFloat(
        rotorTotal?.value
        ||
        "0"
    ) || 0;


    if(
        downpayment
        >
        total
    ){

        setRotorInlineError(

            rotorDownpayment,

            "Downpayment cannot exceed the Total Rental amount."

        );


        return false;

    }


    return true;

}


if(rotorDownpayment){

    rotorDownpayment
    .addEventListener(

        "focus",

        function(){

            if(
                this.value.trim() === "0.00"
            ){

                this.value =
                "";

            }


            this.placeholder =
            "";

        }

    );


    rotorDownpayment
    .addEventListener(

        "blur",

        function(){

            this.placeholder =
            "0.00";


            validateRotorDownpayment();

        }

    );


    rotorDownpayment
    .addEventListener(

        "input",

        function(){

            validateRotorDownpayment();

        }

    );

}


if(rotorArea){

    rotorArea.addEventListener(
        "input",
        computeRotorTotal
    );

}


if(rotorDiscount){

    rotorDiscount.addEventListener(

        "input",

        function(){

            clearRotorInlineError(
                rotorDiscount
            );


            const value =
            this.value.trim();


            if(
                value !== ""
                &&
                !/^\d+(\.\d{0,2})?$/
                .test(
                    value
                )
            ){

                setRotorInlineError(

                    rotorDiscount,

                    "Please enter numbers only, up to 2 decimal places."

                );


                return;

            }


            computeRotorTotal();

        }

    );

}


/* ======================================================
   AFFILIATE DROPDOWN
====================================================== */

const rotorAffiliateBtn =
document.getElementById(
    "rotorAffiliateBtn"
);


const rotorAffiliateMenu =
document.getElementById(
    "rotorAffiliateMenu"
);


const rotorAffiliateText =
document.getElementById(
    "rotorAffiliateText"
);


if(
    rotorAffiliateBtn &&
    rotorAffiliateMenu
){

    rotorAffiliateBtn.addEventListener(

        "click",

        function(e){

            e.preventDefault();

            e.stopPropagation();


            rotorAffiliateMenu
            .classList
            .toggle("show");

        }

    );

}


/* ======================================================
   AFFILIATE STATE
====================================================== */

function updateRotorAffiliateState(){

    if(
        !rotorAffiliate ||
        !rotorDiscount
    ){

        return;

    }


    const isMember =
    rotorAffiliate.value === "Member";


    rotorDiscount.disabled =
    !isMember;


    if(isMember){

        rotorDiscount.placeholder = "";


        rotorDiscount
        .classList
        .remove(
            "rotor-discount-disabled"
        );

    }

    else{

        rotorDiscount.value = "";


        rotorDiscount.placeholder =
        "Members only";


        rotorDiscount
        .classList
        .add(
            "rotor-discount-disabled"
        );

    }


    if(rotorCustomerInput){

        rotorCustomerInput.disabled =
        rotorAffiliate.value === "";


        if(isMember){

            rotorCustomerInput.placeholder =
            "Search member...";

        }

        else if(
            rotorAffiliate.value ===
            "Not Member"
        ){

            rotorCustomerInput.placeholder =
            "Enter customer name";

        }

        else{

            rotorCustomerInput.placeholder =
            "Select affiliate first";

        }

    }


    computeRotorTotal();

}


/* ======================================================
   SELECT AFFILIATE
====================================================== */

document
.querySelectorAll(
    ".rotor-affiliate-item"
)
.forEach(
function(item){


    item.addEventListener(

        "click",

        function(e){

            e.preventDefault();

            e.stopPropagation();


            const value =
            this.dataset.value;


            if(rotorAffiliateText){

                rotorAffiliateText.textContent =
                this.textContent.trim();

            }


            if(rotorAffiliate){

                rotorAffiliate.value =
                value;


                rotorAffiliate
                .dispatchEvent(
                    new Event("change")
                );

            }


            if(rotorAffiliateMenu){

                rotorAffiliateMenu
                .classList
                .remove("show");

            }


            if(rotorCustomerInput){

                rotorCustomerInput.value =
                "";


                rotorCustomerInput.disabled =
                false;

            }


            if(rotorMemberId){

                rotorMemberId.value =
                "";

            }


            if(rotorMemberSuggestion){

                rotorMemberSuggestion.innerHTML =
                "";


                rotorMemberSuggestion.style.display =
                "none";

            }


            updateRotorAffiliateState();

        }

    );

});


if(rotorAffiliate){

    rotorAffiliate.addEventListener(
        "change",
        updateRotorAffiliateState
    );

}


updateRotorAffiliateState();


/* ======================================================
   MEMBER NAME NORMALIZER
====================================================== */

function normalizeRotorMemberName(
    name
){

    return String(
        name || ""
    )
    .trim()
    .replace(
        /\s+/g,
        " "
    )
    .toLowerCase();

}


/* ======================================================
   FIND EXACT MEMBER
====================================================== */

async function findExactRotorMember(
    memberName
){

    const cleanName =
    String(
        memberName || ""
    )
    .trim()
    .replace(
        /\s+/g,
        " "
    );


    if(cleanName.length < 3){

        return null;

    }


    const response =
    await fetch(

        "search_member.php?keyword=" +
        encodeURIComponent(
            cleanName
        )

    );


    if(!response.ok){

        throw new Error(
            "Unable to search members."
        );

    }


    const html =
    await response.text();


    const container =
    document.createElement(
        "div"
    );


    container.innerHTML =
    html;


    const typedName =
    normalizeRotorMemberName(
        cleanName
    );


    let exactMember =
    null;


    container
    .querySelectorAll(
        ".member-item"
    )
    .forEach(
    function(item){


        const id =
        item.dataset.id;


        const name =
        item.dataset.name;


        if(
            id &&
            name &&
            normalizeRotorMemberName(
                name
            )
            ===
            typedName
        ){

            exactMember = {

                id:id,

                name:name

            };

        }

    });


    return exactMember;

}


/* ======================================================
   MEMBER LIVE SEARCH
====================================================== */

let rotorMemberSearchTimer =
null;


if(rotorCustomerInput){

    rotorCustomerInput
    .addEventListener(

        "input",

        function(){


            if(
                !rotorAffiliate ||
                !rotorMemberId ||
                !rotorMemberSuggestion
            ){

                return;

            }


            if(
                rotorAffiliate.value
                !==
                "Member"
            ){

                rotorMemberId.value =
                "";


                rotorMemberSuggestion.innerHTML =
                "";


                rotorMemberSuggestion.style.display =
                "none";


                return;

            }


            rotorMemberId.value =
            "";


            const keyword =
            rotorCustomerInput.value
            .trim()
            .replace(
                /\s+/g,
                " "
            );


            clearTimeout(
                rotorMemberSearchTimer
            );


            if(
                keyword.length < 3
            ){

                rotorMemberSuggestion.innerHTML =
                "";


                rotorMemberSuggestion.style.display =
                "none";


                return;

            }


            rotorMemberSearchTimer =
            setTimeout(

                function(){


                    fetch(

                        "search_member.php?keyword=" +
                        encodeURIComponent(
                            keyword
                        )

                    )

                    .then(
                        response =>
                        response.text()
                    )

                    .then(data=>{


                        const currentName =
                        rotorCustomerInput.value
                        .trim()
                        .replace(
                            /\s+/g,
                            " "
                        );


                        if(
                            currentName
                            !==
                            keyword
                        ){

                            return;

                        }


                        rotorMemberSuggestion.innerHTML =
                        data;


                        const typedName =
                        normalizeRotorMemberName(
                            rotorCustomerInput.value
                        );


                        let exactMember =
                        null;


                        rotorMemberSuggestion
                        .querySelectorAll(
                            ".member-item"
                        )
                        .forEach(
                        function(item){


                            const id =
                            item.dataset.id;


                            const name =
                            item.dataset.name;


                            if(
                                id &&
                                name &&
                                normalizeRotorMemberName(
                                    name
                                )
                                ===
                                typedName
                            ){

                                exactMember = {

                                    id:id,

                                    name:name

                                };

                            }

                        });


                        rotorMemberId.value =

                        exactMember
                        ?
                        exactMember.id
                        :
                        "";


                        rotorMemberSuggestion.style.display =
                        "block";

                    })

                    .catch(error=>{


                        console.error(
                            "ROTOR MEMBER SEARCH ERROR:",
                            error
                        );


                        rotorMemberId.value =
                        "";


                        rotorMemberSuggestion.innerHTML =
                        '<div class="rotor-member-search-message">Unable to search members.</div>';


                        rotorMemberSuggestion.style.display =
                        "block";

                    });

                },

                200

            );

        }

    );

}


/* ======================================================
   SELECT MEMBER
====================================================== */

document.addEventListener(

    "click",

    function(e){


        const selectedMember =
        e.target.closest(
            ".member-item"
        );


        if(
            selectedMember &&
            selectedMember.dataset.id &&
            selectedMember.dataset.name &&
            rotorCustomerInput
        ){

            rotorCustomerInput.value =
            selectedMember.dataset.name;


            if(rotorMemberId){

                rotorMemberId.value =
                selectedMember.dataset.id;

            }


            if(rotorMemberSuggestion){

                rotorMemberSuggestion.style.display =
                "none";

            }


            return;

        }


        if(rotorAffiliateMenu){

            rotorAffiliateMenu
            .classList
            .remove("show");

        }


        if(
            rotorMemberSuggestion &&
            rotorCustomerInput &&
            !rotorMemberSuggestion
            .contains(e.target)
            &&
            e.target
            !==
            rotorCustomerInput
        ){

            rotorMemberSuggestion.style.display =
            "none";

        }

    }

);


/* ======================================================
   ROTOR ADD RENTAL
   DATE + TRACTOR + DRIVER ASSIGNMENT FLOW
====================================================== */


/* ======================================================
   ELEMENTS
====================================================== */

const rotorDate =
document.getElementById(
    "rotorDate"
);


const rotorDateBtn =
document.getElementById(
    "rotorDateBtn"
);


const rotorDateText =
document.getElementById(
    "rotorDateText"
);


const rotorCalendar =
document.getElementById(
    "rotorCalendar"
);


const rotorCalendarMonth =
document.getElementById(
    "rotorCalendarMonth"
);


const rotorCalendarDays =
document.getElementById(
    "rotorCalendarDays"
);


const rotorCalendarPrev =
document.getElementById(
    "rotorCalendarPrev"
);


const rotorCalendarNext =
document.getElementById(
    "rotorCalendarNext"
);


/* PRIMARY TRACTOR */

const rotorTractorID =
document.getElementById(
    "rotorTractorID"
);


const rotorTractorNo =
document.getElementById(
    "rotorTractorNo"
);


const rotorTractorBtn =
document.getElementById(
    "rotorTractorBtn"
);


const rotorTractorText =
document.getElementById(
    "rotorTractorText"
);


const rotorTractorMenu =
document.getElementById(
    "rotorTractorMenu"
);


/* PRIMARY DRIVER */

const rotorPrimaryDriverID =
document.getElementById(
    "rotorPrimaryDriverID"
);


const rotorPrimaryDriverBtn =
document.getElementById(
    "rotorPrimaryDriverBtn"
);


const rotorPrimaryDriverText =
document.getElementById(
    "rotorPrimaryDriverText"
);


const rotorPrimaryDriverMenu =
document.getElementById(
    "rotorPrimaryDriverMenu"
);


/* SECONDARY */

const rotorSecondaryToggle =
document.getElementById(
    "rotorSecondaryToggle"
);


const rotorSecondaryAssignment =
document.getElementById(
    "rotorSecondaryAssignment"
);


const rotorSecondaryTractorID =
document.getElementById(
    "rotorSecondaryTractorID"
);


const rotorSecondaryTractorNo =
document.getElementById(
    "rotorSecondaryTractorNo"
);


const rotorSecondaryTractorBtn =
document.getElementById(
    "rotorSecondaryTractorBtn"
);


const rotorSecondaryTractorText =
document.getElementById(
    "rotorSecondaryTractorText"
);


const rotorSecondaryTractorMenu =
document.getElementById(
    "rotorSecondaryTractorMenu"
);


const rotorSecondaryDriverID =
document.getElementById(
    "rotorSecondaryDriverID"
);


const rotorSecondaryDriverBtn =
document.getElementById(
    "rotorSecondaryDriverBtn"
);


const rotorSecondaryDriverText =
document.getElementById(
    "rotorSecondaryDriverText"
);


const rotorSecondaryDriverMenu =
document.getElementById(
    "rotorSecondaryDriverMenu"
);


/* STATUS */

const rotorStatus =
document.getElementById(
    "rotorStatus"
);


const rotorStatusBtn =
document.getElementById(
    "rotorStatusBtn"
);


const rotorStatusText =
document.getElementById(
    "rotorStatusText"
);


const rotorStatusMenu =
document.getElementById(
    "rotorStatusMenu"
);


const rotorHistoricalStatusNote =
document.getElementById(
    "rotorHistoricalStatusNote"
);


const addRotorModalElement =
document.getElementById(
    "addRotorModal"
);


/* ======================================================
   STATE
====================================================== */

let rotorTractors =
[];


let rotorDrivers =
[];


let rotorAvailabilityRequest =
0;


let rotorCalendarAvailabilityRequest =
0;


let rotorCalendarAvailability =
{};


let rotorCalendarAvailabilityKey =
"";


let rotorSecondaryOpen =
false;


let rotorSaveBusy =
false;


const rotorInitialDate =
new Date();


let rotorCalendarView =
new Date(
    rotorInitialDate.getFullYear(),
    rotorInitialDate.getMonth(),
    1
);


/* ======================================================
   HELPERS
====================================================== */

function rotorEscapeHTML(
    value
){

    const element =
    document.createElement(
        "div"
    );


    element.textContent =
    String(
        value
        ||
        ""
    );


    return element.innerHTML;

}


function rotorPadNumber(
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


function rotorBuildDateKey(
    year,
    monthIndex,
    day
){

    return (

        year
        +
        "-"
        +
        rotorPadNumber(
            monthIndex + 1
        )
        +
        "-"
        +
        rotorPadNumber(
            day
        )

    );

}


function getRotorTodayKey(){

    const today =
    new Date();


    return rotorBuildDateKey(

        today.getFullYear(),

        today.getMonth(),

        today.getDate()

    );

}


function rotorFormatDate(
    dateKey
){

    const parts =
    String(
        dateKey
        ||
        ""
    )
    .split(
        "-"
    );


    if(
        parts.length !== 3
    ){

        return dateKey || "";

    }


    const date =
    new Date(

        Number(
            parts[0]
        ),

        Number(
            parts[1]
        ) - 1,

        Number(
            parts[2]
        )

    );


    return date.toLocaleDateString(

        "en-US",

        {

            month:
            "long",

            day:
            "numeric",

            year:
            "numeric"

        }

    );

}


function getRotorCalendarViewKey(){

    return (

        rotorCalendarView
        .getFullYear()

        +
        "-"

        +
        rotorPadNumber(
            rotorCalendarView
            .getMonth()
            +
            1
        )

    );

}


/* ======================================================
   CLOSE ASSIGNMENT MENUS
====================================================== */

function closeRotorAssignmentMenus(){

    rotorTractorMenu
    ?.classList
    .remove(
        "show"
    );


    rotorPrimaryDriverMenu
    ?.classList
    .remove(
        "show"
    );


    rotorSecondaryTractorMenu
    ?.classList
    .remove(
        "show"
    );


    rotorSecondaryDriverMenu
    ?.classList
    .remove(
        "show"
    );

}


/* ======================================================
   RESET STATUS
====================================================== */

function resetRotorAddStatus(){

    if(rotorStatus){

        rotorStatus.value =
        "";

    }


    if(rotorStatusText){

        rotorStatusText.textContent =
        "Select Date First";

    }


    if(rotorStatusBtn){

        rotorStatusBtn.disabled =
        true;

    }


    if(rotorHistoricalStatusNote){

        rotorHistoricalStatusNote.hidden =
        true;

    }


    rotorStatusMenu
    ?.classList
    .remove(
        "show"
    );


    rotorStatusMenu
    ?.querySelectorAll(
        ".rotor-status-item"
    )
    .forEach(
    function(item){

        item.style.display =
        "";

    });

}


/* ======================================================
   DATE STATUS RULES

   FUTURE
   = Pending automatically

   TODAY
   = Pending / Ongoing / Done

   PAST
   = Historical / Audit Entry
   = Done automatically
====================================================== */

function applyRotorStatusRules(){

    if(
        !rotorDate
        ||
        !rotorStatus
        ||
        !rotorStatusBtn
    ){

        return;

    }


    const selectedDate =
    rotorDate.value;


    if(
        selectedDate === ""
    ){

        resetRotorAddStatus();

        return;

    }


    const today =
    getRotorTodayKey();


    const isPast =
    selectedDate
    <
    today;


    const isFuture =
    selectedDate
    >
    today;


    const items =
    rotorStatusMenu
    ?.querySelectorAll(
        ".rotor-status-item"
    )
    ||
    [];


    /* ==============================================
       HISTORICAL / AUDIT
    ============================================== */

    if(isPast){

        rotorStatus.value =
        "Done";


        rotorStatusText.textContent =
        "Done";


        rotorStatusBtn.disabled =
        true;


        items.forEach(
        function(item){

            item.style.display =

            item.dataset.value ===
            "Done"

            ?

            ""

            :

            "none";

        });


        rotorStatusMenu
        ?.classList
        .remove(
            "show"
        );


        if(
            rotorHistoricalStatusNote
        ){

            rotorHistoricalStatusNote.hidden =
            false;

        }


        return;

    }


    if(
        rotorHistoricalStatusNote
    ){

        rotorHistoricalStatusNote.hidden =
        true;

    }


    /* ==============================================
       FUTURE
    ============================================== */

    if(isFuture){

        rotorStatus.value =
        "Pending";


        rotorStatusText.textContent =
        "Pending";


        rotorStatusBtn.disabled =
        true;


        items.forEach(
        function(item){

            item.style.display =

            item.dataset.value ===
            "Pending"

            ?

            ""

            :

            "none";

        });


        rotorStatusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==============================================
       TODAY
    ============================================== */

    rotorStatusBtn.disabled =
    false;


    items.forEach(
    function(item){

        item.style.display =
        "";

    });


    if(
        ![
            "Pending",
            "Ongoing",
            "Done"
        ]
        .includes(
            rotorStatus.value
        )
    ){

        rotorStatus.value =
        "Pending";


        rotorStatusText.textContent =
        "Pending";

    }

}


/* ======================================================
   RESET PRIMARY / SECONDARY ASSIGNMENTS
====================================================== */

function resetRotorPrimaryAssignment(){

    if(rotorTractorID){

        rotorTractorID.value =
        "";

    }


    if(rotorTractorNo){

        rotorTractorNo.value =
        "";

    }


    if(rotorTractorText){

        rotorTractorText.textContent =
        rotorDate?.value
        ?
        "Select Primary Tractor"
        :
        "Select Rental Date First";

    }


    if(rotorPrimaryDriverID){

        rotorPrimaryDriverID.value =
        "";

    }


    if(rotorPrimaryDriverText){

        rotorPrimaryDriverText.textContent =
        rotorDate?.value
        ?
        "Select Primary Driver"
        :
        "Select Rental Date First";

    }

}


function resetRotorSecondarySelections(){

    if(rotorSecondaryTractorID){

        rotorSecondaryTractorID.value =
        "";

    }


    if(rotorSecondaryTractorNo){

        rotorSecondaryTractorNo.value =
        "";

    }


    if(rotorSecondaryTractorText){

        rotorSecondaryTractorText.textContent =
        "Select Secondary Tractor";

    }


    if(rotorSecondaryDriverID){

        rotorSecondaryDriverID.value =
        "";

    }


    if(rotorSecondaryDriverText){

        rotorSecondaryDriverText.textContent =
        "Select Secondary Driver";

    }

}


function resetRotorAssignments(){

    resetRotorPrimaryAssignment();

    resetRotorSecondarySelections();

    closeRotorAssignmentMenus();

}


/* ======================================================
   SECONDARY SECTION
====================================================== */

function resetRotorSecondarySection(){

    rotorSecondaryOpen =
    false;


    resetRotorSecondarySelections();


    if(
        rotorSecondaryAssignment
    ){

        rotorSecondaryAssignment
        .classList
        .remove(
            "is-open"
        );

    }


    if(
        rotorSecondaryToggle
    ){

        rotorSecondaryToggle
        .classList
        .remove(
            "is-open"
        );


        const text =
        rotorSecondaryToggle
        .querySelector(
            "span:last-child"
        );


        if(text){

            text.textContent =
            "ADD SECONDARY ASSIGNMENT";

        }


        const icon =
        rotorSecondaryToggle
        .querySelector(
            "i"
        );


        if(icon){

            icon.className =
            "bi bi-plus-lg";

        }

    }

}


/* ======================================================
   DISABLE ASSIGNMENT BUTTONS
====================================================== */

function disableRotorAssignments(){

    [
        rotorTractorBtn,
        rotorPrimaryDriverBtn,
        rotorSecondaryTractorBtn,
        rotorSecondaryDriverBtn
    ]
    .forEach(
    function(button){

        if(button){

            button.disabled =
            true;

        }

    });

}


/* ======================================================
   ASSIGNMENT LOADING
====================================================== */

function setRotorAssignmentLoading(){

    resetRotorAssignments();


    disableRotorAssignments();


    if(rotorTractorText){

        rotorTractorText.textContent =
        "Loading Tractors...";

    }


    if(rotorPrimaryDriverText){

        rotorPrimaryDriverText.textContent =
        "Loading Drivers...";

    }

}


/* ======================================================
   TRACTOR AVAILABILITY
====================================================== */

async function getRotorTractorAvailability(
    rentalDate
){

    const url =

    "tractor/get_tractor_availability.php"
    +
    "?tractor_type=Rotor"
    +
    "&rental_date="
    +
    encodeURIComponent(
        rentalDate
    )
    +
    "&rental_type=rotor";


    const response =
    await fetch(
        url,
        {
            cache:
            "no-store"
        }
    );


    let data;


    try{

        data =
        await response.json();

    }
    catch(error){

        throw new Error(
            "Invalid Tractor availability response."
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
            "Unable to load Rotor tractor availability."

        );

    }


    return data;

}


/* ======================================================
   DRIVER AVAILABILITY
====================================================== */

async function getRotorDriverAvailability(
    rentalDate
){

    const url =

    "driver/get_driver_availability.php"
    +
    "?rental_date="
    +
    encodeURIComponent(
        rentalDate
    )
    +
    "&rental_type=rotor";


    const response =
    await fetch(
        url,
        {
            cache:
            "no-store"
        }
    );


    let data;


    try{

        data =
        await response.json();

    }
    catch(error){

        throw new Error(
            "Invalid Driver availability response."
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
            "Unable to load Driver availability."

        );

    }


    return data;

}


/* ======================================================
   TRACTOR STATE CLASS
====================================================== */

function getRotorTractorStateClass(
    availability
){

    switch(
        availability
    ){

        case "Available":

            return "rotor-tractor-available";


        case "Reserved":

            return "rotor-tractor-reserved";


        case "In Use":

            return "rotor-tractor-in-use";


        case "Maintenance":

            return "rotor-tractor-maintenance";


        default:

            return "";

    }

}


/* ======================================================
   DRIVER STATE CLASS
====================================================== */

function getRotorDriverStateClass(
    availability
){

    switch(
        availability
    ){

        case "Available":

            return "rotor-tractor-available";


        case "Reserved":

            return "rotor-tractor-reserved";


        case "Assigned":

            return "rotor-tractor-in-use";


        default:

            return "";

    }

}


/* ======================================================
   RENDER TRACTOR MENU

   IMPORTANT:
   No "None" option.

   Selected Primary Tractor disappears from Secondary.
   Selected Secondary Tractor disappears from Primary.
====================================================== */

function renderRotorTractorMenu(
    menu,
    tractors
){

    if(!menu){

        return;

    }


    if(
        !Array.isArray(
            tractors
        )
        ||
        tractors.length === 0
    ){

        menu.innerHTML =
        `
        <div class="rotor-tractor-empty">
            No Rotor tractors available.
        </div>
        `;


        return;

    }


    let html =
    "";


    tractors.forEach(
    function(tractor){


        const tractorID =
        parseInt(
            tractor.tractor_id,
            10
        )
        ||
        0;


        if(
            tractorID <= 0
        ){

            return;

        }


        const available =
        tractor.available
        ===
        true;


        const availability =
        String(
            tractor.availability
            ||
            "Unavailable"
        );


        const stateClass =
        getRotorTractorStateClass(
            availability
        );


        const tractorNo =
        rotorEscapeHTML(
            tractor.tractor_no
            ||
            ""
        );


        const tractorName =
        rotorEscapeHTML(
            tractor.tractor_name
            ||
            ""
        );


        html +=
        `
        <div
        class="
            rotor-tractor-item
            ${available ? "" : "rotor-tractor-disabled"}
        "
        data-resource-id="${tractorID}"
        data-tractor-no="${tractorNo}"
        data-tractor-name="${tractorName}"
        data-available="${available ? "1" : "0"}">

            <div class="rotor-tractor-details">

                <span class="rotor-tractor-number">
                    ${tractorNo}
                </span>

                <span class="rotor-tractor-name">
                    ${tractorName}
                </span>

            </div>

            <span
            class="
                rotor-tractor-status
                ${stateClass}
            ">
                ${rotorEscapeHTML(availability)}
            </span>

        </div>
        `;

    });


    menu.innerHTML =
    html
    ||
    `
    <div class="rotor-tractor-empty">
        No Rotor tractors available.
    </div>
    `;

}


/* ======================================================
   RENDER DRIVER MENU
====================================================== */

function renderRotorDriverMenu(
    menu,
    drivers
){

    if(!menu){

        return;

    }


    if(
        !Array.isArray(
            drivers
        )
        ||
        drivers.length === 0
    ){

        menu.innerHTML =
        `
        <div class="rotor-driver-empty">
            No Drivers available.
        </div>
        `;


        return;

    }


    let html =
    "";


    drivers.forEach(
    function(driver){


        const driverID =
        parseInt(
            driver.driver_id,
            10
        )
        ||
        0;


        if(
            driverID <= 0
        ){

            return;

        }


        const available =
        driver.available
        ===
        true;


        const availability =
        String(
            driver.availability
            ||
            "Unavailable"
        );


        const driverCode =
        rotorEscapeHTML(
            driver.driver_code
            ||
            ""
        );


        const driverName =
        rotorEscapeHTML(
            driver.full_name
            ||
            ""
        );


        const stateClass =
        getRotorDriverStateClass(
            availability
        );


        html +=
        `
        <div
        class="
            rotor-driver-item
            ${available ? "" : "rotor-driver-disabled"}
        "
        data-resource-id="${driverID}"
        data-driver-name="${driverName}"
        data-available="${available ? "1" : "0"}">

            <div class="rotor-tractor-details">

                <span class="rotor-tractor-number">
                    ${driverCode}
                </span>

                <span class="rotor-tractor-name">
                    ${driverName}
                </span>

            </div>

            <span
            class="
                rotor-tractor-status
                ${stateClass}
            ">
                ${rotorEscapeHTML(availability)}
            </span>

            </div>

        `;

    });


    menu.innerHTML =
    html
    ||
    `
    <div class="rotor-driver-empty">
        No Drivers available.
    </div>
    `;

}


/* ======================================================
   REFRESH PRIMARY / SECONDARY MENUS

   BIDIRECTIONAL EXCLUSION:

   Primary selected
   -> disappears from Secondary.

   Secondary selected
   -> disappears from Primary.
====================================================== */

function refreshRotorAssignmentMenus(){

    const primaryTractorID =
    String(
        rotorTractorID?.value
        ||
        ""
    );


    const secondaryTractorID =
    String(
        rotorSecondaryTractorID?.value
        ||
        ""
    );


    const primaryDriverID =
    String(
        rotorPrimaryDriverID?.value
        ||
        ""
    );


    const secondaryDriverID =
    String(
        rotorSecondaryDriverID?.value
        ||
        ""
    );


    const primaryTractors =
    rotorTractors.filter(
    function(tractor){

        return String(
            tractor.tractor_id
            ||
            ""
        )
        !==
        secondaryTractorID;

    });


    const secondaryTractors =
    rotorTractors.filter(
    function(tractor){

        return String(
            tractor.tractor_id
            ||
            ""
        )
        !==
        primaryTractorID;

    });


    const primaryDrivers =
    rotorDrivers.filter(
    function(driver){

        return String(
            driver.driver_id
            ||
            ""
        )
        !==
        secondaryDriverID;

    });


    const secondaryDrivers =
    rotorDrivers.filter(
    function(driver){

        return String(
            driver.driver_id
            ||
            ""
        )
        !==
        primaryDriverID;

    });


    renderRotorTractorMenu(
        rotorTractorMenu,
        primaryTractors
    );


    renderRotorTractorMenu(
        rotorSecondaryTractorMenu,
        secondaryTractors
    );


    renderRotorDriverMenu(
        rotorPrimaryDriverMenu,
        primaryDrivers
    );


    renderRotorDriverMenu(
        rotorSecondaryDriverMenu,
        secondaryDrivers
    );

}


/* ======================================================
   LOAD DATE ASSIGNMENT AVAILABILITY
====================================================== */

async function loadRotorAssignmentAvailability(
    rentalDate
){

    if(
        !rentalDate
    ){

        rotorTractors =
        [];


        rotorDrivers =
        [];


        resetRotorAssignments();


        disableRotorAssignments();


        return;

    }


    const requestID =
    ++rotorAvailabilityRequest;


    setRotorAssignmentLoading();


    try{


        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getRotorTractorAvailability(
                rentalDate
            ),

            getRotorDriverAvailability(
                rentalDate
            )

        ]);


        if(
            requestID
            !==
            rotorAvailabilityRequest
        ){

            return;

        }


        if(
            rotorDate?.value
            !==
            rentalDate
        ){

            return;

        }


        rotorTractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        rotorDrivers =

        Array.isArray(
            driverData.drivers
        )

        ?

        driverData.drivers

        :

        [];


        refreshRotorAssignmentMenus();


        if(rotorTractorBtn){

            rotorTractorBtn.disabled =
            false;


            rotorTractorText.textContent =
            "Select Primary Tractor";

        }


        if(rotorPrimaryDriverBtn){

            rotorPrimaryDriverBtn.disabled =
            false;


            rotorPrimaryDriverText.textContent =
            "Select Primary Driver";

        }


        /*
           Secondary buttons are only usable
           when Secondary Assignment is open.
        */

        if(rotorSecondaryTractorBtn){

            rotorSecondaryTractorBtn.disabled =
            !rotorSecondaryOpen;

        }


        if(rotorSecondaryDriverBtn){

            rotorSecondaryDriverBtn.disabled =
            !rotorSecondaryOpen;

        }


        const availableTractors =
        rotorTractors.filter(
            tractor =>
            tractor.available
            ===
            true
        );


        const availableDrivers =
        rotorDrivers.filter(
            driver =>
            driver.available
            ===
            true
        );


        if(
            availableTractors.length === 0
            ||
            availableDrivers.length === 0
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "No Available Assignment",

                text:
                "There is no available Rotor tractor and Driver pair for the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });

        }

    }
    catch(error){


        console.error(
            "ROTOR AVAILABILITY ERROR:",
            error
        );


        rotorTractors =
        [];


        rotorDrivers =
        [];


        resetRotorAssignments();


        disableRotorAssignments();


        Swal.fire({

            icon:
            "error",

            title:
            "Unable to Load Availability",

            text:
            error.message
            ||
            "Unable to load Rotor tractor and Driver availability.",

            confirmButtonColor:
            "#B71C1C"

        });

    }

}


/* ======================================================
   SELECT PRIMARY TRACTOR
====================================================== */

rotorTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-tractor-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available
            !==
            "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Tractor Unavailable",

                text:
                "This tractor is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const tractorID =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(
            tractorID <= 0
        ){

            return;

        }


        if(
            String(
                rotorSecondaryTractorID?.value
                ||
                ""
            )
            ===
            String(
                tractorID
            )
        ){

            return;

        }


        rotorTractorID.value =
        String(
            tractorID
        );


        rotorTractorNo.value =
        item.dataset.tractorNo
        ||
        "";


        rotorTractorText.textContent =

        (
            item.dataset.tractorNo
            ||
            ""
        )

        +

        " - "

        +

        (
            item.dataset.tractorName
            ||
            ""
        );


        rotorTractorMenu
        .classList
        .remove(
            "show"
        );


        refreshRotorAssignmentMenus();

    }
);


/* ======================================================
   SELECT SECONDARY TRACTOR
====================================================== */

rotorSecondaryTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-tractor-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available
            !==
            "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Tractor Unavailable",

                text:
                "This tractor is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const tractorID =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(
            tractorID <= 0
        ){

            return;

        }


        if(
            String(
                rotorTractorID?.value
                ||
                ""
            )
            ===
            String(
                tractorID
            )
        ){

            return;

        }


        rotorSecondaryTractorID.value =
        String(
            tractorID
        );


        rotorSecondaryTractorNo.value =
        item.dataset.tractorNo
        ||
        "";


        rotorSecondaryTractorText.textContent =

        (
            item.dataset.tractorNo
            ||
            ""
        )

        +

        " - "

        +

        (
            item.dataset.tractorName
            ||
            ""
        );


        rotorSecondaryTractorMenu
        .classList
        .remove(
            "show"
        );


        refreshRotorAssignmentMenus();

    }
);


/* ======================================================
   SELECT PRIMARY DRIVER
====================================================== */

rotorPrimaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-driver-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available
            !==
            "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Driver Unavailable",

                text:
                "This driver is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const driverID =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(
            driverID <= 0
        ){

            return;

        }


        if(
            String(
                rotorSecondaryDriverID?.value
                ||
                ""
            )
            ===
            String(
                driverID
            )
        ){

            return;

        }


        rotorPrimaryDriverID.value =
        String(
            driverID
        );


        rotorPrimaryDriverText.textContent =
        item.dataset.driverName
        ||
        "";


        rotorPrimaryDriverMenu
        .classList
        .remove(
            "show"
        );


        refreshRotorAssignmentMenus();

    }
);


/* ======================================================
   SELECT SECONDARY DRIVER
====================================================== */

rotorSecondaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-driver-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available
            !==
            "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Driver Unavailable",

                text:
                "This driver is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const driverID =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(
            driverID <= 0
        ){

            return;

        }


        if(
            String(
                rotorPrimaryDriverID?.value
                ||
                ""
            )
            ===
            String(
                driverID
            )
        ){

            return;

        }


        rotorSecondaryDriverID.value =
        String(
            driverID
        );


        rotorSecondaryDriverText.textContent =
        item.dataset.driverName
        ||
        "";


        rotorSecondaryDriverMenu
        .classList
        .remove(
            "show"
        );


        refreshRotorAssignmentMenus();

    }
);


/* ======================================================
   OPEN ASSIGNMENT MENUS
====================================================== */

function setupRotorAssignmentButton(
    button,
    menu
){

    if(
        !button
        ||
        !menu
    ){

        return;

    }


    button.addEventListener(
        "click",
        function(event){


            event.preventDefault();
            event.stopPropagation();


            if(
                button.disabled
                ||
                !rotorDate?.value
            ){

                return;

            }


            const willOpen =

            !menu
            .classList
            .contains(
                "show"
            );


            closeRotorAssignmentMenus();


            rotorCalendar
            ?.classList
            .remove(
                "show"
            );


            rotorStatusMenu
            ?.classList
            .remove(
                "show"
            );


            rotorAffiliateMenu
            ?.classList
            .remove(
                "show"
            );


            menu
            .classList
            .toggle(
                "show",
                willOpen
            );

        }
    );


    menu.addEventListener(
        "click",
        function(event){

            event.stopPropagation();

        }
    );

}


setupRotorAssignmentButton(
    rotorTractorBtn,
    rotorTractorMenu
);


setupRotorAssignmentButton(
    rotorPrimaryDriverBtn,
    rotorPrimaryDriverMenu
);


setupRotorAssignmentButton(
    rotorSecondaryTractorBtn,
    rotorSecondaryTractorMenu
);


setupRotorAssignmentButton(
    rotorSecondaryDriverBtn,
    rotorSecondaryDriverMenu
);


/* ======================================================
   SECONDARY ASSIGNMENT TOGGLE
====================================================== */

rotorSecondaryToggle
?.addEventListener(
    "click",
    function(event){


        event.preventDefault();
        event.stopPropagation();


        rotorSecondaryOpen =
        !rotorSecondaryOpen;


        if(
            rotorSecondaryOpen
        ){

            rotorSecondaryAssignment
            ?.classList
            .add(
                "is-open"
            );


            rotorSecondaryToggle
            .classList
            .add(
                "is-open"
            );


            const text =
            rotorSecondaryToggle
            .querySelector(
                "span:last-child"
            );


            if(text){

                text.textContent =
                "REMOVE SECONDARY ASSIGNMENT";

            }


            const icon =
            rotorSecondaryToggle
            .querySelector(
                "i"
            );


            if(icon){

                icon.className =
                "bi bi-dash-lg";

            }


            const dateSelected =
            Boolean(
                rotorDate?.value
            );


            if(
                rotorSecondaryTractorBtn
            ){

                rotorSecondaryTractorBtn.disabled =
                !dateSelected;

            }


            if(
                rotorSecondaryDriverBtn
            ){

                rotorSecondaryDriverBtn.disabled =
                !dateSelected;

            }


            refreshRotorAssignmentMenus();

        }

        else{

            resetRotorSecondarySection();


            if(
                rotorSecondaryTractorBtn
            ){

                rotorSecondaryTractorBtn.disabled =
                true;

            }


            if(
                rotorSecondaryDriverBtn
            ){

                rotorSecondaryDriverBtn.disabled =
                true;

            }


            refreshRotorAssignmentMenus();

        }

    }
);


/* ======================================================
   CALENDAR AVAILABILITY
====================================================== */

async function loadRotorCalendarAvailability(){

    const year =
    rotorCalendarView
    .getFullYear();


    const month =
    rotorCalendarView
    .getMonth()
    +
    1;


    const requestedKey =

    year
    +
    "-"
    +
    rotorPadNumber(
        month
    );


    const requestID =
    ++rotorCalendarAvailabilityRequest;


    try{


        const response =
        await fetch(

            "get_rotor_booked_dates.php"
            +
            "?year="
            +
            encodeURIComponent(
                year
            )
            +
            "&month="
            +
            encodeURIComponent(
                month
            )
            +
            "&_="
            +
            Date.now(),

            {
                cache:
                "no-store"
            }

        );


        let result;


        try{

            result =
            await response.json();

        }
        catch(error){

            throw new Error(
                "Invalid Rotor calendar response."
            );

        }


        if(
            !response.ok
            ||
            !result.success
        ){

            throw new Error(

                result.message
                ||
                "Unable to load Rotor calendar availability."

            );

        }


        if(
            requestID
            !==
            rotorCalendarAvailabilityRequest
        ){

            return;

        }


        if(
            requestedKey
            !==
            getRotorCalendarViewKey()
        ){

            return;

        }


        rotorCalendarAvailability =

        result.data
        &&
        result.data.dates
        &&
        typeof result.data.dates
        ===
        "object"

        ?

        result.data.dates

        :

        {};


        rotorCalendarAvailabilityKey =
        requestedKey;


        renderRotorCalendar();

    }
    catch(error){


        console.error(
            "ROTOR CALENDAR ERROR:",
            error
        );


        if(
            requestID
            !==
            rotorCalendarAvailabilityRequest
        ){

            return;

        }


        rotorCalendarAvailability =
        {};


        rotorCalendarAvailabilityKey =
        requestedKey;


        renderRotorCalendar();

    }

}


/* ======================================================
   RENDER CALENDAR
====================================================== */

function renderRotorCalendar(){

    if(
        !rotorCalendarDays
        ||
        !rotorCalendarMonth
    ){

        return;

    }


    const year =
    rotorCalendarView
    .getFullYear();


    const month =
    rotorCalendarView
    .getMonth();


    rotorCalendarMonth.textContent =
    rotorCalendarView
    .toLocaleDateString(

        "en-US",

        {
            month:
            "long",

            year:
            "numeric"
        }

    );


    rotorCalendarDays.innerHTML =
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
            "div"
        );


        empty.className =
        "rotor-calendar-empty";


        rotorCalendarDays
        .appendChild(
            empty
        );

    }


    const today =
    getRotorTodayKey();


    const availabilityReady =

    rotorCalendarAvailabilityKey
    ===
    getRotorCalendarViewKey();


    for(
        let day = 1;
        day <= daysInMonth;
        day++
    ){

        const dateKey =
        rotorBuildDateKey(
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
        "rotor-calendar-day";


        dayButton.textContent =
        String(
            day
        );


        dayButton.dataset.date =
        dateKey;


        const isPast =
        dateKey
        <
        today;


        const dateAvailability =

        availabilityReady

        ?

        (
            rotorCalendarAvailability[
                dateKey
            ]
            ||
            null
        )

        :

        null;


        /* HISTORICAL */

        if(isPast){

            dayButton
            .classList
            .add(
                "rotor-calendar-historical"
            );

        }


        /* FULLY BOOKED */

        if(
            dateAvailability?.status
            ===
            "Fully Booked"
        ){

            dayButton.disabled =
            true;


            dayButton
            .classList
            .add(
                "rotor-calendar-booked"
            );


            dayButton.title =
            isPast

            ?

            "This historical date has no available Rotor tractor and Driver pair."

            :

            "Fully booked. No Rotor tractor and Driver pair is available.";

        }

        /* LIMITED */

        else if(
            dateAvailability?.status
            ===
            "Limited"
        ){

            dayButton
            .classList
            .add(
                "rotor-calendar-limited"
            );


            dayButton.title =
            isPast

            ?

            "Historical date with limited assignment availability."

            :

            "Limited availability.";

        }

        /* AVAILABLE */

        else if(
            dateAvailability?.status
            ===
            "Available"
        ){

            dayButton
            .classList
            .add(
                "rotor-calendar-available"
            );


            dayButton.title =
            isPast

            ?

            "Past date. Selectable for a Historical / Audit Entry."

            :

            "Available";

        }


        if(
            dateKey === today
        ){

            dayButton
            .classList
            .add(
                "rotor-calendar-today"
            );

        }


        if(
            rotorDate?.value
            ===
            dateKey
        ){

            dayButton
            .classList
            .add(
                "rotor-calendar-selected"
            );

        }


        dayButton
        .addEventListener(
            "click",
            async function(event){


                event.preventDefault();
                event.stopPropagation();


                if(
                    this.disabled
                ){

                    return;

                }


                const selectedDate =
                this.dataset.date;


                if(
                    !selectedDate
                ){

                    return;

                }


                rotorDate.value =
                selectedDate;


                rotorDateText.textContent =
                rotorFormatDate(
                    selectedDate
                );


                rotorDateBtn
                ?.classList
                .add(
                    "has-date"
                );


                /*
                   New date means old assignments
                   are no longer trustworthy.
                */

                resetRotorAssignments();


                applyRotorStatusRules();


                clearRotorInlineError(
                    rotorDateBtn
                );


                if(
                    rotorStatus
                    &&
                    [
                        "Pending",
                        "Ongoing",
                        "Done"
                    ]
                    .includes(
                        rotorStatus.value
                    )
                ){

                    clearRotorInlineError(
                        rotorStatusBtn
                    );

                }


                rotorCalendar
                ?.classList
                .remove(
                    "show"
                );


                renderRotorCalendar();


                await loadRotorAssignmentAvailability(
                    selectedDate
                );

            }
        );


        rotorCalendarDays
        .appendChild(
            dayButton
        );

    }

}


/* ======================================================
   CALENDAR POSITION
====================================================== */

function positionRotorCalendar(){

    if(
        !rotorCalendar
        ||
        !rotorDateBtn
        ||
        !rotorCalendar
        .classList
        .contains(
            "show"
        )
    ){

        return;

    }


    const buttonRect =
    rotorDateBtn
    .getBoundingClientRect();


    const calendarWidth =
    rotorCalendar.offsetWidth
    ||
    360;


    const calendarHeight =
    rotorCalendar.offsetHeight
    ||
    390;


    const margin =
    8;


    const edge =
    12;


    let left =
    buttonRect.left;


    let top =
    buttonRect.bottom
    +
    margin;


    if(
        top
        +
        calendarHeight
        >
        window.innerHeight
        -
        edge
    ){

        top =
        buttonRect.top
        -
        calendarHeight
        -
        margin;

    }


    if(
        left
        +
        calendarWidth
        >
        window.innerWidth
        -
        edge
    ){

        left =
        window.innerWidth
        -
        calendarWidth
        -
        edge;

    }


    left =
    Math.max(
        edge,
        left
    );


    top =
    Math.max(
        edge,
        top
    );


    rotorCalendar.style.left =
    left
    +
    "px";


    rotorCalendar.style.top =
    top
    +
    "px";

}


/* ======================================================
   DATE BUTTON
====================================================== */

rotorDateBtn
?.addEventListener(
    "click",
    async function(event){


        event.preventDefault();
        event.stopPropagation();


        closeRotorAssignmentMenus();


        rotorStatusMenu
        ?.classList
        .remove(
            "show"
        );


        rotorAffiliateMenu
        ?.classList
        .remove(
            "show"
        );


        if(
            rotorDate?.value
        ){

            const parts =
            rotorDate.value
            .split(
                "-"
            );


            rotorCalendarView =
            new Date(

                Number(
                    parts[0]
                ),

                Number(
                    parts[1]
                ) - 1,

                1

            );

        }


        await loadRotorCalendarAvailability();


        rotorCalendar
        ?.classList
        .toggle(
            "show"
        );


        if(
            rotorCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            requestAnimationFrame(
                positionRotorCalendar
            );

        }

    }
);


/* ======================================================
   CALENDAR PREVIOUS
====================================================== */

rotorCalendarPrev
?.addEventListener(
    "click",
    async function(event){


        event.preventDefault();
        event.stopPropagation();


        rotorCalendarView =
        new Date(

            rotorCalendarView
            .getFullYear(),

            rotorCalendarView
            .getMonth() - 1,

            1

        );


        await loadRotorCalendarAvailability();


        requestAnimationFrame(
            positionRotorCalendar
        );

    }
);


/* ======================================================
   CALENDAR NEXT
====================================================== */

rotorCalendarNext
?.addEventListener(
    "click",
    async function(event){


        event.preventDefault();
        event.stopPropagation();


        rotorCalendarView =
        new Date(

            rotorCalendarView
            .getFullYear(),

            rotorCalendarView
            .getMonth() + 1,

            1

        );


        await loadRotorCalendarAvailability();


        requestAnimationFrame(
            positionRotorCalendar
        );

    }
);


/* ======================================================
   STATUS DROPDOWN
====================================================== */

rotorStatusBtn
?.addEventListener(
    "click",
    function(event){


        event.preventDefault();
        event.stopPropagation();


        if(
            rotorStatusBtn.disabled
        ){

            return;

        }


        closeRotorAssignmentMenus();


        rotorCalendar
        ?.classList
        .remove(
            "show"
        );


        rotorAffiliateMenu
        ?.classList
        .remove(
            "show"
        );


        rotorStatusMenu
        ?.classList
        .toggle(
            "show"
        );

    }
);


rotorStatusMenu
?.querySelectorAll(
    ".rotor-status-item"
)
.forEach(
function(item){


    item.addEventListener(
        "click",
        function(event){


            event.preventDefault();
            event.stopPropagation();


            const selectedDate =
            rotorDate?.value
            ||
            "";


            if(
                selectedDate === ""
            ){

                return;

            }


            const today =
            getRotorTodayKey();


            const value =
            this.dataset.value;


            if(
                selectedDate < today
            ){

                return;

            }


            if(
                selectedDate > today
                &&
                value !== "Pending"
            ){

                return;

            }


            if(
                ![
                    "Pending",
                    "Ongoing",
                    "Done"
                ]
                .includes(
                    value
                )
            ){

                return;

            }


            rotorStatus.value =
            value;


            rotorStatusText.textContent =
            value;


            rotorStatusMenu
            ?.classList
            .remove(
                "show"
            );

        }
    );

});


/* ======================================================
   FINAL AVAILABILITY RECHECK
====================================================== */

async function validateRotorAssignmentsBeforeSave(){

    const selectedDate =
    rotorDate?.value
    ||
    "";


    if(
        selectedDate === ""
    ){

        setRotorInlineError(

            rotorDateBtn,

            "Please select the Rental Date."

        );


        focusRotorInvalidField(
            rotorDateBtn
        );


        return false;

    }


    function availabilityError(
        control,
        message
    ){

        const error =
        new Error(
            message
        );


        error.rotorControl =
        control;


        return error;

    }


    try{


        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getRotorTractorAvailability(
                selectedDate
            ),

            getRotorDriverAvailability(
                selectedDate
            )

        ]);


        const latestTractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        const latestDrivers =

        Array.isArray(
            driverData.drivers
        )

        ?

        driverData.drivers

        :

        [];


        const primaryTractorID =
        parseInt(
            rotorTractorID?.value
            ||
            "0",
            10
        );


        const primaryDriverID =
        parseInt(
            rotorPrimaryDriverID?.value
            ||
            "0",
            10
        );


        const secondaryTractorID =
        parseInt(
            rotorSecondaryTractorID?.value
            ||
            "0",
            10
        );


        const secondaryDriverID =
        parseInt(
            rotorSecondaryDriverID?.value
            ||
            "0",
            10
        );


        const primaryTractor =
        latestTractors.find(
            item =>
            Number(
                item.tractor_id
            )
            ===
            primaryTractorID
        );


        const primaryDriver =
        latestDrivers.find(
            item =>
            Number(
                item.driver_id
            )
            ===
            primaryDriverID
        );


        if(
            !primaryTractor
            ||
            primaryTractor.available
            !==
            true
        ){

            throw availabilityError(

                rotorTractorBtn,

                "Primary Tractor is no longer available on this date."

            );

        }


        if(
            !primaryDriver
            ||
            primaryDriver.available
            !==
            true
        ){

            throw availabilityError(

                rotorPrimaryDriverBtn,

                "Primary Driver is no longer available on this date."

            );

        }


        if(
            secondaryTractorID > 0
        ){

            const secondaryTractor =
            latestTractors.find(
                item =>
                Number(
                    item.tractor_id
                )
                ===
                secondaryTractorID
            );


            if(
                !secondaryTractor
                ||
                secondaryTractor.available
                !==
                true
            ){

                throw availabilityError(

                    rotorSecondaryTractorBtn,

                    "Secondary Tractor is no longer available on this date."

                );

            }

        }


        if(
            secondaryDriverID > 0
        ){

            const secondaryDriver =
            latestDrivers.find(
                item =>
                Number(
                    item.driver_id
                )
                ===
                secondaryDriverID
            );


            if(
                !secondaryDriver
                ||
                secondaryDriver.available
                !==
                true
            ){

                throw availabilityError(

                    rotorSecondaryDriverBtn,

                    "Secondary Driver is no longer available on this date."

                );

            }

        }


        return true;

    }
    catch(error){


        console.error(
            "ROTOR FINAL AVAILABILITY ERROR:",
            error
        );


        await loadRotorAssignmentAvailability(
            selectedDate
        );


        const control =
        error.rotorControl
        ||
        rotorDateBtn;


        setRotorInlineError(

            control,

            error.message
            ||
            "The selected assignment is no longer available."

        );


        focusRotorInvalidField(
            control
        );


        return false;

    }

}


/* ======================================================
   CLOSE CUSTOM MENUS
====================================================== */

document.addEventListener(
    "click",
    function(event){


        if(
            rotorCalendar
            &&
            !rotorCalendar.contains(
                event.target
            )
            &&
            event.target
            !==
            rotorDateBtn
            &&
            !rotorDateBtn
            ?.contains(
                event.target
            )
        ){

            rotorCalendar
            .classList
            .remove(
                "show"
            );

        }


        closeRotorAssignmentMenus();


        rotorStatusMenu
        ?.classList
        .remove(
            "show"
        );

    }
);

/* ======================================================
   RESIZE CALENDAR
====================================================== */

window.addEventListener(
    "resize",
    function(){

        if(
            rotorCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            positionRotorCalendar();

        }

    }
);


/* ======================================================
   INITIAL STATE
====================================================== */

resetRotorAssignments();

resetRotorSecondarySection();

disableRotorAssignments();

resetRotorAddStatus();

renderRotorCalendar();


/* ======================================================
   RESET MODAL AFTER CLOSE
====================================================== */

addRotorModalElement
?.addEventListener(
    "hidden.bs.modal",
    function(){


        rotorAvailabilityRequest++;


        rotorCalendarAvailabilityRequest++;


        rotorForm
        ?.reset();


        rotorTractors =
        [];


        rotorDrivers =
        [];


        rotorCalendarAvailability =
        {};


        rotorCalendarAvailabilityKey =
        "";


        if(rotorAffiliate){

            rotorAffiliate.value =
            "";

        }


        if(rotorAffiliateText){

            rotorAffiliateText.textContent =
            "Select Affiliate";

        }


        if(rotorCustomerInput){

            rotorCustomerInput.value =
            "";


            rotorCustomerInput.disabled =
            true;


            rotorCustomerInput.placeholder =
            "Select affiliate first";

        }


        if(rotorMemberId){

            rotorMemberId.value =
            "";

        }


        if(rotorMemberSuggestion){

            rotorMemberSuggestion.innerHTML =
            "";


            rotorMemberSuggestion.style.display =
            "none";

        }


        if(rotorDate){

            rotorDate.value =
            "";

        }


        if(rotorDateText){

            rotorDateText.textContent =
            "Select Rental Date";

        }


        rotorDateBtn
        ?.classList
        .remove(
            "has-date"
        );


        resetRotorAssignments();


        resetRotorSecondarySection();


        disableRotorAssignments();


        resetRotorAddStatus();


        if(rotorDiscount){

            rotorDiscount.value =
            "";

        }


        if(rotorDownpayment){

            rotorDownpayment.value =
            "";


            rotorDownpayment.placeholder =
            "0.00";


            clearRotorInlineError(
                rotorDownpayment
            );

        }


        updateRotorAffiliateState();


        if(rotorTotal){

            rotorTotal.value =
            "0.00";

        }


        rotorCalendarView =
        new Date();


        rotorCalendarView
        .setDate(
            1
        );


        rotorCalendar
        ?.classList
        .remove(
            "show"
        );


        closeRotorAssignmentMenus();


        renderRotorCalendar();

    }
);

/* ======================================================
   ROTOR INLINE VALIDATION
====================================================== */

function getRotorErrorContainer(
    control
){

    if(
        !control
    ){

        return null;

    }


    return (

        control.closest(
            '[class*="col-"]'
        )

        ||

        control.parentElement

    );

}


/* ======================================================
   CLEAR ONE ERROR
====================================================== */

function clearRotorInlineError(
    control
){

    if(
        !control
    ){

        return;

    }


    control
    .classList
    .remove(
        "is-invalid"
    );


    control
    .removeAttribute(
        "aria-invalid"
    );


    const container =
    getRotorErrorContainer(
        control
    );


    if(
        !container
    ){

        return;

    }


    const controlID =
    control.id
    ||
    "";


    container
    .querySelectorAll(
        ".rotor-field-error"
    )
    .forEach(
    function(error){

        if(
            !controlID
            ||
            error.dataset.for
            ===
            controlID
        ){

            error.remove();

        }

    });

}


/* ======================================================
   SET ONE ERROR
====================================================== */

function setRotorInlineError(
    control,
    message
){

    if(
        !control
    ){

        return;

    }


    clearRotorInlineError(
        control
    );


    control
    .classList
    .add(
        "is-invalid"
    );


    control
    .setAttribute(
        "aria-invalid",
        "true"
    );


    const container =
    getRotorErrorContainer(
        control
    );


    if(
        !container
    ){

        return;

    }


    const error =
    document.createElement(
        "small"
    );


    error.className =
    "rotor-field-error";


    error.dataset.for =
    control.id
    ||
    "";


    error.textContent =
    message;


    container
    .appendChild(
        error
    );

}


/* ======================================================
   CLEAR ALL ERRORS
====================================================== */

function clearRotorInlineErrors(){

    document
    .querySelectorAll(
        "#addRotorModal .is-invalid"
    )
    .forEach(
    function(control){

        control
        .classList
        .remove(
            "is-invalid"
        );


        control
        .removeAttribute(
            "aria-invalid"
        );

    });


    document
    .querySelectorAll(
        "#addRotorModal .rotor-field-error"
    )
    .forEach(
    function(error){

        error.remove();

    });

}


/* ======================================================
   FOCUS FIRST INVALID FIELD
====================================================== */

function focusRotorInvalidField(
    control
){

    if(
        !control
    ){

        return;

    }


    control
    .scrollIntoView({

        behavior:
        "smooth",

        block:
        "center"

    });


    window.setTimeout(

        function(){

            try{

                control.focus();

            }
            catch(error){

                /* no action */

            }

        },

        220

    );

}


/* ======================================================
   VALIDATE ADD ROTOR FORM
====================================================== */

async function validateRotorAddForm(
    customer,
    location
){

    let valid =
    true;


    let firstInvalid =
    null;


    function markInvalid(
        control,
        message
    ){

        valid =
        false;


        setRotorInlineError(
            control,
            message
        );


        if(
            !firstInvalid
        ){

            firstInvalid =
            control;

        }

    }


    /* ==========================================
       PRICE
    ========================================== */

    if(
        !pricePerHectareInput
        ||
        parseFloat(
            pricePerHectareInput.value
        ) <= 0
    ){

        valid =
        false;


        if(
            rotorPriceWarning
        ){

            rotorPriceWarning
            .classList
            .remove(
                "d-none"
            );

        }

    }


    /* ==========================================
       AFFILIATE
    ========================================== */

    const affiliateValid =

    rotorAffiliate

    &&

    [
        "Member",
        "Not Member"
    ]
    .includes(
        rotorAffiliate.value
    );


    if(
        !affiliateValid
    ){

        markInvalid(

            rotorAffiliateBtn,

            "Please select the customer affiliate."

        );

    }


    /* ==========================================
       CUSTOMER

       Only validate once Affiliate is selected,
       because Customer is disabled before that.
    ========================================== */

    if(
        affiliateValid
    ){

        if(
            !customer
            ||
            customer.value
            .trim()
            ===
            ""
        ){

            markInvalid(

                customer,

                rotorAffiliate.value
                ===
                "Member"

                ?

                "Please search and select a registered member."

                :

                "Please enter the customer name."

            );

        }

        else if(
            rotorAffiliate.value
            ===
            "Member"
        ){

            try{


                const exactMember =
                await findExactRotorMember(
                    customer.value
                );


                if(
                    !exactMember
                ){

                    if(
                        rotorMemberId
                    ){

                        rotorMemberId.value =
                        "";

                    }


                    markInvalid(

                        customer,

                        "Please select a registered member from the suggestions."

                    );

                }

                else{


                    if(
                        rotorMemberId
                    ){

                        rotorMemberId.value =
                        exactMember.id;

                    }


                    customer.value =
                    exactMember.name;

                }

            }
            catch(error){


                console.error(
                    "ROTOR MEMBER VALIDATION ERROR:",
                    error
                );


                markInvalid(

                    customer,

                    "Unable to verify this member. Please try again."

                );

            }

        }

        else if(
            rotorMemberId
        ){

            rotorMemberId.value =
            "";

        }

    }


    /* ==========================================
       LOCATION
    ========================================== */

    if(
        !location
        ||
        location.value
        .trim()
        ===
        ""
    ){

        markInvalid(

            location,

            "Please enter the service location."

        );

    }


    /* ==========================================
       AREA
    ========================================== */

    const areaValue =
    Number(
        rotorArea?.value
        ||
        0
    );


    if(
        !rotorArea
        ||
        !Number.isFinite(
            areaValue
        )
        ||
        areaValue <= 0
    ){

        markInvalid(

            rotorArea,

            "Please enter a valid area in hectares."

        );

    }


    /* ==========================================
       RENTAL DATE
    ========================================== */

    const hasRentalDate =

    rotorDate

    &&

    rotorDate.value
    !==
    "";


    if(
        !hasRentalDate
    ){

        markInvalid(

            rotorDateBtn,

            "Please select the Rental Date."

        );

    }


    /* ==========================================
       ASSIGNMENT + STATUS

       Only validate once Rental Date exists,
       because these controls depend on Date.
    ========================================== */

    if(
        hasRentalDate
    ){

        applyRotorStatusRules();


        const primaryTractorID =
        parseInt(
            rotorTractorID?.value
            ||
            "0",
            10
        );


        const primaryDriverID =
        parseInt(
            rotorPrimaryDriverID?.value
            ||
            "0",
            10
        );


        if(
            primaryTractorID <= 0
        ){

            markInvalid(

                rotorTractorBtn,

                "Please select a Primary Rotor Tractor."

            );

        }


        if(
            primaryDriverID <= 0
        ){

            markInvalid(

                rotorPrimaryDriverBtn,

                "Please select a Primary Driver."

            );

        }


        if(
            !rotorStatus
            ||
            ![
                "Pending",
                "Ongoing",
                "Done"
            ]
            .includes(
                rotorStatus.value
            )
        ){

            markInvalid(

                rotorStatusBtn,

                "Please select the Rental Status."

            );

        }


        /* ======================================
           SECONDARY

           Only required when section is open.
        ====================================== */

        if(
            rotorSecondaryOpen
        ){

            const secondaryTractorID =
            parseInt(
                rotorSecondaryTractorID?.value
                ||
                "0",
                10
            );


            const secondaryDriverID =
            parseInt(
                rotorSecondaryDriverID?.value
                ||
                "0",
                10
            );


            if(
                secondaryTractorID <= 0
            ){

                markInvalid(

                    rotorSecondaryTractorBtn,

                    "Please select a Secondary Tractor."

                );

            }


            if(
                secondaryDriverID <= 0
            ){

                markInvalid(

                    rotorSecondaryDriverBtn,

                    "Please select a Secondary Driver."

                );

            }


            if(
                secondaryTractorID > 0
                &&
                secondaryTractorID
                ===
                primaryTractorID
            ){

                markInvalid(

                    rotorSecondaryTractorBtn,

                    "Secondary Tractor must be different from Primary Tractor."

                );

            }


            if(
                secondaryDriverID > 0
                &&
                secondaryDriverID
                ===
                primaryDriverID
            ){

                markInvalid(

                    rotorSecondaryDriverBtn,

                    "Secondary Driver must be different from Primary Driver."

                );

            }

        }

    }


    /* ==========================================
       MEMBER DISCOUNT
    ========================================== */

    if(
        affiliateValid
        &&
        rotorAffiliate.value
        ===
        "Member"
    ){

        const discountText =
        rotorDiscount?.value
        .trim()
        ||
        "";


        if(
            discountText !== ""
        ){

            if(
                !/^\d+(\.\d{1,2})?$/
                .test(
                    discountText
                )
            ){

                markInvalid(

                    rotorDiscount,

                    "Please enter a valid member discount."

                );

            }

            else{


                const subtotal =

                (
                    parseFloat(
                        pricePerHectareInput?.value
                    )
                    ||
                    0
                )

                *

                areaValue;


                if(
                    Number(
                        discountText
                    )
                    >
                    subtotal
                ){

                    markInvalid(

                        rotorDiscount,

                        "Discount cannot exceed the computed rental amount."

                    );

                }

            }

        }

    }

        /* ==========================================
        DOWNPAYMENT
        ========================================== */

        if(
            rotorDownpayment
            &&
            !validateRotorDownpayment()
        ){

            valid =
            false;


            if(
                !firstInvalid
            ){

                firstInvalid =
                rotorDownpayment;

            }

        }


    return {

        valid:
        valid,

        firstInvalid:
        firstInvalid

    };

}


/* ======================================================
   CLEAR ERROR WHILE TYPING
====================================================== */

addRotorModalElement
?.addEventListener(

    "input",

    function(event){

        const control =
        event.target;


        if(
            !control
            ||
            control === rotorDiscount
            ||
            control === rotorDownpayment
        ){

            return;

        }


        clearRotorInlineError(
            control
        );

    }

);


/* ======================================================
   CLEAR CUSTOM CONTROL ERROR AFTER SELECTION
====================================================== */

document.addEventListener(

    "click",

    function(event){


        const affiliateItem =
        event.target.closest(
            ".rotor-affiliate-item"
        );


        if(
            affiliateItem
        ){

            clearRotorInlineError(
                rotorAffiliateBtn
            );

        }


        const calendarDay =
        event.target.closest(
            ".rotor-calendar-day"
        );


        if(
            calendarDay
            &&
            !calendarDay.disabled
        ){

            clearRotorInlineError(
                rotorDateBtn
            );

        }


        const tractorItem =
        event.target.closest(
            ".rotor-tractor-item"
        );


        if(
            tractorItem
            &&
            !tractorItem.classList.contains(
                "rotor-tractor-disabled"
            )
        ){

            if(
                rotorTractorMenu
                ?.contains(
                    tractorItem
                )
            ){

                clearRotorInlineError(
                    rotorTractorBtn
                );

            }


            if(
                rotorSecondaryTractorMenu
                ?.contains(
                    tractorItem
                )
            ){

                clearRotorInlineError(
                    rotorSecondaryTractorBtn
                );

            }

        }


        const driverItem =
        event.target.closest(
            ".rotor-driver-item"
        );


        if(
            driverItem
            &&
            !driverItem.classList.contains(
                "rotor-driver-disabled"
            )
        ){

            if(
                rotorPrimaryDriverMenu
                ?.contains(
                    driverItem
                )
            ){

                clearRotorInlineError(
                    rotorPrimaryDriverBtn
                );

            }


            if(
                rotorSecondaryDriverMenu
                ?.contains(
                    driverItem
                )
            ){

                clearRotorInlineError(
                    rotorSecondaryDriverBtn
                );

            }

        }


        const statusItem =
        event.target.closest(
            ".rotor-status-item"
        );


        if(
            statusItem
        ){

            clearRotorInlineError(
                rotorStatusBtn
            );

        }

    },

    true

);

/* ======================================================
   SAVE ROTOR
====================================================== */

if(
    rotorForm
    &&
    saveRotorBtn
){

    saveRotorBtn
    .addEventListener(
        "click",
        async function(){


            if(
                rotorSaveBusy
            ){

                return;

            }


            const customer =
            document.getElementById(
                "rotorCustomerName"
            );


            const location =
            document.getElementById(
                "rotorLocation"
            );


            /* ==========================================
            INLINE FORM VALIDATION
            ========================================== */

            clearRotorInlineErrors();


            computeRotorTotal();


            const validation =
            await validateRotorAddForm(
                customer,
                location
            );


            if(
                !validation.valid
            ){

                focusRotorInvalidField(
                    validation.firstInvalid
                );


                return;

            }


            /* ==========================================
               FINAL LIVE AVAILABILITY CHECK
            ========================================== */

            const assignmentsValid =
            await validateRotorAssignmentsBeforeSave();


            if(
                !assignmentsValid
            ){

                return;

            }


            /* ==========================================
               CONFIRM
            ========================================== */

            const result =
            await Swal.fire({

                icon:
                "question",

                title:
                "Add Rotor Rental?",

                text:
                rotorDate.value
                <
                getRotorTodayKey()

                ?

                "This will be saved as a Historical / Audit Entry with Done status."

                :

                "Are you sure you want to add this Rotor rental?",

                showCancelButton:
                true,

                confirmButtonText:
                "Yes, Add",

                cancelButtonText:
                "Cancel",

                confirmButtonColor:
                "#8CA15B",

                cancelButtonColor:
                "#B71C1C",

                reverseButtons:
                true

            });


            if(
                !result.isConfirmed
            ){

                return;

            }


            rotorSaveBusy =
            true;


            saveRotorBtn.disabled =
            true;


            const originalButtonText =
            saveRotorBtn.innerHTML;


            saveRotorBtn.innerHTML =
            `
            <span
            class="spinner-border spinner-border-sm me-2"
            role="status">
            </span>
            SAVING...
            `;


            try{


                const formData =
                new FormData(
                    rotorForm
                );


                const response =
                await fetch(

                    "save/save_rotor.php",

                    {
                        method:
                        "POST",

                        body:
                        formData
                    }

                );


                const responseText =
                await response.text();


                const data =
                responseText.trim();


                if(
                    !response.ok
                    ||
                    data !== "success"
                ){

                    throw new Error(

                        data
                        ||
                        "Unable to save the Rotor rental."

                    );

                }


                const modal =
                bootstrap.Modal
                .getInstance(
                    addRotorModalElement
                );


                modal
                ?.hide();


                await Swal.fire({

                    icon:
                    "success",

                    title:
                    "Rotor rental added successfully.",

                    showConfirmButton:
                    false,

                    timer:
                    1500

                });


                window.location.reload();

            }
            catch(error){


                console.error(
                    "SAVE ROTOR ERROR:",
                    error
                );


                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Save Failed",

                    text:
                    error.message
                    ||
                    "Unable to save the Rotor rental.",

                    confirmButtonColor:
                    "#B71C1C"

                });

            }
            finally{


                rotorSaveBusy =
                false;


                saveRotorBtn.disabled =
                false;


                saveRotorBtn.innerHTML =
                originalButtonText;

            }

        }
    );

}

/* ======================================================
   LEDGER STATUS COLORS
====================================================== */

function updateRotorStatusColor(
    select
){

    select.classList.remove(

        "rotor-status-pending",

        "rotor-status-ongoing",

        "rotor-status-done"

    );


    if(
        select.value ===
        "Pending"
    ){

        select.classList.add(
            "rotor-status-pending"
        );

    }

    else if(
        select.value ===
        "Ongoing"
    ){

        select.classList.add(
            "rotor-status-ongoing"
        );

    }

    else{

        select.classList.add(
            "rotor-status-done"
        );

    }

}


/* ======================================================
   LEDGER REMARKS COLORS
====================================================== */

function updateRotorRemarksColor(
    select
){

    select.classList.remove(

        "rotor-remarks-paid",

        "rotor-remarks-unpaid"

    );


    if(
        select.value ===
        "Paid"
    ){

        select.classList.add(
            "rotor-remarks-paid"
        );

    }

    else{

        select.classList.add(
            "rotor-remarks-unpaid"
        );

    }

}


/* ======================================================
   ROTOR LEDGER DATE HELPER
====================================================== */

function getRotorLedgerTodayKey(){

    return getRotorTodayKey();

}


/* ======================================================
   APPLY LEDGER STATUS RULES

   FUTURE
   - Pending only
   - locked

   TODAY
   - Pending
   - Ongoing
   - Done

   PAST + PENDING
   - Missed
   - Pending / Done
   - Ongoing unavailable

   PAST + ONGOING
   - Delayed
   - current Ongoing remains visible
   - Done allowed
   - Pending only through Edit / Reschedule

   MAINTENANCE
   - Pending only
====================================================== */

function applyRotorLedgerStatusRules(
    select
){

    if(!select){

        return;

    }


    const rentalDate =
    select.dataset.date
    ||
    "";


    if(
        rentalDate === ""
    ){

        return;

    }


    const today =
    getRotorLedgerTodayKey();


    const pendingOption =
    select.querySelector(
        'option[value="Pending"]'
    );


    const ongoingOption =
    select.querySelector(
        'option[value="Ongoing"]'
    );


    const doneOption =
    select.querySelector(
        'option[value="Done"]'
    );


    const ledgerRow =
    select.closest(
        ".rotor-ledger-row"
    );


    const tractorIsMaintenance =
    ledgerRow
    ?.classList
    .contains(
        "rotor-maintenance-alert-row"
    )
    ===
    true;


    /* ==================================================
       RESET
    ================================================== */

    [
        pendingOption,
        ongoingOption,
        doneOption
    ]
    .forEach(
    function(option){

        if(option){

            option.disabled =
            false;


            option.hidden =
            false;

        }

    });


    select.disabled =
    false;


    select.title =
    "";


    /* ==================================================
       DONE = FINAL SAFETY

       Normally PHP already renders Done as a badge.
    ================================================== */

    if(
        select.value ===
        "Done"
        &&
        !tractorIsMaintenance
        &&
        rentalDate <= today
    ){

        select.disabled =
        true;


        select.title =
        "This Rotor rental is completed. Done status is final.";


        return;

    }


    /* ==================================================
       MAINTENANCE = HIGHEST PRIORITY
    ================================================== */

    if(
        tractorIsMaintenance
    ){

        select.disabled =
        false;


        select.title =
        "This Rotor rental must remain Pending while an assigned tractor is under Maintenance.";


        if(ongoingOption){

            ongoingOption.disabled =
            true;

        }


        if(doneOption){

            doneOption.disabled =
            true;

        }


        return;

    }


    /* ==================================================
       FUTURE = PENDING ONLY
    ================================================== */

    if(
        rentalDate > today
    ){

        if(
            select.value ===
            "Pending"
        ){

            select.disabled =
            true;


            select.title =
            "Future Rotor rental status remains Pending until its Rental Date.";

        }

        else{

            /*
               Legacy / invalid old record.
               Keep Pending available so it can be corrected.
            */

            select.disabled =
            false;


            if(pendingOption){

                pendingOption.disabled =
                false;

            }


            if(
                ongoingOption
                &&
                select.value !==
                "Ongoing"
            ){

                ongoingOption.disabled =
                true;

            }


            if(
                doneOption
                &&
                select.value !==
                "Done"
            ){

                doneOption.disabled =
                true;

            }

        }


        return;

    }


    /* ==================================================
       TODAY
    ================================================== */

    if(
        rentalDate === today
    ){

        select.disabled =
        false;


        return;

    }


    /* ==================================================
       PAST
    ================================================== */

    if(
        rentalDate < today
    ){

        select.disabled =
        false;


        /*
           DELAYED

           Existing Ongoing must remain visible because
           it describes the CURRENT state.

           It may move to Done.

           Returning to Pending requires an actual
           reschedule through the Edit modal.
        */

        if(
            select.value ===
            "Ongoing"
        ){

            if(pendingOption){

                pendingOption.disabled =
                true;

            }


            if(ongoingOption){

                ongoingOption.disabled =
                true;

                ongoingOption.hidden =
                false;

            }


            if(doneOption){

                doneOption.disabled =
                false;

            }


            select.title =
            "Delayed rental. Reschedule through Edit, or mark Done when the service is completed.";


            return;

        }


        /*
           MISSED

           Past Pending may remain Pending for
           rescheduling or may be marked Done.

           It cannot newly become Ongoing in the past.
        */

        if(ongoingOption){

            ongoingOption.disabled =
            true;

            ongoingOption.hidden =
            true;

        }

    }

}


/* ======================================================
   FUTURE RENTAL STATUS LOCK
====================================================== */

const rotorFutureStatusLocks =
document.querySelectorAll(
    ".rotor-future-status-lock"
);


rotorFutureStatusLocks
.forEach(
function(button){

    button
    .addEventListener(
        "click",
        function(){

            const rentalDate =
            this.dataset.rentalDate
            ||
            "";


            const formattedDate =

            rentalDate !== ""

            ?

            rotorFormatDate(
                rentalDate
            )

            :

            "";


            Swal.fire({

                icon:
                "info",

                title:
                "Status Locked",

                text:

                formattedDate !== ""

                ?

                "This rental is scheduled for "
                +
                formattedDate
                +
                ". The status can only be updated starting on the Rental Date."

                :

                "This future rental must remain Pending until its Rental Date.",

                confirmButtonColor:
                "#8CA15B"

            });

        }
    );

});


/* ======================================================
   UPDATE ROTOR STATUS
====================================================== */

document
.querySelectorAll(
    ".rotor-status-select"
)
.forEach(
function(select){


    updateRotorStatusColor(
        select
    );


    select.dataset.old =
    select.value;


    applyRotorLedgerStatusRules(
        select
    );


    select.addEventListener(

        "change",

        function(){


            const current =
            this;


            const oldStatus =
            current.dataset.old
            ||
            current.value;


            const newStatus =
            current.value;


            const rentalDate =
            current.dataset.date
            ||
            "";


            const today =
            getRotorLedgerTodayKey();


            if(
                rentalDate > today
                &&
                newStatus !==
                "Pending"
            ){

                current.value =
                oldStatus;


                updateRotorStatusColor(
                    current
                );


                applyRotorLedgerStatusRules(
                    current
                );


                Swal.fire({

                    icon:"warning",

                    title:
                    "Invalid Status",

                    text:
                    "A future Rotor rental must remain Pending until its service date.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            if(
                rentalDate < today
                &&
                newStatus ===
                "Ongoing"
            ){

                current.value =
                oldStatus;


                updateRotorStatusColor(
                    current
                );


                applyRotorLedgerStatusRules(
                    current
                );


                Swal.fire({

                    icon:"warning",

                    title:
                    "Invalid Status",

                    text:
                    "A past Rotor rental cannot be changed to Ongoing.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }

            /*
            DELAYED ONGOING

            Returning to Pending is only valid when
            the rental is actually rescheduled through Edit.
            */

            if(
                rentalDate < today
                &&
                oldStatus ===
                "Ongoing"
                &&
                newStatus ===
                "Pending"
            ){

                current.value =
                oldStatus;


                updateRotorStatusColor(
                    current
                );


                applyRotorLedgerStatusRules(
                    current
                );


                Swal.fire({

                    icon:
                    "info",

                    title:
                    "Reschedule Required",

                    text:
                    "A Delayed Rotor rental can only return to Pending when it is rescheduled through Edit Rental.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            if(
                newStatus ===
                oldStatus
            ){

                applyRotorLedgerStatusRules(
                    current
                );


                return;

            }


            Swal.fire({

                icon:"question",

                title:
                "Change Status?",

                text:

                newStatus ===
                "Done"

                ?

                "Mark this Rotor rental as Done? This status will become final."

                :

                "Are you sure you want to change the status?",

                showCancelButton:true,

                confirmButtonText:
                "Yes",

                cancelButtonText:
                "Cancel",

                confirmButtonColor:
                "#8CA15B",

                cancelButtonColor:
                "#C62828",

                reverseButtons:true

            })

            .then(result=>{


                if(
                    !result.isConfirmed
                ){

                    current.value =
                    oldStatus;


                    updateRotorStatusColor(
                        current
                    );


                    applyRotorLedgerStatusRules(
                        current
                    );


                    return;

                }


                fetch(

                    "update/update_rotor_status.php",

                    {

                        method:"POST",

                        headers:{

                            "Content-Type":
                            "application/x-www-form-urlencoded"

                        },

                        body:

                        "id="
                        +
                        encodeURIComponent(
                            current.dataset.id
                        )

                        +

                        "&status="
                        +
                        encodeURIComponent(
                            newStatus
                        )
                        +
                        "&csrf_token="
                        +
                        encodeURIComponent(
                            String(
                                document
                                .querySelector(
                                    'input[name="csrf_token"]'
                                )
                                ?.value
                                ||
                                ""
                            )
                        )

                    }

                )

                .then(
                    response =>
                    response.text()
                )

                .then(response=>{


                    response =
                    response.trim();


                    console.log(
                        "ROTOR STATUS:",
                        response
                    );


                    if(
                        response ===
                        "success"
                    ){

                        current.dataset.old =
                        newStatus;


                        Swal.fire({

                            icon:"success",

                            title:
                            "Status updated successfully.",

                            showConfirmButton:
                            false,

                            timer:1200

                        })

                        .then(()=>{

                            saveRotorLedgerPosition();

                            window.location.reload();

                        });

                    }

                    else{

                        current.value =
                        oldStatus;


                        updateRotorStatusColor(
                            current
                        );


                        applyRotorLedgerStatusRules(
                            current
                        );


                        Swal.fire({

                            icon:"error",

                            title:
                            "Update Failed",

                            text:
                            response
                            ||
                            "Unable to update the Rotor status.",

                            confirmButtonColor:
                            "#B71C1C"

                        });

                    }

                })

                .catch(error=>{


                    console.error(
                        "ROTOR STATUS ERROR:",
                        error
                    );


                    current.value =
                    oldStatus;


                    updateRotorStatusColor(
                        current
                    );


                    applyRotorLedgerStatusRules(
                        current
                    );


                    Swal.fire({

                        icon:"error",

                        title:
                        "Update Failed",

                        text:
                        "Unable to update the Rotor status. Please try again.",

                        confirmButtonColor:
                        "#B71C1C"

                    });

                });

            });

        }

    );

});

/* ======================================================
   PRESERVE ROTOR LEDGER POSITION AFTER UPDATE
====================================================== */

const rotorLedgerBody =
document.querySelector(
    ".rotor-ledger-body"
);


const rotorLedgerScrollKey =
"rotorLedgerScrollTop";


function saveRotorLedgerPosition(){

    if(
        !rotorLedgerBody
    ){

        return;
    }


    sessionStorage.setItem(

        rotorLedgerScrollKey,

        String(
            rotorLedgerBody.scrollTop
        )

    );

}


function restoreRotorLedgerPosition(){

    if(
        !rotorLedgerBody
    ){

        return;
    }


    const savedPosition =
    sessionStorage.getItem(
        rotorLedgerScrollKey
    );


    if(
        savedPosition === null
    ){

        return;
    }


    rotorLedgerBody.scrollTop =
    Number(
        savedPosition
    )
    ||
    0;


    sessionStorage.removeItem(
        rotorLedgerScrollKey
    );

}


restoreRotorLedgerPosition();

/* ======================================================
   ROTOR FILTERS
====================================================== */

const rotorStartDateFilter =
document.getElementById(
    "rotorStartDateFilter"
);


const rotorEndDateFilter =
document.getElementById(
    "rotorEndDateFilter"
);


const rotorApplyDateFilter =
document.getElementById(
    "applyRotorDateFilter"
);


const rotorClearDateFilter =
document.getElementById(
    "clearRotorDateFilter"
);


const rotorSearch =
document.getElementById(
    "searchRotor"
);


const rotorAffiliateFilter =
document.getElementById(
    "rotorAffiliateFilter"
);


const rotorStatusFilter =
document.getElementById(
    "rotorStatusFilter"
);


const rotorRemarksFilter =
document.getElementById(
    "rotorRemarksFilter"
);

/* ======================================================
   LEDGER DATE RESTRICTIONS
====================================================== */

function updateRotorLedgerDateRestrictions() {

    if (rotorStartDateFilter) {

        rotorStartDateFilter.min = "";

        rotorStartDateFilter.max = rotorEndDateFilter
            ? rotorEndDateFilter.value
            : "";
    }

    if (rotorEndDateFilter) {

        rotorEndDateFilter.min = rotorStartDateFilter
            ? rotorStartDateFilter.value
            : "";

        rotorEndDateFilter.max = "";
    }
}


if (rotorStartDateFilter) {
    rotorStartDateFilter.addEventListener(
        "change",
        updateRotorLedgerDateRestrictions
    );
}

if (rotorEndDateFilter) {
    rotorEndDateFilter.addEventListener(
        "change",
        updateRotorLedgerDateRestrictions
    );
}

updateRotorLedgerDateRestrictions();


function filterRotor() {

    const start = rotorStartDateFilter
        ? rotorStartDateFilter.value
        : "";

    const end = rotorEndDateFilter
        ? rotorEndDateFilter.value
        : "";

    const keyword = rotorSearch
        ? rotorSearch.value
            .toLowerCase()
            .trim()
        : "";

    const affiliate = rotorAffiliateFilter
        ? rotorAffiliateFilter.value
        : "all";

    const status = rotorStatusFilter
        ? rotorStatusFilter.value
        : "all";

    const remarks = rotorRemarksFilter
        ? rotorRemarksFilter.value
        : "all";


    document
    .querySelectorAll(".rotor-ledger-row")
    .forEach(function(row) {

        const rowSearch = (
            row.dataset.search || ""
        ).toLowerCase();

        const rowAffiliate =
            row.dataset.affiliate || "";

        const rowStatus =
            row.dataset.status || "";

        const rowRemarks =
            row.dataset.remarks || "";

        const rowDate =
            row.dataset.date || "";

        const rowIsMaintenance =
            row.dataset.maintenance === "1";


        const matchSearch =
            !keyword ||
            rowSearch.includes(keyword);

        const matchAffiliate =
            affiliate === "all" ||
            rowAffiliate === affiliate;

        const matchStatus =
            status === "all" ||
            (
                status === "Maintenance"
                    ? rowIsMaintenance
                    : rowStatus === status
            );

        const matchRemarks =
            remarks === "all" ||
            rowRemarks === remarks;

        const matchDate =
            (
                !start ||
                rowDate >= start
            ) &&
            (
                !end ||
                rowDate <= end
            );


        row.style.display =
            (
                matchSearch &&
                matchAffiliate &&
                matchStatus &&
                matchRemarks &&
                matchDate
            )
                ? "grid"
                : "none";

    });

    const emptyMessage =
    document.getElementById("rotorFilterEmpty");

    if(emptyMessage){

        const rows =
        document.querySelectorAll(".rotor-ledger-row");

        const hasVisibleRow =
        Array.from(rows).some(
            row => row.style.display !== "none"
        );

        emptyMessage.style.display =
            rows.length > 0 && !hasVisibleRow
            ? "flex"
            : "none";
    }

}


if(rotorSearch){

    rotorSearch.addEventListener(
        "input",
        filterRotor
    );

}


if(rotorAffiliateFilter){

    rotorAffiliateFilter.addEventListener(
        "change",
        filterRotor
    );

}


if(rotorStatusFilter){

    rotorStatusFilter.addEventListener(
        "change",
        filterRotor
    );

}


if(rotorRemarksFilter){

    rotorRemarksFilter.addEventListener(
        "change",
        filterRotor
    );

}


if(rotorApplyDateFilter){

    rotorApplyDateFilter.addEventListener(
        "click",
        function() {

            const start = rotorStartDateFilter
                ? rotorStartDateFilter.value
                : "";

            const end = rotorEndDateFilter
                ? rotorEndDateFilter.value
                : "";


            if (start && end && start > end) {

                Swal.fire({
                    icon: "warning",
                    title: "Invalid Date Range",
                    text: "The Start Date cannot be later than the End Date.",
                    confirmButtonColor: "#8CA15B"
                });

                return;
            }

            filterRotor();


            const dateModal = document.getElementById(
                "rotorDateFilterModal"
            );

            if (
                dateModal &&
                typeof bootstrap !== "undefined"
            ) {

                bootstrap.Modal
                    .getOrCreateInstance(dateModal)
                    .hide();
            }
        }
    );

}


if(rotorClearDateFilter){

    rotorClearDateFilter
    .addEventListener(

        "click",

        function(){


            if(rotorStartDateFilter){

                rotorStartDateFilter.value =
                "";

            }


            if(rotorEndDateFilter){

                rotorEndDateFilter.value =
                "";

            }


            if(rotorAffiliateFilter){

                rotorAffiliateFilter.value =
                "all";

            }


            if(rotorStatusFilter){

                rotorStatusFilter.value =
                "all";

            }


            if(rotorRemarksFilter){

                rotorRemarksFilter.value =
                "all";

            }


            if(rotorSearch){

                rotorSearch.value =
                "";

            }


            updateRotorLedgerDateRestrictions();

            filterRotor();

        }

    );

}


filterRotor();


/* ======================================================
   ADD ROTOR / SET PRICE
====================================================== */

const addRotorBtn =
document.getElementById(
    "addRotorBtn"
);


const rotorCurrentPrice =
document.getElementById(
    "rotorCurrentPrice"
);


const setPriceBtn =
document.getElementById(
    "setPriceBtn"
);


if(
    addRotorBtn &&
    rotorCurrentPrice
){

    addRotorBtn.addEventListener(

        "click",

        function(){


            const price =
            parseFloat(
                rotorCurrentPrice.value
            ) || 0;


            if(
                price <= 0
            ){

                Swal.fire({

                    icon:"warning",

                    title:
                    "Price Not Set",

                    text:
                    "Please set the price per hectare first.",

                    showCancelButton:true,

                    confirmButtonText:
                    "Set Price",

                    cancelButtonText:
                    "Cancel",

                    confirmButtonColor:
                    "#8CA15B",

                    cancelButtonColor:
                    "#B71C1C",

                    reverseButtons:true

                })

                .then(result=>{


                    if(
                        !result.isConfirmed
                    ){

                        return;

                    }


                    sessionStorage.setItem(

                        "openRotorAfterPrice",

                        "yes"

                    );


                    const setPriceModalElement =
                    document.getElementById(
                        "setPriceModal"
                    );


                    if(
                        setPriceModalElement
                    ){

                        const setPriceModal =
                        new bootstrap.Modal(
                            setPriceModalElement
                        );


                        setPriceModal.show();

                    }

                });

            }

            else{

                const addRotorModalElement =
                document.getElementById(
                    "addRotorModal"
                );


                if(
                    addRotorModalElement
                ){

                    const addRotorModal =
                    new bootstrap.Modal(
                        addRotorModalElement
                    );


                    addRotorModal.show();

                }

            }

        }

    );

}


if(setPriceBtn){

    setPriceBtn.addEventListener(

        "click",

        function(){

            sessionStorage.removeItem(
                "openRotorAfterPrice"
            );

        }

    );

}

/* =========================================================
   EDIT ROTOR RENTAL
   SEPTEMBER 2026

   RULES:
   - Pending:
     Date + Primary + Secondary editable.

   - Ongoing:
     Date + Primary normally locked.
     Secondary editable.

   - Missed:
     Past Pending.
     May reschedule to today/future.

   - Delayed:
     Past Ongoing.
     May reschedule to today/future.
     Date change will later reset Ongoing -> Pending
     through the backend.

   - Done:
     Fully read-only.

   - No "None" dropdown option.
   - Secondary is removed through the toggle.
========================================================= */


/* =========================================================
   ELEMENTS
========================================================= */

const editRotorModalElement =
document.getElementById(
    "editRotorModal"
);


const editRotorForm =
document.getElementById(
    "editRotorForm"
);


const editRotorID =
document.getElementById(
    "editRotorID"
);


const editRotorTransactionID =
document.getElementById(
    "editRotorTransactionID"
);


const editRotorTransactionBadge =
document.getElementById(
    "editRotorTransactionBadge"
);


const editRotorAffiliate =
document.getElementById(
    "editRotorAffiliate"
);


const editRotorAffiliateText =
document.getElementById(
    "editRotorAffiliateText"
);


const editRotorMemberID =
document.getElementById(
    "editRotorMemberID"
);


const editRotorCustomerName =
document.getElementById(
    "editRotorCustomerName"
);


const editRotorLocation =
document.getElementById(
    "editRotorLocation"
);


const editRotorArea =
document.getElementById(
    "editRotorArea"
);


/* =========================================================
   DATE
========================================================= */

const editRotorDate =
document.getElementById(
    "editRotorDate"
);


const editRotorDateBtn =
document.getElementById(
    "editRotorDateBtn"
);


const editRotorDateText =
document.getElementById(
    "editRotorDateText"
);


const editRotorCalendar =
document.getElementById(
    "editRotorCalendar"
);


const editRotorCalendarPrev =
document.getElementById(
    "editRotorCalendarPrev"
);


const editRotorCalendarMonth =
document.getElementById(
    "editRotorCalendarMonth"
);


const editRotorCalendarNext =
document.getElementById(
    "editRotorCalendarNext"
);


const editRotorCalendarDays =
document.getElementById(
    "editRotorCalendarDays"
);


/* =========================================================
   PRIMARY TRACTOR
========================================================= */

const editRotorTractorID =
document.getElementById(
    "editRotorTractorID"
);


const editRotorTractorNo =
document.getElementById(
    "editRotorTractorNo"
);


const editRotorTractorBtn =
document.getElementById(
    "editRotorTractorBtn"
);


const editRotorTractorText =
document.getElementById(
    "editRotorTractorText"
);


const editRotorTractorMenu =
document.getElementById(
    "editRotorTractorMenu"
);


/* =========================================================
   PRIMARY DRIVER
========================================================= */

const editRotorPrimaryDriverID =
document.getElementById(
    "editRotorPrimaryDriverID"
);


const editRotorPrimaryDriverBtn =
document.getElementById(
    "editRotorPrimaryDriverBtn"
);


const editRotorPrimaryDriverText =
document.getElementById(
    "editRotorPrimaryDriverText"
);


const editRotorPrimaryDriverMenu =
document.getElementById(
    "editRotorPrimaryDriverMenu"
);


/* =========================================================
   SECONDARY
========================================================= */

const editRotorSecondaryToggle =
document.getElementById(
    "editRotorSecondaryToggle"
);


const editRotorSecondaryToggleText =
document.getElementById(
    "editRotorSecondaryToggleText"
);


const editRotorSecondaryAssignment =
document.getElementById(
    "editRotorSecondaryAssignment"
);


const editRotorSecondaryTractorID =
document.getElementById(
    "editRotorSecondaryTractorID"
);


const editRotorSecondaryTractorNo =
document.getElementById(
    "editRotorSecondaryTractorNo"
);


const editRotorSecondaryTractorBtn =
document.getElementById(
    "editRotorSecondaryTractorBtn"
);


const editRotorSecondaryTractorText =
document.getElementById(
    "editRotorSecondaryTractorText"
);


const editRotorSecondaryTractorMenu =
document.getElementById(
    "editRotorSecondaryTractorMenu"
);


const editRotorSecondaryDriverID =
document.getElementById(
    "editRotorSecondaryDriverID"
);


const editRotorSecondaryDriverBtn =
document.getElementById(
    "editRotorSecondaryDriverBtn"
);


const editRotorSecondaryDriverText =
document.getElementById(
    "editRotorSecondaryDriverText"
);


const editRotorSecondaryDriverMenu =
document.getElementById(
    "editRotorSecondaryDriverMenu"
);


/* =========================================================
   SUMMARY
========================================================= */

const editRotorPricePerHectare =
document.getElementById(
    "editRotorPricePerHectare"
);


const editRotorDiscount =
document.getElementById(
    "editRotorDiscount"
);


const editRotorTotal =
document.getElementById(
    "editRotorTotal"
);


const editRotorAmountPaid =
document.getElementById(
    "editRotorAmountPaid"
);


const editRotorBalance =
document.getElementById(
    "editRotorBalance"
);


const editRotorAdditionalPayment =
document.getElementById(
    "editRotorAdditionalPayment"
);

const editRotorPaymentHistory =
document.getElementById(
    "editRotorPaymentHistory"
);

const editRotorStatus =
document.getElementById(
    "editRotorStatus"
);


const editRotorStatusText =
document.getElementById(
    "editRotorStatusText"
);


const editRotorRemarksText =
document.getElementById(
    "editRotorRemarksText"
);


const editRotorLifecycleHint =
document.getElementById(
    "editRotorLifecycleHint"
);


const updateRotorBtn =
document.getElementById(
    "updateRotorBtn"
);


/* =========================================================
   STATE
========================================================= */

let editRotorLoadedRecord =
null;


let editRotorTractors =
[];


let editRotorDrivers =
[];


let editRotorAvailabilityRequest =
0;


let editRotorCalendarAvailability =
{};


let editRotorCalendarAvailabilityKey =
"";


let editRotorCalendarRequest =
0;


let editRotorCalendarView =
new Date();


editRotorCalendarView.setDate(
    1
);


/* =========================================================
   MONEY
========================================================= */

function editRotorMoneyValue(
    value
){

    const amount =
    Number(
        value
    );


    if(
        !Number.isFinite(
            amount
        )
    ){

        return "0.00";

    }


    return amount.toFixed(
        2
    );

}


/* =========================================================
   FORMAT ROTOR PAYMENT DATE
========================================================= */

function formatEditRotorPaymentDate(value) {

    const raw = String(value ?? "").trim();

    const match = raw.match(
        /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/
    );

    if (!match) {
        return raw || "—";
    }

    const date = new Date(
        `${match[1]}-${match[2]}-${match[3]}T${match[4]}:${match[5]}:${match[6] || "00"}+08:00`
    );

    if (Number.isNaN(date.getTime())) {
        return raw;
    }

    const dateText = date.toLocaleDateString("en-US", {
        timeZone: "Asia/Manila",
        month: "short",
        day: "numeric",
        year: "numeric"
    });

    const timeText = date.toLocaleTimeString("en-US", {
        timeZone: "Asia/Manila",
        hour: "numeric",
        minute: "2-digit",
        hour12: true
    });

    return `${dateText} • ${timeText}`;
}



/* =========================================================
   EDIT ROTOR PAYMENT HISTORY
========================================================= */

function renderEditRotorPaymentHistory(payments, amountPaid = 0) {

    if (!editRotorPaymentHistory) return;

    editRotorPaymentHistory.replaceChildren();

    if (!Array.isArray(payments) || payments.length === 0) {

        const row = document.createElement("tr");
        row.className = "rotor-edit-payment-empty";

        const cell = document.createElement("td");
        cell.colSpan = 6;

        cell.textContent = Number(amountPaid) > 0
            ? "No itemized history is available for earlier payments."
            : "No payment records available.";

        row.appendChild(cell);
        editRotorPaymentHistory.appendChild(row);

        return;
    }

    const money = function(value) {

        const amount = Number(value);

        return "₱" + (
            Number.isFinite(amount) ? amount : 0
        ).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    payments.forEach(function(payment) {

        const recordedBy =
            String(payment.recorded_by_name || "").trim()
            ||
            (
                payment.recorded_by_id
                ? "User #" + payment.recorded_by_id
                : "—"
            );

        const values = [
            formatEditRotorPaymentDate(payment.payment_date),
            payment.payment_type || "—",
            money(payment.amount),
            money(payment.balance_after),
            payment.notes || "—",
            recordedBy
        ];

        const row = document.createElement("tr");

        values.forEach(function(value, index) {

            const cell = document.createElement("td");
            cell.textContent = String(value);

            if (index === 2) {
                cell.className = "rotor-edit-payment-amount";
            }

            if (index === 3) {
                cell.className = "rotor-edit-payment-balance";
            }

            row.appendChild(cell);
        });

        editRotorPaymentHistory.appendChild(row);
    });
}


/* =========================================================
   ADDITIONAL PAYMENT VALIDATION
========================================================= */

function validateEditRotorAdditionalPayment(){

    if(
        !editRotorAdditionalPayment
    ){

        return true;

    }


    clearRotorInlineError(
        editRotorAdditionalPayment
    );


    const value =
    editRotorAdditionalPayment
    .value
    .trim();


    if(
        value === ""
    ){

        return true;

    }


    if(
        !/^\d+(\.\d{0,2})?$/
        .test(
            value
        )
    ){

        setRotorInlineError(

            editRotorAdditionalPayment,

            "Please enter numbers only, up to 2 decimal places."

        );


        return false;

    }


    const additionalPayment =
    parseFloat(
        value
    ) || 0;


    const currentBalance =
    parseFloat(
        editRotorBalance?.value
        ||
        "0"
    ) || 0;


    if(
        additionalPayment
        >
        currentBalance + 0.009
    ){

        setRotorInlineError(

            editRotorAdditionalPayment,

            "Additional Payment cannot exceed the Current Balance."

        );


        return false;

    }


    return true;

}


editRotorAdditionalPayment
?.addEventListener(

    "input",

    function(){

        validateEditRotorAdditionalPayment();

    }

);


/* =========================================================
   CLOSE EDIT ASSIGNMENT MENUS
========================================================= */

function closeEditRotorAssignmentMenus(){

    editRotorTractorMenu
    ?.classList
    .remove(
        "show"
    );


    editRotorPrimaryDriverMenu
    ?.classList
    .remove(
        "show"
    );


    editRotorSecondaryTractorMenu
    ?.classList
    .remove(
        "show"
    );


    editRotorSecondaryDriverMenu
    ?.classList
    .remove(
        "show"
    );

}


/* =========================================================
   SECONDARY UI
========================================================= */

function resetEditRotorSecondaryUI(){

    editRotorSecondaryAssignment
    ?.classList
    .remove(
        "is-open"
    );


    editRotorSecondaryToggle
    ?.classList
    .remove(
        "is-open"
    );


    if(
        editRotorSecondaryToggleText
    ){

        editRotorSecondaryToggleText.textContent =
        "ADD SECONDARY ASSIGNMENT";

    }


    const icon =
    editRotorSecondaryToggle
    ?.querySelector(
        "i"
    );


    if(icon){

        icon.className =
        "bi bi-plus-lg";

    }

}


function openEditRotorSecondaryUI(){

    editRotorSecondaryAssignment
    ?.classList
    .add(
        "is-open"
    );


    editRotorSecondaryToggle
    ?.classList
    .add(
        "is-open"
    );


    if(
        editRotorSecondaryToggleText
    ){

        editRotorSecondaryToggleText.textContent =
        "REMOVE SECONDARY ASSIGNMENT";

    }


    const icon =
    editRotorSecondaryToggle
    ?.querySelector(
        "i"
    );


    if(icon){

        icon.className =
        "bi bi-dash-lg";

    }

}


/* =========================================================
   CLEAR SECONDARY ASSIGNMENT
========================================================= */

function clearEditRotorSecondaryAssignment(){

    if(
        editRotorSecondaryTractorID
    ){

        editRotorSecondaryTractorID.value =
        "";

    }


    if(
        editRotorSecondaryTractorNo
    ){

        editRotorSecondaryTractorNo.value =
        "";

    }


    if(
        editRotorSecondaryDriverID
    ){

        editRotorSecondaryDriverID.value =
        "";

    }


    if(
        editRotorSecondaryTractorText
    ){

        editRotorSecondaryTractorText.textContent =
        "Select Secondary Tractor";

    }


    if(
        editRotorSecondaryDriverText
    ){

        editRotorSecondaryDriverText.textContent =
        "Select Secondary Driver";

    }


    refreshEditRotorAssignmentMenus();

}


/* =========================================================
   EDIT TRACTOR AVAILABILITY
   SELF-EXCLUDES CURRENT ROTOR RENTAL
========================================================= */

async function getEditRotorTractorAvailability(
    rentalDate,
    rotorID
){

    const url =

    "tractor/get_tractor_availability.php"
    +
    "?tractor_type=Rotor"
    +
    "&rental_date="
    +
    encodeURIComponent(
        rentalDate
    )
    +
    "&rental_type=rotor"
    +
    "&rental_id="
    +
    encodeURIComponent(
        rotorID
    );


    const response =
    await fetch(
        url,
        {
            cache:
            "no-store"
        }
    );


    let data;


    try{

        data =
        await response.json();

    }
    catch(error){

        throw new Error(
            "Invalid Tractor availability response."
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
            "Unable to load Rotor tractor availability."
        );

    }


    return data;

}


/* =========================================================
   EDIT DRIVER AVAILABILITY
========================================================= */

async function getEditRotorDriverAvailability(
    rentalDate,
    rotorID
){

    const url =

    "driver/get_driver_availability.php"
    +
    "?rental_date="
    +
    encodeURIComponent(
        rentalDate
    )
    +
    "&rental_type=rotor"
    +
    "&rental_id="
    +
    encodeURIComponent(
        rotorID
    );


    const response =
    await fetch(
        url,
        {
            cache:
            "no-store"
        }
    );


    let data;


    try{

        data =
        await response.json();

    }
    catch(error){

        throw new Error(
            "Invalid Driver availability response."
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
            "Unable to load Driver availability."
        );

    }


    return data;

}


/* =========================================================
   EDIT TRACTOR MENU
========================================================= */

function renderEditRotorTractorMenu(
    menu,
    tractors
){

    if(!menu){

        return;

    }


    if(
        !Array.isArray(
            tractors
        )
        ||
        tractors.length === 0
    ){

        menu.innerHTML =
        `
        <div class="rotor-tractor-empty">
            No Rotor tractors available.
        </div>
        `;


        return;

    }


    let html =
    "";


    tractors.forEach(
    function(tractor){


        const tractorID =
        parseInt(
            tractor.tractor_id,
            10
        )
        ||
        0;


        if(
            tractorID <= 0
        ){

            return;

        }


        const available =
        tractor.available
        ===
        true;


        const tractorNo =
        rotorEscapeHTML(
            tractor.tractor_no
            ||
            ""
        );


        const tractorName =
        rotorEscapeHTML(
            tractor.tractor_name
            ||
            ""
        );


        const availability =
        rotorEscapeHTML(
            tractor.availability
            ||
            "Unavailable"
        );


        const stateClass =
        getRotorTractorStateClass(
            tractor.availability
        );


        html +=
        `
        <div
        class="
            rotor-tractor-item
            ${available ? "" : "rotor-tractor-disabled"}
        "
        data-resource-id="${tractorID}"
        data-tractor-no="${tractorNo}"
        data-tractor-name="${tractorName}"
        data-available="${available ? "1" : "0"}">

            <div class="rotor-tractor-details">

                <span class="rotor-tractor-number">
                    ${tractorNo}
                </span>

                <span class="rotor-tractor-name">
                    ${tractorName}
                </span>

            </div>

            <span
            class="
                rotor-tractor-status
                ${stateClass}
            ">
                ${rotorEscapeHTML(availability)}
            </span>

        </div>
        `;

    });


    menu.innerHTML =
    html
    ||
    `
    <div class="rotor-tractor-empty">
        No Rotor tractors available.
    </div>
    `;

}


/* =========================================================
   EDIT DRIVER MENU
========================================================= */

function renderEditRotorDriverMenu(
    menu,
    drivers
){

    if(!menu){

        return;

    }


    if(
        !Array.isArray(
            drivers
        )
        ||
        drivers.length === 0
    ){

        menu.innerHTML =
        `
        <div class="rotor-driver-empty">
            No Drivers available.
        </div>
        `;


        return;

    }


    let html =
    "";


    drivers.forEach(
    function(driver){


        const driverID =
        parseInt(
            driver.driver_id,
            10
        )
        ||
        0;


        if(
            driverID <= 0
        ){

            return;

        }


        const available =
        driver.available
        ===
        true;


        const driverName =
        rotorEscapeHTML(
            driver.full_name
            ||
            ""
        );


        const driverCode =
        rotorEscapeHTML(
            driver.driver_code
            ||
            ""
        );


        const availability =
        rotorEscapeHTML(
            driver.availability
            ||
            "Unavailable"
        );


        const stateClass =
        getRotorDriverStateClass(
            driver.availability
        );


        html +=
        `
        <div
        class="
            rotor-driver-item
            ${available ? "" : "rotor-driver-disabled"}
        "
        data-resource-id="${driverID}"
        data-driver-name="${driverName}"
        data-available="${available ? "1" : "0"}">

            <div class="rotor-tractor-details">

                    <span class="rotor-tractor-number">
                        ${driverCode}
                    </span>

                    <span class="rotor-tractor-name">
                        ${driverName}
                    </span>

                </div>

                <span
                class="
                    rotor-tractor-status
                    ${stateClass}
                ">
                    ${rotorEscapeHTML(availability)}
                </span>

            </div>

        `;

    });


    menu.innerHTML =
    html
    ||
    `
    <div class="rotor-driver-empty">
        No Drivers available.
    </div>
    `;

}


/* =========================================================
   EDIT BIDIRECTIONAL EXCLUSION

   Primary selected
   -> hidden from Secondary

   Secondary selected
   -> hidden from Primary
========================================================= */

function refreshEditRotorAssignmentMenus(){

    const primaryTractorID =
    String(
        editRotorTractorID?.value
        ||
        ""
    );


    const secondaryTractorID =
    String(
        editRotorSecondaryTractorID?.value
        ||
        ""
    );


    const primaryDriverID =
    String(
        editRotorPrimaryDriverID?.value
        ||
        ""
    );


    const secondaryDriverID =
    String(
        editRotorSecondaryDriverID?.value
        ||
        ""
    );


    renderEditRotorTractorMenu(

        editRotorTractorMenu,

        editRotorTractors.filter(
        function(tractor){

            return String(
                tractor.tractor_id
                ||
                ""
            )
            !==
            secondaryTractorID;

        })

    );


    renderEditRotorTractorMenu(

        editRotorSecondaryTractorMenu,

        editRotorTractors.filter(
        function(tractor){

            return String(
                tractor.tractor_id
                ||
                ""
            )
            !==
            primaryTractorID;

        })

    );


    renderEditRotorDriverMenu(

        editRotorPrimaryDriverMenu,

        editRotorDrivers.filter(
        function(driver){

            return String(
                driver.driver_id
                ||
                ""
            )
            !==
            secondaryDriverID;

        })

    );


    renderEditRotorDriverMenu(

        editRotorSecondaryDriverMenu,

        editRotorDrivers.filter(
        function(driver){

            return String(
                driver.driver_id
                ||
                ""
            )
            !==
            primaryDriverID;

        })

    );

}


/* =========================================================
   LOAD EDIT ASSIGNMENT AVAILABILITY
========================================================= */

async function loadEditRotorAssignmentAvailability(){

    const rentalDate =
    String(
        editRotorDate?.value
        ||
        ""
    );


    const rotorID =
    parseInt(
        editRotorID?.value
        ||
        "0",
        10
    );


    if(
        rentalDate === ""
        ||
        rotorID <= 0
    ){

        return false;

    }


    const requestID =
    ++editRotorAvailabilityRequest;


    try{


        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getEditRotorTractorAvailability(
                rentalDate,
                rotorID
            ),

            getEditRotorDriverAvailability(
                rentalDate,
                rotorID
            )

        ]);


        if(
            requestID
            !==
            editRotorAvailabilityRequest
        ){

            return false;

        }


        editRotorTractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        editRotorDrivers =

        Array.isArray(
            driverData.drivers
        )

        ?

        driverData.drivers

        :

        [];


        refreshEditRotorAssignmentMenus();


        return true;

    }
    catch(error){


        console.error(
            "EDIT ROTOR AVAILABILITY ERROR:",
            error
        );


        Swal.fire({

            icon:
            "error",

            title:
            "Unable to Load Availability",

            text:
            error.message
            ||
            "Unable to load Rotor Tractor and Driver availability.",

            confirmButtonColor:
            "#B71C1C"

        });


        return false;

    }

}


/* =========================================================
   LIFECYCLE
========================================================= */

function applyEditRotorLifecycle(
    record
){

    const status =
    String(
        record?.status
        ||
        ""
    )
    .trim();


    const rentalDate =
    String(
        record?.rental_date
        ||
        ""
    )
    .trim();


    const selectedRentalDate =
    String(
        editRotorDate?.value
        ||
        rentalDate
    )
    .trim();


    const dateChanged =

    selectedRentalDate !== ""
    &&
    rentalDate !== ""
    &&
    selectedRentalDate !== rentalDate;


    const today =
    getRotorTodayKey();


    const isPending =
    status ===
    "Pending";


    const isOngoing =
    status ===
    "Ongoing";


    const isDone =
    status ===
    "Done";

    const currentBalance =
    Number(
        record?.balance
    ) || 0;


    const hasOutstandingBalance =
    currentBalance > 0.009;


    const isPast =
    rentalDate !== ""
    &&
    rentalDate < today;


    const isMissed =
    isPending
    &&
    isPast;


    const isDelayed =
    isOngoing
    &&
    isPast;


    const needsReschedule =
    (
        isMissed
        ||
        isDelayed
    )
    &&
    !dateChanged;


    const rescheduleCount =
    parseInt(
        record?.reschedule_count,
        10
    )
    ||
    0;


    let lockDate =
    true;


    let lockPrimary =
    true;


    let lockSecondary =
    true;


    let lockSave =
    true;


    let hint =
    "This Rotor rental is read-only.";


    /* =====================================================
       PENDING
    ===================================================== */

    if(isPending){

        lockDate =
        false;


        lockPrimary =
        false;


        lockSecondary =
        false;


        lockSave =
        false;


        if(isMissed){

            hint =
            "Missed rental: reschedule it to today or a future date. Status will remain Pending.";

        }
        else if(
            rescheduleCount > 0
        ){

            hint =
            "Rescheduled rental: schedule and assignments may still be updated while Pending.";

        }
        else{

            hint =
            "Pending rental: schedule and assignments may still be updated.";

        }

    }


    /* =====================================================
       ONGOING
    ===================================================== */

    else if(isOngoing){

            if(isDelayed){

        lockDate =
        false;


        /*
        Delayed Ongoing:
        - Rental Date may be changed
        - Primary stays locked while still on
            the original Delayed date
        - Primary unlocks after a new date
            is actually selected
        - Secondary remains editable
        */

        lockPrimary =
        !dateChanged;


        lockSecondary =
        false;


        lockSave =
        false;


        hint =

        dateChanged

        ?

        "Rescheduling delayed rental: Primary Assignment may now be updated if needed. Saving will reset the status to Pending."

        :

        "Delayed rental: select a new Rental Date to reschedule. Primary Assignment stays locked until the date is changed.";

    }
        else{

            lockDate =
            true;


            lockPrimary =
            true;


            lockSecondary =
            false;


            lockSave =
            false;


            hint =
            "Ongoing rental: Rental Date and Primary Assignment are locked. Secondary Assignment may still be adjusted.";

        }

    }


    /* =====================================================
       DONE
    ===================================================== */

    else if(isDone){

        lockDate =
        true;


        lockPrimary =
        true;


        lockSecondary =
        true;


        lockSave =
        !hasOutstandingBalance;


        hint =

        hasOutstandingBalance

        ?

        "Completed rental: schedule and assignments are locked. Additional payment may still be added until the balance is fully paid."

        :

        "Completed rental: this record is fully paid and read-only.";

    }


    if(editRotorDateBtn){

        editRotorDateBtn.disabled =
        lockDate;


        editRotorDateBtn
        .classList
        .toggle(
            "rotor-reschedule-needed",
            needsReschedule
        );

    }


    if(editRotorTractorBtn){

        editRotorTractorBtn.disabled =
        lockPrimary;

    }


    if(editRotorPrimaryDriverBtn){

        editRotorPrimaryDriverBtn.disabled =
        lockPrimary;

    }


    if(editRotorSecondaryToggle){

        editRotorSecondaryToggle.disabled =
        lockSecondary;


        editRotorSecondaryToggle.style.display =

        isDone

        ?

        "none"

        :

        "";

    }


    if(editRotorSecondaryTractorBtn){

        editRotorSecondaryTractorBtn.disabled =
        lockSecondary;

    }


    if(editRotorSecondaryDriverBtn){

        editRotorSecondaryDriverBtn.disabled =
        lockSecondary;

    }


    if(editRotorLifecycleHint){

        const text =
        editRotorLifecycleHint
        .querySelector(
            "span"
        );


        if(text){

            text.textContent =
            hint;

        }

    }


    if(updateRotorBtn){

        updateRotorBtn.disabled =
        lockSave;


        updateRotorBtn.textContent =

        isDone

        ?

        (
            hasOutstandingBalance

            ?

            "SAVE PAYMENT"

            :

            "COMPLETED"
        )

        :

        "SAVE CHANGES";

    }

}


/* =========================================================
   SECONDARY TOGGLE
========================================================= */

editRotorSecondaryToggle
?.addEventListener(
    "click",
    async function(event){


        event.preventDefault();
        event.stopPropagation();


        if(
            editRotorSecondaryToggle.disabled
        ){

            return;

        }


        const isOpen =
        editRotorSecondaryAssignment
        ?.classList
        .contains(
            "is-open"
        );


        if(!isOpen){

            openEditRotorSecondaryUI();

            refreshEditRotorAssignmentMenus();

            return;

        }


        const hasSecondary =

        String(
            editRotorSecondaryTractorID?.value
            ||
            ""
        ) !== ""

        ||

        String(
            editRotorSecondaryDriverID?.value
            ||
            ""
        ) !== "";


        if(hasSecondary){

            const result =
            await Swal.fire({

                icon:
                "question",

                title:
                "Remove Secondary Assignment?",

                text:
                "The selected Secondary Tractor and Driver will both be cleared.",

                showCancelButton:
                true,

                confirmButtonText:
                "Remove",

                cancelButtonText:
                "Cancel",

                confirmButtonColor:
                "#B71C1C",

                cancelButtonColor:
                "#8CA15B",

                reverseButtons:
                true

            });


            if(
                !result.isConfirmed
            ){

                return;

            }

        }


        clearEditRotorSecondaryAssignment();

        resetEditRotorSecondaryUI();

    }
);


/* =========================================================
   MENU BUTTON
========================================================= */

function setupEditRotorAssignmentButton(
    button,
    menu
){

    if(
        !button
        ||
        !menu
    ){

        return;

    }


    button.addEventListener(
        "click",
        function(event){


            event.preventDefault();
            event.stopPropagation();


            if(button.disabled){

                return;

            }


            const currentlyOpen =
            menu.classList.contains(
                "show"
            );


            closeEditRotorAssignmentMenus();


            editRotorCalendar
            ?.classList
            .remove(
                "show"
            );


            if(!currentlyOpen){

                menu.classList.add(
                    "show"
                );

            }

        }
    );

}


setupEditRotorAssignmentButton(
    editRotorTractorBtn,
    editRotorTractorMenu
);


setupEditRotorAssignmentButton(
    editRotorPrimaryDriverBtn,
    editRotorPrimaryDriverMenu
);


setupEditRotorAssignmentButton(
    editRotorSecondaryTractorBtn,
    editRotorSecondaryTractorMenu
);


setupEditRotorAssignmentButton(
    editRotorSecondaryDriverBtn,
    editRotorSecondaryDriverMenu
);


/* =========================================================
   PRIMARY TRACTOR SELECT
========================================================= */

editRotorTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-tractor-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available !== "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Tractor Unavailable",

                text:
                "This tractor is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const id =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(id <= 0){

            return;

        }


        if(
            String(
                editRotorSecondaryTractorID?.value
                ||
                ""
            )
            ===
            String(
                id
            )
        ){

            return;

        }


        editRotorTractorID.value =
        String(
            id
        );


        editRotorTractorNo.value =
        item.dataset.tractorNo
        ||
        "";


        editRotorTractorText.textContent =

        (
            item.dataset.tractorNo
            ||
            ""
        )

        +

        " - "

        +

        (
            item.dataset.tractorName
            ||
            ""
        );


        closeEditRotorAssignmentMenus();

        refreshEditRotorAssignmentMenus();

    }
);


/* =========================================================
   SECONDARY TRACTOR SELECT
========================================================= */

editRotorSecondaryTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-tractor-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available !== "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Tractor Unavailable",

                text:
                "This tractor is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const id =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(id <= 0){

            return;

        }


        if(
            String(
                editRotorTractorID?.value
                ||
                ""
            )
            ===
            String(
                id
            )
        ){

            return;

        }


        editRotorSecondaryTractorID.value =
        String(
            id
        );


        editRotorSecondaryTractorNo.value =
        item.dataset.tractorNo
        ||
        "";


        editRotorSecondaryTractorText.textContent =

        (
            item.dataset.tractorNo
            ||
            ""
        )

        +

        " - "

        +

        (
            item.dataset.tractorName
            ||
            ""
        );


        closeEditRotorAssignmentMenus();

        refreshEditRotorAssignmentMenus();

    }
);


/* =========================================================
   PRIMARY DRIVER SELECT
========================================================= */

editRotorPrimaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-driver-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available !== "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Driver Unavailable",

                text:
                "This driver is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const id =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(id <= 0){

            return;

        }


        if(
            String(
                editRotorSecondaryDriverID?.value
                ||
                ""
            )
            ===
            String(
                id
            )
        ){

            return;

        }


        editRotorPrimaryDriverID.value =
        String(
            id
        );


        editRotorPrimaryDriverText.textContent =
        item.dataset.driverName
        ||
        "";


        closeEditRotorAssignmentMenus();

        refreshEditRotorAssignmentMenus();

    }
);


/* =========================================================
   SECONDARY DRIVER SELECT
========================================================= */

editRotorSecondaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".rotor-driver-item"
        );


        if(!item){

            return;

        }


        event.preventDefault();
        event.stopPropagation();


        if(
            item.dataset.available !== "1"
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Driver Unavailable",

                text:
                "This driver is not available on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        const id =
        parseInt(
            item.dataset.resourceId
            ||
            "0",
            10
        );


        if(id <= 0){

            return;

        }


        if(
            String(
                editRotorPrimaryDriverID?.value
                ||
                ""
            )
            ===
            String(
                id
            )
        ){

            return;

        }


        editRotorSecondaryDriverID.value =
        String(
            id
        );


        editRotorSecondaryDriverText.textContent =
        item.dataset.driverName
        ||
        "";


        closeEditRotorAssignmentMenus();

        refreshEditRotorAssignmentMenus();

    }
);


/* =========================================================
   CALENDAR AVAILABILITY
========================================================= */

async function loadEditRotorCalendarAvailability(){

    const rotorID =
    parseInt(
        editRotorID?.value
        ||
        "0",
        10
    );


    if(rotorID <= 0){

        return false;

    }


    const year =
    editRotorCalendarView
    .getFullYear();


    const month =
    editRotorCalendarView
    .getMonth()
    +
    1;


    const viewKey =

    year
    +
    "-"
    +
    rotorPadNumber(
        month
    );


    const requestID =
    ++editRotorCalendarRequest;


    try{


        const response =
        await fetch(

            "get_rotor_booked_dates.php"
            +
            "?year="
            +
            encodeURIComponent(
                year
            )
            +
            "&month="
            +
            encodeURIComponent(
                month
            )
            +
            "&rental_id="
            +
            encodeURIComponent(
                rotorID
            )
            +
            "&primary_tractor_id="
            +
            encodeURIComponent(
                editRotorTractorID?.value
                ||
                ""
            )
            +
            "&secondary_tractor_id="
            +
            encodeURIComponent(
                editRotorSecondaryTractorID?.value
                ||
                ""
            )
            +
            "&primary_driver_id="
            +
            encodeURIComponent(
                editRotorPrimaryDriverID?.value
                ||
                ""
            )
            +
            "&secondary_driver_id="
            +
            encodeURIComponent(
                editRotorSecondaryDriverID?.value
                ||
                ""
            )
            +
            "&_="
            +
            Date.now(),

            {
                cache:
                "no-store"
            }

        );


        const result =
        await response.json();


        if(
            !response.ok
            ||
            !result.success
        ){

            throw new Error(
                result.message
                ||
                "Unable to load Rotor calendar."
            );

        }


        if(
            requestID
            !==
            editRotorCalendarRequest
        ){

            return false;

        }


        editRotorCalendarAvailability =

        result.data?.dates
        &&
        typeof result.data.dates
        ===
        "object"

        ?

        result.data.dates

        :

        {};


        editRotorCalendarAvailabilityKey =
        viewKey;


        renderEditRotorCalendar();


        return true;

    }
    catch(error){


        console.error(
            "EDIT ROTOR CALENDAR ERROR:",
            error
        );


        editRotorCalendarAvailability =
        {};


        editRotorCalendarAvailabilityKey =
        viewKey;


        renderEditRotorCalendar();


        return false;

    }

}


/* =========================================================
   EDIT CALENDAR
========================================================= */

function renderEditRotorCalendar(){

    if(
        !editRotorCalendarMonth
        ||
        !editRotorCalendarDays
    ){

        return;

    }


    const year =
    editRotorCalendarView
    .getFullYear();


    const month =
    editRotorCalendarView
    .getMonth();


    const viewKey =

    year
    +
    "-"
    +
    rotorPadNumber(
        month + 1
    );


    editRotorCalendarMonth.textContent =
    editRotorCalendarView
    .toLocaleDateString(

        "en-US",

        {
            month:
            "long",

            year:
            "numeric"
        }

    );


    editRotorCalendarDays.innerHTML =
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
            "div"
        );


        empty.className =
        "rotor-calendar-empty";


        editRotorCalendarDays
        .appendChild(
            empty
        );

    }


    const today =
    getRotorTodayKey();


    const availabilityReady =

    editRotorCalendarAvailabilityKey
    ===
    viewKey;


    for(
        let day = 1;
        day <= daysInMonth;
        day++
    ){

        const dateKey =
        rotorBuildDateKey(
            year,
            month,
            day
        );


        const button =
        document.createElement(
            "button"
        );


        button.type =
        "button";


        button.className =
        "rotor-calendar-day";


        button.textContent =
        String(
            day
        );


        button.dataset.date =
        dateKey;


        const availability =

        availabilityReady

        ?

        (
            editRotorCalendarAvailability[
                dateKey
            ]
            ||
            null
        )

        :

        null;


        /*
           Edit rescheduling never creates
           a new schedule in the past.

           Existing Missed / Delayed record may still
           DISPLAY its past date, but any new selected
           date must be today or future.
        */

        if(
            dateKey < today
        ){

            button.disabled =
            true;


            button.classList.add(
                "rotor-calendar-historical"
            );

        }


        const calendarStatus =

        availability?.assignment_status
        ||
        availability?.status
        ||
        "";


        if(
            calendarStatus
            ===
            "Fully Booked"
        ){

            button.disabled =
            true;


            button.classList.add(
                "rotor-calendar-booked"
            );


            button.title =
            "Fully booked. No valid Rotor Tractor and Driver pair is available on this date.";

        }
        else if(
            calendarStatus
            ===
            "Limited"
        ){

            button.classList.add(
                "rotor-calendar-limited"
            );


            button.title =
            "Limited availability. One or more currently assigned Tractor or Driver is unavailable on this date and may need replacement.";

        }
        else if(
            calendarStatus
            ===
            "Available"
        ){

            button.classList.add(
                "rotor-calendar-available"
            );


            button.title =
            "Current assignment is available on this date.";

        }


        if(
            dateKey === today
        ){

            button.classList.add(
                "rotor-calendar-today"
            );

        }


        if(
            editRotorDate?.value
            ===
            dateKey
        ){

            button.classList.add(
                "rotor-calendar-selected"
            );

        }


        button.addEventListener(
            "click",
            async function(event){


                event.preventDefault();
                event.stopPropagation();


                if(this.disabled){

                    return;

                }


                const selectedDate =
                this.dataset.date;


                if(
                    !selectedDate
                    ||
                    selectedDate < today
                ){

                    return;

                }


                const oldPrimaryTractorID =
                String(
                    editRotorTractorID?.value
                    ||
                    ""
                );


                const oldPrimaryDriverID =
                String(
                    editRotorPrimaryDriverID?.value
                    ||
                    ""
                );


                const oldSecondaryTractorID =
                String(
                    editRotorSecondaryTractorID?.value
                    ||
                    ""
                );


                const oldSecondaryDriverID =
                String(
                    editRotorSecondaryDriverID?.value
                    ||
                    ""
                );


                editRotorDate.value =
                selectedDate;


                editRotorDateText.textContent =
                rotorFormatDate(
                    selectedDate
                );


                editRotorCalendar
                ?.classList
                .remove(
                    "show"
                );


                const loaded =
                await loadEditRotorAssignmentAvailability();


                if(!loaded){

                    return;

                }


                /* =========================================
                   KEEP ASSIGNMENTS ONLY IF STILL AVAILABLE
                ========================================= */

                const primaryTractorValid =
                editRotorTractors.some(
                    item =>
                    String(
                        item.tractor_id
                    )
                    ===
                    oldPrimaryTractorID
                    &&
                    item.available
                    ===
                    true
                );


                const primaryDriverValid =
                editRotorDrivers.some(
                    item =>
                    String(
                        item.driver_id
                    )
                    ===
                    oldPrimaryDriverID
                    &&
                    item.available
                    ===
                    true
                );


                const secondaryTractorValid =

                oldSecondaryTractorID === ""

                ||

                editRotorTractors.some(
                    item =>
                    String(
                        item.tractor_id
                    )
                    ===
                    oldSecondaryTractorID
                    &&
                    item.available
                    ===
                    true
                );


                const secondaryDriverValid =

                oldSecondaryDriverID === ""

                ||

                editRotorDrivers.some(
                    item =>
                    String(
                        item.driver_id
                    )
                    ===
                    oldSecondaryDriverID
                    &&
                    item.available
                    ===
                    true
                );


                let assignmentCleared =
                false;


                if(
                    !primaryTractorValid
                ){

                    editRotorTractorID.value =
                    "";


                    editRotorTractorNo.value =
                    "";


                    editRotorTractorText.textContent =
                    "Select Primary Tractor";


                    assignmentCleared =
                    true;

                }


                if(
                    !primaryDriverValid
                ){

                    editRotorPrimaryDriverID.value =
                    "";


                    editRotorPrimaryDriverText.textContent =
                    "Select Primary Driver";


                    assignmentCleared =
                    true;

                }


                if(
                    !secondaryTractorValid
                    ||
                    !secondaryDriverValid
                ){

                    clearEditRotorSecondaryAssignment();

                    resetEditRotorSecondaryUI();


                    assignmentCleared =
                    true;

                }


                refreshEditRotorAssignmentMenus();

                renderEditRotorCalendar();


                /*
                Re-apply lifecycle after changing Rental Date.

                Delayed Ongoing:
                - original date = Primary locked
                - new date = Primary editable
                */

                if(
                    editRotorLoadedRecord
                ){

                    applyEditRotorLifecycle(
                        editRotorLoadedRecord
                    );

                }


                if(assignmentCleared){

                    Swal.fire({

                        icon:
                        "info",

                        title:
                        "Assignment Updated",

                        text:
                        "One or more assigned Tractor or Driver is unavailable on the new date. Please select a replacement.",

                        confirmButtonColor:
                        "#8CA15B"

                    });

                }

            }
        );


        editRotorCalendarDays
        .appendChild(
            button
        );

    }

}


/* =========================================================
   EDIT CALENDAR POSITION
========================================================= */

function positionEditRotorCalendar(){

    if(
        !editRotorCalendar
        ||
        !editRotorDateBtn
    ){

        return;

    }


    const buttonRect =
    editRotorDateBtn
    .getBoundingClientRect();


    const calendarRect =
    editRotorCalendar
    .getBoundingClientRect();


    const margin =
    12;


    const gap =
    8;


    let left =
    buttonRect.left;


    let top =
    buttonRect.bottom
    +
    gap;


    if(
        left
        +
        calendarRect.width
        >
        window.innerWidth
        -
        margin
    ){

        left =
        window.innerWidth
        -
        calendarRect.width
        -
        margin;

    }


    if(
        left < margin
    ){

        left =
        margin;

    }


    if(
        top
        +
        calendarRect.height
        >
        window.innerHeight
        -
        margin
    ){

        top =
        buttonRect.top
        -
        calendarRect.height
        -
        gap;

    }


    if(
        top < margin
    ){

        top =
        margin;

    }


    editRotorCalendar.style.left =
    Math.round(
        left
    )
    +
    "px";


    editRotorCalendar.style.top =
    Math.round(
        top
    )
    +
    "px";

}


/* =========================================================
   DATE BUTTON
========================================================= */

editRotorDateBtn
?.addEventListener(
    "click",
    async function(event){


        event.preventDefault();
        event.stopPropagation();


        if(
            editRotorDateBtn.disabled
        ){

            return;

        }


        closeEditRotorAssignmentMenus();


        const date =
        String(
            editRotorDate?.value
            ||
            ""
        );


        if(date){

            const parts =
            date.split(
                "-"
            );


            editRotorCalendarView =
            new Date(

                Number(
                    parts[0]
                ),

                Number(
                    parts[1]
                ) - 1,

                1

            );

        }


        await loadEditRotorCalendarAvailability();


        editRotorCalendar
        ?.classList
        .toggle(
            "show"
        );


        if(
            editRotorCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            requestAnimationFrame(
                positionEditRotorCalendar
            );

        }

    }
);


/* =========================================================
   PREVIOUS MONTH
========================================================= */

editRotorCalendarPrev
?.addEventListener(
    "click",
    async function(event){


        event.preventDefault();
        event.stopPropagation();


        editRotorCalendarView =
        new Date(

            editRotorCalendarView
            .getFullYear(),

            editRotorCalendarView
            .getMonth() - 1,

            1

        );


        await loadEditRotorCalendarAvailability();

        requestAnimationFrame(
            positionEditRotorCalendar
        );

    }
);


/* =========================================================
   NEXT MONTH
========================================================= */

editRotorCalendarNext
?.addEventListener(
    "click",
    async function(event){


        event.preventDefault();
        event.stopPropagation();


        editRotorCalendarView =
        new Date(

            editRotorCalendarView
            .getFullYear(),

            editRotorCalendarView
            .getMonth() + 1,

            1

        );


        await loadEditRotorCalendarAvailability();

        requestAnimationFrame(
            positionEditRotorCalendar
        );

    }
);


/* =========================================================
   RESET EDIT MODAL
========================================================= */

function resetEditRotorModal(){

    editRotorLoadedRecord =
    null;

    renderEditRotorPaymentHistory([], 0);

    editRotorAvailabilityRequest++;


    editRotorCalendarRequest++;


    editRotorForm
    ?.reset();

    clearEditRotorInlineErrors();


    if(editRotorID){

        editRotorID.value =
        "";

    }


    if(editRotorTransactionID){

        editRotorTransactionID.value =
        "";

    }


    if(editRotorTransactionBadge){

        editRotorTransactionBadge.textContent =
        "RTR-0000";

    }


    if(editRotorAffiliate){

        editRotorAffiliate.value =
        "";

    }


    if(editRotorAffiliateText){

        editRotorAffiliateText.textContent =
        "—";

    }


    if(editRotorMemberID){

        editRotorMemberID.value =
        "";

    }


    if(editRotorDate){

        editRotorDate.value =
        "";

    }


    if(editRotorDateText){

        editRotorDateText.textContent =
        "Select Rental Date";

    }


    if(editRotorTractorID){

        editRotorTractorID.value =
        "";

    }


    if(editRotorTractorNo){

        editRotorTractorNo.value =
        "";

    }


    if(editRotorTractorText){

        editRotorTractorText.textContent =
        "Select Primary Tractor";

    }


    if(editRotorPrimaryDriverID){

        editRotorPrimaryDriverID.value =
        "";

    }


    if(editRotorPrimaryDriverText){

        editRotorPrimaryDriverText.textContent =
        "Select Primary Driver";

    }


    clearEditRotorSecondaryAssignment();

    resetEditRotorSecondaryUI();


    if(editRotorStatus){

        editRotorStatus.value =
        "";

    }


    if(editRotorStatusText){

        editRotorStatusText.textContent =
        "—";

    }


    if(editRotorRemarksText){

        editRotorRemarksText.textContent =
        "—";

    }


    if(editRotorPricePerHectare){

        editRotorPricePerHectare.value =
        "0.00";

    }


    if(editRotorDiscount){

        editRotorDiscount.value =
        "0.00";

    }


    if(editRotorTotal){

        editRotorTotal.value =
        "0.00";

    }

    if(editRotorAmountPaid){

    editRotorAmountPaid.value =
    "0.00";

}


if(editRotorBalance){

    editRotorBalance.value =
    "0.00";

}


if(editRotorAdditionalPayment){

    editRotorAdditionalPayment.value =
    "";

    editRotorAdditionalPayment.disabled =
    false;

}


    editRotorTractors =
    [];


    editRotorDrivers =
    [];


    editRotorCalendarAvailability =
    {};


    editRotorCalendarAvailabilityKey =
    "";


    closeEditRotorAssignmentMenus();


    editRotorCalendar
    ?.classList
    .remove(
        "show"
    );

}


/* =========================================================
   POPULATE EDIT MODAL
========================================================= */

function populateEditRotorModal(
    record
){

    resetEditRotorModal();


    editRotorLoadedRecord =
    {
        ...record
    };


    /* RECORD */

    editRotorID.value =
    record.rotor_id
    ??
    "";


    editRotorTransactionID.value =
    record.transaction_id
    ??
    "";


    editRotorTransactionBadge.textContent =
    record.transaction_id
    ||
    "RTR";


    /* CUSTOMER */

    editRotorAffiliate.value =
    record.affiliate
    ??
    "";


    editRotorAffiliateText.textContent =
    record.affiliate
    ||
    "—";


    editRotorMemberID.value =
    record.member_id
    ??
    "";


    editRotorCustomerName.value =
    record.customer_name
    ??
    "";


    editRotorLocation.value =
    record.location
    ??
    "";


    editRotorArea.value =
    record.hectare
    ??
    "";


    /* DATE */

    const rentalDate =
    String(
        record.rental_date
        ||
        ""
    );


    editRotorDate.value =
    rentalDate;


    editRotorDateText.textContent =

    rentalDate

    ?

    rotorFormatDate(
        rentalDate
    )

    :

    "Select Rental Date";


    /* PRIMARY TRACTOR */

    editRotorTractorID.value =
    record.tractor_id
    ??
    "";


    editRotorTractorNo.value =
    record.tractor_no
    ??
    "";


    editRotorTractorText.textContent =

    (
        record.tractor_no
        ||
        ""
    )

    +

    (
        record.tractor_name

        ?

        " - "
        +
        record.tractor_name

        :

        ""
    );


    if(
        editRotorTractorText.textContent
        .trim()
        ===
        ""
    ){

        editRotorTractorText.textContent =
        "Select Primary Tractor";

    }


    /* PRIMARY DRIVER */

    editRotorPrimaryDriverID.value =
    record.primary_driver_id
    ??
    "";


    editRotorPrimaryDriverText.textContent =
    record.operator
    ||
    "Select Primary Driver";


    /* SECONDARY */

    editRotorSecondaryTractorID.value =
    record.secondary_tractor_id
    ??
    "";


    editRotorSecondaryTractorNo.value =
    record.secondary_tractor_no
    ??
    "";


    editRotorSecondaryDriverID.value =
    record.secondary_driver_id
    ??
    "";


    const secondaryTractorLabel =

    (
        record.secondary_tractor_no
        ||
        ""
    )

    +

    (
        record.secondary_tractor_name

        ?

        " - "
        +
        record.secondary_tractor_name

        :

        ""
    );


    editRotorSecondaryTractorText.textContent =

    secondaryTractorLabel.trim()
    !==
    ""

    ?

    secondaryTractorLabel

    :

    "Select Secondary Tractor";


    editRotorSecondaryDriverText.textContent =
    record.secondary_operator
    ||
    "Select Secondary Driver";


    if(
        String(
            record.secondary_tractor_id
            ??
            ""
        ) !== ""

        ||

        String(
            record.secondary_driver_id
            ??
            ""
        ) !== ""
    ){

        openEditRotorSecondaryUI();

    }
    else{

        resetEditRotorSecondaryUI();

    }


    /* FINANCIAL */

    editRotorPricePerHectare.value =
    editRotorMoneyValue(
        record.price
    );


    editRotorDiscount.value =
    editRotorMoneyValue(
        record.discounted_price
    );


    editRotorTotal.value =
editRotorMoneyValue(
    record.total_price
);


if(editRotorAmountPaid){

    editRotorAmountPaid.value =
    editRotorMoneyValue(
        record.amount_paid
    );

}


if(editRotorBalance){

    editRotorBalance.value =
    editRotorMoneyValue(
        record.balance
    );

}


if(editRotorAdditionalPayment){

    editRotorAdditionalPayment.value =
    "";


    const currentBalance =
    Number(
        record.balance
    ) || 0;


    editRotorAdditionalPayment.disabled =

    currentBalance <= 0.009;

}

renderEditRotorPaymentHistory(
    record.payment_history,
    record.amount_paid
);

/* STATUS */

    editRotorStatus.value =
    record.status
    ??
    "";


    editRotorStatusText.textContent =
    record.status
    ||
    "—";


    editRotorRemarksText.textContent =
    record.remarks
    ||
    "—";


    /* CALENDAR VIEW */

    if(rentalDate){

        const parts =
        rentalDate.split(
            "-"
        );


        editRotorCalendarView =
        new Date(

            Number(
                parts[0]
            ),

            Number(
                parts[1]
            ) - 1,

            1

        );

    }


    applyEditRotorLifecycle(
        record
    );

}


/* =========================================================
   LOAD ROTOR FOR EDIT
========================================================= */

async function loadRotorForEdit(
    rotorID
){

    const numericID =
    parseInt(
        rotorID,
        10
    );


    if(
        numericID <= 0
    ){

        Swal.fire({

            icon:
            "error",

            title:
            "Invalid Rental",

            text:
            "Unable to identify the selected Rotor rental.",

            confirmButtonColor:
            "#B71C1C"

        });


        return;

    }


    try{


        const response =
        await fetch(

            "get_rotor.php?id="
            +
            encodeURIComponent(
                numericID
            ),

            {
                cache:
                "no-store"
            }

        );


        let result;


        try{

            result =
            await response.json();

        }
        catch(error){

            throw new Error(
                "Invalid server response."
            );

        }


        if(
            !response.ok
            ||
            !result.success
            ||
            !result.data
        ){

            throw new Error(
                result.message
                ||
                "Unable to load the selected Rotor rental."
            );

        }


        populateEditRotorModal(
            result.data
        );


        /*
           Availability is loaded before the user
           starts changing assignments.
        */

        await loadEditRotorAssignmentAvailability();


        refreshEditRotorAssignmentMenus();


        const modal =
        bootstrap.Modal
        .getOrCreateInstance(
            editRotorModalElement
        );


        modal.show();

    }
    catch(error){


        console.error(
            "LOAD ROTOR EDIT ERROR:",
            error
        );


        Swal.fire({

            icon:
            "error",

            title:
            "Unable to Open Rental",

            text:
            error.message
            ||
            "Unable to load the Rotor rental.",

            confirmButtonColor:
            "#B71C1C"

        });

    }

}


/* =========================================================
   EDIT BUTTON
========================================================= */

document.addEventListener(
    "click",
    function(event){


        const button =
        event.target.closest(
            ".rotor-edit-btn"
        );


        if(!button){

            return;

        }


        event.preventDefault();


        const rotorID =
        button.dataset.id
        ||
        "";


        loadRotorForEdit(
            rotorID
        );

    }
);

/* =========================================================
   EDIT ROTOR INLINE VALIDATION
========================================================= */

function clearEditRotorInlineErrors(){

    document
    .querySelectorAll(
        "#editRotorModal .is-invalid"
    )
    .forEach(
    function(control){

        control
        .classList
        .remove(
            "is-invalid"
        );

        control
        .removeAttribute(
            "aria-invalid"
        );

    });


    document
    .querySelectorAll(
        "#editRotorModal .rotor-field-error"
    )
    .forEach(
    function(error){

        error.remove();

    });

}


/* =========================================================
   VALIDATE EDIT ROTOR FORM
========================================================= */

function validateEditRotorForm(
    context
){

    let valid =
    true;


    let firstInvalid =
    null;


    function markInvalid(
        control,
        message
    ){

        if(
            !control
        ){

            return;

        }


        valid =
        false;


        setRotorInlineError(
            control,
            message
        );


        if(
            !firstInvalid
        ){

            firstInvalid =
            control;

        }

    }


    const rentalDate =
    context.rentalDate;


    const primaryTractorID =
    context.primaryTractorID;


    const primaryDriverID =
    context.primaryDriverID;


    const secondaryTractorID =
    context.secondaryTractorID;


    const secondaryDriverID =
    context.secondaryDriverID;


    /* ==========================================
       RENTAL DATE
    ========================================== */

    if(
        rentalDate === ""
    ){

        markInvalid(

            editRotorDateBtn,

            "Please select the Rental Date."

        );

    }


    if(
        context.originalStatus ===
        "Pending"
        &&
        rentalDate !== ""
        &&
        rentalDate <
        context.today
    ){

        markInvalid(

            editRotorDateBtn,

            "A Pending rental must be scheduled for today or a future date."

        );

    }


    if(
        context.wasDelayed
        &&
        context.dateChanged
        &&
        rentalDate !== ""
        &&
        rentalDate <
        context.today
    ){

        markInvalid(

            editRotorDateBtn,

            "A Delayed rental can only be rescheduled for today or a future date."

        );

    }


    if(
        context.wasDelayed
        &&
        !context.dateChanged
        &&
        context.primaryChanged
    ){

        markInvalid(

            editRotorDateBtn,

            "Select a new Rental Date when changing the Primary Assignment of a Delayed rental."

        );

    }


    /* ==========================================
       PRIMARY ASSIGNMENT
    ========================================== */

    if(
        primaryTractorID <= 0
    ){

        markInvalid(

            editRotorTractorBtn,

            "Please select a Primary Rotor Tractor."

        );

    }


    if(
        primaryDriverID <= 0
    ){

        markInvalid(

            editRotorPrimaryDriverBtn,

            "Please select a Primary Driver."

        );

    }


    /* ==========================================
       SECONDARY ASSIGNMENT
    ========================================== */

    const secondaryOpen =

    editRotorSecondaryAssignment
    ?.classList
    .contains(
        "is-open"
    )
    ===
    true;


    const hasSecondaryTractor =
    secondaryTractorID > 0;


    const hasSecondaryDriver =
    secondaryDriverID > 0;


    if(
        secondaryOpen
        ||
        hasSecondaryTractor
        ||
        hasSecondaryDriver
    ){

        if(
            !hasSecondaryTractor
        ){

            markInvalid(

                editRotorSecondaryTractorBtn,

                "Please select a Secondary Tractor."

            );

        }


        if(
            !hasSecondaryDriver
        ){

            markInvalid(

                editRotorSecondaryDriverBtn,

                "Please select a Secondary Driver."

            );

        }

    }


    /* ==========================================
       DUPLICATE ASSIGNMENT
    ========================================== */

    if(
        hasSecondaryTractor
        &&
        primaryTractorID > 0
        &&
        secondaryTractorID ===
        primaryTractorID
    ){

        markInvalid(

            editRotorSecondaryTractorBtn,

            "Secondary Tractor must be different from Primary Tractor."

        );

    }


    if(
        hasSecondaryDriver
        &&
        primaryDriverID > 0
        &&
        secondaryDriverID ===
        primaryDriverID
    ){

        markInvalid(

            editRotorSecondaryDriverBtn,

            "Secondary Driver must be different from Primary Driver."

        );

    }

    /* ==========================================
    ADDITIONAL PAYMENT
    ========================================== */

    if(
        !validateEditRotorAdditionalPayment()
    ){

        valid =
        false;


        if(
            !firstInvalid
        ){

            firstInvalid =
            editRotorAdditionalPayment;

        }

    }


    return {

        valid:
        valid,

        firstInvalid:
        firstInvalid

    };

}


/* =========================================================
   CLEAR EDIT ERROR AFTER VALID SELECTION
========================================================= */

document.addEventListener(

    "click",

    function(event){


        if(
            !event.target.closest(
                "#editRotorModal"
            )
        ){

            return;

        }


        const calendarDay =
        event.target.closest(
            "#editRotorCalendar .rotor-calendar-day"
        );


        if(
            calendarDay
            &&
            !calendarDay.disabled
        ){

            clearRotorInlineError(
                editRotorDateBtn
            );

        }


        const tractorItem =
        event.target.closest(
            ".rotor-tractor-item"
        );


        if(
            tractorItem
            &&
            !tractorItem
            .classList
            .contains(
                "rotor-tractor-disabled"
            )
        ){

            if(
                editRotorTractorMenu
                ?.contains(
                    tractorItem
                )
            ){

                clearRotorInlineError(
                    editRotorTractorBtn
                );

                clearRotorInlineError(
                    editRotorSecondaryTractorBtn
                );

            }


            if(
                editRotorSecondaryTractorMenu
                ?.contains(
                    tractorItem
                )
            ){

                clearRotorInlineError(
                    editRotorSecondaryTractorBtn
                );

                clearRotorInlineError(
                    editRotorTractorBtn
                );

            }

        }


        const driverItem =
        event.target.closest(
            ".rotor-driver-item"
        );


        if(
            driverItem
            &&
            !driverItem
            .classList
            .contains(
                "rotor-driver-disabled"
            )
        ){

            if(
                editRotorPrimaryDriverMenu
                ?.contains(
                    driverItem
                )
            ){

                clearRotorInlineError(
                    editRotorPrimaryDriverBtn
                );

                clearRotorInlineError(
                    editRotorSecondaryDriverBtn
                );

            }


            if(
                editRotorSecondaryDriverMenu
                ?.contains(
                    driverItem
                )
            ){

                clearRotorInlineError(
                    editRotorSecondaryDriverBtn
                );

                clearRotorInlineError(
                    editRotorPrimaryDriverBtn
                );

            }

        }


        if(
            event.target.closest(
                "#editRotorSecondaryToggle"
            )
        ){

            clearRotorInlineError(
                editRotorSecondaryTractorBtn
            );

            clearRotorInlineError(
                editRotorSecondaryDriverBtn
            );

        }

    },

    true

);


/* =========================================================
   FINAL EDIT AVAILABILITY CHECK
========================================================= */

async function validateEditRotorAvailability(){

    const rotorID =
    parseInt(
        editRotorID?.value
        ||
        "0",
        10
    );


    const rentalDate =
    String(
        editRotorDate?.value
        ||
        ""
    );


    if(
        rotorID <= 0
        ||
        rentalDate === ""
    ){

        return false;

    }


    try{


        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getEditRotorTractorAvailability(
                rentalDate,
                rotorID
            ),

            getEditRotorDriverAvailability(
                rentalDate,
                rotorID
            )

        ]);


        const tractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        const drivers =

        Array.isArray(
            driverData.drivers
        )

        ?

        driverData.drivers

        :

        [];


        /*
           Keep the Edit dropdowns synchronized
           with the latest availability response.
        */

        editRotorTractors =
        tractors;


        editRotorDrivers =
        drivers;


        refreshEditRotorAssignmentMenus();


        const primaryTractorID =
        parseInt(
            editRotorTractorID?.value
            ||
            "0",
            10
        );


        const primaryDriverID =
        parseInt(
            editRotorPrimaryDriverID?.value
            ||
            "0",
            10
        );


        const secondaryTractorID =
        parseInt(
            editRotorSecondaryTractorID?.value
            ||
            "0",
            10
        );


        const secondaryDriverID =
        parseInt(
            editRotorSecondaryDriverID?.value
            ||
            "0",
            10
        );


        let valid =
        true;


        let firstInvalid =
        null;


        function markUnavailable(
            control,
            message
        ){

            valid =
            false;


            setRotorInlineError(
                control,
                message
            );


            if(
                !firstInvalid
            ){

                firstInvalid =
                control;

            }

        }


        /* ==========================================
           PRIMARY TRACTOR
        ========================================== */

        const primaryTractor =
        tractors.find(
            item =>
            Number(
                item.tractor_id
            )
            ===
            primaryTractorID
        );


        if(
            !primaryTractor
            ||
            primaryTractor.available
            !==
            true
        ){

            markUnavailable(

                editRotorTractorBtn,

                "The selected Primary Tractor is no longer available. Please select a replacement."

            );

        }


        /* ==========================================
           PRIMARY DRIVER
        ========================================== */

        const primaryDriver =
        drivers.find(
            item =>
            Number(
                item.driver_id
            )
            ===
            primaryDriverID
        );


        if(
            !primaryDriver
            ||
            primaryDriver.available
            !==
            true
        ){

            markUnavailable(

                editRotorPrimaryDriverBtn,

                "The selected Primary Driver is no longer available. Please select a replacement."

            );

        }


        /* ==========================================
           SECONDARY TRACTOR
        ========================================== */

        if(
            secondaryTractorID > 0
        ){

            const secondaryTractor =
            tractors.find(
                item =>
                Number(
                    item.tractor_id
                )
                ===
                secondaryTractorID
            );


            if(
                !secondaryTractor
                ||
                secondaryTractor.available
                !==
                true
            ){

                markUnavailable(

                    editRotorSecondaryTractorBtn,

                    "The selected Secondary Tractor is no longer available. Please select a replacement."

                );

            }

        }


        /* ==========================================
           SECONDARY DRIVER
        ========================================== */

        if(
            secondaryDriverID > 0
        ){

            const secondaryDriver =
            drivers.find(
                item =>
                Number(
                    item.driver_id
                )
                ===
                secondaryDriverID
            );


            if(
                !secondaryDriver
                ||
                secondaryDriver.available
                !==
                true
            ){

                markUnavailable(

                    editRotorSecondaryDriverBtn,

                    "The selected Secondary Driver is no longer available. Please select a replacement."

                );

            }

        }


        if(
            !valid
        ){

            focusRotorInvalidField(
                firstInvalid
            );


            return false;

        }


        return true;

    }
    catch(error){


        console.error(
            "EDIT ROTOR FINAL AVAILABILITY ERROR:",
            error
        );


        /*
           This is a system/request failure,
           not an ordinary field validation.
           SweetAlert is appropriate here.
        */

        Swal.fire({

            icon:
            "error",

            title:
            "Unable to Verify Availability",

            text:
            error.message
            ||
            "Unable to verify the latest Tractor and Driver availability.",

            confirmButtonColor:
            "#B71C1C"

        });


        return false;

    }

}


/* =========================================================
   SAVE EDIT
========================================================= */

updateRotorBtn
?.addEventListener(
    "click",
    async function(){


        if(
            updateRotorBtn.disabled
        ){

            return;

        }


        const rotorID =
        parseInt(
            editRotorID?.value
            ||
            "0",
            10
        );


        const rentalDate =
        String(
            editRotorDate?.value
            ||
            ""
        )
        .trim();


        const primaryTractorID =
        parseInt(
            editRotorTractorID?.value
            ||
            "0",
            10
        );


        const primaryDriverID =
        parseInt(
            editRotorPrimaryDriverID?.value
            ||
            "0",
            10
        );


        const secondaryTractorID =
        parseInt(
            editRotorSecondaryTractorID?.value
            ||
            "0",
            10
        );


        const secondaryDriverID =
        parseInt(
            editRotorSecondaryDriverID?.value
            ||
            "0",
            10
        );


        const originalDate =
        String(
            editRotorLoadedRecord?.rental_date
            ||
            ""
        );


        const originalStatus =
        String(
            editRotorLoadedRecord?.status
            ||
            ""
        );


        const originalPrimaryTractorID =
        String(
            editRotorLoadedRecord?.tractor_id
            ??
            ""
        );


        const originalPrimaryDriverID =
        String(
            editRotorLoadedRecord?.primary_driver_id
            ??
            ""
        );


        const today =
        getRotorTodayKey();


        const wasMissed =

        originalStatus ===
        "Pending"

        &&

        originalDate !== ""

        &&

        originalDate < today;


        const wasDelayed =

        originalStatus ===
        "Ongoing"

        &&

        originalDate !== ""

        &&

        originalDate < today;


        const dateChanged =
        rentalDate
        !==
        originalDate;


        const primaryChanged =

        String(
            primaryTractorID
        )
        !==
        originalPrimaryTractorID

        ||

        String(
            primaryDriverID
        )
        !==
        originalPrimaryDriverID;


        /* =====================================================
           BASIC VALIDATION
        ===================================================== */

        if(rotorID <= 0){

            Swal.fire({

                icon:
                "error",

                title:
                "Invalid Rental",

                text:
                "Unable to identify this Rotor rental.",

                confirmButtonColor:
                "#B71C1C"

            });


            return;

        }

        const originalBalance =
        Number(
            editRotorLoadedRecord?.balance
        ) || 0;


        const donePaymentOnly =

        originalStatus === "Done"

        &&

        originalBalance > 0.009;


        if(
            originalStatus === "Done"
            &&
            originalBalance <= 0.009
        ){

            Swal.fire({

                icon:
                "info",

                title:
                "Rental Completed",

                text:
                "This completed Rotor rental is already fully paid.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        /* =====================================================
        VALIDATION
        ===================================================== */

        clearEditRotorInlineErrors();


        if(
            donePaymentOnly
        ){

            if(
                !validateEditRotorAdditionalPayment()
            ){

                focusRotorInvalidField(
                    editRotorAdditionalPayment
                );


                return;

            }


            const additionalPayment =

            parseFloat(
                editRotorAdditionalPayment?.value
                ||
                "0"
            ) || 0;


            if(
                additionalPayment <= 0.009
            ){

                setRotorInlineError(

                    editRotorAdditionalPayment,

                    "Please enter an Additional Payment."

                );


                focusRotorInvalidField(
                    editRotorAdditionalPayment
                );


                return;

            }

        }
        else{

            const validation =
            validateEditRotorForm({

                rentalDate:
                rentalDate,

                originalStatus:
                originalStatus,

                today:
                today,

                wasDelayed:
                wasDelayed,

                dateChanged:
                dateChanged,

                primaryChanged:
                primaryChanged,

                primaryTractorID:
                primaryTractorID,

                primaryDriverID:
                primaryDriverID,

                secondaryTractorID:
                secondaryTractorID,

                secondaryDriverID:
                secondaryDriverID

            });


            if(
                !validation.valid
            ){

                focusRotorInvalidField(
                    validation.firstInvalid
                );


                return;

            }


            const availabilityOK =
            await validateEditRotorAvailability();


            if(
                !availabilityOK
            ){

                return;

            }

        }


        /* =====================================================
           CONFIRM
        ===================================================== */

        let confirmText =

        donePaymentOnly

        ?

        "Add this payment to the completed Rotor rental?"

        :

        "Save the changes to this Rotor rental?";


        if(
            wasMissed
            &&
            dateChanged
        ){

            confirmText =
            "Reschedule this Missed Rotor rental to the selected date?";

        }
        else if(
            wasDelayed
            &&
            dateChanged
        ){

            confirmText =
            "Reschedule this Delayed Rotor rental? Its status will return to Pending.";

        }


        const confirmation =
        await Swal.fire({

            icon:
            "question",

            title:
            "Save Changes?",

            text:
            confirmText,

            showCancelButton:
            true,

            confirmButtonText:
            "Yes, Save",

            cancelButtonText:
            "Cancel",

            confirmButtonColor:
            "#8CA15B",

            cancelButtonColor:
            "#B71C1C",

            reverseButtons:
            true

        });


        if(
            !confirmation.isConfirmed
        ){

            return;

        }


        /* =====================================================
           SUBMIT
        ===================================================== */

        const originalText =
        updateRotorBtn.textContent;


        updateRotorBtn.disabled =
        true;


        updateRotorBtn.textContent =
        "SAVING...";


        try{


            const formData =
            new FormData(
                editRotorForm
            );


            const response =
            await fetch(

                "update/update_rotor.php",

                {
                    method:
                    "POST",

                    body:
                    formData,

                    cache:
                    "no-store"
                }

            );


            const result =
            (
                await response.text()
            )
            .trim();


            if(
                !response.ok
                ||
                result !== "success"
            ){

                throw new Error(
                    result
                    ||
                    "Unable to update the Rotor rental."
                );

            }


            const modal =
            bootstrap.Modal
            .getInstance(
                editRotorModalElement
            );


            modal
            ?.hide();


            await Swal.fire({

                icon:
                "success",

                title:
                "Rotor rental updated successfully.",

                showConfirmButton:
                false,

                timer:
                1400

            });


            saveRotorLedgerPosition();

            window.location.reload();

        }
        catch(error){


            console.error(
                "UPDATE ROTOR ERROR:",
                error
            );


            Swal.fire({

                icon:
                "error",

                title:
                "Update Failed",

                text:
                error.message
                ||
                "Unable to update the Rotor rental.",

                confirmButtonColor:
                "#B71C1C"

            });


            updateRotorBtn.disabled =
            false;


            updateRotorBtn.textContent =
            originalText;

        }

    }
);


/* =========================================================
   MODAL CLOSE RESET
========================================================= */

editRotorModalElement
?.addEventListener(
    "hidden.bs.modal",
    function(){

        resetEditRotorModal();

    }
);


/* =========================================================
   OUTSIDE CLICK
========================================================= */

document.addEventListener(
    "click",
    function(event){


        if(
            editRotorCalendar
            &&
            !editRotorCalendar.contains(
                event.target
            )
            &&
            !editRotorDateBtn
            ?.contains(
                event.target
            )
        ){

            editRotorCalendar
            .classList
            .remove(
                "show"
            );

        }


        if(
            !event.target.closest(
                ".rotor-assignment-dropdown"
            )
        ){

            closeEditRotorAssignmentMenus();

        }

    }
);


/* =========================================================
   RESIZE
========================================================= */

window.addEventListener(
    "resize",
    function(){

        if(
            editRotorCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            positionEditRotorCalendar();

        }

    }
);