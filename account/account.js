document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       ADD ACCOUNT ELEMENTS
    ===================================================== */

    const form =
        document.getElementById("accountForm");

    const modal =
        document.getElementById("addAccountModal");


    if (!form) {
        return;
    }


    const nameInput =
        document.getElementById("accountName");

    const emailInput =
        document.getElementById("accountEmail");

    const passwordInput =
        document.getElementById("password");

    const confirmPasswordInput =
        document.getElementById("confirmPassword");

    const roleInput =
        document.getElementById("accountRole");


    const passwordRuleHint =
        document.getElementById("passwordRuleHint");

    const passwordMatchFeedback =
        document.getElementById("passwordMatchFeedback");

    const passwordMatchIcon =
        document.getElementById("passwordMatchIcon");

    const passwordMatchText =
        document.getElementById("passwordMatchText");


    const moduleAccessValue =
        document.getElementById("moduleAccessValue");

    const moduleGrid =
        document.querySelector(
            "#addAccountModal .module-grid"
        );

    const moduleCheckboxes =
        document.querySelectorAll(
            "#addAccountModal " +
            ".module-option " +
            "input[type='checkbox']" +
            ":not(#allModules)"
        );
  
    const regularModuleCheckboxes =
        Array.from(moduleCheckboxes).filter(
            function (checkbox) {
                return checkbox.value !== "Password Reset";
            }
        );

    const allModules =
        document.getElementById("allModules");

    const allModulesOption =
        document.getElementById("allModulesOption");


    const lendingAccess =
        document.getElementById("lendingAccess");

    const lendingPermissionWrapper =
        document.getElementById(
            "lendingPermissionWrapper"
        );

    const lendingPermissionInput =
        document.getElementById(
            "lendingPermission"
        );

    const permissionOptions =
        document.querySelector(
            "#addAccountModal .permission-options"
        );

    const permissionRadios =
        document.querySelectorAll(
            '#addAccountModal ' +
            'input[name="lending_permission"]'
        );


    let passwordMatchTimer = null;

    
    let passwordResetNoticeShown = false;



    /* =====================================================
       GENERIC FIELD VALIDATION
    ===================================================== */

    function getFieldContainer(target) {

        if (!target) {
            return null;
        }

        return target.closest(".account-field");
    }


    function removeFieldError(
        input,
        visualTarget = null
    ) {

        if (!input) {
            return;
        }

        const target =
            visualTarget || input;


        target.classList.remove(
            "is-invalid"
        );

        input.removeAttribute(
            "aria-invalid"
        );


        const field =
            getFieldContainer(target);


        if (!field) {
            return;
        }


        field
            .querySelectorAll(
                ".account-field-error"
            )
            .forEach(function (error) {

                error.remove();

            });

    }


    function clearFieldValidation(
        input,
        visualTarget = null
    ) {

        if (!input) {
            return;
        }

        const target =
            visualTarget || input;


        target.classList.remove(
            "is-invalid",
            "is-valid"
        );

        input.removeAttribute(
            "aria-invalid"
        );


        const field =
            getFieldContainer(target);


        if (!field) {
            return;
        }


        field
            .querySelectorAll(
                ".account-field-error"
            )
            .forEach(function (error) {

                error.remove();

            });

    }


    function showFieldError(
        input,
        message,
        visualTarget = null
    ) {

        if (!input) {
            return;
        }


        const target =
            visualTarget || input;


        clearFieldValidation(
            input,
            visualTarget
        );


        target.classList.add(
            "is-invalid"
        );


        input.setAttribute(
            "aria-invalid",
            "true"
        );


        const field =
            getFieldContainer(target);


        if (!field) {
            return;
        }


        const error =
            document.createElement("small");


        error.className =
            "account-field-error";


        error.textContent =
            message;


        target.insertAdjacentElement(
            "afterend",
            error
        );

    }


    /* =====================================================
       GROUP VALIDATION
    ===================================================== */

    function clearGroupError(target) {

        if (!target) {
            return;
        }


        target.classList.remove(
            "account-group-invalid"
        );


        const parent =
            target.parentElement;


        if (!parent) {
            return;
        }


        parent
            .querySelectorAll(
                ".account-group-error"
            )
            .forEach(function (error) {

                error.remove();

            });

    }


    function showGroupError(
        target,
        message
    ) {

        if (!target) {
            return;
        }


        clearGroupError(target);


        target.classList.add(
            "account-group-invalid"
        );


        const error =
            document.createElement("small");


        error.className =
            "account-field-error " +
            "account-group-error";


        error.textContent =
            message;


        target.insertAdjacentElement(
            "afterend",
            error
        );

    }


    /* =====================================================
       PASSWORD RULE HINT
    ===================================================== */

    function updatePasswordRuleHint() {

        if (
            !passwordInput ||
            !passwordRuleHint
        ) {
            return;
        }


        if (
            passwordInput.value.length >= 8
        ) {

            passwordRuleHint
                .classList
                .add("is-satisfied");

        }
        else {

            passwordRuleHint
                .classList
                .remove("is-satisfied");

        }

    }


    /* =====================================================
       PASSWORD MATCH FEEDBACK
    ===================================================== */

    function hidePasswordMatchFeedback() {

        if (!passwordMatchFeedback) {
            return;
        }


        window.clearTimeout(
            passwordMatchTimer
        );


        passwordMatchFeedback
            .classList
            .remove(
                "is-match",
                "is-mismatch"
            );


        passwordMatchFeedback
            .classList
            .add("is-hidden");


        if (passwordMatchText) {
            passwordMatchText.textContent = "";
        }

    }


    function showPasswordMismatch() {

        if (
            !confirmPasswordInput ||
            !passwordMatchFeedback
        ) {
            return;
        }


        window.clearTimeout(
            passwordMatchTimer
        );


        removeFieldError(
            confirmPasswordInput
        );


        confirmPasswordInput
            .classList
            .remove("is-valid");


        confirmPasswordInput
            .classList
            .add("is-invalid");


        confirmPasswordInput
            .setAttribute(
                "aria-invalid",
                "true"
            );


        passwordMatchFeedback
            .classList
            .remove(
                "is-hidden",
                "is-match"
            );


        passwordMatchFeedback
            .classList
            .add("is-mismatch");


        if (passwordMatchIcon) {

            passwordMatchIcon.className =
                "bi bi-x-circle-fill";

        }


        if (passwordMatchText) {

            passwordMatchText.textContent =
                "Passwords do not match.";

        }

    }


    function showPasswordMatch() {

        if (
            !confirmPasswordInput ||
            !passwordMatchFeedback
        ) {
            return;
        }


        window.clearTimeout(
            passwordMatchTimer
        );


        removeFieldError(
            confirmPasswordInput
        );


        confirmPasswordInput
            .classList
            .remove("is-invalid");


        confirmPasswordInput
            .classList
            .add("is-valid");


        confirmPasswordInput
            .removeAttribute(
                "aria-invalid"
            );


        passwordMatchFeedback
            .classList
            .remove(
                "is-hidden",
                "is-mismatch"
            );


        passwordMatchFeedback
            .classList
            .add("is-match");


        if (passwordMatchIcon) {

            passwordMatchIcon.className =
                "bi bi-check-circle-fill";

        }


        if (passwordMatchText) {

            passwordMatchText.textContent =
                "Passwords match.";

        }


        /*
         * Positive feedback fades after
         * a short moment.
         */

        passwordMatchTimer =
            window.setTimeout(
                function () {

                    passwordMatchFeedback
                        .classList
                        .add("is-hidden");

                },
                1400
            );

    }


    function updatePasswordMatchFeedback() {

        if (
            !passwordInput ||
            !confirmPasswordInput
        ) {
            return;
        }


        const password =
            passwordInput.value;

        const confirmation =
            confirmPasswordInput.value;


        /*
         * No confirmation yet.
         */

        if (confirmation === "") {

            confirmPasswordInput
                .classList
                .remove(
                    "is-invalid",
                    "is-valid"
                );


            hidePasswordMatchFeedback();

            return;

        }


        /*
         * Main password is not valid yet.
         * Don't show a green "match" state
         * for an invalid short password.
         */

        if (password.length < 8) {

            confirmPasswordInput
                .classList
                .remove(
                    "is-invalid",
                    "is-valid"
                );


            hidePasswordMatchFeedback();

            return;

        }


        if (password === confirmation) {

            showPasswordMatch();

        }
        else {

            showPasswordMismatch();

        }

    }


    /* =====================================================
       MODULE ACCESS VALUE
    ===================================================== */

    function syncModuleAccessValue() {

        if (!moduleAccessValue) {
            return;
        }


        const selectedModules = [];


        moduleCheckboxes.forEach(
            function (checkbox) {

                if (checkbox.checked) {

                    selectedModules.push(
                        checkbox.value
                    );

                }

            }
        );


        moduleAccessValue.value =
            selectedModules.join(",");

    }


    /* =====================================================
       ALL MODULES STATE
    ===================================================== */

    function updateAllModulesState() {

        if (
            !allModules ||
            !allModulesOption
        ) {
            return;
        }


        if (
            regularModuleCheckboxes.length === 0
        ) {

            allModules.checked = false;

            allModulesOption
                .classList
                .remove("active");

            return;

        }


        const allChecked =
            Array
                .from(regularModuleCheckboxes)
                .every(
                    function (checkbox) {

                        return checkbox.checked;

                    }
                );


        allModules.checked =
            allChecked;


        allModulesOption
            .classList
            .toggle(
                "active",
                allChecked
            );

    }


    /* =====================================================
       LENDING PERMISSION
    ===================================================== */

    function clearLendingPermission() {

        if (lendingPermissionInput) {

            lendingPermissionInput.value =
                "";

        }


        permissionRadios.forEach(
            function (radio) {

                radio.checked = false;


                const option =
                    radio.closest(
                        ".permission-option"
                    );


                if (option) {

                    option.classList.remove(
                        "active"
                    );

                }

            }
        );


        clearGroupError(
            permissionOptions
        );

    }


    function updateLendingPermission() {

        if (
            !lendingAccess ||
            !lendingPermissionWrapper
        ) {
            return;
        }


        if (lendingAccess.checked) {

            lendingPermissionWrapper
                .classList
                .add("is-open");

        }
        else {

            lendingPermissionWrapper
                .classList
                .remove("is-open");


            clearLendingPermission();

        }

    }


    /* =====================================================
       MODULE CHECKBOX EVENTS
    ===================================================== */

    moduleCheckboxes.forEach(
        function (checkbox) {

            checkbox.addEventListener(
                "change",
                function () {

                    const option =
                        this.closest(
                            ".module-option"
                        );


                    if (option) {

                        option.classList.toggle(
                            "active",
                            this.checked
                        );

                    }
                   
                    if (
                        this.value === "Password Reset" &&
                        this.checked &&
                        !passwordResetNoticeShown
                    ) {

                        passwordResetNoticeShown = true;

                        Swal.fire({
                            icon: "info",
                            title: "Password Reset Access",

                            text:
                                "This permission allows the user to reset " +
                                "other accounts' passwords by issuing temporary " +
                                "passwords. Grant this access only to trusted " +
                                "personnel. Account owners must create a new " +
                                "password after logging in.",

                            confirmButtonText: "UNDERSTOOD",
                            confirmButtonColor: "#8CA15B"
                        });

                    }

                    syncModuleAccessValue();

                    updateAllModulesState();

                    updateLendingPermission();

                    clearGroupError(
                        moduleGrid
                    );

                }
            );

        }
    );
 
    /* =====================================================
       ALL MODULES EVENT
    ===================================================== */

    if (allModules) {

        allModules.addEventListener(
            "change",
            function () {

                regularModuleCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            allModules.checked;

                        const option =
                            checkbox.closest(
                                ".module-option"
                            );

                        if (option) {

                            option.classList.toggle(
                                "active",
                                checkbox.checked
                            );

                        }

                    }
                );

                if (allModulesOption) {

                    allModulesOption.classList.toggle(
                        "active",
                        allModules.checked
                    );

                }

                syncModuleAccessValue();
                updateLendingPermission();
                clearGroupError(moduleGrid);

            }
        );

    }

    /* =====================================================
       LENDING PERMISSION EVENTS
    ===================================================== */

    permissionRadios.forEach(
        function (radio) {

            radio.addEventListener(
                "change",
                function () {

                    permissionRadios.forEach(
                        function (item) {

                            const option =
                                item.closest(
                                    ".permission-option"
                                );


                            if (option) {

                                option
                                    .classList
                                    .toggle(
                                        "active",
                                        item.checked
                                    );

                            }

                        }
                    );


                    if (
                        lendingPermissionInput
                    ) {

                        lendingPermissionInput.value =
                            this.value;

                    }


                    clearGroupError(
                        permissionOptions
                    );

                }
            );

        }
    );


    /* =====================================================
       NAME LIVE VALIDATION
    ===================================================== */

    if (nameInput) {

        nameInput.addEventListener(
            "input",
            function () {

                clearFieldValidation(
                    nameInput
                );

            }
        );

    }


    /* =====================================================
       EMAIL LIVE VALIDATION
    ===================================================== */

    if (emailInput) {

        const emailWrapper =
            emailInput.closest(
                ".email-wrapper"
            );


        emailInput.addEventListener(
            "input",
            function () {

                clearFieldValidation(
                    emailInput,
                    emailWrapper
                );

            }
        );


        emailInput.addEventListener(
            "blur",
            function () {

                /*
                 * SPIS uses username@spis.com.
                 * Remove anything after @ if typed.
                 */

                this.value =
                    this.value.replace(
                        /@.*$/,
                        ""
                    );

            }
        );

    }


    /* =====================================================
       PASSWORD LIVE VALIDATION
    ===================================================== */

    if (passwordInput) {

        passwordInput.addEventListener(
            "input",
            function () {

                clearFieldValidation(
                    passwordInput
                );


                updatePasswordRuleHint();

                updatePasswordMatchFeedback();

            }
        );

    }


    /* =====================================================
       CONFIRM PASSWORD LIVE VALIDATION
    ===================================================== */

    if (confirmPasswordInput) {

        confirmPasswordInput.addEventListener(
            "input",
            function () {

                /*
                 * Remove old submit-generated
                 * error text first.
                 */

                removeFieldError(
                    confirmPasswordInput
                );


                updatePasswordMatchFeedback();

            }
        );

    }


    /* =====================================================
       ROLE LIVE VALIDATION
    ===================================================== */

    if (roleInput) {

        roleInput.addEventListener(
            "input",
            function () {

                clearFieldValidation(
                    roleInput
                );

            }
        );

    }


    /* =====================================================
       CLEAR ALL VALIDATION
    ===================================================== */

    function clearAllValidation() {

        clearFieldValidation(
            nameInput
        );


        if (emailInput) {

            clearFieldValidation(
                emailInput,
                emailInput.closest(
                    ".email-wrapper"
                )
            );

        }


        clearFieldValidation(
            passwordInput
        );


        clearFieldValidation(
            confirmPasswordInput
        );


        clearFieldValidation(
            roleInput
        );


        clearGroupError(
            moduleGrid
        );


        clearGroupError(
            permissionOptions
        );


        hidePasswordMatchFeedback();

    }


    /* =====================================================
       FORM VALIDATION
    ===================================================== */

    form.noValidate = true;


    form.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();


            /*
             * Clear old submit errors.
             * Password live state is rebuilt later.
             */

            clearAllValidation();

            updatePasswordRuleHint();


            let hasError = false;

            let firstInvalidField = null;


            const name =
                nameInput
                    ? nameInput.value.trim()
                    : "";


            const email =
                emailInput
                    ? emailInput.value.trim()
                    : "";


            const password =
                passwordInput
                    ? passwordInput.value
                    : "";


            const confirmation =
                confirmPasswordInput
                    ? confirmPasswordInput.value
                    : "";


            const role =
                roleInput
                    ? roleInput.value.trim()
                    : "";


            /* =================================================
               NAME
            ================================================= */

            if (name === "") {

                showFieldError(
                    nameInput,
                    "Name is required."
                );


                firstInvalidField =
                    firstInvalidField ||
                    nameInput;


                hasError = true;

            }
            else if (name.length > 100) {

                showFieldError(
                    nameInput,
                    "Name must not exceed 100 characters."
                );


                firstInvalidField =
                    firstInvalidField ||
                    nameInput;


                hasError = true;

            }


            /* =================================================
               EMAIL
            ================================================= */

            const emailWrapper =
                emailInput
                    ? emailInput.closest(
                        ".email-wrapper"
                    )
                    : null;


            if (email === "") {

                showFieldError(
                    emailInput,
                    "Email username is required.",
                    emailWrapper
                );


                firstInvalidField =
                    firstInvalidField ||
                    emailInput;


                hasError = true;

            }
            else if (
                email.length < 3 ||
                email.length > 50 ||
                !/^[a-zA-Z0-9_-]+$/.test(
                    email
                )
            ) {

                showFieldError(
                    emailInput,
                    "Use 3 to 50 letters, numbers, underscores, or hyphens only.",
                    emailWrapper
                );


                firstInvalidField =
                    firstInvalidField ||
                    emailInput;


                hasError = true;

            }


            /* =================================================
               PASSWORD
            ================================================= */

            if (password === "") {

                showFieldError(
                    passwordInput,
                    "Password is required."
                );


                firstInvalidField =
                    firstInvalidField ||
                    passwordInput;


                hasError = true;

            }
            else if (password.length < 8) {

                showFieldError(
                    passwordInput,
                    "Password must contain at least 8 characters."
                );


                firstInvalidField =
                    firstInvalidField ||
                    passwordInput;


                hasError = true;

            }
            else if (password.length > 255) {

                showFieldError(
                    passwordInput,
                    "Password is too long."
                );


                firstInvalidField =
                    firstInvalidField ||
                    passwordInput;


                hasError = true;

            }


            /* =================================================
               CONFIRM PASSWORD
            ================================================= */

            if (confirmation === "") {

                showFieldError(
                    confirmPasswordInput,
                    "Please confirm the password."
                );


                firstInvalidField =
                    firstInvalidField ||
                    confirmPasswordInput;


                hasError = true;

            }
            else if (
                password !== "" &&
                password !== confirmation
            ) {

                /*
                 * Use polished password feedback
                 * instead of creating duplicate text.
                 */

                showPasswordMismatch();


                firstInvalidField =
                    firstInvalidField ||
                    confirmPasswordInput;


                hasError = true;

            }
            else if (
                password.length >= 8
            ) {

                showPasswordMatch();

            }


            /* =================================================
               ROLE
            ================================================= */

            if (role === "") {

                showFieldError(
                    roleInput,
                    "Role is required."
                );


                firstInvalidField =
                    firstInvalidField ||
                    roleInput;


                hasError = true;

            }
            else if (role.length > 80) {

                showFieldError(
                    roleInput,
                    "Role must not exceed 80 characters."
                );


                firstInvalidField =
                    firstInvalidField ||
                    roleInput;


                hasError = true;

            }


            /* =================================================
               MODULE ACCESS
            ================================================= */

            const selectedModules =
                Array
                    .from(moduleCheckboxes)
                    .filter(
                        function (checkbox) {

                            return checkbox.checked;

                        }
                    );


            if (
                selectedModules.length === 0
            ) {

                showGroupError(
                    moduleGrid,
                    "Please select at least one module access."
                );


                hasError = true;

            }


            /* =================================================
               LENDING PERMISSION
            ================================================= */

            if (
                lendingAccess &&
                lendingAccess.checked
            ) {

                const selectedPermission =
                    Array
                        .from(permissionRadios)
                        .find(
                            function (radio) {

                                return radio.checked;

                            }
                        );


                if (!selectedPermission) {

                    showGroupError(
                        permissionOptions,
                        "Please select Limited or Full Access."
                    );


                    hasError = true;

                }

            }


            /* =================================================
               STOP IF INVALID
            ================================================= */

            if (hasError) {

                if (firstInvalidField) {

                    firstInvalidField.focus();


                    firstInvalidField
                        .scrollIntoView({
                            behavior: "smooth",
                            block: "center"
                        });

                }


                return;

            }


            /* =================================================
               FINAL MODULE SYNC
            ================================================= */

            syncModuleAccessValue();


            /* =================================================
               CONFIRM ACCOUNT CREATION
            ================================================= */

            Swal.fire({

                icon: "question",

                title: "Add Account?",

                text:
                    "Are you sure you want to create this account?",

                showCancelButton: true,

                confirmButtonText:
                    "YES, SAVE",

                cancelButtonText:
                    "REVIEW AGAIN",

                confirmButtonColor:
                    "#8CA15B",

                cancelButtonColor:
                    "#777777",

                reverseButtons: true,

                focusCancel: true,

                allowOutsideClick: false

            })
            .then(function (result) {

                if (result.isConfirmed) {

                    /*
                     * PHP performs final security
                     * and validation checks.
                     */

                    form.submit();

                }

            });

        }
    );


    /* =====================================================
       RESET MODAL
    ===================================================== */

    if (modal) {

        modal.addEventListener(
            "hidden.bs.modal",
            function () {
                
                passwordResetNoticeShown = false;


                form.reset();


                clearAllValidation();


                /* PASSWORD */

                updatePasswordRuleHint();

                hidePasswordMatchFeedback();


                /* MODULES */

                moduleCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            false;


                        const option =
                            checkbox.closest(
                                ".module-option"
                            );


                        if (option) {

                            option.classList.remove(
                                "active"
                            );

                        }

                    }
                );


                if (allModules) {

                    allModules.checked =
                        false;

                }


                if (allModulesOption) {

                    allModulesOption
                        .classList
                        .remove("active");

                }


                if (moduleAccessValue) {

                    moduleAccessValue.value =
                        "";

                }


                /* LENDING */

                clearLendingPermission();


                if (
                    lendingPermissionWrapper
                ) {

                    lendingPermissionWrapper
                        .classList
                        .remove("is-open");

                }

            }
        );

    }


    /* =====================================================
       INITIAL STATE
    ===================================================== */

    updatePasswordRuleHint();

    hidePasswordMatchFeedback();

    syncModuleAccessValue();

    updateAllModulesState();

    updateLendingPermission();

});