<?php

/* =========================================================
   SPIS - GET ROTOR RENTAL

   Used by:
   - Rotor Edit Modal

   CURRENT RENTAL RULES:
   - Date-only scheduling
   - Tractor / Driver IDs are authoritative
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
   JSON
========================================================= */

header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


/* =========================================================
   RESPONSE
========================================================= */

function rotorGetResponse(
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

    rotorGetResponse(
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

(int) $_SESSION['user_id']

:

0;


if (
    $userID <= 0
) {

    rotorGetResponse(
        false,
        'Your session has expired. Please sign in again.',
        [],
        401
    );
}


/* =========================================================
   VERIFY RENTAL ACCESS
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

    rotorGetResponse(
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


    rotorGetResponse(
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

    rotorGetResponse(
        false,
        'You do not have permission to access Rental records.',
        [],
        403
    );
}


/* =========================================================
   ROTOR ID
========================================================= */

$rotorID =

isset(
    $_GET['id']
)

?

(int) $_GET['id']

:

0;


if (
    $rotorID <= 0
) {

    rotorGetResponse(
        false,
        'Invalid Rotor record.',
        [],
        400
    );
}


/* =========================================================
   LOAD ROTOR RECORD
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
            AS primary_tractor_status,

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
           CURRENT CUSTOMER
        ============================================= */

        cm.full_name
            AS current_customer_name


    FROM rotor r


    LEFT JOIN tractors pt

        ON pt.tractor_id =
            r.tractor_id


    LEFT JOIN tractors st

        ON st.tractor_id =
            r.secondary_tractor_id


    LEFT JOIN drivers pd

        ON pd.driver_id =
            r.primary_driver_id


    LEFT JOIN members pm

        ON pm.member_id =
            pd.member_id


    LEFT JOIN drivers sd

        ON sd.driver_id =
            r.secondary_driver_id


    LEFT JOIN members sm

        ON sm.member_id =
            sd.member_id


    LEFT JOIN members cm

        ON cm.member_id =
            r.member_id


    WHERE
        r.rotor_id = ?

    LIMIT 1
    "
);


if (
    !$stmt
) {

    rotorGetResponse(
        false,
        'Unable to prepare Rotor record.',
        [],
        500
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

    mysqli_stmt_close(
        $stmt
    );


    rotorGetResponse(
        false,
        'Unable to load Rotor record.',
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


    rotorGetResponse(
        false,
        'Rotor rental was not found.',
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

function rotorNullableInt(
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


    return (int) $row[$key];
}


function rotorString(
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
   CURRENT CUSTOMER
========================================================= */

$currentCustomerName =

rotorString(
    $row,
    'current_customer_name'
);


if (
    $currentCustomerName === ''
) {

    $currentCustomerName =

    rotorString(
        $row,
        'customer_name'
    );
}


/* =========================================================
   CURRENT PRIMARY TRACTOR
========================================================= */

$currentPrimaryTractorNo =

rotorString(
    $row,
    'current_primary_tractor_no'
);


if (
    $currentPrimaryTractorNo === ''
) {

    $currentPrimaryTractorNo =

    rotorString(
        $row,
        'tractor_no'
    );
}


$currentPrimaryTractorName =

rotorString(
    $row,
    'current_primary_tractor_name'
);


/* =========================================================
   CURRENT SECONDARY TRACTOR
========================================================= */

$currentSecondaryTractorNo =

rotorString(
    $row,
    'current_secondary_tractor_no'
);


if (
    $currentSecondaryTractorNo === ''
) {

    $currentSecondaryTractorNo =

    rotorString(
        $row,
        'secondary_tractor_no'
    );
}


$currentSecondaryTractorName =

rotorString(
    $row,
    'current_secondary_tractor_name'
);


/* =========================================================
   CURRENT PRIMARY DRIVER
========================================================= */

$currentPrimaryDriverName =

rotorString(
    $row,
    'current_primary_driver_name'
);


if (
    $currentPrimaryDriverName === ''
) {

    $currentPrimaryDriverName =

    rotorString(
        $row,
        'operator'
    );
}


/* =========================================================
   CURRENT SECONDARY DRIVER
========================================================= */

$currentSecondaryDriverName =

rotorString(
    $row,
    'current_secondary_driver_name'
);


if (
    $currentSecondaryDriverName === ''
) {

    $currentSecondaryDriverName =

    rotorString(
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

    'rotor_id' =>

        (int) (
            $row['rotor_id']
            ??
            0
        ),


    'transaction_id' =>

        rotorString(
            $row,
            'transaction_id'
        ),


    /* =============================================
       CUSTOMER
    ============================================= */

    'member_id' =>

        rotorNullableInt(
            $row,
            'member_id'
        ),


    'customer_name' =>

        $currentCustomerName,


    'affiliate' =>

        rotorString(
            $row,
            'affiliate'
        ),


    'location' =>

        rotorString(
            $row,
            'location'
        ),


    'hectare' =>

        isset(
            $row['hectare']
        )

        ?

        (float) $row['hectare']

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

        rotorNullableInt(
            $row,
            'tractor_id'
        ),


    'tractor_no' =>

        $currentPrimaryTractorNo,


    'tractor_name' =>

        $currentPrimaryTractorName,


    'tractor_status' =>

        rotorString(
            $row,
            'primary_tractor_status'
        ),


    'primary_driver_id' =>

        rotorNullableInt(
            $row,
            'primary_driver_id'
        ),


    'operator' =>

        $currentPrimaryDriverName,


    /* =============================================
       SECONDARY ASSIGNMENT
    ============================================= */

    'secondary_tractor_id' =>

        rotorNullableInt(
            $row,
            'secondary_tractor_id'
        ),


    'secondary_tractor_no' =>

        $currentSecondaryTractorNo,


    'secondary_tractor_name' =>

        $currentSecondaryTractorName,


    'secondary_tractor_status' =>

        rotorString(
            $row,
            'secondary_tractor_status'
        ),


    'secondary_driver_id' =>

        rotorNullableInt(
            $row,
            'secondary_driver_id'
        ),


    'secondary_operator' =>

        $currentSecondaryDriverName,


    /* =============================================
       PRICING

       price
       = saved Price per Hectare snapshot

       discounted_price
       = saved Discount
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
       RENTAL STATUS
    ============================================= */

    'status' =>

        rotorString(
            $row,
            'status'
        ),


    /* =============================================
       PAYMENT
    ============================================= */

    'amount_paid' =>

        isset(
            $row['amount_paid']
        )

        ?

        (float) $row['amount_paid']

        :

        0,


    'balance' =>

        round(
            max(
                0,

                (
                    (float) (
                        $row['total_price']
                        ??
                        0
                    )
                )

                -

                (
                    (float) (
                        $row['amount_paid']
                        ??
                        0
                    )
                )
            ),
            2
        ),


    'remarks' =>

        rotorString(
            $row,
            'remarks'
        ),


    /* =============================================
       HISTORICAL / AUDIT
    ============================================= */

    'is_historical_entry' =>

        isset(
            $row['is_historical_entry']
        )

        ?

        (int) $row['is_historical_entry']

        :

        0,


    /* =============================================
       LIFECYCLE
    ============================================= */

    'actual_started_date' =>

        rotorString(
            $row,
            'actual_started_date'
        ),


    'completed_date' =>

        rotorString(
            $row,
            'completed_date'
        ),


    /* =============================================
       RESCHEDULE HISTORY
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

        rotorString(
            $row,
            'last_rescheduled_from'
        ),


    'last_rescheduled_at' =>

        rotorString(
            $row,
            'last_rescheduled_at'
        )

];


/* =========================================================
   ROTOR PAYMENT HISTORY
========================================================= */

$paymentHistory = [];


$paymentStmt = mysqli_prepare(
    $conn,
    "
    SELECT
        rp.payment_id,
        rp.payment_type,
        rp.amount,
        rp.balance_after,
        rp.payment_date,
        rp.notes,
        rp.recorded_by_id,

        COALESCE(
            NULLIF(TRIM(rp.recorded_by_name), ''),
            u.name
        ) AS recorded_by_name

    FROM rotor_payments AS rp

    LEFT JOIN users AS u
        ON u.user_id = rp.recorded_by_id

    WHERE rp.rotor_id = ?

    ORDER BY
        rp.payment_date ASC,
        rp.payment_id ASC
    "
);


if (!$paymentStmt) {
    rotorGetResponse(
        false,
        'Unable to load Rotor payment history.',
        [],
        500
    );
}

mysqli_stmt_bind_param(
    $paymentStmt,
    'i',
    $rotorID
);

if (!mysqli_stmt_execute($paymentStmt)) {

    mysqli_stmt_close($paymentStmt);

    rotorGetResponse(
        false,
        'Unable to load Rotor payment history.',
        [],
        500
    );
}

$paymentResult = mysqli_stmt_get_result($paymentStmt);

if ($paymentResult === false) {

    mysqli_stmt_close($paymentStmt);

    rotorGetResponse(
        false,
        'Unable to load Rotor payment history.',
        [],
        500
    );
}

while ($payment = mysqli_fetch_assoc($paymentResult)) {

    $paymentHistory[] = [
        'payment_id' => (int) $payment['payment_id'],
        'payment_type' => $payment['payment_type'],
        'amount' => (float) $payment['amount'],
        'balance_after' => (float) $payment['balance_after'],
        'payment_date' => $payment['payment_date'],
        'notes' => $payment['notes'],
        'recorded_by_id' => $payment['recorded_by_id'],
        'recorded_by_name' => $payment['recorded_by_name']
    ];
}

mysqli_stmt_close($paymentStmt);

$data['payment_history'] = $paymentHistory;



/* =========================================================
   SUCCESS
========================================================= */

rotorGetResponse(
    true,
    '',
    $data
);