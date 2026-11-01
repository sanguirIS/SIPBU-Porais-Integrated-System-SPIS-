<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}


/* ======================================================
   MANILA TIME
====================================================== */

date_default_timezone_set(
    'Asia/Manila'
);


/* ======================================================
   DATABASE
====================================================== */

require_once '../../db.php';


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* ======================================================
   VERIFY LIVE RENTAL ACCESS
====================================================== */

$userID =
isset(
    $_SESSION['user_id']
)
?
(int)$_SESSION['user_id']
:
0;


if(
    $userID <= 0
){

    http_response_code(
        401
    );

    exit(
        'Your session has expired. Please sign in again.'
    );
}


$userStmt =
mysqli_prepare(
    $conn,
    "
    SELECT
        user_id

    FROM users

    WHERE user_id = ?

    AND archived = 0

    AND status = 'Active'

    AND rental_access = 1

    LIMIT 1
    "
);


if(!$userStmt){

    http_response_code(
        500
    );

    exit(
        'Unable to verify Rental access.'
    );
}


mysqli_stmt_bind_param(
    $userStmt,
    'i',
    $userID
);


if(
    !mysqli_stmt_execute(
        $userStmt
    )
){

    mysqli_stmt_close(
        $userStmt
    );

    http_response_code(
        500
    );

    exit(
        'Unable to verify Rental access.'
    );
}


mysqli_stmt_store_result(
    $userStmt
);


$hasRentalAccess =
mysqli_stmt_num_rows(
    $userStmt
)
===
1;


mysqli_stmt_close(
    $userStmt
);


if(!$hasRentalAccess){

    http_response_code(
        403
    );

    exit(
        'You do not have permission to access Rental.'
    );
}


/* ======================================================
   TRACTOR CSRF TOKEN
====================================================== */

if(
    empty(
        $_SESSION['tractor_csrf_token']
    )
){

    $_SESSION['tractor_csrf_token'] =
    bin2hex(
        random_bytes(32)
    );
}


$tractorCsrfToken =
(string)$_SESSION['tractor_csrf_token'];


/* ======================================================
   SYNC TRACTOR STATUS
====================================================== */

require_once 'tractor_status_sync.php';


/* ======================================================
   SIDEBAR
====================================================== */

require_once '../../includes/sidebar.php';


/* ======================================================
   LOAD ACTIVE TRACTORS
====================================================== */

$tractorQuery =
mysqli_query(
    $conn,
    "
    SELECT

        tractor_id,

        tractor_no,

        tractor_name,

        tractor_type,

        status

    FROM tractors

    WHERE archived = 0

    ORDER BY tractor_id DESC
    "
);

?>


<link
rel="stylesheet"
href="../../css/tractor/tractor.css?v=<?= time() ?>">


<!-- =====================================================
     HEADER
====================================================== -->

<div class="tractor-page-header">


    <div class="tractor-title-area">

        <h2>
            RENTAL - TRACTOR REGISTRY
        </h2>

    </div>


    <button
    type="button"
    class="btn-add-tractor"
    data-bs-toggle="modal"
    data-bs-target="#addTractorModal">

        <i class="bi bi-truck-front-fill"></i>

        <span>
            ADD TRACTOR
        </span>

    </button>


</div>


<hr>


<!-- =====================================================
     TOOLBAR
====================================================== -->

<div class="tractor-toolbar">


    <!-- LEFT TOOLBAR -->

    <div class="tractor-toolbar-left">


        <!-- BACK BUTTON -->

        <a
        href="../rental.php"
        class="tractor-back-btn"
        aria-label="Back to Rental"
        title="Back to Rental">

            <i class="bi bi-arrow-left"></i>

        </a>


        <!-- SEARCH -->

        <div class="tractor-search-box">

            <i class="bi bi-search"></i>

            <input
            type="text"
            id="tractorSearch"
            placeholder="Search tractor">

        </div>


    </div>


    <!-- FILTERS -->

    <div class="tractor-filter-area">


        <select
        id="tractorTypeFilter"
        class="tractor-filter">

            <option value="all">
                All Types
            </option>

            <option value="Hauling">
                Hauling
            </option>

            <option value="Rotor">
                Rotor
            </option>

            <option value="Reaper">
                Reaper
            </option>

        </select>


        <select
        id="tractorStatusFilter"
        class="tractor-filter">

            <option value="all">
                All Status
            </option>

            <option value="Available">
                Available
            </option>

            <option value="Reserved">
                Reserved
            </option>

            <option value="In Use">
                In Use
            </option>

            <option value="Maintenance">
                Maintenance
            </option>

        </select>


    </div>


