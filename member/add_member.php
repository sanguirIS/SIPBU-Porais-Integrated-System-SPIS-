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


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   ACCESS CHECK
========================================================= */

if(
    !isset(
        $_SESSION['user_id']
    )
){

    header(
        "Location: ../login.php"
    );

    exit();
}


if(
    !isset(
        $_SESSION['members_access']
    )
    ||
    (int)$_SESSION['members_access']
        !== 1
){

    http_response_code(
        403
    );

    exit(
        "Access denied."
    );
}


/* =========================================================
   POST ONLY
========================================================= */

if(
    $_SERVER['REQUEST_METHOD']
    !==
    'POST'
){

    http_response_code(
        405
    );

    exit(
        "Invalid request."
    );
}


/* =========================================================
   SMALL HELPER
========================================================= */

function addMemberFail(
    string $message,
    int $statusCode = 400
): void {

    http_response_code(
        $statusCode
    );

    exit(
        $message
    );
}


/* =========================================================
   CSRF
========================================================= */

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
    addMemberFail(
        "Invalid or expired form request. Please refresh the page and try again.",
        403
    );
}


/* =========================================================
   REMOVE CREATED DIRECTORY ON FAILURE
========================================================= */

function removeMemberDirectory(
    string $directory
): void {

    if(
        $directory === ''
        ||
        !is_dir(
            $directory
        )
    ){
        return;
    }


    $items =
        scandir(
            $directory
        );


    if(
        $items === false
    ){
        return;
    }


    foreach(
        $items
        as
        $item
    ){

        if(
            $item === '.'
            ||
            $item === '..'
        ){
            continue;
        }


        $path =
            $directory
            .
            DIRECTORY_SEPARATOR
            .
            $item;


        if(
            is_dir(
                $path
            )
        ){

            removeMemberDirectory(
                $path
            );

        }
        else{

            @unlink(
                $path
            );

        }

    }


    @rmdir(
        $directory
    );
}


/* =========================================================
   USER
========================================================= */

$currentUserID =
    (int)$_SESSION['user_id'];


/* =========================================================
   FORM VALUES
========================================================= */

$fullname =
    trim(
        $_POST['fullname']
        ??
        ''
    );


$membershipDate =
    trim(
        $_POST['membership_date']
        ??
        ''
    );


$birthDate =
    trim(
        $_POST['birth_date']
        ??
        ''
    );


$gender =
    trim(
        $_POST['gender']
        ??
        ''
    );


$address =
    trim(
        $_POST['address']
        ??
        ''
    );


$tin =
    preg_replace(
        '/[^0-9]/',
        '',
        $_POST['tin']
        ??
        ''
    );


$rsbsa =
    strtoupper(
        trim(
            $_POST['rsbsa']
            ??
            ''
        )
    );


$contact =
    preg_replace(
        '/[^0-9]/',
        '',
        $_POST['contact']
        ??
        ''
    );


$socialMediaName =
    trim(
        $_POST['social_media_name']
        ??
        ''
    );


$farmFieldRaw =
    trim(
        $_POST['farm_field_hectares']
        ??
        ''
    );


$initialCbuRaw =
    trim(
        $_POST['initial_cbu']
        ??
        ''
    );


$status =
    strtoupper(
        trim(
            $_POST['status']
            ??
            ''
        )
    );


/* =========================================================
   FULL NAME
========================================================= */

if(
    $fullname === ''
){

    addMemberFail(
        "Member name is required."
    );
}


if(
    mb_strlen(
        $fullname
    ) > 255
){

    addMemberFail(
        "Member name is too long."
    );
}


if(
    !preg_match(
        "/^[\p{L}\s.'-]+$/u",
        $fullname
    )
){

    addMemberFail(
        "Please enter a valid member name."
    );
}


/* =========================================================
   MEMBERSHIP DATE
========================================================= */

if(
    $membershipDate === ''
){

    addMemberFail(
        "Membership date is required."
    );
}


