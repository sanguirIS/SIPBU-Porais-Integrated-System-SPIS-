<?php
/* =========================================================
   SPIS - PASSWORD RESET
   Authorized password reset interface.
========================================================= */

require_once "../includes/check_access.php";
checkAccess("password_reset_access");

date_default_timezone_set("Asia/Manila");
require_once "../db.php";
mysqli_set_charset($conn, "utf8mb4");

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

/* CSRF TOKEN */
if (empty($_SESSION["password_reset_csrf_token"])) {
    $_SESSION["password_reset_csrf_token"] = bin2hex(random_bytes(32));
}

/* ONE-TIME TEMPORARY PASSWORD */
$resetResult = $_SESSION["password_reset_result"] ?? null;
unset($_SESSION["password_reset_result"]);

if (
    ($_GET["success"] ?? "") !== "reset" ||
    !is_array($resetResult) ||
    !isset($resetResult["target_name"], $resetResult["temporary_password"]) ||
    !is_string($resetResult["target_name"]) ||
    !is_string($resetResult["temporary_password"])
) {
    $resetResult = null;
}

/* ERROR MESSAGES */
$resetErrors = [
    "csrf" => "Security token expired. Please refresh the page and try again.",
    "invalid_target" => "That account cannot be reset.",
    "forbidden" => "You are not authorized to perform this reset.",
    "failed" => "Password reset failed. Please try again."
];

$resetErrorCode = $_GET["error"] ?? "";
$resetErrorMessage = is_string($resetErrorCode)
    ? ($resetErrors[$resetErrorCode] ?? "")
    : "";

/* LOAD ELIGIBLE ACCOUNTS */
$accounts = [];
$loadError = false;

try {
    $currentUserID = (int) ($_SESSION["user_id"] ?? 0);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT user_id, name, email, role
         FROM users
         WHERE archived = 0
           AND status = 'Active'
           AND user_id <> ?
         ORDER BY name ASC, user_id ASC"
    );

    if (!$stmt) {
        throw new RuntimeException("Unable to prepare account list.");
    }

    mysqli_stmt_bind_param($stmt, "i", $currentUserID);

    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException("Unable to load account list.");
    }

    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        throw new RuntimeException("Unable to read account list.");
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $accounts[] = $row;
    }

    mysqli_stmt_close($stmt);
} catch (Throwable $error) {
    error_log("SPIS password reset list: " . $error->getMessage());
    $loadError = true;
}

/* HTML ESCAPING */
function resetEscape($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

/* SPIS SIDEBAR */
include "../includes/sidebar.php";
?>

<style>
/* =========================================================
   SPIS PASSWORD RESET - LEDGER
========================================================= */
.reset-page {
    --reset-green: #8ca15b;
    --reset-green-dark: #6f8347;
    --reset-cream: #fdfbd4;
    --reset-ink: #25291f;
    padding: 4px 0 40px;
}

.reset-page-header {
    min-height: 72px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
}

.reset-title-group {
    display: flex;
    align-items: center;
    gap: 9px;
    min-width: 0;
}

.reset-page-header h2 {
    flex: 0 0 auto;
    margin: 0;
    color: #111;
    font-size: 32px;
    font-weight: 500;
    line-height: 1.2;
}

.reset-header-tools {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    min-width: 0;
}

.reset-search {
    width: 320px;
    min-width: 0;
    height: 45px;
    padding: 0 16px;
    border: 1px solid rgba(140, 161, 91, .34);
    border-radius: 12px;
    background: #fffef4;
    color: #30372a;
    font-size: 13px;
    outline: none;
}

.reset-search:focus {
    border-color: var(--reset-green);
    box-shadow: 0 0 0 4px rgba(140, 161, 91, .15);
}

.reset-count {
    flex: 0 0 auto;
    padding: 10px 15px;
    border: 1px solid rgba(140, 161, 91, .22);
    border-radius: 999px;
    background: #e9efd8;
    color: #54683a;
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
}

/* TRACTOR-STYLE HELP TOOLTIP */

.reset-help-wrap {
    display: inline-flex;
    align-items: center;
    position: relative;
    flex: 0 0 auto;
    z-index: 10;
}

.reset-help-button {
    width: 20px;
    height: 20px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: var(--reset-green);
    font-size: 18px;
    line-height: 1;
    cursor: help;
}

.reset-help-tooltip {
    position: absolute;
    top: calc(100% + 15px);
    left: 50%;
    width: min(355px, calc(100vw - 40px));
    padding: 16px 19px;
    border: 1px solid #e4e2bf;
    border-radius: 13px;
    background: #fffde9;
    color: #25301c;
    box-shadow: 0 8px 22px rgba(40, 50, 28, .16);
    font-size: 14px;
    font-weight: 400;
    line-height: 1.55;
    text-align: left;
    transform: translate(-50%, -5px);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity .15s ease, transform .15s ease, visibility .15s;
}

.reset-help-tooltip::before {
    content: "";
    position: absolute;
    top: -7px;
    left: calc(50% - 7px);
    width: 12px;
    height: 12px;
    background: #fffde9;
    border-top: 1px solid #e4e2bf;
    border-left: 1px solid #e4e2bf;
    transform: rotate(45deg);
}

.reset-help-tooltip p {
    margin: 0;
}

.reset-help-tooltip p + p {
    margin-top: 12px;
}

.reset-help-wrap:hover .reset-help-tooltip,
.reset-help-wrap:focus-within .reset-help-tooltip,
.reset-help-wrap.is-open .reset-help-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translate(-50%, 0);
}

