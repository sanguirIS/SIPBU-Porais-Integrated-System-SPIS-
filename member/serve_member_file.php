<?php

/* =========================================================
   SECURE MEMBER FILE SERVER
========================================================= */

if (
    session_status()
    ===
    PHP_SESSION_NONE
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
   RESPONSE HELPER
========================================================= */

function memberFileFail(
    int $statusCode,
    string $message
): never {

    http_response_code(
        $statusCode
    );


    header(
        'Content-Type: text/plain; charset=UTF-8'
    );


    header(
        'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
    );


    echo $message;

    exit();

}


/* =========================================================
   AUTHENTICATION
========================================================= */

$userID =
    isset(
        $_SESSION['user_id']
    )
    ?
    (int)$_SESSION['user_id']
    :
    0;


if (
    $userID <= 0
) {

    memberFileFail(
        401,
        'Authentication required.'
    );

}


/* =========================================================
   FILE TYPE
========================================================= */

$type =
    strtolower(
        trim(
            (string)(
                $_GET['type']
                ??
                ''
            )
        )
    );


if (
    !in_array(
        $type,
        [
            'profile',
            'attachment'
        ],
        true
    )
) {

    memberFileFail(
        400,
        'Invalid file request.'
    );

}


/* =========================================================
   LOAD REQUESTED FILE RECORD
========================================================= */

$filePath =
    '';

$memberID =
    0;

$isArchived =
    0;


/* ---------------------------------------------------------
   PROFILE IMAGE
--------------------------------------------------------- */

if (
    $type === 'profile'
) {

    $memberID =
        isset(
            $_GET['member_id']
        )
        ?
        (int)$_GET['member_id']
        :
        0;


    if (
        $memberID <= 0
    ) {

        memberFileFail(
            400,
            'Invalid member record.'
        );

    }


    $stmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                member_id,
                profile_image,
                archived

            FROM members

            WHERE member_id = ?

            LIMIT 1
            "
        );


    if (
        !$stmt
    ) {

        memberFileFail(
            500,
            'Unable to prepare file request.'
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $memberID
    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        mysqli_stmt_close(
            $stmt
        );


        memberFileFail(
            500,
            'Unable to load file.'
        );

    }


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    $member =
        $result
        ?
        mysqli_fetch_assoc(
            $result
        )
        :
        null;


    mysqli_stmt_close(
        $stmt
    );


    if (
        !$member
    ) {

        memberFileFail(
            404,
            'File not found.'
        );

    }


    $filePath =
        trim(
            (string)(
                $member['profile_image']
                ??
                ''
            )
        );


    $isArchived =
        (int)(
            $member['archived']
            ??
            0
        );

}


/* ---------------------------------------------------------
   SUPPORTING ATTACHMENT
--------------------------------------------------------- */

else {

    $attachmentID =
        isset(
            $_GET['attachment_id']
        )
        ?
        (int)$_GET['attachment_id']
        :
        0;


    if (
        $attachmentID <= 0
    ) {

        memberFileFail(
            400,
            'Invalid attachment record.'
        );

    }


    $stmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                ma.member_id,
                ma.file_path,
                m.archived

            FROM member_attachments AS ma

            INNER JOIN members AS m
                ON m.member_id = ma.member_id

            WHERE ma.attachment_id = ?

            LIMIT 1
            "
        );


    if (
        !$stmt
    ) {

        memberFileFail(
            500,
            'Unable to prepare file request.'
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $attachmentID
    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        mysqli_stmt_close(
            $stmt
        );


        memberFileFail(
            500,
            'Unable to load file.'
        );

    }


    $result =
        mysqli_stmt_get_result(
            $stmt
        );


    $attachment =
        $result
        ?
        mysqli_fetch_assoc(
            $result
        )
        :
        null;


    mysqli_stmt_close(
        $stmt
    );


    if (
        !$attachment
    ) {

        memberFileFail(
            404,
            'File not found.'
        );

    }


    $memberID =
        (int)(
            $attachment['member_id']
            ??
            0
        );


    $filePath =
        trim(
            (string)(
                $attachment['file_path']
                ??
                ''
            )
        );


    $isArchived =
        (int)(
            $attachment['archived']
            ??
            0
        );

}


/* =========================================================
   MODULE PERMISSION
========================================================= */

/*
Active member:
Members access is required.

Archived member:
Archives access is required.

This also keeps the endpoint compatible with the
Archive module when we audit it later.
*/

if (
    $isArchived === 1
) {

    if (
        !isset(
            $_SESSION['archives_access']
        )
        ||
        (int)$_SESSION['archives_access'] !== 1
    ) {

        memberFileFail(
            403,
            'Access denied.'
        );

    }

}
else {

    if (
        !isset(
            $_SESSION['members_access']
        )
        ||
        (int)$_SESSION['members_access'] !== 1
    ) {

        memberFileFail(
            403,
            'Access denied.'
        );

    }

}


/* =========================================================
   VALIDATE STORED PATH
========================================================= */

if (
    $filePath === ''
) {

    memberFileFail(
        404,
        'File not found.'
    );

}


$normalizedPath =
    str_replace(
        '\\',
        '/',
        ltrim(
            $filePath,
            '/'
        )
    );


$expectedDirectory =
    $type === 'profile'
    ?
    'assets/members/'
    :
    'assets/documents/members/';


if (
    !str_starts_with(
        $normalizedPath,
        $expectedDirectory
    )
) {

    memberFileFail(
        403,
        'Invalid file location.'
    );

}


/* =========================================================
   RESOLVE PHYSICAL FILE
========================================================= */

$systemRoot =
    realpath(
        dirname(
            __DIR__
        )
    );


if (
    $systemRoot === false
) {

    memberFileFail(
        500,
        'Unable to locate system files.'
    );

}


$allowedRoot =
    realpath(
        $systemRoot
        .
        DIRECTORY_SEPARATOR
        .
        rtrim(
            $expectedDirectory,
            '/'
        )
    );


if (
    $allowedRoot === false
) {

    memberFileFail(
        404,
        'File storage is unavailable.'
    );

}


$candidatePath =
    $systemRoot
    .
    DIRECTORY_SEPARATOR
    .
    str_replace(
        '/',
        DIRECTORY_SEPARATOR,
        $normalizedPath
    );


$realFile =
    realpath(
        $candidatePath
    );


if (
    $realFile === false
    ||
    !is_file(
        $realFile
    )
) {

    memberFileFail(
        404,
        'File not found.'
    );

}


/* =========================================================
   DIRECTORY CONTAINMENT
========================================================= */

$realFileNormalized =
    str_replace(
        '\\',
        '/',
        $realFile
    );


$allowedRootNormalized =
    rtrim(
        str_replace(
            '\\',
            '/',
            $allowedRoot
        ),
        '/'
    )
    .
    '/';


if (
    !str_starts_with(
        strtolower(
            $realFileNormalized
        ),
        strtolower(
            $allowedRootNormalized
        )
    )
) {

    memberFileFail(
        403,
        'Invalid file location.'
    );

}


/* =========================================================
   MIME VALIDATION
========================================================= */

$finfo =
    new finfo(
        FILEINFO_MIME_TYPE
    );


$mimeType =
    $finfo->file(
        $realFile
    );


$allowedMimeTypes = [

    'image/jpeg',
    'image/png',
    'image/webp',
    'application/pdf'

];


if (
    !is_string(
        $mimeType
    )
    ||
    !in_array(
        $mimeType,
        $allowedMimeTypes,
        true
    )
) {

    memberFileFail(
        403,
        'Unsupported file type.'
    );

}


/* =========================================================
   SEND FILE
========================================================= */

$filename =
    basename(
        $realFile
    );


header(
    'Content-Type: '
    .
    $mimeType
);


header(
    'Content-Length: '
    .
    filesize(
        $realFile
    )
);


header(
    'Content-Disposition: inline; filename="'
    .
    str_replace(
        '"',
        '',
        $filename
    )
    .
    '"'
);


header(
    'X-Content-Type-Options: nosniff'
);


header(
    'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0'
);


header(
    'Pragma: no-cache'
);


readfile(
    $realFile
);


exit();
?>