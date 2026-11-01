<?php

/* =========================================================
   EDIT PRODUCT MODAL
========================================================= */

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


$quantity =
    (int) (
        $row["quantity"]
        ??
        0
    );


$image =
    trim(
        (string) (
            $row["image"]
            ??
            ""
        )
    );


$sellingPrice =
    $acquiredPrice
    +
    (
        $acquiredPrice
        *
        $markupPercent
        /
        100
    );

?>


<!-- =========================================================
     EDIT PRODUCT MODAL
========================================================= -->

<div
    class="modal fade"
    id="editModal<?= $productID; ?>"
    tabindex="-1"
    aria-labelledby="editProductModalTitle<?= $productID; ?>"
    aria-hidden="true">

    <div
        class="
            modal-dialog
            modal-xl
            modal-dialog-centered
        ">

        <div class="modal-content add-product-window">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="window-header">

                <h1
                    id="editProductModalTitle<?= $productID; ?>">

                    EDIT PRODUCT

                </h1>


                <button
                    type="button"
                    class="close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                    ×

                </button>

            </div>


            <!-- =================================================
                 BODY
            ================================================== -->

            <div class="modal-body">


                <form
                    class="editProductForm"
                    method="POST"
                    action="update_product.php"
                    enctype="multipart/form-data"
                    autocomplete="off">


                    <!-- INTERNAL PRODUCT ID -->

                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= $productID; ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= htmlspecialchars(
                            $_SESSION["inventory_csrf_token"],
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>">    


                    <div class="row g-4">


                        <!-- =====================================
                             PRODUCT INFORMATION
                        ====================================== -->

                        <div class="col-lg-7">


                            <!-- PRODUCT NAME -->

                            <div class="mb-3">

                                <label
                                    for="editProductName<?= $productID; ?>">

                                    PRODUCT NAME

                                </label>


                                <input
                                    type="text"
                                    id="editProductName<?= $productID; ?>"
                                    name="product_name"
                                    class="form-control custom-input"
                                    value="<?= htmlspecialchars(
                                        $productName,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ); ?>"
                                    maxlength="150"
                                    autocomplete="off"
                                    required>

                            </div>


                            <!-- =================================
                                 PRICE FIELDS
                            ================================== -->

                            <div class="row g-3">


                                <!-- ACQUIRED PRICE -->

                                <div class="col-md-6">

                                    <label
                                        for="editAcquiredPrice<?= $productID; ?>">

                                        ACQUIRED PRICE

                                    </label>


                                    <input
                                        type="number"
                                        id="editAcquiredPrice<?= $productID; ?>"
                                        name="acquired_price"
                                        class="
                                            form-control
                                            custom-input
                                            acquired-price
                                        "
                                        value="<?= htmlspecialchars(
                                            number_format(
                                                $acquiredPrice,
                                                2,
                                                ".",
                                                ""
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        min="0.01"
                                        step="0.01"
                                        inputmode="decimal"
                                        oninput="computeEditPrice(this)"
                                        required>

                                </div>


                                <!-- MARK UP -->

                                <div class="col-md-6">

                                    <label
                                        for="editMarkupPercent<?= $productID; ?>">

                                        MARK UP %

                                    </label>


                                    <input
                                        type="number"
                                        id="editMarkupPercent<?= $productID; ?>"
                                        name="markup_percent"
                                        class="
                                            form-control
                                            custom-input
                                            markup-percent
                                        "
                                        value="<?= htmlspecialchars(
                                            number_format(
                                                $markupPercent,
                                                2,
                                                ".",
                                                ""
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        oninput="computeEditPrice(this)"
                                        required>

                                </div>


                            </div>


                            <!-- =================================
                                 QUANTITY / SELLING PRICE
                            ================================== -->

                            <div class="row g-3 mt-1">


                                <!-- QUANTITY -->

                                <div class="col-md-6">

                                    <label
                                        for="editQuantity<?= $productID; ?>">

                                        PRODUCT QUANTITY

                                    </label>


                                    <input
                                        type="number"
                                        id="editQuantity<?= $productID; ?>"
                                        name="quantity"
                                        class="form-control custom-input"
                                        value="<?= htmlspecialchars(
                                            (string) $quantity,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        min="0"
                                        step="1"
                                        inputmode="numeric"
                                        required>

                                </div>


                                <!-- SELLING PRICE -->

                                <div class="col-md-6">

                                    <label
                                        for="editSellingPrice<?= $productID; ?>">

                                        SELLING PRICE

                                    </label>


                                    <input
                                        type="text"
                                        id="editSellingPrice<?= $productID; ?>"
                                        class="
                                            form-control
                                            custom-input
                                            selling-price
                                        "
                                        value="<?= htmlspecialchars(
                                            number_format(
                                                $sellingPrice,
                                                2,
                                                ".",
                                                ""
                                            ),
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        readonly
                                        tabindex="-1"
                                        aria-readonly="true">

                                </div>


                            </div>


                            <!-- =================================
                                 INFORMATION NOTE
                            ================================== -->

                            <div
                                style="
                                    margin-top:18px;
                                    padding:12px 14px;

                                    color:#FDFBD4;

                                    background:
                                        rgba(
                                            253,
                                            251,
                                            212,
                                            .08
                                        );

                                    border:
                                        1px solid
                                        rgba(
                                            253,
                                            251,
                                            212,
                                            .18
                                        );

                                    border-radius:12px;

                                    font-size:12px;
                                    line-height:1.45;
                                ">

                                <i
                                    class="bi bi-info-circle"
                                    style="margin-right:5px;">
                                </i>

                                Selling Price is automatically calculated
                                from the Acquired Price and Mark Up.

                            </div>


                        </div>


                        <!-- =====================================
                             PRODUCT IMAGE
                        ====================================== -->

                        <div class="col-lg-5">


                            <label
                                for="imageInput<?= $productID; ?>"
                                style="
                                    display:block;
                                    text-align:center;
                                    margin-bottom:8px;
                                ">

                                PRODUCT IMAGE

                            </label>


                            <label
                                for="imageInput<?= $productID; ?>"
                                class="image-upload-box">


                                <?php

                                if (
                                    $image !== ""
                                ) {

                                ?>


                                    <span
                                        id="image-text<?= $productID; ?>"
                                        style="display:none;">

                                        <i
                                            class="bi bi-image"
                                            style="
                                                display:block;
                                                margin-bottom:8px;
                                                font-size:34px;
                                            ">
                                        </i>

                                        UPLOAD IMAGE

                                    </span>


                                    <img
                                        id="preview-image<?= $productID; ?>"
                                        src="../<?= htmlspecialchars(
                                            $image,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        alt="<?= htmlspecialchars(
                                            $productName,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ); ?>"
                                        style="
                                            display:block;
                                            width:100%;
                                            height:100%;
                                            padding:12px;
                                            object-fit:contain;
                                            border-radius:14px;
                                        ">


                                <?php

                                } else {

                                ?>


                                    <span
                                        id="image-text<?= $productID; ?>"
                                        style="
                                            width:100%;
                                            height:100%;
                                            padding:20px;

                                            display:flex;
                                            flex-direction:column;
                                            justify-content:center;
                                            align-items:center;

                                            gap:3px;

                                            text-align:center;
                                        ">

                                        <i
                                            class="bi bi-image"
                                            style="
                                                display:block;
                                                margin-bottom:7px;
                                                font-size:34px;
                                            ">
                                        </i>

                                        UPLOAD IMAGE


                                        <small
                                            style="
                                                display:block;
                                                margin-top:5px;

                                                font-size:11px;
                                                font-weight:500;

                                                opacity:.75;
                                            ">

                                            JPG, PNG or WEBP

                                        </small>

                                    </span>


                                    <img
                                        id="preview-image<?= $productID; ?>"
                                        alt="Product image preview"
                                        style="
                                            display:none;
                                            width:100%;
                                            height:100%;
                                            padding:12px;
                                            object-fit:contain;
                                            border-radius:14px;
                                        ">


                                <?php

                                }

                                ?>


                            </label>


                            <input
                                type="file"
                                id="imageInput<?= $productID; ?>"
                                name="image"
                                accept="
                                    .jpg,
                                    .jpeg,
                                    .png,
                                    .webp,
                                    image/jpeg,
                                    image/png,
                                    image/webp
                                "
                                hidden
                                onchange="previewEditImage(
                                    event,
                                    <?= $productID; ?>
                                )">


                            <div
                                style="
                                    margin-top:8px;

                                    color:
                                        rgba(
                                            253,
                                            251,
                                            212,
                                            .72
                                        );

                                    font-size:10px;
                                    text-align:center;
                                ">

                                Click the image to replace it.

                            </div>


                        </div>


                    </div>


                    <!-- =================================================
                         BOTTOM ACTIONS
                    ================================================== -->

                    <div class="bottom-buttons">


                        <!-- SAVE CHANGES -->

                        <button
                            type="submit"
                            class="save-product-btn">

                            SAVE CHANGES

                        </button>


                    </div>


                </form>


            </div>


        </div>

    </div>

</div>