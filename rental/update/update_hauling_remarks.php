<?php

/* =====================================================
   SPIS - UPDATE HAULING PAYMENT REMARKS

   TEMPORARY PAYMENT HANDLING

   Rental Status and Payment Remarks are independent.

   Valid examples:
   - Pending + Unpaid
   - Pending + Paid
   - Ongoing + Unpaid
   - Ongoing + Paid
   - Done + Unpaid
   - Done + Paid

   This endpoint will later be replaced / expanded by the
   proper Hauling payment transaction system for:
   - Downpayment
   - Installment
   - Full payment
   - Automatic Unpaid / Partial / Paid status
===================================================== */

if (
    session_status() === PHP_SESSION_NONE
) {
    session_start();
}


/* =====================================================
   TIMEZONE
===================================================== */

date_default_timezone_set(
    'Asia/Manila'
);


error_reporting(E_ALL);
ini_set(
    'display_errors',
    '0'
);


/* =====================================================
   REQUIRED FILES
===================================================== */

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


/* =====================================================
   RESPONSE HELPER
===================================================== */

function haulingRemarksResponse(
    string $message,
    int $statusCode = 200
): void {

    http_response_code(
        $statusCode
    );

    echo $message;

    exit();
}


/* =====================================================
   POST REQUEST ONLY
===================================================== */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
) {

    haulingRemarksResponse(
        'Invalid request.',
        405
    );
}


/* =====================================================
   VERIFY CURRENT USER / RENTAL ACCESS

   Do not trust only the existing session permission.
   Recheck the live account from the database.
===================================================== */

$actorID =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


if (
    $actorID <= 0
) {

    haulingRemarksResponse(
        'Your session has expired. Please sign in again.',
        401
    );
}


