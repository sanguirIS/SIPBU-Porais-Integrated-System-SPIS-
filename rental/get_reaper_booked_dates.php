<?php

/* =========================================================
   SPIS - GET REAPER CALENDAR AVAILABILITY

   - Date-only scheduling
   - Primary + Secondary Tractor/Driver assignments count
   - Drivers are shared across Hauling / Rotor / Reaper
   - Pending / Ongoing reserve resources
   - Done releases resources
   - Optional rental_id self-excludes current Reaper record
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


/* =========================================================
   DATABASE
========================================================= */

require_once '../db.php';


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

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


function reaperCalendarResponse(
    bool $success,
    string $message = '',
    array $extra = [],
    int $statusCode = 200
): void {

    http_response_code(
        $statusCode
    );


    echo json_encode(

        array_merge(

            [
                'success' => $success,
                'message' => $message
            ],

            $extra

        ),

        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES

    );


    exit();

}


/* =========================================================
   GET ONLY
========================================================= */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'GET'
) {

    reaperCalendarResponse(
        false,
        'Invalid request.',
        [],
        405
    );

}


/* =========================================================
   RENTAL ACCESS
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

    reaperCalendarResponse(
        false,
        'Your session has expired. Please sign in again.',
        [],
        401
    );

}


$accessStmt =

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
    !$accessStmt
) {

    reaperCalendarResponse(
        false,
        'Unable to verify Rental access.',
        [],
        500
    );

}


mysqli_stmt_bind_param(
    $accessStmt,
    'i',
    $userID
);


if (
    !mysqli_stmt_execute(
        $accessStmt
    )
) {

    mysqli_stmt_close(
        $accessStmt
    );


    reaperCalendarResponse(
        false,
        'Unable to verify Rental access.',
        [],
        500
    );

}


$accessResult =

mysqli_stmt_get_result(
    $accessStmt
);


$hasRentalAccess =

    $accessResult

    &&

    mysqli_num_rows(
        $accessResult
    ) === 1;


mysqli_stmt_close(
    $accessStmt
);


if (
    !$hasRentalAccess
) {

    reaperCalendarResponse(
        false,
        'You do not have permission to access Rental records.',
        [],
        403
    );

}


/* =========================================================
   MONTH / YEAR
========================================================= */

$year =

    isset(
        $_GET['year']
    )

    ?

    (int) $_GET['year']

    :

    0;


$month =

    isset(
        $_GET['month']
    )

    ?

    (int) $_GET['month']

    :

    0;


/* =========================================================
   OPTIONAL EDIT SELF-EXCLUSION
========================================================= */

$rentalID =

    isset(
        $_GET['rental_id']
    )

    ?

    max(
        0,
        (int) $_GET['rental_id']
    )

    :

    0;


/* =========================================================
   CURRENT EDIT ASSIGNMENT

   Used only by the Edit / Reschedule calendar.

   These IDs let the calendar determine whether the exact
   currently selected Reaper Tractor / Driver assignment
   can still be used on each candidate date.
========================================================= */

$editPrimaryTractorID =

    isset(
        $_GET['primary_tractor_id']
    )

    ?

    max(
        0,
        (int) $_GET['primary_tractor_id']
    )

    :

    0;


$editSecondaryTractorID =

    isset(
        $_GET['secondary_tractor_id']
    )

    ?

    max(
        0,
        (int) $_GET['secondary_tractor_id']
    )

    :

    0;


$editPrimaryDriverID =

    isset(
        $_GET['primary_driver_id']
    )

    ?

    max(
        0,
        (int) $_GET['primary_driver_id']
    )

    :

    0;


$editSecondaryDriverID =

    isset(
        $_GET['secondary_driver_id']
    )

    ?

    max(
        0,
        (int) $_GET['secondary_driver_id']
    )

    :

    0;


$hasEditAssignmentContext =

    (
        $rentalID > 0
        &&
        (
            $editPrimaryTractorID > 0
            ||
            $editSecondaryTractorID > 0
            ||
            $editPrimaryDriverID > 0
            ||
            $editSecondaryDriverID > 0
        )
    );


if (
    $year < 2000
    ||
    $year > 2100
    ||
    $month < 1
    ||
    $month > 12
) {

    reaperCalendarResponse(
        false,
        'Invalid calendar month.',
        [],
        400
    );

}


/* =========================================================
   MONTH RANGE
========================================================= */

$monthStart =

