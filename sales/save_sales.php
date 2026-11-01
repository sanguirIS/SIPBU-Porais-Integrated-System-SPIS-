<?php

/* =========================================================
   SESSION
========================================================= */

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set(
    'Asia/Manila'
);


/* =========================================================
   INCLUDES
========================================================= */

include '../db.php';

require_once '../includes/check_access.php';

include '../includes/history_logger.php';


checkAccess(
    "sales_access"
);


/*
Keep MySQL aligned with Philippine time.
*/

mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   ERROR REDIRECT
========================================================= */

function salesError($message){

    header(
        'Location: sales.php?error='
        .
        urlencode($message)
    );

    exit();

}


/* =========================================================
   POST ONLY
========================================================= */

if(
    $_SERVER['REQUEST_METHOD']
    !==
    'POST'
){

    salesError(
        'Invalid sales request.'
    );

}

/* =========================================================
   CSRF VALIDATION
========================================================= */

$sessionToken =
    $_SESSION[
        "sales_csrf_token"
    ]
    ??
    "";


$formToken =
    $_POST[
        "csrf_token"
    ]
    ??
    "";


if (
    !is_string(
        $sessionToken
    )
    ||
    !is_string(
        $formToken
    )
    ||
    $sessionToken === ""
    ||
    $formToken === ""
    ||
    !hash_equals(
        $sessionToken,
        $formToken
    )
) {

    salesError(
        "Invalid security token. Please refresh the page and try again."
    );

}


/* =========================================================
   GET FORM VALUES
========================================================= */

$buyer =
trim(
    $_POST['buyer_name']
    ??
    ''
);


$affiliateRaw =
strtolower(
    trim(
        $_POST['affiliate']
        ??
        ''
    )
);


$memberIDRaw =
isset(
    $_POST['member_id']
)
?
(int)$_POST['member_id']
:
0;


$saleDateRaw =
trim(
    $_POST['sale_date']
    ??
    ''
);


$cartRaw =
$_POST['cart']
??
'';


/* =========================================================
   CASH RECEIVED - REQUIRED
========================================================= */

$cashReceivedRaw = trim((string) ($_POST['cash_received'] ?? ''));

if (
    !preg_match('/\A\d+(?:\.\d{1,2})?\z/', $cashReceivedRaw)
) {
    salesError('Please enter a valid Cash Received amount (up to 2 decimal places).');
}

$cashReceived = (float) $cashReceivedRaw;

if (
    !is_finite($cashReceived) ||
    $cashReceived > 9999999999999.99
) {
    salesError('Cash Received is outside the allowed range.');
}


/* =========================================================
   VALIDATE CUSTOMER TYPE
========================================================= */

$affiliateOptions = [

    'member'
    =>
    'Member',

    'not member'
    =>
    'Not Member'

];


if(
    !isset(
        $affiliateOptions[
            $affiliateRaw
        ]
    )
){

    salesError(
        'Please select a valid customer type.'
    );

}


$affiliate =
$affiliateOptions[
    $affiliateRaw
];


/* =========================================================
   VALIDATE TRANSACTION DATE
========================================================= */

if(
    $saleDateRaw
    ===
    ''
){

    salesError(
        'Transaction date is required.'
    );

}


$saleDateObject =
DateTimeImmutable::createFromFormat(
    '!Y-m-d',
    $saleDateRaw,
    new DateTimeZone(
        'Asia/Manila'
    )
);


$dateErrors =
DateTimeImmutable::getLastErrors();


if(
    !$saleDateObject
    ||
    (
        is_array($dateErrors)
        &&
        (
            $dateErrors['warning_count'] > 0
            ||
            $dateErrors['error_count'] > 0
        )
    )
    ||
    $saleDateObject->format('Y-m-d')
    !==
    $saleDateRaw
){

    salesError(
        'Invalid transaction date.'
    );

}


$todayManila =
new DateTimeImmutable(
    'today',
    new DateTimeZone(
        'Asia/Manila'
    )
);


if(
    $saleDateObject
    >
    $todayManila
){

    salesError(
        'Future transaction dates are not allowed.'
    );

}


/* =========================================================
   HISTORICAL / AUDIT FLAG

   This is decided only when the sale is created.
   It does NOT change automatically on later dates.
========================================================= */

$isHistoricalEntry =
$saleDateObject < $todayManila
?
1
:
0;


/*
Sales only needs the transaction DATE.

The actual time when the record is created is still
captured by the History module for audit purposes.
*/

$saleDateTime =
$saleDateObject
->format(
    'Y-m-d'
)
.
' 00:00:00';


/* =========================================================
   DECODE CART
========================================================= */

