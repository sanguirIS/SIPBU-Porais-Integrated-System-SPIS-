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


require_once '../db.php';


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


if($userID <= 0){

    http_response_code(401);

    echo '<div class="member-empty">Session expired. Please sign in again.</div>';

    exit();
}


$userStmt =
mysqli_prepare(
    $conn,
    "
    SELECT user_id
    FROM users
    WHERE user_id = ?
    AND archived = 0
    AND status = 'Active'
    AND rental_access = 1
    LIMIT 1
    "
);


if(!$userStmt){

    http_response_code(500);

    echo '<div class="member-empty">Unable to verify Rental access.</div>';

    exit();
}


mysqli_stmt_bind_param(
    $userStmt,
    'i',
    $userID
);


if(!mysqli_stmt_execute($userStmt)){

    mysqli_stmt_close($userStmt);

    http_response_code(500);

    echo '<div class="member-empty">Unable to verify Rental access.</div>';

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

    http_response_code(403);

    echo '<div class="member-empty">You do not have permission to access Rental.</div>';

    exit();
}


/* ======================================================
   GET SEARCH KEYWORD
====================================================== */

$keyword =
trim(
    $_GET['keyword']
    ??
    ''
);


if(
    strlen($keyword)
    <
    3
){
    exit();
}


/* ======================================================
   SEARCH ACTIVE MEMBERS
====================================================== */

$searchKeyword =
'%'
.
$keyword
.
'%';


$memberStmt =
mysqli_prepare(
    $conn,
    "
    SELECT
        member_id,
        full_name

    FROM members

    WHERE archived = 0

    AND status = 'Active'

    AND full_name LIKE ?

    ORDER BY full_name ASC

    LIMIT 10
    "
);


if(!$memberStmt){

    http_response_code(500);

    echo '<div class="member-empty">Unable to search members.</div>';

    exit();
}


mysqli_stmt_bind_param(
    $memberStmt,
    's',
    $searchKeyword
);


if(!mysqli_stmt_execute($memberStmt)){

    mysqli_stmt_close($memberStmt);

    http_response_code(500);

    echo '<div class="member-empty">Unable to search members.</div>';

    exit();
}


$result =
mysqli_stmt_get_result(
    $memberStmt
);


if(
    $result
    &&
    mysqli_num_rows($result) > 0
){

    while(
        $row =
        mysqli_fetch_assoc($result)
    ){

        $memberID =
        (int)$row['member_id'];


        $memberName =
        htmlspecialchars(
            (string)$row['full_name'],
            ENT_QUOTES,
            'UTF-8'
        );


        echo
        '<div class="member-item"'
        .
        ' data-id="'
        .
        $memberID
        .
        '" data-name="'
        .
        $memberName
        .
        '">'
        .
        $memberName
        .
        '</div>';
    }

}
else{

    echo '<div class="member-empty">No active member found.</div>';
}


mysqli_stmt_close(
    $memberStmt
);

?>