.reset-header-divider {
    display: block;
    width: 100%;
    height: 2px;
    margin: 7px 0 12px;
    border: 0;
    border-radius: 2px;
    background: #a6b28c;
}

/* LEDGER, VERTICAL SCROLL, STICKY HEADER */

.reset-panel {
    border: 0;
    background: transparent;
}

.reset-table-wrap {
    max-height: calc(100vh - 195px);
    padding: 0 3px 6px;
    overflow-x: auto;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: #8ca15b rgba(253, 251, 212, .55);
    scrollbar-gutter: stable;
}



.reset-table {
    width: 100%;
    min-width: 750px;
    border-collapse: separate;
    border-spacing: 0 11px;
    table-layout: fixed;
}

.reset-table th,
.reset-table td {
    padding: 15px 18px;
    vertical-align: middle;
    text-align: center;
}

.reset-table th:nth-child(1) {
    width: 25%;
}

.reset-table th:nth-child(2) {
    width: 34%;
}

.reset-table th:nth-child(3) {
    width: 17%;
}

.reset-table th:nth-child(4) {
    width: 24%;
}


.reset-table thead th {
    position: sticky;
    top: 0px;
    z-index: 2;
    height: 66px;
    border: 0;
    background: #8ca15b;
    color: #fffef0;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .45px;
}


.reset-table thead th:first-child {
    border-radius: 20px 0 0 20px;
}

.reset-table thead th:last-child {
    border-radius: 0 20px 20px 0;
}

.reset-account-row {
    filter: drop-shadow(0 3px 7px rgba(63, 77, 39, .045));
    transition: filter .2s ease, transform .2s ease;
}

.reset-account-row:hover {
    filter: drop-shadow(0 7px 11px rgba(63, 77, 39, .1));
    transform: translateY(-2px);
}

.reset-table tbody .reset-account-row td {
    height: 78px;
    border-top: 1px solid rgba(140, 161, 91, .16);
    border-bottom: 1px solid rgba(140, 161, 91, .16);
    background: #fff;
    color: var(--reset-ink);
    font-size: 12.5px;
    overflow-wrap: anywhere;
    transition: background .2s ease;
}

.reset-table tbody .reset-account-row:hover td {
    background: #f8faf4;
}

.reset-table tbody .reset-account-row td:first-child {
    border-left: 4px solid rgba(140, 161, 91, .66);
    border-radius: 18px 0 0 18px;
    font-size: 13px;
    font-weight: 800;
}

.reset-table tbody .reset-account-row td:nth-child(2) {
    color: #5d6257;
}

.reset-table tbody .reset-account-row td:last-child {
    border-right: 1px solid rgba(140, 161, 91, .16);
    border-radius: 0 18px 18px 0;
}

.reset-role-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    max-width: 100%;
    padding: 7px 12px;
    border: 1px solid rgba(140, 161, 91, .24);
    border-radius: 999px;
    background: rgba(140, 161, 91, .13);
    color: #566938;
    font-size: 11px;
    font-weight: 800;
}

.reset-action-form {
    display: flex;
    justify-content: center;
    margin: 0;
}

