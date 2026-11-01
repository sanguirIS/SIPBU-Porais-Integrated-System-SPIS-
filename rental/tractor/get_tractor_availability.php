<?php

/* =========================================================
   SPIS - TRACTOR AVAILABILITY
   Date-based Rental scheduling

   Used for:
   - Primary Tractor
   - Secondary Tractor
   - Add Rental
   - Edit Rental

   Rental types:
   - Hauling
   - Rotor
   - Reaper
========================================================= */

if (
    session_status() === PHP_SESSION_NONE
) {
    session_start();
}


date_default_timezone_set(
    'Asia/Manila'
);


error_reporting(E_ALL);

ini_set(
    'display_errors',
    '0'
);


header(
    'Content-Type: application/json; charset=UTF-8'
);


/* =========================================================
   DATABASE
========================================================= */

require_once '../../db.php';


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   RESPONSE
========================================================= */

function tractorAvailabilityResponse(
    array $data,
    int $statusCode = 200
): void {

    http_response_code(
        $statusCode
    );


    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    );


    exit();

}


/* =========================================================
   SESSION
========================================================= */

$userID =

    isset(
        $_SESSION['user_id']
    )

    ?

    (int) $_SESSION['user_id']

    :

    0;


if (
    $userID <= 0
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Your session has expired. Please sign in again.'
        ],
        401
    );

}


/* =========================================================
   LIVE RENTAL ACCESS
========================================================= */

$userStmt =

    mysqli_prepare(
        $conn,
        "
        SELECT
            user_id

        FROM users

        WHERE
            user_id = ?

        AND archived = 0

        AND status = 'Active'

        AND rental_access = 1

        LIMIT 1
        "
    );


if (
    !$userStmt
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Unable to verify Rental access.'
        ],
        500
    );

}


mysqli_stmt_bind_param(
    $userStmt,
    'i',
    $userID
);


if (
    !mysqli_stmt_execute(
        $userStmt
    )
) {

    mysqli_stmt_close(
        $userStmt
    );


    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Unable to verify Rental access.'
        ],
        500
    );

}


$userResult =

    mysqli_stmt_get_result(
        $userStmt
    );


$hasRentalAccess =

    $userResult

    &&

    mysqli_num_rows(
        $userResult
    ) === 1;


mysqli_stmt_close(
    $userStmt
);


if (
    !$hasRentalAccess
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'You do not have permission to access Rental.'
        ],
        403
    );

}


/* =========================================================
   REQUEST VALUES

   tractor_type
       Hauling / Rotor / Reaper

   rental_date
       YYYY-MM-DD

   rental_type
       Optional for Add
       Required together with rental_id during Edit

   rental_id
       Current Rental record being edited
========================================================= */

$tractorType =

    trim(
        (string) (
            $_GET['tractor_type']
            ??
            ''
        )
    );


$rentalDate =

    trim(
        (string) (
            $_GET['rental_date']
            ??
            ''
        )
    );


$rentalType =

    strtolower(
        trim(
            (string) (
                $_GET['rental_type']
                ??
                ''
            )
        )
    );


$rentalID =

    isset(
        $_GET['rental_id']
    )

    ?

    (int) $_GET['rental_id']

    :

    0;


/* =========================================================
   TRACTOR TYPE
========================================================= */

$allowedTractorTypes = [

    'Hauling',
    'Rotor',
    'Reaper'

];


if (
    !in_array(
        $tractorType,
        $allowedTractorTypes,
        true
    )
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Invalid tractor type.'
        ],
        400
    );

}


/* =========================================================
   RENTAL DATE
========================================================= */

if (
    $rentalDate === ''
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Rental date is required.'
        ],
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


$dateIsValid =

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
    !$dateIsValid
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Invalid Rental date.'
        ],
        400
    );

}


/* =========================================================
   RENTAL TYPE
========================================================= */

$allowedRentalTypes = [

    '',
    'hauling',
    'rotor',
    'reaper'

];


if (
    !in_array(
        $rentalType,
        $allowedRentalTypes,
        true
    )
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Invalid Rental type.'
        ],
        400
    );

}


if (
    $rentalID > 0
    &&
    $rentalType === ''
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Rental type is required when editing a Rental.'
        ],
        400
    );

}


/* =========================================================
   LOAD SHARED TRACTOR POOL

   IMPORTANT:

   Tractor Type remains for classification / identification
   only. It must NOT restrict which registered tractor may be
   assigned to Hauling, Rotor, or Reaper.

   Current registry status such as:
   - Reserved
   - In Use

   represents CURRENT physical state.

   It does NOT automatically make a tractor unavailable
   for a different future date.

   Maintenance is different:
   Maintenance tractors cannot be selected.
========================================================= */

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

        WHERE archived = 0

        ORDER BY
            tractor_no ASC,
            tractor_name ASC,
            tractor_id ASC
        "
    );


if (
    !$tractorStmt
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Unable to load Tractor Registry.'
        ],
        500
    );

}


