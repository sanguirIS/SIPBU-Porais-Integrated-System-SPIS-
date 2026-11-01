<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}

date_default_timezone_set(
    'Asia/Manila'
);

require_once "../db.php";
require_once "../includes/history_logger.php";

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

if(
    !isset($_SESSION['user_id'])
){
    header('Location: ../login.php');
    exit();
}

if(
    !isset($_SESSION['members_access'])
    ||
    (int)$_SESSION['members_access'] !== 1
){
    http_response_code(403);
    exit('Access denied.');
}


/* =====================================================
   POST REQUEST ONLY
===================================================== */

if(
    $_SERVER['REQUEST_METHOD']
    !==
    'POST'
){

    echo 'Invalid request.';

    exit();

}


/* =====================================================
   CSRF
===================================================== */

$csrfToken =
    (string)(
        $_POST['csrf_token']
        ??
        ''
    );

$sessionCsrfToken =
    (string)(
        $_SESSION['member_form_csrf_token']
        ??
        ''
    );

if(
    $csrfToken === ''
    ||
    $sessionCsrfToken === ''
    ||
    !hash_equals(
        $sessionCsrfToken,
        $csrfToken
    )
){
    http_response_code(403);
    exit('Invalid or expired form request. Please refresh the page and try again.');
}


/* =====================================================
   GET FORM VALUES
===================================================== */

$memberID =
isset(
    $_POST['member_id']
)
?
(int)$_POST['member_id']
:
0;


$page =
isset(
    $_POST['page']
)
?
max(
    1,
    (int)$_POST['page']
)
:
1;


$search =
trim(
    $_POST['search']
    ??
    ''
);


$statusFilter = (string)($_POST['status_filter'] ?? 'all');

if (!in_array($statusFilter, ['all', 'Active', 'Inactive'], true)) {
    $statusFilter = 'all';
}

$classificationFilter = (string)($_POST['classification_filter'] ?? 'all');

if (!in_array($classificationFilter, ['all', 'Regular', 'Irregular'], true)) {
    $classificationFilter = 'all';
}



/* =====================================================
   VALIDATE MEMBER ID
===================================================== */

if($memberID <= 0){

    echo 'Invalid member record.';

    exit();

}


/* =====================================================
   ARCHIVED BY
===================================================== */

$archivedBy =
trim(
    $_SESSION['name']
    ??
    'Unknown User'
);


/* =====================================================
   START TRANSACTION
===================================================== */

mysqli_begin_transaction(
    $conn
);


try{


    /* =================================================
       GET CURRENT MEMBER
    ================================================= */

    $checkStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            member_id,
            member_code,
            full_name

        FROM members

        WHERE member_id = ?

        AND archived = 0

        LIMIT 1

        FOR UPDATE
        "
    );


    if(!$checkStmt){

        throw new Exception(
            'Unable to check the member record.'
        );

    }


    mysqli_stmt_bind_param(
        $checkStmt,
        'i',
        $memberID
    );


    if(
        !mysqli_stmt_execute(
            $checkStmt
        )
    ){

        mysqli_stmt_close(
            $checkStmt
        );


        throw new Exception(
            'Unable to check the member record.'
        );

    }


    $memberResult =
    mysqli_stmt_get_result(
        $checkStmt
    );


    if(
        !$memberResult
        ||
        mysqli_num_rows(
            $memberResult
        ) === 0
    ){

        mysqli_stmt_close(
            $checkStmt
        );


        throw new Exception(
            'Member record was not found or is already archived.'
        );

    }


    $member =
    mysqli_fetch_assoc(
        $memberResult
    );


    mysqli_stmt_close(
        $checkStmt
    );


    /* =================================================
       MEMBER INFORMATION
    ================================================= */

    $memberCode =
    trim(
        $member['member_code']
        ??
        ''
    );


    $memberName =
    trim(
        $member['full_name']
        ??
        'Unknown Member'
    );


    /* =================================================
       ARCHIVE MEMBER
    ================================================= */

    $archiveStmt =
    mysqli_prepare(
        $conn,
        "
        UPDATE members

        SET
            archived = 1,
            archived_by = ?

        WHERE member_id = ?

        AND archived = 0
        "
    );


    if(!$archiveStmt){

        throw new Exception(
            'Unable to prepare the member archive request.'
        );

    }


    mysqli_stmt_bind_param(
        $archiveStmt,
        'si',
        $archivedBy,
        $memberID
    );


    if(
        !mysqli_stmt_execute(
            $archiveStmt
        )
    ){

        mysqli_stmt_close(
            $archiveStmt
        );


        throw new Exception(
            'Unable to archive the member record.'
        );

    }


    $affectedRows =
    mysqli_stmt_affected_rows(
        $archiveStmt
    );


    mysqli_stmt_close(
        $archiveStmt
    );


    if($affectedRows <= 0){

        throw new Exception(
            'No member record was archived.'
        );

    }


    /* =================================================
       SAVE HISTORY LOG
    ================================================= */

    log_activity(
        $conn,
        'Members',
        'Archived',
        'archived the member "'
        .
        $memberName
        .
        '" ('
        .
        $memberCode
        .
        ').',
        $memberID
    );


    /* =================================================
       COMMIT
    ================================================= */

    mysqli_commit(
        $conn
    );
    
    header(
        'Location: member.php?' . http_build_query([
            'page' => $page,
            'search' => $search,
            'status' => $statusFilter,
            'classification' => $classificationFilter,
            'success' => 'archived'
        ])
    );

    exit();


}
catch(Throwable $error){


    mysqli_rollback(
        $conn
    );


    echo
    $error->getMessage();


    exit();

}

?>