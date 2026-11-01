<?php

/* =========================================================
   SPIS - DRIVER AVAILABILITY
   Date-based Rental scheduling
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
   JSON RESPONSE
========================================================= */

function driverAvailabilityResponse(
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
   VERIFY USER SESSION
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

    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'Your session has expired. Please sign in again.'
        ],
        401
    );

}


/* =========================================================
   VERIFY LIVE RENTAL ACCESS
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

    driverAvailabilityResponse(
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


    driverAvailabilityResponse(
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

    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'You do not have permission to access Rental.'
        ],
        403
    );

}


/* =========================================================
   REQUEST DATA

   rental_date:
       Required.
       Example: 2026-09-15

   rental_type:
       Optional.
       hauling / rotor / reaper

   rental_id:
       Optional.
       Used by Edit modal so the current Rental does not
       conflict with itself.
========================================================= */

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
   DATE VALIDATION
========================================================= */

if (
    $rentalDate === ''
) {

    driverAvailabilityResponse(
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

    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'Invalid Rental date.'
        ],
        400
    );

}


/* =========================================================
   RENTAL TYPE VALIDATION

   Blank is allowed for Add mode.

   rental_type + rental_id are mainly used during Edit mode
   to exclude the currently edited record from conflicts.
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

    driverAvailabilityResponse(
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

    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'Rental type is required when editing a Rental.'
        ],
        400
    );

}


/* =========================================================
   LOAD ACTIVE DRIVERS

   Only:
   - Active
   - Not archived

   Inactive / archived drivers should never be offered
   as new Rental assignments.
========================================================= */

$driverStmt =

    mysqli_prepare(
        $conn,
        "
        SELECT

            d.driver_id,

            m.full_name
                AS full_name,

            m.contact_no
                AS contact_no

        FROM drivers d

        INNER JOIN members m

            ON m.member_id =
                d.member_id

            AND m.archived = 0

        WHERE
            d.archived = 0

        AND d.status = 'Active'

        AND UPPER(
            TRIM(
                COALESCE(
                    m.status,
                    ''
                )
            )
        ) = 'ACTIVE'

        ORDER BY
            d.driver_id ASC
        "
    );


if (
    !$driverStmt
) {

    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'Unable to load Driver Registry.'
        ],
        500
    );

}


if (
    !mysqli_stmt_execute(
        $driverStmt
    )
) {

    mysqli_stmt_close(
        $driverStmt
    );


    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'Unable to load Driver Registry.'
        ],
        500
    );

}


$driverResult =

    mysqli_stmt_get_result(
        $driverStmt
    );


$drivers = [];


