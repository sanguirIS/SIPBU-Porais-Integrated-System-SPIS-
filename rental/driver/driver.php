<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once '../../db.php';

mysqli_set_charset($conn, 'utf8mb4');
mysqli_query($conn, "SET time_zone = '+08:00'");


/* =========================================================
   VERIFY LIVE RENTAL ACCESS
========================================================= */

$userID =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


if ($userID <= 0) {

    http_response_code(401);

    exit(
        'Your session has expired. Please sign in again.'
    );
}


$accessStmt =
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


if (!$accessStmt) {

    http_response_code(500);

    exit(
        'Unable to verify Rental access.'
    );
}


mysqli_stmt_bind_param(
    $accessStmt,
    'i',
    $userID
);


if (!mysqli_stmt_execute($accessStmt)) {

    mysqli_stmt_close($accessStmt);

    http_response_code(500);

    exit(
        'Unable to verify Rental access.'
    );
}


mysqli_stmt_store_result(
    $accessStmt
);


$hasRentalAccess =
    mysqli_stmt_num_rows(
        $accessStmt
    ) === 1;


mysqli_stmt_close(
    $accessStmt
);


if (!$hasRentalAccess) {

    http_response_code(403);

    exit(
        'You do not have permission to access Rental.'
    );
}


/* =========================================================
   SIDEBAR
========================================================= */

require_once '../../includes/sidebar.php';


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    (string) $_SESSION['csrf_token'];


/* =========================================================
   ELIGIBLE MEMBERS FOR NEW DRIVER

   RULES:
   - Member only
   - Member must be Active
   - Member must not be archived
   - One Member = one Driver Registry record
========================================================= */

$eligibleMembers = [];


$eligibleMemberStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            m.member_id,
            m.member_code,
            m.full_name,
            m.contact_no

        FROM members m

        LEFT JOIN drivers d
            ON d.member_id = m.member_id

        WHERE m.archived = 0

        AND UPPER(
            TRIM(
                COALESCE(
                    m.status,
                    ''
                )
            )
        ) = 'ACTIVE'

        AND d.driver_id IS NULL

        ORDER BY
            m.full_name ASC,
            m.member_id ASC
        "
    );


if (
    $eligibleMemberStmt
    &&
    mysqli_stmt_execute(
        $eligibleMemberStmt
    )
) {

    $eligibleMemberResult =
        mysqli_stmt_get_result(
            $eligibleMemberStmt
        );


    if (
        $eligibleMemberResult
    ) {

        while (
            $member =
            mysqli_fetch_assoc(
                $eligibleMemberResult
            )
        ) {

            $eligibleMembers[] = [

                'member_id' =>
                    (int) (
                        $member['member_id']
                        ??
                        0
                    ),

                'member_code' =>
                    trim(
                        (string) (
                            $member['member_code']
                            ??
                            ''
                        )
                    ),

                'full_name' =>
                    trim(
                        (string) (
                            $member['full_name']
                            ??
                            ''
                        )
                    ),

                'contact_no' =>
                    trim(
                        (string) (
                            $member['contact_no']
                            ??
                            ''
                        )
                    )

            ];

        }

    }

}


if (
    $eligibleMemberStmt
) {

    mysqli_stmt_close(
        $eligibleMemberStmt
    );

}


/* =========================================================
   DRIVER REGISTRY

   Driver Name + Contact now come from linked Member.
========================================================= */

