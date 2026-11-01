<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}

require_once "../db.php";

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

header(
    'Content-Type: application/json; charset=UTF-8'
);

if(
    !isset($_SESSION['user_id'])
    ||
    !isset($_SESSION['members_access'])
    ||
    (int)$_SESSION['members_access'] !== 1
){
    http_response_code(403);
    echo json_encode([
        'error' => 'Access denied.'
    ]);
    exit();
}

$memberID =
    isset($_GET['member_id'])
    ?
    (int)$_GET['member_id']
    :
    0;

if($memberID <= 0){
    http_response_code(400);
    echo json_encode([
        'error' => 'Invalid member record.'
    ]);
    exit();
}

$memberStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT member_id
        FROM members
        WHERE member_id = ?
        AND archived = 0
        LIMIT 1
        "
    );

if(!$memberStmt){
    http_response_code(500);
    echo json_encode([
        'error' => 'Unable to load attachments.'
    ]);
    exit();
}

mysqli_stmt_bind_param(
    $memberStmt,
    'i',
    $memberID
);

mysqli_stmt_execute(
    $memberStmt
);

$memberResult =
    mysqli_stmt_get_result(
        $memberStmt
    );

if(
    !$memberResult
    ||
    mysqli_num_rows($memberResult) === 0
){
    mysqli_stmt_close($memberStmt);
    http_response_code(404);
    echo json_encode([
        'error' => 'Member record was not found.'
    ]);
    exit();
}

mysqli_stmt_close(
    $memberStmt
);

$stmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            attachment_id,
            member_id,
            file_path
        FROM member_attachments
        WHERE member_id = ?
        ORDER BY attachment_id ASC
        "
    );

if(!$stmt){
    http_response_code(500);
    echo json_encode([
        'error' => 'Unable to load attachments.'
    ]);
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    'i',
    $memberID
);

if(!mysqli_stmt_execute($stmt)){
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode([
        'error' => 'Unable to load attachments.'
    ]);
    exit();
}

$result =
    mysqli_stmt_get_result(
        $stmt
    );

$attachments = [];

while(
    $row =
        mysqli_fetch_assoc(
            $result
        )
){
    $attachments[] = $row;
}

mysqli_stmt_close(
    $stmt
);

echo json_encode(
    $attachments,
    JSON_UNESCAPED_SLASHES
);

?>
