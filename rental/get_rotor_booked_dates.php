<?php

/* =========================================================
   SPIS - GET ROTOR CALENDAR AVAILABILITY

   PURPOSE:
   - Date-only Rotor calendar
   - Primary + Secondary assignments are counted
   - Shared Tractor + Driver availability are both considered
   - Driver conflicts are checked across Hauling / Rotor / Reaper
   - Tractor conflicts are checked across Hauling / Rotor / Reaper
   - Optional rental_id supports Edit self-exclusion

   CALENDAR STATUS:
   - Available
   - Limited
   - Fully Booked
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
    'Content-Type: application/json; charset=utf-8'
);


header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
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

function rotorCalendarResponse(
    bool $success,
    string $message = '',
    array $data = [],
    int $statusCode = 200
): void {


    http_response_code(
        $statusCode
    );


    echo json_encode(
        [
            'success' =>
                $success,

            'message' =>
                $message,

            'data' =>
                $data
        ],

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
    (
        $_SERVER[
            'REQUEST_METHOD'
        ]
        ??
        ''
    )
    !==
    'GET'
) {

    rotorCalendarResponse(
        false,
        'Invalid request.',
        [],
        405
    );

}


/* =========================================================
   VERIFY LIVE RENTAL ACCESS
========================================================= */

$userID =

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
    $userID <= 0
) {

    rotorCalendarResponse(
        false,
        'Your session has expired. Please sign in again.',
        [],
        401
    );

}


$userStmt =

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
    !$userStmt
) {

    rotorCalendarResponse(
        false,
        'Unable to verify Rental access.',
        [],
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


    rotorCalendarResponse(
        false,
        'Unable to verify Rental access.',
        [],
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
)
===
1;


mysqli_stmt_close(
    $userStmt
);


if (
    !$hasRentalAccess
) {

    rotorCalendarResponse(
        false,
        'You do not have permission to access Rental records.',
        [],
        403
    );

}


/* =========================================================
   REQUEST VALUES
========================================================= */

$year =

isset(
    $_GET[
        'year'
    ]
)

?

(int)
$_GET[
    'year'
]

:

0;


$month =

isset(
    $_GET[
        'month'
    ]
)

?

(int)
$_GET[
    'month'
]

:

0;


/*
   Used later by Edit Rotor.

   Add mode:
   rental_id = 0

   Edit mode:
   rental_id = current Rotor record
*/

$rentalID =

isset(
    $_GET[
        'rental_id'
    ]
)

?

(int)
$_GET[
    'rental_id'
]

:

0;


if (
    $rentalID < 0
) {

    $rentalID =
    0;

}


/* =========================================================
   CURRENT EDIT ASSIGNMENT

   Used only by the Edit / Reschedule calendar.

   These are the resources currently selected in the modal.
   The current rental is still self-excluded below, while
   these IDs let the calendar tell whether that exact
   assignment can be carried to each candidate date.
========================================================= */

$editPrimaryTractorID =

isset(
    $_GET[
        'primary_tractor_id'
    ]
)

?

max(
    0,
    (int) $_GET[
        'primary_tractor_id'
    ]
)

:

0;


$editSecondaryTractorID =

isset(
    $_GET[
        'secondary_tractor_id'
    ]
)

?

max(
    0,
    (int) $_GET[
        'secondary_tractor_id'
    ]
)

:

0;


$editPrimaryDriverID =

isset(
    $_GET[
        'primary_driver_id'
    ]
)

?

max(
    0,
    (int) $_GET[
        'primary_driver_id'
    ]
)

:

0;


$editSecondaryDriverID =

isset(
    $_GET[
        'secondary_driver_id'
    ]
)

?

max(
    0,
    (int) $_GET[
        'secondary_driver_id'
    ]
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


/* =========================================================
   VALIDATE MONTH
========================================================= */

if (
    $year < 2000
    ||
    $year > 2100
    ||
    $month < 1
    ||
    $month > 12
) {

    rotorCalendarResponse(
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


$monthStartDate =

DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $monthStart
);


if (
    !$monthStartDate
) {

    rotorCalendarResponse(
        false,
        'Invalid calendar month.',
        [],
        400
    );

}


$monthEndDate =

$monthStartDate
->modify(
    'last day of this month'
);


$monthEnd =

$monthEndDate
->format(
    'Y-m-d'
);


$daysInMonth =

(int)
$monthEndDate
->format(
    'j'
);


/* =========================================================
   ELIGIBLE SHARED TRACTOR POOL

   Tractor Type is classification only.
   Any active registered tractor may be assigned across
   Hauling, Rotor, or Reaper.

   Maintenance:
   - never available for a new assignment

   Reserved / In Use:
   - represents the CURRENT physical state only
   - date conflicts below determine whether the tractor
     is occupied for the selected Rental Date
========================================================= */

$tractorIDs =
[];


$tractorStmt =

mysqli_prepare(
    $conn,
    "
    SELECT
        tractor_id

    FROM tractors

    WHERE archived = 0

    AND status != 'Maintenance'

    ORDER BY
        tractor_id ASC
    "
);


if (
    !$tractorStmt
) {

    rotorCalendarResponse(
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


    rotorCalendarResponse(
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
            $tractor[
                'tractor_id'
            ]
            ??
            0
        );


        if (
            $tractorID > 0
        ) {

            $tractorIDs[
                $tractorID
            ] =
            true;

        }

    }

}


mysqli_stmt_close(
    $tractorStmt
);


/* =========================================================
   ELIGIBLE DRIVERS

   Driver must be:
   - Active
   - Not archived
   - Linked to an Active Member
   - Linked Member must not be archived
========================================================= */

$driverIDs =
[];


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

    rotorCalendarResponse(
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


    rotorCalendarResponse(
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
            $driver[
                'driver_id'
            ]
            ??
            0
        );


        if (
            $driverID > 0
        ) {

            $driverIDs[
                $driverID
            ] =
            true;

        }

    }

}


mysqli_stmt_close(
    $driverStmt
);


/* =========================================================
   TOTAL RENTAL CAPACITY

   One assignment requires:
   - 1 Tractor
   - 1 Driver
========================================================= */

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
   ACTIVE RENTAL ASSIGNMENTS FOR THE MONTH

   Pending:
   - resource is Reserved

   Ongoing:
   - resource is In Use / Assigned

   Done:
   - does not block the date

   IMPORTANT:
   Both PRIMARY and SECONDARY resources are checked.
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
            )
                AS rental_date,

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
            )
                AS rental_date,

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
            )
                AS rental_date,

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

    )
    AS active_assignments

    ORDER BY

        rental_date ASC,

        rental_type ASC,

        rental_id ASC
    "
);