sprintf(
    '%04d-%02d-01',
    $year,
    $month
);


$monthStartObject =

DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $monthStart
);


if (
    !$monthStartObject
) {

    reaperCalendarResponse(
        false,
        'Unable to prepare calendar month.',
        [],
        500
    );

}


$monthEndObject =

$monthStartObject
->modify(
    'last day of this month'
);


$monthEnd =

$monthEndObject
->format(
    'Y-m-d'
);


$daysInMonth =

(int)
$monthEndObject
->format(
    'j'
);


/* =========================================================
   ELIGIBLE SHARED TRACTOR POOL

   Tractor Type is classification only.
   Any active registered tractor may be assigned across
   Hauling, Rotor, or Reaper.

   Maintenance = hard lock.
========================================================= */

$tractorIDs = [];


$tractorStmt =

mysqli_prepare(
    $conn,
    "
    SELECT
        tractor_id

    FROM tractors

    WHERE archived = 0

    AND status <> 'Maintenance'

    ORDER BY tractor_id ASC
    "
);


if (
    !$tractorStmt
) {

    reaperCalendarResponse(
        false,
        'Unable to load registered tractors.',
        [],
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


    reaperCalendarResponse(
        false,
        'Unable to load registered tractors.',
        [],
        500
    );

}


$tractorResult =

mysqli_stmt_get_result(
    $tractorStmt
);


if (
    $tractorResult
) {

    while (
        $tractor =
        mysqli_fetch_assoc(
            $tractorResult
        )
    ) {

        $tractorID =

        (int) (
            $tractor['tractor_id']
            ??
            0
        );


        if (
            $tractorID > 0
        ) {

            $tractorIDs[
                $tractorID
            ] = true;

        }

    }

}


mysqli_stmt_close(
    $tractorStmt
);


/* =========================================================
   ELIGIBLE ACTIVE DRIVERS

   Driver Registry is linked to Members.
   Linked Member must also remain Active.
========================================================= */

$driverIDs = [];


$driverStmt =

mysqli_prepare(
    $conn,
    "
    SELECT
        d.driver_id

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

    reaperCalendarResponse(
        false,
        'Unable to load active Drivers.',
        [],
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


    reaperCalendarResponse(
        false,
        'Unable to load active Drivers.',
        [],
        500
    );

}


$driverResult =

mysqli_stmt_get_result(
    $driverStmt
);


if (
    $driverResult
) {

    while (
        $driver =
        mysqli_fetch_assoc(
            $driverResult
        )
    ) {

        $driverID =

        (int) (
            $driver['driver_id']
            ??
            0
        );


        if (
            $driverID > 0
        ) {

            $driverIDs[
                $driverID
            ] = true;

        }

    }

}


mysqli_stmt_close(
    $driverStmt
);


$totalTractors =

count(
    $tractorIDs
);


$totalDrivers =

count(
    $driverIDs
);


$totalPairCapacity =

min(
    $totalTractors,
    $totalDrivers
);


/* =========================================================
   ACTIVE RENTAL ASSIGNMENTS

   Pending / Ongoing
   = resource is occupied

   Done
   = resource is released

   Both Primary and Secondary assignments count.

   Drivers are shared across:
   - Hauling
   - Rotor
   - Reaper
========================================================= */

$assignmentStmt =

mysqli_prepare(
    $conn,
    "
    SELECT

        rental_type,
        rental_id,
        rental_date,

        primary_tractor_id,
        secondary_tractor_id,

        primary_driver_id,
        secondary_driver_id

    FROM
    (

        /* =============================================
           HAULING
        ============================================= */

        SELECT

            'hauling'
                AS rental_type,

            h.hauling_id
                AS rental_id,

            DATE(
                h.rental_date
            ) AS rental_date,

            h.tractor_id
                AS primary_tractor_id,

            h.secondary_tractor_id,

            h.primary_driver_id,
            h.secondary_driver_id

        FROM hauling h

        WHERE
            DATE(
                h.rental_date
            )
            BETWEEN ? AND ?

        AND h.status IN (
            'Pending',
            'Ongoing'
        )


        UNION ALL


        /* =============================================
           ROTOR
        ============================================= */

        SELECT

            'rotor'
                AS rental_type,

            r.rotor_id
                AS rental_id,

            DATE(
                r.rental_date
            ) AS rental_date,

            r.tractor_id
                AS primary_tractor_id,

            r.secondary_tractor_id,

            r.primary_driver_id,
            r.secondary_driver_id

        FROM rotor r

        WHERE
            DATE(
                r.rental_date
            )
            BETWEEN ? AND ?

        AND r.status IN (
            'Pending',
            'Ongoing'
        )


        UNION ALL


        /* =============================================
           REAPER
        ============================================= */

        SELECT

            'reaper'
                AS rental_type,

            rp.reaper_id
                AS rental_id,

            DATE(
                rp.rental_date
            ) AS rental_date,

            rp.tractor_id
                AS primary_tractor_id,

            rp.secondary_tractor_id,

            rp.primary_driver_id,
            rp.secondary_driver_id

        FROM reaper rp

        WHERE
            DATE(
                rp.rental_date
            )
            BETWEEN ? AND ?

        AND rp.status IN (
            'Pending',
            'Ongoing'
        )

    ) AS active_assignments

    ORDER BY
        rental_date ASC
    "
);


if (
    !$assignmentStmt
) {

    reaperCalendarResponse(
        false,
        'Unable to load Rental calendar availability.',
        [],
        500
    );

}


mysqli_stmt_bind_param(
    $assignmentStmt,
    'ssssss',

    $monthStart,
    $monthEnd,

    $monthStart,
    $monthEnd,

    $monthStart,
    $monthEnd
);


if (
    !mysqli_stmt_execute(
        $assignmentStmt
    )
) {

    mysqli_stmt_close(
        $assignmentStmt
    );


    reaperCalendarResponse(
        false,
        'Unable to load Rental calendar availability.',
        [],
        500
    );

}


$assignmentResult =

mysqli_stmt_get_result(
    $assignmentStmt
);


$busyTractorsByDate = [];

$busyDriversByDate = [];


if (
    $assignmentResult
) {

    while (
        $assignment =
        mysqli_fetch_assoc(
            $assignmentResult
        )
    ) {

        /* =============================================
           EDIT MODE SELF-EXCLUSION
        ============================================= */

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


        if (
            $rentalID > 0
            &&
            $assignmentType === 'reaper'
            &&
            $assignmentID === $rentalID
        ) {

            continue;

        }


        $date =

        trim(
            (string) (
                $assignment[
                    'rental_date'
                ]
                ??
                ''
            )
        );


        if (
            $date === ''
        ) {

            continue;

        }


        if (
            !isset(
                $busyTractorsByDate[
                    $date
                ]
            )
        ) {

            $busyTractorsByDate[
                $date
            ] = [];

        }


        if (
            !isset(
                $busyDriversByDate[
                    $date
                ]
            )
        ) {

            $busyDriversByDate[
                $date
            ] = [];

        }


        $primaryTractorID =

        (int) (
            $assignment[
                'primary_tractor_id'
            ]
            ??
            0
        );


        $secondaryTractorID =

        (int) (
            $assignment[
                'secondary_tractor_id'
            ]
            ??
            0
        );


        $primaryDriverID =

        (int) (
            $assignment[
                'primary_driver_id'
            ]
            ??
            0
        );


        $secondaryDriverID =

        (int) (
            $assignment[
                'secondary_driver_id'
            ]
            ??
            0
        );


        /* =============================================
        TRACTORS
        ============================================= */

        if (
            $primaryTractorID > 0
            &&
            isset(
                $tractorIDs[
                    $primaryTractorID
                ]
            )
        ) {

            $busyTractorsByDate[
                $date
            ][
                $primaryTractorID
            ] = true;

        }


        if (
            $secondaryTractorID > 0
            &&
            isset(
                $tractorIDs[
                    $secondaryTractorID
                ]
            )
        ) {

            $busyTractorsByDate[
                $date
            ][
                $secondaryTractorID
            ] = true;

        }


        /* =============================================
           DRIVERS

           Driver conflicts count across every
           Rental module.
        ============================================= */

        if (
            $primaryDriverID > 0
            &&
            isset(
                $driverIDs[
                    $primaryDriverID
                ]
            )
        ) {

            $busyDriversByDate[
                $date
            ][
                $primaryDriverID
            ] = true;

        }


        if (
            $secondaryDriverID > 0
            &&
            isset(
                $driverIDs[
                    $secondaryDriverID
                ]
            )
        ) {

            $busyDriversByDate[
                $date
            ][
                $secondaryDriverID
            ] = true;

        }

    }

}


mysqli_stmt_close(
    $assignmentStmt
);


/* =========================================================
   BUILD CALENDAR STATUS

   Example:

   2 Tractors
   2 Drivers

   Capacity = 2 assignment pairs

   2 pairs available
   = Available

   1 pair available
   = Limited

   0 pairs available
   = Fully Booked
========================================================= */

$dates = [];

$bookedDates = [];

$limitedDates = [];


for (
    $day = 1;
    $day <= $daysInMonth;
    $day++
) {

    $date =

    sprintf(
        '%04d-%02d-%02d',
        $year,
        $month,
        $day
    );


    $busyTractorCount =

        isset(
            $busyTractorsByDate[
                $date
            ]
        )

        ?

        count(
            $busyTractorsByDate[
                $date
            ]
        )

        :

        0;


    $busyDriverCount =

        isset(
            $busyDriversByDate[
                $date
            ]
        )

        ?

        count(
            $busyDriversByDate[
                $date
            ]
        )

        :

        0;


    $availableTractors =

    max(
        0,
        $totalTractors
        -
        $busyTractorCount
    );


    $availableDrivers =

    max(
        0,
        $totalDrivers
        -
        $busyDriverCount
    );


    $availablePairs =

    min(
        $availableTractors,
        $availableDrivers
    );


    /* =============================================
       FULLY BOOKED
    ============================================= */

    if (
        $totalPairCapacity <= 0
        ||
        $availablePairs <= 0
    ) {

        $status =
        'Fully Booked';


        $bookedDates[] =
        $date;

    }


    /* =============================================
       LIMITED
    ============================================= */

    elseif (
        $availablePairs
        <
        $totalPairCapacity
    ) {

        $status =
        'Limited';


        $limitedDates[] =
        $date;

    }


    /* =============================================
       AVAILABLE
    ============================================= */

    else {

        $status =
        'Available';

    }


    /* =============================================
       EDIT / RESCHEDULE ASSIGNMENT STATUS

       Generic status:
       Is there any Tractor + Driver pair left?

       Assignment status:
       Can the currently selected Tractor / Driver
       assignment be carried to this date?
    ============================================= */

    $assignmentStatus =
        $status;


    if (
        $hasEditAssignmentContext
        &&
        $status !== 'Fully Booked'
    ) {

        $assignmentConflict =
            false;


        foreach (
            [
                $editPrimaryTractorID,
                $editSecondaryTractorID
            ]
            as
            $editTractorID
        ) {

            if (
                $editTractorID <= 0
            ) {

                continue;

            }


            if (
                !isset(
                    $tractorIDs[
                        $editTractorID
                    ]
                )
                ||
                isset(
                    $busyTractorsByDate[
                        $date
                    ][
                        $editTractorID
                    ]
                )
            ) {

                $assignmentConflict =
                    true;

                break;

            }

        }


        if (
            !$assignmentConflict
        ) {

            foreach (
                [
                    $editPrimaryDriverID,
                    $editSecondaryDriverID
                ]
                as
                $editDriverID
            ) {

                if (
                    $editDriverID <= 0
                ) {

                    continue;

                }


                if (
                    !isset(
                        $driverIDs[
                            $editDriverID
                        ]
                    )
                    ||
                    isset(
                        $busyDriversByDate[
                            $date
                        ][
                            $editDriverID
                    ]
                )
            ) {

                    $assignmentConflict =
                        true;

                    break;

                }

            }

        }


        $assignmentStatus =

            $assignmentConflict

            ?

            'Limited'

            :

            'Available';

    }


    $dates[
        $date
    ] = [

        'status' =>
            $status,

        'assignment_status' =>
            $assignmentStatus,

        'available_pairs' =>
            $availablePairs,

        'available_tractors' =>
            $availableTractors,

        'available_drivers' =>
            $availableDrivers,

        'busy_tractors' =>
            $busyTractorCount,

        'busy_drivers' =>
            $busyDriverCount

    ];

}


/* =========================================================
   SUCCESS
========================================================= */

reaperCalendarResponse(
    true,
    '',
    [

        'month' =>

            sprintf(
                '%04d-%02d',
                $year,
                $month
            ),


        'capacity' => [

            'tractors' =>
                $totalTractors,

            'drivers' =>
                $totalDrivers,

            'pairs' =>
                $totalPairCapacity

        ],


        'dates' =>
            $dates,


        'booked_dates' =>
            $bookedDates,


        'limited_dates' =>
            $limitedDates

    ]
);