$actorStmt =
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
    !$actorStmt
) {

    haulingRemarksResponse(
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

    haulingRemarksResponse(
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

    haulingRemarksResponse(
        'You do not have permission to update Rental records.',
        403
    );
}


/* =====================================================
   CSRF VALIDATION
===================================================== */

$sessionCsrfToken =
    (string) (
        $_SESSION['rental_csrf_token']
        ??
        ''
    );


$requestCsrfToken =
    (string) (
        $_POST['csrf_token']
        ??
        ''
    );


if (
    $sessionCsrfToken === ''
    ||
    $requestCsrfToken === ''
    ||
    !hash_equals(
        $sessionCsrfToken,
        $requestCsrfToken
    )
) {

    haulingRemarksResponse(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =====================================================
   GET REQUEST VALUES
===================================================== */

$haulingID =
    isset($_POST['id'])
        ? (int) $_POST['id']
        : 0;


$remarks =
    trim(
        (string) (
            $_POST['remarks']
            ??
            ''
        )
    );


/* =====================================================
   BASIC VALIDATION
===================================================== */

if (
    $haulingID <= 0
) {

    haulingRemarksResponse(
        'Invalid hauling record.',
        400
    );
}


/*
   For the CURRENT Hauling version only.

   Later this manual remarks selection will be replaced
   with automatic payment status:

   Unpaid  = no payment
   Partial = payment exists but balance remains
   Paid    = balance is zero
*/

$allowedRemarks = [
    'Unpaid',
    'Paid'
];


if (
    !in_array(
        $remarks,
        $allowedRemarks,
        true
    )
) {

    haulingRemarksResponse(
        'Please select valid payment remarks.',
        400
    );
}


/* =====================================================
   BEGIN TRANSACTION
===================================================== */

mysqli_begin_transaction(
    $conn
);


try {

    /* =================================================
       LOCK + LOAD CURRENT HAULING RECORD
    ================================================= */

    $checkStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                hauling_id,
                transaction_id,
                customer_name,
                tractor_no,
                remarks AS current_remarks
            FROM hauling
            WHERE hauling_id = ?
            LIMIT 1
            FOR UPDATE
            "
        );


    if (
        !$checkStmt
    ) {

        throw new Exception(
            'Unable to check hauling record.'
        );
    }


    mysqli_stmt_bind_param(
        $checkStmt,
        'i',
        $haulingID
    );


    if (
        !mysqli_stmt_execute(
            $checkStmt
        )
    ) {

        mysqli_stmt_close(
            $checkStmt
        );

        throw new Exception(
            'Unable to check hauling record.'
        );
    }


    $result =
        mysqli_stmt_get_result(
            $checkStmt
        );


    if (
        !$result
        ||
        mysqli_num_rows(
            $result
        ) !== 1
    ) {

        mysqli_stmt_close(
            $checkStmt
        );

        throw new Exception(
            'Hauling record was not found.'
        );
    }


    $hauling =
        mysqli_fetch_assoc(
            $result
        );


    mysqli_stmt_close(
        $checkStmt
    );


    /* =================================================
       CURRENT VALUES
    ================================================= */

    $transactionID =
        trim(
            (string) (
                $hauling['transaction_id']
                ??
                ''
            )
        );


    $customerName =
        trim(
            (string) (
                $hauling['customer_name']
                ??
                ''
            )
        );


    $tractorNo =
        trim(
            (string) (
                $hauling['tractor_no']
                ??
                ''
            )
        );


    $currentRemarks =
        trim(
            (string) (
                $hauling['current_remarks']
                ??
                ''
            )
        );


    /* =================================================
       VALIDATE CURRENT REMARKS

       Only validate values supported by the CURRENT
       database/UI version.
    ================================================= */

    if (
        !in_array(
            $currentRemarks,
            $allowedRemarks,
            true
        )
    ) {

        throw new Exception(
            'This hauling record has invalid payment remarks.'
        );
    }


    /* =================================================
       SAME VALUE

       Do nothing and avoid duplicate History entries.
    ================================================= */

    if (
        $currentRemarks === $remarks
    ) {

        mysqli_commit(
            $conn
        );

        haulingRemarksResponse(
            'success'
        );
    }

    /* =================================================
    PAID IS FINAL

    Once a Hauling rental is marked Paid,
    it must never return to Unpaid.
    ================================================= */

    if (
        $currentRemarks === 'Paid'
        &&
        $remarks !== 'Paid'
    ) {

        throw new Exception(
            'Paid payment status is final and can no longer be changed.'
        );
    }


    /* =================================================
       IMPORTANT BUSINESS RULE

       THERE IS INTENTIONALLY NO STATUS-TO-PAYMENT LOCK.

       Examples:

       Pending + Paid
       = valid advance/full payment before service.

       Ongoing + Unpaid
       = service has started but payment is not settled.

       Done + Unpaid
       = service is completed but payment is still due.

       Rental progress and payment progress are separate.
    ================================================= */


    /* =================================================
       UPDATE PAYMENT REMARKS
    ================================================= */

    $updateStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE hauling
            SET remarks = ?
            WHERE hauling_id = ?
            "
        );


    if (
        !$updateStmt
    ) {

        throw new Exception(
            'Unable to prepare hauling payment update.'
        );
    }


    mysqli_stmt_bind_param(
        $updateStmt,
        'si',
        $remarks,
        $haulingID
    );


    if (
        !mysqli_stmt_execute(
            $updateStmt
        )
    ) {

        mysqli_stmt_close(
            $updateStmt
        );

        throw new Exception(
            'Unable to update hauling payment remarks.'
        );
    }


    mysqli_stmt_close(
        $updateStmt
    );


    /* =================================================
       HISTORY LOG
    ================================================= */

    log_activity(
        $conn,
        'Rental',
        'Updated',
        'changed the Hauling rental "'
        .
        $transactionID
        .
        '" for "'
        .
        $customerName
        .
        '" using tractor "'
        .
        $tractorNo
        .
        '" payment remarks from '
        .
        $currentRemarks
        .
        ' to '
        .
        $remarks
        .
        '.',
        $haulingID
    );


    /* =================================================
       COMMIT
    ================================================= */

    mysqli_commit(
        $conn
    );


    haulingRemarksResponse(
        'success'
    );

}
catch (
    Throwable $error
) {

    mysqli_rollback(
        $conn
    );


    $safeMessage =
        trim(
            (string) $error->getMessage()
        );


    if (
        $safeMessage === ''
    ) {

        $safeMessage =
            'Unable to update hauling payment remarks.';
    }


    haulingRemarksResponse(
        $safeMessage,
        400
    );
}

?>