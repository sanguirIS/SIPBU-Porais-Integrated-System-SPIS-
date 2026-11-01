/* =========================================================
   SPIS INVENTORY
========================================================= */

"use strict";


/* =========================================================
   HELPERS
========================================================= */

function inventoryNumber(value) {

    const number =
        Number.parseFloat(value);

    return Number.isFinite(number)
        ? number
        : 0;

}


function inventoryNativeSubmit(form) {

    HTMLFormElement
        .prototype
        .submit
        .call(form);

}


/* =========================================================
   ADD PRODUCT - SELLING PRICE
========================================================= */

function computeSellingPrice() {

    const acquiredInput =
        document.getElementById(
            "acquired_price"
        );

    const markupInput =
        document.getElementById(
            "markup_percent"
        );

    const sellingInput =
        document.getElementById(
            "selling_price"
        );


    if (
        !acquiredInput
        ||
        !markupInput
        ||
        !sellingInput
    ) {

        return;

    }


    const acquired =
        Math.max(
            0,
            inventoryNumber(
                acquiredInput.value
            )
        );


    const markup =
        Math.max(
            0,
            inventoryNumber(
                markupInput.value
            )
        );


    const sellingPrice =
        acquired
        +
        (
            acquired
            *
            markup
            /
            100
        );


    sellingInput.value =
        sellingPrice.toFixed(2);

}


/* =========================================================
   ADD PRODUCT - PRICE LISTENERS
========================================================= */

const acquiredPriceInput =
    document.getElementById(
        "acquired_price"
    );


const markupPercentInput =
    document.getElementById(
        "markup_percent"
    );


if (acquiredPriceInput) {

    acquiredPriceInput
        .addEventListener(
            "input",
            computeSellingPrice
        );

}


if (markupPercentInput) {

    markupPercentInput
        .addEventListener(
            "input",
            computeSellingPrice
        );

}


/* =========================================================
   EDIT PRODUCT - SELLING PRICE
========================================================= */

function computeEditPrice(input) {

    if (!input) {

        return;

    }


    const form =
        input.closest(
            ".editProductForm"
        );


    if (!form) {

        return;

    }


    const acquiredInput =
        form.querySelector(
            ".acquired-price"
        );


    const markupInput =
        form.querySelector(
            ".markup-percent"
        );


    const sellingInput =
        form.querySelector(
            ".selling-price"
        );


    if (
        !acquiredInput
        ||
        !markupInput
        ||
        !sellingInput
    ) {

        return;

    }


    const acquired =
        Math.max(
            0,
            inventoryNumber(
                acquiredInput.value
            )
        );


    const markup =
        Math.max(
            0,
            inventoryNumber(
                markupInput.value
            )
        );


    const sellingPrice =
        acquired
        +
        (
            acquired
            *
            markup
            /
            100
        );


    sellingInput.value =
        sellingPrice.toFixed(2);

}


/* =========================================================
   IMAGE VALIDATION FOR PREVIEW ONLY

   IMPORTANT:
   This is UX validation only.
   Actual security validation will be done in PHP.
========================================================= */

function validatePreviewImage(file) {

    if (!file) {

        return false;

    }


    const allowedTypes = [

        "image/jpeg",
        "image/png",
        "image/webp"

    ];


    if (
        !allowedTypes.includes(
            file.type
        )
    ) {

        Swal.fire({

            icon:
                "warning",

            title:
                "Invalid Image",

            text:
                "Please select a JPG, PNG, or WEBP image."

        });

        return false;

    }


    /*
     * Preview-side size limit only.
     * Server-side validation will still be required.
     */

    const maximumSize =
        20
        *
        1024
        *
        1024;


    if (
        file.size >
        maximumSize
    ) {

        Swal.fire({

            icon:
                "warning",

            title:
                "Image Too Large",

            text:
                "Please select an image smaller than 20 MB."

        });

        return false;

    }


    return true;

}


