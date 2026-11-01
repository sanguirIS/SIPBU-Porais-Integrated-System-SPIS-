/* ======================================================
   HAULING
   DATE-ONLY RENTAL ASSIGNMENT
====================================================== */


/* ======================================================
   MAIN FORM ELEMENTS
====================================================== */

const haulingAffiliate =
document.getElementById(
    "haulingAffiliate"
);


const haulingPrice =
document.getElementById(
    "haulingPrice"
);


const haulingDiscount =
document.getElementById(
    "haulingDiscount"
);


const haulingTotal =
document.getElementById(
    "haulingTotal"
);


const haulingForm =
document.getElementById(
    "haulingForm"
);


const saveHaulingBtn =
document.getElementById(
    "saveHaulingBtn"
);


/* ======================================================
   MEMBER ELEMENTS
====================================================== */

const customerInput =
document.getElementById(
    "haulingCustomerName"
);


const suggestionBox =
document.getElementById(
    "memberSuggestion"
);


const memberID =
document.getElementById(
    "member_id"
);


/* ======================================================
   INITIAL CUSTOMER STATE
====================================================== */

if(
    customerInput
    &&
    !haulingAffiliate?.value
){

    customerInput.disabled =
    true;


    customerInput.placeholder =
    "Select affiliate first";

}


/* ======================================================
   RENTAL DATE
====================================================== */

const haulingDate =
document.getElementById(
    "haulingDate"
);


const haulingDateBtn =
document.getElementById(
    "haulingDateBtn"
);


const haulingDateText =
document.getElementById(
    "haulingDateText"
);


const haulingCalendar =
document.getElementById(
    "haulingCalendar"
);


const haulingCalendarMonth =
document.getElementById(
    "haulingCalendarMonth"
);


const haulingCalendarDays =
document.getElementById(
    "haulingCalendarDays"
);


const haulingCalendarPrev =
document.getElementById(
    "haulingCalendarPrev"
);


const haulingCalendarNext =
document.getElementById(
    "haulingCalendarNext"
);


let haulingCalendarView =
new Date();


haulingCalendarView.setDate(
    1
);


/* ======================================================
   PRIMARY TRACTOR
====================================================== */

const haulingTractorID =
document.getElementById(
    "haulingTractorID"
);


const haulingTractorNo =
document.getElementById(
    "haulingTractorNo"
);


const haulingTractorBtn =
document.getElementById(
    "haulingTractorBtn"
);


const haulingTractorText =
document.getElementById(
    "haulingTractorText"
);


const haulingTractorMenu =
document.getElementById(
    "haulingTractorMenu"
);


/* ======================================================
   PRIMARY DRIVER
====================================================== */

const haulingPrimaryDriverID =
document.getElementById(
    "haulingPrimaryDriverID"
);


const haulingPrimaryDriverBtn =
document.getElementById(
    "haulingPrimaryDriverBtn"
);


const haulingPrimaryDriverText =
document.getElementById(
    "haulingPrimaryDriverText"
);


const haulingPrimaryDriverMenu =
document.getElementById(
    "haulingPrimaryDriverMenu"
);


/* ======================================================
   SECONDARY TRACTOR
====================================================== */

const haulingSecondaryTractorID =
document.getElementById(
    "haulingSecondaryTractorID"
);


const haulingSecondaryTractorNo =
document.getElementById(
    "haulingSecondaryTractorNo"
);


const haulingSecondaryTractorBtn =
document.getElementById(
    "haulingSecondaryTractorBtn"
);


const haulingSecondaryTractorText =
document.getElementById(
    "haulingSecondaryTractorText"
);


const haulingSecondaryTractorMenu =
document.getElementById(
    "haulingSecondaryTractorMenu"
);


/* ======================================================
   SECONDARY DRIVER
====================================================== */

const haulingSecondaryDriverID =
document.getElementById(
    "haulingSecondaryDriverID"
);


const haulingSecondaryDriverBtn =
document.getElementById(
    "haulingSecondaryDriverBtn"
);


const haulingSecondaryDriverText =
document.getElementById(
    "haulingSecondaryDriverText"
);


const haulingSecondaryDriverMenu =
document.getElementById(
    "haulingSecondaryDriverMenu"
);


/* ======================================================
   ADD STATUS
====================================================== */

const statusBtn =
document.getElementById(
    "statusBtn"
);


const statusMenu =
document.getElementById(
    "statusMenu"
);


const statusText =
document.getElementById(
    "statusText"
);


const haulingStatus =
document.getElementById(
    "haulingStatus"
);

const haulingHistoricalStatusNote =
document.getElementById(
    "haulingHistoricalStatusNote"
);


/* ======================================================
   AVAILABILITY STATE
====================================================== */

let haulingTractors =
[];


let haulingDrivers =
[];


let haulingAvailabilityRequest =
0;


/* ======================================================
   DATE HELPERS
====================================================== */

