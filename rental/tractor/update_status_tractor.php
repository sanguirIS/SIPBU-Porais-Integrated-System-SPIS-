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
require_once '../../includes/history_logger.php';


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

    echo
    'Your session has expired. Please sign in again.';

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

    echo
    'Unable to verify Rental access.';

    exit();
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

    echo
    'Unable to verify Rental access.';

    exit();
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

    echo
    'You do not have permission to manage the Tractor Registry.';

    exit();
}


/* ======================================================
   POST ONLY
====================================================== */

if(
    $_SERVER['REQUEST_METHOD']
    !==
    'POST'
){

    echo 'Invalid request.';

    exit();

}


/* ======================================================
   CSRF VALIDATION
====================================================== */

$sessionCsrfToken =
(string)(
    $_SESSION['tractor_csrf_token']
    ??
    ''
);


$requestCsrfToken =
(string)(
    $_POST['csrf_token']
    ??
    ''
);


if(
    $sessionCsrfToken === ''
    ||
    $requestCsrfToken === ''
    ||
    !hash_equals(
        $sessionCsrfToken,
        $requestCsrfToken
    )
){

    http_response_code(403);

    exit(
        'Invalid or expired form request. Please refresh the page and try again.'
    );
}


/* ======================================================
   GET REQUEST VALUES
====================================================== */
$tractorID =
isset($_POST['tractor_id'])
?
(int)$_POST['tractor_id']
:
0;


$status =
trim(
    $_POST['status']
    ??
    ''
);


$checkOnly =
isset($_POST['check_only'])
&&
$_POST['check_only'] === '1';


$confirmMaintenance =
isset($_POST['confirm_maintenance'])
&&
$_POST['confirm_maintenance'] === '1';


/* ======================================================
   VALIDATE TRACTOR ID
====================================================== */

if($tractorID <= 0){

    echo 'Invalid tractor.';

    exit();

}

/* ======================================================
   VALID MANUAL STATUSES

   Reserved and In Use remain automatic.
====================================================== */

$allowedStatuses = [

    'Available',
    'Maintenance'

];


if(
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
){

    echo 'Invalid tractor status.';

    exit();

}


/* ======================================================
   GET TRACTOR INFORMATION
====================================================== */

$tractorStmt =
mysqli_prepare(
    $conn,
    "
    SELECT
        tractor_id,
        tractor_no,
        tractor_name,
        tractor_type,
        status
    FROM tractors
    WHERE tractor_id = ?
    AND archived = 0
    LIMIT 1
    "
);


if(!$tractorStmt){

    echo 'Unable to check tractor.';

    exit();

}


mysqli_stmt_bind_param(
    $tractorStmt,
    'i',
    $tractorID
);


mysqli_stmt_execute(
    $tractorStmt
);


$tractorResult =
mysqli_stmt_get_result(
    $tractorStmt
);


if(
    !$tractorResult
    ||
    mysqli_num_rows($tractorResult) === 0
){

    mysqli_stmt_close(
        $tractorStmt
    );


    echo 'Tractor was not found.';

    exit();

}


$tractor =
mysqli_fetch_assoc(
    $tractorResult
);


mysqli_stmt_close(
    $tractorStmt
);


$oldStatus =
$tractor['status'];


$tractorType =
$tractor['tractor_type'];


/* ======================================================
   AUTOMATIC STATUS CANNOT BE MANUALLY CHANGED

   Reserved
   = Pending rental today

   In Use
   = Active/Ongoing rental today

   Both statuses are controlled automatically by
   the Rental modules and Tractor Status Sync.
====================================================== */

if(
    in_array(
        $oldStatus,
        [
            'Reserved',
            'In Use'
        ],
        true
    )
){

    echo
    'This tractor has an automatic Rental status and cannot be changed manually.';

    exit();

}


/* ======================================================
   MAINTENANCE RENTAL CHECK

   CROSS-RENTAL RULE:

   Tractor Type is classification only.

   Before placing ANY tractor under Maintenance,
   check Hauling, Rotor, and Reaper for active or
   upcoming assignments.

   Primary + Secondary tractors are included.
====================================================== */

