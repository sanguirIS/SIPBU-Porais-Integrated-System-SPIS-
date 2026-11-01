<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}


/* =========================================================
   MANILA TIME
========================================================= */

date_default_timezone_set(
    'Asia/Manila'
);


/* =========================================================
   DATABASE
========================================================= */

require_once '../../db.php';


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   VERIFY LIVE RENTAL ACCESS
========================================================= */

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

    exit(
        'Your session has expired. Please sign in again.'
    );
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

    exit(
        'Unable to verify Rental access.'
    );
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

    exit(
        'Unable to verify Rental access.'
    );
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

    exit(
        'You do not have permission to access Rental reports.'
    );
}


/* =========================================================
   PREPARED BY
========================================================= */

$preparedBy =
trim(
    (string)(
        $_SESSION['name']
        ??
        ''
    )
);

$role =
ucwords(
    trim(
        (string)(
            $_SESSION['role']
            ??
            ''
        )
    )
);


/* =========================================================
   REPORT FILTERS
========================================================= */

$affiliate =
$_GET['affiliate']
??
'all';

$status =
$_GET['status']
??
'all';

$remarks =
$_GET['remarks']
??
'all';

$dateOption =
$_GET['dateOption']
??
'all';

$from =
$_GET['from']
??
'';

$to =
$_GET['to']
??
'';


/* =========================================================
   ALLOWED FILTER VALUES
========================================================= */

$allowedAffiliates =
[
    'all',
    'Member',
    'Not Member'
];

$allowedStatuses =
[
    'all',
    'Pending',
    'Ongoing',
    'Done',
    'Maintenance'
];

$allowedRemarks =
[
    'all',
    'Paid',
    'Unpaid'
];

$allowedDateOptions =
[
    'all',
    'custom'
];


if(
    !in_array(
        $affiliate,
        $allowedAffiliates,
        true
    )
){
    $affiliate = 'all';
}


if(
    !in_array(
        $status,
        $allowedStatuses,
        true
    )
){
    $status = 'all';
}


if(
    !in_array(
        $remarks,
        $allowedRemarks,
        true
    )
){
    $remarks = 'all';
}


if(
    !in_array(
        $dateOption,
        $allowedDateOptions,
        true
    )
){
    $dateOption = 'all';
}


/* =========================================================
   DATE VALIDATION
========================================================= */

function rotorReportValidDate(
    string $date
): bool {

    $dateObject =
    DateTime::createFromFormat(
        'Y-m-d',
        $date
    );

    return
    $dateObject
    &&
    $dateObject->format(
        'Y-m-d'
    ) === $date;
}


if(
    $dateOption === 'custom'
){

    if(
        !rotorReportValidDate(
            $from
        )
        ||
        !rotorReportValidDate(
            $to
        )
        ||
        $from > $to
    ){

        http_response_code(
            400
        );

        exit(
            'Invalid Rotor report date range.'
        );
    }
}


/* =========================================================
   EFFECTIVE PAYMENT REMARKS

   Payment remarks are based on the
   current remaining balance.
========================================================= */

$effectiveRemarksSql = "

    CASE

        WHEN
            (
                COALESCE(
                    r.total_price,
                    0
                )
                -
                COALESCE(
                    r.amount_paid,
                    0
                )
            )
            <= 0.009

        THEN 'Paid'

        ELSE 'Unpaid'

    END

";


/* =========================================================
   REPORT QUERY

   Current Member / Driver / Tractor values are used for
   display. Stored Rotor values remain as fallback.
========================================================= */

$sql = "

