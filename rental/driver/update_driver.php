<?php

/* =========================================================
   SPIS - UPDATE DRIVER
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
   RESPONSE HELPER
========================================================= */

function driverUpdateResponse(
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
   POST ONLY
========================================================= */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'POST'
) {

    driverUpdateResponse(
        'Invalid request.',
        405
    );

}


/* =========================================================
   CURRENT USER
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

    driverUpdateResponse(
        'Your session has expired. Please sign in again.',
        401
    );

}


/* =========================================================
   CSRF
========================================================= */

$sessionToken =

    $_SESSION[
        'csrf_token'
    ]

    ??

    '';


$formToken =

    $_POST[
        'csrf_token'
    ]

    ??

    '';


if (
    !is_string(
        $sessionToken
    )
    ||
    !is_string(
        $formToken
    )
    ||
    $sessionToken === ''
    ||
    $formToken === ''
    ||
    !hash_equals(
        $sessionToken,
        $formToken
    )
) {

    driverUpdateResponse(
        'Invalid security token. Please refresh the page and try again.',
        403
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

        WHERE
            user_id = ?

        AND archived = 0

        AND status = 'Active'

        AND rental_access = 1

        LIMIT 1
        "
    );


if (
    !$actorStmt
) {

    driverUpdateResponse(
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

    mysqli_stmt_close(
        $actorStmt
    );


    driverUpdateResponse(
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

    driverUpdateResponse(
        'You do not have permission to manage Rental drivers.',
        403
    );

}


/* =========================================================
   GET VALUES

   IMPORTANT:
   Driver identity belongs to the linked Member record.
   Only Driver Registry status is editable here.
========================================================= */

$driverID =

    isset(
        $_POST['driver_id']
    )

    ?

    (int) $_POST['driver_id']

    :

    0;


$status =

    trim(
        (string) (
            $_POST['status']
            ??
            ''
        )
    );


/* =========================================================
   DRIVER ID
========================================================= */

if (
    $driverID <= 0
) {

    driverUpdateResponse(
        'Invalid driver record.',
        400
    );

}


/* =========================================================
   STATUS VALIDATION
========================================================= */

$allowedStatuses = [

    'Active',
    'Inactive'

];


if (
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
) {

    driverUpdateResponse(
        'Invalid driver status.',
        400
    );

}


/* =========================================================
   TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try {


    /* =====================================================
       LOAD CURRENT DRIVER
    ===================================================== */

    $currentStmt =

        mysqli_prepare(
            $conn,
            "
            SELECT

                d.driver_id,
                d.member_id,
                d.status,

                m.full_name
                    AS member_full_name,

                m.contact_no
                    AS member_contact_no,

                m.status
                    AS member_status

            FROM drivers d

            INNER JOIN members m

                ON m.member_id =
                    d.member_id

                AND m.archived = 0

            WHERE
                d.driver_id = ?

            AND d.archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );


    if (
        !$currentStmt
    ) {

        throw new Exception(
            'Unable to load the driver record.'
        );

    }


    mysqli_stmt_bind_param(
        $currentStmt,
        'i',
        $driverID
    );


    if (
        !mysqli_stmt_execute(
            $currentStmt
        )
    ) {

        mysqli_stmt_close(
            $currentStmt
        );


        throw new Exception(
            'Unable to load the driver record.'
        );

    }


    $currentResult =

        mysqli_stmt_get_result(
            $currentStmt
        );


    if (
        !$currentResult
        ||
        mysqli_num_rows(
            $currentResult
        ) !== 1
    ) {

        mysqli_stmt_close(
            $currentStmt
        );


        throw new Exception(
            'Driver record was not found.'
        );

    }


    $currentDriver =

        mysqli_fetch_assoc(
            $currentResult
        );


    mysqli_stmt_close(
        $currentStmt
    );


    /* =====================================================
    CURRENT MEMBER / DRIVER VALUES
    ===================================================== */

    $fullName =

        trim(
            (string) (
                $currentDriver[
                    'member_full_name'
                ]
                ??
                ''
            )
        );


    $memberStatus =

        strtoupper(
            trim(
                (string) (
                    $currentDriver[
                        'member_status'
                    ]
                    ??
                    ''
                )
            )
        );


    $oldStatus =

        trim(
            (string) (
                $currentDriver[
                    'status'
                ]
                ??
                'Active'
            )
        );


    if (
        $fullName === ''
    ) {

        throw new Exception(
            'Unable to verify the linked Member record.'
        );

    }


    /*
    A Driver cannot be manually set to Active
    if the linked Member itself is not Active.
    */

    if (
        $status === 'Active'
        &&
        $memberStatus !== 'ACTIVE'
    ) {

        throw new Exception(
            'This driver cannot be set to Active because the linked Member is currently inactive.'
        );

    }


    /* =====================================================
       INACTIVE VALIDATION

       A driver cannot be made Inactive when:

       1. Driver currently has an Ongoing Rental
          regardless of date, for legacy safety.

       OR

       2. Driver has Pending Rental scheduled
          today or in the future.

       Past Pending records are considered Missed and do not
       permanently lock the Driver Registry.
    ===================================================== */

    if (
        $status === 'Inactive'
        &&
        $oldStatus !== 'Inactive'
    ) {


        $assignmentStmt =

            mysqli_prepare(
                $conn,
                "
                SELECT
                    rental_source,
                    transaction_id,
                    rental_date,
                    rental_status

                FROM
                (

                    /* ==============================
                       HAULING
                    ============================== */

                    SELECT

                        'Hauling'
                            AS rental_source,

                        h.transaction_id,

                        DATE(h.rental_date)
                            AS rental_date,

                        h.status
                            AS rental_status

                    FROM hauling h

                    WHERE

                        (
                            h.primary_driver_id = ?

                            OR

                            h.secondary_driver_id = ?
                        )

                    AND
                    (
                        h.status = 'Ongoing'

                        OR

                        (
                            h.status = 'Pending'

                            AND DATE(h.rental_date)
                                >= CURDATE()
                        )
                    )


                    UNION ALL


                    /* ==============================
                       ROTOR
                    ============================== */

                    SELECT

                        'Rotor'
                            AS rental_source,

                        r.transaction_id,

                        DATE(r.rental_date)
                            AS rental_date,

                        r.status
                            AS rental_status

                    FROM rotor r

                    WHERE

                        (
                            r.primary_driver_id = ?

                            OR

                            r.secondary_driver_id = ?
                        )

                    AND
                    (
                        r.status = 'Ongoing'

                        OR

                        (
                            r.status = 'Pending'

                            AND DATE(r.rental_date)
                                >= CURDATE()
                        )
                    )


                    UNION ALL


                    /* ==============================
                       REAPER
                    ============================== */

                    SELECT

                        'Reaper'
                            AS rental_source,

                        rp.transaction_id,

                        DATE(rp.rental_date)
                            AS rental_date,

                        rp.status
                            AS rental_status

                    FROM reaper rp

                    WHERE

                        (
                            rp.primary_driver_id = ?

                            OR

                            rp.secondary_driver_id = ?
                        )

                    AND
                    (
                        rp.status = 'Ongoing'

                        OR

                        (
                            rp.status = 'Pending'

                            AND DATE(rp.rental_date)
                                >= CURDATE()
                        )
                    )

                ) AS active_assignments

                ORDER BY
                    rental_date ASC

                LIMIT 1
                "
            );


        if (
            !$assignmentStmt
        ) {

            throw new Exception(
                'Unable to check the driver Rental assignments.'
            );

        }


        mysqli_stmt_bind_param(
            $assignmentStmt,
            'iiiiii',
            $driverID,
            $driverID,
            $driverID,
            $driverID,
            $driverID,
            $driverID
        );


        if (
            !mysqli_stmt_execute(
                $assignmentStmt
            )
        ) {

            mysqli_stmt_close(
                $assignmentStmt
            );


            throw new Exception(
                'Unable to check the driver Rental assignments.'
            );

        }


        $assignmentResult =

            mysqli_stmt_get_result(
                $assignmentStmt
            );


        if (
            $assignmentResult
            &&
            mysqli_num_rows(
                $assignmentResult
            ) > 0
        ) {


            $activeAssignment =

                mysqli_fetch_assoc(
                    $assignmentResult
                );


            mysqli_stmt_close(
                $assignmentStmt
            );


            $rentalSource =

                trim(
                    (string) (
                        $activeAssignment[
                            'rental_source'
                        ]
                        ??
                        'Rental'
                    )
                );


            $transactionID =

                trim(
                    (string) (
                        $activeAssignment[
                            'transaction_id'
                        ]
                        ??
                        ''
                    )
                );


            $rentalDate =

                trim(
                    (string) (
                        $activeAssignment[
                            'rental_date'
                        ]
                        ??
                        ''
                    )
                );


            $rentalStatus =

                trim(
                    (string) (
                        $activeAssignment[
                            'rental_status'
                        ]
                        ??
                        ''
                    )
                );


            $message =

                'This driver cannot be set to Inactive because of an active or upcoming '

                .

                $rentalSource

                .

                ' Rental';


            if (
                $transactionID !== ''
            ) {

                $message .=

                    ' "'

                    .

                    $transactionID

                    .

                    '"';

            }


            if (
                $rentalDate !== ''
            ) {

                $message .=

                    ' scheduled on '

                    .

                    date(
                        'm-d-Y',
                        strtotime(
                            $rentalDate
                        )
                    );

            }


            if (
                $rentalStatus !== ''
            ) {

                $message .=

                    ' with '

                    .

                    $rentalStatus

                    .

                    ' status';

            }


            $message .=
                '.';


            throw new Exception(
                $message
            );

        }


        mysqli_stmt_close(
            $assignmentStmt
        );

    }


    /* =====================================================
    BUILD HISTORY CHANGES
    ===================================================== */

    $changes = [];


    if (
        $oldStatus !== $status
    ) {

        $changes[] =

            'Status: '

            .

            $oldStatus

            .

            ' → '

            .

            $status;

    }


    /* =====================================================
       NO CHANGES

       Successful response is intentional.
       No unnecessary database update or History log.
    ===================================================== */

    if (
        empty(
            $changes
        )
    ) {


        mysqli_commit(
            $conn
        );


        driverUpdateResponse(
            'success'
        );

    }


    /* =====================================================
       UPDATE DRIVER
    ===================================================== */

    $updateStmt =

        mysqli_prepare(
            $conn,
            "
            UPDATE drivers

            SET
                status = ?

            WHERE
                driver_id = ?

            AND archived = 0
            "
        );


    if (
        !$updateStmt
    ) {

        throw new Exception(
            'Unable to prepare the driver update.'
        );

    }


    mysqli_stmt_bind_param(
        $updateStmt,
        'si',
        $status,
        $driverID
    );


    if (
        !mysqli_stmt_execute(
            $updateStmt
        )
    ) {

        $updateError =

            mysqli_stmt_error(
                $updateStmt
            );


        mysqli_stmt_close(
            $updateStmt
        );


        throw new Exception(

            $updateError !== ''

            ?

            $updateError

            :

            'Unable to update driver.'

        );

    }


    $affectedRows =

        mysqli_stmt_affected_rows(
            $updateStmt
        );


    mysqli_stmt_close(
        $updateStmt
    );


    if (
        $affectedRows <= 0
    ) {

        throw new Exception(
            'Unable to update driver.'
        );

    }


    /* =====================================================
       DRIVER CODE
    ===================================================== */

    $driverCode =

        'DRV-'

        .

        str_pad(
            (string) $driverID,
            4,
            '0',
            STR_PAD_LEFT
        );


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    log_activity(
        $conn,
        'Rental',
        'Updated',

        'updated the driver "'

        .

        $driverCode

        .

        ' - '

        .

        $fullName

        .

        '": '

        .

        implode(
            ', ',
            $changes
        )

        .

        '.',

        $driverID
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    driverUpdateResponse(
        'success'
    );


}
catch (
    Throwable $exception
) {


    mysqli_rollback(
        $conn
    );


    $message =

        trim(
            (string) $exception->getMessage()
        );


    if (
        $message === ''
    ) {

        $message =
            'Unable to update driver.';

    }


    driverUpdateResponse(
        $message,
        400
    );

}