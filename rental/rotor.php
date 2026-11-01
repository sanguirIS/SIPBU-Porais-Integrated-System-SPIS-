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
   SYNC TRACTOR STATUS
====================================================== */

include 'tractor/tractor_status_sync.php';


/* ======================================================
   ROTOR TRACTOR GUARD

   Direct access to Rotor is not allowed when there
   is no active Rotor tractor in the registry.
====================================================== */

$requiredTractorType =
'Rotor';

include 'tractor/tractor_guard.php';


/* ======================================================
   SIDEBAR

   Important:
   Sidebar comes AFTER the guard so header redirects
   can still work correctly.
====================================================== */

include '../includes/sidebar.php';


/* ======================================================
   CURRENT ROTOR PRICE
====================================================== */

$rotorPriceQuery =
mysqli_query(
    $conn,
    "
    SELECT
        price_per_hectare
    FROM rotor_price
    ORDER BY id DESC
    LIMIT 1
    "
);


$rotorPrice =
0;


if(
    $rotorPriceQuery
    &&
    mysqli_num_rows(
        $rotorPriceQuery
    ) > 0
){

    $priceRow =
    mysqli_fetch_assoc(
        $rotorPriceQuery
    );


    $rotorPrice =
    (float)$priceRow[
        'price_per_hectare'
    ];

}

?>

<link rel="stylesheet" href="../css/rental.css">

<link rel="stylesheet" href="../css/rental/rotor.css?v=<?= time() ?>">

<link rel="stylesheet" href="generate_rotor/report_rotor.css">

<div class="page-header">

    <h2>RENTAL - ROTOR</h2>

    <div class="rotor-header-buttons">

        <button
        type="button"
        class="btn-generate-rotor-report"
        data-bs-toggle="modal"
        data-bs-target="#generateRotorReportModal">

            <i class="bi bi-printer-fill"></i>

            GENERATE REPORT

        </button>

        <button
        id="setPriceBtn"
        class="btn-set-price"
        data-bs-toggle="modal"
        data-bs-target="#setPriceModal">

            <i class="bi bi-cash-stack"></i>

            SET PRICE

        </button>

    </div>

</div>

<hr>

<div class="rotor-toolbar">

    <div class="rotor-toolbar-left">

        <a href="rental.php" class="rotor-back-btn">

            <i class="bi bi-arrow-left"></i>

        </a>

        <div class="rotor-search-box">

            <i class="bi bi-search"></i>

            <input
            type="text"
            id="searchRotor"
            placeholder="Search">

        </div>

    </div>

    <div class="rotor-toolbar-right">

        <button
        class="date-filter-btn"
        data-bs-toggle="modal"
        data-bs-target="#rotorDateFilterModal">

            <i class="bi bi-calendar3"></i>

        </button>

        <select
        id="rotorAffiliateFilter"
        class="rotor-filter">

            <option value="all">All Affiliate</option>
            <option value="Member">Member</option>
            <option value="Not Member">Not Member</option>

        </select>

        <select
        id="rotorStatusFilter"
        class="rotor-filter">

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
        id="rotorRemarksFilter"
        class="rotor-filter">

            <option value="all">All Remarks</option>
            <option value="Unpaid">Unpaid</option>
            <option value="Paid">Paid</option>

        </select>

        <button
        type="button"
        id="addRotorBtn"
        class="btn-add-rotor">

            ADD RENTAL

        </button>

        <input
        type="hidden"
        id="rotorCurrentPrice"
        value="<?= $rotorPrice ?>">

</div>

</div>

<!-- ======================================================
     ROTOR LEDGER
====================================================== -->