SELECT

    r.*,


    /* =============================================
       CURRENT CUSTOMER
    ============================================= */

    COALESCE(
        NULLIF(
            TRIM(
                cm.full_name
            ),
            ''
        ),
        NULLIF(
            TRIM(
                r.customer_name
            ),
            ''
        )
    )
        AS report_customer_name,


    /* =============================================
       CURRENT PRIMARY DRIVER
    ============================================= */

    COALESCE(
        NULLIF(
            TRIM(
                pm.full_name
            ),
            ''
        ),
        NULLIF(
            TRIM(
                pd.full_name
            ),
            ''
        ),
        NULLIF(
            TRIM(
                r.operator
            ),
            ''
        )
    )
        AS report_primary_driver_name,


    /* =============================================
       CURRENT PRIMARY TRACTOR
    ============================================= */

    COALESCE(
        NULLIF(
            TRIM(
                t.tractor_no
            ),
            ''
        ),
        NULLIF(
            TRIM(
                r.tractor_no
            ),
            ''
        )
    )
        AS report_primary_tractor_no,


    NULLIF(
        TRIM(
            t.tractor_name
        ),
        ''
    )
        AS report_primary_tractor_name,


    /* =============================================
       CURRENT SECONDARY DRIVER
    ============================================= */

    COALESCE(
        NULLIF(
            TRIM(
                sm.full_name
            ),
            ''
        ),
        NULLIF(
            TRIM(
                sd.full_name
            ),
            ''
        ),
        NULLIF(
            TRIM(
                r.secondary_operator
            ),
            ''
        )
    )
        AS report_secondary_driver_name,


    /* =============================================
       CURRENT SECONDARY TRACTOR
    ============================================= */

    COALESCE(
        NULLIF(
            TRIM(
                st.tractor_no
            ),
            ''
        ),
        NULLIF(
            TRIM(
                r.secondary_tractor_no
            ),
            ''
        )
    )
        AS report_secondary_tractor_no,


    NULLIF(
        TRIM(
            st.tractor_name
        ),
        ''
    )
        AS report_secondary_tractor_name,


    /* =============================================
       PAYMENT REMARKS
    ============================================= */

    $effectiveRemarksSql
        AS report_remarks,


    /* =============================================
       MAINTENANCE FLAG
    ============================================= */

    CASE

        WHEN
            (
                t.status = 'Maintenance'

                OR

                st.status = 'Maintenance'
            )

            AND

            r.status IN (
                'Pending',
                'Ongoing'
            )

        THEN 1

        ELSE 0

    END
        AS maintenance_affected


FROM rotor r


LEFT JOIN tractors t

    ON t.tractor_id =
    r.tractor_id


LEFT JOIN tractors st

    ON st.tractor_id =
    r.secondary_tractor_id


LEFT JOIN drivers pd

    ON pd.driver_id =
    r.primary_driver_id


LEFT JOIN members pm

    ON pm.member_id =
    pd.member_id


LEFT JOIN drivers sd

    ON sd.driver_id =
    r.secondary_driver_id


LEFT JOIN members sm

    ON sm.member_id =
    sd.member_id


LEFT JOIN members cm

    ON cm.member_id =
    r.member_id


WHERE 1=1

";


/* =========================================================
   AFFILIATE FILTER
========================================================= */

if(
    $affiliate !== 'all'
){

    $safeAffiliate =
    mysqli_real_escape_string(
        $conn,
        $affiliate
    );

    $sql .= "

        AND r.affiliate =
        '$safeAffiliate'

    ";
}


/* =========================================================
   STATUS FILTER
========================================================= */

if(
    $status === 'Maintenance'
){

    $sql .= "

        AND
        (
            t.status = 'Maintenance'

            OR

            st.status = 'Maintenance'
        )

        AND r.status IN (
            'Pending',
            'Ongoing'
        )

    ";
}
elseif(
    $status !== 'all'
){

    $safeStatus =
    mysqli_real_escape_string(
        $conn,
        $status
    );

    $sql .= "

        AND r.status =
        '$safeStatus'

    ";
}


/* =========================================================
   REMARKS FILTER
========================================================= */

if(
    $remarks !== 'all'
){

    $safeRemarks =
    mysqli_real_escape_string(
        $conn,
        $remarks
    );

    $sql .= "

        AND
        (
            $effectiveRemarksSql
        ) = '$safeRemarks'

    ";
}


/* =========================================================
   DATE FILTER
========================================================= */

if(
    $dateOption === 'custom'
){

    $safeFrom =
    mysqli_real_escape_string(
        $conn,
        $from
    );

    $safeTo =
    mysqli_real_escape_string(
        $conn,
        $to
    );

    $sql .= "

        AND DATE(
            r.rental_date
        )
        BETWEEN
            '$safeFrom'
        AND
            '$safeTo'

    ";
}


/* =========================================================
   ORDER
========================================================= */

$sql .= "

    ORDER BY
        r.rotor_id ASC

";


/* =========================================================
   FETCH REPORT DATA
========================================================= */

$query =
mysqli_query(
    $conn,
    $sql
);


$reportRows =
[];


$totalSubtotal =
0.00;


$totalDiscount =
0.00;


$totalGrandTotal =
0.00;


$reportLoadError =
false;


