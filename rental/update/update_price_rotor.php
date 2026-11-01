<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}

date_default_timezone_set('Asia/Manila');

include '../../db.php';
include '../../includes/history_logger.php';

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

(int)
$_SESSION['user_id']

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


if(
    !$userStmt
){

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


if(
    !$hasRentalAccess
){

    http_response_code(
        403
    );


    echo
    'You do not have permission to access Rental.';


    exit();

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
   CSRF VALIDATION
===================================================== */

$sessionCsrfToken =
(string)(
    $_SESSION['rental_csrf_token']
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



/* =====================================================
   GET PRICE
===================================================== */

$priceRaw =
trim(
    $_POST['price_per_hectare']
    ??
    ''
);


/* =====================================================
   VALIDATE PRICE
===================================================== */

if(
    $priceRaw === ''
    ||
    !is_numeric($priceRaw)
){

    echo 'Please enter a valid Rotor price per hectare.';

    exit();

}


$newPrice =
round(
    (float)$priceRaw,
    2
);


if($newPrice <= 0){

    echo 'Rotor price per hectare must be greater than zero.';

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
       GET CURRENT ROTOR PRICE
    ================================================= */

    $checkStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            id,
            price_per_hectare

        FROM rotor_price

        WHERE id = 1

        LIMIT 1

        FOR UPDATE
        "
    );


    if(!$checkStmt){

        throw new Exception(
            'Unable to check the current Rotor price.'
        );

    }


    if(
        !mysqli_stmt_execute(
            $checkStmt
        )
    ){

        mysqli_stmt_close(
            $checkStmt
        );


        throw new Exception(
            'Unable to check the current Rotor price.'
        );

    }


    $result =
    mysqli_stmt_get_result(
        $checkStmt
    );


    $hasExistingPrice =
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0;


    $currentPrice =
    null;


    if($hasExistingPrice){

        $priceRecord =
        mysqli_fetch_assoc(
            $result
        );


        $currentPrice =
        round(
            (float)$priceRecord['price_per_hectare'],
            2
        );

    }


    mysqli_stmt_close(
        $checkStmt
    );


    /* =================================================
       SAME PRICE

       Do not update or create a duplicate History log.
    ================================================= */

    if(
        $currentPrice !== null
        &&
        $currentPrice === $newPrice
    ){

        mysqli_commit(
            $conn
        );


        header(
            'Location: ../rotor.php?price=updated'
        );


        exit();

    }


    /* =================================================
       UPDATE EXISTING PRICE
    ================================================= */

    if($hasExistingPrice){

        $updateStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE rotor_price

            SET price_per_hectare = ?

            WHERE id = 1
            "
        );


        if(!$updateStmt){

            throw new Exception(
                'Unable to prepare the Rotor price update.'
            );

        }


        mysqli_stmt_bind_param(
            $updateStmt,
            'd',
            $newPrice
        );


        if(
            !mysqli_stmt_execute(
                $updateStmt
            )
        ){

            mysqli_stmt_close(
                $updateStmt
            );


            throw new Exception(
                'Unable to update the Rotor price per hectare.'
            );

        }


        mysqli_stmt_close(
            $updateStmt
        );


        /* =============================================
           HISTORY: CHANGED EXISTING PRICE
        ============================================= */

        log_activity(
            $conn,
            'Rental',
            'Updated',
            'changed the Rotor price per hectare from ₱'
            .
            number_format(
                $currentPrice,
                2
            )
            .
            ' to ₱'
            .
            number_format(
                $newPrice,
                2
            )
            .
            '.',
            1
        );

    }


    /* =================================================
       INSERT INITIAL PRICE
    ================================================= */

    else{

        $insertStmt =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO rotor_price
            (
                id,
                price_per_hectare
            )
            VALUES
            (
                1,
                ?
            )
            "
        );


        if(!$insertStmt){

            throw new Exception(
                'Unable to prepare the initial Rotor price.'
            );

        }


        mysqli_stmt_bind_param(
            $insertStmt,
            'd',
            $newPrice
        );


        if(
            !mysqli_stmt_execute(
                $insertStmt
            )
        ){

            mysqli_stmt_close(
                $insertStmt
            );


            throw new Exception(
                'Unable to set the Rotor price per hectare.'
            );

        }


        mysqli_stmt_close(
            $insertStmt
        );


        /* =============================================
           HISTORY: SET INITIAL PRICE
        ============================================= */

        log_activity(
            $conn,
            'Rental',
            'Updated',
            'set the Rotor price per hectare to ₱'
            .
            number_format(
                $newPrice,
                2
            )
            .
            '.',
            1
        );

    }


    /* =================================================
       COMMIT
    ================================================= */

    mysqli_commit(
        $conn
    );


    header(
        'Location: ../rotor.php?price=updated'
    );


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