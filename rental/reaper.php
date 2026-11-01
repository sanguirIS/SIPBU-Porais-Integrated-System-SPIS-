<?php

if (
    session_status()
    ===
    PHP_SESSION_NONE
) {
    session_start();
}


date_default_timezone_set(
    'Asia/Manila'
);


require_once '../db.php';


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
isset($_SESSION['user_id'])
?
(int)$_SESSION['user_id']
:
0;


if(
    $userID <= 0
){

    http_response_code(401);

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

    http_response_code(500);

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

    http_response_code(500);

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

    http_response_code(403);

    exit(
        'You do not have permission to access Rental.'
    );
}


/* ======================================================
   RENTAL CSRF TOKEN
====================================================== */

if(
    empty(
        $_SESSION['rental_csrf_token']
    )
){

    $_SESSION['rental_csrf_token'] =
    bin2hex(
        random_bytes(32)
    );
}


$rentalCsrfToken =
(string)
$_SESSION['rental_csrf_token'];


/* ======================================================
   SYNC SHARED TRACTOR STATUS
====================================================== */

include 'tractor/tractor_status_sync.php';


/* ======================================================
   TRACTOR GUARD

   Tractor Type is classification only.
   The guard only requires at least one registered tractor.
====================================================== */

$requiredTractorType =
'Reaper';

include 'tractor/tractor_guard.php';


include '../includes/sidebar.php';

?>

<link rel="stylesheet" href="../css/rental.css">

<link rel="stylesheet" href="../css/rental/reaper.css">

<link rel="stylesheet" href="generate_reaper/report_reaper.css">

<div class="page-header">

    <h2>RENTAL - REAPER</h2>

    <button
    type="button"
    class="btn-generate-reaper-report"
    data-bs-toggle="modal"
    data-bs-target="#generateReaperReportModal">

        <i class="bi bi-printer-fill"></i>

        GENERATE REPORT

    </button>

</div>

<hr>

<div class="reaper-toolbar">

    <div class="reaper-toolbar-left">

        <a href="rental.php" class="reaper-back-btn">

            <i class="bi bi-arrow-left"></i>

        </a>

        <div class="reaper-search-box">

            <i class="bi bi-search"></i>

            <input
            type="text"
            id="searchReaper"
            placeholder="Search">

        </div>

    </div>

    <div class="reaper-toolbar-right">

        <button
        class="reaper-date-filter-btn"
        data-bs-toggle="modal"
        data-bs-target="#reaperDateFilterModal">

            <i class="bi bi-calendar3"></i>

        </button>

        <select
        id="affiliateFilter"
        class="reaper-filter">

            <option value="all">All Affiliate</option>
            <option value="Member">Member</option>
            <option value="Not Member">Not Member</option>

        </select>

        <select
        id="statusFilter"
        class="reaper-filter">

            <option value="all">
                All Status
            </option>

            <option value="Pending">
                Pending
            </option>

            <option value="Ongoing">
                Ongoing
            </option>

            <option value="Maintenance">
                Maintenance
            </option>

            <option value="Done">
                Done
            </option>

        </select>

        <select
        id="remarksFilter"
        class="reaper-filter">

            <option value="all">All Remarks</option>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>

        </select>

        <button
        class="btn-add-reaper"
        data-bs-toggle="modal"
        data-bs-target="#addReaperModal">

            ADD RENTAL

        </button>

    </div>

</div>