if($status === 'Maintenance'){

    $rentalStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            rental_type,
            transaction_id,
            rental_date,
            status

        FROM
        (
            /* ==========================================
               HAULING
            ========================================== */

            SELECT
                'Hauling' AS rental_type,
                h.transaction_id,
                DATE(h.rental_date) AS rental_date,
                h.status,
                h.hauling_id AS rental_id

            FROM hauling h

            WHERE
            (
                h.tractor_id = ?

                OR

                h.secondary_tractor_id = ?
            )

            AND DATE(h.rental_date) >= CURDATE()

            AND h.status IN (
                'Pending',
                'Ongoing'
            )


            UNION ALL


            /* ==========================================
               ROTOR
            ========================================== */

            SELECT
                'Rotor' AS rental_type,
                r.transaction_id,
                DATE(r.rental_date) AS rental_date,
                r.status,
                r.rotor_id AS rental_id

            FROM rotor r

            WHERE
            (
                r.tractor_id = ?

                OR

                r.secondary_tractor_id = ?
            )

            AND DATE(r.rental_date) >= CURDATE()

            AND r.status IN (
                'Pending',
                'Ongoing'
            )


            UNION ALL


            /* ==========================================
               REAPER
            ========================================== */

            SELECT
                'Reaper' AS rental_type,
                rp.transaction_id,
                DATE(rp.rental_date) AS rental_date,
                rp.status,
                rp.reaper_id AS rental_id

            FROM reaper rp

            WHERE
            (
                rp.tractor_id = ?

                OR

                rp.secondary_tractor_id = ?
            )

            AND DATE(rp.rental_date) >= CURDATE()

            AND rp.status IN (
                'Pending',
                'Ongoing'
            )

        ) AS tractor_rentals

        ORDER BY
            rental_date ASC,
            rental_type ASC,
            rental_id ASC
        "
    );


    if(!$rentalStmt){

        echo
        'Unable to check upcoming rentals.';

        exit();

    }


    mysqli_stmt_bind_param(
        $rentalStmt,
        'iiiiii',
        $tractorID,
        $tractorID,
        $tractorID,
        $tractorID,
        $tractorID,
        $tractorID
    );


    if(
        !mysqli_stmt_execute(
            $rentalStmt
        )
    ){

        mysqli_stmt_close(
            $rentalStmt
        );


        echo
        'Unable to check upcoming rentals.';

        exit();

    }


    $rentalResult =
    mysqli_stmt_get_result(
        $rentalStmt
    );


    $upcomingRentals = [];


    if($rentalResult){

        while(
            $rental =
            mysqli_fetch_assoc(
                $rentalResult
            )
        ){

            $upcomingRentals[] = [

                'rental_type' =>
                $rental['rental_type'],

                'transaction_id' =>
                $rental['transaction_id'],

                'rental_date' =>
                $rental['rental_date'],

                'status' =>
                $rental['status']

            ];

        }

    }


    mysqli_stmt_close(
        $rentalStmt
    );


    /* ==================================================
       CHECK REQUEST ONLY

       Do not update the database yet.
    ================================================== */

    if($checkOnly){

        if(
            count(
                $upcomingRentals
            ) === 0
        ){

            echo 'maintenance_clear';

            exit();

        }


        $warningData = [

            'tractor_no' =>
            $tractor['tractor_no'],

            'tractor_name' =>
            $tractor['tractor_name'],

            'tractor_type' =>
            $tractorType,

            'rentals' =>
            $upcomingRentals

        ];


        echo
        'maintenance_warning:'
        .
        json_encode(
            $warningData,
            JSON_UNESCAPED_UNICODE
            |
            JSON_UNESCAPED_SLASHES
        );


        exit();

    }


    /* ==================================================
       FINAL MAINTENANCE CONFIRMATION
    ================================================== */

    if(!$confirmMaintenance){

        echo
        'Please confirm the Maintenance status change.';

        exit();

    }

}


/* ======================================================
   INVALID CHECK-ONLY REQUEST
====================================================== */

elseif($checkOnly){

    echo
    'Invalid maintenance check.';

    exit();

}


/* ======================================================
   DETERMINE ACTUAL FINAL STATUS
====================================================== */

$finalStatus =
$status;


/* ======================================================
   REMOVING MAINTENANCE

   Determine the real physical status for TODAY
   across ALL Rental services.

   CROSS-RENTAL RULES:

   HAULING:
   - Ongoing = In Use
   - Pending = Reserved

   ROTOR:
   - Ongoing = In Use
   - Pending = Reserved

   REAPER:
   - Pending / Ongoing = In Use

   Primary + Secondary assignments are included.

   Priority:
   1. In Use
   2. Reserved
   3. Available
====================================================== */

