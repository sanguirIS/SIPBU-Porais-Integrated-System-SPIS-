<?php

/* =========================================================
   MEMBER CBU HISTORY
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


header(
    'Content-Type: application/json; charset=utf-8'
);

header(
    'X-Content-Type-Options: nosniff'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);


/* =========================================================
   DATABASE
========================================================= */

require_once '../db.php';


if (
    !isset($conn)
    ||
    !($conn instanceof mysqli)
) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Database connection is unavailable.'
    ]);

    exit();
}


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   AUTHENTICATION
========================================================= */

if (
    !isset(
        $_SESSION['user_id']
    )
) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Your session has expired. Please log in again.'
    ]);

    exit();
}


/* =========================================================
   MEMBERS ACCESS
========================================================= */

if (
    !isset(
        $_SESSION['members_access']
    )
    ||
    (int)$_SESSION['members_access'] !== 1
) {

    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Access denied.'
    ]);

    exit();
}


/* =========================================================
   GET REQUEST ONLY
========================================================= */

if (
    $_SERVER['REQUEST_METHOD']
    !==
    'GET'
) {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);

    exit();
}


/* =========================================================
   MEMBER ID
========================================================= */

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

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid member record.'
    ]);

    exit();
}


/* =========================================================
   GET MEMBER
========================================================= */

$memberStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            member_id,
            member_code,
            full_name,
            cbu_balance,
            classification,
            status

        FROM members

        WHERE member_id = ?

        AND archived = 0

        LIMIT 1
        "
    );


if (
    !$memberStmt
) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare member information.'
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $memberStmt,
    'i',
    $memberID
);


if (
    !mysqli_stmt_execute(
        $memberStmt
    )
) {

    mysqli_stmt_close(
        $memberStmt
    );


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve member information.'
    ]);

    exit();
}


$memberResult =
    mysqli_stmt_get_result(
        $memberStmt
    );


if (
    !$memberResult
    ||
    mysqli_num_rows(
        $memberResult
    ) === 0
) {

    mysqli_stmt_close(
        $memberStmt
    );


    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Member record was not found.'
    ]);

    exit();
}


$member =
    mysqli_fetch_assoc(
        $memberResult
    );


mysqli_stmt_close(
    $memberStmt
);


/* =========================================================
   MEMBER SUMMARY
========================================================= */

$memberName =
    trim(
        (string)(
            $member['full_name']
            ??
            ''
        )
    );


$memberCode =
    trim(
        (string)(
            $member['member_code']
            ??
            ''
        )
    );


$currentCbu =
    number_format(
        (float)(
            $member['cbu_balance']
            ??
            0
        ),
        2,
        '.',
        ''
    );


$classification =
    trim(
        (string)(
            $member['classification']
            ??
            'Irregular'
        )
    );


$status =
    trim(
        (string)(
            $member['status']
            ??
            ''
        )
    );


/* =========================================================
   GET CBU TRANSACTIONS
========================================================= */

$historyStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            cbu.transaction_type,
            cbu.amount,
            cbu.previous_balance,
            cbu.new_balance,
            cbu.remarks,
            cbu.recorded_by,
            cbu.transaction_date,

            u.name AS recorded_by_name

        FROM member_cbu_transactions AS cbu

        LEFT JOIN users AS u
            ON u.user_id = cbu.recorded_by

        WHERE cbu.member_id = ?

        ORDER BY
            cbu.transaction_date DESC
        "
    );


if (
    !$historyStmt
) {

    error_log(
        'CBU History Prepare Error: '
        .
        mysqli_error(
            $conn
        )
    );


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to prepare CBU history.'
    ]);

    exit();
}


mysqli_stmt_bind_param(
    $historyStmt,
    'i',
    $memberID
);


if (
    !mysqli_stmt_execute(
        $historyStmt
    )
) {

    mysqli_stmt_close(
        $historyStmt
    );


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to retrieve CBU history.'
    ]);

    exit();
}


$historyResult =
    mysqli_stmt_get_result(
        $historyStmt
    );


if (
    !$historyResult
) {

    mysqli_stmt_close(
        $historyStmt
    );


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to read CBU history.'
    ]);

    exit();
}


/* =========================================================
   BUILD TRANSACTION LIST
========================================================= */

$transactions = [];


while (
    $row =
    mysqli_fetch_assoc(
        $historyResult
    )
) {

    $transactionType =
        strtoupper(
            trim(
                (string)(
                    $row['transaction_type']
                    ??
                    ''
                )
            )
        );


    $remarks =
        trim(
            (string)(
                $row['remarks']
                ??
                ''
            )
        );


    /*
        Initial CBU is stored as ADD in the database.

        For display purposes only, identify the original
        registration transaction as INITIAL.
    */

    $displayType =
        (
            $transactionType === 'ADD'
            &&
            strcasecmp(
                $remarks,
                'Initial CBU upon member registration'
            ) === 0
        )
        ?
        'INITIAL'
        :
        $transactionType;


    $recordedByName =
        trim(
            (string)(
                $row['recorded_by_name']
                ??
                ''
            )
        );


    if (
        $recordedByName === ''
    ) {

        $recordedByID =
            (int)(
                $row['recorded_by']
                ??
                0
            );


        $recordedByName =
            $recordedByID > 0
            ?
            'User #' . $recordedByID
            :
            'Unknown User';
    }


    $transactions[] = [


        'transaction_type'
            =>
        $transactionType,


        'display_type'
            =>
        $displayType,


        'amount'
            =>
        number_format(
            (float)(
                $row['amount']
                ??
                0
            ),
            2,
            '.',
            ''
        ),


        'previous_balance'
            =>
        number_format(
            (float)(
                $row['previous_balance']
                ??
                0
            ),
            2,
            '.',
            ''
        ),


        'new_balance'
            =>
        number_format(
            (float)(
                $row['new_balance']
                ??
                0
            ),
            2,
            '.',
            ''
        ),


        'remarks'
            =>
        $remarks !== ''
        ?
        $remarks
        :
        '—',


        'recorded_by'
            =>
        $recordedByName,


        'transaction_date'
            =>
        (string)(
            $row['transaction_date']
            ??
            ''
        )

    ];

}


mysqli_stmt_close(
    $historyStmt
);


/* =========================================================
   RESPONSE
========================================================= */

echo json_encode(
    [

        'success' => true,


        'member' => [

            'member_id'
                =>
            $memberID,


            'member_code'
                =>
            $memberCode,


            'full_name'
                =>
            $memberName,


            'current_cbu'
                =>
            $currentCbu,


            'classification'
                =>
            $classification,


            'status'
                =>
            $status

        ],


        'transactions'
            =>
        $transactions

    ],
    JSON_UNESCAPED_UNICODE
    |
    JSON_UNESCAPED_SLASHES
);

exit();