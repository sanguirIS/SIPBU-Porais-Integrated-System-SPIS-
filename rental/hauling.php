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


error_reporting(E_ALL);

ini_set(
    'display_errors',
    '0'
);


/* ======================================================
   DATABASE
====================================================== */

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
        ? (int) $_SESSION['user_id']
        : 0;


if (
    $userID <= 0
) {

    header(
        'Location: ../login.php'
    );

    exit();
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


if (
    !$accessStmt
) {

    http_response_code(
        500
    );

    exit(
        'Unable to verify Rental access.'
    );
}


mysqli_stmt_bind_param(
    $accessStmt,
    'i',
    $userID
);


if (
    !mysqli_stmt_execute(
        $accessStmt
    )
) {

    mysqli_stmt_close(
        $accessStmt
    );


    http_response_code(
        500
    );

    exit(
        'Unable to verify Rental access.'
    );
}


$accessResult =
    mysqli_stmt_get_result(
        $accessStmt
    );


$hasRentalAccess =
    (
        $accessResult
        &&
        mysqli_num_rows(
            $accessResult
        ) === 1
    );


mysqli_stmt_close(
    $accessStmt
);


if (
    !$hasRentalAccess
) {

    http_response_code(
        403
    );

    exit(
        'You do not have permission to access Rental.'
    );
}


/* ======================================================
   SYNC CURRENT TRACTOR STATUS
====================================================== */

include 'tractor/tractor_status_sync.php';


/* ======================================================
   TRACTOR AVAILABILITY GUARD

   Tractor Type is classification only.
   tractor_guard.php checks the shared tractor pool.
====================================================== */

$requiredTractorType =
    'Hauling';


include 'tractor/tractor_guard.php';


/* ======================================================
   SIDEBAR
====================================================== */

include '../includes/sidebar.php';


/* ======================================================
   HAULING LEDGER

   Join tractors so the ledger always reads the CURRENT
   physical status of the assigned tractor.
====================================================== */

$haulingQuery =
mysqli_query(
    $conn,
    "
    SELECT

        h.*,

        /* ==========================================
           CURRENT PRIMARY TRACTOR
        ========================================== */

        pt.status
        AS tractor_status,

        pt.tractor_no
        AS current_primary_tractor_no,

        pt.tractor_name
        AS current_primary_tractor_name,


        /* ==========================================
           CURRENT SECONDARY TRACTOR
        ========================================== */

        st.tractor_no
        AS current_secondary_tractor_no,

        st.tractor_name
        AS current_secondary_tractor_name,


        /* ==========================================
           CURRENT PRIMARY DRIVER

           Member is the master identity.
           Driver copy is fallback.
        ========================================== */

        COALESCE(
            NULLIF(
                TRIM(
                    pm.full_name
                ),
                ''
            ),
            NULLIF(
                TRIM(
                    pd.full_name
                ),
                ''
            )
        )
        AS current_primary_driver_name,


        /* ==========================================
           CURRENT SECONDARY DRIVER
        ========================================== */

        COALESCE(
            NULLIF(
                TRIM(
                    sm.full_name
                ),
                ''
            ),
            NULLIF(
                TRIM(
                    sd.full_name
                ),
                ''
            )
        )
        AS current_secondary_driver_name,


        /* ==========================================
           CURRENT MEMBER CUSTOMER
        ========================================== */

        cm.full_name
        AS current_customer_name


    FROM hauling h


    LEFT JOIN tractors pt

        ON pt.tractor_id =
        h.tractor_id


    LEFT JOIN tractors st

        ON st.tractor_id =
        h.secondary_tractor_id


    LEFT JOIN drivers pd

        ON pd.driver_id =
        h.primary_driver_id


    LEFT JOIN members pm

        ON pm.member_id =
        pd.member_id


    LEFT JOIN drivers sd

        ON sd.driver_id =
        h.secondary_driver_id


    LEFT JOIN members sm

        ON sm.member_id =
        sd.member_id


    LEFT JOIN members cm

        ON cm.member_id =
        h.member_id


    ORDER BY
        h.hauling_id DESC
    "
);

?>

<link rel="stylesheet" href="../css/rental.css">

<link
rel="stylesheet"
href="../css/rental/hauling.css?v=<?= time() ?>">

<link rel="stylesheet" href="generate_hauling/report_hauling.css">


<div class="page-header d-flex align-items-center">

    <h2 class="m-0">

        RENTAL - HAULING

    </h2>


    <button
    type="button"
    class="btn-generate-hauling-report ms-3"
    data-bs-toggle="modal"
    data-bs-target="#generateHaulingReportModal">

        <i class="bi bi-printer-fill"></i>

        GENERATE REPORT

    </button>

</div>


<hr>


<div class="hauling-toolbar">


    <div class="hauling-toolbar-left">


        <a
        href="rental.php"
        class="hauling-back-btn">

            <i class="bi bi-arrow-left"></i>

        </a>


        <div class="hauling-search-box">

            <i class="bi bi-search"></i>

            <input
            type="text"
            id="searchHauling"
            placeholder="Search">

        </div>


    </div>


    <div class="hauling-toolbar-right">


        <button
        class="btn-calendar"
        data-bs-toggle="modal"
        data-bs-target="#dateFilterModal">

            <i class="bi bi-calendar3"></i>

        </button>


        <select
        id="affiliateFilter"
        class="hauling-filter">

            <option value="all">
                All Affiliate
            </option>

            <option value="Member">
                Member
            </option>

            <option value="Not Member">
                Not Member
            </option>

        </select>


        <select
        id="statusFilter"
        class="hauling-filter">

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
        class="hauling-filter">

            <option value="all">
                All Remarks
            </option>

            <option value="Unpaid">
                Unpaid
            </option>

            <option value="Paid">
                Paid
            </option>

        </select>


        <button
        class="btn-add-hauling"
        data-bs-toggle="modal"
        data-bs-target="#addhaulingModal">

            ADD RENTAL

        </button>


    </div>


</div>


<!-- ======================================================
     HAULING LEDGER
====================================================== -->

<div class="hauling-ledger">


    <!-- ==================================================
         LEDGER HEADER
    ================================================== -->

    <div class="hauling-ledger-header">

        <div>
            TRANSACTION
        </div>

        <div>
            CUSTOMER
        </div>

        <div>
            NO. OF
            <br>
            HAULING
        </div>

        <div>
            ASSIGNMENT
        </div>

        <div>
            LOCATION
        </div>

        <div>
            TOTAL
        </div>

        <div>
            STATUS
        </div>

        <div>
            REMARKS
        </div>

        <div>
            ACTION
        </div>

    </div>


    <!-- ==================================================
         LEDGER BODY
    ================================================== -->

    <div class="hauling-ledger-body">


        <?php

        if(
            $haulingQuery
            &&
            mysqli_num_rows(
                $haulingQuery
            ) > 0
        ){


            while(
                $row =
                mysqli_fetch_assoc(
                    $haulingQuery
                )
            ){


                /* ==========================================
                   PRIMARY TRACTOR MAINTENANCE
                ========================================== */

                $tractorMaintenanceAffected =
                (
                    (int)(
                        $row['tractor_id']
                        ??
                        0
                    ) > 0

                    &&

                    (
                        $row['tractor_status']
                        ??
                        ''
                    )
                    ===
                    'Maintenance'

                    &&

                    in_array(
                        $row['status'],
                        [
                            'Pending',
                            'Ongoing'
                        ],
                        true
                    )
                );


                /* ==========================================
                   STATUS LOCK

                   Done is final.
                ========================================== */

                $statusLocked =
                (
                    (
                        $row['status']
                        ??
                        ''
                    )
                    ===
                    'Done'
                );


                /* ==========================================
                   REMARKS / PAYMENT LOCK

                   Once Paid:
                   - no dropdown anymore
                   - cannot change back from ledger
                ========================================== */

                $remarksLocked =
                (
                    (
                        $row['remarks']
                        ??
                        ''
                    )
                    ===
                    'Paid'
                );

                /* ==========================================
                   HISTORICAL / AUDIT ENTRY

                   Permanent creation-time marker.

                   This is separate from:
                   - Missed
                   - Delayed
                   - Rescheduled
                ========================================== */

                $isHistoricalEntry =
                (
                    (int)(
                        $row['is_historical_entry']
                        ??
                        0
                    )
                    ===
                    1
                );

                /* ==========================================
                   SCHEDULE FLAG

                   PRIORITY:

                   1. Past + Pending = Missed
                   2. Past + Ongoing = Delayed
                   3. Previously rescheduled = Rescheduled

                   IMPORTANT:
                   - Missed / Delayed describe CURRENT state.
                   - Rescheduled is persistent history.
                   - Flag is NOT the Rental status.
                ========================================== */

                $scheduleFlag =
                '';


                $scheduleDate =
                trim(
                    (string)(
                        $row['rental_date']
                        ??
                        ''
                    )
                );


                $scheduleDateKey =
                '';


                if(
                    $scheduleDate !== ''
                ){

                    $scheduleTimestamp =
                    strtotime(
                        $scheduleDate
                    );


                    if(
                        $scheduleTimestamp !== false
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

                $isFutureRental =
                (
                    $scheduleDateKey !== ''
                    &&
                    $scheduleDateKey > $todayDate
                );


                $currentRentalStatus =
                trim(
                    (string)(
                        $row['status']
                        ??
                        ''
                    )
                );


                $rescheduleCount =
                (int)(
                    $row['reschedule_count']
                    ??
                    0
                );


                /*
                   CURRENT PROBLEM FLAGS

                   These take priority over the historical
                   Rescheduled indicator.
                */

                if(
                    $scheduleDateKey !== ''
                    &&
                    $scheduleDateKey < $todayDate
                ){

                    if(
                        $currentRentalStatus
                        ===
                        'Pending'
                    ){

                        $scheduleFlag =
                        'Missed';

                    }
                    elseif(
                        $currentRentalStatus
                        ===
                        'Ongoing'
                    ){

                        $scheduleFlag =
                        'Delayed';

                    }

                }


                /*
                   RESCHEDULED

                   Show only when the record is not currently
                   Missed or Delayed.

                   If the rescheduled date passes again while
                   still Pending/Ongoing, Missed/Delayed takes
                   priority.
                */

                if(
                    $scheduleFlag === ''
                    &&
                    $rescheduleCount > 0
                ){

                    $scheduleFlag =
                    'Rescheduled';

                }

                /* ==========================================
                   CURRENT LINKED DISPLAY VALUES

                   Registry / Members are the source of truth.
                   Stored Hauling values remain fallback only.
                ========================================== */

                $currentCustomerName =
                trim(
                    (string)(
                        $row[
                            'current_customer_name'
                        ]
                        ??
                        ''
                    )
                );


                if(
                    $currentCustomerName === ''
                ){

                    $currentCustomerName =
                    trim(
                        (string)(
                            $row[
                                'customer_name'
                            ]
                            ??
                            ''
                        )
                    );

                }


                $currentPrimaryTractorNo =
                trim(
                    (string)(
                        $row[
                            'current_primary_tractor_no'
                        ]
                        ??
                        $row[
                            'tractor_no'
                        ]
                        ??
                        ''
                    )
                );


                $currentSecondaryTractorNo =
                trim(
                    (string)(
                        $row[
                            'current_secondary_tractor_no'
                        ]
                        ??
                        $row[
                            'secondary_tractor_no'
                        ]
                        ??
                        ''
                    )
                );


                /* ==========================================
                   SECONDARY ASSIGNMENT
                ========================================== */

                $secondaryTractorNo =
                $currentSecondaryTractorNo;

                $primaryDriver =
                trim(
                    (string)(
                        $row[
                            'current_primary_driver_name'
                        ]
                        ??
                        $row[
                            'operator'
                        ]
                        ??
                        ''
                    )
                );


                $secondaryDriver =
                trim(
                    (string)(
                        $row[
                            'current_secondary_driver_name'
                        ]
                        ??
                        $row[
                            'secondary_operator'
                        ]
                        ??
                        ''
                    )
                );


                $hasSecondaryAssignment =
                (
                    $secondaryTractorNo !== ''
                    ||
                    $secondaryDriver !== ''
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


                /* ==========================================
                   SEARCH TEXT
                ========================================== */

                $searchText =
                strtolower(

                    (
                        $row['transaction_id']
                        ??
                        ''
                    )

                    .
                    ' '

                    .
                    preg_replace(
                        '/^HLG-\d{8}-/',
                        'HLG-',
                        trim(
                            (string)(
                                $row['transaction_id']
                                ??
                                ''
                            )
                        )
                    )

                    .
                    ' '

                    .
                    $currentCustomerName

                    .
                    ' '

                    .
                    $affiliate

                    .
                    ' '

                    .
                    $primaryDriver

                    .
                    ' '

                    .
                    $secondaryDriver

                    .
                    ' '

                    .
                    (
                        $row['location']
                        ??
                        ''
                    )

                    .
                    ' '

                    .
                    $currentPrimaryTractorNo

                    .
                    ' '

                    .
                    $secondaryTractorNo

                    .
                    ' '

                    .
                    (
                        $row['status']
                        ??
                        ''
                    )

                    .
                    ' '

                    .
                    (
                        $row['remarks']
                        ??
                        ''
                    )

                    .
                    ' '

                    .
                    $scheduleFlag

                );


        ?>


        <!-- ==================================================
             LEDGER ROW
        ================================================== -->

        <div
        class="
            hauling-row
            <?=
                $tractorMaintenanceAffected
                ?
                'hauling-maintenance-alert-row'
                :
                ''
            ?>
        "

        data-id="<?=
            (int)(
                $row['hauling_id']
                ??
                0
            )
        ?>"

        data-search="<?=
            htmlspecialchars(
                $searchText,
                ENT_QUOTES,
                'UTF-8'
            )
        ?>"

        data-affiliate="<?=
            htmlspecialchars(
                $affiliate,
                ENT_QUOTES,
                'UTF-8'
            )
        ?>"

        data-status="<?=
            htmlspecialchars(
                (
                    $row['status']
                    ??
                    ''
                ),
                ENT_QUOTES,
                'UTF-8'
            )
        ?>"

        data-remarks="<?=
            htmlspecialchars(
                (
                    $row['remarks']
                    ??
                    ''
                ),
                ENT_QUOTES,
                'UTF-8'
            )
        ?>"

        data-flag="<?=
            htmlspecialchars(
                $scheduleFlag,
                ENT_QUOTES,
                'UTF-8'
            )
        ?>"

        data-date="<?=
            htmlspecialchars(
                (
                    $row['rental_date']
                    ??
                    ''
                ),
                ENT_QUOTES,
                'UTF-8'
            )
        ?>"

        data-tractor-id="<?=
            (int)(
                $row['tractor_id']
                ??
                0
            )
        ?>"

        data-maintenance="<?=
            $tractorMaintenanceAffected
            ?
            '1'
            :
            '0'
        ?>"
        >


            <!-- ==========================================
                TRANSACTION
            ========================================== -->

            <?php

            $fullTransactionID =
            trim(
                (string)(
                    $row['transaction_id']
                    ??
                    ''
                )
            );


            /*
                HLG-20260912-0017
                becomes
                HLG-0017
            */

            $shortTransactionID =
            preg_replace(
                '/^HLG-\d{8}-/',
                'HLG-',
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

            ?>


            <div
            class="hauling-transaction-cell"
            title="<?=
                htmlspecialchars(
                    $fullTransactionID,
                    ENT_QUOTES,
                    'UTF-8'
                )
            ?>">


                <span class="hauling-transaction-id">

                    <?=
                        htmlspecialchars(
                            $shortTransactionID,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ?>

                </span>


                <span class="hauling-transaction-date">

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

                <?php if(
                    $isHistoricalEntry
                ){ ?>

                    <span
                    class="hauling-audit-flag"
                    title="This rental was created using a Rental Date earlier than its record creation date."
                    >

                        <i class="bi bi-journal-check"></i>

                        Historical / Audit Entry

                    </span>

                <?php } ?>

                <?php if(
                    $scheduleFlag !== ''
                ){ ?>

                    <span
                    class="
                        hauling-schedule-flag
                        <?=
                            $scheduleFlag === 'Missed'
                            ?
                            'is-missed'
                            :
                            (
                                $scheduleFlag === 'Delayed'
                                ?
                                'is-delayed'
                                :
                                'is-rescheduled'
                            )
                        ?>
                    "
                    title="<?=
                        $scheduleFlag === 'Missed'
                        ?
                        'The scheduled rental date has passed while the rental is still Pending.'
                        :
                        (
                            $scheduleFlag === 'Delayed'
                            ?
                            'The scheduled rental date has passed while the rental is still Ongoing.'
                            :
                            'This rental has been rescheduled from a previous Rental Date.'
                        )
                    ?>"
                    >

                        <i
                        class="<?=
                            $scheduleFlag === 'Missed'
                            ?
                            'bi bi-calendar-x'
                            :
                            (
                                $scheduleFlag === 'Delayed'
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

            <div class="hauling-customer-cell">

                <span class="hauling-customer-name">

                    <?=
                        htmlspecialchars(
                            $currentCustomerName,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ?>

                </span>


                <span
                class="
                    hauling-affiliate-chip
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
                 NO. OF HAULING
            ========================================== -->

            <div class="hauling-count-cell">

                <?=
                    htmlspecialchars(
                        (
                            $row['hauling_count']
                            ??
                            ''
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ?>

            </div>


            <!-- ==========================================
                 ASSIGNMENT
            ========================================== -->

            <div class="hauling-assignment-cell">


                <!-- PRIMARY -->

                <div class="hauling-assignment-primary">


                    <div class="hauling-assignment-label">

                        PRIMARY

                    </div>


                    <div class="hauling-assignment-item">

                        <i class="bi bi-truck-front-fill"></i>

                        <span>

                            <?=
                                htmlspecialchars(
                                    (
                                        $currentPrimaryTractorNo
                                        !==
                                        ''
                                    )
                                    ?
                                    $currentPrimaryTractorNo
                                    :
                                    'No Tractor',
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ?>

                        </span>

                    </div>


                    <div class="hauling-assignment-item">

                        <i class="bi bi-person-fill"></i>

                        <span>

                            <?=
                                htmlspecialchars(
                                    (
                                        $primaryDriver
                                        !==
                                        ''
                                    )
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


                    <?php if(
                        $tractorMaintenanceAffected
                    ){ ?>


                        <span class="hauling-assignment-maintenance">

                            <i class="bi bi-tools"></i>

                            Maintenance

                        </span>


                    <?php } ?>


                </div>


                <!-- SECONDARY -->


                <?php if(
                    $hasSecondaryAssignment
                ){ ?>


                    <div class="hauling-assignment-secondary">


                        <div class="hauling-assignment-label">

                            SECONDARY

                        </div>


                        <div class="hauling-assignment-item">

                            <i class="bi bi-truck-front"></i>

                            <span>

                                <?=
                                    htmlspecialchars(
                                        (
                                            $secondaryTractorNo
                                            !==
                                            ''
                                        )
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


                        <div class="hauling-assignment-item">

                            <i class="bi bi-person"></i>

                            <span>

                                <?=
                                    htmlspecialchars(
                                        (
                                            $secondaryDriver
                                            !==
                                            ''
                                        )
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


            <!-- ==========================================
                 LOCATION
            ========================================== -->

            <div class="hauling-location-cell">

                <?=
                    htmlspecialchars(
                        (
                            $row['location']
                            ??
                            ''
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    )
                ?>

            </div>


            <!-- ==========================================
                 TOTAL PRICE
            ========================================== -->

            <div class="hauling-price-cell">

                <span class="hauling-price-value">

                    ₱<?= number_format(
                        (float)(
                            $row['total_price']
                            ??
                            0
                        ),
                        2
                    ) ?>

                </span>

            </div>


            <!-- ==========================================
                 STATUS
            ========================================== -->

            <div class="hauling-status-ledger-cell">


                <?php if(
                    $statusLocked
                ){ ?>


                    <span
                    class="
                        hauling-ledger-badge
                        hauling-ledger-badge-done
                    ">

                        <i class="bi bi-check2"></i>

                        Done

                    </span>


                <?php }elseif(
                    $isFutureRental
                ){ ?>


                    <button
                    type="button"
                    class="hauling-future-status-lock"
                    data-rental-date="<?=
                        htmlspecialchars(
                            $scheduleDateKey,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ?>"
                    title="Status can only be updated when the Rental Date arrives."
                    >

                        <i class="bi bi-lock-fill"></i>

                        Pending

                    </button>


                <?php }else{ ?>


                    <select
                    class="status-dropdown"
                    data-id="<?=
                        (int)(
                            $row['hauling_id']
                            ??
                            0
                        )
                    ?>"
                    >


                        <option
                        value="Pending"
                        <?=
                            (
                                (
                                    $row['status']
                                    ??
                                    ''
                                )
                                ===
                                'Pending'
                            )
                            ?
                            'selected'
                            :
                            ''
                        ?>
                        >

                            Pending

                        </option>


                        <option
                        value="Ongoing"
                        <?=
                            (
                                (
                                    $row['status']
                                    ??
                                    ''
                                )
                                ===
                                'Ongoing'
                            )
                            ?
                            'selected'
                            :
                            ''
                        ?>

                        <?=
                            $tractorMaintenanceAffected
                            ?
                            'disabled'
                            :
                            ''
                        ?>
                        >

                            Ongoing

                        </option>


                        <option
                        value="Done"
                        <?=
                            (
                                (
                                    $row['status']
                                    ??
                                    ''
                                )
                                ===
                                'Done'
                            )
                            ?
                            'selected'
                            :
                            ''
                        ?>

                        <?=
                            $tractorMaintenanceAffected
                            ?
                            'disabled'
                            :
                            ''
                        ?>
                        >

                            Done

                        </option>


                    </select>


                    <?php if(
                        $tractorMaintenanceAffected
                    ){ ?>


                        <span
                        class="hauling-status-lock-note"
                        title="Ongoing and Done are unavailable while the assigned tractor is under Maintenance."
                        >

                            <i class="bi bi-lock-fill"></i>

                            Maintenance

                        </span>


                    <?php } ?>


                <?php } ?>


            </div>


            <!-- ==========================================
                REMARKS / PAYMENT STATUS
            ========================================== -->

            <div class="hauling-remarks-ledger-cell">

                <?php if(
                    $remarksLocked
                ){ ?>

                    <span
                    class="
                        hauling-ledger-badge
                        hauling-ledger-badge-paid
                    "
                    title="Paid payment status is final."
                    >

                        <i class="bi bi-lock-fill"></i>

                        Paid

                    </span>

                <?php }else{ ?>

                    <select
                    class="remarks-dropdown"
                    data-id="<?=
                        (int)(
                            $row['hauling_id']
                            ??
                            0
                        )
                    ?>"
                    >

                        <option
                        value="Unpaid"
                        <?=
                            (
                                (
                                    $row['remarks']
                                    ??
                                    ''
                                )
                                ===
                                'Unpaid'
                            )
                            ?
                            'selected'
                            :
                            ''
                        ?>
                        >
                            Unpaid
                        </option>

                        <option value="Paid">
                            Paid
                        </option>

                    </select>

                <?php } ?>

            </div>


            <!-- ==========================================
                ACTION
            ========================================== -->

            <div class="hauling-action-cell">

                <button
                type="button"
                class="hauling-edit-btn"
                data-id="<?=
                    (int)(
                        $row['hauling_id']
                        ??
                        0
                    )
                ?>"
                title="Edit Hauling Rental"
                aria-label="Edit Hauling Rental">

                    <i class="bi bi-pencil-square"></i>

                </button>

            </div>


            </div>
            <!-- END .hauling-row -->


           <?php

    }

}
else{

?>


    <div class="hauling-empty">

        <i class="bi bi-truck-front"></i>

        <strong>
            No hauling records found.
        </strong>
        
    </div>


<?php

}

?>


    <div
    id="haulingFilterEmpty"
    class="hauling-empty"
    style="display:none;">

        <i class="bi bi-search"></i>

        <strong>
            No records found.
        </strong>

        <span>
            No hauling records match the selected filters.
        </span>

    </div>


    </div>
    <!-- END .hauling-ledger-body -->


</div>
<!-- END .hauling-ledger -->

<!-- ======================================================
     DATE FILTER MODAL
====================================================== -->

<div
class="modal fade"
id="dateFilterModal">


    <div
    class="modal-dialog modal-dialog-centered">


        <div
        class="modal-content add-product-window">


            <div class="window-header">


                <h1>
                    FILTER DATE
                </h1>


                <button
                type="button"
                class="close-btn"
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
                id="startDateFilter"
                class="form-control custom-input">


            </div>


            <div class="mb-4">


                <label>
                    END DATE
                </label>


                <input
                type="date"
                id="endDateFilter"
                class="form-control custom-input">


            </div>


            <div
            class="
            d-flex
            justify-content-center
            gap-3
            ">


                <button
                type="button"
                id="clearDateFilter"
                class="btn btn-light">

                    CLEAR FILTER

                </button>


                <button
                type="button"
                id="applyDateFilter"
                class="btn-add-sales">

                    APPLY

                </button>


            </div>


        </div>


    </div>


</div>


<!-- ======================================================
     ADD HAULING MODAL
====================================================== -->

<?php

include 'add/add_rental_hauling.php';

?>

<!-- ======================================================
     EDIT HAULING MODAL
====================================================== -->

<?php

include 'edit/edit_rental_hauling.php';

?>


<!-- ======================================================
     SUCCESS MESSAGE
====================================================== -->

<?php


if(
    isset(
        $_GET['success']
    )
    &&
    $_GET['success']
    ===
    'added'
){


?>


<script>


document.addEventListener(

    "DOMContentLoaded",

    function(){


        Swal.fire({

            icon:
            "success",

            title:
            "Hauling rental added successfully.",

            showConfirmButton:
            false,

            timer:
            1500

        })

        .then(()=>{


            window.history.replaceState(

                {},

                document.title,

                "hauling.php"

            );


        });


    }

);


</script>


<?php


}


?>


<!-- ======================================================
     GENERATE REPORT
====================================================== -->

<?php

include
"generate_hauling/report_hauling_modal.php";

?>


<!-- ======================================================
     HAULING JAVASCRIPT
====================================================== -->

<script src="../js/rental/hauling.js?v=<?= time() ?>"></script>


<script
src="generate_hauling/report_hauling.js">
</script>