/* =========================================================
   ADD PRODUCT - IMAGE PREVIEW
========================================================= */

function previewImage(event) {

    const input =
        event?.target;


    const file =
        input?.files?.[0];


    if (!file) {

        return;

    }


    if (
        !validatePreviewImage(
            file
        )
    ) {

        input.value =
            "";

        return;

    }


    const preview =
        document.getElementById(
            "preview-image"
        );


    const imageText =
        document.getElementById(
            "image-text"
        );


    if (!preview) {

        return;

    }


    const reader =
        new FileReader();


    reader.addEventListener(
        "load",
        function() {

            preview.src =
                String(
                    reader.result
                    ??
                    ""
                );


            preview.style.display =
                "block";


            if (imageText) {

                imageText.style.display =
                    "none";

            }

        }
    );


    reader.readAsDataURL(
        file
    );

}


/* =========================================================
   EDIT PRODUCT - IMAGE PREVIEW
========================================================= */

function previewEditImage(
    event,
    id
) {

    const input =
        event?.target;


    const file =
        input?.files?.[0];


    if (!file) {

        return;

    }


    if (
        !validatePreviewImage(
            file
        )
    ) {

        input.value =
            "";

        return;

    }


    const preview =
        document.getElementById(
            "preview-image"
            +
            id
        );


    const imageText =
        document.getElementById(
            "image-text"
            +
            id
        );


    if (!preview) {

        return;

    }


    const reader =
        new FileReader();


    reader.addEventListener(
        "load",
        function() {

            preview.src =
                String(
                    reader.result
                    ??
                    ""
                );


            preview.style.display =
                "block";


            if (imageText) {

                imageText.style.display =
                    "none";

            }

        }
    );


    reader.readAsDataURL(
        file
    );

}


/* =========================================================
   INVENTORY SEARCH
========================================================= */

const inventorySearchInput =
    document.getElementById(
        "searchInput"
    );


if (inventorySearchInput) {

    inventorySearchInput
        .addEventListener(
            "input",
            function() {

                const search =
                    this.value
                        .trim()
                        .toLowerCase();


                const cards =
                    document
                        .querySelectorAll(
                            ".searchable-card"
                        );


                cards.forEach(
                    function(card) {

                        const productName =
                            (
                                card.dataset
                                    .productName
                                ||
                                card
                                    .querySelector(
                                        ".product-search-name"
                                    )
                                    ?.textContent
                                ||
                                ""
                            )
                                .trim()
                                .toLowerCase();


                        const column =
                            card.closest(
                                ".col-xl-4, .col-lg-4, .col-md-6"
                            )
                            ||
                            card.parentElement;


                        if (!column) {

                            return;

                        }


                        column.style.display =
                            productName.includes(
                                search
                            )
                            ?
                            ""
                            :
                            "none";

                    }
                );

            }
        );

}


/* =========================================================
   INVENTORY INLINE FORM VALIDATION
========================================================= */

