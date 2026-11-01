<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}


date_default_timezone_set(
    'Asia/Manila'
);


require_once "../db.php";


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

    header(
        'Location: ../login.php'
    );

    exit();

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
   SYNC TRACTOR STATUS
====================================================== */

include 'tractor/tractor_status_sync.php';


/* ======================================================
   CHECK SHARED TRACTOR POOL

   Tractor Type is classification only.
   Any active registered tractor may be used in
   Hauling, Rotor, or Reaper.
====================================================== */

$tractorQuery =
mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS tractor_count
    FROM tractors
    WHERE archived = 0
    "
);


$registeredTractorCount = 0;


if($tractorQuery){

    $row =
    mysqli_fetch_assoc(
        $tractorQuery
    );


    if($row){

        $registeredTractorCount =
        (int)(
            $row['tractor_count']
            ??
            0
        );

    }

}


$hasRegisteredTractor =
$registeredTractorCount > 0;


/*
All three Rental services use the same tractor pool.
These aliases are kept so the existing card markup
does not need to change.
*/

$hasHaulingTractor =
$hasRegisteredTractor;


$hasRotorTractor =
$hasRegisteredTractor;


$hasReaperTractor =
$hasRegisteredTractor;


/* ======================================================
   SIDEBAR
====================================================== */

include "../includes/sidebar.php";


/* ======================================================
   RENTAL CARD SUMMARY
====================================================== */

function getRentalCardSummary(
    $conn,
    $table
) {
    $summary = [
        'done_count' => 0,
        'pending_count' => 0,
        'ongoing_count' => 0,
        'maintenance_count' => 0,
        'paid_count' => 0,
        'unpaid_count' => 0
    ];

    $allowedTables = [
        'hauling',
        'rotor',
        'reaper'
    ];

    if (
        !in_array(
            $table,
            $allowedTables,
            true
        )
    ) {
        return $summary;
    }

    $query = mysqli_query(
        $conn,
        "
        SELECT
            SUM(
                CASE
                    WHEN r.status = 'Done'
                    THEN 1
                    ELSE 0
                END
            ) AS done_count,

            SUM(
                CASE
                    WHEN r.status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ) AS pending_count,

            SUM(
                CASE
                    WHEN r.status = 'Ongoing'
                    THEN 1
                    ELSE 0
                END
            ) AS ongoing_count,

            SUM(
                CASE
                    WHEN t.status = 'Maintenance'
                    AND r.status IN ('Pending', 'Ongoing')
                    THEN 1
                    ELSE 0
                END
            ) AS maintenance_count,

            SUM(
                CASE
                    WHEN r.remarks = 'Paid'
                    THEN 1
                    ELSE 0
                END
            ) AS paid_count,

            SUM(
                CASE
                    WHEN r.remarks = 'Unpaid'
                    THEN 1
                    ELSE 0
                END
            ) AS unpaid_count

        FROM `$table` r

        LEFT JOIN tractors t
            ON t.tractor_id = r.tractor_id
            AND t.archived = 0
        "
    );

    if (!$query) {
        return $summary;
    }

    $row = mysqli_fetch_assoc($query);

    if (!$row) {
        return $summary;
    }

    foreach (array_keys($summary) as $key) {
        $summary[$key] = (int)(
            $row[$key] ?? 0
        );
    }

    return $summary;
}


/* ======================================================
   STATUS DOT ANIMATION CLASSES
====================================================== */

function getPendingDotAnimationClass(
    $summary
){

    if(
        $summary['pending_count'] <= 0
    ){

        return '';

    }


    if(
        $summary['ongoing_count'] > 0
    ){

        return ' rental-dot-pending-glow';

    }


    return ' rental-dot-status-pulse';

}


function getOngoingDotAnimationClass(
    $summary
){

    if(
        $summary['ongoing_count'] > 0
    ){

        return ' rental-dot-status-pulse';

    }


    return '';

}


/* ======================================================
   LOAD SUMMARY PER RENTAL TYPE
====================================================== */

$haulingSummary =
getRentalCardSummary(
    $conn,
    'hauling'
);


$rotorSummary =
getRentalCardSummary(
    $conn,
    'rotor'
);