<div class="rotor-ledger">


    <!-- ==================================================
         LEDGER HEADER
    ================================================== -->

    <div class="rotor-ledger-header">

        <div>
            TRANSACTION
        </div>

        <div>
            CUSTOMER
        </div>

        <div>
            AREA
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
            BALANCE
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

    <div class="rotor-ledger-body">


        <?php


        /* ==================================================
           ROTOR LEDGER QUERY

           Registry / Members are used for current display.

           Stored values remain fallback snapshots.
        ================================================== */

        $rotorQuery =

        mysqli_query(
            $conn,
            "
            SELECT

                r.*,


                /* ==========================================
                   CURRENT PRIMARY TRACTOR
                ========================================== */

                pt.status
                AS primary_tractor_status,

                pt.tractor_no
                AS current_primary_tractor_no,

                pt.tractor_name
                AS current_primary_tractor_name,


                /* ==========================================
                   CURRENT SECONDARY TRACTOR
                ========================================== */

                st.status
                AS secondary_tractor_status,

                st.tractor_no
                AS current_secondary_tractor_no,

                st.tractor_name
                AS current_secondary_tractor_name,


                /* ==========================================
                   CURRENT PRIMARY DRIVER
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
                   CURRENT CUSTOMER
                ========================================== */

                cm.full_name
                AS current_customer_name


            FROM rotor r


            LEFT JOIN tractors pt

                ON pt.tractor_id =
                r.tractor_id


            LEFT JOIN tractors st

                ON st.tractor_id =
                r.secondary_tractor_id


            LEFT JOIN drivers pd

                ON pd.driver_id =
                r.primary_driver_id


            LEFT JOIN members pm

                ON pm.member_id =
                pd.member_id


            LEFT JOIN drivers sd

                ON sd.driver_id =
                r.secondary_driver_id


            LEFT JOIN members sm

                ON sm.member_id =
                sd.member_id


            LEFT JOIN members cm

                ON cm.member_id =
                r.member_id


            ORDER BY

                r.rotor_id DESC
            "
        );


        if(
            $rotorQuery
            &&
            mysqli_num_rows(
                $rotorQuery
            ) > 0
        ){


            while(
                $row =
                mysqli_fetch_assoc(
                    $rotorQuery
                )
            ){


                /* ==========================================
                   RENTAL DATE
                ========================================== */

                $rentalDate =

                trim(
                    (string)(
                        $row[
                            'rental_date'
                        ]
                        ??
                        ''
                    )
                );


                $rentalDateKey =
                '';


                if(
                    $rentalDate !== ''
                ){

                    $rentalTimestamp =

                    strtotime(
                        $rentalDate
                    );


                    if(
                        $rentalTimestamp
                        !==
                        false
                    ){

                        $rentalDateKey =

                        date(
                            'Y-m-d',
                            $rentalTimestamp
                        );

                    }

                }


                $todayDate =

                date(
                    'Y-m-d'
                );


                $isFutureRental =

                (
                    $rentalDateKey
                    !==
                    ''

                    &&

                    $rentalDateKey
                    >
                    $todayDate
                );


                /* ==========================================
                   CURRENT STATUS
                ========================================== */

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


                /* ==========================================
                   PRIMARY MAINTENANCE
                ========================================== */

                $primaryMaintenanceAffected =

                (
                    (
                        (int)(
                            $row[
                                'tractor_id'
                            ]
                            ??
                            0
                        )
                        >
                        0
                    )

                    &&

                    (
                        (
                            $row[
                                'primary_tractor_status'
                            ]
                            ??
                            ''
                        )
                        ===
                        'Maintenance'
                    )

                    &&

                    in_array(
                        $currentRentalStatus,
                        [
                            'Pending',
                            'Ongoing'
                        ],
                        true
                    )
                );


                /* ==========================================
                   SECONDARY MAINTENANCE
                ========================================== */

                $secondaryMaintenanceAffected =

                (
                    (
                        (int)(
                            $row[
                                'secondary_tractor_id'
                            ]
                            ??
                            0
                        )
                        >
                        0
                    )

                    &&

                    (
                        (
                            $row[
                                'secondary_tractor_status'
                            ]
                            ??
                            ''
                        )
                        ===
                        'Maintenance'
                    )

                    &&

                    in_array(
                        $currentRentalStatus,
                        [
                            'Pending',
                            'Ongoing'
                        ],
                        true
                    )
                );


                /* ==========================================
                   ANY MAINTENANCE
                ========================================== */

                $tractorMaintenanceAffected =

                (
                    $primaryMaintenanceAffected
                    ||
                    $secondaryMaintenanceAffected
                );


                /* ==========================================
                   FINAL LOCKS
                ========================================== */

                $statusLocked =

                (
                    $currentRentalStatus
                    ===
                    'Done'
                );


                /* ==========================================
                   HISTORICAL / AUDIT ENTRY

                   Permanent marker from creation.
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

                   Past + Pending
                   = Missed

                   Past + Ongoing
                   = Delayed

                   reschedule_count > 0
                   = Rescheduled

                   Missed / Delayed have priority.
                ========================================== */

                $scheduleFlag =
                '';


                $rescheduleCount =

                (int)(
                    $row[
                        'reschedule_count'
                    ]
                    ??
                    0
                );


                if(
                    $rentalDateKey
                    !==
                    ''

                    &&

                    $rentalDateKey
                    <
                    $todayDate
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


                if(
                    $scheduleFlag
                    ===
                    ''

                    &&

                    $rescheduleCount
                    >
                    0
                ){

                    $scheduleFlag =
                    'Rescheduled';

                }


                /* ==========================================
                   CURRENT CUSTOMER NAME
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
                    $currentCustomerName
                    ===
                    ''
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


                /* ==========================================
                   PRIMARY TRACTOR
                ========================================== */

                $primaryTractorNo =

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


                $primaryTractorName =

                trim(
                    (string)(
                        $row[
                            'current_primary_tractor_name'
                        ]
                        ??
                        ''
                    )
                );


                /* ==========================================
                   SECONDARY TRACTOR
                ========================================== */

                $secondaryTractorNo =

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


                $secondaryTractorName =

                trim(
                    (string)(
                        $row[
                            'current_secondary_tractor_name'
                        ]
                        ??
                        ''
                    )
                );


                /* ==========================================
                   PRIMARY DRIVER
                ========================================== */

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


                /* ==========================================
                   SECONDARY DRIVER
                ========================================== */

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


                /* ==========================================
                   SECONDARY ASSIGNMENT
                ========================================== */

                $hasSecondaryAssignment =

                (
                    $secondaryTractorNo
                    !==
                    ''

                    ||

                    $secondaryDriver
                    !==
                    ''
                );


                /* ==========================================
                   AFFILIATE
                ========================================== */

                $affiliate =

                trim(
                    (string)(
                        $row[
                            'affiliate'
                        ]
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
                   TRANSACTION ID
                ========================================== */

                $fullTransactionID =

                trim(
                    (string)(
                        $row[
                            'transaction_id'
                        ]
                        ??
                        ''
                    )
                );


                /*
                   RTR-20260918-0001

                   becomes

                   RTR-0001
                */

                $shortTransactionID =

                preg_replace(
                    '/^RTR-\d{8}-/',
                    'RTR-',
                    $fullTransactionID
                );


                /* ==========================================
                   SEARCH TEXT
                ========================================== */

                $searchText =

                strtolower(

                    $fullTransactionID

                    .
                    ' '

                    .
                    $shortTransactionID

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
                    $primaryTractorNo

                    .
                    ' '

                    .
                    $primaryTractorName

                    .
                    ' '

                    .
                    $primaryDriver

                    .
                    ' '

                    .
                    $secondaryTractorNo

                    .
                    ' '

                    .
                    $secondaryTractorName

                    .
                    ' '

                    .
                    $secondaryDriver

                    .
                    ' '

                    .
                    (
                        $row[
                            'location'
                        ]
                        ??
                        ''
                    )

                    .
                    ' '

                    .
                    $currentRentalStatus

                    .
                    ' '

                    .
                    (
                        $row[
                            'remarks'
                        ]
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
                rotor-ledger-row
                <?=

                    $tractorMaintenanceAffected

                    ?

                    'rotor-maintenance-alert-row'

                    :

                    ''

                ?>
            "

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
                    $currentRentalStatus,
                    ENT_QUOTES,
                    'UTF-8'
                )

            ?>"

            data-remarks="<?=

                htmlspecialchars(
                    (
                        $row[
                            'remarks'
                        ]
                        ??
                        ''
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                )

            ?>"

            data-date="<?=

                htmlspecialchars(
                    $rentalDateKey,
                    ENT_QUOTES,
                    'UTF-8'
                )

            ?>"

            data-tractor-id="<?=

                (int)(
                    $row[
                        'tractor_id'
                    ]
                    ??
                    0
                )

            ?>"

            data-secondary-tractor-id="<?=

                (int)(
                    $row[
                        'secondary_tractor_id'
                    ]
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

                <div
                class="rotor-transaction-cell"
                title="<?=

                    htmlspecialchars(
                        $fullTransactionID,
                        ENT_QUOTES,
                        'UTF-8'
                    )

                ?>">


                    <span class="rotor-transaction-id">

                        <?=

                            htmlspecialchars(
                                $shortTransactionID,
                                ENT_QUOTES,
                                'UTF-8'
                            )

                        ?>

                    </span>


                    <span class="rotor-transaction-date">

                        <?=

                            $rentalDate
                            !==
                            ''

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


                    <!-- HISTORICAL -->

                    <?php if(
                        $isHistoricalEntry
                    ){ ?>

                        <span
                        class="rotor-audit-flag"
                        title="This rental was created using a Rental Date earlier than its record creation date."
                        >

                            <i class="bi bi-journal-check"></i>

                            Historical / Audit Entry

                        </span>

                    <?php } ?>


                    <!-- SCHEDULE FLAG -->

                    <?php if(
                        $scheduleFlag
                        !==
                        ''
                    ){ ?>

                        <span
                        class="
                            rotor-schedule-flag

                            <?=

                                $scheduleFlag
                                ===
                                'Missed'

                                ?

                                'is-missed'

                                :

                                (
                                    $scheduleFlag
                                    ===
                                    'Delayed'

                                    ?

                                    'is-delayed'

                                    :

                                    'is-rescheduled'
                                )

                            ?>
                        "

                        title="<?=

                            $scheduleFlag
                            ===
                            'Missed'

                            ?

                            'The scheduled Rental Date has passed while the rental is still Pending.'

                            :

                            (
                                $scheduleFlag
                                ===
                                'Delayed'

                                ?

                                'The scheduled Rental Date has passed while the rental is still Ongoing.'

                                :

                                'This rental has been rescheduled from a previous Rental Date.'
                            )

                        ?>"
                        >


                            <i
                            class="<?=

                                $scheduleFlag
                                ===
                                'Missed'

                                ?

                                'bi bi-calendar-x'

                                :

                                (
                                    $scheduleFlag
                                    ===
                                    'Delayed'

                                    ?

                                    'bi bi-clock-history'

                                    :

                                    'bi bi-calendar-check'
                                )

                            ?>">
                            </i>


                            <?=

                                htmlspecialchars(
                                    $scheduleFlag,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )

                            ?>

                        </span>

                    <?php } ?>


                </div>


                <!-- ==========================================
                     CUSTOMER
                ========================================== -->

                <div class="rotor-customer-cell">


                    <span class="rotor-customer-name">

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
                        rotor-affiliate-chip
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
                     AREA
                ========================================== -->

                <div class="rotor-area-cell">


                    <span class="rotor-area-value">

                        <?=

                            number_format(
                                (float)(
                                    $row[
                                        'hectare'
                                    ]
                                    ??
                                    0
                                ),
                                2
                            )

                        ?>

                    </span>


                    <span class="rotor-area-unit">

                        ha

                    </span>


                </div>


                <!-- ==========================================
                     ASSIGNMENT
                ========================================== -->

                <div class="rotor-assignment-cell">


                    <!-- PRIMARY -->

                    <div class="rotor-assignment-primary">


                        <div class="rotor-assignment-label">

                            PRIMARY

                        </div>


                        <div class="rotor-assignment-item">

                            <i class="bi bi-truck-front-fill"></i>


                            <span
                            title="<?=

                                htmlspecialchars(
                                    trim(
                                        $primaryTractorNo
                                        .
                                        ' '
                                        .
                                        $primaryTractorName
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                )

                            ?>">

                                <?=

                                    htmlspecialchars(
                                        (
                                            $primaryTractorNo
                                            !==
                                            ''
                                        )

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


                        <div class="rotor-assignment-item">

                            <i class="bi bi-person-fill"></i>


                            <span
                            title="<?=

                                htmlspecialchars(
                                    $primaryDriver,
                                    ENT_QUOTES,
                                    'UTF-8'
                                )

                            ?>">

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
                            $primaryMaintenanceAffected
                        ){ ?>

                            <span
                            class="rotor-assignment-maintenance">

                                <i class="bi bi-tools"></i>

                                Maintenance

                            </span>

                        <?php } ?>


                    </div>


                    <!-- SECONDARY -->

                    <?php if(
                        $hasSecondaryAssignment
                    ){ ?>


                        <div class="rotor-assignment-secondary">


                            <div class="rotor-assignment-label">

                                SECONDARY

                            </div>


                            <div class="rotor-assignment-item">

                                <i class="bi bi-truck-front"></i>


                                <span
                                title="<?=

                                    htmlspecialchars(
                                        trim(
                                            $secondaryTractorNo
                                            .
                                            ' '
                                            .
                                            $secondaryTractorName
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )

                                ?>">

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


                            <div class="rotor-assignment-item">

                                <i class="bi bi-person"></i>


                                <span
                                title="<?=

                                    htmlspecialchars(
                                        $secondaryDriver,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )

                                ?>">

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


                            <?php if(
                                $secondaryMaintenanceAffected
                            ){ ?>

                                <span
                                class="rotor-assignment-maintenance">

                                    <i class="bi bi-tools"></i>

                                    Maintenance

                                </span>

                            <?php } ?>


                        </div>


                    <?php } ?>


                </div>


                <!-- ==========================================
                     LOCATION
                ========================================== -->

                <div class="rotor-location-cell">

                    <?=

                        htmlspecialchars(
                            (
                                $row[
                                    'location'
                                ]
                                ??
                                ''
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        )

                    ?>

                </div>


                <!-- ==========================================
                     TOTAL
                ========================================== -->

                <div class="rotor-price-cell">


                    <span class="rotor-price-value">

                        ₱<?=

                            number_format(
                                (float)(
                                    $row[
                                        'total_price'
                                    ]
                                    ??
                                    0
                                ),
                                2
                            )

                        ?>

                    </span>


                </div>


                <!-- ==========================================
                     BALANCE
                ========================================== -->

                <div class="rotor-price-cell rotor-balance-cell">


                    <span class="rotor-price-value">

                        ₱<?=

                            number_format(
                                round(
                                    max(
                                        0,
                                        (float)(
                                            $row[
                                                'total_price'
                                            ]
                                            ??
                                            0
                                        )
                                        -
                                        (float)(
                                            $row[
                                                'amount_paid'
                                            ]
                                            ??
                                            0
                                        )
                                    ),
                                    2
                                ),
                                2
                            )

                        ?>

                    </span>


                </div>


                <!-- ==========================================
                     STATUS
                ========================================== -->

                <div class="rotor-status-ledger-cell">


                    <!-- DONE = FINAL -->

                    <?php if(
                        $statusLocked
                    ){ ?>


                        <span
                        class="
                            rotor-ledger-badge
                            rotor-ledger-badge-done
                        ">

                            <i class="bi bi-check2"></i>

                            Done

                        </span>


                    <!-- FUTURE = PENDING LOCK -->

                    <?php }elseif(
                        $isFutureRental
                    ){ ?>


                        <button
                        type="button"
                        class="rotor-future-status-lock"
                        data-rental-date="<?=

                            htmlspecialchars(
                                $rentalDateKey,
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
                        class="
                            status-dropdown
                            rotor-status-select
                        "

                        data-id="<?=

                            (int)(
                                $row[
                                    'rotor_id'
                                ]
                                ??
                                0
                            )

                        ?>"

                        data-date="<?=

                            htmlspecialchars(
                                $rentalDateKey,
                                ENT_QUOTES,
                                'UTF-8'
                            )

                        ?>"
                        >


                            <option
                            value="Pending"

                            <?=

                                $currentRentalStatus
                                ===
                                'Pending'

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

                                $currentRentalStatus
                                ===
                                'Ongoing'

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

                                $currentRentalStatus
                                ===
                                'Done'

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
                            class="rotor-status-lock-note"
                            title="Ongoing and Done are unavailable while an assigned tractor is under Maintenance."
                            >

                                <i class="bi bi-lock-fill"></i>

                                Maintenance

                            </span>


                        <?php } ?>


                    <?php } ?>


                </div>


                <!-- ==========================================
                    REMARKS
                ========================================== -->

                <div class="rotor-remarks-ledger-cell">

                    <?php

                        $paymentRemarks =

                        (
                            (
                                $row[
                                    'remarks'
                                ]
                                ??
                                ''
                            )
                            ===
                            'Paid'
                        )

                        ?

                        'Paid'

                        :

                        'Unpaid';

                    ?>


                    <span
                    class="
                        rotor-ledger-badge

                        <?=

                            $paymentRemarks
                            ===
                            'Paid'

                            ?

                            'rotor-ledger-badge-paid'

                            :

                            'rotor-ledger-badge-unpaid'

                        ?>
                    "
                    >

                        <i
                        class="<?=

                            $paymentRemarks
                            ===
                            'Paid'

                            ?

                            'bi bi-check2'

                            :

                            'bi bi-hourglass-split'

                        ?>">
                        </i>

                        <?= $paymentRemarks ?>

                    </span>

                </div>


                <!-- ==========================================
                     ACTION
                ========================================== -->

                <div class="rotor-action-cell">


                    <button
                    type="button"
                    class="rotor-edit-btn"

                    data-id="<?=

                        (int)(
                            $row[
                                'rotor_id'
                            ]
                            ??
                            0
                        )

                    ?>"

                    title="Edit Rotor Rental"
                    aria-label="Edit Rotor Rental"
                    >

                        <i class="bi bi-pencil-square"></i>

                    </button>


                </div>


            </div>
            <!-- END ROTOR ROW -->


        <?php

            }

        }

        else{

        ?>


            <div class="rotor-empty">

                No Rotor records found.

            </div>


        <?php

        }

        ?>

        <div
            id="rotorFilterEmpty"
            class="rotor-empty rotor-filter-empty"
            style="display:none;">

            <i class="bi bi-search"></i>

            <strong>No records found.</strong>

            <span>
                No rotor records match the selected filters.
            </span>

        </div>

    </div>

</div>

<div
class="modal fade"
id="rotorDateFilterModal">

    <div
    class="modal-dialog modal-dialog-centered">

        <div
        class="modal-content add-product-window">

            <div
            class="window-header">

                <h1>FILTER DATE</h1>

                <button
                type="button"
                class="close-btn"
                data-bs-dismiss="modal">

                    ×

                </button>

            </div>

            <div class="mb-3">

                <label>START DATE</label>

                <input
                type="date"
                id="rotorStartDateFilter"
                class="form-control custom-input">

            </div>

            <div class="mb-4">

                <label>END DATE</label>

                <input
                type="date"
                id="rotorEndDateFilter"
                class="form-control custom-input">

            </div>

            <div
            class="d-flex justify-content-center gap-3">

                <button
                type="button"
                id="clearRotorDateFilter"
                class="rotor-clear-btn">

                    CLEAR FILTER

                </button>

                <button
                type="button"
                id="applyRotorDateFilter"
                class="rotor-apply-btn">

                    APPLY

                </button>

            </div>

        </div>

    </div>

</div>

<?php include 'modal/set_price_rotor.php'; ?>


<!-- ======================================================
     ADD ROTOR MODAL
====================================================== -->

<?php

$pricePerHectare =
$rotorPrice;

include
'add/add_rental_rotor.php';

?>


<!-- ======================================================
     EDIT ROTOR MODAL
====================================================== -->

<?php

include
'edit/edit_rental_rotor.php';

?>


<!-- ======================================================
     GENERATE REPORT
====================================================== -->

<?php

include
"generate_rotor/report_rotor_modal.php";

?>


<!-- ======================================================
     ROTOR JAVASCRIPT
====================================================== -->

<script
src="../js/rental/rotor.js?v=<?= time() ?>">
</script>


<script
src="generate_rotor/report_rotor.js?v=<?= time() ?>">
</script>

<?php if(isset($_GET["price"]) && $_GET["price"]=="updated"){ ?>

<script>

document.addEventListener(

"DOMContentLoaded",

function(){

    Swal.fire({

        icon:"success",

        title:"Rotor price updated successfully.",

        showConfirmButton:false,

        timer:1500

    })

    .then(()=>{

        window.history.replaceState(

            {},

            document.title,

            "rotor.php"

        );

        if(

            sessionStorage.getItem(

                "openRotorAfterPrice"

            ) === "yes"

        ){

            sessionStorage.removeItem(

                "openRotorAfterPrice"

            );

            const modal =

            new bootstrap.Modal(

                document.getElementById(

                    "addRotorModal"

                )

            );

            modal.show();

        }

    });

});

</script>

<?php

}

?>

<?php if(
    isset($_GET["success"])
    &&
    $_GET["success"] == "added"
){ ?>

<script>

document.addEventListener(

"DOMContentLoaded",

function(){

    Swal.fire({

        icon:"success",

        title:"Rotor rental added successfully.",

        showConfirmButton:false,

        timer:1700

    }).then(()=>{

        window.history.replaceState(

            {},

            document.title,

            "rotor.php"

        );

    });

});

</script>

<?php } ?>