$membershipDateObject =
    DateTime::createFromFormat(
        '!Y-m-d',
        $membershipDate,
        new DateTimeZone(
            'Asia/Manila'
        )
    );


if(
    !$membershipDateObject
    ||
    $membershipDateObject->format(
        'Y-m-d'
    )
    !==
    $membershipDate
){

    addMemberFail(
        "Invalid membership date."
    );
}


$membershipToday =
    new DateTime(
        'today',
        new DateTimeZone(
            'Asia/Manila'
        )
    );


if(
    $membershipDateObject
    >
    $membershipToday
){

    addMemberFail(
        "Membership date cannot be in the future."
    );
}


/* =========================================================
   BIRTH DATE
========================================================= */

if(
    $birthDate === ''
){

    addMemberFail(
        "Birth date is required."
    );
}


$birthDateObject =
    DateTime::createFromFormat(
        '!Y-m-d',
        $birthDate,
        new DateTimeZone(
            'Asia/Manila'
        )
    );


if(
    !$birthDateObject
    ||
    $birthDateObject->format(
        'Y-m-d'
    )
    !==
    $birthDate
){

    addMemberFail(
        "Invalid birth date."
    );
}


$today =
    new DateTime(
        'today',
        new DateTimeZone(
            'Asia/Manila'
        )
    );


if(
    $birthDateObject
    >
    $today
){

    addMemberFail(
        "Birth date cannot be in the future."
    );
}


/* =========================================================
   GENDER
========================================================= */

if(
    !in_array(
        $gender,
        [
            'Male',
            'Female'
        ],
        true
    )
){

    addMemberFail(
        "Please select a valid gender."
    );
}


/* =========================================================
   ADDRESS
========================================================= */

if(
    $address === ''
){

    addMemberFail(
        "Address is required."
    );
}


/* =========================================================
   TIN
========================================================= */

if(
    strlen(
        $tin
    ) !== 9
    &&
    strlen(
        $tin
    ) !== 12
){

    addMemberFail(
        "TIN Number must contain exactly 9 or 12 digits."
    );
}


/* =========================================================
   RSBSA

   FORMAT:
   XX-XX-XX-XXX-REMAINDER

   Minimum: 14 alphanumeric characters
   Maximum: 26 alphanumeric characters
   Maximum formatted length: 30
========================================================= */

if(
    $rsbsa === ''
){

    addMemberFail(
        "RSBSA Number is required."
    );
}


if(
    !preg_match(
        '/^[A-Z0-9-]+$/',
        $rsbsa
    )
){

    addMemberFail(
        "RSBSA Number may contain letters, numbers, and hyphens only."
    );
}


$rsbsaCompact =
    str_replace(
        '-',
        '',
        $rsbsa
    );


if(
    !preg_match(
        '/^[A-Z0-9]+$/',
        $rsbsaCompact
    )
){

    addMemberFail(
        "RSBSA Number contains invalid characters."
    );
}


$rsbsaLength =
    strlen(
        $rsbsaCompact
    );


if(
    $rsbsaLength < 14
){

    addMemberFail(
        "RSBSA Number must contain at least 14 letters or numbers."
    );
}


if(
    $rsbsaLength > 26
){

    addMemberFail(
        "RSBSA Number is too long."
    );
}


$rsbsa =
    substr(
        $rsbsaCompact,
        0,
        2
    )
    .
    '-'
    .
    substr(
        $rsbsaCompact,
        2,
        2
    )
    .
    '-'
    .
    substr(
        $rsbsaCompact,
        4,
        2
    )
    .
    '-'
    .
    substr(
        $rsbsaCompact,
        6,
        3
    )
    .
    '-'
    .
    substr(
        $rsbsaCompact,
        9
    );


if(
    strlen(
        $rsbsa
    ) > 30
){

    addMemberFail(
        "RSBSA Number is too long."
    );
}