if(
    $query
){

    while(
        $row =
        mysqli_fetch_assoc(
            $query
        )
    ){

        $reportRows[] =
        $row;


        $hectare =
        (float)(
            $row['hectare']
            ??
            0
        );


        $price =
        (float)(
            $row['price']
            ??
            0
        );


        $totalSubtotal +=
        $hectare
        *
        $price;


        $totalDiscount +=
        (float)(
            $row['discounted_price']
            ??
            0
        );


        $totalGrandTotal +=
        (float)(
            $row['total_price']
            ??
            0
        );
    }
}
else{

    $reportLoadError =
    true;


    error_log(
        'Rotor Report SQL Error: '
        .
        mysqli_error(
            $conn
        )
    );
}


$totalTransactions =
count(
    $reportRows
);


/* =========================================================
   CURRENT ROTOR PRICE
========================================================= */

$setPrice =
0.00;


$priceQuery =
mysqli_query(
    $conn,
    "

    SELECT
        price_per_hectare

    FROM rotor_price

    LIMIT 1

    "
);


if(
    $priceQuery
    &&
    mysqli_num_rows(
        $priceQuery
    ) > 0
){

    $priceRow =
    mysqli_fetch_assoc(
        $priceQuery
    );


    $setPrice =
    (float)(
        $priceRow[
            'price_per_hectare'
        ]
        ??
        0
    );
}


/* =========================================================
   DISPLAY LABELS
========================================================= */

$affiliateLabel =
$affiliate === 'all'
?
'All Affiliates'
:
$affiliate;


$statusLabel =
$status === 'all'
?
'All Status'
:
$status;


$remarksLabel =
$remarks === 'all'
?
'All Remarks'
:
$remarks;


$dateRangeLabel =
'All Transactions';