function inventoryGetFieldError(
    input,
    mode
) {

    if (!input) {

        return "";

    }


    const name =
        input.name;


    const rawValue =
        String(
            input.value ?? ""
        );


    const value =
        rawValue.trim();


    /* =====================================================
       PRODUCT NAME
    ===================================================== */

    if (
        name ===
        "product_name"
    ) {

        if (
            value === ""
        ) {

            return "Product name is required.";

        }


        if (
            value.length >
            150
        ) {

            return "Product name must not exceed 150 characters.";

        }


        return "";

    }


    /* =====================================================
       ACQUIRED PRICE
    ===================================================== */

    if (
        name ===
        "acquired_price"
    ) {

        if (
            value === ""
        ) {

            return "Acquired price is required.";

        }


        const price =
            Number(value);


        if (
            !Number.isFinite(
                price
            )
            ||
            price < 0.01
        ) {

            return "Acquired price must be at least ₱0.01.";

        }


        return "";

    }


    /* =====================================================
       MARK UP
    ===================================================== */

    if (
        name ===
        "markup_percent"
    ) {

        if (
            value === ""
        ) {

            return "Mark up percentage is required.";

        }


        const markup =
            Number(value);


        if (
            !Number.isFinite(
                markup
            )
            ||
            markup < 0
        ) {

            return "Mark up percentage cannot be negative.";

        }


        return "";

    }


    /* =====================================================
       QUANTITY
    ===================================================== */

    if (
        name ===
        "quantity"
    ) {

        if (
            value === ""
        ) {

            return "Product quantity is required.";

        }


        const quantity =
            Number(value);


        if (
            !Number.isInteger(
                quantity
            )
        ) {

            return "Product quantity must be a whole number.";

        }


        if (
            mode === "add"
            &&
            quantity < 1
        ) {

            return "Product quantity must be at least 1.";

        }


        if (
            mode === "edit"
            &&
            quantity < 0
        ) {

            return "Product quantity cannot be negative.";

        }


        return "";

    }


    return "";

}


/* =========================================================
   SHOW FIELD ERROR
========================================================= */

function inventorySetFieldError(
    input,
    message
) {

    if (
        !input
        ||
        !message
    ) {

        return;

    }


    inventoryClearFieldError(
        input
    );


    input.dataset.inventoryInvalid =
        "true";


    input.style.setProperty(
        "border",
        "2px solid #ff6b6b",
        "important"
    );


    input.style.setProperty(
        "box-shadow",
        "0 0 0 3px rgba(255, 107, 107, .16)",
        "important"
    );


    const errorMessage =
        document.createElement(
            "small"
        );


    errorMessage.className =
        "inventory-field-error";


    errorMessage.dataset.inventoryErrorFor =
        input.id
        ||
        input.name;


    errorMessage.textContent =
        message;


    errorMessage.style.display =
        "block";


    errorMessage.style.marginTop =
        "6px";


    errorMessage.style.paddingLeft =
        "3px";


    errorMessage.style.color =
        "#ff8a8a";


    errorMessage.style.fontSize =
        "11px";


    errorMessage.style.fontWeight =
        "600";


    errorMessage.style.lineHeight =
        "1.35";


    input.insertAdjacentElement(
        "afterend",
        errorMessage
    );

}


/* =========================================================
   CLEAR ONE FIELD ERROR
========================================================= */

function inventoryClearFieldError(
    input
) {

    if (!input) {

        return;

    }


    delete input.dataset.inventoryInvalid;


    input.style.removeProperty(
        "border"
    );


    input.style.removeProperty(
        "box-shadow"
    );


    const sibling =
        input.nextElementSibling;


    if (
        sibling
        &&
        sibling.classList.contains(
            "inventory-field-error"
        )
    ) {

        sibling.remove();

    }

}


/* =========================================================
   CLEAR FORM ERRORS
========================================================= */

function inventoryClearFormErrors(
    form
) {

    if (!form) {

        return;

    }


    form
        .querySelectorAll(
            ".inventory-field-error"
        )
        .forEach(
            function(errorMessage) {

                errorMessage.remove();

            }
        );


    form
        .querySelectorAll(
            "[data-inventory-invalid='true']"
        )
        .forEach(
            function(input) {

                inventoryClearFieldError(
                    input
                );

            }
        );

}


/* =========================================================
   VALIDATE ENTIRE FORM
========================================================= */

