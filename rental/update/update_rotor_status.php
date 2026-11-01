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

(int)
$_SESSION['user_id']

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


if(
    !$userStmt
){

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


if(
    !$hasRentalAccess
){

    http_response_code(
        403
    );


    echo
    'You do not have permission to access Rental.';


    exit();

}

/* ======================================================
   SHARED PHYSICAL TRACTOR STATUS SYNC

   Ongoing in any Rental service:
       In Use

   Pending Reaper:
       In Use

   Pending Hauling / Rotor:
       Reserved

   No current assignment today:
       Available

   In Use has priority over Reserved.
   Maintenance is never overwritten.
====================================================== */

function rotorStatusSyncTractorPhysicalState(
    mysqli $conn,
    int $tractorID
): void {

    if($tractorID <= 0){

        return;

    }


    $statusStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            status

        FROM tractors

        WHERE tractor_id = ?

        AND archived = 0

        LIMIT 1

        FOR UPDATE
        "
    );


    if(!$statusStmt){

        throw new Exception(
            'Unable to synchronize tractor status.'
        );

    }


    mysqli_stmt_bind_param(
        $statusStmt,
        'i',
        $tractorID
    );


    if(
        !mysqli_stmt_execute(
            $statusStmt
        )
    ){

        mysqli_stmt_close(
            $statusStmt
        );


        throw new Exception(
            'Unable to synchronize tractor status.'
        );

    }


    $statusResult =
    mysqli_stmt_get_result(
        $statusStmt
    );


    $registry =

        $statusResult

        ?

        mysqli_fetch_assoc(
            $statusResult
        )

        :

        null;


    mysqli_stmt_close(
        $statusStmt
    );


    if(
        !$registry
        ||
        (
            $registry['status']
            ??
            ''
        ) === 'Maintenance'
    ){

        return;

    }


    $hasInUse =
    false;


    $hasReserved =
    false;


    foreach(
        [
            'hauling',
            'rotor',
            'reaper'
        ]
        as
        $table
    ){

        $activeStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                status

            FROM {$table}

            WHERE DATE(rental_date) = CURDATE()

            AND status IN (
                'Pending',
                'Ongoing'
            )

            AND
            (
                tractor_id = ?

                OR

                secondary_tractor_id = ?
            )

            FOR UPDATE
            "
        );


        if(!$activeStmt){

            throw new Exception(
                'Unable to check current tractor use.'
            );

        }


        mysqli_stmt_bind_param(
            $activeStmt,
            'ii',
            $tractorID,
            $tractorID
        );


        if(
            !mysqli_stmt_execute(
                $activeStmt
            )
        ){

            mysqli_stmt_close(
                $activeStmt
            );


            throw new Exception(
                'Unable to check current tractor use.'
            );

        }


        $activeResult =
        mysqli_stmt_get_result(
            $activeStmt
        );


        if($activeResult){

            while(
                $activeRow =
                mysqli_fetch_assoc(
                    $activeResult
                )
            ){

                $activeStatus =
                trim(
                    (string)(
                        $activeRow['status']
                        ??
                        ''
                    )
                );


                if($activeStatus === 'Ongoing'){

                    $hasInUse =
                    true;

                }
                elseif($activeStatus === 'Pending'){

                    if($table === 'reaper'){

                        $hasInUse =
                        true;

                    }
                    else{

                        $hasReserved =
                        true;

                    }

                }

            }

        }


        mysqli_stmt_close(
            $activeStmt
        );


        if($hasInUse){

            break;

        }

    }


    $physicalStatus =

        $hasInUse

        ?

        'In Use'

        :

        (
            $hasReserved

            ?

            'Reserved'

            :

            'Available'
        );


    $syncStmt =
    mysqli_prepare(
        $conn,
        "
        UPDATE tractors

        SET status = ?

        WHERE tractor_id = ?

        AND archived = 0

        AND status != 'Maintenance'
        "
    );


    if(!$syncStmt){

        throw new Exception(
            'Unable to synchronize tractor status.'
        );

    }


    mysqli_stmt_bind_param(
        $syncStmt,
        'si',
        $physicalStatus,
        $tractorID
    );


    if(
        !mysqli_stmt_execute(
            $syncStmt
        )
    ){

        mysqli_stmt_close(
            $syncStmt
        );


        throw new Exception(
            'Unable to synchronize tractor status.'
        );

    }


    mysqli_stmt_close(
        $syncStmt
    );

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
    $_SESSION['rental_csrf_token']
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
   GET VALUES
====================================================== */

$rotorID =
isset($_POST['id'])
?
(int)$_POST['id']
:
0;


$status =
trim(
    $_POST['status']
    ??
    ''
);


/* ======================================================
   BASIC VALIDATION
====================================================== */

if($rotorID <= 0){

    echo 'Invalid Rotor record.';

    exit();

}


$allowedStatuses = [

    'Pending',

    'Ongoing',

    'Done'

];


if(
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
){

    echo 'Invalid Rotor status.';

    exit();

}


/* ======================================================
   START TRANSACTION
====================================================== */

