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
    (int)$_SESSION['members_access'] !== 1
){
    http_response_code(403);
    exit('Access denied.');
}


/* =========================================================
   POST ONLY
========================================================= */

if(
    $_SERVER['REQUEST_METHOD'] !== 'POST'
){
    http_response_code(405);
    exit('Invalid request.');
}


/* =========================================================
   HELPERS
========================================================= */

function updateMemberFail(
    string $message,
    int $statusCode = 400
): void {
    http_response_code($statusCode);
    exit($message);
}

function updateMemberMoneyToCents(
    string $amount
): int {
    return (int)round(
        ((float)$amount) * 100
    );
}

function updateMemberCentsToMoney(
    int $cents
): string {
    return number_format(
        $cents / 100,
        2,
        '.',
        ''
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
    updateMemberFail(
        'Invalid or expired form request. Please refresh the page and try again.',
        403
    );
}


/* =========================================================
   FORM VALUES
========================================================= */

$currentUserID =
    (int)$_SESSION['user_id'];

$memberID =
    isset($_POST['member_id'])
    ?
    (int)$_POST['member_id']
    :
    0;

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

$status =
    strtoupper(
        trim(
            $_POST['status']
            ??
            ''
        )
    );

$cbuAction =
    strtoupper(
        trim(
            $_POST['cbu_action']
            ??
            ''
        )
    );

$cbuAdjustmentRaw =
    trim(
        $_POST['cbu_adjustment_amount']
        ??
        ''
    );

$cbuRemarks =
    trim(
        $_POST['cbu_adjustment_remarks']
        ??
        ''
    );

$page =
    isset($_POST['page'])
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


/* =========================================================
   MEMBER ID
========================================================= */

if($memberID <= 0){
    updateMemberFail(
        'Invalid member record.'
    );
}


/* =========================================================
   FULL NAME
========================================================= */

if($fullname === ''){
    updateMemberFail(
        'Member name is required.'
    );
}

if(
    mb_strlen($fullname) > 255
){
    updateMemberFail(
        'Member name is too long.'
    );
}

if(
    !preg_match(
        "/^[\p{L}\s.'-]+$/u",
        $fullname
    )
){
    updateMemberFail(
        'Please enter a valid member name.'
    );
}


/* =========================================================
   MEMBERSHIP DATE
========================================================= */

if($membershipDate === ''){

    updateMemberFail(
        'Membership date is required.'
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

    updateMemberFail(
        'Invalid membership date.'
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
    $membershipDateObject > $membershipToday
){

    updateMemberFail(
        'Membership date cannot be in the future.'
    );
}


/* =========================================================
   BIRTH DATE
========================================================= */

if($birthDate === ''){
    updateMemberFail(
        'Birth date is required.'
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
    $birthDateObject->format('Y-m-d') !== $birthDate
){
    updateMemberFail(
        'Invalid birth date.'
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
    $birthDateObject > $today
){
    updateMemberFail(
        'Birth date cannot be in the future.'
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
    updateMemberFail(
        'Please select a valid gender.'
    );
}


/* =========================================================
   ADDRESS
========================================================= */

if($address === ''){
    updateMemberFail(
        'Address is required.'
    );
}


/* =========================================================
   TIN
========================================================= */

if(
    strlen($tin) !== 9
    &&
    strlen($tin) !== 12
){
    updateMemberFail(
        'TIN Number must contain exactly 9 or 12 digits.'
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

    updateMemberFail(
        'RSBSA Number is required.'
    );
}


if(
    !preg_match(
        '/^[A-Z0-9-]+$/',
        $rsbsa
    )
){

    updateMemberFail(
        'RSBSA Number may contain letters, numbers, and hyphens only.'
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

    updateMemberFail(
        'RSBSA Number contains invalid characters.'
    );
}


$rsbsaLength =
    strlen(
        $rsbsaCompact
    );


if(
    $rsbsaLength < 14
){

    updateMemberFail(
        'RSBSA Number must contain at least 14 letters or numbers.'
    );
}


if(
    $rsbsaLength > 26
){

    updateMemberFail(
        'RSBSA Number is too long.'
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

    updateMemberFail(
        'RSBSA Number is too long.'
    );
}


/* =========================================================
   CONTACT NUMBER - OPTIONAL
========================================================= */

$contactDB = null;

if($contact !== ''){

    if(strlen($contact) !== 11){
        updateMemberFail(
            'Contact Number must contain exactly 11 digits.'
        );
    }

    if(
        !str_starts_with($contact, '09')
        &&
        !str_starts_with($contact, '08')
    ){
        updateMemberFail(
            'Contact Number must start with 08 or 09.'
        );
    }

    $contactDB = $contact;
}


/* =========================================================
   FACEBOOK / MESSENGER
   Required by the current member form.
========================================================= */

if($socialMediaName === ''){
    updateMemberFail(
        'Facebook / Messenger name is required.'
    );
}

if(
    mb_strlen($socialMediaName) > 150
){
    updateMemberFail(
        'Facebook / Messenger name is too long.'
    );
}

$socialMediaDB =
    $socialMediaName;


/* =========================================================
   FARM FIELD
   Required by the current member form.
========================================================= */

if($farmFieldRaw === ''){
    updateMemberFail(
        'Farm Field is required.'
    );
}

if(
    !preg_match(
        '/^\d+(?:\.\d{1,4})?$/',
        $farmFieldRaw
    )
){
    updateMemberFail(
        'Farm Field must contain a valid hectare value.'
    );
}

$farmFieldValue =
    (float)$farmFieldRaw;

if(
    $farmFieldValue < 0
    ||
    $farmFieldValue > 999999.9999
){
    updateMemberFail(
        'Farm Field value is outside the allowed range.'
    );
}

$farmFieldDB =
    number_format(
        $farmFieldValue,
        4,
        '.',
        ''
    );


/* =========================================================
   STATUS
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
    updateMemberFail(
        'Please select a valid member status.'
    );
}


/* =========================================================
   CBU ADJUSTMENT INPUT

   CBU fields are OPTIONAL during an ordinary member edit.
   When a CBU action is selected, a positive amount is required.
========================================================= */

$cbuAdjustmentCents = 0;

if($cbuAction === ''){

    if(
        $cbuAdjustmentRaw !== ''
        ||
        $cbuRemarks !== ''
    ){
        updateMemberFail(
            'Please select ADD CBU or DEDUCT CBU before entering CBU transaction details.'
        );
    }

}
else{

    if(
        !in_array(
            $cbuAction,
            [
                'ADD',
                'DEDUCT'
            ],
            true
        )
    ){
        updateMemberFail(
            'Invalid CBU action.'
        );
    }

    if($cbuAdjustmentRaw === ''){
        updateMemberFail(
            'CBU amount is required.'
        );
    }

    if(
        !preg_match(
            '/^\d+(?:\.\d{1,2})?$/',
            $cbuAdjustmentRaw
        )
    ){
        updateMemberFail(
            'CBU amount must contain a valid amount.'
        );
    }

    $cbuAdjustmentCents =
        updateMemberMoneyToCents(
            $cbuAdjustmentRaw
        );

    if($cbuAdjustmentCents <= 0){
        updateMemberFail(
            'CBU amount must be greater than zero.'
        );
    }

    if(
        $cbuAdjustmentCents > 999999999999
    ){
        updateMemberFail(
            'CBU amount is outside the allowed range.'
        );
    }

    if(
        mb_strlen($cbuRemarks) > 255
    ){
        updateMemberFail(
            'CBU remarks must not exceed 255 characters.'
        );
    }
}


/* =========================================================
   VALIDATE PROFILE IMAGE BEFORE DATABASE CHANGES
========================================================= */

$profileUpload = null;

if(
    isset($_FILES['profile'])
    &&
    ($_FILES['profile']['error'] ?? UPLOAD_ERR_NO_FILE)
        !== UPLOAD_ERR_NO_FILE
){

    if(
        ($_FILES['profile']['error'] ?? UPLOAD_ERR_NO_FILE)
        !== UPLOAD_ERR_OK
    ){
        updateMemberFail(
            'Unable to upload the profile image.'
        );
    }

    if(
        (int)($_FILES['profile']['size'] ?? 0)
        > 5 * 1024 * 1024
    ){
        updateMemberFail(
            'Profile image must not exceed 5 MB.'
        );
    }

    $profileTmp =
        (string)(
            $_FILES['profile']['tmp_name']
            ??
            ''
        );

    if(
        $profileTmp === ''
        ||
        !is_uploaded_file($profileTmp)
    ){
        updateMemberFail(
            'Invalid profile image upload.'
        );
    }

    $profileImageInfo =
        @getimagesize(
            $profileTmp
        );

    if(
        $profileImageInfo === false
    ){
        updateMemberFail(
            'Invalid profile image file.'
        );
    }

    $profileImageType =
        (int)(
            $profileImageInfo[2]
            ??
            0
        );

    $allowedProfileTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];

    if(
        !isset(
            $allowedProfileTypes[
                $profileImageType
            ]
        )
    ){
        updateMemberFail(
            'Profile image must be JPG, PNG, or WEBP.'
        );
    }

    $profileUpload = [
        'tmp_name' =>
            $profileTmp,

        'extension' =>
            $allowedProfileTypes[
                $profileImageType
            ]
    ];
}

/* =========================================================
   VALIDATE ATTACHMENTS BEFORE DATABASE CHANGES
========================================================= */

$validatedAttachments = [];

if(
    isset($_FILES['attachment'])
    &&
    isset($_FILES['attachment']['name'])
    &&
    is_array($_FILES['attachment']['name'])
){

    $attachmentCount =
        count(
            $_FILES['attachment']['name']
        );

    if($attachmentCount > 20){
        updateMemberFail(
            'A maximum of 20 attachments can be uploaded at one time.'
        );
    }

    $attachmentFinfo =
        new finfo(
            FILEINFO_MIME_TYPE
        );

    $allowedAttachmentTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp'
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

        if($error === UPLOAD_ERR_NO_FILE){
            continue;
        }

        if($error !== UPLOAD_ERR_OK){
            updateMemberFail(
                'Unable to upload one of the supporting documents.'
            );
        }

        $size =
            (int)(
                $_FILES['attachment']['size'][$i]
                ??
                0
            );

        if(
            $size <= 0
            ||
            $size > 10 * 1024 * 1024
        ){
            updateMemberFail(
                'Each supporting document must not exceed 10 MB.'
            );
        }

        $tmpName =
            (string)(
                $_FILES['attachment']['tmp_name'][$i]
                ??
                ''
            );

        if(
            $tmpName === ''
            ||
            !is_uploaded_file($tmpName)
        ){
            updateMemberFail(
                'Invalid supporting document upload.'
            );
        }

        $mime =
            $attachmentFinfo->file(
                $tmpName
            );

        if(
            !isset(
                $allowedAttachmentTypes[$mime]
            )
        ){
            updateMemberFail(
                'Attachments must be PDF, JPG, PNG, or WEBP files.'
            );
        }

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
            @getimagesize($tmpName) === false
        ){
            updateMemberFail(
                'One of the image attachments is invalid.'
            );
        }

        $originalBaseName =
            pathinfo(
                (string)(
                    $_FILES['attachment']['name'][$i]
                    ??
                    ''
                ),
                PATHINFO_FILENAME
            );

        $safeOriginalName =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $originalBaseName
            );

        $safeOriginalName =
            trim(
                (string)$safeOriginalName,
                '_'
            );

        if($safeOriginalName === ''){
            $safeOriginalName = 'attachment';
        }

        $validatedAttachments[] = [
            'tmp_name' => $tmpName,
            'safe_name' => $safeOriginalName,
            'extension' => $allowedAttachmentTypes[$mime]
        ];
    }
}


/* =========================================================
   DATABASE TRANSACTION
========================================================= */

$newPhysicalFiles = [];
$oldProfilePhysicalPath = null;
$newProfileUploaded = false;

mysqli_begin_transaction(
    $conn
);

try{

    /* =====================================================
       LOCK CURRENT MEMBER
    ===================================================== */

    $checkStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                m.member_id,
                m.member_code,
                m.profile_image,
                m.full_name,
                m.birth_date,
                m.date_registered,
                m.gender,
                m.address,
                m.tin_no,
                m.rsbsa_no,
                m.contact_no,
                m.social_media_name,
                m.farm_field_hectares,
                m.cbu_balance,
                m.cbu_completed_at,
                m.status,
                m.classification,
                (
                    SELECT ma.file_path
                    FROM member_attachments AS ma
                    WHERE ma.member_id = m.member_id
                    ORDER BY ma.attachment_id ASC
                    LIMIT 1
                ) AS sample_attachment_path
            FROM members AS m
            WHERE m.member_id = ?
            AND m.archived = 0
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
        !mysqli_stmt_execute($checkStmt)
    ){
        mysqli_stmt_close($checkStmt);
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
        mysqli_num_rows($memberResult) === 0
    ){
        mysqli_stmt_close($checkStmt);
        throw new Exception(
            'Member record was not found.'
        );
    }

    $currentMember =
        mysqli_fetch_assoc(
            $memberResult
        );

    mysqli_stmt_close(
        $checkStmt
    );


    /* =====================================================
       CURRENT VALUES
    ===================================================== */

    $memberCode =
        trim(
            $currentMember['member_code']
            ??
            ''
        );

    $currentProfilePath =
        trim(
            $currentMember['profile_image']
            ??
            ''
        );

    $currentFullname =
        trim(
            $currentMember['full_name']
            ??
            ''
        );

    $currentBirthDate =
        trim(
            $currentMember['birth_date']
            ??
            ''
        );

    $currentMembershipDate =
        '';


    if(
        !empty(
            $currentMember['date_registered']
        )
    ){

        $currentMembershipTimestamp =
            strtotime(
                (string)$currentMember['date_registered']
            );


        if(
            $currentMembershipTimestamp !== false
        ){

            $currentMembershipDate =
                date(
                    'Y-m-d',
                    $currentMembershipTimestamp
                );

        }

    }


    $currentGender =
        trim(
            $currentMember['gender']
            ??
            ''
        );

    $currentAddress =
        trim(
            $currentMember['address']
            ??
            ''
        );

    $currentTIN =
        preg_replace(
            '/[^0-9]/',
            '',
            $currentMember['tin_no']
            ??
            ''
        );

    $currentRSBSA =
        trim(
            $currentMember['rsbsa_no']
            ??
            ''
        );

    $currentContact =
        preg_replace(
            '/[^0-9]/',
            '',
            $currentMember['contact_no']
            ??
            ''
        );

    $currentSocialMedia =
        trim(
            $currentMember['social_media_name']
            ??
            ''
        );

    $currentFarmField =
        $currentMember['farm_field_hectares'];

    $currentStatus =
        strtoupper(
            trim(
                $currentMember['status']
                ??
                ''
            )
        );

    $currentClassification =
        ucfirst(
            strtolower(
                trim(
                    $currentMember['classification']
                    ??
                    'Irregular'
                )
            )
        );

    $currentCbuCompletedAt =
        $currentMember['cbu_completed_at'];

    $currentCbuCents =
        (int)round(
            ((float)(
                $currentMember['cbu_balance']
                ??
                0
            ))
            * 100
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


        if(
            !$duplicateMemberStmt
        ){

            throw new Exception(
                "Unable to prepare duplicate member validation."
            );
        }


        mysqli_stmt_bind_param(
            $duplicateMemberStmt,
            'is',
            $memberID,
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


            updateMemberFail(
                "Another active member already uses this name.",
                409
            );
        }    


    /* =====================================================
       SERVER-SIDE CBU CALCULATION

       Never trust the browser's Current CBU or Balance After.
       The locked database value is the only source of truth.
    ===================================================== */

    $newCbuCents =
        $currentCbuCents;

    $cbuTransactionAmount = null;
    $cbuTransactionRemarks = null;

    if($cbuAction !== ''){

        if($cbuAction === 'ADD'){

            $newCbuCents =
                $currentCbuCents
                +
                $cbuAdjustmentCents;

            if(
                $newCbuCents > 999999999999
            ){
                throw new Exception(
                    'The resulting CBU balance is outside the allowed range.'
                );
            }

        }
        else{

            if(
                $cbuAdjustmentCents > $currentCbuCents
            ){
                throw new Exception(
                    'CBU deduction cannot be greater than the current balance.'
                );
            }

            $newCbuCents =
                $currentCbuCents
                -
                $cbuAdjustmentCents;
        }

        $cbuTransactionAmount =
            updateMemberCentsToMoney(
                $cbuAdjustmentCents
            );

        $cbuTransactionRemarks =
            $cbuRemarks !== ''
            ?
            $cbuRemarks
            :
            (
                $cbuAction === 'ADD'
                ?
                'CBU addition'
                :
                'CBU deduction'
            );
    }

    $newCbuBalance =
        updateMemberCentsToMoney(
            $newCbuCents
        );

    $wasRegular =
        strtolower($currentClassification) === 'regular'
        ||
        !empty($currentCbuCompletedAt);

    $newClassification =
        (
            $wasRegular
            ||
            $newCbuCents >= 1000000
        )
        ?
        'Regular'
        :
        'Irregular';

    $newCbuCompletedAt =
        $currentCbuCompletedAt;

    if(
        !$wasRegular
        &&
        $newCbuCents >= 1000000
        &&
        empty($newCbuCompletedAt)
    ){
        $newCbuCompletedAt =
            date('Y-m-d H:i:s');
    }


    /* =====================================================
       MEMBER STORAGE FOLDER
    ===================================================== */

    $folderName = '';

    if($currentProfilePath !== ''){
        $folderName =
            basename(
                dirname(
                    str_replace(
                        '\\',
                        '/',
                        $currentProfilePath
                    )
                )
            );
    }

    if(
        $folderName === ''
        ||
        $folderName === '.'
    ){
        $sampleAttachmentPath =
            trim(
                $currentMember['sample_attachment_path']
                ??
                ''
            );

        if($sampleAttachmentPath !== ''){
            $folderName =
                basename(
                    dirname(
                        str_replace(
                            '\\',
                            '/',
                            $sampleAttachmentPath
                        )
                    )
                );
        }
    }

    if(
        $folderName === ''
        ||
        $folderName === '.'
    ){

        $safeCurrentName =
            preg_replace(
                '/[^A-Za-z0-9_-]/',
                '_',
                $currentFullname
            );

        $safeCurrentName =
            trim(
                (string)$safeCurrentName,
                '_'
            );

        if($safeCurrentName === ''){
            $safeCurrentName = 'Member';
        }

        $folderName =
            $memberCode
            .
            '_'
            .
            $safeCurrentName;
    }

    $profileFolder =
        '../assets/members/'
        .
        $folderName;

    $documentFolder =
        '../assets/documents/members/'
        .
        $folderName;

    if(
        !is_dir($profileFolder)
        &&
        !mkdir(
            $profileFolder,
            0755,
            true
        )
    ){
        throw new Exception(
            'Unable to create the member profile directory.'
        );
    }

    if(
        !is_dir($documentFolder)
        &&
        !mkdir(
            $documentFolder,
            0755,
            true
        )
    ){
        throw new Exception(
            'Unable to create the member document directory.'
        );
    }

        /* =====================================================
       OPTIONAL PROFILE UPDATE
    ===================================================== */

    $profilePath =
        $currentProfilePath !== ''
        ?
        $currentProfilePath
        :
        null;

    if($profileUpload !== null){

        $profileFileName =
            'profile_'
            .
            bin2hex(
                random_bytes(6)
            )
            .
            '.'
            .
            $profileUpload['extension'];

        $profileDestination =
            $profileFolder
            .
            DIRECTORY_SEPARATOR
            .
            $profileFileName;

        if(
            !move_uploaded_file(
                $profileUpload['tmp_name'],
                $profileDestination
            )
        ){
            throw new Exception(
                'Unable to save the new profile image.'
            );
        }

        $newPhysicalFiles[] =
            $profileDestination;

        $profilePath =
            'assets/members/'
            .
            $folderName
            .
            '/'
            .
            $profileFileName;

        $newProfileUploaded = true;

        if($currentProfilePath !== ''){
            $normalizedCurrentProfilePath =
                str_replace(
                    '\\',
                    '/',
                    ltrim(
                        $currentProfilePath,
                        '/'
                    )
                );

            if(
                str_starts_with(
                    $normalizedCurrentProfilePath,
                    'assets/members/'
                )
            ){
                $oldProfilePhysicalPath =
                    '../'
                    .
                    $normalizedCurrentProfilePath;
            }
        }
    }


    /* =====================================================
       SAVE NEW ATTACHMENT FILES
    ===================================================== */

    $newAttachmentPaths = [];

    foreach(
        $validatedAttachments
        as
        $attachment
    ){

        $finalName =
            $attachment['safe_name']
            .
            '_'
            .
            bin2hex(
                random_bytes(4)
            )
            .
            '.'
            .
            $attachment['extension'];

        $destination =
            $documentFolder
            .
            DIRECTORY_SEPARATOR
            .
            $finalName;

        if(
            !move_uploaded_file(
                $attachment['tmp_name'],
                $destination
            )
        ){
            throw new Exception(
                'Unable to save one of the supporting documents.'
            );
        }

        $newPhysicalFiles[] =
            $destination;

        $newAttachmentPaths[] =
            'assets/documents/members/'
            .
            $folderName
            .
            '/'
            .
            $finalName;
    }


    /* =====================================================
       BUILD HISTORY CHANGES
    ===================================================== */

    $changes = [];

    if($currentFullname !== $fullname){
        $changes[] =
            'name from "'
            .
            $currentFullname
            .
            '" to "'
            .
            $fullname
            .
            '"';
    }

    if(
        $currentMembershipDate
        !==
        $membershipDate
    ){

        $changes[] =
            'membership date';

    }

    if($currentBirthDate !== $birthDate){
        $changes[] = 'birth date';
    }

    if($currentGender !== $gender){
        $changes[] =
            'gender from '
            .
            $currentGender
            .
            ' to '
            .
            $gender;
    }

    if($currentAddress !== $address){
        $changes[] = 'address';
    }

    if($currentTIN !== $tin){
        $changes[] = 'TIN number';
    }

    if($currentRSBSA !== $rsbsa){
        $changes[] = 'RSBSA number';
    }

    if($currentContact !== $contact){
        $changes[] = 'contact number';
    }

    if($currentSocialMedia !== $socialMediaDB){
        $changes[] = 'Facebook / Messenger name';
    }

    $currentFarmComparable =
        $currentFarmField === null
        ?
        ''
        :
        number_format(
            (float)$currentFarmField,
            4,
            '.',
            ''
        );

    if($currentFarmComparable !== $farmFieldDB){
        $changes[] = 'farm field';
    }

    if($currentStatus !== $status){
        $changes[] =
            'status from '
            .
            $currentStatus
            .
            ' to '
            .
            $status;
    }

    if($cbuAction !== ''){
        $changes[] =
            (
                $cbuAction === 'ADD'
                ?
                'added ₱'
                :
                'deducted ₱'
            )
            .
            number_format(
                $cbuAdjustmentCents / 100,
                2
            )
            .
            ' CBU (₱'
            .
            number_format(
                $currentCbuCents / 100,
                2
            )
            .
            ' to ₱'
            .
            number_format(
                $newCbuCents / 100,
                2
            )
            .
            ')';
    }

    if(
        strtolower($currentClassification)
        !==
        strtolower($newClassification)
    ){
        $changes[] =
            'classification from '
            .
            $currentClassification
            .
            ' to '
            .
            $newClassification;
    }

    if($newProfileUploaded){
        $changes[] = 'profile image';
    }


    /* =====================================================
       UPDATE MEMBER RECORD
    ===================================================== */

    $updateStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE members
            SET
                profile_image = ?,
                full_name = ?,
                birth_date = ?,
                date_registered = ?,
                gender = ?,
                address = ?,
                tin_no = ?,
                rsbsa_no = ?,
                contact_no = ?,
                social_media_name = ?,
                farm_field_hectares = ?,
                cbu_balance = ?,
                cbu_completed_at = ?,
                status = ?,
                classification = ?
            WHERE member_id = ?
            AND archived = 0
            LIMIT 1
            "
        );

    if(!$updateStmt){
        throw new Exception(
            'Unable to prepare the member update.'
        );
    }

    mysqli_stmt_bind_param(
        $updateStmt,
        'sssssssssssssssi',
        $profilePath,
        $fullname,
        $birthDate,
        $membershipDate,
        $gender,
        $address,
        $tin,
        $rsbsa,
        $contactDB,
        $socialMediaDB,
        $farmFieldDB,
        $newCbuBalance,
        $newCbuCompletedAt,
        $status,
        $newClassification,
        $memberID
    );

    if(
        !mysqli_stmt_execute($updateStmt)
    ){

        $updateError =
            mysqli_stmt_error(
                $updateStmt
            );


        error_log(
            'Update Member SQL Error: '
            .
            (
                $updateError !== ''
                ?
                $updateError
                :
                'Unknown database error.'
            )
        );


        mysqli_stmt_close(
            $updateStmt
        );


        throw new Exception(
            'Unable to update the member record.'
        );

    }

    if(
        mysqli_stmt_affected_rows($updateStmt) < 0
    ){
        mysqli_stmt_close($updateStmt);
        throw new Exception(
            'Unable to update the member record.'
        );
    }

    mysqli_stmt_close(
        $updateStmt
    );


    /* =====================================================
       CBU TRANSACTION LEDGER
    ===================================================== */

    if($cbuAction !== ''){

        $previousBalance =
            updateMemberCentsToMoney(
                $currentCbuCents
            );

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

        if(!$cbuStmt){
            throw new Exception(
                'Unable to prepare the CBU transaction.'
            );
        }

        mysqli_stmt_bind_param(
            $cbuStmt,
            'isssssi',
            $memberID,
            $cbuAction,
            $cbuTransactionAmount,
            $previousBalance,
            $newCbuBalance,
            $cbuTransactionRemarks,
            $currentUserID
        );

        if(
            !mysqli_stmt_execute($cbuStmt)
        ){
            mysqli_stmt_close($cbuStmt);
            throw new Exception(
                'Unable to record the CBU transaction.'
            );
        }

        mysqli_stmt_close(
            $cbuStmt
        );
    }


    /* =====================================================
       SAVE ATTACHMENT DATABASE RECORDS
    ===================================================== */

    $savedAttachmentCount = 0;

    if(!empty($newAttachmentPaths)){

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

        if(!$attachmentStmt){
            throw new Exception(
                'Unable to prepare supporting attachment records.'
            );
        }

        foreach(
            $newAttachmentPaths
            as
            $attachmentPath
        ){

            mysqli_stmt_bind_param(
                $attachmentStmt,
                'is',
                $memberID,
                $attachmentPath
            );

            if(
                !mysqli_stmt_execute($attachmentStmt)
            ){
                mysqli_stmt_close($attachmentStmt);
                throw new Exception(
                    'Unable to save one of the supporting attachment records.'
                );
            }

            $savedAttachmentCount++;
        }

        mysqli_stmt_close(
            $attachmentStmt
        );
    }

    if($savedAttachmentCount > 0){
        $changes[] =
            'uploaded '
            .
            $savedAttachmentCount
            .
            (
                $savedAttachmentCount === 1
                ?
                ' supporting attachment'
                :
                ' supporting attachments'
            );
    }


    /* =====================================================
       HISTORY LOG
    ===================================================== */

    if(!empty($changes)){

        log_activity(
            $conn,
            'Members',
            'Updated',
            'updated the member "'
            .
            $currentFullname
            .
            '" ('
            .
            $memberCode
            .
            '): '
            .
            implode(
                ', ',
                $changes
            )
            .
            '.',
            $memberID
        );
    }


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit(
        $conn
    );


    /* =====================================================
       DELETE PREVIOUS PROFILE ONLY AFTER COMMIT
    ===================================================== */

    if(
        $newProfileUploaded
        &&
        $oldProfilePhysicalPath !== null
        &&
        basename($oldProfilePhysicalPath) !== 'default.png'
        &&
        is_file($oldProfilePhysicalPath)
    ){
        @unlink(
            $oldProfilePhysicalPath
        );
    }


    /* =====================================================
       REDIRECT
    ===================================================== */

    header(
        'Location: member.php?' . http_build_query([
            'page' => $page,
            'search' => $search,
            'status' => $statusFilter,
            'classification' => $classificationFilter,
            'success' => 'updated'
        ])
    );

    exit();
}
catch(Throwable $error){

    mysqli_rollback(
        $conn
    );

    foreach(
        $newPhysicalFiles
        as
        $newPhysicalFile
    ){
        if(
            is_file($newPhysicalFile)
        ){
            @unlink($newPhysicalFile);
        }
    }

    error_log(
        'Update Member Error: '
        .
        $error->getMessage()
    );

    http_response_code(400);

    exit(
        $error->getMessage()
    );
}

?>