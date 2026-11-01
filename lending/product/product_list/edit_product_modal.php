<?php

/* =========================================================
   PRODUCT LOAN - PRODUCT LIST - EDIT PRODUCT MODALS

   Included by product_list/index.php.
========================================================= */

if (
    !isset($products)
    ||
    !is_array($products)
    ||
    empty($products)
) {
    return;
}


foreach (
    $products
    as
    $product
) {

    /* =====================================================
       PRODUCT DATA
    ===================================================== */

    $productID =
        (int) (
            $product[
                "loan_product_id"
            ]
            ?? 0
        );


    if ($productID <= 0) {
        continue;
    }


    $productName =
        trim(
            (string) (
                $product[
                    "product_name"
                ]
                ?? ""
            )
        );


    if ($productName === "") {

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


    $imagePath =
        loanProductImagePath(
            $product[
                "image"
            ]
            ?? ""
        );

?>


<!-- =========================================================
     EDIT PRODUCT MODAL
========================================================= -->

<div
    class="modal fade loan-product-modal"
    id="editLoanProductModal<?php echo $productID; ?>"
    tabindex="-1"
    aria-labelledby="editLoanProductModalTitle<?php echo $productID; ?>"
    aria-hidden="true">

    <div
        class="
            modal-dialog
            modal-xl
            modal-dialog-centered
            loan-product-modal-dialog
        ">

        <div class="modal-content loan-product-modal-window">

            <form
                class="edit-loan-product-form"
                data-product-id="<?php echo $productID; ?>"
                action="update_product.php"
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                novalidate>


                <!-- =================================================
                     SECURITY / RECORD DATA
                ================================================== -->

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
                    value="<?php echo $productID; ?>">


                <input
                    type="hidden"
                    name="remove_image"
                    id="editLoanProductRemoveImage<?php echo $productID; ?>"
                    value="0">


                <!-- =================================================
                     MODAL HEADER
                ================================================== -->

                <div class="loan-product-modal-header">

                    <div class="loan-product-modal-title">

                        <span class="loan-product-modal-title-icon">

                            <i class="bi bi-pencil-square"></i>

                        </span>


                        <div>

                            <h1
                                id="editLoanProductModalTitle<?php echo $productID; ?>">

                                EDIT PRODUCT

                            </h1>


                            <p>

                                Update the product information used in Product Loan applications.

                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="loan-product-modal-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">

                        ×

                    </button>

                </div>


                <hr>


                <!-- =================================================
                     MODAL BODY
                ================================================== -->

                <div class="loan-product-modal-body">

                    <div class="loan-product-modal-main-grid">


                        <!-- =========================================
                             PRODUCT INFORMATION
                        ========================================== -->

                        <section class="loan-product-modal-section">

                            <div class="loan-product-section-heading">

                                <span class="loan-product-section-icon">

                                    <i class="bi bi-card-list"></i>

                                </span>


                                <div>

                                    <h2>

                                        PRODUCT INFORMATION

                                    </h2>


                                    <p>

                                        Edit the product details shown in the Product List.

                                    </p>

                                </div>

                            </div>


                            <!-- =====================================
                                 PRODUCT NAME
                            ====================================== -->

                            <div class="loan-product-field">

                                <label
                                    for="editLoanProductName<?php echo $productID; ?>">

                                    PRODUCT NAME

                                    <span class="loan-product-required-mark">
                                        *
                                    </span>

                                </label>


                                <div class="loan-product-input-wrapper">

                                    <i class="bi bi-box"></i>


                                    <input
                                        type="text"
                                        id="editLoanProductName<?php echo $productID; ?>"
                                        name="product_name"
                                        class="
                                            loan-product-input
                                            loan-product-input-with-icon
                                        "
                                        value="<?php
                                        echo loanProductEscape(
                                            $productName
                                        );
                                        ?>"
                                        maxlength="150"
                                        autocomplete="off"
                                        aria-required="true"
                                        required>

                                </div>


                                <small class="loan-product-field-hint">

                                    This name is shown on the Product Loan product tiles.

                                </small>

                            </div>


                            <!-- =====================================
                                 PRICE
                            ====================================== -->

                            <div class="loan-product-field">

                                <label
                                    for="editLoanProductPrice<?php echo $productID; ?>">

                                    PRICE

                                    <span class="loan-product-required-mark">
                                        *
                                    </span>

                                </label>


                                <div class="loan-product-money-wrapper">

                                    <span>
                                        ₱
                                    </span>


                                    <input
                                        type="number"
                                        id="editLoanProductPrice<?php echo $productID; ?>"
                                        name="loan_price"
                                        class="
                                            loan-product-input
                                            loan-product-money-input
                                        "
                                        value="<?php
                                        echo loanProductEscape(
                                            number_format(
                                                $loanPrice,
                                                2,
                                                ".",
                                                ""
                                            )
                                        );
                                        ?>"
                                        min="0.01"
                                        step="0.01"
                                        inputmode="decimal"
                                        aria-required="true"
                                        required>

                                </div>


                                <small class="loan-product-field-hint">

                                    This is the official unit price used for new Product Loan selections.

                                </small>

                            </div>

                        </section>


                        <!-- =========================================
                             PRODUCT IMAGE
                        ========================================== -->

                        <section
                            class="
                                loan-product-modal-section
                                loan-product-image-section
                            ">

                            <div class="loan-product-section-heading">

                                <span class="loan-product-section-icon">

                                    <i class="bi bi-image-fill"></i>

                                </span>


                                <div>

                                    <h2>

                                        PRODUCT IMAGE

                                    </h2>


                                    <p>

                                        Keep the current image or select a replacement.

                                    </p>

                                </div>

                            </div>


                            <!-- =====================================
                                 IMAGE INPUT
                            ====================================== -->

                            <input
                                type="file"
                                id="editLoanProductImage<?php echo $productID; ?>"
                                name="image"
                                class="edit-loan-product-image-input"
                                data-product-id="<?php echo $productID; ?>"
                                accept="
                                    .jpg,
                                    .jpeg,
                                    .png,
                                    .webp,
                                    image/jpeg,
                                    image/png,
                                    image/webp
                                "
                                aria-describedby="editLoanProductImageHelp<?php echo $productID; ?>"
                                hidden>


                            <!-- =====================================
                                 IMAGE UPLOAD / PREVIEW
                            ====================================== -->

                            <label
                                for="editLoanProductImage<?php echo $productID; ?>"
                                id="editLoanProductImageUploadBox<?php echo $productID; ?>"
                                class="
                                    loan-product-image-upload-box
                                    edit-loan-product-image-upload-box
                                "
                                data-product-id="<?php echo $productID; ?>">


                                <!-- NO IMAGE / SELECT IMAGE -->

                                <div
                                    id="editLoanProductImagePlaceholder<?php echo $productID; ?>"
                                    class="loan-product-image-placeholder"
                                    <?php
                                    if ($imagePath !== "") {
                                        echo 'hidden';
                                    }
                                    ?>>

                                    <span class="loan-product-image-placeholder-icon">

                                        <i class="bi bi-cloud-arrow-up-fill"></i>

                                    </span>


                                    <strong>

                                        SELECT PRODUCT IMAGE

                                    </strong>


                                    <span>

                                        Click to browse a replacement

                                    </span>

                                </div>


                                <!-- CURRENT / NEW IMAGE PREVIEW -->

                                <img
                                    id="editLoanProductPreviewImage<?php echo $productID; ?>"
                                    class="loan-product-preview-image"

                                    <?php
                                    if ($imagePath !== "") {
                                    ?>

                                        src="/spis/<?php
                                        echo loanProductEscape(
                                            $imagePath
                                        );
                                        ?>"

                                    <?php
                                    }
                                    ?>

                                    alt="<?php
                                    echo loanProductEscape(
                                        $productName
                                    );
                                    ?> image preview"

                                    <?php
                                    if ($imagePath === "") {
                                        echo 'hidden';
                                    }
                                    ?>>

                            </label>


                            <!-- =====================================
                                 IMAGE DETAILS
                            ====================================== -->

                            <div class="loan-product-image-meta">

                                <small
                                    id="editLoanProductImageHelp<?php echo $productID; ?>">

                                    JPG, PNG, or WEBP · Maximum 10 MB

                                </small>


                                <button
                                    type="button"
                                    id="removeEditLoanProductImageBtn<?php echo $productID; ?>"
                                    class="
                                        loan-product-remove-image-btn
                                        edit-loan-product-remove-image-btn
                                    "
                                    data-product-id="<?php echo $productID; ?>"

                                    <?php
                                    if ($imagePath === "") {
                                        echo 'hidden';
                                    }
                                    ?>>

                                    <i class="bi bi-trash3-fill"></i>

                                    REMOVE IMAGE

                                </button>

                            </div>

                        </section>

                    </div>

                </div>


                <!-- =================================================
                     MODAL FOOTER
                ================================================== -->

                <div class="loan-product-modal-footer">

                    <button
                        type="button"
                        class="loan-product-modal-cancel"
                        data-bs-dismiss="modal">

                        CANCEL

                    </button>


                    <button
                        type="submit"
                        class="
                            loan-product-modal-save
                            edit-loan-product-save-btn
                        "
                        data-product-id="<?php echo $productID; ?>">

                        <i class="bi bi-check-circle-fill"></i>


                        <strong>

                            SAVE CHANGES

                        </strong>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<?php

}

?>