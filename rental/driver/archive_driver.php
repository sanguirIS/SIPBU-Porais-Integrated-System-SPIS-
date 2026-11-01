<?php

/* =========================================================
   SPIS - ARCHIVE DRIVER
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

function driverArchiveResponse(
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

    driverArchiveResponse(
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


$actorName =

    trim(
        (string) (
            $_SESSION['name']
            ??
            ''
        )
    );


if (
    $actorID <= 0
) {

    driverArchiveResponse(
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

    driverArchiveResponse(
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

    driverArchiveResponse(
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


    driverArchiveResponse(
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

    driverArchiveResponse(
        'You do not have permission to manage Rental drivers.',
        403
    );

}


/* =========================================================
   DRIVER ID
========================================================= */

$driverID =

    isset(
        $_POST['driver_id']
    )

    ?

    (int) $_POST['driver_id']

    :

    0;


if (
    $driverID <= 0
) {

    driverArchiveResponse(
        'Invalid driver record.',
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
       LOAD DRIVER
    ===================================================== */

    $driverStmt =

        mysqli_prepare(
            $conn,
            "
            SELECT

                driver_id,
                full_name,
                contact_no,
                status

            FROM drivers

            WHERE
                driver_id = ?

            AND archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );


    if (
        !$driverStmt
    ) {

        throw new Exception(
            'Unable to load the driver record.'
        );

    }


    mysqli_stmt_bind_param(
        $driverStmt,
        'i',
        $driverID
    );


    if (
        !mysqli_stmt_execute(
            $driverStmt
        )
    ) {

        mysqli_stmt_close(
            $driverStmt
        );


        throw new Exception(
            'Unable to load the driver record.'
        );

    }


    $driverResult =

        mysqli_stmt_get_result(
            $driverStmt
        );


    if (
        !$driverResult
        ||
        mysqli_num_rows(
            $driverResult
        ) !== 1
    ) {

        mysqli_stmt_close(
            $driverStmt
        );


        throw new Exception(
            'Driver record was not found.'
        );

    }


    $driver =

        mysqli_fetch_assoc(
            $driverResult
        );


    mysqli_stmt_close(
        $driverStmt
    );


    $driverName =

        trim(
            (string) (
                $driver['full_name']
                ??
                ''
            )
        );


    /* =====================================================
       CHECK ACTIVE / UPCOMING RENTAL ASSIGNMENTS

       BLOCK ARCHIVE WHEN:

       - Any Ongoing Rental exists
       OR
       - Pending Rental today or in the future exists

       Past Pending records are treated as Missed later,
       so they do not permanently lock the driver.
    ===================================================== */

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

                /* =========================================
                   HAULING
                ========================================= */

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


                /* =========================================
                   ROTOR
                ========================================= */

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


                /* =========================================
                   REAPER
                ========================================= */

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


        $assignment =

            mysqli_fetch_assoc(
                $assignmentResult
            );


        mysqli_stmt_close(
            $assignmentStmt
        );


        $source =

            trim(
                (string) (
                    $assignment['rental_source']
                    ??
                    'Rental'
                )
            );


        $transactionID =

            trim(
                (string) (
                    $assignment['transaction_id']
                    ??
                    ''
                )
            );


        $rentalDate =

            trim(
                (string) (
                    $assignment['rental_date']
                    ??
                    ''
                )
            );


        $status =

            trim(
                (string) (
                    $assignment['rental_status']
                    ??
                    ''
                )
            );


        $message =

            'This driver cannot be archived because of an active or upcoming '

            .

            $source

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
            $status !== ''
        ) {

            $message .=

                ' with '

                .

                $status

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


    /* =====================================================
       ARCHIVE DRIVER
    ===================================================== */

    $archiveStmt =

        mysqli_prepare(
            $conn,
            "
            UPDATE drivers

            SET

                archived = 1,

                archived_by = NULLIF(
                    ?,
                    ''
                ),

                archived_at = NOW()

            WHERE
                driver_id = ?

            AND archived = 0
            "
        );


    if (
        !$archiveStmt
    ) {

        throw new Exception(
            'Unable to prepare the driver archive.'
        );

    }


    mysqli_stmt_bind_param(
        $archiveStmt,
        'si',
        $actorName,
        $driverID
    );


    if (
        !mysqli_stmt_execute(
            $archiveStmt
        )
    ) {

        $archiveError =

            mysqli_stmt_error(
                $archiveStmt
            );


        mysqli_stmt_close(
            $archiveStmt
        );


        throw new Exception(

            $archiveError !== ''

            ?

            $archiveError

            :

            'Unable to archive driver.'

        );

    }


    $affectedRows =

        mysqli_stmt_affected_rows(
            $archiveStmt
        );


    mysqli_stmt_close(
        $archiveStmt
    );


    if (
        $affectedRows <= 0
    ) {

        throw new Exception(
            'Unable to archive driver.'
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
        'Archived',

        'archived the driver "'

        .

        $driverCode

        .

        ' - '

        .

        $driverName

        .

        '".',

        $driverID
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    driverArchiveResponse(
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
            'Unable to archive driver.';

    }


    driverArchiveResponse(
        $message,
        400
    );

}