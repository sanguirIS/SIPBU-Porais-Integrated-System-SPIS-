<?php

/* =========================================================
   SPIS - GET REAPER RENTAL

   Used by:
   - Reaper Edit Modal

   CURRENT REAPER RULES:
   - Date-only scheduling
   - Tractor / Driver IDs are the real assignments
   - operator fields are saved name snapshots
   - Primary + optional Secondary Assignment
   - Payment Remarks are returned for Edit rules
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

function reaperGetResponse(
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

    reaperGetResponse(
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

    isset(
        $_SESSION['user_id']
    )

    ?

    (int)
    $_SESSION['user_id']

    :

    0;


if (
    $userID <= 0
) {

    reaperGetResponse(
        false,
        'Your session has expired. Please sign in again.',
        [],
        401
    );
}


/* =========================================================
   VERIFY LIVE RENTAL ACCESS
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

    reaperGetResponse(
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


    reaperGetResponse(
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

    reaperGetResponse(
        false,
        'You do not have permission to access Rental records.',
        [],
        403
    );
}


/* =========================================================
   REAPER ID
========================================================= */

$reaperID =

    isset(
        $_GET['id']
    )

    ?

    (int)
    $_GET['id']

    :

    0;


if (
    $reaperID <= 0
) {

    reaperGetResponse(
        false,
        'Invalid Reaper record.',
        [],
        400
    );
}


/* =========================================================
   LOAD RECORD

   Registry / linked tables are used for current display data.

   Snapshot columns inside reaper remain fallback values for
   legacy records or assignments whose linked rows changed.
========================================================= */

$stmt =

mysqli_prepare(
    $conn,
    "
    SELECT

        r.*,


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

        st.status
            AS secondary_tractor_status,

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


    FROM reaper r


    /* =================================================
       PRIMARY TRACTOR
    ================================================= */

    LEFT JOIN tractors pt

        ON pt.tractor_id =
            r.tractor_id


    /* =================================================
       SECONDARY TRACTOR
    ================================================= */

    LEFT JOIN tractors st

        ON st.tractor_id =
            r.secondary_tractor_id


    /* =================================================
       PRIMARY DRIVER
    ================================================= */

    LEFT JOIN drivers pd

        ON pd.driver_id =
            r.primary_driver_id


    LEFT JOIN members pm

        ON pm.member_id =
            pd.member_id


    /* =================================================
       SECONDARY DRIVER
    ================================================= */

    LEFT JOIN drivers sd

        ON sd.driver_id =
            r.secondary_driver_id


    LEFT JOIN members sm

        ON sm.member_id =
            sd.member_id


    /* =================================================
       RENTAL CUSTOMER MEMBER
    ================================================= */

    LEFT JOIN members cm

        ON cm.member_id =
            r.member_id


    WHERE r.reaper_id = ?

    LIMIT 1
    "
);


if (
    !$stmt
) {

    reaperGetResponse(
        false,
        'Unable to prepare Reaper record.',
        [],
        500
    );
}


mysqli_stmt_bind_param(
    $stmt,
    'i',
    $reaperID
);