if (
    $driverResult
) {

    while (
        $row =
        mysqli_fetch_assoc(
            $driverResult
        )
    ) {


        $driverID =

            (int) (
                $row['driver_id']
                ??
                0
            );


        if (
            $driverID <= 0
        ) {

            continue;

        }


        $drivers[
            $driverID
        ] = [

            'driver_id' =>
                $driverID,

            'driver_code' =>
                'DRV-'
                .
                str_pad(
                    (string) $driverID,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' =>
                trim(
                    (string) (
                        $row['full_name']
                        ??
                        ''
                    )
                ),

            'contact_no' =>
                trim(
                    (string) (
                        $row['contact_no']
                        ??
                        ''
                    )
                ),

            'available' =>
                true,

            'availability' =>
                'Available',

            'conflicts' =>
                []

        ];

    }

}


mysqli_stmt_close(
    $driverStmt
);


/* =========================================================
   NO DRIVERS
========================================================= */

if (
    empty(
        $drivers
    )
) {

    driverAvailabilityResponse(
        [
            'success' =>
                true,

            'rental_date' =>
                $rentalDate,

            'drivers' =>
                []
        ]
    );

}


/* =========================================================
   LOAD ACTIVE RENTAL ASSIGNMENTS FOR SELECTED DATE

   IMPORTANT:

   Driver conflict applies ACROSS ALL Rental types.

   Example:

   Juan is Primary Driver for Hauling on Sept. 15.

   Juan cannot also become:
   - Primary Driver in Rotor
   - Secondary Driver in Rotor
   - Primary Driver in Reaper
   - Secondary Driver in Reaper

   on Sept. 15.

   Date only. No time logic.
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

            primary_driver_id,
            secondary_driver_id

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

                h.primary_driver_id,

                h.secondary_driver_id

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

                r.primary_driver_id,

                r.secondary_driver_id

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

                rp.primary_driver_id,

                rp.secondary_driver_id

            FROM reaper rp

            WHERE
                DATE(rp.rental_date) = ?

            AND rp.status IN (
                'Pending',
                'Ongoing'
            )

        ) AS rental_assignments

        ORDER BY
            rental_type ASC,
            rental_id ASC
        "
    );


if (
    !$assignmentStmt
) {

    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'Unable to check Driver availability.'
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


    driverAvailabilityResponse(
        [
            'success' => false,
            'message' =>
                'Unable to check Driver availability.'
        ],
        500
    );

}


$assignmentResult =

    mysqli_stmt_get_result(
        $assignmentStmt
    );


/* =========================================================
   PROCESS CONFLICTS
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
                        $assignment[
                            'rental_type'
                        ]
                        ??
                        ''
                    )
                )
            );


        $assignmentID =

            (int) (
                $assignment[
                    'rental_id'
                ]
                ??
                0
            );


        /* =================================================
           EDIT MODE SELF-EXCLUSION

           Example:

           Editing Hauling #12.

           Its existing Primary Driver must not appear as
           unavailable because of Hauling #12 itself.
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
                    $assignment[
                        'transaction_id'
                    ]
                    ??
                    ''
                )
            );


        $rentalStatus =

            trim(
                (string) (
                    $assignment[
                        'rental_status'
                    ]
                    ??
                    ''
                )
            );


        $availabilityLabel =

            $rentalStatus === 'Ongoing'

            ?

            'Assigned'

            :

            'Reserved';


        /* =================================================
           CHECK PRIMARY + SECONDARY DRIVER
        ================================================= */

        $assignmentDriverIDs = [

            'Primary' =>
                (int) (
                    $assignment[
                        'primary_driver_id'
                    ]
                    ??
                    0
                ),

            'Secondary' =>
                (int) (
                    $assignment[
                        'secondary_driver_id'
                    ]
                    ??
                    0
                )

        ];


        foreach (
            $assignmentDriverIDs
            as
            $assignmentRole
            =>
            $assignmentDriverID
        ) {


            if (
                $assignmentDriverID <= 0
            ) {

                continue;

            }


            if (
                !isset(
                    $drivers[
                        $assignmentDriverID
                    ]
                )
            ) {

                continue;

            }


            $drivers[
                $assignmentDriverID
            ]['available'] =
                false;


            /*
               Ongoing takes priority over Pending
               when somehow more than one old/legacy
               conflicting record exists.
            */

            if (
                $availabilityLabel === 'Assigned'

                ||

                $drivers[
                    $assignmentDriverID
                ]['availability']
                ===
                'Available'
            ) {

                $drivers[
                    $assignmentDriverID
                ]['availability'] =
                    $availabilityLabel;

            }


            $drivers[
                $assignmentDriverID
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
   NORMALIZE DRIVER ARRAY

   We used driver_id as temporary associative keys.
   Convert back to normal JSON array.
========================================================= */

$driverList =

    array_values(
        $drivers
    );


/* =========================================================
   DRIVER ID ORDER

   Lowest Driver ID to highest.
========================================================= */

usort(
    $driverList,

    function (
        array $a,
        array $b
    ): int {

        return
            (int) (
                $a['driver_id']
                ??
                0
            )
            <=>
            (int) (
                $b['driver_id']
                ??
                0
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
    $driverList
    as
    $driver
) {

    if (
        !empty(
            $driver['available']
        )
    ) {

        $availableCount++;

    }
    else {

        $unavailableCount++;

    }

}


/* =========================================================
   RESPONSE
========================================================= */

driverAvailabilityResponse(
    [
        'success' =>
            true,

        'rental_date' =>
            $rentalDate,

        'available_count' =>
            $availableCount,

        'unavailable_count' =>
            $unavailableCount,

        'drivers' =>
            $driverList
    ]
);