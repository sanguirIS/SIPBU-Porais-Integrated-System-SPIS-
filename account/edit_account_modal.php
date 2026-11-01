<div
class="modal fade"
id="editAccountModal"
tabindex="-1"
aria-hidden="true">

    <div
    class="modal-dialog modal-dialog-centered">

        <div class="modal-content account-modal">


            <!-- =================================================
                 HEADER
            ================================================== -->

            <div class="account-modal-header">

                <h2>
                    EDIT ACCOUNT
                </h2>


                <div class="account-header-actions">


                    <!-- ARCHIVE -->

                    <button
                    type="button"
                    class="btn-archive-account"
                    id="archiveAccountBtn">

                        <i class="bi bi-archive-fill"></i>

                        Archive Account

                    </button>


                    <!-- CLOSE -->

                    <button
                    type="button"
                    class="account-close-btn"
                    data-bs-dismiss="modal"
                    aria-label="Close">

                        ×

                    </button>

                </div>

            </div>


            <!-- =================================================
                 EDIT ACCOUNT FORM
            ================================================== -->

            <form
            id="editAccountForm"
            action="update_account.php"
            method="POST"
            novalidate>


                <!-- =================================================
                     SECURITY TOKEN
                ================================================== -->

                <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $accountCsrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>">


                <!-- =================================================
                     ACCOUNT ID
                ================================================== -->

                <input
                type="hidden"
                name="user_id"
                id="editUserID">


                <!-- =================================================
                     MODULE ACCESS VALUE
                ================================================== -->

                <input
                type="hidden"
                name="module_access"
                id="editModuleAccessValue"
                value="">


                <!-- =================================================
                     BODY
                ================================================== -->

                <div class="account-modal-body">


                    <!-- =================================================
                         LEFT PANEL
                         ACCOUNT DETAILS
                    ================================================== -->

                    <div class="account-info-section">


                        <h4>
                            Account Details
                        </h4>


                        <!-- =============================================
                             NAME + ROLE
                        ============================================== -->

                        <div class="account-two-column">


                            <!-- NAME -->

                            <div class="account-field">

                                <label for="editName">
                                    Name
                                </label>


                                <input
                                type="text"
                                name="name"
                                id="editName"
                                class="account-input"
                                placeholder="Enter full name"
                                autocomplete="name"
                                required>

                            </div>


                            <!-- ROLE -->

                            <div class="account-field">

                                <label for="editRole">
                                    Role
                                </label>


                                <input
                                type="text"
                                name="role"
                                id="editRole"
                                class="account-input"
                                placeholder="Enter role"
                                autocomplete="off"
                                required>

                            </div>

                        </div>


                        <!-- =============================================
                             EMAIL
                        ============================================== -->

                        <div class="account-field">

                            <label for="editEmail">
                                Email
                            </label>


                            <div
                            class="email-wrapper account-readonly-wrapper">

                                <input
                                type="text"
                                name="email_name"
                                id="editEmail"
                                class="account-input"
                                readonly
                                aria-readonly="true"
                                tabindex="-1">


                                <span class="email-domain">
                                    @spis.com
                                </span>

                            </div>


                            <small class="account-field-helper">
                                Email cannot be changed after the account is created.
                            </small>

                        </div>


                        <!-- =============================================
                             STATUS
                        ============================================== -->

                        <div class="account-field">

                            <label>
                                Status
                            </label>


                            <input
                            type="hidden"
                            name="status"
                            id="editStatus">


                            <div class="account-status-dropdown">


                                <button
                                type="button"
                                id="editStatusBtn"
                                class="account-status-btn"
                                aria-haspopup="listbox"
                                aria-expanded="false">

                                    <span id="editStatusText">
                                        Select Status
                                    </span>

                                    <i class="bi bi-chevron-down"></i>

                                </button>


                                <div
                                id="editStatusMenu"
                                class="account-status-menu"
                                role="listbox">


                                    <div
                                    class="account-status-item"
                                    data-value="Active"
                                    role="option">

                                        Active

                                    </div>


                                    <div
                                    class="account-status-item"
                                    data-value="Inactive"
                                    role="option">

                                        Inactive

                                    </div>

                                </div>

                            </div>


                            <small class="account-field-helper">
                                Inactive accounts cannot sign in to SPIS.
                            </small>

                        </div>


                        <!-- =============================================
                             INFORMATION NOTE
                        ============================================== -->

                        <div class="account-active-note">

                            <div class="account-active-note-icon">

                                <i class="bi bi-info-circle-fill"></i>

                            </div>


                            <div class="account-active-note-content">

                                <strong>
                                    Account access
                                </strong>

                                <span>
                                    Changes to status and module access take effect
                                    after the account is updated.
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- =================================================
                         RIGHT PANEL
                         ACCESS CONTROL
                    ================================================== -->

                    <div class="account-access-section">


                        <h4>
                            Access Control
                        </h4>


                        <p class="module-guide">
                            Select the modules this account can access.
                        </p>


                        <!-- =================================================
                             MODULE ACCESS
                        ================================================== -->

                        <div
                        class="module-grid"
                        id="editModuleGrid">


                            <!-- ALL MODULES -->

                            <label
                            class="module-option"
                            id="editAllModulesOption"
                            data-module="All Modules">

                                <input
                                type="checkbox"
                                id="editAllModules">

                                <span></span>

                                All Modules

                            </label>


                            <!-- INVENTORY -->

                            <label
                            class="module-option"
                            data-module="Inventory">

                                <input
                                type="checkbox"
                                name="modules[]"
                                value="Inventory"
                                id="editInventory">

                                <span></span>

                                Inventory

                            </label>


                            <!-- SALES -->

                            <label
                            class="module-option"
                            data-module="Sales">

                                <input
                                type="checkbox"
                                name="modules[]"
                                value="Sales"
                                id="editSales">

                                <span></span>

                                Sales

                            </label>


                            <!-- MEMBERS -->

                            <label
                            class="module-option"
                            data-module="Members">

                                <input
                                type="checkbox"
                                name="modules[]"
                                value="Members"
                                id="editMembers">

                                <span></span>

                                Members

                            </label>


                            <!-- LENDING -->

                            <label
                            class="module-option lending-check"
                            data-module="Lending">

                                <input
                                type="checkbox"
                                name="modules[]"
                                value="Lending"
                                id="editLending">

                                <span></span>

                                Lending

                            </label>


                            <!-- RENTAL -->

                            <label
                            class="module-option"
                            data-module="Rental">

                                <input
                                type="checkbox"
                                name="modules[]"
                                value="Rental"
                                id="editRental">

                                <span></span>

                                Rental

                            </label>


                            <!-- ARCHIVES -->

                            <label
                            class="module-option"
                            data-module="Archives">

                                <input
                                type="checkbox"
                                name="modules[]"
                                value="Archives"
                                id="editArchives">

                                <span></span>

                                Archives

                            </label>
                            
                        <!-- ACCOUNT -->

                        <label
                        class="module-option"
                        data-module="Account">

                            <input
                            type="checkbox"
                            name="modules[]"
                            value="Account"
                            id="editAccount">

                            <span></span>

                            Account

                        </label>


                        <!-- PASSWORD RESET -->

                        <label
                        class="module-option"
                        data-module="Password Reset">

                            <input
                            type="checkbox"
                            name="modules[]"
                            value="Password Reset"
                            id="editPasswordReset">

                            <span></span>

                            Password Reset

                        </label>

                        </div>

                        <!-- =================================================
                             LENDING PERMISSION
                        ================================================== -->

                        <div
                        id="editLendingPermissionWrapper"
                        class="lending-permission-wrapper">


                            <label>
                                Lending Permission
                            </label>


                            <div
                            class="permission-options"
                            id="editPermissionOptions">


                                <!-- LIMITED -->

                                <label class="permission-option">

                                    <input
                                    type="radio"
                                    name="lending_permission"
                                    value="Limited"
                                    id="editLimited">

                                    <span></span>

                                    Limited

                                </label>


                                <!-- FULL ACCESS -->

                                <label class="permission-option">

                                    <input
                                    type="radio"
                                    name="lending_permission"
                                    value="Full"
                                    id="editFull">

                                    <span></span>

                                    Full Access

                                </label>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     FOOTER
                ================================================== -->

                <div class="edit-account-footer">

                    <button
                    type="submit"
                    class="btn-update-account">

                        SAVE CHANGES

                    </button>

                </div>

            </form>


            <!-- =================================================
                 ARCHIVE ACCOUNT FORM
            ================================================== -->

            <form
            id="archiveAccountForm"
            action="archive_account.php"
            method="POST"
            class="d-none">


                <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $accountCsrfToken,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>">


                <input
                type="hidden"
                name="user_id"
                id="archiveUserID">

            </form>

        </div>

    </div>

</div>