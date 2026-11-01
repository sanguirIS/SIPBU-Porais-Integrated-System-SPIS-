<?php

/* =====================================================
   SPIS - UPDATE HAULING STATUS
===================================================== */

if (
    session_status() === PHP_SESSION_NONE
) {
    session_start();
}


date_default_timezone_set(
    "Asia/Manila"
);


error_reporting(E_ALL);
ini_set("display_errors", "0");


require_once '../../db.php';
require_once '../../includes/history_logger.php';


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =====================================================
   RESPONSE HELPER
===================================================== */

function haulingStatusResponse(
    string $message,
    int $statusCode = 200
): void {

    http_response_code(
        $statusCode
    );

    echo $message;
    exit();
}


/* =====================================================
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
===================================================== */

function haulingStatusSyncTractorPhysicalState(
    mysqli $conn,
    int $tractorID
): void {

    if (
        $tractorID <= 0
    ) {

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


    if (
        !$statusStmt
    ) {

        throw new Exception(
            'Unable to synchronize tractor status.'
        );
    }


    mysqli_stmt_bind_param(
        $statusStmt,
        'i',
        $tractorID
    );


    if (
        !mysqli_stmt_execute(
            $statusStmt
        )
    ) {

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


    if (
        !$registry
        ||
        (
            $registry['status']
            ??
            ''
        ) === 'Maintenance'
    ) {

        return;
    }


    $hasInUse =
        false;


    $hasReserved =
        false;


    foreach (
        [
            'hauling',
            'rotor',
            'reaper'
        ]
        as
        $table
    ) {

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


        if (
            !$activeStmt
        ) {

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


        if (
            !mysqli_stmt_execute(
                $activeStmt
            )
        ) {

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


        if (
            $activeResult
        ) {

            while (
                $activeRow =
                    mysqli_fetch_assoc(
                        $activeResult
                    )
            ) {

                $activeStatus =
                    trim(
                        (string) (
                            $activeRow['status']
                            ??
                            ''
                        )
                    );


                if (
                    $activeStatus === 'Ongoing'
                ) {

                    $hasInUse =
                        true;
                }
                elseif (
                    $activeStatus === 'Pending'
                ) {

                    if (
                        $table === 'reaper'
                    ) {

                        $hasInUse =
                            true;
                    }
                    else {

                        $hasReserved =
                            true;
                    }
                }
            }
        }


        mysqli_stmt_close(
            $activeStmt
        );


        if (
            $hasInUse
        ) {

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


    if (
        !$syncStmt
    ) {

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


    if (
        !mysqli_stmt_execute(
            $syncStmt
        )
    ) {

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

/* =====================================================
   POST REQUEST ONLY
===================================================== */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
) {

    haulingStatusResponse(
        'Invalid request.',
        405
    );
}


/* =====================================================
   SESSION / RENTAL ACCESS

   Recheck the logged-in account from the database.
   A stale session must not keep Rental write access if
   the account was archived, disabled, or had its Rental
   permission removed.
===================================================== */

$actorID =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


if (
    $actorID <= 0
) {

    haulingStatusResponse(
        'Your session has expired. Please sign in again.',
        401
    );
}


$actorStmt =
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
    !$actorStmt
) {

    haulingStatusResponse(
        'Unable to verify Rental access.',
        500
    );
}


mysqli_stmt_bind_param(
    $actorStmt,
    'i',
    $actorID
);


if (
    !mysqli_stmt_execute(
        $actorStmt
    )
) {

    mysqli_stmt_close(
        $actorStmt
    );

    haulingStatusResponse(
        'Unable to verify Rental access.',
        500
    );
}


$actorResult =
    mysqli_stmt_get_result(
        $actorStmt
    );


$hasRentalAccess =
    $actorResult
    &&
    mysqli_num_rows(
        $actorResult
    ) === 1;


mysqli_stmt_close(
    $actorStmt
);


if (
    !$hasRentalAccess
) {

    haulingStatusResponse(
        'You do not have permission to update Rental records.',
        403
    );
}


/* =====================================================
   CSRF VALIDATION
===================================================== */

$sessionCsrfToken =
    (string) (
        $_SESSION['rental_csrf_token']
        ??
        ''
    );


$requestCsrfToken =
    (string) (
        $_POST['csrf_token']
        ??
        ''
    );


if (
    $sessionCsrfToken === ''
    ||
    $requestCsrfToken === ''
    ||
    !hash_equals(
        $sessionCsrfToken,
        $requestCsrfToken
    )
) {

    haulingStatusResponse(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =====================================================
   GET VALUES
===================================================== */

$haulingID =
    isset($_POST['id'])
        ? (int) $_POST['id']
        : 0;


$status =
    trim(
        (string) (
            $_POST['status']
            ??
            ''
        )
    );


/* =====================================================
   BASIC VALIDATION
===================================================== */

if (
    $haulingID <= 0
) {

    haulingStatusResponse(
        'Invalid hauling record.',
        400
    );
}


$allowedStatuses = [
    'Pending',
    'Ongoing',
    'Done'
];


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    haulingStatusResponse(
        'Invalid hauling status.',
        400
    );
}


/* =====================================================
   START TRANSACTION
===================================================== */

mysqli_begin_transaction(
    $conn
);


try {

    /* =================================================
       GET CURRENT HAULING RECORD

       CURDATE() keeps the comparison tied to the
       database/server date instead of the device date.
    ================================================= */

    $checkStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                hauling_id,
                transaction_id,
                customer_name,
                tractor_no,
                tractor_id,
                secondary_tractor_no,
                secondary_tractor_id,
                status AS current_status,
                remarks AS current_remarks,
                DATE(rental_date) AS rental_day,
                CURDATE() AS today
            FROM hauling
            WHERE hauling_id = ?
            LIMIT 1
            FOR UPDATE
            "
        );


    if (
        !$checkStmt
    ) {

        throw new Exception(
            'Unable to check hauling record.'
        );
    }


    mysqli_stmt_bind_param(
        $checkStmt,
        'i',
        $haulingID
    );


    if (
        !mysqli_stmt_execute(
            $checkStmt
        )
    ) {

        mysqli_stmt_close(
            $checkStmt
        );

        throw new Exception(
            'Unable to check hauling record.'
        );
    }


    $result =
        mysqli_stmt_get_result(
            $checkStmt
        );


    if (
        !$result
        ||
        mysqli_num_rows(
            $result
        ) === 0
    ) {

        mysqli_stmt_close(
            $checkStmt
        );

        throw new Exception(
            'Hauling record was not found.'
        );
    }


    $hauling =
        mysqli_fetch_assoc(
            $result
        );


    mysqli_stmt_close(
        $checkStmt
    );


    /* =================================================
       RECORD VALUES
    ================================================= */

    $tractorID =
        isset($hauling['tractor_id'])
            ? (int) $hauling['tractor_id']
            : 0;


    $secondaryTractorID =
        isset($hauling['secondary_tractor_id'])
            ? (int) $hauling['secondary_tractor_id']
            : 0;


    $secondaryTractorNo =
        trim(
            (string) (
                $hauling['secondary_tractor_no']
                ??
                ''
            )
        );


    $transactionID =
        trim(
            (string) (
                $hauling['transaction_id']
                ??
                ''
            )
        );


    $customerName =
        trim(
            (string) (
                $hauling['customer_name']
                ??
                ''
            )
        );


    $tractorNo =
        trim(
            (string) (
                $hauling['tractor_no']
                ??
                ''
            )
        );


    $currentStatus =
        trim(
            (string) (
                $hauling['current_status']
                ??
                ''
            )
        );


    $currentRemarks =
        trim(
            (string) (
                $hauling['current_remarks']
                ??
                ''
            )
        );


    $rentalDay =
        trim(
            (string) (
                $hauling['rental_day']
                ??
                ''
            )
        );


    $today =
        trim(
            (string) (
                $hauling['today']
                ??
                ''
            )
        );


    /* =================================================
       VALIDATE CURRENT DATABASE VALUES
    ================================================= */

    if (
        !in_array(
            $currentStatus,
            $allowedStatuses,
            true
        )
    ) {

        throw new Exception(
            'This hauling record has an invalid rental status.'
        );
    }


    $allowedRemarks = [
        'Unpaid',
        'Paid'
    ];


    if (
        !in_array(
            $currentRemarks,
            $allowedRemarks,
            true
        )
    ) {

        throw new Exception(
            'This hauling record has invalid payment remarks.'
        );
    }


    if (
        $rentalDay === ''
        ||
        $today === ''
    ) {

        throw new Exception(
            'Unable to verify the rental date.'
        );
    }


    /* =================================================
    ONGOING SERVICE CANNOT RETURN TO PENDING

    Payment status is NOT considered here.

    Once an actual Hauling service has started,
    its Rental Status cannot return to Pending.

    Valid examples:
    Pending + Paid   = valid
    Pending + Unpaid = valid

    But:
    Ongoing -> Pending = not allowed

    This rule is based on SERVICE PROGRESS,
    not payment progress.
    ================================================= */

    if (
        $currentStatus === 'Ongoing'
        &&
        $status === 'Pending'
    ) {

        throw new Exception(
            'An ongoing Hauling service cannot be changed back to Pending.'
        );
    }


    /* =================================================
       DATE-AWARE STATUS RULES
    ================================================= */


    /* =================================================
       FUTURE DATE

       Future services must remain Pending.
    ================================================= */

    if (
        $rentalDay > $today
        &&
        $status !== 'Pending'
    ) {

        throw new Exception(
            'A future rental must remain Pending until its service date.'
        );
    }


    /* =================================================
       PAST DATE

       A past service cannot be changed to Ongoing.

       Pending remains valid for unresolved Unpaid
       bookings that did not actually proceed.
    ================================================= */

    if (
        $rentalDay < $today
        &&
        $status === 'Ongoing'
    ) {

        throw new Exception(
            'A past rental cannot be changed to Ongoing.'
        );
    }


    /* =================================================
       DONE = FINAL

       Completed rentals dated today or in the past
       cannot return to Pending or Ongoing.

       A legacy future record that was incorrectly saved
       as Done may still be corrected.
    ================================================= */

    if (
        $currentStatus === 'Done'
        &&
        $rentalDay <= $today
        &&
        $status !== 'Done'
    ) {

        throw new Exception(
            'This rental is already completed. Done status is final.'
        );
    }


        /* =================================================
       TRACTOR MAINTENANCE HARD LOCK

       Tractor Type is classification only.

       Check BOTH Primary and Secondary tractors.
       If either assigned tractor is under Maintenance,
       an unresolved Hauling rental must remain Pending.
    ================================================= */

    $assignedTractors = [

        [
            'id' =>
                $tractorID,

            'label' =>
                'Primary Tractor'
        ]

    ];


    if (
        $secondaryTractorID > 0
    ) {

        $assignedTractors[] = [

            'id' =>
                $secondaryTractorID,

            'label' =>
                'Secondary Tractor'

        ];
    }


    foreach (
        $assignedTractors
        as
        $assignedTractor
    ) {

        $assignedTractorID =
            (int) (
                $assignedTractor['id']
                ??
                0
            );


        $assignmentLabel =
            trim(
                (string) (
                    $assignedTractor['label']
                    ??
                    'Assigned Tractor'
                )
            );


        if (
            $assignedTractorID <= 0
        ) {

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


        if (
            !$maintenanceStmt
        ) {

            throw new Exception(
                'Unable to verify tractor maintenance status.'
            );
        }


        mysqli_stmt_bind_param(
            $maintenanceStmt,
            'i',
            $assignedTractorID
        );


        if (
            !mysqli_stmt_execute(
                $maintenanceStmt
            )
        ) {

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


        if (
            $maintenanceResult
            &&
            mysqli_num_rows(
                $maintenanceResult
            ) > 0
        ) {

            $tractor =
                mysqli_fetch_assoc(
                    $maintenanceResult
                );


            if (
                (
                    $tractor['status']
                    ??
                    ''
                ) === 'Maintenance'
                &&
                $status !== 'Pending'
            ) {

                mysqli_stmt_close(
                    $maintenanceStmt
                );

                throw new Exception(
                    $assignmentLabel
                    .
                    ' is under Maintenance. This Hauling rental must remain Pending.'
                );
            }
        }


        mysqli_stmt_close(
            $maintenanceStmt
        );
    }




    /* =================================================
       SAME STATUS

       No update and no duplicate History record.
    ================================================= */

    if (
        $currentStatus === $status
    ) {

        mysqli_commit(
            $conn
        );


        haulingStatusResponse(
            'success'
        );
    }


    /* =================================================
       UPDATE HAULING STATUS
    ================================================= */

    $updateStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE hauling
            SET status = ?
            WHERE hauling_id = ?
            "
        );


    if (
        !$updateStmt
    ) {

        throw new Exception(
            'Unable to prepare hauling status update.'
        );
    }


    mysqli_stmt_bind_param(
        $updateStmt,
        'si',
        $status,
        $haulingID
    );


    if (
        !mysqli_stmt_execute(
            $updateStmt
        )
    ) {

        mysqli_stmt_close(
            $updateStmt
        );

        throw new Exception(
            'Unable to update hauling status.'
        );
    }


    mysqli_stmt_close(
        $updateStmt
    );


        /* =================================================
       TRACTOR STATUS SYNCHRONIZATION

       Recalculate BOTH Primary and Secondary tractors
       against TODAY'S active assignments across:

       - Hauling
       - Rotor
       - Reaper

       Tractor Type is classification only.
    ================================================= */

    $affectedTractors = [

        $tractorID

    ];


    if (
        $secondaryTractorID > 0
    ) {

        $affectedTractors[] =
            $secondaryTractorID;
    }


    $affectedTractors =
        array_values(
            array_unique(
                array_filter(
                    $affectedTractors,
                    static function(
                        $id
                    ) {

                        return
                            (int) $id > 0;
                    }
                )
            )
        );


    foreach (
        $affectedTractors
        as
        $affectedTractorID
    ) {

        haulingStatusSyncTractorPhysicalState(
            $conn,
            (int) $affectedTractorID
        );
    }


    /* =================================================
       SAVE HISTORY LOG
    ================================================= */

    log_activity(
        $conn,
        'Rental',
        'Updated',
        'changed the Hauling rental "'
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
        $haulingID
    );


    /* =================================================
       COMMIT
    ================================================= */

    mysqli_commit(
        $conn
    );


    haulingStatusResponse(
        'success'
    );

}
catch (
    Throwable $error
) {

    mysqli_rollback(
        $conn
    );


    $safeMessage =
        trim(
            $error->getMessage()
        );


    if (
        $safeMessage === ''
    ) {

        $safeMessage =
            'Unable to update hauling status.';
    }


    haulingStatusResponse(
        $safeMessage,
        400
    );
}

?>