function haulingPadNumber(
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


function haulingBuildDateKey(
    year,
    month,
    day
){

    return (
        year
        +
        "-"
        +
        haulingPadNumber(
            month + 1
        )
        +
        "-"
        +
        haulingPadNumber(
            day
        )
    );

}


function haulingFormatDate(
    dateValue
){

    if(!dateValue){

        return "";

    }


    const parts =
    dateValue.split("-");


    if(
        parts.length
        !==
        3
    ){

        return dateValue;

    }


    const year =
    parseInt(
        parts[0],
        10
    );


    const month =
    parseInt(
        parts[1],
        10
    )
    -
    1;


    const day =
    parseInt(
        parts[2],
        10
    );


    const dateObject =
    new Date(
        year,
        month,
        day
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
   TODAY
====================================================== */

function getHaulingTodayKey(){

    const today =
    new Date();


    return haulingBuildDateKey(

        today.getFullYear(),

        today.getMonth(),

        today.getDate()

    );

}


/* ======================================================
   ESCAPE HTML
====================================================== */

function haulingEscapeHTML(
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
   RESET ADD STATUS
====================================================== */

function resetHaulingAddStatus(){

    if(
        haulingStatus
    ){

        haulingStatus.value =
        "";

    }


    if(
        statusText
    ){

        statusText.textContent =
        "Select Date First";

    }


    if(
        statusBtn
    ){

        statusBtn.disabled =
        true;


        statusBtn
        .classList
        .remove(
            "is-historical-locked"
        );

    }


    if(
        haulingHistoricalStatusNote
    ){

        haulingHistoricalStatusNote.hidden =
        true;

    }


    statusMenu
    ?.classList
    .remove(
        "show"
    );


    document
    .querySelectorAll(
        "#statusMenu .hauling-status-item"
    )
    .forEach(
    function(item){

        item.style.display =
        "";

    });

}


/* ======================================================
   DATE-BASED STATUS RULES

   FUTURE
   - Pending only

   TODAY
   - Pending
   - Ongoing
   - Done

   PAST
   - Done only
   - Historical / Audit Entry
====================================================== */

function applyHaulingStatusRules(){

    if(
        !haulingDate
        ||
        !haulingStatus
        ||
        !statusBtn
    ){

        return;

    }


    const selectedDate =
    haulingDate.value;


    if(
        selectedDate
        ===
        ""
    ){

        resetHaulingAddStatus();

        return;

    }


    const today =
    getHaulingTodayKey();


    const isFuture =
    selectedDate
    >
    today;


    const isPast =
    selectedDate
    <
    today;


    const wasHistoricalLocked =
    statusBtn
    .classList
    .contains(
        "is-historical-locked"
    );


    const statusItems =
    document
    .querySelectorAll(
        "#statusMenu .hauling-status-item"
    );


    /*
       Clear previous Historical state first.
    */

    statusBtn
    .classList
    .remove(
        "is-historical-locked"
    );


    if(
        haulingHistoricalStatusNote
    ){

        haulingHistoricalStatusNote.hidden =
        true;

    }


    /* ==========================================
       FUTURE = PENDING ONLY
    ========================================== */

    if(
        isFuture
    ){

        haulingStatus.value =
        "Pending";


        if(
            statusText
        ){

            statusText.textContent =
            "Pending";

        }


        statusItems
        .forEach(
        function(item){

            item.style.display =

            item.dataset.value
            ===
            "Pending"

            ?

            ""

            :

            "none";

        });


        statusBtn.disabled =
        true;


        statusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==========================================
       PAST = HISTORICAL / AUDIT + DONE
    ========================================== */

    if(
        isPast
    ){

        haulingStatus.value =
        "Done";


        if(
            statusText
        ){

            statusText.textContent =
            "Done";

        }


        statusItems
        .forEach(
        function(item){

            item.style.display =

            item.dataset.value
            ===
            "Done"

            ?

            ""

            :

            "none";

        });


        statusBtn.disabled =
        true;


        statusBtn
        .classList
        .add(
            "is-historical-locked"
        );


        if(
            haulingHistoricalStatusNote
        ){

            haulingHistoricalStatusNote.hidden =
            false;

        }


        statusMenu
        ?.classList
        .remove(
            "show"
        );


        return;

    }


    /* ==========================================
       TODAY = NORMAL STATUS SELECTION
    ========================================== */

    statusBtn.disabled =
    false;


    statusItems
    .forEach(
    function(item){

        item.style.display =
        "";

    });


    /*
       If user came from a Historical date,
       return today's default to Pending.
    */

    if(
        wasHistoricalLocked
        ||
        haulingStatus.value
        ===
        ""
    ){

        haulingStatus.value =
        "Pending";


        if(
            statusText
        ){

            statusText.textContent =
            "Pending";

        }

    }

}


/* ======================================================
   CLOSE ASSIGNMENT MENUS
====================================================== */

function closeHaulingAssignmentMenus(
    exceptMenu = null
){

    const menus = [

        haulingTractorMenu,
        haulingPrimaryDriverMenu,

        haulingSecondaryTractorMenu,
        haulingSecondaryDriverMenu

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

function resetHaulingPrimaryTractor(){

    if(
        haulingTractorID
    ){

        haulingTractorID.value =
        "";

    }


    if(
        haulingTractorNo
    ){

        haulingTractorNo.value =
        "";

    }


    if(
        haulingTractorText
    ){

        haulingTractorText.textContent =

        haulingDate?.value

        ?

        "Select Primary Tractor"

        :

        "Select Rental Date First";

    }

}


/* ======================================================
   RESET PRIMARY DRIVER
====================================================== */

function resetHaulingPrimaryDriver(){

    if(
        haulingPrimaryDriverID
    ){

        haulingPrimaryDriverID.value =
        "";

    }


    if(
        haulingPrimaryDriverText
    ){

        haulingPrimaryDriverText.textContent =

        haulingDate?.value

        ?

        "Select Primary Driver"

        :

        "Select Rental Date First";

    }

}


/* ======================================================
   RESET SECONDARY TRACTOR
====================================================== */

function resetHaulingSecondaryTractor(){

    if(
        haulingSecondaryTractorID
    ){

        haulingSecondaryTractorID.value =
        "";

    }


    if(
        haulingSecondaryTractorNo
    ){

        haulingSecondaryTractorNo.value =
        "";

    }


    if(
        haulingSecondaryTractorText
    ){

        haulingSecondaryTractorText.textContent =
        "No Secondary Tractor";

    }

}


/* ======================================================
   RESET SECONDARY DRIVER
====================================================== */

function resetHaulingSecondaryDriver(){

    if(
        haulingSecondaryDriverID
    ){

        haulingSecondaryDriverID.value =
        "";

    }


    if(
        haulingSecondaryDriverText
    ){

        haulingSecondaryDriverText.textContent =
        "No Secondary Driver";

    }

}


/* ======================================================
   RESET ALL ASSIGNMENTS
====================================================== */

function resetHaulingAssignments(){

    resetHaulingPrimaryTractor();

    resetHaulingPrimaryDriver();

    resetHaulingSecondaryTractor();

    resetHaulingSecondaryDriver();

}


/* ======================================================
   DISABLE ASSIGNMENTS
====================================================== */

function disableHaulingAssignments(
    message = "Select Rental Date First"
){

    [

        haulingTractorBtn,
        haulingPrimaryDriverBtn,

        haulingSecondaryTractorBtn,
        haulingSecondaryDriverBtn

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
        haulingTractorText
    ){

        haulingTractorText.textContent =
        message;

    }


    if(
        haulingPrimaryDriverText
    ){

        haulingPrimaryDriverText.textContent =
        message;

    }


    if(
        haulingSecondaryTractorText
    ){

        haulingSecondaryTractorText.textContent =
        "No Secondary Tractor";

    }


    if(
        haulingSecondaryDriverText
    ){

        haulingSecondaryDriverText.textContent =
        "No Secondary Driver";

    }


    closeHaulingAssignmentMenus();

}


/* ======================================================
   LOADING ASSIGNMENTS
====================================================== */

function setHaulingAssignmentLoading(){

    resetHaulingAssignments();


    [

        haulingTractorBtn,
        haulingPrimaryDriverBtn,

        haulingSecondaryTractorBtn,
        haulingSecondaryDriverBtn

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
        haulingTractorText
    ){

        haulingTractorText.textContent =
        "Loading Tractors...";

    }


    if(
        haulingPrimaryDriverText
    ){

        haulingPrimaryDriverText.textContent =
        "Loading Drivers...";

    }

}


/* ======================================================
   TRACTOR AVAILABILITY
====================================================== */

async function getHaulingTractorAvailability(
    rentalDate
){

    const url =

    "tractor/get_tractor_availability.php"
    +
    "?tractor_type=Hauling"
    +
    "&rental_date="
    +
    encodeURIComponent(
        rentalDate
    )
    +
    "&rental_type=hauling";


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

async function getHaulingDriverAvailability(
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
    "&rental_type=hauling";


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
   TRACTOR STATUS CSS
====================================================== */

function getHaulingTractorStateClass(
    availability
){

    switch(
        availability
    ){

        case "Available":

            return "hauling-tractor-available";


        case "Reserved":

            return "hauling-tractor-reserved";


        case "In Use":

            return "hauling-tractor-in-use";


        case "Maintenance":

            return "hauling-tractor-maintenance";


        default:

            return "";

    }

}


/* ======================================================
   DRIVER STATUS CSS

   Reuse same visual status classes.
====================================================== */

function getHaulingDriverStateClass(
    availability
){

    switch(
        availability
    ){

        case "Available":

            return "hauling-tractor-available";


        case "Reserved":

            return "hauling-tractor-reserved";


        case "Assigned":

            return "hauling-tractor-in-use";


        default:

            return "";

    }

}


/* ======================================================
   RENDER TRACTOR MENU
====================================================== */

function renderHaulingTractorMenu(
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
        <div class="hauling-tractor-empty">
            No registered Hauling tractors.
        </div>
        `;


        menu.innerHTML =
        html;


        return;

    }


    tractors
    .forEach(
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


        const stateClass =
        getHaulingTractorStateClass(
            availability
        );


        const tractorID =
        parseInt(
            tractor.tractor_id,
            10
        )
        ||
        0;


        const tractorNo =
        haulingEscapeHTML(
            tractor.tractor_no
            ||
            ""
        );


        const tractorName =
        haulingEscapeHTML(
            tractor.tractor_name
            ||
            ""
        );


        html +=
        `
        <div
        class="
            hauling-tractor-item
            ${
                available
                ?
                ""
                :
                "hauling-tractor-disabled"
            }
        "
        data-id="${tractorID}"
        data-no="${tractorNo}"
        data-name="${tractorName}"
        data-availability="${
            haulingEscapeHTML(
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

            <div class="hauling-tractor-details">

                <span class="hauling-tractor-number">
                    ${tractorNo}
                </span>

                <span class="hauling-tractor-name">
                    ${tractorName}
                </span>

            </div>

            <span
            class="
                hauling-tractor-state
                ${stateClass}
            ">
                ${
                    haulingEscapeHTML(
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

function renderHaulingDriverMenu(
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
        <div class="hauling-tractor-empty">
            No active drivers registered.
        </div>
        `;


        menu.innerHTML =
        html;


        return;

    }


    drivers
    .forEach(
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


        const stateClass =
        getHaulingDriverStateClass(
            availability
        );


        const driverID =
        parseInt(
            driver.driver_id,
            10
        )
        ||
        0;


        const driverCode =
        haulingEscapeHTML(
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
        haulingEscapeHTML(
            driver.full_name
            ||
            ""
        );


        html +=
        `
        <div
        class="
            hauling-tractor-item
            ${
                available
                ?
                ""
                :
                "hauling-tractor-disabled"
            }
        "
        data-id="${driverID}"
        data-name="${driverName}"
        data-availability="${
            haulingEscapeHTML(
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

            <div class="hauling-tractor-details">

                <span class="hauling-tractor-number">
                    ${driverCode}
                </span>

                <span class="hauling-tractor-name">
                    ${driverName}
                </span>

            </div>

            <span
            class="
                hauling-tractor-state
                ${stateClass}
            ">
                ${
                    haulingEscapeHTML(
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
   SECONDARY MENU FILTER
   Hide current Primary Tractor / Driver
====================================================== */

function refreshHaulingSecondaryMenus(){

    const primaryTractorID =
    String(
        haulingTractorID?.value
        ||
        ""
    );


    const primaryDriverID =
    String(
        haulingPrimaryDriverID?.value
        ||
        ""
    );


    const secondaryTractors =
    haulingTractors.filter(
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


    const secondaryDrivers =
    haulingDrivers.filter(
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


    renderHaulingTractorMenu(
        haulingSecondaryTractorMenu,
        secondaryTractors,
        true
    );


    renderHaulingDriverMenu(
        haulingSecondaryDriverMenu,
        secondaryDrivers,
        true
    );

}

/* ======================================================
   LOAD DATE AVAILABILITY
====================================================== */

async function loadHaulingAssignmentAvailability(
    rentalDate
){

    if(
        !rentalDate
    ){

        haulingTractors =
        [];


        haulingDrivers =
        [];


        resetHaulingAssignments();


        disableHaulingAssignments();


        return;

    }


    const requestID =
    ++haulingAvailabilityRequest;


    setHaulingAssignmentLoading();


    try{


        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getHaulingTractorAvailability(
                rentalDate
            ),

            getHaulingDriverAvailability(
                rentalDate
            )

        ]);


        /* ==========================================
           IGNORE OLD RESPONSE
        ========================================== */

        if(
            requestID
            !==
            haulingAvailabilityRequest
        ){

            return;

        }


        if(
            haulingDate?.value
            !==
            rentalDate
        ){

            return;

        }


        haulingTractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        haulingDrivers =

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

        renderHaulingTractorMenu(

            haulingTractorMenu,

            haulingTractors,

            false

        );


        renderHaulingTractorMenu(

            haulingSecondaryTractorMenu,

            haulingTractors,

            false

        );


        renderHaulingDriverMenu(

            haulingPrimaryDriverMenu,

            haulingDrivers,

            false

        );


        renderHaulingDriverMenu(

            haulingSecondaryDriverMenu,

            haulingDrivers,

            false

        );


        /* ==========================================
           ENABLE BUTTONS
        ========================================== */

        if(
            haulingTractorBtn
        ){

            haulingTractorBtn.disabled =
            false;


            haulingTractorText.textContent =
            "Select Primary Tractor";

        }


        if(
            haulingPrimaryDriverBtn
        ){

            haulingPrimaryDriverBtn.disabled =
            false;


            haulingPrimaryDriverText.textContent =
            "Select Primary Driver";

        }


        if(
            haulingSecondaryTractorBtn
        ){

            haulingSecondaryTractorBtn.disabled =
            false;


            haulingSecondaryTractorText.textContent =
            "No Secondary Tractor";

        }


        if(
            haulingSecondaryDriverBtn
        ){

            haulingSecondaryDriverBtn.disabled =
            false;


            haulingSecondaryDriverText.textContent =
            "No Secondary Driver";

        }


        /* ==========================================
           NOTHING AVAILABLE
        ========================================== */

        const availableTractors =

        haulingTractors.filter(
            tractor =>
            tractor.available
            ===
            true
        );


        const availableDrivers =

        haulingDrivers.filter(
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

            Swal.fire({

                icon:
                "info",

                title:
                "Limited Availability",

                text:

                availableTractors.length
                ===
                0

                ?

                "There is no available Hauling tractor on the selected date."

                :

                "There is no available driver on the selected date.",

                confirmButtonColor:
                "#8CA15B"

            });

        }

    }
    catch(error){


        console.error(
            "HAULING AVAILABILITY ERROR:",
            error
        );


        if(
            requestID
            !==
            haulingAvailabilityRequest
        ){

            return;

        }


        haulingTractors =
        [];


        haulingDrivers =
        [];


        resetHaulingAssignments();


        disableHaulingAssignments(
            "Availability Unavailable"
        );


        Swal.fire({

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

function setupHaulingAssignmentButton(
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


            haulingCalendar
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


            closeHaulingAssignmentMenus(
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
   SETUP DROPDOWN BUTTONS
====================================================== */

setupHaulingAssignmentButton(

    haulingTractorBtn,
    haulingTractorMenu

);


setupHaulingAssignmentButton(

    haulingPrimaryDriverBtn,
    haulingPrimaryDriverMenu

);


setupHaulingAssignmentButton(

    haulingSecondaryTractorBtn,
    haulingSecondaryTractorMenu

);


setupHaulingAssignmentButton(

    haulingSecondaryDriverBtn,
    haulingSecondaryDriverMenu

);


/* ======================================================
   PRIMARY TRACTOR SELECTION
====================================================== */

haulingTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".hauling-tractor-item"
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
            tractorID <= 0
        ){

            return;

        }


        if(
            String(
                haulingSecondaryTractorID?.value
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


        haulingTractorID.value =
        tractorID;


        haulingTractorNo.value =
        item.dataset.no
        ||
        "";


        haulingTractorText.textContent =

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


        haulingTractorMenu
        .classList
        .remove(
            "show"
        );

        refreshHaulingSecondaryMenus();

    }
);


/* ======================================================
   PRIMARY DRIVER SELECTION
====================================================== */

haulingPrimaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".hauling-tractor-item"
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
            driverID <= 0
        ){

            return;

        }


        if(
            String(
                haulingSecondaryDriverID?.value
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


        haulingPrimaryDriverID.value =
        driverID;


        haulingPrimaryDriverText.textContent =
        item.dataset.name
        ||
        "Selected Driver";


        haulingPrimaryDriverMenu
        .classList
        .remove(
            "show"
        );

        refreshHaulingSecondaryMenus();

    }
);


/* ======================================================
   SECONDARY TRACTOR SELECTION
====================================================== */

haulingSecondaryTractorMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".hauling-tractor-item"
        );


        if(
            !item
        ){

            return;

        }


        /* ==========================================
           CLEAR SECONDARY ASSIGNMENT
        ========================================== */

        if(
            item.dataset.action
            ===
            "clear"
        ){

            resetHaulingSecondaryTractor();

            resetHaulingSecondaryDriver();


            haulingSecondaryTractorMenu
            .classList
            .remove(
                "show"
            );


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
            tractorID <= 0
        ){

            return;

        }


        if(
            String(
                haulingTractorID?.value
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


        haulingSecondaryTractorID.value =
        tractorID;


        haulingSecondaryTractorNo.value =
        item.dataset.no
        ||
        "";


        haulingSecondaryTractorText.textContent =

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


        haulingSecondaryTractorMenu
        .classList
        .remove(
            "show"
        );

    }
);


/* ======================================================
   SECONDARY DRIVER SELECTION
====================================================== */

haulingSecondaryDriverMenu
?.addEventListener(
    "click",
    function(event){


        const item =
        event.target.closest(
            ".hauling-tractor-item"
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

            resetHaulingSecondaryTractor();

            resetHaulingSecondaryDriver();


            haulingSecondaryDriverMenu
            .classList
            .remove(
                "show"
            );


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
            driverID <= 0
        ){

            return;

        }


        if(
            String(
                haulingPrimaryDriverID?.value
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


        haulingSecondaryDriverID.value =
        driverID;


        haulingSecondaryDriverText.textContent =
        item.dataset.name
        ||
        "Selected Driver";


        haulingSecondaryDriverMenu
        .classList
        .remove(
            "show"
        );

    }
);

/* ======================================================
   HAULING CALENDAR AVAILABILITY
====================================================== */

let haulingCalendarAvailability = {};

let haulingCalendarAvailabilityKey = "";

let haulingCalendarAvailabilityRequest = 0;


/* ======================================================
   CURRENT CALENDAR MONTH KEY
====================================================== */

function getHaulingCalendarViewKey(){

    const year =
    haulingCalendarView
    .getFullYear();


    const month =
    String(
        haulingCalendarView
        .getMonth()
        +
        1
    )
    .padStart(
        2,
        "0"
    );


    return (
        year
        +
        "-"
        +
        month
    );
}


/* ======================================================
   LOAD MONTH AVAILABILITY
====================================================== */

async function loadHaulingCalendarAvailability(){

    const year =
    haulingCalendarView
    .getFullYear();


    const month =
    haulingCalendarView
    .getMonth()
    +
    1;


    const viewKey =
    (
        year
        +
        "-"
        +
        String(
            month
        )
        .padStart(
            2,
            "0"
        )
    );


    const requestID =
    ++haulingCalendarAvailabilityRequest;


    try{

        const response =
        await fetch(
            "get_hauling_booked_dates.php"
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


        let result;


        try{

            result =
            await response.json();

        }
        catch(error){

            throw new Error(
                "Invalid calendar availability response."
            );

        }


        if(
            requestID
            !==
            haulingCalendarAvailabilityRequest
        ){

            return false;

        }


        if(
            !response.ok
            ||
            !result.success
        ){

            throw new Error(
                result.message
                ||
                "Unable to load calendar availability."
            );

        }


        haulingCalendarAvailability =
        result.data?.dates
        ||
        {};


        haulingCalendarAvailabilityKey =
        viewKey;


        renderHaulingCalendar();


        return true;

    }
    catch(error){

        if(
            requestID
            !==
            haulingCalendarAvailabilityRequest
        ){

            return false;

        }


        console.error(
            "Hauling calendar availability:",
            error
        );


        haulingCalendarAvailability =
        {};


        haulingCalendarAvailabilityKey =
        viewKey;


        /*
           Calendar remains usable even if the visual
           availability request fails.

           The normal Tractor / Driver availability check
           still validates the selected date.
        */

        renderHaulingCalendar();


        return false;

    }

}


/* ======================================================
   RENDER CALENDAR

   Date is selected FIRST.

   Availability of Tractor / Driver is loaded only after
   a Rental Date is selected.
====================================================== */

function renderHaulingCalendar(){

    if(
        !haulingCalendarMonth
        ||
        !haulingCalendarDays
    ){

        return;

    }


    const year =
    haulingCalendarView
    .getFullYear();


    const month =
    haulingCalendarView
    .getMonth();


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


    haulingCalendarMonth.textContent =

    new Date(
        year,
        month,
        1
    )
    .toLocaleDateString(
        "en-US",
        {
            month:
            "long",

            year:
            "numeric"
        }
    );


    haulingCalendarDays.innerHTML =
    "";


    /* ==========================================
       EMPTY CELLS
    ========================================== */

    for(
        let blank = 0;
        blank < firstDay;
        blank++
    ){

        const emptyCell =
        document.createElement(
            "div"
        );


        emptyCell.className =
        "hauling-calendar-empty";


        haulingCalendarDays
        .appendChild(
            emptyCell
        );

    }


    const todayKey =
    getHaulingTodayKey();


    /* ==========================================
       DAYS
    ========================================== */

    for(
        let day = 1;
        day <= daysInMonth;
        day++
    ){


        const dateKey =
        haulingBuildDateKey(
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
        "hauling-calendar-day";


        dayButton.textContent =
        day;


        dayButton.dataset.date =
        dateKey;

                /* ======================================
           DATE AVAILABILITY
        ====================================== */

        const currentViewKey =
        getHaulingCalendarViewKey();


        const dateAvailability =

        haulingCalendarAvailabilityKey
        ===
        currentViewKey

        ?

        (
            haulingCalendarAvailability[
                dateKey
            ]
            ||
            null
        )

        :

        null;


        /* ======================================
           PAST DATE

           Past dates remain selectable for
           audit / historical back-entry.
        ====================================== */

        const isPastCalendarDate =
        dateKey
        <
        todayKey;


        if(
            isPastCalendarDate
        ){

            dayButton.classList.add(
                "hauling-calendar-past"
            );


            dayButton.title =
            "Past date. Selectable for audit or historical entry.";

        }


        /* ======================================
           FULLY BOOKED

           Even historical entries must respect
           existing resource assignments.
        ====================================== */

        if(
            dateAvailability?.status
            ===
            "Fully Booked"
        ){

            dayButton.disabled =
            true;


            dayButton.classList.add(
                "hauling-calendar-booked"
            );


            dayButton.title =
            "Fully booked. No Hauling tractor and driver pair is available on this date.";

        }

        /* ======================================
           LIMITED
        ====================================== */

        else if(
            dateAvailability?.status
            ===
            "Limited"
        ){

            dayButton.classList.add(
                "hauling-calendar-limited"
            );


            dayButton.title =

            isPastCalendarDate

            ?

            "Past date with limited availability. Selectable for audit or historical entry."

            :

            "Limited availability. At least one Hauling tractor and driver pair is still available.";

        }

        /* ======================================
           AVAILABLE
        ====================================== */

        else if(
            dateAvailability?.status
            ===
            "Available"
        ){

            dayButton.title =

            isPastCalendarDate

            ?

            "Past date. Selectable for audit or historical entry."

            :

            "Available";

        }


        /* ======================================
           TODAY
        ====================================== */

        if(
            dateKey
            ===
            todayKey
        ){

            dayButton
            .classList
            .add(
                "hauling-calendar-today"
            );

        }


        /* ======================================
           SELECTED
        ====================================== */

        if(
            haulingDate?.value
            ===
            dateKey
        ){

            dayButton
            .classList
            .add(
                "hauling-calendar-selected"
            );

        }


        /* ======================================
           SELECT DATE
        ====================================== */

        dayButton
        .addEventListener(
            "click",
            async function(event){

                event.preventDefault();

                event.stopPropagation();


                const selectedDate =
                this.dataset.date;


                if(
                    !selectedDate
                ){

                    return;

                }


                if(
                    haulingDate
                ){

                    haulingDate.value =
                    selectedDate;

                }


                if(
                    haulingDateText
                ){

                    haulingDateText.textContent =
                    haulingFormatDate(
                        selectedDate
                    );

                }


                /* ==================================
                   DATE CHANGED

                   Existing assignments are invalid
                   until availability is checked for
                   the newly selected date.
                ================================== */

                resetHaulingAssignments();


                applyHaulingStatusRules();


                haulingCalendar
                ?.classList
                .remove(
                    "show"
                );


                renderHaulingCalendar();


                await loadHaulingAssignmentAvailability(
                    selectedDate
                );

            }
        );


        haulingCalendarDays
        .appendChild(
            dayButton
        );

    }

}


/* ======================================================
   CALENDAR POSITION
====================================================== */

function positionHaulingCalendar(){

    if(
        !haulingCalendar
        ||
        !haulingDateBtn
    ){

        return;

    }


    const buttonRect =
    haulingDateBtn
    .getBoundingClientRect();


    const calendarRect =
    haulingCalendar
    .getBoundingClientRect();


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


    /* ==========================================
       RIGHT OVERFLOW
    ========================================== */

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


    /* ==========================================
       LEFT OVERFLOW
    ========================================== */

    if(
        left
        <
        margin
    ){

        left =
        margin;

    }


    /* ==========================================
       OPEN ABOVE IF NEEDED
    ========================================== */

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
        top
        <
        margin
    ){

        top =
        margin;

    }


    haulingCalendar.style.left =
    Math.round(
        left
    )
    +
    "px";


    haulingCalendar.style.top =
    Math.round(
        top
    )
    +
    "px";

}


/* ======================================================
   DATE BUTTON
====================================================== */

if(
    haulingDateBtn
    &&
    haulingCalendar
){

    haulingDateBtn
    .addEventListener(
        "click",
        async function(event){

            event.preventDefault();

            event.stopPropagation();


            closeHaulingAssignmentMenus();


            const willOpen =

            !haulingCalendar
            .classList
            .contains(
                "show"
            );


            if(
                willOpen
            ){

                await loadHaulingCalendarAvailability();

            }
            else{

                renderHaulingCalendar();

            }


            haulingCalendar
            .classList
            .toggle(
                "show"
            );


            if(
                willOpen
            ){

                requestAnimationFrame(
                    function(){

                        positionHaulingCalendar();

                    }
                );

            }

        }
    );


    haulingCalendar
    .addEventListener(
        "click",
        function(event){

            event.stopPropagation();

        }
    );

}


/* ======================================================
   PREVIOUS MONTH
====================================================== */

haulingCalendarPrev
?.addEventListener(
    "click",
    async function(event){

        event.preventDefault();

        event.stopPropagation();


        haulingCalendarView
        .setMonth(

            haulingCalendarView
            .getMonth()
            -
            1

        );


        await loadHaulingCalendarAvailability();


        if(
            haulingCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            positionHaulingCalendar();

        }

    }
);


/* ======================================================
   NEXT MONTH
====================================================== */

haulingCalendarNext
?.addEventListener(
    "click",
    async function(event){

        event.preventDefault();

        event.stopPropagation();


        haulingCalendarView
        .setMonth(

            haulingCalendarView
            .getMonth()
            +
            1

        );


        await loadHaulingCalendarAvailability();


        if(
            haulingCalendar
            ?.classList
            .contains(
                "show"
            )
        ){

            positionHaulingCalendar();

        }

    }
);


/* ======================================================
   CLOSE CALENDAR / ASSIGNMENTS
====================================================== */

document
.addEventListener(
    "click",
    function(){

        haulingCalendar
        ?.classList
        .remove(
            "show"
        );


        closeHaulingAssignmentMenus();

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
            haulingCalendar
            &&
            haulingCalendar
            .classList
            .contains(
                "show"
            )
        ){

            positionHaulingCalendar();

        }

    }
);


/* ======================================================
   COMPUTE TOTAL
====================================================== */

function computeHaulingTotal(){

    const price =

    parseFloat(
        haulingPrice?.value
    )

    ||

    0;


    let discount =

    parseFloat(
        haulingDiscount?.value
    )

    ||

    0;


    if(
        haulingAffiliate?.value
        ===
        "Member"
    ){

        if(
            haulingDiscount
        ){

            haulingDiscount.disabled =
            false;


            haulingDiscount.placeholder =
            "";

        }


        if(
            discount
            <
            0
        ){

            discount =
            0;

        }


        if(
            discount
            >
            price
        ){

            discount =
            price;


            if(
                haulingDiscount
            ){

                haulingDiscount.value =
                price.toFixed(
                    2
                );

            }

        }


        let total =
        price
        -
        discount;


        if(
            total
            <
            0
        ){

            total =
            0;

        }


        if(
            haulingTotal
        ){

            haulingTotal.value =
            total.toFixed(
                2
            );

        }

    }
    else{

        if(
            haulingDiscount
        ){

            haulingDiscount.disabled =
            true;


            haulingDiscount.value =
            "";


            haulingDiscount.placeholder =
            "Members only";

        }


        if(
            haulingTotal
        ){

            haulingTotal.value =
            price.toFixed(
                2
            );

        }

    }

}


/* ======================================================
   TOTAL EVENTS
====================================================== */

if(
    haulingAffiliate
    &&
    haulingPrice
    &&
    haulingDiscount
    &&
    haulingTotal
){

    haulingAffiliate
    .addEventListener(
        "change",
        computeHaulingTotal
    );


    haulingPrice
    .addEventListener(
        "input",
        computeHaulingTotal
    );


    haulingDiscount
    .addEventListener(
        "input",
        computeHaulingTotal
    );


    computeHaulingTotal();

}


/* ======================================================
   MEMBER NAME NORMALIZER
====================================================== */

function normalizeMemberName(
    name
){

    return String(
        name
        ||
        ""
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

async function findExactMember(
    memberName
){

    const cleanName =
    String(
        memberName
        ||
        ""
    )
    .trim();


    if(
        cleanName.length
        <
        3
    ){

        return null;

    }


    const response =
    await fetch(

        "search_member.php?keyword="
        +
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


    const memberItems =
    container
    .querySelectorAll(
        ".member-item"
    );


    const typedName =
    normalizeMemberName(
        cleanName
    );


    let exactMember =
    null;


    memberItems
    .forEach(
    function(item){

        const id =
        item.dataset.id;


        const name =
        item.dataset.name;


        if(
            !id
            ||
            !name
        ){

            return;

        }


        if(
            normalizeMemberName(
                name
            )
            ===
            typedName
        ){

            exactMember = {

                id:
                id,

                name:
                name

            };

        }

    });


    return exactMember;

}


/* ======================================================
   FIND RESOURCE BY ID
====================================================== */

function findHaulingTractorByID(
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


function findHaulingDriverByID(
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
   FINAL ASSIGNMENT RECHECK

   Availability is checked again immediately before save
   because another user may have created a Rental after
   this modal originally loaded.
====================================================== */

async function validateHaulingAssignmentsBeforeSave(){

    const rentalDate =
    haulingDate?.value
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
    haulingTractorID?.value
    ||
    "";


    const primaryDriverID =
    haulingPrimaryDriverID?.value
    ||
    "";


    const secondaryTractorID =
    haulingSecondaryTractorID?.value
    ||
    "";


    const secondaryDriverID =
    haulingSecondaryDriverID?.value
    ||
    "";


    const [
        tractorData,
        driverData
    ] =

    await Promise.all([

        getHaulingTractorAvailability(
            rentalDate
        ),

        getHaulingDriverAvailability(
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

    findHaulingTractorByID(
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

        await loadHaulingAssignmentAvailability(
            rentalDate
        );


        Swal.fire({

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

    findHaulingDriverByID(
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

        await loadHaulingAssignmentAvailability(
            rentalDate
        );


        Swal.fire({

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

        findHaulingTractorByID(
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

            await loadHaulingAssignmentAvailability(
                rentalDate
            );


            Swal.fire({

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

        findHaulingDriverByID(
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

            await loadHaulingAssignmentAvailability(
                rentalDate
            );


            Swal.fire({

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
   ADD HAULING - INLINE FIELD VALIDATION
====================================================== */

function getHaulingErrorContainer(
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


function clearHaulingInlineError(
    control
){

    if(
        !control
    ){

        return;

    }


    control.classList.remove(
        "is-invalid"
    );


    control.removeAttribute(
        "aria-invalid"
    );


    const container =
    getHaulingErrorContainer(
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
        ".hauling-field-error"
    )
    .forEach(
    errorElement => {


        if(
            !controlID
            ||
            errorElement.dataset.for
            ===
            controlID
        ){

            errorElement.remove();

        }


    });

}


function setHaulingInlineError(
    control,
    message
){

    if(
        !control
    ){

        return;

    }


    clearHaulingInlineError(
        control
    );


    control.classList.add(
        "is-invalid"
    );


    control.setAttribute(
        "aria-invalid",
        "true"
    );


    const container =
    getHaulingErrorContainer(
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
    "hauling-field-error";


    error.dataset.for =
    control.id
    ||
    "";


    error.textContent =
    message;


    container.appendChild(
        error
    );

}


function clearHaulingInlineErrors(){

    document
    .querySelectorAll(
        "#addhaulingModal .is-invalid"
    )
    .forEach(
    control => {

        control.classList.remove(
            "is-invalid"
        );


        control.removeAttribute(
            "aria-invalid"
        );

    });


    document
    .querySelectorAll(
        "#addhaulingModal .hauling-field-error"
    )
    .forEach(
    error => {

        error.remove();

    });

}


function focusHaulingInvalidField(
    control
){

    if(
        !control
    ){

        return;

    }


    control.scrollIntoView({

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
   REMOVE ERROR WHILE USER CORRECTS INPUT
====================================================== */

[
    "haulingCustomerName",
    "haulingCount",
    "haulingLocation",
    "haulingPrice",
    "haulingDiscount"
]
.forEach(
function(id){

    const field =
    document.getElementById(
        id
    );


    field
    ?.addEventListener(
        "input",
        function(){

            clearHaulingInlineError(
                field
            );

        }
    );

});


[
    "affiliateBtn",
    "haulingDateBtn",
    "haulingTractorBtn",
    "haulingPrimaryDriverBtn",
    "haulingSecondaryTractorBtn",
    "haulingSecondaryDriverBtn",
    "statusBtn"
]
.forEach(
function(id){

    const button =
    document.getElementById(
        id
    );


    button
    ?.addEventListener(
        "click",
        function(){

            clearHaulingInlineError(
                button
            );

        }
    );

});


/* ======================================================
   PREVENT NORMAL FORM SUBMIT

   ADD RENTAL button handles the save through fetch.
====================================================== */

haulingForm
?.addEventListener(
    "submit",
    function(event){

        event.preventDefault();

    }
);


/* ======================================================
   SAVE HAULING
====================================================== */

if (
    haulingForm &&
    saveHaulingBtn
) {

    saveHaulingBtn.addEventListener(
        "click",
        async function () {

            const customer =
                document.getElementById(
                    "haulingCustomerName"
                );

            const haulingCount =
                document.getElementById(
                    "haulingCount"
                );

            const affiliate =
                document.getElementById(
                    "haulingAffiliate"
                );

            const affiliateButton =
                document.getElementById(
                    "affiliateBtn"
                );

            const haulingLocation =
                document.getElementById(
                    "haulingLocation"
                );

            const date =
                document.getElementById(
                    "haulingDate"
                );

            const price =
                document.getElementById(
                    "haulingPrice"
                );

            const status =
                document.getElementById(
                    "haulingStatus"
                );


            /* ==========================================
               CLEAR OLD INLINE ERRORS
            ========================================== */

            clearHaulingInlineErrors();


            let firstInvalidControl = null;
            let hasValidationError = false;


            function markInvalid(
                control,
                message
            ) {

                if (!control) {
                    return;
                }

                setHaulingInlineError(
                    control,
                    message
                );

                if (!firstInvalidControl) {
                    firstInvalidControl = control;
                }

                hasValidationError = true;
            }


            /* ==========================================
               AFFILIATE
            ========================================== */

            if (
                !affiliate ||
                affiliate.value === ""
            ) {

                markInvalid(
                    affiliateButton,
                    "Please select an affiliate."
                );

            }


            /* ==========================================
               CUSTOMER NAME

               Do not show this yet if Affiliate itself
               has not been selected because Customer
               Name is intentionally disabled.
            ========================================== */

            if (
                affiliate &&
                affiliate.value !== "" &&
                (
                    !customer ||
                    customer.value.trim() === ""
                )
            ) {

                markInvalid(
                    customer,
                    "Customer name is required."
                );

            }


            /* ==========================================
               NO. OF HAULING
            ========================================== */

            const haulingCountValue =
                haulingCount
                    ? Number(haulingCount.value)
                    : NaN;


            if (
                !haulingCount ||
                haulingCount.value === "" ||
                !Number.isFinite(
                    haulingCountValue
                ) ||
                !Number.isInteger(
                    haulingCountValue
                ) ||
                haulingCountValue <= 0
            ) {

                markInvalid(
                    haulingCount,
                    "Enter a whole number greater than zero."
                );

            }


            /* ==========================================
               SERVICE LOCATION
            ========================================== */

            if (
                !haulingLocation ||
                haulingLocation.value.trim() === ""
            ) {

                markInvalid(
                    haulingLocation,
                    "Service location is required."
                );

            }


            /* ==========================================
               RENTAL DATE
            ========================================== */

            if (
                !date ||
                date.value === ""
            ) {

                markInvalid(
                    haulingDateBtn,
                    "Please select a Rental date."
                );

            }


            /* ==========================================
               PRIMARY TRACTOR / DRIVER

               They depend on Rental Date, so only show
               these errors after a date was selected.
            ========================================== */

            if (
                date &&
                date.value !== ""
            ) {

                if (
                    !haulingTractorID ||
                    haulingTractorID.value === ""
                ) {

                    markInvalid(
                        haulingTractorBtn,
                        "Please select a Primary Tractor."
                    );

                }


                if (
                    !haulingPrimaryDriverID ||
                    haulingPrimaryDriverID.value === ""
                ) {

                    markInvalid(
                        haulingPrimaryDriverBtn,
                        "Please select a Primary Driver."
                    );

                }

            }


            /* ==========================================
               SECONDARY ASSIGNMENT
            ========================================== */

            const secondaryTractorID =
                haulingSecondaryTractorID?.value || "";

            const secondaryDriverID =
                haulingSecondaryDriverID?.value || "";


            const hasSecondaryTractor =
                secondaryTractorID !== "";

            const hasSecondaryDriver =
                secondaryDriverID !== "";


            /* ==========================================
               SECONDARY PAIR
            ========================================== */

            if (
                hasSecondaryTractor !==
                hasSecondaryDriver
            ) {

                markInvalid(
                    haulingSecondaryTractorBtn,
                    "Secondary Tractor and Secondary Driver must be selected together."
                );

                markInvalid(
                    haulingSecondaryDriverBtn,
                    "Secondary Tractor and Secondary Driver must be selected together."
                );

            }


            /* ==========================================
               DUPLICATE TRACTOR
            ========================================== */

            if (
                hasSecondaryTractor &&
                haulingTractorID &&
                String(
                    secondaryTractorID
                ) ===
                String(
                    haulingTractorID.value
                )
            ) {

                markInvalid(
                    haulingSecondaryTractorBtn,
                    "Secondary Tractor must be different from the Primary Tractor."
                );

            }


            /* ==========================================
               DUPLICATE DRIVER
            ========================================== */

            if (
                hasSecondaryDriver &&
                haulingPrimaryDriverID &&
                String(
                    secondaryDriverID
                ) ===
                String(
                    haulingPrimaryDriverID.value
                )
            ) {

                markInvalid(
                    haulingSecondaryDriverBtn,
                    "Secondary Driver must be different from the Primary Driver."
                );

            }


            /* ==========================================
               STATUS
            ========================================== */

            applyHaulingStatusRules();


            if (
                date &&
                date.value !== "" &&
                (
                    !status ||
                    status.value === ""
                )
            ) {

                markInvalid(
                    statusBtn,
                    "Please select the Rental status."
                );

            }


            /* ==========================================
               RENTAL PRICE
            ========================================== */

            const rentalPriceValue =
                price
                    ? Number(price.value)
                    : NaN;


            if (
                !price ||
                price.value === "" ||
                !Number.isFinite(
                    rentalPriceValue
                ) ||
                rentalPriceValue < 0
            ) {

                markInvalid(
                    price,
                    "Enter a valid Rental price."
                );

            }


            /* ==========================================
               MEMBER DISCOUNT
            ========================================== */

            if (
                haulingDiscount &&
                !haulingDiscount.disabled &&
                haulingDiscount.value !== ""
            ) {

                const discountValue =
                    Number(
                        haulingDiscount.value
                    );


                if (
                    !Number.isFinite(
                        discountValue
                    ) ||
                    discountValue < 0
                ) {

                    markInvalid(
                        haulingDiscount,
                        "Enter a valid discount amount."
                    );

                }

            }


            /* ==========================================
               STOP IF BASIC VALIDATION FAILED

               All invalid fields are already marked.
               Only focus the FIRST one.
            ========================================== */

            if (
                hasValidationError
            ) {

                focusHaulingInvalidField(
                    firstInvalidControl
                );

                return;
            }


            /* ==========================================
               MEMBER VALIDATION
            ========================================== */

            if (
                affiliate.value === "Member"
            ) {

                try {

                    const exactMember =
                        await findExactMember(
                            customer.value
                        );


                    if (
                        !exactMember
                    ) {

                        if (
                            memberID
                        ) {

                            memberID.value = "";

                        }


                        setHaulingInlineError(
                            customer,
                            "Select a valid registered Member from the suggestions."
                        );


                        focusHaulingInvalidField(
                            customer
                        );


                        return;
                    }


                    memberID.value =
                        exactMember.id;


                    customer.value =
                        exactMember.name;


                    clearHaulingInlineError(
                        customer
                    );

                }
                catch (error) {

                    console.error(
                        "MEMBER VALIDATION ERROR:",
                        error
                    );


                    Swal.fire({

                        icon: "error",

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
            else {

                if (
                    memberID
                ) {

                    memberID.value = "";

                }

            }


            /* ==========================================
               RECHECK TRACTOR + DRIVER AVAILABILITY

               Keep SweetAlert here because this is not
               a normal empty-field validation.
            ========================================== */

            try {

                const assignmentsValid =
                    await validateHaulingAssignmentsBeforeSave();


                if (
                    !assignmentsValid
                ) {

                    return;
                }

            }
            catch (error) {

                console.error(
                    "HAULING FINAL AVAILABILITY ERROR:",
                    error
                );


                Swal.fire({

                    icon: "error",

                    title:
                        "Availability Check Failed",

                    text:
                        error.message ||
                        "Unable to verify Rental availability.",

                    confirmButtonColor:
                        "#B71C1C"

                });


                return;
            }


            /* ==========================================
               FINAL CONFIRMATION
            ========================================== */

            const confirmation =
                await Swal.fire({

                    icon:
                        "question",

                    title:
                        "Add Rental?",

                    text:
                        "Are you sure you want to add this Hauling rental?",

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


            if (
                !confirmation.isConfirmed
            ) {

                return;
            }


            /* ==========================================
               DISABLE SAVE BUTTON
            ========================================== */

            saveHaulingBtn.disabled =
                true;


            const originalButtonText =
                saveHaulingBtn.textContent;


            saveHaulingBtn.textContent =
                "SAVING...";


            try {

                const formData =
                    new FormData(
                        haulingForm
                    );


                const response =
                    await fetch(

                        "save/save_hauling.php",

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
                    "SAVE HAULING:",
                    data
                );


                /* ======================================
                   SUCCESS
                ====================================== */

                if (
                    response.ok &&
                    data.trim() === "success"
                ) {

                    const modalElement =
                        document.getElementById(
                            "addhaulingModal"
                        );


                    if (
                        modalElement
                    ) {

                        const haulingModal =
                            bootstrap.Modal
                                .getInstance(
                                    modalElement
                                );


                        if (
                            haulingModal
                        ) {

                            haulingModal.hide();

                        }

                    }


                    await Swal.fire({

                        icon:
                            "success",

                        title:
                            "Hauling Rental Added",

                        text:
                            "The Hauling rental was added successfully.",

                        showConfirmButton:
                            false,

                        timer:
                            1500

                    });


                    window.location.reload();


                    return;
                }


                /* ======================================
                   BACKEND VALIDATION / SAVE ERROR
                ====================================== */

                Swal.fire({

                    icon:
                        "error",

                    title:
                        "Save Failed",

                    text:
                        data.trim() ||
                        "Unable to save the Hauling rental.",

                    confirmButtonColor:
                        "#B71C1C"

                });

            }
            catch (error) {

                console.error(
                    "SAVE HAULING ERROR:",
                    error
                );


                Swal.fire({

                    icon:
                        "error",

                    title:
                        "Save Failed",

                    text:
                        "Unable to save the Hauling rental. Please try again.",

                    confirmButtonColor:
                        "#B71C1C"

                });

            }
            finally {

                saveHaulingBtn.disabled =
                    false;


                saveHaulingBtn.textContent =
                    originalButtonText;

            }

        }
    );

}

/* ======================================================
   SECONDARY ASSIGNMENT TOGGLE
====================================================== */

const haulingSecondaryToggle =
document.getElementById(
    "haulingSecondaryToggle"
);


const haulingSecondaryAssignment =
document.getElementById(
    "haulingSecondaryAssignment"
);


/* ======================================================
   RESET SECONDARY SECTION UI
====================================================== */

function resetHaulingSecondarySection(){

    if(
        haulingSecondaryAssignment
    ){

        haulingSecondaryAssignment
        .classList
        .remove(
            "is-open"
        );

    }


    if(
        haulingSecondaryToggle
    ){

        haulingSecondaryToggle
        .classList
        .remove(
            "is-open"
        );


        const toggleText =
        haulingSecondaryToggle
        .querySelector(
            "span:last-child"
        );


        if(
            toggleText
        ){

            toggleText.textContent =
            "ADD SECONDARY ASSIGNMENT";

        }

    }


    resetHaulingSecondaryTractor();

    resetHaulingSecondaryDriver();

}


/* ======================================================
   TOGGLE SECONDARY SECTION
====================================================== */

if(
    haulingSecondaryToggle
    &&
    haulingSecondaryAssignment
){

    haulingSecondaryToggle
    .addEventListener(
        "click",
        function(event){

            event.preventDefault();


            const isOpen =

            haulingSecondaryAssignment
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

                (
                    haulingSecondaryTractorID?.value
                    ||
                    ""
                )
                !==
                ""

                ||

                (
                    haulingSecondaryDriverID?.value
                    ||
                    ""
                )
                !==
                "";


                /*
                   Ask first before removing an already
                   selected Secondary Assignment.
                */

                if(
                    hasSelectedSecondary
                ){

                    Swal.fire({

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

                    })
                    .then(
                    function(result){

                        if(
                            !result.isConfirmed
                        ){

                            return;

                        }


                        resetHaulingSecondarySection();

                    });


                    return;

                }


                resetHaulingSecondarySection();


                return;

            }


            /* ==========================================
               OPEN
            ========================================== */

            haulingSecondaryAssignment
            .classList
            .add(
                "is-open"
            );


            haulingSecondaryToggle
            .classList
            .add(
                "is-open"
            );


            const toggleText =
            haulingSecondaryToggle
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
   INITIAL ADD MODAL STATE
====================================================== */

resetHaulingAssignments();

resetHaulingSecondarySection();

disableHaulingAssignments();

resetHaulingAddStatus();

renderHaulingCalendar();


/* ======================================================
   RESET ADD MODAL AFTER CLOSE
====================================================== */

const addHaulingModalElement =
document.getElementById(
    "addhaulingModal"
);


addHaulingModalElement
?.addEventListener(
    "hidden.bs.modal",
    function(){


        haulingAvailabilityRequest++;


        haulingForm
        ?.reset();


        clearHaulingInlineErrors();


        haulingTractors =
        [];


        haulingDrivers =
        [];


        if(
            haulingDate
        ){

            haulingDate.value =
            "";

        }


        if(
            haulingDateText
        ){

            haulingDateText.textContent =
            "Select Rental Date";

        }


        const affiliateTextElement =
        document.getElementById(
            "affiliateText"
        );


        if(
            affiliateTextElement
        ){

            affiliateTextElement.textContent =
            "Select Affiliate";

        }


        if(
            haulingAffiliate
        ){

            haulingAffiliate.value =
            "";

        }


        if(
            customerInput
        ){

            customerInput.value =
            "";


            customerInput.disabled =
            true;


            customerInput.placeholder =
            "Select affiliate first";

        }


        if(
            memberID
        ){

            memberID.value =
            "";

        }


        if(
            suggestionBox
        ){

            suggestionBox.innerHTML =
            "";


            suggestionBox.style.display =
            "none";

        }


        resetHaulingAssignments();

        resetHaulingSecondarySection();

        disableHaulingAssignments();

        resetHaulingAddStatus();


        if(
            haulingTotal
        ){

            haulingTotal.value =
            "0.00";

        }


        haulingCalendarView =
        new Date();


        haulingCalendarView
        .setDate(
            1
        );


        haulingCalendar
        ?.classList
        .remove(
            "show"
        );


        closeHaulingAssignmentMenus();


        renderHaulingCalendar();

    }
);


/* ======================================================
   DROPDOWN COLORS
====================================================== */

function updateStatusColor(
    dropdown
){

    dropdown.classList.remove(

        "status-pending-select",

        "status-ongoing-select"

    );


    if(
        dropdown.value
        ===
        "Pending"
    ){

        dropdown.classList.add(
            "status-pending-select"
        );

    }

    else if(
        dropdown.value
        ===
        "Ongoing"
    ){

        dropdown.classList.add(
            "status-ongoing-select"
        );

    }

}


function updateRemarksColor(
    dropdown
){

    dropdown.classList.remove(
        "remarks-unpaid-select"
    );


    if(
        dropdown.value
        ===
        "Unpaid"
    ){

        dropdown.classList.add(
            "remarks-unpaid-select"
        );

    }

}


/* ======================================================
   UPDATE HAULING STATUS - LEDGER
====================================================== */


/* ======================================================
   APPLY LEDGER STATUS RULES

   NORMAL:
   FUTURE
   - Pending only

   TODAY
   - Pending
   - Ongoing
   - Done

   PAST
   - Pending
   - Done
   - Ongoing not allowed

   MAINTENANCE:
   - Pending only
   - Ongoing disabled
   - Done disabled

   Maintenance has the highest priority.
====================================================== */

function applyHaulingLedgerStatusRules(
    select
){

    if(!select){

        return;

    }


    const haulingRow =
    select.closest(
        ".hauling-row"
    );


    const serviceDate =
    haulingRow?.dataset.date
    ||
    "";


    if(serviceDate === ""){

        return;

    }


    const today =
    getHaulingTodayKey();


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


    const tractorIsMaintenance =
    haulingRow
    ?.classList
    .contains(
        "hauling-maintenance-alert-row"
    )
    ===
    true;


    /* ==================================================
       RESET OPTION STATE

       PHP may already disable Maintenance options,
       but JS also controls them so the rules remain
       correct after page initialization.
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
       DONE = FINAL

       This is mainly a safety rule.

       Current hauling.php normally displays Done as
       a final badge instead of a dropdown.
    ================================================== */

    if(
        select.value
        ===
        "Done"
        &&
        !tractorIsMaintenance
        &&
        serviceDate <= today
    ){

        select.disabled =
        true;


        select.title =
        "This rental is completed. Done status is final.";


        return;

    }


    /* ==================================================
       MAINTENANCE = HIGHEST PRIORITY

       Keep the dropdown clickable so the user can see
       why Ongoing and Done are unavailable.

       Pending remains selectable.

       Ongoing and Done stay visible but disabled so our
       red Maintenance styling can also be seen.
    ================================================== */

    if(
        tractorIsMaintenance
    ){

        select.disabled =
        false;


        select.title =
        "This rental must remain Pending while the tractor is under maintenance.";


        if(
            ongoingOption
        ){

            ongoingOption.disabled =
            true;

        }


        if(
            doneOption
        ){

            doneOption.disabled =
            true;

        }


        return;

    }


    /* ==================================================
       FUTURE = PENDING ONLY
    ================================================== */

    if(
        serviceDate > today
    ){


        /* ==============================================
           VALID FUTURE PENDING

           Lock the dropdown because there is nothing
           else the user may select.
        ============================================== */

        if(
            select.value
            ===
            "Pending"
        ){

            select.disabled =
            true;


            select.title =
            "Future rental status remains Pending until its service date.";

        }


        /* ==============================================
           LEGACY / INVALID FUTURE STATUS

           Keep the dropdown usable so an old invalid
           record may still be corrected back to Pending.
        ============================================== */

        else{

            select.disabled =
            false;


            select.title =
            "A future rental must remain Pending until its service date.";


            if(
                ongoingOption
                &&
                select.value
                !==
                "Ongoing"
            ){

                ongoingOption.disabled =
                true;

            }


            if(
                doneOption
                &&
                select.value
                !==
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

       No additional restriction.
    ================================================== */

    if(
        serviceDate === today
    ){

        select.disabled =
        false;


        return;

    }


    /* ==================================================
       PAST

       Pending / Done allowed.
       Ongoing not allowed.
    ================================================== */

    if(
        serviceDate < today
    ){

        select.disabled =
        false;


        if(
            ongoingOption
        ){

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

const haulingFutureStatusLocks =
document.querySelectorAll(
    ".hauling-future-status-lock"
);


haulingFutureStatusLocks
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

            haulingFormatDate(
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
   GET LEDGER STATUS DROPDOWNS
====================================================== */

const haulingStatusDropdowns =
document.querySelectorAll(
    ".status-dropdown"
);


/* ======================================================
   INITIALIZE + STATUS UPDATE
====================================================== */

haulingStatusDropdowns
.forEach(
function(dropdown){


    /* ==================================================
       SAVE ORIGINAL VALUE
    ================================================== */

    dropdown.dataset.oldValue =
    dropdown.value;


    /* ==================================================
       INITIAL COLOR
    ================================================== */

    updateStatusColor(
        dropdown
    );


    /* ==================================================
       INITIAL STATUS RULES
    ================================================== */

    applyHaulingLedgerStatusRules(
        dropdown
    );


    /* ==================================================
       CHANGE STATUS
    ================================================== */

    dropdown
    .addEventListener(

        "change",

        function(){


            const id =
            this.dataset.id;


            const newStatus =
            this.value;


            const oldStatus =
            this.dataset.oldValue;


            const row =
            this.closest(
                ".hauling-row"
            );


            const rowServiceDate =
            row?.dataset.date
            ||
            "";


            const currentToday =
            getHaulingTodayKey();


            const tractorIsMaintenance =
            row
            ?.classList
            .contains(
                "hauling-maintenance-alert-row"
            )
            ===
            true;


            /* ==========================================
               MAINTENANCE HARD UI RULE

               Normally impossible to trigger because
               Ongoing / Done are disabled.

               This remains as an extra UI safety layer.
            ========================================== */

            if(
                tractorIsMaintenance
                &&
                newStatus
                !==
                "Pending"
            ){

                this.value =
                oldStatus;


                updateStatusColor(
                    this
                );


                applyHaulingLedgerStatusRules(
                    this
                );


                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Tractor Maintenance",

                    text:
                    "This Hauling rental must remain Pending while its tractor is under maintenance.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               FUTURE = PENDING ONLY
            ========================================== */

            if(
                rowServiceDate
                &&
                rowServiceDate
                >
                currentToday
                &&
                newStatus
                !==
                "Pending"
            ){

                this.value =
                oldStatus;


                updateStatusColor(
                    this
                );


                applyHaulingLedgerStatusRules(
                    this
                );


                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Future Service",

                    text:
                    "A future rental must remain Pending until its service date.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               PAST = NO ONGOING
            ========================================== */

            if(
                rowServiceDate
                &&
                rowServiceDate
                <
                currentToday
                &&
                newStatus
                ===
                "Ongoing"
            ){

                this.value =
                oldStatus;


                updateStatusColor(
                    this
                );


                applyHaulingLedgerStatusRules(
                    this
                );


                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Invalid Status",

                    text:
                    "A past rental cannot be changed to Ongoing.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               NO CHANGE
            ========================================== */

            if(
                newStatus
                ===
                oldStatus
            ){

                applyHaulingLedgerStatusRules(
                    this
                );


                return;

            }


            /* ==========================================
               CONFIRM STATUS CHANGE
            ========================================== */

            Swal.fire({

                icon:
                "question",

                title:
                "Change Status?",

                text:
                "Are you sure you want to change the status?",

                showCancelButton:
                true,

                confirmButtonText:
                "Yes",

                cancelButtonText:
                "Cancel",

                confirmButtonColor:
                "#8CA15B",

                cancelButtonColor:
                "#B71C1C",

                reverseButtons:
                true

            })

            .then(
            function(result){


                /* ======================================
                   CONFIRMED
                ====================================== */

                if(
                    result.isConfirmed
                ){


                    fetch(

                        "update/update_hauling_status.php",

                        {

                            method:
                            "POST",

                            headers:{

                                "Content-Type":
                                "application/x-www-form-urlencoded"

                            },

                            body:
                            "id="
                            +
                            encodeURIComponent(
                                id
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

                    .then(
                    function(response){


                        response =
                        response.trim();


                        console.log(
                            "Hauling Status:",
                            response
                        );


                        /* ==================================
                           SUCCESS
                        ================================== */

                        if(
                            response
                            ===
                            "success"
                        ){

                            Swal.fire({

                                icon:
                                "success",

                                title:
                                "Status updated successfully.",

                                showConfirmButton:
                                false,

                                timer:
                                1500

                            })

                            .then(
                            function(){

                                saveHaulingLedgerPosition();

                                window.location.reload();

                            });

                        }


                        /* ==================================
                           BACKEND REJECTED
                        ================================== */

                        else{

                            dropdown.value =
                            oldStatus;


                            updateStatusColor(
                                dropdown
                            );


                            applyHaulingLedgerStatusRules(
                                dropdown
                            );


                            Swal.fire({

                                icon:
                                "error",

                                title:
                                "Update Failed",

                                text:
                                response,

                                confirmButtonColor:
                                "#B71C1C"

                            });

                        }

                    })

                    .catch(
                    function(error){


                        console.error(
                            "HAULING STATUS ERROR:",
                            error
                        );


                        dropdown.value =
                        oldStatus;


                        updateStatusColor(
                            dropdown
                        );


                        applyHaulingLedgerStatusRules(
                            dropdown
                        );


                        Swal.fire({

                            icon:
                            "error",

                            title:
                            "Update Failed",

                            text:
                            "Unable to update the hauling status.",

                            confirmButtonColor:
                            "#B71C1C"

                        });

                    });

                }


                /* ======================================
                   CANCELLED
                ====================================== */

                else{

                    dropdown.value =
                    oldStatus;


                    updateStatusColor(
                        dropdown
                    );


                    applyHaulingLedgerStatusRules(
                        dropdown
                    );

                }

            });

        }

    );

});


/* ======================================================
   UPDATE HAULING REMARKS
====================================================== */

const haulingRemarksDropdowns =
document.querySelectorAll(
    ".remarks-dropdown"
);


haulingRemarksDropdowns
.forEach(
function(dropdown){


    dropdown.dataset.oldValue =
    dropdown.value;


    updateRemarksColor(
        dropdown
    );


    dropdown
    .addEventListener(

        "change",

        function(){


            const id =
            this.dataset.id;


            const newRemarks =
            this.value;


            const oldRemarks =
            this.dataset.oldValue;


            Swal.fire({

                icon:"question",

                title:
                "Update Payment?",

                text:
                "Are you sure you want to update the payment status?",

                showCancelButton:
                true,

                confirmButtonText:
                "Yes",

                cancelButtonText:
                "Cancel",

                confirmButtonColor:
                "#8CA15B",

                cancelButtonColor:
                "#B71C1C",

                reverseButtons:
                true

            })

            .then(
            function(result){


                if(
                    result.isConfirmed
                ){

                    fetch(

                        "update/update_hauling_remarks.php",

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
                                id
                            )
                            +
                            "&remarks="
                            +
                            encodeURIComponent(
                                newRemarks
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

                    .then(
                    function(response){


                        response =
                        response.trim();


                        console.log(
                            "Hauling Remarks:",
                            response
                        );


                        if(
                            response
                            ===
                            "success"
                        ){

                            Swal.fire({

                                icon:"success",

                                title:
                                "Payment status updated successfully.",

                                showConfirmButton:
                                false,

                                timer:
                                1500

                            })

                            .then(
                            function(){

                                saveHaulingLedgerPosition();

                                window.location.reload();

                            });

                        }

                        else{

                            dropdown.value =
                            oldRemarks;


                            updateRemarksColor(
                                dropdown
                            );


                            Swal.fire({

                                icon:"error",

                                title:
                                "Update Failed",

                                text:
                                response,

                                confirmButtonColor:
                                "#B71C1C"

                            });

                        }

                    })

                    .catch(
                    function(error){


                        console.error(
                            "HAULING REMARKS ERROR:",
                            error
                        );


                        dropdown.value =
                        oldRemarks;


                        updateRemarksColor(
                            dropdown
                        );


                        Swal.fire({

                            icon:"error",

                            title:
                            "Update Failed",

                            text:
                            "Unable to update the payment status.",

                            confirmButtonColor:
                            "#B71C1C"

                        });

                    });

                }

                else{

                    dropdown.value =
                    oldRemarks;


                    updateRemarksColor(
                        dropdown
                    );

                }

            });

        }

    );

});


/* ======================================================
   FILTER
====================================================== */

const searchInput =
document.getElementById(
    "searchHauling"
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
    "startDateFilter"
);


const endDateFilter =
document.getElementById(
    "endDateFilter"
);


const applyDateFilter =
document.getElementById(
    "applyDateFilter"
);


const clearDateFilter =
document.getElementById(
    "clearDateFilter"
);

/* ======================================================
   LEDGER DATE RESTRICTIONS
====================================================== */

function updateHaulingFilterDateRestrictions(){

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
        updateHaulingFilterDateRestrictions
    );
}

if(endDateFilter){
    endDateFilter.addEventListener(
        "change",
        updateHaulingFilterDateRestrictions
    );
}

updateHaulingFilterDateRestrictions();


const haulingRows =
document.querySelectorAll(
    ".hauling-row"
);

const haulingFilterEmpty =
document.getElementById(
    "haulingFilterEmpty"
);


function filterHauling() {

    const search = searchInput
        ? searchInput.value.toLowerCase()
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

    const start = startDateFilter
        ? startDateFilter.value
        : "";

    const end = endDateFilter
        ? endDateFilter.value
        : "";


    let visibleCount = 0;


    haulingRows.forEach(function(row) {

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


        let visible = true;


        if (
            search &&
            !rowSearch.includes(search)
        ) {
            visible = false;
        }


        if (
            affiliate !== "all" &&
            rowAffiliate !== affiliate
        ) {
            visible = false;
        }


        if (
            status === "Maintenance"
        ) {

            if (!rowIsMaintenance) {
                visible = false;
            }

        } else if (
            status !== "all" &&
            rowStatus !== status
        ) {
            visible = false;
        }


        if (
            remarks !== "all" &&
            rowRemarks !== remarks
        ) {
            visible = false;
        }


        if (
            start &&
            rowDate < start
        ) {
            visible = false;
        }


        if (
            end &&
            rowDate > end
        ) {
            visible = false;
        }


        row.style.display = visible
            ? ""
            : "none";


        if(
            visible
        ){

            visibleCount++;

        }

        });


    /* ==========================================
       FILTER EMPTY STATE
    ========================================== */

    if(
        haulingFilterEmpty
    ){

        haulingFilterEmpty.style.display =
        (
            haulingRows.length > 0
            &&
            visibleCount === 0
        )
        ?
        "flex"
        :
        "none";

    }

}


if(searchInput){

    searchInput
    .addEventListener(
        "input",
        filterHauling
    );

}


if(affiliateFilter){

    affiliateFilter
    .addEventListener(
        "change",
        filterHauling
    );

}


if(statusFilter){

    statusFilter
    .addEventListener(
        "change",
        filterHauling
    );

}


if(remarksFilter){

    remarksFilter
    .addEventListener(
        "change",
        filterHauling
    );

}


if(applyDateFilter){

    applyDateFilter
    .addEventListener(
        "click",
        function(event){

            event.preventDefault();


            const start =
            startDateFilter
            ?
            String(
                startDateFilter.value
                ||
                ""
            ).trim()
            :
            "";


            const end =
            endDateFilter
            ?
            String(
                endDateFilter.value
                ||
                ""
            ).trim()
            :
            "";


            /* ==========================================
               DATE RANGE VALIDATION

               One-sided filtering is allowed:
               - Start only
               - End only

               But when both dates exist,
               Start cannot be later than End.
            ========================================== */

            if(
                start !== ""
                &&
                end !== ""
                &&
                start > end
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Invalid Date Range",

                    text:
                    "The Start Date cannot be later than the End Date.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }

            /* ==========================================
               APPLY FILTER
            ========================================== */

            filterHauling();


            /* ==========================================
               CLOSE MODAL ONLY AFTER VALIDATION PASSES
            ========================================== */

            const dateFilterModal =
            document.getElementById(
                "dateFilterModal"
            );


            if(
                dateFilterModal
                &&
                typeof bootstrap
                !==
                "undefined"
            ){

                const modalInstance =
                bootstrap.Modal
                .getOrCreateInstance(
                    dateFilterModal
                );


                modalInstance.hide();

            }

        }
    );

}


if(clearDateFilter){

    clearDateFilter
    .addEventListener(

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

            updateHaulingFilterDateRestrictions();

            filterHauling();

        }

    );

}


/* ======================================================
   AFFILIATE DROPDOWN
====================================================== */

const affiliateBtn =
document.getElementById(
    "affiliateBtn"
);


const affiliateMenu =
document.getElementById(
    "affiliateMenu"
);


const affiliateText =
document.getElementById(
    "affiliateText"
);


const affiliateHidden =
document.getElementById(
    "haulingAffiliate"
);


if(
    affiliateBtn
    &&
    affiliateMenu
){

    affiliateBtn
    .addEventListener(

        "click",

        function(e){

            e.preventDefault();

            e.stopPropagation();


            affiliateMenu
            .classList
            .toggle(
                "show"
            );

        }

    );

}


/* ======================================================
   SELECT AFFILIATE
====================================================== */

document
.querySelectorAll(
    "#affiliateMenu .affiliate-item"
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


            if(affiliateText){

                affiliateText.textContent =
                this.textContent.trim();

            }


            if(affiliateHidden){

                affiliateHidden.value =
                value;


                affiliateHidden
                .dispatchEvent(
                    new Event(
                        "change"
                    )
                );

            }


            if(affiliateMenu){

                affiliateMenu
                .classList
                .remove(
                    "show"
                );

            }


            /* ==========================================
               RESET NAME
            ========================================== */

            if(customerInput){

                customerInput.value =
                "";


                customerInput.disabled =
                false;


                if(
                    value
                    ===
                    "Member"
                ){

                    customerInput.placeholder =
                    "Search member...";

                }

                else{

                    customerInput.placeholder =
                    "Enter customer name";

                }

            }


            if(memberID){

                memberID.value =
                "";

            }


            if(suggestionBox){

                suggestionBox.innerHTML =
                "";


                suggestionBox.style.display =
                "none";

            }

        }

    );

});


/* ======================================================
   CLOSE AFFILIATE DROPDOWN
====================================================== */

document.addEventListener(

    "click",

    function(){

        if(affiliateMenu){

            affiliateMenu
            .classList
            .remove(
                "show"
            );

        }

    }

);


/* ======================================================
   OPEN ADD STATUS MENU
====================================================== */

if(
    statusBtn
    &&
    statusMenu
){

    statusBtn
    .addEventListener(

        "click",

        function(e){

            e.preventDefault();

            e.stopPropagation();


            if(statusBtn.disabled){

                return;

            }


            statusMenu
            .classList
            .toggle(
                "show"
            );

        }

    );

}


/* ======================================================
   SELECT ADD HAULING STATUS
====================================================== */

document
.querySelectorAll(
    "#statusMenu .hauling-status-item"
)
.forEach(
function(item){


    item.addEventListener(

        "click",

        function(e){

            e.preventDefault();

            e.stopPropagation();


            if(
                this.style.display
                ===
                "none"
            ){

                return;

            }


            const selectedDate =
            haulingDate?.value
            ||
            "";


            const today =
            getHaulingTodayKey();


            const value =
            this.dataset.value;


            /* ==========================================
               FUTURE = PENDING ONLY
            ========================================== */

            if(
                selectedDate > today
                &&
                value !== "Pending"
            ){

                return;

            }


            /* ==========================================
               PAST = NO ONGOING
            ========================================== */

            if(
                selectedDate < today
                &&
                value === "Ongoing"
            ){

                return;

            }


            if(haulingStatus){

                haulingStatus.value =
                value;

            }


            if(statusText){

                statusText.textContent =
                this.textContent.trim();

            }


            statusMenu
            ?.classList
            .remove(
                "show"
            );

        }

    );

});


/* ======================================================
   CLOSE ADD STATUS MENU
====================================================== */

document.addEventListener(

    "click",

    function(){

        statusMenu
        ?.classList
        .remove(
            "show"
        );

    }

);


/* ======================================================
   INITIAL ADD STATUS STATE
====================================================== */

applyHaulingStatusRules();


/* ======================================================
   MEMBER LIVE SEARCH
====================================================== */

let memberSearchTimer =
null;


if(customerInput){

    customerInput
    .addEventListener(

        "input",

        function(){


            if(
                !suggestionBox
                ||
                !memberID
            ){

                return;

            }


            /* ==========================================
               NOT MEMBER
            ========================================== */

            if(
                affiliateHidden?.value
                !==
                "Member"
            ){

                memberID.value =
                "";


                suggestionBox.innerHTML =
                "";


                suggestionBox.style.display =
                "none";


                return;

            }


            /* ==========================================
               MEMBER

               Clear ID habang vine-verify ulit
               ang typed name.
            ========================================== */

            memberID.value =
            "";


            const keyword =
            customerInput
            .value
            .trim();


            clearTimeout(
                memberSearchTimer
            );


            if(
                keyword.length < 3
            ){

                suggestionBox.innerHTML =
                "";


                suggestionBox.style.display =
                "none";


                return;

            }


            memberSearchTimer =
            setTimeout(

                function(){


                    fetch(

                        "search_member.php?keyword="
                        +
                        encodeURIComponent(
                            keyword
                        )

                    )

                    .then(
                        response =>
                        response.text()
                    )

                    .then(
                    function(data){


                        /* ==================================
                           IGNORE OLD SEARCH RESPONSE
                        ================================== */

                        if(
                            customerInput
                            .value
                            .trim()
                            !==
                            keyword
                        ){

                            return;

                        }


                        suggestionBox.innerHTML =
                        data;


                        const typedName =
                        normalizeMemberName(
                            customerInput.value
                        );


                        const memberItems =
                        suggestionBox
                        .querySelectorAll(
                            ".member-item"
                        );


                        let exactMember =
                        null;


                        memberItems
                        .forEach(
                        function(item){


                            const id =
                            item.dataset.id;


                            const name =
                            item.dataset.name;


                            if(
                                !id
                                ||
                                !name
                            ){

                                return;

                            }


                            if(
                                normalizeMemberName(
                                    name
                                )
                                ===
                                typedName
                            ){

                                exactMember =
                                {
                                    id:id,
                                    name:name
                                };

                            }

                        });


                        /* ==================================
                           EXACT NAME RESTORES MEMBER ID

                           Example:
                           Juan Dela Cruz
                           → add typo x
                           → invalid
                           → delete x
                           → exact valid again
                        ================================== */

                        if(exactMember){

                            memberID.value =
                            exactMember.id;

                        }

                        else{

                            memberID.value =
                            "";

                        }


                        suggestionBox.style.display =
                        "block";

                    })

                    .catch(
                    function(error){


                        console.error(
                            "MEMBER SEARCH ERROR:",
                            error
                        );


                        memberID.value =
                        "";


                        suggestionBox.innerHTML =
                        '<div class="member-search-error">Unable to search members.</div>';


                        suggestionBox.style.display =
                        "block";

                    });

                },

                200

            );

        }

    );

}


/* ======================================================
   SELECT MEMBER FROM LIST
====================================================== */

document.addEventListener(

    "click",

    function(e){


        const selectedMember =
        e.target.closest(
            ".member-item"
        );


        if(
            !selectedMember
            ||
            !suggestionBox
            ||
            !suggestionBox.contains(
                selectedMember
            )
            ||
            !selectedMember.dataset.id
            ||
            !selectedMember.dataset.name
        ){

            return;

        }


        if(!customerInput){

            return;

        }


        customerInput.value =
        selectedMember.dataset.name;


        if(memberID){

            memberID.value =
            selectedMember.dataset.id;

        }


        if(suggestionBox){

            suggestionBox.style.display =
            "none";

        }

    }

);


/* ======================================================
   HIDE MEMBER SUGGESTIONS
====================================================== */

document.addEventListener(

    "click",

    function(e){

        if(
            suggestionBox
            &&
            customerInput
            &&
            !suggestionBox
            .contains(e.target)
            &&
            e.target
            !==
            customerInput
        ){

            suggestionBox.style.display =
            "none";

        }

    }

);

/* ======================================================
   HAULING EDIT MODAL
   RECORD LOADER
====================================================== */


/* ======================================================
   EDIT ELEMENTS
====================================================== */

const editHaulingModalElement =
document.getElementById(
    "editHaulingModal"
);


const editHaulingForm =
document.getElementById(
    "editHaulingForm"
);


const editHaulingID =
document.getElementById(
    "editHaulingID"
);


const editHaulingTransactionID =
document.getElementById(
    "editHaulingTransactionID"
);


const editHaulingTransactionBadge =
document.getElementById(
    "editHaulingTransactionBadge"
);


/* ======================================================
   CUSTOMER
====================================================== */

const editHaulingAffiliate =
document.getElementById(
    "editHaulingAffiliate"
);


const editAffiliateText =
document.getElementById(
    "editAffiliateText"
);


const editHaulingCustomerName =
document.getElementById(
    "editHaulingCustomerName"
);


const editHaulingMemberID =
document.getElementById(
    "editHaulingMemberID"
);


const editHaulingLocation =
document.getElementById(
    "editHaulingLocation"
);


const editHaulingCount =
document.getElementById(
    "editHaulingCount"
);


/* ======================================================
   DATE
====================================================== */

const editHaulingDate =
document.getElementById(
    "editHaulingDate"
);


const editHaulingDateText =
document.getElementById(
    "editHaulingDateText"
);


/* ======================================================
   PRIMARY ASSIGNMENT
====================================================== */

const editHaulingTractorID =
document.getElementById(
    "editHaulingTractorID"
);


const editHaulingTractorNo =
document.getElementById(
    "editHaulingTractorNo"
);


const editHaulingTractorText =
document.getElementById(
    "editHaulingTractorText"
);


const editHaulingPrimaryDriverID =
document.getElementById(
    "editHaulingPrimaryDriverID"
);


const editHaulingPrimaryDriverText =
document.getElementById(
    "editHaulingPrimaryDriverText"
);


/* ======================================================
   SECONDARY ASSIGNMENT
====================================================== */

const editHaulingSecondaryTractorID =
document.getElementById(
    "editHaulingSecondaryTractorID"
);


const editHaulingSecondaryTractorNo =
document.getElementById(
    "editHaulingSecondaryTractorNo"
);


const editHaulingSecondaryTractorText =
document.getElementById(
    "editHaulingSecondaryTractorText"
);


const editHaulingSecondaryDriverID =
document.getElementById(
    "editHaulingSecondaryDriverID"
);


const editHaulingSecondaryDriverText =
document.getElementById(
    "editHaulingSecondaryDriverText"
);

const editHaulingSecondaryTractorMenu =
document.getElementById(
    "editHaulingSecondaryTractorMenu"
);


const editHaulingSecondaryDriverMenu =
document.getElementById(
    "editHaulingSecondaryDriverMenu"
);

let editHaulingTractors =
[];


let editHaulingDrivers =
[];


let editHaulingAvailabilityRequest =
0;

const editHaulingSecondaryAssignment =
document.getElementById(
    "editHaulingSecondaryAssignment"
);


const editHaulingSecondaryToggle =
document.getElementById(
    "editHaulingSecondaryToggle"
);


const editHaulingSecondaryToggleText =
document.getElementById(
    "editHaulingSecondaryToggleText"
);


/* ======================================================
   AMOUNT
====================================================== */

const editHaulingPrice =
document.getElementById(
    "editHaulingPrice"
);


const editHaulingDiscount =
document.getElementById(
    "editHaulingDiscount"
);


const editHaulingTotal =
document.getElementById(
    "editHaulingTotal"
);


/* ======================================================
   STATUS
====================================================== */

const editHaulingStatus =
document.getElementById(
    "editHaulingStatus"
);


const editHaulingStatusText =
document.getElementById(
    "editHaulingStatusText"
);


const editHaulingStatusBtn =
document.getElementById(
    "editHaulingStatusBtn"
);


const updateHaulingBtn =
document.getElementById(
    "updateHaulingBtn"
);

/* ======================================================
   EDIT INTERACTIVE CONTROLS
====================================================== */

const editHaulingDateBtn =
document.getElementById(
    "editHaulingDateBtn"
);


const editHaulingTractorBtn =
document.getElementById(
    "editHaulingTractorBtn"
);


const editHaulingPrimaryDriverBtn =
document.getElementById(
    "editHaulingPrimaryDriverBtn"
);


const editHaulingSecondaryTractorBtn =
document.getElementById(
    "editHaulingSecondaryTractorBtn"
);


const editHaulingSecondaryDriverBtn =
document.getElementById(
    "editHaulingSecondaryDriverBtn"
);


const editHaulingLifecycleHint =
document.getElementById(
    "editHaulingLifecycleHint"
);

/* ======================================================
   CURRENT EDIT RECORD

   Keeps the original server values while the user edits.
   Used to detect Missed / Delayed / Reschedule changes.
====================================================== */

let editHaulingLoadedRecord =
null;

/* ======================================================
   EDIT PRIMARY MENUS
====================================================== */

const editHaulingTractorMenu =
document.getElementById(
    "editHaulingTractorMenu"
);


const editHaulingPrimaryDriverMenu =
document.getElementById(
    "editHaulingPrimaryDriverMenu"
);


/* ======================================================
   EDIT CALENDAR
====================================================== */

const editHaulingCalendar =
document.getElementById(
    "editHaulingCalendar"
);


const editHaulingCalendarPrev =
document.getElementById(
    "editHaulingCalendarPrev"
);


const editHaulingCalendarMonth =
document.getElementById(
    "editHaulingCalendarMonth"
);


const editHaulingCalendarNext =
document.getElementById(
    "editHaulingCalendarNext"
);


const editHaulingCalendarDays =
document.getElementById(
    "editHaulingCalendarDays"
);


let editHaulingCalendarView =
new Date();


editHaulingCalendarView.setDate(
    1
);

/* ======================================================
   EDIT TRACTOR AVAILABILITY
   SELF-EXCLUDES CURRENT HAULING RECORD
====================================================== */

async function getEditHaulingTractorAvailability(
    rentalDate,
    haulingID
){

    const url =

    "tractor/get_tractor_availability.php"
    +
    "?tractor_type=Hauling"
    +
    "&rental_date="
    +
    encodeURIComponent(
        rentalDate
    )
    +
    "&rental_type=hauling"
    +
    "&rental_id="
    +
    encodeURIComponent(
        haulingID
    );


    const response =
    await fetch(
        url,
        {
            cache:
            "no-store"
        }
    );


    const data =
    await response.json();


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
   SELF-EXCLUDES CURRENT HAULING RECORD
====================================================== */

async function getEditHaulingDriverAvailability(
    rentalDate,
    haulingID
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
    "&rental_type=hauling"
    +
    "&rental_id="
    +
    encodeURIComponent(
        haulingID
    );


    const response =
    await fetch(
        url,
        {
            cache:
            "no-store"
        }
    );


    const data =
    await response.json();


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
   LOAD EDIT HAULING AVAILABILITY
====================================================== */

async function loadEditHaulingAssignmentAvailability(){

    const rentalDate =
    editHaulingDate?.value
    ||
    "";


    const haulingID =
    parseInt(
        editHaulingID?.value,
        10
    )
    ||
    0;


    if(
        !rentalDate
        ||
        haulingID <= 0
    ){

        return false;

    }


    const requestID =
    ++editHaulingAvailabilityRequest;


    try{

        const [
            tractorData,
            driverData
        ] =

        await Promise.all([

            getEditHaulingTractorAvailability(
                rentalDate,
                haulingID
            ),

            getEditHaulingDriverAvailability(
                rentalDate,
                haulingID
            )

        ]);


        if(
            requestID
            !==
            editHaulingAvailabilityRequest
        ){

            return false;

        }


        editHaulingTractors =

        Array.isArray(
            tractorData.tractors
        )

        ?

        tractorData.tractors

        :

        [];


        editHaulingDrivers =

        Array.isArray(
            driverData.drivers
        )

        ?

        driverData.drivers

        :

        [];


        renderHaulingTractorMenu(
            editHaulingTractorMenu,
            editHaulingTractors,
            false
        );


        renderHaulingDriverMenu(
            editHaulingPrimaryDriverMenu,
            editHaulingDrivers,
            false
        );


        refreshEditHaulingSecondaryMenus();


        return true;

    }
    catch(error){

        console.error(
            "EDIT HAULING AVAILABILITY ERROR:",
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
            "Unable to load Tractor and Driver availability.",

            confirmButtonColor:
            "#8CA15B"

        });


        return false;

    }

}

/* ======================================================
   EDIT SECONDARY MENU FILTER
   Hide current Primary Tractor / Driver
====================================================== */

function refreshEditHaulingSecondaryMenus(){

    const primaryTractorID =
    String(
        editHaulingTractorID?.value
        ||
        ""
    );


    const primaryDriverID =
    String(
        editHaulingPrimaryDriverID?.value
        ||
        ""
    );


    const secondaryTractors =
    editHaulingTractors.filter(
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


    const secondaryDrivers =
    editHaulingDrivers.filter(
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


    renderHaulingTractorMenu(
        editHaulingSecondaryTractorMenu,
        secondaryTractors,
        true
    );


    renderHaulingDriverMenu(
        editHaulingSecondaryDriverMenu,
        secondaryDrivers,
        true
    );

}

/* ======================================================
   FORMAT MONEY
====================================================== */

function editHaulingMoneyValue(
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

/* ======================================================
   EDIT HAULING LIFECYCLE LOCKS

   PENDING
   - Date editable
   - Primary editable
   - Secondary editable

   ONGOING
   - Date locked
   - Primary locked
   - Secondary editable

   DONE
   - Everything locked
====================================================== */

function applyEditHaulingLifecycle(
    record
){

    const status =
    String(
        record?.status
        ??
        ""
    )
    .trim();


    const isPending =
    status === "Pending";


    const isOngoing =
    status === "Ongoing";


    const isDone =
    status === "Done";

    const rentalDate =
    String(
        record?.rental_date
        ??
        ""
    )
    .trim();


    const selectedRentalDate =
    String(
        editHaulingDate?.value
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


    const todayKey =
    getHaulingTodayKey();


    const isPastDate =
    rentalDate !== ""
    &&
    rentalDate < todayKey;


    const isMissed =
    isPending
    &&
    isPastDate;


    const isDelayed =
    isOngoing
    &&
    isPastDate;


    const rescheduleCount =
    parseInt(
        record?.reschedule_count,
        10
    )
    ||
    0;


    const wasRescheduled =
    rescheduleCount > 0;


    const needsReschedule =
    (
        isMissed
        ||
        isDelayed
    )
    &&
    !dateChanged;


    let lockDate =
    true;


    let lockPrimary =
    true;


    let lockSecondary =
    true;


    let lockSave =
    true;


    let hintText =
    "This rental is currently read-only.";


    if(
        isPending
    ){

        lockDate =
        false;

        lockPrimary =
        false;

        lockSecondary =
        false;

        lockSave =
        false;


        if(
            isMissed
        ){

            hintText =
            "Missed rental: reschedule it to today or a future date. Status will remain Pending.";

        }
        else if(
            wasRescheduled
        ){

            hintText =
            "Rescheduled rental: schedule and assignments can still be updated while Pending.";

        }
        else{

            hintText =
            "Pending rental: schedule and assignments can still be updated.";

        }

    }
    else if(
        isOngoing
    ){

        if(
            isDelayed
        ){

            /*
            DELAYED ONGOING

            - Rental Date may be changed
            - Primary stays locked while still on
                the original Delayed date
            - Primary unlocks after a new date
                is actually selected
            - Secondary remains editable
            */

            lockDate =
            false;


            lockPrimary =
            !dateChanged;


            lockSecondary =
            false;


            lockSave =
            false;


            hintText =

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

            hintText =
            "Ongoing rental: Rental Date and Primary Assignment are locked. Secondary Assignment may still be adjusted.";

        }

    }
    else if(
        isDone
    ){

        lockDate =
        true;


        lockPrimary =
        true;


        lockSecondary =
        true;


        lockSave =
        true;


        hintText =
        "Completed rental: this record is now read-only.";

    }


    if(
        editHaulingDateBtn
    ){

        editHaulingDateBtn.disabled =
        lockDate;


        editHaulingDateBtn
        .classList
        .toggle(
            "hauling-reschedule-needed",
            needsReschedule
        );

    }


    if(
        editHaulingTractorBtn
    ){

        editHaulingTractorBtn.disabled =
        lockPrimary;

    }


    if(
        editHaulingPrimaryDriverBtn
    ){

        editHaulingPrimaryDriverBtn.disabled =
        lockPrimary;

    }


    if(
        editHaulingSecondaryToggle
    ){

        editHaulingSecondaryToggle.disabled =
        lockSecondary;


        editHaulingSecondaryToggle.style.display =

        isDone

        ?

        "none"

        :

        "";

    }


    if(
        editHaulingSecondaryTractorBtn
    ){

        editHaulingSecondaryTractorBtn.disabled =
        lockSecondary;

    }


    if(
        editHaulingSecondaryDriverBtn
    ){

        editHaulingSecondaryDriverBtn.disabled =
        lockSecondary;

    }


    if(
        editHaulingLifecycleHint
    ){

        const hintSpan =
        editHaulingLifecycleHint
        .querySelector(
            "span"
        );


        if(
            hintSpan
        ){

            hintSpan.textContent =
            hintText;

        }

    }


    if(
        updateHaulingBtn
    ){

        updateHaulingBtn.disabled =
        lockSave;


        updateHaulingBtn.textContent =

        isDone

        ?

        "COMPLETED"

        :

        (
            isPending
            ||
            isOngoing
        )

        ?

        "SAVE CHANGES"

        :

        "LOCKED";

    }

}


/* ======================================================
   RESET EDIT SECONDARY UI
====================================================== */

function resetEditHaulingSecondaryUI(){

    if(
        editHaulingSecondaryAssignment
    ){

        editHaulingSecondaryAssignment
        .classList
        .remove(
            "is-open"
        );

    }


    if(
        editHaulingSecondaryToggle
    ){

        editHaulingSecondaryToggle
        .classList
        .remove(
            "is-open"
        );

    }


    if(
        editHaulingSecondaryToggleText
    ){

        editHaulingSecondaryToggleText
        .textContent =
        "ADD SECONDARY ASSIGNMENT";

    }

}


/* ======================================================
   OPEN EDIT SECONDARY UI
====================================================== */

function openEditHaulingSecondaryUI(){

    if(
        editHaulingSecondaryAssignment
    ){

        editHaulingSecondaryAssignment
        .classList
        .add(
            "is-open"
        );

    }


    if(
        editHaulingSecondaryToggle
    ){

        editHaulingSecondaryToggle
        .classList
        .add(
            "is-open"
        );

    }


    if(
        editHaulingSecondaryToggleText
    ){

        editHaulingSecondaryToggleText
        .textContent =
        "REMOVE SECONDARY ASSIGNMENT";

    }

}

/* ======================================================
   EDIT SECONDARY ASSIGNMENT TOGGLE
====================================================== */

if(
    editHaulingSecondaryToggle
    &&
    editHaulingSecondaryAssignment
){

    editHaulingSecondaryToggle
    .addEventListener(
        "click",
        async function(event){

            event.preventDefault();
            event.stopPropagation();


            /* ==========================================
               DISABLED / DONE
            ========================================== */

            if(
                editHaulingSecondaryToggle.disabled
            ){

                return;

            }


            const rentalStatus =
            String(
                editHaulingStatus?.value
                ||
                ""
            )
            .trim();

                        const originalRentalDate =
            String(
                editHaulingLoadedRecord?.rental_date
                ||
                ""
            )
            .trim();


            const isDelayedOngoing =
            rentalStatus === "Ongoing"
            &&
            originalRentalDate !== ""
            &&
            originalRentalDate < getHaulingTodayKey();


            const isOpen =
            editHaulingSecondaryAssignment
            .classList
            .contains(
                "is-open"
            );


            /* ==========================================
               CLOSE SECONDARY AREA
            ========================================== */

            if(
                isOpen
            ){

                const hasSecondary =

                String(
                    editHaulingSecondaryTractorID?.value
                    ||
                    ""
                )
                !==
                ""

                ||

                String(
                    editHaulingSecondaryDriverID?.value
                    ||
                    ""
                )
                !==
                "";


                /* ======================================
                   ASK BEFORE CLEARING SELECTED RECORDS
                ====================================== */

                if(
                    hasSecondary
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


                    if(
                        editHaulingSecondaryTractorID
                    ){

                        editHaulingSecondaryTractorID.value =
                        "";

                    }


                    if(
                        editHaulingSecondaryTractorNo
                    ){

                        editHaulingSecondaryTractorNo.value =
                        "";

                    }


                    if(
                        editHaulingSecondaryDriverID
                    ){

                        editHaulingSecondaryDriverID.value =
                        "";

                    }


                    if(
                        editHaulingSecondaryTractorText
                    ){

                        editHaulingSecondaryTractorText.textContent =
                        "No Secondary Tractor";

                    }


                    if(
                        editHaulingSecondaryDriverText
                    ){

                        editHaulingSecondaryDriverText.textContent =
                        "No Secondary Driver";

                    }

                }


                resetEditHaulingSecondaryUI();

                return;

            }


            /* ==========================================
               ONGOING CONFIRMATION

               Primary assignment is already locked.
               Secondary can still be adjusted.
            ========================================== */

            if(
                rentalStatus === "Ongoing"
                &&
                !isDelayedOngoing
            ){

                const result =
                await Swal.fire({

                    icon:
                    "question",

                    title:
                    "Adjust Secondary Assignment?",

                    text:
                    "This rental is already Ongoing. Only the Secondary Tractor and Driver may be adjusted.",

                    showCancelButton:
                    true,

                    confirmButtonText:
                    "Continue",

                    cancelButtonText:
                    "Cancel",

                    confirmButtonColor:
                    "#8CA15B",

                    cancelButtonColor:
                    "#6C757D"

                });


                if(
                    !result.isConfirmed
                ){

                    return;

                }

            }


            /* ==========================================
               OPEN SECONDARY AREA
            ========================================== */

            const availabilityLoaded =
            await loadEditHaulingAssignmentAvailability();


            if(
                !availabilityLoaded
            ){

                return;

            }


            openEditHaulingSecondaryUI();

        }
    );

}

/* ======================================================
   EDIT SECONDARY TRACTOR DROPDOWN
====================================================== */

if(
    editHaulingSecondaryTractorBtn
    &&
    editHaulingSecondaryTractorMenu
){

    editHaulingSecondaryTractorBtn
    .addEventListener(
        "click",
        async function(event){

            event.preventDefault();
            event.stopPropagation();


            if(
                editHaulingSecondaryTractorBtn.disabled
            ){

                return;

            }


            const willOpen =

            !editHaulingSecondaryTractorMenu
            .classList
            .contains(
                "show"
            );


            if(
                willOpen
            ){

                const loaded =
                await loadEditHaulingAssignmentAvailability();


                if(
                    !loaded
                ){

                    return;

                }

            }


            editHaulingCalendar
            ?.classList
            .remove(
                "show"
            );


            closeEditHaulingAssignmentMenus(
                editHaulingSecondaryTractorMenu
            );


            editHaulingSecondaryTractorMenu
            .classList
            .toggle(
                "show",
                willOpen
            );

        }
    );


    editHaulingSecondaryTractorMenu
    .addEventListener(
        "click",
        function(event){

            event.stopPropagation();


            const item =
            event.target.closest(
                ".hauling-tractor-item"
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

                editHaulingSecondaryTractorID.value =
                "";

                editHaulingSecondaryTractorNo.value =
                "";

                editHaulingSecondaryDriverID.value =
                "";

                editHaulingSecondaryTractorText.textContent =
                "No Secondary Tractor";

                editHaulingSecondaryDriverText.textContent =
                "No Secondary Driver";


                editHaulingSecondaryTractorMenu
                .classList
                .remove(
                    "show"
                );


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
                tractorID <= 0
            ){

                return;

            }


            if(
                String(
                    editHaulingTractorID?.value
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


            editHaulingSecondaryTractorID.value =
            tractorID;


            editHaulingSecondaryTractorNo.value =
            item.dataset.no
            ||
            "";


            editHaulingSecondaryTractorText.textContent =

            (
                item.dataset.no
                ||
                ""
            )

            +

            (
                item.dataset.name

                ?

                " - "
                +
                item.dataset.name

                :

                ""
            );


            editHaulingSecondaryTractorMenu
            .classList
            .remove(
                "show"
            );

        }
    );

}

/* ======================================================
   EDIT SECONDARY DRIVER DROPDOWN
====================================================== */

if(
    editHaulingSecondaryDriverBtn
    &&
    editHaulingSecondaryDriverMenu
){

    editHaulingSecondaryDriverBtn
    .addEventListener(
        "click",
        async function(event){

            event.preventDefault();
            event.stopPropagation();


            if(
                editHaulingSecondaryDriverBtn.disabled
            ){

                return;

            }


            const willOpen =

            !editHaulingSecondaryDriverMenu
            .classList
            .contains(
                "show"
            );


            if(
                willOpen
            ){

                const loaded =
                await loadEditHaulingAssignmentAvailability();


                if(
                    !loaded
                ){

                    return;

                }

            }


            editHaulingCalendar
            ?.classList
            .remove(
                "show"
            );


            closeEditHaulingAssignmentMenus(
                editHaulingSecondaryDriverMenu
            );


            editHaulingSecondaryDriverMenu
            .classList
            .toggle(
                "show",
                willOpen
            );

        }
    );


    editHaulingSecondaryDriverMenu
    .addEventListener(
        "click",
        function(event){

            event.stopPropagation();


            const item =
            event.target.closest(
                ".hauling-tractor-item"
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

                editHaulingSecondaryTractorID.value =
                "";

                editHaulingSecondaryTractorNo.value =
                "";

                editHaulingSecondaryDriverID.value =
                "";

                editHaulingSecondaryTractorText.textContent =
                "No Secondary Tractor";

                editHaulingSecondaryDriverText.textContent =
                "No Secondary Driver";


                editHaulingSecondaryDriverMenu
                .classList
                .remove(
                    "show"
                );


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
                driverID <= 0
            ){

                return;

            }


            if(
                String(
                    editHaulingPrimaryDriverID?.value
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


            editHaulingSecondaryDriverID.value =
            driverID;


            editHaulingSecondaryDriverText.textContent =
            item.dataset.name
            ||
            "Selected Driver";


            editHaulingSecondaryDriverMenu
            .classList
            .remove(
                "show"
            );

        }
    );

}

/* ======================================================
   EDIT PRIMARY ASSIGNMENT MENUS
====================================================== */

function closeEditHaulingAssignmentMenus(
    exceptMenu = null
){

    const menus = [

        editHaulingTractorMenu,
        editHaulingPrimaryDriverMenu,
        editHaulingSecondaryTractorMenu,
        editHaulingSecondaryDriverMenu

    ];


    menus.forEach(
    function(menu){

        if(
            menu
            &&
            menu !== exceptMenu
        ){

            menu.classList.remove(
                "show"
            );

        }

    });

}


/* ======================================================
   EDIT PRIMARY TRACTOR BUTTON
====================================================== */

if(
    editHaulingTractorBtn
    &&
    editHaulingTractorMenu
){

    editHaulingTractorBtn
    .addEventListener(
        "click",
        async function(event){

            event.preventDefault();
            event.stopPropagation();


            if(
                editHaulingTractorBtn.disabled
            ){

                return;

            }


            const loaded =
            await loadEditHaulingAssignmentAvailability();


            if(
                !loaded
            ){

                return;

            }


            editHaulingCalendar
            ?.classList
            .remove(
                "show"
            );


            const willOpen =

            !editHaulingTractorMenu
            .classList
            .contains(
                "show"
            );


            closeEditHaulingAssignmentMenus(
                editHaulingTractorMenu
            );


            editHaulingTractorMenu
            .classList
            .toggle(
                "show",
                willOpen
            );

        }
    );


    editHaulingTractorMenu
    .addEventListener(
        "click",
        function(event){

            event.stopPropagation();


            const item =
            event.target.closest(
                ".hauling-tractor-item"
            );


            if(
                !item
            ){

                return;

            }


            if(
                item.dataset.disabled === "1"
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
                tractorID <= 0
            ){

                return;

            }


            if(
                String(
                    editHaulingSecondaryTractorID?.value
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
                    "Primary Tractor cannot be the same as the Secondary Tractor.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            editHaulingTractorID.value =
            tractorID;


            editHaulingTractorNo.value =
            item.dataset.no
            ||
            "";


            editHaulingTractorText.textContent =

            (
                item.dataset.no
                ||
                ""
            )

            +

            (
                item.dataset.name

                ?

                " - "
                +
                item.dataset.name

                :

                ""
            );


            editHaulingTractorMenu
            .classList
            .remove(
                "show"
            );

        }
    );

}


/* ======================================================
   EDIT PRIMARY DRIVER BUTTON
====================================================== */

if(
    editHaulingPrimaryDriverBtn
    &&
    editHaulingPrimaryDriverMenu
){

    editHaulingPrimaryDriverBtn
    .addEventListener(
        "click",
        async function(event){

            event.preventDefault();
            event.stopPropagation();


            if(
                editHaulingPrimaryDriverBtn.disabled
            ){

                return;

            }


            const loaded =
            await loadEditHaulingAssignmentAvailability();


            if(
                !loaded
            ){

                return;

            }


            editHaulingCalendar
            ?.classList
            .remove(
                "show"
            );


            const willOpen =

            !editHaulingPrimaryDriverMenu
            .classList
            .contains(
                "show"
            );


            closeEditHaulingAssignmentMenus(
                editHaulingPrimaryDriverMenu
            );


            editHaulingPrimaryDriverMenu
            .classList
            .toggle(
                "show",
                willOpen
            );

        }
    );


    editHaulingPrimaryDriverMenu
    .addEventListener(
        "click",
        function(event){

            event.stopPropagation();


            const item =
            event.target.closest(
                ".hauling-tractor-item"
            );


            if(
                !item
            ){

                return;

            }


            if(
                item.dataset.disabled === "1"
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
                driverID <= 0
            ){

                return;

            }


            if(
                String(
                    editHaulingSecondaryDriverID?.value
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
                    "Primary Driver cannot be the same as the Secondary Driver.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            editHaulingPrimaryDriverID.value =
            driverID;


            editHaulingPrimaryDriverText.textContent =
            item.dataset.name
            ||
            "Selected Driver";


            editHaulingPrimaryDriverMenu
            .classList
            .remove(
                "show"
            );

        }
    );

}

/* ======================================================
   EDIT CALENDAR AVAILABILITY

   Uses current Hauling ID so the rental being edited
   does not conflict with itself.
====================================================== */

let editHaulingCalendarAvailability = {};

let editHaulingCalendarAvailabilityKey = "";

let editHaulingCalendarAvailabilityRequest = 0;


/* ======================================================
   EDIT CALENDAR MONTH KEY
====================================================== */

function getEditHaulingCalendarViewKey(){

    const year =
    editHaulingCalendarView
    .getFullYear();


    const month =
    String(
        editHaulingCalendarView
        .getMonth()
        +
        1
    )
    .padStart(
        2,
        "0"
    );


    return (
        year
        +
        "-"
        +
        month
    );
}


/* ======================================================
   LOAD EDIT CALENDAR AVAILABILITY
====================================================== */

async function loadEditHaulingCalendarAvailability(){

    const haulingID =

    parseInt(
        editHaulingID?.value,
        10
    )
    ||
    0;


    if(
        haulingID <= 0
    ){

        return false;
    }


    const year =
    editHaulingCalendarView
    .getFullYear();


    const month =
    editHaulingCalendarView
    .getMonth()
    +
    1;


    const viewKey =
    (
        year
        +
        "-"
        +
        String(
            month
        )
        .padStart(
            2,
            "0"
        )
    );


    const requestID =
    ++editHaulingCalendarAvailabilityRequest;


    try{

        const response =
        await fetch(

            "get_hauling_booked_dates.php"
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
                haulingID
            )
            +
            "&primary_tractor_id="
            +
            encodeURIComponent(
                editHaulingTractorID?.value
                ||
                ""
            )
            +
            "&secondary_tractor_id="
            +
            encodeURIComponent(
                editHaulingSecondaryTractorID?.value
                ||
                ""
            )
            +
            "&primary_driver_id="
            +
            encodeURIComponent(
                editHaulingPrimaryDriverID?.value
                ||
                ""
            )
            +
            "&secondary_driver_id="
            +
            encodeURIComponent(
                editHaulingSecondaryDriverID?.value
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


        let result;


        try{

            result =
            await response.json();

        }
        catch(error){

            throw new Error(
                "Invalid Edit calendar availability response."
            );

        }


        if(
            requestID
            !==
            editHaulingCalendarAvailabilityRequest
        ){

            return false;

        }


        if(
            !response.ok
            ||
            !result.success
        ){

            throw new Error(
                result.message
                ||
                "Unable to load Edit calendar availability."
            );

        }


        editHaulingCalendarAvailability =
        result.data?.dates
        ||
        {};


        editHaulingCalendarAvailabilityKey =
        viewKey;


        renderEditHaulingCalendar();


        return true;

    }
    catch(error){

        if(
            requestID
            !==
            editHaulingCalendarAvailabilityRequest
        ){

            return false;

        }


        console.error(
            "EDIT HAULING CALENDAR AVAILABILITY:",
            error
        );


        editHaulingCalendarAvailability =
        {};


        editHaulingCalendarAvailabilityKey =
        viewKey;


        renderEditHaulingCalendar();


        return false;

    }

}

/* ======================================================
   EDIT RENTAL DATE CALENDAR
====================================================== */

function renderEditHaulingCalendar(){

    if(
        !editHaulingCalendarMonth
        ||
        !editHaulingCalendarDays
    ){

        return;

    }


    const year =
    editHaulingCalendarView
    .getFullYear();


    const month =
    editHaulingCalendarView
    .getMonth();


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


    editHaulingCalendarMonth.textContent =

    new Date(
        year,
        month,
        1
    )
    .toLocaleDateString(
        "en-US",
        {
            month:
            "long",

            year:
            "numeric"
        }
    );


    editHaulingCalendarDays.innerHTML =
    "";


    /* ==========================================
       EMPTY CELLS
    ========================================== */

    for(
        let blank = 0;
        blank < firstDay;
        blank++
    ){

        const emptyCell =
        document.createElement(
            "div"
        );


        emptyCell.className =
        "hauling-calendar-empty";


        editHaulingCalendarDays
        .appendChild(
            emptyCell
        );

    }


    const todayKey =
    getHaulingTodayKey();


    /* ==========================================
       DAYS
    ========================================== */

    for(
        let day = 1;
        day <= daysInMonth;
        day++
    ){

        const dateKey =
        haulingBuildDateKey(
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
        "hauling-calendar-day";


        dayButton.textContent =
        day;


        dayButton.dataset.date =
        dateKey;

                /* ======================================
           EDIT DATE AVAILABILITY
        ====================================== */

        const currentViewKey =
        getEditHaulingCalendarViewKey();


        const dateAvailability =

        editHaulingCalendarAvailabilityKey
        ===
        currentViewKey

        ?

        (
            editHaulingCalendarAvailability[
                dateKey
            ]
            ||
            null
        )

        :

        null;

        const calendarStatus =

        dateAvailability?.assignment_status
        ||
        dateAvailability?.status
        ||
        "";


        /* ======================================
           PAST DATE = NOT SELECTABLE IN EDIT
        ====================================== */

        if(
            dateKey < todayKey
        ){

            dayButton.disabled =
            true;


            dayButton.classList.add(
                "hauling-calendar-past"
            );


            dayButton.title =
            "Past dates cannot be selected when editing a rental.";

        }

        /* ======================================
           FULLY BOOKED
        ====================================== */

        else if(
            calendarStatus
            ===
            "Fully Booked"
        ){

            dayButton.disabled =
            true;


            dayButton.classList.add(
                "hauling-calendar-booked"
            );


            dayButton.title =
            "Fully booked. No valid Hauling Tractor and Driver pair is available on this date.";

        }

        /* ======================================
           LIMITED
        ====================================== */

        else if(
            calendarStatus
            ===
            "Limited"
        ){

            dayButton.classList.add(
                "hauling-calendar-limited"
            );


            dayButton.title =
            "Limited availability. One or more currently assigned Tractor or Driver is unavailable on this date and may need replacement.";

        }

        /* ======================================
           AVAILABLE
        ====================================== */

        else if(
            calendarStatus
            ===
            "Available"
        ){

            dayButton.classList.add(
                "hauling-calendar-available"
            );


            dayButton.title =
            "Current assignment is available on this date.";

        }


        /* ======================================
           TODAY
        ====================================== */

        if(
            dateKey === todayKey
        ){

            dayButton
            .classList
            .add(
                "hauling-calendar-today"
            );

        }


        /* ======================================
           CURRENTLY SELECTED DATE
        ====================================== */

        if(
            editHaulingDate?.value
            ===
            dateKey
        ){

            dayButton
            .classList
            .add(
                "hauling-calendar-selected"
            );

        }


        /* ======================================
           SELECT DATE
        ====================================== */

        dayButton
        .addEventListener(
            "click",
            async function(event){

                event.preventDefault();
                event.stopPropagation();


                if(
                    editHaulingDateBtn?.disabled
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


                const previousDate =
                editHaulingDate?.value
                ||
                "";


                /* same date = just close */

                if(
                    selectedDate
                    ===
                    previousDate
                ){

                    editHaulingCalendar
                    ?.classList
                    .remove(
                        "show"
                    );


                    return;

                }


                if(
                    editHaulingDate
                ){

                    editHaulingDate.value =
                    selectedDate;

                }


                if(
                    editHaulingDateText
                ){

                    editHaulingDateText.textContent =
                    haulingFormatDate(
                        selectedDate
                    );

                }


                editHaulingCalendar
                ?.classList
                .remove(
                    "show"
                );


                /* ==================================
                   LOAD AVAILABILITY FOR NEW DATE
                ================================== */

                const loaded =
                await loadEditHaulingAssignmentAvailability();


                if(
                    !loaded
                ){

                    /* restore previous date if load failed */

                    editHaulingDate.value =
                    previousDate;


                    editHaulingDateText.textContent =

                    previousDate

                    ?

                    haulingFormatDate(
                        previousDate
                    )

                    :

                    "Select Rental Date";


                    return;

                }


                /* ==================================
                   RECHECK CURRENT PRIMARY TRACTOR
                ================================== */

                let clearedAssignment =
                false;


                const currentPrimaryTractorID =
                editHaulingTractorID?.value
                ||
                "";


                if(
                    currentPrimaryTractorID
                    !==
                    ""
                ){

                    const tractor =
                    findHaulingTractorByID(
                        editHaulingTractors,
                        currentPrimaryTractorID
                    );


                    if(
                        !tractor
                        ||
                        tractor.available
                        !==
                        true
                    ){

                        editHaulingTractorID.value =
                        "";

                        editHaulingTractorNo.value =
                        "";

                        editHaulingTractorText.textContent =
                        "Select Primary Tractor";


                        clearedAssignment =
                        true;

                    }

                }


                /* ==================================
                   RECHECK CURRENT PRIMARY DRIVER
                ================================== */

                const currentPrimaryDriverID =
                editHaulingPrimaryDriverID?.value
                ||
                "";


                if(
                    currentPrimaryDriverID
                    !==
                    ""
                ){

                    const driver =
                    findHaulingDriverByID(
                        editHaulingDrivers,
                        currentPrimaryDriverID
                    );


                    if(
                        !driver
                        ||
                        driver.available
                        !==
                        true
                    ){

                        editHaulingPrimaryDriverID.value =
                        "";

                        editHaulingPrimaryDriverText.textContent =
                        "Select Primary Driver";


                        clearedAssignment =
                        true;

                    }

                }


                /* ==================================
                   RECHECK SECONDARY PAIR
                ================================== */

                const currentSecondaryTractorID =
                editHaulingSecondaryTractorID?.value
                ||
                "";


                const currentSecondaryDriverID =
                editHaulingSecondaryDriverID?.value
                ||
                "";


                let secondaryInvalid =
                false;


                if(
                    currentSecondaryTractorID
                    !==
                    ""
                ){

                    const tractor =
                    findHaulingTractorByID(
                        editHaulingTractors,
                        currentSecondaryTractorID
                    );


                    if(
                        !tractor
                        ||
                        tractor.available
                        !==
                        true
                    ){

                        secondaryInvalid =
                        true;

                    }

                }


                if(
                    currentSecondaryDriverID
                    !==
                    ""
                ){

                    const driver =
                    findHaulingDriverByID(
                        editHaulingDrivers,
                        currentSecondaryDriverID
                    );


                    if(
                        !driver
                        ||
                        driver.available
                        !==
                        true
                    ){

                        secondaryInvalid =
                        true;

                    }

                }


                if(
                    secondaryInvalid
                ){

                    editHaulingSecondaryTractorID.value =
                    "";

                    editHaulingSecondaryTractorNo.value =
                    "";

                    editHaulingSecondaryDriverID.value =
                    "";

                    editHaulingSecondaryTractorText.textContent =
                    "No Secondary Tractor";

                    editHaulingSecondaryDriverText.textContent =
                    "No Secondary Driver";


                    clearedAssignment =
                    true;

                }


                renderEditHaulingCalendar();


                /*
                Re-apply lifecycle after changing Rental Date.

                Delayed Ongoing:
                - original date = Primary locked
                - new date = Primary editable
                */

                if(
                    editHaulingLoadedRecord
                ){

                    applyEditHaulingLifecycle(
                        editHaulingLoadedRecord
                    );

                }


                /* ==================================
                INFORM USER ONLY WHEN NEEDED
                ================================== */

                if(
                    clearedAssignment
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


        editHaulingCalendarDays
        .appendChild(
            dayButton
        );

    }

}


/* ======================================================
   EDIT CALENDAR POSITION
====================================================== */

function positionEditHaulingCalendar(){

    if(
        !editHaulingCalendar
        ||
        !editHaulingDateBtn
    ){

        return;

    }


    const buttonRect =
    editHaulingDateBtn
    .getBoundingClientRect();


    const calendarRect =
    editHaulingCalendar
    .getBoundingClientRect();


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


    editHaulingCalendar.style.left =
    Math.round(
        left
    )
    +
    "px";


    editHaulingCalendar.style.top =
    Math.round(
        top
    )
    +
    "px";

}


/* ======================================================
   EDIT DATE BUTTON
====================================================== */

if(
    editHaulingDateBtn
    &&
    editHaulingCalendar
){

    editHaulingDateBtn
    .addEventListener(
        "click",
        function(event){

            event.preventDefault();
            event.stopPropagation();


            if(
                editHaulingDateBtn.disabled
            ){

                return;

            }


            closeEditHaulingAssignmentMenus();


            /* ======================================
               OPEN CALENDAR ON CURRENT RENTAL DATE
            ====================================== */

            const currentDate =
            editHaulingDate?.value
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

                    editHaulingCalendarView =
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


            renderEditHaulingCalendar();

            loadEditHaulingCalendarAvailability();

            const willOpen =

            !editHaulingCalendar
            .classList
            .contains(
                "show"
            );


            editHaulingCalendar
            .classList
            .toggle(
                "show",
                willOpen
            );


            if(
                willOpen
            ){

                requestAnimationFrame(
                    function(){

                        positionEditHaulingCalendar();

                    }
                );

            }

        }
    );


    editHaulingCalendar
    .addEventListener(
        "click",
        function(event){

            event.stopPropagation();

        }
    );

}


/* ======================================================
   EDIT CALENDAR PREVIOUS MONTH
====================================================== */

editHaulingCalendarPrev
?.addEventListener(
    "click",
    function(event){

        event.preventDefault();
        event.stopPropagation();


        editHaulingCalendarView
        .setMonth(

            editHaulingCalendarView
            .getMonth()
            -
            1

        );


        renderEditHaulingCalendar();

        loadEditHaulingCalendarAvailability();

        positionEditHaulingCalendar();

    }
);


/* ======================================================
   EDIT CALENDAR NEXT MONTH
====================================================== */

editHaulingCalendarNext
?.addEventListener(
    "click",
    function(event){

        event.preventDefault();
        event.stopPropagation();


        editHaulingCalendarView
        .setMonth(

            editHaulingCalendarView
            .getMonth()
            +
            1

        );


        renderEditHaulingCalendar();

        loadEditHaulingCalendarAvailability();

        positionEditHaulingCalendar();

    }
);


/* ======================================================
   CLOSE EDIT CALENDAR WHEN CLICKING OUTSIDE
====================================================== */

document
.addEventListener(
    "click",
    function(){

        editHaulingCalendar
        ?.classList
        .remove(
            "show"
        );


        closeEditHaulingAssignmentMenus();

    }
);


/* ======================================================
   RESET EDIT MODAL
====================================================== */

function resetEditHaulingModal(){

    editHaulingLoadedRecord =
    null;

    editHaulingForm
    ?.reset();


    if(
        editHaulingID
    ){

        editHaulingID.value =
        "";

    }


    if(
        editHaulingTransactionID
    ){

        editHaulingTransactionID.value =
        "";

    }


    if(
        editHaulingTransactionBadge
    ){

        editHaulingTransactionBadge.textContent =
        "HLG-0000";

    }


    if(
        editHaulingAffiliate
    ){

        editHaulingAffiliate.value =
        "";

    }


    if(
        editAffiliateText
    ){

        editAffiliateText.textContent =
        "Select Affiliate";

    }


    if(
        editHaulingMemberID
    ){

        editHaulingMemberID.value =
        "";

    }


    if(
        editHaulingDate
    ){

        editHaulingDate.value =
        "";

    }


    if(
        editHaulingDateText
    ){

        editHaulingDateText.textContent =
        "Select Rental Date";

    }


    if(
        editHaulingTractorID
    ){

        editHaulingTractorID.value =
        "";

    }


    if(
        editHaulingTractorNo
    ){

        editHaulingTractorNo.value =
        "";

    }


    if(
        editHaulingTractorText
    ){

        editHaulingTractorText.textContent =
        "Select Primary Tractor";

    }


    if(
        editHaulingPrimaryDriverID
    ){

        editHaulingPrimaryDriverID.value =
        "";

    }


    if(
        editHaulingPrimaryDriverText
    ){

        editHaulingPrimaryDriverText.textContent =
        "Select Primary Driver";

    }


    if(
        editHaulingSecondaryTractorID
    ){

        editHaulingSecondaryTractorID.value =
        "";

    }


    if(
        editHaulingSecondaryTractorNo
    ){

        editHaulingSecondaryTractorNo.value =
        "";

    }


    if(
        editHaulingSecondaryTractorText
    ){

        editHaulingSecondaryTractorText.textContent =
        "No Secondary Tractor";

    }


    if(
        editHaulingSecondaryDriverID
    ){

        editHaulingSecondaryDriverID.value =
        "";

    }


    if(
        editHaulingSecondaryDriverText
    ){

        editHaulingSecondaryDriverText.textContent =
        "No Secondary Driver";

    }


    if(
        editHaulingStatus
    ){

        editHaulingStatus.value =
        "";

    }


    if(
        editHaulingStatusText
    ){

        editHaulingStatusText.textContent =
        "Select Status";

    }


    if(
        editHaulingTotal
    ){

        editHaulingTotal.value =
        "0.00";

    }


    resetEditHaulingSecondaryUI();

}


/* ======================================================
   POPULATE EDIT MODAL
====================================================== */

function populateEditHaulingModal(
    record
){

    resetEditHaulingModal();

    editHaulingLoadedRecord =
    {
        ...record
    };


    /* ==========================================
       RECORD
    ========================================== */

    if(
        editHaulingID
    ){

        editHaulingID.value =
        record.hauling_id
        ??
        "";

    }


    if(
        editHaulingTransactionID
    ){

        editHaulingTransactionID.value =
        record.transaction_id
        ??
        "";

    }


    if(
        editHaulingTransactionBadge
    ){

        editHaulingTransactionBadge.textContent =
        record.transaction_id
        ||
        "HAULING";

    }


    /* ==========================================
       AFFILIATE
    ========================================== */

    const affiliate =
    String(
        record.affiliate
        ??
        ""
    );


    if(
        editHaulingAffiliate
    ){

        editHaulingAffiliate.value =
        affiliate;

    }


    if(
        editAffiliateText
    ){

        editAffiliateText.textContent =
        affiliate
        ||
        "Select Affiliate";

    }


    /* ==========================================
       CUSTOMER
    ========================================== */

    if(
        editHaulingCustomerName
    ){

        editHaulingCustomerName.value =
        record.customer_name
        ??
        "";


        editHaulingCustomerName.disabled =
        false;


        editHaulingCustomerName.placeholder =

        affiliate === "Member"

        ?

        "Search member..."

        :

        "Enter customer name";

    }


    if(
        editHaulingMemberID
    ){

        editHaulingMemberID.value =
        record.member_id
        ??
        "";

    }


    if(
        editHaulingLocation
    ){

        editHaulingLocation.value =
        record.location
        ??
        "";

    }


    if(
        editHaulingCount
    ){

        editHaulingCount.value =
        record.hauling_count
        ??
        "";

    }


    /* ==========================================
       DATE
    ========================================== */

    const rentalDate =
    String(
        record.rental_date
        ??
        ""
    );


    if(
        editHaulingDate
    ){

        editHaulingDate.value =
        rentalDate;

    }


    if(
        editHaulingDateText
    ){

        editHaulingDateText.textContent =

        rentalDate

        ?

        haulingFormatDate(
            rentalDate
        )

        :

        "Select Rental Date";

    }


    /* ==========================================
       PRIMARY TRACTOR
    ========================================== */

    if(
        editHaulingTractorID
    ){

        editHaulingTractorID.value =
        record.tractor_id
        ??
        "";

    }


    if(
        editHaulingTractorNo
    ){

        editHaulingTractorNo.value =
        record.tractor_no
        ??
        "";

    }


    if(
        editHaulingTractorText
    ){

        editHaulingTractorText.textContent =
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
            editHaulingTractorText.textContent.trim()
            ===
            ""
        ){
            editHaulingTractorText.textContent =
            "Select Primary Tractor";
        }

    }


    /* ==========================================
       PRIMARY DRIVER
    ========================================== */

    if(
        editHaulingPrimaryDriverID
    ){

        editHaulingPrimaryDriverID.value =
        record.primary_driver_id
        ??
        "";

    }


    if(
        editHaulingPrimaryDriverText
    ){

        editHaulingPrimaryDriverText.textContent =
        record.operator
        ||
        "Select Primary Driver";

    }


    /* ==========================================
       SECONDARY TRACTOR
    ========================================== */

    const secondaryTractorID =
    record.secondary_tractor_id
    ??
    "";


    const secondaryDriverID =
    record.secondary_driver_id
    ??
    "";


    if(
        editHaulingSecondaryTractorID
    ){

        editHaulingSecondaryTractorID.value =
        secondaryTractorID;

    }


    if(
        editHaulingSecondaryTractorNo
    ){

        editHaulingSecondaryTractorNo.value =
        record.secondary_tractor_no
        ??
        "";

    }


    if(
        editHaulingSecondaryTractorText
    ){

        editHaulingSecondaryTractorText.textContent =
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


        if(
            editHaulingSecondaryTractorText.textContent.trim()
            ===
            ""
        ){

            editHaulingSecondaryTractorText.textContent =
            "No Secondary Tractor";

        }

    }


    /* ==========================================
       SECONDARY DRIVER
    ========================================== */

    if(
        editHaulingSecondaryDriverID
    ){

        editHaulingSecondaryDriverID.value =
        secondaryDriverID;

    }


    if(
        editHaulingSecondaryDriverText
    ){

        editHaulingSecondaryDriverText.textContent =
        record.secondary_operator
        ||
        "No Secondary Driver";

    }


    /* ==========================================
       OPEN SECONDARY AREA WHEN RECORD HAS ONE
    ========================================== */

    if(
        String(
            secondaryTractorID
        ) !== ""
        ||
        String(
            secondaryDriverID
        ) !== ""
    ){

        openEditHaulingSecondaryUI();

    }
    else{

        resetEditHaulingSecondaryUI();

    }


    /* ==========================================
       AMOUNT
    ========================================== */

    if(
        editHaulingPrice
    ){

        editHaulingPrice.value =
        editHaulingMoneyValue(
            record.price
        );

    }


    if(
        editHaulingDiscount
    ){

        editHaulingDiscount.value =
        editHaulingMoneyValue(
            record.discounted_price
        );


        editHaulingDiscount.disabled =
        false;

    }


    if(
        editHaulingTotal
    ){

        editHaulingTotal.value =
        editHaulingMoneyValue(
            record.total_price
        );

    }


    /* ==========================================
       STATUS
    ========================================== */

    const rentalStatus =
    String(
        record.status
        ??
        ""
    );


    if(
        editHaulingStatus
    ){

        editHaulingStatus.value =
        rentalStatus;

    }


    if(
        editHaulingStatusText
    ){

        editHaulingStatusText.textContent =
        rentalStatus
        ||
        "Select Status";

    }


    /* ==========================================
    APPLY EDIT LIFECYCLE RULES
    ========================================== */

    applyEditHaulingLifecycle(
        record
    );

}


/* ======================================================
   LOAD EDIT RECORD
====================================================== */

async function loadHaulingForEdit(
    haulingID
){

    const numericID =
    parseInt(
        haulingID,
        10
    );


    if(
        !numericID
    ){

        Swal.fire({

            icon:
            "error",

            title:
            "Invalid Rental",

            text:
            "Unable to identify the selected Hauling rental.",

            confirmButtonColor:
            "#B71C1C"

        });


        return;

    }


    try{

        const response =
        await fetch(

            "get_hauling.php?id="
            +
            encodeURIComponent(
                numericID
            ),

            {
                cache:
                "no-store"
            }

        );


        let result =
        null;


        try{

            result =
            await response.json();

        }
        catch(
            jsonError
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

                result?.message
                ||
                "Unable to load the selected Hauling rental."

            );

        }


        populateEditHaulingModal(
            result.data
        );


        if(
            !editHaulingModalElement
        ){

            throw new Error(
                "Edit modal was not found."
            );

        }


        if(
            typeof bootstrap
            ===
            "undefined"
            ||
            !bootstrap.Modal
        ){

            throw new Error(
                "Bootstrap modal is unavailable."
            );

        }


        const editModal =
        bootstrap.Modal
        .getOrCreateInstance(
            editHaulingModalElement
        );


        editModal.show();

    }
    catch(
        error
    ){

        console.error(
            "LOAD HAULING EDIT ERROR:",
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
            "Unable to load the Hauling rental.",

            confirmButtonColor:
            "#B71C1C"

        });

    }

}

/* ======================================================
   SAVE EDIT HAULING
====================================================== */

if(
    updateHaulingBtn
    &&
    editHaulingForm
){

    updateHaulingBtn
    .addEventListener(
        "click",
        async function(){

            /* ==========================================
               PREVENT DOUBLE CLICK / LOCKED RECORD
            ========================================== */

            if(
                updateHaulingBtn.disabled
            ){

                return;

            }


            const haulingID =
            parseInt(
                editHaulingID?.value,
                10
            )
            ||
            0;


            const rentalDate =
            String(
                editHaulingDate?.value
                ||
                ""
            )
            .trim();


            const primaryTractorID =
            parseInt(
                editHaulingTractorID?.value,
                10
            )
            ||
            0;


            const primaryDriverID =
            parseInt(
                editHaulingPrimaryDriverID?.value,
                10
            )
            ||
            0;


            const secondaryTractorID =
            String(
                editHaulingSecondaryTractorID?.value
                ||
                ""
            )
            .trim();


            const secondaryDriverID =
            String(
                editHaulingSecondaryDriverID?.value
                ||
                ""
            )
            .trim();


            const currentStatus =
            String(
                editHaulingStatus?.value
                ||
                ""
            )
            .trim();

            const originalRentalDate =
            String(
                editHaulingLoadedRecord?.rental_date
                ||
                ""
            )
            .trim();


            const originalStatus =
            String(
                editHaulingLoadedRecord?.status
                ||
                ""
            )
            .trim();


            const originalPrimaryTractorID =
            String(
                editHaulingLoadedRecord?.tractor_id
                ??
                ""
            );


            const originalPrimaryDriverID =
            String(
                editHaulingLoadedRecord?.primary_driver_id
                ??
                ""
            );


            const todayKey =
            getHaulingTodayKey();


            const wasMissed =
            originalStatus === "Pending"
            &&
            originalRentalDate !== ""
            &&
            originalRentalDate < todayKey;


            const wasDelayed =
            originalStatus === "Ongoing"
            &&
            originalRentalDate !== ""
            &&
            originalRentalDate < todayKey;


            const dateWasChanged =
            rentalDate !== originalRentalDate;


            const primaryWasChanged =
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


            const isFlaggedReschedule =
            dateWasChanged
            &&
            (
                wasMissed
                ||
                wasDelayed
            );


            /* ==========================================
               BASIC VALIDATION
            ========================================== */

            if(
                haulingID <= 0
            ){

                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Invalid Rental",

                    text:
                    "Unable to identify the selected Hauling rental.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            if(
                currentStatus === "Done"
            ){

                Swal.fire({

                    icon:
                    "info",

                    title:
                    "Rental Completed",

                    text:
                    "Completed rentals can no longer be edited.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            if(
                rentalDate === ""
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Rental Date Required",

                    text:
                    "Please select a Rental Date.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }


            /* ==========================================
               PAST DATE PROTECTION
            ========================================== */

            if(
                currentStatus === "Pending"
                &&
                rentalDate < getHaulingTodayKey()
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Invalid Rental Date",

                    text:
                    "A Pending rental can only be rescheduled for today or a future date.",

                    confirmButtonColor:
                    "#8CA15B"

                });


                return;

            }

                        /* ==========================================
               DELAYED RESCHEDULE RULES
            ========================================== */

            if(
                wasDelayed
                &&
                dateWasChanged
                &&
                rentalDate < todayKey
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Invalid Reschedule Date",

                    text:
                    "A Delayed rental can only be rescheduled for today or a future date.",

                    confirmButtonColor:
                    "#8CA15B"

                });

                return;
            }


            /*
               A Delayed Ongoing record may still have its
               Secondary Assignment adjusted without being
               rescheduled.

               But Primary Assignment changes require an
               actual new Rental Date.
            */

            if(
                wasDelayed
                &&
                !dateWasChanged
                &&
                primaryWasChanged
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Reschedule Date Required",

                    text:
                    "Change the Rental Date first before changing the Primary Assignment of a Delayed rental.",

                    confirmButtonColor:
                    "#8CA15B"

                });

                return;
            }


            /* ==========================================
               PRIMARY ASSIGNMENT REQUIRED
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
               SECONDARY MUST BE A PAIR
            ========================================== */

            const hasSecondaryTractor =
            secondaryTractorID !== "";


            const hasSecondaryDriver =
            secondaryDriverID !== "";


            if(
                hasSecondaryTractor
                !==
                hasSecondaryDriver
            ){

                Swal.fire({

                    icon:
                    "warning",

                    title:
                    "Incomplete Secondary Assignment",

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
                String(
                    primaryTractorID
                )
                ===
                secondaryTractorID
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


            /* ==========================================
               DUPLICATE DRIVER
            ========================================== */

            if(
                hasSecondaryDriver
                &&
                String(
                    primaryDriverID
                )
                ===
                secondaryDriverID
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


            /* ==========================================
               CONFIRM UPDATE
            ========================================== */

            let confirmTitle =
            "Save Changes?";


            let confirmText =
            "The updated schedule and assignment will be saved.";


            if(
                isFlaggedReschedule
                &&
                wasMissed
            ){

                confirmTitle =
                "Reschedule Missed Rental?";

                confirmText =
                "The rental will be moved to the new date and will remain Pending.";

            }
            else if(
                isFlaggedReschedule
                &&
                wasDelayed
            ){

                confirmTitle =
                "Reschedule Delayed Rental?";

                confirmText =
                "The rental will be moved to the new date and its status will reset from Ongoing to Pending.";

            }
            else if(
                wasDelayed
                &&
                !dateWasChanged
            ){

                confirmText =
                "The rental will remain Delayed and Ongoing. Only the allowed assignment changes will be saved.";

            }
            else if(
                currentStatus === "Ongoing"
            ){

                confirmText =
                "This rental is already Ongoing. Only the Secondary Assignment changes will be saved.";

            }

            const confirmResult =
            await Swal.fire({

                icon:
                "question",

                title:
                confirmTitle,

                text:
                confirmText,

                showCancelButton:
                true,

                confirmButtonText:
                "Save Changes",

                cancelButtonText:
                "Cancel",

                confirmButtonColor:
                "#8CA15B",

                cancelButtonColor:
                "#6C757D",

                reverseButtons:
                true

            });


            if(
                !confirmResult.isConfirmed
            ){

                return;

            }


            /* ==========================================
               BUILD REQUEST

               ONLY editable schedule/assignment values
               are sent.
            ========================================== */

            const formData =
            new FormData();


            const editHaulingCsrfToken =
            String(
                document
                .getElementById(
                    "editHaulingCsrfToken"
                )
                ?.value
                ||
                ""
            )
            .trim();


            formData.append(
                "csrf_token",
                editHaulingCsrfToken
            );


            formData.append(
                "hauling_id",
                haulingID
            );


            formData.append(
                "rental_date",
                rentalDate
            );


            formData.append(
                "tractor_id",
                primaryTractorID
            );


            formData.append(
                "primary_driver_id",
                primaryDriverID
            );


            formData.append(
                "secondary_tractor_id",
                secondaryTractorID
            );


            formData.append(
                "secondary_driver_id",
                secondaryDriverID
            );


            /* ==========================================
               LOADING STATE
            ========================================== */

            const originalButtonText =
            updateHaulingBtn.textContent;


            updateHaulingBtn.disabled =
            true;


            updateHaulingBtn.textContent =
            "SAVING...";


            try{

                const response =
                await fetch(

                    "update/update_hauling.php",

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

                        "Unable to update the Hauling rental."

                    );

                }


                /* ======================================
                   SUCCESS
                ====================================== */

                await Swal.fire({

                    icon:
                    "success",

                    title:
                    "Rental Updated",

                    text:
                    "The Hauling schedule and assignment were updated successfully.",

                    confirmButtonText:
                    "OK",

                    confirmButtonColor:
                    "#8CA15B"

                });


                /* ======================================
                   PRESERVE PAGE POSITION AFTER REFRESH
                ====================================== */

                saveHaulingLedgerPosition();


                window.location.reload();

            }
            catch(
                error
            ){

                console.error(

                    "UPDATE HAULING ERROR:",

                    error

                );


                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Unable to Save Changes",

                    text:
                    error.message

                    ||

                    "Unable to update the Hauling rental.",

                    confirmButtonColor:
                    "#B71C1C"

                });


                updateHaulingBtn.disabled =
                false;


                updateHaulingBtn.textContent =
                originalButtonText;

            }

        }
    );

}


/* ======================================================
   PRESERVE HAULING LEDGER POSITION AFTER UPDATE
====================================================== */

const haulingLedgerBody =
document.querySelector(
    ".hauling-ledger-body"
);


const haulingLedgerScrollKey =
"haulingLedgerScrollTop";


function saveHaulingLedgerPosition(){

    if(
        !haulingLedgerBody
    ){

        return;
    }


    sessionStorage.setItem(

        haulingLedgerScrollKey,

        String(
            haulingLedgerBody.scrollTop
        )

    );

}


function restoreHaulingLedgerPosition(){

    if(
        !haulingLedgerBody
    ){

        return;
    }


    const savedPosition =
    sessionStorage.getItem(
        haulingLedgerScrollKey
    );


    if(
        savedPosition === null
    ){

        return;
    }


    haulingLedgerBody.scrollTop =
    Number(
        savedPosition
    )
    ||
    0;


    sessionStorage.removeItem(
        haulingLedgerScrollKey
    );

}


restoreHaulingLedgerPosition();

/* ======================================================
   EDIT BUTTON
====================================================== */

document.addEventListener(
    "click",
    function(event){

        const editButton =
        event.target.closest(
            ".hauling-edit-btn"
        );


        if(
            !editButton
        ){

            return;

        }


        event.preventDefault();


        const haulingID =
        editButton.dataset.id
        ||
        "";


        loadHaulingForEdit(
            haulingID
        );

    }
);


/* ======================================================
   RESET AFTER EDIT MODAL CLOSE
====================================================== */

editHaulingModalElement
?.addEventListener(
    "hidden.bs.modal",
    function(){

        resetEditHaulingModal();

    }
);