if (
    !mysqli_stmt_execute(
        $tractorStmt
    )
) {

    mysqli_stmt_close(
        $tractorStmt
    );


    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Unable to load Tractor Registry.'
        ],
        500
    );

}


$tractorResult =

    mysqli_stmt_get_result(
        $tractorStmt
    );


$tractors = [];


if (
    $tractorResult
) {

    while (
        $row =
        mysqli_fetch_assoc(
            $tractorResult
        )
    ) {


        $tractorID =

            (int) (
                $row['tractor_id']
                ??
                0
            );


        if (
            $tractorID <= 0
        ) {

            continue;

        }


        $registryStatus =

            trim(
                (string) (
                    $row['status']
                    ??
                    'Available'
                )
            );


        $maintenance =

            $registryStatus
            ===
            'Maintenance';


        $tractors[
            $tractorID
        ] = [

            'tractor_id' =>
                $tractorID,

            'tractor_no' =>
                trim(
                    (string) (
                        $row['tractor_no']
                        ??
                        ''
                    )
                ),

            'tractor_name' =>
                trim(
                    (string) (
                        $row['tractor_name']
                        ??
                        ''
                    )
                ),

            'tractor_type' =>
                trim(
                    (string) (
                        $row['tractor_type']
                        ??
                        ''
                    )
                ),

            'registry_status' =>
                $registryStatus,

            'available' =>
                !$maintenance,

            'availability' =>

                $maintenance

                ?

                'Maintenance'

                :

                'Available',

            'conflicts' =>
                []

        ];

    }

}


mysqli_stmt_close(
    $tractorStmt
);


/* =========================================================
   NO REGISTERED TRACTORS
========================================================= */

if (
    empty(
        $tractors
    )
) {

    tractorAvailabilityResponse(
        [
            'success' =>
                true,

            'tractor_type' =>
                $tractorType,

            'rental_date' =>
                $rentalDate,

            'available_count' =>
                0,

            'unavailable_count' =>
                0,

            'tractors' =>
                []
        ]
    );

}


/* =========================================================
   ACTIVE TRACTOR ASSIGNMENTS

   Conflict rules:

   Pending
       Tractor is Reserved for that date.

   Ongoing
       Tractor is In Use for that date.

   Done
       Released. Does not block the date.

   BOTH:
   - primary tractor
   - secondary tractor

   are checked.

   No scheduled_time.
   No time overlap.
========================================================= */

$assignmentStmt =

    mysqli_prepare(
        $conn,
        "
        SELECT

            rental_type,
            rental_id,
            transaction_id,
            rental_status,

            primary_tractor_id,
            secondary_tractor_id

        FROM
        (

            /* =================================================
               HAULING
            ================================================= */

            SELECT

                'hauling'
                    AS rental_type,

                h.hauling_id
                    AS rental_id,

                h.transaction_id,

                h.status
                    AS rental_status,

                h.tractor_id
                    AS primary_tractor_id,

                h.secondary_tractor_id

            FROM hauling h

            WHERE
                DATE(h.rental_date) = ?

            AND h.status IN (
                'Pending',
                'Ongoing'
            )


            UNION ALL


            /* =================================================
               ROTOR
            ================================================= */

            SELECT

                'rotor'
                    AS rental_type,

                r.rotor_id
                    AS rental_id,

                r.transaction_id,

                r.status
                    AS rental_status,

                r.tractor_id
                    AS primary_tractor_id,

                r.secondary_tractor_id

            FROM rotor r

            WHERE
                DATE(r.rental_date) = ?

            AND r.status IN (
                'Pending',
                'Ongoing'
            )


            UNION ALL


            /* =================================================
               REAPER
            ================================================= */

            SELECT

                'reaper'
                    AS rental_type,

                rp.reaper_id
                    AS rental_id,

                rp.transaction_id,

                rp.status
                    AS rental_status,

                rp.tractor_id
                    AS primary_tractor_id,

                rp.secondary_tractor_id

            FROM reaper rp

            WHERE
                DATE(rp.rental_date) = ?

            AND rp.status IN (
                'Pending',
                'Ongoing'
            )

        ) AS active_assignments

        ORDER BY
            rental_type ASC,
            rental_id ASC
        "
    );


if (
    !$assignmentStmt
) {

    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Unable to check tractor availability.'
        ],
        500
    );

}


mysqli_stmt_bind_param(
    $assignmentStmt,
    'sss',
    $rentalDate,
    $rentalDate,
    $rentalDate
);


if (
    !mysqli_stmt_execute(
        $assignmentStmt
    )
) {

    mysqli_stmt_close(
        $assignmentStmt
    );


    tractorAvailabilityResponse(
        [
            'success' => false,

            'message' =>
                'Unable to check tractor availability.'
        ],
        500
    );

}


$assignmentResult =

    mysqli_stmt_get_result(
        $assignmentStmt
    );


/* =========================================================
   PROCESS ASSIGNMENTS
========================================================= */