.reset-button {
    min-height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 16px;
    border: 1px solid rgba(86, 105, 56, .18);
    border-radius: 11px;
    background: var(--reset-green);
    color: var(--reset-cream);
    box-shadow: 0 4px 10px rgba(72, 87, 45, .14);
    font-size: 12px;
    font-weight: 800;
    white-space: nowrap;
    cursor: pointer;
    transition: background .18s ease, transform .18s ease;
}

.reset-button:hover {
    background: var(--reset-green-dark);
    transform: translateY(-1px);
}

.reset-feedback {
    margin: 0 3px 16px;
    padding: 15px;
    border: 1px solid #d3dec8;
    border-radius: 14px;
    background: #f5f8f0;
}

.reset-feedback.error {
    border-color: #e8c5bc;
    background: #fff3f0;
    color: #91372a;
}

.reset-empty {
    padding: 32px 15px;
    border: 1px dashed rgba(140, 161, 91, .3);
    border-radius: 16px;
    background: rgba(255, 255, 255, .85);
    color: #697365;
    text-align: center;
}

.reset-account-row[hidden],
#resetNoMatches[hidden] {
    display: none !important;
}

/* =========================================================
   SPIS CONFIRMATION + ONE-TIME PASSWORD MODALS
========================================================= */

.reset-modal {
    width: min(510px, calc(100vw - 28px));
    max-height: calc(100vh - 28px);
    margin: auto;
    padding: 0;
    overflow: auto;
    border: 4px solid #8ca15b;
    border-radius: 24px;
    background: #4b4d46;
    color: #fdfbd4;
    box-shadow: 0 24px 65px rgba(0, 0, 0, .33);
}

.reset-modal::backdrop {
    background: rgba(24, 32, 17, .64);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.reset-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 23px 25px 18px;
    border-bottom: 1px solid rgba(253, 251, 212, .17);
}

.reset-modal-icon {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 40px;
    border-radius: 12px;
    background: rgba(140, 161, 91, .25);
    color: #d3e8a1;
    font-size: 19px;
}

.reset-modal-header h3 {
    margin: 0;
    color: #fdfbd4;
    font-size: 20px;
    font-weight: 800;
    letter-spacing: .3px;
}

.reset-modal-body {
    padding: 22px 25px;
    font-size: 14px;
    line-height: 1.65;
    color: #f6f2d5;
}

.reset-modal-body p {
    margin: 0 0 13px;
}

.reset-modal-body p:last-child {
    margin-bottom: 0;
}

.reset-modal-body strong {
    color: #fffde7;
}

.reset-modal-note {
    padding: 12px 14px;
    border: 1px solid rgba(183, 219, 127, .24);
    border-radius: 11px;
    background: rgba(140, 161, 91, .13);
    color: #fdfbd4;
    font-size: 12px;
}

.reset-modal-footer {
    display: flex;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 10px;
    padding: 16px 25px 21px;
    border-top: 1px solid rgba(253, 251, 212, .17);
}

.reset-modal-action {
    min-height: 43px;
    padding: 0 18px;
    border: 1px solid transparent;
    border-radius: 11px;
    font-size: 13px;
    font-weight: 800;
    cursor: pointer;
}

.reset-modal-action.secondary {
    color: #fdfbd4;
    background: rgba(253, 251, 212, .1);
    border-color: rgba(253, 251, 212, .22);
}

.reset-modal-action.primary {
    color: #fffde7;
    background: #8ca15b;
}

.reset-modal-action.primary:hover {
    background: #6f8347;
}

.reset-modal-action:disabled {
    opacity: .6;
    cursor: wait;
}

.reset-secret-row {
    display: flex;
    align-items: stretch;
    gap: 9px;
    margin: 14px 0 16px;
}

.reset-secret {
    flex: 1 1 auto;
    min-width: 0;
    display: flex;
    align-items: center;
    padding: 12px;
    border: 1px solid #cbd3b1;
    border-radius: 10px;
    background: #fffde7;
    color: #25301c;
    font-family: Consolas, monospace;
    font-size: 14px;
    overflow-wrap: anywhere;
    user-select: all;
}

.reset-copy-button {
    flex: 0 0 auto;
    padding: 0 13px;
    border: 1px solid rgba(183, 219, 127, .3);
    border-radius: 10px;
    background: #8ca15b;
    color: #fffde7;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
}

