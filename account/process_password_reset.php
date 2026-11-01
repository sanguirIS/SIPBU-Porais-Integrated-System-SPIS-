<?php

/* =========================================================
   SPIS - PROCESS PASSWORD RESET
   Only an authorized user may reset another user's password.
========================================================= */

require_once '../includes/check_access.php';
checkAccess('password_reset_access');

require_once '../db.php';
require_once '../includes/history_logger.php';

date_default_timezone_set('Asia/Manila');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
mysqli_set_charset($conn, 'utf8mb4');

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function passwordResetRedirect(string $type, string $code): never
{
    header(
        'Location: password_reset.php?' .
        rawurlencode($type) . '=' . rawurlencode($code),
        true,
        303
    );
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method Not Allowed.');
}

$actorID = (int) ($_SESSION['user_id'] ?? 0);
$targetID = filter_var(
    $_POST['target_user_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$sessionToken = $_SESSION['password_reset_csrf_token'] ?? null;
$formToken = $_POST['csrf_token'] ?? null;

if (
    !is_string($sessionToken) ||
    !is_string($formToken) ||
    $sessionToken === '' ||
    $formToken === '' ||
    !hash_equals($sessionToken, $formToken)
) {
    passwordResetRedirect('error', 'csrf');
}

if (
    $actorID <= 0 ||
    $targetID === false ||
    $targetID === $actorID
) {
    passwordResetRedirect('error', 'invalid_target');
}

$transactionStarted = false;

try {
    mysqli_begin_transaction($conn);
    $transactionStarted = true;

    /* Re-check authorization while holding the actor row lock. */
    $actorStmt = mysqli_prepare(
        $conn,
        "SELECT password, password_reset_access, must_change_password,
                status, archived
         FROM users
         WHERE user_id = ?
         LIMIT 1
         FOR UPDATE"
    );
    mysqli_stmt_bind_param($actorStmt, 'i', $actorID);
    mysqli_stmt_execute($actorStmt);
    $actor = mysqli_fetch_assoc(mysqli_stmt_get_result($actorStmt));
    mysqli_stmt_close($actorStmt);

    if (
        !$actor ||
        (int) $actor['archived'] !== 0 ||
        $actor['status'] !== 'Active' ||
        (int) $actor['password_reset_access'] !== 1 ||
        (int) $actor['must_change_password'] !== 0
    ) {
        throw new DomainException('Reset permission unavailable.');
    }

    $sessionVersion = $_SESSION['password_version'] ?? null;
    if (
        !is_string($sessionVersion) ||
        !hash_equals(
            hash('sha256', (string) $actor['password']),
            $sessionVersion
        )
    ) {
        throw new DomainException('Reset session is no longer valid.');
    }

    /* Validate the target under lock: no self-reset or inactive users. */
    $targetStmt = mysqli_prepare(
        $conn,
        "SELECT user_id, name, email, status, archived
         FROM users
         WHERE user_id = ?
         LIMIT 1
         FOR UPDATE"
    );
    mysqli_stmt_bind_param($targetStmt, 'i', $targetID);
    mysqli_stmt_execute($targetStmt);
    $target = mysqli_fetch_assoc(mysqli_stmt_get_result($targetStmt));
    mysqli_stmt_close($targetStmt);

    if (
        !$target ||
        (int) $target['archived'] !== 0 ||
        $target['status'] !== 'Active' ||
        (int) $target['user_id'] === $actorID
    ) {
        throw new InvalidArgumentException('Target is not eligible.');
    }


    /* Generate an 8-character temporary password. */

    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    $temporaryPassword = '';

    for ($i = 0; $i < 8; $i++) {
        $temporaryPassword .= $alphabet[
            random_int(0, strlen($alphabet) - 1)
        ];
    }

    $temporaryHash = password_hash(
        $temporaryPassword,
        PASSWORD_DEFAULT
    );


    $updateStmt = mysqli_prepare(
        $conn,
        "UPDATE users
         SET password = ?, must_change_password = 1
         WHERE user_id = ?
           AND archived = 0
           AND status = 'Active'"
    );
    mysqli_stmt_bind_param(
        $updateStmt,
        'si',
        $temporaryHash,
        $targetID
    );
    mysqli_stmt_execute($updateStmt);
    $updatedRows = mysqli_stmt_affected_rows($updateStmt);
    mysqli_stmt_close($updateStmt);

    if ($updatedRows !== 1) {
        throw new RuntimeException('Could not update the target password.');
    }

    /* No plaintext temporary password is included in History. */
    $targetName = str_replace(
        ["\r", "\n"],
        ' ',
        (string) $target['name']
    );
    $historyMessage =
        'reset the password for account "' . $targetName .
        '" (' . $target['email'] .
        '). A temporary password was issued; a password change is required.';

    if (log_activity(
        $conn,
        'Account',
        'Password Reset',
        $historyMessage,
        $targetID
    ) !== true) {
        throw new RuntimeException('Unable to record password reset History.');
    }

    mysqli_commit($conn);
    $transactionStarted = false;

    /* Show the temporary password only once on the next authorized page. */
    $_SESSION['password_reset_result'] = [
        'target_name' => (string) $target['name'],
        'temporary_password' => $temporaryPassword
    ];
    $_SESSION['password_reset_csrf_token'] = bin2hex(random_bytes(32));

    passwordResetRedirect('success', 'reset');

} catch (Throwable $error) {
    if ($transactionStarted) {
        try {
            mysqli_rollback($conn);
        } catch (Throwable $rollbackError) {
            error_log('SPIS password reset rollback failed.');
        }
    }

    error_log('SPIS password reset failed: ' . $error->getMessage());

    if ($error instanceof DomainException) {
        passwordResetRedirect('error', 'forbidden');
    }
    if ($error instanceof InvalidArgumentException) {
        passwordResetRedirect('error', 'invalid_target');
    }
    passwordResetRedirect('error', 'failed');
}
