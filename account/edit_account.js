document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       ELEMENTS
    ===================================================== */

    const modal =
        document.getElementById("editAccountModal");

    const form =
        document.getElementById("editAccountForm");

    const archiveForm =
        document.getElementById("archiveAccountForm");

    const archiveButton =
        document.getElementById("archiveAccountBtn");


    if (!modal || !form) {
        return;
    }


    /* ACCOUNT FIELDS */

    const userIDInput =
        document.getElementById("editUserID");

    const nameInput =
        document.getElementById("editName");

    const emailInput =
        document.getElementById("editEmail");

    const roleInput =
        document.getElementById("editRole");


    /* STATUS */

    const statusInput =
        document.getElementById("editStatus");

    const statusButton =
        document.getElementById("editStatusBtn");

    const statusText =
        document.getElementById("editStatusText");

    const statusMenu =
        document.getElementById("editStatusMenu");

    const statusItems =
        document.querySelectorAll(
            "#editStatusMenu .account-status-item"
        );


    /* MODULE ACCESS */

    const moduleAccessValue =
        document.getElementById(
            "editModuleAccessValue"
        );

    const moduleGrid =
        document.getElementById(
            "editModuleGrid"
        );

    const moduleCheckboxes =
        document.querySelectorAll(
            '#editAccountForm ' +
            'input[name="modules[]"]'
        );

    const regularModuleCheckboxes =
        Array.from(moduleCheckboxes).filter(
            function (checkbox) {
                return checkbox.value !== "Password Reset";
            }
        );

    const allModules =
        document.getElementById(
            "editAllModules"
        );

    const allModulesOption =
        document.getElementById(
            "editAllModulesOption"
        );


    /* LENDING */

    const lendingAccess =
        document.getElementById(
            "editLending"
        );

    const lendingWrapper =
        document.getElementById(
            "editLendingPermissionWrapper"
        );

    const permissionOptions =
        document.getElementById(
            "editPermissionOptions"
        );

    const permissionRadios =
        document.querySelectorAll(
            '#editAccountForm ' +
            'input[name="lending_permission"]'
        );


    /* ARCHIVE */

    const archiveUserIDInput =
        document.getElementById(
            "archiveUserID"
        );


    let isLoadingAccount = false;


    let passwordResetNoticeShown = false;

    /* =====================================================
       SMALL HELPERS
    ===================================================== */

    function normalizeText(value) {

        return String(
            value ?? ""
        ).trim();

    }


    function escapeSelectorValue(value) {

        if (
            window.CSS &&
            typeof window.CSS.escape ===
                "function"
        ) {

            return window.CSS.escape(
                String(value)
            );

        }


        return String(value).replace(
            /["\\]/g,
            "\\$&"
        );

    }


    /* =====================================================
       FIELD VALIDATION
    ===================================================== */

    function getFieldContainer(target) {

        if (!target) {
            return null;
        }


        return target.closest(
            ".account-field"
        );

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
            .forEach(
                function (error) {

                    error.remove();

                }
            );

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
            document.createElement(
                "small"
            );


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
            .forEach(
                function (error) {

                    error.remove();

                }
            );

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
            document.createElement(
                "small"
            );


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


    function clearAllValidation() {

        clearFieldValidation(
            nameInput
        );

        clearFieldValidation(
            roleInput
        );

        clearFieldValidation(
            statusInput,
            statusButton
        );

        clearGroupError(
            moduleGrid
        );

        clearGroupError(
            permissionOptions
        );

    }


    /* =====================================================
       STATUS
    ===================================================== */

    function closeStatusMenu() {

        if (!statusMenu) {
            return;
        }


        statusMenu.classList.remove(
            "show"
        );


        if (statusButton) {

            statusButton.setAttribute(
                "aria-expanded",
                "false"
            );

        }

    }


    function setStatus(value) {

        const normalizedValue =
            normalizeText(value);


        if (
            normalizedValue !== "Active" &&
            normalizedValue !== "Inactive"
        ) {

            statusInput.value = "";

            statusText.textContent =
                "Select Status";


            statusItems.forEach(
                function (item) {

                    item.classList.remove(
                        "selected"
                    );

                }
            );


            return;

        }


        statusInput.value =
            normalizedValue;


        statusText.textContent =
            normalizedValue;


        statusItems.forEach(
            function (item) {

                item.classList.toggle(
                    "selected",
                    item.dataset.value ===
                        normalizedValue
                );

            }
        );


        clearFieldValidation(
            statusInput,
            statusButton
        );

    }


    if (statusButton) {

        statusButton.addEventListener(
            "click",
            function (event) {

                event.stopPropagation();


                const willOpen =
                    !statusMenu.classList
                        .contains("show");


                closeStatusMenu();


                if (willOpen) {

                    statusMenu.classList.add(
                        "show"
                    );


                    statusButton.setAttribute(
                        "aria-expanded",
                        "true"
                    );

                }

            }
        );

    }


    statusItems.forEach(
        function (item) {

            item.addEventListener(
                "click",
                function () {

                    setStatus(
                        this.dataset.value
                    );


                    closeStatusMenu();

                }
            );

        }
    );


    document.addEventListener(
        "click",
        function (event) {

            if (
                statusMenu &&
                statusButton &&
                !statusMenu.contains(
                    event.target
                ) &&
                !statusButton.contains(
                    event.target
                )
            ) {

                closeStatusMenu();

            }

        }
    );


    document.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {

                closeStatusMenu();

            }

        }
    );


    /* =====================================================
       MODULE ACCESS
    ===================================================== */

    function syncModuleAccessValue() {

        if (!moduleAccessValue) {
            return;
        }


        const selected =
            [];


        moduleCheckboxes.forEach(
            function (checkbox) {

                if (checkbox.checked) {

                    selected.push(
                        checkbox.value
                    );

                }

            }
        );


        moduleAccessValue.value =
            selected.join(",");

    }


    function updateModuleVisuals() {

        moduleCheckboxes.forEach(
            function (checkbox) {

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

    }


    function updateAllModulesState() {

        if (
            !allModules ||
            !allModulesOption
        ) {
            return;
        }

        const allChecked =
            regularModuleCheckboxes.length > 0 &&
            Array
                .from(regularModuleCheckboxes)

                .every(
                    function (checkbox) {

                        return checkbox.checked;

                    }
                );


        allModules.checked =
            allChecked;


        allModulesOption.classList.toggle(
            "active",
            allChecked
        );

    }


    /* =====================================================
       LENDING PERMISSION
    ===================================================== */

    function clearLendingPermission() {

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


    function updateLendingVisuals() {

        permissionRadios.forEach(
            function (radio) {

                const option =
                    radio.closest(
                        ".permission-option"
                    );


                if (option) {

                    option.classList.toggle(
                        "active",
                        radio.checked
                    );

                }

            }
        );

    }


    function updateLendingPermission() {

        if (
            !lendingAccess ||
            !lendingWrapper
        ) {
            return;
        }


        if (lendingAccess.checked) {

            lendingWrapper.classList.add(
                "is-open"
            );

        }
        else {

            lendingWrapper.classList.remove(
                "is-open"
            );


            clearLendingPermission();

        }

    }


    /* =====================================================
       MODULE EVENTS
    ===================================================== */

    if (allModules) {

        allModules.addEventListener(
            "change",
            function () {

                regularModuleCheckboxes.forEach(
                    function (checkbox) {

                        checkbox.checked =
                            allModules.checked;

                    }
                );


                updateModuleVisuals();

                updateAllModulesState();

                syncModuleAccessValue();

                updateLendingPermission();

                clearGroupError(
                    moduleGrid
                );

            }
        );

    }


    moduleCheckboxes.forEach(
        function (checkbox) {

            checkbox.addEventListener(
                "change",
                function () {

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
                   
                    if (
                        checkbox.value === "Password Reset" &&
                        checkbox.checked &&
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

                    updateAllModulesState();

                    syncModuleAccessValue();

                    updateLendingPermission();

                    clearGroupError(
                        moduleGrid
                    );

                }
            );

        }
    );


    permissionRadios.forEach(
        function (radio) {

            radio.addEventListener(
                "change",
                function () {

                    updateLendingVisuals();


                    clearGroupError(
                        permissionOptions
                    );

                }
            );

        }
    );


    /* =====================================================
       LIVE FIELD VALIDATION
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
       RESET EDIT MODAL
    ===================================================== */

    function resetEditModal() {

        passwordResetNoticeShown = false;

        form.reset();

        clearAllValidation();

        closeStatusMenu();


        userIDInput.value = "";

        nameInput.value = "";

        emailInput.value = "";

        roleInput.value = "";

        statusInput.value = "";

        statusText.textContent =
            "Select Status";


        statusItems.forEach(
            function (item) {

                item.classList.remove(
                    "selected"
                );

            }
        );


        moduleCheckboxes.forEach(
            function (checkbox) {

                checkbox.checked = false;

            }
        );


        if (allModules) {

            allModules.checked = false;

        }


        if (allModulesOption) {

            allModulesOption.classList.remove(
                "active"
            );

        }


        updateModuleVisuals();


        if (moduleAccessValue) {

            moduleAccessValue.value = "";

        }


        clearLendingPermission();


        if (lendingWrapper) {

            lendingWrapper.classList.remove(
                "is-open"
            );

        }


        if (archiveUserIDInput) {

            archiveUserIDInput.value =
                "";

        }

    }


    /* =====================================================
       POPULATE ACCOUNT
    ===================================================== */

    function populateAccount(user) {

        resetEditModal();


        userIDInput.value =
            String(
                user.user_id ?? ""
            );


        nameInput.value =
            String(
                user.name ?? ""
            );


        /*
         * get_account.php returns the
         * complete SPIS email.
         */

        const fullEmail =
            String(
                user.email ?? ""
            );


        emailInput.value =
            fullEmail.replace(
                /@spis\.com$/i,
                ""
            );


        roleInput.value =
            String(
                user.role ?? ""
            );


        setStatus(
            user.status
        );


        /* MODULES */

        const accessMap = {

            Inventory:
                Number(
                    user.inventory_access
                ) === 1,

            Sales:
                Number(
                    user.sales_access
                ) === 1,

            Members:
                Number(
                    user.members_access
                ) === 1,

            Lending:
                Number(
                    user.lending_access
                ) === 1,

            Rental:
                Number(
                    user.rental_access
                ) === 1,

            Archives:
                Number(
                    user.archives_access
                ) === 1,
            
            Account:
                Number(
                    user.account_access
                ) === 1,

            "Password Reset":
                Number(
                    user.password_reset_access
                ) === 1

        };


        moduleCheckboxes.forEach(
            function (checkbox) {

                checkbox.checked =
                    accessMap[
                        checkbox.value
                    ] === true;

            }
        );


        updateModuleVisuals();

        updateAllModulesState();

        syncModuleAccessValue();


        /* LENDING */

        clearLendingPermission();


        if (lendingAccess.checked) {

            const permission =
                String(
                    user.lending_permission ??
                    ""
                );


            const matchingRadio =
                document.querySelector(
                    '#editAccountForm ' +
                    'input[name="lending_permission"]' +
                    '[value="' +
                    escapeSelectorValue(
                        permission
                    ) +
                    '"]'
                );


            if (matchingRadio) {

                matchingRadio.checked =
                    true;

            }


            updateLendingVisuals();

        }


        updateLendingPermission();


        /* ARCHIVE TARGET */

        if (archiveUserIDInput) {

            archiveUserIDInput.value =
                userIDInput.value;

        }

    }


    /* =====================================================
       LOAD ACCOUNT
    ===================================================== */

    async function loadAccount(userID) {

        if (isLoadingAccount) {
            return;
        }


        const numericID =
            Number(userID);


        if (
            !Number.isInteger(numericID) ||
            numericID <= 0
        ) {

            Swal.fire({

                icon: "error",

                title: "Unable to load",

                text:
                    "The selected account is invalid.",

                confirmButtonColor:
                    "#8CA15B"

            });


            return;

        }


        isLoadingAccount =
            true;


        try {

            const response =
                await fetch(
                    "get_account.php?id=" +
                    encodeURIComponent(
                        numericID
                    ),
                    {
                        method: "GET",

                        headers: {

                            "Accept":
                                "application/json",

                            "X-Requested-With":
                                "XMLHttpRequest"

                        },

                        cache: "no-store",

                        credentials:
                            "same-origin"
                    }
                );


            const responseText =
                await response.text();


            let user;


            try {

                user =
                    JSON.parse(
                        responseText
                    );

            }
            catch (error) {

                throw new Error(
                    "The server returned an invalid response."
                );

            }


            if (!response.ok) {

                throw new Error(
                    user.message ||
                    user.error ||
                    "Unable to load this account."
                );

            }


            if (
                !user ||
                !user.user_id
            ) {

                throw new Error(
                    "The account record could not be loaded."
                );

            }


            populateAccount(user);


            const modalInstance =
                bootstrap.Modal
                    .getOrCreateInstance(
                        modal
                    );


            modalInstance.show();

        }
        catch (error) {

            console.error(
                "Account load error:",
                error
            );


            Swal.fire({

                icon: "error",

                title: "Unable to load",

                text:
                    error.message ||
                    "Unable to load this account.",

                confirmButtonColor:
                    "#8CA15B"

            });

        }
        finally {

            isLoadingAccount =
                false;

        }

    }


    /* =====================================================
       EDIT BUTTONS
    ===================================================== */

    document
        .querySelectorAll(
            ".account-edit-btn"
        )
        .forEach(
            function (button) {

                button.addEventListener(
                    "click",
                    function (event) {

                        /*
                         * Prevent an old Bootstrap
                         * data-target from opening an
                         * empty modal before fetch ends.
                         */

                        event.preventDefault();


                        loadAccount(
                            button.dataset.id
                        );

                    }
                );

            }
        );


    /* =====================================================
       VALIDATE EDIT FORM
    ===================================================== */

    form.noValidate = true;


    function validateEditForm() {

        clearAllValidation();


        let hasError =
            false;

        let firstInvalidField =
            null;


        const name =
            normalizeText(
                nameInput.value
            );

        const role =
            normalizeText(
                roleInput.value
            );

        const status =
            normalizeText(
                statusInput.value
            );


        /* NAME */

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
        else if (
            name.length > 100
        ) {

            showFieldError(
                nameInput,
                "Name must not exceed 100 characters."
            );


            firstInvalidField =
                firstInvalidField ||
                nameInput;


            hasError = true;

        }


        /* ROLE */

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
        else if (
            role.length > 80
        ) {

            showFieldError(
                roleInput,
                "Role must not exceed 80 characters."
            );


            firstInvalidField =
                firstInvalidField ||
                roleInput;


            hasError = true;

        }


        /* STATUS */

        if (
            status !== "Active" &&
            status !== "Inactive"
        ) {

            showFieldError(
                statusInput,
                "Please select an account status.",
                statusButton
            );


            firstInvalidField =
                firstInvalidField ||
                statusButton;


            hasError = true;

        }


        /* MODULES */

        const selectedModules =
            Array
                .from(
                    moduleCheckboxes
                )
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


        /* LENDING PERMISSION */

        if (
            lendingAccess &&
            lendingAccess.checked
        ) {

            const selectedPermission =
                Array
                    .from(
                        permissionRadios
                    )
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


        if (
            !userIDInput.value ||
            Number(userIDInput.value) <= 0
        ) {

            hasError = true;


            Swal.fire({

                icon: "error",

                title:
                    "Account unavailable",

                text:
                    "The account record is no longer loaded. Please close the window and try again.",

                confirmButtonColor:
                    "#8CA15B"

            });

        }


        if (
            hasError &&
            firstInvalidField
        ) {

            firstInvalidField.focus();


            firstInvalidField
                .scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });

        }


        return !hasError;

    }


    /* =====================================================
       SAVE CHANGES
    ===================================================== */

    form.addEventListener(
        "submit",
        async function (event) {

            event.preventDefault();


            if (!validateEditForm()) {
                return;
            }


            syncModuleAccessValue();


            let confirmed =
                true;


            if (
                typeof Swal !==
                "undefined"
            ) {

                const confirmation =
                    await Swal.fire({

                        icon: "question",

                        title:
                            "Save Account Changes?",

                        text:
                            "The account information and access permissions will be updated.",

                        showCancelButton:
                            true,

                        confirmButtonText:
                            "YES, SAVE",

                        cancelButtonText:
                            "REVIEW AGAIN",

                        confirmButtonColor:
                            "#8CA15B",

                        cancelButtonColor:
                            "#777777",

                        reverseButtons:
                            true,

                        focusCancel:
                            true,

                        allowOutsideClick:
                            false

                    });


                confirmed =
                    confirmation.isConfirmed;

            }
            else {

                confirmed =
                    window.confirm(
                        "Save changes to this account?"
                    );

            }


            if (!confirmed) {
                return;
            }


            /*
             * Final authority remains
             * update_account.php.
             */

            form.submit();

        }
    );


    /* =====================================================
       ARCHIVE ACCOUNT
    ===================================================== */

    if (
        archiveButton &&
        archiveForm
    ) {

        archiveButton.addEventListener(
            "click",
            async function () {

                const userID =
                    Number(
                        userIDInput.value
                    );


                if (
                    !Number.isInteger(
                        userID
                    ) ||
                    userID <= 0
                ) {

                    Swal.fire({

                        icon: "error",

                        title:
                            "Account unavailable",

                        text:
                            "The account record could not be identified.",

                        confirmButtonColor:
                            "#8CA15B"

                    });


                    return;

                }


                if (archiveUserIDInput) {

                    archiveUserIDInput.value =
                        String(userID);

                }


                let confirmed =
                    true;


                if (
                    typeof Swal !==
                    "undefined"
                ) {

                    const confirmation =
                        await Swal.fire({

                            icon: "warning",

                            title:
                                "Archive Account?",

                            text:
                                "This account will be removed from the active account list and moved to Archive.",

                            showCancelButton:
                                true,

                            confirmButtonText:
                                "YES, ARCHIVE",

                            cancelButtonText:
                                "KEEP ACCOUNT",

                            confirmButtonColor:
                                "#C62828",

                            cancelButtonColor:
                                "#8CA15B",

                            reverseButtons:
                                true,

                            focusCancel:
                                true,

                            allowOutsideClick:
                                false

                        });


                    confirmed =
                        confirmation
                            .isConfirmed;

                }
                else {

                    confirmed =
                        window.confirm(
                            "Archive this account?"
                        );

                }


                if (!confirmed) {
                    return;
                }


                /*
                 * Final permission,
                 * self-archive and last-manager
                 * protections will be enforced
                 * server-side.
                 */

                archiveForm.submit();

            }
        );

    }


    /* =====================================================
       MODAL RESET
    ===================================================== */

    modal.addEventListener(
        "hidden.bs.modal",
        function () {

            resetEditModal();

        }
    );


    /* =====================================================
       INITIAL STATE
    ===================================================== */

    resetEditModal();

});