.reset-copy-button:hover {
    background: #6f8347;
}

.reset-help-button:focus-visible,
.reset-button:focus-visible,
.reset-copy-button:focus-visible,
.reset-modal-action:focus-visible {
    outline: 3px solid rgba(183, 219, 127, .65);
    outline-offset: 3px;
}

/* RESPONSIVE DESIGN */

@media (max-width: 1050px) {
    .reset-page-header {
        flex-wrap: wrap;
        gap: 14px;
        padding-bottom: 6px;
    }

    .reset-header-tools {
        flex: 1 1 100%;
        justify-content: flex-start;
        flex-wrap: wrap;
    }

    .reset-search {
        flex: 1 1 220px;
        width: auto;
    }
}

@media (max-width: 650px) {
    .reset-page {
        padding: 6px 0 30px;
    }

    .reset-page-header h2 {
        font-size: 25px;
    }

    .reset-table {
        min-width: 750px;
    }

    .reset-modal-header {
        padding: 18px;
    }

    .reset-modal-body {
        padding: 18px;
    }

    .reset-modal-footer {
        padding: 14px 18px;
    }

    .reset-secret-row {
        flex-direction: column;
    }

    .reset-copy-button {
        min-height: 40px;
    }
}

/* FIXED HEADER / ROWS-ONLY SCROLL */

.reset-table-shell {
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
}

.reset-table-head,
.reset-table-wrap {
    min-width: 750px;
    box-sizing: border-box;
    scrollbar-width: thin;
    scrollbar-gutter: stable;
}

.reset-table-head {
    padding: 11px 3px 0;
    overflow: hidden;
}

.reset-table-head .reset-table {
    border-spacing: 0;
}

.reset-table-head .reset-table thead th {
    position: static;
    top: auto;
}


.reset-table-wrap {
    max-height: calc(100vh - 245px);
    overflow-x: hidden;
    overflow-y: auto;
}


</style>

<main class="reset-page">

    <header class="reset-page-header">

        <div class="reset-title-group">

            <h2>PASSWORD RESET</h2>

            <span class="reset-help-wrap" id="resetHelpWrap">

                <button
                    type="button"
                    class="reset-help-button"
                    id="resetHelpButton"
                    aria-label="Password Reset guide"
                    aria-describedby="resetHelpTooltip"
                    aria-expanded="false">

                    <i
                        class="bi bi-question-circle-fill"
                        aria-hidden="true"></i>

                </button>

                <span
                    class="reset-help-tooltip"
                    id="resetHelpTooltip"
                    role="tooltip">

                    <p>
                        <strong>Verified requests:</strong>
                        Confirm the account owner's request before
                        resetting their password.
                    </p>

                    <p>
                        <strong>Temporary password:</strong>
                        Share it privately. The user must change it
                        at the next login.
                    </p>

                </span>

            </span>

        </div>

        <div class="reset-header-tools">

            <input
                type="search"
                id="resetSearch"
                class="reset-search"
                placeholder="Search name, email, or role"
                aria-label="Search accounts"
                <?= $loadError ? "disabled" : "" ?>
            >

            <span class="reset-count" id="resetCount">
                <?= count($accounts) ?> account(s)
            </span>

        </div>

    </header>

    <hr class="reset-header-divider">

    <section
        class="reset-panel"
        aria-label="Accounts for password reset">

        <?php if ($resetErrorMessage !== ""): ?>

            <div class="reset-feedback error" role="alert">
                <?= resetEscape($resetErrorMessage) ?>
            </div>

        <?php endif; ?>

        <div class="reset-table-shell">

            <!-- FIXED LEDGER HEADER -->
            <div class="reset-table-head">

                <table class="reset-table">
                    <colgroup>
                        <col style="width:25%">
                        <col style="width:34%">
                        <col style="width:17%">
                        <col style="width:24%">
                    </colgroup>

                    <thead>
                        <tr>
                            <th scope="col">NAME</th>
                            <th scope="col">EMAIL</th>
                            <th scope="col">ROLE</th>
                            <th scope="col">ACTION</th>
                        </tr>
                    </thead>

                </table>
            </div>

            <!-- SCROLLABLE ACCOUNT ROWS -->
            <div class="reset-table-wrap">

                <table
                    class="reset-table"
                    aria-label="Accounts for password reset">

                    <colgroup>
                        <col style="width:25%">
                        <col style="width:34%">
                        <col style="width:17%">
                        <col style="width:24%">
                    </colgroup>

                    <tbody id="resetAccountRows">


                    <?php if ($loadError): ?>

                        <tr>
                            <td colspan="4" class="reset-empty">
                                Unable to load accounts.
                            </td>
                        </tr>

                    <?php elseif (empty($accounts)): ?>

                        <tr>
                            <td colspan="4" class="reset-empty">
                                No eligible accounts found.
                            </td>
                        </tr>

                    <?php else: ?>

                        <?php foreach ($accounts as $account): ?>

                            <tr class="reset-account-row">

                                <td>
                                    <?= resetEscape($account["name"]) ?>
                                </td>

                                <td>
                                    <?= resetEscape($account["email"]) ?>
                                </td>

                                <td>
                                    <span class="reset-role-badge">
                                        <?= resetEscape($account["role"]) ?>
                                    </span>
                                </td>

                                <td>

                                    <form
                                        method="POST"
                                        action="process_password_reset.php"
                                        class="reset-action-form"
                                        data-account-name="<?= resetEscape(
                                            $account["name"]
                                        ) ?>">

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= resetEscape(
                                                $_SESSION["password_reset_csrf_token"]
                                            ) ?>">

                                        <input
                                            type="hidden"
                                            name="target_user_id"
                                            value="<?= (int) $account["user_id"] ?>">

                                        <button
                                            type="submit"
                                            class="reset-button">

                                            <i
                                                class="bi bi-key-fill"
                                                aria-hidden="true"></i>

                                            Reset Password

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php endif; ?>

                    <?php if (!$loadError && !empty($accounts)): ?>

                        <tr id="resetNoMatches" hidden>
                            <td colspan="4" class="reset-empty">
                                No matching accounts.
                            </td>
                        </tr>

                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </div>

    </section>