if(
    $dateOption === 'custom'
){

    $fromTimestamp =
    strtotime(
        $from
    );


    $toTimestamp =
    strtotime(
        $to
    );


    if(
        $fromTimestamp !== false
        &&
        $toTimestamp !== false
    ){

        $dateRangeLabel =
        date(
            'M d, Y',
            $fromTimestamp
        )
        .
        ' - '
        .
        date(
            'M d, Y',
            $toTimestamp
        );
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
    Rotor Report
</title>

<link
rel="stylesheet"
href="report_rotor.css?v=<?= time() ?>">

</head>


<body class="rotor-report-document">


<div class="rotor-report-content">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="rotor-report-document-header">

        <img
        src="../../assets/logo/triangle-logo.png"
        alt="SIPBU-MPC Logo"
        class="rotor-report-document-logo">


        <h1>

            SIPBU-MPC PORAIS INTEGRATED SYSTEM

        </h1>


        <h2>

            Rotor Report

        </h2>


        <p>

            Rental Management System

        </p>

    </div>


    <!-- =====================================================
         ACTION BUTTONS
    ====================================================== -->

    <div class="rotor-report-document-actions">

        <button
        type="button"
        class="rotor-report-print-button"
        onclick="window.print()">

            <span>
                🖨
            </span>

            Print / Save as PDF

        </button>


        <button
        type="button"
        class="rotor-report-close-button"
        onclick="window.close()">

            ✖ Close

        </button>

    </div>


    <!-- =====================================================
         REPORT INFORMATION
    ====================================================== -->

    <div class="rotor-report-document-info">


        <p>

            <strong>
                Date Generated:
            </strong>

            <?= htmlspecialchars(
                date(
                    'F d, Y'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <p>

            <strong>
                Time Generated:
            </strong>

            <?= htmlspecialchars(
                date(
                    'h:i A'
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <hr>


        <p>

            <strong>
                Affiliate:
            </strong>

            <?= htmlspecialchars(
                $affiliateLabel,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <p>

            <strong>
                Status:
            </strong>

            <?= htmlspecialchars(
                $statusLabel,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <p>

            <strong>
                Remarks:
            </strong>

            <?= htmlspecialchars(
                $remarksLabel,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <p>

            <strong>
                Date Range:
            </strong>

            <?= htmlspecialchars(
                $dateRangeLabel,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <p>

            <strong>
                Set Price:
            </strong>

            ₱<?= number_format(
                $setPrice,
                2
            ) ?> / hectare

        </p>


    </div>


    <hr class="rotor-report-divider">


    <!-- =====================================================
         REPORT TABLE
    ====================================================== -->

    <div class="rotor-report-table-container">

        <table class="rotor-report-document-table">


            <thead>

                <tr>

                    <th>
                        Transaction ID
                    </th>

                    <th>
                        Customer
                    </th>

                    <th>
                        Affiliate
                    </th>

                    <th>
                        Primary Assignment
                    </th>

                    <th>
                        Secondary Assignment
                    </th>

                    <th>
                        Location
                    </th>

                    <th>
                        Area (Ha)
                    </th>

                    <th>
                        Subtotal
                    </th>

                    <th>
                        Discount
                    </th>

                    <th>
                        Total Price
                    </th>

                    <th>
                        Balance
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Remarks
                    </th>

                    <th>
                        Rental Date
                    </th>

                </tr>

            </thead>


            <tbody>


            <?php

            if(
                $reportLoadError
            ){

            ?>

                <tr>

                    <td
                    colspan="14"
                    class="rotor-report-no-records">

                        Unable to load the report.

                    </td>

                </tr>


            <?php

            }
            elseif(
                count(
                    $reportRows
                ) === 0
            ){

            ?>

                <tr>

                    <td
                    colspan="14"
                    class="rotor-report-no-records">

                        No Rotor records found.

                    </td>

                </tr>


            <?php

            }
            else{

                foreach(
                    $reportRows
                    as
                    $row
                ){

                    $customerName =
                    trim(
                        (string)(
                            $row[
                                'report_customer_name'
                            ]
                            ??
                            $row[
                                'customer_name'
                            ]
                            ??
                            ''
                        )
                    );


                    $operatorName =
                    trim(
                        (string)(
                            $row[
                                'report_primary_driver_name'
                            ]
                            ??
                            $row[
                                'operator'
                            ]
                            ??
                            ''
                        )
                    );


                    $tractorNo =
                    trim(
                        (string)(
                            $row[
                                'report_primary_tractor_no'
                            ]
                            ??
                            $row[
                                'tractor_no'
                            ]
                            ??
                            ''
                        )
                    );


                    $tractorName =
                    trim(
                        (string)(
                            $row[
                                'report_primary_tractor_name'
                            ]
                            ??
                            ''
                        )
                    );


                    $secondaryDriverName =
                    trim(
                        (string)(
                            $row[
                                'report_secondary_driver_name'
                            ]
                            ??
                            $row[
                                'secondary_operator'
                            ]
                            ??
                            ''
                        )
                    );


                    $secondaryTractorNo =
                    trim(
                        (string)(
                            $row[
                                'report_secondary_tractor_no'
                            ]
                            ??
                            $row[
                                'secondary_tractor_no'
                            ]
                            ??
                            ''
                        )
                    );


                    $secondaryTractorName =
                    trim(
                        (string)(
                            $row[
                                'report_secondary_tractor_name'
                            ]
                            ??
                            ''
                        )
                    );


                    if(
                        $tractorName === ''
                    ){

                        $tractorName =
                        '—';
                    }


                    if(
                        $secondaryDriverName === ''
                    ){

                        $secondaryDriverName =
                        '—';
                    }


                    if(
                        $secondaryTractorNo === ''
                    ){

                        $secondaryTractorNo =
                        '—';
                    }


                    if(
                        $secondaryTractorName === ''
                    ){

                        $secondaryTractorName =
                        '—';
                    }


                    $paymentRemarks =
                    trim(
                        (string)(
                            $row[
                                'report_remarks'
                            ]
                            ??
                            'Unpaid'
                        )
                    );


                    if(
                        $paymentRemarks === ''
                    ){

                        $paymentRemarks =
                        'Unpaid';
                    }


                    $rentalDateDisplay =
                    '—';


                    $rentalDate =
                    trim(
                        (string)(
                            $row[
                                'rental_date'
                            ]
                            ??
                            ''
                        )
                    );


                    if(
                        $rentalDate !== ''
                    ){

                        $rentalTimestamp =
                        strtotime(
                            $rentalDate
                        );


                        if(
                            $rentalTimestamp !== false
                        ){

                            $rentalDateDisplay =
                            date(
                                'm-d-Y',
                                $rentalTimestamp
                            );
                        }
                    }


                    $hectare =
                    (float)(
                        $row[
                            'hectare'
                        ]
                        ??
                        0
                    );


                    $price =
                    (float)(
                        $row[
                            'price'
                        ]
                        ??
                        0
                    );


                    $subtotal =
                    $hectare
                    *
                    $price;

                    $balance =
                    round(
                        max(
                            0,

                            (float)(
                                $row[
                                    'total_price'
                                ]
                                ??
                                0
                            )

                            -

                            (float)(
                                $row[
                                    'amount_paid'
                                ]
                                ??
                                0
                            )
                        ),
                        2
                    );

            ?>


                <tr>


                    <td>

                        <?= htmlspecialchars(
                            (
                                $row[
                                    'transaction_id'
                                ]
                                ??
                                ''
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $customerName,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            (
                                $row[
                                    'affiliate'
                                ]
                                ??
                                ''
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </td>


                    <td class="rotor-report-assignment">

                        <strong>

                            <?= htmlspecialchars(
                                $tractorNo !== ''
                                ?
                                $tractorNo
                                :
                                '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </strong>


                        <span>

                            <?= htmlspecialchars(
                                $tractorName,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>


                        <span>

                            <?= htmlspecialchars(
                                $operatorName !== ''
                                ?
                                $operatorName
                                :
                                '—',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </span>

                    </td>


                    <td class="rotor-report-assignment">

                        <?php
                        if(
                            $secondaryTractorNo === '—'
                            &&
                            $secondaryTractorName === '—'
                            &&
                            $secondaryDriverName === '—'
                        ){

                        ?>

                            <span>
                                —
                            </span>

                        <?php

                        }
                        else{

                        ?>

                            <strong>

                                <?= htmlspecialchars(
                                    $secondaryTractorNo,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </strong>

                            <span>

                                <?= htmlspecialchars(
                                    $secondaryTractorName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                            <span>

                                <?= htmlspecialchars(
                                    $secondaryDriverName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>

                            </span>

                        <?php

                        }

                        ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            (
                                $row[
                                    'location'
                                ]
                                ??
                                ''
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </td>


                    <td>

                        <?= number_format(
                            $hectare,
                            2
                        ) ?>

                    </td>


                    <td class="rotor-report-money">

                        ₱<?= number_format(
                            $subtotal,
                            2
                        ) ?>

                    </td>


                    <td class="rotor-report-money">

                        ₱<?= number_format(
                            (float)(
                                $row[
                                    'discounted_price'
                                ]
                                ??
                                0
                            ),
                            2
                        ) ?>

                    </td>


                    <td class="rotor-report-money">

                        ₱<?= number_format(
                            (float)(
                                $row[
                                    'total_price'
                                ]
                                ??
                                0
                            ),
                            2
                        ) ?>

                    </td>

                    <td class="rotor-report-money">

                        ₱<?= number_format(
                            $balance,
                            2
                        ) ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            (
                                $row[
                                    'status'
                                ]
                                ??
                                ''
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>


                        <?php

                        if(
                            (int)(
                                $row[
                                    'maintenance_affected'
                                ]
                                ??
                                0
                            )
                            ===
                            1
                        ){

                        ?>

                            <div class="rotor-report-maintenance-label">

                                Maintenance

                            </div>

                        <?php

                        }

                        ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $paymentRemarks,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $rentalDateDisplay,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </td>


                </tr>


            <?php

                }

            }

            ?>


            </tbody>


        </table>

    </div>


    <hr class="rotor-report-divider">


    <!-- =====================================================
         SUMMARY
    ====================================================== -->

    <div class="rotor-report-document-summary">

        <hr>

        <h3>

            SUMMARY REPORT

        </h3>


        <p>

            <strong>
                Total Transactions:
            </strong>

            <?= number_format(
                $totalTransactions
            ) ?>

        </p>


        <p>

            <strong>
                Total Subtotal:
            </strong>

            ₱<?= number_format(
                $totalSubtotal,
                2
            ) ?>

        </p>


        <p>

            <strong>
                Total Discount:
            </strong>

            ₱<?= number_format(
                $totalDiscount,
                2
            ) ?>

        </p>


        <p>

            <strong>
                Grand Total:
            </strong>

            ₱<?= number_format(
                $totalGrandTotal,
                2
            ) ?>

        </p>


    </div>


    <hr class="rotor-report-divider">

    <hr>


    <!-- =====================================================
         PREPARED BY
    ====================================================== -->

    <div class="rotor-report-document-prepared">


        <p>

            <strong>
                Prepared by:
            </strong>

        </p>


        <div class="rotor-report-signature-space"></div>


        <strong>

            <?= htmlspecialchars(
                $preparedBy,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </strong>


        <span>

            <?= htmlspecialchars(
                $role,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </span>


    </div>


</div>


</body>

</html>