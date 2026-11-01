<?php

/* =========================================================
   SPIS - SAVE DRIVER
   Member-linked Driver Registry
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

error_reporting(E_ALL);
ini_set('display_errors', '0');

header(
    'Content-Type: text/plain; charset=UTF-8'
);

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
   RESPONSE
========================================================= */

function driverSaveResponse(
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
   INTERNAL ERROR LOG
========================================================= */

function driverSaveLogError(
    string $context,
    mysqli $conn,
    ?mysqli_stmt $stmt = null
): void {

    $error =

        $stmt

        ?

        mysqli_stmt_error(
            $stmt
        )

        :

        mysqli_error(
            $conn
        );


    error_log(
        '[SPIS][Driver] '
        .
        $context
        .
        ': '
        .
        $error
    );
}


/* =========================================================
   POST ONLY
========================================================= */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '')
    !==
    'POST'
) {

    driverSaveResponse(
        'Invalid request.',
        405
    );
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

    driverSaveResponse(
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

    driverSaveResponse(
        'Invalid security token. Please refresh the page and try again.',
        403
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

    driverSaveLogError(
        'prepare access check',
        $conn
    );


    driverSaveResponse(
        'Unable to verify Rental access.',
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

    driverSaveLogError(
        'execute access check',
        $conn,
        $accessStmt
    );


    mysqli_stmt_close(
        $accessStmt
    );


    driverSaveResponse(
        'Unable to verify Rental access.',
        500
    );
}


$accessResult =

    mysqli_stmt_get_result(
        $accessStmt
    );


$hasAccess =

    $accessResult

    &&

    mysqli_num_rows(
        $accessResult
    ) === 1;


mysqli_stmt_close(
    $accessStmt
);


if (
    !$hasAccess
) {

    driverSaveResponse(
        'You do not have permission to manage Rental drivers.',
        403
    );
}


/* =========================================================
   MEMBER ID
========================================================= */

$memberID =

    filter_var(
        $_POST[
            'member_id'
        ]
        ??
        null,
        FILTER_VALIDATE_INT
    );


if (
    $memberID === false
    ||
    $memberID === null
    ||
    $memberID <= 0
) {

    driverSaveResponse(
        'Please select a valid registered Member.',
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
       LOAD MEMBER

       Member must:
       - exist
       - not be archived
       - be Active

       Name/contact are taken from DB.
       Browser values are NOT trusted.
    ===================================================== */

    $memberStmt =

        mysqli_prepare(
            $conn,
            "
            SELECT

                member_id,
                member_code,
                full_name,
                contact_no,
                status

            FROM members

            WHERE member_id = ?

            AND archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );


    if (
        !$memberStmt
    ) {

        driverSaveLogError(
            'prepare member lookup',
            $conn
        );


        throw new RuntimeException(
            'Unable to validate the selected Member.'
        );
    }


    mysqli_stmt_bind_param(
        $memberStmt,
        'i',
        $memberID
    );


    if (
        !mysqli_stmt_execute(
            $memberStmt
        )
    ) {

        driverSaveLogError(
            'execute member lookup',
            $conn,
            $memberStmt
        );


        mysqli_stmt_close(
            $memberStmt
        );


        throw new RuntimeException(
            'Unable to validate the selected Member.'
        );
    }


    $memberResult =

        mysqli_stmt_get_result(
            $memberStmt
        );


    $member =

        $memberResult

        ?

        mysqli_fetch_assoc(
            $memberResult
        )

        :

        null;


    mysqli_stmt_close(
        $memberStmt
    );


    if (
        !$member
    ) {

        throw new InvalidArgumentException(
            'The selected Member is no longer available.'
        );
    }


    $memberStatus =

        strtoupper(
            trim(
                (string) (
                    $member[
                        'status'
                    ]
                    ??
                    ''
                )
            )
        );


    if (
        $memberStatus !== 'ACTIVE'
    ) {

        throw new InvalidArgumentException(
            'Only an Active Member can be registered as a driver.'
        );
    }


    $fullName =

        trim(
            (string) (
                $member[
                    'full_name'
                ]
                ??
                ''
            )
        );


    $contactNo =

        trim(
            (string) (
                $member[
                    'contact_no'
                ]
                ??
                ''
            )
        );


    $memberCode =

        trim(
            (string) (
                $member[
                    'member_code'
                ]
                ??
                ''
            )
        );


    if (
        $fullName === ''
    ) {

        throw new RuntimeException(
            'Unable to verify the Member name.'
        );
    }


    /* =====================================================
       CHECK EXISTING DRIVER

       One Member = one Driver Registry record.
    ===================================================== */

    $duplicateStmt =

        mysqli_prepare(
            $conn,
            "
            SELECT

                driver_id,
                archived

            FROM drivers

            WHERE member_id = ?

            LIMIT 1

            FOR UPDATE
            "
        );


    if (
        !$duplicateStmt
    ) {

        driverSaveLogError(
            'prepare duplicate check',
            $conn
        );


        throw new RuntimeException(
            'Unable to verify Driver Registry.'
        );
    }


    mysqli_stmt_bind_param(
        $duplicateStmt,
        'i',
        $memberID
    );


    if (
        !mysqli_stmt_execute(
            $duplicateStmt
        )
    ) {

        driverSaveLogError(
            'execute duplicate check',
            $conn,
            $duplicateStmt
        );


        mysqli_stmt_close(
            $duplicateStmt
        );


        throw new RuntimeException(
            'Unable to verify Driver Registry.'
        );
    }


    $duplicateResult =

        mysqli_stmt_get_result(
            $duplicateStmt
        );


    $existingDriver =

        $duplicateResult

        ?

        mysqli_fetch_assoc(
            $duplicateResult
        )

        :

        null;


    mysqli_stmt_close(
        $duplicateStmt
    );


    if (
        $existingDriver
    ) {


        if (
            (int) (
                $existingDriver[
                    'archived'
                ]
                ??
                0
            ) === 1
        ) {

            throw new InvalidArgumentException(
                'This Member already has an archived Driver Registry record.'
            );
        }


        throw new InvalidArgumentException(
            'This Member is already registered as a driver.'
        );

    }


    /* =====================================================
       CREATE DRIVER
    ===================================================== */

    $status =
        'Active';


    $insertStmt =

        mysqli_prepare(
            $conn,
            "
            INSERT INTO drivers
            (
                member_id,
                full_name,
                contact_no,
                status,
                archived
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                0
            )
            "
        );


    if (
        !$insertStmt
    ) {

        driverSaveLogError(
            'prepare driver insert',
            $conn
        );


        throw new RuntimeException(
            'Unable to prepare Driver registration.'
        );
    }


    mysqli_stmt_bind_param(
        $insertStmt,
        'isss',
        $memberID,
        $fullName,
        $contactNo,
        $status
    );


    if (
        !mysqli_stmt_execute(
            $insertStmt
        )
    ) {

        driverSaveLogError(
            'execute driver insert',
            $conn,
            $insertStmt
        );


        mysqli_stmt_close(
            $insertStmt
        );


        throw new RuntimeException(
            'Unable to register the driver.'
        );
    }


    $driverID =

        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $insertStmt
    );


    if (
        $driverID <= 0
    ) {

        throw new RuntimeException(
            'Unable to generate Driver ID.'
        );
    }


    /* =====================================================
       HISTORY
    ===================================================== */

    $historyMessage =

        'added Member "'

        .

        $fullName

        .

        '"';


    if (
        $memberCode !== ''
    ) {

        $historyMessage .=

            ' ('

            .

            $memberCode

            .

            ')';

    }


    $historyMessage .=

        ' to the Driver Registry as DRV-'

        .

        str_pad(
            (string) $driverID,
            4,
            '0',
            STR_PAD_LEFT
        )

        .

        '.';


    log_activity(
        $conn,
        'Rental',
        'Added',
        $historyMessage,
        $driverID
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    driverSaveResponse(
        'success'
    );

}


/* =========================================================
   USER-SAFE VALIDATION ERROR
========================================================= */

catch (
    InvalidArgumentException $error
) {


    mysqli_rollback(
        $conn
    );


    driverSaveResponse(
        $error->getMessage(),
        400
    );

}


/* =========================================================
   INTERNAL ERROR
========================================================= */

catch (
    Throwable $error
) {


    mysqli_rollback(
        $conn
    );


    error_log(
        '[SPIS][Driver] save failed: '
        .
        $error->getMessage()
    );


    driverSaveResponse(
        'Unable to register the driver. Please try again.',
        500
    );

}

?>