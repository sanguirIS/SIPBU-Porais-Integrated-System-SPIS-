<div
class="modal fade"
id="addAccountModal"
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
                    ADD ACCOUNT
                </h2>


                <button
                type="button"
                class="account-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close">

                    ×

                </button>

            </div>


            <!-- =================================================
                 ADD ACCOUNT FORM
            ================================================== -->

            <form
            id="accountForm"
            action="save_account.php"
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
                     MODULE ACCESS VALUE
                ================================================== -->

                <input
                type="hidden"
                name="module_access"
                id="moduleAccessValue"
                value="">


                <!-- =================================================
                     ACCOUNT STATUS
                     New accounts are always Active.
                ================================================== -->

                <input
                type="hidden"
                name="status"
                id="accountStatus"
                value="Active">


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
                             NAME
                        ============================================== -->

                        <div class="account-field">

                            <label for="accountName">
                                Name
                            </label>


                            <input
                            type="text"
                            name="name"
                            id="accountName"
                            class="account-input"
                            placeholder="Enter full name"
                            autocomplete="name"
                            required>

                        </div>


                        <!-- =============================================
                             EMAIL
                        ============================================== -->

                        <div class="account-field">

                            <label for="accountEmail">
                                Email
                            </label>


                            <div class="email-wrapper">

                                <input
                                type="text"
                                name="email_name"
                                id="accountEmail"
                                class="account-input"
                                placeholder="username"
                                autocomplete="off"
                                spellcheck="false"
                                required>


                                <span class="email-domain">
                                    @spis.com
                                </span>

                            </div>

                        </div>


                        <!-- =============================================
                             PASSWORD
                        ============================================== -->

                        <div class="account-field">

                            <label for="password">
                                Password
                            </label>


                            <input
                            type="password"
                            name="password"
                            id="password"
                            class="account-input"
                            autocomplete="new-password"
                            required>


                            <!--
                                Visible immediately.
                                JS will smoothly hide this once
                                the password reaches 8 characters.
                            -->

                            <small
                            class="account-field-helper"
                            id="passwordRuleHint">

                                Password must contain at least 8 characters.

                            </small>

                        </div>


                        <!-- =============================================
                             CONFIRM PASSWORD
                        ============================================== -->

                        <div class="account-field">

                            <label for="confirmPassword">
                                Confirm Password
                            </label>


                            <input
                            type="password"
                            name="confirm_password"
                            id="confirmPassword"
                            class="account-input"
                            autocomplete="new-password"
                            required>


                            <!--
                                Live password match feedback.

                                Possible JS states later:

                                is-match
                                is-mismatch
                                is-hidden
                            -->

                            <div
                            class="account-password-feedback is-hidden"
                            id="passwordMatchFeedback"
                            aria-live="polite">

                                <i
                                class="bi bi-check-circle-fill"
                                id="passwordMatchIcon">
                                </i>

                                <span id="passwordMatchText">
                                </span>

                            </div>

                        </div>


                        <!-- =============================================
                             ROLE
                        ============================================== -->

                        <div class="account-field">

                            <label for="accountRole">
                                Role
                            </label>


                            <input
                            type="text"
                            name="role"
                            id="accountRole"
                            class="account-input"
                            placeholder="Enter role"
                            autocomplete="off"
                            required>

                        </div>


                        <!-- =============================================
                             ACTIVE BY DEFAULT NOTE
                        ============================================== -->

                        <div class="account-active-note">

                            <div class="account-active-note-icon">

                                <i class="bi bi-info-circle-fill"></i>

                            </div>


                            <div class="account-active-note-content">

                                <strong>
                                    Active by default
                                </strong>

                                <span>
                                    New accounts are automatically Active.
                                    Status can be changed later in Edit Account.
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
                            Select one or more modules this account can access.
                        </p>


                        <!-- =================================================
                             MODULE ACCESS
                        ================================================== -->

                        <div class="module-grid">


                            <!-- ALL MODULES -->

                            <label
                            class="module-option"
                            data-module="All Modules"
                            id="allModulesOption">

                                <input
                                type="checkbox"
                                id="allModules">

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
                                value="Inventory">

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
                                value="Sales">

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
                                value="Members">

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
                                id="lendingAccess">

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
                                value="Rental">

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
                                value="Archives">

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
                            value="Account">

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
                            value="Password Reset">

                            <span></span>

                            Password Reset

                        </label>

                        </div>
                        <!-- =================================================
                             LENDING PERMISSION

                             No inline display:none.
                             CSS + JS handle the smooth reveal.
                        ================================================== -->

                        <div
                        id="lendingPermissionWrapper"
                        class="lending-permission-wrapper">


                            <!--
                                JS mirror value.
                                Server still receives the checked
                                lending_permission radio itself.
                            -->

                            <input
                            type="hidden"
                            id="lendingPermission"
                            value="">


                            <label>
                                Lending Permission
                            </label>


                            <div class="permission-options">


                                <!-- LIMITED -->

                                <label class="permission-option">

                                    <input
                                    type="radio"
                                    name="lending_permission"
                                    value="Limited">

                                    <span></span>

                                    Limited

                                </label>


                                <!-- FULL ACCESS -->

                                <label class="permission-option">

                                    <input
                                    type="radio"
                                    name="lending_permission"
                                    value="Full">

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

                <div class="account-modal-footer">

                    <button
                    type="submit"
                    class="btn-save-account">

                        SAVE ACCOUNT

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>