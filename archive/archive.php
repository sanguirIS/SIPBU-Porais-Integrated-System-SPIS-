<?php

/* ======================================================
   ARCHIVE
====================================================== */

if (
    session_status()
    ===
    PHP_SESSION_NONE
) {
    session_start();
}


/* ======================================================
   ACCESS CONTROL
====================================================== */

require "../includes/check_access.php";

checkAccess(
    "archives_access"
);


/* ======================================================
   TIMEZONE
====================================================== */

date_default_timezone_set(
    "Asia/Manila"
);


/* ======================================================
   DATABASE
====================================================== */

include "../db.php";

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* ======================================================
   INVENTORY ARCHIVE CSRF TOKEN
====================================================== */

if (
    empty(
        $_SESSION[
            "archive_inventory_csrf_token"
        ]
    )
) {
    $_SESSION[
        "archive_inventory_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );
}

/* ======================================================
   MEMBER ARCHIVE CSRF TOKEN
====================================================== */

if (
    empty(
        $_SESSION[
            "archive_member_csrf_token"
        ]
    )
) {

    $_SESSION[
        "archive_member_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );

}


$memberArchiveCsrfToken =
    $_SESSION[
        "archive_member_csrf_token"
    ];


/* ======================================================
   PRODUCT LIST ARCHIVE CSRF TOKEN
====================================================== */

if (
    empty(
        $_SESSION[
            "archive_product_list_csrf_token"
        ]
    )
) {

    $_SESSION[
        "archive_product_list_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );

}


$productListArchiveCsrfToken =
    $_SESSION[
        "archive_product_list_csrf_token"
    ];


/* ======================================================
   ACCOUNT ARCHIVE CSRF TOKEN
====================================================== */

if (
    empty(
        $_SESSION[
            "account_csrf_token"
        ]
    )
) {

    $_SESSION[
        "account_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );

}


$accountCsrfToken =
    $_SESSION[
        "account_csrf_token"
    ];


/* ======================================================
   TRACTOR ARCHIVE CSRF TOKEN
====================================================== */

if (
    empty(
        $_SESSION[
            "tractor_csrf_token"
        ]
    )
) {

    $_SESSION[
        "tractor_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );

}


$tractorCsrfToken =
    $_SESSION[
        "tractor_csrf_token"
    ];


/* ======================================================
   DRIVER ARCHIVE CSRF TOKEN
====================================================== */

if (
    empty(
        $_SESSION[
            "driver_archive_csrf_token"
        ]
    )
) {

    $_SESSION[
        "driver_archive_csrf_token"
    ] =
        bin2hex(
            random_bytes(32)
        );

}


$driverArchiveCsrfToken =
    $_SESSION[
        "driver_archive_csrf_token"
    ];


/* ======================================================
   ARCHIVE MODULE ACCESS

   Archive itself still requires archives_access.

   The tabs inside Archive follow the user's access
   to their corresponding source modules:

   Member    -> members_access
   Account   -> account_access
    Lending      -> lending_access
    Product List -> lending_access
    Inventory    -> inventory_access
   Tractors  -> rental_access
   Drivers   -> rental_access
====================================================== */

$archiveModuleMap = [

    "member" => [
        "label"   => "MEMBERS",
        "session" => "members_access"
    ],

    "account" => [
        "label"   => "ACCOUNT",
        "session" => "account_access"
    ],

    "lending" => [
        "label"   => "LENDING",
        "session" => "lending_access"
    ],

    "product_list" => [
    "label"   => "PRODUCT LIST",
    "session" => "lending_access"
    ],

    "inventory" => [
        "label"   => "INVENTORY",
        "session" => "inventory_access"
    ],

    /*
     * Tractor Registry is part of Rental.
     */
    "tractor" => [
        "label"   => "TRACTORS",
        "session" => "rental_access"
    ],

    /*
     * Driver Registry is part of Rental.
     */
    "driver" => [
        "label"   => "DRIVERS",
        "session" => "rental_access"
    ]

];


/* ======================================================
   BUILD ALLOWED ARCHIVE MODULES
====================================================== */

$allowedArchiveModules = [];


foreach (
    $archiveModuleMap
    as
    $archiveSlug
    =>
    $archiveData
) {

    $sessionPermission =
        $archiveData[
            "session"
        ];


    if (
        (int) (
            $_SESSION[
                $sessionPermission
            ]
            ??
            0
        )
        ===
        1
    ) {

        $allowedArchiveModules[
            $archiveSlug
        ] =
            $archiveData;

    }

}


/* ======================================================
   RESOLVE REQUESTED MODULE
====================================================== */

$requestedArchiveModuleValue =
    $_GET[
        "module"
    ]
    ??
    "";


$requestedArchiveModule =
    is_string(
        $requestedArchiveModuleValue
    )
    ?
    strtolower(
        trim(
            $requestedArchiveModuleValue
        )
    )
    :
    "";


$firstAllowedArchiveModule =
    array_key_first(
        $allowedArchiveModules
    );


if (
    $requestedArchiveModule
    ===
    ""
) {

    $module =
        $firstAllowedArchiveModule
        ??
        "";

}
elseif (
    !isset(
        $allowedArchiveModules[
            $requestedArchiveModule
        ]
    )
) {

    if (
        $firstAllowedArchiveModule
        !==
        null
        &&
        $firstAllowedArchiveModule
        !==
        ""
    ) {

        header(
            "Location: archive.php?module="
            .
            rawurlencode(
                $firstAllowedArchiveModule
            )
        );

        exit();

    }


    /*
     * The account may have Archive access while having
     * no source-module access. In that case, show the
     * Archive shell only and expose no restricted ledger.
     */

    $module =
        "";

}
else {

    $module =
        $requestedArchiveModule;

}


/* ======================================================
   TOTAL ARCHIVES

   Count only the archived records from source modules
   the current account can access.
====================================================== */

$totalInventory =
    0;


$totalAccounts =
    0;


$totalMembers =
    0;


$totalTractors =
    0;


$totalDrivers =
    0;


$totalLending =
    0;

$totalProductList =
    0;    


/* ======================================================
   INVENTORY COUNT
====================================================== */

if (
    isset(
        $allowedArchiveModules[
            "inventory"
        ]
    )
) {

    $inventoryCountResult =
        mysqli_query(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM inventory
            WHERE archived = 1
            "
        );


    if (
        $inventoryCountResult
    ) {

        $inventoryCountRow =
            mysqli_fetch_assoc(
                $inventoryCountResult
            );


        $totalInventory =
            (int) (
                $inventoryCountRow[
                    "total"
                ]
                ??
                0
            );

    }

}


/* ======================================================
   ACCOUNT COUNT
====================================================== */

if (
    isset(
        $allowedArchiveModules[
            "account"
        ]
    )
) {

    $accountCountResult =
        mysqli_query(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM users
            WHERE archived = 1
            "
        );


    if (
        $accountCountResult
    ) {

        $accountCountRow =
            mysqli_fetch_assoc(
                $accountCountResult
            );


        $totalAccounts =
            (int) (
                $accountCountRow[
                    "total"
                ]
                ??
                0
            );

    }

}


/* ======================================================
   MEMBER COUNT
====================================================== */

if (
    isset(
        $allowedArchiveModules[
            "member"
        ]
    )
) {

    $memberCountResult =
        mysqli_query(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM members
            WHERE archived = 1
            "
        );


    if (
        $memberCountResult
    ) {

        $memberCountRow =
            mysqli_fetch_assoc(
                $memberCountResult
            );


        $totalMembers =
            (int) (
                $memberCountRow[
                    "total"
                ]
                ??
                0
            );

    }

}


/* ======================================================
   TRACTOR COUNT
====================================================== */

if (
    isset(
        $allowedArchiveModules[
            "tractor"
        ]
    )
) {

    $tractorCountResult =
        mysqli_query(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM tractors
            WHERE archived = 1
            "
        );


    if (
        $tractorCountResult
    ) {

        $tractorCountRow =
            mysqli_fetch_assoc(
                $tractorCountResult
            );


        $totalTractors =
            (int) (
                $tractorCountRow[
                    "total"
                ]
                ??
                0
            );

    }

}


/* ======================================================
   DRIVER COUNT
====================================================== */

if (
    isset(
        $allowedArchiveModules[
            "driver"
        ]
    )
) {

    $driverCountResult =
        mysqli_query(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM drivers
            WHERE archived = 1
            "
        );


    if (
        $driverCountResult
    ) {

        $driverCountRow =
            mysqli_fetch_assoc(
                $driverCountResult
            );


        $totalDrivers =
            (int) (
                $driverCountRow[
                    "total"
                ]
                ??
                0
            );

    }

}


/* ======================================================
   LENDING COUNT
====================================================== */

if (
    isset(
        $allowedArchiveModules[
            "lending"
        ]
    )
) {

    $lendingCountResult =
        mysqli_query(
            $conn,
            "
            SELECT

                (
                    SELECT COUNT(*)
                    FROM product_loans
                    WHERE is_archived = 1
                )

                +

                (
                    SELECT COUNT(*)
                    FROM monetary_loans
                    WHERE is_archived = 1
                )

                AS total
            "
        );


    if (
        $lendingCountResult
    ) {

        $lendingCountRow =
            mysqli_fetch_assoc(
                $lendingCountResult
            );


        $totalLending =
            (int) (
                $lendingCountRow[
                    "total"
                ]
                ??
                0
            );

    }

}

/* ======================================================
   PRODUCT LIST COUNT
====================================================== */

if (
    isset(
        $allowedArchiveModules[
            "product_list"
        ]
    )
) {

    $productListCountResult =
        mysqli_query(
            $conn,
            "
            SELECT COUNT(*) AS total
            FROM product_loan_products
            WHERE archived = 1
            "
        );


    if (
        $productListCountResult
    ) {

        $productListCountRow =
            mysqli_fetch_assoc(
                $productListCountResult
            );


        $totalProductList =
            (int) (
                $productListCountRow[
                    "total"
                ]
                ??
                0
            );

    }

}


/* ======================================================
   VISIBLE TOTAL
====================================================== */

$total =
    $totalInventory
    +
    $totalAccounts
    +
    $totalMembers
    +
    $totalTractors
    +
    $totalDrivers
    +
    $totalLending
    +
    $totalProductList;


/* ======================================================
   PAGE COMPONENTS
====================================================== */

include "../includes/sidebar.php";

?>


<link
rel="stylesheet"
href="../css/archive.css?v=<?= time(); ?>">


<?php

if (
    $module !== ""
) {

?>

<link
rel="stylesheet"
href="../css/archive_<?= htmlspecialchars(
    $module,
    ENT_QUOTES,
    "UTF-8"
); ?>.css?v=<?= time(); ?>">

<?php

}

?>


<h2>
    ARCHIVE
</h2>


<hr>


<!-- =====================================================
     ARCHIVE TABS
====================================================== -->

<div class="archive-tabs">


    <?php

    foreach (
        $allowedArchiveModules
        as
        $archiveSlug
        =>
        $archiveData
    ) {

    ?>

        <a
        href="?module=<?= htmlspecialchars(
            $archiveSlug,
            ENT_QUOTES,
            "UTF-8"
        ); ?>"
        class="archive-tab <?= $module === $archiveSlug
            ? "active"
            : "";
        ?>">

            <?= htmlspecialchars(
                $archiveData[
                    "label"
                ],
                ENT_QUOTES,
                "UTF-8"
            ); ?>

        </a>

    <?php

    }

    ?>


    <div class="archive-total-tab">

        TOTAL ARCHIVES:

        <span>
            <?= number_format(
                $total
            ); ?>
        </span>

    </div>


</div>


<?php

if (
    count(
        $allowedArchiveModules
    )
    ===
    0
) {

?>

    <div class="archive-empty">

        No archive modules are available for this account.

    </div>

<?php

}

?>


<!-- =====================================================
     MEMBER ARCHIVE
====================================================== -->

<?php

if (
    $module === 'member'
) {

    $sql = "
        SELECT *
        FROM members
        WHERE archived = 1
        ORDER BY member_id DESC
    ";


    $result =
        mysqli_query(
            $conn,
            $sql
        );

?>


<div class="
    archive-table
    member-archive-table
">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="
        archive-header
        member-header
    ">

        <div>
            MEMBER
        </div>

        <div>
            DATE
        </div>

        <div>
            CONTACT
        </div>

        <div>
            ADDRESS
        </div>

        <div>
            STATUS
        </div>

        <div>
            CLASSIFICATION
        </div>

        <div>
            ARCHIVED BY
        </div>

        <div>
            ACTIONS
        </div>

    </div>


<?php

if (
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
) {

    while (
        $row =
            mysqli_fetch_assoc(
                $result
            )
    ) {


        /* =============================================
           MEMBER
        ============================================= */

        $memberID =
            (int) (
                $row['member_id']
                ??
                0
            );


        $memberCode =
            trim(
                (string) (
                    $row['member_code']
                    ??
                    ''
                )
            );


        $memberName =
            trim(
                (string) (
                    $row['full_name']
                    ??
                    ''
                )
            );


        $gender =
            trim(
                (string) (
                    $row['gender']
                    ??
                    ''
                )
            );


        /* =============================================
           MEMBERSHIP DATE
        ============================================= */

        $membershipDate =
            '—';


        if (
            !empty(
                $row['date_registered']
            )
        ) {

            $membershipTimestamp =
                strtotime(
                    $row['date_registered']
                );


            if (
                $membershipTimestamp !== false
            ) {

                $membershipDate =
                    date(
                        'm-d-Y',
                        $membershipTimestamp
                    );

            }

        }


        /* =============================================
           BIRTH DATE
        ============================================= */

        $birthDate =
            '—';


        if (
            !empty(
                $row['birth_date']
            )
        ) {

            $birthTimestamp =
                strtotime(
                    $row['birth_date']
                );


            if (
                $birthTimestamp !== false
            ) {

                $birthDate =
                    date(
                        'm-d-Y',
                        $birthTimestamp
                    );

            }

        }


        /* =============================================
           CONTACT
        ============================================= */

        $rawContact =
            trim(
                (string) (
                    $row['contact_no']
                    ??
                    ''
                )
            );


        $contactDigits =
            preg_replace(
                '/\D/',
                '',
                $rawContact
            );


        if (
            strlen(
                $contactDigits
            ) === 11
        ) {

            $contactDisplay =
                substr(
                    $contactDigits,
                    0,
                    4
                )
                .
                '-'
                .
                substr(
                    $contactDigits,
                    4,
                    3
                )
                .
                '-'
                .
                substr(
                    $contactDigits,
                    7,
                    4
                );

        } else {

            $contactDisplay =
                $rawContact !== ''
                ?
                $rawContact
                :
                '—';

        }


        $facebook =
            trim(
                (string) (
                    $row['social_media_name']
                    ??
                    ''
                )
            );


        if (
            $facebook === ''
        ) {

            $facebook =
                '—';

        }


        /* =============================================
           ADDRESS
        ============================================= */

        $address =
            trim(
                (string) (
                    $row['address']
                    ??
                    ''
                )
            );


        if (
            $address === ''
        ) {

            $address =
                '—';

        }


        /* =============================================
           STATUS
        ============================================= */

        $status =
            ucfirst(
                strtolower(
                    trim(
                        (string) (
                            $row['status']
                            ??
                            'Inactive'
                        )
                    )
                )
            );


        if (
            $status === ''
        ) {

            $status =
                'Inactive';

        }


        $statusClass =
            strtolower(
                $status
            ) === 'active'
            ?
            'member-archive-status-active'
            :
            'member-archive-status-inactive';


        /* =============================================
           CLASSIFICATION
        ============================================= */

        $classification =
            ucfirst(
                strtolower(
                    trim(
                        (string) (
                            $row['classification']
                            ??
                            'Irregular'
                        )
                    )
                )
            );


        if (
            $classification === ''
        ) {

            $classification =
                'Irregular';

        }


        $classificationClass =
            strtolower(
                $classification
            ) === 'regular'
            ?
            'member-archive-classification-regular'
            :
            'member-archive-classification-irregular';


        /* =============================================
           ARCHIVED BY
        ============================================= */

        $archivedBy =
            trim(
                (string) (
                    $row['archived_by']
                    ??
                    ''
                )
            );


        if (
            $archivedBy === ''
        ) {

            $archivedBy =
                'Not recorded';

        }

?>


    <div class="
        archive-row
        member-row
    ">


        <!-- MEMBER -->

        <div class="member-archive-member">

            <strong>

                <?= htmlspecialchars(
                    $memberName !== ''
                    ?
                    $memberName
                    :
                    '—',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </strong>


            <small>

                <?= htmlspecialchars(
                    $memberCode !== ''
                    ?
                    $memberCode
                    :
                    '—',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>


                <?php

                if (
                    $gender !== ''
                ) {

                ?>

                    ·

                    <?= htmlspecialchars(
                        $gender,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                <?php

                }

                ?>

            </small>

        </div>


        <!-- DATE -->

        <div class="member-archive-stack">

            <span class="member-archive-detail">

                <small>
                    Membership
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $membershipDate,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </span>


            <span class="member-archive-detail">

                <small>
                    Birth
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $birthDate,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </span>

        </div>


        <!-- CONTACT -->

        <div class="member-archive-stack">

            <span class="member-archive-detail">

                <small>
                    Number
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $contactDisplay,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </span>


            <span class="member-archive-detail">

                <small>
                    Facebook
                </small>

                <strong>

                    <?= htmlspecialchars(
                        $facebook,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </strong>

            </span>

        </div>


        <!-- ADDRESS -->

        <div class="member-archive-address">

            <?= htmlspecialchars(
                $address,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>


        <!-- STATUS -->

        <div>

            <span class="
                member-archive-chip
                <?= $statusClass; ?>
            ">

                <span class="member-archive-dot"></span>

                <?= htmlspecialchars(
                    $status,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </span>

        </div>


        <!-- CLASSIFICATION -->

        <div>

            <span class="
                member-archive-chip
                <?= $classificationClass; ?>
            ">

                <span class="member-archive-dot"></span>

                <?= htmlspecialchars(
                    $classification,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </span>

        </div>


        <!-- ARCHIVED BY -->

        <div class="member-archive-by">

            <strong>

                <?= htmlspecialchars(
                    $archivedBy,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>

            </strong>

        </div>


        <!-- ACTION -->

        <div>

            <a
            href="#"
            class="
                retrieve-btn
                member-retrieve-btn
            "
            data-id="<?= $memberID; ?>">

                RETRIEVE

            </a>

        </div>


    </div>


<?php

    }

} else {

?>


    <div class="archive-empty">

        No archived members found.

    </div>


<?php

}

?>


</div>


<?php

}

?>


<!-- =====================================================
     ACCOUNT ARCHIVE
====================================================== -->

<?php

if(
    $module === 'account'
){

    $sql =
    "
    SELECT *
    FROM users
    WHERE archived = 1
    ORDER BY user_id DESC
    ";


    $result =
    mysqli_query(
        $conn,
        $sql
    );

?>


<div class="archive-table">


    <div class="archive-header account-header">

        <div>RECORD ID</div>

        <div>NAME</div>

        <div>EMAIL</div>

        <div>ROLE</div>

        <div>ACTION BY</div>

        <div>ACTIONS</div>

    </div>


<?php

if(
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
){

    while(
        $row =
        mysqli_fetch_assoc(
            $result
        )
    ){

?>


    <div class="archive-row account-row">


        <div>

            <?= str_pad(
                $row['user_id'],
                3,
                "0",
                STR_PAD_LEFT
            ) ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $row['name']
            ) ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $row['email']
            ) ?>

        </div>


        <div>

            <?= htmlspecialchars(
                strtoupper(
                    $row['role']
                )
            ) ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $row['archived_by']
                ??
                'Not recorded'
            ) ?>

        </div>


        <div>

            <a
            href="#"
            class="retrieve-btn account-retrieve-btn"
            data-id="<?= (int)$row['user_id'] ?>">

                RETRIEVE

            </a>

        </div>


    </div>


<?php

    }

}else{

?>


    <div class="archive-empty">

        No archived accounts found.

    </div>


<?php

}

?>


</div>


<?php

}

?>


<!-- =====================================================
     LENDING ARCHIVE
====================================================== -->

<?php

if(
    $module === 'lending'
){

    require __DIR__ . '/../lending/archive_lending.php';

}

?>

<!-- =====================================================
     PRODUCT LIST ARCHIVE
====================================================== -->

<?php

if (
    $module === "product_list"
) {

    $sql = "
        SELECT
            loan_product_id,
            product_name,
            loan_price,
            image,
            updated_at
        FROM product_loan_products
        WHERE archived = 1
        ORDER BY loan_product_id DESC
    ";


    $result =
        mysqli_query(
            $conn,
            $sql
        );

?>


<div class="
    archive-table
    product-list-archive-table
">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="
        archive-header
        product-list-archive-header
    ">

        <div>
            PRODUCT
        </div>

        <div>
            LOAN PRICE
        </div>

        <div>
            ARCHIVED DATE
        </div>

        <div>
            ACTION
        </div>

    </div>


<?php

if (
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
) {

    while (
        $row =
            mysqli_fetch_assoc(
                $result
            )
    ) {

        /* =============================================
           SAFE VALUES
        ============================================= */

        $productID =
            (int) (
                $row[
                    "loan_product_id"
                ]
                ??
                0
            );


        $productName =
            trim(
                (string) (
                    $row[
                        "product_name"
                    ]
                    ??
                    ""
                )
            );


        if (
            $productName === ""
        ) {

            $productName =
                "Unnamed Product";

        }


        $loanPrice =
            max(
                0,
                (float) (
                    $row[
                        "loan_price"
                    ]
                    ??
                    0
                )
            );


        /* =============================================
           ARCHIVED DATE

           archive_product.php sets updated_at = NOW()
           when the Product List item is archived.
        ============================================= */

        $archivedDate =
            "—";


        if (
            !empty(
                $row[
                    "updated_at"
                ]
            )
        ) {

            $archivedTimestamp =
                strtotime(
                    $row[
                        "updated_at"
                    ]
                );


            if (
                $archivedTimestamp !== false
            ) {

                $archivedDate =
                    date(
                        "m-d-Y h:i A",
                        $archivedTimestamp
                    );

            }

        }


        /* =============================================
           SAFE PRODUCT IMAGE
        ============================================= */

        $image =
            str_replace(
                "\\",
                "/",
                trim(
                    (string) (
                        $row[
                            "image"
                        ]
                        ??
                        ""
                    )
                )
            );


        $normalizedImage =
            preg_replace(
                "#/+#",
                "/",
                $image
            )
            ??
            "";


        $imageFilename =
            basename(
                $normalizedImage
            );


        $safeImagePath =
            "";


        $physicalImagePath =
            "";


        if (
            $imageFilename !== ""
            &&
            str_starts_with(
                $normalizedImage,
                "assets/lending/product_loan_products/"
            )
        ) {

            $safeImagePath =
                "../assets/lending/product_loan_products/"
                .
                $imageFilename;


            $physicalImagePath =
                dirname(
                    __DIR__
                )
                .
                DIRECTORY_SEPARATOR
                .
                "assets"
                .
                DIRECTORY_SEPARATOR
                .
                "lending"
                .
                DIRECTORY_SEPARATOR
                .
                "product_loan_products"
                .
                DIRECTORY_SEPARATOR
                .
                $imageFilename;

        }


        $hasProductImage =
            $safeImagePath !== ""
            &&
            $physicalImagePath !== ""
            &&
            is_file(
                $physicalImagePath
            );

?>


    <!-- =================================================
         ARCHIVED PRODUCT LIST ROW
    ================================================== -->

    <div class="
        archive-row
        product-list-archive-row
    ">


        <!-- PRODUCT -->

        <div class="product-list-archive-product">


            <div class="product-list-archive-image">

                <?php

                if (
                    $hasProductImage
                ) {

                ?>

                    <img
                        src="<?= htmlspecialchars(
                            $safeImagePath,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        alt="<?= htmlspecialchars(
                            $productName,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        loading="lazy">

                <?php

                } else {

                ?>

                    <i class="bi bi-box-seam"></i>

                <?php

                }

                ?>

            </div>


            <div class="product-list-archive-product-info">

                <strong>

                    <?= htmlspecialchars(
                        $productName,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </strong>


                <span>

                    Product #<?= str_pad(
                        (string) $productID,
                        3,
                        "0",
                        STR_PAD_LEFT
                    ); ?>

                </span>

            </div>


        </div>


        <!-- LOAN PRICE -->

        <div class="product-list-archive-money">

            ₱<?= number_format(
                $loanPrice,
                2
            ); ?>

        </div>


        <!-- ARCHIVED DATE -->

        <div class="product-list-archive-date">

            <?= htmlspecialchars(
                $archivedDate,
                ENT_QUOTES,
                "UTF-8"
            ); ?>

        </div>


        <!-- RETRIEVE -->

        <div>

            <button
                type="button"
                class="
                    retrieve-btn
                    product-list-retrieve-btn
                "
                data-id="<?= $productID; ?>"
                data-name="<?= htmlspecialchars(
                    $productName,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>">

                RETRIEVE

            </button>

        </div>


    </div>


<?php

    }

} else {

?>


    <div class="archive-empty">

        No archived Product List items found.

    </div>


<?php

}

?>


</div>


<?php

}

?>


<!-- =====================================================
     INVENTORY ARCHIVE
====================================================== -->

<?php

if (
    $module === "inventory"
) {

    $sql = "
        SELECT
            product_id,
            product_name,
            acquired_price,
            markup_percent,
            selling_price,
            quantity,
            image,
            archived_by
        FROM inventory
        WHERE archived = 1
        ORDER BY product_id DESC
    ";


    $result =
        mysqli_query(
            $conn,
            $sql
        );

?>


<div class="archive-table inventory-archive-table">


    <!-- =================================================
         HEADER
    ================================================== -->

    <div class="archive-header inventory-header">

        <div>
            PRODUCT
        </div>

        <div>
            ACQUIRED PRICE
        </div>

        <div>
            MARKUP
        </div>

        <div>
            SELLING PRICE
        </div>

        <div>
            QUANTITY
        </div>

        <div>
            ARCHIVED BY
        </div>

        <div>
            ACTION
        </div>

    </div>


<?php

if (
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
) {

    while (
        $row =
            mysqli_fetch_assoc(
                $result
            )
    ) {

        /* =============================================
           SAFE VALUES
        ============================================= */

        $productID =
            (int) (
                $row["product_id"]
                ??
                0
            );


        $productName =
            trim(
                (string) (
                    $row["product_name"]
                    ??
                    ""
                )
            );


        if (
            $productName === ""
        ) {
            $productName =
                "Unnamed Product";
        }


        $acquiredPrice =
            (float) (
                $row["acquired_price"]
                ??
                0
            );


        $markupPercent =
            (float) (
                $row["markup_percent"]
                ??
                0
            );


        $sellingPrice =
            (float) (
                $row["selling_price"]
                ??
                0
            );


        $quantity =
            (int) (
                $row["quantity"]
                ??
                0
            );


        $archivedBy =
            trim(
                (string) (
                    $row["archived_by"]
                    ??
                    ""
                )
            );


        if (
            $archivedBy === ""
        ) {
            $archivedBy =
                "Not recorded";
        }


        /* =============================================
           SAFE PRODUCT IMAGE
        ============================================= */

        $image =
            trim(
                (string) (
                    $row["image"]
                    ??
                    ""
                )
            );


        $normalizedImage =
            str_replace(
                "\\",
                "/",
                $image
            );


        $normalizedImage =
            preg_replace(
                "#/+#",
                "/",
                $normalizedImage
            )
            ??
            "";


        $imageFilename =
            basename(
                $normalizedImage
            );


        $safeImagePath =
            $imageFilename !== ""
            ?
            "../assets/products/"
            .
            $imageFilename
            :
            "";


        $physicalImagePath =
            $imageFilename !== ""
            ?
            dirname(
                __DIR__
            )
            .
            DIRECTORY_SEPARATOR
            .
            "assets"
            .
            DIRECTORY_SEPARATOR
            .
            "products"
            .
            DIRECTORY_SEPARATOR
            .
            $imageFilename
            :
            "";


        $hasProductImage =
            $safeImagePath !== ""
            &&
            $physicalImagePath !== ""
            &&
            is_file(
                $physicalImagePath
            );

?>


    <!-- =================================================
         ARCHIVED PRODUCT ROW
    ================================================== -->

    <div class="archive-row inventory-row">


        <!-- PRODUCT -->

        <div class="inventory-archive-product">


            <div class="inventory-archive-image">

                <?php

                if (
                    $hasProductImage
                ) {

                ?>

                    <img
                        src="<?= htmlspecialchars(
                            $safeImagePath,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        alt="<?= htmlspecialchars(
                            $productName,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>"
                        loading="lazy">

                <?php

                } else {

                ?>

                    <i class="bi bi-image"></i>

                <?php

                }

                ?>

            </div>


            <div class="inventory-archive-product-info">

                <strong>

                    <?= htmlspecialchars(
                        $productName,
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>

                </strong>


                <span>

                    Product #<?= str_pad(
                        (string) $productID,
                        3,
                        "0",
                        STR_PAD_LEFT
                    ); ?>

                </span>

            </div>


        </div>


        <!-- ACQUIRED PRICE -->

        <div class="inventory-archive-money">

            ₱<?= number_format(
                $acquiredPrice,
                2
            ); ?>

        </div>


        <!-- MARKUP -->

        <div>

            <span class="inventory-archive-markup">

                <?= number_format(
                    $markupPercent,
                    2
                ); ?>%

            </span>

        </div>


        <!-- SELLING PRICE -->

        <div class="inventory-archive-money selling">

            ₱<?= number_format(
                $sellingPrice,
                2
            ); ?>

        </div>


        <!-- QUANTITY -->

        <div>

            <span class="
                inventory-archive-quantity
                <?= $quantity <= 0
                    ? "out-of-stock"
                    : "";
                ?>
            ">

                <?php

                if (
                    $quantity <= 0
                ) {

                    echo "Out of Stock";

                } else {

                    echo number_format(
                        $quantity
                    );

                }

                ?>

            </span>

        </div>


        <!-- ARCHIVED BY -->

        <div class="inventory-archive-user">

            <?= htmlspecialchars(
                $archivedBy,
                ENT_QUOTES,
                "UTF-8"
            ); ?>

        </div>


        <!-- RETRIEVE -->

        <div>

            <button
                type="button"
                class="
                    retrieve-btn
                    inventory-retrieve-btn
                "
                data-id="<?= $productID; ?>"
                data-name="<?= htmlspecialchars(
                    $productName,
                    ENT_QUOTES,
                    "UTF-8"
                ); ?>">

                RETRIEVE

            </button>

        </div>


    </div>


<?php

    }

} else {

?>


    <div class="archive-empty">

        No archived products found.

    </div>


<?php

}

?>


</div>


<?php

}

?>


<!-- =====================================================
     TRACTOR REGISTRY ARCHIVE
====================================================== -->

<?php

if(
    $module === 'tractor'
){

    $sql =
    "
    SELECT *
    FROM tractors
    WHERE archived = 1
    ORDER BY tractor_id DESC
    ";


    $result =
    mysqli_query(
        $conn,
        $sql
    );

?>


<div class="archive-table">


    <div class="archive-header tractor-archive-header">

        <div>RECORD ID</div>

        <div>TRACTOR NO.</div>

        <div>TRACTOR NAME</div>

        <div>TYPE</div>

        <div>STATUS</div>

        <div>ACTION BY</div>

        <div>ACTIONS</div>

    </div>


<?php

if(
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
){

    while(
        $row =
        mysqli_fetch_assoc(
            $result
        )
    ){

        $typeClass =
        strtolower(
            trim(
                $row['tractor_type']
            )
        );

?>


    <div class="archive-row tractor-archive-row">


        <div>

            <?= str_pad(
                $row['tractor_id'],
                3,
                "0",
                STR_PAD_LEFT
            ) ?>

        </div>


        <div class="tractor-archive-number">

            <?= htmlspecialchars(
                $row['tractor_no']
            ) ?>

        </div>


        <div class="tractor-archive-name">

            <?= htmlspecialchars(
                $row['tractor_name']
            ) ?>

        </div>


        <div>

            <span
            class="tractor-archive-type tractor-archive-type-<?= htmlspecialchars($typeClass) ?>">

                <?= htmlspecialchars(
                    $row['tractor_type']
                ) ?>

            </span>

        </div>


        <div>

            <?= htmlspecialchars(
                $row['status']
            ) ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $row['archived_by']
                ??
                'Not recorded'
            ) ?>

        </div>


        <div>

            <a
            href="#"
            class="retrieve-btn tractor-retrieve-btn"
            data-id="<?= (int)$row['tractor_id'] ?>"
            data-number="<?= htmlspecialchars($row['tractor_no']) ?>"
            data-name="<?= htmlspecialchars($row['tractor_name']) ?>">

                RETRIEVE

            </a>

        </div>


    </div>


<?php

    }

}else{

?>


    <div class="archive-empty">

        No archived tractors found.

    </div>


<?php

}

?>


</div>


<?php

}

?>


<!-- =====================================================
     DRIVER REGISTRY ARCHIVE
====================================================== -->

<?php

if (
    $module === 'driver'
) {

    $sql = "
        SELECT
            d.driver_id,
            d.member_id,
            d.full_name AS stored_full_name,
            d.contact_no AS stored_contact_no,
            d.status,
            d.archived_by,
            d.archived_at,
            m.member_code,
            m.full_name AS member_full_name,
            m.contact_no AS member_contact_no
        FROM drivers d
        LEFT JOIN members m
            ON m.member_id = d.member_id
        WHERE d.archived = 1
        ORDER BY d.driver_id DESC
    ";


    $result =
        mysqli_query(
            $conn,
            $sql
        );

?>


<div class="archive-table driver-archive-table">


    <div
    class="archive-header driver-archive-header"
    style="grid-template-columns: 120px 1.5fr 1.1fr 1fr 1.1fr 130px;">

        <div>DRIVER ID</div>

        <div>DRIVER NAME</div>

        <div>CONTACT NO.</div>

        <div>STATUS</div>

        <div>ARCHIVED BY</div>

        <div>ACTIONS</div>

    </div>


<?php

if (
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
) {

    while (
        $row =
            mysqli_fetch_assoc(
                $result
            )
    ) {

        $driverID =
            (int) (
                $row['driver_id']
                ??
                0
            );


        $driverCode =
            'DRV-'
            .
            str_pad(
                (string) $driverID,
                4,
                '0',
                STR_PAD_LEFT
            );


        $driverName =
            trim(
                (string) (
                    $row['member_full_name']
                    ??
                    ''
                )
            );


        if (
            $driverName === ''
        ) {

            $driverName =
                trim(
                    (string) (
                        $row['stored_full_name']
                        ??
                        ''
                    )
                );

        }


        if (
            $driverName === ''
        ) {

            $driverName =
                '—';

        }


        $contactNo =
            trim(
                (string) (
                    $row['member_contact_no']
                    ??
                    ''
                )
            );


        if (
            $contactNo === ''
        ) {

            $contactNo =
                trim(
                    (string) (
                        $row['stored_contact_no']
                        ??
                        ''
                    )
                );

        }


        if (
            $contactNo === ''
        ) {

            $contactNo =
                '—';

        }


        $driverStatus =
            trim(
                (string) (
                    $row['status']
                    ??
                    'Inactive'
                )
            );


        if (
            $driverStatus === ''
        ) {

            $driverStatus =
                'Inactive';

        }


        $archivedBy =
            trim(
                (string) (
                    $row['archived_by']
                    ??
                    ''
                )
            );


        if (
            $archivedBy === ''
        ) {

            $archivedBy =
                'Not recorded';

        }

?>


    <div
    class="archive-row driver-archive-row"
    style="grid-template-columns: 120px 1.5fr 1.1fr 1fr 1.1fr 130px;">


        <div>

            <?= htmlspecialchars(
                $driverCode,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $driverName,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $contactNo,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $driverStatus,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>


        <div>

            <?= htmlspecialchars(
                $archivedBy,
                ENT_QUOTES,
                'UTF-8'
            ); ?>

        </div>


        <div>

            <button
            type="button"
            class="retrieve-btn driver-retrieve-btn"
            data-id="<?= $driverID; ?>"
            data-name="<?= htmlspecialchars(
                $driverName,
                ENT_QUOTES,
                'UTF-8'
            ); ?>">

                RETRIEVE

            </button>

        </div>


    </div>


<?php

    }

} else {

?>


    <div class="archive-empty">

        No archived drivers found.

    </div>


<?php

}

?>


</div>


<?php

}

?>


<script src="../assets/sweetalert/sweetalert2.min.js"></script>


<!-- =====================================================
     RESTORE SUCCESS ALERT
====================================================== -->

<?php

if(
    isset(
        $_GET['success']
    )
    &&
    $_GET['success'] === 'restored'
){

    $title =
    "Account Restored!";


    $text =
    "The account has been restored successfully.";


    if(
        $module === 'member'
    ){

        $title =
        "Member Restored!";


        $text =
        "The member has been restored successfully.";
    
    }elseif(
        $module === 'lending'
    ){

        $title =
        "Product Loan Restored!";


        $text =
        "The Product Loan has been restored successfully.";
    
    }elseif(
        $module === 'product_list'
    ){

        $title =
        "Product Restored!";


        $text =
        "The Product List item has been restored successfully.";

    }elseif(
        $module === 'inventory'
    ){

        $title =
        "Product Restored!";


        $text =
        "The product has been restored successfully.";

    }elseif(
        $module === 'tractor'
    ){

        $title =
        "Tractor Restored!";


        $text =
        "The tractor has been restored successfully.";

    }elseif(
        $module === 'driver'
    ){

        $title =
        "Driver Restored!";


        $text =
        "The driver has been restored successfully.";

    }

?>


<script>

Swal.fire({

    icon:"success",

    title:<?= json_encode($title) ?>,

    text:<?= json_encode($text) ?>,

    showConfirmButton:false,

    timer:1800,

    timerProgressBar:true

});

</script>


<?php

}

?>

<?php

/* =====================================================
   ARCHIVE ERROR ALERTS
===================================================== */

$archiveErrorMessages = [

    "invalid_request" => [
        "icon" => "error",
        "title" => "Invalid Request",
        "text" => "The requested archive action is not allowed."
    ],

    "session" => [
        "icon" => "warning",
        "title" => "Session Expired",
        "text" => "Your session has expired. Please sign in again."
    ],

    "forbidden" => [
        "icon" => "error",
        "title" => "Access Denied",
        "text" => "You do not have permission to perform this archive action."
    ],

    "csrf" => [
        "icon" => "warning",
        "title" => "Security Check Failed",
        "text" => "The security token is invalid or has expired. Refresh the page and try again."
    ],

    "invalid_account" => [
        "icon" => "error",
        "title" => "Invalid Account",
        "text" => "The selected account could not be identified."
    ],

    "account_not_found" => [
        "icon" => "error",
        "title" => "Account Not Found",
        "text" => "The archived account could not be found."
    ],

    "not_archived" => [
        "icon" => "warning",
        "title" => "Account Already Active",
        "text" => "This account is no longer archived."
    ],

    "account_duplicate" => [
        "icon" => "warning",
        "title" => "Account Already Exists",
        "text" => "An active account with the same name already exists."
    ],

    "retrieve_failed" => [
        "icon" => "error",
        "title" => "Retrieve Failed",
        "text" => "The selected record could not be restored. No changes were saved."
    ],

    "member_duplicate" => [
        "icon" => "warning",
        "title" => "Member Already Exists",
        "text" => "An active member with the same name already exists."
    ],

    "invalid_tractor" => [
        "icon" => "error",
        "title" => "Invalid Tractor",
        "text" => "The selected tractor could not be identified."
    ],

    "tractor_not_found" => [
        "icon" => "error",
        "title" => "Tractor Not Found",
        "text" => "The archived tractor could not be found."
    ],

    "tractor_not_archived" => [
        "icon" => "warning",
        "title" => "Tractor Already Active",
        "text" => "This tractor is no longer archived."
    ],

    "tractor_retrieve_failed" => [
        "icon" => "error",
        "title" => "Retrieve Failed",
        "text" => "The tractor could not be restored. No changes were saved."
    ],

    "invalid_driver" => [
        "icon" => "error",
        "title" => "Invalid Driver",
        "text" => "The selected driver could not be identified."
    ],

    "driver_not_found" => [
        "icon" => "error",
        "title" => "Driver Not Found",
        "text" => "The archived driver could not be found."
    ],

    "driver_not_archived" => [
        "icon" => "warning",
        "title" => "Driver Already Active",
        "text" => "This driver is no longer archived."
    ],

    "driver_member_unavailable" => [
        "icon" => "warning",
        "title" => "Member Unavailable",
        "text" => "The linked Member must be active and not archived before this driver can be restored."
    ],

    "driver_retrieve_failed" => [
        "icon" => "error",
        "title" => "Retrieve Failed",
        "text" => "The driver could not be restored. No changes were saved."
    ],

    "invalid_product_list_product" => [
        "icon" => "error",
        "title" => "Invalid Product",
        "text" => "The selected Product List item could not be identified."
    ],

    "product_list_product_not_found" => [
        "icon" => "error",
        "title" => "Product Not Found",
        "text" => "The archived Product List item could not be found."
    ],

    "product_list_duplicate" => [
        "icon" => "warning",
        "title" => "Product Already Exists",
        "text" => "An active Product List item with the same product name already exists."
    ]

];


$archiveErrorCode =
    trim(
        (string) (
            $_GET["error"]
            ?? ""
        )
    );


if (
    isset(
        $archiveErrorMessages[
            $archiveErrorCode
        ]
    )
) {

    $archiveError =
        $archiveErrorMessages[
            $archiveErrorCode
        ];

?>

<script>

Swal.fire({

    icon:
        <?= json_encode(
            $archiveError["icon"]
        ); ?>,

    title:
        <?= json_encode(
            $archiveError["title"]
        ); ?>,

    text:
        <?= json_encode(
            $archiveError["text"]
        ); ?>,

    confirmButtonColor:
        "#8CA15B",

    showConfirmButton:
        true

});

</script>

<?php

}

?>


<script>


/* =====================================================
   RETRIEVE MEMBER
===================================================== */

const memberRestoreCsrfToken =
    <?= json_encode(
        $memberArchiveCsrfToken,
        JSON_UNESCAPED_UNICODE
        |
        JSON_UNESCAPED_SLASHES
    ); ?>;


document
.querySelectorAll(
    ".member-retrieve-btn"
)
.forEach(button=>{

    button.addEventListener(
        "click",
        function(e){

            e.preventDefault();


            const id =
                Number(
                    this.dataset.id
                );


            if(
                !Number.isInteger(id)
                ||
                id <= 0
            ){

                Swal.fire({
                    icon:"error",
                    title:"Invalid Member",
                    text:"The selected member could not be identified.",
                    confirmButtonColor:"#8CA15B"
                });

                return;
            }


            Swal.fire({

                title:"Retrieve Member?",

                text:
                "Are you sure you want to retrieve this member?",

                icon:"question",

                showCancelButton:true,

                confirmButtonText:"Retrieve",

                cancelButtonText:"Cancel",

                confirmButtonColor:"#8CA15B",

                cancelButtonColor:"#C62828",

                reverseButtons:true

            }).then(result=>{

                if(
                    result.isConfirmed
                ){

                    const form =
                        document.createElement(
                            "form"
                        );


                    form.method =
                        "POST";


                    form.action =
                        "retrieve_member.php";


                    form.style.display =
                        "none";


                    const memberInput =
                        document.createElement(
                            "input"
                        );


                    memberInput.type =
                        "hidden";


                    memberInput.name =
                        "member_id";


                    memberInput.value =
                        String(id);


                    form.appendChild(
                        memberInput
                    );


                    const csrfInput =
                        document.createElement(
                            "input"
                        );


                    csrfInput.type =
                        "hidden";


                    csrfInput.name =
                        "csrf_token";


                    csrfInput.value =
                        memberRestoreCsrfToken;


                    form.appendChild(
                        csrfInput
                    );


                    document.body.appendChild(
                        form
                    );


                    form.submit();

                }

            });

        }
    );

});


/* =====================================================
   RETRIEVE ACCOUNT
===================================================== */

const accountRestoreCsrfToken =
    <?= json_encode(
        $accountCsrfToken
    ); ?>;


document
.querySelectorAll(
    ".account-retrieve-btn"
)
.forEach(button=>{

    button.addEventListener(
        "click",
        function(e){

            e.preventDefault();


            const id =
                Number(
                    this.dataset.id
                );


            if(
                !Number.isInteger(id)
                ||
                id <= 0
            ){

                Swal.fire({

                    icon:
                        "error",

                    title:
                        "Invalid Account",

                    text:
                        "The selected account could not be identified.",

                    confirmButtonColor:
                        "#8CA15B"

                });


                return;

            }


            Swal.fire({

                title:
                    "Retrieve Account?",

                text:
                    "This account will be restored to the active Account ledger.",

                icon:
                    "question",

                showCancelButton:
                    true,

                confirmButtonText:
                    "RETRIEVE",

                cancelButtonText:
                    "CANCEL",

                confirmButtonColor:
                    "#8CA15B",

                cancelButtonColor:
                    "#C62828",

                reverseButtons:
                    true,

                allowOutsideClick:
                    false

            }).then(result=>{

                if(
                    !result.isConfirmed
                ){
                    return;
                }


                Swal.fire({

                    title:
                        "Retrieving Account...",

                    text:
                        "Please wait while the account is being restored.",

                    allowOutsideClick:
                        false,

                    allowEscapeKey:
                        false,

                    showConfirmButton:
                        false,

                    didOpen:()=>{

                        Swal.showLoading();

                    }

                });


                /* =========================================
                   SECURE POST FORM
                ========================================= */

                const form =
                    document.createElement(
                        "form"
                    );


                form.method =
                    "POST";


                form.action =
                    "retrieve_account.php";


                form.style.display =
                    "none";


                /* ACCOUNT ID */

                const idInput =
                    document.createElement(
                        "input"
                    );


                idInput.type =
                    "hidden";


                idInput.name =
                    "user_id";


                idInput.value =
                    String(id);


                /* CSRF TOKEN */

                const csrfInput =
                    document.createElement(
                        "input"
                    );


                csrfInput.type =
                    "hidden";


                csrfInput.name =
                    "csrf_token";


                csrfInput.value =
                    accountRestoreCsrfToken;


                form.appendChild(
                    idInput
                );


                form.appendChild(
                    csrfInput
                );


                document.body.appendChild(
                    form
                );


                form.submit();

            });

        }
    );

});


/* =====================================================
   RETRIEVE PRODUCT
===================================================== */

document
.querySelectorAll(
    ".inventory-retrieve-btn"
)
.forEach(button => {

    button.addEventListener(
        "click",
        function(e){

            e.preventDefault();


            const id =
                this.dataset.id;


            const productName =
                this.dataset.name
                ||
                "this product";


            Swal.fire({

                title:
                    "Retrieve Product?",

                text:
                    "Are you sure you want to retrieve " +
                    productName +
                    "?",

                icon:
                    "question",

                showCancelButton:
                    true,

                confirmButtonText:
                    "Retrieve",

                cancelButtonText:
                    "Cancel",

                confirmButtonColor:
                    "#8CA15B",

                cancelButtonColor:
                    "#C62828",

                reverseButtons:
                    true

            }).then(result => {

                if (
                    !result.isConfirmed
                ) {
                    return;
                }


                /* =========================================
                   SHOW RESTORING STATE
                ========================================= */

                Swal.fire({

                    title:
                        "Retrieving Product...",

                    text:
                        "Please wait while the product is being restored.",

                    allowOutsideClick:
                        false,

                    allowEscapeKey:
                        false,

                    showConfirmButton:
                        false,

                    didOpen: () => {

                        Swal.showLoading();

                    }

                });


                /* =========================================
                   SECURE POST FORM
                ========================================= */

                const form =
                    document.createElement(
                        "form"
                    );


                form.method =
                    "POST";


                form.action =
                    "retrieve_product.php";


                form.style.display =
                    "none";


                /* PRODUCT ID */

                const productInput =
                    document.createElement(
                        "input"
                    );


                productInput.type =
                    "hidden";


                productInput.name =
                    "product_id";


                productInput.value =
                    id;


                form.appendChild(
                    productInput
                );


                /* CSRF TOKEN */

                const csrfInput =
                    document.createElement(
                        "input"
                    );


                csrfInput.type =
                    "hidden";


                csrfInput.name =
                    "csrf_token";


                csrfInput.value =
                    <?= json_encode(
                        $_SESSION[
                            "archive_inventory_csrf_token"
                        ]
                        ??
                        "",
                        JSON_UNESCAPED_UNICODE
                        |
                        JSON_UNESCAPED_SLASHES
                    ); ?>;


                form.appendChild(
                    csrfInput
                );


                /* SUBMIT */

                document.body.appendChild(
                    form
                );


                form.submit();

            });

        }

    );

});

/* =====================================================
   RETRIEVE PRODUCT LIST ITEM
===================================================== */

const productListRestoreCsrfToken =
    <?= json_encode(
        $productListArchiveCsrfToken
    ); ?>;


document
.querySelectorAll(
    ".product-list-retrieve-btn"
)
.forEach(button => {

    button.addEventListener(
        "click",
        function(e){

            e.preventDefault();


            const id =
                Number(
                    this.dataset.id
                );


            const productName =
                this.dataset.name
                ||
                "this product";


            if (
                !Number.isInteger(id)
                ||
                id <= 0
            ) {

                Swal.fire({

                    icon:
                        "error",

                    title:
                        "Invalid Product",

                    text:
                        "The selected Product List item could not be identified.",

                    confirmButtonColor:
                        "#8CA15B"

                });


                return;
            }


            Swal.fire({

                title:
                    "Retrieve Product?",

                text:
                    productName +
                    " will be restored to the Product List.",

                icon:
                    "question",

                showCancelButton:
                    true,

                confirmButtonText:
                    "RETRIEVE",

                cancelButtonText:
                    "CANCEL",

                confirmButtonColor:
                    "#8CA15B",

                cancelButtonColor:
                    "#C62828",

                reverseButtons:
                    true,

                allowOutsideClick:
                    false

            }).then(result => {

                if (
                    !result.isConfirmed
                ) {
                    return;
                }


                Swal.fire({

                    title:
                        "Retrieving Product...",

                    text:
                        "Please wait while the Product List item is being restored.",

                    allowOutsideClick:
                        false,

                    allowEscapeKey:
                        false,

                    showConfirmButton:
                        false,

                    didOpen: () => {

                        Swal.showLoading();

                    }

                });


                const form =
                    document.createElement(
                        "form"
                    );


                form.method =
                    "POST";


                form.action =
                    "retrieve_product_list.php";


                form.style.display =
                    "none";


                /* PRODUCT LIST ID */

                const productInput =
                    document.createElement(
                        "input"
                    );


                productInput.type =
                    "hidden";


                productInput.name =
                    "loan_product_id";


                productInput.value =
                    String(id);


                /* CSRF TOKEN */

                const csrfInput =
                    document.createElement(
                        "input"
                    );


                csrfInput.type =
                    "hidden";


                csrfInput.name =
                    "csrf_token";


                csrfInput.value =
                    productListRestoreCsrfToken;


                form.appendChild(
                    productInput
                );


                form.appendChild(
                    csrfInput
                );


                document.body.appendChild(
                    form
                );


                form.submit();

            });

        }

    );

});


/* =====================================================
   RETRIEVE TRACTOR
===================================================== */

const tractorRestoreCsrfToken =
    <?= json_encode(
        $tractorCsrfToken
    ); ?>;


document
.querySelectorAll(
    ".tractor-retrieve-btn"
)
.forEach(button=>{

    button.addEventListener(
        "click",
        function(e){

            e.preventDefault();


            const id =
                Number(
                    this.dataset.id
                );


            const tractorNumber =
                this.dataset.number
                ||
                "";


            const tractorName =
                this.dataset.name
                ||
                "";


            if(
                !Number.isInteger(id)
                ||
                id <= 0
            ){

                Swal.fire({

                    icon:
                        "error",

                    title:
                        "Invalid Tractor",

                    text:
                        "The selected tractor could not be identified.",

                    confirmButtonColor:
                        "#8CA15B"

                });


                return;

            }


            const tractorLabel =
                [
                    tractorNumber,
                    tractorName
                ]
                .filter(Boolean)
                .join(" - ");


            Swal.fire({

                title:
                    "Retrieve Tractor?",

                text:
                    tractorLabel !== ""
                    ?
                    tractorLabel +
                    " will be restored to the active Tractor Registry."
                    :
                    "This tractor will be restored to the active Tractor Registry.",

                icon:
                    "question",

                showCancelButton:
                    true,

                confirmButtonText:
                    "RETRIEVE",

                cancelButtonText:
                    "CANCEL",

                confirmButtonColor:
                    "#8CA15B",

                cancelButtonColor:
                    "#C62828",

                reverseButtons:
                    true,

                allowOutsideClick:
                    false

            }).then(result=>{

                if(
                    !result.isConfirmed
                ){
                    return;
                }


                Swal.fire({

                    title:
                        "Retrieving Tractor...",

                    text:
                        "Please wait while the tractor is being restored.",

                    allowOutsideClick:
                        false,

                    allowEscapeKey:
                        false,

                    showConfirmButton:
                        false,

                    didOpen:()=>{

                        Swal.showLoading();

                    }

                });


                /* =========================================
                   SECURE POST FORM
                ========================================= */

                const form =
                    document.createElement(
                        "form"
                    );


                form.method =
                    "POST";


                form.action =
                    "retrieve_tractor.php";


                form.style.display =
                    "none";


                /* TRACTOR ID */

                const idInput =
                    document.createElement(
                        "input"
                    );


                idInput.type =
                    "hidden";


                idInput.name =
                    "tractor_id";


                idInput.value =
                    String(id);


                /* CSRF TOKEN */

                const csrfInput =
                    document.createElement(
                        "input"
                    );


                csrfInput.type =
                    "hidden";


                csrfInput.name =
                    "csrf_token";


                csrfInput.value =
                    tractorRestoreCsrfToken;


                form.appendChild(
                    idInput
                );


                form.appendChild(
                    csrfInput
                );


                document.body.appendChild(
                    form
                );


                form.submit();

            });

        }
    );

});


/* =====================================================
   RETRIEVE DRIVER
===================================================== */

const driverRestoreCsrfToken =
    <?= json_encode(
        $driverArchiveCsrfToken
    ); ?>;


document
.querySelectorAll(
    ".driver-retrieve-btn"
)
.forEach(button=>{

    button.addEventListener(
        "click",
        function(e){

            e.preventDefault();


            const id =
                Number(
                    this.dataset.id
                );


            const driverName =
                this.dataset.name
                ||
                "this driver";


            if (
                !Number.isInteger(id)
                ||
                id <= 0
            ) {

                Swal.fire({

                    icon:
                        "error",

                    title:
                        "Invalid Driver",

                    text:
                        "The selected driver could not be identified.",

                    confirmButtonColor:
                        "#8CA15B"

                });


                return;

            }


            Swal.fire({

                title:
                    "Retrieve Driver?",

                text:
                    driverName +
                    " will be restored to the active Driver Registry.",

                icon:
                    "question",

                showCancelButton:
                    true,

                confirmButtonText:
                    "RETRIEVE",

                cancelButtonText:
                    "CANCEL",

                confirmButtonColor:
                    "#8CA15B",

                cancelButtonColor:
                    "#C62828",

                reverseButtons:
                    true,

                allowOutsideClick:
                    false

            }).then(result=>{

                if (
                    !result.isConfirmed
                ) {
                    return;
                }


                Swal.fire({

                    title:
                        "Retrieving Driver...",

                    text:
                        "Please wait while the driver is being restored.",

                    allowOutsideClick:
                        false,

                    allowEscapeKey:
                        false,

                    showConfirmButton:
                        false,

                    didOpen:()=>{

                        Swal.showLoading();

                    }

                });


                const form =
                    document.createElement(
                        "form"
                    );


                form.method =
                    "POST";


                form.action =
                    "retrieve_driver.php";


                form.style.display =
                    "none";


                const idInput =
                    document.createElement(
                        "input"
                    );


                idInput.type =
                    "hidden";


                idInput.name =
                    "driver_id";


                idInput.value =
                    String(id);


                const csrfInput =
                    document.createElement(
                        "input"
                    );


                csrfInput.type =
                    "hidden";


                csrfInput.name =
                    "csrf_token";


                csrfInput.value =
                    driverRestoreCsrfToken;


                form.appendChild(
                    idInput
                );


                form.appendChild(
                    csrfInput
                );


                document.body.appendChild(
                    form
                );


                form.submit();

            });

        }
    );

});


</script>


<script src="../js/sidebar.js"></script>