function inventoryValidateForm(
    form,
    mode
) {

    if (!form) {

        return false;

    }


    const fields = [

        form.querySelector(
            'input[name="product_name"]'
        ),

        form.querySelector(
            'input[name="acquired_price"]'
        ),

        form.querySelector(
            'input[name="markup_percent"]'
        ),

        form.querySelector(
            'input[name="quantity"]'
        )

    ];


    let firstInvalidField =
        null;


    fields.forEach(
        function(input) {

            if (!input) {

                return;

            }


            const message =
                inventoryGetFieldError(
                    input,
                    mode
                );


            if (
                message
            ) {

                inventorySetFieldError(
                    input,
                    message
                );


                if (
                    !firstInvalidField
                ) {

                    firstInvalidField =
                        input;

                }

            }
            else {

                inventoryClearFieldError(
                    input
                );

            }

        }
    );


    if (
        firstInvalidField
    ) {

        firstInvalidField
            .scrollIntoView({

                behavior:
                    "smooth",

                block:
                    "center"

            });


        setTimeout(
            function() {

                firstInvalidField.focus({

                    preventScroll:
                        true

                });

            },
            180
        );


        return false;

    }


    return true;

}


/* =========================================================
   LIVE ERROR CLEARING
========================================================= */

function inventorySetupInlineValidation(
    form,
    mode
) {

    if (!form) {

        return;

    }


    /*
     * Disable browser-native validation bubbles.
     * Server-side validation remains the final authority.
     */

    form.noValidate =
        true;


    const fields =
        form.querySelectorAll(
            `
            input[name="product_name"],
            input[name="acquired_price"],
            input[name="markup_percent"],
            input[name="quantity"]
            `
        );


    fields.forEach(
        function(input) {

            input.addEventListener(
                "input",
                function() {

                    /*
                     * Do not show red errors immediately
                     * before the user has submitted.
                     *
                     * Once an error is already visible,
                     * keep checking until the field
                     * becomes correct.
                     */

                    if (
                        input.dataset
                            .inventoryInvalid
                        !==
                        "true"
                    ) {

                        return;

                    }


                    const message =
                        inventoryGetFieldError(
                            input,
                            mode
                        );


                    if (
                        !message
                    ) {

                        inventoryClearFieldError(
                            input
                        );

                        return;

                    }


                    const errorMessage =
                        input
                            .nextElementSibling;


                    if (
                        errorMessage
                        &&
                        errorMessage
                            .classList
                            .contains(
                                "inventory-field-error"
                            )
                    ) {

                        errorMessage.textContent =
                            message;

                    }

                }
            );

        }
    );

}


/* =========================================================
   ADD PRODUCT CONFIRMATION
========================================================= */

const addProductForm =
    document.getElementById(
        "addProductForm"
    );


if (
    addProductForm
) {

    let addProductSubmitting =
        false;


    inventorySetupInlineValidation(
        addProductForm,
        "add"
    );


    addProductForm
        .addEventListener(
            "submit",
            function(event) {

                event.preventDefault();


                if (
                    addProductSubmitting
                ) {

                    return;

                }


                /* =================================================
                   INLINE FIELD VALIDATION FIRST
                ================================================= */

                const isValid =
                    inventoryValidateForm(
                        addProductForm,
                        "add"
                    );


                if (
                    !isValid
                ) {

                    return;

                }


                /* =================================================
                   FINAL CONFIRMATION
                ================================================= */

                Swal.fire({

                    title:
                        "Add Product?",

                    text:
                        "Save this new product to Inventory?",

                    icon:
                        "question",

                    showCancelButton:
                        true,

                    confirmButtonText:
                        "Save Product",

                    cancelButtonText:
                        "Cancel",

                    allowOutsideClick:
                        false,

                    reverseButtons:
                        true

                })
                .then(
                    function(result) {

                        if (
                            !result.isConfirmed
                        ) {

                            return;

                        }


                        addProductSubmitting =
                            true;


                        Swal.fire({

                            title:
                                "Saving Product...",

                            text:
                                "Please wait while the product image is being processed.",

                            allowOutsideClick:
                                false,

                            allowEscapeKey:
                                false,

                            showConfirmButton:
                                false,

                            didOpen:
                                function() {

                                    Swal.showLoading();

                                }

                        });


                        setTimeout(
                            function() {

                                inventoryNativeSubmit(
                                    addProductForm
                                );

                            },
                            150
                        );

                    }
                );

            }
        );

}


