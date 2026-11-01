<?php

/* =========================================================
   SPIS - UPDATE ROTOR RENTAL
   Edit schedule / assignment only.
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);

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

class RotorUpdateValidationException extends Exception
{
}


/* =========================================================
   RESPONSE
========================================================= */

function rotorUpdateResponse(
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

function rotorUpdateLogError(
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

        '[SPIS][Rotor Update] '

        .

        $context

        .

        ': '

        .

        $error

    );
}


/* =========================================================
   VALID DATE
========================================================= */

function rotorUpdateValidDate(
    string $date
): bool {

    $parsed =

        DateTimeImmutable::createFromFormat(

            '!Y-m-d',

            $date

        );


    $errors =

        DateTimeImmutable::getLastErrors();


    return

        $parsed !== false

        &&

        (

            $errors === false

            ||

            (

                (
                    $errors['warning_count']
                    ??
                    0
                ) === 0

                &&

                (
                    $errors['error_count']
                    ??
                    0
                ) === 0

            )

        )

        &&

        $parsed->format(
            'Y-m-d'
        ) === $date;
}


/* =========================================================
   LOCK / VALIDATE DRIVER
========================================================= */

function rotorUpdateLockDriver(
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
                    NULLIF(
                        TRIM(
                            m.full_name
                        ),
                        ''
                    ),
                    NULLIF(
                        TRIM(
                            d.full_name
                        ),
                        ''
                    )
                )
                AS full_name,

                d.status

            FROM drivers d

            INNER JOIN members m

                ON m.member_id =
                    d.member_id

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

        rotorUpdateLogError(

            'prepare driver lookup',

            $conn

        );


        throw new RuntimeException(
            'Unable to validate Driver.'
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

        rotorUpdateLogError(

            'execute driver lookup',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(
            'Unable to validate Driver.'
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

        throw new RotorUpdateValidationException(

            'The selected Driver is unavailable, inactive, or no longer linked to an active Member.'

        );
    }


    if (
        trim(
            (string) (
                $driver['full_name']
                ??
                ''
            )
        ) === ''
    ) {

        throw new RuntimeException(
            'Unable to verify the selected Driver name.'
        );
    }


    return $driver;
}


/* =========================================================
   LOCK / VALIDATE TRACTOR

   Tractor Type is classification only.
   Any active registered tractor may be assigned
   to Hauling, Rotor, or Reaper.
========================================================= */

function rotorUpdateLockTractor(

    mysqli $conn,

    int $tractorID,

    bool $allowExistingMaintenance = false

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

        rotorUpdateLogError(

            'prepare tractor lookup',

            $conn

        );


        throw new RuntimeException(
            'Unable to validate Tractor.'
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

        rotorUpdateLogError(

            'execute tractor lookup',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(
            'Unable to validate Tractor.'
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

        throw new RotorUpdateValidationException(

            'The selected Tractor was not found or is archived.'

        );
    }


    /*
       Existing assignment that later became Maintenance
       may remain untouched.

       Maintenance can never be newly assigned.
    */

    if (

        (
            $tractor['status']
            ??
            ''
        )
        ===
        'Maintenance'

        &&

        !$allowExistingMaintenance

    ) {

        throw new RotorUpdateValidationException(

            'The selected Tractor is under Maintenance and cannot be newly assigned.'

        );
    }


    return $tractor;
}


/* =========================================================
   CROSS-RENTAL ASSIGNMENT CONFLICT
========================================================= */

function rotorUpdateFindConflict(

    mysqli $conn,

    string $resourceType,

    int $resourceID,

    string $rentalDate,

    int $excludeRotorID

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
           CURRENT ROTOR TABLE

           Exclude itself.
        ============================================= */

        if (
            $table ===
            'rotor'
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

                rotorUpdateLogError(

                    'prepare Rotor conflict lookup',

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

                $excludeRotorID,

                $resourceID,

                $resourceID

            );

        }

        /* =============================================
           HAULING / REAPER
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

                rotorUpdateLogError(

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

            rotorUpdateLogError(

                'execute conflict lookup',

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


                'transaction_id' =>

                    trim(
                        (string) (
                            $row['transaction_id']
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

function rotorUpdateConflictMessage(

    string $label,

    array $conflict,

    string $rentalDate

): string {


    $type =

        trim(
            (string) (
                $conflict['rental_type']
                ??
                'Rental'
            )
        );


    $transactionID =

        trim(
            (string) (
                $conflict['transaction_id']
                ??
                ''
            )
        );


    $record =

        $transactionID !== ''

        ?

        $type
        .
        ' Rental "'
        .
        $transactionID
        .
        '"'

        :

        $type
        .
        ' Rental';


    return

        $label

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
   PHYSICAL TRACTOR STATUS SYNC

   Ongoing in any Rental service:
       In Use

   Pending Reaper:
       In Use

   Pending Hauling / Rotor:
       Reserved

   No active booking today:
       Available

   In Use has priority over Reserved.
   Maintenance is never overwritten.
========================================================= */

function rotorUpdateSyncTractorStatus(

    mysqli $conn,

    int $tractorID

): void {


    if (
        $tractorID <= 0
    ) {

        return;
    }


    $stmt =

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
        !$stmt
    ) {

        rotorUpdateLogError(

            'prepare tractor status lookup',

            $conn

        );


        throw new RuntimeException(
            'Unable to synchronize Tractor status.'
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

        rotorUpdateLogError(

            'execute tractor status lookup',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(
            'Unable to synchronize Tractor status.'
        );
    }


    $result =

        mysqli_stmt_get_result(
            $stmt
        );


    $registry =

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
        !$registry

        ||

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

            rotorUpdateLogError(

                'prepare current Tractor use lookup',

                $conn

            );


            throw new RuntimeException(
                'Unable to synchronize Tractor status.'
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

            rotorUpdateLogError(

                'execute current Tractor use lookup',

                $conn,

                $stmt

            );


            mysqli_stmt_close(
                $stmt
            );


            throw new RuntimeException(
                'Unable to synchronize Tractor status.'
            );
        }


        $active =

            mysqli_stmt_get_result(
                $stmt
            );


        if (
            $active
        ) {

            while (
                $row =
                mysqli_fetch_assoc(
                    $active
                )
            ) {

                $status =

                    trim(
                        (string) (
                            $row['status']
                            ??
                            ''
                        )
                    );


                if (
                    $status ===
                    'Ongoing'
                ) {

                    $hasInUse =
                        true;

                }

                elseif (
                    $status ===
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


    $stmt =

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
        !$stmt
    ) {

        rotorUpdateLogError(

            'prepare Tractor status sync',

            $conn

        );


        throw new RuntimeException(
            'Unable to synchronize Tractor status.'
        );
    }


    mysqli_stmt_bind_param(

        $stmt,

        'si',

        $physicalStatus,

        $tractorID

    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        rotorUpdateLogError(

            'execute Tractor status sync',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(
            'Unable to synchronize Tractor status.'
        );
    }


    mysqli_stmt_close(
        $stmt
    );
}


/* =========================================================
   REQUEST METHOD
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

    rotorUpdateResponse(

        'Invalid request.',

        405

    );
}


/* =========================================================
   SESSION
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

    rotorUpdateResponse(

        'Your session has expired. Please sign in again.',

        401

    );
}


/* =========================================================
   LIVE RENTAL ACCESS CHECK
========================================================= */

$stmt =

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
    !$stmt
) {

    rotorUpdateLogError(

        'prepare Rental access check',

        $conn

    );


    rotorUpdateResponse(

        'Unable to verify Rental access.',

        500

    );
}


mysqli_stmt_bind_param(

    $stmt,

    'i',

    $actorID

);


if (
    !mysqli_stmt_execute(
        $stmt
    )
) {

    rotorUpdateLogError(

        'execute Rental access check',

        $conn,

        $stmt

    );


    mysqli_stmt_close(
        $stmt
    );


    rotorUpdateResponse(

        'Unable to verify Rental access.',

        500

    );
}


$result =

    mysqli_stmt_get_result(
        $stmt
    );


$hasAccess =

    $result

    &&

    mysqli_num_rows(
        $result
    ) === 1;


mysqli_stmt_close(
    $stmt
);


if (
    !$hasAccess
) {

    rotorUpdateResponse(

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

    rotorUpdateResponse(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =========================================================
   POST VALUES

   IMPORTANT:

   Browser-submitted:
   - names
   - status
   - remarks
   - prices

   are intentionally ignored.
========================================================= */

$rotorID =

    isset(
        $_POST['rotor_id']
    )

    ?

    (int)
    $_POST['rotor_id']

    :

    (
        isset(
            $_POST['id']
        )

        ?

        (int)
        $_POST['id']

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


$additionalPaymentText =

    trim(
        (string) (
            $_POST['additional_payment']
            ??
            ''
        )
    );


if (
    $additionalPaymentText === ''
) {

    $additionalPayment =
        0.00;

}
else {

    if (
        !preg_match(
            '/^\d+(?:\.\d{1,2})?$/',
            $additionalPaymentText
        )
    ) {

        rotorUpdateResponse(
            'Please enter a valid Additional Payment amount.',
            400
        );
    }


    $additionalPayment =

        round(
            (float) $additionalPaymentText,
            2
        );
}


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $rotorID <= 0
) {

    rotorUpdateResponse(

        'Invalid Rotor record.',

        400

    );
}


if (
    $rentalDate === ''

    ||

    !rotorUpdateValidDate(
        $rentalDate
    )
) {

    rotorUpdateResponse(

        'Please select a valid Rental date.',

        400

    );
}


if (
    $tractorID <= 0
) {

    rotorUpdateResponse(

        'Please select a Primary Tractor.',

        400

    );
}


if (
    $primaryDriverID <= 0
) {

    rotorUpdateResponse(

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

    rotorUpdateResponse(

        'Secondary Tractor and Secondary Driver must be selected together.',

        400

    );
}


/* =========================================================
   DUPLICATE PRIMARY / SECONDARY
========================================================= */

if (

    $hasSecondaryTractor

    &&

    $secondaryTractorID ===
    $tractorID

) {

    rotorUpdateResponse(

        'Primary Tractor and Secondary Tractor cannot be the same.',

        400

    );
}


if (

    $hasSecondaryDriver

    &&

    $secondaryDriverID ===
    $primaryDriverID

) {

    rotorUpdateResponse(

        'Primary Driver and Secondary Driver cannot be the same.',

        400

    );
}


/* =========================================================
   RECORD ROTOR ADDITIONAL PAYMENT
========================================================= */

function rotorRecordAdditionalPayment(
    mysqli $conn,
    int $rotorID,
    float $amount,
    float $balanceAfter,
    int $actorID
): void {

    if ($amount <= 0) {
        return;
    }

    $paymentType = $balanceAfter <= 0.009
        ? 'Full Payment'
        : 'Installment';

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO rotor_payments
            (
                rotor_id,
                payment_type,
                amount,
                balance_after,
                payment_date,
                recorded_by_id
            )
         VALUES (?, ?, ?, ?, NOW(), ?)"
    );

    if (!$stmt) {
        throw new RuntimeException(
            'Unable to prepare Rotor payment history.'
        );
    }

    mysqli_stmt_bind_param(
        $stmt,
        'isddi',
        $rotorID,
        $paymentType,
        $amount,
        $balanceAfter,
        $actorID
    );

    if (!mysqli_stmt_execute($stmt)) {

        rotorUpdateLogError(
            'insert Rotor payment history',
            $conn,
            $stmt
        );

        mysqli_stmt_close($stmt);

        throw new RuntimeException(
            'Unable to record Rotor payment history.'
        );
    }

    mysqli_stmt_close($stmt);
}



/* =========================================================
   DATABASE TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {


    /* =====================================================
       LOAD / LOCK CURRENT ROTOR
    ===================================================== */

    $stmt =

        mysqli_prepare(

            $conn,

            "
            SELECT

                rotor_id,
                transaction_id,
                customer_name,

                status,
                remarks,

                total_price,
                COALESCE(
                    amount_paid,
                    0
                )
                    AS amount_paid,

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

                actual_started_date,
                completed_date,

                COALESCE(
                    reschedule_count,
                    0
                )
                    AS reschedule_count,

                last_rescheduled_from,
                last_rescheduled_at,

                CURDATE()
                    AS today

            FROM rotor

            WHERE rotor_id = ?

            LIMIT 1

            FOR UPDATE
            "

        );


    if (
        !$stmt
    ) {

        rotorUpdateLogError(

            'prepare current Rotor lookup',

            $conn

        );


        throw new RuntimeException(
            'Unable to load the Rotor rental.'
        );
    }


    mysqli_stmt_bind_param(

        $stmt,

        'i',

        $rotorID

    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        rotorUpdateLogError(

            'execute current Rotor lookup',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(
            'Unable to load the Rotor rental.'
        );
    }


    $result =

        mysqli_stmt_get_result(
            $stmt
        );


    $current =

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
        !$current
    ) {

        throw new RotorUpdateValidationException(

            'The selected Rotor rental was not found.'

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


    $currentTotalPrice =

        round(
            (float) (
                $current['total_price']
                ??
                0
            ),
            2
        );


    $currentAmountPaid =

        round(
            (float) (
                $current['amount_paid']
                ??
                0
            ),
            2
        );


    $currentBalance =

        round(
            max(
                0,
                $currentTotalPrice - $currentAmountPaid
            ),
            2
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


    $currentActualStartedDate =

        trim(
            (string) (
                $current['actual_started_date']
                ??
                ''
            )
        );


    $currentCompletedDate =

        trim(
            (string) (
                $current['completed_date']
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
            'Unable to verify the Rotor Rental date.'
        );
    }


    /* =====================================================
       VALIDATE DATABASE STATE
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
            'This Rotor rental has an invalid status.'
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
            'This Rotor rental has invalid payment remarks.'
        );
    }

        if (
        $currentTotalPrice < 0

        ||

        $currentAmountPaid < 0

        ||

        $currentAmountPaid >
        $currentTotalPrice + 0.009
    ) {

        throw new RuntimeException(
            'This Rotor rental has invalid payment data.'
        );
    }


    if (
        $additionalPayment >
        $currentBalance + 0.009
    ) {

        throw new RotorUpdateValidationException(
            'Additional Payment cannot exceed the current balance.'
        );
    }


    $newAmountPaid =

        round(
            $currentAmountPaid + $additionalPayment,
            2
        );


    if (
        abs(
            $currentTotalPrice - $newAmountPaid
        ) <= 0.009
    ) {

        $newAmountPaid =
            $currentTotalPrice;
    }


    $newBalance =

        round(
            max(
                0,
                $currentTotalPrice - $newAmountPaid
            ),
            2
        );


    $newRemarks =

        $newBalance <= 0.009

        ?

        'Paid'

        :

        'Unpaid';


    $paymentChanged =

        $additionalPayment > 0.009;


    /* =====================================================
       DONE + UNPAID
       Payment only. Schedule / assignments stay locked.
    ===================================================== */

    if (
        $currentStatus ===
        'Done'
    ) {

        if (
            $currentBalance <= 0.009
        ) {

            throw new RotorUpdateValidationException(

                'This completed Rotor rental is already fully paid.'

            );
        }


        if (
            !$paymentChanged
        ) {

            throw new RotorUpdateValidationException(

                'Please enter an Additional Payment.'

            );
        }


        if (

            $rentalDate !==
            $currentRentalDate

            ||

            $tractorID !==
            $oldTractorID

            ||

            $primaryDriverID !==
            $oldPrimaryDriverID

            ||

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

            ||

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

        ) {

            throw new RotorUpdateValidationException(

                'Completed Rotor rentals only allow Additional Payment.'

            );
        }


        $stmt =

            mysqli_prepare(

                $conn,

                "
                UPDATE rotor

                SET

                    amount_paid = ?,

                    remarks = ?

                WHERE rotor_id = ?

                LIMIT 1
                "

            );


        if (
            !$stmt
        ) {

            rotorUpdateLogError(

                'prepare completed Rotor payment update',

                $conn

            );


            throw new RuntimeException(
                'Unable to prepare Rotor payment update.'
            );
        }


        mysqli_stmt_bind_param(

            $stmt,

            'dsi',

            $newAmountPaid,

            $newRemarks,

            $rotorID

        );


        if (
            !mysqli_stmt_execute(
                $stmt
            )
        ) {

            rotorUpdateLogError(

                'execute completed Rotor payment update',

                $conn,

                $stmt

            );


            mysqli_stmt_close(
                $stmt
            );


            throw new RuntimeException(
                'Unable to update the Rotor payment.'
            );
        }

        mysqli_stmt_close(
            $stmt
        );

        rotorRecordAdditionalPayment(
            $conn,
            $rotorID,
            $additionalPayment,
            $newBalance,
            $actorID
        );

        $paymentHistory =


            'recorded an additional payment for the completed Rotor rental "'

            .

            $transactionID

            .

            '" for "'

            .

            $customerName

            .

            '": Additional Payment ₱'

            .

            number_format(
                $additionalPayment,
                2
            )

            .

            '; Amount Paid from ₱'

            .

            number_format(
                $currentAmountPaid,
                2
            )

            .

            ' to ₱'

            .

            number_format(
                $newAmountPaid,
                2
            )

            .

            '; Balance ₱'

            .

            number_format(
                $newBalance,
                2
            );


        if (
            $newRemarks !==
            $currentRemarks
        ) {

            $paymentHistory .=

                '; Payment Remarks from "'

                .

                $currentRemarks

                .

                '" to "'

                .

                $newRemarks

                .

                '"';

        }


        $paymentHistory .=
            '.';


        log_activity(

            $conn,

            'Rental',

            'Updated',

            $paymentHistory,

            $rotorID

        );


        mysqli_commit(
            $conn
        );


        rotorUpdateResponse(
            'success'
        );
    }


    /* =====================================================
       CHANGE DETECTION
    ===================================================== */

    $dateChanged =

        $rentalDate !==
        $currentRentalDate;


    $primaryTractorChanged =

        $tractorID !==
        $oldTractorID;


    $primaryDriverChanged =

        $primaryDriverID !==
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


    $primaryChanged =

        $primaryTractorChanged

        ||

        $primaryDriverChanged;


/* =====================================================
   SCHEDULE STATE
===================================================== */

    $isMissed =

        $currentStatus ===
        'Pending'

        &&

        $currentRentalDate <
        $today;


    $isDelayed =

        $currentStatus ===
        'Ongoing'

        &&

        $currentRentalDate <
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
       NEXT VALUES
    ===================================================== */

    $newStatus =
        $currentStatus;


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
       RESCHEDULE
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
           Delayed Ongoing
           becomes Pending again after rescheduling.
        */

        if (
            $isDelayed
        ) {

            $newStatus =
                'Pending';


            $newActualStartedDate =
                null;


            $newCompletedDate =
                null;

        }

    }


    /* =====================================================
       PENDING

       Missed Pending must be moved to today/future.
    ===================================================== */

    if (

        $currentStatus ===
        'Pending'

        &&

        $rentalDate <
        $today

    ) {

        throw new RotorUpdateValidationException(

            'A Pending rental must be rescheduled to today or a future date.'

        );
    }


    /* =====================================================
       ONGOING
    ===================================================== */

    if (
        $currentStatus ===
        'Ongoing'
    ) {

        /* =============================================
           DELAYED ONGOING
        ============================================= */

        if (
            $isDelayed
        ) {

            if (

                $dateChanged

                &&

                $rentalDate <
                $today

            ) {

                throw new RotorUpdateValidationException(

                    'A Delayed rental can only be rescheduled to today or a future date.'

                );
            }


            /*
               Primary may change only when the
               delayed rental is actually rescheduled.
            */

            if (

                !$dateChanged

                &&

                $primaryChanged

            ) {

                throw new RotorUpdateValidationException(

                    'Change the Rental Date first before changing the Primary Assignment of a Delayed rental.'

                );
            }

        }

        /* =============================================
           NORMAL ONGOING
        ============================================= */

        else {

            if (
                $dateChanged
            ) {

                throw new RotorUpdateValidationException(

                    'Rental Date is locked once the Rotor service is Ongoing.'

                );
            }


            if (
                $primaryChanged
            ) {

                throw new RotorUpdateValidationException(

                    'Primary Tractor and Primary Driver are locked once the Rotor service is Ongoing.'

                );
            }

        }

    }


    /* =====================================================
       REGISTRY ASSIGNMENTS

       Existing Maintenance assignment can remain untouched,
       but Maintenance cannot be newly selected.
    ===================================================== */

    $allowPrimaryMaintenance =

        !$dateChanged

        &&

        $tractorID ===
        $oldTractorID;


    $allowSecondaryMaintenance =

        !$dateChanged

        &&

        $hasSecondaryTractor

        &&

        $secondaryTractorID ===
        $oldSecondaryTractorID;


    $primaryTractor =

        rotorUpdateLockTractor(

            $conn,

            $tractorID,

            $allowPrimaryMaintenance

        );


    $primaryDriver =

        rotorUpdateLockDriver(

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

            rotorUpdateLockTractor(

                $conn,

                (int)
                $secondaryTractorID,

                $allowSecondaryMaintenance

            );


        $secondaryDriver =

            rotorUpdateLockDriver(

                $conn,

                (int)
                $secondaryDriverID

            );
    }


    /* =====================================================
       CANONICAL SNAPSHOTS

       Browser names / tractor numbers are ignored.
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
       CROSS-RENTAL CONFLICT CHECK
    ===================================================== */

    $checks = [

        [
            'type' =>
                'tractor',

            'id' =>
                $tractorID,

            'label' =>
                'Primary Tractor'
        ],

        [
            'type' =>
                'driver',

            'id' =>
                $primaryDriverID,

            'label' =>
                'Primary Driver'
        ]

    ];


    if (
        $hasSecondaryTractor
    ) {

        $checks[] = [

            'type' =>
                'tractor',

            'id' =>
                (int)
                $secondaryTractorID,

            'label' =>
                'Secondary Tractor'

        ];


        $checks[] = [

            'type' =>
                'driver',

            'id' =>
                (int)
                $secondaryDriverID,

            'label' =>
                'Secondary Driver'

        ];

    }


    foreach (
        $checks
        as
        $check
    ) {

        $conflict =

            rotorUpdateFindConflict(

                $conn,

                $check['type'],

                (int)
                $check['id'],

                $rentalDate,

                $rotorID

            );


        if (
            $conflict
        ) {

            throw new RotorUpdateValidationException(

                rotorUpdateConflictMessage(

                    $check['label'],

                    $conflict,

                    $rentalDate

                )

            );
        }

    }


    /* =====================================================
       ANY CHANGES?
    ===================================================== */

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

        $newStatus !==
        $currentStatus

        ||

        $paymentChanged

        ||

        $newRemarks !==
        $currentRemarks;


    if (
        !$hasChanges
    ) {

        mysqli_commit(
            $conn
        );


        rotorUpdateResponse(
            'success'
        );
    }


    /* =====================================================
       UPDATE ROTOR

       NOT CHANGED HERE:
       - Customer
       - Affiliate
       - Location
       - Hectare
       - Price
       - Discount
       - Total
       - Payment Remarks
       - Historical/Audit marker
    ===================================================== */

    $stmt =

        mysqli_prepare(

            $conn,

            "
            UPDATE rotor

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

                actual_started_date = ?,

                completed_date = ?,

                reschedule_count = ?,

                last_rescheduled_from = ?,

                last_rescheduled_at = ?,

                amount_paid = ?,

                remarks = ?

                WHERE rotor_id = ?

            LIMIT 1
            "

        );


    if (
        !$stmt
    ) {

        rotorUpdateLogError(

            'prepare Rotor update',

            $conn

        );


        throw new RuntimeException(
            'Unable to prepare Rotor update.'
        );
    }


    mysqli_stmt_bind_param(

        $stmt,

        'siiiisssssssissdsi',

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

        $newActualStartedDate,

        $newCompletedDate,

        $newRescheduleCount,

        $newLastRescheduledFrom,

        $newLastRescheduledAt,

        $newAmountPaid,

        $newRemarks,

        $rotorID

    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        rotorUpdateLogError(

            'execute Rotor update',

            $conn,

            $stmt

        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(
            'Unable to update the Rotor rental.'
        );
    }


    mysqli_stmt_close(
        $stmt
    );

    
    if ($paymentChanged) {
        rotorRecordAdditionalPayment(
            $conn,
            $rotorID,
            $additionalPayment,
            $newBalance,
            $actorID
        );
    }



    /* =====================================================
       PHYSICAL TRACTOR STATUS

       Recalculate both old and new Primary / Secondary.
    ===================================================== */

    $affectedTractors = [

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

        $affectedTractors[] =

            (int)
            $oldSecondaryTractorID;

    }


    if (
        (
            $secondaryTractorID
            ??
            0
        ) > 0
    ) {

        $affectedTractors[] =

            (int)
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
                            (int) $id
                            >
                            0;
                    }

                )

            )

        );


    foreach (
        $affectedTractors
        as
        $affectedTractorID
    ) {

        rotorUpdateSyncTractorStatus(

            $conn,

            (int)
            $affectedTractorID

        );

    }


    /* =====================================================
       HISTORY CHANGES
    ===================================================== */

    $changes = [];


    if (
        $dateChanged
    ) {

        $changes[] =

            $isReschedule

            ?

            'Rescheduled from '
            .
            $currentRentalDate
            .
            ' to '
            .
            $rentalDate

            :

            'Rental Date from '
            .
            $currentRentalDate
            .
            ' to '
            .
            $rentalDate;

    }


    if (
        $newStatus !==
        $currentStatus
    ) {

        $changes[] =

            'Status from "'

            .

            $currentStatus

            .

            '" to "'

            .

            $newStatus

            .

            '"';

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

    if (
        $paymentChanged
    ) {

        $changes[] =

            'Additional Payment ₱'
            .
            number_format(
                $additionalPayment,
                2
            )
            .
            '; Amount Paid from ₱'
            .
            number_format(
                $currentAmountPaid,
                2
            )
            .
            ' to ₱'
            .
            number_format(
                $newAmountPaid,
                2
            )
            .
            '; Balance ₱'
            .
            number_format(
                $newBalance,
                2
            );

    }


    if (
        $newRemarks !==
        $currentRemarks
    ) {

        $changes[] =

            'Payment Remarks from "'
            .
            $currentRemarks
            .
            '" to "'
            .
            $newRemarks
            .
            '"';

    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    $historyMessage =

        'updated the Rotor rental "'

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

        $rotorID

    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    rotorUpdateResponse(
        'success'
    );

}


/* =========================================================
   SAFE VALIDATION ERROR
========================================================= */

catch (
    RotorUpdateValidationException $error
) {

    mysqli_rollback(
        $conn
    );


    rotorUpdateResponse(

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

        '[SPIS][Rotor Update] update failed: '

        .

        $error->getMessage()

    );


    rotorUpdateResponse(

        'Unable to update the Rotor rental. Please try again.',

        500

    );
}


?>