if (
    !$assignmentStmt
) {

    rotorCalendarResponse(
        false,
        'Unable to check Rental availability.',
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


    rotorCalendarResponse(
        false,
        'Unable to check Rental availability.',
        [],
        500
    );

}


$assignmentResult =

mysqli_stmt_get_result(
    $assignmentStmt
);


/* =========================================================
   OCCUPIED RESOURCES BY DATE
========================================================= */

$busyTractorsByDate =
[];


$busyDriversByDate =
[];


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


        /* =============================================
           EDIT ROTOR SELF-EXCLUSION

           The current Rotor record being edited must
           not conflict with itself.
        ============================================= */

        if (
            $rentalID > 0
            &&
            $assignmentType === 'rotor'
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

           All eligible registered tractors belong to the
           same shared Rental tractor pool.
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
            ] =
            true;

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
            ] =
            true;

        }


        /* =============================================
           DRIVERS

           Drivers are shared across ALL Rental services.
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
            ] =
            true;

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
            ] =
            true;

        }

    }

}


mysqli_stmt_close(
    $assignmentStmt
);


/* =========================================================
   BUILD CALENDAR STATUS

   Example:

   3 Tractors
    4 Drivers

   Pair capacity = 3

   Available pairs:
   3 = Available
   1 or 2 = Limited
   0 = Fully Booked
========================================================= */

$dates =
[];


$bookedDates =
[];


$limitedDates =
[];


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

       Generic status above answers:
       "Is there any Tractor + Driver pair left?"

       Assignment status answers:
       "Can the resources currently selected in Edit
       be carried to this date without replacement?"

       Available
       - current selected assignment is still usable

       Limited
       - at least one selected Tractor / Driver is busy
         or no longer eligible, but another pair exists

       Fully Booked
       - no valid Tractor + Driver pair exists
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

rotorCalendarResponse(
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

?>