/* =========================================================
   CONTACT NUMBER

   OPTIONAL.
   Validate only when a value is entered.
========================================================= */

$contactDB =
    null;


if(
    $contact !== ''
){

    if(
        strlen(
            $contact
        ) !== 11
    ){

        addMemberFail(
            "Contact Number must contain exactly 11 digits."
        );
    }


    if(
        !str_starts_with(
            $contact,
            '09'
        )
        &&
        !str_starts_with(
            $contact,
            '08'
        )
    ){

        addMemberFail(
            "Contact Number must start with 08 or 09."
        );
    }


    $contactDB =
        $contact;
}


/* =========================================================
   SOCIAL MEDIA
========================================================= */

if(
    $socialMediaName === ''
){

    addMemberFail(
        "Facebook / Messenger name is required."
    );
}


if(
    mb_strlen(
        $socialMediaName
    ) > 150
){

    addMemberFail(
        "Facebook / Messenger name is too long."
    );
}


$socialMediaDB =
    $socialMediaName;


/* =========================================================
   FARM FIELD

   REQUIRED FOR MEMBER REGISTRATION.
   Unit: hectares.
========================================================= */

if(
    $farmFieldRaw === ''
){

    addMemberFail(
        "Farm Field is required."
    );
}


$farmFieldDB =
    null;


if(
    $farmFieldRaw !== ''
){

    if(
        !preg_match(
            '/^\d+(?:\.\d{1,4})?$/',
            $farmFieldRaw
        )
    ){

        addMemberFail(
            "Farm Field must contain a valid hectare value."
        );
    }


    $farmFieldValue =
        (float)$farmFieldRaw;


    if(
        $farmFieldValue < 0
        ||
        $farmFieldValue > 999999.9999
    ){

        addMemberFail(
            "Farm Field value is outside the allowed range."
        );
    }


    $farmFieldDB =
        number_format(
            $farmFieldValue,
            4,
            '.',
            ''
        );
}


/* =========================================================
   INITIAL CBU

   OPTIONAL.

   IMPORTANT:
   Classification is decided HERE on the server.
   We do NOT trust the classification sent by the browser.
========================================================= */

$initialCbu =
    0.00;


if(
    $initialCbuRaw !== ''
){

    if(
        !preg_match(
            '/^\d+(?:\.\d{1,2})?$/',
            $initialCbuRaw
        )
    ){

        addMemberFail(
            "Initial CBU must contain a valid amount."
        );
    }


    $initialCbu =
        (float)$initialCbuRaw;


    if(
        $initialCbu < 0
        ||
        $initialCbu > 9999999999.99
    ){

        addMemberFail(
            "Initial CBU amount is outside the allowed range."
        );
    }

}


$cbuBalance =
    number_format(
        $initialCbu,
        2,
        '.',
        ''
    );


/* =========================================================
   AUTOMATIC CLASSIFICATION

   Once a member reaches ₱10,000:
   - Classification becomes Regular.
   - cbu_completed_at gets a permanent timestamp.

   Future deductions will NOT remove that timestamp.
========================================================= */

if(
    $initialCbu >= 10000
){

    $classification =
        'Regular';


    $cbuCompletedAt =
        date(
            'Y-m-d H:i:s'
        );

}
else{

    $classification =
        'Irregular';


    $cbuCompletedAt =
        null;

}


/* =========================================================
   MEMBER STATUS
========================================================= */

if(
    !in_array(
        $status,
        [
            'ACTIVE',
            'INACTIVE'
        ],
        true
    )
){

    addMemberFail(
        "Please select a valid member status."
    );
}

/* =========================================================
   VALIDATE PROFILE IMAGE BEFORE DATABASE CHANGES
========================================================= */

$profileUpload =
    null;


