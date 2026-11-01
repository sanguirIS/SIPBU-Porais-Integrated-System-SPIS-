<?php
/* SPIS - REQUIRED PASSWORD CHANGE */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

$userID = (int) ($_SESSION['user_id'] ?? 0);

if ($userID <= 0) {
    header('Location: /spis/login.php');
    exit;
}

require_once __DIR__ . '/db.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');


/* =========================================================
   LOGOUT HELPER
========================================================= */

function requiredPasswordLogout(): void {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]);
    }

    session_destroy();
}


/* =========================================================
   VERIFY ACCOUNT
========================================================= */

try {

    $stmt = mysqli_prepare(
        $conn,

        'SELECT status, archived, must_change_password,
                password
         FROM users
         WHERE user_id = ?
         LIMIT 1'

    );

    mysqli_stmt_bind_param(
        $stmt,
        'i',
        $userID
    );

    mysqli_stmt_execute($stmt);

    $account = mysqli_fetch_assoc(
        mysqli_stmt_get_result($stmt)
    );

    mysqli_stmt_close($stmt);

} catch (Throwable $exception) {

    error_log(
        'SPIS required password access: ' .
        $exception->getMessage()
    );

    http_response_code(503);
    exit('Unable to verify account.');
}


/* =========================================================
   ACCOUNT STATUS PROTECTION
========================================================= */

if (
    !$account ||
    (int) $account['archived'] !== 0 ||
    $account['status'] !== 'Active'
) {

    requiredPasswordLogout();

    header('Location: /spis/login.php');
    exit;
}



/* =========================================================
   VERIFY SESSION AGAINST CURRENT PASSWORD
========================================================= */

$sessionPasswordVersion =
    $_SESSION['password_version'] ?? null;

$currentPasswordVersion = hash(
    'sha256',
    (string) ($account['password'] ?? '')
);

if (
    !is_string($sessionPasswordVersion) ||
    !hash_equals(
        $currentPasswordVersion,
        $sessionPasswordVersion
    )
) {

    requiredPasswordLogout();

    header('Location: /spis/login.php', true, 303);
    exit;
}


/* =========================================================
   REQUIRE RESET FLAG
========================================================= */


if ((int) $account['must_change_password'] !== 1) {

    header('Location: /spis/dashboard.php');
    exit;
}


/* =========================================================
   CSRF TOKEN
========================================================= */

if (empty($_SESSION['required_password_csrf'])) {

    $_SESSION['required_password_csrf'] =
        bin2hex(random_bytes(32));
}

$errorMessage = '';