$reaperSummary =
getRentalCardSummary(
    $conn,
    'reaper'
);

?>


<link
rel="stylesheet"
href="../css/rental.css?v=<?php echo time();?>">


<link
rel="stylesheet"
href="generate/rental_report.css">


<!-- =====================================================
     HEADER
====================================================== -->

<div
class="page-header d-flex justify-content-between align-items-center">


    <h2 class="m-0">

        RENTAL

    </h2>


    <div class="rental-registry-actions">


        <!-- TRACTOR REGISTRY -->

        <a
        href="tractor/tractor.php"
        class="tractor-registry-btn">

            <i class="bi bi-truck-front-fill"></i>

            <span>
                TRACTOR REGISTRY
            </span>

        </a>


        <!-- DRIVER REGISTRY -->

        <a
        href="driver/driver.php"
        class="driver-registry-btn">

            <i class="bi bi-person-badge-fill"></i>

            <span>
                DRIVER REGISTRY
            </span>

        </a>


    </div>


</div>


<hr>


<!-- =====================================================
     RENTAL CARDS
====================================================== -->

<div class="rental-container">


    <!-- TOP CARDS -->

    <div class="rental-top">


        <!-- =================================================
             HAULING
        ================================================== -->

        <a
        href="hauling.php"
        class="rental-card rental-module-card <?= !$hasHaulingTractor ? 'rental-card-locked' : '' ?>"
        data-ready="<?= $hasHaulingTractor ? '1' : '0' ?>"
        data-type="Hauling">


            <!-- ACTIVE STATUS INDICATORS -->

            <?php if (
                $haulingSummary['maintenance_count'] > 0 ||
                $haulingSummary['pending_count'] > 0 ||
                $haulingSummary['ongoing_count'] > 0
            ): ?>

                <div
                    class="rental-card-status-indicators"
                    aria-label="Active Hauling statuses"
                >

                    <?php if (
                        $haulingSummary['maintenance_count'] > 0
                    ): ?>

                        <span
                            class="rental-maintenance-signal"
                            title="<?= $haulingSummary['maintenance_count'] ?>
                            Hauling rental<?= $haulingSummary['maintenance_count'] === 1 ? '' : 's' ?>
                            affected by Maintenance"
                        >
                            &#9660;
                        </span>

                    <?php endif; ?>


                    <?php if (
                        $haulingSummary['pending_count'] > 0
                    ): ?>

                        <span
                            class="rental-card-status-dot rental-dot-yellow<?= getPendingDotAnimationClass($haulingSummary) ?>"
                            title="<?= $haulingSummary['pending_count'] ?> Pending"
                        ></span>

                    <?php endif; ?>


                    <?php if (
                        $haulingSummary['ongoing_count'] > 0
                    ): ?>

                        <span
                            class="rental-card-status-dot rental-dot-orange<?= getOngoingDotAnimationClass($haulingSummary) ?>"
                            title="<?= $haulingSummary['ongoing_count'] ?> Ongoing"
                        ></span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <!-- NORMAL CARD CONTENT -->

            <div class="rental-card-main">


                <i
                class="bi bi-truck rental-icon">
                </i>


                <h3>
                    HAULING
                </h3>


                <?php if(!$hasHaulingTractor): ?>

                    <div
                    class="rental-lock-indicator">

                        <i
                        class="bi bi-lock-fill">
                        </i>

                        No Registered Tractor

                    </div>

                <?php endif; ?>


            </div>


            <!-- HOVER SUMMARY -->

            <div
            class="rental-summary-overlay"
            aria-hidden="true">


                <div
                class="rental-summary-section">


                    <div
                    class="rental-summary-title">
                        STATUS
                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-green">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $haulingSummary['done_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Done
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-yellow">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $haulingSummary['pending_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Pending
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-orange">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $haulingSummary['ongoing_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Ongoing
                        </span>

                    </div>

                <div
                class="rental-summary-row">

                    <span
                    class="rental-summary-maintenance-icon">
                        &#9660;
                    </span>

                    <span
                    class="rental-summary-count">
                        <?= number_format(
                            $haulingSummary['maintenance_count']
                        ) ?>
                    </span>

                    <span
                    class="rental-summary-label">
                        Maintenance
                    </span>

                </div>    

                </div>


                <div
                class="rental-summary-divider">
                </div>


                <div
                class="rental-summary-section">


                    <div
                    class="rental-summary-title">
                        REMARKS
                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-green">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $haulingSummary['paid_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Paid
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-red">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $haulingSummary['unpaid_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Unpaid
                        </span>

                    </div>


                </div>


            </div>


        </a>


        <!-- =================================================
             REAPER
        ================================================== -->

        <a
        href="reaper.php"
        class="rental-card rental-module-card <?= !$hasReaperTractor ? 'rental-card-locked' : '' ?>"
        data-ready="<?= $hasReaperTractor ? '1' : '0' ?>"
        data-type="Reaper">


            <!-- ACTIVE STATUS INDICATORS -->

            <?php if (
                $reaperSummary['maintenance_count'] > 0 ||
                $reaperSummary['pending_count'] > 0 ||
                $reaperSummary['ongoing_count'] > 0
            ): ?>

                <div
                    class="rental-card-status-indicators"
                    aria-label="Active Reaper statuses"
                >

                    <?php if (
                        $reaperSummary['maintenance_count'] > 0
                    ): ?>

                        <span
                            class="rental-maintenance-signal"
                            title="<?= $reaperSummary['maintenance_count'] ?>
                            Reaper rental<?= $reaperSummary['maintenance_count'] === 1 ? '' : 's' ?>
                            affected by Maintenance"
                        >
                            &#9660;
                        </span>

                    <?php endif; ?>


                    <?php if (
                        $reaperSummary['pending_count'] > 0
                    ): ?>

                        <span
                            class="rental-card-status-dot rental-dot-yellow<?= getPendingDotAnimationClass($reaperSummary) ?>"
                            title="<?= $reaperSummary['pending_count'] ?> Pending"
                        ></span>

                    <?php endif; ?>


                    <?php if (
                        $reaperSummary['ongoing_count'] > 0
                    ): ?>

                        <span
                            class="rental-card-status-dot rental-dot-orange<?= getOngoingDotAnimationClass($reaperSummary) ?>"
                            title="<?= $reaperSummary['ongoing_count'] ?> Ongoing"
                        ></span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <!-- NORMAL CARD CONTENT -->

            <div class="rental-card-main">


                <i
                class="bi bi-tree rental-icon">
                </i>


                <h3>
                    REAPER
                </h3>


                <?php if(!$hasReaperTractor): ?>

                    <div
                    class="rental-lock-indicator">

                        <i
                        class="bi bi-lock-fill">
                        </i>

                        No Registered Tractor

                    </div>

                <?php endif; ?>


            </div>


            <!-- HOVER SUMMARY -->

            <div
            class="rental-summary-overlay"
            aria-hidden="true">


                <div
                class="rental-summary-section">


                    <div
                    class="rental-summary-title">
                        STATUS
                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-green">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $reaperSummary['done_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Done
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-yellow">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $reaperSummary['pending_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Pending
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-orange">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $reaperSummary['ongoing_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Ongoing
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-maintenance-icon">
                            &#9660;
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $reaperSummary['maintenance_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Maintenance
                        </span>

                    </div>


                </div>


                <div
                class="rental-summary-divider">
                </div>


                <div
                class="rental-summary-section">


                    <div
                    class="rental-summary-title">
                        REMARKS
                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-green">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $reaperSummary['paid_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Paid
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-red">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $reaperSummary['unpaid_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Unpaid
                        </span>

                    </div>


                </div>


            </div>


        </a>


    </div>


    <!-- BOTTOM CARD -->

    <div class="rental-bottom">


        <!-- =================================================
             ROTOR
        ================================================== -->

        <a
        href="rotor.php"
        class="rental-card rental-module-card <?= !$hasRotorTractor ? 'rental-card-locked' : '' ?>"
        data-ready="<?= $hasRotorTractor ? '1' : '0' ?>"
        data-type="Rotor">


            <!-- ACTIVE STATUS INDICATORS -->

            <?php if (
                $rotorSummary['maintenance_count'] > 0 ||
                $rotorSummary['pending_count'] > 0 ||
                $rotorSummary['ongoing_count'] > 0
            ): ?>

                <div
                    class="rental-card-status-indicators"
                    aria-label="Active Rotor statuses"
                >

                    <?php if (
                        $rotorSummary['maintenance_count'] > 0
                    ): ?>

                        <span
                            class="rental-maintenance-signal"
                            title="<?= $rotorSummary['maintenance_count'] ?>
                            Rotor rental<?= $rotorSummary['maintenance_count'] === 1 ? '' : 's' ?>
                            affected by Maintenance"
                        >
                            &#9660;
                        </span>

                    <?php endif; ?>


                    <?php if (
                        $rotorSummary['pending_count'] > 0
                    ): ?>

                        <span
                            class="rental-card-status-dot rental-dot-yellow<?= getPendingDotAnimationClass($rotorSummary) ?>"
                            title="<?= $rotorSummary['pending_count'] ?> Pending"
                        ></span>

                    <?php endif; ?>


                    <?php if (
                        $rotorSummary['ongoing_count'] > 0
                    ): ?>

                        <span
                            class="rental-card-status-dot rental-dot-orange<?= getOngoingDotAnimationClass($rotorSummary) ?>"
                            title="<?= $rotorSummary['ongoing_count'] ?> Ongoing"
                        ></span>

                    <?php endif; ?>

                </div>

            <?php endif; ?>


            <!-- NORMAL CARD CONTENT -->

            <div class="rental-card-main">


                <i
                class="bi bi-gear-wide-connected rental-icon">
                </i>


                <h3>
                    ROTOR
                </h3>


                <?php if(!$hasRotorTractor): ?>

                    <div
                    class="rental-lock-indicator">

                        <i
                        class="bi bi-lock-fill">
                        </i>

                        No Registered Tractor

                    </div>

                <?php endif; ?>


            </div>


            <!-- HOVER SUMMARY -->

            <div
            class="rental-summary-overlay"
            aria-hidden="true">


                <div
                class="rental-summary-section">


                    <div
                    class="rental-summary-title">
                        STATUS
                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-green">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $rotorSummary['done_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Done
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-yellow">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $rotorSummary['pending_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Pending
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-orange">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $rotorSummary['ongoing_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Ongoing
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-maintenance-icon">
                            &#9660;
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $rotorSummary['maintenance_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Maintenance
                        </span>

                    </div>


                </div>


                <div
                class="rental-summary-divider">
                </div>


                <div
                class="rental-summary-section">


                    <div
                    class="rental-summary-title">
                        REMARKS
                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-green">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $rotorSummary['paid_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Paid
                        </span>

                    </div>


                    <div
                    class="rental-summary-row">

                        <span
                        class="rental-summary-dot rental-dot-red">
                        </span>

                        <span
                        class="rental-summary-count">
                            <?= number_format(
                                $rotorSummary['unpaid_count']
                            ) ?>
                        </span>

                        <span
                        class="rental-summary-label">
                            Unpaid
                        </span>

                    </div>


                </div>


            </div>


        </a>


    </div>


</div>


<!-- =====================================================
     RENTAL ACCESS VALIDATION
====================================================== -->

<script>

document
.querySelectorAll(".rental-module-card")
.forEach(function(card){


    card.addEventListener(
        "click",
        function(e){


            if(
                this.dataset.ready === "1"
            ){

                return;

            }


            e.preventDefault();


            Swal.fire({

                icon:"warning",

                title:
                "No Registered Tractor",

                text:
                "Please register at least one tractor first before accessing a rental service.",

                confirmButtonText:
                "OK",

                confirmButtonColor:
                "#8CA15B"

            });

        }
    );

});

</script>


<!-- =====================================================
     DIRECT URL REDIRECT ALERT
====================================================== -->

<?php

if(
    isset($_GET['error'])
    &&
    $_GET['error'] === 'no_tractor'
){

?>

<script>

Swal.fire({

    icon:"warning",

    title:
    "No Registered Tractor",

    text:
    "Please register at least one tractor first before accessing a rental service.",

    confirmButtonColor:
    "#8CA15B"

});

</script>

<?php

}

?>


<script src="../js/sidebar.js"></script>


</body>

</html>