</main>

<!-- CONFIRMATION MODAL -->

<dialog
    id="resetConfirmDialog"
    class="reset-modal"
    aria-labelledby="resetConfirmTitle"
    aria-describedby="resetConfirmDescription">

    <div class="reset-modal-header">

        <span class="reset-modal-icon" aria-hidden="true">
            <i class="bi bi-shield-lock-fill"></i>
        </span>

        <h3 id="resetConfirmTitle">
            RESET PASSWORD
        </h3>

    </div>

    <div class="reset-modal-body">

        <p id="resetConfirmDescription">
            Are you sure you want to reset the password for
            <strong id="resetConfirmAccountName"></strong>?
        </p>

        <div class="reset-modal-note">
            Their current password will immediately stop working.
            A temporary password will be generated, and the user must
            change it on their next login.
        </div>

    </div>

    <div class="reset-modal-footer">

        <button
            type="button"
            id="resetCancelButton"
            class="reset-modal-action secondary">
            Cancel
        </button>

        <button
            type="button"
            id="resetConfirmButton"
            class="reset-modal-action primary">
            Confirm Reset
        </button>

    </div>

</dialog>

<!-- SUCCESS MODAL: ONLY AFTER A SUCCESSFUL SERVER RESET -->

<?php if ($resetResult !== null): ?>

    <dialog
        id="resetSuccessDialog"
        class="reset-modal"
        aria-labelledby="resetSuccessTitle"
        aria-describedby="resetSuccessDescription">

        <div class="reset-modal-header">

            <span class="reset-modal-icon" aria-hidden="true">
                <i class="bi bi-check-circle-fill"></i>
            </span>

            <h3 id="resetSuccessTitle">
                PASSWORD RESET SUCCESSFUL
            </h3>

        </div>

        <div class="reset-modal-body">

            <p id="resetSuccessDescription">
                Temporary password for
                <strong>
                    <?= resetEscape($resetResult["target_name"]) ?>
                </strong>:
            </p>

            <div class="reset-secret-row">

                <code
                    id="resetTempPassword"
                    class="reset-secret"><?= resetEscape(
                        $resetResult["temporary_password"]
                    ) ?></code>

                <button
                    type="button"
                    id="resetCopyPassword"
                    class="reset-copy-button">

                    <i
                        class="bi bi-copy"
                        aria-hidden="true"></i>

                    Copy

                </button>

            </div>

            <div class="reset-modal-note">
                This password is shown only once. Copy it before closing
                and share it privately with the account owner.
                Do not include it in History or screenshots.
            </div>

        </div>

        <div class="reset-modal-footer">

            <button
                type="button"
                id="resetSuccessDone"
                class="reset-modal-action primary">
                Done
            </button>

        </div>

    </dialog>