</div>


<!-- =====================================================
     TRACTOR LEDGER
====================================================== -->

<div class="tractor-ledger">


    <!-- HEADER -->

    <div class="tractor-ledger-header">

        <div>
            TRACTOR NO.
        </div>

        <div>
            TRACTOR NAME
        </div>

        <div>
            TYPE
        </div>

        <div class="tractor-status-header">

            STATUS

            <span class="tractor-info-wrap">

                <i class="bi bi-info-circle-fill tractor-info-icon"></i>

                <span class="tractor-info-tooltip">

                    <strong>Reserved</strong> means the tractor has a Pending rental scheduled today.

                    <br><br>

                    <strong>In Use</strong> means an Ongoing rental service is currently using the tractor.

                </span>

            </span>

        </div>

        <div>
            ACTION
        </div>

    </div>


    <!-- BODY -->

    <div
    class="tractor-ledger-body"
    id="tractorLedgerBody">


        <?php

        $tractorCount = 0;


        if(
            $tractorQuery &&
            mysqli_num_rows($tractorQuery) > 0
        ){

            while(
                $tractor =
                mysqli_fetch_assoc($tractorQuery)
            ){

                $tractorCount++;


                $statusClass = '';


                switch($tractor['status']){

                    case 'Available':

                        $statusClass =
                        'tractor-status-available';

                        break;


                    case 'Reserved':

                        $statusClass =
                        'tractor-status-reserved';

                        break;


                    case 'In Use':

                        $statusClass =
                        'tractor-status-in-use';

                        break;


                    case 'Maintenance':

                        $statusClass =
                        'tractor-status-maintenance';

                        break;

                }

        ?>


        <div
        class="tractor-row"

        data-id="<?= (int)$tractor['tractor_id'] ?>"

        data-type="<?= htmlspecialchars($tractor['tractor_type']) ?>"

        data-status="<?= htmlspecialchars($tractor['status']) ?>"

        data-search="<?= htmlspecialchars(
            strtolower(
                $tractor['tractor_no']
                . ' ' .
                $tractor['tractor_name']
                . ' ' .
                $tractor['tractor_type']
                . ' ' .
                $tractor['status']
            )
        ) ?>">


            <!-- TRACTOR NO -->

            <div class="tractor-number">

                <?= htmlspecialchars(
                    $tractor['tractor_no']
                ) ?>

            </div>


            <!-- NAME -->

            <div class="tractor-name">

                <?= htmlspecialchars(
                    $tractor['tractor_name']
                ) ?>

            </div>


            <!-- TYPE -->

            <div>

                <?php

                $typeBackground = '';
                $typeColor = '';

                switch($tractor['tractor_type']){

                    case 'Hauling':

                        $typeBackground = '#DDE8C5';
                        $typeColor = '#536B2F';

                        break;


                    case 'Rotor':

                        $typeBackground = '#DCEAF5';
                        $typeColor = '#3F6F91';

                        break;


                    case 'Reaper':

                        $typeBackground = '#F4DEC8';
                        $typeColor = '#9A5C28';

                        break;

                }

                ?>

                <span
                class="tractor-type"
                style="
                    background-color: <?= $typeBackground ?>;
                    color: <?= $typeColor ?>;
                ">

                    <?= htmlspecialchars(
                        $tractor['tractor_type']
                    ) ?>

                </span>

            </div>


            <!-- STATUS -->

            <div>

                <?php

                $isAutomaticStatus =

                    in_array(
                        $tractor['status'],
                        [
                            'Reserved',
                            'In Use'
                        ],
                        true
                    );

                ?>


                <?php if($isAutomaticStatus){ ?>


                    <span
                    class="tractor-status <?= $statusClass ?>">

                        <?= htmlspecialchars(
                            $tractor['status']
                        ) ?>

                    </span>


                <?php }else{ ?>


                    <div class="tractor-status-dropdown">


                        <button
                        type="button"
                        class="tractor-status tractor-status-btn <?= $statusClass ?>"
                        data-id="<?= (int)$tractor['tractor_id'] ?>"
                        data-status="<?= htmlspecialchars($tractor['status']) ?>">


                            <span class="tractor-status-text">

                                <?= htmlspecialchars(
                                    $tractor['status']
                                ) ?>

                            </span>


                            <i class="bi bi-chevron-down"></i>


                        </button>


                        <div class="tractor-status-menu">


                            <div
                            class="tractor-status-item"
                            data-value="Available">

                                Available

                            </div>


                            <div
                            class="tractor-status-item"
                            data-value="Maintenance">

                                Maintenance

                            </div>


                        </div>


                    </div>


                <?php } ?>

            </div>


            <!-- ACTION -->

            <div>

                <button
                type="button"
                class="tractor-archive-btn"

                data-id="<?= (int)$tractor['tractor_id'] ?>"

                data-name="<?= htmlspecialchars(
                    $tractor['tractor_name']
                ) ?>"

                data-number="<?= htmlspecialchars(
                    $tractor['tractor_no']
                ) ?>">

                    <i class="bi bi-archive-fill"></i>

                </button>

            </div>


        </div>


        <?php

            }

        }

        ?>


        <!-- NO TRACTORS -->

        <div
        id="tractorEmptyState"
        class="tractor-empty-state"
        style="<?= $tractorCount > 0 ? 'display:none;' : '' ?>">


            <i class="bi bi-truck-front-fill"></i>


            <span>
                No registered tractors found.
            </span>


        </div>


        <!-- NO SEARCH RESULTS -->

        <div
        id="tractorNoResults"
        class="tractor-empty-state"
        style="display:none;">


            <i class="bi bi-search"></i>


            <span>
                No matching tractors found.
            </span>


        </div>


    </div>