$cart =
json_decode(
    $cartRaw,
    true
);


if(
    !is_array($cart)
    ||
    empty($cart)
){

    salesError(
        'Please add at least one product to the cart.'
    );

}


/* =========================================================
   NORMALIZE CART

   Only product ID and quantity from the browser are used.
   Prices and totals will be taken from Inventory again.
========================================================= */

$requestedProducts = [];


foreach(
    $cart
    as
    $item
){

    $productID =
    isset(
        $item['id']
    )
    ?
    (int)$item['id']
    :
    0;


    $quantity =
    isset(
        $item['qty']
    )
    ?
    (int)$item['qty']
    :
    0;


    if(
        $productID
        <=
        0
    ){

        salesError(
            'The cart contains an invalid product.'
        );

    }


    if(
        $quantity
        <=
        0
    ){

        salesError(
            'The cart contains an invalid quantity.'
        );

    }


    /*
    Combine duplicate product IDs.
    */

    if(
        isset(
            $requestedProducts[
                $productID
            ]
        )
    ){

        $requestedProducts[
            $productID
        ]
        +=
        $quantity;

    }
    else{

        $requestedProducts[
            $productID
        ]
        =
        $quantity;

    }

}


/* =========================================================
   START DATABASE TRANSACTION
========================================================= */

mysqli_begin_transaction(
    $conn
);