$driverQuery =
    mysqli_query(
        $conn,
        "
        SELECT

            d.driver_id,
            d.member_id,
            d.status AS stored_driver_status,
            d.created_at,

            m.member_code,
            m.full_name,
            m.contact_no,
            m.status AS member_status,

            CASE

                WHEN UPPER(
                    TRIM(
                        COALESCE(
                            m.status,
                            ''
                        )
                    )
                ) <> 'ACTIVE'

                    THEN 'Inactive'

                ELSE d.status

            END AS registry_status,


            CASE

                WHEN UPPER(
                    TRIM(
                        COALESCE(
                            m.status,
                            ''
                        )
                    )
                ) <> 'ACTIVE'

                    THEN 'Inactive'


                WHEN d.status = 'Inactive'

                    THEN 'Inactive'


                WHEN

                    EXISTS (

                        SELECT 1

                        FROM hauling h

                        WHERE DATE(
                            h.rental_date
                        ) = CURDATE()

                        AND h.status = 'Ongoing'

                        AND (

                            h.primary_driver_id =
                                d.driver_id

                            OR

                            h.secondary_driver_id =
                                d.driver_id

                        )

                    )

                    OR

                    EXISTS (

                        SELECT 1

                        FROM rotor r

                        WHERE DATE(
                            r.rental_date
                        ) = CURDATE()

                        AND r.status = 'Ongoing'

                        AND (

                            r.primary_driver_id =
                                d.driver_id

                            OR

                            r.secondary_driver_id =
                                d.driver_id

                        )

                    )

                    OR

                    EXISTS (

                        SELECT 1

                        FROM reaper rp

                        WHERE DATE(
                            rp.rental_date
                        ) = CURDATE()

                        AND rp.status = 'Ongoing'

                        AND (

                            rp.primary_driver_id =
                                d.driver_id

                            OR

                            rp.secondary_driver_id =
                                d.driver_id

                        )

                    )

                    THEN 'Assigned'


                WHEN

                    EXISTS (

                        SELECT 1

                        FROM hauling h

                        WHERE DATE(
                            h.rental_date
                        ) = CURDATE()

                        AND h.status = 'Pending'

                        AND (

                            h.primary_driver_id =
                                d.driver_id

                            OR

                            h.secondary_driver_id =
                                d.driver_id

                        )

                    )

                    OR

                    EXISTS (

                        SELECT 1

                        FROM rotor r

                        WHERE DATE(
                            r.rental_date
                        ) = CURDATE()

                        AND r.status = 'Pending'

                        AND (

                            r.primary_driver_id =
                                d.driver_id

                            OR

                            r.secondary_driver_id =
                                d.driver_id

                        )

                    )

                    OR

                    EXISTS (

                        SELECT 1

                        FROM reaper rp

                        WHERE DATE(
                            rp.rental_date
                        ) = CURDATE()

                        AND rp.status = 'Pending'

                        AND (

                            rp.primary_driver_id =
                                d.driver_id

                            OR

                            rp.secondary_driver_id =
                                d.driver_id

                        )

                    )

                    THEN 'Reserved'


                ELSE 'Available'

            END AS assignment_status


        FROM drivers d


        INNER JOIN members m

            ON m.member_id =
                d.member_id

            AND m.archived = 0


        WHERE d.archived = 0


        ORDER BY
            d.driver_id ASC
        "
    );

?>


<link
rel="stylesheet"
href="driver.css?v=<?= time() ?>">


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="driver-page-header">

    <div class="driver-title-area">

        <h2>
            RENTAL - DRIVER REGISTRY
        </h2>

    </div>


    <button
    type="button"
    class="btn-add-driver"
    data-bs-toggle="modal"
    data-bs-target="#addDriverModal">

        <i class="bi bi-person-plus-fill"></i>

        ADD DRIVER

    </button>

</div>


<hr>


<!-- =========================================================
     TOOLBAR
========================================================= -->

<div class="driver-toolbar">


    <div class="driver-toolbar-left">


        <a
        href="../rental.php"
        class="driver-back-btn"
        title="Back to Rental"
        aria-label="Back to Rental">

            <i class="bi bi-arrow-left"></i>

        </a>


        <div class="driver-search-box">

            <i class="bi bi-search"></i>


            <input
            type="text"
            id="driverSearch"
            placeholder="Search driver"
            autocomplete="off">

        </div>


    </div>


    <div class="driver-toolbar-right">


        <select
        id="driverRegistryStatusFilter"
        class="driver-filter">

            <option value="all">
                All Registry Status
            </option>

            <option value="Active">
                Active
            </option>

            <option value="Inactive">
                Inactive
            </option>

        </select>


        <select
        id="driverAvailabilityFilter"
        class="driver-filter">

            <option value="all">
                All Availability
            </option>

            <option value="Available">
                Available
            </option>

            <option value="Reserved">
                Reserved
            </option>

            <option value="Assigned">
                Assigned
            </option>

            <option value="Inactive">
                Inactive
            </option>

        </select>


    </div>


</div>


<!-- =========================================================
     LEDGER
========================================================= -->