if (
    $assignmentResult
) {

    while (
        $assignment =
        mysqli_fetch_assoc(
            $assignmentResult
        )
    ) {


        $assignmentType =

            strtolower(
                trim(
                    (string) (
                        $assignment['rental_type']
                        ??
                        ''
                    )
                )
            );


        $assignmentID =

            (int) (
                $assignment['rental_id']
                ??
                0
            );


        /* =================================================
           EDIT SELF-EXCLUSION

           The Rental currently being edited must not
           conflict with itself.
        ================================================= */

        if (
            $rentalID > 0
            &&
            $rentalType !== ''
            &&
            $assignmentType === $rentalType
            &&
            $assignmentID === $rentalID
        ) {

            continue;

        }


        $transactionID =

            trim(
                (string) (
                    $assignment['transaction_id']
                    ??
                    ''
                )
            );


        $rentalStatus =

            trim(
                (string) (
                    $assignment['rental_status']
                    ??
                    ''
                )
            );


        $availabilityLabel =

            $rentalStatus === 'Ongoing'

            ?

            'In Use'

            :

            'Reserved';


        /* =================================================
           PRIMARY + SECONDARY TRACTOR
        ================================================= */

        $assignmentTractors = [

            'Primary' =>

                (int) (
                    $assignment['primary_tractor_id']
                    ??
                    0
                ),

            'Secondary' =>

                (int) (
                    $assignment['secondary_tractor_id']
                    ??
                    0
                )

        ];


        foreach (
            $assignmentTractors
            as
            $assignmentRole
            =>
            $assignedTractorID
        ) {


            if (
                $assignedTractorID <= 0
            ) {

                continue;

            }


            /*
            The shared pool loads every active registered
            tractor regardless of Tractor Type.

            Keep this defensive check in case an older
            Rental record references a missing/archived
            tractor record.
            */

            if (
                !isset(
                    $tractors[
                        $assignedTractorID
                    ]
                )
            ) {

                continue;

            }


            /*
               Maintenance remains Maintenance.

               Do not replace the visible reason with
               Reserved / In Use.
            */

            if (
                $tractors[
                    $assignedTractorID
                ]['availability']
                !==
                'Maintenance'
            ) {


                $tractors[
                    $assignedTractorID
                ]['available'] =
                    false;


                /*
                   Ongoing / In Use takes priority over
                   Pending / Reserved if legacy duplicate
                   conflicts happen to exist.
                */

                if (
                    $availabilityLabel === 'In Use'

                    ||

                    $tractors[
                        $assignedTractorID
                    ]['availability']
                    ===
                    'Available'
                ) {

                    $tractors[
                        $assignedTractorID
                    ]['availability'] =
                        $availabilityLabel;

                }

            }


            $tractors[
                $assignedTractorID
            ]['conflicts'][] = [

                'rental_type' =>
                    ucfirst(
                        $assignmentType
                    ),

                'rental_id' =>
                    $assignmentID,

                'transaction_id' =>
                    $transactionID,

                'status' =>
                    $rentalStatus,

                'assignment_role' =>
                    $assignmentRole

            ];

        }

    }

}


mysqli_stmt_close(
    $assignmentStmt
);


/* =========================================================
   NORMAL ARRAY
========================================================= */

$tractorList =

    array_values(
        $tractors
    );


/* =========================================================
   SORT

   Order:
   1. Available
   2. Reserved
   3. In Use
   4. Maintenance

   Within each group:
   Tractor No.
========================================================= */

$availabilityOrder = [

    'Available' =>
        1,

    'Reserved' =>
        2,

    'In Use' =>
        3,

    'Maintenance' =>
        4

];


usort(
    $tractorList,

    function (
        array $a,
        array $b
    ) use (
        $availabilityOrder
    ): int {


        $aStatus =

            (string) (
                $a['availability']
                ??
                'Available'
            );


        $bStatus =

            (string) (
                $b['availability']
                ??
                'Available'
            );


        $aOrder =

            $availabilityOrder[
                $aStatus
            ]
            ??
            99;


        $bOrder =

            $availabilityOrder[
                $bStatus
            ]
            ??
            99;


        if (
            $aOrder !==
            $bOrder
        ) {

            return
                $aOrder
                <=>
                $bOrder;

        }


        return

            strcasecmp(
                (string) (
                    $a['tractor_no']
                    ??
                    ''
                ),
                (string) (
                    $b['tractor_no']
                    ??
                    ''
                )
            );

    }
);


/* =========================================================
   COUNTS
========================================================= */

$availableCount =
    0;


$unavailableCount =
    0;


foreach (
    $tractorList
    as
    $tractor
) {

    if (
        !empty(
            $tractor['available']
        )
    ) {

        $availableCount++;

    }
    else {

        $unavailableCount++;

    }

}


/* =========================================================
   SUCCESS
========================================================= */

tractorAvailabilityResponse(
    [
        'success' =>
            true,

        'tractor_type' =>
            $tractorType,

        'rental_date' =>
            $rentalDate,

        'available_count' =>
            $availableCount,

        'unavailable_count' =>
            $unavailableCount,

        'tractors' =>
            $tractorList
    ]
);