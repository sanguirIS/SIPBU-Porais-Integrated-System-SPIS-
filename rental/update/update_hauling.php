<?php

/* =========================================================
   SPIS - UPDATE HAULING RENTAL
   Edit Schedule / Assignment only

   RULES:

   PENDING
   - Rental Date editable
   - Primary Tractor editable
   - Primary Driver editable
   - Secondary Assignment editable

   ONGOING
   - Rental Date locked
   - Primary Tractor locked
   - Primary Driver locked
   - Secondary Assignment editable

   DONE
   - Fully locked

   IMPORTANT
   - Date-only scheduling
   - No Service Time
   - Payment Remarks are NOT changed here
   - Rental Status changes only when a Delayed rental is rescheduled
   - Customer information is NOT changed here
   - Price / Discount / Total are NOT changed here
========================================================= */


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


error_reporting(
    E_ALL
);


ini_set(
    'display_errors',
    '0'
);


header(
    'Content-Type: text/plain; charset=UTF-8'
);


/* =========================================================
   REQUIRED FILES
========================================================= */

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


/* =========================================================
   SAFE VALIDATION EXCEPTION
========================================================= */

class HaulingUpdateValidationException
extends Exception
{
}


/* =========================================================
   RESPONSE
========================================================= */

function haulingUpdateResponse(
    string $message,
    int $statusCode = 200
): void {

    http_response_code(
        $statusCode
    );


    echo $message;

    exit();

}


/* =========================================================
   INTERNAL ERROR LOG
========================================================= */

function haulingUpdateLogInternalError(
    string $context,
    mysqli $conn,
    ?mysqli_stmt $stmt = null
): void {

    $error =

        $stmt

        ?

        mysqli_stmt_error(
            $stmt
        )

        :

        mysqli_error(
            $conn
        );


    error_log(

        '[SPIS][Hauling Update] '

        .

        $context

        .

        ': '

        .

        $error

    );

}


/* =========================================================
   LOAD / LOCK DRIVER
========================================================= */

