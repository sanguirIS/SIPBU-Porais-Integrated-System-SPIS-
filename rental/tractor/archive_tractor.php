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


/* ===========================
   ONLY ACCEPT POST REQUEST
=========================== */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo 'Invalid request.';

    exit;
}


/* ===========================
   CSRF VALIDATION
=========================== */

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


/* ===========================
   GET TRACTOR ID
=========================== */

$tractorID =
    (int)($_POST['tractor_id'] ?? 0);


if ($tractorID <= 0) {

    echo 'Invalid tractor.';

    exit;
}


/* ===========================
   GET TRACTOR INFORMATION
=========================== */

$tractorStmt = mysqli_prepare(
    $conn,
    "
    SELECT
        tractor_no,
        tractor_name,
        tractor_type,
        status
    FROM tractors
    WHERE tractor_id = ?
    AND archived = 0
    LIMIT 1
    "
);


if (!$tractorStmt) {

    echo 'Unable to check tractor record.';

    exit;
}


mysqli_stmt_bind_param(
    $tractorStmt,
    'i',
    $tractorID
);


mysqli_stmt_execute(
    $tractorStmt
);


$tractorResult =
    mysqli_stmt_get_result(
        $tractorStmt
    );


if (
    !$tractorResult
    ||
    mysqli_num_rows($tractorResult) === 0
) {

    mysqli_stmt_close(
        $tractorStmt
    );


    echo 'Tractor record was not found.';

    exit;
}


$tractor =
    mysqli_fetch_assoc(
        $tractorResult
    );


mysqli_stmt_close(
    $tractorStmt
);


/* ======================================================
   ARCHIVE SAFETY CHECK

   Do not archive when:
   - Tractor is Reserved
   - Tractor is In Use
   - It has a Pending / Ongoing rental today or later
   - It is assigned as Primary OR Secondary

   Check all Rental modules for safety.
====================================================== */

if(
    in_array(
        $tractor['status'],
        [
            'Reserved',
            'In Use'
        ],
        true
    )
){

    echo
    'This tractor currently has an active Rental assignment and cannot be archived.';

    exit();

}


/* ======================================================
   CHECK ACTIVE / UPCOMING RENTALS
====================================================== */

$activeRentalTables = [

    [
        'table' => 'hauling',
        'label' => 'Hauling'
    ],

    [
        'table' => 'rotor',
        'label' => 'Rotor'
    ],

    [
        'table' => 'reaper',
        'label' => 'Reaper'
    ]

];


foreach(
    $activeRentalTables
    as
    $rentalInfo
){

    $rentalTable =
    $rentalInfo['table'];


    $rentalLabel =
    $rentalInfo['label'];


    $rentalStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            transaction_id,
            DATE(rental_date) AS rental_date,
            status

        FROM {$rentalTable}

        WHERE
        (
            tractor_id = ?

            OR

            secondary_tractor_id = ?
        )

        AND DATE(rental_date) >= CURDATE()

        AND status IN (
            'Pending',
            'Ongoing'
        )

        ORDER BY
            rental_date ASC

        LIMIT 1
        "
    );


    if(!$rentalStmt){

        echo
        'Unable to verify tractor Rental assignments.';

        exit();

    }


    mysqli_stmt_bind_param(
        $rentalStmt,
        'ii',
        $tractorID,
        $tractorID
    );


    if(
        !mysqli_stmt_execute(
            $rentalStmt
        )
    ){

        mysqli_stmt_close(
            $rentalStmt
        );


        echo
        'Unable to verify tractor Rental assignments.';

        exit();

    }


    $rentalResult =
    mysqli_stmt_get_result(
        $rentalStmt
    );


    $activeRental =

    $rentalResult

    ?

    mysqli_fetch_assoc(
        $rentalResult
    )

    :

    null;


    mysqli_stmt_close(
        $rentalStmt
    );


    if($activeRental){

        $transactionID =
        trim(
            (string)(
                $activeRental['transaction_id']
                ??
                ''
            )
        );


        $rentalDate =
        trim(
            (string)(
                $activeRental['rental_date']
                ??
                ''
            )
        );


        echo
        'This tractor is still assigned to an active or upcoming '
        .
        $rentalLabel
        .
        ' Rental'
        .
        (
            $transactionID !== ''
            ?
            ' (' . $transactionID . ')'
            :
            ''
        )
        .
        (
            $rentalDate !== ''
            ?
            ' scheduled on ' . $rentalDate
            :
            ''
        )
        .
        ' and cannot be archived.';

        exit();

    }

}


/* ===========================
   USER WHO ARCHIVED
=========================== */

$archivedBy =
    trim(
        $_SESSION['name']
        ??
        'Unknown User'
    );


/* ===========================
   CHECK OPTIONAL COLUMNS
=========================== */

$hasArchivedBy = false;
$hasArchivedAt = false;


$columnQuery = mysqli_query(
    $conn,
    "
    SHOW COLUMNS
    FROM tractors
    "
);


if ($columnQuery) {

    while (
        $column =
        mysqli_fetch_assoc($columnQuery)
    ) {

        if ($column['Field'] === 'archived_by') {
            $hasArchivedBy = true;
        }


        if ($column['Field'] === 'archived_at') {
            $hasArchivedAt = true;
        }
    }
}


/* ===========================
   BUILD ARCHIVE QUERY
=========================== */

if ($hasArchivedBy && $hasArchivedAt) {

    $archiveSQL =
        "
        UPDATE tractors
        SET
            archived = 1,
            archived_by = ?,
            archived_at = NOW()
        WHERE tractor_id = ?
        AND archived = 0
        ";

} elseif ($hasArchivedBy) {

    $archiveSQL =
        "
        UPDATE tractors
        SET
            archived = 1,
            archived_by = ?
        WHERE tractor_id = ?
        AND archived = 0
        ";

} elseif ($hasArchivedAt) {

    $archiveSQL =
        "
        UPDATE tractors
        SET
            archived = 1,
            archived_at = NOW()
        WHERE tractor_id = ?
        AND archived = 0
        ";

} else {

    $archiveSQL =
        "
        UPDATE tractors
        SET archived = 1
        WHERE tractor_id = ?
        AND archived = 0
        ";
}


/* ===========================
   ARCHIVE TRACTOR
=========================== */

$archiveStmt = mysqli_prepare(
    $conn,
    $archiveSQL
);


if (!$archiveStmt) {

    echo 'Unable to prepare archive request.';

    exit;
}


if ($hasArchivedBy) {

    mysqli_stmt_bind_param(
        $archiveStmt,
        'si',
        $archivedBy,
        $tractorID
    );

} else {

    mysqli_stmt_bind_param(
        $archiveStmt,
        'i',
        $tractorID
    );
}


$archived =
    mysqli_stmt_execute(
        $archiveStmt
    );


$affectedRows =
    mysqli_stmt_affected_rows(
        $archiveStmt
    );


mysqli_stmt_close(
    $archiveStmt
);


/* ===========================
   SAVE HISTORY LOG
=========================== */

if (
    $archived
    &&
    $affectedRows > 0
) {

    log_activity(
        $conn,
        'Rental',
        'Archived',
        'archived the tractor "' .
        $tractor['tractor_no'] .
        ' - ' .
        $tractor['tractor_name'] .
        '".',
        $tractorID
    );


    echo 'success';

    exit;
}


echo 'Unable to archive tractor.';

exit;

?>