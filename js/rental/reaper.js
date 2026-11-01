/* ======================================================
   REAPER MODULE
====================================================== */

const reaperForm =
document.getElementById("reaperForm");

const saveReaperBtn =
document.getElementById("saveReaperBtn");


const reaperAffiliate =
document.getElementById("reaperAffiliate");

const reaperAffiliateBtn =
document.getElementById("reaperAffiliateBtn");

const reaperAffiliateMenu =
document.getElementById("reaperAffiliateMenu");

const reaperAffiliateText =
document.getElementById("reaperAffiliateText");


const reaperCustomerInput =
document.getElementById("reaperCustomerName");

const reaperMemberId =
document.getElementById("reaperMemberId");

const reaperMemberSuggestion =
document.getElementById("reaperMemberSuggestion");


const reaperHarvest =
document.getElementById("reaperHarvest");

const reaperTractorID =
document.getElementById("reaperTractorID");

const reaperTractorNo =
document.getElementById("reaperTractorNo");

const reaperTractorBtn =
document.getElementById("reaperTractorBtn");

const reaperTractorMenu =
document.getElementById("reaperTractorMenu");

const reaperTractorText =
document.getElementById("reaperTractorText");

const reaperLocation =
document.getElementById("reaperLocation");

const reaperDate =
document.getElementById("reaperDate");

const reaperCommodity =
document.getElementById("reaperCommodity");


const reaperStatus =
document.getElementById("reaperStatus");

const reaperStatusBtn =
document.getElementById("reaperStatusBtn");

const reaperStatusMenu =
document.getElementById("reaperStatusMenu");

const reaperStatusText =
document.getElementById("reaperStatusText");


const reaperRemarks =
document.getElementById("reaperRemarks");

const reaperRemarksBtn =
document.getElementById("reaperRemarksBtn");

const reaperRemarksMenu =
document.getElementById("reaperRemarksMenu");

const reaperRemarksText =
document.getElementById("reaperRemarksText");


/* ======================================================
   AFFILIATE DROPDOWN
====================================================== */

if(
    reaperAffiliateBtn &&
    reaperAffiliateMenu
){

    reaperAffiliateBtn.addEventListener(

        "click",

        function(e){

            e.preventDefault();

            e.stopPropagation();


            reaperAffiliateMenu
            .classList
            .toggle("show");

        }

    );

}


/* ======================================================
   AFFILIATE STATE
====================================================== */

function updateReaperAffiliateState(){

    if(
        !reaperAffiliate ||
        !reaperCustomerInput
    ){

        return;

    }


    const value =
    reaperAffiliate.value;


    reaperCustomerInput.disabled =
    value === "";


    if(
        value === "Member"
    ){

        reaperCustomerInput.placeholder =
        "Search member...";

    }

    else if(
        value === "Not Member"
    ){

        reaperCustomerInput.placeholder =
        "Enter customer name";

    }

    else{

        reaperCustomerInput.placeholder =
        "Select affiliate first";

    }

}


/* ======================================================
   SELECT AFFILIATE
====================================================== */

document
.querySelectorAll(
    "#reaperAffiliateMenu .reaper-affiliate-item"
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


            reaperAffiliate.value =
            value;


            reaperAffiliateText.textContent =
            this.textContent.trim();


            reaperAffiliateMenu
            .classList
            .remove("show");


            /*
            Reset name every time Affiliate changes.
            */

            if(reaperCustomerInput){

                reaperCustomerInput.value =
                "";

                reaperCustomerInput.disabled =
                false;

            }


            if(reaperMemberId){

                reaperMemberId.value =
                "";

            }


            if(reaperMemberSuggestion){

                reaperMemberSuggestion.innerHTML =
                "";

                reaperMemberSuggestion.style.display =
                "none";

            }


            updateReaperAffiliateState();

        }

    );

});


updateReaperAffiliateState();


/* ======================================================
   MEMBER NORMALIZER
====================================================== */

