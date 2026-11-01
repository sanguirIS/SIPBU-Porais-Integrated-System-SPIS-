<?php

/* =========================================================
   SPIS - SAVE HAULING RENTAL
   Date-only scheduling + Driver Registry + Secondary support
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

   Business-rule errors may be shown to the user.

   Internal SQL errors are NOT exposed.
========================================================= */

class HaulingValidationException extends Exception {}


/* =========================================================
   RESPONSE
========================================================= */

function haulingSaveResponse(
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

function haulingLogInternalError(
    string $context,
    mysqli $conn,
    ?mysqli_stmt $stmt = null
): void {

    $error =
        $stmt
            ? mysqli_stmt_error($stmt)
            : mysqli_error($conn);


    error_log(
        '[SPIS][Hauling] '
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

function haulingLockDriver(
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


    if (!$stmt) {

        haulingLogInternalError(
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

        haulingLogInternalError(
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
            ? mysqli_fetch_assoc($result)
            : null;


    mysqli_stmt_close(
        $stmt
    );


    if (!$driver) {

        throw new HaulingValidationException(
            'The selected driver is no longer available.'
        );
    }


    if (
        ($driver['status'] ?? '')
        !==
        'Active'
    ) {

        throw new HaulingValidationException(
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

        throw new HaulingValidationException(
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

function haulingLockTractor(
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


    if (!$stmt) {

        haulingLogInternalError(
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

        haulingLogInternalError(
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
            ? mysqli_fetch_assoc($result)
            : null;


    mysqli_stmt_close(
        $stmt
    );


    if (!$tractor) {

        throw new HaulingValidationException(
            'The selected tractor was not found.'
        );
    }


    if (
        ($tractor['status'] ?? '')
        ===
        'Maintenance'
    ) {

        throw new HaulingValidationException(
            'The selected tractor is currently under maintenance.'
        );
    }


    return $tractor;
}


/* =========================================================
   CHECK DATE-ONLY ASSIGNMENT CONFLICT

   Checks ALL Rental modules:

   Hauling
   Rotor
   Reaper

   Resource:
   tractor
   driver

   Active conflicts:
   Pending
   Ongoing

   Done does NOT block.
========================================================= */

function haulingFindAssignmentConflict(
    mysqli $conn,
    string $resourceType,
    int $resourceID,
    string $rentalDate
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
            'table' => 'hauling',
            'id' => 'hauling_id',
            'label' => 'Hauling'
        ],

        [
            'table' => 'rotor',
            'id' => 'rotor_id',
            'label' => 'Rotor'
        ],

        [
            'table' => 'reaper',
            'id' => 'reaper_id',
            'label' => 'Reaper'
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
           Table and column identifiers above are hard-coded.

           No user input is inserted into SQL identifiers.
        */

        $sql = "
            SELECT

                {$idColumn}
                    AS rental_id,

                transaction_id,
                status

            FROM {$table}

            WHERE
                DATE(rental_date) = ?

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


        if (!$stmt) {

            haulingLogInternalError(
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


        if (
            !mysqli_stmt_execute(
                $stmt
            )
        ) {

            haulingLogInternalError(
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
                ? mysqli_fetch_assoc($result)
                : null;


        mysqli_stmt_close(
            $stmt
        );


        if ($row) {

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

function haulingConflictMessage(
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
   POST ONLY
========================================================= */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'POST'
) {

    haulingSaveResponse(
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

    haulingSaveResponse(
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


if (!$actorStmt) {

    haulingLogInternalError(
        'prepare access check',
        $conn
    );


    haulingSaveResponse(
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

    haulingLogInternalError(
        'execute access check',
        $conn,
        $actorStmt
    );


    mysqli_stmt_close(
        $actorStmt
    );


    haulingSaveResponse(
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

    haulingSaveResponse(
        'You do not have permission to add Rental records.',
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

    haulingSaveResponse(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =========================================================
   FORM DATA
========================================================= */

$customer =

    trim(
        (string) (
            $_POST['customer_name']
            ??
            ''
        )
    );


$affiliate =

    trim(
        (string) (
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

    (int) $_POST['member_id']

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
   SERVICE DETAILS
========================================================= */

$location =

    trim(
        (string) (
            $_POST['location']
            ??
            ''
        )
    );


$rentalDate =

    trim(
        (string) (
            $_POST['rental_date']
            ??
            ''
        )
    );


$status =

    trim(
        (string) (
            $_POST['status']
            ??
            ''
        )
    );


$haulingCountRaw =

    trim(
        (string) (
            $_POST['hauling_count']
            ??
            ''
        )
    );


$priceRaw =

    trim(
        (string) (
            $_POST['price']
            ??
            ''
        )
    );


$discountRaw =

    trim(
        (string) (
            $_POST['discounted_price']
            ??
            '0'
        )
    );


/* =========================================================
   PAYMENT REMARKS

   New Rental always begins Unpaid.

   Therefore:
   Pending can never be created as Paid.
========================================================= */

$remarks =
    'Unpaid';


/* =========================================================
   AFFILIATE
========================================================= */

if (
    !in_array(
        $affiliate,
        [
            'Member',
            'Not Member'
        ],
        true
    )
) {

    haulingSaveResponse(
        'Please select a valid affiliate.',
        400
    );
}


/* =========================================================
   CUSTOMER
========================================================= */

if (
    $customer === ''
) {

    haulingSaveResponse(
        'Customer name is required.',
        400
    );
}


if (
    mb_strlen(
        $customer,
        'UTF-8'
    ) > 150
) {

    haulingSaveResponse(
        'Customer name is too long.',
        400
    );
}


/* =========================================================
   LOCATION
========================================================= */

if (
    $location === ''
) {

    haulingSaveResponse(
        'Location is required.',
        400
    );
}


if (
    mb_strlen(
        $location,
        'UTF-8'
    ) > 255
) {

    haulingSaveResponse(
        'Location is too long.',
        400
    );
}


/* =========================================================
   HAULING COUNT
========================================================= */

if (
    $haulingCountRaw === ''
    ||
    !ctype_digit(
        $haulingCountRaw
    )
    ||
    (int) $haulingCountRaw <= 0
) {

    haulingSaveResponse(
        'Please enter a valid number of hauling.',
        400
    );
}


$haulingCount =

    (int) $haulingCountRaw;


if (
    $haulingCount > 1000000
) {

    haulingSaveResponse(
        'Number of hauling is too large.',
        400
    );
}


/* =========================================================
   PRIMARY ASSIGNMENT REQUIRED
========================================================= */

if (
    $tractorID <= 0
) {

    haulingSaveResponse(
        'Please select a Primary Tractor.',
        400
    );
}


if (
    $primaryDriverID <= 0
) {

    haulingSaveResponse(
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

    haulingSaveResponse(
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
    $secondaryTractorID === $tractorID
) {

    haulingSaveResponse(
        'Primary Tractor and Secondary Tractor cannot be the same.',
        400
    );
}


if (
    $hasSecondaryDriver
    &&
    $secondaryDriverID === $primaryDriverID
) {

    haulingSaveResponse(
        'Primary Driver and Secondary Driver cannot be the same.',
        400
    );
}


/* =========================================================
   RENTAL DATE
========================================================= */

if (
    $rentalDate === ''
) {

    haulingSaveResponse(
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

    haulingSaveResponse(
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


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    haulingSaveResponse(
        'Invalid Rental status.',
        400
    );
}


/* =========================================================
   PRICE / DISCOUNT
========================================================= */

if (
    $priceRaw === ''
    ||
    !is_numeric(
        $priceRaw
    )
) {

    haulingSaveResponse(
        'Please enter a valid Rental price.',
        400
    );
}


if (
    $discountRaw !== ''
    &&
    !is_numeric(
        $discountRaw
    )
) {

    haulingSaveResponse(
        'Please enter a valid member discount.',
        400
    );
}


$price =

    round(
        (float) $priceRaw,
        2
    );


$discount =

    $discountRaw === ''

    ?

    0.00

    :

    round(
        (float) $discountRaw,
        2
    );


if (
    $price < 0
    ||
    $price > 999999999.99
) {

    haulingSaveResponse(
        'Invalid Rental price.',
        400
    );
}


if (
    $discount < 0
) {

    $discount =
        0.00;
}


/* =========================================================
   DATABASE DATE

   Database Manila time is the authority.
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


if (
    !$serverQuery
) {

    haulingLogInternalError(
        'read server date',
        $conn
    );


    haulingSaveResponse(
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
        (string) (
            $serverRow['db_today']
            ??
            ''
        )
    );


$currentDateTime =

    trim(
        (string) (
            $serverRow['db_now']
            ??
            ''
        )
    );


if (
    $today === ''
    ||
    $currentDateTime === ''
) {

    haulingSaveResponse(
        'Unable to verify the current date.',
        500
    );
}

/* =========================================================
   HISTORICAL / AUDIT ENTRY

   A record is historical when it is CREATED through
   the Add Rental modal using a Rental Date earlier
   than the current database date.

   This value is stored permanently in the record.
========================================================= */

$isHistoricalEntry =

    $rentalDate < $today

    ?

    1

    :

    0;

/* =========================================================
   HISTORICAL STATUS

   Historical / Audit Entries represent rentals that are
   being recorded after their actual Rental Date.

   They are therefore stored as completed records.
========================================================= */

if (
    $isHistoricalEntry === 1
) {

    $status =
        'Done';

}

/* =========================================================
   DATE-BASED STATUS RULES
========================================================= */


/* FUTURE = PENDING ONLY */

if (
    $rentalDate > $today
    &&
    $status !== 'Pending'
) {

    haulingSaveResponse(
        'A future Rental must remain Pending until its service date.',
        400
    );
}


/* PAST CANNOT BE ONGOING */

if (
    $rentalDate < $today
    &&
    $status === 'Ongoing'
) {

    haulingSaveResponse(
        'A past Rental cannot be created as Ongoing.',
        400
    );
}

/* =========================================================
   HAULING CUSTOMER LOCK
========================================================= */

$haulingCustomerLock = '';


/* =========================================================
   START DATABASE TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {


    /* =====================================================
       MEMBER VALIDATION
    ===================================================== */

    if (
        $affiliate === 'Member'
    ) {


        if (
            $memberID === null
            ||
            $memberID <= 0
        ) {

            throw new HaulingValidationException(
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


        if (!$memberStmt) {

            haulingLogInternalError(
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


        if (
            !mysqli_stmt_execute(
                $memberStmt
            )
        ) {

            haulingLogInternalError(
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


        if (!$member) {

            throw new HaulingValidationException(
                'The selected member is no longer available.'
            );
        }


        /*
           Canonical registered member name.
        */

        $customer =

            trim(
                (string) (
                    $member['full_name']
                    ??
                    ''
                )
            );


        if (
            $customer === ''
        ) {

            throw new RuntimeException(
                'Unable to verify member name.'
            );
        }

    }
    else {


        /*
           Not Member:
           no member ID
           no member discount
        */

        $memberID =
            null;


        $discount =
            0.00;

    }

        /* =====================================================
       UNPAID HAULING CHECK

       Member: member_id
       Not Member: customer name

       Other Rental services remain independent.
    ===================================================== */

    $identityKey = $affiliate === 'Member'
        ? 'member:' . $memberID
        : 'nonmember:' . mb_strtolower(
            trim($customer),
            'UTF-8'
        );

    $haulingCustomerLock = 'spis_hlg_' . substr(
        hash('sha256', $identityKey),
        0,
        48
    );

    $lockResult = mysqli_query(
        $conn,
        "SELECT GET_LOCK('" . $haulingCustomerLock . "', 10) AS acquired"
    );

    if (!$lockResult) {
        throw new RuntimeException(
            'Unable to check existing Hauling rentals.'
        );
    }

    $lockRow = mysqli_fetch_assoc($lockResult);

    if ((int) ($lockRow['acquired'] ?? 0) !== 1) {
        throw new HaulingValidationException(
            'Another Hauling request is in progress for this customer. Please try again.'
        );
    }

    if ($affiliate === 'Member') {

        $unpaidSql = "
            SELECT transaction_id
            FROM hauling
            WHERE affiliate = 'Member'
            AND member_id = ?
            AND remarks = 'Unpaid'
            LIMIT 1
            FOR UPDATE
        ";

        $identityValue = $memberID;
        $identityType = 'i';

    } else {

        $unpaidSql = "
            SELECT transaction_id
            FROM hauling
            WHERE affiliate = 'Not Member'
            AND LOWER(TRIM(customer_name)) = LOWER(TRIM(?))
            AND remarks = 'Unpaid'
            LIMIT 1
            FOR UPDATE
        ";

        $identityValue = $customer;
        $identityType = 's';

    }

    $unpaidStmt = mysqli_prepare(
        $conn,
        $unpaidSql
    );

    if (!$unpaidStmt) {
        throw new RuntimeException(
            'Unable to check existing Hauling rentals.'
        );
    }

    mysqli_stmt_bind_param(
        $unpaidStmt,
        $identityType,
        $identityValue
    );

    if (!mysqli_stmt_execute($unpaidStmt)) {

        mysqli_stmt_close($unpaidStmt);

        throw new RuntimeException(
            'Unable to check existing Hauling rentals.'
        );
    }

    $unpaidResult = mysqli_stmt_get_result(
        $unpaidStmt
    );

    $unpaidRental = $unpaidResult
        ? mysqli_fetch_assoc($unpaidResult)
        : null;

    mysqli_stmt_close($unpaidStmt);

    if ($unpaidRental) {

        throw new HaulingValidationException(
            'This customer still has an Unpaid Hauling rental (' .
            $unpaidRental['transaction_id'] .
            '). Please mark it as Paid before adding another Hauling rental.'
        );

    }


    /* =====================================================
       DISCOUNT LIMIT
    ===================================================== */

    if (
        $discount > $price
    ) {

        $discount =
            $price;

    }


    /* =====================================================
       SERVER-SIDE TOTAL
    ===================================================== */

    $total =

        round(
            $price
            -
            $discount,
            2
        );


    if (
        $total < 0
    ) {

        $total =
            0.00;

    }


    /* =====================================================
       PRIMARY ASSIGNMENT

       Database records are locked here.
    ===================================================== */

    $primaryTractor =

        haulingLockTractor(
            $conn,
            $tractorID
        );


    $primaryDriver =

        haulingLockDriver(
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


    if (
        $hasSecondaryTractor
    ) {

        $secondaryTractor =

            haulingLockTractor(
                $conn,
                (int) $secondaryTractorID
            );


        $secondaryDriver =

            haulingLockDriver(
                $conn,
                (int) $secondaryDriverID
            );

    }


    /* =====================================================
       CANONICAL SNAPSHOTS

       Do NOT trust names/numbers submitted by browser.
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
       PRIMARY TRACTOR DATE CONFLICT
    ===================================================== */

    $conflict =

        haulingFindAssignmentConflict(
            $conn,
            'tractor',
            $tractorID,
            $rentalDate
        );


    if (
        $conflict
    ) {

        throw new HaulingValidationException(

            haulingConflictMessage(
                'Primary Tractor',
                $conflict,
                $rentalDate
            )

        );
    }


    /* =====================================================
       SECONDARY TRACTOR DATE CONFLICT
    ===================================================== */

    if (
        $hasSecondaryTractor
    ) {

        $conflict =

            haulingFindAssignmentConflict(
                $conn,
                'tractor',
                (int) $secondaryTractorID,
                $rentalDate
            );


        if (
            $conflict
        ) {

            throw new HaulingValidationException(

                haulingConflictMessage(
                    'Secondary Tractor',
                    $conflict,
                    $rentalDate
                )

            );
        }

    }


    /* =====================================================
       PRIMARY DRIVER DATE CONFLICT
    ===================================================== */

    $conflict =

        haulingFindAssignmentConflict(
            $conn,
            'driver',
            $primaryDriverID,
            $rentalDate
        );


    if (
        $conflict
    ) {

        throw new HaulingValidationException(

            haulingConflictMessage(
                'Primary Driver',
                $conflict,
                $rentalDate
            )

        );
    }


    /* =====================================================
       SECONDARY DRIVER DATE CONFLICT
    ===================================================== */

    if (
        $hasSecondaryDriver
    ) {

        $conflict =

            haulingFindAssignmentConflict(
                $conn,
                'driver',
                (int) $secondaryDriverID,
                $rentalDate
            );


        if (
            $conflict
        ) {

            throw new HaulingValidationException(

                haulingConflictMessage(
                    'Secondary Driver',
                    $conflict,
                    $rentalDate
                )

            );
        }

    }


    /* =====================================================
       CREATE TRANSACTION ID

       Preserve existing format:

       HLG-YYYYMMDD-0001
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

            FROM hauling

            ORDER BY
                hauling_id DESC

            LIMIT 1

            FOR UPDATE
            "
        );


    if (!$lastStmt) {

        haulingLogInternalError(
            'prepare transaction id lookup',
            $conn
        );


        throw new RuntimeException(
            'Unable to generate transaction ID.'
        );
    }


    if (
        !mysqli_stmt_execute(
            $lastStmt
        )
    ) {

        haulingLogInternalError(
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


    if (
        $lastResult
        &&
        mysqli_num_rows(
            $lastResult
        ) > 0
    ) {


        $last =

            mysqli_fetch_assoc(
                $lastResult
            );


        $parts =

            explode(
                '-',
                (string) (
                    $last['transaction_id']
                    ??
                    ''
                )
            );


        if (
            isset(
                $parts[2]
            )
            &&
            is_numeric(
                $parts[2]
            )
        ) {

            $running =

                (int) $parts[2]
                +
                1;

        }

    }


    mysqli_stmt_close(
        $lastStmt
    );


    $transactionID =

        'HLG-'

        .

        $transactionDate

        .

        '-'

        .

        str_pad(
            (string) $running,
            4,
            '0',
            STR_PAD_LEFT
        );


    /* =====================================================
       ACTUAL START / COMPLETION
    ===================================================== */

    $actualStartedAt =
        null;


    $actualStartedDate =
        null;


    $completedDate =
        null;


    /*
       Ongoing is only allowed today.

       Starting Ongoing means the service starts now.
    */

    if (
        $status === 'Ongoing'
    ) {

        $actualStartedAt =
            $currentDateTime;


        $actualStartedDate =
            $today;

    }
    elseif (
        $status === 'Done'
    ) {

        /*
           Add modal does not contain separate actual-start
           and completion-date inputs.

           A directly-created Done record is therefore
           treated as having been completed on its Rental
           date.
        */

        $actualStartedDate =
            $rentalDate;


        $completedDate =
            $rentalDate;

    }


    /* =====================================================
       SAVE HAULING

       NO scheduled_time.
       NO manual Operator input.

       operator / tractor_no are canonical snapshots.
    ===================================================== */

    $saveStmt =

        mysqli_prepare(
            $conn,
            "
            INSERT INTO hauling
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
                hauling_count,

                price,
                discounted_price,
                total_price,

                status,
                remarks,

                tractor_no,
                secondary_tractor_no,

                rental_date,
                is_historical_entry,

                actual_started_at,
                actual_started_date,
                completed_date
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
            "
        );


    if (!$saveStmt) {

        haulingLogInternalError(
            'prepare hauling insert',
            $conn
        );


        throw new RuntimeException(
            'Unable to prepare Hauling transaction.'
        );
    }


    mysqli_stmt_bind_param(
        $saveStmt,

        'siiiiisssssidddsssssisss',

        $transactionID,

        $memberID,

        $tractorID,
        $primaryDriverID,

        $secondaryTractorID,
        $secondaryDriverID,

        $customer,
        $affiliate,

        $operator,
        $secondaryOperator,

        $location,
        $haulingCount,

        $price,
        $discount,
        $total,

        $status,
        $remarks,

        $tractorNo,
        $secondaryTractorNo,

        $rentalDate,
        $isHistoricalEntry,

        $actualStartedAt,
        $actualStartedDate,
        $completedDate
    );


    if (
        !mysqli_stmt_execute(
            $saveStmt
        )
    ) {

        /*
           Detailed SQL error is written only to PHP log.
           It is NOT returned to the browser.
        */

        haulingLogInternalError(
            'execute hauling insert',
            $conn,
            $saveStmt
        );


        mysqli_stmt_close(
            $saveStmt
        );


        throw new RuntimeException(
            'Unable to save Hauling transaction.'
        );
    }


    $newHaulingID =

        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $saveStmt
    );


    /* =====================================================
       CURRENT TRACTOR STATUS

       Only today's Rental affects current physical status.

       Pending = Reserved
       Ongoing = In Use

       Future Pending does NOT change today's status.
    ===================================================== */

    if (
        $rentalDate === $today
        &&
        in_array(
            $status,
            [
                'Pending',
                'Ongoing'
            ],
            true
        )
    ) {


        $physicalStatus =

            $status === 'Ongoing'

            ?

            'In Use'

            :

            'Reserved';


        $assignedTractorIDs = [

            $tractorID

        ];


        if (
            $hasSecondaryTractor
        ) {

            $assignedTractorIDs[] =
                (int) $secondaryTractorID;

        }


        foreach (
            $assignedTractorIDs
            as
            $assignedTractorID
        ) {


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


            if (!$statusStmt) {

                haulingLogInternalError(
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


            if (
                !mysqli_stmt_execute(
                    $statusStmt
                )
            ) {

                haulingLogInternalError(
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
       HISTORY MESSAGE
    ===================================================== */

    $historyMessage =

        'added the Hauling rental "'

        .

        $transactionID

        .

        '" for "'

        .

        $customer

        .

        '" using Primary Tractor "'

        .

        $tractorNo

        .

        ' - '

        .

        trim(
            (string) (
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


    if (
        $hasSecondaryTractor
    ) {

        $historyMessage .=

            ', supported by Secondary Tractor "'

            .

            $secondaryTractorNo

            .

            ' - '

            .

            trim(
                (string) (
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

        '.';


    /* =====================================================
       SAVE HISTORY
    ===================================================== */

    log_activity(
        $conn,
        'Rental',
        'Added',
        $historyMessage,
        $newHaulingID
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );

    if ($haulingCustomerLock !== '') {
        mysqli_query(
            $conn,
            "SELECT RELEASE_LOCK('" . $haulingCustomerLock . "')"
        );
    }

    haulingSaveResponse(
        'success'
    );

}


/* =========================================================
   USER-SAFE BUSINESS ERROR
========================================================= */

catch (
    HaulingValidationException $error
) {


    mysqli_rollback(
        $conn
    );

    if ($haulingCustomerLock !== '') {
        mysqli_query(
            $conn,
            "SELECT RELEASE_LOCK('" . $haulingCustomerLock . "')"
        );
    }

    haulingSaveResponse(
        $error->getMessage(),
        400
    );

}


/* =========================================================
   INTERNAL ERROR

   Raw SQL/PHP details go only to PHP error log.
========================================================= */

catch (
    Throwable $error
) {


    mysqli_rollback(
        $conn
    );

    if ($haulingCustomerLock !== '') {
        mysqli_query(
            $conn,
            "SELECT RELEASE_LOCK('" . $haulingCustomerLock . "')"
        );
    }

    error_log(
        '[SPIS][Hauling] save failed: '
        .
        $error->getMessage()
    );


    haulingSaveResponse(
        'Unable to save the Hauling rental. Please try again.',
        500
    );

}

?>