<div class="driver-ledger">


    <div class="driver-ledger-header">

        <div>
            DRIVER ID
        </div>

        <div>
            DRIVER NAME
        </div>

        <div>
            CONTACT NO.
        </div>

        <div>
            REGISTRY STATUS
        </div>

        <div>
            CURRENT AVAILABILITY
        </div>

        <div>
            ACTION
        </div>

    </div>


    <div
    class="driver-ledger-body"
    id="driverLedgerBody">


        <?php

        $driverCount =
            0;


        if (
            $driverQuery
            &&
            mysqli_num_rows(
                $driverQuery
            ) > 0
        ) {


            while (
                $driver =
                mysqli_fetch_assoc(
                    $driverQuery
                )
            ) {


                $driverCount++;


                $driverID =
                    (int) (
                        $driver[
                            'driver_id'
                        ]
                        ??
                        0
                    );


                $memberID =
                    (int) (
                        $driver[
                            'member_id'
                        ]
                        ??
                        0
                    );


                $memberCode =
                    trim(
                        (string) (
                            $driver[
                                'member_code'
                            ]
                            ??
                            ''
                        )
                    );


                $fullName =
                    trim(
                        (string) (
                            $driver[
                                'full_name'
                            ]
                            ??
                            ''
                        )
                    );


                $contactNo =
                    trim(
                        (string) (
                            $driver[
                                'contact_no'
                            ]
                            ??
                            ''
                        )
                    );


                $registryStatus =
                    trim(
                        (string) (
                            $driver[
                                'registry_status'
                            ]
                            ??
                            'Active'
                        )
                    );


                $availability =
                    trim(
                        (string) (
                            $driver[
                                'assignment_status'
                            ]
                            ??
                            'Available'
                        )
                    );


                $availabilityClass =
                    strtolower(
                        $availability
                    );


                $registryClass =
                    strtolower(
                        $registryStatus
                    );

        ?>


        <div
        class="driver-row"

        data-id="<?= $driverID ?>"

        data-member-id="<?= $memberID ?>"

        data-member-code="<?=
            htmlspecialchars(
                $memberCode,
                ENT_QUOTES,
                'UTF-8'
            );
        ?>"

        data-name="<?=
            htmlspecialchars(
                $fullName,
                ENT_QUOTES,
                'UTF-8'
            );
        ?>"

        data-contact="<?=
            htmlspecialchars(
                $contactNo,
                ENT_QUOTES,
                'UTF-8'
            );
        ?>"

        data-registry-status="<?=
            htmlspecialchars(
                $registryStatus,
                ENT_QUOTES,
                'UTF-8'
            );
        ?>"

        data-availability="<?=
            htmlspecialchars(
                $availability,
                ENT_QUOTES,
                'UTF-8'
            );
        ?>"

        data-search="<?=
            htmlspecialchars(

                strtolower(

                    $fullName
                    .
                    ' '
                    .
                    $contactNo
                    .
                    ' '
                    .
                    $memberCode
                    .
                    ' DRV-'
                    .
                    str_pad(
                        (string) $driverID,
                        4,
                        '0',
                        STR_PAD_LEFT
                    )
                    .
                    ' '
                    .
                    $registryStatus
                    .
                    ' '
                    .
                    $availability

                ),

                ENT_QUOTES,
                'UTF-8'

            );
        ?>">


            <div class="driver-id-cell">

                <strong>

                    DRV-<?=
                        str_pad(
                            (string) $driverID,
                            4,
                            '0',
                            STR_PAD_LEFT
                        );
                    ?>

                </strong>

            </div>


            <div class="driver-name-cell">

                <strong>

                    <?=
                        htmlspecialchars(
                            $fullName,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>

                </strong>


                <?php

                if (
                    $memberCode !== ''
                ):

                ?>


                    <span class="driver-muted">

                        <?=
                            htmlspecialchars(
                                $memberCode,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                        ?>

                    </span>


                <?php

                endif;

                ?>

            </div>


            <div>

                <?php

                if (
                    $contactNo !== ''
                ) {

                    echo
                    htmlspecialchars(
                        $contactNo,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                }
                else {

                    echo
                    '<span class="driver-muted">—</span>';

                }

                ?>

            </div>


            <div>

                <span
                class="driver-badge driver-registry-<?=
                    htmlspecialchars(
                        $registryClass,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>">

                    <?=
                        htmlspecialchars(
                            $registryStatus,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>

                </span>

            </div>


            <div>

                <span
                class="driver-badge driver-availability-<?=
                    htmlspecialchars(
                        $availabilityClass,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>">

                    <?=
                        htmlspecialchars(
                            $availability,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                    ?>

                </span>

            </div>


            <div class="driver-actions">


                <button
                type="button"
                class="driver-action-btn driver-edit-btn"
                title="Edit Driver"
                aria-label="Edit Driver">

                    <i class="bi bi-pencil-square"></i>

                </button>


                <button
                type="button"
                class="driver-action-btn driver-archive-btn"
                title="Archive Driver"
                aria-label="Archive Driver">

                    <i class="bi bi-archive-fill"></i>

                </button>


            </div>


        </div>


        <?php

            }

        }

        ?>


        <!-- EMPTY -->


        <div
        id="driverEmptyState"
        class="driver-empty-state"
        style="<?=
            $driverCount === 0
            ?
            'display:flex;'
            :
            'display:none;'
        ?>">

            <i class="bi bi-person-badge"></i>

            <strong>
                No registered drivers yet.
            </strong>

            <span>
                Add an eligible Member to the Driver Registry.
            </span>

        </div>


        <!-- NO RESULTS -->


        <div
        id="driverNoResults"
        class="driver-empty-state"
        style="display:none;">

            <i class="bi bi-search"></i>

            <strong>
                No matching drivers found.
            </strong>

            <span>
                Try changing your search or filters.
            </span>

        </div>


    </div>

</div>


<!-- =========================================================
     ADD DRIVER MODAL
========================================================= -->

<div
class="modal fade"
id="addDriverModal"
tabindex="-1"
aria-hidden="true">


    <div
    class="modal-dialog modal-lg modal-dialog-centered">


        <div
        class="modal-content driver-modal-window">


            <form
            id="addDriverForm"
            novalidate>


                <input
                type="hidden"
                name="csrf_token"
                value="<?=
                    htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>">


                <input
                type="hidden"
                id="driverMemberID"
                name="member_id">


                <!-- HEADER -->


                <div class="driver-modal-header">


                    <div class="driver-modal-title-group">


                        <h1>
                            ADD DRIVER
                        </h1>


                        <span class="driver-header-badge">

                            <i class="bi bi-person-check-fill"></i>

                            ACTIVE

                        </span>


                    </div>


                    <button
                    type="button"
                    class="driver-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                        ×

                    </button>


                </div>


                <hr>


                <!-- BODY -->


                <div class="driver-modal-body">


                    <section class="driver-section">


                        <div class="driver-section-heading">


                            <i class="bi bi-person-vcard"></i>


                            <div>

                                <h2>
                                    MEMBER INFORMATION
                                </h2>

                                <p>
                                    Only an active registered Member can be added as a Rental driver.
                                </p>

                            </div>


                        </div>


                        <div class="driver-form-grid">


                            <!-- MEMBER SEARCH -->


                            <div
                            class="driver-field driver-field-full"
                            style="position:relative;">


                                <label for="driverMemberSearch">

                                    SEARCH MEMBER

                                    <span>*</span>

                                </label>


                                <input
                                type="text"
                                id="driverMemberSearch"
                                name="full_name"
                                class="driver-input"
                                placeholder="Type member name, ID, or contact no."
                                autocomplete="off"
                                required>


                                <div
                                id="driverMemberSuggestions"
                                class="list-group position-absolute w-100 shadow-sm"

                                style="
                                    display:none;
                                    z-index:1080;
                                    max-height:230px;
                                    overflow-y:auto;
                                    top:100%;
                                    margin-top:6px;
                                ">
                                </div>


                                <small class="driver-field-note">

                                    Select a Member from the suggestions.
                                    Members already registered as drivers are not listed.

                                </small>


                            </div>


                            <!-- MEMBER CODE -->


                            <div class="driver-field">


                                <label for="driverMemberCode">

                                    MEMBER ID

                                </label>


                                <input
                                type="text"
                                id="driverMemberCode"
                                class="driver-input"
                                placeholder="Auto-filled"
                                readonly>


                            </div>


                            <!-- CONTACT -->


                            <div class="driver-field">


                                <label for="driverContactNo">

                                    CONTACT NO.

                                </label>


                                <input
                                type="text"
                                id="driverContactNo"
                                name="contact_no"
                                class="driver-input"
                                placeholder="Auto-filled"
                                readonly>


                            </div>


                        </div>


                    </section>


                    <section
                    class="driver-section driver-section-soft">


                        <div class="driver-section-heading">


                            <i class="bi bi-info-circle"></i>


                            <div>

                                <h2>
                                    REGISTRY STATUS
                                </h2>

                                <p>
                                    New drivers are registered as Active.
                                    Availability is computed from Rental schedules.
                                </p>

                            </div>


                        </div>


                    </section>


                </div>


                <!-- FOOTER -->


                <div class="driver-modal-footer">


                    <button
                    type="button"
                    class="driver-cancel-btn"
                    data-bs-dismiss="modal">

                        CANCEL

                    </button>


                    <button
                    type="submit"
                    class="driver-save-btn">

                        SAVE DRIVER

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<!-- =========================================================
     EDIT DRIVER MODAL
========================================================= -->

<div
class="modal fade"
id="editDriverModal"
tabindex="-1"
aria-hidden="true">


    <div
    class="modal-dialog modal-lg modal-dialog-centered">


        <div
        class="modal-content driver-modal-window">


            <form
            id="editDriverForm"
            novalidate>


                <input
                type="hidden"
                id="editDriverID"
                name="driver_id">


                <input
                type="hidden"
                name="csrf_token"
                value="<?=
                    htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>">


                <div class="driver-modal-header">


                    <div class="driver-modal-title-group">


                        <h1>
                            EDIT DRIVER
                        </h1>


                        <span
                        id="editDriverHeaderBadge"
                        class="driver-header-badge">

                            ACTIVE

                        </span>


                    </div>


                    <button
                    type="button"
                    class="driver-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                        ×

                    </button>


                </div>


                <hr>


                <div class="driver-modal-body">


                    <section class="driver-section">


                        <div class="driver-section-heading">


                            <i class="bi bi-person-vcard"></i>


                            <div>

                                <h2>
                                    DRIVER INFORMATION
                                </h2>

                                <p>
                                    Name and contact details come from the linked Member record.
                                </p>

                            </div>


                        </div>


                        <div class="driver-form-grid">


                            <div class="driver-field driver-field-wide">


                                <label for="editDriverFullName">

                                    DRIVER NAME

                                </label>


                                <input
                                type="text"
                                id="editDriverFullName"
                                name="full_name"
                                class="driver-input"
                                readonly>


                            </div>


                            <div class="driver-field">


                                <label for="editDriverContactNo">

                                    CONTACT NO.

                                </label>


                                <input
                                type="text"
                                id="editDriverContactNo"
                                name="contact_no"
                                class="driver-input"
                                readonly>


                            </div>


                            <div class="driver-field driver-field-full">


                                <label for="editDriverStatus">

                                    REGISTRY STATUS

                                    <span>*</span>

                                </label>


                                <select
                                id="editDriverStatus"
                                name="status"
                                class="driver-input">


                                    <option value="Active">
                                        Active
                                    </option>


                                    <option value="Inactive">
                                        Inactive
                                    </option>


                                </select>


                                <small class="driver-field-note">

                                    Driver identity is managed from Members.
                                    Only the Driver Registry status can be changed here.

                                </small>


                            </div>


                        </div>


                    </section>


                </div>


                <div class="driver-modal-footer">


                    <button
                    type="button"
                    class="driver-cancel-btn"
                    data-bs-dismiss="modal">

                        CANCEL

                    </button>


                    <button
                    type="submit"
                    class="driver-save-btn">

                        SAVE CHANGES

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>


<!-- =========================================================
     DRIVER SCRIPT
========================================================= -->

<script>

(() => {


    const csrfToken =
    <?= json_encode(
        $csrfToken,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    ); ?>;


    const eligibleMembers =
    <?= json_encode(
        $eligibleMembers,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
        |
        JSON_HEX_TAG
        |
        JSON_HEX_AMP
        |
        JSON_HEX_APOS
        |
        JSON_HEX_QUOT
    ); ?>;


    /* =====================================================
       LEDGER FILTERS
    ===================================================== */


    const search =
    document.getElementById(
        'driverSearch'
    );


    const statusFilter =
    document.getElementById(
        'driverRegistryStatusFilter'
    );


    const availabilityFilter =
    document.getElementById(
        'driverAvailabilityFilter'
    );


    const noResults =
    document.getElementById(
        'driverNoResults'
    );


    const emptyState =
    document.getElementById(
        'driverEmptyState'
    );


    function filterDrivers(){


        const rows =

        [
            ...document.querySelectorAll(
                '.driver-row'
            )
        ];


        const keyword =

        (
            search?.value
            ||
            ''
        )
        .trim()
        .toLowerCase();


        const registryStatus =

        statusFilter?.value
        ||
        'all';


        const availability =

        availabilityFilter?.value
        ||
        'all';


        let visible =
        0;


        rows.forEach(
        row => {


            const matchesSearch =

            keyword === ''

            ||

            (
                row.dataset.search
                ||
                ''
            )
            .includes(
                keyword
            );


            const matchesStatus =

            registryStatus === 'all'

            ||

            row.dataset.registryStatus
            ===
            registryStatus;


            const matchesAvailability =

            availability === 'all'

            ||

            row.dataset.availability
            ===
            availability;


            const show =

            matchesSearch
            &&
            matchesStatus
            &&
            matchesAvailability;


            row.style.display =

            show
            ?
            'grid'
            :
            'none';


            if(
                show
            ){

                visible++;

            }


        });


        if(
            rows.length === 0
        ){

            if(
                emptyState
            ){

                emptyState.style.display =
                'flex';

            }


            if(
                noResults
            ){

                noResults.style.display =
                'none';

            }


            return;

        }


        if(
            emptyState
        ){

            emptyState.style.display =
            'none';

        }


        if(
            noResults
        ){

            noResults.style.display =

            visible === 0
            ?
            'flex'
            :
            'none';

        }


    }


    search
    ?.addEventListener(
        'input',
        filterDrivers
    );


    statusFilter
    ?.addEventListener(
        'change',
        filterDrivers
    );


    availabilityFilter
    ?.addEventListener(
        'change',
        filterDrivers
    );


    /* =====================================================
       ADD DRIVER - MEMBER SEARCH
    ===================================================== */


    const addModalElement =
    document.getElementById(
        'addDriverModal'
    );


    const addForm =
    document.getElementById(
        'addDriverForm'
    );


    const memberSearch =
    document.getElementById(
        'driverMemberSearch'
    );


    const memberIDInput =
    document.getElementById(
        'driverMemberID'
    );


    const memberCodeInput =
    document.getElementById(
        'driverMemberCode'
    );


    const contactInput =
    document.getElementById(
        'driverContactNo'
    );


    const memberSuggestions =
    document.getElementById(
        'driverMemberSuggestions'
    );


    let selectedMember =
    null;


    function clearSelectedMember(
        keepSearchText = true
    ){


        selectedMember =
        null;


        if(
            memberIDInput
        ){

            memberIDInput.value =
            '';

        }


        if(
            memberCodeInput
        ){

            memberCodeInput.value =
            '';

        }


        if(
            contactInput
        ){

            contactInput.value =
            '';

        }


        if(
            !keepSearchText
            &&
            memberSearch
        ){

            memberSearch.value =
            '';

        }


    }


    function hideMemberSuggestions(){


        if(
            !memberSuggestions
        ){

            return;

        }


        memberSuggestions.innerHTML =
        '';


        memberSuggestions.style.display =
        'none';


    }


    function selectMember(
        member
    ){


        selectedMember =
        member;


        if(
            memberIDInput
        ){

            memberIDInput.value =

            String(
                member.member_id
                ||
                ''
            );

        }


        if(
            memberSearch
        ){

            memberSearch.value =
            member.full_name
            ||
            '';

        }


        if(
            memberCodeInput
        ){

            memberCodeInput.value =
            member.member_code
            ||
            '';

        }


        if(
            contactInput
        ){

            contactInput.value =
            member.contact_no
            ||
            '';

        }


        hideMemberSuggestions();


    }


    function buildMemberSuggestion(
        member
    ){


        const button =
        document.createElement(
            'button'
        );


        button.type =
        'button';


        button.className =
        'list-group-item list-group-item-action';


        const name =
        document.createElement(
            'strong'
        );


        name.textContent =

        member.full_name
        ||
        'Unnamed Member';


        const meta =
        document.createElement(
            'small'
        );


        meta.className =
        'd-block text-muted mt-1';


        meta.textContent =

        [

            member.member_code
            ||
            '',

            member.contact_no
            ||
            'No contact no.'

        ]
        .filter(
            Boolean
        )
        .join(
            ' • '
        );


        button.appendChild(
            name
        );


        button.appendChild(
            meta
        );


        button.addEventListener(
            'mousedown',
            event => {


                event.preventDefault();


                selectMember(
                    member
                );


            }
        );


        return button;


    }


    function renderMemberSuggestions(){


        if(
            !memberSearch
            ||
            !memberSuggestions
        ){

            return;

        }


        const keyword =

        memberSearch.value
        .trim()
        .toLowerCase();


        clearSelectedMember(
            true
        );


        memberSuggestions.innerHTML =
        '';


        if(
            keyword.length < 2
        ){

            hideMemberSuggestions();

            return;

        }


        const matches =

        eligibleMembers
        .filter(
        member => {


            const haystack =

            [

                member.full_name,

                member.member_code,

                member.contact_no

            ]
            .join(
                ' '
            )
            .toLowerCase();


            return haystack.includes(
                keyword
            );


        })
        .slice(
            0,
            8
        );


        if(
            matches.length === 0
        ){


            const empty =
            document.createElement(
                'div'
            );


            empty.className =
            'list-group-item text-muted';


            empty.textContent =
            'No eligible Member found.';


            memberSuggestions.appendChild(
                empty
            );


            memberSuggestions.style.display =
            'block';


            return;


        }


        matches.forEach(
        member => {


            memberSuggestions.appendChild(

                buildMemberSuggestion(
                    member
                )

            );


        });


        memberSuggestions.style.display =
        'block';


    }


    memberSearch
    ?.addEventListener(
        'input',
        renderMemberSuggestions
    );


    memberSearch
    ?.addEventListener(
        'focus',
        () => {


            if(
                (
                    memberSearch.value
                    ||
                    ''
                )
                .trim()
                .length >= 2

                &&

                !selectedMember
            ){

                renderMemberSuggestions();

            }


        }
    );


    memberSearch
    ?.addEventListener(
        'blur',
        () => {


            window.setTimeout(
                hideMemberSuggestions,
                120
            );


        }
    );


    addModalElement
    ?.addEventListener(
        'hidden.bs.modal',
        () => {


            addForm
            ?.reset();


            clearSelectedMember(
                false
            );


            hideMemberSuggestions();


        }
    );


    /* =====================================================
       SAVE DRIVER
    ===================================================== */


    addForm
    ?.addEventListener(
        'submit',
        async event => {


            event.preventDefault();


            if(
                !memberIDInput?.value
                ||
                !selectedMember
            ){


                Swal.fire({

                    icon:
                    'warning',

                    title:
                    'Select a Member',

                    text:
                    'Please choose a registered Member from the suggestions.',

                    confirmButtonColor:
                    '#8CA15B'

                });


                memberSearch
                ?.focus();


                return;


            }


            const confirm =
            await Swal.fire({

                icon:
                'question',

                title:
                'Add Driver?',

                text:
                `Register ${
                    selectedMember.full_name
                    ||
                    'this Member'
                } as a Rental driver?`,

                showCancelButton:
                true,

                confirmButtonText:
                'Yes, Add',

                cancelButtonText:
                'Cancel',

                confirmButtonColor:
                '#8CA15B',

                cancelButtonColor:
                '#B71C1C',

                reverseButtons:
                true

            });


            if(
                !confirm.isConfirmed
            ){

                return;

            }


            try {


                const formData =
                new FormData(
                    addForm
                );


                formData.set(
                    'csrf_token',
                    csrfToken
                );


                const response =
                await fetch(
                    'save_driver.php',
                    {

                        method:
                        'POST',

                        body:
                        formData,

                        headers: {

                            'X-Requested-With':
                            'XMLHttpRequest'

                        }

                    }
                );


                const text =

                (
                    await response.text()
                )
                .trim();


                if(
                    !response.ok
                    ||
                    text !== 'success'
                ){

                    throw new Error(

                        text
                        ||
                        'Unable to register driver.'

                    );

                }


                await Swal.fire({

                    icon:
                    'success',

                    title:
                    'Driver Registered',

                    showConfirmButton:
                    false,

                    timer:
                    1200

                });


                window.location.reload();


            }
            catch(
                error
            ){


                Swal.fire({

                    icon:
                    'error',

                    title:
                    'Save Failed',

                    text:
                    error.message
                    ||
                    'Unable to register driver.',

                    confirmButtonColor:
                    '#B71C1C'

                });


            }


        }
    );


    /* =====================================================
       EDIT DRIVER
    ===================================================== */


    const editModalElement =
    document.getElementById(
        'editDriverModal'
    );


    const editModal =

    editModalElement
    ?
    new bootstrap.Modal(
        editModalElement
    )
    :
    null;


    const editForm =
    document.getElementById(
        'editDriverForm'
    );


    document
    .querySelectorAll(
        '.driver-edit-btn'
    )
    .forEach(
    button => {


        button.addEventListener(
            'click',
            () => {


                const row =
                button.closest(
                    '.driver-row'
                );


                if(
                    !row
                ){

                    return;

                }


                document
                .getElementById(
                    'editDriverID'
                )
                .value =

                row.dataset.id
                ||
                '';


                document
                .getElementById(
                    'editDriverFullName'
                )
                .value =

                row.dataset.name
                ||
                '';


                document
                .getElementById(
                    'editDriverContactNo'
                )
                .value =

                row.dataset.contact
                ||
                '';


                document
                .getElementById(
                    'editDriverStatus'
                )
                .value =

                row.dataset.registryStatus
                ||
                'Active';


                const badge =
                document.getElementById(
                    'editDriverHeaderBadge'
                );


                if(
                    badge
                ){

                    badge.textContent =

                    (
                        row.dataset.registryStatus
                        ||
                        'Active'
                    )
                    .toUpperCase();

                }


                editModal
                ?.show();


            }
        );


    });


    editForm
    ?.addEventListener(
        'submit',
        async event => {


            event.preventDefault();


            const confirm =
            await Swal.fire({

                icon:
                'question',

                title:
                'Save Driver Changes?',

                showCancelButton:
                true,

                confirmButtonText:
                'Yes, Save',

                cancelButtonText:
                'Cancel',

                confirmButtonColor:
                '#8CA15B',

                cancelButtonColor:
                '#B71C1C',

                reverseButtons:
                true

            });


            if(
                !confirm.isConfirmed
            ){

                return;

            }


            try {


                const formData =
                new FormData(
                    editForm
                );


                formData.set(
                    'csrf_token',
                    csrfToken
                );


                const response =
                await fetch(
                    'update_driver.php',
                    {

                        method:
                        'POST',

                        body:
                        formData,

                        headers: {

                            'X-Requested-With':
                            'XMLHttpRequest'

                        }

                    }
                );


                const text =

                (
                    await response.text()
                )
                .trim();


                if(
                    !response.ok
                    ||
                    text !== 'success'
                ){

                    throw new Error(

                        text
                        ||
                        'Unable to update driver.'

                    );

                }


                await Swal.fire({

                    icon:
                    'success',

                    title:
                    'Driver Updated',

                    showConfirmButton:
                    false,

                    timer:
                    1200

                });


                window.location.reload();


            }
            catch(
                error
            ){


                Swal.fire({

                    icon:
                    'error',

                    title:
                    'Update Failed',

                    text:
                    error.message
                    ||
                    'Unable to update driver.',

                    confirmButtonColor:
                    '#B71C1C'

                });


            }


        }
    );


    /* =====================================================
       ARCHIVE DRIVER
    ===================================================== */


    document
    .querySelectorAll(
        '.driver-archive-btn'
    )
    .forEach(
    button => {


        button.addEventListener(
            'click',
            async () => {


                const row =
                button.closest(
                    '.driver-row'
                );


                if(
                    !row
                ){

                    return;

                }


                const confirm =
                await Swal.fire({

                    icon:
                    'warning',

                    title:
                    'Archive Driver?',

                    text:
                    `${
                        row.dataset.name
                        ||
                        'This driver'
                    } will no longer appear in Rental assignment choices.`,

                    showCancelButton:
                    true,

                    confirmButtonText:
                    'Yes, Archive',

                    cancelButtonText:
                    'Cancel',

                    confirmButtonColor:
                    '#B71C1C',

                    cancelButtonColor:
                    '#8CA15B',

                    reverseButtons:
                    true

                });


                if(
                    !confirm.isConfirmed
                ){

                    return;

                }


                const formData =
                new FormData();


                formData.append(
                    'driver_id',
                    row.dataset.id
                    ||
                    ''
                );


                formData.append(
                    'csrf_token',
                    csrfToken
                );


                try {


                    const response =
                    await fetch(
                        'archive_driver.php',
                        {

                            method:
                            'POST',

                            body:
                            formData,

                            headers: {

                                'X-Requested-With':
                                'XMLHttpRequest'

                            }

                        }
                    );


                    const text =

                    (
                        await response.text()
                    )
                    .trim();


                    if(
                        !response.ok
                        ||
                        text !== 'success'
                    ){

                        throw new Error(

                            text
                            ||
                            'Unable to archive driver.'

                        );

                    }


                    await Swal.fire({

                        icon:
                        'success',

                        title:
                        'Driver Archived',

                        showConfirmButton:
                        false,

                        timer:
                        1200

                    });


                    window.location.reload();


                }
                catch(
                    error
                ){


                    Swal.fire({

                        icon:
                        'error',

                        title:
                        'Archive Failed',

                        text:
                        error.message
                        ||
                        'Unable to archive driver.',

                        confirmButtonColor:
                        '#B71C1C'

                    });


                }


            }
        );


    });


})();

</script>