try{


    /* =====================================================
       CUSTOMER VALIDATION
    ====================================================== */

    $memberID =
    null;


    /* -----------------------------------------------------
       MEMBER
    ------------------------------------------------------ */

    if(
        $affiliate
        ===
        'Member'
    ){

        if(
            $memberIDRaw
            <=
            0
        ){

            throw new Exception(
                'Please search and select a registered member.'
            );

        }


        $memberStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                member_id,
                member_code,
                full_name

            FROM members

            WHERE member_id = ?
            AND archived = 0

            LIMIT 1
            "
        );


        if(
            !$memberStmt
        ){

            throw new Exception(
                'Unable to validate the selected member.'
            );

        }


        mysqli_stmt_bind_param(
            $memberStmt,
            'i',
            $memberIDRaw
        );


        if(
            !mysqli_stmt_execute(
                $memberStmt
            )
        ){

            mysqli_stmt_close(
                $memberStmt
            );


            throw new Exception(
                'Unable to validate the selected member.'
            );

        }


        $memberResult =
        mysqli_stmt_get_result(
            $memberStmt
        );


        if(
            !$memberResult
            ||
            mysqli_num_rows(
                $memberResult
            )
            ===
            0
        ){

            mysqli_stmt_close(
                $memberStmt
            );


            throw new Exception(
                'The selected member was not found or is already archived.'
            );

        }


        $member =
        mysqli_fetch_assoc(
            $memberResult
        );


        mysqli_stmt_close(
            $memberStmt
        );


        /*
        Never trust the typed buyer name for Member.

        Always use the official member record.
        */

        $memberID =
        (int)$member[
            'member_id'
        ];


        $buyer =
        trim(
            (string)$member[
                'full_name'
            ]
        );


        $memberCode =
        trim(
            (string)(
                $member[
                    'member_code'
                ]
                ??
                ''
            )
        );

    }


    /* -----------------------------------------------------
       NOT MEMBER
    ------------------------------------------------------ */

    else{

        /*
        Buyer name is optional for Not Member transactions.

        If left blank, the transaction will be displayed
        as "Walk-in Customer" in the ledger.
        */

        $memberID =
        null;


        $memberCode =
        '';

    }


    /* =====================================================
       VALIDATE PRODUCTS AGAIN FROM DATABASE
    ====================================================== */

    $validatedItems =
    [];


    $grandTotal =
    0.00;


    $totalQuantity =
    0;


    foreach(
        $requestedProducts
        as
        $productID
        =>
        $quantity
    ){

        $productStmt =
        mysqli_prepare(
            $conn,
            "
            SELECT
                product_id,
                product_name,
                selling_price,
                quantity

            FROM inventory

            WHERE product_id = ?
            AND archived = 0

            LIMIT 1

            FOR UPDATE
            "
        );


        if(
            !$productStmt
        ){

            throw new Exception(
                'Unable to check one of the selected products.'
            );

        }


        mysqli_stmt_bind_param(
            $productStmt,
            'i',
            $productID
        );


        if(
            !mysqli_stmt_execute(
                $productStmt
            )
        ){

            mysqli_stmt_close(
                $productStmt
            );


            throw new Exception(
                'Unable to check one of the selected products.'
            );

        }


        $productResult =
        mysqli_stmt_get_result(
            $productStmt
        );


        if(
            !$productResult
            ||
            mysqli_num_rows(
                $productResult
            )
            ===
            0
        ){

            mysqli_stmt_close(
                $productStmt
            );


            throw new Exception(
                'One of the selected products is no longer available.'
            );

        }


        $product =
        mysqli_fetch_assoc(
            $productResult
        );


        mysqli_stmt_close(
            $productStmt
        );


        $productName =
        trim(
            (string)$product[
                'product_name'
            ]
        );


        $stock =
        (int)$product[
            'quantity'
        ];


        $sellingPrice =
        round(
            (float)$product[
                'selling_price'
            ],
            2
        );


        if(
            $quantity
            >
            $stock
        ){

            throw new Exception(
                'Not enough stock for "'
                .
                $productName
                .
                '". Available stock: '
                .
                $stock
                .
                '.'
            );

        }


        $subtotal =
        round(
            $sellingPrice
            *
            $quantity,
            2
        );


        $validatedItems[] = [

            'product_id'
            =>
            $productID,

            'product_name'
            =>
            $productName,

            'quantity'
            =>
            $quantity,

            'selling_price'
            =>
            $sellingPrice,

            'subtotal'
            =>
            $subtotal

        ];


        $grandTotal +=
        $subtotal;


        $totalQuantity +=
        $quantity;

    }


    $grandTotal =
    round(
        $grandTotal,
        2
    );


    /* =====================================================
       VALIDATE PAYMENT BEFORE SAVING
    ====================================================== */

    $totalCents = (int) round($grandTotal * 100);
    $cashCents = (int) round($cashReceived * 100);

    if ($cashCents < $totalCents) {
        throw new Exception(
            'Insufficient Cash Received. Payment must cover the Grand Total.'
        );
    }

    $changeAmount = ($cashCents - $totalCents) / 100;


    /* =====================================================
       CREATE TRANSACTION ID
    ====================================================== */

    /*
    The date portion follows the selected transaction date.
    */

    $datePrefix =
    $saleDateObject
    ->format(
        'Ymd'
    );


    /*
    Lock latest Sales row before generating the next number.
    */

    $lastStmt =
    mysqli_prepare(
        $conn,
        "
        SELECT
            transaction_id

        FROM sales

        ORDER BY sale_id DESC

        LIMIT 1

        FOR UPDATE
        "
    );


    if(
        !$lastStmt
    ){

        throw new Exception(
            'Unable to generate the transaction ID.'
        );

    }


    if(
        !mysqli_stmt_execute(
            $lastStmt
        )
    ){

        mysqli_stmt_close(
            $lastStmt
        );


        throw new Exception(
            'Unable to generate the transaction ID.'
        );

    }


    $lastResult =
    mysqli_stmt_get_result(
        $lastStmt
    );


    $runningNumber =
    1;


    if(
        $lastResult
        &&
        mysqli_num_rows(
            $lastResult
        ) > 0
    ){

        $lastSale =
        mysqli_fetch_assoc(
            $lastResult
        );


        $lastTransactionID =
        $lastSale[
            'transaction_id'
        ]
        ??
        '';


        $parts =
        explode(
            '-',
            $lastTransactionID
        );


        if(
            isset(
                $parts[2]
            )
            &&
            is_numeric(
                $parts[2]
            )
        ){

            $runningNumber =
            (int)$parts[2]
            +
            1;

        }

    }


    mysqli_stmt_close(
        $lastStmt
    );


    $transactionID =
    'SPIS-'
    .
    $datePrefix
    .
    '-'
    .
    str_pad(
        $runningNumber,
        4,
        '0',
        STR_PAD_LEFT
    );


    /* =====================================================
       SAVE SALES TRANSACTION
    ====================================================== */

    $buyerForDatabase =
    $buyer !== ''
    ?
    $buyer
    :
    null;


    $saleStmt =
    mysqli_prepare(
        $conn,
        "
        INSERT INTO sales
        (
            transaction_id,
            buyer_name,
            affiliate,
            member_id,
            sale_date,
            is_historical,
            grand_total,
            cash_received,
            change_amount
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
            ?
        )
        "
    );


    if(
        !$saleStmt
    ){

        throw new Exception(
            'Unable to prepare the sales transaction.'
        );

    }


    mysqli_stmt_bind_param(
        $saleStmt,
        'sssisiddd',
        $transactionID,
        $buyerForDatabase,
        $affiliate,
        $memberID,
        $saleDateTime,
        $isHistoricalEntry,
        $grandTotal,
        $cashReceived,
        $changeAmount
    );


    if(
        !mysqli_stmt_execute(
            $saleStmt
        )
    ){

        $saleError =
        mysqli_stmt_error(
            $saleStmt
        );


        mysqli_stmt_close(
            $saleStmt
        );


        throw new Exception(
            $saleError
            ?:
            'Unable to save the sales transaction.'
        );

    }


    $saleID =
    mysqli_insert_id(
        $conn
    );


    mysqli_stmt_close(
        $saleStmt
    );


    /* =====================================================
       SAVE ITEMS + DEDUCT INVENTORY
    ====================================================== */

    foreach(
        $validatedItems
        as
        $item
    ){

        $productID =
        $item[
            'product_id'
        ];


        $quantity =
        $item[
            'quantity'
        ];


        $sellingPrice =
        $item[
            'selling_price'
        ];


        $subtotal =
        $item[
            'subtotal'
        ];


        /* -------------------------------------------------
           SALES ITEM
        -------------------------------------------------- */

        $itemStmt =
        mysqli_prepare(
            $conn,
            "
            INSERT INTO sales_items
            (
                sale_id,
                product_id,
                quantity,
                selling_price,
                subtotal
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
            "
        );


        if(
            !$itemStmt
        ){

            throw new Exception(
                'Unable to prepare one of the sales items.'
            );

        }


        mysqli_stmt_bind_param(
            $itemStmt,
            'iiidd',
            $saleID,
            $productID,
            $quantity,
            $sellingPrice,
            $subtotal
        );


        if(
            !mysqli_stmt_execute(
                $itemStmt
            )
        ){

            mysqli_stmt_close(
                $itemStmt
            );


            throw new Exception(
                'Unable to save one of the sales items.'
            );

        }


        mysqli_stmt_close(
            $itemStmt
        );


        /* -------------------------------------------------
           INVENTORY DEDUCTION
        -------------------------------------------------- */

        $stockStmt =
        mysqli_prepare(
            $conn,
            "
            UPDATE inventory

            SET quantity =
                quantity - ?

            WHERE product_id = ?
            AND archived = 0
            AND quantity >= ?
            "
        );


        if(
            !$stockStmt
        ){

            throw new Exception(
                'Unable to prepare the inventory update.'
            );

        }


        mysqli_stmt_bind_param(
            $stockStmt,
            'iii',
            $quantity,
            $productID,
            $quantity
        );


        if(
            !mysqli_stmt_execute(
                $stockStmt
            )
        ){

            mysqli_stmt_close(
                $stockStmt
            );


            throw new Exception(
                'Unable to update Inventory stock.'
            );

        }


        $affectedRows =
        mysqli_stmt_affected_rows(
            $stockStmt
        );


        mysqli_stmt_close(
            $stockStmt
        );


        if(
            $affectedRows
            <=
            0
        ){

            throw new Exception(
                'Inventory stock changed while saving the sale. Please try again.'
            );

        }

    }


    /* =====================================================
       HISTORY
    ====================================================== */

    $productCount =
    count(
        $validatedItems
    );


    $historyBuyer =
    $buyer !== ''
    ?
    $buyer
    :
    'Walk-in Customer';


    $historyMessage =
    'recorded '
    .
    (
        $isHistoricalEntry === 1
        ?
        'Historical / Audit sales transaction "'
        :
        'sales transaction "'
    )
    .
    $transactionID
    .
    '" dated '
    .
    $saleDateObject->format(
        'M d, Y'
    )
    .
    ' for "'
    .
    $historyBuyer
    .
    '" ('
    .
    $affiliate
    .
    ') with '
    .
    $productCount
    .
    (
        $productCount === 1
        ?
        ' product'
        :
        ' products'
    )
    .
    ', '
    .
    $totalQuantity
    .
    (
        $totalQuantity === 1
        ?
        ' total unit'
        :
        ' total units'
    )
    .
    ', totaling ₱'
    .
    number_format(
        $grandTotal,
        2
    )
    .
    '.';


    log_activity(
        $conn,
        'Sales',
        'Added',
        $historyMessage,
        $saleID
    );


    /* =====================================================
       COMMIT
    ====================================================== */

    mysqli_commit(
    $conn
    );


    /* =====================================================
    ROTATE SALES CSRF TOKEN
    ===================================================== */

    try {

        $_SESSION[
            "sales_csrf_token"
        ] =
            bin2hex(
                random_bytes(
                    32
                )
            );

    }
    catch (
        Throwable $tokenError
    ) {

        error_log(
            "Sales CSRF rotation error: "
            .
            $tokenError->getMessage()
        );

    }


    header(
        'Location: sales.php?success=added'
    );


        exit();

    }


/* =========================================================
   ERROR
========================================================= */

catch(
    Throwable $error
){

    mysqli_rollback(
        $conn
    );


    salesError(
        $error->getMessage()
    );

}