"use strict";

/* =========================================================
   PRODUCT LOAN - PRODUCT LIST
   Dedicated client-side behavior
========================================================= */

document.addEventListener("DOMContentLoaded", () => {

    const MAX_IMAGE_SIZE =
        10 * 1024 * 1024;


    const ALLOWED_IMAGE_TYPES = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];


    const ALLOWED_IMAGE_EXTENSIONS = [
        "jpg",
        "jpeg",
        "png",
        "webp"
    ];


    /* =====================================================
       PAGE ELEMENTS
    ===================================================== */

    const searchInput =
        document.getElementById(
            "loanProductSearchInput"
        );


    const visibleCount =
        document.getElementById(
            "loanProductVisibleCount"
        );


    const searchEmptyState =
        document.getElementById(
            "loanProductSearchEmpty"
        );


    const initialEmptyState =
        document.getElementById(
            "loanProductInitialEmpty"
        );


    const pageData =
        document.getElementById(
            "loanProductListPageData"
        );


    /* =====================================================
       ADD MODAL ELEMENTS
    ===================================================== */

    const addModal =
        document.getElementById(
            "addLoanProductModal"
        );


    const addForm =
        document.getElementById(
            "addLoanProductForm"
        );


    const addImageInput =
        document.getElementById(
            "loanProductImage"
        );


    const addImagePlaceholder =
        document.getElementById(
            "loanProductImagePlaceholder"
        );


    const addPreviewImage =
        document.getElementById(
            "loanProductPreviewImage"
        );


    const addRemoveImageButton =
        document.getElementById(
            "removeLoanProductImageBtn"
        );


    /* =====================================================
       INTERNAL STATE
    ===================================================== */

    let addPreviewUrl =
        null;


    const editPreviewUrls =
        new Map();


    const formsBeingSubmitted =
        new WeakSet();


    /* =====================================================
       ALERT HELPERS
    ===================================================== */

    async function showAlert(
        icon,
        title,
        text
    ) {

        if (
            window.Swal
            &&
            typeof window.Swal.fire ===
                "function"
        ) {

            return window.Swal.fire({
                icon,
                title,
                text,
                confirmButtonText:
                    "OK"
            });
        }


        window.alert(
            text ||
            title
        );


        return null;
    }


    async function showConfirmation(
        title,
        text,
        confirmText = "Confirm",
        icon = "question",
        confirmColor = null
    ) {

        if (
            window.Swal
            &&
            typeof window.Swal.fire ===
                "function"
        ) {

            const result =
                await window.Swal.fire({

                    icon,

                    title,

                    text,

                    showCancelButton:
                        true,

                    confirmButtonText:
                        confirmText,

                    cancelButtonText:
                        "Cancel",

                    reverseButtons:
                        true,

                    focusCancel:
                        true,

                    didOpen:
                        () => {

                            if (
                                !confirmColor
                            ) {

                                return;
                            }


                            const confirmButton =
                                window.Swal.getConfirmButton();


                            if (
                                !confirmButton
                            ) {

                                return;
                            }


                            confirmButton.style.setProperty(
                                "background-color",
                                confirmColor,
                                "important"
                            );


                            confirmButton.style.setProperty(
                                "border-color",
                                confirmColor,
                                "important"
                            );
                        }

                });


            return Boolean(
                result.isConfirmed
            );
        }


        return window.confirm(
            text ||
            title
        );
    }


    /* =====================================================
       RESPONSE CODE MESSAGES
    ===================================================== */

    function messageForCode(
        code,
        success
    ) {

        const successMessages = {

            added:
                "The product was added to the Product List successfully.",

            updated:
                "The Product List item was updated successfully.",

            archived:
                "The product was moved to Archive successfully."

        };


        const errorMessages = {

            invalid_request:
                "The request could not be processed.",

            session_expired:
                "Your session has expired. Please log in again.",

            access_denied:
                "You do not have permission to perform this action.",

            invalid_security_token:
                "The security token expired. Refresh the page and try again.",

            product_name_required:
                "Product name is required.",

            product_name_too_long:
                "Product name must not exceed 150 characters.",

            invalid_loan_price:
                "Enter a valid product price greater than zero.",

            duplicate_product:
                "Another active Product List item already uses this product name.",

            invalid_image_type:
                "Only JPG, PNG, or WEBP product images are allowed.",

            invalid_image_size:
                "The product image must not exceed 10 MB.",

            invalid_image_content:
                "The selected file is not a valid product image.",

            product_not_found:
                "The Product List item was not found or has already been archived.",

            save_failed:
                "Unable to save the Product List item.",

            update_failed:
                "Unable to update the Product List item.",

            archive_failed:
                "Unable to archive the Product List item."

        };


        if (
            success
        ) {

            return (
                successMessages[
                    code
                ]
                ||
                "The Product List was updated successfully."
            );
        }


        return (
            errorMessages[
                code
            ]
            ||
            "Something went wrong while processing the Product List request."
        );
    }


    /* =====================================================
       PAGE STATUS MESSAGE
    ===================================================== */

    async function showPageStatusMessage() {

        if (
            !pageData
        ) {

            return;
        }


        const successCode =
            String(
                pageData.dataset.success
                ||
                ""
            ).trim();


        const errorCode =
            String(
                pageData.dataset.error
                ||
                ""
            ).trim();


        if (
            !successCode
            &&
            !errorCode
        ) {

            return;
        }


        if (
            successCode
        ) {

            await showAlert(
                "success",
                "Success",
                messageForCode(
                    successCode,
                    true
                )
            );
        }
        else {

            await showAlert(
                "error",
                "Unable to complete request",
                messageForCode(
                    errorCode,
                    false
                )
            );
        }


        try {

            const cleanUrl =
                new URL(
                    window.location.href
                );


            cleanUrl.searchParams.delete(
                "success"
            );


            cleanUrl.searchParams.delete(
                "error"
            );


            window.history.replaceState(
                {},
                "",
                cleanUrl.toString()
            );

        }
        catch (
            error
        ) {

            /* Optional URL cleanup only. */

        }
    }


    /* =====================================================
       VALIDATION UI
    ===================================================== */

    function getFieldContainer(
        input
    ) {

        return input
            ?
            input.closest(
                ".loan-product-field"
            )
            :
            null;
    }


    function clearFieldError(
        input
    ) {

        if (
            !input
        ) {

            return;
        }


        input.classList.remove(
            "is-invalid"
        );


        input.removeAttribute(
            "aria-invalid"
        );


        const container =
            getFieldContainer(
                input
            );


        if (
            !container
        ) {

            return;
        }


        container
            .querySelectorAll(
                ".loan-product-field-error"
            )
            .forEach(
                (
                    element
                ) => {

                    element.remove();

                }
            );
    }


    function setFieldError(
        input,
        message
    ) {

        if (
            !input
        ) {

            return;
        }


        clearFieldError(
            input
        );


        input.classList.add(
            "is-invalid"
        );


        input.setAttribute(
            "aria-invalid",
            "true"
        );


        const container =
            getFieldContainer(
                input
            );


        if (
            !container
        ) {

            return;
        }


        const errorElement =
            document.createElement(
                "small"
            );


        errorElement.className =
            "loan-product-field-error";


        errorElement.textContent =
            message;


        container.appendChild(
            errorElement
        );
    }


    function clearFormErrors(
        form
    ) {

        if (
            !form
        ) {

            return;
        }


        form
            .querySelectorAll(
                ".loan-product-input.is-invalid"
            )
            .forEach(
                (
                    input
                ) => {

                    input.classList.remove(
                        "is-invalid"
                    );


                    input.removeAttribute(
                        "aria-invalid"
                    );

                }
            );


        form
            .querySelectorAll(
                ".loan-product-field-error"
            )
            .forEach(
                (
                    element
                ) => {

                    element.remove();

                }
            );
    }


    /* =====================================================
       VALUE VALIDATION
    ===================================================== */

    function normalizeName(
        value
    ) {

        return String(
            value ||
            ""
        )
            .replace(
                /\s+/g,
                " "
            )
            .trim();
    }


    function validMoneyString(
        value
    ) {

        const raw =
            String(
                value ||
                ""
            ).trim();


        if (
            !/^\d+(?:\.\d{1,2})?$/.test(
                raw
            )
        ) {

            return false;
        }


        const amount =
            Number(
                raw
            );


        return (
            Number.isFinite(
                amount
            )
            &&
            amount > 0
        );
    }

    function validateProductForm(
        form
    ) {

        clearFormErrors(
            form
        );


        const nameInput =
            form.querySelector(
                '[name="product_name"]'
            );


        const priceInput =
            form.querySelector(
                '[name="loan_price"]'
            );


        let firstInvalid =
            null;


        let valid =
            true;


        const productName =
            normalizeName(
                nameInput
                    ?
                    nameInput.value
                    :
                    ""
            );


        if (
            !productName
        ) {

            setFieldError(
                nameInput,
                "Product name is required."
            );


            firstInvalid =
                firstInvalid ||
                nameInput;


            valid =
                false;
        }
        else if (
            productName.length >
            150
        ) {

            setFieldError(
                nameInput,
                "Product name must not exceed 150 characters."
            );


            firstInvalid =
                firstInvalid ||
                nameInput;


            valid =
                false;
        }
        else if (
            nameInput
        ) {

            nameInput.value =
                productName;
        }


        if (
            !validMoneyString(
                priceInput
                    ?
                    priceInput.value
                    :
                    ""
            )
        ) {

            setFieldError(
                priceInput,
                "Enter a valid price greater than zero with up to two decimal places."
            );


            firstInvalid =
                firstInvalid ||
                priceInput;


            valid =
                false;
        }


        if (
            firstInvalid
            &&
            typeof firstInvalid.focus ===
                "function"
        ) {

            firstInvalid.focus();
        }


        return valid;
    }


    /* =====================================================
       PRODUCT DISPLAY HELPERS
    ===================================================== */

    function formatMoney(
        value
    ) {

        const amount =
            Number(
                value
            );


        if (
            !Number.isFinite(
                amount
            )
        ) {

            return "₱0.00";
        }


        return new Intl.NumberFormat(
            "en-PH",
            {
                style:
                    "currency",

                currency:
                    "PHP",

                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2
            }
        ).format(
            amount
        );
    }


    function getProductFormValues(
        form
    ) {

        const nameInput =
            form.querySelector(
                '[name="product_name"]'
            );


        const priceInput =
            form.querySelector(
                '[name="loan_price"]'
            );

        return {

            productName:
                normalizeName(
                    nameInput
                        ?
                        nameInput.value
                        :
                        ""
                ),

            loanPrice:
                Number(
                    priceInput
                        ?
                        priceInput.value
                        :
                        0
                )

        };
    }


    /* =====================================================
       IMAGE HELPERS
    ===================================================== */

    function extensionFromFileName(
        fileName
    ) {

        const name =
            String(
                fileName ||
                ""
            ).toLowerCase();


        const lastDot =
            name.lastIndexOf(
                "."
            );


        return lastDot >= 0
            ?
            name.slice(
                lastDot + 1
            )
            :
            "";
    }


    function validateImageFile(
        file
    ) {

        if (
            !file
        ) {

            return {
                valid:
                    false,

                message:
                    "No image was selected."
            };
        }


        if (
            file.size <= 0
            ||
            file.size >
                MAX_IMAGE_SIZE
        ) {

            return {
                valid:
                    false,

                message:
                    "The product image must not exceed 10 MB."
            };
        }


        const extension =
            extensionFromFileName(
                file.name
            );


        if (
            !ALLOWED_IMAGE_EXTENSIONS.includes(
                extension
            )
        ) {

            return {
                valid:
                    false,

                message:
                    "Only JPG, PNG, or WEBP product images are allowed."
            };
        }


        if (
            file.type
            &&
            !ALLOWED_IMAGE_TYPES.includes(
                file.type
            )
        ) {

            return {
                valid:
                    false,

                message:
                    "Only JPG, PNG, or WEBP product images are allowed."
            };
        }


        return {
            valid:
                true,

            message:
                ""
        };
    }


    function revokePreviewUrl(
        url
    ) {

        if (
            url
        ) {

            URL.revokeObjectURL(
                url
            );
        }
    }


    function showImagePreview(
        previewImage,
        placeholder,
        removeButton,
        file,
        oldUrl = null
    ) {

        if (
            !previewImage
            ||
            !placeholder
            ||
            !removeButton
            ||
            !file
        ) {

            return null;
        }


        revokePreviewUrl(
            oldUrl
        );


        const previewUrl =
            URL.createObjectURL(
                file
            );


        previewImage.src =
            previewUrl;


        previewImage.hidden =
            false;


        placeholder.hidden =
            true;


        removeButton.hidden =
            false;


        return previewUrl;
    }


    function showNoImage(
        previewImage,
        placeholder,
        removeButton
    ) {

        if (
            previewImage
        ) {

            previewImage.removeAttribute(
                "src"
            );


            previewImage.hidden =
                true;
        }


        if (
            placeholder
        ) {

            placeholder.hidden =
                false;
        }


        if (
            removeButton
        ) {

            removeButton.hidden =
                true;
        }
    }


    /* =====================================================
       PRODUCT SEARCH
    ===================================================== */

    function updateSearchResults() {

        const cards =
            Array.from(
                document.querySelectorAll(
                    ".loan-product-list-card"
                )
            );


        const query =
            String(
                searchInput
                    ?
                    searchInput.value
                    :
                    ""
            )
                .trim()
                .toLocaleLowerCase();


        let shown =
            0;


        cards.forEach(
            (
                card
            ) => {

                const searchableText = [

                    card.dataset.productName
                    ||
                    "",

                    card.dataset.productId
                    ||
                    "",

                    card.textContent
                    ||
                    ""

                ]
                    .join(
                        " "
                    )
                    .toLocaleLowerCase();


                const matches =
                    !query
                    ||
                    searchableText.includes(
                        query
                    );


                card.hidden =
                    !matches;


                if (
                    matches
                ) {

                    shown +=
                        1;
                }
            }
        );


        if (
            visibleCount
        ) {

            visibleCount.textContent =
                String(
                    shown
                );
        }


        if (
            searchEmptyState
        ) {

            searchEmptyState.hidden =
                !(
                    cards.length > 0
                    &&
                    shown === 0
                );
        }


        if (
            initialEmptyState
        ) {

            initialEmptyState.hidden =
                cards.length >
                0;
        }
    }


    if (
        searchInput
    ) {

        searchInput.addEventListener(
            "input",
            updateSearchResults
        );
    }


    updateSearchResults();


    /* =====================================================
       ADD PRODUCT IMAGE
    ===================================================== */

    if (
        addImageInput
    ) {

        addImageInput.addEventListener(
            "change",
            async () => {

                const file =
                    addImageInput.files
                    &&
                    addImageInput.files[
                        0
                    ]
                        ?
                        addImageInput.files[
                            0
                        ]
                        :
                        null;


                if (
                    !file
                ) {

                    revokePreviewUrl(
                        addPreviewUrl
                    );


                    addPreviewUrl =
                        null;


                    showNoImage(
                        addPreviewImage,
                        addImagePlaceholder,
                        addRemoveImageButton
                    );


                    return;
                }


                const result =
                    validateImageFile(
                        file
                    );


                if (
                    !result.valid
                ) {

                    addImageInput.value =
                        "";


                    revokePreviewUrl(
                        addPreviewUrl
                    );


                    addPreviewUrl =
                        null;


                    showNoImage(
                        addPreviewImage,
                        addImagePlaceholder,
                        addRemoveImageButton
                    );


                    await showAlert(
                        "warning",
                        "Invalid product image",
                        result.message
                    );


                    return;
                }


                addPreviewUrl =
                    showImagePreview(
                        addPreviewImage,
                        addImagePlaceholder,
                        addRemoveImageButton,
                        file,
                        addPreviewUrl
                    );
            }
        );
    }


    if (
        addRemoveImageButton
    ) {

        addRemoveImageButton.addEventListener(
            "click",
            () => {

                if (
                    addImageInput
                ) {

                    addImageInput.value =
                        "";
                }


                revokePreviewUrl(
                    addPreviewUrl
                );


                addPreviewUrl =
                    null;


                showNoImage(
                    addPreviewImage,
                    addImagePlaceholder,
                    addRemoveImageButton
                );
            }
        );
    }


    /* =====================================================
       EDIT PRODUCT IMAGE
    ===================================================== */

    document
        .querySelectorAll(
            ".edit-loan-product-form"
        )
        .forEach(
            (
                form
            ) => {

                const productId =
                    String(
                        form.dataset.productId
                        ||
                        ""
                    ).trim();


                const modal =
                    form.closest(
                        ".loan-product-modal"
                    );


                const imageInput =
                    form.querySelector(
                        ".edit-loan-product-image-input"
                    );


                const previewImage =
                    form.querySelector(
                        ".loan-product-preview-image"
                    );


                const placeholder =
                    form.querySelector(
                        ".loan-product-image-placeholder"
                    );


                const removeButton =
                    form.querySelector(
                        ".edit-loan-product-remove-image-btn"
                    );


                const removeImageInput =
                    form.querySelector(
                        '[name="remove_image"]'
                    );


                const initialImageSrc =
                    previewImage
                    &&
                    !previewImage.hidden
                        ?
                        previewImage.getAttribute(
                            "src"
                        )
                        ||
                        ""
                        :
                        "";


                form.dataset.initialImageSrc =
                    initialImageSrc;


                if (
                    imageInput
                ) {

                    imageInput.addEventListener(
                        "change",
                        async () => {

                            const file =
                                imageInput.files
                                &&
                                imageInput.files[
                                    0
                                ]
                                    ?
                                    imageInput.files[
                                        0
                                    ]
                                    :
                                    null;


                            if (
                                !file
                            ) {

                                return;
                            }


                            const result =
                                validateImageFile(
                                    file
                                );


                            if (
                                !result.valid
                            ) {

                                imageInput.value =
                                    "";


                                await showAlert(
                                    "warning",
                                    "Invalid product image",
                                    result.message
                                );


                                return;
                            }


                            const oldPreviewUrl =
                                editPreviewUrls.get(
                                    productId
                                )
                                ||
                                null;


                            const newPreviewUrl =
                                showImagePreview(
                                    previewImage,
                                    placeholder,
                                    removeButton,
                                    file,
                                    oldPreviewUrl
                                );


                            if (
                                newPreviewUrl
                            ) {

                                editPreviewUrls.set(
                                    productId,
                                    newPreviewUrl
                                );
                            }


                            if (
                                removeImageInput
                            ) {

                                removeImageInput.value =
                                    "0";
                            }
                        }
                    );
                }


                if (
                    removeButton
                ) {

                    removeButton.addEventListener(
                        "click",
                        () => {

                            if (
                                imageInput
                            ) {

                                imageInput.value =
                                    "";
                            }


                            const oldPreviewUrl =
                                editPreviewUrls.get(
                                    productId
                                )
                                ||
                                null;


                            revokePreviewUrl(
                                oldPreviewUrl
                            );


                            editPreviewUrls.delete(
                                productId
                            );


                            showNoImage(
                                previewImage,
                                placeholder,
                                removeButton
                            );


                            if (
                                removeImageInput
                            ) {

                                removeImageInput.value =
                                    "1";
                            }
                        }
                    );
                }


                if (
                    modal
                ) {

                    modal.addEventListener(
                        "hidden.bs.modal",
                        () => {

                            if (
                                formsBeingSubmitted.has(
                                    form
                                )
                            ) {

                                return;
                            }


                            form.reset();


                            clearFormErrors(
                                form
                            );


                            const oldPreviewUrl =
                                editPreviewUrls.get(
                                    productId
                                )
                                ||
                                null;


                            revokePreviewUrl(
                                oldPreviewUrl
                            );


                            editPreviewUrls.delete(
                                productId
                            );


                            const originalSrc =
                                String(
                                    form.dataset.initialImageSrc
                                    ||
                                    ""
                                );


                            if (
                                previewImage
                                &&
                                originalSrc
                            ) {

                                previewImage.src =
                                    originalSrc;


                                previewImage.hidden =
                                    false;


                                if (
                                    placeholder
                                ) {

                                    placeholder.hidden =
                                        true;
                                }


                                if (
                                    removeButton
                                ) {

                                    removeButton.hidden =
                                        false;
                                }
                            }
                            else {

                                showNoImage(
                                    previewImage,
                                    placeholder,
                                    removeButton
                                );
                            }


                            if (
                                removeImageInput
                            ) {

                                removeImageInput.value =
                                    "0";
                            }
                        }
                    );
                }
            }
        );


    /* =====================================================
       RESET ADD MODAL ON CLOSE
    ===================================================== */

    if (
        addModal
        &&
        addForm
    ) {

        addModal.addEventListener(
            "hidden.bs.modal",
            () => {

                if (
                    formsBeingSubmitted.has(
                        addForm
                    )
                ) {

                    return;
                }


                addForm.reset();


                clearFormErrors(
                    addForm
                );


                revokePreviewUrl(
                    addPreviewUrl
                );


                addPreviewUrl =
                    null;


                showNoImage(
                    addPreviewImage,
                    addImagePlaceholder,
                    addRemoveImageButton
                );
            }
        );
    }


    /* =====================================================
       CLEAR VALIDATION WHILE TYPING
    ===================================================== */

    document
        .querySelectorAll(
            ".loan-product-input"
        )
        .forEach(
            (
                input
            ) => {

                input.addEventListener(
                    "input",
                    () => {

                        clearFieldError(
                            input
                        );
                    }
                );
            }
        );


    /* =====================================================
       SUBMIT BUTTON STATE
    ===================================================== */

    function getSubmitButton(
        form
    ) {

        return form.querySelector(
            '[type="submit"]'
        );
    }


    function setFormSubmitting(
        form,
        submitting,
        busyText = "SAVING..."
    ) {

        const button =
            getSubmitButton(
                form
            );


        if (
            !button
        ) {

            return;
        }


        if (
            submitting
        ) {

            if (
                !button.dataset.originalHtml
            ) {

                button.dataset.originalHtml =
                    button.innerHTML;
            }


            button.disabled =
                true;


            button.setAttribute(
                "aria-busy",
                "true"
            );


            button.innerHTML =
                `
                <span
                    class="spinner-border spinner-border-sm"
                    aria-hidden="true">
                </span>

                <strong>
                    ${busyText}
                </strong>
                `;
        }
        else {

            button.disabled =
                false;


            button.removeAttribute(
                "aria-busy"
            );


            if (
                button.dataset.originalHtml
            ) {

                button.innerHTML =
                    button.dataset.originalHtml;
            }
        }
    }


    /* =====================================================
       AJAX FORM SUBMISSION
    ===================================================== */

    async function submitFormWithAjax(
        form
    ) {

        const response =
            await fetch(
                form.action,
                {
                    method:
                        "POST",

                    body:
                        new FormData(
                            form
                        ),

                    credentials:
                        "same-origin",

                    headers: {

                        "X-Requested-With":
                            "XMLHttpRequest",

                        "Accept":
                            "application/json"

                    }
                }
            );


        let payload =
            null;


        try {

            payload =
                await response.json();

        }
        catch (
            error
        ) {

            payload =
                null;
        }


        if (
            !payload
            ||
            typeof payload !==
                "object"
        ) {

            throw new Error(
                response.ok
                    ?
                    "The server returned an invalid response."
                    :
                    "The request could not be completed."
            );
        }


        if (
            !response.ok
            ||
            payload.success !==
                true
        ) {

            const error =
                new Error(
                    String(
                        payload.message
                        ||
                        "The request could not be completed."
                    )
                );


            error.code =
                String(
                    payload.code
                    ||
                    "request_failed"
                );


            throw error;
        }


        return payload;
    }


    /* =====================================================
       ADD / EDIT PRODUCT SUBMISSION
       CONFIRMATION REQUIRED BEFORE DATABASE CHANGE
    ===================================================== */

    document
        .querySelectorAll(
            "#addLoanProductForm, .edit-loan-product-form"
        )
        .forEach(
            (
                form
            ) => {

                form.addEventListener(
                    "submit",
                    async (
                        event
                    ) => {

                        event.preventDefault();


                        if (
                            formsBeingSubmitted.has(
                                form
                            )
                        ) {

                            return;
                        }


                        if (
                            !validateProductForm(
                                form
                            )
                        ) {

                            return;
                        }


                        const isAddForm =
                            form.id ===
                            "addLoanProductForm";


                        const values =
                            getProductFormValues(
                                form
                            );


                        let confirmed =
                            false;


                        if (
                            isAddForm
                        ) {

                            confirmed =
                                await showConfirmation(

                                    "Add this product?",

                                    `Add "${values.productName}" to the Product List with a price of ${formatMoney(values.loanPrice)}?`,

                                    "Add Product",

                                    "question"

                                );
                        }
                        else {

                            confirmed =
                                await showConfirmation(

                                    "Save product changes?",

                                    `Update "${values.productName}" with a price of ${formatMoney(values.loanPrice)}?`,

                                    "Save Changes",

                                    "question"

                                );
                        }


                        if (
                            !confirmed
                        ) {

                            return;
                        }


                        const busyText =
                            isAddForm
                                ?
                                "SAVING..."
                                :
                                "UPDATING...";


                        formsBeingSubmitted.add(
                            form
                        );


                        setFormSubmitting(
                            form,
                            true,
                            busyText
                        );


                        try {

                            const payload =
                                await submitFormWithAjax(
                                    form
                                );


                            await showAlert(
                                "success",
                                isAddForm
                                    ?
                                    "Product added"
                                    :
                                    "Product updated",
                                String(
                                    payload.message
                                    ||
                                    messageForCode(
                                        payload.code
                                        ||
                                        "updated",
                                        true
                                    )
                                )
                            );


                            window.location.reload();

                        }
                        catch (
                            error
                        ) {

                            formsBeingSubmitted.delete(
                                form
                            );


                            setFormSubmitting(
                                form,
                                false
                            );


                            await showAlert(
                                "error",
                                isAddForm
                                    ?
                                    "Unable to add product"
                                    :
                                    "Unable to update product",
                                error
                                &&
                                error.message
                                    ?
                                    error.message
                                    :
                                    "Unable to save the Product List item."
                            );
                        }
                    }
                );
            }
        );


    /* =====================================================
       ARCHIVE PRODUCT
       CONFIRMATION REQUIRED
    ===================================================== */

    document
        .querySelectorAll(
            ".loan-product-archive-form"
        )
        .forEach(
            (
                form
            ) => {

                form.addEventListener(
                    "submit",
                    async (
                        event
                    ) => {

                        event.preventDefault();


                        if (
                            formsBeingSubmitted.has(
                                form
                            )
                        ) {

                            return;
                        }


                        const productName =
                            String(
                                form.dataset.productName
                                ||
                                "this product"
                            ).trim();


                        const confirmed =
                            await showConfirmation(

                                "Archive product?",

                                `${productName} will be removed from the active Product List and can be retrieved later from the Archive module.`,

                                "Archive",

                                "warning",

                                "#d85c5c"

                            );


                        if (
                            !confirmed
                        ) {

                            return;
                        }


                        formsBeingSubmitted.add(
                            form
                        );


                        setFormSubmitting(
                            form,
                            true,
                            "ARCHIVING..."
                        );


                        try {

                            const payload =
                                await submitFormWithAjax(
                                    form
                                );


                            await showAlert(
                                "success",
                                "Product archived",
                                String(
                                    payload.message
                                    ||
                                    `${productName} was moved to Archive.`
                                )
                            );


                            window.location.reload();

                        }
                        catch (
                            error
                        ) {

                            formsBeingSubmitted.delete(
                                form
                            );


                            setFormSubmitting(
                                form,
                                false
                            );


                            await showAlert(
                                "error",
                                "Unable to archive product",
                                error
                                &&
                                error.message
                                    ?
                                    error.message
                                    :
                                    "Unable to archive the Product List item."
                            );
                        }
                    }
                );
            }
        );


    /* =====================================================
       STARTUP PAGE MESSAGE
    ===================================================== */

    showPageStatusMessage();


    /* =====================================================
       CLEAN OBJECT URLS BEFORE LEAVING PAGE
    ===================================================== */

    window.addEventListener(
        "beforeunload",
        () => {

            revokePreviewUrl(
                addPreviewUrl
            );


            editPreviewUrls.forEach(
                (
                    url
                ) => {

                    revokePreviewUrl(
                        url
                    );
                }
            );


            editPreviewUrls.clear();
        }
    );

});