/* =========================================================
   EDIT PRODUCT CONFIRMATION
========================================================= */

document
    .querySelectorAll(
        ".editProductForm"
    )
    .forEach(
        function(form) {

            let editSubmitting =
                false;


            inventorySetupInlineValidation(
                form,
                "edit"
            );


            form.addEventListener(
                "submit",
                function(event) {

                    event.preventDefault();


                    if (
                        editSubmitting
                    ) {

                        return;

                    }


                    /* =============================================
                       INLINE FIELD VALIDATION FIRST
                    ============================================= */

                    const isValid =
                        inventoryValidateForm(
                            form,
                            "edit"
                        );


                    if (
                        !isValid
                    ) {

                        return;

                    }


                    /* =============================================
                       FINAL CONFIRMATION
                    ============================================= */

                    Swal.fire({

                        title:
                            "Update Product?",

                        text:
                            "Save all changes made to this product?",

                        icon:
                            "question",

                        showCancelButton:
                            true,

                        confirmButtonText:
                            "Save Changes",

                        cancelButtonText:
                            "Cancel",

                        allowOutsideClick:
                            false,

                        reverseButtons:
                            true

                    })
                    .then(
                        function(result) {

                            if (
                                !result.isConfirmed
                            ) {

                                return;

                            }


                            editSubmitting =
                                true;


                            Swal.fire({

                                title:
                                    "Updating Product...",

                                text:
                                    "Please wait while the product changes are being processed.",

                                allowOutsideClick:
                                    false,

                                allowEscapeKey:
                                    false,

                                showConfirmButton:
                                    false,

                                didOpen:
                                    function() {

                                    Swal.showLoading();

                                }

                            });


                            setTimeout(
                                function() {

                                    inventoryNativeSubmit(
                                        form
                                    );

                                },
                                150
                            );

                        }
                    );

                }
            );

        }
    );


/* =========================================================
   ADD PRODUCT MODAL RESET
========================================================= */

const addProductModal =
    document.getElementById(
        "addProductModal"
    );


if (
    addProductModal
) {

    addProductModal
        .addEventListener(
            "hidden.bs.modal",
            function() {

                if (
                    addProductForm
                ) {

                    addProductForm.reset();

                }


                const preview =
                    document.getElementById(
                        "preview-image"
                    );


                const imageText =
                    document.getElementById(
                        "image-text"
                    );


                if (
                    preview
                ) {

                    preview.removeAttribute(
                        "src"
                    );

                    preview.style.display =
                        "none";

                }


                if (
                    imageText
                ) {

                    imageText.style.display =
                        "flex";

                }


                computeSellingPrice();

            }
        );

}


/* =========================================================
   KEYBOARD SUPPORT FOR EDIT BUTTONS
========================================================= */

document
    .querySelectorAll(
        ".edit-btn[role='button']"
    )
    .forEach(
        function(button) {

            button.addEventListener(
                "keydown",
                function(event) {

                    if (
                        event.key !==
                        "Enter"
                        &&
                        event.key !==
                        " "
                    ) {

                        return;

                    }


                    event.preventDefault();

                    button.click();

                }
            );

        }
    );


/* =========================================================
   ARCHIVE PRODUCT CONFIRMATION
========================================================= */

let archiveProcessing =
    false;


