<?php

/* =========================================================
   SPIS - GET HAULING RENTAL

   Used by:
   - Hauling Edit Modal

   CURRENT RENTAL RULES:
   - Date-only scheduling
   - No scheduled_time
   - Tractor / Driver IDs are the real assignments
   - operator fields are saved name snapshots
   - Payment Remarks are returned for reference only
========================================================= */


if (
    session_status() === PHP_SESSION_NONE
) {
    session_start();
}


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set(
    'Asia/Manila'
);


/* =========================================================
   ERROR SETTINGS
========================================================= */

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
   JSON RESPONSE
========================================================= */

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


/* =========================================================
   RESPONSE HELPER
========================================================= */

function haulingGetResponse(
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
            'success' => $success,
            'message' => $message,
            'data' => $data
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
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'GET'
) {

    haulingGetResponse(
        false,
        'Invalid request.',
        [],
        405
    );
}


/* =========================================================
   CURRENT USER
========================================================= */

$userID =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


if (
    $userID <= 0
) {

    haulingGetResponse(
        false,
        'Your session has expired. Please sign in again.',
        [],
        401
    );
}


/* =========================================================
   VERIFY LIVE RENTAL ACCESS

   Same principle used by our Hauling update endpoints:
   never trust only an old session permission.
========================================================= */

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

    haulingGetResponse(
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


    haulingGetResponse(
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
    (
        $accessResult
        &&
        mysqli_num_rows(
            $accessResult
        ) === 1
    );


mysqli_stmt_close(
    $accessStmt
);


if (
    !$hasRentalAccess
) {

    haulingGetResponse(
        false,
        'You do not have permission to access Rental records.',
        [],
        403
    );
}


/* =========================================================
   HAULING ID
========================================================= */

$haulingID =
    isset($_GET['id'])
        ? (int) $_GET['id']
        : 0;


if (
    $haulingID <= 0
) {

    haulingGetResponse(
        false,
        'Invalid Hauling record.',
        [],
        400
    );
}


/* =========================================================
   LOAD RECORD

   Join current Primary Tractor so the Edit Modal can also
   know whether that physical tractor is under Maintenance.
========================================================= */

$stmt =
    mysqli_prepare(
        $conn,
        "
        SELECT

            h.*,

            /* =============================================
            CURRENT PRIMARY TRACTOR
            ============================================= */

            pt.status
                AS tractor_status,

            pt.tractor_no
                AS current_primary_tractor_no,

            pt.tractor_name
                AS current_primary_tractor_name,


            /* =============================================
            CURRENT SECONDARY TRACTOR
            ============================================= */

            st.tractor_no
                AS current_secondary_tractor_no,

            st.tractor_name
                AS current_secondary_tractor_name,


            /* =============================================
            CURRENT PRIMARY DRIVER

            Member is the master identity.
            drivers.full_name is fallback for legacy data.
            ============================================= */

            COALESCE(
                NULLIF(
                    TRIM(
                        pm.full_name
                    ),
                    ''
                ),
                NULLIF(
                    TRIM(
                        pd.full_name
                    ),
                    ''
                )
            )
                AS current_primary_driver_name,


            /* =============================================
            CURRENT SECONDARY DRIVER
            ============================================= */

            COALESCE(
                NULLIF(
                    TRIM(
                        sm.full_name
                    ),
                    ''
                ),
                NULLIF(
                    TRIM(
                        sd.full_name
                    ),
                    ''
                )
            )
                AS current_secondary_driver_name,


            /* =============================================
            CURRENT MEMBER CUSTOMER
            ============================================= */

            cm.full_name
                AS current_customer_name


        FROM hauling h


        /* =================================================
        PRIMARY TRACTOR
        ================================================= */

        LEFT JOIN tractors pt

            ON pt.tractor_id =
                h.tractor_id


        /* =================================================
        SECONDARY TRACTOR
        ================================================= */

        LEFT JOIN tractors st

            ON st.tractor_id =
                h.secondary_tractor_id


        /* =================================================
        PRIMARY DRIVER
        ================================================= */

        LEFT JOIN drivers pd

            ON pd.driver_id =
                h.primary_driver_id


        LEFT JOIN members pm

            ON pm.member_id =
                pd.member_id


        /* =================================================
        SECONDARY DRIVER
        ================================================= */

        LEFT JOIN drivers sd

            ON sd.driver_id =
                h.secondary_driver_id


        LEFT JOIN members sm

            ON sm.member_id =
                sd.member_id


        /* =================================================
        RENTAL CUSTOMER MEMBER
        ================================================= */

        LEFT JOIN members cm

            ON cm.member_id =
                h.member_id


        WHERE
            h.hauling_id = ?

        LIMIT 1
        "
    );


if (
    !$stmt
) {

    haulingGetResponse(
        false,
        'Unable to prepare Hauling record.',
        [],
        500
    );
}


mysqli_stmt_bind_param(
    $stmt,
    'i',
    $haulingID
);


if (
    !mysqli_stmt_execute(
        $stmt
    )
) {

    mysqli_stmt_close(
        $stmt
    );


    haulingGetResponse(
        false,
        'Unable to load Hauling record.',
        [],
        500
    );
}


$result =
    mysqli_stmt_get_result(
        $stmt
    );


if (
    !$result
    ||
    mysqli_num_rows(
        $result
    ) !== 1
) {

    mysqli_stmt_close(
        $stmt
    );


    haulingGetResponse(
        false,
        'Hauling rental was not found.',
        [],
        404
    );
}


$row =
    mysqli_fetch_assoc(
        $result
    );


mysqli_stmt_close(
    $stmt
);


/* =========================================================
   HELPERS
========================================================= */

function haulingNullableInt(
    array $row,
    string $key
): ?int {

    if (
        !isset($row[$key])
        ||
        $row[$key] === ''
        ||
        $row[$key] === null
    ) {

        return null;
    }


    return (int) $row[$key];
}


function haulingString(
    array $row,
    string $key
): string {

    return trim(
        (string) (
            $row[$key]
            ??
            ''
        )
    );
}


/* =========================================================
   RENTAL DATE

   Current Hauling is DATE-ONLY.
========================================================= */

$rentalDate = '';


if (
    !empty(
        $row['rental_date']
    )
) {

    $timestamp =
        strtotime(
            $row['rental_date']
        );


    if (
        $timestamp !== false
    ) {

        $rentalDate =
            date(
                'Y-m-d',
                $timestamp
            );
    }
}

/* =========================================================
   CURRENT LINKED VALUES

   Registry / Member data is the primary display source.

   Snapshot columns inside hauling are retained only as
   fallback for old / legacy records whose linked row may
   no longer exist.
========================================================= */

$currentCustomerName =
    haulingString(
        $row,
        'current_customer_name'
    );

if (
    $currentCustomerName === ''
) {

    $currentCustomerName =
        haulingString(
            $row,
            'customer_name'
        );
}


$currentPrimaryTractorNo =
    haulingString(
        $row,
        'current_primary_tractor_no'
    );

if (
    $currentPrimaryTractorNo === ''
) {

    $currentPrimaryTractorNo =
        haulingString(
            $row,
            'tractor_no'
        );
}

$currentPrimaryTractorName =
    haulingString(
        $row,
        'current_primary_tractor_name'
    );


$currentSecondaryTractorNo =
    haulingString(
        $row,
        'current_secondary_tractor_no'
    );

if (
    $currentSecondaryTractorNo === ''
) {

    $currentSecondaryTractorNo =
        haulingString(
            $row,
            'secondary_tractor_no'
        );
}

$currentSecondaryTractorName =
    haulingString(
        $row,
        'current_secondary_tractor_name'
    );


$currentPrimaryDriverName =
    haulingString(
        $row,
        'current_primary_driver_name'
    );

if (
    $currentPrimaryDriverName === ''
) {

    $currentPrimaryDriverName =
        haulingString(
            $row,
            'operator'
        );
}


$currentSecondaryDriverName =
    haulingString(
        $row,
        'current_secondary_driver_name'
    );

if (
    $currentSecondaryDriverName === ''
) {

    $currentSecondaryDriverName =
        haulingString(
            $row,
            'secondary_operator'
        );
}


/* =========================================================
   RESPONSE DATA
========================================================= */

$data = [

    /* =============================================
       RECORD
    ============================================= */

    'hauling_id' =>
        (int) (
            $row['hauling_id']
            ??
            0
        ),

    'transaction_id' =>
        haulingString(
            $row,
            'transaction_id'
        ),


    /* =============================================
       CUSTOMER
    ============================================= */

    'member_id' =>
        haulingNullableInt(
            $row,
            'member_id'
        ),

    'customer_name' =>
        $currentCustomerName,

    'affiliate' =>
        haulingString(
            $row,
            'affiliate'
        ),

    'location' =>
        haulingString(
            $row,
            'location'
        ),

    'hauling_count' =>
        isset(
            $row['hauling_count']
        )
        ?
        (int) $row['hauling_count']
        :
        0,


    /* =============================================
       SCHEDULE
    ============================================= */

    'rental_date' =>
        $rentalDate,


    /* =============================================
       PRIMARY ASSIGNMENT
    ============================================= */

    'tractor_id' =>
        haulingNullableInt(
            $row,
            'tractor_id'
        ),

    'tractor_no' =>
        $currentPrimaryTractorNo,

    'tractor_name' =>
        $currentPrimaryTractorName,    

    'tractor_status' =>
        haulingString(
            $row,
            'tractor_status'
        ),

    'primary_driver_id' =>
        haulingNullableInt(
            $row,
            'primary_driver_id'
        ),

    'operator' =>
        $currentPrimaryDriverName,


    /* =============================================
       SECONDARY ASSIGNMENT
    ============================================= */

    'secondary_tractor_id' =>
        haulingNullableInt(
            $row,
            'secondary_tractor_id'
        ),

    'secondary_tractor_no' =>
        $currentSecondaryTractorNo,

    'secondary_tractor_name' =>
        $currentSecondaryTractorName,

    'secondary_driver_id' =>
        haulingNullableInt(
            $row,
            'secondary_driver_id'
        ),

    'secondary_operator' =>
        $currentSecondaryDriverName,


    /* =============================================
       PRICE
    ============================================= */

    'price' =>
        isset(
            $row['price']
        )
        ?
        (float) $row['price']
        :
        0,

    'discounted_price' =>
        isset(
            $row['discounted_price']
        )
        ?
        (float) $row['discounted_price']
        :
        0,

    'total_price' =>
        isset(
            $row['total_price']
        )
        ?
        (float) $row['total_price']
        :
        0,


    /* =============================================
       RENTAL PROGRESS
    ============================================= */

    'status' =>
        haulingString(
            $row,
            'status'
        ),


    /* =============================================
       PAYMENT

       READ ONLY for Edit Modal.
       Do not update this through Edit.
    ============================================= */

    'remarks' =>
        haulingString(
            $row,
            'remarks'
        ),


    /* =============================================
       LIFECYCLE DATES

       Useful later for Missed / Delayed flags.
    ============================================= */

    'actual_started_at' =>
        haulingString(
            $row,
            'actual_started_at'
        ),

    'actual_started_date' =>
        haulingString(
            $row,
            'actual_started_date'
        ),

    'completed_date' =>
    haulingString(
        $row,
        'completed_date'
    ),


/* =============================================
   RESCHEDULE HISTORY

   Persistent metadata:
   - how many times the rental was rescheduled
   - previous Rental Date
   - latest reschedule timestamp
============================================= */

'reschedule_count' =>
    isset(
        $row['reschedule_count']
    )
    ?
    (int) $row['reschedule_count']
    :
    0,


'last_rescheduled_from' =>
    haulingString(
        $row,
        'last_rescheduled_from'
    ),


'last_rescheduled_at' =>
    haulingString(
        $row,
        'last_rescheduled_at'
    )

];


/* =========================================================
   SUCCESS
========================================================= */

haulingGetResponse(
    true,
    '',
    $data
);