<?php

/* =========================================================
   PRODUCT LOAN - PRODUCT LIST
========================================================= */

if (
    session_status() ===
    PHP_SESSION_NONE
) {
    session_start();
}


date_default_timezone_set(
    "Asia/Manila"
);


error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);


/* =========================================================
   SECURITY HEADERS
========================================================= */

header(
    "X-Content-Type-Options: nosniff"
);

header(
    "X-Frame-Options: SAMEORIGIN"
);

header(
    "Referrer-Policy: same-origin"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   AUTHENTICATION
========================================================= */

$userID =
    isset(
        $_SESSION["user_id"]
    )
        ? (int) $_SESSION["user_id"]
        : 0;


if ($userID <= 0) {

    header(
        "Location: /spis/login.php"
    );

    exit();
}


/* =========================================================
   LENDING ACCESS
========================================================= */

if (
    (int) (
        $_SESSION[
            "lending_access"
        ]
        ?? 0
    ) !== 1
) {

    header(
        "Location: /spis/dashboard.php?access=denied"
    );

    exit();
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (
    empty(
        $_SESSION[
            "csrf_token"
        ]
    )
    ||
    !is_string(
        $_SESSION[
            "csrf_token"
        ]
    )
) {

    $_SESSION[
        "csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );
}


$csrfToken =
    $_SESSION[
        "csrf_token"
    ];


/* =========================================================
   DATABASE
========================================================= */

require_once
    "../../../db.php";


if (
    !isset($conn)
    ||
    !($conn instanceof mysqli)
) {

    http_response_code(
        500
    );

    exit(
        "Database connection is unavailable."
    );
}


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   HELPERS
========================================================= */

function loanProductEscape(
    $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES |
        ENT_SUBSTITUTE,
        "UTF-8"
    );
}


function loanProductMoney(
    $value
): string {

    return
        "₱" .
        number_format(
            (float) $value,
            2
        );
}


function loanProductImagePath(
    $value
): string {

    $image =
        str_replace(
            "\\",
            "/",
            trim(
                (string) $value
            )
        );


    if (
        $image === ""
        ||
        !str_starts_with(
            $image,
            "assets/lending/product_loan_products/"
        )
        ||
        basename(
            $image
        ) === ""
    ) {

        return "";
    }


    return $image;
}


/* =========================================================
   LOAD ACTIVE PRODUCT LIST PRODUCTS
========================================================= */

$productStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT
            product.loan_product_id,
            product.product_name,
            product.loan_price,
            product.image,
            product.created_at,
            product.updated_at,

            COALESCE(
                (
                    SELECT
                        SUM(item.quantity)

                    FROM product_loan_items AS item

                    INNER JOIN product_loans AS loan
                        ON loan.product_loan_id =
                           item.product_loan_id

                    WHERE
                        loan.status = 'Pending'
                        AND loan.is_archived = 0
                        AND LOWER(
                            TRIM(
                                item.product_name
                            )
                        ) =
                        LOWER(
                            TRIM(
                                product.product_name
                            )
                        )
                ),
                0
            ) AS reserved_quantity

        FROM product_loan_products AS product

        WHERE product.archived = 0

        ORDER BY
            product.product_name ASC,
            product.loan_product_id ASC
        "
    );


if (!$productStatement) {

    http_response_code(
        500
    );

    exit(
        "Unable to load Product Loan products."
    );
}


mysqli_stmt_execute(
    $productStatement
);


$productResult =
    mysqli_stmt_get_result(
        $productStatement
    );


$products = [];


if ($productResult) {

    while (
        $product =
            mysqli_fetch_assoc(
                $productResult
            )
    ) {

        $products[] =
            $product;
    }
}


mysqli_stmt_close(
    $productStatement
);


/* =========================================================
   SIDEBAR
========================================================= */

require_once
    "../../../includes/sidebar.php";

?>


<!-- =========================================================
     PRODUCT LIST PAGE DATA
========================================================= -->

<div
id="loanProductListPageData"
data-success="<?php
echo loanProductEscape(
    $_GET[
        "success"
    ]
    ?? ""
);
?>"
data-error="<?php
echo loanProductEscape(
    $_GET[
        "error"
    ]
    ?? ""
);
?>"
hidden>
</div>


<!-- =========================================================
     STYLES
========================================================= -->

<link
rel="stylesheet"
href="product_list.css?v=<?php echo time(); ?>">


<!-- =========================================================
     PAGE HEADER
========================================================= -->

<div class="loan-product-list-header">


    <!-- =====================================================
         PAGE TITLE
    ====================================================== -->

    <div class="loan-product-list-title">

        <h2>
            PL - PRODUCT LIST
        </h2>

    </div>


    <!-- =====================================================
         HEADER CONTROLS
    ====================================================== -->

    <div class="loan-product-list-header-actions">


        <!-- BACK -->

        <a
        href="../index.php"
        class="loan-product-list-back-btn"
        title="Back to Product Loan"
        aria-label="Back to Product Loan">

            <i class="bi bi-arrow-left"></i>

        </a>


        <!-- SEARCH -->

        <div class="loan-product-list-search">

            <i class="bi bi-search"></i>


            <input
            type="text"
            id="loanProductSearchInput"
            placeholder="Search product..."
            autocomplete="off"
            aria-label="Search Product Loan product">

        </div>


        <!-- ADD PRODUCT -->

        <button
        type="button"
        class="loan-product-list-add-btn"
        id="openLoanProductAddBtn"
        data-bs-toggle="modal"
        data-bs-target="#addLoanProductModal">

            <i class="bi bi-plus-lg"></i>


            <span>
                ADD PRODUCT
            </span>

        </button>

    </div>

</div>


<!-- =========================================================
     PAGE DIVIDER
========================================================= -->

<hr class="loan-product-list-page-line">


<!-- =========================================================
     PRODUCT GRID
========================================================= -->

<div
class="loan-product-list-body"
id="loanProductListBody">


    <?php

    if (
        !empty(
            $products
        )
    ) {

    ?>


        <div
        class="loan-product-list-grid"
        id="loanProductGrid">


            <?php

            foreach (
                $products
                as
                $product
            ) {


                /* =============================================
                   SAFE PRODUCT DATA
                ============================================= */

                $productID =
                    (int) (
                        $product[
                            "loan_product_id"
                        ]
                        ?? 0
                    );


                $productName =
                    trim(
                        (string) (
                            $product[
                                "product_name"
                            ]
                            ?? ""
                        )
                    );


                if (
                    $productName === ""
                ) {

                    $productName =
                        "Unnamed Product";
                }


                $loanPrice =
                    round(
                        (float) (
                            $product[
                                "loan_price"
                            ]
                            ?? 0
                        ),
                        2
                    );


                $reservedQuantity =
                max(
                    0,
                    (int) (
                        $product[
                            "reserved_quantity"
                        ]
                        ?? 0
                    )
                );


                $imagePath =
                    loanProductImagePath(
                        $product[
                            "image"
                        ]
                        ?? ""
                    );

            ?>


                <!-- =================================================
                     PRODUCT CARD
                ================================================== -->

                <article
                class="loan-product-list-card"
                data-product-name="<?php
                echo loanProductEscape(
                    strtolower(
                        $productName
                    )
                );
                ?>"
                data-product-id="<?php
                echo $productID;
                ?>">


                    <!-- =============================================
                         CARD ACTIONS
                    ============================================== -->

                    <div class="loan-product-card-actions">


                        <!-- EDIT -->

                        <button
                        type="button"
                        class="
                            loan-product-card-action
                            loan-product-edit-btn
                        "
                        data-product-id="<?php
                        echo $productID;
                        ?>"
                        data-bs-toggle="modal"
                        data-bs-target="#editLoanProductModal<?php
                        echo $productID;
                        ?>"
                        title="Edit Product"
                        aria-label="Edit <?php
                        echo loanProductEscape(
                            $productName
                        );
                        ?>">

                            <i class="bi bi-pencil-fill"></i>

                        </button>


                        <!-- ARCHIVE -->

                        <form
                        method="POST"
                        action="archive_product.php"
                        class="loan-product-archive-form"
                        data-product-name="<?php
                        echo loanProductEscape(
                            $productName
                        );
                        ?>">


                            <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php
                            echo loanProductEscape(
                                $csrfToken
                            );
                            ?>">


                            <input
                            type="hidden"
                            name="loan_product_id"
                            value="<?php
                            echo $productID;
                            ?>">


                            <button
                            type="submit"
                            class="
                                loan-product-card-action
                                loan-product-archive-btn
                            "
                            title="Archive Product"
                            aria-label="Archive <?php
                            echo loanProductEscape(
                                $productName
                            );
                            ?>">

                                <i class="bi bi-archive-fill"></i>

                            </button>

                        </form>

                    </div>


                    <!-- =============================================
                         PRODUCT IMAGE
                    ============================================== -->

                    <div class="loan-product-inventory-image-area">

                        <div class="loan-product-inventory-image-frame">


                            <?php

                            if (
                                $imagePath !== ""
                            ) {

                            ?>


                                <img
                                src="/spis/<?php
                                echo loanProductEscape(
                                    $imagePath
                                );
                                ?>"
                                alt="<?php
                                echo loanProductEscape(
                                    $productName
                                );
                                ?>"
                                loading="lazy">


                            <?php

                            }
                            else {

                            ?>


                                <div class="loan-product-inventory-no-image">

                                    <i class="bi bi-box-seam"></i>


                                    <span>
                                        NO IMAGE
                                    </span>

                                </div>


                            <?php

                            }

                            ?>


                        </div>

                    </div>


                    <!-- =============================================
                         PRODUCT NAME
                    ============================================== -->

                    <div class="loan-product-inventory-name-area">

                        <h3>

                            <?php
                            echo loanProductEscape(
                                $productName
                            );
                            ?>

                        </h3>

                    </div>


                    <!-- =============================================
                         PRODUCT DETAILS
                    ============================================== -->

                    <div class="loan-product-inventory-details">


                        <!-- PRICE -->

                        <div class="loan-product-inventory-detail-row">

                            <span>
                                PRICE
                            </span>


                            <strong>

                                <?php
                                echo loanProductMoney(
                                    $loanPrice
                                );
                                ?>

                            </strong>

                        </div>

                        <?php

                        if (
                            $reservedQuantity > 0
                        ) {

                        ?>


                            <div class="loan-product-reservation-box">

                                <div class="loan-product-reservation-row">

                                    <span>

                                        <i class="bi bi-bookmark-check-fill"></i>

                                        PENDING RESERVED

                                    </span>


                                    <strong>

                                        <?php
                                        echo number_format(
                                            $reservedQuantity
                                        );
                                        ?>

                                    </strong>

                                </div>

                            </div>


                        <?php

                        }

                        ?>

                    </div>

                </article>


            <?php

            }

            ?>


        </div>


    <?php

    }
    else {

    ?>


        <!-- =================================================
             INITIAL EMPTY STATE
        ================================================== -->

        <div
        class="loan-product-list-empty"
        id="loanProductInitialEmpty">

            <i class="bi bi-box-seam"></i>


            <h3>
                No Products Yet
            </h3>


            <p>
                Add products that can be used for Product Loan applications.
            </p>

        </div>


    <?php

    }

    ?>


    <!-- =====================================================
         SEARCH EMPTY STATE
    ====================================================== -->

    <div
    id="loanProductSearchEmpty"
    class="loan-product-list-empty"
    hidden>

        <i class="bi bi-search"></i>


        <h3>
            No Matching Product
        </h3>


        <p>
            No Product Loan product matches your search.
        </p>

    </div>

</div>


<?php

/* =========================================================
   ADD PRODUCT MODAL
========================================================= */

require_once
    "add_product_modal.php";


/* =========================================================
   EDIT PRODUCT MODALS
========================================================= */

$editModalFile =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    "edit_product_modal.php";


if (
    is_file(
        $editModalFile
    )
) {

    require_once
        $editModalFile;
}

?>


<!-- =========================================================
     LOCAL / OFFLINE SCRIPTS
========================================================= -->

<script src="/spis/assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="/spis/js/sidebar.js"></script>

<script
src="product_list.js?v=<?php echo time(); ?>">
</script>