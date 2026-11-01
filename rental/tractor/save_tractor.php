<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}


/* ======================================================
   MANILA TIME
====================================================== */

date_default_timezone_set(
    'Asia/Manila'
);


/* ======================================================
   DATABASE
====================================================== */

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


/* ======================================================
   VERIFY LIVE RENTAL ACCESS
====================================================== */

$userID =
isset(
    $_SESSION['user_id']
)
?
(int)$_SESSION['user_id']
:
0;


if(
    $userID <= 0
){

    http_response_code(
        401
    );

    echo
    'Your session has expired. Please sign in again.';

    exit();
}


$userStmt =
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


if(!$userStmt){

    http_response_code(
        500
    );

    echo
    'Unable to verify Rental access.';

    exit();
}


mysqli_stmt_bind_param(
    $userStmt,
    'i',
    $userID
);


if(
    !mysqli_stmt_execute(
        $userStmt
    )
){

    mysqli_stmt_close(
        $userStmt
    );

    http_response_code(
        500
    );

    echo
    'Unable to verify Rental access.';

    exit();
}


mysqli_stmt_store_result(
    $userStmt
);


$hasRentalAccess =
mysqli_stmt_num_rows(
    $userStmt
)
===
1;


mysqli_stmt_close(
    $userStmt
);


if(!$hasRentalAccess){

    http_response_code(
        403
    );

    echo
    'You do not have permission to manage the Tractor Registry.';

    exit();
}


/* ======================================================
   ONLY ACCEPT POST REQUEST
====================================================== */

if($_SERVER['REQUEST_METHOD'] !== 'POST'){

    echo 'Invalid request.';

    exit();

}


/* ======================================================
   CSRF VALIDATION
====================================================== */

$sessionCsrfToken =
(string)(
    $_SESSION['tractor_csrf_token']
    ??
    ''
);


$requestCsrfToken =
(string)(
    $_POST['csrf_token']
    ??
    ''
);


if(
    $sessionCsrfToken === ''
    ||
    $requestCsrfToken === ''
    ||
    !hash_equals(
        $sessionCsrfToken,
        $requestCsrfToken
    )
){

    http_response_code(403);

    exit(
        'Invalid or expired form request. Please refresh the page and try again.'
    );
}


/* ======================================================
   GET FORM VALUES
====================================================== */

$tractorNo =
trim(
    $_POST['tractor_no']
    ??
    ''
);


$tractorName =
trim(
    $_POST['tractor_name']
    ??
    ''
);


$tractorType =
trim(
    $_POST['tractor_type']
    ??
    ''
);


/* ======================================================
   REQUIRED FIELDS
====================================================== */

if($tractorNo === ''){

    echo 'Please enter the tractor number.';

    exit();

}


if($tractorName === ''){

    echo 'Please enter the tractor name.';

    exit();

}


if($tractorType === ''){

    echo 'Please select the tractor type.';

    exit();

}


/* ======================================================
   VALID TRACTOR TYPES
====================================================== */

$allowedTypes = [

    'Hauling',
    'Rotor',
    'Reaper'

];


if(
    !in_array(
        $tractorType,
        $allowedTypes,
        true
    )
){

    echo 'Invalid tractor type.';

    exit();

}


/* ======================================================
   CHECK DUPLICATE TRACTOR NUMBER
====================================================== */

$checkStmt =
mysqli_prepare(
    $conn,
    "
    SELECT tractor_id
    FROM tractors
    WHERE LOWER(tractor_no) = LOWER(?)
    LIMIT 1
    "
);


if(!$checkStmt){

    echo 'Unable to validate tractor number.';

    exit();

}


mysqli_stmt_bind_param(
    $checkStmt,
    's',
    $tractorNo
);


mysqli_stmt_execute(
    $checkStmt
);


mysqli_stmt_store_result(
    $checkStmt
);


if(
    mysqli_stmt_num_rows(
        $checkStmt
    ) > 0
){

    mysqli_stmt_close(
        $checkStmt
    );


    echo 'This tractor number is already registered.';

    exit();

}


mysqli_stmt_close(
    $checkStmt
);


/* ======================================================
   SAVE TRACTOR
====================================================== */

$status =
'Available';


$insertStmt =
mysqli_prepare(
    $conn,
    "
    INSERT INTO tractors
    (
        tractor_no,
        tractor_name,
        tractor_type,
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


if(!$insertStmt){

    echo 'Unable to prepare tractor record.';

    exit();

}


mysqli_stmt_bind_param(
    $insertStmt,
    'ssss',
    $tractorNo,
    $tractorName,
    $tractorType,
    $status
);


/* ======================================================
   EXECUTE
====================================================== */

if(
    mysqli_stmt_execute(
        $insertStmt
    )
){

    $tractorID =
    mysqli_insert_id(
        $conn
    );


    mysqli_stmt_close(
        $insertStmt
    );


    /* ======================================================
       SAVE HISTORY LOG
    ====================================================== */

    log_activity(
        $conn,
        'Rental',
        'Added',
        'added the ' .
        $tractorType .
        ' tractor "' .
        $tractorNo .
        ' - ' .
        $tractorName .
        '".',
        $tractorID
    );


    echo 'success';

    exit();

}


/* ======================================================
   DATABASE ERROR
====================================================== */

mysqli_stmt_close(
    $insertStmt
);


echo 'Unable to register tractor.';

exit();

?>