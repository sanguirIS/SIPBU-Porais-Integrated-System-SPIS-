<?php

/* =========================================================
   SPIS - SAVE REAPER RENTAL
   Date-only scheduling + Driver Registry + Secondary support
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', '0');

header('Content-Type: text/plain; charset=UTF-8');


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

class ReaperValidationException extends Exception {}


/* =========================================================
   RESPONSE
========================================================= */

function reaperSaveResponse(
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
   INTERNAL ERROR LOGGING
========================================================= */

function reaperLogInternalError(
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
        '[SPIS][Reaper] '
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

function reaperLockDriver(
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


    if(
        !$stmt
    ){

        reaperLogInternalError(
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


    if(
        !mysqli_stmt_execute(
            $stmt
        )
    ){

        reaperLogInternalError(
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


    if(
        !$driver
    ){

        throw new ReaperValidationException(
            'The selected driver is no longer available.'
        );

    }


    if(
        (
            $driver['status']
            ??
            ''
        )
        !==
        'Active'
    ){

        throw new ReaperValidationException(
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

function reaperLockTractor(
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


    if(
        !$stmt
    ){

        reaperLogInternalError(
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


    if(
        !mysqli_stmt_execute(
            $stmt
        )
    ){

        reaperLogInternalError(
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


    if(
        !$tractor
    ){

        throw new ReaperValidationException(
            'The selected tractor was not found.'
        );

    }


    if(
        (
            $tractor['status']
            ??
            ''
        )
        ===
        'Maintenance'
    ){

        throw new ReaperValidationException(
            'The selected tractor is currently under maintenance.'
        );

    }


    return $tractor;

}


/* =========================================================
   CHECK DATE-ONLY ASSIGNMENT CONFLICT

   Checks:
   - Hauling
   - Rotor
   - Reaper

   Resource:
   - Tractor
   - Driver

   Pending / Ongoing = Reserved
   Done = Released
========================================================= */

function reaperFindAssignmentConflict(
    mysqli $conn,
    string $resourceType,
    int $resourceID,
    string $rentalDate
): ?array {


    if(
        $resourceType
        ===
        'tractor'
    ){

        $primaryColumn =
        'tractor_id';

        $secondaryColumn =
        'secondary_tractor_id';

    }
    elseif(
        $resourceType
        ===
        'driver'
    ){

        $primaryColumn =
        'primary_driver_id';

        $secondaryColumn =
        'secondary_driver_id';

    }
    else{

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


    foreach(
        $tables
        as
        $info
    ){

        $table =
        $info['table'];


        $idColumn =
        $info['id'];


        /*
        Table and column names above are hard-coded.
        User input never becomes an SQL identifier.
        */

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


        if(
            !$stmt
        ){

            reaperLogInternalError(
                'prepare availability lookup',
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


        if(
            !mysqli_stmt_execute(
                $stmt
            )
        ){

            reaperLogInternalError(
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


        if(
            $row
        ){

            return [

                'rental_type' =>
                $info['label'],

                'rental_id' =>
                (int)(
                    $row['rental_id']
                    ??
                    0
                ),

                'transaction_id' =>
                trim(
                    (string)(
                        $row['transaction_id']
                        ??
                        ''
                    )
                ),

                'status' =>
                trim(
                    (string)(
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

function reaperConflictMessage(
    string $assignmentLabel,
    array $conflict,
    string $rentalDate
): string {

    $transactionID =
    trim(
        (string)(
            $conflict['transaction_id']
            ??
            ''
        )
    );


    $rentalType =
    trim(
        (string)(
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
   RELEASE REAPER CUSTOMER LOCK
========================================================= */

function reaperReleaseUnpaidCustomerLock(
    mysqli $conn,
    ?string $lockName
): void {

    if($lockName === null){
        return;
    }

    try{

        $stmt = mysqli_prepare(
            $conn,
            "SELECT RELEASE_LOCK(?)"
        );

        if(!$stmt){
            error_log('[SPIS][Reaper] Unable to prepare customer lock release.');
            return;
        }

        mysqli_stmt_bind_param(
            $stmt,
            's',
            $lockName
        );

        if(!mysqli_stmt_execute($stmt)){
            error_log('[SPIS][Reaper] Unable to release customer lock.');
        }

        mysqli_stmt_close($stmt);

    }
    catch(Throwable $error){

        error_log(
            '[SPIS][Reaper] Customer lock release failed: '
            . $error->getMessage()
        );

    }

}

/* =========================================================
   POST ONLY
========================================================= */

if(
    (
        $_SERVER['REQUEST_METHOD']
        ??
        ''
    )
    !==
    'POST'
){

    reaperSaveResponse(
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


if(
    $actorID
    <=
    0
){

    reaperSaveResponse(
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


if(
    !$actorStmt
){

    reaperLogInternalError(
        'prepare access check',
        $conn
    );


    reaperSaveResponse(
        'Unable to verify Rental access.',
        500
    );

}


mysqli_stmt_bind_param(
    $actorStmt,
    'i',
    $actorID
);


if(
    !mysqli_stmt_execute(
        $actorStmt
    )
){

    reaperLogInternalError(
        'execute access check',
        $conn,
        $actorStmt
    );


    mysqli_stmt_close(
        $actorStmt
    );


    reaperSaveResponse(
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
    )
    ===
    1;


mysqli_stmt_close(
    $actorStmt
);


if(
    !$hasRentalAccess
){

    reaperSaveResponse(
        'You do not have permission to add Rental records.',
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

    reaperSaveResponse(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =========================================================
   FORM DATA
========================================================= */

$customerName =
trim(
    (string)(
        $_POST['customer_name']
        ??
        ''
    )
);


$affiliate =
trim(
    (string)(
        $_POST['affiliate']
        ??
        ''
    )
);


$memberID =

    !empty(
        $_POST['member_id']
    )

    ?

    (int)
    $_POST['member_id']

    :

    null;


/* =========================================================
   PRIMARY ASSIGNMENT
========================================================= */

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
   SERVICE DETAILS
========================================================= */

$location =
trim(
    (string)(
        $_POST['location']
        ??
        ''
    )
);


$rentalDate =
trim(
    (string)(
        $_POST['rental_date']
        ??
        ''
    )
);


$status =
trim(
    (string)(
        $_POST['status']
        ??
        ''
    )
);


$remarks =
trim(
    (string)(
        $_POST['remarks']
        ??
        ''
    )
);


$commodityRaw =
trim(
    (string)(
        $_POST['commodity']
        ??
        ''
    )
);


$harvestRaw =
trim(
    (string)(
        $_POST['no_of_harvest']
        ??
        ''
    )
);


/* =========================================================
   AFFILIATE
========================================================= */

if(
    !in_array(
        $affiliate,
        [
            'Member',
            'Not Member'
        ],
        true
    )
){

    reaperSaveResponse(
        'Please select a valid affiliate.',
        400
    );

}


/* =========================================================
   CUSTOMER
========================================================= */

if(
    $customerName
    ===
    ''
){

    reaperSaveResponse(
        'Customer name is required.',
        400
    );

}


if(
    mb_strlen(
        $customerName,
        'UTF-8'
    )
    >
    150
){

    reaperSaveResponse(
        'Customer name is too long.',
        400
    );

}


/* =========================================================
   LOCATION
========================================================= */

if(
    $location
    ===
    ''
){

    reaperSaveResponse(
        'Service location is required.',
        400
    );

}


if(
    mb_strlen(
        $location,
        'UTF-8'
    )
    >
    255
){

    reaperSaveResponse(
        'Service location is too long.',
        400
    );

}


/* =========================================================
   PRIMARY ASSIGNMENT REQUIRED
========================================================= */

if(
    $tractorID
    <=
    0
){

    reaperSaveResponse(
        'Please select a Primary Tractor.',
        400
    );

}


if(
    $primaryDriverID
    <=
    0
){

    reaperSaveResponse(
        'Please select a Primary Driver.',
        400
    );

}


/* =========================================================
   SECONDARY PAIR
========================================================= */

$hasSecondaryTractor =

    $secondaryTractorID
    !==
    null

    &&

    $secondaryTractorID
    >
    0;


$hasSecondaryDriver =

    $secondaryDriverID
    !==
    null

    &&

    $secondaryDriverID
    >
    0;


if(
    $hasSecondaryTractor
    !==
    $hasSecondaryDriver
){

    reaperSaveResponse(
        'Secondary Tractor and Secondary Driver must be selected together.',
        400
    );

}


/* =========================================================
   PRIMARY != SECONDARY
========================================================= */

if(
    $hasSecondaryTractor
    &&
    $secondaryTractorID
    ===
    $tractorID
){

    reaperSaveResponse(
        'Primary Tractor and Secondary Tractor cannot be the same.',
        400
    );

}


if(
    $hasSecondaryDriver
    &&
    $secondaryDriverID
    ===
    $primaryDriverID
){

    reaperSaveResponse(
        'Primary Driver and Secondary Driver cannot be the same.',
        400
    );

}


/* =========================================================
   RENTAL DATE
========================================================= */

if(
    $rentalDate
    ===
    ''
){

    reaperSaveResponse(
        'Please select the Rental date.',
        400
    );

}


$dateObject =
DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $rentalDate
);


$dateErrors =
DateTimeImmutable::getLastErrors();


$dateValid =

    $dateObject
    !==
    false

    &&

    (
        $dateErrors
        ===
        false

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


if(
    !$dateValid
){

    reaperSaveResponse(
        'Invalid Rental date.',
        400
    );

}


/* =========================================================
   STATUS
========================================================= */

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

    reaperSaveResponse(
        'Invalid Rental status.',
        400
    );

}


/* =========================================================
   PAYMENT REMARKS
========================================================= */

if(
    !in_array(
        $remarks,
        [
            'Unpaid',
            'Paid'
        ],
        true
    )
){

    reaperSaveResponse(
        'Invalid payment status.',
        400
    );

}


/* =========================================================
   HARVEST
========================================================= */

$noOfHarvest =
null;


if(
    $harvestRaw
    !==
    ''
){

    if(
        !ctype_digit(
            $harvestRaw
        )
        ||
        (int)
        $harvestRaw
        <
        1
    ){

        reaperSaveResponse(
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


if(
    $commodityRaw
    !==
    ''
){

    if(
        !ctype_digit(
            $commodityRaw
        )
        ||
        (int)
        $commodityRaw
        <
        1
    ){

        reaperSaveResponse(
            'Commodity must be a whole number of at least 1.',
            400
        );

    }


    $commodity =
    (int)
    $commodityRaw;

}


/* =========================================================
   DONE REQUIRES COMPLETION DATA
========================================================= */

if(
    $status
    ===
    'Done'

    &&

    (
        $noOfHarvest
        ===
        null

        ||

        $commodity
        ===
        null
    )
){

    reaperSaveResponse(
        'No. of Harvest and Commodity are required before marking this rental as Done.',
        400
    );

}


/* =========================================================
   PAYMENT RULES

   Pending / Ongoing = Unpaid only
   Done              = Unpaid or Paid
========================================================= */

if(
    $remarks
    ===
    'Paid'

    &&

    (
        $status
        !==
        'Done'

        ||

        $noOfHarvest
        ===
        null

        ||

        $commodity
        ===
        null
    )
){

    reaperSaveResponse(
        'The rental must be Done with No. of Harvest and Commodity completed before marking it as Paid.',
        400
    );

}


/*
Even if somebody manually changes the hidden browser field,
Pending / Ongoing are always Unpaid.
*/

if(
    in_array(
        $status,
        [
            'Pending',
            'Ongoing'
        ],
        true
    )
){

    $remarks =
    'Unpaid';

}


/* =========================================================
   DATABASE DATE

   Database Manila time is authoritative.
========================================================= */

$serverQuery =
mysqli_query(
    $conn,
    "
    SELECT

        DATE_FORMAT(
            CURDATE(),
            '%Y-%m-%d'
        ) AS db_today,

        DATE_FORMAT(
            NOW(),
            '%Y-%m-%d %H:%i:%s'
        ) AS db_now
    "
);


if(
    !$serverQuery
){

    reaperLogInternalError(
        'read server date',
        $conn
    );


    reaperSaveResponse(
        'Unable to verify the current date.',
        500
    );

}


$serverRow =
mysqli_fetch_assoc(
    $serverQuery
);


$today =
trim(
    (string)(
        $serverRow['db_today']
        ??
        ''
    )
);


$currentDateTime =
trim(
    (string)(
        $serverRow['db_now']
        ??
        ''
    )
);


if(
    $today
    ===
    ''

    ||

    $currentDateTime
    ===
    ''
){

    reaperSaveResponse(
        'Unable to verify the current date.',
        500
    );

}


/* =========================================================
   DATE-BASED REAPER RULES
========================================================= */


/* FUTURE = PENDING ONLY */

if(
    $rentalDate
    >
    $today

    &&

    $status
    !==
    'Pending'
){

    reaperSaveResponse(
        'A future Reaper rental must remain Pending until its service date.',
        400
    );

}


/* FUTURE = NO SERVICE DATA */

if(
    $rentalDate
    >
    $today

    &&

    (
        $noOfHarvest
        !==
        null

        ||

        $commodity
        !==
        null
    )
){

    reaperSaveResponse(
        'No. of Harvest and Commodity cannot be entered before the Reaper service date.',
        400
    );

}


/* =========================================================
   HISTORICAL / AUDIT ENTRY

   Past dates are permanent historical back-entries.

   Rules:
   - automatically Done
   - Harvest required
   - Commodity required
   - does not affect today's physical Tractor status
========================================================= */

$isHistoricalEntry =
(
    $rentalDate
    <
    $today
)
?
1
:
0;


if(
    $isHistoricalEntry ===
    1
){

    $status =
    'Done';


    if(
        $noOfHarvest ===
        null
        ||
        $commodity ===
        null
    ){

        reaperSaveResponse(
            'Historical Reaper entries require both No. of Harvest and Commodity.',
            400
        );

    }

}


/* =========================================================
   START DATABASE TRANSACTION
========================================================= */

$reaperUnpaidCustomerLock = null;

mysqli_begin_transaction(
    $conn
);


try{


    /* =====================================================
       MEMBER VALIDATION
    ===================================================== */

    if(
        $affiliate
        ===
        'Member'
    ){

        if(
            $memberID
            ===
            null

            ||

            $memberID
            <=
            0
        ){

            throw new ReaperValidationException(
                'Please select a valid registered member.'
            );

        }


        $memberStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                member_id,
                full_name

            FROM members

            WHERE member_id = ?

            AND archived = 0

            AND UPPER(
                TRIM(
                    COALESCE(
                        status,
                        ''
                    )
                )
            ) = 'ACTIVE'

            LIMIT 1

            FOR UPDATE
            "
        );


        if(
            !$memberStmt
        ){

            reaperLogInternalError(
                'prepare member lookup',
                $conn
            );


            throw new RuntimeException(
                'Unable to validate member.'
            );

        }


        mysqli_stmt_bind_param(
            $memberStmt,
            'i',
            $memberID
        );


        if(
            !mysqli_stmt_execute(
                $memberStmt
            )
        ){

            reaperLogInternalError(
                'execute member lookup',
                $conn,
                $memberStmt
            );


            mysqli_stmt_close(
                $memberStmt
            );


            throw new RuntimeException(
                'Unable to validate member.'
            );

        }


        $memberResult =
        mysqli_stmt_get_result(
            $memberStmt
        );


        $member =

            $memberResult

            ?

            mysqli_fetch_assoc(
                $memberResult
            )

            :

            null;


        mysqli_stmt_close(
            $memberStmt
        );


        if(
            !$member
        ){

            throw new ReaperValidationException(
                'The selected member is no longer available.'
            );

        }


        /*
        Always use official registered Member name.
        */

        $customerName =
        trim(
            (string)(
                $member['full_name']
                ??
                ''
            )
        );


        if(
            $customerName
            ===
            ''
        ){

            throw new RuntimeException(
                'Unable to verify member name.'
            );

        }

    }
    else{

        /*
        Not Member must never keep a Member ID.
        */

        $memberID =
        null;

    }

    /* =====================================================
       NO DUPLICATE UNPAID REAPER RENTAL

       Member: member_id
       Not Member: normalized customer name
    ===================================================== */

    $normalizedReaperName =
    mb_strtolower(
        trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $customerName
            )
        ),
        'UTF-8'
    );

    $customerIdentity =

        $affiliate === 'Member'

        ?

        'M:' . (int)$memberID

        :

        'N:' . $normalizedReaperName;


    $lockName =
    'spis_rpr_'
    .
    substr(
        hash('sha256', $customerIdentity),
        0,
        48
    );


    $lockStmt =
    mysqli_prepare(
        $conn,
        "SELECT GET_LOCK(?, 10) AS acquired"
    );

    if(!$lockStmt){
        throw new RuntimeException(
            'Unable to verify previous Reaper rentals.'
        );
    }

    mysqli_stmt_bind_param(
        $lockStmt,
        's',
        $lockName
    );

    if(!mysqli_stmt_execute($lockStmt)){

        mysqli_stmt_close($lockStmt);

        throw new RuntimeException(
            'Unable to verify previous Reaper rentals.'
        );
    }

    $lockResult =
    mysqli_stmt_get_result($lockStmt);

    $lockRow =
    $lockResult
    ?
    mysqli_fetch_assoc($lockResult)
    :
    null;

    mysqli_stmt_close($lockStmt);

    if((int)($lockRow['acquired'] ?? 0) !== 1){

        throw new ReaperValidationException(
            'Another Reaper rental for this customer is currently being processed. Please try again.'
        );
    }

    $reaperUnpaidCustomerLock = $lockName;


    if($affiliate === 'Member'){

        $duplicateSql = "
            SELECT transaction_id
            FROM reaper
            WHERE member_id = ?
            AND remarks = 'Unpaid'
            LIMIT 1
            FOR UPDATE
        ";

    }
    else{

        $duplicateSql = "
            SELECT transaction_id
            FROM reaper
            WHERE affiliate = 'Not Member'
            AND remarks = 'Unpaid'
            AND LOWER(
                TRIM(
                    REGEXP_REPLACE(
                        customer_name,
                        '[[:space:]]+',
                        ' '
                    )
                )
            ) = ?
            LIMIT 1
            FOR UPDATE
        ";

    }


    $duplicateStmt =
    mysqli_prepare(
        $conn,
        $duplicateSql
    );

    if(!$duplicateStmt){

        throw new RuntimeException(
            'Unable to verify previous Reaper rentals.'
        );
    }

    if($affiliate === 'Member'){

        mysqli_stmt_bind_param(
            $duplicateStmt,
            'i',
            $memberID
        );

    }
    else{

        mysqli_stmt_bind_param(
            $duplicateStmt,
            's',
            $normalizedReaperName
        );

    }


    if(!mysqli_stmt_execute($duplicateStmt)){

        mysqli_stmt_close($duplicateStmt);

        throw new RuntimeException(
            'Unable to verify previous Reaper rentals.'
        );
    }


    $duplicateResult =
    mysqli_stmt_get_result($duplicateStmt);

    $duplicateRow =
    $duplicateResult
    ?
    mysqli_fetch_assoc($duplicateResult)
    :
    null;

    mysqli_stmt_close($duplicateStmt);


    if($duplicateRow){

        throw new ReaperValidationException(
            'This customer already has an Unpaid Reaper rental ('
            . (string)($duplicateRow['transaction_id'] ?? '')
            . '). Please settle it before adding another.'
        );
    }    


    /* =====================================================
       PRIMARY ASSIGNMENT
    ===================================================== */

    $primaryTractor =
    reaperLockTractor(
        $conn,
        $tractorID
    );


    $primaryDriver =
    reaperLockDriver(
        $conn,
        $primaryDriverID
    );


    /* =====================================================
       SECONDARY ASSIGNMENT
    ===================================================== */

    $secondaryTractor =
    null;


    $secondaryDriver =
    null;


    if(
        $hasSecondaryTractor
    ){

        $secondaryTractor =
        reaperLockTractor(
            $conn,
            (int)
            $secondaryTractorID
        );


        $secondaryDriver =
        reaperLockDriver(
            $conn,
            (int)
            $secondaryDriverID
        );

    }


    /* =====================================================
       CANONICAL SNAPSHOTS

       Browser does NOT decide these:
       - tractor number
       - operator name
    ===================================================== */

    $tractorNo =
    trim(
        (string)(
            $primaryTractor['tractor_no']
            ??
            ''
        )
    );


    $operator =
    trim(
        (string)(
            $primaryDriver['full_name']
            ??
            ''
        )
    );


    $secondaryTractorNo =

        $secondaryTractor

        ?

        trim(
            (string)(
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
            (string)(
                $secondaryDriver['full_name']
                ??
                ''
            )
        )

        :

        null;


    if(
        $tractorNo
        ===
        ''

        ||

        $operator
        ===
        ''
    ){

        throw new RuntimeException(
            'Unable to verify Primary Assignment.'
        );

    }


    if(
        $hasSecondaryTractor

        &&

        (
            $secondaryTractorNo
            ===
            ''

            ||

            $secondaryOperator
            ===
            ''
        )
    ){

        throw new RuntimeException(
            'Unable to verify Secondary Assignment.'
        );

    }


    /* =====================================================
       PRIMARY TRACTOR CONFLICT
    ===================================================== */

    $conflict =
    reaperFindAssignmentConflict(
        $conn,
        'tractor',
        $tractorID,
        $rentalDate
    );


    if(
        $conflict
    ){

        throw new ReaperValidationException(

            reaperConflictMessage(
                'Primary Tractor',
                $conflict,
                $rentalDate
            )

        );

    }


    /* =====================================================
       SECONDARY TRACTOR CONFLICT
    ===================================================== */

    if(
        $hasSecondaryTractor
    ){

        $conflict =
        reaperFindAssignmentConflict(
            $conn,
            'tractor',
            (int)
            $secondaryTractorID,
            $rentalDate
        );


        if(
            $conflict
        ){

            throw new ReaperValidationException(

                reaperConflictMessage(
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
    reaperFindAssignmentConflict(
        $conn,
        'driver',
        $primaryDriverID,
        $rentalDate
    );


    if(
        $conflict
    ){

        throw new ReaperValidationException(

            reaperConflictMessage(
                'Primary Driver',
                $conflict,
                $rentalDate
            )

        );

    }


    /* =====================================================
       SECONDARY DRIVER CONFLICT
    ===================================================== */

    if(
        $hasSecondaryDriver
    ){

        $conflict =
        reaperFindAssignmentConflict(
            $conn,
            'driver',
            (int)
            $secondaryDriverID,
            $rentalDate
        );


        if(
            $conflict
        ){

            throw new ReaperValidationException(

                reaperConflictMessage(
                    'Secondary Driver',
                    $conflict,
                    $rentalDate
                )

            );

        }

    }


    /* =====================================================
       CREATE TRANSACTION ID

       RPR-YYYYMMDD-0001
    ===================================================== */

    $transactionDate =
    str_replace(
        '-',
        '',
        $today
    );


    $lastStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            transaction_id

        FROM reaper

        ORDER BY
            reaper_id DESC

        LIMIT 1

        FOR UPDATE
        "
    );


    if(
        !$lastStmt
    ){

        reaperLogInternalError(
            'prepare transaction id lookup',
            $conn
        );


        throw new RuntimeException(
            'Unable to generate transaction ID.'
        );

    }


    if(
        !mysqli_stmt_execute(
            $lastStmt
        )
    ){

        reaperLogInternalError(
            'execute transaction id lookup',
            $conn,
            $lastStmt
        );


        mysqli_stmt_close(
            $lastStmt
        );


        throw new RuntimeException(
            'Unable to generate transaction ID.'
        );

    }


    $lastResult =
    mysqli_stmt_get_result(
        $lastStmt
    );


    $running =
    1;


    if(
        $lastResult

        &&

        mysqli_num_rows(
            $lastResult
        )
        >
        0
    ){

        $last =
        mysqli_fetch_assoc(
            $lastResult
        );


        if(
            preg_match(
                '/RPR-\d{8}-(\d+)/',
                (string)(
                    $last['transaction_id']
                    ??
                    ''
                ),
                $match
            )
        ){

            $running =
            (int)
            $match[1]
            +
            1;

        }

    }


    mysqli_stmt_close(
        $lastStmt
    );


    $transactionID =

        'RPR-'

        .

        $transactionDate

        .

        '-'

        .

        str_pad(
            (string)
            $running,
            4,
            '0',
            STR_PAD_LEFT
        );


    /* =====================================================
       LIFECYCLE DATES
    ===================================================== */

    $actualStartedDate =
    null;


    $completedDate =
    null;


    /*
    Ongoing may only be created for today.
    */

    if(
        $status
        ===
        'Ongoing'
    ){

        $actualStartedDate =
        $today;

    }
    elseif(
        $status
        ===
        'Done'
    ){

        /*
        A directly-created Done record is treated as having
        started and completed on its Rental Date.
        */

        $actualStartedDate =
        $rentalDate;


        $completedDate =
        $rentalDate;

    }


    /* =====================================================
       SAVE REAPER

       NO manual Operator.
       operator / tractor_no are canonical snapshots.
    ===================================================== */

    $saveStmt =
    mysqli_prepare(
        $conn,
        "
        INSERT INTO reaper
        (
            transaction_id,

            member_id,

            tractor_id,
            primary_driver_id,

            secondary_tractor_id,
            secondary_driver_id,

            customer_name,
            affiliate,

            operator,
            secondary_operator,

            location,
            rental_date,
            is_historical_entry,

            tractor_no,
            secondary_tractor_no,

            commodity,
            no_of_harvest,

            status,
            remarks,

            actual_started_date,
            completed_date,

            created_at
        )
        VALUES
        (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?,
            NOW()
        )
        "
    );


    if(
        !$saveStmt
    ){

        reaperLogInternalError(
            'prepare reaper insert',
            $conn
        );


        throw new RuntimeException(
            'Unable to prepare Reaper transaction.'
        );

    }


    mysqli_stmt_bind_param(
        $saveStmt,

        'siiiiissssssissdissss',

        $transactionID,

        $memberID,

        $tractorID,
        $primaryDriverID,

        $secondaryTractorID,
        $secondaryDriverID,

        $customerName,
        $affiliate,

        $operator,
        $secondaryOperator,

        $location,
        $rentalDate,
        $isHistoricalEntry,

        $tractorNo,
        $secondaryTractorNo,

        $commodity,
        $noOfHarvest,

        $status,
        $remarks,

        $actualStartedDate,
        $completedDate
    );


    if(
        !mysqli_stmt_execute(
            $saveStmt
        )
    ){

        reaperLogInternalError(
            'execute reaper insert',
            $conn,
            $saveStmt
        );


        mysqli_stmt_close(
            $saveStmt
        );


        throw new RuntimeException(
            'Unable to save Reaper transaction.'
        );

    }


    $newReaperID =
    mysqli_insert_id(
        $conn
    );


    mysqli_stmt_close(
        $saveStmt
    );


    /* =====================================================
    CURRENT TRACTOR STATUS

    Only TODAY affects current physical status:

    Pending / Ongoing = In Use

    Future / Past = no physical status change.
    ===================================================== */

    if(
        $rentalDate
        ===
        $today

        &&

        in_array(
            $status,
            [
                'Pending',
                'Ongoing'
            ],
            true
        )
    ){

        $physicalStatus = 'In Use';


        $assignedTractorIDs = [

            $tractorID

        ];


        if(
            $hasSecondaryTractor
        ){

            $assignedTractorIDs[] =
            (int)
            $secondaryTractorID;

        }


        foreach(
            $assignedTractorIDs
            as
            $assignedTractorID
        ){

            $statusStmt =
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


            if(
                !$statusStmt
            ){

                reaperLogInternalError(
                    'prepare tractor status sync',
                    $conn
                );


                throw new RuntimeException(
                    'Unable to synchronize tractor status.'
                );

            }


            mysqli_stmt_bind_param(
                $statusStmt,
                'si',
                $physicalStatus,
                $assignedTractorID
            );


            if(
                !mysqli_stmt_execute(
                    $statusStmt
                )
            ){

                reaperLogInternalError(
                    'execute tractor status sync',
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


            mysqli_stmt_close(
                $statusStmt
            );

        }

    }

    /* =====================================================
    TODAY + DONE = RELEASE TRACTORS

    A Done record no longer reserves the tractor.
    Release each assigned tractor only when no
    other TODAY Pending / Ongoing Rental uses it.
    ===================================================== */

    if(
        $rentalDate
        ===
        $today

        &&

        $status
        ===
        'Done'
    ){

        $assignedTractorIDs = [

            $tractorID

        ];


        if(
            $hasSecondaryTractor
        ){

            $assignedTractorIDs[] =
            (int)
            $secondaryTractorID;

        }


        $assignedTractorIDs =

            array_values(

                array_unique(

                    array_filter(

                        $assignedTractorIDs,

                        static function(
                            $id
                        ){

                            return
                                (int)
                                $id
                                >
                                0;

                        }

                    )

                )

            );


        foreach(
            $assignedTractorIDs
            as
            $assignedTractorID
        ){

            $hasOtherActiveRental =
            false;


            foreach(
                [
                    'hauling',
                    'rotor',
                    'reaper'
                ]
                as
                $activeTable
            ){

                $activeStmt =

                mysqli_prepare(
                    $conn,
                    "
                    SELECT
                        1

                    FROM {$activeTable}

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

                    LIMIT 1

                    FOR UPDATE
                    "
                );


                if(
                    !$activeStmt
                ){

                    reaperLogInternalError(
                        'prepare active tractor lookup',
                        $conn
                    );


                    throw new RuntimeException(
                        'Unable to synchronize tractor availability.'
                    );

                }


                mysqli_stmt_bind_param(
                    $activeStmt,
                    'ii',
                    $assignedTractorID,
                    $assignedTractorID
                );


                if(
                    !mysqli_stmt_execute(
                        $activeStmt
                    )
                ){

                    reaperLogInternalError(
                        'execute active tractor lookup',
                        $conn,
                        $activeStmt
                    );


                    mysqli_stmt_close(
                        $activeStmt
                    );


                    throw new RuntimeException(
                        'Unable to synchronize tractor availability.'
                    );

                }


                mysqli_stmt_store_result(
                    $activeStmt
                );


                if(
                    mysqli_stmt_num_rows(
                        $activeStmt
                    )
                    >
                    0
                ){

                    $hasOtherActiveRental =
                    true;

                }


                mysqli_stmt_close(
                    $activeStmt
                );


                if(
                    $hasOtherActiveRental
                ){

                    break;

                }

            }


            if(
                !$hasOtherActiveRental
            ){

                $availableStmt =

                mysqli_prepare(
                    $conn,
                    "
                    UPDATE tractors

                    SET status = 'Available'

                    WHERE tractor_id = ?

                    AND archived = 0

                    AND status != 'Maintenance'
                    "
                );


                if(
                    !$availableStmt
                ){

                    reaperLogInternalError(
                        'prepare tractor release',
                        $conn
                    );


                    throw new RuntimeException(
                        'Unable to synchronize tractor availability.'
                    );

                }


                mysqli_stmt_bind_param(
                    $availableStmt,
                    'i',
                    $assignedTractorID
                );


                if(
                    !mysqli_stmt_execute(
                        $availableStmt
                    )
                ){

                    reaperLogInternalError(
                        'execute tractor release',
                        $conn,
                        $availableStmt
                    );


                    mysqli_stmt_close(
                        $availableStmt
                    );


                    throw new RuntimeException(
                        'Unable to synchronize tractor availability.'
                    );

                }


                mysqli_stmt_close(
                    $availableStmt
                );

            }

        }

    }


    /* =====================================================
       HISTORY MESSAGE
    ===================================================== */

    $historyMessage =

        'added the Reaper rental "'

        .

        $transactionID

        .

        '" for "'

        .

        $customerName

        .

        '" using Primary Tractor "'

        .

        $tractorNo

        .

        ' - '

        .

        trim(
            (string)(
                $primaryTractor['tractor_name']
                ??
                ''
            )
        )

        .

        '" with Primary Driver "'

        .

        $operator

        .

        '"';


    if(
        $hasSecondaryTractor
    ){

        $historyMessage .=

            ', supported by Secondary Tractor "'

            .

            $secondaryTractorNo

            .

            ' - '

            .

            trim(
                (string)(
                    $secondaryTractor['tractor_name']
                    ??
                    ''
                )
            )

            .

            '" with Secondary Driver "'

            .

            $secondaryOperator

            .

            '"';

    }


    $historyMessage .=

        ' scheduled for '

        .

        $rentalDate

        .

        ' with status "'

        .

        $status

        .

        '" and payment "'

        .

        $remarks

        .

        '".';


    log_activity(
        $conn,
        'Rental',
        'Added',
        $historyMessage,
        $newReaperID
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );

    reaperReleaseUnpaidCustomerLock(
        $conn,
        $reaperUnpaidCustomerLock
    );

    reaperSaveResponse(
        'success'
    );

}


/* =========================================================
   USER-SAFE BUSINESS ERROR
========================================================= */

catch(
    ReaperValidationException $error
){

    mysqli_rollback(
        $conn
    );

    reaperReleaseUnpaidCustomerLock(
        $conn,
        $reaperUnpaidCustomerLock
    );

    reaperSaveResponse(
        $error->getMessage(),
        400
    );

}


/* =========================================================
   INTERNAL ERROR

   Raw SQL/PHP errors only go to PHP error log.
========================================================= */

catch(
    Throwable $error
){

    mysqli_rollback(
        $conn
    );

    reaperReleaseUnpaidCustomerLock(
        $conn,
        $reaperUnpaidCustomerLock
    );

    error_log(
        '[SPIS][Reaper] save failed: '
        .
        $error->getMessage()
    );

    reaperSaveResponse(
        'Unable to save the Reaper rental. Please try again.',
        500
    );

}

?>