function haulingUpdateLockDriver(
    mysqli $conn,
    int $driverID
): array {

    $stmt =

        mysqli_prepare(

            $conn,

            "
            SELECT

                d.driver_id,
                d.member_id,

                m.full_name,
                m.contact_no,

                d.status,
                m.status AS member_status

            FROM drivers d

            INNER JOIN members m
                ON m.member_id = d.member_id
                AND m.archived = 0

            WHERE d.driver_id = ?

            AND d.archived = 0

            LIMIT 1

            FOR UPDATE
            "

        );


    if (
        !$stmt
    ) {

        haulingUpdateLogInternalError(

            'prepare driver lookup',

            $conn

        );


        throw new RuntimeException(

            'Unable to validate driver.'

        );

    }


    mysqli_stmt_bind_param(

        $stmt,

        'i',

        $driverID

    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        haulingUpdateLogInternalError(

            'execute driver lookup',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(

            'Unable to validate driver.'

        );

    }


    $result =

        mysqli_stmt_get_result(
            $stmt
        );


    $driver =

        $result

        ?

        mysqli_fetch_assoc(
            $result
        )

        :

        null;


    mysqli_stmt_close(
        $stmt
    );


    if (
        !$driver
    ) {

        throw new HaulingUpdateValidationException(

            'The selected driver is no longer available.'

        );

    }


    if (
        (
            $driver['status']
            ??
            ''
        )
        !==
        'Active'
    ) {

        throw new HaulingUpdateValidationException(

            'The selected driver is currently inactive.'

        );

    }

        if (
        strtoupper(
            trim(
                (string) (
                    $driver['member_status']
                    ??
                    ''
                )
            )
        )
        !==
        'ACTIVE'
    ) {

        throw new HaulingUpdateValidationException(

            'The selected driver is linked to an inactive Member.'

        );

    }


    return $driver;

}


/* =========================================================
   LOAD / LOCK TRACTOR

   Tractor Type is classification only.
   Any active registered tractor may be assigned
   to Hauling, Rotor, or Reaper.
========================================================= */

function haulingUpdateLockTractor(
    mysqli $conn,
    int $tractorID
): array {

    $stmt =

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

            FOR UPDATE
            "

        );


    if (
        !$stmt
    ) {

        haulingUpdateLogInternalError(

            'prepare tractor lookup',

            $conn

        );


        throw new RuntimeException(

            'Unable to validate tractor.'

        );

    }


    mysqli_stmt_bind_param(

        $stmt,

        'i',

        $tractorID

    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        haulingUpdateLogInternalError(

            'execute tractor lookup',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(

            'Unable to validate tractor.'

        );

    }


    $result =

        mysqli_stmt_get_result(
            $stmt
        );


    $tractor =

        $result

        ?

        mysqli_fetch_assoc(
            $result
        )

        :

        null;


    mysqli_stmt_close(
        $stmt
    );


    if (
        !$tractor
    ) {

        throw new HaulingUpdateValidationException(

            'The selected tractor was not found.'

        );

    }


    /*
       MAINTENANCE HARD LOCK
    */

    if (
        (
            $tractor['status']
            ??
            ''
        )
        ===
        'Maintenance'
    ) {

        throw new HaulingUpdateValidationException(

            'The selected tractor is currently under maintenance.'

        );

    }


    return $tractor;

}


/* =========================================================
   DATE-ONLY RESOURCE CONFLICT

   Checks:

   - Hauling
   - Rotor
   - Reaper

   Active records:

   - Pending
   - Ongoing

   Done does not block.

   IMPORTANT:

   Current Hauling record is excluded so the rental
   does not conflict with itself while editing.
========================================================= */

function haulingUpdateFindAssignmentConflict(

    mysqli $conn,

    string $resourceType,

    int $resourceID,

    string $rentalDate,

    int $excludeHaulingID

): ?array {


    if (
        $resourceType
        ===
        'tractor'
    ) {

        $primaryColumn =
        'tractor_id';


        $secondaryColumn =
        'secondary_tractor_id';

    }
    elseif (
        $resourceType
        ===
        'driver'
    ) {

        $primaryColumn =
        'primary_driver_id';


        $secondaryColumn =
        'secondary_driver_id';

    }
    else {

        throw new RuntimeException(

            'Invalid availability resource.'

        );

    }


    $tables = [

        [

            'table' =>
                'hauling',

            'id' =>
                'hauling_id',

            'label' =>
                'Hauling'

        ],

        [

            'table' =>
                'rotor',

            'id' =>
                'rotor_id',

            'label' =>
                'Rotor'

        ],

        [

            'table' =>
                'reaper',

            'id' =>
                'reaper_id',

            'label' =>
                'Reaper'

        ]

    ];


    foreach (
        $tables
        as
        $info
    ) {

        $table =
        $info['table'];


        $idColumn =
        $info['id'];


        /*
           HAULING

           Exclude current Hauling rental.
        */

        if (
            $table
            ===
            'hauling'
        ) {

            $sql =

            "
            SELECT

                {$idColumn}
                AS rental_id,

                transaction_id,

                status

            FROM {$table}

            WHERE DATE(rental_date) = ?

            AND {$idColumn} != ?

            AND status IN (
                'Pending',
                'Ongoing'
            )

            AND
            (
                {$primaryColumn} = ?

                OR

                {$secondaryColumn} = ?
            )

            LIMIT 1

            FOR UPDATE
            ";


            $stmt =

                mysqli_prepare(

                    $conn,

                    $sql

                );


            if (
                !$stmt
            ) {

                haulingUpdateLogInternalError(

                    'prepare hauling conflict lookup',

                    $conn

                );


                throw new RuntimeException(

                    'Unable to verify Rental availability.'

                );

            }


            mysqli_stmt_bind_param(

                $stmt,

                'siii',

                $rentalDate,

                $excludeHaulingID,

                $resourceID,

                $resourceID

            );

        }

        /*
           ROTOR / REAPER
        */

        else {

            $sql =

            "
            SELECT

                {$idColumn}
                AS rental_id,

                transaction_id,

                status

            FROM {$table}

            WHERE DATE(rental_date) = ?

            AND status IN (
                'Pending',
                'Ongoing'
            )

            AND
            (
                {$primaryColumn} = ?

                OR

                {$secondaryColumn} = ?
            )

            LIMIT 1

            FOR UPDATE
            ";


            $stmt =

                mysqli_prepare(

                    $conn,

                    $sql

                );


            if (
                !$stmt
            ) {

                haulingUpdateLogInternalError(

                    'prepare cross-rental conflict lookup',

                    $conn

                );


                throw new RuntimeException(

                    'Unable to verify Rental availability.'

                );

            }


            mysqli_stmt_bind_param(

                $stmt,

                'sii',

                $rentalDate,

                $resourceID,

                $resourceID

            );

        }


        if (
            !mysqli_stmt_execute(
                $stmt
            )
        ) {

            haulingUpdateLogInternalError(

                'execute availability lookup',

                $conn,

                $stmt

            );


            mysqli_stmt_close(
                $stmt
            );


            throw new RuntimeException(

                'Unable to verify Rental availability.'

            );

        }


        $result =

            mysqli_stmt_get_result(
                $stmt
            );


        $row =

            $result

            ?

            mysqli_fetch_assoc(
                $result
            )

            :

            null;


        mysqli_stmt_close(
            $stmt
        );


        if (
            $row
        ) {

            return [

                'rental_type' =>

                    $info['label'],


                'rental_id' =>

                    (int) (
                        $row['rental_id']
                        ??
                        0
                    ),


                'transaction_id' =>

                    trim(

                        (string) (
                            $row['transaction_id']
                            ??
                            ''
                        )

                    ),


                'status' =>

                    trim(

                        (string) (
                            $row['status']
                            ??
                            ''
                        )

                    )

            ];

        }

    }


    return null;

}


/* =========================================================
   FRIENDLY CONFLICT MESSAGE
========================================================= */

function haulingUpdateConflictMessage(

    string $assignmentLabel,

    array $conflict,

    string $rentalDate

): string {


    $transactionID =

        trim(

            (string) (
                $conflict['transaction_id']
                ??
                ''
            )

        );


    $rentalType =

        trim(

            (string) (
                $conflict['rental_type']
                ??
                'Rental'
            )

        );


    $record =

        $transactionID !== ''

        ?

        $rentalType
        .
        ' Rental "'
        .
        $transactionID
        .
        '"'

        :

        $rentalType
        .
        ' Rental';


    return

        $assignmentLabel

        .

        ' is already assigned to '

        .

        $record

        .

        ' on '

        .

        $rentalDate

        .

        '.';

}


/* =========================================================
   CURRENT PHYSICAL TRACTOR STATUS

   After editing the rental, recalculate each affected
   tractor using TODAY'S active assignments.

   Ongoing in any Rental service:
       In Use

   Pending Reaper:
       In Use

   Pending Hauling / Rotor:
       Reserved

   No current assignment:
       Available

   In Use has priority over Reserved.
   Maintenance is never overwritten.
========================================================= */

function haulingUpdateSyncTractorStatus(

    mysqli $conn,

    int $tractorID

): void {


    if (
        $tractorID <= 0
    ) {

        return;

    }


    /* =====================================================
       CURRENT REGISTRY STATUS
    ===================================================== */

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

        haulingUpdateLogInternalError(

            'prepare tractor registry status lookup',

            $conn

        );


        throw new RuntimeException(

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

        haulingUpdateLogInternalError(

            'execute tractor registry status lookup',

            $conn,

            $statusStmt

        );


        mysqli_stmt_close(
            $statusStmt
        );


        throw new RuntimeException(

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
    ) {

        return;

    }


    /*
       Never override Maintenance.
    */

    if (
        (
            $registry['status']
            ??
            ''
        )
        ===
        'Maintenance'
    ) {

        return;

    }


    $hasInUse =
    false;


    $hasReserved =
    false;


    $tables = [

        [
            'table' =>
                'hauling'
        ],

        [
            'table' =>
                'rotor'
        ],

        [
            'table' =>
                'reaper'
        ]

    ];


    /* =====================================================
       CHECK TODAY'S ACTIVE RENTALS
    ===================================================== */

    foreach (
        $tables
        as
        $info
    ) {

        $table =
        $info['table'];


        $stmt =

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
            !$stmt
        ) {

            haulingUpdateLogInternalError(

                'prepare current tractor use lookup',

                $conn

            );


            throw new RuntimeException(

                'Unable to synchronize tractor status.'

            );

        }


        mysqli_stmt_bind_param(

            $stmt,

            'ii',

            $tractorID,

            $tractorID

        );


        if (
            !mysqli_stmt_execute(
                $stmt
            )
        ) {

            haulingUpdateLogInternalError(

                'execute current tractor use lookup',

                $conn,

                $stmt

            );


            mysqli_stmt_close(
                $stmt
            );


            throw new RuntimeException(

                'Unable to synchronize tractor status.'

            );

        }


        $result =

            mysqli_stmt_get_result(
                $stmt
            );


        if (
            $result
        ) {

            while (
                $row =
                mysqli_fetch_assoc(
                    $result
                )
            ) {

                $rentalStatus =

                    trim(

                        (string) (
                            $row['status']
                            ??
                            ''
                        )

                    );


                if (
                    $rentalStatus
                    ===
                    'Ongoing'
                ) {

                    $hasInUse =
                    true;

                }
                elseif (
                    $rentalStatus
                    ===
                    'Pending'
                ) {

                    if (
                        $table
                        ===
                        'reaper'
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
            $stmt
        );

    }


    /* =====================================================
       DETERMINE PHYSICAL STATUS
    ===================================================== */

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


    /* =====================================================
       UPDATE REGISTRY
    ===================================================== */

    $updateStmt =

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
        !$updateStmt
    ) {

        haulingUpdateLogInternalError(

            'prepare tractor status sync',

            $conn

        );


        throw new RuntimeException(

            'Unable to synchronize tractor status.'

        );

    }


    mysqli_stmt_bind_param(

        $updateStmt,

        'si',

        $physicalStatus,

        $tractorID

    );


    if (
        !mysqli_stmt_execute(
            $updateStmt
        )
    ) {

        haulingUpdateLogInternalError(

            'execute tractor status sync',

            $conn,

            $updateStmt

        );


        mysqli_stmt_close(
            $updateStmt
        );


        throw new RuntimeException(

            'Unable to synchronize tractor status.'

        );

    }


    mysqli_stmt_close(
        $updateStmt
    );

}


/* =========================================================
   POST ONLY
========================================================= */

if (
    (
        $_SERVER['REQUEST_METHOD']
        ??
        ''
    )
    !==
    'POST'
) {

    haulingUpdateResponse(

        'Invalid request.',

        405

    );

}


/* =========================================================
   VERIFY SESSION
========================================================= */

$actorID =

    isset(
        $_SESSION['user_id']
    )

    ?

    (int) $_SESSION['user_id']

    :

    0;


if (
    $actorID <= 0
) {

    haulingUpdateResponse(

        'Your session has expired. Please sign in again.',

        401

    );

}


/* =========================================================
   VERIFY LIVE RENTAL ACCESS
========================================================= */

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

    haulingUpdateLogInternalError(

        'prepare access check',

        $conn

    );


    haulingUpdateResponse(

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

    haulingUpdateLogInternalError(

        'execute access check',

        $conn,

        $actorStmt

    );


    mysqli_stmt_close(
        $actorStmt
    );


    haulingUpdateResponse(

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

    haulingUpdateResponse(

        'You do not have permission to update Rental records.',

        403

    );

}


/* =========================================================
   CSRF VALIDATION
========================================================= */

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

    haulingUpdateResponse(

        'Invalid or expired form request. Please refresh the page and try again.',

        403

    );

}


/* =========================================================
   REQUEST VALUES

   ONLY THESE VALUES ARE EDITABLE HERE:

   - Rental Date
   - Primary Tractor
   - Primary Driver
   - Secondary Tractor
   - Secondary Driver

   We intentionally ignore:

   - Customer
   - Affiliate
   - Location
   - No. of Hauling
   - Price
   - Discount
   - Total
   - Rental Status
   - Payment Remarks
========================================================= */

$haulingID =

    isset(
        $_POST['hauling_id']
    )

    ?

    (int) $_POST['hauling_id']

    :

    (
        isset(
            $_POST['id']
        )

        ?

        (int) $_POST['id']

        :

        0
    );


$rentalDate =

    trim(

        (string) (
            $_POST['rental_date']
            ??
            ''
        )

    );


$tractorID =

    isset(
        $_POST['tractor_id']
    )

    ?

    (int) $_POST['tractor_id']

    :

    0;


$primaryDriverID =

    isset(
        $_POST['primary_driver_id']
    )

    ?

    (int) $_POST['primary_driver_id']

    :

    0;


/* =========================================================
   SECONDARY ASSIGNMENT
========================================================= */

$secondaryTractorID =

    !empty(
        $_POST['secondary_tractor_id']
    )

    ?

    (int) $_POST['secondary_tractor_id']

    :

    null;


$secondaryDriverID =

    !empty(
        $_POST['secondary_driver_id']
    )

    ?

    (int) $_POST['secondary_driver_id']

    :

    null;


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $haulingID <= 0
) {

    haulingUpdateResponse(

        'Invalid hauling record.',

        400

    );

}


if (
    $rentalDate === ''
) {

    haulingUpdateResponse(

        'Please select the Rental date.',

        400

    );

}


/* =========================================================
   VALIDATE DATE FORMAT
========================================================= */

$dateObject =

    DateTimeImmutable::createFromFormat(

        '!Y-m-d',

        $rentalDate

    );


$dateErrors =

    DateTimeImmutable::getLastErrors();


$dateValid =

    $dateObject !== false

    &&

    (

        $dateErrors === false

        ||

        (

            (
                $dateErrors['warning_count']
                ??
                0
            ) === 0

            &&

            (
                $dateErrors['error_count']
                ??
                0
            ) === 0

        )

    )

    &&

    $dateObject->format(
        'Y-m-d'
    ) === $rentalDate;


if (
    !$dateValid
) {

    haulingUpdateResponse(

        'Invalid Rental date.',

        400

    );

}


/* =========================================================
   PRIMARY REQUIRED
========================================================= */

if (
    $tractorID <= 0
) {

    haulingUpdateResponse(

        'Please select a Primary Tractor.',

        400

    );

}


if (
    $primaryDriverID <= 0
) {

    haulingUpdateResponse(

        'Please select a Primary Driver.',

        400

    );

}


/* =========================================================
   SECONDARY PAIR
========================================================= */

$hasSecondaryTractor =

    $secondaryTractorID !== null

    &&

    $secondaryTractorID > 0;


$hasSecondaryDriver =

    $secondaryDriverID !== null

    &&

    $secondaryDriverID > 0;


if (
    $hasSecondaryTractor
    !==
    $hasSecondaryDriver
) {

    haulingUpdateResponse(

        'Secondary Tractor and Secondary Driver must be selected together.',

        400

    );

}


/* =========================================================
   PRIMARY != SECONDARY
========================================================= */

if (
    $hasSecondaryTractor

    &&

    $secondaryTractorID
    ===
    $tractorID
) {

    haulingUpdateResponse(

        'Primary Tractor and Secondary Tractor cannot be the same.',

        400

    );

}


if (
    $hasSecondaryDriver

    &&

    $secondaryDriverID
    ===
    $primaryDriverID
) {

    haulingUpdateResponse(

        'Primary Driver and Secondary Driver cannot be the same.',

        400

    );

}


/* =========================================================
   START DATABASE TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {


    /* =====================================================
       LOCK + LOAD CURRENT HAULING RECORD
    ===================================================== */

    $currentStmt =

        mysqli_prepare(

            $conn,

            "
            SELECT

                hauling_id,
                transaction_id,
                customer_name,

                status,
                remarks,

                DATE(rental_date)
                    AS current_rental_date,

                tractor_id,
                primary_driver_id,

                secondary_tractor_id,
                secondary_driver_id,

                tractor_no,
                operator,

                secondary_tractor_no,
                secondary_operator,

                actual_started_at,
                actual_started_date,

                COALESCE(
                    reschedule_count,
                    0
                )
                    AS reschedule_count,

                last_rescheduled_from,
                last_rescheduled_at,

                CURDATE()
                    AS today

            FROM hauling

            WHERE hauling_id = ?

            LIMIT 1

            FOR UPDATE
            "

        );


    if (
        !$currentStmt
    ) {

        haulingUpdateLogInternalError(

            'prepare current hauling lookup',

            $conn

        );


        throw new RuntimeException(

            'Unable to load the Hauling rental.'

        );

    }


    mysqli_stmt_bind_param(

        $currentStmt,

        'i',

        $haulingID

    );


    if (
        !mysqli_stmt_execute(
            $currentStmt
        )
    ) {

        haulingUpdateLogInternalError(

            'execute current hauling lookup',

            $conn,

            $currentStmt

        );


        mysqli_stmt_close(
            $currentStmt
        );


        throw new RuntimeException(

            'Unable to load the Hauling rental.'

        );

    }


    $currentResult =

        mysqli_stmt_get_result(
            $currentStmt
        );


    $current =

        $currentResult

        ?

        mysqli_fetch_assoc(
            $currentResult
        )

        :

        null;


    mysqli_stmt_close(
        $currentStmt
    );


    if (
        !$current
    ) {

        throw new HaulingUpdateValidationException(

            'The selected Hauling rental was not found.'

        );

    }


    /* =====================================================
       CURRENT VALUES
    ===================================================== */

    $transactionID =

        trim(

            (string) (
                $current['transaction_id']
                ??
                ''
            )

        );


    $customerName =

        trim(

            (string) (
                $current['customer_name']
                ??
                ''
            )

        );


    $currentStatus =

        trim(

            (string) (
                $current['status']
                ??
                ''
            )

        );


    /*
       Payment Remarks is read only here.

       We load it only to validate that the existing
       database value is valid.

       WE DO NOT UPDATE IT.
    */

    $currentRemarks =

        trim(

            (string) (
                $current['remarks']
                ??
                ''
            )

        );


    $currentRentalDate =

        trim(

            (string) (
                $current['current_rental_date']
                ??
                ''
            )

        );


    $today =

        trim(

            (string) (
                $current['today']
                ??
                ''
            )

        );


    $oldTractorID =

        (int) (
            $current['tractor_id']
            ??
            0
        );


    $oldPrimaryDriverID =

        (int) (
            $current['primary_driver_id']
            ??
            0
        );


    $oldSecondaryTractorID =

        !empty(
            $current['secondary_tractor_id']
        )

        ?

        (int) $current['secondary_tractor_id']

        :

        null;


    $oldSecondaryDriverID =

        !empty(
            $current['secondary_driver_id']
        )

        ?

        (int) $current['secondary_driver_id']

        :

        null;


    $oldTractorNo =

        trim(

            (string) (
                $current['tractor_no']
                ??
                ''
            )

        );


    $oldOperator =

        trim(

            (string) (
                $current['operator']
                ??
                ''
            )

        );


    $oldSecondaryTractorNo =

        trim(

            (string) (
                $current['secondary_tractor_no']
                ??
                ''
            )

        );


    $oldSecondaryOperator =

        trim(

            (string) (
                $current['secondary_operator']
                ??
                ''
            )

        );

        /* =====================================================
       CURRENT RESCHEDULE / LIFECYCLE VALUES
    ===================================================== */

    $currentActualStartedAt =

        trim(

            (string) (
                $current['actual_started_at']
                ??
                ''
            )

        );


    $currentActualStartedDate =

        trim(

            (string) (
                $current['actual_started_date']
                ??
                ''
            )

        );


    $currentRescheduleCount =

        (int) (
            $current['reschedule_count']
            ??
            0
        );


    $currentLastRescheduledFrom =

        trim(

            (string) (
                $current['last_rescheduled_from']
                ??
                ''
            )

        );


    $currentLastRescheduledAt =

        trim(

            (string) (
                $current['last_rescheduled_at']
                ??
                ''
            )

        );    


    if (
        $today === ''

        ||

        $currentRentalDate === ''
    ) {

        throw new RuntimeException(

            'Unable to verify the Rental date.'

        );

    }


    /* =====================================================
       VALIDATE CURRENT DATABASE STATUS
    ===================================================== */

    if (
        !in_array(

            $currentStatus,

            [
                'Pending',
                'Ongoing',
                'Done'
            ],

            true

        )
    ) {

        throw new RuntimeException(

            'This Hauling rental has an invalid status.'

        );

    }


    if (
        !in_array(

            $currentRemarks,

            [
                'Unpaid',
                'Paid'
            ],

            true

        )
    ) {

        throw new RuntimeException(

            'This Hauling rental has invalid payment remarks.'

        );

    }


    /* =====================================================
       DONE = FULLY LOCKED
    ===================================================== */

    if (
        $currentStatus
        ===
        'Done'
    ) {

        throw new HaulingUpdateValidationException(

            'This rental is already completed and can no longer be edited.'

        );

    }

    /* =====================================================
       SCHEDULE FLAG / RESCHEDULE STATE
    ===================================================== */

    $dateChanged =

        $rentalDate
        !==
        $currentRentalDate;


    $isMissed =

        $currentStatus
        ===
        'Pending'

        &&

        $currentRentalDate
        <
        $today;


    $isDelayed =

        $currentStatus
        ===
        'Ongoing'

        &&

        $currentRentalDate
        <
        $today;


    $isReschedule =

        $dateChanged

        &&

        (
            $isMissed
            ||
            $isDelayed
        );


    $newStatus =
        $currentStatus;


    $newRescheduleCount =
        $currentRescheduleCount;


    $newLastRescheduledFrom =

        $currentLastRescheduledFrom !== ''

        ?

        $currentLastRescheduledFrom

        :

        null;


    $newLastRescheduledAt =

        $currentLastRescheduledAt !== ''

        ?

        $currentLastRescheduledAt

        :

        null;


    $newActualStartedAt =

        $currentActualStartedAt !== ''

        ?

        $currentActualStartedAt

        :

        null;


    $newActualStartedDate =

        $currentActualStartedDate !== ''

        ?

        $currentActualStartedDate

        :

        null;


    if (
        $isReschedule
    ) {

        $newRescheduleCount++;


        $newLastRescheduledFrom =
            $currentRentalDate;


        $newLastRescheduledAt =
            date(
                'Y-m-d H:i:s'
            );


        /*
           DELAYED RESCHEDULE

           Ongoing -> Pending

           Clear previous started markers because the
           rescheduled service has not started yet.
        */

        if (
            $isDelayed
        ) {

            $newStatus =
                'Pending';


            $newActualStartedAt =
                null;


            $newActualStartedDate =
                null;

        }

    }


    /* =====================================================
       PENDING RULE

       A past Pending record may represent a Missed rental.

       It CAN be rescheduled.

       But the NEW Rental Date must be TODAY or FUTURE.
    ===================================================== */

    if (
        $currentStatus
        ===
        'Pending'
    ) {

        if (
            $rentalDate
            <
            $today
        ) {

            throw new HaulingUpdateValidationException(

                'Past dates cannot be selected when rescheduling a Pending rental.'

            );

        }

    }


    /* =====================================================
       ONGOING RULE

       NORMAL ONGOING
       - Date locked
       - Primary locked
       - Secondary may change

       DELAYED ONGOING
       - May be rescheduled to today / future
       - New Primary / Secondary assignments may be chosen
       - Status resets to Pending after reschedule
    ===================================================== */

    if (
        $currentStatus
        ===
        'Ongoing'
    ) {

        if (
            $isDelayed
        ) {

            if (
                $dateChanged
            ) {

                if (
                    $rentalDate
                    <
                    $today
                ) {

                    throw new HaulingUpdateValidationException(

                        'A Delayed rental can only be rescheduled to today or a future date.'

                    );

                }

            }
            else {

                /*
                   Delayed but NOT rescheduled yet.

                   Primary Assignment stays locked.
                   Secondary may still be adjusted.
                */

                if (
                    $tractorID
                    !==
                    $oldTractorID

                    ||

                    $primaryDriverID
                    !==
                    $oldPrimaryDriverID
                ) {

                    throw new HaulingUpdateValidationException(

                        'Change the Rental Date first before changing the Primary Assignment of a Delayed rental.'

                    );

                }

            }

        }
        else {

            if (
                $rentalDate
                !==
                $currentRentalDate
            ) {

                throw new HaulingUpdateValidationException(

                    'Rental Date is locked once the Hauling service is Ongoing.'

                );

            }


            if (
                $tractorID
                !==
                $oldTractorID

                ||

                $primaryDriverID
                !==
                $oldPrimaryDriverID
            ) {

                throw new HaulingUpdateValidationException(

                    'Primary Tractor and Primary Driver are locked once the Hauling service is Ongoing.'

                );

            }

        }

    }


    /* =====================================================
       LOCK + VALIDATE PRIMARY ASSIGNMENT
    ===================================================== */

    $primaryTractor =

        haulingUpdateLockTractor(

            $conn,

            $tractorID

        );


    $primaryDriver =

        haulingUpdateLockDriver(

            $conn,

            $primaryDriverID

        );


    /* =====================================================
       LOCK + VALIDATE SECONDARY ASSIGNMENT
    ===================================================== */

    $secondaryTractor =
    null;


    $secondaryDriver =
    null;


    if (
        $hasSecondaryTractor
    ) {

        $secondaryTractor =

            haulingUpdateLockTractor(

                $conn,

                (int) $secondaryTractorID

            );


        $secondaryDriver =

            haulingUpdateLockDriver(

                $conn,

                (int) $secondaryDriverID

            );

    }


    /* =====================================================
       CANONICAL SNAPSHOTS

       Browser-submitted names and tractor numbers are
       intentionally ignored.

       Values come from Registry records.
    ===================================================== */

    $tractorNo =

        trim(

            (string) (
                $primaryTractor['tractor_no']
                ??
                ''
            )

        );


    $operator =

        trim(

            (string) (
                $primaryDriver['full_name']
                ??
                ''
            )

        );


    $secondaryTractorNo =

        $secondaryTractor

        ?

        trim(

            (string) (
                $secondaryTractor['tractor_no']
                ??
                ''
            )

        )

        :

        null;


    $secondaryOperator =

        $secondaryDriver

        ?

        trim(

            (string) (
                $secondaryDriver['full_name']
                ??
                ''
            )

        )

        :

        null;


    if (
        $tractorNo === ''

        ||

        $operator === ''
    ) {

        throw new RuntimeException(

            'Unable to verify Primary Assignment.'

        );

    }


    if (
        $hasSecondaryTractor

        &&

        (

            $secondaryTractorNo === ''

            ||

            $secondaryOperator === ''

        )
    ) {

        throw new RuntimeException(

            'Unable to verify Secondary Assignment.'

        );

    }


    /* =====================================================
       PRIMARY TRACTOR CONFLICT

       Current Hauling ID is excluded.
    ===================================================== */

    $conflict =

        haulingUpdateFindAssignmentConflict(

            $conn,

            'tractor',

            $tractorID,

            $rentalDate,

            $haulingID

        );


    if (
        $conflict
    ) {

        throw new HaulingUpdateValidationException(

            haulingUpdateConflictMessage(

                'Primary Tractor',

                $conflict,

                $rentalDate

            )

        );

    }


    /* =====================================================
       SECONDARY TRACTOR CONFLICT
    ===================================================== */

    if (
        $hasSecondaryTractor
    ) {

        $conflict =

            haulingUpdateFindAssignmentConflict(

                $conn,

                'tractor',

                (int) $secondaryTractorID,

                $rentalDate,

                $haulingID

            );


        if (
            $conflict
        ) {

            throw new HaulingUpdateValidationException(

                haulingUpdateConflictMessage(

                    'Secondary Tractor',

                    $conflict,

                    $rentalDate

                )

            );

        }

    }


    /* =====================================================
       PRIMARY DRIVER CONFLICT
    ===================================================== */

    $conflict =

        haulingUpdateFindAssignmentConflict(

            $conn,

            'driver',

            $primaryDriverID,

            $rentalDate,

            $haulingID

        );


    if (
        $conflict
    ) {

        throw new HaulingUpdateValidationException(

            haulingUpdateConflictMessage(

                'Primary Driver',

                $conflict,

                $rentalDate

            )

        );

    }


    /* =====================================================
       SECONDARY DRIVER CONFLICT
    ===================================================== */

    if (
        $hasSecondaryDriver
    ) {

        $conflict =

            haulingUpdateFindAssignmentConflict(

                $conn,

                'driver',

                (int) $secondaryDriverID,

                $rentalDate,

                $haulingID

            );


        if (
            $conflict
        ) {

            throw new HaulingUpdateValidationException(

                haulingUpdateConflictMessage(

                    'Secondary Driver',

                    $conflict,

                    $rentalDate

                )

            );

        }

    }


    /* =====================================================
       CHANGE DETECTION
    ===================================================== */

    $primaryTractorChanged =

        $tractorID
        !==
        $oldTractorID;


    $primaryDriverChanged =

        $primaryDriverID
        !==
        $oldPrimaryDriverID;


    $secondaryTractorChanged =

        (
            $secondaryTractorID
            ??
            0
        )

        !==

        (
            $oldSecondaryTractorID
            ??
            0
        );


    $secondaryDriverChanged =

        (
            $secondaryDriverID
            ??
            0
        )

        !==

        (
            $oldSecondaryDriverID
            ??
            0
        );


    $hasChanges =

        $dateChanged

        ||

        $primaryTractorChanged

        ||

        $primaryDriverChanged

        ||

        $secondaryTractorChanged

        ||

        $secondaryDriverChanged;


    /* =====================================================
       NOTHING CHANGED

       Do not create unnecessary History log.
    ===================================================== */

    if (
        !$hasChanges
    ) {

        mysqli_commit(
            $conn
        );


        haulingUpdateResponse(
            'success'
        );

    }


    /* =====================================================
       UPDATE SCHEDULE + ASSIGNMENT ONLY

       NOT UPDATED:

       - customer_name
       - member_id
       - affiliate
       - location
       - hauling_count
       - price
       - discounted_price
       - total_price
       - remarks
       - completed_date
    ===================================================== */

    $updateStmt =

        mysqli_prepare(

            $conn,

            "
            UPDATE hauling

            SET

                rental_date = ?,

                tractor_id = ?,

                primary_driver_id = ?,

                secondary_tractor_id = ?,

                secondary_driver_id = ?,

                tractor_no = ?,

                operator = ?,

                secondary_tractor_no = ?,

                secondary_operator = ?,

                status = ?,

                actual_started_at = ?,

                actual_started_date = ?,

                reschedule_count = ?,

                last_rescheduled_from = ?,

                last_rescheduled_at = ?

            WHERE hauling_id = ?

            LIMIT 1
            "

        );


    if (
        !$updateStmt
    ) {

        haulingUpdateLogInternalError(

            'prepare hauling assignment update',

            $conn

        );


        throw new RuntimeException(

            'Unable to prepare Hauling update.'

        );

    }

    mysqli_stmt_bind_param(

        $updateStmt,

        'siiiisssssssissi',

        $rentalDate,

        $tractorID,

        $primaryDriverID,

        $secondaryTractorID,

        $secondaryDriverID,

        $tractorNo,

        $operator,

        $secondaryTractorNo,

        $secondaryOperator,

        $newStatus,

        $newActualStartedAt,

        $newActualStartedDate,

        $newRescheduleCount,

        $newLastRescheduledFrom,

        $newLastRescheduledAt,

        $haulingID

    );


    if (
        !mysqli_stmt_execute(
            $updateStmt
        )
    ) {

        haulingUpdateLogInternalError(

            'execute hauling assignment update',

            $conn,

            $updateStmt

        );


        mysqli_stmt_close(
            $updateStmt
        );


        throw new RuntimeException(

            'Unable to update the Hauling rental.'

        );

    }


    mysqli_stmt_close(
        $updateStmt
    );


    /* =====================================================
       TRACTOR STATUS SYNC

       We include both OLD and NEW tractors.

       Example:

       Old Primary:
       NVB134

       New Primary:
       NVI032

       If NVB134 has no other active rental today,
       it returns to Available.

       New tractor gets Reserved / In Use depending on
       the active rental records for today.
    ===================================================== */

    $affectedTractorIDs = [

        $oldTractorID,

        $tractorID

    ];


    if (
        (
            $oldSecondaryTractorID
            ??
            0
        ) > 0
    ) {

        $affectedTractorIDs[] =

            (int) $oldSecondaryTractorID;

    }


    if (
        (
            $secondaryTractorID
            ??
            0
        ) > 0
    ) {

        $affectedTractorIDs[] =

            (int) $secondaryTractorID;

    }


    $affectedTractorIDs =

        array_values(

            array_unique(

                array_filter(

                    $affectedTractorIDs,

                    static function(
                        $id
                    ) {

                        return
                            (int) $id
                            >
                            0;

                    }

                )

            )

        );


    foreach (
        $affectedTractorIDs
        as
        $affectedTractorID
    ) {

        haulingUpdateSyncTractorStatus(

            $conn,

            (int) $affectedTractorID

        );

    }


    /* =====================================================
       HISTORY CHANGES
    ===================================================== */

    $changes = [];

    if (
        $dateChanged
    ) {

        if (
            $isReschedule
        ) {

            $changes[] =

                'Rescheduled from '

                .

                $currentRentalDate

                .

                ' to '

                .

                $rentalDate;

        }
        else {

            $changes[] =

                'Rental Date from '

                .

                $currentRentalDate

                .

                ' to '

                .

                $rentalDate;

        }

    }


    if (
        $isReschedule
        &&
        $isDelayed
    ) {

        $changes[] =
            'Status from "Ongoing" to "Pending"';

    }


    if (
        $primaryTractorChanged
    ) {

        $changes[] =

            'Primary Tractor from "'

            .

            (
                $oldTractorNo !== ''

                ?

                $oldTractorNo

                :

                'None'
            )

            .

            '" to "'

            .

            $tractorNo

            .

            '"';

    }


    if (
        $primaryDriverChanged
    ) {

        $changes[] =

            'Primary Driver from "'

            .

            (
                $oldOperator !== ''

                ?

                $oldOperator

                :

                'None'
            )

            .

            '" to "'

            .

            $operator

            .

            '"';

    }


    if (
        $secondaryTractorChanged

        ||

        $secondaryDriverChanged
    ) {

        $oldSecondary =

            (
                $oldSecondaryTractorNo !== ''

                ||

                $oldSecondaryOperator !== ''
            )

            ?

            trim(

                (
                    $oldSecondaryTractorNo !== ''

                    ?

                    $oldSecondaryTractorNo

                    :

                    'No Tractor'
                )

                .

                ' / '

                .

                (
                    $oldSecondaryOperator !== ''

                    ?

                    $oldSecondaryOperator

                    :

                    'No Driver'
                )

            )

            :

            'None';


        $newSecondary =

            (
                $secondaryTractorNo !== null

                ||

                $secondaryOperator !== null
            )

            ?

            trim(

                (
                    $secondaryTractorNo
                    ??
                    'No Tractor'
                )

                .

                ' / '

                .

                (
                    $secondaryOperator
                    ??
                    'No Driver'
                )

            )

            :

            'None';


        $changes[] =

            'Secondary Assignment from "'

            .

            $oldSecondary

            .

            '" to "'

            .

            $newSecondary

            .

            '"';

    }


    /* =====================================================
       HISTORY MESSAGE
    ===================================================== */

    $historyMessage =

        'updated the Hauling rental "'

        .

        $transactionID

        .

        '" for "'

        .

        $customerName

        .

        '": '

        .

        implode(

            '; ',

            $changes

        )

        .

        '.';


    log_activity(

        $conn,

        'Rental',

        'Updated',

        $historyMessage,

        $haulingID

    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    haulingUpdateResponse(
        'success'
    );

}


/* =========================================================
   USER-SAFE VALIDATION ERROR
========================================================= */

catch (
    HaulingUpdateValidationException $error
) {

    mysqli_rollback(
        $conn
    );


    haulingUpdateResponse(

        $error->getMessage(),

        400

    );

}


/* =========================================================
   INTERNAL ERROR

   SQL / PHP details are written only to server log.
========================================================= */

catch (
    Throwable $error
) {

    mysqli_rollback(
        $conn
    );


    error_log(

        '[SPIS][Hauling Update] update failed: '

        .

        $error->getMessage()

    );


    haulingUpdateResponse(

        'Unable to update the Hauling rental. Please try again.',

        500

    );

}


?>