document
    .querySelectorAll(
        ".inventory-archive-form"
    )
    .forEach(
        function(archiveForm) {

            archiveForm
                .addEventListener(
                    "submit",
                    function(event) {

                        event.preventDefault();


                        if (
                            archiveProcessing
                        ) {

                            return;

                        }


                        /* =========================================
                           SECURE FORM VALUES
                        ========================================= */

                        const productIDInput =
                            archiveForm.querySelector(
                                'input[name="product_id"]'
                            );


                        const csrfInput =
                            archiveForm.querySelector(
                                'input[name="csrf_token"]'
                            );


                        const productName =
                            String(
                                archiveForm.dataset.productName
                                ||
                                "this product"
                            ).trim();


                        if (
                            !productIDInput
                            ||
                            !csrfInput
                            ||
                            !productIDInput.value
                            ||
                            !csrfInput.value
                        ) {

                            Swal.fire({

                                icon:
                                    "error",

                                title:
                                    "Unable to Archive",

                                text:
                                    "Required security information is missing."

                            });

                            return;

                        }


                        /* =========================================
                           CONFIRM ARCHIVE
                        ========================================= */

                        Swal.fire({

                            title:
                                "Archive this Product?",

                            text:
                                productName
                                +
                                " will be moved to the Archive Module and can be restored later.",

                            icon:
                                "warning",

                            showCancelButton:
                                true,

                            confirmButtonText:
                                "YES, ARCHIVE",

                            cancelButtonText:
                                "CANCEL",

                            allowOutsideClick:
                                false,

                            allowEscapeKey:
                                false,

                            reverseButtons:
                                true,

                            confirmButtonColor:
                                "#D85C5C",

                            cancelButtonColor:
                                "#777777"

                        })
                        .then(
                            function(result) {

                                if (
                                    !result.isConfirmed
                                ) {

                                    return;

                                }


                                archiveProcessing =
                                    true;


                                /* =================================
                                   PROCESSING SCREEN
                                ================================= */

                                Swal.fire({

                                    title:
                                        "Archiving Product...",

                                    text:
                                        "Please wait while the product is being moved to Archive.",

                                    allowOutsideClick:
                                        false,

                                    allowEscapeKey:
                                        false,

                                    showConfirmButton:
                                        false,

                                    didOpen:
                                        function() {

                                            Swal.showLoading();

                                        }

                                });


                                /* =================================
                                   SUBMIT EXISTING SECURE FORM
                                ================================= */

                                setTimeout(
                                    function() {

                                        inventoryNativeSubmit(
                                            archiveForm
                                        );

                                    },
                                    150
                                );

                            }
                        );

                    }
                );

        }
    );


/* =========================================================
   KEEP INVENTORY SCROLL POSITION
========================================================= */

const inventoryBody =
    document.querySelector(
        ".inventory-body"
    );


const inventoryScrollKey =
    "spis_inventory_scroll_position";


if (
    inventoryBody
) {


    /* =====================================================
       SAVE POSITION WHILE SCROLLING
    ===================================================== */

    inventoryBody.addEventListener(
        "scroll",
        function() {

            sessionStorage.setItem(

                inventoryScrollKey,

                String(
                    inventoryBody.scrollTop
                )

            );

        },
        {
            passive:
                true
        }
    );


    /* =====================================================
       SAVE AGAIN BEFORE LEAVING / REFRESHING
    ===================================================== */

    window.addEventListener(
        "pagehide",
        function() {

            sessionStorage.setItem(

                inventoryScrollKey,

                String(
                    inventoryBody.scrollTop
                )

            );

        }
    );


    /* =====================================================
       RESTORE SAVED POSITION
    ===================================================== */

    window.addEventListener(
        "load",
        function() {

            const savedPosition =
                sessionStorage.getItem(
                    inventoryScrollKey
                );


            if (
                savedPosition === null
            ) {

                return;

            }


            const scrollPosition =
                Number.parseInt(
                    savedPosition,
                    10
                );


            if (
                !Number.isFinite(
                    scrollPosition
                )
            ) {

                return;

            }


            /*
             * Give product cards/images a moment
             * to finish their final layout.
             */

            setTimeout(
                function() {

                    inventoryBody.scrollTop =
                        scrollPosition;

                },
                50
            );

        }
    );

}


/* =========================================================
   INITIAL CALCULATION
========================================================= */

computeSellingPrice();