<div class="reaper-ledger">

    <div class="reaper-ledger-header">

        <div>TRANSACTION</div>

        <div>CUSTOMER</div>

        <div>NO. OF HARVEST</div>

        <div>ASSIGNMENT</div>

        <div>LOCATION</div>

        <div>COMMODITY</div>

        <div>STATUS</div>

        <div>REMARKS</div>

        <div>ACTION</div>

    </div>

    <div class="reaper-ledger-body">

    <?php

    $query = mysqli_query($conn, "
        SELECT
            r.*,
            t.status AS tractor_status,
            st.status AS secondary_tractor_status
        FROM reaper r
        LEFT JOIN tractors t
            ON t.tractor_id = r.tractor_id
            AND t.archived = 0
        LEFT JOIN tractors st
            ON st.tractor_id = r.secondary_tractor_id
            AND st.archived = 0
        ORDER BY r.reaper_id DESC
    ");

    if(mysqli_num_rows($query) > 0){

        $i = 0;

    while($row = mysqli_fetch_assoc($query)){

        $i++;

        $tractorMaintenanceAffected =
    (
        in_array(
            $row['status'],
            [
                'Pending',
                'Ongoing'
            ],
            true
        )
        &&
        (
            (
                (int)($row['tractor_id'] ?? 0) > 0
                &&
                ($row['tractor_status'] ?? '') === 'Maintenance'
            )
            ||
            (
                (int)($row['secondary_tractor_id'] ?? 0) > 0
                &&
                ($row['secondary_tractor_status'] ?? '') === 'Maintenance'
            )
        )
    );

    /* ==========================================
    HISTORICAL / AUDIT ENTRY
    ========================================== */

    $isHistoricalEntry =
    (
        (int)(
            $row[
                'is_historical_entry'
            ]
            ??
            0
        )
        ===
        1
    );


    /* ==========================================
    SCHEDULE FLAG

    PRIORITY

    1. Past + Pending = Missed
    2. Past + Ongoing = Delayed
    3. reschedule_count > 0 = Rescheduled

    Historical / Audit is separate.
    ========================================== */

    $scheduleFlag =
    '';


    $scheduleDate =
    trim(
        (string)(
            $row[
                'rental_date'
            ]
            ??
            ''
        )
    );


    $scheduleDateKey =
    '';


    if(
        $scheduleDate !==
        ''
    ){

        $scheduleTimestamp =
        strtotime(
            $scheduleDate
        );


        if(
            $scheduleTimestamp !==
            false
        ){

            $scheduleDateKey =
            date(
                'Y-m-d',
                $scheduleTimestamp
            );

        }

    }


    $todayDate =
    date(
        'Y-m-d'
    );


    $currentRentalStatus =
    trim(
        (string)(
            $row[
                'status'
            ]
            ??
            ''
        )
    );


    $rescheduleCount =
    (int)(
        $row[
            'reschedule_count'
        ]
        ??
        0
    );


    if(
        $scheduleDateKey !==
        ''
        &&
        $scheduleDateKey <
        $todayDate
    ){

        if(
            $currentRentalStatus ===
            'Pending'
        ){

            $scheduleFlag =
            'Missed';

        }
        elseif(
            $currentRentalStatus ===
            'Ongoing'
        ){

            $scheduleFlag =
            'Delayed';

        }

    }


    if(
        $scheduleFlag ===
        ''
        &&
        $rescheduleCount >
        0
    ){

        $scheduleFlag =
        'Rescheduled';

    }

    $primaryTractorNo =
        trim(
            (string)(
                $row['tractor_no']
                ??
                ''
            )
        );


        $primaryDriver =
        trim(
            (string)(
                $row['operator']
                ??
                ''
            )
        );


        $secondaryTractorNo =
        trim(
            (string)(
                $row['secondary_tractor_no']
                ??
                ''
            )
        );


        $secondaryDriver =
        trim(
            (string)(
                $row['secondary_operator']
                ??
                ''
            )
        );


        $hasSecondaryAssignment =
        (
            (int)(
                $row['secondary_tractor_id']
                ??
                0
            ) > 0
        )
        ||
        (
            (int)(
                $row['secondary_driver_id']
                ??
                0
            ) > 0
        )
        ||
        $secondaryTractorNo !== ''
        ||
        $secondaryDriver !== '';

        /* ==========================================
        TRANSACTION DISPLAY
        ========================================== */

        $fullTransactionID =
        trim(
            (string)(
                $row['transaction_id']
                ??
                ''
            )
        );


        /*
            RPR-20260918-0001
            becomes
            RPR-0001
        */

        $shortTransactionID =
        preg_replace(
            '/^RPR-\d{8}-/',
            'RPR-',
            $fullTransactionID
        );


        $rentalDate =
        trim(
            (string)(
                $row['rental_date']
                ??
                ''
            )
        );


        /* ==========================================
        AFFILIATE STYLE
        ========================================== */

        $affiliate =
        trim(
            (string)(
                $row['affiliate']
                ??
                ''
            )
        );


        $affiliateClass =
        (
            $affiliate
            ===
            'Member'
        )
        ?
        'is-member'
        :
        'is-non-member';

    ?>

        <div
        class="
        reaper-row
        <?= ($i == 1) ? 'first-row' : '' ?>
        <?= $tractorMaintenanceAffected
            ? 'reaper-maintenance-alert-row'
            : ''
        ?>
        "

        data-id="<?= $row['reaper_id'] ?>"

        data-name="<?=
            htmlspecialchars(
                $row['customer_name']
            )
        ?>"

        data-harvest="<?=
            is_null($row['no_of_harvest'])
                ? ''
                : htmlspecialchars(
                    $row['no_of_harvest']
                )
        ?>"

        data-affiliate="<?=
            htmlspecialchars(
                $row['affiliate']
            )
        ?>"

        data-tractor="<?=
            htmlspecialchars(
                $row['tractor_no']
            )
        ?>"

        data-operator="<?=
            htmlspecialchars(
                $row['operator']
            )
        ?>"

        data-location="<?=
            htmlspecialchars(
                $row['location']
            )
        ?>"

        data-date="<?= $row['rental_date'] ?>"

        data-commodity="<?=
            is_null($row['commodity'])
                ? ''
                : htmlspecialchars(
                    $row['commodity']
                )
        ?>"

        data-status="<?=
            htmlspecialchars(
                $row['status']
            )
        ?>"

        data-remarks="<?=
            htmlspecialchars(
                $row['remarks']
            )
        ?>"

        data-tractor-id="<?=
            (int)(
                $row['tractor_id']
                ??
                0
            )
        ?>"

        data-tractor-status="<?=
            htmlspecialchars(
                $row['tractor_status']
                ??
                ''
            )
        ?>"

        data-maintenance="<?=
            $tractorMaintenanceAffected
                ? '1'
                : '0'
        ?>"

        data-search="<?=
            strtolower(
                htmlspecialchars(
                    $row['transaction_id']
                    . " "
                    . $row['customer_name']
                    . " "
                    . $row['operator']
                    . " "
                    . $row['location']
                    . " "
                    . $row['commodity']
                    . " "
                    . $row['tractor_no']

                    . " "
                    . ($row['secondary_operator'] ?? '')
                    . " "
                    . ($row['secondary_tractor_no'] ?? '')
                )
            )
        ?>"
        >

        <!-- ==========================================
            TRANSACTION
        ========================================== -->

        <div
        class="reaper-transaction-cell"
        title="<?=
            htmlspecialchars(
                $fullTransactionID,
                ENT_QUOTES,
                'UTF-8'
            )
        ?>">


            <span class="reaper-transaction-id">

                <?=
                    htmlspecialchars(
                        $shortTransactionID,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ?>

            </span>


            <span class="reaper-transaction-date">

                <?=
                    $rentalDate !== ''
                    ?
                    date(
                        'M d, Y',
                        strtotime(
                            $rentalDate
                        )
                    )
                    :
                    '—'
                ?>

            </span>


            <!-- HISTORICAL / AUDIT -->

            <?php if(
                $isHistoricalEntry
            ){ ?>

                <span
                class="reaper-audit-flag"
                title="This rental was created using a Rental Date earlier than its record creation date.">

                    <i class="bi bi-journal-check"></i>

                    Historical / Audit Entry

                </span>

            <?php } ?>


            <!-- SCHEDULE FLAG -->

            <?php if(
                $scheduleFlag !==
                ''
            ){ ?>

                <span
                class="
                    reaper-schedule-flag

                    <?=
                        $scheduleFlag ===
                        'Missed'

                        ?

                        'is-missed'

                        :

                        (
                            $scheduleFlag ===
                            'Delayed'

                            ?

                            'is-delayed'

                            :

                            'is-rescheduled'
                        )
                    ?>
                "

                title="<?=
                    $scheduleFlag ===
                    'Missed'

                    ?

                    'The scheduled Rental Date has passed while the rental is still Pending.'

                    :

                    (
                        $scheduleFlag ===
                        'Delayed'

                        ?

                        'The scheduled Rental Date has passed while the rental is still Ongoing.'

                        :

                        'This rental has been rescheduled from a previous Rental Date.'
                    )
                ?>">


                    <i
                    class="<?=
                        $scheduleFlag ===
                        'Missed'

                        ?

                        'bi bi-calendar-x'

                        :

                        (
                            $scheduleFlag ===
                            'Delayed'

                            ?

                            'bi bi-clock-history'

                            :

                            'bi bi-calendar-check'
                        )
                    ?>">
                    </i>


                    <?= htmlspecialchars(
                        $scheduleFlag,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>

                </span>

            <?php } ?>


        </div>


        <!-- ==========================================
            CUSTOMER
        ========================================== -->

        <div class="reaper-customer-cell">

            <span class="reaper-customer-name">

                <?=
                    htmlspecialchars(
                        $row['customer_name'],
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ?>

            </span>


            <span
            class="
                reaper-affiliate-chip
                <?= $affiliateClass ?>
            ">

                <?=
                    htmlspecialchars(
                        $affiliate,
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ?>

            </span>

        </div>


        <!-- ==========================================
            NO. OF HARVEST
        ========================================== -->

        <div class="reaper-harvest-cell">

            <?=
                is_null(
                    $row['no_of_harvest']
                )
                ?
                '—'
                :
                htmlspecialchars(
                    $row['no_of_harvest']
                )
            ?>

        </div>


        <!-- ==========================================
            ASSIGNMENT
        ========================================== -->

        <div class="reaper-assignment-cell">


            <!-- PRIMARY -->

            <div class="reaper-assignment-primary">

                <div class="reaper-assignment-label">
                    PRIMARY
                </div>

                <div class="reaper-assignment-item">

                    <i class="bi bi-truck-front-fill"></i>

                    <span>
                        <?=
                            htmlspecialchars(
                                $primaryTractorNo !== ''
                                ?
                                $primaryTractorNo
                                :
                                'No Tractor',
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ?>
                    </span>

                </div>

                <div class="reaper-assignment-item">

                    <i class="bi bi-person-fill"></i>

                    <span>
                        <?=
                            htmlspecialchars(
                                $primaryDriver !== ''
                                ?
                                $primaryDriver
                                :
                                'No Driver',
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ?>
                    </span>

                </div>

            </div>


            <!-- SECONDARY -->

            <?php if($hasSecondaryAssignment){ ?>

                <div class="reaper-assignment-secondary">

                    <div class="reaper-assignment-label">
                        SECONDARY
                    </div>

                    <div class="reaper-assignment-item">

                        <i class="bi bi-truck-front"></i>

                        <span>
                            <?=
                                htmlspecialchars(
                                    $secondaryTractorNo !== ''
                                    ?
                                    $secondaryTractorNo
                                    :
                                    '—',
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ?>
                        </span>

                    </div>

                    <div class="reaper-assignment-item">

                        <i class="bi bi-person"></i>

                        <span>
                            <?=
                                htmlspecialchars(
                                    $secondaryDriver !== ''
                                    ?
                                    $secondaryDriver
                                    :
                                    '—',
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ?>
                        </span>

                    </div>

                </div>

            <?php } ?>


        </div>

        <div class="reaper-location-cell">

            <?= htmlspecialchars($row['location']) ?>

        </div>


        <div class="reaper-commodity-cell">

            <?=
                is_null($row['commodity'])
                ?
                '—'
                :
                htmlspecialchars($row['commodity'])
            ?>

        </div>

        <div>

            <div class="reaper-status-ledger-cell">

                <?php

                $statusClass =
                "reaper-status ";

                switch($row['status']){

                    case "Pending":

                        $statusClass .=
                        "reaper-status-pending";

                        break;


                    case "Ongoing":

                        $statusClass .=
                        "reaper-status-ongoing";

                        break;


                    case "Done":

                        $statusClass .=
                        "reaper-status-done";

                        break;

                }

                ?>


                <span class="<?= $statusClass ?>">

                    <?= htmlspecialchars($row['status']) ?>

                </span>


                <?php if($tractorMaintenanceAffected){ ?>

                    <span class="reaper-maintenance-status-hint">

                        <i class="bi bi-lock-fill"></i>

                        Pending only

                    </span>

                <?php } ?>

            </div>

        </div>

        <div>

        <?php

        $remarksClass = "reaper-remarks ";

        switch($row['remarks']){

            case "Paid":

                $remarksClass .= "reaper-remarks-paid";

                break;

            case "Unpaid":

                $remarksClass .= "reaper-remarks-unpaid";

                break;

        }

        ?>

        <span class="<?= $remarksClass ?>">

            <?= htmlspecialchars($row['remarks']) ?>

        </span>

        </div>

        <!-- ==========================================
            ACTION
        ========================================== -->

        <div class="reaper-action-cell">

            <button
            type="button"
            class="reaper-edit-btn"
            data-id="<?=
                (int)(
                    $row['reaper_id']
                    ??
                    0
                )
            ?>"
            title="Edit Reaper Rental"
            aria-label="Edit Reaper Rental">

                <i class="bi bi-pencil-square"></i>

            </button>

        </div>

    </div>

    <?php

        }

    }else{

    ?>

        <div class="reaper-empty">
            No reaper records found.
        </div>

    <?php

    }

    ?>

    <div
        id="reaperFilterEmpty"
        class="reaper-empty reaper-filter-empty"
        style="display:none;">

        <i class="bi bi-search"></i>

        <strong>No records found.</strong>

        <span>
            No reaper records match the selected filters.
        </span>

    </div>

    </div>

</div>

<!-- =====================================
     DATE FILTER MODAL
===================================== -->

<div
class="modal fade"
id="reaperDateFilterModal">

    <div
    class="modal-dialog modal-dialog-centered">

        <div
        class="modal-content reaper-window">

            <div class="reaper-header">

                <h1>

                    FILTER DATE

                </h1>

                <button
                type="button"
                class="reaper-close-btn"
                data-bs-dismiss="modal">

                    ×

                </button>

            </div>

            <div class="mb-3">

                <label>

                    START DATE

                </label>

                <input
                type="date"
                id="reaperStartDateFilter"
                class="reaper-input">

            </div>

            <div class="mb-4">

                <label>

                    END DATE

                </label>

                <input
                type="date"
                id="reaperEndDateFilter"
                class="reaper-input">

            </div>

            <div
            class="d-flex justify-content-center gap-3">

                <button
                type="button"
                id="reaperClearDateFilter"
                class="reaper-clear-btn">

                    CLEAR FILTER

                </button>

                <button
                type="button"
                id="reaperApplyDateFilter"
                class="reaper-apply-btn">

                    APPLY

                </button>

            </div>

        </div>

    </div>

</div>

<?php include 'add/add_rental_reaper.php'; ?>

<?php include 'modal/edit_modal_reaper.php'; ?>

<script src="../js/rental/reaper.js?v=<?= time() ?>"></script>

<?php include "generate_reaper/report_reaper_modal.php"; ?>

<script src="generate_reaper/report_reaper.js"></script>