</div>


<!-- =====================================================
     ADD TRACTOR MODAL
====================================================== -->

<div
class="modal fade"
id="addTractorModal"
tabindex="-1"
aria-hidden="true">


    <div
    class="modal-dialog
    modal-lg
    modal-dialog-centered">


        <div class="modal-content tractor-window">


            <!-- MODAL HEADER -->

            <div class="tractor-modal-header">


                <h1>
                    ADD TRACTOR
                </h1>


                <button
                type="button"
                class="tractor-close-btn"
                data-bs-dismiss="modal">

                    ×

                </button>


            </div>


            <hr>


            <!-- FORM -->

<form id="addTractorForm">


                <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $tractorCsrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">


                <div class="row">


                    <!-- TRACTOR NUMBER -->

                    <div class="col-md-6 mb-4">


                        <label>
                            Tractor No.
                        </label>


                        <input
                        type="text"
                        id="tractorNo"
                        name="tractor_no"
                        class="tractor-input"
                        autocomplete="off"
                        placeholder="Enter tractor number"
                        required>


                    </div>


                    <!-- TRACTOR NAME -->

                    <div class="col-md-6 mb-4">


                        <label>
                            Tractor Name
                        </label>


                        <input
                        type="text"
                        id="tractorName"
                        name="tractor_name"
                        class="tractor-input"
                        autocomplete="off"
                        placeholder="Enter tractor name"
                        required>


                    </div>


                    <!-- TRACTOR TYPE -->

                    <div class="col-md-12 mb-4">


                        <label>
                            Tractor Type
                        </label>


                        <div class="tractor-type-dropdown">

                            <input
                            type="hidden"
                            id="tractorType"
                            name="tractor_type">

                            <button
                            type="button"
                            id="tractorTypeDropdown"
                            class="tractor-input tractor-dropdown-btn">

                                <span id="tractorTypeText">

                                    Select Tractor Type

                                </span>

                                <i class="bi bi-chevron-down"></i>

                            </button>

                            <div
                            id="tractorTypeMenu"
                            class="tractor-dropdown-menu">

                                <button
                                type="button"
                                class="tractor-dropdown-option"
                                data-value="Hauling">

                                    Hauling

                                </button>

                                <button
                                type="button"
                                class="tractor-dropdown-option"
                                data-value="Rotor">

                                    Rotor

                                </button>

                                <button
                                type="button"
                                class="tractor-dropdown-option"
                                data-value="Reaper">

                                    Reaper

                                </button>

                            </div>

                        </div>

                    </div>

                    <!-- DEFAULT STATUS -->

                    <div class="col-md-12">


                        <div class="tractor-default-status">


                            <i class="bi bi-info-circle-fill"></i>


                            <span>

                                New tractors will automatically be registered as

                                <strong>
                                    Available
                                </strong>.

                            </span>


                        </div>


                    </div>


                </div>


                <!-- SAVE -->

                <div class="tractor-save-container">


                    <button
                    type="button"
                    id="saveTractorBtn"
                    class="tractor-save-btn">

                        SAVE

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<!-- =====================================================
     SEARCH AND FILTER
