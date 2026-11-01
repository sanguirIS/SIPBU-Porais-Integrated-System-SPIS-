<?php

/* =========================================================
   SPIS - UPDATE REAPER RENTAL
   Assignment + lifecycle + payment update
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: text/plain; charset=UTF-8');

require_once '../../db.php';
require_once '../../includes/history_logger.php';

mysqli_set_charset($conn, 'utf8mb4');

mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   SAFE VALIDATION EXCEPTION
========================================================= */

class ReaperUpdateValidationException extends Exception
{
}


/* =========================================================
   RESPONSE
========================================================= */

function reaperUpdateResponse(
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

function reaperUpdateLogInternalError(
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

        '[SPIS][Reaper Update] '

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

function reaperUpdateLockDriver(
    mysqli $conn,
    int $driverID
): array {

    $stmt =

    mysqli_prepare(
        $conn,
        "
        SELECT
            d.driver_id,

            COALESCE(
                NULLIF(TRIM(m.full_name), ''),
                NULLIF(TRIM(d.full_name), '')
            ) AS full_name,

            d.contact_no,
            d.status

        FROM drivers d

        INNER JOIN members m
            ON m.member_id = d.member_id
            AND m.archived = 0

        WHERE d.driver_id = ?

        AND d.archived = 0

        AND d.status = 'Active'

        AND UPPER(
            TRIM(
                COALESCE(
                    m.status,
                    ''
                )
            )
        ) = 'ACTIVE'

        LIMIT 1

        FOR UPDATE
        "
    );


    if (
        !$stmt
    ) {

        reaperUpdateLogInternalError(
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

        reaperUpdateLogInternalError(
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

        throw new ReaperUpdateValidationException(
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

        throw new ReaperUpdateValidationException(
            'The selected driver is currently inactive.'
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

function reaperUpdateLockTractor(
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

        reaperUpdateLogInternalError(
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

        reaperUpdateLogInternalError(
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

        throw new ReaperUpdateValidationException(
            'The selected tractor was not found.'
        );
    }


    if (
        (
            $tractor['status']
            ??
            ''
        )
        ===
        'Maintenance'
    ) {

        throw new ReaperUpdateValidationException(
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

   Pending / Ongoing block resources.
   Done releases resources.

   Current Reaper record is excluded.
========================================================= */

function reaperUpdateFindAssignmentConflict(

    mysqli $conn,

    string $resourceType,

    int $resourceID,

    string $rentalDate,

    int $excludeReaperID

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


        /* =============================================
           CURRENT REAPER

           Exclude the record being edited.
        ============================================= */

        if (
            $table
            ===
            'reaper'
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

                reaperUpdateLogInternalError(

                    'prepare Reaper conflict lookup',

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

                $excludeReaperID,

                $resourceID,

                $resourceID

            );
        }


        /* =============================================
           HAULING / ROTOR
        ============================================= */

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

                reaperUpdateLogInternalError(

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

            reaperUpdateLogInternalError(

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

function reaperUpdateConflictMessage(

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

        $transactionID
        !==
        ''

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

   ONLY TODAY matters.

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

function reaperUpdateSyncTractorStatus(

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

        reaperUpdateLogInternalError(

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

        reaperUpdateLogInternalError(

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

    /* Never overwrite Maintenance. */

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


    /* =====================================================
       CHECK TODAY'S ACTIVE RENTALS
    ===================================================== */

    foreach (
        [
            'hauling',
            'rotor',
            'reaper'
        ]
        as
        $table
    ) {

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

            reaperUpdateLogInternalError(

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

            reaperUpdateLogInternalError(

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


        $activeResult =

        mysqli_stmt_get_result(
            $stmt
        );


        if (
            $activeResult
        ) {

            while (
                $row =
                mysqli_fetch_assoc(
                    $activeResult
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


        if (
            $hasInUse
        ) {

            break;
        }
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

        reaperUpdateLogInternalError(

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

        reaperUpdateLogInternalError(

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

    reaperUpdateResponse(

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

    (int)
    $_SESSION['user_id']

    :

    0;


if (
    $actorID <= 0
) {

    reaperUpdateResponse(

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

    reaperUpdateLogInternalError(

        'prepare access check',

        $conn

    );


    reaperUpdateResponse(

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

    reaperUpdateLogInternalError(

        'execute access check',

        $conn,

        $actorStmt

    );


    mysqli_stmt_close(
        $actorStmt
    );


    reaperUpdateResponse(

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

    reaperUpdateResponse(

        'You do not have permission to update Rental records.',

        403

    );
}


/* =========================================================
   CSRF VALIDATION
========================================================= */

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

    reaperUpdateResponse(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =========================================================
   REQUEST VALUES
========================================================= */

$reaperID =

    isset(
        $_POST['reaper_id']
    )

    ?

    (int)
    $_POST['reaper_id']

    :

    0;


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

    (int)
    $_POST['tractor_id']

    :

    0;


$primaryDriverID =

    isset(
        $_POST['primary_driver_id']
    )

    ?

    (int)
    $_POST['primary_driver_id']

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

    (int)
    $_POST['secondary_tractor_id']

    :

    null;


$secondaryDriverID =

    !empty(
        $_POST['secondary_driver_id']
    )

    ?

    (int)
    $_POST['secondary_driver_id']

    :

    null;


/* =========================================================
   SERVICE / STATUS / PAYMENT
========================================================= */

$status =

    trim(
        (string) (
            $_POST['status']
            ??
            ''
        )
    );


$remarks =

    trim(
        (string) (
            $_POST['remarks']
            ??
            ''
        )
    );


$harvestRaw =

    trim(
        (string) (
            $_POST['no_of_harvest']
            ??
            ''
        )
    );


$commodityRaw =

    trim(
        (string) (
            $_POST['commodity']
            ??
            ''
        )
    );


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $reaperID <= 0
) {

    reaperUpdateResponse(

        'Invalid Reaper record.',

        400

    );
}


if (
    $rentalDate === ''
) {

    reaperUpdateResponse(

        'Rental Date is required.',

        400

    );
}


/* =========================================================
   DATE FORMAT
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
            )
            ===
            0

            &&

            (
                $dateErrors['error_count']
                ??
                0
            )
            ===
            0

        )

    )

    &&

    $dateObject->format(
        'Y-m-d'
    )
    ===
    $rentalDate;


if (
    !$dateValid
) {

    reaperUpdateResponse(

        'Invalid Rental Date.',

        400

    );
}


/* =========================================================
   PRIMARY ASSIGNMENT REQUIRED
========================================================= */

if (
    $tractorID <= 0
) {

    reaperUpdateResponse(

        'Please select a Primary Tractor.',

        400

    );
}


if (
    $primaryDriverID <= 0
) {

    reaperUpdateResponse(

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

    reaperUpdateResponse(

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

    reaperUpdateResponse(

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

    reaperUpdateResponse(

        'Primary Driver and Secondary Driver cannot be the same.',

        400

    );
}


/* =========================================================
   STATUS
========================================================= */

if (
    !in_array(

        $status,

        [
            'Pending',
            'Ongoing',
            'Done'
        ],

        true

    )
) {

    reaperUpdateResponse(

        'Invalid Rental status.',

        400

    );
}


/* =========================================================
   PAYMENT
========================================================= */

if (
    !in_array(

        $remarks,

        [
            'Unpaid',
            'Paid'
        ],

        true

    )
) {

    reaperUpdateResponse(

        'Invalid payment status.',

        400

    );
}


/* =========================================================
   HARVEST
========================================================= */

$noOfHarvest =
null;


if (
    $harvestRaw !== ''
) {

    if (
        !ctype_digit(
            $harvestRaw
        )

        ||

        (int)
        $harvestRaw
        <
        1
    ) {

        reaperUpdateResponse(

            'No. of Harvest must be a whole number of at least 1.',

            400

        );
    }


    $noOfHarvest =

    (int)
    $harvestRaw;
}


/* =========================================================
   COMMODITY
========================================================= */

$commodity =
null;


if (
    $commodityRaw !== ''
) {

    if (
        !ctype_digit(
            $commodityRaw
        )
        ||
        (int)
        $commodityRaw
        <
        1
    ) {

        reaperUpdateResponse(

            'Commodity must be a whole number of at least 1.',

            400

        );
    }


    $commodity =

    (int)
    $commodityRaw;
}


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {


    /* =====================================================
       LOCK + LOAD CURRENT REAPER
    ===================================================== */

    $currentStmt =

    mysqli_prepare(
        $conn,
        "
        SELECT

            r.reaper_id,

            r.transaction_id,

            r.customer_name,

            r.status,

            r.remarks,

            DATE(r.rental_date)
                AS current_rental_date,


            r.tractor_id,

            r.primary_driver_id,

            r.secondary_tractor_id,

            r.secondary_driver_id,


            r.tractor_no,

            r.operator,

            r.secondary_tractor_no,

            r.secondary_operator,


            r.no_of_harvest,

            r.commodity,


            r.actual_started_date,

            r.completed_date,


            COALESCE(
                r.reschedule_count,
                0
            )
                AS reschedule_count,

            r.last_rescheduled_from,

            r.last_rescheduled_at,


            t.status
                AS current_primary_tractor_status,


            CURDATE()
                AS today


        FROM reaper r


        LEFT JOIN tractors t

            ON t.tractor_id =
                r.tractor_id


        WHERE r.reaper_id = ?

        LIMIT 1

        FOR UPDATE
        "
    );


    if (
        !$currentStmt
    ) {

        reaperUpdateLogInternalError(

            'prepare current Reaper lookup',

            $conn

        );


        throw new RuntimeException(
            'Unable to load the Reaper rental.'
        );
    }


    mysqli_stmt_bind_param(

        $currentStmt,

        'i',

        $reaperID

    );


    if (
        !mysqli_stmt_execute(
            $currentStmt
        )
    ) {

        reaperUpdateLogInternalError(

            'execute current Reaper lookup',

            $conn,

            $currentStmt

        );


        mysqli_stmt_close(
            $currentStmt
        );


        throw new RuntimeException(
            'Unable to load the Reaper rental.'
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

        throw new ReaperUpdateValidationException(
            'The selected Reaper rental was not found.'
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


    $currentPrimaryTractorStatus =

        trim(
            (string) (
                $current['current_primary_tractor_status']
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

        (int)
        $current['secondary_tractor_id']

        :

        null;


    $oldSecondaryDriverID =

        !empty(
            $current['secondary_driver_id']
        )

        ?

        (int)
        $current['secondary_driver_id']

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


    $oldHarvest =

        $current['no_of_harvest'] === null

        ||

        $current['no_of_harvest'] === ''

        ?

        null

        :

        (int)
        $current['no_of_harvest'];


    $oldCommodity =

        $current['commodity'] === null

        ||

        $current['commodity'] === ''

        ?

        null

        :

        round(

            (float)
            $current['commodity'],

            2

        );


    $actualStartedDate =

        trim(
            (string) (
                $current['actual_started_date']
                ??
                ''
            )
        );


    $completedDate =

        trim(
            (string) (
                $current['completed_date']
                ??
                ''
            )
        );


    $actualStartedDate =

        $actualStartedDate === ''

        ?

        null

        :

        $actualStartedDate;

        $completedDate =

        $completedDate === ''

        ?

        null

        :

        $completedDate;


    /* =====================================================
    CURRENT RESCHEDULE VALUES
    ===================================================== */

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
            'Unable to verify the Reaper rental date.'
        );
    }


    /* =====================================================
       VALIDATE CURRENT DATABASE STATE
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
            'This Reaper rental has an invalid status.'
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
            'This Reaper rental has invalid payment remarks.'
        );
    }


    /* =====================================================
       PAID = FINAL
    ===================================================== */

    if (
        $currentRemarks
        ===
        'Paid'
    ) {

        throw new ReaperUpdateValidationException(
            'This Reaper rental is already Paid and finalized.'
        );
    }


    /* =====================================================
    SCHEDULE / RESCHEDULE STATE
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

    /* =====================================================
    DONE RENTAL DATE IS FINAL
    ===================================================== */

    if (
        $currentStatus
        ===
        'Done'

        &&

        $dateChanged
    ) {

        throw new ReaperUpdateValidationException(
            'Rental Date is locked once the Reaper service is Done.'
        );
    }


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


    /* =====================================================
    REGISTER RESCHEDULE
    ===================================================== */

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
        Missed / Delayed reschedules always become
        a fresh Pending schedule.
        */

        $status =
        'Pending';


        $remarks =
        'Unpaid';


        $actualStartedDate =
        null;


        $completedDate =
        null;
    }
    
    /* =====================================================
    STATUS TRANSITION FLOW

    Normal:
    Pending -> Pending / Ongoing / Done
    Ongoing -> Ongoing / Done
    Done    -> Done

    Direct Pending -> Done is allowed only when the
    completion data passes the validation below.

    Exception:
    Missed / Delayed reschedule is automatically
    reset to Pending above.
    ===================================================== */

    if (
        !$isReschedule
    ) {

        if (
            $currentStatus === 'Pending'
            &&
            !in_array(
                $status,
                [
                    'Pending',
                    'Ongoing',
                    'Done'
                ],
                true
            )
        ) {

            throw new ReaperUpdateValidationException(
                'Invalid status transition for this Pending Reaper rental.'
            );
        }


        if (
            $currentStatus === 'Ongoing'
            &&
            !in_array(
                $status,
                [
                    'Ongoing',
                    'Done'
                ],
                true
            )
        ) {

            throw new ReaperUpdateValidationException(
                'An Ongoing Reaper rental cannot be changed back to Pending.'
            );
        }
    }


    /* =====================================================
    PENDING / MISSED

    A past Pending record normally needs rescheduling.

    Exception:
    If the service was actually completed and the user
    is saving it as Done, keep the original Rental Date.
    Completion-data validation below still applies.
    ===================================================== */

    if (
        $currentStatus
        ===
        'Pending'

        &&

        $status
        !==
        'Done'
    ) {

        if (
            $isMissed

            &&

            !$dateChanged
        ) {

            throw new ReaperUpdateValidationException(
                'This Reaper rental is Missed. Please select a new Rental Date before saving.'
            );
        }


        if (
            $rentalDate
            <
            $today
        ) {

            throw new ReaperUpdateValidationException(
                'A Missed Reaper rental can only be rescheduled to today or a future date.'
            );
        }
    }


    /* =====================================================
    ONGOING / DELAYED
    ===================================================== */

    if (
        $currentStatus
        ===
        'Ongoing'
    ) {

        if (
            $isDelayed
        ) {

            /*
            Delayed + new date:
            Date and assignments may be changed.
            */

            if (
                $dateChanged
            ) {

                if (
                    $rentalDate
                    <
                    $today
                ) {

                    throw new ReaperUpdateValidationException(
                        'A Delayed Reaper rental can only be rescheduled to today or a future date.'
                    );
                }

            }

        }
        else {

            /*
            Normal Ongoing:
            Rental Date stays locked.
            */

            if (
                $dateChanged
            ) {

                throw new ReaperUpdateValidationException(
                    'Rental Date is locked once the Reaper service is Ongoing.'
                );
            }

        }
    }


    /* =====================================================
    NEW DATE RULES
    ===================================================== */

    $isFuture =

        $rentalDate
        >
        $today;


    if (
        $isFuture

        &&

        $status
        !==
        'Pending'
    ) {

        throw new ReaperUpdateValidationException(
            'A future Reaper rental must remain Pending until its service date.'
        );
    }


    if (
        $isFuture

        &&

        (
            $noOfHarvest !== null

            ||

            $commodity !== null
        )
    ) {

        throw new ReaperUpdateValidationException(
            'No. of Harvest and Commodity cannot be entered before the Reaper service date.'
        );
    }


    /* =====================================================
    DONE REQUIRES COMPLETION
    ===================================================== */

    if (
        $status
        ===
        'Done'

        &&

        (
            $noOfHarvest === null

            ||

            $commodity === null
        )
    ) {

        throw new ReaperUpdateValidationException(
            'No. of Harvest and Commodity are required before marking this rental as Done.'
        );
    }


    /* =====================================================
    PENDING / ONGOING = UNPAID
    ===================================================== */

    if (
        in_array(

            $status,

            [
                'Pending',
                'Ongoing'
            ],

            true

        )

        &&

        $remarks
        !==
        'Unpaid'
    ) {

        throw new ReaperUpdateValidationException(
            'Pending and Ongoing Reaper rentals must remain Unpaid.'
        );
    }


    /* =====================================================
    PAID REQUIRES DONE
    ===================================================== */

    if (
        $remarks
        ===
        'Paid'

        &&

        (
            $status
            !==
            'Done'

            ||

            $noOfHarvest === null

            ||

            $commodity === null
        )
    ) {

        throw new ReaperUpdateValidationException(
            'The rental must be Done with completion data before it can be marked Paid.'
        );
    }


    /* =====================================================
    DONE STATUS IS FINAL
    ===================================================== */

    if (
        $currentStatus
        ===
        'Done'

        &&

        $status
        !==
        'Done'
    ) {

        throw new ReaperUpdateValidationException(
            'This Reaper rental is already completed. Done status is final.'
        );
    }


    /* =====================================================
    ASSIGNMENT CHANGES
    ===================================================== */

    $primaryAssignmentChanged =

        $tractorID
        !==
        $oldTractorID

        ||

        $primaryDriverID
        !==
        $oldPrimaryDriverID;


    $secondaryAssignmentChanged =

        (
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
            )
        )

        ||

        (
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
            )
        );


    /* DONE = ASSIGNMENT LOCKED */

    if (
        $currentStatus
        ===
        'Done'

        &&

        (
            $primaryAssignmentChanged

            ||

            $secondaryAssignmentChanged
        )
    ) {

        throw new ReaperUpdateValidationException(
            'Rental Assignment is locked once the Reaper service is Done.'
        );
    }


    /* =====================================================
    ONGOING PRIMARY LOCK

    Normal Ongoing:
    Primary locked.

    Delayed:
    Primary unlocks only after a new date is selected.

    Maintenance:
    replacement remains allowed.
    ===================================================== */

    if (
        $currentStatus
        ===
        'Ongoing'

        &&

        $primaryAssignmentChanged
    ) {

        if (
            $isDelayed
        ) {

            if (
                !$dateChanged

                &&

                $currentPrimaryTractorStatus
                !==
                'Maintenance'
            ) {

                throw new ReaperUpdateValidationException(
                    'Change the Rental Date first before changing the Primary Assignment of a Delayed Reaper rental.'
                );
            }

        }
        elseif (
            $currentPrimaryTractorStatus
            !==
            'Maintenance'
        ) {

            throw new ReaperUpdateValidationException(
                'Primary Tractor and Primary Driver are locked once the Reaper service is Ongoing.'
            );
        }
    }


    /* =====================================================
       CANONICAL ASSIGNMENT VALUES
    ===================================================== */

    $tractorNo =
    $oldTractorNo;


    $operator =
    $oldOperator;


    $secondaryTractorNo =

        $oldSecondaryTractorNo !== ''

        ?

        $oldSecondaryTractorNo

        :

        null;


    $secondaryOperator =

        $oldSecondaryOperator !== ''

        ?

        $oldSecondaryOperator

        :

        null;


    /* =====================================================
       LIVE REGISTRY VALIDATION

       Current Done records keep historical snapshots.

       Pending / Ongoing records and records becoming Done
       validate the submitted assignments.
    ===================================================== */

    if (
        $currentStatus
        !==
        'Done'
    ) {

        $primaryTractor =

        reaperUpdateLockTractor(

            $conn,

            $tractorID

        );


        $primaryDriver =

        reaperUpdateLockDriver(

            $conn,

            $primaryDriverID

        );


        $secondaryTractor =
        null;


        $secondaryDriver =
        null;


        if (
            $hasSecondaryTractor
        ) {

            $secondaryTractor =

            reaperUpdateLockTractor(

                $conn,

                (int)
                $secondaryTractorID

            );


            $secondaryDriver =

            reaperUpdateLockDriver(

                $conn,

                (int)
                $secondaryDriverID

            );
        }


        /* =============================================
           CANONICAL SNAPSHOTS

           Browser names are ignored.
        ============================================= */

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
    }


    /* =====================================================
       ACTIVE ASSIGNMENT CONFLICTS

       Done releases resources.
    ===================================================== */

    if (
        in_array(

            $status,

            [
                'Pending',
                'Ongoing'
            ],

            true

        )
    ) {


        /* =============================================
           PRIMARY TRACTOR
        ============================================= */

        $conflict =

        reaperUpdateFindAssignmentConflict(

            $conn,

            'tractor',

            $tractorID,

            $rentalDate,

            $reaperID

        );


        if (
            $conflict
        ) {

            throw new ReaperUpdateValidationException(

                reaperUpdateConflictMessage(

                    'Primary Tractor',

                    $conflict,

                    $rentalDate

                )

            );
        }


        /* =============================================
           SECONDARY TRACTOR
        ============================================= */

        if (
            $hasSecondaryTractor
        ) {

            $conflict =

            reaperUpdateFindAssignmentConflict(

                $conn,

                'tractor',

                (int)
                $secondaryTractorID,

                $rentalDate,

                $reaperID

            );


            if (
                $conflict
            ) {

                throw new ReaperUpdateValidationException(

                    reaperUpdateConflictMessage(

                        'Secondary Tractor',

                        $conflict,

                        $rentalDate

                    )

                );
            }
        }


        /* =============================================
           PRIMARY DRIVER
        ============================================= */

        $conflict =

        reaperUpdateFindAssignmentConflict(

            $conn,

            'driver',

            $primaryDriverID,

            $rentalDate,

            $reaperID

        );


        if (
            $conflict
        ) {

            throw new ReaperUpdateValidationException(

                reaperUpdateConflictMessage(

                    'Primary Driver',

                    $conflict,

                    $rentalDate

                )

            );
        }


        /* =============================================
           SECONDARY DRIVER
        ============================================= */

        if (
            $hasSecondaryDriver
        ) {

            $conflict =

            reaperUpdateFindAssignmentConflict(

                $conn,

                'driver',

                (int)
                $secondaryDriverID,

                $rentalDate,

                $reaperID

            );


            if (
                $conflict
            ) {

                throw new ReaperUpdateValidationException(

                    reaperUpdateConflictMessage(

                        'Secondary Driver',

                        $conflict,

                        $rentalDate

                    )

                );
            }
        }
    }


    /* =====================================================
    LIFECYCLE DATES
    ===================================================== */

    if (
        $status
        ===
        'Pending'
    ) {

        $actualStartedDate =
        null;


        $completedDate =
        null;

    }
    elseif (
        $status
        ===
        'Ongoing'
    ) {

        if (
            $actualStartedDate
            ===
            null
        ) {

            $actualStartedDate =
            $today;

        }


        $completedDate =
        null;

    }
    elseif (
        $status
        ===
        'Done'
    ) {

        if (
            $actualStartedDate
            ===
            null
        ) {

            $actualStartedDate =
            $rentalDate;

        }


        if (
            $completedDate
            ===
            null
        ) {

            $completedDate =
            $today;

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


    $harvestChanged =

        $noOfHarvest
        !==
        $oldHarvest;


    $commodityChanged =

        (
            $commodity === null

            xor

            $oldCommodity === null
        )

        ||

        (
            $commodity !== null

            &&

            $oldCommodity !== null

            &&

            abs(
                $commodity
                -
                $oldCommodity
            )
            >
            0.00001
        );


    $statusChanged =

        $status
        !==
        $currentStatus;


    $remarksChanged =

        $remarks
        !==
        $currentRemarks;


    $hasChanges =

        $dateChanged

        ||

        $primaryTractorChanged

        ||

        $primaryDriverChanged

        ||

        $secondaryTractorChanged

        ||

        $secondaryDriverChanged

        ||

        $harvestChanged

        ||

        $commodityChanged

        ||

        $statusChanged

        ||

        $remarksChanged;


    /* =====================================================
       NOTHING CHANGED
    ===================================================== */

    if (
        !$hasChanges
    ) {

        mysqli_commit(
            $conn
        );


        reaperUpdateResponse(
            'success'
        );
    }


    /* =====================================================
       UPDATE REAPER
    ===================================================== */

    $updateStmt =

    mysqli_prepare(
        $conn,
        "
        UPDATE reaper

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


            no_of_harvest = ?,

            commodity = ?,


            status = ?,

            remarks = ?,


            actual_started_date = ?,

            completed_date = ?,

            reschedule_count = ?,

            last_rescheduled_from = ?,

            last_rescheduled_at = ?


        WHERE reaper_id = ?

        LIMIT 1
        "
    );


    if (
        !$updateStmt
    ) {

        reaperUpdateLogInternalError(

            'prepare Reaper update',

            $conn

        );


        throw new RuntimeException(
            'Unable to prepare Reaper update.'
        );
    }


    mysqli_stmt_bind_param(

        $updateStmt,

        'siiiissssidssssissi',

        $rentalDate,

        $tractorID,

        $primaryDriverID,

        $secondaryTractorID,

        $secondaryDriverID,


        $tractorNo,

        $operator,

        $secondaryTractorNo,

        $secondaryOperator,


        $noOfHarvest,

        $commodity,


        $status,

        $remarks,


        $actualStartedDate,

        $completedDate,


        $newRescheduleCount,

        $newLastRescheduledFrom,

        $newLastRescheduledAt,


        $reaperID

    );


    if (
        !mysqli_stmt_execute(
            $updateStmt
        )
    ) {

        reaperUpdateLogInternalError(

            'execute Reaper update',

            $conn,

            $updateStmt

        );


        mysqli_stmt_close(
            $updateStmt
        );


        throw new RuntimeException(
            'Unable to update the Reaper rental.'
        );
    }


    mysqli_stmt_close(
        $updateStmt
    );


    /* =====================================================
       PHYSICAL TRACTOR STATUS SYNC

       Include OLD + NEW Primary / Secondary tractors.

       REAPER:
       TODAY Pending / Ongoing = In Use
       No TODAY active record  = Available
       Future / Past = no effect
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
        )
        >
        0
    ) {

        $affectedTractorIDs[] =

            (int)
            $oldSecondaryTractorID;
    }


    if (
        (
            $secondaryTractorID
            ??
            0
        )
        >
        0
    ) {

        $affectedTractorIDs[] =

            (int)
            $secondaryTractorID;
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
                            (int)
                            $id
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

        reaperUpdateSyncTractorStatus(

            $conn,

            (int)
            $affectedTractorID

        );
    }


    /* =====================================================
       HISTORY CHANGES
    ===================================================== */

    $changes =
    [];

    /* RENTAL DATE / RESCHEDULE */

    if (
        $dateChanged
    ) {

        if (
            $isReschedule
        ) {

            $changes[] =

                'Rescheduled Rental Date from "'

                .

                $currentRentalDate

                .

                '" to "'

                .

                $rentalDate

                .

                '"';

        }
        else {

            $changes[] =

                'Rental Date from "'

                .

                $currentRentalDate

                .

                '" to "'

                .

                $rentalDate

                .

                '"';

        }

    }


    /* PRIMARY TRACTOR */

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


    /* PRIMARY DRIVER */

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


    /* SECONDARY ASSIGNMENT */

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


    /* HARVEST */

    if (
        $harvestChanged
    ) {

        $changes[] =

            'No. of Harvest from "'

            .

            (
                $oldHarvest !== null

                ?

                (string)
                $oldHarvest

                :

                'None'
            )

            .

            '" to "'

            .

            (
                $noOfHarvest !== null

                ?

                (string)
                $noOfHarvest

                :

                'None'
            )

            .

            '"';
    }


    /* COMMODITY */

    if (
        $commodityChanged
    ) {

        $changes[] =

            'Commodity from "'

            .

            (
                $oldCommodity !== null

                ?

                number_format(
                    $oldCommodity,
                    2,
                    '.',
                    ''
                )

                :

                'None'
            )

            .

            '" to "'

            .

            (
                $commodity !== null

                ?

                number_format(
                    $commodity,
                    2,
                    '.',
                    ''
                )

                :

                'None'
            )

            .

            '"';
    }


    /* STATUS */

    if (
        $statusChanged
    ) {

        $changes[] =

            'Status from "'

            .

            $currentStatus

            .

            '" to "'

            .

            $status

            .

            '"';
    }


    /* PAYMENT */

    if (
        $remarksChanged
    ) {

        $changes[] =

            'Payment from "'

            .

            $currentRemarks

            .

            '" to "'

            .

            $remarks

            .

            '"';
    }


    /* =====================================================
       HISTORY MESSAGE
    ===================================================== */

    $historyMessage =

        'updated the Reaper rental "'

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

        $reaperID

    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    reaperUpdateResponse(
        'success'
    );
}


/* =========================================================
   USER-SAFE VALIDATION ERROR
========================================================= */

catch (
    ReaperUpdateValidationException $error
) {

    mysqli_rollback(
        $conn
    );


    reaperUpdateResponse(

        $error->getMessage(),

        400

    );
}


/* =========================================================
   INTERNAL ERROR
========================================================= */

catch (
    Throwable $error
) {

    mysqli_rollback(
        $conn
    );


    error_log(

        '[SPIS][Reaper Update] update failed: '

        .

        $error->getMessage()

    );


    reaperUpdateResponse(

        'Unable to update the Reaper rental. Please try again.',

        500

    );
}

?>