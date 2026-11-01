<!-- =========================================================
     PRODUCT LOAN - ADD PRODUCT MODAL
========================================================= -->

<div
    class="modal fade loan-product-modal"
    id="addLoanProductModal"
    tabindex="-1"
    aria-labelledby="addLoanProductModalTitle"
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
                id="addLoanProductForm"
                action="save_product.php"
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                novalidate>

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php
                    echo htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        "UTF-8"
                    );
                    ?>">

                <!-- =================================================
                     HEADER
                ================================================== -->

                <div class="loan-product-modal-header">

                    <div class="loan-product-modal-title">

                        <span class="loan-product-modal-title-icon">

                            <i class="bi bi-box-seam-fill"></i>

                        </span>


                        <div>

                            <h1 id="addLoanProductModalTitle">

                                ADD PRODUCT

                            </h1>


                            <p>

                                Add a product that can be selected in Product Loan applications.

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
                     BODY
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

                                        Enter the product details used in Product Loan applications.

                                    </p>

                                </div>

                            </div>


                            <!-- =====================================
                                 PRODUCT NAME
                            ====================================== -->

                            <div class="loan-product-field">

                                <label for="loanProductName">

                                    PRODUCT NAME

                                    <span class="loan-product-required-mark">
                                        *
                                    </span>

                                </label>


                                <div class="loan-product-input-wrapper">

                                    <i class="bi bi-box"></i>


                                    <input
                                        type="text"
                                        id="loanProductName"
                                        name="product_name"
                                        class="
                                            loan-product-input
                                            loan-product-input-with-icon
                                        "
                                        maxlength="150"
                                        placeholder="Enter product name"
                                        autocomplete="off"
                                        aria-required="true"
                                        required>

                                </div>


                                <small class="loan-product-field-hint">

                                    This name will appear on the Product Loan product tiles.

                                </small>

                            </div>


                            <!-- =====================================
                                 PRICE
                            ====================================== -->

                            <div class="loan-product-field">

                                <label for="loanProductPrice">

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
                                        id="loanProductPrice"
                                        name="loan_price"
                                        class="
                                            loan-product-input
                                            loan-product-money-input
                                        "
                                        min="0.01"
                                        step="0.01"
                                        inputmode="decimal"
                                        placeholder="0.00"
                                        aria-required="true"
                                        required>

                                </div>


                                <small class="loan-product-field-hint">

                                    This is the official unit price used when the product is added to a Product Loan.

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

                                        Optional image shown in the Product List and Product Loan tiles.

                                    </p>

                                </div>

                            </div>


                            <!-- =====================================
                                 IMAGE INPUT
                            ====================================== -->

                            <input
                                type="file"
                                id="loanProductImage"
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
                                aria-describedby="loanProductImageHelp"
                                hidden>


                            <!-- =====================================
                                 IMAGE UPLOAD BOX
                            ====================================== -->

                            <label
                                for="loanProductImage"
                                id="loanProductImageUploadBox"
                                class="loan-product-image-upload-box">


                                <div
                                    id="loanProductImagePlaceholder"
                                    class="loan-product-image-placeholder">

                                    <span class="loan-product-image-placeholder-icon">

                                        <i class="bi bi-cloud-arrow-up-fill"></i>

                                    </span>


                                    <strong>

                                        SELECT PRODUCT IMAGE

                                    </strong>


                                    <span>

                                        Click to browse a file

                                    </span>

                                </div>


                                <img
                                    id="loanProductPreviewImage"
                                    class="loan-product-preview-image"
                                    alt="Product image preview"
                                    hidden>

                            </label>


                            <!-- =====================================
                                 IMAGE DETAILS
                            ====================================== -->

                            <div class="loan-product-image-meta">

                                <small id="loanProductImageHelp">

                                    JPG, PNG, or WEBP · Maximum 10 MB

                                </small>


                                <button
                                    type="button"
                                    id="removeLoanProductImageBtn"
                                    class="loan-product-remove-image-btn"
                                    hidden>

                                    <i class="bi bi-trash3-fill"></i>

                                    REMOVE IMAGE

                                </button>

                            </div>

                        </section>

                    </div>

                </div>


                <!-- =================================================
                     FOOTER
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
                        class="loan-product-modal-save"
                        id="saveLoanProductBtn">

                        <i class="bi bi-check-circle-fill"></i>


                        <strong>

                            SAVE PRODUCT

                        </strong>

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>