====================================================== -->

<script>


const tractorCsrfToken =
<?= json_encode(
    $tractorCsrfToken,
    JSON_UNESCAPED_SLASHES
); ?>;


const tractorSearch =
document.getElementById(
    "tractorSearch"
);


const tractorTypeFilter =
document.getElementById(
    "tractorTypeFilter"
);


const tractorStatusFilter =
document.getElementById(
    "tractorStatusFilter"
);


const tractorEmptyState =
document.getElementById(
    "tractorEmptyState"
);


const tractorNoResults =
document.getElementById(
    "tractorNoResults"
);


/* ======================================================
   FILTER TRACTORS
====================================================== */

function filterTractors(){


    const keyword =

    tractorSearch
    .value
    .trim()
    .toLowerCase();


    const selectedType =
    tractorTypeFilter.value;


    const selectedStatus =
    tractorStatusFilter.value;


    const rows =
    document.querySelectorAll(
        ".tractor-row"
    );


    let visibleCount =
    0;


    rows.forEach(function(row){


        const searchText =
        (
            row.dataset.search ||
            ""
        )
        .toLowerCase();


        const type =
        row.dataset.type || "";


        const status =
        row.dataset.status || "";


        const matchSearch =

        keyword === ""

        ||

        searchText.includes(
            keyword
        );


        const matchType =

        selectedType === "all"

        ||

        type === selectedType;


        const matchStatus =

        selectedStatus === "all"

        ||

        status === selectedStatus;


        const visible =

        matchSearch

        &&

        matchType

        &&

        matchStatus;


        row.style.display =

        visible

        ?

        "grid"

        :

        "none";


        if(visible){

            visibleCount++;

        }


    });


    /*
    No registered tractor at all
    */

    if(rows.length === 0){


        tractorEmptyState.style.display =
        "flex";


        tractorNoResults.style.display =
        "none";


        return;

    }


    tractorEmptyState.style.display =
    "none";


    /*
    Tractors exist but filters return nothing
    */

    tractorNoResults.style.display =

    visibleCount === 0

    ?

    "flex"

    :

    "none";


}


/* ======================================================
   FILTER EVENTS
====================================================== */

tractorSearch.addEventListener(
    "input",
    filterTractors
);


tractorTypeFilter.addEventListener(
    "change",
    filterTractors
);


tractorStatusFilter.addEventListener(
    "change",
    filterTractors
);

/* ======================================================
   TRACTOR TYPE CUSTOM DROPDOWN
====================================================== */

const tractorTypeDropdown =
document.getElementById(
    "tractorTypeDropdown"
);


const tractorTypeMenu =
document.getElementById(
    "tractorTypeMenu"
);


const tractorTypeInput =
document.getElementById(
    "tractorType"
);


const tractorTypeText =
document.getElementById(
    "tractorTypeText"
);


tractorTypeDropdown.addEventListener(
    "click",
    function(event){


        event.stopPropagation();


        tractorTypeMenu
        .classList
        .toggle(
            "show"
        );


        tractorTypeDropdown
        .classList
        .toggle(
            "open"
        );


    }
);


document
.querySelectorAll(
    ".tractor-dropdown-option"
)
.forEach(function(option){


    option.addEventListener(
        "click",
        function(){


            const value =
            this.dataset.value;


            tractorTypeInput.value =
            value;


            tractorTypeText.textContent =
            value;


            tractorTypeMenu
            .classList
            .remove(
                "show"
            );


            tractorTypeDropdown
            .classList
            .remove(
                "open"
            );


        }
    );


});


document.addEventListener(
    "click",
    function(event){


        if(
            !event.target.closest(
                ".tractor-type-dropdown"
            )
        ){


            tractorTypeMenu
            .classList
            .remove(
                "show"
            );


            tractorTypeDropdown
            .classList
            .remove(
                "open"
            );


        }


    }
);


/* ======================================================
   ADD TRACTOR VALIDATION
====================================================== */

const addTractorForm =
document.getElementById(
    "addTractorForm"
);


const saveTractorBtn =
document.getElementById(
    "saveTractorBtn"
);