mysqli_begin_transaction(
    $conn
);


try{


    /* ==================================================
       GET + LOCK ROTOR RECORD

       MySQL CURDATE() is used so all date rules
       follow the same database/server date.
    ================================================== */

    $checkStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            rotor_id,
            transaction_id,
            customer_name,
            tractor_no,
            tractor_id,

            secondary_tractor_no,
            secondary_tractor_id,

            actual_started_date,
            completed_date,

            status AS current_status,
            DATE(rental_date) AS rental_day,
            CURDATE() AS today
        FROM rotor
        WHERE rotor_id = ?
        LIMIT 1
        FOR UPDATE
        "
    );


    if(!$checkStmt){

        throw new Exception(
            'Unable to check Rotor record.'
        );

    }


    mysqli_stmt_bind_param(
        $checkStmt,
        'i',
        $rotorID
    );


    if(
        !mysqli_stmt_execute(
            $checkStmt
        )
    ){

        mysqli_stmt_close(
            $checkStmt
        );


        throw new Exception(
            'Unable to check Rotor record.'
        );

    }


    $result =
    mysqli_stmt_get_result(
        $checkStmt
    );


    if(
        !$result
        ||
        mysqli_num_rows(
            $result
        ) === 0
    ){

        mysqli_stmt_close(
            $checkStmt
        );


        throw new Exception(
            'Rotor record was not found.'
        );

    }


    $rotor =
    mysqli_fetch_assoc(
        $result
    );


    mysqli_stmt_close(
        $checkStmt
    );


    $currentStatus =
    $rotor['current_status']
    ??
    '';


    $transactionID =
    $rotor['transaction_id']
    ??
    '';


    $customerName =
    $rotor['customer_name']
    ??
    '';


    $tractorNo =
    $rotor['tractor_no']
    ??
    '';


    $rentalDay =
    $rotor['rental_day']
    ??
    '';


    $today =
    $rotor['today']
    ??
    '';


    $tractorID =
    isset(
        $rotor['tractor_id']
    )
    ?
    (int)$rotor['tractor_id']
    :
    0;

    $secondaryTractorNo =
    $rotor['secondary_tractor_no']
    ??
    '';


    $secondaryTractorID =
        isset(
            $rotor['secondary_tractor_id']
        )
        ?
        (int)$rotor['secondary_tractor_id']
        :
        0;

        $currentActualStartedDate =
    trim(
        (string)(
            $rotor['actual_started_date']
            ??
            ''
        )
    );


    $currentCompletedDate =
    trim(
        (string)(
            $rotor['completed_date']
            ??
            ''
        )
    );


    if(
        $rentalDay === ''
        ||
        $today === ''
    ){

        throw new Exception(
            'Unable to verify the Rotor rental date.'
        );

    }


    /* ==================================================
       DONE IS FINAL

       Valid Done records for today/past cannot return
       to Pending or Ongoing.

       A legacy future record incorrectly saved as Done
       may still be corrected back to Pending.
    ================================================== */

    if(
        $currentStatus === 'Done'
        &&
        $rentalDay <= $today
        &&
        $status !== 'Done'
    ){

        throw new Exception(
            'A completed Rotor rental can no longer be changed.'
        );

    }


    /* ==================================================
       FUTURE DATE

       Pending only.
    ================================================== */

    if($rentalDay > $today){

        if($status !== 'Pending'){

            throw new Exception(
                'A future Rotor rental must remain Pending until its service date.'
            );

        }

    }


    /* ==================================================
       PAST DATE

       Pending or Done only.
       Ongoing is not valid for a past service date.
    ================================================== */

    if(
        $rentalDay < $today
        &&
        $status === 'Ongoing'
    ){

        throw new Exception(
            'A past Rotor rental cannot be changed to Ongoing.'
        );

    }

    /* ==================================================
    DELAYED ONGOING

    A past Ongoing rental is Delayed.

    It cannot return directly to Pending through the
    ledger status endpoint.

    Returning to Pending is only valid when the rental
    is actually rescheduled through Edit Rental.
    ================================================== */

    if(
        $rentalDay < $today
        &&
        $currentStatus === 'Ongoing'
        &&
        $status === 'Pending'
    ){

        throw new Exception(
            'A Delayed Rotor rental can only return to Pending when it is rescheduled through Edit Rental.'
        );

    }


    /* ==================================================
    MAINTENANCE STATUS GUARD

    Checks BOTH Primary and Secondary Rotor tractors.

    If either assigned tractor is under Maintenance,
    an unresolved rental must remain Pending.
    ================================================== */

    $assignedTractors = [

        [
            'id' => $tractorID,
            'label' => 'Primary Tractor'
        ]

    ];


    if($secondaryTractorID > 0){

        $assignedTractors[] = [

            'id' => $secondaryTractorID,

            'label' => 'Secondary Tractor'

        ];

    }


    foreach(
        $assignedTractors
        as
        $assignedTractor
    ){

        $assignedTractorID =
        (int)(
            $assignedTractor['id']
            ??
            0
        );


        $assignmentLabel =
        $assignedTractor['label']
        ??
        'Assigned Tractor';


        if($assignedTractorID <= 0){

            continue;

        }


        $maintenanceStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                status

            FROM tractors

            WHERE tractor_id = ?

            AND archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );


        if(!$maintenanceStmt){

            throw new Exception(
                'Unable to verify tractor maintenance status.'
            );

        }


        mysqli_stmt_bind_param(
            $maintenanceStmt,
            'i',
            $assignedTractorID
        );


        if(
            !mysqli_stmt_execute(
                $maintenanceStmt
            )
        ){

            mysqli_stmt_close(
                $maintenanceStmt
            );


            throw new Exception(
                'Unable to verify tractor maintenance status.'
            );

        }


        $maintenanceResult =
        mysqli_stmt_get_result(
            $maintenanceStmt
        );


        if(
            $maintenanceResult
            &&
            mysqli_num_rows(
                $maintenanceResult
            ) > 0
        ){

            $registryTractor =
            mysqli_fetch_assoc(
                $maintenanceResult
            );


            $registryStatus =
            $registryTractor['status']
            ??
            '';


            if(
                $registryStatus === 'Maintenance'
                &&
                $status !== 'Pending'
            ){

                mysqli_stmt_close(
                    $maintenanceStmt
                );


                throw new Exception(

                    $assignmentLabel
                    .
                    ' is under Maintenance. This Rotor rental must remain Pending until the assignment is updated.'

                );

            }

        }


        mysqli_stmt_close(
            $maintenanceStmt
        );

    }


    /* ==================================================
    UPDATE ROTOR STATUS + LIFECYCLE DATES

    Pending
    = no actual start / completion

    Ongoing
    = actual start is recorded

    Done
    = completion is recorded

    If the status is unchanged, skip the database
    update and History log, but continue recalculating
    the physical tractor status.
    ================================================== */

    $statusChanged =
    $status !== $currentStatus;


    $newActualStartedDate =
    $currentActualStartedDate !== ''
    ?
    $currentActualStartedDate
    :
    null;


    $newCompletedDate =
    $currentCompletedDate !== ''
    ?
    $currentCompletedDate
    :
    null;


    if($statusChanged){

        if($status === 'Pending'){

            $newActualStartedDate =
            null;


            $newCompletedDate =
            null;

        }
        elseif($status === 'Ongoing'){

            if($newActualStartedDate === null){

                $newActualStartedDate =
                $today;

            }


            $newCompletedDate =
            null;

        }
        elseif($status === 'Done'){

            if($newActualStartedDate === null){

                $newActualStartedDate =
                $rentalDay;

            }


            $newCompletedDate =
            $today;

        }


        $updateStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE rotor

            SET
                status = ?,
                actual_started_date = ?,
                completed_date = ?

            WHERE rotor_id = ?
            "
        );


        if(!$updateStmt){

            throw new Exception(
                'Unable to prepare Rotor status update.'
            );

        }


        mysqli_stmt_bind_param(
            $updateStmt,
            'sssi',
            $status,
            $newActualStartedDate,
            $newCompletedDate,
            $rotorID
        );


        if(
            !mysqli_stmt_execute(
                $updateStmt
            )
        ){

            mysqli_stmt_close(
                $updateStmt
            );


            throw new Exception(
                'Unable to update Rotor status.'
            );

        }


        mysqli_stmt_close(
            $updateStmt
        );

    }


    /* ==================================================
    RECALCULATE PHYSICAL TRACTOR STATUS

    Physical registry status changes apply only when
    THIS Rotor rental is scheduled for TODAY.

    Future and past Rotor records must not physically
    alter tractor registry status.

    For TODAY, BOTH Primary and Secondary tractors are
    recalculated across Hauling + Rotor + Reaper:

    Ongoing in any Rental service = In Use
    Pending Reaper                = In Use
    Pending Hauling / Rotor       = Reserved
    None                          = Available

    Maintenance is never overwritten.
    ================================================== */

    if($rentalDay === $today){

        foreach(
            $assignedTractors
            as
            $assignedTractor
        ){

            $assignedTractorID =
            (int)(
                $assignedTractor['id']
                ??
                0
            );


            if($assignedTractorID <= 0){

                continue;

            }


            rotorStatusSyncTractorPhysicalState(
                $conn,
                $assignedTractorID
            );

        }

    }

    /* ==================================================
       SAVE HISTORY LOG
    ================================================== */

    if($statusChanged){

        log_activity(
            $conn,
            'Rental',
            'Updated',
            'changed the Rotor rental "'
            .
            $transactionID
            .
            '" for "'
            .
            $customerName
            .
            '" using tractor "'
            .
            $tractorNo
            .
            '" status from '
            .
            $currentStatus
            .
            ' to '
            .
            $status
            .
            '.',
            $rotorID
        );

    }


    /* ==================================================
       COMMIT
    ================================================== */

    mysqli_commit(
        $conn
    );


    echo 'success';

    exit();


}
catch(Exception $error){


    mysqli_rollback(
        $conn
    );


    echo
    $error->getMessage();


    exit();

}

?>