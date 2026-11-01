<?php

/* =====================================================
   ACCESS
===================================================== */

require_once '../includes/check_access.php';

checkAccess(
    "sales_access"
);


/* =====================================================
   DATABASE
===================================================== */

include '../db.php';


/* =====================================================
   RESPONSE TYPE
===================================================== */

header(
    'Content-Type: text/html; charset=UTF-8'
);


/* =====================================================
   GET SEARCH KEYWORD
===================================================== */

$keyword =
trim(
    $_GET['keyword']
    ??
    ''
);


$keyword =
substr(
    $keyword,
    0,
    100
);


/*
Do not search until at least 3 characters
have been entered.
*/

if(strlen($keyword) < 3){

    exit();

}


/* =====================================================
   SEARCH ACTIVE MEMBERS
===================================================== */

$searchValue =
'%'
.
$keyword
.
'%';


$memberStatement =
mysqli_prepare(
    $conn,
    "
    SELECT
        member_id,
        member_code,
        full_name

    FROM members

    WHERE archived = 0

    AND
    (
        full_name LIKE ?
        OR member_code LIKE ?
    )

    ORDER BY full_name ASC

    LIMIT 10
    "
);


if(!$memberStatement){

    http_response_code(500);

    ?>

    <div class="sales-member-search-message">

        Unable to search members.

    </div>

    <?php

    exit();

}


mysqli_stmt_bind_param(
    $memberStatement,
    'ss',
    $searchValue,
    $searchValue
);


if(
    !mysqli_stmt_execute(
        $memberStatement
    )
){

    mysqli_stmt_close(
        $memberStatement
    );


    http_response_code(500);

    ?>

    <div class="sales-member-search-message">

        Unable to search members.

    </div>

    <?php

    exit();

}


$memberResult =
mysqli_stmt_get_result(
    $memberStatement
);


/* =====================================================
   DISPLAY RESULTS
===================================================== */

$memberFound =
false;


while(
    $member =
    mysqli_fetch_assoc(
        $memberResult
    )
){

    $memberFound =
    true;


    $memberID =
    (int)$member['member_id'];


    $memberName =
    trim(
        $member['full_name']
    );


    $memberCode =
    trim(
        $member['member_code']
        ??
        ''
    );

    ?>

    <div
    class="member-item sales-member-item"
    data-id="<?= $memberID ?>"
    data-name="<?= htmlspecialchars(
        $memberName,
        ENT_QUOTES,
        'UTF-8'
    ) ?>">

        <span class="sales-member-name">

            <?= htmlspecialchars(
                $memberName,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </span>


        <?php if($memberCode !== ''): ?>

            <span class="sales-member-code">

                <?= htmlspecialchars(
                    $memberCode,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </span>

        <?php endif; ?>

    </div>

    <?php

}


mysqli_stmt_close(
    $memberStatement
);


/* =====================================================
   NO RESULTS
===================================================== */

if(!$memberFound){

    ?>

    <div class="sales-member-search-message">

        No registered member found.

    </div>

    <?php

}

?>