saveTractorBtn.addEventListener(
    "click",
    function(){


        const tractorNo =
        document
        .getElementById(
            "tractorNo"
        )
        .value
        .trim();


        const tractorName =
        document
        .getElementById(
            "tractorName"
        )
        .value
        .trim();


        const tractorType =
        document
        .getElementById(
            "tractorType"
        )
        .value;


        /* TRACTOR NUMBER */

        if(
            tractorNo === ""
        ){


            Swal.fire({

                icon:"warning",

                title:
                "Tractor Number Required",

                text:
                "Please enter the tractor number.",

                confirmButtonColor:
                "#8CA15B"

            });


            document
            .getElementById(
                "tractorNo"
            )
            .focus();


            return;

        }


        /* NAME */

        if(
            tractorName === ""
        ){


            Swal.fire({

                icon:"warning",

                title:
                "Tractor Name Required",

                text:
                "Please enter the tractor name.",

                confirmButtonColor:
                "#8CA15B"

            });


            document
            .getElementById(
                "tractorName"
            )
            .focus();


            return;

        }


        /* TYPE */

        if(
            tractorType === ""
        ){


            Swal.fire({

                icon:"warning",

                title:
                "Tractor Type Required",

                text:
                "Please select the tractor type.",

                confirmButtonColor:
                "#8CA15B"

            });


            return;

        }


        /* CONFIRM */

        Swal.fire({

            icon:"question",

            title:
            "Register Tractor?",

            text:
            "Are you sure you want to register this tractor?",

            showCancelButton:true,

            confirmButtonText:
            "Yes, Register",

            cancelButtonText:
            "Cancel",

            reverseButtons:true,

            confirmButtonColor:
            "#8CA15B",

            cancelButtonColor:
            "#B71C1C"

        })

        .then(function(result){


            if(
                !result.isConfirmed
            ){

                return;

            }


            const formData =
            new FormData(
                addTractorForm
            );


            fetch(

                "save_tractor.php",

                {

                    method:"POST",

                    body:formData

                }

            )

            .then(
                response =>
                response.text()
            )

            .then(function(data){


                data =
                data.trim();


                if(
                    data === "success"
                ){


                    Swal.fire({

                        icon:"success",

                        title:
                        "Tractor registered successfully.",

                        showConfirmButton:
                        false,

                        timer:1500

                    })

                    .then(function(){

                        window.location.reload();

                    });


                    return;

                }


                Swal.fire({

                    icon:"error",

                    title:
                    "Save Failed",

                    text:

                    data

                    ||

                    "Unable to register tractor.",

                    confirmButtonColor:
                    "#B71C1C"

                });


            })

            .catch(function(){


                Swal.fire({

                    icon:"error",

                    title:
                    "Save Failed",

                    text:
                    "Unable to register tractor. Please try again.",

                    confirmButtonColor:
                    "#B71C1C"

                });


            });


        });


    }
);

/* ======================================================
   TRACTOR STATUS DROPDOWN
====================================================== */

document
.querySelectorAll(
    ".tractor-status-btn"
)
.forEach(
function(button){


    button.addEventListener(

        "click",

        function(event){


            event.stopPropagation();


            const currentMenu =
            this
            .closest(
                ".tractor-status-dropdown"
            )
            .querySelector(
                ".tractor-status-menu"
            );


            document
            .querySelectorAll(
                ".tractor-status-menu"
            )
            .forEach(
            function(menu){


                if(
                    menu !==
                    currentMenu
                ){

                    menu
                    .classList
                    .remove(
                        "show"
                    );

                }

            });


            const currentRow =
            this.closest(
                ".tractor-row"
            );


            document
            .querySelectorAll(
                ".tractor-row"
            )
            .forEach(
            function(row){


                if(
                    row !==
                    currentRow
                ){

                    row
                    .classList
                    .remove(
                        "status-menu-open"
                    );

                }

            });


            const willOpen =
            !currentMenu
            .classList
            .contains(
                "show"
            );


            /* ================================================
            DECIDE IF MENU SHOULD OPEN UP OR DOWN
            ================================================ */

            currentMenu
            .classList
            .remove(
                "open-up"
            );


            if(willOpen){

                const ledgerBody =
                this.closest(
                    ".tractor-ledger-body"
                );


                if(ledgerBody){

                    const buttonRect =
                    this.getBoundingClientRect();

                    const ledgerRect =
                    ledgerBody.getBoundingClientRect();


                    /*
                    Approximate dropdown height + safe spacing.
                    */

                    const requiredSpace = 125;

                    const spaceBelow =
                    ledgerRect.bottom
                    -
                    buttonRect.bottom;


                    if(
                        spaceBelow <
                        requiredSpace
                    ){

                        currentMenu
                        .classList
                        .add(
                            "open-up"
                        );
                    }
                }
            }


            currentMenu
            .classList
            .toggle(
                "show"
            );


            if(willOpen){

                currentRow
                ?.classList
                .add(
                    "status-menu-open"
                );

            }

            else{

                currentRow
                ?.classList
                .remove(
                    "status-menu-open"
                );

            }

        }

    );

});