if (
    !mysqli_stmt_execute(
        $stmt
    )
) {

    mysqli_stmt_close(
        $stmt
    );


    reaperGetResponse(
        false,
        'Unable to load Reaper record.',
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


    reaperGetResponse(
        false,
        'Reaper rental was not found.',
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

function reaperNullableInt(
    array $row,
    string $key
): ?int {

    if (
        !isset(
            $row[$key]
        )
        ||
        $row[$key] === ''
        ||
        $row[$key] === null
    ) {

        return null;
    }


    return (int)
    $row[$key];
}


function reaperString(
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


function reaperNullableFloat(
    array $row,
    string $key
): ?float {

    if (
        !isset(
            $row[$key]
        )
        ||
        $row[$key] === ''
        ||
        $row[$key] === null
    ) {

        return null;
    }


    return (float)
    $row[$key];
}


function reaperDriverCode(
    ?int $driverID
): string {

    if (
        $driverID === null
        ||
        $driverID <= 0
    ) {

        return '';
    }


    return

        'DRV-'

        .

        str_pad(
            (string)
            $driverID,
            4,
            '0',
            STR_PAD_LEFT
        );
}


/* =========================================================
   RENTAL DATE
========================================================= */

$rentalDate =
'';


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

   Current Registry / Member data is preferred.

   Snapshot fields remain fallback for old records.
========================================================= */

$currentCustomerName =

reaperString(
    $row,
    'current_customer_name'
);


if (
    $currentCustomerName === ''
) {

    $currentCustomerName =

    reaperString(
        $row,
        'customer_name'
    );
}


/* =========================================================
   PRIMARY TRACTOR
========================================================= */

$currentPrimaryTractorNo =

reaperString(
    $row,
    'current_primary_tractor_no'
);


if (
    $currentPrimaryTractorNo === ''
) {

    $currentPrimaryTractorNo =

    reaperString(
        $row,
        'tractor_no'
    );
}


$currentPrimaryTractorName =

reaperString(
    $row,
    'current_primary_tractor_name'
);


/* =========================================================
   SECONDARY TRACTOR
========================================================= */

$currentSecondaryTractorNo =

reaperString(
    $row,
    'current_secondary_tractor_no'
);


if (
    $currentSecondaryTractorNo === ''
) {

    $currentSecondaryTractorNo =

    reaperString(
        $row,
        'secondary_tractor_no'
    );
}


$currentSecondaryTractorName =

reaperString(
    $row,
    'current_secondary_tractor_name'
);


/* =========================================================
   PRIMARY DRIVER
========================================================= */

$currentPrimaryDriverName =

reaperString(
    $row,
    'current_primary_driver_name'
);


if (
    $currentPrimaryDriverName === ''
) {

    $currentPrimaryDriverName =

    reaperString(
        $row,
        'operator'
    );
}


/* =========================================================
   SECONDARY DRIVER
========================================================= */

$currentSecondaryDriverName =

reaperString(
    $row,
    'current_secondary_driver_name'
);


if (
    $currentSecondaryDriverName === ''
) {

    $currentSecondaryDriverName =

    reaperString(
        $row,
        'secondary_operator'
    );
}


$primaryDriverID =

reaperNullableInt(
    $row,
    'primary_driver_id'
);


$secondaryDriverID =

reaperNullableInt(
    $row,
    'secondary_driver_id'
);


/* =========================================================
   RESPONSE DATA
========================================================= */

$data = [


    /* =============================================
       RECORD
    ============================================= */

    'reaper_id' =>

        (int) (
            $row['reaper_id']
            ??
            0
        ),


    'transaction_id' =>

        reaperString(
            $row,
            'transaction_id'
        ),


    /* =============================================
       CUSTOMER
    ============================================= */

    'member_id' =>

        reaperNullableInt(
            $row,
            'member_id'
        ),


    'customer_name' =>

        $currentCustomerName,


    'affiliate' =>

        reaperString(
            $row,
            'affiliate'
        ),


    'location' =>

        reaperString(
            $row,
            'location'
        ),


    /* =============================================
       SCHEDULE
    ============================================= */

    'rental_date' =>

        $rentalDate,


    /* =============================================
       PRIMARY ASSIGNMENT
    ============================================= */

    'tractor_id' =>

        reaperNullableInt(
            $row,
            'tractor_id'
        ),


    'tractor_no' =>

        $currentPrimaryTractorNo,


    'tractor_name' =>

        $currentPrimaryTractorName,


    'tractor_status' =>

        reaperString(
            $row,
            'tractor_status'
        ),


    'primary_tractor_status' =>

        reaperString(
            $row,
            'tractor_status'
        ),


    'primary_driver_id' =>

        $primaryDriverID,


    'primary_driver_code' =>

        reaperDriverCode(
            $primaryDriverID
        ),


    'primary_driver_name' =>

        $currentPrimaryDriverName,


    'operator' =>

        $currentPrimaryDriverName,


    /* =============================================
       SECONDARY ASSIGNMENT
    ============================================= */

    'secondary_tractor_id' =>

        reaperNullableInt(
            $row,
            'secondary_tractor_id'
        ),


    'secondary_tractor_no' =>

        $currentSecondaryTractorNo,


    'secondary_tractor_name' =>

        $currentSecondaryTractorName,


    'secondary_tractor_status' =>

        reaperString(
            $row,
            'secondary_tractor_status'
        ),


    'secondary_driver_id' =>

        $secondaryDriverID,


    'secondary_driver_code' =>

        reaperDriverCode(
            $secondaryDriverID
        ),


    'secondary_driver_name' =>

        $currentSecondaryDriverName,


    'secondary_operator' =>

        $currentSecondaryDriverName,


    /* =============================================
       REAPER SERVICE DETAILS
    ============================================= */

    'no_of_harvest' =>

        reaperNullableInt(
            $row,
            'no_of_harvest'
        ),


    'commodity' =>

        reaperNullableFloat(
            $row,
            'commodity'
        ),


    /* =============================================
       RENTAL PROGRESS
    ============================================= */

    'status' =>

        reaperString(
            $row,
            'status'
        ),


    /* =============================================
       PAYMENT
    ============================================= */

    'remarks' =>

        reaperString(
            $row,
            'remarks'
        ),


    /* =============================================
       LIFECYCLE DATES
    ============================================= */

    'actual_started_date' =>

        reaperString(
            $row,
            'actual_started_date'
        ),


    'completed_date' =>

        reaperString(
            $row,
            'completed_date'
        )

];


/* =========================================================
   SUCCESS
========================================================= */

reaperGetResponse(
    true,
    '',
    $data
);