if(
    isset(
        $_FILES['profile']
    )
    &&
    $_FILES['profile']['error']
        !== UPLOAD_ERR_NO_FILE
){

    if(
        $_FILES['profile']['error']
        !== UPLOAD_ERR_OK
    ){

        addMemberFail(
            "Unable to upload the profile image."
        );
    }


    if(
        $_FILES['profile']['size']
        > 5 * 1024 * 1024
    ){

        addMemberFail(
            "Profile image must not exceed 5 MB."
        );
    }


    if(
        !is_uploaded_file(
            $_FILES['profile']['tmp_name']
        )
    ){

        addMemberFail(
            "Invalid profile image upload."
        );
    }


    $profileImageInfo =
        @getimagesize(
            $_FILES['profile']['tmp_name']
        );


    if(
        $profileImageInfo === false
    ){

        addMemberFail(
            "Invalid profile image file."
        );
    }


    $profileImageType =
        (int)(
            $profileImageInfo[2]
            ??
            0
        );


    $allowedProfileTypes = [

        IMAGETYPE_JPEG
            =>
        'jpg',

        IMAGETYPE_PNG
            =>
        'png',

        IMAGETYPE_WEBP
            =>
        'webp'

    ];


    if(
        !isset(
            $allowedProfileTypes[
                $profileImageType
            ]
        )
    ){

        $detectedProfileType =
            image_type_to_extension(
                $profileImageType,
                false
            );

        $detectedProfileType =
            $detectedProfileType
            ?
            strtoupper(
                $detectedProfileType
            )
            :
            "UNKNOWN";


        addMemberFail(
            "The selected profile image is detected as "
            .
            $detectedProfileType
            .
            ". Please use a JPG, PNG, or WEBP image."
        );
    }


    $profileUpload = [

        'tmp_name'
            =>
        $_FILES['profile']['tmp_name'],

        'extension'
            =>
        $allowedProfileTypes[
            $profileImageType
        ]

    ];

}


/* =========================================================
   VALIDATE ATTACHMENTS BEFORE DATABASE CHANGES
========================================================= */

$validatedAttachments =
    [];


if(
    isset(
        $_FILES['attachment']
    )
    &&
    isset(
        $_FILES['attachment']['name']
    )
    &&
    is_array(
        $_FILES['attachment']['name']
    )
){

    $attachmentCount =
        count(
            $_FILES['attachment']['name']
        );


    if(
        $attachmentCount > 20
    ){

        addMemberFail(
            "A maximum of 20 attachments can be uploaded at one time."
        );
    }


    $attachmentFinfo =
        new finfo(
            FILEINFO_MIME_TYPE
        );


    $allowedAttachmentTypes = [

        'application/pdf'
            =>
        'pdf',

        'image/jpeg'
            =>
        'jpg',

        'image/png'
            =>
        'png',

        'image/webp'
            =>
        'webp'

    ];


    for(
        $i = 0;
        $i < $attachmentCount;
        $i++
    ){

        $error =
            $_FILES['attachment']['error'][$i]
            ??
            UPLOAD_ERR_NO_FILE;


        if(
            $error === UPLOAD_ERR_NO_FILE
        ){
            continue;
        }


        if(
            $error !== UPLOAD_ERR_OK
        ){

            addMemberFail(
                "Unable to upload one of the supporting documents."
            );
        }


        $attachmentSize =
            (int)(
                $_FILES['attachment']['size'][$i]
                ??
                0
            );


        if(
            $attachmentSize <= 0
            ||
            $attachmentSize
                > 10 * 1024 * 1024
        ){

            addMemberFail(
                "Each supporting document must not exceed 10 MB."
            );
        }


        $tmpName =
            $_FILES['attachment']['tmp_name'][$i]
            ??
            '';


        if(
            $tmpName === ''
            ||
            !is_uploaded_file(
                $tmpName
            )
        ){

            addMemberFail(
                "Invalid supporting document upload."
            );
        }


        $mime =
            $attachmentFinfo->file(
                $tmpName
            );


        if(
            !isset(
                $allowedAttachmentTypes[
                    $mime
                ]
            )
        ){

            addMemberFail(
                "Attachments must be PDF, JPG, PNG, or WEBP files."
            );
        }


        $extension =
            $allowedAttachmentTypes[
                $mime
            ];


        if(
            in_array(
                $mime,
                [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ],
                true
            )
            &&
            @getimagesize(
                $tmpName
            )
            === false
        ){

            addMemberFail(
                "One of the image attachments is invalid."
            );
        }


        $originalName =
            pathinfo(
                $_FILES['attachment']['name'][$i],
                PATHINFO_FILENAME
            );


        $safeOriginalName =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $originalName
            );


        $safeOriginalName =
            trim(
                (string)$safeOriginalName,
                '_'
            );


        if(
            $safeOriginalName === ''
        ){

            $safeOriginalName =
                'attachment';
        }


        $validatedAttachments[] = [

            'tmp_name'
                =>
            $tmpName,

            'safe_name'
                =>
            $safeOriginalName,

            'extension'
                =>
            $extension

        ];

    }

}


