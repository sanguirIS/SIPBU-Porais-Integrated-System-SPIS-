<?php

if (
    session_status() === PHP_SESSION_NONE
) {
    session_start();
}


date_default_timezone_set(
    'Asia/Manila'
);


require_once '../db.php';
require_once '../includes/check_access.php';
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
   LIVE ACCESS CONTROL
========================================================= */

checkAccess(
    'archives_access'
);

checkAccess(
    'members_access'
);


/* =========================================================
   POST ONLY
========================================================= */

if (
    ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST'
) {

    header(
        'Location: archive.php?module=member&error=invalid_request',
        true,
        303
    );

    exit();
}


/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionCsrfToken =
    (string) (
        $_SESSION['archive_member_csrf_token']
        ?? ''
    );

$requestCsrfToken =
    (string) (
        $_POST['csrf_token']
        ?? ''
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

    header(
        'Location: archive.php?module=member&error=csrf',
        true,
        303
    );

    exit();
}


/* =========================================================
   MEMBER ID
========================================================= */

$memberID =
    filter_var(
        $_POST['member_id']
        ?? null,
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1
            ]
        ]
    );


if ($memberID === false) {

    header(
        'Location: archive.php?module=member&error=retrieve_failed',
        true,
        303
    );

    exit();
}


$memberID =
    (int) $memberID;


/* =========================================================
   RESTORE TRANSACTION
========================================================= */

$transactionStarted =
    false;


try {

    mysqli_begin_transaction(
        $conn
    );

    $transactionStarted =
        true;


    $memberStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                member_id,
                full_name

            FROM members

            WHERE member_id = ?
            AND archived = 1

            LIMIT 1

            FOR UPDATE
            "
        );


    if (!$memberStmt) {

        throw new RuntimeException(
            'Unable to prepare the member restore request.'
        );
    }


    mysqli_stmt_bind_param(
        $memberStmt,
        'i',
        $memberID
    );


    if (!mysqli_stmt_execute($memberStmt)) {

        mysqli_stmt_close(
            $memberStmt
        );

        throw new RuntimeException(
            'Unable to check the archived member.'
        );
    }


    $memberResult =
        mysqli_stmt_get_result(
            $memberStmt
        );


    $member =
        $memberResult
            ? mysqli_fetch_assoc($memberResult)
            : null;


    mysqli_stmt_close(
        $memberStmt
    );


    if (!$member) {

        throw new RuntimeException(
            'Archived member was not found.'
        );
    }


    $memberName =
        trim(
            (string) (
                $member['full_name']
                ?? 'Unknown Member'
            )
        );


    /* =====================================================
    PREVENT DUPLICATE ACTIVE MEMBER NAME
    ===================================================== */

    $duplicateMemberStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                member_id

            FROM members

            WHERE
                archived = 0
                AND member_id <> ?
                AND LOWER(
                    TRIM(
                        full_name
                    )
                ) = LOWER(
                    TRIM(?)
                )

            LIMIT 1

            FOR UPDATE
            "
        );


    if (!$duplicateMemberStmt) {

        throw new RuntimeException(
            'Unable to prepare duplicate member validation.'
        );
    }


    mysqli_stmt_bind_param(
        $duplicateMemberStmt,
        'is',
        $memberID,
        $memberName
    );


    if (
        !mysqli_stmt_execute(
            $duplicateMemberStmt
        )
    ) {

        mysqli_stmt_close(
            $duplicateMemberStmt
        );


        throw new RuntimeException(
            'Unable to validate duplicate member name.'
        );
    }


    $duplicateMemberResult =
        mysqli_stmt_get_result(
            $duplicateMemberStmt
        );


    $duplicateMember =
        $duplicateMemberResult
            ? mysqli_fetch_assoc(
                $duplicateMemberResult
            )
            : null;


    mysqli_stmt_close(
        $duplicateMemberStmt
    );


    if ($duplicateMember) {

        mysqli_rollback(
            $conn
        );


        $transactionStarted =
            false;


        header(
            'Location: archive.php?module=member&error=member_duplicate',
            true,
            303
        );


        exit();
    }


    $restoreStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE members

            SET
                archived = 0,
                archived_by = NULL

            WHERE member_id = ?
            AND archived = 1
            "
        );


    if (!$restoreStmt) {

        throw new RuntimeException(
            'Unable to prepare restore request.'
        );
    }


    mysqli_stmt_bind_param(
        $restoreStmt,
        'i',
        $memberID
    );


    if (!mysqli_stmt_execute($restoreStmt)) {

        mysqli_stmt_close(
            $restoreStmt
        );

        throw new RuntimeException(
            'Unable to restore the member.'
        );
    }


    $affectedRows =
        mysqli_stmt_affected_rows(
            $restoreStmt
        );


    mysqli_stmt_close(
        $restoreStmt
    );


    if ($affectedRows !== 1) {

        throw new RuntimeException(
            'No member record was restored.'
        );
    }


    log_activity(
        $conn,
        'Archive',
        'Restored',
        'restored the member record of "' .
        $memberName .
        '".',
        $memberID
    );


    mysqli_commit(
        $conn
    );


    $transactionStarted =
        false;


    $_SESSION['archive_member_csrf_token'] =
        bin2hex(
            random_bytes(32)
        );


    header(
        'Location: archive.php?module=member&success=restored',
        true,
        303
    );

    exit();

}
catch (Throwable $error) {

    if ($transactionStarted) {

        mysqli_rollback(
            $conn
        );
    }


    error_log(
        'Retrieve Member error: ' .
        $error->getMessage()
    );


    header(
        'Location: archive.php?module=member&error=retrieve_failed',
        true,
        303
    );

    exit();
}

?>