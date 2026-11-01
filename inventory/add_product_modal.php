<!-- =========================================================
     ADD PRODUCT MODAL
========================================================= -->

<div
    class="modal fade"
    id="addProductModal"
    tabindex="-1"
    aria-labelledby="addProductModalTitle"
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

                <h1 id="addProductModalTitle">
                    ADD PRODUCT
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
                    id="addProductForm"
                    action="save_product.php"
                    method="POST"
                    enctype="multipart/form-data"
                    autocomplete="off">

                    <?php

                    if (
                        session_status()
                        !==
                        PHP_SESSION_ACTIVE
                    ) {

                        session_start();

                    }


                    if (
                        empty(
                            $_SESSION["inventory_csrf_token"]
                        )
                    ) {

                        $_SESSION["inventory_csrf_token"] =
                            bin2hex(
                                random_bytes(32)
                            );

                    }

                    ?>


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

                                <label for="addProductName">
                                    PRODUCT NAME
                                </label>

                                <input
                                    type="text"
                                    id="addProductName"
                                    name="product_name"
                                    class="form-control custom-input"
                                    maxlength="150"
                                    placeholder="Enter product name"
                                    autocomplete="off"
                                    required>

                            </div>


                            <!-- PRICE FIELDS -->

                            <div class="row g-3">


                                <div class="col-md-6">

                                    <label for="acquired_price">
                                        ACQUIRED PRICE
                                    </label>

                                    <input
                                        type="number"
                                        id="acquired_price"
                                        name="acquired_price"
                                        class="form-control custom-input"
                                        min="0.01"
                                        step="0.01"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                        required>

                                </div>


                                <div class="col-md-6">

                                    <label for="markup_percent">
                                        MARK UP %
                                    </label>

                                    <input
                                        type="number"
                                        id="markup_percent"
                                        name="markup_percent"
                                        class="form-control custom-input"
                                        min="0"
                                        step="0.01"
                                        inputmode="decimal"
                                        placeholder="0"
                                        required>

                                </div>


                            </div>


                            <!-- QUANTITY / SELLING PRICE -->

                            <div class="row g-3 mt-1">


                                <div class="col-md-6">

                                    <label for="quantity">
                                        PRODUCT QUANTITY
                                    </label>

                                    <input
                                        type="number"
                                        id="quantity"
                                        name="quantity"
                                        class="form-control custom-input"
                                        min="1"
                                        step="1"
                                        inputmode="numeric"
                                        placeholder="0"
                                        required>

                                </div>


                                <div class="col-md-6">

                                    <label for="selling_price">
                                        SELLING PRICE
                                    </label>

                                    <input
                                        type="text"
                                        id="selling_price"
                                        name="selling_price"
                                        class="form-control custom-input"
                                        placeholder="0.00"
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
                                    background:rgba(253,251,212,.08);
                                    border:1px solid rgba(253,251,212,.18);
                                    border-radius:12px;
                                    color:#FDFBD4;
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
                                for="imageInput"
                                style="
                                    display:block;
                                    text-align:center;
                                    margin-bottom:8px;
                                ">

                                PRODUCT IMAGE

                            </label>


                            <label
                                for="imageInput"
                                class="image-upload-box">


                                <span id="image-text">

                                    <i
                                        class="bi bi-image"
                                        style="
                                            display:block;
                                            margin-bottom:8px;
                                            font-size:34px;
                                        ">
                                    </i>

                                    UPLOAD IMAGE

                                    <small
                                        style="
                                            display:block;
                                            margin-top:6px;
                                            font-size:11px;
                                            font-weight:500;
                                            opacity:.75;
                                        ">

                                        JPG, PNG or WEBP

                                    </small>

                                </span>


                                <img
                                    id="preview-image"
                                    alt="Product image preview">

                            </label>


                            <input
                                type="file"
                                id="imageInput"
                                name="image"
                                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                hidden
                                onchange="previewImage(event)">


                        </div>


                    </div>


                    <!-- =========================================
                         SAVE
                    ========================================== -->

                    <div class="save-container">

                        <button
                            type="submit"
                            class="save-product-btn">

                            SAVE PRODUCT

                        </button>

                    </div>


                </form>


            </div>


        </div>

    </div>

</div>