/* ======================================================
   ESCAPE HTML

   Rental details returned by PHP are placed inside
   SweetAlert HTML, so escape them first.
====================================================== */

function escapeTractorHTML(
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
   FORMAT RENTAL DATE
====================================================== */

function formatTractorRentalDate(
    dateKey
){

    const parts =
    String(
        dateKey
        ||
        ""
    )
    .split("-");


    if(
        parts.length !==
        3
    ){

        return dateKey;

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


    return date
    .toLocaleDateString(

        "en-US",

        {

            month:
            "short",

            day:
            "numeric",

            year:
            "numeric"

        }

    );

}


/* ======================================================
   SEND TRACTOR STATUS REQUEST
====================================================== */

async function sendTractorStatusRequest(

    tractorID,

    status,

    options = {}

){

    const formData =
    new FormData();


    formData.append(
        "tractor_id",
        tractorID
    );


    formData.append(
        "status",
        status
    );


    formData.append(
        "csrf_token",
        tractorCsrfToken
    );


    if(
        options.checkOnly
    ){

        formData.append(
            "check_only",
            "1"
        );

    }


    if(
        options.confirmMaintenance
    ){

        formData.append(
            "confirm_maintenance",
            "1"
        );

    }


    const response =
    await fetch(

        "update_status_tractor.php",

        {

            method:
            "POST",

            body:
            formData

        }

    );


    return (
        await response.text()
    )
    .trim();

}


/* ======================================================
   SHOW UPCOMING RENTAL WARNING
====================================================== */

async function confirmTractorMaintenance(

    tractorID

){

    let checkResponse;


    try{

        checkResponse =
        await sendTractorStatusRequest(

            tractorID,

            "Maintenance",

            {

                checkOnly:
                true

            }

        );

    }

    catch(error){


        console.error(
            "MAINTENANCE CHECK ERROR:",
            error
        );


        Swal.fire({

            icon:
            "error",

            title:
            "Unable to Check Rentals",

            text:
            "Unable to check upcoming rentals for this tractor.",

            confirmButtonColor:
            "#B71C1C"

        });


        return false;

    }


    /* ==============================================
       NO FUTURE RENTALS
    ============================================== */

    if(
        checkResponse ===
        "maintenance_clear"
    ){

        const result =
        await Swal.fire({

            icon:
            "question",

            title:
            "Set Tractor to Maintenance?",

            text:
            "This tractor will become unavailable for new rentals until Maintenance is removed.",

            showCancelButton:
            true,

            confirmButtonText:
            "Yes, Set Maintenance",

            cancelButtonText:
            "Cancel",

            reverseButtons:
            true,

            confirmButtonColor:
            "#B71C1C",

            cancelButtonColor:
            "#6C757D"

        });


        return (
            result.isConfirmed
            ===
            true
        );

    }


    /* ==============================================
    FUTURE RENTALS FOUND
    ============================================== */

    if(
        checkResponse
        .startsWith(
            "maintenance_warning:"
        )
    ){

        let warningData;


        try{

            warningData =
            JSON.parse(

                checkResponse.slice(
                    "maintenance_warning:"
                    .length
                )

            );

        }

        catch(error){


            console.error(
                "MAINTENANCE WARNING PARSE ERROR:",
                error
            );


            Swal.fire({

                icon:
                "error",

                title:
                "Unable to Read Rental Warning",

                text:
                "The tractor status was not changed.",

                confirmButtonColor:
                "#B71C1C"

            });


            return false;

        }


        const rentals =
        Array.isArray(
            warningData.rentals
        )
        ?
        warningData.rentals
        :
        [];


        let rentalHTML =
        "";


        rentals.forEach(
        function(rental){


            const date =
            formatTractorRentalDate(
                rental.rental_date
            );


            rentalHTML +=

            '<div style="'
            +
            'padding:9px 11px;'
            +
            'margin-bottom:7px;'
            +
            'background:#fff5f5;'
            +
            'border-left:4px solid #B71C1C;'
            +
            'border-radius:7px;'
            +
            'text-align:left;'
            +
            '">'
            +

            '<strong>'
            +
            escapeTractorHTML(
                date
            )
            +
            '</strong>'
            +

            '<br>'
            +

            '<span style="'
            +
            'font-size:13px;'
            +
            'color:#666;'
            +
            '">'
            +

            escapeTractorHTML(
                rental.transaction_id
            )
            +

            ' • '
            +

            escapeTractorHTML(
                rental.status
            )
            +

            '</span>'
            +

            '</div>';

        });


        const tractorLabel =

        escapeTractorHTML(
            warningData.tractor_no
        )

        +

        " - "

        +

        escapeTractorHTML(
            warningData.tractor_name
        );


        const tractorType =

        escapeTractorHTML(
            warningData.tractor_type
        );


        const rentalCount =
        rentals.length;


        const result =
        await Swal.fire({

            icon:
            "warning",

            title:
            "Upcoming Rentals Found",

            html:

            '<div style="'
            +
            'text-align:left;'
            +
            'line-height:1.45;'
            +
            '">'
            +

            '<p style="margin-bottom:10px;">'
            +

            '<strong>'
            +
            tractorLabel
            +
            '</strong>'
            +

            ' has '

            +

            '<strong>'
            +
            rentalCount
            +
            '</strong> upcoming '

            +

            '<strong>'
            +
            tractorType
            +
            '</strong> rental'
            +

            (
                rentalCount === 1
                ?
                ''
                :
                's'
            )

            +

            ':'

            +

            '</p>'

            +

            /* ==========================================
            SCROLLABLE RENTAL LIST ONLY
            ========================================== */

            '<div style="'
            +
            'max-height:220px;'
            +
            'overflow-y:auto;'
            +
            'overflow-x:hidden;'
            +
            'padding-right:6px;'
            +
            'margin-bottom:12px;'
            +
            '">'
            +

            rentalHTML

            +

            '</div>'

            +

            '<div style="'
            +
            'padding:10px 12px;'
            +
            'background:#fff8e1;'
            +
            'border-radius:8px;'
            +
            'font-size:14px;'
            +
            '">'
            +

            'These reservations will remain active. '
            +

            'Setting this tractor to Maintenance will prevent it from being used until Maintenance is removed.'

            +

            '</div>'

            +

            '</div>',

            showCancelButton:
            true,

            confirmButtonText:
            "Proceed",

            cancelButtonText:
            "Cancel",

            reverseButtons:
            true,

            confirmButtonColor:
            "#B71C1C",

            cancelButtonColor:
            "#6C757D",

            heightAuto:
            false

        });


        return (
            result.isConfirmed
            ===
            true
        );

    }


    /* ==============================================
       PHP RETURNED AN ERROR
    ============================================== */

    Swal.fire({

        icon:
        "error",

        title:
        "Unable to Check Rentals",

        text:
        checkResponse
        ||
        "Unable to check upcoming rentals.",

        confirmButtonColor:
        "#B71C1C"

    });


    return false;

}


/* ======================================================
   SELECT TRACTOR STATUS
====================================================== */

document
.querySelectorAll(
    ".tractor-status-item"
)
.forEach(
function(item){


    item.addEventListener(

        "click",

        async function(event){


            event.stopPropagation();


            const dropdown =
            this.closest(
                ".tractor-status-dropdown"
            );


            const button =
            dropdown
            .querySelector(
                ".tractor-status-btn"
            );


            const menu =
            dropdown
            .querySelector(
                ".tractor-status-menu"
            );


            const tractorID =
            button.dataset.id;


            const currentStatus =
            button.dataset.status;


            const newStatus =
            this.dataset.value;


            menu
            ?.classList
            .remove(
                "show"
            );


            button
            .closest(
                ".tractor-row"
            )
            ?.classList
            .remove(
                "status-menu-open"
            );


            if(
                currentStatus ===
                newStatus
            ){

                return;

            }


            let confirmed =
            false;


            /* ==========================================
               MAINTENANCE

               First check upcoming rentals.
            ========================================== */

            if(
                newStatus ===
                "Maintenance"
            ){

                confirmed =
                await confirmTractorMaintenance(
                    tractorID
                );

            }


            /* ==========================================
               AVAILABLE
            ========================================== */

            else{

                const result =
                await Swal.fire({

                    icon:
                    "question",

                    title:
                    "Change Tractor Status?",

                    text:
                    "Set tractor status to Available?",

                    showCancelButton:
                    true,

                    confirmButtonText:
                    "Yes",

                    cancelButtonText:
                    "Cancel",

                    reverseButtons:
                    true,

                    confirmButtonColor:
                    "#8CA15B",

                    cancelButtonColor:
                    "#6C757D"

                });


                confirmed =
                result.isConfirmed;

            }


            if(!confirmed){

                return;

            }


            /* ==========================================
               FINAL UPDATE
            ========================================== */

            let updateResponse;


            try{

                updateResponse =
                await sendTractorStatusRequest(

                    tractorID,

                    newStatus,

                    {

                        confirmMaintenance:
                        (
                            newStatus ===
                            "Maintenance"
                        )

                    }

                );

            }

            catch(error){


                console.error(
                    "TRACTOR STATUS ERROR:",
                    error
                );


                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Update Failed",

                    text:
                    "Unable to update tractor status.",

                    confirmButtonColor:
                    "#B71C1C"

                });


                return;

            }


            if(
                updateResponse !==
                "success"
            ){

                Swal.fire({

                    icon:
                    "error",

                    title:
                    "Update Failed",

                    text:
                    updateResponse
                    ||
                    "Unable to update tractor status.",

                    confirmButtonColor:
                    "#B71C1C"

                });


                return;

            }


            Swal.fire({

                icon:
                "success",

                title:
                "Status updated.",

                showConfirmButton:
                false,

                timer:
                1200

            })

            .then(
            function(){

                /*
                Reload instead of manually changing
                the button.

                Important because Available may actually
                become In Use if the tractor already has
                an active rental today.
                */

                window.location.reload();

            });

        }

    );

});


