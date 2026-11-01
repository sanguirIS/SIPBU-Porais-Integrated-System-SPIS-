<?php

/* =========================================================
   SPIS - RETRIEVE DRIVER
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


/* =========================================================
   REQUIRED FILES
========================================================= */

require_once '../includes/check_access.php';

checkAccess(
    'archives_access'
);


require_once '../db.php';
require_once '../includes/history_logger.php';


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   REDIRECT HELPER
========================================================= */

function driverRestoreRedirect(
    string $type,
    string $value
): void {

    header(
        'Location: archive.php?module=driver&'
        .
        rawurlencode($type)
        .
        '='
        .
        rawurlencode($value)
    );

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

    driverRestoreRedirect(
        'error',
        'invalid_request'
    );

}


/* =========================================================
   SESSION / CURRENT USER
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

    driverRestoreRedirect(
        'error',
        'session'
    );

}


/* =========================================================
   CSRF
========================================================= */

$sessionToken =

    $_SESSION[
        'driver_archive_csrf_token'
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

    driverRestoreRedirect(
        'error',
        'csrf'
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

    driverRestoreRedirect(
        'error',
        'forbidden'
    );

}


mysqli_stmt_bind_param(
    $accessStmt,
    'i',
    $actorID
);


if (
    !mysqli_stmt_execute(
        $accessStmt
    )
) {

    mysqli_stmt_close(
        $accessStmt
    );


    driverRestoreRedirect(
        'error',
        'forbidden'
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

    driverRestoreRedirect(
        'error',
        'forbidden'
    );

}


/* =========================================================
   DRIVER ID
========================================================= */

$driverID =

    filter_var(
        $_POST[
            'driver_id'
        ]
        ??
        null,
        FILTER_VALIDATE_INT
    );


if (
    $driverID === false
    ||
    $driverID === null
    ||
    $driverID <= 0
) {

    driverRestoreRedirect(
        'error',
        'invalid_driver'
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
       LOAD ARCHIVED DRIVER + LINKED MEMBER
    ===================================================== */

    $driverStmt =

        mysqli_prepare(
            $conn,
            "
            SELECT

                d.driver_id,
                d.member_id,
                d.full_name AS stored_full_name,
                d.contact_no AS stored_contact_no,
                d.status AS driver_status,

                m.full_name AS member_full_name,
                m.contact_no AS member_contact_no,
                m.status AS member_status,
                m.archived AS member_archived

            FROM drivers d

            LEFT JOIN members m
                ON m.member_id =
                    d.member_id

            WHERE d.driver_id = ?

            AND d.archived = 1

            LIMIT 1

            FOR UPDATE
            "
        );


    if (
        !$driverStmt
    ) {

        throw new RuntimeException(
            'driver_retrieve_failed'
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


        throw new RuntimeException(
            'driver_retrieve_failed'
        );

    }


    $driverResult =

        mysqli_stmt_get_result(
            $driverStmt
        );


    $driver =

        $driverResult

        ?

        mysqli_fetch_assoc(
            $driverResult
        )

        :

        null;


    mysqli_stmt_close(
        $driverStmt
    );


    if (
        !$driver
    ) {

        throw new RuntimeException(
            'driver_not_found'
        );

    }


    /* =====================================================
       LINKED MEMBER MUST STILL BE AVAILABLE
    ===================================================== */

    $memberID =

        (int) (
            $driver[
                'member_id'
            ]
            ??
            0
        );


    $memberName =

        trim(
            (string) (
                $driver[
                    'member_full_name'
                ]
                ??
                ''
            )
        );


    $memberContact =

        trim(
            (string) (
                $driver[
                    'member_contact_no'
                ]
                ??
                ''
            )
        );


    $memberStatus =

        strtoupper(
            trim(
                (string) (
                    $driver[
                        'member_status'
                    ]
                    ??
                    ''
                )
            )
        );


    $memberArchived =

        (int) (
            $driver[
                'member_archived'
            ]
            ??
            1
        );


    if (
        $memberID <= 0
        ||
        $memberName === ''
        ||
        $memberArchived !== 0
        ||
        $memberStatus !== 'ACTIVE'
    ) {

        throw new RuntimeException(
            'driver_member_unavailable'
        );

    }


    /* =====================================================
       RESTORE DRIVER

       Preserve the Driver Registry status that existed
       before archive.

       Driver identity is refreshed from the linked Member.
    ===================================================== */

    $restoreStmt =

        mysqli_prepare(
            $conn,
            "
            UPDATE drivers

            SET
                full_name = ?,
                contact_no = ?,
                archived = 0,
                archived_by = NULL,
                archived_at = NULL

            WHERE driver_id = ?

            AND archived = 1
            "
        );


    if (
        !$restoreStmt
    ) {

        throw new RuntimeException(
            'driver_retrieve_failed'
        );

    }


    mysqli_stmt_bind_param(
        $restoreStmt,
        'ssi',
        $memberName,
        $memberContact,
        $driverID
    );


    if (
        !mysqli_stmt_execute(
            $restoreStmt
        )
    ) {

        mysqli_stmt_close(
            $restoreStmt
        );


        throw new RuntimeException(
            'driver_retrieve_failed'
        );

    }


    $affectedRows =

        mysqli_stmt_affected_rows(
            $restoreStmt
        );


    mysqli_stmt_close(
        $restoreStmt
    );


    if (
        $affectedRows !== 1
    ) {

        throw new RuntimeException(
            'driver_retrieve_failed'
        );

    }


    /* =====================================================
       HISTORY LOG
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


    log_activity(
        $conn,
        'Rental',
        'Restored',

        'restored the driver "'

        .

        $driverCode

        .

        ' - '

        .

        $memberName

        .

        '" to the Driver Registry.',

        $driverID
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    driverRestoreRedirect(
        'success',
        'restored'
    );


}
catch (
    Throwable $exception
) {


    mysqli_rollback(
        $conn
    );


    $errorCode =

        trim(
            (string) $exception->getMessage()
        );


    $allowedErrors = [

        'driver_not_found',
        'driver_member_unavailable',
        'driver_retrieve_failed'

    ];


    if (
        !in_array(
            $errorCode,
            $allowedErrors,
            true
        )
    ) {

        $errorCode =
            'driver_retrieve_failed';

    }


    driverRestoreRedirect(
        'error',
        $errorCode
    );

}