if(
    count(
        $validatedAttachments
    ) === 0
){

    addMemberFail(
        "At least one supporting document is required."
    );
}


/* =========================================================
   TRANSACTION
========================================================= */

$profileFolder =
    '';


$documentFolder =
    '';


mysqli_begin_transaction(
    $conn
);


try{

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


    if(
        !$duplicateMemberStmt
    ){

        throw new Exception(
            "Unable to prepare duplicate member validation."
        );
    }


    mysqli_stmt_bind_param(
        $duplicateMemberStmt,
        's',
        $fullname
    );


    if(
        !mysqli_stmt_execute(
            $duplicateMemberStmt
        )
    ){

        mysqli_stmt_close(
            $duplicateMemberStmt
        );


        throw new Exception(
            "Unable to validate duplicate member name."
        );
    }


    $duplicateMemberResult =
        mysqli_stmt_get_result(
            $duplicateMemberStmt
        );


    $duplicateMember =
        $duplicateMemberResult
        ?
        mysqli_fetch_assoc(
            $duplicateMemberResult
        )
        :
        null;


    mysqli_stmt_close(
        $duplicateMemberStmt
    );


    if(
        $duplicateMember
    ){

        mysqli_rollback(
            $conn
        );


        addMemberFail(
            "An active member with this name already exists.",
            409
        );
    }


    /* =====================================================
       TEMPORARY MEMBER CODE

       The real code is generated using the actual
       AUTO_INCREMENT member_id. This avoids two users
       getting the same "next ID" at the same time.
    ===================================================== */

    $temporaryCode =
        'TMP'
        .
        bin2hex(
            random_bytes(
                6
            )
        );


    $profilePathDB =
        null;


    /* =====================================================
       CREATE MEMBER
    ===================================================== */

    $insertMemberStmt =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO members
            (
                member_code,
                profile_image,
                full_name,
                birth_date,
                gender,
                address,
                tin_no,
                rsbsa_no,
                contact_no,
                social_media_name,
                farm_field_hectares,
                cbu_balance,
                cbu_completed_at,
                status,
                classification,
                archived,
                date_registered
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                0,
                ?
            )
            "
        );


    if(
        !$insertMemberStmt
    ){

        throw new Exception(
            "Unable to prepare member registration."
        );
    }


    mysqli_stmt_bind_param(
        $insertMemberStmt,
        'ssssssssssssssss',
        $temporaryCode,
        $profilePathDB,
        $fullname,
        $birthDate,
        $gender,
        $address,
        $tin,
        $rsbsa,
        $contactDB,
        $socialMediaDB,
        $farmFieldDB,
        $cbuBalance,
        $cbuCompletedAt,
        $status,
        $classification,
        $membershipDate
    );


    if(
        !mysqli_stmt_execute(
            $insertMemberStmt
        )
    ){

        $insertError =
            mysqli_stmt_error(
                $insertMemberStmt
            );


        mysqli_stmt_close(
            $insertMemberStmt
        );


        throw new Exception(
            "Unable to save member: "
            .
            $insertError
        );
    }


    mysqli_stmt_close(
        $insertMemberStmt
    );

        $memberID =
        mysqli_insert_id(
            $conn
        );


    if(
        $memberID <= 0
    ){

        throw new Exception(
            "Unable to generate member ID."
        );
    }


    /* =====================================================
       FINAL MEMBER CODE
    ===================================================== */

    $memberCode =
        'MEM'
        .
        str_pad(
            (string)$memberID,
            3,
            '0',
            STR_PAD_LEFT
        );


    /* =====================================================
       SAFE MEMBER FOLDER NAME
    ===================================================== */

    $safeFullname =
        preg_replace(
            '/[^A-Za-z0-9_-]/',
            '_',
            $fullname
        );


    $safeFullname =
        trim(
            (string)$safeFullname,
            '_'
        );


    if(
        $safeFullname === ''
    ){

        $safeFullname =
            'Member';
    }


    $folderName =
        $memberCode
        .
        '_'
        .
        $safeFullname;


    $profileFolder =
        "../assets/members/"
        .
        $folderName;


    $documentFolder =
        "../assets/documents/members/"
        .
        $folderName;


    /* =====================================================
       CREATE MEMBER DIRECTORIES
    ===================================================== */

    if(
        !is_dir(
            $profileFolder
        )
        &&
        !mkdir(
            $profileFolder,
            0755,
            true
        )
    ){

        throw new Exception(
            "Unable to create member profile folder."
        );
    }


    if(
        !is_dir(
            $documentFolder
        )
        &&
        !mkdir(
            $documentFolder,
            0755,
            true
        )
    ){

        throw new Exception(
            "Unable to create member document folder."
        );
    }


    /* =====================================================
       SAVE PROFILE IMAGE
    ===================================================== */

    $profilePathDB =
        null;


    if(
        $profileUpload
        !==
        null
    ){

        $profileFileName =
            'profile.'
            .
            $profileUpload[
                'extension'
            ];


        $profileDestination =
            $profileFolder
            .
            DIRECTORY_SEPARATOR
            .
            $profileFileName;


        if(
            !move_uploaded_file(
                $profileUpload[
                    'tmp_name'
                ],
                $profileDestination
            )
        ){

            throw new Exception(
                "Unable to save the profile image."
            );
        }


        $profilePathDB =
            'assets/members/'
            .
            $folderName
            .
            '/'
            .
            $profileFileName;

    }


    /* =====================================================
       UPDATE MEMBER CODE + PROFILE
    ===================================================== */

    $updateMemberStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE members

            SET
                member_code = ?,
                profile_image = ?

            WHERE member_id = ?

            LIMIT 1
            "
        );


    if(
        !$updateMemberStmt
    ){

        throw new Exception(
            "Unable to finalize member record."
        );
    }


    mysqli_stmt_bind_param(
        $updateMemberStmt,
        'ssi',
        $memberCode,
        $profilePathDB,
        $memberID
    );


    if(
        !mysqli_stmt_execute(
            $updateMemberStmt
        )
    ){

        mysqli_stmt_close(
            $updateMemberStmt
        );


        throw new Exception(
            "Unable to finalize member information."
        );
    }


    mysqli_stmt_close(
        $updateMemberStmt
    );


    /* =====================================================
       SAVE SUPPORTING DOCUMENTS
    ===================================================== */

    $savedAttachmentCount =
        0;


    if(
        count(
            $validatedAttachments
        ) > 0
    ){

        $attachmentStmt =
            mysqli_prepare(
                $conn,
                "
                INSERT INTO member_attachments
                (
                    member_id,
                    file_path
                )
                VALUES
                (
                    ?,
                    ?
                )
                "
            );


        if(
            !$attachmentStmt
        ){

            throw new Exception(
                "Unable to prepare supporting documents."
            );
        }


        foreach(
            $validatedAttachments
            as
            $index
            =>
            $attachment
        ){

            $randomSuffix =
                bin2hex(
                    random_bytes(
                        4
                    )
                );


            $finalName =
                $attachment[
                    'safe_name'
                ]
                .
                '_'
                .
                $randomSuffix
                .
                '.'
                .
                $attachment[
                    'extension'
                ];


            $destination =
                $documentFolder
                .
                DIRECTORY_SEPARATOR
                .
                $finalName;


            if(
                !move_uploaded_file(
                    $attachment[
                        'tmp_name'
                    ],
                    $destination
                )
            ){

                mysqli_stmt_close(
                    $attachmentStmt
                );


                throw new Exception(
                    "Unable to save one of the supporting documents."
                );
            }


            $filePath =
                'assets/documents/members/'
                .
                $folderName
                .
                '/'
                .
                $finalName;


            mysqli_stmt_bind_param(
                $attachmentStmt,
                'is',
                $memberID,
                $filePath
            );


            if(
                !mysqli_stmt_execute(
                    $attachmentStmt
                )
            ){

                mysqli_stmt_close(
                    $attachmentStmt
                );


                throw new Exception(
                    "Unable to record one of the supporting documents."
                );
            }


            $savedAttachmentCount++;

        }


        mysqli_stmt_close(
            $attachmentStmt
        );

    }


    /* =====================================================
       INITIAL CBU TRANSACTION

       No transaction row is created when Initial CBU = 0.
    ===================================================== */

    if(
        $initialCbu > 0
    ){

        $cbuTransactionType =
            'ADD';


        $previousBalance =
            '0.00';


        $newBalance =
            $cbuBalance;


        $cbuRemarks =
            'Initial CBU upon member registration';


        $cbuStmt =
            mysqli_prepare(
                $conn,
                "
                INSERT INTO member_cbu_transactions
                (
                    member_id,
                    transaction_type,
                    amount,
                    previous_balance,
                    new_balance,
                    remarks,
                    recorded_by,
                    transaction_date
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW()
                )
                "
            );


        if(
            !$cbuStmt
        ){

            throw new Exception(
                "Unable to prepare the initial CBU transaction."
            );
        }


        mysqli_stmt_bind_param(
            $cbuStmt,
            'isssssi',
            $memberID,
            $cbuTransactionType,
            $cbuBalance,
            $previousBalance,
            $newBalance,
            $cbuRemarks,
            $currentUserID
        );


        if(
            !mysqli_stmt_execute(
                $cbuStmt
            )
        ){

            mysqli_stmt_close(
                $cbuStmt
            );


            throw new Exception(
                "Unable to save the initial CBU transaction."
            );
        }


        mysqli_stmt_close(
            $cbuStmt
        );

    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    $historyMessage =
        'added the member "'
        .
        $fullname
        .
        '" ('
        .
        $memberCode
        .
        ') as '
        .
        $classification
        .
        ' with an initial CBU of ₱'
        .
        number_format(
            $initialCbu,
            2
        )
        .
        '.';


    if(
        $savedAttachmentCount > 0
    ){

        $historyMessage .=
            ' Added '
            .
            $savedAttachmentCount
            .
            (
                $savedAttachmentCount === 1
                ?
                ' supporting attachment.'
                :
                ' supporting attachments.'
            );

    }


    log_activity(
        $conn,
        'Members',
        'Added',
        $historyMessage,
        $memberID
    );


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    header(
        "Location: member.php?success=added"
    );


    exit();

}
catch(
    Throwable $exception
){

    mysqli_rollback(
        $conn
    );


    /*
     * Remove files/folders created before
     * the database transaction failed.
     */
    removeMemberDirectory(
        $profileFolder
    );


    removeMemberDirectory(
        $documentFolder
    );


    error_log(
        "Add Member Error: "
        .
        $exception->getMessage()
    );


    http_response_code(
        500
    );


    exit(
        "Unable to add the member. Please check the information and try again."
    );

}

?>