function normalizeReaperMemberName(
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

async function findExactReaperMember(
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


    if(
        cleanName.length < 3
    ){

        return null;

    }


    const response =
    await fetch(

        "search_member.php?keyword=" +
        encodeURIComponent(
            cleanName
        )

    );


    if(
        !response.ok
    ){

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
    normalizeReaperMemberName(
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

            normalizeReaperMemberName(
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

let reaperMemberSearchTimer =
null;


if(
    reaperCustomerInput
){

    reaperCustomerInput
    .addEventListener(

        "input",

        function(){


            if(

                !reaperAffiliate ||

                !reaperMemberId ||

                !reaperMemberSuggestion

            ){

                return;

            }


            /*
            NOT MEMBER:
            free text only.
            */

            if(
                reaperAffiliate.value
                !==
                "Member"
            ){

                reaperMemberId.value =
                "";


                reaperMemberSuggestion.innerHTML =
                "";


                reaperMemberSuggestion.style.display =
                "none";


                return;

            }


            /*
            MEMBER:
            clear current ID while name
            is being changed.
            */

            reaperMemberId.value =
            "";


            const keyword =
            reaperCustomerInput.value

            .trim()

            .replace(
                /\s+/g,
                " "
            );


            clearTimeout(
                reaperMemberSearchTimer
            );


            if(
                keyword.length < 3
            ){

                reaperMemberSuggestion.innerHTML =
                "";


                reaperMemberSuggestion.style.display =
                "none";


                return;

            }


            reaperMemberSearchTimer =
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
                        reaperCustomerInput.value

                        .trim()

                        .replace(
                            /\s+/g,
                            " "
                        );


                        /*
                        Ignore old response kapag
                        may bagong tinype.
                        */

                        if(
                            currentName
                            !==
                            keyword
                        ){

                            return;

                        }


                        reaperMemberSuggestion.innerHTML =
                        data;


                        const typedName =
                        normalizeReaperMemberName(
                            reaperCustomerInput.value
                        );


                        let exactMember =
                        null;


                        reaperMemberSuggestion

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

                                normalizeReaperMemberName(
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


                        /*
                        If exact again after deleting
                        accidental typo, automatically
                        restore member_id.
                        */

                        reaperMemberId.value =

                        exactMember

                        ?

                        exactMember.id

                        :

                        "";


                        reaperMemberSuggestion.style.display =
                        "block";

                    })

                    .catch(error=>{


                        console.error(
                            "REAPER MEMBER SEARCH ERROR:",
                            error
                        );


                        reaperMemberId.value =
                        "";


                        reaperMemberSuggestion.innerHTML =

                        '<div class="reaper-member-search-message">Unable to search members.</div>';


                        reaperMemberSuggestion.style.display =
                        "block";

                    });

                },

                200

            );

        }

    );

}

/* ======================================================
   SELECT MEMBER FROM SUGGESTION
====================================================== */

if(
    reaperMemberSuggestion
    &&
    reaperCustomerInput
    &&
    reaperMemberId
){

    reaperMemberSuggestion
    .addEventListener(

        "click",

        function(e){

            const selectedMember =
            e.target.closest(
                ".member-item"
            );


            if(
                !selectedMember
                ||
                !reaperMemberSuggestion.contains(
                    selectedMember
                )
            ){

                return;

            }


            const memberID =
            selectedMember.dataset.id
            ||
            "";


            const memberName =
            selectedMember.dataset.name
            ||
            "";


            if(
                !memberID
                ||
                !memberName
            ){

                return;

            }


            e.preventDefault();
            e.stopPropagation();


            reaperCustomerInput.value =
            memberName;


            reaperMemberId.value =
            memberID;


            reaperMemberSuggestion.innerHTML =
            "";


            reaperMemberSuggestion.style.display =
            "none";

        }

    );

}


/* ======================================================
   HIDE MEMBER SUGGESTIONS
====================================================== */

document.addEventListener(

    "click",

    function(e){

        if(
            reaperMemberSuggestion
            &&
            reaperCustomerInput
            &&
            !reaperMemberSuggestion.contains(
                e.target
            )
            &&
            e.target !== reaperCustomerInput
        ){

            reaperMemberSuggestion.style.display =
            "none";

        }

    }

);

/* ======================================================
   SIMPLE CUSTOM DROPDOWN HELPER
====================================================== */

function setupReaperDropdown(

    button,

    menu,

    hiddenInput,

    text,

    itemSelector

){

    if(
        button &&
        menu
    ){

        button.addEventListener(

            "click",

            function(e){

                e.preventDefault();

                e.stopPropagation();


                menu
                .classList
                .toggle("show");

            }

        );

    }


    document
    .querySelectorAll(
        itemSelector
    )
    .forEach(
    function(item){


        item.addEventListener(

            "click",

            function(e){

                e.preventDefault();
                e.stopPropagation();


                /* ==========================================
                DISABLED DROPDOWN ITEM
                ========================================== */

                if(
                    this.dataset.disabled ===
                    "1"
                ){

                    const disabledMessage =
                    this.dataset.disabledMessage
                    ||
                    this.title
                    ||
                    "";


                    if(
                        disabledMessage
                    ){

                        Swal.fire({

                            icon:
                            "info",

                            title:
                            "Action Not Available",

                            text:
                            disabledMessage,

                            confirmButtonColor:
                            "#8CA15B"

                        });

                    }


                    return;

                }


                if(hiddenInput){

                    hiddenInput.value =
                    this.dataset.value;

                }


                if(text){

                    text.textContent =
                    this.textContent.trim();

                }


                if(menu){

                    menu
                    .classList
                    .remove("show");

                }

            }

        );

    });

}

/* ======================================================
   REAPER RENTAL ASSIGNMENT
   DATE-FIRST / DRIVER REGISTRY FLOW

   FLOW
   1. Rental Date
   2. Primary Tractor
   3. Primary Driver
   4. Optional Secondary Tractor
   5. Optional Secondary Driver
====================================================== */

const reaperDateBtn =
document.getElementById(
    "reaperDateBtn"
);

const reaperDateText =
document.getElementById(
    "reaperDateText"
);

const reaperCalendar =
document.getElementById(
    "reaperCalendar"
);

const reaperCalendarMonth =
document.getElementById(
    "reaperCalendarMonth"
);

const reaperCalendarDays =
document.getElementById(
    "reaperCalendarDays"
);

const reaperCalendarPrev =
document.getElementById(
    "reaperCalendarPrev"
);

const reaperCalendarNext =
document.getElementById(
    "reaperCalendarNext"
);


/* ======================================================
   PRIMARY DRIVER
====================================================== */

const reaperPrimaryDriverID =
document.getElementById(
    "reaperPrimaryDriverID"
);

const reaperPrimaryDriverBtn =
document.getElementById(
    "reaperPrimaryDriverBtn"
);

const reaperPrimaryDriverText =
document.getElementById(
    "reaperPrimaryDriverText"
);

const reaperPrimaryDriverMenu =
document.getElementById(
    "reaperPrimaryDriverMenu"
);


/* ======================================================
   SECONDARY TRACTOR
====================================================== */

const reaperSecondaryTractorID =
document.getElementById(
    "reaperSecondaryTractorID"
);

const reaperSecondaryTractorNo =
document.getElementById(
    "reaperSecondaryTractorNo"
);

const reaperSecondaryTractorBtn =
document.getElementById(
    "reaperSecondaryTractorBtn"
);

const reaperSecondaryTractorText =
document.getElementById(
    "reaperSecondaryTractorText"
);

const reaperSecondaryTractorMenu =
document.getElementById(
    "reaperSecondaryTractorMenu"
);


/* ======================================================
   SECONDARY DRIVER
====================================================== */

const reaperSecondaryDriverID =
document.getElementById(
    "reaperSecondaryDriverID"
);

const reaperSecondaryDriverBtn =
document.getElementById(
    "reaperSecondaryDriverBtn"
);

const reaperSecondaryDriverText =
document.getElementById(
    "reaperSecondaryDriverText"
);

const reaperSecondaryDriverMenu =
document.getElementById(
    "reaperSecondaryDriverMenu"
);


/* ======================================================
   SECONDARY ASSIGNMENT
====================================================== */

const reaperSecondaryToggle =
document.getElementById(
    "reaperSecondaryToggle"
);

const reaperSecondaryAssignment =
document.getElementById(
    "reaperSecondaryAssignment"
);


/* ======================================================
   AVAILABILITY STATE
====================================================== */

let reaperTractors = [];

let reaperDrivers = [];

let reaperAvailabilityRequest =
0;

let reaperCalendarAvailability =
{};

let reaperCalendarAvailabilityKey =
"";

let reaperCalendarAvailabilityRequest =
0;


/* ======================================================
   CALENDAR VIEW
====================================================== */

let reaperCalendarView =
new Date();

reaperCalendarView.setDate(
    1
);


/* ======================================================
   DATE HELPERS
====================================================== */

function reaperPadNumber(
    number
){

    return String(
        number
    )
    .padStart(
        2,
        "0"
    );

}


function reaperBuildDateKey(
    year,
    month,
    day
){

    return (
        year
        +
        "-"
        +
        reaperPadNumber(
            month + 1
        )
        +
        "-"
        +
        reaperPadNumber(
            day
        )
    );

}


/* ======================================================
   MANILA TODAY

   This keeps JS date rules consistent with PHP / MySQL
   Asia/Manila timezone.
====================================================== */

function getReaperTodayKey(){

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


    const values =
    {};


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

    });


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


/* ======================================================
   DATE DISPLAY
====================================================== */

function reaperFormatDate(
    dateValue
){

    if(
        !dateValue
    ){

        return "";

    }


    const parts =
    dateValue.split(
        "-"
    );


    if(
        parts.length
        !==
        3
    ){

        return dateValue;

    }


    const dateObject =
    new Date(

        Number(
            parts[0]
        ),

        Number(
            parts[1]
        )
        -
        1,

        Number(
            parts[2]
        )

    );


    return dateObject
    .toLocaleDateString(
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


/* ======================================================
   ESCAPE HTML
====================================================== */

function reaperEscapeHTML(
    value
){

    return String(
        value
        ??
        ""
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


/* ======================================================
   PAYMENT BUTTON LOCK

   Shared by Add + Edit Reaper.
====================================================== */

function setReaperRemarksButtonLocked(
    button,
    locked
){

    if(
        !button
    ){

        return;

    }


    button.disabled =
    Boolean(
        locked
    );


    button
    .classList
    .toggle(
        "reaper-remarks-locked",
        Boolean(
            locked
        )
    );

}


/* ======================================================
   ADD REAPER COMPLETION DATA
====================================================== */

function reaperAddCompletionReady(){

    const harvestValue =
    String(
        reaperHarvest?.value
        ??
        ""
    )
    .trim();


    const commodityValue =
    String(
        reaperCommodity?.value
        ??
        ""
    )
    .trim();


    if(
        harvestValue
        ===
        ""
        ||
        commodityValue
        ===
        ""
    ){

        return false;

    }


    const harvest =
    Number(
        harvestValue
    );


    const commodity =
    Number(
        commodityValue
    );


    return (

        Number.isInteger(
            harvest
        )

        &&

        harvest
        >=
        1

        &&

        Number.isInteger(
            commodity
        )

        &&

        commodity
        >
        0

    );

}


/* ======================================================
   ADD STATUS ITEM
====================================================== */

function setReaperAddStatusItem(
    value,
    enabled,
    reason = ""
){

    const item =
    document.querySelector(

        '#reaperStatusMenu '
        +
        '.reaper-status-item'
        +
        '[data-value="'
        +
        value
        +
        '"]'

    );


    if(
        !item
    ){

        return;

    }


    item.dataset.disabled =

    enabled

    ?

    "0"

    :

    "1";


    item
    .classList
    .toggle(
        "reaper-dropdown-disabled",
        !enabled
    );

    item.dataset.disabledMessage =

    enabled

    ?

    ""

    :

    reason;

}


/* ======================================================
   ADD REMARKS ITEM
====================================================== */

function setReaperAddRemarksItem(
    value,
    enabled
){

    const item =
    document.querySelector(

        '#reaperRemarksMenu '
        +
        '.reaper-remarks-item'
        +
        '[data-value="'
        +
        value
        +
        '"]'

    );


    if(
        !item
    ){

        return;

    }


    item.dataset.disabled =

    enabled

    ?

    "0"

    :

    "1";


    item
    .classList
    .toggle(
        "reaper-dropdown-disabled",
        !enabled
    );

}


/* ======================================================
   RESET ADD STATUS
====================================================== */

function resetReaperAddStatus(){

    if(
        reaperStatus
    ){

        reaperStatus.value =
        "";

    }


    if(
        reaperStatusText
    ){

        reaperStatusText.textContent =
        "Select Date First";

    }


    if(
        reaperStatusBtn
    ){

        reaperStatusBtn.disabled =
        true;

    }


    [
        "Pending",
        "Ongoing",
        "Done"
    ]
    .forEach(
    function(value){

        setReaperAddStatusItem(
            value,
            true
        );

    });


    reaperStatusMenu
    ?.classList
    .remove(
        "show"
    );

}


/* ======================================================
   SERVICE DATA RULES

   FUTURE
   - Harvest unavailable
   - Commodity unavailable

   TODAY / PAST
   - Service completion may be entered
====================================================== */

function applyReaperAddServiceDataRules(){

    const selectedDate =
    reaperDate?.value
    ||
    "";


    const today =
    getReaperTodayKey();


    const future =

    selectedDate
    !==
    ""

    &&

    selectedDate
    >
    today;


    const serviceDataEnabled =

    selectedDate
    !==
    ""

    &&

    !future;


    /* ==========================================
       NO. OF HARVEST

       Available only on today / past dates.
    ========================================== */

    if(
        reaperHarvest
    ){

        reaperHarvest.disabled =
        !serviceDataEnabled;


        if(
            !serviceDataEnabled
        ){

            reaperHarvest.value =
            "";

        }

    }


    /* ==========================================
       COMMODITY

       Available only when:
       - service date allows completion data
       - No. of Harvest is a valid whole number >= 1
    ========================================== */

    if(
        reaperCommodity
    ){

        const harvestRaw =
        String(
            reaperHarvest?.value
            ??
            ""
        )
        .trim();


        const harvest =
        Number(
            harvestRaw
        );


        const harvestReady =

        serviceDataEnabled

        &&

        Number.isInteger(
            harvest
        )

        &&

        harvest >= 1;


        reaperCommodity.disabled =
        !harvestReady;


        reaperCommodity.placeholder =

        harvestReady

        ?

        "Enter Commodity"

        :

        "Enter No. of Harvest first";


        if(
            !harvestReady
        ){

            reaperCommodity.value =
            "";

        }

    }

}


/* ======================================================
   DATE-BASED STATUS RULES

   FUTURE
   - Pending only

   TODAY
   - Pending
   - Ongoing
   - Done only with completion data

   PAST
   - Historical / Audit entry
   - Done only
   - Harvest + Commodity required before save
====================================================== */

function applyReaperAddStatusRules(){

    if(
        !reaperDate
        ||
        !reaperStatus
        ||
        !reaperStatusBtn
    ){

        return;

    }


    const selectedDate =
    reaperDate.value;


    if(
        selectedDate === ""
    ){

        resetReaperAddStatus();

        return;

    }


    const today =
    getReaperTodayKey();


    const isFuture =
    selectedDate > today;


    const isPast =
    selectedDate < today;


    const completionReady =
    reaperAddCompletionReady();


    const statusItems =
    document.querySelectorAll(
        "#reaperStatusMenu .reaper-status-item"
    );


    /*
       Reset all items first.
    */

    statusItems.forEach(
    function(item){

        item.style.display =
        "";

    });


    setReaperAddStatusItem(
        "Pending",
        true
    );


    setReaperAddStatusItem(
        "Ongoing",
        true
    );


    setReaperAddStatusItem(
        "Done",
        completionReady,
        "Enter No. of Harvest and Commodity first."
    );


    /* ==========================================
       FUTURE = PENDING ONLY
    ========================================== */

    if(
        isFuture
    ){

        reaperStatus.value =
        "Pending";


        if(
            reaperStatusText
        ){

            reaperStatusText.textContent =
            "Pending";

        }


        statusItems.forEach(
        function(item){

            item.style.display =

            item.dataset.value ===
            "Pending"

            ?

            ""

            :

            "none";

        });


        reaperStatusBtn.disabled =
        true;


        reaperStatusBtn.title =
        "A future rental must remain Pending until its service date.";


        reaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==========================================
       PAST = HISTORICAL / AUDIT + DONE ONLY
    ========================================== */

    if(
        isPast
    ){

        reaperStatus.value =
        "Done";


        if(
            reaperStatusText
        ){

            reaperStatusText.textContent =
            "Done";

        }


        statusItems.forEach(
        function(item){

            item.style.display =

            item.dataset.value ===
            "Done"

            ?

            ""

            :

            "none";

        });


        /*
           Status itself is automatic.

           Completion data is still required
           before the transaction can be saved.
        */

        reaperStatusBtn.disabled =
        true;


        reaperStatusBtn.title =
        "Past dates are recorded as Historical / Audit entries and are automatically Done.";


        reaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==========================================
       TODAY
    ========================================== */

    reaperStatusBtn.disabled =
    false;


    reaperStatusBtn.title =
    "";


    setReaperAddStatusItem(
        "Pending",
        true
    );


    setReaperAddStatusItem(
        "Ongoing",
        true
    );


    setReaperAddStatusItem(
        "Done",
        completionReady,
        "Enter No. of Harvest and Commodity first."
    );


    const currentStatus =
    reaperStatus.value;


    const currentValid =
    (
        currentStatus ===
        "Pending"
    )
    ||
    (
        currentStatus ===
        "Ongoing"
    )
    ||
    (
        currentStatus ===
        "Done"
        &&
        completionReady
    );


    if(
        !currentValid
    ){

        reaperStatus.value =
        "Pending";


        if(
            reaperStatusText
        ){

            reaperStatusText.textContent =
            "Pending";

        }

    }

}

/* ======================================================
   PAYMENT RULES

   Pending / Ongoing
   - Unpaid only

   Done + complete
   - Unpaid
   - Paid

   Future
   - Unpaid only
====================================================== */

function applyReaperAddRemarksRules(){

    if(
        !reaperRemarks
        ||
        !reaperRemarksText
    ){

        return;

    }


    const selectedDate =
    reaperDate?.value
    ||
    "";


    const today =
    getReaperTodayKey();


    const isFuture =

    selectedDate
    !==
    ""

    &&

    selectedDate
    >
    today;


    const paidAllowed =

    !isFuture

    &&

    reaperStatus?.value
    ===
    "Done"

    &&

    reaperAddCompletionReady();


    setReaperAddRemarksItem(
        "Unpaid",
        true
    );


    setReaperAddRemarksItem(
        "Paid",
        paidAllowed
    );


    if(
        !paidAllowed
    ){

        reaperRemarks.value =
        "Unpaid";


        reaperRemarksText.textContent =
        "Unpaid";


        setReaperRemarksButtonLocked(
            reaperRemarksBtn,
            true
        );


        reaperRemarksMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    setReaperRemarksButtonLocked(
        reaperRemarksBtn,
        false
    );


    if(
        ![
            "Unpaid",
            "Paid"
        ]
        .includes(
            reaperRemarks.value
        )
    ){

        reaperRemarks.value =
        "Unpaid";


        reaperRemarksText.textContent =
        "Unpaid";

    }

}


/* ======================================================
   REFRESH ADD LIFECYCLE
====================================================== */

function refreshReaperAddLifecycle(){

    applyReaperAddServiceDataRules();

    applyReaperAddStatusRules();

    applyReaperAddRemarksRules();

}


/* ======================================================
   CLOSE ASSIGNMENT MENUS
====================================================== */

function closeReaperAssignmentMenus(
    exceptMenu = null
){

    const menus = [

        reaperTractorMenu,

        reaperPrimaryDriverMenu,

        reaperSecondaryTractorMenu,

        reaperSecondaryDriverMenu

    ];


    menus.forEach(
    function(menu){

        if(
            menu
            &&
            menu
            !==
            exceptMenu
        ){

            menu
            .classList
            .remove(
                "show"
            );

        }

    });

}


/* ======================================================
   RESET PRIMARY TRACTOR
====================================================== */

function resetReaperPrimaryTractor(){

    if(
        reaperTractorID
    ){

        reaperTractorID.value =
        "";

    }


    if(
        reaperTractorNo
    ){

        reaperTractorNo.value =
        "";

    }


    if(
        reaperTractorText
    ){

        reaperTractorText.textContent =

        reaperDate?.value

        ?

        "Select Primary Tractor"

        :

        "Select Rental Date First";

    }

}


/* ======================================================
   RESET PRIMARY DRIVER
====================================================== */

function resetReaperPrimaryDriver(){

    if(
        reaperPrimaryDriverID
    ){

        reaperPrimaryDriverID.value =
        "";

    }


    if(
        reaperPrimaryDriverText
    ){

        reaperPrimaryDriverText.textContent =

        reaperDate?.value

        ?

        "Select Primary Driver"

        :

        "Select Rental Date First";

    }

}


/* ======================================================
   RESET SECONDARY TRACTOR
====================================================== */

function resetReaperSecondaryTractor(){

    if(
        reaperSecondaryTractorID
    ){

        reaperSecondaryTractorID.value =
        "";

    }


    if(
        reaperSecondaryTractorNo
    ){

        reaperSecondaryTractorNo.value =
        "";

    }


    if(
        reaperSecondaryTractorText
    ){

        reaperSecondaryTractorText.textContent =
        "No Secondary Tractor";

    }

}


/* ======================================================
   RESET SECONDARY DRIVER
====================================================== */

function resetReaperSecondaryDriver(){

    if(
        reaperSecondaryDriverID
    ){

        reaperSecondaryDriverID.value =
        "";

    }


    if(
        reaperSecondaryDriverText
    ){

        reaperSecondaryDriverText.textContent =
        "No Secondary Driver";

    }

}


/* ======================================================
   RESET ALL ASSIGNMENTS
====================================================== */

function resetReaperAssignments(){

    resetReaperPrimaryTractor();

    resetReaperPrimaryDriver();

    resetReaperSecondaryTractor();

    resetReaperSecondaryDriver();

}


/* ======================================================
   DISABLE ASSIGNMENTS
====================================================== */

function disableReaperAssignments(
    message = "Select Rental Date First"
){

    [
        reaperTractorBtn,
        reaperPrimaryDriverBtn,
        reaperSecondaryTractorBtn,
        reaperSecondaryDriverBtn
    ]
    .forEach(
    function(button){

        if(
            button
        ){

            button.disabled =
            true;

        }

    });


    if(
        reaperTractorText
    ){

        reaperTractorText.textContent =
        message;

    }


    if(
        reaperPrimaryDriverText
    ){

        reaperPrimaryDriverText.textContent =
        message;

    }


    if(
        reaperSecondaryTractorText
    ){

        reaperSecondaryTractorText.textContent =
        "No Secondary Tractor";

    }


    if(
        reaperSecondaryDriverText
    ){

        reaperSecondaryDriverText.textContent =
        "No Secondary Driver";

    }


    closeReaperAssignmentMenus();

}


/* ======================================================
   LOADING ASSIGNMENTS
====================================================== */

function setReaperAssignmentLoading(){

    resetReaperAssignments();


    [
        reaperTractorBtn,
        reaperPrimaryDriverBtn,
        reaperSecondaryTractorBtn,
        reaperSecondaryDriverBtn
    ]
    .forEach(
    function(button){

        if(
            button
        ){

            button.disabled =
            true;

        }

    });


    if(
        reaperTractorText
    ){

        reaperTractorText.textContent =
        "Loading Tractors...";

    }


    if(
        reaperPrimaryDriverText
    ){

        reaperPrimaryDriverText.textContent =
        "Loading Drivers...";

    }

}


/* ======================================================
   TRACTOR AVAILABILITY
====================================================== */

async function getReaperTractorAvailability(
    rentalDate
){

    const url =

    "tractor/get_tractor_availability.php"

    +

    "?tractor_type=Reaper"

    +

    "&rental_date="

    +

    encodeURIComponent(
        rentalDate
    )

    +

    "&rental_type=reaper";


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

            "Unable to load Tractor availability."

        );

    }


    return data;

}


/* ======================================================
   DRIVER AVAILABILITY
====================================================== */

async function getReaperDriverAvailability(
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

    "&rental_type=reaper";


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
   RESOURCE STATE CSS
====================================================== */

function getReaperResourceStateClass(
    availability
){

    switch(
        String(
            availability
            ||
            ""
        )
    ){

        case "Available":

            return "reaper-resource-available";


        case "Reserved":

            return "reaper-resource-reserved";


        case "In Use":

        case "Assigned":

            return "reaper-resource-in-use";


        case "Maintenance":

            return "reaper-resource-maintenance";


        default:

            return "";

    }

}


/* ======================================================
   RENDER TRACTOR MENU
====================================================== */

function renderReaperTractorMenu(
    menu,
    tractors,
    secondary = false
){

    if(
        !menu
    ){

        return;

    }


    let html =
    "";


    if(
        !Array.isArray(
            tractors
        )
        ||
        tractors.length
        ===
        0
    ){

        html +=
        `
        <div class="reaper-tractor-empty">
            No registered Reaper tractors.
        </div>
        `;


        menu.innerHTML =
        html;


        return;

    }


    tractors.forEach(
    function(tractor){


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


        const tractorID =
        parseInt(
            tractor.tractor_id,
            10
        )
        ||
        0;


        const tractorNo =
        reaperEscapeHTML(
            tractor.tractor_no
            ||
            ""
        );


        const tractorName =
        reaperEscapeHTML(
            tractor.tractor_name
            ||
            ""
        );


        const stateClass =
        getReaperResourceStateClass(
            availability
        );


        html +=
        `
        <div
        class="
            reaper-tractor-item
            ${
                available
                ?
                ""
                :
                "reaper-tractor-disabled"
            }
        "
        data-id="${tractorID}"
        data-no="${tractorNo}"
        data-name="${tractorName}"
        data-availability="${
            reaperEscapeHTML(
                availability
            )
        }"
        data-disabled="${
            available
            ?
            "0"
            :
            "1"
        }">

            <div class="reaper-tractor-details">

                <span class="reaper-tractor-number">
                    ${tractorNo}
                </span>

                <span class="reaper-tractor-name">
                    ${tractorName}
                </span>

            </div>

            <span
            class="
                reaper-tractor-state
                ${stateClass}
            ">
                ${
                    reaperEscapeHTML(
                        availability
                    )
                }
            </span>

        </div>
        `;

    });


    menu.innerHTML =
    html;

}


/* ======================================================
   RENDER DRIVER MENU
====================================================== */

function renderReaperDriverMenu(
    menu,
    drivers,
    secondary = false
){

    if(
        !menu
    ){

        return;

    }


    let html =
    "";

    if(
        !Array.isArray(
            drivers
        )
        ||
        drivers.length
        ===
        0
    ){

        html +=
        `
        <div class="reaper-tractor-empty">
            No active drivers registered.
        </div>
        `;


        menu.innerHTML =
        html;


        return;

    }


    drivers.forEach(
    function(driver){


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


        const driverID =
        parseInt(
            driver.driver_id,
            10
        )
        ||
        0;


        const driverCode =
        reaperEscapeHTML(

            driver.driver_code

            ||

            (
                "DRV-"
                +
                String(
                    driverID
                )
                .padStart(
                    4,
                    "0"
                )
            )

        );


        const driverName =
        reaperEscapeHTML(
            driver.full_name
            ||
            ""
        );


        const stateClass =
        getReaperResourceStateClass(
            availability
        );


        html +=
        `
        <div
        class="
            reaper-tractor-item
            ${
                available
                ?
                ""
                :
                "reaper-tractor-disabled"
            }
        "
        data-id="${driverID}"
        data-name="${driverName}"
        data-availability="${
            reaperEscapeHTML(
                availability
            )
        }"
        data-disabled="${
            available
            ?
            "0"
            :
            "1"
        }">

            <div class="reaper-tractor-details">

                <span class="reaper-tractor-number">
                    ${driverCode}
                </span>

                <span class="reaper-tractor-name">
                    ${driverName}
                </span>

            </div>

            <span
            class="
                reaper-tractor-state
                ${stateClass}
            ">
                ${
                    reaperEscapeHTML(
                        availability
                    )
                }
            </span>

        </div>
        `;

    });


    menu.innerHTML =
    html;

}

/* ======================================================
   PRIMARY / SECONDARY MENU FILTER
   Keep Tractor / Driver assignments unique both ways.
====================================================== */

function refreshReaperSecondaryMenus(){

    const primaryTractorID =
    String(
        reaperTractorID?.value
        ||
        ""
    );


    const secondaryTractorID =
    String(
        reaperSecondaryTractorID?.value
        ||
        ""
    );


    const primaryDriverID =
    String(
        reaperPrimaryDriverID?.value
        ||
        ""
    );


    const secondaryDriverID =
    String(
        reaperSecondaryDriverID?.value
        ||
        ""
    );


    const primaryTractors =
    reaperTractors.filter(
        function(tractor){

            return String(
                tractor.tractor_id
                ||
                ""
            )
            !==
            secondaryTractorID;

        }
    );


    const secondaryTractors =
    reaperTractors.filter(
        function(tractor){

            return String(
                tractor.tractor_id
                ||
                ""
            )
            !==
            primaryTractorID;

        }
    );


    const primaryDrivers =
    reaperDrivers.filter(
        function(driver){

            return String(
                driver.driver_id
                ||
                ""
            )
            !==
            secondaryDriverID;

        }
    );


    const secondaryDrivers =
    reaperDrivers.filter(
        function(driver){

            return String(
                driver.driver_id
                ||
                ""
            )
            !==
            primaryDriverID;

        }
    );


    renderReaperTractorMenu(
        reaperTractorMenu,
        primaryTractors,
        false
    );


    renderReaperTractorMenu(
        reaperSecondaryTractorMenu,
        secondaryTractors,
        true
    );


    renderReaperDriverMenu(
        reaperPrimaryDriverMenu,
        primaryDrivers,
        false
    );


    renderReaperDriverMenu(
        reaperSecondaryDriverMenu,
        secondaryDrivers,
        true
    );

}

/* ======================================================
   LOAD DATE AVAILABILITY
====================================================== */

async function loadReaperAssignmentAvailability(
    rentalDate
){

    if(
        !rentalDate
    ){

        reaperTractors =
        [];


        reaperDrivers =
        [];


        resetReaperAssignments();


        disableReaperAssignments();


        return;

    }


    const requestID =
    ++reaperAvailabilityRequest;


    setReaperAssignmentLoading();


    try{


        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getReaperTractorAvailability(
                rentalDate
            ),

            getReaperDriverAvailability(
                rentalDate
            )

        ]);


        /* ==========================================
           IGNORE OLD RESPONSE
        ========================================== */

        if(
            requestID
            !==
            reaperAvailabilityRequest
        ){

            return;

        }


        if(
            reaperDate?.value
            !==
            rentalDate
        ){

            return;

        }


        reaperTractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        reaperDrivers =

        Array.isArray(
            driverData.drivers
        )

        ?

        driverData.drivers

        :

        [];


        /* ==========================================
           MENUS
        ========================================== */

        renderReaperTractorMenu(

            reaperTractorMenu,

            reaperTractors,

            false

        );


        renderReaperTractorMenu(

            reaperSecondaryTractorMenu,

            reaperTractors,

            true

        );


        renderReaperDriverMenu(

            reaperPrimaryDriverMenu,

            reaperDrivers,

            false

        );


        renderReaperDriverMenu(

            reaperSecondaryDriverMenu,

            reaperDrivers,

            true

        );


        /* ==========================================
           ENABLE BUTTONS
        ========================================== */

        if(
            reaperTractorBtn
        ){

            reaperTractorBtn.disabled =
            false;


            reaperTractorText.textContent =
            "Select Primary Tractor";

        }


        if(
            reaperPrimaryDriverBtn
        ){

            reaperPrimaryDriverBtn.disabled =
            false;


            reaperPrimaryDriverText.textContent =
            "Select Primary Driver";

        }


        if(
            reaperSecondaryTractorBtn
        ){

            reaperSecondaryTractorBtn.disabled =
            false;


            reaperSecondaryTractorText.textContent =
            "No Secondary Tractor";

        }


        if(
            reaperSecondaryDriverBtn
        ){

            reaperSecondaryDriverBtn.disabled =
            false;


            reaperSecondaryDriverText.textContent =
            "No Secondary Driver";

        }


        /* ==========================================
           NOTHING AVAILABLE
        ========================================== */

        const availableTractors =

        reaperTractors.filter(
            tractor =>
            tractor.available
            ===
            true
        );


        const availableDrivers =

        reaperDrivers.filter(
            driver =>
            driver.available
            ===
            true
        );


        if(
            availableTractors.length
            ===
            0
            ||
            availableDrivers.length
            ===
            0
        ){

            await Swal.fire({

                icon:
                "info",

                title:
                "Limited Availability",

                text:

                availableTractors.length
                ===
                0

                ?

                "There is no available Reaper tractor on the selected date."

                :

                "There is no available driver on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });

        }

    }
    catch(error){


        console.error(
            "REAPER AVAILABILITY ERROR:",
            error
        );


        if(
            requestID
            !==
            reaperAvailabilityRequest
        ){

            return;

        }


        reaperTractors =
        [];


        reaperDrivers =
        [];


        resetReaperAssignments();


        disableReaperAssignments(
            "Availability Unavailable"
        );


        await Swal.fire({

            icon:
            "error",

            title:
            "Unable to Load Availability",

            text:

            error.message

            ||

            "Unable to load Rental availability.",

            confirmButtonColor:
            "#B71C1C"

        });

    }

}


/* ======================================================
   OPEN ASSIGNMENT MENU
====================================================== */

function setupReaperAssignmentButton(
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


    button
    .addEventListener(
        "click",
        function(event){

            event.preventDefault();

            event.stopPropagation();


            if(
                button.disabled
            ){

                return;

            }


            reaperCalendar
            ?.classList
            .remove(
                "show"
            );


            reaperStatusMenu
            ?.classList
            .remove(
                "show"
            );


            reaperRemarksMenu
            ?.classList
            .remove(
                "show"
            );


            const willOpen =

            !menu
            .classList
            .contains(
                "show"
            );


            closeReaperAssignmentMenus(
                menu
            );


            menu
            .classList
            .toggle(
                "show",
                willOpen
            );

        }
    );


    menu
    .addEventListener(
        "click",
        function(event){

            event.stopPropagation();

        }
    );

}


/* ======================================================
   SETUP ASSIGNMENT BUTTONS
====================================================== */

setupReaperAssignmentButton(

    reaperTractorBtn,

    reaperTractorMenu

);


setupReaperAssignmentButton(

    reaperPrimaryDriverBtn,

    reaperPrimaryDriverMenu

);


setupReaperAssignmentButton(

    reaperSecondaryTractorBtn,

    reaperSecondaryTractorMenu

);


setupReaperAssignmentButton(

    reaperSecondaryDriverBtn,

    reaperSecondaryDriverMenu

);


/* ======================================================
   PRIMARY TRACTOR SELECTION
====================================================== */

reaperTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
        ){

            return;

        }


        if(
            item.dataset.disabled
            ===
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
            item.dataset.id,
            10
        )
        ||
        0;


        if(
            tractorID
            <=
            0
        ){

            return;

        }


        if(
            String(
                reaperSecondaryTractorID?.value
                ||
                ""
            )
            ===
            String(
                tractorID
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Duplicate Tractor",

                text:
                "Primary Tractor and Secondary Tractor cannot be the same.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        reaperTractorID.value =
        tractorID;


        reaperTractorNo.value =
        item.dataset.no
        ||
        "";


        reaperTractorText.textContent =

        (
            item.dataset.no
            ||
            ""
        )

        +

        " - "

        +

        (
            item.dataset.name
            ||
            ""
        );


        reaperTractorMenu
        .classList
        .remove(
            "show"
        );


        clearReaperInlineError(
            reaperTractorBtn
        );

        refreshReaperSecondaryMenus();

    }
);


/* ======================================================
   PRIMARY DRIVER SELECTION
====================================================== */

reaperPrimaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
        ){

            return;

        }


        if(
            item.dataset.disabled
            ===
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
            item.dataset.id,
            10
        )
        ||
        0;


        if(
            driverID
            <=
            0
        ){

            return;

        }


        if(
            String(
                reaperSecondaryDriverID?.value
                ||
                ""
            )
            ===
            String(
                driverID
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Duplicate Driver",

                text:
                "Primary Driver and Secondary Driver cannot be the same.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        reaperPrimaryDriverID.value =
        driverID;


        reaperPrimaryDriverText.textContent =
        item.dataset.name
        ||
        "Selected Driver";


        reaperPrimaryDriverMenu
        .classList
        .remove(
            "show"
        );


        clearReaperInlineError(
            reaperPrimaryDriverBtn
        );

        refreshReaperSecondaryMenus();

    }
);


/* ======================================================
   SECONDARY TRACTOR SELECTION
====================================================== */

reaperSecondaryTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
        ){

            return;

        }


        /* ==========================================
           CLEAR SECONDARY PAIR
        ========================================== */

        if(
            item.dataset.action
            ===
            "clear"
        ){

            resetReaperSecondaryTractor();

            resetReaperSecondaryDriver();


            reaperSecondaryTractorMenu
            .classList
            .remove(
                "show"
            );


            refreshReaperSecondaryMenus();


            return;

        }


        if(
            item.dataset.disabled
            ===
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
            item.dataset.id,
            10
        )
        ||
        0;


        if(
            tractorID
            <=
            0
        ){

            return;

        }


        if(
            String(
                reaperTractorID?.value
                ||
                ""
            )
            ===
            String(
                tractorID
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Duplicate Tractor",

                text:
                "Secondary Tractor cannot be the same as the Primary Tractor.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        reaperSecondaryTractorID.value =
        tractorID;


        reaperSecondaryTractorNo.value =
        item.dataset.no
        ||
        "";


        reaperSecondaryTractorText.textContent =

        (
            item.dataset.no
            ||
            ""
        )

        +

        " - "

        +

        (
            item.dataset.name
            ||
            ""
        );


        reaperSecondaryTractorMenu
        .classList
        .remove(
            "show"
        );


        clearReaperInlineError(
            reaperSecondaryTractorBtn
        );

        refreshReaperSecondaryMenus();

    }
);


/* ======================================================
   SECONDARY DRIVER SELECTION
====================================================== */

reaperSecondaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
        ){

            return;

        }


        if(
            item.dataset.action
            ===
            "clear"
        ){

            resetReaperSecondaryTractor();

            resetReaperSecondaryDriver();


            reaperSecondaryDriverMenu
            .classList
            .remove(
                "show"
            );


            refreshReaperSecondaryMenus();


            return;

        }


        if(
            item.dataset.disabled
            ===
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
            item.dataset.id,
            10
        )
        ||
        0;


        if(
            driverID
            <=
            0
        ){

            return;

        }


        if(
            String(
                reaperPrimaryDriverID?.value
                ||
                ""
            )
            ===
            String(
                driverID
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Duplicate Driver",

                text:
                "Secondary Driver cannot be the same as the Primary Driver.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        reaperSecondaryDriverID.value =
        driverID;


        reaperSecondaryDriverText.textContent =
        item.dataset.name
        ||
        "Selected Driver";


        reaperSecondaryDriverMenu
        .classList
        .remove(
            "show"
        );


        clearReaperInlineError(
            reaperSecondaryDriverBtn
        );

        refreshReaperSecondaryMenus();

    }
);

/* ======================================================
   SECONDARY ASSIGNMENT TOGGLE
====================================================== */

function resetReaperSecondarySection(){

    reaperSecondaryAssignment
    ?.classList
    .remove(
        "is-open"
    );


    reaperSecondaryToggle
    ?.classList
    .remove(
        "is-open"
    );


    const toggleText =
    reaperSecondaryToggle
    ?.querySelector(
        "span:last-child"
    );


    if(
        toggleText
    ){

        toggleText.textContent =
        "ADD SECONDARY ASSIGNMENT";

    }


    resetReaperSecondaryTractor();

    resetReaperSecondaryDriver();

    refreshReaperSecondaryMenus();

}


if(
    reaperSecondaryToggle
    &&
    reaperSecondaryAssignment
){

    reaperSecondaryToggle
    .addEventListener(

        "click",

        async function(event){

            event.preventDefault();


            const isOpen =
            reaperSecondaryAssignment
            .classList
            .contains(
                "is-open"
            );


            /* ==========================================
               CLOSE
            ========================================== */

            if(
                isOpen
            ){

                const hasSelectedSecondary =

                    String(
                        reaperSecondaryTractorID
                        ?.value
                        ||
                        ""
                    )
                    !==
                    ""

                    ||

                    String(
                        reaperSecondaryDriverID
                        ?.value
                        ||
                        ""
                    )
                    !==
                    "";


                if(
                    hasSelectedSecondary
                ){

                    const result =
                    await Swal.fire({

                        icon:
                        "question",

                        title:
                        "Remove Secondary Assignment?",

                        text:
                        "The selected Secondary Tractor and Driver will be cleared.",

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


                resetReaperSecondarySection();

                return;

            }


            /* ==========================================
               OPEN
            ========================================== */

            reaperSecondaryAssignment
            .classList
            .add(
                "is-open"
            );


            reaperSecondaryToggle
            .classList
            .add(
                "is-open"
            );


            const toggleText =
            reaperSecondaryToggle
            .querySelector(
                "span:last-child"
            );


            if(
                toggleText
            ){

                toggleText.textContent =
                "REMOVE SECONDARY ASSIGNMENT";

            }

        }

    );

}


/* ======================================================
   MONTH CALENDAR AVAILABILITY
====================================================== */

function getReaperCalendarViewKey(){

    return (

        reaperCalendarView
        .getFullYear()

        +

        "-"

        +

        reaperPadNumber(
            reaperCalendarView
            .getMonth()
            +
            1
        )

    );

}


async function loadReaperCalendarAvailability(){

    const year =
    reaperCalendarView
    .getFullYear();


    const month =
    reaperCalendarView
    .getMonth()
    +
    1;


    const viewKey =
    year
    +
    "-"
    +
    reaperPadNumber(
        month
    );


    const requestID =
    ++reaperCalendarAvailabilityRequest;


    try{

        const response =
        await fetch(

            "get_reaper_booked_dates.php"

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
            ),

            {
                cache:
                "no-store"
            }

        );


        const data =
        await response.json();


        if(
            requestID
            !==
            reaperCalendarAvailabilityRequest
        ){

            return;

        }


        if(
            !response.ok
            ||
            !data.success
        ){

            throw new Error(

                data.message

                ||

                "Unable to load Reaper calendar availability."

            );

        }


        reaperCalendarAvailability =

        data.dates

        &&

        typeof data.dates
        ===
        "object"

        ?

        data.dates

        :

        {};


        reaperCalendarAvailabilityKey =
        viewKey;

    }
    catch(error){

        console.error(
            "REAPER CALENDAR AVAILABILITY ERROR:",
            error
        );


        if(
            requestID
            !==
            reaperCalendarAvailabilityRequest
        ){

            return;

        }


        reaperCalendarAvailability =
        {};


        reaperCalendarAvailabilityKey =
        viewKey;

    }

}


/* ======================================================
   RENDER REAPER CALENDAR
====================================================== */

function renderReaperCalendar(){

    if(
        !reaperCalendarMonth
        ||
        !reaperCalendarDays
    ){

        return;

    }


    const year =
    reaperCalendarView
    .getFullYear();


    const month =
    reaperCalendarView
    .getMonth();


    reaperCalendarMonth.textContent =
    reaperCalendarView
    .toLocaleDateString(

        "en-US",

        {
            month:
            "long",

            year:
            "numeric"
        }

    );


    reaperCalendarDays.innerHTML =
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


    const currentViewKey =
    getReaperCalendarViewKey();


    const availabilityReady =

    reaperCalendarAvailabilityKey
    ===
    currentViewKey;


    const today =
    getReaperTodayKey();


    /* ==========================================
       EMPTY CALENDAR CELLS
    ========================================== */

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
        "reaper-calendar-empty";


        reaperCalendarDays
        .appendChild(
            empty
        );

    }


    /* ==========================================
       DAYS
    ========================================== */

    for(
        let day = 1;
        day <= daysInMonth;
        day++
    ){

        const dateKey =
        reaperBuildDateKey(
            year,
            month,
            day
        );


        const dateAvailability =

        availabilityReady

        ?

        reaperCalendarAvailability[
            dateKey
        ]

        :

        null;


        const button =
        document.createElement(
            "button"
        );


        button.type =
        "button";


        button.className =
        "reaper-calendar-day";


        button.textContent =
        day;


        button.dataset.date =
        dateKey;


        const status =
        String(
            dateAvailability
            ?.status
            ||
            ""
        );

        /* ==========================================
        PAST DATE / HISTORICAL

        Past dates remain selectable for
        audit / historical back-entry.
        ========================================== */

        const isPastCalendarDate =
        dateKey
        <
        today;


        if(
            isPastCalendarDate
        ){

            button
            .classList
            .add(
                "reaper-calendar-past"
            );


            button.title =
            "Past date. Selectable for audit or historical entry.";

        }


        /* ==========================================
           FULLY BOOKED
        ========================================== */

        if(
            status
            ===
            "Fully Booked"
        ){

            button
            .classList
            .add(
                "reaper-calendar-booked"
            );


            button.disabled =
            true;


            button.title =
            "No available Reaper tractor and driver pair.";

        }


        /* ==========================================
           LIMITED
        ========================================== */

        else if(
            status
            ===
            "Limited"
        ){

            button
            .classList
            .add(
                "reaper-calendar-limited"
            );


            button.title =

            isPastCalendarDate

            ?

            (
                "Past date with limited availability. "
                +
                (
                    dateAvailability
                    ?.available_pairs
                    ??
                    0
                )
                +
                " assignment pair(s) available."
            )

            :

            (
                (
                    dateAvailability
                    ?.available_pairs
                    ??
                    0
                )
                +
                " assignment pair(s) available"
            );

        }


        /* ==========================================
           AVAILABLE
        ========================================== */

        else if(
            status
            ===
            "Available"
        ){

            button
            .classList
            .add(
                "reaper-calendar-available"
            );

        }


        /* ==========================================
           TODAY
        ========================================== */

        if(
            dateKey
            ===
            today
        ){

            button
            .classList
            .add(
                "reaper-calendar-today"
            );

        }


        /* ==========================================
           SELECTED
        ========================================== */

        if(
            reaperDate
            ?.value
            ===
            dateKey
        ){

            button
            .classList
            .add(
                "reaper-calendar-selected"
            );

        }


        /* ==========================================
           DATE SELECTION
        ========================================== */

        if(
            !button.disabled
        ){

            button
            .addEventListener(

                "click",

                async function(event){

                    event.preventDefault();

                    event.stopPropagation();


                    if(
                        !reaperDate
                    ){

                        return;

                    }


                    reaperDate.value =
                    dateKey;


                    reaperDateText.textContent =
                    reaperFormatDate(
                        dateKey
                    );


                    reaperCalendar
                    .classList
                    .remove(
                        "show"
                    );


                    /*
                    Cancel old availability requests.
                    */

                    reaperAvailabilityRequest++;


                    /*
                    Changing date invalidates all previous
                    Tractor / Driver assignments.
                    */

                    resetReaperAssignments();

                    resetReaperSecondarySection();


                    disableReaperAssignments(
                        "Loading Availability..."
                    );


                    /*
                    Update Reaper-specific lifecycle.
                    */

                    refreshReaperAddLifecycle();


                    renderReaperCalendar();


                    clearReaperInlineError(
                        reaperDateBtn
                    );


                    await loadReaperAssignmentAvailability(
                        dateKey
                    );

                }

            );

        }


        reaperCalendarDays
        .appendChild(
            button
        );

    }

}


/* ======================================================
   POSITION CALENDAR
====================================================== */

function positionReaperCalendar(){

    if(
        !reaperCalendar
        ||
        !reaperDateBtn
    ){

        return;

    }


    const rect =
    reaperDateBtn
    .getBoundingClientRect();


    const calendarWidth =
    reaperCalendar.offsetWidth
    ||
    350;


    const calendarHeight =
    reaperCalendar.offsetHeight
    ||
    390;


    const gap =
    8;


    let left =
    rect.left;


    let top =
    rect.bottom
    +
    gap;


    if(
        left
        +
        calendarWidth
        >
        window.innerWidth
        -
        12
    ){

        left =
        window.innerWidth
        -
        calendarWidth
        -
        12;

    }


    if(
        left
        <
        12
    ){

        left =
        12;

    }


    /*
    Open upward if there is not enough room below.
    */

    if(
        top
        +
        calendarHeight
        >
        window.innerHeight
        -
        12
    ){

        top =
        rect.top
        -
        calendarHeight
        -
        gap;

    }


    if(
        top
        <
        12
    ){

        top =
        12;

    }


    reaperCalendar.style.left =
    left
    +
    "px";


    reaperCalendar.style.top =
    top
    +
    "px";

}


/* ======================================================
   OPEN CALENDAR
====================================================== */

if(
    reaperDateBtn
){

    reaperDateBtn
    .addEventListener(

        "click",

        async function(event){

            event.preventDefault();

            event.stopPropagation();


            closeReaperAssignmentMenus();


            reaperStatusMenu
            ?.classList
            .remove(
                "show"
            );


            reaperRemarksMenu
            ?.classList
            .remove(
                "show"
            );


            const willOpen =

            !reaperCalendar
            ?.classList
            .contains(
                "show"
            );


            if(
                !willOpen
            ){

                reaperCalendar
                ?.classList
                .remove(
                    "show"
                );


                return;

            }


            await loadReaperCalendarAvailability();


            renderReaperCalendar();


            reaperCalendar
            ?.classList
            .add(
                "show"
            );


            requestAnimationFrame(
                positionReaperCalendar
            );

        }

    );

}


/* ======================================================
   PREVIOUS MONTH
====================================================== */

reaperCalendarPrev
?.addEventListener(

    "click",

    async function(event){

        event.preventDefault();

        event.stopPropagation();


        reaperCalendarView
        .setMonth(

            reaperCalendarView
            .getMonth()
            -
            1

        );


        reaperCalendarAvailabilityKey =
        "";


        await loadReaperCalendarAvailability();


        renderReaperCalendar();


        requestAnimationFrame(
            positionReaperCalendar
        );

    }

);


/* ======================================================
   NEXT MONTH
====================================================== */

reaperCalendarNext
?.addEventListener(

    "click",

    async function(event){

        event.preventDefault();

        event.stopPropagation();


        reaperCalendarView
        .setMonth(

            reaperCalendarView
            .getMonth()
            +
            1

        );


        reaperCalendarAvailabilityKey =
        "";


        await loadReaperCalendarAvailability();


        renderReaperCalendar();


        requestAnimationFrame(
            positionReaperCalendar
        );

    }

);


/* ======================================================
   ADD STATUS / REMARKS DROPDOWNS
====================================================== */

setupReaperDropdown(

    reaperStatusBtn,

    reaperStatusMenu,

    reaperStatus,

    reaperStatusText,

    "#reaperStatusMenu .reaper-status-item"

);


setupReaperDropdown(

    reaperRemarksBtn,

    reaperRemarksMenu,

    reaperRemarks,

    reaperRemarksText,

    "#reaperRemarksMenu .reaper-remarks-item"

);


/* ======================================================
   STATUS CHANGE
====================================================== */

reaperStatusMenu
?.querySelectorAll(
    ".reaper-status-item"
)
.forEach(
function(item){

    item.addEventListener(

        "click",

        function(){

            window.setTimeout(

                applyReaperAddRemarksRules,

                0

            );

        }

    );

});


/* ======================================================
   REMARKS CHANGE
====================================================== */

reaperRemarksMenu
?.querySelectorAll(
    ".reaper-remarks-item"
)
.forEach(
function(item){

    item.addEventListener(

        "click",

        function(){

            window.setTimeout(

                applyReaperAddRemarksRules,

                0

            );

        }

    );

});


/* ======================================================
   COMPLETION INPUT CHANGES
====================================================== */

[
    reaperHarvest,
    reaperCommodity
]
.forEach(
function(field){

    field
    ?.addEventListener(

        "input",

        function(){

            applyReaperAddServiceDataRules();

            applyReaperAddStatusRules();

            applyReaperAddRemarksRules();

            clearReaperInlineError(
                field
            );

            if(
                reaperAddCompletionReady()
            ){

                clearReaperInlineError(
                    reaperStatusBtn
                );

            }

        }

    );

});


/* ======================================================
   INLINE VALIDATION
====================================================== */

function getReaperErrorContainer(
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
   CLEAR ONE FIELD ERROR
====================================================== */

function clearReaperInlineError(
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
    getReaperErrorContainer(
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
        ".reaper-field-error"
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
   SET ONE FIELD ERROR
====================================================== */

function setReaperInlineError(
    control,
    message
){

    if(
        !control
    ){

        return;

    }


    clearReaperInlineError(
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
    getReaperErrorContainer(
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
    "reaper-field-error";


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
   CLEAR ALL INLINE ERRORS
====================================================== */

function clearReaperInlineErrors(){

    document
    .querySelectorAll(
        "#addReaperModal .is-invalid"
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
        "#addReaperModal .reaper-field-error"
    )
    .forEach(
    function(error){

        error.remove();

    });

}


/* ======================================================
   FOCUS FIRST INVALID FIELD
====================================================== */

function focusReaperInvalidField(
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

            control.focus();

        },

        220

    );

}


/* ======================================================
   REMOVE ERRORS WHILE CORRECTING FIELDS
====================================================== */

[
    reaperCustomerInput,
    reaperLocation,
    reaperHarvest,
    reaperCommodity
]
.forEach(
function(field){

    field
    ?.addEventListener(

        "input",

        function(){

            clearReaperInlineError(
                field
            );

        }

    );

});


[
    reaperAffiliateBtn,
    reaperDateBtn,
    reaperTractorBtn,
    reaperPrimaryDriverBtn,
    reaperSecondaryTractorBtn,
    reaperSecondaryDriverBtn,
    reaperStatusBtn,
    reaperRemarksBtn
]
.forEach(
function(button){

    button
    ?.addEventListener(

        "click",

        function(){

            clearReaperInlineError(
                button
            );

        }

    );

});


/* ======================================================
   FIND RESOURCES BY ID
====================================================== */

function findReaperTractorByID(
    tractors,
    tractorID
){

    return tractors.find(

        function(tractor){

            return String(
                tractor.tractor_id
            )
            ===
            String(
                tractorID
            );

        }

    )
    ||
    null;

}


function findReaperDriverByID(
    drivers,
    driverID
){

    return drivers.find(

        function(driver){

            return String(
                driver.driver_id
            )
            ===
            String(
                driverID
            );

        }

    )
    ||
    null;

}


/* ======================================================
   FINAL RESOURCE RECHECK BEFORE SAVE

   Another user may create a Rental while this modal is
   open, so availability is checked again before saving.
====================================================== */

async function validateReaperAssignmentsBeforeSave(){

    const rentalDate =
    reaperDate
    ?.value
    ||
    "";


    if(
        rentalDate
        ===
        ""
    ){

        return false;

    }


    const primaryTractorID =
    reaperTractorID
    ?.value
    ||
    "";


    const primaryDriverID =
    reaperPrimaryDriverID
    ?.value
    ||
    "";


    const secondaryTractorID =
    reaperSecondaryTractorID
    ?.value
    ||
    "";


    const secondaryDriverID =
    reaperSecondaryDriverID
    ?.value
    ||
    "";


    const [
        tractorData,
        driverData
    ] =

    await Promise.all([

        getReaperTractorAvailability(
            rentalDate
        ),

        getReaperDriverAvailability(
            rentalDate
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


    /* ==========================================
       PRIMARY TRACTOR
    ========================================== */

    const primaryTractor =
    findReaperTractorByID(

        tractors,

        primaryTractorID

    );


    if(
        !primaryTractor
        ||
        primaryTractor.available
        !==
        true
    ){

        await loadReaperAssignmentAvailability(
            rentalDate
        );


        await Swal.fire({

            icon:
            "warning",

            title:
            "Primary Tractor Unavailable",

            text:
            "The selected Primary Tractor is no longer available on this date. Please select another tractor.",

            confirmButtonColor:
            "#8CA15B"

        });


        return false;

    }


    /* ==========================================
       PRIMARY DRIVER
    ========================================== */

    const primaryDriver =
    findReaperDriverByID(

        drivers,

        primaryDriverID

    );


    if(
        !primaryDriver
        ||
        primaryDriver.available
        !==
        true
    ){

        await loadReaperAssignmentAvailability(
            rentalDate
        );


        await Swal.fire({

            icon:
            "warning",

            title:
            "Primary Driver Unavailable",

            text:
            "The selected Primary Driver is no longer available on this date. Please select another driver.",

            confirmButtonColor:
            "#8CA15B"

        });


        return false;

    }


    /* ==========================================
       SECONDARY TRACTOR
    ========================================== */

    if(
        secondaryTractorID
        !==
        ""
    ){

        const secondaryTractor =
        findReaperTractorByID(

            tractors,

            secondaryTractorID

        );


        if(
            !secondaryTractor
            ||
            secondaryTractor.available
            !==
            true
        ){

            await loadReaperAssignmentAvailability(
                rentalDate
            );


            await Swal.fire({

                icon:
                "warning",

                title:
                "Secondary Tractor Unavailable",

                text:
                "The selected Secondary Tractor is no longer available on this date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return false;

        }

    }


    /* ==========================================
       SECONDARY DRIVER
    ========================================== */

    if(
        secondaryDriverID
        !==
        ""
    ){

        const secondaryDriver =
        findReaperDriverByID(

            drivers,

            secondaryDriverID

        );


        if(
            !secondaryDriver
            ||
            secondaryDriver.available
            !==
            true
        ){

            await loadReaperAssignmentAvailability(
                rentalDate
            );


            await Swal.fire({

                icon:
                "warning",

                title:
                "Secondary Driver Unavailable",

                text:
                "The selected Secondary Driver is no longer available on this date.",

                confirmButtonColor:
                "#8CA15B"

            });


            return false;

        }

    }


    return true;

}


/* ======================================================
   PREVENT NORMAL FORM SUBMIT
====================================================== */

reaperForm
?.addEventListener(

    "submit",

    function(event){

        event.preventDefault();

    }

);


/* ======================================================
   SAVE REAPER
====================================================== */

if(
    reaperForm
    &&
    saveReaperBtn
){

    saveReaperBtn
    .addEventListener(

        "click",

        async function(){


            /* ==========================================
               REFRESH CURRENT RULES
            ========================================== */

            clearReaperInlineErrors();


            refreshReaperAddLifecycle();


            let firstInvalidControl =
            null;


            let hasValidationError =
            false;


            function markInvalid(
                control,
                message
            ){

                if(
                    !control
                ){

                    return;

                }


                setReaperInlineError(
                    control,
                    message
                );


                if(
                    !firstInvalidControl
                ){

                    firstInvalidControl =
                    control;

                }


                hasValidationError =
                true;

            }


            /* ==========================================
               AFFILIATE
            ========================================== */

            if(
                !reaperAffiliate
                ||
                reaperAffiliate.value
                ===
                ""
            ){

                markInvalid(

                    reaperAffiliateBtn,

                    "Please select an affiliate."

                );

            }


            /* ==========================================
               CUSTOMER NAME
            ========================================== */

            if(
                reaperAffiliate
                ?.value
                !==
                ""

                &&

                (
                    !reaperCustomerInput
                    ||
                    reaperCustomerInput.value
                    .trim()
                    ===
                    ""
                )
            ){

                markInvalid(

                    reaperCustomerInput,

                    "Customer name is required."

                );

            }


            /* ==========================================
               SERVICE LOCATION
            ========================================== */

            if(
                !reaperLocation
                ||
                reaperLocation.value
                .trim()
                ===
                ""
            ){

                markInvalid(

                    reaperLocation,

                    "Service location is required."

                );

            }


            /* ==========================================
               RENTAL DATE
            ========================================== */

            if(
                !reaperDate
                ||
                reaperDate.value
                ===
                ""
            ){

                markInvalid(

                    reaperDateBtn,

                    "Please select a Rental date."

                );

            }


            /* ==========================================
               PRIMARY TRACTOR / DRIVER
            ========================================== */

            if(
                reaperDate
                ?.value
            ){

                if(
                    !reaperTractorID
                    ||
                    reaperTractorID.value
                    ===
                    ""
                ){

                    markInvalid(

                        reaperTractorBtn,

                        "Please select a Primary Tractor."

                    );

                }


                if(
                    !reaperPrimaryDriverID
                    ||
                    reaperPrimaryDriverID.value
                    ===
                    ""
                ){

                    markInvalid(

                        reaperPrimaryDriverBtn,

                        "Please select a Primary Driver."

                    );

                }

            }


            /* ==========================================
               SECONDARY ASSIGNMENT
            ========================================== */

            const secondaryTractorID =
            reaperSecondaryTractorID
            ?.value
            ||
            "";


            const secondaryDriverID =
            reaperSecondaryDriverID
            ?.value
            ||
            "";


            const hasSecondaryTractor =
            secondaryTractorID
            !==
            "";


            const hasSecondaryDriver =
            secondaryDriverID
            !==
            "";


            /* ==========================================
               SECONDARY PAIR
            ========================================== */

            if(
                hasSecondaryTractor
                !==
                hasSecondaryDriver
            ){

                markInvalid(

                    reaperSecondaryTractorBtn,

                    "Secondary Tractor and Secondary Driver must be selected together."

                );


                markInvalid(

                    reaperSecondaryDriverBtn,

                    "Secondary Tractor and Secondary Driver must be selected together."

                );

            }


            /* ==========================================
               DUPLICATE TRACTOR
            ========================================== */

            if(
                hasSecondaryTractor

                &&

                String(
                    secondaryTractorID
                )
                ===
                String(
                    reaperTractorID
                    ?.value
                    ||
                    ""
                )
            ){

                markInvalid(

                    reaperSecondaryTractorBtn,

                    "Secondary Tractor must be different from the Primary Tractor."

                );

            }


            /* ==========================================
               DUPLICATE DRIVER
            ========================================== */

            if(
                hasSecondaryDriver

                &&

                String(
                    secondaryDriverID
                )
                ===
                String(
                    reaperPrimaryDriverID
                    ?.value
                    ||
                    ""
                )
            ){

                markInvalid(

                    reaperSecondaryDriverBtn,

                    "Secondary Driver must be different from the Primary Driver."

                );

            }


            /* ==========================================
               NO. OF HARVEST
            ========================================== */

            const harvestRaw =
            String(
                reaperHarvest
                ?.value
                ??
                ""
            )
            .trim();


            if(
                harvestRaw
                !==
                ""
            ){

                const harvest =
                Number(
                    harvestRaw
                );


                if(
                    !Number.isInteger(
                        harvest
                    )
                    ||
                    harvest
                    <
                    1
                ){

                    markInvalid(

                        reaperHarvest,

                        "No. of Harvest must be a whole number of at least 1."

                    );

                }

            }


            /* ==========================================
               COMMODITY
            ========================================== */

            const commodityRaw =
            String(
                reaperCommodity
                ?.value
                ??
                ""
            )
            .trim();


            if(
                commodityRaw
                !==
                ""
            ){

                const commodity =
                Number(
                    commodityRaw
                );


                if(
                    !Number.isInteger(
                        commodity
                    )
                    ||
                    commodity
                    <=
                    0
                ){

                    markInvalid(

                        reaperCommodity,

                        "Commodity must be a whole number of at least 1."

                    );

                }

            }


            /* ==========================================
               STATUS
            ========================================== */

            if(
                reaperDate
                ?.value

                &&

                (
                    !reaperStatus
                    ||
                    reaperStatus.value
                    ===
                    ""
                )
            ){

                markInvalid(

                    reaperStatusBtn,

                    "Please select the Rental status."

                );

            }


            /* ==========================================
               DONE REQUIRES COMPLETION
            ========================================== */

            if(
                reaperStatus
                ?.value
                ===
                "Done"

                &&

                !reaperAddCompletionReady()
            ){

                markInvalid(

                    reaperHarvest,

                    "No. of Harvest is required before marking this rental Done."

                );


                markInvalid(

                    reaperCommodity,

                    "Commodity is required before marking this rental Done."

                );

            }


            /* ==========================================
               PAID REQUIRES DONE
            ========================================== */

            if(
                reaperRemarks
                ?.value
                ===
                "Paid"

                &&

                (
                    reaperStatus
                    ?.value
                    !==
                    "Done"

                    ||

                    !reaperAddCompletionReady()
                )
            ){

                markInvalid(

                    reaperRemarksBtn,

                    "Paid is allowed only after the rental is Done and completion data is complete."

                );

            }


            /* ==========================================
               STOP ON FORM VALIDATION
            ========================================== */

            if(
                hasValidationError
            ){

                focusReaperInvalidField(
                    firstInvalidControl
                );


                return;

            }


            /* ==========================================
               MEMBER VALIDATION
            ========================================== */

            if(
                reaperAffiliate.value
                ===
                "Member"
            ){

                try{

                    const exactMember =
                    await findExactReaperMember(

                        reaperCustomerInput
                        .value

                    );


                    if(
                        !exactMember
                    ){

                        if(
                            reaperMemberId
                        ){

                            reaperMemberId.value =
                            "";

                        }


                        setReaperInlineError(

                            reaperCustomerInput,

                            "Select a valid registered Member from the suggestions."

                        );


                        focusReaperInvalidField(
                            reaperCustomerInput
                        );


                        return;

                    }


                    reaperMemberId.value =
                    exactMember.id;


                    reaperCustomerInput.value =
                    exactMember.name;


                    clearReaperInlineError(
                        reaperCustomerInput
                    );

                }
                catch(error){

                    console.error(
                        "REAPER MEMBER VALIDATION ERROR:",
                        error
                    );


                    await Swal.fire({

                        icon:
                        "error",

                        title:
                        "Member Validation Failed",

                        text:
                        "Unable to verify the Member. Please try again.",

                        confirmButtonColor:
                        "#B71C1C"

                    });


                    return;

                }

            }
            else if(
                reaperMemberId
            ){

                reaperMemberId.value =
                "";

            }


            /* ==========================================
               RECHECK TRACTOR + DRIVER AVAILABILITY
            ========================================== */

            try{

                const assignmentsValid =
                await validateReaperAssignmentsBeforeSave();


                if(
                    !assignmentsValid
                ){

                    return;

                }

            }
            catch(error){

                console.error(
                    "REAPER FINAL AVAILABILITY ERROR:",
                    error
                );


                await Swal.fire({

                    icon:
                    "error",

                    title:
                    "Availability Check Failed",

                    text:
                    error.message
                    ||
                    "Unable to verify Rental availability.",

                    confirmButtonColor:
                    "#B71C1C"

                });


                return;

            }


            /* ==========================================
               CONFIRMATION
            ========================================== */

            const confirmation =
            await Swal.fire({

                icon:
                "question",

                title:
                "Add Reaper Rental?",

                text:
                "Are you sure you want to add this Reaper rental?",

                showCancelButton:
                true,

                confirmButtonText:
                "Yes, Add",

                cancelButtonText:
                "Cancel",

                confirmButtonColor:
                "#8CA15B",

                cancelButtonColor:
                "#777777",

                reverseButtons:
                true

            });


            if(
                !confirmation.isConfirmed
            ){

                return;

            }


            /* ==========================================
               SAVE
            ========================================== */

            saveReaperBtn.disabled =
            true;


            const originalButtonText =
            saveReaperBtn.textContent;


            saveReaperBtn.textContent =
            "SAVING...";


            try{

                const formData =
                new FormData(
                    reaperForm
                );


                const response =
                await fetch(

                    "save/save_reaper.php",

                    {
                        method:
                        "POST",

                        body:
                        formData
                    }

                );


                const data =
                await response.text();


                console.log(
                    "SAVE REAPER:",
                    data
                );


                /* ======================================
                   SUCCESS
                ====================================== */

                if(
                    response.ok
                    &&
                    data.trim()
                    ===
                    "success"
                ){

                    const modalElement =
                    document.getElementById(
                        "addReaperModal"
                    );


                    const modalInstance =

                    modalElement

                    ?

                    bootstrap.Modal
                    .getInstance(
                        modalElement
                    )

                    :

                    null;


                    modalInstance
                    ?.hide();


                    await Swal.fire({

                        icon:
                        "success",

                        title:
                        "Reaper Rental Added",

                        text:
                        "The Reaper rental was added successfully.",

                        showConfirmButton:
                        false,

                        timer:
                        1500

                    });

                    window.location
                    .reload();


                    return;

                }


                /* ======================================
                   BACKEND ERROR
                ====================================== */

                await Swal.fire({

                    icon:
                    "error",

                    title:
                    "Save Failed",

                    text:
                    data.trim()
                    ||
                    "Unable to save the Reaper rental.",

                    confirmButtonColor:
                    "#B71C1C"

                });

            }
            catch(error){

                console.error(
                    "SAVE REAPER ERROR:",
                    error
                );


                await Swal.fire({

                    icon:
                    "error",

                    title:
                    "Save Failed",

                    text:
                    "Unable to save the Reaper rental. Please try again.",

                    confirmButtonColor:
                    "#B71C1C"

                });

            }
            finally{

                saveReaperBtn.disabled =
                false;


                saveReaperBtn.textContent =
                originalButtonText;

            }

        }

    );

}

/* ======================================================
   CLEAR STATUS ERROR WHEN CLICKING OUTSIDE STATUS
====================================================== */

document.addEventListener(

    "click",

    function(event){

        const clickedInsideStatus =
        event.target
        ?.closest(
            ".reaper-status-dropdown"
        );


        if(
            !clickedInsideStatus
        ){

            clearReaperInlineError(
                reaperStatusBtn
            );

        }

    },

    true

);


/* ======================================================
   GLOBAL CLOSE EVENTS
====================================================== */

document
.addEventListener(

    "click",

    function(){

        reaperAffiliateMenu
        ?.classList
        .remove(
            "show"
        );


        reaperCalendar
        ?.classList
        .remove(
            "show"
        );


        reaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        reaperRemarksMenu
        ?.classList
        .remove(
            "show"
        );


        closeReaperAssignmentMenus();

    }

);


reaperCalendar
?.addEventListener(

    "click",

    function(event){

        event.stopPropagation();

    }

);


/* ======================================================
   KEEP CALENDAR INSIDE VIEWPORT
====================================================== */

window
.addEventListener(

    "resize",

    function(){

        if(
            reaperCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            positionReaperCalendar();

        }

    }

);


window
.addEventListener(

    "scroll",

    function(){

        if(
            reaperCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            positionReaperCalendar();

        }

    },

    true

);


/* ======================================================
   INITIAL ADD MODAL STATE
====================================================== */

function initializeReaperAddModal(){

    reaperTractors =
    [];


    reaperDrivers =
    [];


    reaperAvailabilityRequest++;


    /* ==========================================
       DATE
    ========================================== */

    if(
        reaperDate
    ){

        reaperDate.value =
        "";

    }


    if(
        reaperDateText
    ){

        reaperDateText.textContent =
        "Select Rental Date";

    }


    /* ==========================================
       ASSIGNMENTS
    ========================================== */

    resetReaperAssignments();

    resetReaperSecondarySection();

    disableReaperAssignments();


    /* ==========================================
       COMPLETION
    ========================================== */

    if(
        reaperHarvest
    ){

        reaperHarvest.value =
        "";

        reaperHarvest.disabled =
        true;

    }


    if(
        reaperCommodity
    ){

        reaperCommodity.value =
        "";

        reaperCommodity.disabled =
        true;

    }


    /* ==========================================
       STATUS
    ========================================== */

    resetReaperAddStatus();


    /* ==========================================
       REMARKS
    ========================================== */

    if(
        reaperRemarks
    ){

        reaperRemarks.value =
        "Unpaid";

    }


    if(
        reaperRemarksText
    ){

        reaperRemarksText.textContent =
        "Unpaid";

    }


    setReaperRemarksButtonLocked(

        reaperRemarksBtn,

        true

    );


    setReaperAddRemarksItem(

        "Unpaid",

        true

    );


    setReaperAddRemarksItem(

        "Paid",

        false

    );


    /* ==========================================
       CALENDAR
    ========================================== */

    reaperCalendarView =
    new Date();


    reaperCalendarView
    .setDate(
        1
    );


    reaperCalendarAvailability =
    {};


    reaperCalendarAvailabilityKey =
    "";


    renderReaperCalendar();

}


/* ======================================================
   INITIALIZE
====================================================== */

initializeReaperAddModal();


/* ======================================================
   RESET ADD MODAL AFTER CLOSE
====================================================== */

const addReaperModalElement =
document.getElementById(
    "addReaperModal"
);


addReaperModalElement
?.addEventListener(

    "hidden.bs.modal",

    function(){

        reaperAvailabilityRequest++;

        reaperCalendarAvailabilityRequest++;


        reaperForm
        ?.reset();


        clearReaperInlineErrors();


        /* ==========================================
           AFFILIATE
        ========================================== */

        if(
            reaperAffiliate
        ){

            reaperAffiliate.value =
            "";

        }


        if(
            reaperAffiliateText
        ){

            reaperAffiliateText.textContent =
            "Select Affiliate";

        }


        /* ==========================================
           CUSTOMER
        ========================================== */

        if(
            reaperCustomerInput
        ){

            reaperCustomerInput.value =
            "";

            reaperCustomerInput.disabled =
            true;

            reaperCustomerInput.placeholder =
            "Select affiliate first";

        }


        if(
            reaperMemberId
        ){

            reaperMemberId.value =
            "";

        }


        if(
            reaperMemberSuggestion
        ){

            reaperMemberSuggestion.innerHTML =
            "";

            reaperMemberSuggestion.style.display =
            "none";

        }


        /* ==========================================
           LOCATION
        ========================================== */

        if(
            reaperLocation
        ){

            reaperLocation.value =
            "";

        }


        initializeReaperAddModal();

    }

);

/* ======================================================
   EDIT REAPER RENTAL

   ASSIGNMENT FLOW
   - Rental Date stays read-only in Edit
   - Primary Tractor + Primary Driver
   - Optional Secondary Tractor + Secondary Driver
   - Driver Registry only
   - Current Reaper record is excluded from availability checks

   REAPER LIFECYCLE
   - Pending / Ongoing = Unpaid only
   - Done = Unpaid or Paid
   - Done requires Harvest + Commodity
   - Paid = finalized and locked
====================================================== */


/* ======================================================
   EDIT ELEMENTS
====================================================== */

const editReaperModalElement =
document.getElementById(
    "editReaperModal"
);


const editReaperForm =
document.getElementById(
    "editReaperForm"
);


const editReaperID =
document.getElementById(
    "editReaperID"
);


const editReaperAffiliate =
document.getElementById(
    "editReaperAffiliate"
);


const editReaperAffiliateText =
document.getElementById(
    "editReaperAffiliateText"
);


const editReaperCustomerName =
document.getElementById(
    "editReaperCustomerName"
);


const editReaperLocation =
document.getElementById(
    "editReaperLocation"
);


const editReaperDate =
document.getElementById(
    "editReaperDate"
);


const editReaperDateBtn =
document.getElementById(
    "editReaperDateBtn"
);


const editReaperDateText =
document.getElementById(
    "editReaperDateText"
);


const editReaperCalendar =
document.getElementById(
    "editReaperCalendar"
);


const editReaperCalendarPrev =
document.getElementById(
    "editReaperCalendarPrev"
);


const editReaperCalendarMonth =
document.getElementById(
    "editReaperCalendarMonth"
);


const editReaperCalendarNext =
document.getElementById(
    "editReaperCalendarNext"
);


const editReaperCalendarDays =
document.getElementById(
    "editReaperCalendarDays"
);


const editReaperTransactionBadge =
document.getElementById(
    "editReaperTransactionBadge"
);


/* ======================================================
   PRIMARY ASSIGNMENT
====================================================== */

const editReaperTractorID =
document.getElementById(
    "editReaperTractorID"
);


const editReaperTractorNo =
document.getElementById(
    "editReaperTractorNo"
);


const editReaperTractorBtn =
document.getElementById(
    "editReaperTractorBtn"
);


const editReaperTractorText =
document.getElementById(
    "editReaperTractorText"
);


const editReaperTractorMenu =
document.getElementById(
    "editReaperTractorMenu"
);


const editReaperPrimaryDriverID =
document.getElementById(
    "editReaperPrimaryDriverID"
);


const editReaperPrimaryDriverBtn =
document.getElementById(
    "editReaperPrimaryDriverBtn"
);


const editReaperPrimaryDriverText =
document.getElementById(
    "editReaperPrimaryDriverText"
);


const editReaperPrimaryDriverMenu =
document.getElementById(
    "editReaperPrimaryDriverMenu"
);


/* ======================================================
   SECONDARY ASSIGNMENT
====================================================== */

const editReaperSecondaryToggle =
document.getElementById(
    "editReaperSecondaryToggle"
);


const editReaperSecondaryToggleText =
document.getElementById(
    "editReaperSecondaryToggleText"
);


const editReaperSecondaryAssignment =
document.getElementById(
    "editReaperSecondaryAssignment"
);


const editReaperSecondaryTractorID =
document.getElementById(
    "editReaperSecondaryTractorID"
);


const editReaperSecondaryTractorNo =
document.getElementById(
    "editReaperSecondaryTractorNo"
);


const editReaperSecondaryTractorBtn =
document.getElementById(
    "editReaperSecondaryTractorBtn"
);


const editReaperSecondaryTractorText =
document.getElementById(
    "editReaperSecondaryTractorText"
);


const editReaperSecondaryTractorMenu =
document.getElementById(
    "editReaperSecondaryTractorMenu"
);


const editReaperSecondaryDriverID =
document.getElementById(
    "editReaperSecondaryDriverID"
);


const editReaperSecondaryDriverBtn =
document.getElementById(
    "editReaperSecondaryDriverBtn"
);


const editReaperSecondaryDriverText =
document.getElementById(
    "editReaperSecondaryDriverText"
);


const editReaperSecondaryDriverMenu =
document.getElementById(
    "editReaperSecondaryDriverMenu"
);


/* ======================================================
   SERVICE / STATUS / PAYMENT
====================================================== */

const editReaperHarvest =
document.getElementById(
    "editReaperHarvest"
);


const editReaperCommodity =
document.getElementById(
    "editReaperCommodity"
);


const editReaperStatusBtn =
document.getElementById(
    "editReaperStatusBtn"
);


const editReaperStatusMenu =
document.getElementById(
    "editReaperStatusMenu"
);


const editReaperStatusText =
document.getElementById(
    "editReaperStatusText"
);


const editReaperStatus =
document.getElementById(
    "editReaperStatus"
);


const editReaperRemarksBtn =
document.getElementById(
    "editReaperRemarksBtn"
);


const editReaperRemarksMenu =
document.getElementById(
    "editReaperRemarksMenu"
);


const editReaperRemarksText =
document.getElementById(
    "editReaperRemarksText"
);


const editReaperRemarks =
document.getElementById(
    "editReaperRemarks"
);


const editReaperLifecycleHint =
document.getElementById(
    "editReaperLifecycleHint"
);


const updateReaperBtn =
document.getElementById(
    "updateReaperBtn"
);


/* ======================================================
   EDIT STATE
====================================================== */

let editReaperLoadedRecord =
null;


let editReaperTractors =
[];


let editReaperDrivers =
[];


let editReaperAvailabilityRequest =
0;


let editReaperAvailabilityReady =
false;


let editReaperCalendarView =
new Date();


editReaperCalendarView.setDate(
    1
);


let editReaperCalendarAvailability =
{};


let editReaperCalendarAvailabilityKey =
"";


let editReaperCalendarRequest =
0;


let editReaperOriginalStatus =
"";


let editReaperOriginalRemarks =
"";


let editReaperRentalDate =
"";


let editReaperPrimaryTractorStatus =
"";


/* ======================================================
   SMALL HELPERS
====================================================== */

function editReaperString(
    value
){

    return String(
        value
        ??
        ""
    )
    .trim();

}

function editReaperNeedsReschedule(){

    const originalRentalDate =
    editReaperString(
        editReaperLoadedRecord?.rental_date
    );


    const currentRentalDate =
    editReaperString(
        editReaperRentalDate
        ||
        editReaperDate?.value
    );


    const currentStatus =
    editReaperString(
        editReaperStatus?.value
        ||
        editReaperOriginalStatus
    );


    const originalNeedsReschedule =
    originalRentalDate !== ""
    &&
    originalRentalDate < getReaperTodayKey()
    &&
    (
        (
            editReaperOriginalStatus === "Pending"
            &&
            currentStatus === "Pending"
        )
        ||
        (
            editReaperOriginalStatus === "Ongoing"
            &&
            currentStatus === "Ongoing"
        )
    );


    return (
        originalNeedsReschedule
        &&
        currentRentalDate === originalRentalDate
    );

}


function editReaperInt(
    value
){

    return (
        parseInt(
            value,
            10
        )
        ||
        0
    );

}


function editReaperAssignmentLabel(
    number,
    name
){

    const cleanNumber =
    editReaperString(
        number
    );


    const cleanName =
    editReaperString(
        name
    );


    if(
        cleanNumber
        &&
        cleanName
    ){

        return (
            cleanNumber
            +
            " - "
            +
            cleanName
        );

    }


    return (
        cleanNumber
        ||
        cleanName
        ||
        "—"
    );

}


function findEditReaperTractor(
    tractorID
){

    return (

        editReaperTractors
        .find(
        function(
            tractor
        ){

            return (
                String(
                    tractor.tractor_id
                )
                ===
                String(
                    tractorID
                )
            );

        })

        ||

        null

    );

}


function findEditReaperDriver(
    driverID
){

    return (

        editReaperDrivers
        .find(
        function(
            driver
        ){

            return (
                String(
                    driver.driver_id
                )
                ===
                String(
                    driverID
                )
            );

        })

        ||

        null

    );

}


/* ======================================================
   LIFECYCLE HINT
====================================================== */

function setReaperEditLifecycleHint(
    message,
    icon = "bi-info-circle"
){

    if(
        !editReaperLifecycleHint
    ){

        return;

    }


    const iconElement =
    editReaperLifecycleHint
    .querySelector(
        "i"
    );


    const textElement =
    editReaperLifecycleHint
    .querySelector(
        "span"
    );


    if(
        iconElement
    ){

        iconElement.className =
        "bi "
        +
        icon;

    }


    if(
        textElement
    ){

        textElement.textContent =
        message;

    }

}


/* ======================================================
   STATUS / REMARKS ITEM STATE
====================================================== */

function setReaperEditItemState(
    menuId,
    itemClass,
    value,
    disabled,
    reason = ""
){

    const item =
    document.querySelector(

        "#"
        +
        menuId
        +
        " ."
        +
        itemClass
        +
        '[data-value="'
        +
        value
        +
        '"]'

    );


    if(
        !item
    ){

        return;

    }


    item.dataset.disabled =
    disabled
    ?
    "1"
    :
    "0";


    item.setAttribute(
        "aria-disabled",
        disabled
        ?
        "true"
        :
        "false"
    );


    item.style.opacity =
    disabled
    ?
    ".42"
    :
    "1";


    item.style.cursor =
    disabled
    ?
    "not-allowed"
    :
    "pointer";


    item.style.color =
    disabled
    ?
    "#999"
    :
    "";


    item.dataset.disabledMessage =
    disabled
    ?
    reason
    :
    "";


    item.removeAttribute(
        "title"
    );

}


function setReaperEditStatusItem(
    value,
    disabled,
    reason = ""
){

    setReaperEditItemState(

        "editReaperStatusMenu",

        "reaper-status-item",

        value,

        disabled,

        reason

    );

}


function setReaperEditRemarksItem(
    value,
    disabled,
    reason = ""
){

    setReaperEditItemState(

        "editReaperRemarksMenu",

        "reaper-remarks-item",

        value,

        disabled,

        reason

    );

}

/* ======================================================
   EDIT / RESCHEDULE CALENDAR
====================================================== */

function getEditReaperCalendarViewKey(){

    return (
        editReaperCalendarView.getFullYear()
        +
        "-"
        +
        reaperPadNumber(
            editReaperCalendarView.getMonth() + 1
        )
    );

}


async function loadEditReaperCalendarAvailability(){

    const reaperID =
    editReaperInt(
        editReaperID?.value
    );


    if(reaperID <= 0){

        return false;

    }


    const year =
    editReaperCalendarView.getFullYear();


    const month =
    editReaperCalendarView.getMonth() + 1;


    const viewKey =
    year
    +
    "-"
    +
    reaperPadNumber(
        month
    );


    const requestID =
    ++editReaperCalendarRequest;


    try{

        const response =
        await fetch(

            "get_reaper_booked_dates.php"
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
                reaperID
            )
            +
            "&primary_tractor_id="
            +
            encodeURIComponent(
                editReaperTractorID?.value
                ||
                ""
            )
            +
            "&secondary_tractor_id="
            +
            encodeURIComponent(
                editReaperSecondaryTractorID?.value
                ||
                ""
            )
            +
            "&primary_driver_id="
            +
            encodeURIComponent(
                editReaperPrimaryDriverID?.value
                ||
                ""
            )
            +
            "&secondary_driver_id="
            +
            encodeURIComponent(
                editReaperSecondaryDriverID?.value
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
                "Unable to load Reaper calendar availability."
            );

        }


        if(
            requestID
            !==
            editReaperCalendarRequest
        ){

            return false;

        }


        editReaperCalendarAvailability =

        result.dates
        &&
        typeof result.dates
        ===
        "object"

        ?

        result.dates

        :

        {};


        editReaperCalendarAvailabilityKey =
        viewKey;


        renderEditReaperCalendar();


        return true;

    }
    catch(error){

        console.error(
            "EDIT REAPER CALENDAR ERROR:",
            error
        );


        if(
            requestID
            !==
            editReaperCalendarRequest
        ){

            return false;

        }


        editReaperCalendarAvailability =
        {};


        editReaperCalendarAvailabilityKey =
        viewKey;


        renderEditReaperCalendar();


        return false;

    }

}


/* ======================================================
   RENDER EDIT / RESCHEDULE CALENDAR
====================================================== */

function renderEditReaperCalendar(){

    if(
        !editReaperCalendarMonth
        ||
        !editReaperCalendarDays
    ){

        return;

    }


    const year =
    editReaperCalendarView.getFullYear();


    const month =
    editReaperCalendarView.getMonth();


    const viewKey =
    year
    +
    "-"
    +
    reaperPadNumber(
        month + 1
    );


    editReaperCalendarMonth.textContent =
    editReaperCalendarView.toLocaleDateString(

        "en-US",

        {
            month:
            "long",

            year:
            "numeric"
        }

    );


    editReaperCalendarDays.innerHTML =
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
        "reaper-calendar-empty";


        editReaperCalendarDays.appendChild(
            empty
        );

    }


    const today =
    getReaperTodayKey();


    const availabilityReady =

    editReaperCalendarAvailabilityKey
    ===
    viewKey;


    for(
        let day = 1;
        day <= daysInMonth;
        day++
    ){

        const dateKey =
        reaperBuildDateKey(
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
        "reaper-calendar-day";


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
            editReaperCalendarAvailability[
                dateKey
            ]
            ||
            null
        )

        :

        null;


        const calendarStatus =

        availability?.assignment_status
        ||
        availability?.status
        ||
        "";


        /* ==========================================
           PAST
        ========================================== */

        if(
            dateKey < today
        ){

            button.disabled =
            true;


            button.classList.add(
                "reaper-calendar-past"
            );


            button.title =
            "Past dates cannot be selected when editing a rental.";

        }

        /* ==========================================
           FULLY BOOKED
        ========================================== */

        else if(
            calendarStatus
            ===
            "Fully Booked"
        ){

            button.disabled =
            true;


            button.classList.add(
                "reaper-calendar-booked"
            );


            button.title =
            "Fully booked. No valid Reaper Tractor and Driver pair is available on this date.";

        }

        /* ==========================================
           LIMITED
        ========================================== */

        else if(
            calendarStatus
            ===
            "Limited"
        ){

            button.classList.add(
                "reaper-calendar-limited"
            );


            button.title =
            "Limited availability. One or more currently assigned Tractor or Driver is unavailable on this date and may need replacement.";

        }

        /* ==========================================
           AVAILABLE
        ========================================== */

        else if(
            calendarStatus
            ===
            "Available"
        ){

            button.classList.add(
                "reaper-calendar-available"
            );


            button.title =
            "Current assignment is available on this date.";

        }


        if(
            dateKey === today
        ){

            button.classList.add(
                "reaper-calendar-today"
            );

        }


        if(
            editReaperDate?.value
            ===
            dateKey
        ){

            button.classList.add(
                "reaper-calendar-selected"
            );


            if(
                editReaperNeedsReschedule()
            ){

                button.classList.add(
                    "reaper-calendar-reschedule-needed"
                );

            }

        }


        if(
            !button.disabled
        ){

            button.addEventListener(

                "click",

                async function(event){

                    event.preventDefault();
                    event.stopPropagation();


                    if(
                        editReaperDateBtn?.disabled
                    ){

                        return;

                    }


                    const selectedDate =
                    this.dataset.date
                    ||
                    "";


                    const previousDate =
                    editReaperDate?.value
                    ||
                    "";


                    if(
                        !selectedDate
                    ){

                        return;

                    }


                    if(
                        selectedDate
                        ===
                        previousDate
                    ){

                        editReaperCalendar
                        ?.classList
                        .remove(
                            "show"
                        );


                        return;

                    }


                    const oldPrimaryTractorID =
                    editReaperString(
                        editReaperTractorID?.value
                    );


                    const oldPrimaryDriverID =
                    editReaperString(
                        editReaperPrimaryDriverID?.value
                    );


                    const oldSecondaryTractorID =
                    editReaperString(
                        editReaperSecondaryTractorID?.value
                    );


                    const oldSecondaryDriverID =
                    editReaperString(
                        editReaperSecondaryDriverID?.value
                    );


                    editReaperDate.value =
                    selectedDate;


                    editReaperRentalDate =
                    selectedDate;


                    if(
                        editReaperDateText
                    ){

                        editReaperDateText.textContent =
                        reaperFormatDate(
                            selectedDate
                        );

                    }


                    editReaperCalendar
                    ?.classList
                    .remove(
                        "show"
                    );


                    const loaded =
                    await loadEditReaperAssignmentAvailability();


                    if(
                        !loaded
                    ){

                        editReaperDate.value =
                        previousDate;


                        editReaperRentalDate =
                        previousDate;


                        if(
                            editReaperDateText
                        ){

                            editReaperDateText.textContent =

                            previousDate

                            ?

                            reaperFormatDate(
                                previousDate
                            )

                            :

                            "Select Rental Date";

                        }


                        applyAllReaperEditRules();


                        return;

                    }


                    let assignmentCleared =
                    false;


                    const primaryTractorValid =

                    oldPrimaryTractorID !== ""

                    &&

                    editReaperTractors.some(
                    function(tractor){

                        return (
                            String(
                                tractor.tractor_id
                            )
                            ===
                            oldPrimaryTractorID

                            &&

                            tractor.available
                            ===
                            true
                        );

                    });


                    const primaryDriverValid =

                    oldPrimaryDriverID !== ""

                    &&

                    editReaperDrivers.some(
                    function(driver){

                        return (
                            String(
                                driver.driver_id
                            )
                            ===
                            oldPrimaryDriverID

                            &&

                            driver.available
                            ===
                            true
                        );

                    });


                    const secondaryTractorValid =

                    oldSecondaryTractorID === ""

                    ||

                    editReaperTractors.some(
                    function(tractor){

                        return (
                            String(
                                tractor.tractor_id
                            )
                            ===
                            oldSecondaryTractorID

                            &&

                            tractor.available
                            ===
                            true
                        );

                    });


                    const secondaryDriverValid =

                    oldSecondaryDriverID === ""

                    ||

                    editReaperDrivers.some(
                    function(driver){

                        return (
                            String(
                                driver.driver_id
                            )
                            ===
                            oldSecondaryDriverID

                            &&

                            driver.available
                            ===
                            true
                        );

                    });


                    if(
                        !primaryTractorValid
                    ){

                        editReaperTractorID.value =
                        "";


                        editReaperTractorNo.value =
                        "";


                        editReaperTractorText.textContent =
                        "Select Primary Tractor";


                        editReaperPrimaryTractorStatus =
                        "";


                        assignmentCleared =
                        true;

                    }


                    if(
                        !primaryDriverValid
                    ){

                        editReaperPrimaryDriverID.value =
                        "";


                        editReaperPrimaryDriverText.textContent =
                        "Select Primary Driver";


                        assignmentCleared =
                        true;

                    }


                    if(
                        !secondaryTractorValid
                        ||
                        !secondaryDriverValid
                    ){

                        closeEditReaperSecondaryUI(
                            true
                        );


                        assignmentCleared =
                        true;

                    }


                    applyAllReaperEditRules();


                    renderEditReaperCalendar();


                    if(
                        assignmentCleared
                    ){

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

        }


        editReaperCalendarDays.appendChild(
            button
        );

    }

}


/* ======================================================
   POSITION EDIT CALENDAR
====================================================== */

function positionEditReaperCalendar(){

    if(
        !editReaperCalendar
        ||
        !editReaperDateBtn
    ){

        return;

    }


    const buttonRect =
    editReaperDateBtn.getBoundingClientRect();


    const calendarRect =
    editReaperCalendar.getBoundingClientRect();


    const gap =
    8;


    const margin =
    12;


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


    editReaperCalendar.style.left =
    Math.round(
        left
    )
    +
    "px";


    editReaperCalendar.style.top =
    Math.round(
        top
    )
    +
    "px";

}


/* ======================================================
   EDIT DATE BUTTON
====================================================== */

editReaperDateBtn
?.addEventListener(

    "click",

    async function(event){

        event.preventDefault();
        event.stopPropagation();


        if(
            editReaperDateBtn.disabled
        ){

            return;

        }


        closeEditReaperAssignmentMenus();


        editReaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        editReaperRemarksMenu
        ?.classList
        .remove(
            "show"
        );


        const currentDate =
        editReaperDate?.value
        ||
        "";


        if(
            currentDate
        ){

            const parts =
            currentDate.split(
                "-"
            );


            if(
                parts.length === 3
            ){

                editReaperCalendarView =
                new Date(

                    parseInt(
                        parts[0],
                        10
                    ),

                    parseInt(
                        parts[1],
                        10
                    )
                    -
                    1,

                    1

                );

            }

        }


        await loadEditReaperCalendarAvailability();


        const willOpen =

        !editReaperCalendar
        ?.classList
        .contains(
            "show"
        );


        editReaperCalendar
        ?.classList
        .toggle(
            "show",
            willOpen
        );


        if(
            willOpen
        ){

            requestAnimationFrame(
                positionEditReaperCalendar
            );

        }

    }

);


/* ======================================================
   PREVIOUS MONTH
====================================================== */

editReaperCalendarPrev
?.addEventListener(

    "click",

    async function(event){

        event.preventDefault();
        event.stopPropagation();


        editReaperCalendarView =
        new Date(

            editReaperCalendarView.getFullYear(),

            editReaperCalendarView.getMonth() - 1,

            1

        );


        await loadEditReaperCalendarAvailability();


        requestAnimationFrame(
            positionEditReaperCalendar
        );

    }

);


/* ======================================================
   NEXT MONTH
====================================================== */

editReaperCalendarNext
?.addEventListener(

    "click",

    async function(event){

        event.preventDefault();
        event.stopPropagation();


        editReaperCalendarView =
        new Date(

            editReaperCalendarView.getFullYear(),

            editReaperCalendarView.getMonth() + 1,

            1

        );


        await loadEditReaperCalendarAvailability();


        requestAnimationFrame(
            positionEditReaperCalendar
        );

    }

);


editReaperCalendar
?.addEventListener(

    "click",

    function(event){

        event.stopPropagation();

    }

);


/* ======================================================
   EDIT TRACTOR AVAILABILITY

   SELF-EXCLUDES CURRENT REAPER RECORD
====================================================== */

async function getEditReaperTractorAvailability(
    rentalDate,
    reaperID
){

    const url =

        "tractor/get_tractor_availability.php"

        +

        "?tractor_type=Reaper"

        +

        "&rental_date="

        +

        encodeURIComponent(
            rentalDate
        )

        +

        "&rental_type=reaper"

        +

        "&rental_id="

        +

        encodeURIComponent(
            reaperID
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
    catch(
        error
    ){

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

            "Unable to load Tractor availability."

        );

    }


    return data;

}


/* ======================================================
   EDIT DRIVER AVAILABILITY

   SELF-EXCLUDES CURRENT REAPER RECORD
====================================================== */

async function getEditReaperDriverAvailability(
    rentalDate,
    reaperID
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

        "&rental_type=reaper"

        +

        "&rental_id="

        +

        encodeURIComponent(
            reaperID
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
    catch(
        error
    ){

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
   LOAD EDIT ASSIGNMENT AVAILABILITY
====================================================== */

async function loadEditReaperAssignmentAvailability(){

    const rentalDate =
    editReaperString(
        editReaperDate
        ?.value
    );


    const reaperID =
    editReaperInt(
        editReaperID
        ?.value
    );


    if(
        !rentalDate
        ||
        reaperID <= 0
    ){

        return false;

    }


    const requestID =
    ++editReaperAvailabilityRequest;


    try{

        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getEditReaperTractorAvailability(
                rentalDate,
                reaperID
            ),

            getEditReaperDriverAvailability(
                rentalDate,
                reaperID
            )

        ]);


        if(
            requestID
            !==
            editReaperAvailabilityRequest
        ){

            return false;

        }


        editReaperAvailabilityReady =
        true;


        editReaperTractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        editReaperDrivers =

        Array.isArray(
            driverData.drivers
        )

        ?

        driverData.drivers

        :

        [];


        renderReaperTractorMenu(

            editReaperTractorMenu,

            editReaperTractors,

            false

        );


        renderReaperTractorMenu(

            editReaperSecondaryTractorMenu,

            editReaperTractors,

            true

        );


        renderReaperDriverMenu(

            editReaperPrimaryDriverMenu,

            editReaperDrivers,

            false

        );


        renderReaperDriverMenu(

            editReaperSecondaryDriverMenu,

            editReaperDrivers,

            true

        );


        const currentPrimaryTractor =
        findEditReaperTractor(

            editReaperTractorID
            ?.value

            ||

            ""

        );


        if(
            currentPrimaryTractor
        ){

            editReaperPrimaryTractorStatus =
            editReaperString(

                currentPrimaryTractor.status

                ||

                currentPrimaryTractor.availability

            );

        }


        return true;

    }
    catch(
        error
    ){

        console.error(
            "EDIT REAPER AVAILABILITY ERROR:",
            error
        );


        editReaperAvailabilityReady =
        false;


        editReaperTractors =
        [];


        editReaperDrivers =
        [];


        if(
            requestID
            ===
            editReaperAvailabilityRequest
        ){

            Swal.fire({

                icon:
                "error",

                title:
                "Unable to Load Availability",

                text:
                error.message

                ||

                "Unable to load Tractor and Driver availability.",

                confirmButtonColor:
                "#8CA15B"

            });

        }


        return false;

    }

}

/* ======================================================
   EDIT PRIMARY / SECONDARY MENU FILTER
   Keep Tractor / Driver assignments unique both ways.
====================================================== */

function refreshEditReaperAssignmentMenus(){

    const primaryTractorID =
    editReaperString(
        editReaperTractorID?.value
    );


    const secondaryTractorID =
    editReaperString(
        editReaperSecondaryTractorID?.value
    );


    const primaryDriverID =
    editReaperString(
        editReaperPrimaryDriverID?.value
    );


    const secondaryDriverID =
    editReaperString(
        editReaperSecondaryDriverID?.value
    );


    const primaryTractors =
    editReaperTractors.filter(
        tractor =>
        String(
            tractor.tractor_id || ""
        )
        !==
        secondaryTractorID
    );


    const secondaryTractors =
    editReaperTractors.filter(
        tractor =>
        String(
            tractor.tractor_id || ""
        )
        !==
        primaryTractorID
    );


    const primaryDrivers =
    editReaperDrivers.filter(
        driver =>
        String(
            driver.driver_id || ""
        )
        !==
        secondaryDriverID
    );


    const secondaryDrivers =
    editReaperDrivers.filter(
        driver =>
        String(
            driver.driver_id || ""
        )
        !==
        primaryDriverID
    );


    renderReaperTractorMenu(
        editReaperTractorMenu,
        primaryTractors,
        false
    );


    renderReaperTractorMenu(
        editReaperSecondaryTractorMenu,
        secondaryTractors,
        true
    );


    renderReaperDriverMenu(
        editReaperPrimaryDriverMenu,
        primaryDrivers,
        false
    );


    renderReaperDriverMenu(
        editReaperSecondaryDriverMenu,
        secondaryDrivers,
        true
    );

}

/* ======================================================
   ASSIGNMENT MENU OPEN / CLOSE
====================================================== */

function closeEditReaperAssignmentMenus(
    exceptMenu = null
){

    [
        editReaperTractorMenu,
        editReaperPrimaryDriverMenu,
        editReaperSecondaryTractorMenu,
        editReaperSecondaryDriverMenu
    ]
    .forEach(
    function(
        menu
    ){

        if(
            menu
            &&
            menu !== exceptMenu
        ){

            menu.classList
            .remove(
                "show"
            );

        }

    });

}


function setupEditReaperAssignmentButton(
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

        function(
            event
        ){

            event.preventDefault();

            event.stopPropagation();


            if(
                button.disabled
            ){

                return;

            }


            const willOpen =
            !menu.classList
            .contains(
                "show"
            );


            closeEditReaperAssignmentMenus(
                menu
            );


            refreshEditReaperAssignmentMenus();


            editReaperStatusMenu
            ?.classList
            .remove(
                "show"
            );


            editReaperRemarksMenu
            ?.classList
            .remove(
                "show"
            );


            menu.classList
            .toggle(
                "show",
                willOpen
            );

        }

    );

}


setupEditReaperAssignmentButton(
    editReaperTractorBtn,
    editReaperTractorMenu
);


setupEditReaperAssignmentButton(
    editReaperPrimaryDriverBtn,
    editReaperPrimaryDriverMenu
);


setupEditReaperAssignmentButton(
    editReaperSecondaryTractorBtn,
    editReaperSecondaryTractorMenu
);


setupEditReaperAssignmentButton(
    editReaperSecondaryDriverBtn,
    editReaperSecondaryDriverMenu
);


/* ======================================================
   RESET SECONDARY VALUES
====================================================== */

function resetEditReaperSecondaryValues(){

    if(
        editReaperSecondaryTractorID
    ){

        editReaperSecondaryTractorID.value =
        "";

    }


    if(
        editReaperSecondaryTractorNo
    ){

        editReaperSecondaryTractorNo.value =
        "";

    }


    if(
        editReaperSecondaryTractorText
    ){

        editReaperSecondaryTractorText.textContent =
        "No Secondary Tractor";

    }


    if(
        editReaperSecondaryDriverID
    ){

        editReaperSecondaryDriverID.value =
        "";

    }


    if(
        editReaperSecondaryDriverText
    ){

        editReaperSecondaryDriverText.textContent =
        "No Secondary Driver";

    }

}


/* ======================================================
   CLOSE SECONDARY UI
====================================================== */

function closeEditReaperSecondaryUI(
    clearValues = false
){

    editReaperSecondaryAssignment
    ?.classList
    .remove(
        "is-open"
    );


    editReaperSecondaryToggle
    ?.classList
    .remove(
        "is-open"
    );


    if(
        editReaperSecondaryToggleText
    ){

        editReaperSecondaryToggleText.textContent =
        "ADD SECONDARY ASSIGNMENT";

    }


    closeEditReaperAssignmentMenus();


    if(
        clearValues
    ){

        resetEditReaperSecondaryValues();

    }

}


/* ======================================================
   OPEN SECONDARY UI
====================================================== */

function openEditReaperSecondaryUI(){

    editReaperSecondaryAssignment
    ?.classList
    .add(
        "is-open"
    );


    editReaperSecondaryToggle
    ?.classList
    .add(
        "is-open"
    );


    if(
        editReaperSecondaryToggleText
    ){

        editReaperSecondaryToggleText.textContent =
        "REMOVE SECONDARY ASSIGNMENT";

    }

}


/* ======================================================
   SECONDARY TOGGLE
====================================================== */

if(
    editReaperSecondaryToggle
    &&
    editReaperSecondaryAssignment
){

    editReaperSecondaryToggle
    .addEventListener(

        "click",

        async function(
            event
        ){

            event.preventDefault();

            event.stopPropagation();


            if(
                editReaperSecondaryToggle.disabled
            ){

                return;

            }


            const isOpen =
            editReaperSecondaryAssignment
            .classList
            .contains(
                "is-open"
            );


            if(
                isOpen
            ){

                const hasSelection =

                    editReaperString(
                        editReaperSecondaryTractorID
                        ?.value
                    )
                    !==
                    ""

                    ||

                    editReaperString(
                        editReaperSecondaryDriverID
                        ?.value
                    )
                    !==
                    "";


                if(
                    hasSelection
                ){

                    const result =
                    await Swal.fire({

                        icon:
                        "question",

                        title:
                        "Remove Secondary Assignment?",

                        text:
                        "The selected Secondary Tractor and Driver will be cleared.",

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


                closeEditReaperSecondaryUI(
                    true
                );


                return;

            }


            openEditReaperSecondaryUI();

        }

    );

}


/* ======================================================
   PRIMARY TRACTOR SELECTION
====================================================== */

editReaperTractorMenu
?.addEventListener(

    "click",

    function(
        event
    ){

        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
            ||
            !editReaperTractorMenu
            .contains(
                item
            )
        ){

            return;

        }


        if(
            item.dataset.disabled
            ===
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
        editReaperInt(
            item.dataset.id
        );


        const tractorNo =
        editReaperString(
            item.dataset.no
        );


        const tractorName =
        editReaperString(
            item.dataset.name
        );


        if(
            tractorID <= 0
        ){

            return;

        }


        if(
            editReaperString(
                editReaperSecondaryTractorID
                ?.value
            )
            !==
            ""

            &&

            String(
                tractorID
            )
            ===
            String(
                editReaperSecondaryTractorID.value
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Tractor Already Selected",

                text:
                "Primary Tractor and Secondary Tractor must be different.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        if(
            editReaperTractorID
        ){

            editReaperTractorID.value =
            String(
                tractorID
            );

        }


        if(
            editReaperTractorNo
        ){

            editReaperTractorNo.value =
            tractorNo;

        }


        if(
            editReaperTractorText
        ){

            editReaperTractorText.textContent =
            editReaperAssignmentLabel(

                tractorNo,

                tractorName

            );

        }


        const tractor =
        findEditReaperTractor(
            tractorID
        );


        editReaperPrimaryTractorStatus =
        editReaperString(

            tractor
            ?.status

            ||

            tractor
            ?.availability

            ||

            item.dataset.availability

        );


        editReaperTractorMenu
        .classList
        .remove(
            "show"
        );


        applyAllReaperEditRules();

    }

);


/* ======================================================
   PRIMARY DRIVER SELECTION
====================================================== */

editReaperPrimaryDriverMenu
?.addEventListener(

    "click",

    function(
        event
    ){

        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
            ||
            !editReaperPrimaryDriverMenu
            .contains(
                item
            )
        ){

            return;

        }


        if(
            item.dataset.disabled
            ===
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
        editReaperInt(
            item.dataset.id
        );


        const driver =
        findEditReaperDriver(
            driverID
        );


        const driverName =
        editReaperString(

            item.dataset.name

            ||

            driver
            ?.full_name

        );


        const driverCode =
        editReaperString(

            driver
            ?.driver_code

            ||

            (
                driverID > 0

                ?

                "DRV-"
                +
                String(
                    driverID
                )
                .padStart(
                    4,
                    "0"
                )

                :

                ""
            )

        );


        if(
            driverID <= 0
        ){

            return;

        }


        if(
            editReaperString(
                editReaperSecondaryDriverID
                ?.value
            )
            !==
            ""

            &&

            String(
                driverID
            )
            ===
            String(
                editReaperSecondaryDriverID.value
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Driver Already Selected",

                text:
                "Primary Driver and Secondary Driver must be different.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        if(
            editReaperPrimaryDriverID
        ){

            editReaperPrimaryDriverID.value =
            String(
                driverID
            );

        }


        if(
            editReaperPrimaryDriverText
        ){

            editReaperPrimaryDriverText.textContent =
            editReaperAssignmentLabel(

                driverCode,

                driverName

            );

        }


        editReaperPrimaryDriverMenu
        .classList
        .remove(
            "show"
        );

    }

);


/* ======================================================
   SECONDARY TRACTOR SELECTION
====================================================== */

editReaperSecondaryTractorMenu
?.addEventListener(

    "click",

    function(
        event
    ){

        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
            ||
            !editReaperSecondaryTractorMenu
            .contains(
                item
            )
        ){

            return;

        }


        if(
            item.dataset.disabled
            ===
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


        if(
            item.dataset.action
            ===
            "clear"
        ){

            if(
                editReaperSecondaryTractorID
            ){

                editReaperSecondaryTractorID.value =
                "";

            }


            if(
                editReaperSecondaryTractorNo
            ){

                editReaperSecondaryTractorNo.value =
                "";

            }


            if(
                editReaperSecondaryTractorText
            ){

                editReaperSecondaryTractorText.textContent =
                "No Secondary Tractor";

            }


            editReaperSecondaryTractorMenu
            .classList
            .remove(
                "show"
            );


            return;

        }


        const tractorID =
        editReaperInt(
            item.dataset.id
        );


        const tractorNo =
        editReaperString(
            item.dataset.no
        );


        const tractorName =
        editReaperString(
            item.dataset.name
        );


        if(
            tractorID <= 0
        ){

            return;

        }


        if(
            String(
                tractorID
            )
            ===
            String(
                editReaperTractorID
                ?.value
                ||
                ""
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Tractor Already Selected",

                text:
                "Secondary Tractor must be different from the Primary Tractor.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        if(
            editReaperSecondaryTractorID
        ){

            editReaperSecondaryTractorID.value =
            String(
                tractorID
            );

        }


        if(
            editReaperSecondaryTractorNo
        ){

            editReaperSecondaryTractorNo.value =
            tractorNo;

        }


        if(
            editReaperSecondaryTractorText
        ){

            editReaperSecondaryTractorText.textContent =
            editReaperAssignmentLabel(

                tractorNo,

                tractorName

            );

        }


        editReaperSecondaryTractorMenu
        .classList
        .remove(
            "show"
        );

    }

);


/* ======================================================
   SECONDARY DRIVER SELECTION
====================================================== */

editReaperSecondaryDriverMenu
?.addEventListener(

    "click",

    function(
        event
    ){

        const item =
        event.target.closest(
            ".reaper-tractor-item"
        );


        if(
            !item
            ||
            !editReaperSecondaryDriverMenu
            .contains(
                item
            )
        ){

            return;

        }


        if(
            item.dataset.disabled
            ===
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

        if(
            item.dataset.action
            ===
            "clear"
        ){

            if(
                editReaperSecondaryDriverID
            ){

                editReaperSecondaryDriverID.value =
                "";

            }


            if(
                editReaperSecondaryDriverText
            ){

                editReaperSecondaryDriverText.textContent =
                "No Secondary Driver";

            }


            editReaperSecondaryDriverMenu
            .classList
            .remove(
                "show"
            );


            return;

        }


        const driverID =
        editReaperInt(
            item.dataset.id
        );


        const driver =
        findEditReaperDriver(
            driverID
        );


        const driverName =
        editReaperString(

            item.dataset.name

            ||

            driver
            ?.full_name

        );


        const driverCode =
        editReaperString(

            driver
            ?.driver_code

            ||

            (
                driverID > 0

                ?

                "DRV-"
                +
                String(
                    driverID
                )
                .padStart(
                    4,
                    "0"
                )

                :

                ""
            )

        );


        if(
            driverID <= 0
        ){

            return;

        }


        if(
            String(
                driverID
            )
            ===
            String(
                editReaperPrimaryDriverID
                ?.value
                ||
                ""
            )
        ){

            Swal.fire({

                icon:
                "warning",

                title:
                "Driver Already Selected",

                text:
                "Secondary Driver must be different from the Primary Driver.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        if(
            editReaperSecondaryDriverID
        ){

            editReaperSecondaryDriverID.value =
            String(
                driverID
            );

        }


        if(
            editReaperSecondaryDriverText
        ){

            editReaperSecondaryDriverText.textContent =
            editReaperAssignmentLabel(

                driverCode,

                driverName

            );

        }


        editReaperSecondaryDriverMenu
        .classList
        .remove(
            "show"
        );

    }

);


/* ======================================================
   COMPLETION CHECK
====================================================== */

function reaperEditCompletionReady(){

    const harvestValue =
    editReaperString(
        editReaperHarvest
        ?.value
    );


    const commodityValue =
    editReaperString(
        editReaperCommodity
        ?.value
    );


    if(
        harvestValue
        ===
        ""

        ||

        commodityValue
        ===
        ""
    ){

        return false;

    }


    const harvest =
    Number(
        harvestValue
    );


    const commodity =
    Number(
        commodityValue
    );


    return (

        Number.isInteger(
            harvest
        )

        &&

        harvest >= 1

        &&

        Number.isInteger(
            commodity
        )

        &&

        commodity > 0

    );

}


/* ======================================================
   SERVICE DATA RULES
====================================================== */

function applyReaperEditServiceDataRules(){

    const today =
    getReaperTodayKey();


    const isFuture =

        editReaperRentalDate
        !==
        ""

        &&

        editReaperRentalDate
        >
        today;


    const isPaidFinal =

        editReaperOriginalRemarks
        ===
        "Paid";


    /* ==========================================
       FUTURE DATE
    ========================================== */

    if(
        isFuture
    ){

        if(
            editReaperHarvest
        ){

            editReaperHarvest.value =
            "";


            editReaperHarvest.disabled =
            true;


            editReaperHarvest.title =
            "Available on or after the service date.";

        }


        if(
            editReaperCommodity
        ){

            editReaperCommodity.value =
            "";


            editReaperCommodity.disabled =
            true;


            editReaperCommodity.placeholder =
            "Enter No. of Harvest first";


            editReaperCommodity.title =
            "Available on or after the service date.";

        }


        return;

    }


    /* ==========================================
       NO. OF HARVEST
    ========================================== */

    if(
        editReaperHarvest
    ){

        editReaperHarvest.disabled =
        isPaidFinal;


        editReaperHarvest.title =

        isPaidFinal

        ?

        "This value is finalized because the rental is already Paid."

        :

        "";

    }


    /* ==========================================
       COMMODITY
    ========================================== */

    if(
        editReaperCommodity
    ){

        const harvestRaw =
        String(
            editReaperHarvest?.value
            ??
            ""
        )
        .trim();


        const harvest =
        Number(
            harvestRaw
        );


        const harvestReady =

        Number.isInteger(
            harvest
        )

        &&

        harvest >= 1;


        editReaperCommodity.disabled =

        isPaidFinal

        ||

        !harvestReady;


        editReaperCommodity.placeholder =

        harvestReady

        ?

        "Enter Commodity"

        :

        "Enter No. of Harvest first";


        if(
            !harvestReady
            &&
            !isPaidFinal
        ){

            editReaperCommodity.value =
            "";

        }


        editReaperCommodity.title =

        isPaidFinal

        ?

        "This value is finalized because the rental is already Paid."

        :

        "";

    }

}


/* ======================================================
   STATUS RULES
====================================================== */

function applyReaperEditStatusRules(){

    if(
        !editReaperStatus
        ||
        !editReaperStatusBtn
    ){

        return;

    }


    const today =
    getReaperTodayKey();


    const rentalDate =
    editReaperRentalDate
    ||
    "";


    const isFuture =

        rentalDate
        !==
        ""

        &&

        rentalDate
        >
        today;


    const isPast =

        rentalDate
        !==
        ""

        &&

        rentalDate
        <
        today;


    const isMaintenance =

        editReaperPrimaryTractorStatus
        ===
        "Maintenance";


    const isDoneFinal =

        editReaperOriginalStatus
        ===
        "Done"

        &&

        rentalDate
        !==
        ""

        &&

        rentalDate
        <=
        today;


    const isPaidFinal =

        editReaperOriginalRemarks
        ===
        "Paid";


    const completionReady =
    reaperEditCompletionReady();


    setReaperEditStatusItem(
        "Pending",
        false
    );


    setReaperEditStatusItem(
        "Ongoing",
        false
    );


    setReaperEditStatusItem(
        "Done",
        false
    );


    editReaperStatusBtn.disabled =
    false;


    /* ==========================================
       DONE / PAID
    ========================================== */

    if(
        isPaidFinal
        ||
        isDoneFinal
    ){

        editReaperStatus.value =
        "Done";


        if(
            editReaperStatusText
        ){

            editReaperStatusText.textContent =
            "Done";

        }


        setReaperEditStatusItem(

            "Pending",

            true,

            "A completed rental cannot return to Pending."

        );


        setReaperEditStatusItem(

            "Ongoing",

            true,

            "A completed rental cannot return to Ongoing."

        );


        editReaperStatusBtn.disabled =
        true;


        editReaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==========================================
       MAINTENANCE
    ========================================== */

    if(
        isMaintenance
    ){

        editReaperStatus.value =
        "Pending";


        if(
            editReaperStatusText
        ){

            editReaperStatusText.textContent =
            "Pending";

        }


        setReaperEditStatusItem(

            "Ongoing",

            true,

            "Ongoing is unavailable while the Primary Tractor is under Maintenance."

        );


        setReaperEditStatusItem(

            "Done",

            true,

            "Done is unavailable while the Primary Tractor is under Maintenance."

        );


        editReaperStatusBtn.disabled =
        true;


        editReaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==========================================
       FUTURE
    ========================================== */

    if(
        isFuture
    ){

        editReaperStatus.value =
        "Pending";


        if(
            editReaperStatusText
        ){

            editReaperStatusText.textContent =
            "Pending";

        }


        setReaperEditStatusItem(

            "Ongoing",

            true,

            "A future rental cannot be Ongoing."

        );


        setReaperEditStatusItem(

            "Done",

            true,

            "A future rental cannot be Done."

        );


        editReaperStatusBtn.disabled =
        true;


        editReaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==========================================
       PAST

       Past Pending = Missed.

       Past Ongoing = Delayed.
       Delayed stays Ongoing until the Rental Date
       is actually changed.
    ========================================== */

    if(
        isPast
    ){

        const isDelayedExisting =

        editReaperOriginalStatus
        ===
        "Ongoing"

        &&

        editReaperString(
            editReaperLoadedRecord?.rental_date
        )
        ===
        rentalDate;


        if(
            isDelayedExisting
        ){

            setReaperEditStatusItem(

                "Pending",

                true,

                "Select a new Rental Date to reschedule this Delayed rental."

            );


            setReaperEditStatusItem(
                "Ongoing",
                false
            );

        }
        else{

            setReaperEditStatusItem(

                "Ongoing",

                true,

                "A past rental cannot be changed to Ongoing."

            );


            if(
                editReaperStatus.value
                ===
                "Ongoing"
            ){

                editReaperStatus.value =
                "Pending";


                if(
                    editReaperStatusText
                ){

                    editReaperStatusText.textContent =
                    "Pending";

                }

            }

        }

    }


    /* ==========================================
       DONE NEEDS COMPLETION
    ========================================== */

    if(
        !completionReady
    ){

        setReaperEditStatusItem(

            "Done",

            true,

            "Enter No. of Harvest and Commodity first."

        );


        if(
            editReaperStatus.value
            ===
            "Done"
        ){

            editReaperStatus.value =
            "Pending";


            if(
                editReaperStatusText
            ){

                editReaperStatusText.textContent =
                "Pending";

            }

        }

    }

}


/* ======================================================
   REMARKS / PAYMENT RULES
====================================================== */

function applyReaperEditRemarksRules(){

    if(
        !editReaperRemarks
        ||
        !editReaperRemarksBtn
    ){

        return;

    }


    const today =
    getReaperTodayKey();


    const isFuture =

        editReaperRentalDate
        !==
        ""

        &&

        editReaperRentalDate
        >
        today;


    const completionReady =
    reaperEditCompletionReady();


    const isDone =

        editReaperStatus
        ?.value
        ===
        "Done";


    const isPaidFinal =

        editReaperOriginalRemarks
        ===
        "Paid";


    /* ==========================================
       ALREADY PAID
    ========================================== */

    if(
        isPaidFinal
    ){

        editReaperRemarks.value =
        "Paid";


        if(
            editReaperRemarksText
        ){

            editReaperRemarksText.textContent =
            "Paid";

        }


        setReaperEditRemarksItem(

            "Unpaid",

            true,

            "A paid Reaper rental cannot return to Unpaid."

        );


        setReaperEditRemarksItem(
            "Paid",
            false
        );


        setReaperRemarksButtonLocked(

            editReaperRemarksBtn,

            true

        );


        editReaperRemarksMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    const paidAllowed =
    (
        !isFuture

        &&

        completionReady

        &&

        isDone
    );


    setReaperEditRemarksItem(

        "Paid",

        !paidAllowed,

        paidAllowed
        ?
        ""
        :
        "Complete the service and mark the rental Done first."

    );


    setReaperEditRemarksItem(
        "Unpaid",
        false
    );


    if(
        !paidAllowed
    ){

        editReaperRemarks.value =
        "Unpaid";


        if(
            editReaperRemarksText
        ){

            editReaperRemarksText.textContent =
            "Unpaid";

        }

    }


    const lockPayment =

    !isDone
    ||
    isFuture;


    setReaperRemarksButtonLocked(

        editReaperRemarksBtn,

        lockPayment

    );


    if(
        lockPayment
    ){

        editReaperRemarksMenu
        ?.classList
        .remove(
            "show"
        );

    }

}


/* ======================================================
   ASSIGNMENT LOCKS

   Pending:
   - Primary editable
   - Secondary editable

   Ongoing:
   - Primary locked
   - Secondary editable

   Done:
   - All assignments locked

   Paid:
   - All assignments locked

   Maintenance Primary Tractor:
   - Assignment remains editable so the tractor
     can be replaced.
====================================================== */

function applyReaperEditAssignmentLocks(){

    const currentStatus =
    editReaperString(

        editReaperStatus?.value
        ||
        editReaperOriginalStatus

    );


    const today =
    getReaperTodayKey();


    const rentalDate =
    editReaperRentalDate
    ||
    "";


    const originalRentalDate =
    editReaperString(
        editReaperLoadedRecord?.rental_date
    );


    const wasDelayed =

    editReaperOriginalStatus === "Ongoing"
    &&
    originalRentalDate !== ""
    &&
    originalRentalDate < today;


    const delayedDateChanged =

    wasDelayed
    &&
    rentalDate !== ""
    &&
    rentalDate !== originalRentalDate;


    const isPaidFinal =

    editReaperOriginalRemarks
    ===
    "Paid";


    const isMaintenance =

    editReaperPrimaryTractorStatus
    ===
    "Maintenance";


    let lockDate =
    false;


    let lockPrimary =
    false;


    let lockSecondary =
    false;


    if(
        !editReaperAvailabilityReady
    ){

        lockPrimary =
        true;


        lockSecondary =
        true;

    }


    if(
        currentStatus
        ===
        "Ongoing"
    ){

        if(
            wasDelayed
        ){

            /*
            Delayed:
            - Date can be changed for rescheduling.
            - Primary stays locked until a NEW date
                has actually been selected.
            - Secondary support may still be adjusted.
            */

            lockDate =
            false;


            lockPrimary =
            !delayedDateChanged;


            lockSecondary =
            false;

        }
        else{

            /*
            Normal Ongoing:
            - Date locked
            - Primary locked
            - Secondary may still be adjusted
            */

            lockDate =
            true;


            lockPrimary =
            true;


            lockSecondary =
            false;

        }

    }
    else if(
        currentStatus
        ===
        "Done"
    ){

        lockDate =
        true;


        lockPrimary =
        true;


        lockSecondary =
        true;

    }


    if(
        isPaidFinal
    ){

        lockDate =
        true;


        lockPrimary =
        true;


        lockSecondary =
        true;

    }


    if(
        editReaperAvailabilityReady
        &&
        isMaintenance
        &&
        !isPaidFinal
        &&
        currentStatus !== "Done"
    ){

        lockPrimary =
        false;


        lockSecondary =
        false;

    }


    if(
        editReaperDateBtn
    ){

        editReaperDateBtn.disabled =
        lockDate;


        editReaperDateBtn
        .classList
        .toggle(
            "reaper-reschedule-needed",
            editReaperNeedsReschedule()
        );

    }


    if(
        lockDate
    ){

        editReaperCalendar
        ?.classList
        .remove(
            "show"
        );

    }


    editReaperTractorBtn.disabled =
    lockPrimary;


    editReaperPrimaryDriverBtn.disabled =
    lockPrimary;


    editReaperSecondaryToggle.disabled =
    lockSecondary;


    editReaperSecondaryToggle.style.display =

    (
        currentStatus === "Done"
        ||
        isPaidFinal
    )

    ?

    "none"

    :

    "";


    editReaperSecondaryTractorBtn.disabled =
    lockSecondary;


    editReaperSecondaryDriverBtn.disabled =
    lockSecondary;


    if(
        lockPrimary
    ){

        editReaperTractorMenu
        ?.classList
        .remove(
            "show"
        );


        editReaperPrimaryDriverMenu
        ?.classList
        .remove(
            "show"
        );

    }


    if(
        lockSecondary
    ){

        editReaperSecondaryTractorMenu
        ?.classList
        .remove(
            "show"
        );


        editReaperSecondaryDriverMenu
        ?.classList
        .remove(
            "show"
        );

    }

}


/* ======================================================
   MODAL STATE / HINT / SAVE BUTTON
====================================================== */

function refreshReaperEditModalState(){

    if(
        !editReaperModalElement
    ){

        return;

    }


    const today =
    getReaperTodayKey();


    const isFuture =

        editReaperRentalDate
        !==
        ""

        &&

        editReaperRentalDate
        >
        today;


    const isPast =

        editReaperRentalDate
        !==
        ""

        &&

        editReaperRentalDate
        <
        today;


    const isMaintenance =

        editReaperPrimaryTractorStatus
        ===
        "Maintenance";


    const isPaidFinal =

        editReaperOriginalRemarks
        ===
        "Paid";


    const currentStatus =
    editReaperString(
        editReaperStatus
        ?.value
    );


    const currentRemarks =
    editReaperString(
        editReaperRemarks
        ?.value
    );


    editReaperModalElement
    .classList
    .toggle(
        "reaper-paid-final",
        isPaidFinal
    );


    applyReaperEditAssignmentLocks();


    /* ==========================================
       PAID FINAL
    ========================================== */

    if(
        isPaidFinal
    ){

        setReaperEditLifecycleHint(

            "Paid and finalized. Assignment, service details, and payment status are now locked.",

            "bi-shield-lock"

        );


        if(
            updateReaperBtn
        ){

            updateReaperBtn.disabled =
            true;


            updateReaperBtn.textContent =
            "PAID / FINALIZED";

        }


        return;

    }


    if(
        updateReaperBtn
    ){

        updateReaperBtn.disabled =
        false;


        updateReaperBtn.textContent =

        currentRemarks
        ===
        "Paid"

        ?

        "FINALIZE PAYMENT"

        :

        "SAVE CHANGES";

    }


    /* ==========================================
       MAINTENANCE
    ========================================== */

    if(
        isMaintenance
    ){

        setReaperEditLifecycleHint(

            "The Primary Tractor is under Maintenance. Replace the assignment before continuing the service.",

            "bi-exclamation-triangle"

        );


        return;

    }


    /* ==========================================
       FUTURE
    ========================================== */

    if(
        isFuture
    ){

        setReaperEditLifecycleHint(

            "Future booking: service completion details and payment stay locked until the service date.",

            "bi-calendar-lock"

        );


        return;

    }


    /* ==========================================
       DONE / UNPAID
    ========================================== */

    if(
        currentStatus
        ===
        "Done"

        &&

        currentRemarks
        ===
        "Unpaid"
    ){

        setReaperEditLifecycleHint(

            "Service completed. Keep Unpaid until payment is collected, then finalize it as Paid.",

            "bi-cash-stack"

        );


        return;

    }


    /* ==========================================
       READY TO FINALIZE
    ========================================== */

    if(
        currentStatus
        ===
        "Done"

        &&

        currentRemarks
        ===
        "Paid"
    ){

        setReaperEditLifecycleHint(

            "Ready to finalize payment. Once saved as Paid, this transaction becomes locked.",

            "bi-shield-check"

        );


        return;

    }


    /* ==========================================
    PAST
    ========================================== */

    if(
        isPast
    ){

        const originalRentalDate =
        editReaperString(
            editReaperLoadedRecord?.rental_date
        );


        const isDelayedExisting =

        editReaperOriginalStatus === "Ongoing"
        &&
        currentStatus === "Ongoing"
        &&
        originalRentalDate !== ""
        &&
        originalRentalDate === editReaperRentalDate;


        if(
            isDelayedExisting
        ){

            setReaperEditLifecycleHint(

                "Delayed rental: complete the service details and mark it Done, or select a new Rental Date to reschedule.",

                "bi-clock-history"

            );


            return;

        }


        setReaperEditLifecycleHint(

            "Past rental: enter the required completion details before marking the rental Done.",

            "bi-clock-history"

        );


        return;

    }


    /* ==========================================
       ONGOING
    ========================================== */

    if(
        currentStatus
        ===
        "Ongoing"
    ){

        setReaperEditLifecycleHint(

            "Ongoing rental: Primary Assignment is locked. Secondary support and completion details may still be updated.",

            "bi-truck"

        );


        return;

    }


    /* ==========================================
       PENDING
    ========================================== */

    setReaperEditLifecycleHint(

        "Pending rental: assignment may still be adjusted. Enter Harvest and Commodity before marking the rental Done.",

        "bi-info-circle"

    );

}


/* ======================================================
   APPLY ALL EDIT RULES
====================================================== */

function applyAllReaperEditRules(){

    applyReaperEditServiceDataRules();

    applyReaperEditStatusRules();

    applyReaperEditRemarksRules();

    refreshReaperEditModalState();

}


/* ======================================================
   STATUS DROPDOWN
====================================================== */

setupReaperDropdown(

    editReaperStatusBtn,

    editReaperStatusMenu,

    editReaperStatus,

    editReaperStatusText,

    "#editReaperStatusMenu .reaper-status-item"

);


/* ======================================================
   REMARKS DROPDOWN
====================================================== */

setupReaperDropdown(

    editReaperRemarksBtn,

    editReaperRemarksMenu,

    editReaperRemarks,

    editReaperRemarksText,

    "#editReaperRemarksMenu .reaper-remarks-item"

);


/* ======================================================
   STATUS CHANGE
====================================================== */

editReaperStatusMenu
?.querySelectorAll(
    ".reaper-status-item"
)
.forEach(
function(
    item
){

    item.addEventListener(

        "click",

        function(){

            window.setTimeout(

                function(){

                    applyReaperEditRemarksRules();

                    refreshReaperEditModalState();

                },

                0

            );

        }

    );

});


/* ======================================================
   REMARKS CHANGE
====================================================== */

editReaperRemarksMenu
?.querySelectorAll(
    ".reaper-remarks-item"
)
.forEach(
function(
    item
){

    item.addEventListener(

        "click",

        function(){

            window.setTimeout(

                refreshReaperEditModalState,

                0

            );

        }

    );

});


/* ======================================================
   HARVEST LIVE CHECK
====================================================== */

editReaperHarvest
?.addEventListener(

    "input",

    function(){

        applyReaperEditServiceDataRules();

        applyReaperEditStatusRules();

        applyReaperEditRemarksRules();

        refreshReaperEditModalState();

    }

);


/* ======================================================
   COMMODITY LIVE CHECK
====================================================== */

editReaperCommodity
?.addEventListener(

    "input",

    function(){

        applyReaperEditStatusRules();

        applyReaperEditRemarksRules();

        refreshReaperEditModalState();

    }

);


/* ======================================================
   RESET EDIT MODAL
====================================================== */

function resetEditReaperModal(){

    editReaperAvailabilityRequest++;


    editReaperAvailabilityReady =
    false;


    editReaperLoadedRecord =
    null;


    editReaperTractors =
    [];


    editReaperDrivers =
    [];


    editReaperOriginalStatus =
    "";


    editReaperOriginalRemarks =
    "";


    editReaperRentalDate =
    "";


    editReaperCalendarRequest++;


    editReaperCalendarAvailability =
    {};


    editReaperCalendarAvailabilityKey =
    "";


    editReaperCalendarView =
    new Date();


    editReaperCalendarView.setDate(
        1
    );


    editReaperCalendar
    ?.classList
    .remove(
        "show"
    );


    if(
        editReaperDateText
    ){

        editReaperDateText.textContent =
        "Select Rental Date";

    }


    editReaperPrimaryTractorStatus =
    "";


    editReaperForm
    ?.reset();


    closeEditReaperAssignmentMenus();


    closeEditReaperSecondaryUI(
        false
    );


    resetEditReaperSecondaryValues();


    if(
        editReaperTractorID
    ){

        editReaperTractorID.value =
        "";

    }


    if(
        editReaperTractorNo
    ){

        editReaperTractorNo.value =
        "";

    }


    if(
        editReaperTractorText
    ){

        editReaperTractorText.textContent =
        "Select Primary Tractor";

    }


    if(
        editReaperPrimaryDriverID
    ){

        editReaperPrimaryDriverID.value =
        "";

    }


    if(
        editReaperPrimaryDriverText
    ){

        editReaperPrimaryDriverText.textContent =
        "Select Primary Driver";

    }


    if(
        editReaperTractorMenu
    ){

        editReaperTractorMenu.innerHTML =
        `
        <div class="reaper-tractor-empty">
            Loading tractors...
        </div>
        `;

    }


    if(
        editReaperPrimaryDriverMenu
    ){

        editReaperPrimaryDriverMenu.innerHTML =
        `
        <div class="reaper-tractor-empty">
            Loading drivers...
        </div>
        `;

    }


    if(
        editReaperSecondaryTractorMenu
    ){

        editReaperSecondaryTractorMenu.innerHTML =
        `
        <div class="reaper-tractor-empty">
            Loading tractors...
        </div>
        `;

    }


    if(
        editReaperSecondaryDriverMenu
    ){

        editReaperSecondaryDriverMenu.innerHTML =
        `
        <div class="reaper-tractor-empty">
            Loading drivers...
        </div>
        `;

    }


    editReaperStatusMenu
    ?.classList
    .remove(
        "show"
    );


    editReaperRemarksMenu
    ?.classList
    .remove(
        "show"
    );


    editReaperModalElement
    ?.classList
    .remove(
        "reaper-paid-final"
    );

}


/* ======================================================
   POPULATE EDIT MODAL
====================================================== */

async function populateEditReaperModal(
    record
){

    resetEditReaperModal();


    editReaperLoadedRecord =
    {
        ...record
    };


    const reaperID =
    editReaperInt(
        record.reaper_id
    );


    const transactionID =
    editReaperString(
        record.transaction_id
    );


    const customerName =
    editReaperString(
        record.customer_name
    );


    const affiliate =
    editReaperString(
        record.affiliate
    );


    const location =
    editReaperString(
        record.location
    );


    const rentalDate =
    editReaperString(
        record.rental_date
    );


    /* ==========================================
       PRIMARY TRACTOR
    ========================================== */

    const primaryTractorID =
    editReaperInt(
        record.tractor_id
    );


    const primaryTractorNo =
    editReaperString(
        record.tractor_no
    );


    const primaryTractorName =
    editReaperString(
        record.tractor_name
    );


    /* ==========================================
       PRIMARY DRIVER
    ========================================== */

    const primaryDriverID =
    editReaperInt(
        record.primary_driver_id
    );


    const primaryDriverName =
    editReaperString(

        record.primary_driver_name

        ||

        record.operator

    );


    const primaryDriverCode =
    editReaperString(
        record.primary_driver_code
    );


    /* ==========================================
       SECONDARY TRACTOR
    ========================================== */

    const secondaryTractorID =
    editReaperInt(
        record.secondary_tractor_id
    );


    const secondaryTractorNo =
    editReaperString(
        record.secondary_tractor_no
    );


    const secondaryTractorName =
    editReaperString(
        record.secondary_tractor_name
    );


    /* ==========================================
       SECONDARY DRIVER
    ========================================== */

    const secondaryDriverID =
    editReaperInt(
        record.secondary_driver_id
    );


    const secondaryDriverName =
    editReaperString(

        record.secondary_driver_name

        ||

        record.secondary_operator

    );


    const secondaryDriverCode =
    editReaperString(
        record.secondary_driver_code
    );


    const status =
    editReaperString(
        record.status
    );


    const remarks =
    editReaperString(
        record.remarks
    )
    ||
    "Unpaid";


    /* ==========================================
       SUMMARY
    ========================================== */

    if(
        editReaperID
    ){

        editReaperID.value =
        String(
            reaperID
        );

    }


    if(
        editReaperTransactionBadge
    ){

        editReaperTransactionBadge.textContent =
        transactionID
        ||
        "RPR-0000";

    }


    if(
        editReaperCustomerName
    ){

        editReaperCustomerName.value =
        customerName;

    }


    if(
        editReaperAffiliate
    ){

        editReaperAffiliate.value =
        affiliate;

    }


    if(
        editReaperAffiliateText
    ){

        editReaperAffiliateText.textContent =
        affiliate
        ||
        "—";

    }


    if(
        editReaperLocation
    ){

        editReaperLocation.value =
        location;

    }


    if(
        editReaperDate
    ){

        editReaperDate.value =
        rentalDate;

    }


    if(
        editReaperDateText
    ){

        editReaperDateText.textContent =

        rentalDate

        ?

        reaperFormatDate(
            rentalDate
        )

        :

        "Select Rental Date";

    }


    if(
        rentalDate
    ){

        const rentalDateParts =
        rentalDate.split(
            "-"
        );


        if(
            rentalDateParts.length
            ===
            3
        ){

            editReaperCalendarView =
            new Date(

                parseInt(
                    rentalDateParts[0],
                    10
                ),

                parseInt(
                    rentalDateParts[1],
                    10
                )
                -
                1,

                1

            );

        }

    }


    /* ==========================================
       PRIMARY TRACTOR VALUE
    ========================================== */

    if(
        editReaperTractorID
    ){

        editReaperTractorID.value =

        primaryTractorID > 0

        ?

        String(
            primaryTractorID
        )

        :

        "";

    }


    if(
        editReaperTractorNo
    ){

        editReaperTractorNo.value =
        primaryTractorNo;

    }


    if(
        editReaperTractorText
    ){

        editReaperTractorText.textContent =
        editReaperAssignmentLabel(

            primaryTractorNo,

            primaryTractorName

        );

    }


    /* ==========================================
       PRIMARY DRIVER VALUE
    ========================================== */

    if(
        editReaperPrimaryDriverID
    ){

        editReaperPrimaryDriverID.value =

        primaryDriverID > 0

        ?

        String(
            primaryDriverID
        )

        :

        "";

    }


    if(
        editReaperPrimaryDriverText
    ){

        editReaperPrimaryDriverText.textContent =
        editReaperAssignmentLabel(

            primaryDriverCode,

            primaryDriverName

        );

    }


    /* ==========================================
       SECONDARY TRACTOR VALUE
    ========================================== */

    if(
        editReaperSecondaryTractorID
    ){

        editReaperSecondaryTractorID.value =

        secondaryTractorID > 0

        ?

        String(
            secondaryTractorID
        )

        :

        "";

    }


    if(
        editReaperSecondaryTractorNo
    ){

        editReaperSecondaryTractorNo.value =
        secondaryTractorNo;

    }


    if(
        editReaperSecondaryTractorText
    ){

        editReaperSecondaryTractorText.textContent =

        secondaryTractorID > 0

        ?

        editReaperAssignmentLabel(

            secondaryTractorNo,

            secondaryTractorName

        )

        :

        "No Secondary Tractor";

    }


    /* ==========================================
       SECONDARY DRIVER VALUE
    ========================================== */

    if(
        editReaperSecondaryDriverID
    ){

        editReaperSecondaryDriverID.value =

        secondaryDriverID > 0

        ?

        String(
            secondaryDriverID
        )

        :

        "";

    }


    if(
        editReaperSecondaryDriverText
    ){

        editReaperSecondaryDriverText.textContent =

        secondaryDriverID > 0

        ?

        editReaperAssignmentLabel(

            secondaryDriverCode,

            secondaryDriverName

        )

        :

        "No Secondary Driver";

    }


    if(
        secondaryTractorID > 0
        ||
        secondaryDriverID > 0
    ){

        openEditReaperSecondaryUI();

    }


    /* ==========================================
       SERVICE DATA
    ========================================== */

    if(
        editReaperHarvest
    ){

        editReaperHarvest.value =
        record.no_of_harvest
        ??
        "";

    }


    if(
        editReaperCommodity
    ){

        editReaperCommodity.value =
        record.commodity
        ??
        "";

    }


    /* ==========================================
       STATUS
    ========================================== */

    if(
        editReaperStatus
    ){

        editReaperStatus.value =
        status;

    }


    if(
        editReaperStatusText
    ){

        editReaperStatusText.textContent =
        status
        ||
        "Select Status";

    }


    /* ==========================================
       REMARKS
    ========================================== */

    if(
        editReaperRemarks
    ){

        editReaperRemarks.value =
        remarks;

    }


    if(
        editReaperRemarksText
    ){

        editReaperRemarksText.textContent =
        remarks;

    }


    /* ==========================================
       ORIGINAL STATE
    ========================================== */

    editReaperOriginalStatus =
    status;


    editReaperOriginalRemarks =
    remarks;


    editReaperRentalDate =
    rentalDate;


    editReaperPrimaryTractorStatus =
    editReaperString(

        record.primary_tractor_status

        ||

        record.tractor_status

    );


    /* ==========================================
       LOAD LIVE AVAILABILITY
    ========================================== */

    const availabilityLoaded =
    await loadEditReaperAssignmentAvailability();


    if(
        !availabilityLoaded
    ){

        if(
            editReaperTractorBtn
        ){

            editReaperTractorBtn.disabled =
            true;

        }


        if(
            editReaperPrimaryDriverBtn
        ){

            editReaperPrimaryDriverBtn.disabled =
            true;

        }


        if(
            editReaperSecondaryToggle
        ){

            editReaperSecondaryToggle.disabled =
            true;

        }


        if(
            editReaperSecondaryTractorBtn
        ){

            editReaperSecondaryTractorBtn.disabled =
            true;

        }


        if(
            editReaperSecondaryDriverBtn
        ){

            editReaperSecondaryDriverBtn.disabled =
            true;

        }

    }


    applyAllReaperEditRules();

}


/* ======================================================
   LOAD EDIT RECORD
====================================================== */

async function loadReaperForEdit(
    reaperID
){

    const numericID =
    editReaperInt(
        reaperID
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
            "Unable to identify the selected Reaper rental.",

            confirmButtonColor:
            "#B71C1C"

        });


        return;

    }


    try{

        const response =
        await fetch(

            "get_reaper.php?id="

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
        catch(
            error
        ){

            throw new Error(
                "Invalid server response."
            );

        }


        if(
            !response.ok
            ||
            !result
            ||
            result.success
            !==
            true
            ||
            !result.data
        ){

            throw new Error(

                result
                ?.message

                ||

                "Unable to load the selected Reaper rental."

            );

        }


        await populateEditReaperModal(
            result.data
        );


        if(
            !editReaperModalElement
        ){

            throw new Error(
                "Edit modal was not found."
            );

        }


        const modal =
        bootstrap.Modal
        .getOrCreateInstance(
            editReaperModalElement
        );


        modal.show();

    }
    catch(
        error
    ){

        console.error(
            "LOAD REAPER EDIT ERROR:",
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

            "Unable to load the Reaper rental.",

            confirmButtonColor:
            "#B71C1C"

        });

    }

}


/* ======================================================
   OPEN EDIT BUTTON
====================================================== */

document
.querySelectorAll(
    ".reaper-edit-btn"
)
.forEach(
function(
    button
){

    button.addEventListener(

        "click",

        function(
            event
        ){

            event.preventDefault();

            event.stopPropagation();


            const row =
            button.closest(
                ".reaper-row"
            );


            const reaperID =

                button.dataset.id

                ||

                row
                ?.dataset.id

                ||

                "";


            loadReaperForEdit(
                reaperID
            );

        }

    );

});


/* ======================================================
   FINAL ASSIGNMENT RECHECK BEFORE UPDATE
====================================================== */

async function validateEditReaperAssignmentsBeforeSave(){

    const rentalDate =
    editReaperString(
        editReaperDate
        ?.value
    );


    const reaperID =
    editReaperInt(
        editReaperID
        ?.value
    );


    const status =
    editReaperString(
        editReaperStatus
        ?.value
    );


    if(
        !rentalDate
        ||
        reaperID <= 0
    ){

        return false;

    }


    /*
    Done records no longer reserve
    Tractor / Driver resources.
    */

    if(
        status
        ===
        "Done"
    ){

        return true;

    }


    const primaryTractorID =
    editReaperInt(
        editReaperTractorID
        ?.value
    );


    const primaryDriverID =
    editReaperInt(
        editReaperPrimaryDriverID
        ?.value
    );


    const secondaryTractorID =
    editReaperInt(
        editReaperSecondaryTractorID
        ?.value
    );


    const secondaryDriverID =
    editReaperInt(
        editReaperSecondaryDriverID
        ?.value
    );


    const [
        tractorData,
        driverData
    ] =

    await Promise.all([

        getEditReaperTractorAvailability(
            rentalDate,
            reaperID
        ),

        getEditReaperDriverAvailability(
            rentalDate,
            reaperID
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


    function tractorAvailable(
        id
    ){

        const tractor =
        tractors.find(
        function(
            item
        ){

            return (
                String(
                    item.tractor_id
                )
                ===
                String(
                    id
                )
            );

        });


        return (
            !!tractor
            &&
            tractor.available
            ===
            true
        );

    }


    function driverAvailable(
        id
    ){

        const driver =
        drivers.find(
        function(
            item
        ){

            return (
                String(
                    item.driver_id
                )
                ===
                String(
                    id
                )
            );

        });


        return (
            !!driver
            &&
            driver.available
            ===
            true
        );

    }


    /* ==========================================
       PRIMARY TRACTOR
    ========================================== */

    if(
        !tractorAvailable(
            primaryTractorID
        )
    ){

        await loadEditReaperAssignmentAvailability();


        Swal.fire({

            icon:
            "warning",

            title:
            "Primary Tractor Unavailable",

            text:
            "The selected Primary Tractor is no longer available on this Rental Date.",

            confirmButtonColor:
            "#8CA15B"

        });


        return false;

    }


    /* ==========================================
       PRIMARY DRIVER
    ========================================== */

    if(
        !driverAvailable(
            primaryDriverID
        )
    ){

        await loadEditReaperAssignmentAvailability();


        Swal.fire({

            icon:
            "warning",

            title:
            "Primary Driver Unavailable",

            text:
            "The selected Primary Driver is no longer available on this Rental Date.",

            confirmButtonColor:
            "#8CA15B"

        });


        return false;

    }


    /* ==========================================
       SECONDARY TRACTOR
    ========================================== */

    if(
        secondaryTractorID > 0

        &&

        !tractorAvailable(
            secondaryTractorID
        )
    ){

        await loadEditReaperAssignmentAvailability();


        Swal.fire({

            icon:
            "warning",

            title:
            "Secondary Tractor Unavailable",

            text:
            "The selected Secondary Tractor is no longer available on this Rental Date.",

            confirmButtonColor:
            "#8CA15B"

        });


        return false;

    }


    /* ==========================================
       SECONDARY DRIVER
    ========================================== */

    if(
        secondaryDriverID > 0

        &&

        !driverAvailable(
            secondaryDriverID
        )
    ){

        await loadEditReaperAssignmentAvailability();


        Swal.fire({

            icon:
            "warning",

            title:
            "Secondary Driver Unavailable",

            text:
            "The selected Secondary Driver is no longer available on this Rental Date.",

            confirmButtonColor:
            "#8CA15B"

        });


        return false;

    }


    return true;

}


/* ======================================================
   UPDATE REAPER
====================================================== */

if(
    updateReaperBtn
    &&
    editReaperForm
){

    updateReaperBtn
    .addEventListener(

        "click",

        async function(){


            /* ==========================================
               BUTTON / FINALIZED GUARD
            ========================================== */

            if(
                updateReaperBtn.disabled
            ){

                return;

            }


            if(
                editReaperOriginalRemarks
                ===
                "Paid"
            ){

                return;

            }


            const reaperID =
            editReaperInt(
                editReaperID
                ?.value
            );


            const primaryTractorID =
            editReaperInt(
                editReaperTractorID
                ?.value
            );


            const primaryDriverID =
            editReaperInt(
                editReaperPrimaryDriverID
                ?.value
            );


            const secondaryTractorID =
            editReaperInt(
                editReaperSecondaryTractorID
                ?.value
            );


            const secondaryDriverID =
            editReaperInt(
                editReaperSecondaryDriverID
                ?.value
            );


            /* ==========================================
               RECORD
            ========================================== */

            if(
                reaperID <= 0
            ){

                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Invalid Rental",

                    text:
                    "Unable to identify the Reaper rental.",

                    confirmButtonColor:
                    "#B71C1C"

                });


                return;

            }


            /* ==========================================
               PRIMARY TRACTOR
            ========================================== */

            if(
                primaryTractorID <= 0
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Primary Tractor Required",

                    text:
                    "Please select a Primary Tractor.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               PRIMARY DRIVER
            ========================================== */

            if(
                primaryDriverID <= 0
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Primary Driver Required",

                    text:
                    "Please select a Primary Driver.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               SECONDARY PAIR
            ========================================== */

            const hasSecondaryTractor =
            secondaryTractorID > 0;


            const hasSecondaryDriver =
            secondaryDriverID > 0;


            if(
                hasSecondaryTractor
                !==
                hasSecondaryDriver
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Complete Secondary Assignment",

                    text:
                    "Secondary Tractor and Secondary Driver must be selected together.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               DUPLICATE TRACTOR
            ========================================== */

            if(
                hasSecondaryTractor

                &&

                secondaryTractorID
                ===
                primaryTractorID
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Duplicate Tractor",

                    text:
                    "Primary Tractor and Secondary Tractor must be different.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               DUPLICATE DRIVER
            ========================================== */

            if(
                hasSecondaryDriver

                &&

                secondaryDriverID
                ===
                primaryDriverID
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Duplicate Driver",

                    text:
                    "Primary Driver and Secondary Driver must be different.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               HARVEST
            ========================================== */

            const harvestRaw =
            editReaperString(
                editReaperHarvest
                ?.value
            );


            if(
                harvestRaw
                !==
                ""
            ){

                const harvest =
                Number(
                    harvestRaw
                );


                if(
                    !Number.isInteger(
                        harvest
                    )
                    ||
                    harvest < 1
                ){

                    Swal.fire({

                        icon:
                        "warning",

                        title:
                        "Invalid Harvest",

                        text:
                        "No. of Harvest must be a whole number of at least 1.",

                        confirmButtonColor:
                        "#8CA15B"

                    });


                    editReaperHarvest
                    ?.focus();


                    return;

                }

            }


            /* ==========================================
               COMMODITY
            ========================================== */

            const commodityRaw =
            editReaperString(
                editReaperCommodity
                ?.value
            );


            if(
                commodityRaw
                !==
                ""
            ){

                const commodity =
                Number(
                    commodityRaw
                );


                if(
                    !Number.isInteger(
                        commodity
                    )
                    ||
                    commodity <= 0
                ){

                    Swal.fire({

                        icon:
                        "warning",

                        title:
                        "Invalid Commodity",

                        text:
                        "Commodity must be a whole number of at least 1.",

                        confirmButtonColor:
                        "#8CA15B"

                    });


                    editReaperCommodity
                    ?.focus();


                    return;

                }

            }


            const status =
            editReaperString(
                editReaperStatus
                ?.value
            );


            const remarks =
            editReaperString(
                editReaperRemarks
                ?.value
            );


            /* ==========================================
               STATUS REQUIRED
            ========================================== */

            if(
                !status
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Status Required",

                    text:
                    "Please select the Rental status.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               DONE REQUIRES COMPLETION
            ========================================== */

            if(
                status
                ===
                "Done"

                &&

                !reaperEditCompletionReady()
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Completion Data Required",

                    text:
                    "Please enter both No. of Harvest and Commodity before marking this rental as Done.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               REMARKS REQUIRED
            ========================================== */

            if(
                !remarks
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Payment Status Required",

                    text:
                    "Please select the payment status.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               PENDING / ONGOING = UNPAID
            ========================================== */

            if(
                [
                    "Pending",
                    "Ongoing"
                ]
                .includes(
                    status
                )

                &&

                remarks
                ===
                "Paid"
            ){

                editReaperRemarks.value =
                "Unpaid";


                if(
                    editReaperRemarksText
                ){

                    editReaperRemarksText.textContent =
                    "Unpaid";

                }


                refreshReaperEditModalState();


                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Payment Not Available Yet",

                    text:
                    "Pending and Ongoing Reaper rentals must remain Unpaid.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               PAID REQUIRES DONE
            ========================================== */

            if(
                remarks
                ===
                "Paid"

                &&

                (
                    status
                    !==
                    "Done"

                    ||

                    !reaperEditCompletionReady()
                )
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Payment Not Available Yet",

                    text:
                    "The rental must be Done with completion data before it can be marked Paid.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               FINAL LIVE AVAILABILITY CHECK
            ========================================== */

            try{

                const assignmentsValid =
                await validateEditReaperAssignmentsBeforeSave();


                if(
                    !assignmentsValid
                ){

                    return;

                }

            }
            catch(
                error
            ){

                console.error(
                    "EDIT REAPER FINAL AVAILABILITY ERROR:",
                    error
                );


                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Availability Check Failed",

                    text:
                    error.message

                    ||

                    "Unable to verify Rental availability.",

                    confirmButtonColor:
                    "#B71C1C"

                });


                return;

            }


            /* ==========================================
               CONFIRMATION
            ========================================== */

            const finalizingPayment =
            remarks
            ===
            "Paid";


            const result =
            await Swal.fire({

                title:

                    finalizingPayment

                    ?

                    "Finalize this payment?"

                    :

                    "Update this rental?",


                text:

                    finalizingPayment

                    ?

                    "Once saved as Paid, this Reaper transaction becomes finalized and can no longer be edited."

                    :

                    "The Reaper rental record will be updated.",


                icon:
                "question",

                showCancelButton:
                true,

                reverseButtons:
                true,

                confirmButtonColor:
                "#8CA15B",

                cancelButtonColor:
                "#C84B4B",

                confirmButtonText:

                    finalizingPayment

                    ?

                    "Finalize"

                    :

                    "Yes, Update",

                cancelButtonText:
                "Cancel"

            });


            if(
                !result.isConfirmed
            ){

                return;

            }


            const originalButtonText =
            updateReaperBtn.textContent;


            updateReaperBtn.disabled =
            true;


            updateReaperBtn.textContent =
            "SAVING...";


            /* ==========================================
               UPDATE
            ========================================== */

            try{

                const formData =
                new FormData(
                    editReaperForm
                );


                const response =
                await fetch(

                    "update/update_reaper.php",

                    {
                        method:
                        "POST",

                        body:
                        formData
                    }

                );


                const data =
                (
                    await response.text()
                )
                .trim();


                if(
                    response.ok
                    &&
                    data
                    ===
                    "success"
                ){

                    const modal =

                        editReaperModalElement

                        ?

                        bootstrap.Modal
                        .getInstance(
                            editReaperModalElement
                        )

                        :

                        null;


                    modal
                    ?.hide();


                    await Swal.fire({

                        icon:
                        "success",

                        title:

                            finalizingPayment

                            ?

                            "Payment finalized successfully."

                            :

                            "Rental updated successfully.",

                        showConfirmButton:
                        false,

                        timer:
                        1500

                    });


                    saveReaperLedgerPosition();


                    window.location
                    .reload();


                    return;

                }


                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Update Failed",

                    text:
                    data

                    ||

                    "Unable to update the rental.",

                    confirmButtonColor:
                    "#B71C1C"

                });

            }
            catch(
                error
            ){

                console.error(
                    "UPDATE REAPER ERROR:",
                    error
                );


                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Update Failed",

                    text:
                    "Unable to update the rental. Please try again.",

                    confirmButtonColor:
                    "#B71C1C"

                });

            }
            finally{

                if(
                    editReaperOriginalRemarks
                    !==
                    "Paid"
                ){

                    updateReaperBtn.disabled =
                    false;


                    updateReaperBtn.textContent =
                    originalButtonText;


                    refreshReaperEditModalState();

                }

            }

        }

    );

}


/* ======================================================
   CLOSE EDIT MENUS
====================================================== */

document.addEventListener(

    "click",

    function(){

        closeEditReaperAssignmentMenus();


        editReaperCalendar
        ?.classList
        .remove(
            "show"
        );


        editReaperStatusMenu
        ?.classList
        .remove(
            "show"
        );


        editReaperRemarksMenu
        ?.classList
        .remove(
            "show"
        );

    }

);


/* ======================================================
   RESET AFTER MODAL CLOSE
====================================================== */

editReaperModalElement
?.addEventListener(

    "hidden.bs.modal",

    function(){

        resetEditReaperModal();

    }

);


/* ======================================================
   PRESERVE REAPER LEDGER POSITION AFTER UPDATE
====================================================== */

const reaperLedgerBody =
document.querySelector(
    ".reaper-ledger-body"
);


const reaperLedgerScrollKey =
"reaperLedgerScrollTop";


function saveReaperLedgerPosition(){

    if(
        !reaperLedgerBody
    ){

        return;
    }


    sessionStorage.setItem(

        reaperLedgerScrollKey,

        String(
            reaperLedgerBody.scrollTop
        )

    );

}


function restoreReaperLedgerPosition(){

    if(
        !reaperLedgerBody
    ){

        return;
    }


    const savedPosition =
    sessionStorage.getItem(
        reaperLedgerScrollKey
    );


    if(
        savedPosition === null
    ){

        return;
    }


    reaperLedgerBody.scrollTop =
    Number(
        savedPosition
    )
    ||
    0;


    sessionStorage.removeItem(
        reaperLedgerScrollKey
    );

}


restoreReaperLedgerPosition();

/* ======================================================
   SEARCH / FILTER
====================================================== */

const searchInput =
document.getElementById(
    "searchReaper"
);


const affiliateFilter =
document.getElementById(
    "affiliateFilter"
);


const statusFilter =
document.getElementById(
    "statusFilter"
);


const remarksFilter =
document.getElementById(
    "remarksFilter"
);


const startDateFilter =
document.getElementById(
    "reaperStartDateFilter"
);


const endDateFilter =
document.getElementById(
    "reaperEndDateFilter"
);


const applyDateFilter =
document.getElementById(
    "reaperApplyDateFilter"
);


const clearDateFilter =
document.getElementById(
    "reaperClearDateFilter"
);

/* ======================================================
   LEDGER FILTER DATE RESTRICTIONS
====================================================== */

function updateReaperLedgerDateRestrictions(){

    if(startDateFilter){

        startDateFilter.min = "";

        startDateFilter.max = endDateFilter
            ? endDateFilter.value
            : "";
    }

    if(endDateFilter){

        endDateFilter.min = startDateFilter
            ? startDateFilter.value
            : "";

        endDateFilter.max = "";
    }
}

if(startDateFilter){
    startDateFilter.addEventListener(
        "change",
        updateReaperLedgerDateRestrictions
    );
}

if(endDateFilter){
    endDateFilter.addEventListener(
        "change",
        updateReaperLedgerDateRestrictions
    );
}

updateReaperLedgerDateRestrictions();


function applyReaperFilters() {

    const keyword = searchInput
        ? searchInput.value
            .toLowerCase()
            .trim()
        : "";

    const affiliate = affiliateFilter
        ? affiliateFilter.value
        : "all";

    const status = statusFilter
        ? statusFilter.value
        : "all";

    const remarks = remarksFilter
        ? remarksFilter.value
        : "all";

    const startDate = startDateFilter
        ? startDateFilter.value
        : "";

    const endDate = endDateFilter
        ? endDateFilter.value
        : "";


    document
    .querySelectorAll(".reaper-row")
    .forEach(function(row) {

        const searchText = (
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
            searchText.includes(keyword);

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

        const matchStartDate =
            !startDate ||
            rowDate >= startDate;

        const matchEndDate =
            !endDate ||
            rowDate <= endDate;


        row.style.display =
            (
                matchSearch &&
                matchAffiliate &&
                matchStatus &&
                matchRemarks &&
                matchStartDate &&
                matchEndDate
            )
                ? "grid"
                : "none";

    });

    const emptyMessage =
    document.getElementById("reaperFilterEmpty");

    if(emptyMessage){

        const rows =
        document.querySelectorAll(".reaper-row");

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


if(searchInput){

    searchInput.addEventListener(
        "input",
        applyReaperFilters
    );

}


if(affiliateFilter){

    affiliateFilter.addEventListener(
        "change",
        applyReaperFilters
    );

}


if(statusFilter){

    statusFilter.addEventListener(
        "change",
        applyReaperFilters
    );

}


if(remarksFilter){

    remarksFilter.addEventListener(
        "change",
        applyReaperFilters
    );

}


if(applyDateFilter){

    applyDateFilter.addEventListener(
        "click",
        function(){

            const start = startDateFilter
                ? startDateFilter.value
                : "";

            const end = endDateFilter
                ? endDateFilter.value
                : "";

            if(start && end && start > end){

                Swal.fire({
                    icon: "warning",
                    title: "Invalid Date Range",
                    text: "The Start Date cannot be later than the End Date.",
                    confirmButtonColor: "#8CA15B"
                });

                return;
            }

            applyReaperFilters();

            const dateModal = document.getElementById(
                "reaperDateFilterModal"
            );

            if(dateModal && typeof bootstrap !== "undefined"){

                bootstrap.Modal
                    .getOrCreateInstance(dateModal)
                    .hide();
            }
        }
    );

}


if(clearDateFilter){

    clearDateFilter.addEventListener(

        "click",

        function(){

            if(startDateFilter){

                startDateFilter.value =
                "";

            }


            if(endDateFilter){

                endDateFilter.value =
                "";

            }


            if(affiliateFilter){

                affiliateFilter.value =
                "all";

            }


            if(statusFilter){

                statusFilter.value =
                "all";

            }


            if(remarksFilter){

                remarksFilter.value =
                "all";

            }


            if(searchInput){

                searchInput.value =
                "";

            }


            updateReaperLedgerDateRestrictions();

            applyReaperFilters();

        }

    );

}


applyReaperFilters();