if($status === 'Available'){

    $activeRentalStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            physical_status

        FROM
        (
            /* ==========================================
               HAULING
            ========================================== */

            SELECT

                CASE
                    WHEN h.status = 'Ongoing'
                        THEN 'In Use'

                    ELSE 'Reserved'
                END
                AS physical_status,

                CASE
                    WHEN h.status = 'Ongoing'
                        THEN 1

                    ELSE 2
                END
                AS status_priority,

                h.hauling_id
                AS rental_id

            FROM hauling h

            WHERE
                DATE(h.rental_date) = CURDATE()

            AND h.status IN (
                'Pending',
                'Ongoing'
            )

            AND
            (
                h.tractor_id = ?

                OR

                h.secondary_tractor_id = ?
            )


            UNION ALL


            /* ==========================================
               ROTOR
            ========================================== */

            SELECT

                CASE
                    WHEN r.status = 'Ongoing'
                        THEN 'In Use'

                    ELSE 'Reserved'
                END
                AS physical_status,

                CASE
                    WHEN r.status = 'Ongoing'
                        THEN 1

                    ELSE 2
                END
                AS status_priority,

                r.rotor_id
                AS rental_id

            FROM rotor r

            WHERE
                DATE(r.rental_date) = CURDATE()

            AND r.status IN (
                'Pending',
                'Ongoing'
            )

            AND
            (
                r.tractor_id = ?

                OR

                r.secondary_tractor_id = ?
            )


            UNION ALL


            /* ==========================================
               REAPER

               Pending / Ongoing today = In Use
            ========================================== */

            SELECT

                'In Use'
                AS physical_status,

                1
                AS status_priority,

                rp.reaper_id
                AS rental_id

            FROM reaper rp

            WHERE
                DATE(rp.rental_date) = CURDATE()

            AND rp.status IN (
                'Pending',
                'Ongoing'
            )

            AND
            (
                rp.tractor_id = ?

                OR

                rp.secondary_tractor_id = ?
            )

        ) AS today_assignments

        ORDER BY
            status_priority ASC,
            rental_id ASC

        LIMIT 1
        "
    );


    if(!$activeRentalStmt){

        echo
        'Unable to check today\'s tractor rental.';

        exit();

    }


    mysqli_stmt_bind_param(
        $activeRentalStmt,
        'iiiiii',
        $tractorID,
        $tractorID,
        $tractorID,
        $tractorID,
        $tractorID,
        $tractorID
    );


    if(
        !mysqli_stmt_execute(
            $activeRentalStmt
        )
    ){

        mysqli_stmt_close(
            $activeRentalStmt
        );


        echo
        'Unable to check today\'s tractor rental.';

        exit();

    }


    $activeRentalResult =
    mysqli_stmt_get_result(
        $activeRentalStmt
    );


    $activeRental =
    $activeRentalResult

    ?

    mysqli_fetch_assoc(
        $activeRentalResult
    )

    :

    null;


    mysqli_stmt_close(
        $activeRentalStmt
    );


    if(!$activeRental){

        $finalStatus =
        'Available';

    }
    else{

        $finalStatus =
        trim(
            (string)(
                $activeRental['physical_status']
                ??
                'Available'
            )
        );

    }

}


/* ======================================================
   UPDATE TRACTOR STATUS
====================================================== */

$updateStmt =
mysqli_prepare(
    $conn,
    "
    UPDATE tractors
    SET status = ?
    WHERE tractor_id = ?
    AND archived = 0
    "
);


if(!$updateStmt){

    echo
    'Unable to prepare status update.';

    exit();

}


mysqli_stmt_bind_param(
    $updateStmt,
    'si',
    $finalStatus,
    $tractorID
);


$updated =
mysqli_stmt_execute(
    $updateStmt
);


$affectedRows =
mysqli_stmt_affected_rows(
    $updateStmt
);


mysqli_stmt_close(
    $updateStmt
);


/* ======================================================
   SAVE HISTORY LOG
====================================================== */

if($updated){

    if(
        $affectedRows > 0
        &&
        $oldStatus !== $finalStatus
    ){

        log_activity(
            $conn,
            'Rental',
            'Updated',
            'changed tractor "' .
            $tractor['tractor_no'] .
            ' - ' .
            $tractor['tractor_name'] .
            '" status from ' .
            $oldStatus .
            ' to ' .
            $finalStatus .
            '.',
            $tractorID
        );

    }


    echo 'success';

    exit();

}


echo 'Unable to update tractor status.';

exit();

?>