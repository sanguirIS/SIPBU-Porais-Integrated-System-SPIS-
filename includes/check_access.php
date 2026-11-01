<?php

if (
    session_status() === PHP_SESSION_NONE
) {
    session_start();
}


/* =========================================================
   LIVE MODULE ACCESS
========================================================= */

function checkAccess($module){

    $allowedModules = [
        'inventory_access',
        'sales_access',
        'members_access',
        'lending_access',
        'rental_access',
        'archives_access',
        'account_access',
        'password_reset_access'
    ];

    if (
        !in_array(
            $module,
            $allowedModules,
            true
        )
    ) {

        http_response_code(403);

        exit('Access Denied.');
    }


    $userID =
        isset($_SESSION['user_id'])
            ? (int) $_SESSION['user_id']
            : 0;


    if ($userID <= 0) {

        header('Location: ../login.php');

        exit;
    }


    global $conn;


    if (
        !isset($conn)
        ||
        !($conn instanceof mysqli)
    ) {

        require dirname(__DIR__) . '/db.php';
    }


    mysqli_set_charset(
        $conn,
        'utf8mb4'
    );


    $accessStmt =
        mysqli_prepare(
            $conn,
            "

            SELECT
                inventory_access,
                sales_access,
                members_access,
                lending_access,
                rental_access,

                archives_access,
                account_access,

                password_reset_access,
                must_change_password,
                password,
                lending_permission


            FROM users

            WHERE user_id = ?

            AND archived = 0

            AND status = 'Active'

            LIMIT 1
            "
        );


    if (!$accessStmt) {

        http_response_code(500);

        exit(
            'Unable to verify account access.'
        );
    }


    mysqli_stmt_bind_param(
        $accessStmt,
        'i',
        $userID
    );


    if (!mysqli_stmt_execute($accessStmt)) {

        mysqli_stmt_close(
            $accessStmt
        );

        http_response_code(500);

        exit(
            'Unable to verify account access.'
        );
    }


    $accessResult =
        mysqli_stmt_get_result(
            $accessStmt
        );


    $currentAccess =
        $accessResult
            ? mysqli_fetch_assoc($accessResult)
            : null;


    mysqli_stmt_close(
        $accessStmt
    );


    if (!$currentAccess) {

        $_SESSION = [];

        session_destroy();

        header('Location: ../login.php');

        exit;
    }




    /* =====================================================
       VERIFY CURRENT PASSWORD VERSION
    ===================================================== */

    $sessionPasswordVersion =
        $_SESSION['password_version'] ?? null;

    $currentPasswordVersion = hash(
        'sha256',
        (string) ($currentAccess['password'] ?? '')
    );

    if (
        !is_string($sessionPasswordVersion)
        ||
        !hash_equals(
            $currentPasswordVersion,
            $sessionPasswordVersion
        )
    ) {

        $_SESSION = [];

        session_destroy();

        header(
            'Location: /spis/login.php',
            true,
            303
        );

        exit;
    }


    /* =====================================================
       REQUIRE PASSWORD CHANGE BEFORE MODULE ACCESS
    ===================================================== */


    $_SESSION['must_change_password'] =
        (int) (
            $currentAccess['must_change_password']
            ?? 0
        );

    if ($_SESSION['must_change_password'] === 1) {

        header(
            'Location: /spis/change_password_required.php',
            true,
            303
        );

        exit;
    }


    foreach (
        $allowedModules
        as
        $accessColumn
    ) {


        $_SESSION[$accessColumn] =
            (int) (
                $currentAccess[$accessColumn]
                ?? 0
            );
    }


    $_SESSION['lending_permission'] =
        (string) (
            $currentAccess['lending_permission']
            ?? 'Limited'
        );


    if (
        (int) (
            $_SESSION[$module]
            ?? 0
        ) !== 1
    ) {

        http_response_code(403);

        echo "
        <script>

        alert('Access Denied.');

        window.location='../login.php';

        </script>
        ";

        exit;
    }
}