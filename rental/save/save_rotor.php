<?php

/* =========================================================
   SPIS - SAVE ROTOR RENTAL
   Hauling-style scheduling + Rotor hectare pricing
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', '0');

header(
    'Content-Type: text/plain; charset=UTF-8'
);

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
   VALIDATION EXCEPTION
========================================================= */

class RotorValidationException extends Exception
{
}


/* =========================================================
   RESPONSE
========================================================= */

function rotorSaveResponse(
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

function rotorLogInternalError(
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

        '[SPIS][Rotor] '

        .

        $context

        .

        ': '

        .

        $error

    );
}


/* =========================================================
   LOCK ELIGIBLE DRIVER

   Driver must:
   - be Active
   - not archived
   - remain linked to an Active Member
========================================================= */

function rotorLockDriver(
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

            d.contact_no,
            d.status

        FROM drivers d

        INNER JOIN members m

            ON m.member_id =
                d.member_id

            AND m.archived = 0

        WHERE
            d.driver_id = ?

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

        rotorLogInternalError(
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

        rotorLogInternalError(
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

        throw new RotorValidationException(
            'The selected driver is no longer available.'
        );
    }


    $driverName =

    trim(
        (string) (
            $driver[
                'full_name'
            ]
            ??
            ''
        )
    );


    if (
        $driverName === ''
    ) {

        throw new RuntimeException(
            'Unable to verify driver name.'
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

function rotorLockTractor(
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

        rotorLogInternalError(
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

        rotorLogInternalError(
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

        throw new RotorValidationException(
            'The selected tractor was not found.'
        );
    }


    if (
        (
            $tractor[
                'status'
            ]
            ??
            ''
        )
        ===
        'Maintenance'
    ) {

        throw new RotorValidationException(
            'The selected tractor is currently under maintenance.'
        );
    }


    return $tractor;
}


/* =========================================================
   CROSS-MODULE ASSIGNMENT CONFLICT

   Pending / Ongoing
   = resource occupied

   Done
   = resource released

   Checks:
   - Hauling
   - Rotor
   - Reaper

   Both Primary and Secondary are checked.
========================================================= */

function rotorFindAssignmentConflict(
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
        $info[
            'table'
        ];


        $idColumn =
        $info[
            'id'
        ];


        /*
           Identifiers here are hard-coded.

           No browser input is inserted
           into SQL table / column names.
        */

        $sql = "

            SELECT

                {$idColumn}
                    AS rental_id,

                transaction_id,
                status

            FROM {$table}

            WHERE
                DATE(
                    rental_date
                ) = ?

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

            rotorLogInternalError(
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

            rotorLogInternalError(
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

                $info[
                    'label'
                ],


                'rental_id' =>

                (int) (
                    $row[
                        'rental_id'
                    ]
                    ??
                    0
                ),


                'transaction_id' =>

                trim(
                    (string) (
                        $row[
                            'transaction_id'
                        ]
                        ??
                        ''
                    )
                ),


                'status' =>

                trim(
                    (string) (
                        $row[
                            'status'
                        ]
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
   CONFLICT MESSAGE
========================================================= */

function rotorConflictMessage(
    string $assignmentLabel,
    array $conflict,
    string $rentalDate
): string {


    $transactionID =

    trim(
        (string) (
            $conflict[
                'transaction_id'
            ]
            ??
            ''
        )
    );


    $rentalType =

    trim(
        (string) (
            $conflict[
                'rental_type'
            ]
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
   ACTIVE TRACTOR CHECK FOR TODAY

   Used when a Done Rental releases a Tractor.
========================================================= */

function rotorTractorHasActiveBookingToday(
    mysqli $conn,
    int $tractorID
): bool {


    $tables = [

        [

            'table' =>
            'hauling',

            'id' =>
            'hauling_id'

        ],

        [

            'table' =>
            'rotor',

            'id' =>
            'rotor_id'

        ],

        [

            'table' =>
            'reaper',

            'id' =>
            'reaper_id'

        ]

    ];


    foreach (
        $tables
        as
        $info
    ) {


        $table =
        $info[
            'table'
        ];


        $idColumn =
        $info[
            'id'
        ];


        $sql = "

            SELECT
                {$idColumn}

            FROM {$table}

            WHERE
                DATE(
                    rental_date
                ) = CURDATE()

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

        ";


        $stmt =

        mysqli_prepare(
            $conn,
            $sql
        );


        if (
            !$stmt
        ) {

            rotorLogInternalError(
                'prepare current tractor use lookup',
                $conn
            );


            throw new RuntimeException(
                'Unable to check current tractor use.'
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

            rotorLogInternalError(
                'execute current tractor use lookup',
                $conn,
                $stmt
            );


            mysqli_stmt_close(
                $stmt
            );


            throw new RuntimeException(
                'Unable to check current tractor use.'
            );
        }


        $result =

        mysqli_stmt_get_result(
            $stmt
        );


        $hasBooking =

        $result

        &&

        mysqli_num_rows(
            $result
        ) > 0;


        mysqli_stmt_close(
            $stmt
        );


        if (
            $hasBooking
        ) {

            return true;
        }

    }


    return false;
}


/* =========================================================
   RELEASE TRACTOR
========================================================= */

function rotorReleaseTractorIfUnusedToday(
    mysqli $conn,
    int $tractorID
): void {


    if (
        rotorTractorHasActiveBookingToday(
            $conn,
            $tractorID
        )
    ) {

        return;
    }


    $stmt =

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


    if (
        !$stmt
    ) {

        rotorLogInternalError(
            'prepare tractor release',
            $conn
        );


        throw new RuntimeException(
            'Unable to synchronize tractor availability.'
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

        rotorLogInternalError(
            'execute tractor release',
            $conn,
            $stmt
        );


        mysqli_stmt_close(
            $stmt
        );


        throw new RuntimeException(
            'Unable to synchronize tractor availability.'
        );
    }


    mysqli_stmt_close(
        $stmt
    );
}


/* =========================================================
   POST ONLY
========================================================= */

if (
    (
        $_SERVER[
            'REQUEST_METHOD'
        ]
        ??
        ''
    )
    !==
    'POST'
) {

    rotorSaveResponse(
        'Invalid request.',
        405
    );
}


/* =========================================================
   SESSION
========================================================= */

$actorID =

isset(
    $_SESSION[
        'user_id'
    ]
)

?

(int)
$_SESSION[
    'user_id'
]

:

0;


if (
    $actorID <= 0
) {

    rotorSaveResponse(
        'Your session has expired. Please sign in again.',
        401
    );
}


/* =========================================================
   RENTAL ACCESS
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

    rotorLogInternalError(
        'prepare access check',
        $conn
    );


    rotorSaveResponse(
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

    rotorLogInternalError(
        'execute access check',
        $conn,
        $actorStmt
    );


    mysqli_stmt_close(
        $actorStmt
    );


    rotorSaveResponse(
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


if (
    !$hasRentalAccess
) {

    rotorSaveResponse(
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

    rotorSaveResponse(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =========================================================
   FORM DATA
========================================================= */

$customerName =

trim(
    (string) (
        $_POST[
            'customer_name'
        ]
        ??
        ''
    )
);


$affiliate =

trim(
    (string) (
        $_POST[
            'affiliate'
        ]
        ??
        ''
    )
);


$memberID =

!empty(
    $_POST[
        'member_id'
    ]
)

?

(int)
$_POST[
    'member_id'
]

:

null;


/* =========================================================
   PRIMARY ASSIGNMENT
========================================================= */

$tractorID =

isset(
    $_POST[
        'tractor_id'
    ]
)

?

(int)
$_POST[
    'tractor_id'
]

:

0;


$primaryDriverID =

isset(
    $_POST[
        'primary_driver_id'
    ]
)

?

(int)
$_POST[
    'primary_driver_id'
]

:

0;


/* =========================================================
   SECONDARY ASSIGNMENT
========================================================= */

$secondaryTractorID =

!empty(
    $_POST[
        'secondary_tractor_id'
    ]
)

?

(int)
$_POST[
    'secondary_tractor_id'
]

:

null;


$secondaryDriverID =

!empty(
    $_POST[
        'secondary_driver_id'
    ]
)

?

(int)
$_POST[
    'secondary_driver_id'
]

:

null;


/* =========================================================
   ROTOR DETAILS
========================================================= */

$location =

trim(
    (string) (
        $_POST[
            'location'
        ]
        ??
        ''
    )
);


$rentalDate =

trim(
    (string) (
        $_POST[
            'rental_date'
        ]
        ??
        ''
    )
);


$status =

trim(
    (string) (
        $_POST[
            'status'
        ]
        ??
        ''
    )
);


$hectareRaw =

trim(
    (string) (
        $_POST[
            'area'
        ]
        ??
        ''
    )
);


$discountRaw =

trim(
    (string) (
        $_POST[
            'discount'
        ]
        ??
        '0'
    )
);


/* =========================================================
   OPTIONAL DOWNPAYMENT
========================================================= */

$downpaymentRaw =

trim(
    (string) (
        $_POST[
            'downpayment'
        ]
        ??
        '0'
    )
);


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

    rotorSaveResponse(
        'Please select a valid affiliate.',
        400
    );
}


/* =========================================================
   CUSTOMER
========================================================= */

if (
    $customerName === ''
) {

    rotorSaveResponse(
        'Customer name is required.',
        400
    );
}


if (
    mb_strlen(
        $customerName,
        'UTF-8'
    )
    >
    100
) {

    rotorSaveResponse(
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

    rotorSaveResponse(
        'Location is required.',
        400
    );
}


if (
    mb_strlen(
        $location,
        'UTF-8'
    )
    >
    150
) {

    rotorSaveResponse(
        'Location is too long.',
        400
    );
}


/* =========================================================
   HECTARE
========================================================= */

if (
    $hectareRaw === ''
    ||
    !is_numeric(
        $hectareRaw
    )
) {

    rotorSaveResponse(
        'Please enter a valid area in hectares.',
        400
    );
}


$hectare =

round(
    (float)
    $hectareRaw,
    2
);


if (
    $hectare <= 0
    ||
    $hectare > 99999999.99
) {

    rotorSaveResponse(
        'Please enter a valid area in hectares.',
        400
    );
}


/* =========================================================
   PRIMARY REQUIRED
========================================================= */

if (
    $tractorID <= 0
) {

    rotorSaveResponse(
        'Please select a Primary Tractor.',
        400
    );
}


if (
    $primaryDriverID <= 0
) {

    rotorSaveResponse(
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


if (
    $hasSecondaryTractor
    !==
    $hasSecondaryDriver
) {

    rotorSaveResponse(
        'Secondary Tractor and Secondary Driver must be selected together.',
        400
    );
}


/* =========================================================
   NO DUPLICATE PRIMARY / SECONDARY
========================================================= */

if (
    $hasSecondaryTractor
    &&
    $secondaryTractorID
    ===
    $tractorID
) {

    rotorSaveResponse(
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

    rotorSaveResponse(
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

    rotorSaveResponse(
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
            $dateErrors[
                'warning_count'
            ]
            ??
            0
        )
        ===
        0

        &&

        (
            $dateErrors[
                'error_count'
            ]
            ??
            0
        )
        ===
        0
    )
)

&&

$dateObject
->format(
    'Y-m-d'
)
===
$rentalDate;


if (
    !$dateValid
) {

    rotorSaveResponse(
        'Invalid Rental date.',
        400
    );
}

/* =========================================================
   DISCOUNT
========================================================= */

if (
    $discountRaw !== ''
    &&
    !is_numeric(
        $discountRaw
    )
) {

    rotorSaveResponse(
        'Please enter a valid member discount.',
        400
    );
}


$discount =

$discountRaw === ''

?

0.00

:

round(
    (float)
    $discountRaw,
    2
);


if (
    $discount < 0
) {

    rotorSaveResponse(
        'Member discount cannot be negative.',
        400
    );
}


if (
    $discount > 99999999.99
) {

    rotorSaveResponse(
        'Member discount is too large.',
        400
    );
}


/* =========================================================
   DOWNPAYMENT
========================================================= */

if (
    $downpaymentRaw !== ''
    &&
    !preg_match(
        '/^\d+(?:\.\d{1,2})?$/',
        $downpaymentRaw
    )
) {

    rotorSaveResponse(
        'Please enter a valid downpayment amount.',
        400
    );
}


$amountPaid =

$downpaymentRaw === ''

?

0.00

:

round(
    (float)
    $downpaymentRaw,
    2
);


if (
    $amountPaid < 0
) {

    rotorSaveResponse(
        'Downpayment cannot be negative.',
        400
    );
}


if (
    $amountPaid > 99999999.99
) {

    rotorSaveResponse(
        'Downpayment amount is too large.',
        400
    );
}


/* =========================================================
   DATABASE DATE
========================================================= */

$serverQuery =

mysqli_query(
    $conn,
    "
    SELECT

        DATE_FORMAT(
            CURDATE(),
            '%Y-%m-%d'
        )
            AS db_today
    "
);


if (
    !$serverQuery
) {

    rotorLogInternalError(
        'read server date',
        $conn
    );


    rotorSaveResponse(
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
        $serverRow[
            'db_today'
        ]
        ??
        ''
    )
);


if (
    $today === ''
) {

    rotorSaveResponse(
        'Unable to verify the current date.',
        500
    );
}


/* =========================================================
   DATE / HISTORICAL RULES

   FUTURE
   = Pending automatically

   TODAY
   = Pending / Ongoing / Done

   PAST
   = Historical / Audit Entry
   = Done automatically

   IMPORTANT:
   Server decides the final status.
   Browser-submitted status cannot override these rules.
========================================================= */

$isHistoricalEntry =
0;


/* =========================================================
   PAST DATE
   HISTORICAL / AUDIT ENTRY
========================================================= */

if (
    $rentalDate
    <
    $today
) {

    $isHistoricalEntry =
    1;


    $status =
    'Done';

}


/* =========================================================
   FUTURE DATE
========================================================= */

elseif (
    $rentalDate
    >
    $today
) {

    $status =
    'Pending';

}


/* =========================================================
   TODAY
========================================================= */

else {

    $isHistoricalEntry =
    0;


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

        rotorSaveResponse(
            'Please select a valid Rental status.',
            400
        );
    }

}


/* =========================================================
   TRANSACTION
========================================================= */

$rotorCustomerLock = '';
$rotorCustomerLockAcquired = false;

mysqli_begin_transaction(
    $conn
);


try {


    /* =====================================================
       MEMBER VALIDATION
    ===================================================== */

    if (
        $affiliate
        ===
        'Member'
    ) {


        if (
            $memberID === null
            ||
            $memberID <= 0
        ) {

            throw new RotorValidationException(
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

            AND status = 'Active'

            LIMIT 1

            FOR UPDATE
            "
        );


        if (
            !$memberStmt
        ) {

            rotorLogInternalError(
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

            rotorLogInternalError(
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


        if (
            !$member
        ) {

            throw new RotorValidationException(
                'The selected member is inactive or no longer available.'
            );
        }


        $customerName =

        trim(
            (string) (
                $member[
                    'full_name'
                ]
                ??
                ''
            )
        );


        if (
            $customerName === ''
        ) {

            throw new RuntimeException(
                'Unable to verify member name.'
            );
        }


        if (
            mb_strlen(
                $customerName,
                'UTF-8'
            )
            >
            100
        ) {

            throw new RuntimeException(
                'Member name is too long for the Rotor record.'
            );
        }

    }

    else {


        /*
           Not Member:
           - no Member ID
           - no Member discount
        */

        $memberID =
        null;


        $discount =
        0.00;

    }

        /* =====================================================
       NO DUPLICATE UNPAID ROTOR RENTAL

       Any Unpaid Rotor rental blocks a new Rotor rental,
       regardless of rental status.

       Other Rental services remain independent.
    ===================================================== */

    $rotorIdentityKey = $affiliate === 'Member'
        ? 'member:' . $memberID
        : 'nonmember:' . mb_strtolower(
            trim($customerName),
            'UTF-8'
        );

    $rotorCustomerLock = 'spis_rtr_' . substr(
        hash('sha256', $rotorIdentityKey),
        0,
        48
    );

    $lockResult = mysqli_query(
        $conn,
        "SELECT GET_LOCK('" . $rotorCustomerLock . "', 10) AS acquired"
    );

    if (!$lockResult) {
        throw new RuntimeException(
            'Unable to check existing Rotor rentals.'
        );
    }

    $lockRow = mysqli_fetch_assoc($lockResult);
    mysqli_free_result($lockResult);

    if ((int)($lockRow['acquired'] ?? 0) !== 1) {
        throw new RotorValidationException(
            'Another Rotor request is in progress for this customer. Please try again.'
        );
    }

    $rotorCustomerLockAcquired = true;

    if ($affiliate === 'Member') {

        $unpaidSql = "
            SELECT transaction_id
            FROM rotor
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
            FROM rotor
            WHERE affiliate = 'Not Member'
              AND LOWER(TRIM(customer_name)) = LOWER(TRIM(?))
              AND remarks = 'Unpaid'
            LIMIT 1
            FOR UPDATE
        ";

        $identityValue = $customerName;
        $identityType = 's';
    }

    $unpaidStmt = mysqli_prepare(
        $conn,
        $unpaidSql
    );

    if (!$unpaidStmt) {
        throw new RuntimeException(
            'Unable to check existing Rotor rentals.'
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
            'Unable to check existing Rotor rentals.'
        );
    }

    $unpaidResult = mysqli_stmt_get_result($unpaidStmt);

    if ($unpaidResult === false) {

        mysqli_stmt_close($unpaidStmt);

        throw new RuntimeException(
            'Unable to check existing Rotor rentals.'
        );
    }

    $unpaidRental = mysqli_fetch_assoc($unpaidResult);

    mysqli_stmt_close($unpaidStmt);

    if ($unpaidRental) {

        throw new RotorValidationException(
            'This customer still has an Unpaid Rotor rental (' .
            $unpaidRental['transaction_id'] .
            '). Please mark it as Paid before adding another Rotor rental.'
        );
    }


    /* =====================================================
       CURRENT ROTOR PRICE

       Database price is authoritative.

       Browser price / total are NOT trusted.
    ===================================================== */

    $priceStmt =

    mysqli_prepare(
        $conn,
        "
        SELECT

            price_per_hectare

        FROM rotor_price

        ORDER BY
            id DESC

        LIMIT 1

        FOR UPDATE
        "
    );


    if (
        !$priceStmt
    ) {

        rotorLogInternalError(
            'prepare Rotor price lookup',
            $conn
        );


        throw new RuntimeException(
            'Unable to verify Rotor price.'
        );
    }


    if (
        !mysqli_stmt_execute(
            $priceStmt
        )
    ) {

        rotorLogInternalError(
            'execute Rotor price lookup',
            $conn,
            $priceStmt
        );


        mysqli_stmt_close(
            $priceStmt
        );


        throw new RuntimeException(
            'Unable to verify Rotor price.'
        );
    }


    $priceResult =

    mysqli_stmt_get_result(
        $priceStmt
    );


    $priceRow =

    $priceResult

    ?

    mysqli_fetch_assoc(
        $priceResult
    )

    :

    null;


    mysqli_stmt_close(
        $priceStmt
    );


    if (
        !$priceRow
    ) {

        throw new RotorValidationException(
            'Please set the Rotor price per hectare first.'
        );
    }


    $pricePerHectare =

    round(
        (float) (
            $priceRow[
                'price_per_hectare'
            ]
            ??
            0
        ),
        2
    );


    if (
        $pricePerHectare <= 0
        ||
        $pricePerHectare > 99999999.99
    ) {

        throw new RotorValidationException(
            'Please set a valid Rotor price per hectare first.'
        );
    }


    /* =====================================================
       ROTOR COMPUTATION

       Subtotal =
       Hectare × Price per Hectare

       Total =
       Subtotal - Discount
    ===================================================== */

    $subtotal =

    round(
        $hectare
        *
        $pricePerHectare,
        2
    );


    if (
        $subtotal < 0
        ||
        $subtotal > 99999999.99
    ) {

        throw new RotorValidationException(
            'The computed Rotor rental amount is too large.'
        );
    }


    if (
        $discount > $subtotal
    ) {

        $discount =
        $subtotal;
    }


    $totalPrice =

    round(
        $subtotal
        -
        $discount,
        2
    );


if (
    $totalPrice < 0
) {

    $totalPrice =
    0.00;
}


/* =====================================================
   INITIAL PAYMENT / BALANCE
===================================================== */

if (
    $amountPaid
    >
    $totalPrice
) {

    throw new RotorValidationException(
        'Downpayment cannot exceed the Total Rental amount.'
    );
}


$balance =

round(
    max(
        0,
        $totalPrice
        -
        $amountPaid
    ),
    2
);


$remarks =

$balance <= 0.009

?

'Paid'

:

'Unpaid';


/* =====================================================
   PRIMARY ASSIGNMENT
===================================================== */

    $primaryTractor =

    rotorLockTractor(
        $conn,
        $tractorID
    );


    $primaryDriver =

    rotorLockDriver(
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

        rotorLockTractor(
            $conn,
            (int)
            $secondaryTractorID
        );


        $secondaryDriver =

        rotorLockDriver(
            $conn,
            (int)
            $secondaryDriverID
        );
    }


    /* =====================================================
       CANONICAL SNAPSHOTS
    ===================================================== */

    $tractorNo =

    trim(
        (string) (
            $primaryTractor[
                'tractor_no'
            ]
            ??
            ''
        )
    );


    $operator =

    trim(
        (string) (
            $primaryDriver[
                'full_name'
            ]
            ??
            ''
        )
    );


    $secondaryTractorNo =

    $secondaryTractor

    ?

    trim(
        (string) (
            $secondaryTractor[
                'tractor_no'
            ]
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
            $secondaryDriver[
                'full_name'
            ]
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
        mb_strlen(
            $operator,
            'UTF-8'
        )
        >
        100
    ) {

        throw new RuntimeException(
            'Primary Driver name is too long for the Rotor record.'
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


    if (
        $secondaryOperator !== null
        &&
        mb_strlen(
            $secondaryOperator,
            'UTF-8'
        )
        >
        150
    ) {

        throw new RuntimeException(
            'Secondary Driver name is too long for the Rotor record.'
        );
    }


    /* =====================================================
       PRIMARY TRACTOR CONFLICT
    ===================================================== */

    $conflict =

    rotorFindAssignmentConflict(
        $conn,
        'tractor',
        $tractorID,
        $rentalDate
    );


    if (
        $conflict
    ) {

        throw new RotorValidationException(

            rotorConflictMessage(
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

        rotorFindAssignmentConflict(
            $conn,
            'tractor',
            (int)
            $secondaryTractorID,
            $rentalDate
        );


        if (
            $conflict
        ) {

            throw new RotorValidationException(

                rotorConflictMessage(
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

    rotorFindAssignmentConflict(
        $conn,
        'driver',
        $primaryDriverID,
        $rentalDate
    );


    if (
        $conflict
    ) {

        throw new RotorValidationException(

            rotorConflictMessage(
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

        rotorFindAssignmentConflict(
            $conn,
            'driver',
            (int)
            $secondaryDriverID,
            $rentalDate
        );


        if (
            $conflict
        ) {

            throw new RotorValidationException(

                rotorConflictMessage(
                    'Secondary Driver',
                    $conflict,
                    $rentalDate
                )

            );
        }
    }


    /* =====================================================
       TRANSACTION ID

       RTR-YYYYMMDD-0001
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

        FROM rotor

        ORDER BY
            rotor_id DESC

        LIMIT 1

        FOR UPDATE
        "
    );


    if (
        !$lastStmt
    ) {

        rotorLogInternalError(
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

        rotorLogInternalError(
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
        )
        >
        0
    ) {

        $last =

        mysqli_fetch_assoc(
            $lastResult
        );


        $parts =

        explode(
            '-',
            (string) (
                $last[
                    'transaction_id'
                ]
                ??
                ''
            )
        );


        if (
            isset(
                $parts[
                    2
                ]
            )
            &&
            is_numeric(
                $parts[
                    2
                ]
            )
        ) {

            $running =

            (int)
            $parts[
                2
            ]

            +

            1;
        }
    }


    mysqli_stmt_close(
        $lastStmt
    );


    $transactionID =

    'RTR-'

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


    if (
        $status
        ===
        'Ongoing'
    ) {

        $actualStartedDate =
        $today;
    }

    elseif (
        $status
        ===
        'Done'
    ) {

        $actualStartedDate =
        $rentalDate;


        $completedDate =
        $rentalDate;
    }


    /* =====================================================
       INSERT ROTOR

       price
       = Price per Hectare snapshot

       discounted_price
       = Discount amount

       total_price
       = Hectare × Price per Hectare - Discount
    ===================================================== */

    $saveStmt =

    mysqli_prepare(
        $conn,
        "
        INSERT INTO rotor
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

            hectare,
            price,
            discounted_price,
            total_price,
            amount_paid,

            status,
            remarks,

            tractor_no,
            secondary_tractor_no,

            rental_date,
            is_historical_entry,

            actual_started_date,
            completed_date,

            created_at
        )

        VALUES
        (
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?,
            ?, ?, ?, ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            NOW()
        )
        "
    );


    if (
        !$saveStmt
    ) {

        rotorLogInternalError(
            'prepare Rotor insert',
            $conn
        );


        throw new RuntimeException(
            'Unable to prepare Rotor transaction.'
        );
    }


    mysqli_stmt_bind_param(
        $saveStmt,

        'siiiiisssssdddddsssssiss',

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

        $hectare,
        $pricePerHectare,
        $discount,
        $totalPrice,
        $amountPaid,

        $status,
        $remarks,

        $tractorNo,
        $secondaryTractorNo,

        $rentalDate,
        $isHistoricalEntry,

        $actualStartedDate,
        $completedDate
    );


    if (
        !mysqli_stmt_execute(
            $saveStmt
        )
    ) {

        rotorLogInternalError(
            'execute Rotor insert',
            $conn,
            $saveStmt
        );


        mysqli_stmt_close(
            $saveStmt
        );


        throw new RuntimeException(
            'Unable to save Rotor transaction.'
        );
    }


    $newRotorID =

    mysqli_insert_id(
        $conn
    );


    mysqli_stmt_close(
        $saveStmt
    );

    
/* =====================================================
   RECORD INITIAL ROTOR PAYMENT
===================================================== */

if ($amountPaid > 0) {

    $paymentType =
        $balance <= 0.009
        ? 'Full Payment'
        : 'Downpayment';

    $paymentStmt = mysqli_prepare(
        $conn,
        "
        INSERT INTO rotor_payments
        (
            rotor_id,
            payment_type,
            amount,
            balance_after,
            payment_date,
            recorded_by_id
        )
        VALUES
        (
            ?, ?, ?, ?, NOW(), ?
        )
        "
    );

    if (!$paymentStmt) {

        throw new RuntimeException(
            'Unable to prepare initial Rotor payment.'
        );
    }

    mysqli_stmt_bind_param(
        $paymentStmt,
        'isddi',
        $newRotorID,
        $paymentType,
        $amountPaid,
        $balance,
        $actorID
    );

    if (!mysqli_stmt_execute($paymentStmt)) {

        rotorLogInternalError(
            'execute initial Rotor payment',
            $conn,
            $paymentStmt
        );

        mysqli_stmt_close(
            $paymentStmt
        );

        throw new RuntimeException(
            'Unable to record initial Rotor payment.'
        );
    }

    mysqli_stmt_close(
        $paymentStmt
    );
}



    /* =====================================================
       PHYSICAL TRACTOR STATUS

       Same as Hauling:

       TODAY + Pending
       = Reserved

       TODAY + Ongoing
       = In Use

       TODAY + Done
       = release if unused

       Future / Past
       = no physical status change
    ===================================================== */

    $assignedTractorIDs = [

        $tractorID

    ];


    if (
        $hasSecondaryTractor
    ) {

        $assignedTractorIDs[] =

        (int)
        $secondaryTractorID;
    }


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

        $status
        ===
        'Ongoing'

        ?

        'In Use'

        :

        'Reserved';


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


            if (
                !$statusStmt
            ) {

                rotorLogInternalError(
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

                rotorLogInternalError(
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


    elseif (
        $rentalDate === $today
        &&
        $status === 'Done'
    ) {

        foreach (
            $assignedTractorIDs
            as
            $assignedTractorID
        ) {

            rotorReleaseTractorIfUnusedToday(
                $conn,
                $assignedTractorID
            );
        }
    }


    /* =====================================================
   HISTORY
===================================================== */

$historyMessage =

'added the Rotor rental "'

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
    (string) (
        $primaryTractor[
            'tractor_name'
        ]
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
            $secondaryTractor[
                'tractor_name'
            ]
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

', Area '

.

number_format(
    $hectare,
    2,
    '.',
    ''
)

.

' hectare(s) at ₱'

.

number_format(
    $pricePerHectare,
    2,
    '.',
    ','
)

.

' per hectare, Subtotal ₱'

.

number_format(
    $subtotal,
    2,
    '.',
    ','
)

.

', Discount ₱'

.

number_format(
    $discount,
    2,
    '.',
    ','
)

.

', Total ₱'

.

number_format(
    $totalPrice,
    2,
    '.',
    ','
)

.

', Amount Paid ₱'

.

number_format(
    $amountPaid,
    2,
    '.',
    ','
)

.

', Balance ₱'

.

number_format(
    $balance,
    2,
    '.',
    ','
)

.

', Payment "'

.

$remarks

.

'", Status "'

.

$status

.

'", scheduled for '

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
    $newRotorID
);


/* =====================================================
   COMMIT
===================================================== */

mysqli_commit(
    $conn
);

if ($rotorCustomerLockAcquired) {

    mysqli_query(
        $conn,
        "DO RELEASE_LOCK('" . $rotorCustomerLock . "')"
    );

    $rotorCustomerLockAcquired = false;
}

rotorSaveResponse(
    'success'
);

}


/* =========================================================
   USER-SAFE BUSINESS ERROR
========================================================= */

catch (
    RotorValidationException $error
) {

    mysqli_rollback(
        $conn
    );

    if ($rotorCustomerLockAcquired) {

        mysqli_query(
            $conn,
            "DO RELEASE_LOCK('" . $rotorCustomerLock . "')"
        );

        $rotorCustomerLockAcquired = false;
    }


    rotorSaveResponse(
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

    if ($rotorCustomerLockAcquired) {

        mysqli_query(
            $conn,
            "DO RELEASE_LOCK('" . $rotorCustomerLock . "')"
        );

        $rotorCustomerLockAcquired = false;
    }


    error_log(
        '[SPIS][Rotor] save failed: '
        .
        $error->getMessage()
    );


    rotorSaveResponse(
        'Unable to save the Rotor rental. Please try again.',
        500
    );

}

?>