<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    /* TRACTOR-STYLE HELP TOOLTIP */

    const helpButton =
        document.getElementById("resetHelpButton");

    const helpWrap =
        document.getElementById("resetHelpWrap");

    if (helpButton && helpWrap) {

        function setHelpOpen(open) {
            helpWrap.classList.toggle("is-open", open);
            helpButton.setAttribute(
                "aria-expanded",
                String(open)
            );
        }

        helpButton.addEventListener("click", function () {
            setHelpOpen(
                !helpWrap.classList.contains("is-open")
            );
        });

        document.addEventListener("click", function (event) {
            if (!helpWrap.contains(event.target)) {
                setHelpOpen(false);
            }
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                setHelpOpen(false);
            }
        });
    }

    /* CUSTOM RESET CONFIRMATION MODAL */

    const confirmDialog =
        document.getElementById("resetConfirmDialog");

    const confirmName =
        document.getElementById("resetConfirmAccountName");

    const cancelButton =
        document.getElementById("resetCancelButton");

    const confirmButton =
        document.getElementById("resetConfirmButton");

    let pendingForm = null;

    document.querySelectorAll(".reset-action-form").forEach(
        function (form) {

            form.addEventListener("submit", function (event) {

                event.preventDefault();

                if (!confirmDialog || !confirmName) {
                    return;
                }

                pendingForm = form;

                confirmName.textContent =
                    form.dataset.accountName || "this account";

                confirmDialog.showModal();
            });
        }
    );

    if (confirmDialog && cancelButton && confirmButton) {

        cancelButton.addEventListener("click", function () {
            confirmDialog.close();
        });

        confirmDialog.addEventListener("close", function () {
            pendingForm = null;
            confirmButton.disabled = false;
        });

        confirmButton.addEventListener("click", function () {

            if (!pendingForm || confirmButton.disabled) {
                return;
            }

            confirmButton.disabled = true;

            /* PHP will validate CSRF and live permissions. */
            pendingForm.submit();
        });
    }

    /* ONE-TIME TEMPORARY PASSWORD SUCCESS MODAL */

    const successDialog =
        document.getElementById("resetSuccessDialog");

    const successDone =
        document.getElementById("resetSuccessDone");

    const tempPassword =
        document.getElementById("resetTempPassword");

    const copyButton =
        document.getElementById("resetCopyPassword");

    if (successDialog && successDone && tempPassword) {

        successDialog.addEventListener("cancel", function (event) {

            /* Prevent accidental loss using Escape. */
            event.preventDefault();
        });

        successDialog.showModal();

        successDone.addEventListener("click", function () {

            successDialog.close();

            /* Remove the temporary password from the page. */
            tempPassword.textContent = "";

            if (copyButton) {
                copyButton.disabled = true;
            }
        });
    }

    if (copyButton && tempPassword) {

        copyButton.addEventListener("click", async function () {

            try {

                await navigator.clipboard.writeText(
                    tempPassword.textContent.trim()
                );

                copyButton.textContent = "Copied!";

            } catch (error) {

                copyButton.textContent =
                    "Select the password to copy manually";
            }
        });
    }

    /* SEARCH BY NAME, EMAIL, OR ROLE */

    const search =
        document.getElementById("resetSearch");

    const rows = Array.from(
        document.querySelectorAll(".reset-account-row")
    );

    const count =
        document.getElementById("resetCount");

    const empty =
        document.getElementById("resetNoMatches");

    if (!search || rows.length === 0) {
        return;
    }

    search.addEventListener("input", function () {

        const query =
            search.value.trim().toLocaleLowerCase();

        let visible = 0;

        rows.forEach(function (row) {

            const match =
                row.textContent
                    .toLocaleLowerCase()
                    .includes(query);

            row.hidden = !match;

            if (match) {
                visible++;
            }
        });

        count.textContent =
            visible + " account(s)";

        if (empty) {
            empty.hidden = visible !== 0;
        }
    });
});
</script>

</div>
</div>
</body>
</html>