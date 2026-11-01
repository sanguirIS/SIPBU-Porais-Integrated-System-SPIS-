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
    http_response_code(401);
    exit('Session expired.');
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
   GET ATTACHMENT ID
===================================================== */

$attachmentID =
isset(
    $_POST['attachment_id']
)
?
(int)$_POST['attachment_id']
:
0;


/* =====================================================
   VALIDATE ATTACHMENT ID
===================================================== */

if($attachmentID <= 0){

    echo 'Invalid attachment record.';

    exit();

}


/* =====================================================
   START TRANSACTION
===================================================== */

mysqli_begin_transaction(
    $conn
);


try{


    /* =================================================
       GET ATTACHMENT AND MEMBER INFORMATION
    ================================================= */

    $checkStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            ma.attachment_id,
            ma.member_id,
            ma.file_path,
            m.member_code,
            m.full_name

        FROM member_attachments AS ma

        INNER JOIN members AS m
            ON m.member_id = ma.member_id

        WHERE ma.attachment_id = ?
        AND m.archived = 0

        LIMIT 1

        FOR UPDATE
        "
    );


    if(!$checkStmt){

        throw new Exception(
            'Unable to check the attachment record.'
        );

    }


    mysqli_stmt_bind_param(
        $checkStmt,
        'i',
        $attachmentID
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
            'Unable to check the attachment record.'
        );

    }


    $attachmentResult =
    mysqli_stmt_get_result(
        $checkStmt
    );


    if(
        !$attachmentResult
        ||
        mysqli_num_rows(
            $attachmentResult
        ) === 0
    ){

        mysqli_stmt_close(
            $checkStmt
        );


        throw new Exception(
            'Attachment record was not found.'
        );

    }


    $attachment =
    mysqli_fetch_assoc(
        $attachmentResult
    );


    mysqli_stmt_close(
        $checkStmt
    );


    /* =================================================
       ATTACHMENT INFORMATION
    ================================================= */

    $memberID =
    (int)(
        $attachment['member_id']
        ??
        0
    );


    $memberCode =
    trim(
        $attachment['member_code']
        ??
        ''
    );


    $memberName =
    trim(
        $attachment['full_name']
        ??
        'Unknown Member'
    );


    $filePath =
    trim(
        $attachment['file_path']
        ??
        ''
    );


    $filename =
    basename(
        $filePath
    );


    if($filename === ''){

        $filename =
        'Unknown Attachment';

    }


    /* =================================================
       DELETE ATTACHMENT DATABASE RECORD
    ================================================= */

    $deleteStmt =
    mysqli_prepare(
        $conn,
        "
        DELETE FROM member_attachments

        WHERE attachment_id = ?
        "
    );


    if(!$deleteStmt){

        throw new Exception(
            'Unable to prepare the attachment removal.'
        );

    }


    mysqli_stmt_bind_param(
        $deleteStmt,
        'i',
        $attachmentID
    );


    if(
        !mysqli_stmt_execute(
            $deleteStmt
        )
    ){

        mysqli_stmt_close(
            $deleteStmt
        );


        throw new Exception(
            'Unable to remove the attachment record.'
        );

    }


    $affectedRows =
    mysqli_stmt_affected_rows(
        $deleteStmt
    );


    mysqli_stmt_close(
        $deleteStmt
    );


    if($affectedRows <= 0){

        throw new Exception(
            'No attachment record was removed.'
        );

    }


    /* =================================================
       SAVE HISTORY LOG
    ================================================= */

    log_activity(
        $conn,
        'Members',
        'Updated',
        'removed the supporting attachment "'
        .
        $filename
        .
        '" from member "'
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
       COMMIT DATABASE CHANGES
    ================================================= */

    mysqli_commit(
        $conn
    );


    /* =================================================
       REMOVE PHYSICAL FILE

       Only member-document paths are allowed here.
       Failure to remove an orphaned physical file does
       not restore the already removed database record.
    ================================================= */

    $normalizedFilePath =
    str_replace(
        '\\',
        '/',
        ltrim(
            $filePath,
            '/'
        )
    );


    $allowedDirectory =
    'assets/documents/members/';


    if(
        $normalizedFilePath !== ''
        &&
        str_starts_with(
            $normalizedFilePath,
            $allowedDirectory
        )
    ){

        $physicalPath =
        '../'
        .
        $normalizedFilePath;


        if(
            is_file(
                $physicalPath
            )
        ){

            unlink(
                $physicalPath
            );

        }

    }


    echo 'success';

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