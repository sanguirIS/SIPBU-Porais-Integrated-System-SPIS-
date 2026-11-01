<?php

/* =========================================================
   SPIS - INVENTORY
========================================================= */

if (
    session_status()
    !==
    PHP_SESSION_ACTIVE
) {

    session_start();

}


/* =========================================================
   ACCESS
========================================================= */

require_once "../includes/check_access.php";

checkAccess(
    "inventory_access"
);


/* =========================================================
   TIMEZONE
========================================================= */

date_default_timezone_set(
    "Asia/Manila"
);


/* =========================================================
   DATABASE
========================================================= */

require_once "../db.php";


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   INVENTORY CSRF TOKEN

   Generate before any page output.
========================================================= */

if (
    empty(
        $_SESSION[
            "inventory_csrf_token"
        ]
    )
) {

    $_SESSION[
        "inventory_csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );

}


/* =========================================================
   LOAD ACTIVE PRODUCTS
========================================================= */

$sql = "
    SELECT
        product_id,
        product_name,
        acquired_price,
        markup_percent,
        selling_price,
        quantity,
        image

    FROM inventory

    WHERE archived = 0

    ORDER BY product_id ASC
";


$result =
    mysqli_query(
        $conn,
        $sql
    );


/* =========================================================
   PAGE COMPONENTS
========================================================= */

include "../includes/sidebar.php";

include "add_product_modal.php";

?>


<!-- =========================================================
     INVENTORY HEADER
========================================================= -->

<div class="inventory-header">


    <h2>
        INVENTORY
    </h2>


    <div class="top-controls">


        <!-- SEARCH -->

        <div class="search-box">

            <i class="bi bi-search"></i>

            <input
                type="text"
                id="searchInput"
                placeholder="Search product..."
                autocomplete="off"
                aria-label="Search inventory product">

        </div>


        <!-- ADD PRODUCT -->

        <button
            type="button"
            class="btn-add"
            data-bs-toggle="modal"
            data-bs-target="#addProductModal"
            aria-label="Add product"
            title="Add Product">

            <i class="bi bi-plus-lg"></i>

        </button>


    </div>

</div>


<hr>


<!-- =========================================================
     INVENTORY BODY
========================================================= -->

<div class="inventory-body">


    <?php

    if (
        $result
        &&
        mysqli_num_rows(
            $result
        ) > 0
    ) {

    ?>


        <div class="row">


            <?php

            while (
                $row =
                    mysqli_fetch_assoc(
                        $result
                    )
            ) {


                /* =============================================
                   SAFE PRODUCT VALUES
                ============================================= */

                $productID =
                    (int) (
                        $row[
                            "product_id"
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


                $sellingPrice =
                    round(
                        (float) (
                            $row[
                                "selling_price"
                            ]
                            ??
                            0
                        ),
                        2
                    );


                $quantity =
                    (int) (
                        $row[
                            "quantity"
                        ]
                        ??
                        0
                    );


                $image =
                    trim(
                        (string) (
                            $row[
                                "image"
                            ]
                            ??
                            ""
                        )
                    );


                /*
                 * Product images are expected to live only
                 * inside assets/products/.
                 *
                 * If an unexpected path somehow reaches the
                 * database, do not render it.
                 */

                $normalizedImage =
                    str_replace(
                        "\\",
                        "/",
                        $image
                    );


                $hasValidImagePath =
                    $normalizedImage !== ""
                    &&
                    str_starts_with(
                        $normalizedImage,
                        "assets/products/"
                    )
                    &&
                    basename(
                        $normalizedImage
                    ) !== "";


            ?>


                <div
                    class="
                        col-xl-4
                        col-lg-4
                        col-md-6
                        mb-4
                    ">

                    <div
                        class="
                            product-card
                            searchable-card
                        "
                        id="inventory-product-<?= $productID; ?>"
                        data-product-name="<?=

                            htmlspecialchars(
                                strtolower(
                                    $productName
                                ),
                                ENT_QUOTES,
                                "UTF-8"
                            );
                        ?>">


                        <!-- =================================
                             CARD ACTIONS
                        ================================== -->

                        <div class="card-top">


                            <!-- EDIT PRODUCT -->

                            <div
                                class="edit-btn"
                                role="button"
                                tabindex="0"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal<?= $productID; ?>"
                                aria-label="Edit <?= htmlspecialchars(
                                    $productName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>"
                                title="Edit Product">

                                <i class="bi bi-pencil-fill"></i>

                            </div>


                            <!-- ARCHIVE PRODUCT -->

                            <form
                                method="POST"
                                action="archive_product.php"
                                class="inventory-archive-form"
                                data-product-name="<?= htmlspecialchars(
                                    $productName,
                                    ENT_QUOTES,
                                    "UTF-8"
                                ); ?>">


                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(
                                        $_SESSION["inventory_csrf_token"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>">


                                <input
                                    type="hidden"
                                    name="product_id"
                                    value="<?= $productID; ?>">


                                <button
                                    type="submit"
                                    class="
                                        inventory-card-archive-btn
                                        archiveProduct
                                    "
                                    aria-label="Archive <?= htmlspecialchars(
                                        $productName,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                    title="Archive Product">

                                    <i class="bi bi-archive-fill"></i>

                                </button>


                            </form>


                        </div>


                        <!-- =================================
                             PRODUCT IMAGE
                        ================================== -->

                        <div class="image-container">


                            <?php

                            if (
                                $hasValidImagePath
                            ) {

                            ?>


                                <img
                                    src="../<?= htmlspecialchars(
                                        $normalizedImage,
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


                                <div
                                    style="
                                        display:flex;
                                        flex-direction:column;
                                        justify-content:center;
                                        align-items:center;
                                        gap:8px;
                                        color:#6F8347;
                                        text-align:center;
                                    ">

                                    <i
                                        class="bi bi-image"
                                        style="
                                            font-size:38px;
                                        ">
                                    </i>


                                    <span
                                        style="
                                            font-size:12px;
                                            font-weight:700;
                                        ">

                                        NO IMAGE

                                    </span>

                                </div>


                            <?php

                            }

                            ?>


                        </div>


                        <!-- =================================
                             PRODUCT NAME
                        ================================== -->

                        <h4 class="product-search-name">

                            <?= htmlspecialchars(
                                $productName,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>

                        </h4>


                        <!-- =================================
                             SELLING PRICE
                        ================================== -->

                        <div class="details">


                            <span>
                                Price
                            </span>


                            <span>

                                ₱<?= number_format(
                                    $sellingPrice,
                                    2
                                ); ?>

                            </span>


                        </div>


                        <!-- =================================
                             QUANTITY
                        ================================== -->

                        <div class="details">


                            <span>
                                Quantity
                            </span>

                            <span class="<?=
                                $quantity <= 0
                                    ? "stock-indicator out-stock"
                                    : ($quantity <= 5
                                        ? "stock-indicator low-stock"
                                        : "")
                            ?>">

                                <?php

                                if ($quantity <= 0) {
                                    echo "Out of Stock";
                                } elseif ($quantity <= 5) {
                                    echo "Low on Stock (" . number_format($quantity) . " left)";
                                } else {
                                    echo number_format($quantity);
                                }

                                ?>

                            </span>

                        </div>


                    </div>


                </div>


                <?php

                /*
                 * Edit modal uses the same $row values and
                 * internal product_id for the correct record.
                 */

                include "edit_product_modal.php";

                ?>


            <?php

            }

            ?>


        </div>


    <?php

    }
    else {

    ?>


        <!-- =================================================
             EMPTY STATE
        ================================================== -->

        <div class="inventory-empty">


            <i class="bi bi-box-seam"></i>


            <h3>
                No Products Yet
            </h3>


            <p>
                Add a product to start building the inventory.
            </p>


        </div>


    <?php

    }

    ?>


</div>


<?php

/* =========================================================
   ERROR ALERTS
========================================================= */

$errorMessages = [

    /* REQUEST / SECURITY */

    "invalid_request" => [
        "Invalid Request",
        "The requested Inventory action is not allowed."
    ],

    "invalid_security_token" => [
        "Session Expired",
        "Please try the action again from the Inventory page."
    ],


    /* PRODUCT */

    "invalid_product" => [
        "Invalid Product",
        "The selected product record is invalid or no longer available."
    ],

    "product_name_required" => [
        "Product Name Required",
        "Please enter a product name."
    ],

    "product_name_too_long" => [
        "Product Name Too Long",
        "Product Name must not exceed 150 characters."
    ],

    "invalid_product_name" => [
        "Invalid Product Name",
        "Please enter a valid product name."
    ],

    "duplicate_product_name" => [
        "Duplicate Product",
        "A product with this name already exists in Inventory or Archive. Please use a different name or restore the archived product."
    ],


    /* PRICE / QUANTITY */

    "invalid_acquired_price" => [
        "Invalid Acquired Price",
        "Acquired Price must be greater than 0 and contain no more than two decimal places."
    ],

    "invalid_markup" => [
        "Invalid Mark Up",
        "Mark Up must be zero or greater and contain no more than two decimal places."
    ],

    "invalid_quantity" => [
        "Invalid Quantity",
        "Please enter a valid whole-number product quantity."
    ],

    "invalid_selling_price" => [
        "Invalid Selling Price",
        "The Selling Price could not be calculated from the entered values."
    ],


    /* LEGACY ERROR KEYS */

    "price" => [
        "Invalid Acquired Price",
        "Acquired Price must be greater than 0."
    ],

    "markup" => [
        "Invalid Mark Up",
        "Mark Up cannot be negative."
    ],

    "quantity" => [
        "Invalid Quantity",
        "Please enter a valid product quantity."
    ],


    /* IMAGE */

    "image_required" => [
        "Product Image Required",
        "Please select a product image."
    ],

    "image_too_large" => [
        "Image Too Large",
        "The product image must not exceed 20 MB."
    ],

    "invalid_image_upload" => [
        "Invalid Image Upload",
        "The selected image could not be verified as a valid upload."
    ],

    "invalid_image" => [
        "Invalid Image",
        "Please select a valid JPG, PNG, or WEBP image."
    ],

    "invalid_image_type" => [
        "Unsupported Image",
        "Only JPG, PNG, and WEBP images are allowed."
    ],

    "image_dimensions_too_large" => [
        "Image Resolution Too Large",
        "Please use a product image with a lower resolution."
    ],

    "image_validation_unavailable" => [
        "Image Validation Unavailable",
        "The system could not validate the selected image."
    ],

    "image_processing_unavailable" => [
        "Image Processing Unavailable",
        "The system image processor is currently unavailable."
    ],

    "image_processing_failed" => [
        "Image Processing Failed",
        "The product image could not be prepared for storage. Please try another image."
    ],

    "image_upload_failed" => [
        "Upload Failed",
        "The selected product image could not be uploaded."
    ],

    "image_directory_error" => [
        "Storage Error",
        "The product image folder is currently unavailable."
    ],


    /* DATABASE ACTIONS */

    "save_failed" => [
        "Unable to Add Product",
        "The product could not be saved. No incomplete record was kept."
    ],

    "update_failed" => [
        "Unable to Update Product",
        "The product changes could not be saved."
    ],

    "archive_failed" => [
        "Unable to Archive Product",
        "The product could not be moved to Archive."
    ]

];


$errorKey =
    isset(
        $_GET[
            "error"
        ]
    )
    ?
    (string) $_GET[
        "error"
    ]
    :
    "";


$errorData =
    $errorMessages[
        $errorKey
    ]
    ??
    null;


if (
    is_array(
        $errorData
    )
) {

    $errorTitle =
        $errorData[0]
        ??
        "Inventory Error";


    $errorText =
        $errorData[1]
        ??
        "The requested action could not be completed.";

?>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function() {

        Swal.fire({

            icon:
                "warning",

            title:
                <?= json_encode(
                    $errorTitle,
                    JSON_UNESCAPED_UNICODE
                    |
                    JSON_UNESCAPED_SLASHES
                ); ?>,

            text:
                <?= json_encode(
                    $errorText,
                    JSON_UNESCAPED_UNICODE
                    |
                    JSON_UNESCAPED_SLASHES
                ); ?>,

            confirmButtonText:
                "OK"

        })
        .then(
            function() {

                if (
                    window.history
                    &&
                    window.history.replaceState
                ) {

                    window.history.replaceState(
                        {},
                        document.title,
                        window.location.pathname
                    );

                }

            }
        );

    }
);

</script>


<?php

}


/* =========================================================
   SUCCESS ALERTS
========================================================= */

$successMessages = [

    "added" =>
        "Product Added Successfully!",

    "updated" =>
        "Product Updated Successfully!",

    "archived" =>
        "Product Archived Successfully!"

];


$successKey =
    isset(
        $_GET[
            "success"
        ]
    )
    ?
    (string) $_GET[
        "success"
    ]
    :
    "";


$successTitle =
    $successMessages[
        $successKey
    ]
    ??
    "";


if (
    $successTitle !== ""
) {

?>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function() {

        Swal.fire({

            icon:
                "success",

            title:
                <?= json_encode(
                    $successTitle,
                    JSON_UNESCAPED_UNICODE
                    |
                    JSON_UNESCAPED_SLASHES
                ); ?>,

            showConfirmButton:
                false,

            timer:
                1800,

            timerProgressBar:
                true

        })
        .then(
            function() {

                if (
                    window.history
                    &&
                    window.history.replaceState
                ) {

                    window.history.replaceState(
                        {},
                        document.title,
                        window.location.pathname
                    );

                }

            }
        );

    }
);

</script>


<?php

}

?>


<!-- =========================================================
     LOCAL / OFFLINE SCRIPTS
========================================================= -->

<script src="../assets/sweetalert/sweetalert2.min.js"></script>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="../js/sidebar.js"></script>

<script src="../js/inventory.js"></script>


</body>

</html>