/* =========================================================
   PROCESS PASSWORD CHANGE
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedToken = $_POST['csrf_token'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (
        !is_string($postedToken) ||
        !hash_equals(
            $_SESSION['required_password_csrf'],
            $postedToken
        )
    ) {

        $errorMessage =
            'Your session expired. Refresh the page and try again.';

    } elseif (
        !is_string($newPassword) ||
        !is_string($confirmPassword)
    ) {

        $errorMessage = 'Invalid password input.';

    } elseif (
        strlen($newPassword) < 8 ||
        strlen($newPassword) > 72
    ) {

        $errorMessage =
            'Use a password between 8 and 72 bytes.';

    } elseif (!hash_equals($newPassword, $confirmPassword)) {

        $errorMessage = 'Passwords do not match.';

    } else {

        $transactionStarted = false;

        try {

            mysqli_begin_transaction($conn);
            $transactionStarted = true;

            $lockStmt = mysqli_prepare(
                $conn,
                'SELECT password, status, archived,
                        must_change_password
                 FROM users
                 WHERE user_id = ?
                 LIMIT 1
                 FOR UPDATE'
            );

            mysqli_stmt_bind_param(
                $lockStmt,
                'i',
                $userID
            );

            mysqli_stmt_execute($lockStmt);

            $lockedAccount = mysqli_fetch_assoc(
                mysqli_stmt_get_result($lockStmt)
            );

            mysqli_stmt_close($lockStmt);

            if (
                !$lockedAccount ||
                (int) $lockedAccount['archived'] !== 0 ||
                $lockedAccount['status'] !== 'Active' ||
                (int) $lockedAccount['must_change_password'] !== 1
            ) {

                throw new RuntimeException(
                    'Account no longer eligible for password change.'
                );
            }


            /* Re-check the version while holding the user row lock. */
            if (
                !hash_equals(
                    (string) $_SESSION['password_version'],
                    hash('sha256', (string) $lockedAccount['password'])
                )
            ) {
                throw new RuntimeException(
                    'Password changed during the request.'
                );
            }

            if (
                password_verify(
                    $newPassword,
                    (string) $lockedAccount['password']
                )
            ) {


                $errorMessage =
                    'Choose a different password from the temporary password.';

                mysqli_rollback($conn);
                $transactionStarted = false;

            } else {

                $newHash = password_hash(
                    $newPassword,
                    PASSWORD_DEFAULT
                );

                if (!is_string($newHash)) {
                    throw new RuntimeException(
                        'Password hashing failed.'
                    );
                }

                $updateStmt = mysqli_prepare(
                    $conn,
                    "UPDATE users
                     SET password = ?,
                         must_change_password = 0
                     WHERE user_id = ?
                       AND must_change_password = 1
                       AND archived = 0
                       AND status = 'Active'"
                );

                mysqli_stmt_bind_param(
                    $updateStmt,
                    'si',
                    $newHash,
                    $userID
                );

                mysqli_stmt_execute($updateStmt);

                $affected = mysqli_stmt_affected_rows(
                    $updateStmt
                );

                mysqli_stmt_close($updateStmt);

                if ($affected !== 1) {

                    throw new RuntimeException(
                        'Password update failed.'
                    );
                }

                mysqli_commit($conn);
                $transactionStarted = false;

                requiredPasswordLogout();

                header(
                    'Location: /spis/login.php?password_changed=1'
                );

                exit;
            }

        } catch (Throwable $exception) {

            if ($transactionStarted) {
                mysqli_rollback($conn);
            }

            error_log(
                'SPIS required password change: ' .
                $exception->getMessage()
            );

            $errorMessage =
                'Unable to update your password. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <title>SPIS | Change Password Required</title>

    <link
        rel="stylesheet"
        href="/spis/css/fonts.css">

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #35432f;
            background: #f4f6f0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .password-card {
            width: 100%;
            max-width: 470px;
            background: #fff;
            border: 1px solid #e1e7da;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 16px 46px rgba(37, 58, 28, .08);
        }

        .brand {
            color: #71884c;
            font-weight: 800;
            letter-spacing: .09em;
            font-size: 12px;
        }

        h1 {
            font-size: 26px;
            margin: 12px 0;
        }

        p {
            color: #6a7663;
            line-height: 1.55;
        }

        label {
            display: block;
            margin: 17px 0 7px;
            font-weight: 700;
            font-size: 14px;
        }

        input {
            display: block;
            width: 100%;
            padding: 12px;
            border: 1px solid #c5d0be;
            border-radius: 9px;
            font: inherit;
        }

        input:focus {
            outline: 2px solid #8ca15b;
            outline-offset: 1px;
        }

        .hint {
            font-size: 12px;
            margin-top: 6px;
        }

        button {
            margin-top: 24px;
            width: 100%;
            background: #748d43;
            color: #fff;
            padding: 13px;
            border: 0;
            border-radius: 9px;
            font-weight: 800;
            cursor: pointer;
        }

        button:hover {
            background: #607a33;
        }

        .error {
            padding: 12px;
            margin-top: 15px;
            background: #fff0ed;
            border: 1px solid #f0c8bd;
            border-radius: 8px;
            color: #8d2e1a;
        }

    </style>

</head>

<body>

    <main class="password-card">

        <div class="brand">
            SIPBU-PORAIS INTEGRATED SYSTEM
        </div>

        <h1>Change Password Required</h1>

        <p>
            Your account is using a temporary password.
            Create a new password to continue using SPIS.
            After saving, you will need to log in again.
        </p>

        <?php if ($errorMessage !== ''): ?>

            <div class="error" role="alert">

                <?= htmlspecialchars(
                    $errorMessage,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>

            </div>

        <?php endif; ?>

        <form
            method="POST"
            action="/spis/change_password_required.php"
            autocomplete="off">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['required_password_csrf'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">

            <label for="newPassword">
                New Password
            </label>

            <input
                type="password"
                id="newPassword"
                name="new_password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required>

            <div class="hint">
                At least 8 characters.
                Use a password different from the temporary one.
            </div>

            <label for="confirmPassword">
                Confirm New Password
            </label>

            <input
                type="password"
                id="confirmPassword"
                name="confirm_password"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required>

            <button type="submit">
                Save New Password
            </button>

        </form>

    </main>

</body>
</html>