/* CLOSE STATUS MENU */

document.addEventListener(
    "click",
    function(){

        document
        .querySelectorAll(
            ".tractor-status-menu"
        )
        .forEach(function(menu){

            menu
            .classList
            .remove(
                "show"
            );

        });


        document
        .querySelectorAll(
            ".tractor-row"
        )
        .forEach(function(row){

            row
            .classList
            .remove(
                "status-menu-open"
            );

        });

    }
);


/* ======================================================
   ARCHIVE TRACTOR
====================================================== */

document
.querySelectorAll(
    ".tractor-archive-btn"
)
.forEach(function(button){


    button.addEventListener(
        "click",
        function(){


            const tractorID =
            this.dataset.id;


            const tractorName =
            this.dataset.name;


            const tractorNumber =
            this.dataset.number;


            Swal.fire({

                icon:"warning",

                title:
                "Archive Tractor?",

                html:

                "Are you sure you want to archive <strong>"
                +
                escapeTractorHTML(
                    tractorNumber
                )
                +
                " - "
                +
                escapeTractorHTML(
                    tractorName
                )
                +
                "</strong>?",

                showCancelButton:true,

                confirmButtonText:
                "Yes, Archive",

                cancelButtonText:
                "Cancel",

                reverseButtons:true,

                confirmButtonColor:
                "#B71C1C",

                cancelButtonColor:
                "#6C757D"

            })

            .then(function(result){


                if(
                    !result.isConfirmed
                ){

                    return;

                }


                const formData =
                new FormData();


                formData.append(
                    "tractor_id",
                    tractorID
                );


                formData.append(
                    "csrf_token",
                    tractorCsrfToken
                );


                fetch(

                    "archive_tractor.php",

                    {

                        method:"POST",

                        body:formData

                    }

                )

                .then(
                    response =>
                    response.text()
                )

                .then(function(data){


                    data =
                    data.trim();


                    if(
                        data === "success"
                    ){


                        Swal.fire({

                            icon:"success",

                            title:
                            "Tractor archived successfully.",

                            showConfirmButton:
                            false,

                            timer:1500

                        })

                        .then(function(){

                            window.location.reload();

                        });


                        return;

                    }


                    Swal.fire({

                        icon:"error",

                        title:
                        "Archive Failed",

                        text:

                        data

                        ||

                        "Unable to archive tractor.",

                        confirmButtonColor:
                        "#B71C1C"

                    });


                })

                .catch(function(){


                    Swal.fire({

                        icon:"error",

                        title:
                        "Archive Failed",

                        text:
                        "Unable to archive tractor. Please try again.",

                        confirmButtonColor:
                        "#B71C1C"

                    });


                });


            });


        }
    );


});


filterTractors();


</script>


<script src="../../js/sidebar.js"></script>


</body>
</html>