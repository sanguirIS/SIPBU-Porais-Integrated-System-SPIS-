<?php

/* =========================================================
   SPIS LOGIN
========================================================= */

if (
    session_status()
    !==
    PHP_SESSION_ACTIVE
) {

    session_start();

}


/* =========================================================
   TIMEZONE / ERROR HANDLING
========================================================= */

date_default_timezone_set(
    "Asia/Manila"
);


error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);


/* =========================================================
   DATABASE
========================================================= */

require_once "db.php";


mysqli_report(
    MYSQLI_REPORT_ERROR
    |
    MYSQLI_REPORT_STRICT
);


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   LOGIN CSRF TOKEN
========================================================= */

if (
    empty(
        $_SESSION[
            "login_csrf_token"
        ]
    )
) {

    $_SESSION[
        "login_csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );

}


/* =========================================================
   VARIABLES
========================================================= */

$error =
    "";


$emailValue =
    "";


/* =========================================================
   LOGIN REQUEST
========================================================= */

if (
    $_SERVER[
        "REQUEST_METHOD"
    ]
    ===
    "POST"
) {


    /* =====================================================
       CSRF VALIDATION
    ===================================================== */

    $sessionToken =
        $_SESSION[
            "login_csrf_token"
        ]
        ??
        "";


    $formToken =
        $_POST[
            "csrf_token"
        ]
        ??
        "";


    if (
        !is_string(
            $sessionToken
        )
        ||
        !is_string(
            $formToken
        )
        ||
        $sessionToken === ""
        ||
        $formToken === ""
        ||
        !hash_equals(
            $sessionToken,
            $formToken
        )
    ) {

        $error =
            "Your login session expired. Please try again.";

    }
    else {


        /* =================================================
           FORM VALUES
        ================================================= */
        
        $emailValue =
            trim(
                (string) (
                    $_POST["email"]
                    ??
                    ""
                )
            );

        /*
         * Do NOT trim passwords.
         *
         * Spaces may intentionally be part
         * of a user's password.
         */

        $password =
            (string) (
                $_POST[
                    "password"
                ]
                ??
                ""
            );


        /* =================================================
           BASIC VALIDATION
        ================================================= */

        if (
            $emailValue === ""
            ||
            $password === ""
        ) {

            $error =
                "Enter your email and password.";

        }
        elseif (
            !filter_var(
                $emailValue,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            $error =
                "Enter a valid email address.";

        }
        else {


            try {


            /* =============================================
            LOGIN RATE LIMIT
            ============================================= */

            $clientIp =
                (string) (
                    $_SERVER[
                        "REMOTE_ADDR"
                    ]
                    ??
                    ""
                );


            if (
                $clientIp === ""
                ||
                strlen(
                    $clientIp
                )
                >
                45
            ) {

                $clientIp =
                    "unknown";

            }
     
            $emailHash =
                hash(
                    "sha256",
                    strtolower($emailValue)
                );

            /*
            * Remove expired failed-login records.
            */

            mysqli_query(
                $conn,
                "
                DELETE FROM login_attempts
                WHERE attempted_at
                    <
                    DATE_SUB(
                        NOW(),
                        INTERVAL 1 DAY
                    )
                "
            );


            $attemptCheckStmt =
                mysqli_prepare(
                    $conn,
                    "
                    SELECT COUNT(*) AS failed_count

                    FROM login_attempts

                    WHERE email_hash = ?
                    AND ip_address = ?
                    AND attempted_at
                            >=
                            DATE_SUB(
                                NOW(),
                                INTERVAL 1 MINUTE
                            )
                    "
                );


            mysqli_stmt_bind_param(
                $attemptCheckStmt,
                "ss",
                $emailHash,
                $clientIp
            );


            mysqli_stmt_execute(
                $attemptCheckStmt
            );


            $attemptCheckResult =
                mysqli_stmt_get_result(
                    $attemptCheckStmt
                );


            $attemptRow =
                mysqli_fetch_assoc(
                    $attemptCheckResult
                );


            mysqli_stmt_close(
                $attemptCheckStmt
            );


            $failedAttempts =
                (int) (
                    $attemptRow[
                        "failed_count"
                    ]
                    ??
                    0
                );


            if (
                $failedAttempts >= 5
            ) {

                $error =
                    "Too many failed login attempts. Please wait 1 minute and try again.";

            }
            else {


                /* =============================================
                FIND ACCOUNT
                ============================================= */

                $userStmt =
                    mysqli_prepare(

                        $conn,

                        "
                        SELECT

                            user_id,
                            name,
                            email,
                            password,
                            role,
                            status,
                            archived,

                            inventory_access,
                            sales_access,
                            members_access,
                            lending_access,
                            rental_access,

                            archives_access,
                            account_access,
                            password_reset_access,
                            must_change_password,

                            lending_permission

                        FROM users
                        
                        WHERE BINARY email = BINARY ?

                        LIMIT 1
                        "

                    );


                mysqli_stmt_bind_param(
                    $userStmt,
                    "s",
                    $emailValue
                );


                mysqli_stmt_execute(
                    $userStmt
                );


                $userResult =
                    mysqli_stmt_get_result(
                        $userStmt
                    );


                $user =
                    mysqli_fetch_assoc(
                        $userResult
                    );


                mysqli_stmt_close(
                    $userStmt
                );


                /* =============================================
                   INVALID CREDENTIALS
                ============================================= */

                if (
                    !$user
                    ||
                    !password_verify(
                        $password,
                        (string) (
                            $user[
                                "password"
                            ]
                            ??
                            ""
                        )
                    )
                ) {

                    /*
                     * Generic message prevents revealing
                     * whether a specific email exists.
                     */

                    $attemptStmt =
                        mysqli_prepare(
                            $conn,
                            "
                            INSERT INTO login_attempts (
                                email_hash,
                                ip_address,
                                attempted_at
                            )

                            VALUES (
                                ?,
                                ?,
                                NOW()
                            )
                            "
                        );


                    mysqli_stmt_bind_param(
                        $attemptStmt,
                        "ss",
                        $emailHash,
                        $clientIp
                    );


                    mysqli_stmt_execute(
                        $attemptStmt
                    );


                    mysqli_stmt_close(
                        $attemptStmt
                    );


                    $error =
                        "Invalid email or password.";

                }


                /* =============================================
                   ARCHIVED ACCOUNT
                ============================================= */

                elseif (
                    (int) (
                        $user[
                            "archived"
                        ]
                        ??
                        0
                    )
                    ===
                    1
                ) {

                    $error =
                        "This account has been archived.";

                }


                /* =============================================
                   INACTIVE ACCOUNT
                ============================================= */

                elseif (
                    strcasecmp(
                        trim(
                            (string) (
                                $user[
                                    "status"
                                ]
                                ??
                                ""
                            )
                        ),
                        "Active"
                    )
                    !==
                    0
                ) {

                    $error =
                        "This account is inactive.";

                }


                /* =============================================
                   LOGIN SUCCESS
                ============================================= */

                else {


                    /*
                     * Prevent session fixation.
                     */

                    $clearAttemptsStmt =
                        mysqli_prepare(
                            $conn,
                            "
                            DELETE FROM login_attempts

                            WHERE email_hash = ?
                            AND ip_address = ?
                            "
                        );


                    mysqli_stmt_bind_param(
                        $clearAttemptsStmt,
                        "ss",
                        $emailHash,
                        $clientIp
                    );


                    mysqli_stmt_execute(
                        $clearAttemptsStmt
                    );


                    mysqli_stmt_close(
                        $clearAttemptsStmt
                    );

                    session_regenerate_id(
                        true
                    );


                    $_SESSION[
                        "user_id"
                    ] =
                        (int) $user[
                            "user_id"
                        ];


                    $_SESSION[
                        "name"
                    ] =
                        (string) $user[
                            "name"
                        ];


                    $_SESSION[
                        "role"
                    ] =
                        (string) $user[
                            "role"
                        ];


                    /* =========================================
                       MODULE ACCESS
                    ========================================= */

                    $_SESSION[
                        "inventory_access"
                    ] =
                        (int) (
                            $user[
                                "inventory_access"
                            ]
                            ??
                            0
                        );


                    $_SESSION[
                        "sales_access"
                    ] =
                        (int) (
                            $user[
                                "sales_access"
                            ]
                            ??
                            0
                        );


                    $_SESSION[
                        "members_access"
                    ] =
                        (int) (
                            $user[
                                "members_access"
                            ]
                            ??
                            0
                        );


                    $_SESSION[
                        "lending_access"
                    ] =
                        (int) (
                            $user[
                                "lending_access"
                            ]
                            ??
                            0
                        );


                    $_SESSION[
                        "rental_access"
                    ] =
                        (int) (
                            $user[
                                "rental_access"
                            ]
                            ??
                            0
                        );


                    $_SESSION[
                        "archives_access"
                    ] =
                        (int) (
                            $user[
                                "archives_access"
                            ]
                            ??
                            0
                        );

                    $_SESSION[
                        "account_access"
                    ] =
                        (int) (
                            $user[
                                "account_access"
                            ]
                            ??
                            0
                        );



                    $_SESSION[
                        "password_reset_access"
                    ] =
                        (int) (
                            $user[
                                "password_reset_access"
                            ]
                            ??
                            0
                        );


                    /* =========================================
                       PASSWORD RESET SESSION SECURITY
                    ========================================= */

                    $_SESSION[
                        "must_change_password"
                    ] =
                        (int) (
                            $user[
                                "must_change_password"
                            ]
                            ??
                            0
                        );

                    $_SESSION[
                        "password_version"
                    ] =
                        hash(
                            "sha256",
                            (string) $user[
                                "password"
                            ]
                        );



                    /* =========================================
                       LENDING PERMISSION
                    ========================================= */

                    $_SESSION[
                        "lending_permission"
                    ] =
                        strtolower(
                            trim(
                                (string) (
                                    $user[
                                        "lending_permission"
                                    ]
                                    ??
                                    "limited"
                                )
                            )
                        );


                    /* =========================================
                       FIRST NAME
                    ========================================= */

                    $fullName =
                        trim(
                            (string) $user[
                                "name"
                            ]
                        );


                    $nameParts =
                        preg_split(
                            '/\s+/',
                            $fullName
                        );


                    $_SESSION[
                        "first_name"
                    ] =
                        $nameParts[
                            0
                        ]
                        ??
                        $fullName;


                    /* =========================================
                       ROTATE LOGIN CSRF
                    ========================================= */

                    $_SESSION[
                        "login_csrf_token"
                    ] =
                        bin2hex(
                            random_bytes(
                                32
                            )
                        );



                    /* =========================================
                       LOGIN DESTINATION
                    ========================================= */

                    if (
                        $_SESSION[
                            "must_change_password"
                        ] === 1
                    ) {

                        header(
                            "Location: /spis/change_password_required.php",
                            true,
                            303
                        );

                    } else {

                        header(
                            "Location: dashboard.php?login=success",
                            true,
                            303
                        );

                    }

                    exit();


                }

            }

        }
        catch (
            Throwable $errorObject
        ) {


                /*
                 * Keep technical details private.
                 */

                error_log(
                    "SPIS login error: "
                    .
                    $errorObject
                        ->getMessage()
                );


                $error =
                    "Unable to sign in right now. Please try again.";

            }

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
        content="width=device-width, initial-scale=1.0">

    <title>
        SPIS | Login
    </title>

    <link
        rel="stylesheet"
        href="css/fonts.css?v=<?= time(); ?>">
    
    <link
        rel="stylesheet"
        href="assets/sweetalert/sweetalert2.min.css">

    <link
        rel="stylesheet"
        href="css/style.css?v=<?= time(); ?>">

</head>


<body class="login-page">


<!-- =========================================================
     BACKGROUND DECORATIONS
========================================================= -->

<div
    class="login-background"
    aria-hidden="true">

    <div class="login-orb login-orb-one"></div>

    <div class="login-orb login-orb-two"></div>

    <div class="login-grid"></div>

</div>


<!-- =========================================================
     LOGIN WINDOW
========================================================= -->

<main class="login-shell">


    <!-- =====================================================
         FORM SIDE
    ====================================================== -->

    <section class="login-panel login-form-panel">


        <div class="login-form-inner">


            <!-- BRAND LABEL -->

            <div class="login-eyebrow">

                <span class="login-eyebrow-dot"></span>

                SIPBU-MPC • PORAIS BRANCH

            </div>


            <!-- HEADING -->

            <div class="login-heading">

                <h1>
                    Welcome to SPIS
                </h1>

                <p>
                    Sign in to access the
                    SIPBU-Porais Integrated System.
                </p>

            </div>


            <!-- =============================================
                 SERVER MESSAGE
            ============================================== -->

            <?php

            if (
                $error !== ""
            ) {

            ?>

                <div
                    class="login-server-error"
                    role="alert">

                    <span
                        class="login-server-error-icon"
                        aria-hidden="true">

                        !

                    </span>

                    <span>

                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            "UTF-8"
                        ); ?>

                    </span>

                </div>

            <?php

            }

            ?>


            <!-- =============================================
                 LOGIN FORM
            ============================================== -->

            <form
                id="loginForm"
                method="POST"
                action="login.php"
                autocomplete="off"
                novalidate>


                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION[
                            "login_csrf_token"
                        ],
                        ENT_QUOTES,
                        "UTF-8"
                    ); ?>">


                <!-- EMAIL -->

                <div class="login-field">

                    <label
                        for="loginEmail">

                        EMAIL

                    </label>


                    <div class="login-input-wrap">

                        <input
                            type="email"
                            id="loginEmail"
                            name="email"
                            value="<?= htmlspecialchars(
                                $emailValue,
                                ENT_QUOTES,
                                "UTF-8"
                            ); ?>"
                            placeholder="username@spis.com"
                            maxlength="190"
                            inputmode="email"
                            autocomplete="username">

                    </div>


                    <small
                        class="login-field-error"
                        data-error-for="email">

                    </small>

                </div>


                <!-- PASSWORD -->

                <div class="login-field">

                    <label
                        for="loginPassword">

                        PASSWORD

                    </label>


                    <div class="login-input-wrap">

                        <input
                            type="password"
                            id="loginPassword"
                            name="password"
                            placeholder="Enter your password"
                            maxlength="255"
                            autocomplete="current-password">


                        <button
                            type="button"
                            class="login-password-toggle"
                            id="loginPasswordToggle"
                            aria-label="Show password"
                            aria-pressed="false">


                            <svg
                                class="login-eye login-eye-open"
                                viewBox="0 0 24 24"
                                aria-hidden="true">

                                <path
                                    d="
                                    M2.5 12
                                    C4.7 7.8 8 5.5 12 5.5
                                    C16 5.5 19.3 7.8 21.5 12
                                    C19.3 16.2 16 18.5 12 18.5
                                    C8 18.5 4.7 16.2 2.5 12Z
                                    "
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round">

                                </path>

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="2.6"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.8">

                                </circle>

                            </svg>

                        </button>

                    </div>


                    <small
                        class="login-field-error"
                        data-error-for="password">

                    </small>

                </div>


                <!-- =========================================
                     LOGIN BUTTON
                ========================================== -->

                <button
                    type="submit"
                    class="login-submit"
                    id="loginSubmit">


                    <span class="login-submit-text">

                        LOG IN

                    </span>


                    <span
                        class="login-submit-loader"
                        aria-hidden="true">

                    </span>


                </button>


            </form>


            <div class="login-footer-note">

                <span class="login-footer-lock">
                    ●
                </span>

                Authorized personnel only

            </div>


        </div>

    </section>


    <!-- =====================================================
         BRAND SIDE
    ====================================================== -->

    <section class="login-panel login-brand-panel">


        <div class="login-brand-glow"></div>


        <div class="login-brand-content">


            <div class="login-logo-wrap">

                <img
                    src="assets/logo/sipbu-logo.png"
                    alt="SIPBU-MPC logo"
                    class="login-logo">

            </div>


            <div class="login-brand-copy">

                <span class="login-brand-kicker">

                    SIPBU-PORAIS

                </span>


                <h2>

                    Integrated System

                </h2>

            </div>


            <div class="login-brand-status">

                <span></span>

                LOCAL SYSTEM READY

            </div>


        </div>

    </section>


</main>


<!-- =========================================================
     LOGIN TRANSITION
========================================================= -->

<div
    class="login-transition"
    id="loginTransition"
    aria-hidden="true">


    <div class="login-transition-content">


        <div class="login-transition-logo">

            <img
                src="assets/logo/sipbu-logo.png"
                alt="">

        </div>


        <div class="login-transition-title">

            Opening SPIS

        </div>


        <div class="login-transition-text">

            Verifying your account...

        </div>


        <div class="login-transition-bar">

            <span></span>

        </div>


    </div>


</div>


<!-- =========================================================
     LOCAL SWEETALERT
========================================================= -->

<script
    src="assets/sweetalert/sweetalert2.min.js">
</script>


<!-- =========================================================
     LOGIN JAVASCRIPT
========================================================= -->

<script>

"use strict";


/* =========================================================
   ELEMENTS
========================================================= */

const loginForm =
    document.getElementById(
        "loginForm"
    );


const loginEmail =
    document.getElementById(
        "loginEmail"
    );


const loginPassword =
    document.getElementById(
        "loginPassword"
    );


const loginSubmit =
    document.getElementById(
        "loginSubmit"
    );


const loginPasswordToggle =
    document.getElementById(
        "loginPasswordToggle"
    );


const loginTransition =
    document.getElementById(
        "loginTransition"
    );


let loginSubmitting =
    false;


/* =========================================================
   FIELD ERROR
========================================================= */

function loginSetError(
    input,
    message
) {

    if (!input) {

        return;

    }


    const field =
        input.closest(
            ".login-field"
        );


    if (!field) {

        return;

    }


    field.classList.add(
        "has-error"
    );


    const errorElement =
        field.querySelector(
            ".login-field-error"
        );


    if (errorElement) {

        errorElement.textContent =
            message;

    }

}


/* =========================================================
   CLEAR FIELD ERROR
========================================================= */

function loginClearError(
    input
) {

    if (!input) {

        return;

    }


    const field =
        input.closest(
            ".login-field"
        );


    if (!field) {

        return;

    }


    field.classList.remove(
        "has-error"
    );


    const errorElement =
        field.querySelector(
            ".login-field-error"
        );


    if (errorElement) {

        errorElement.textContent =
            "";

    }

}


/* =========================================================
   EMAIL VALIDATION
========================================================= */

function loginValidateEmail() {

    const value =
        loginEmail
            .value
            .trim();


    if (
        value === ""
    ) {

        return "Email address is required.";

    }


    const emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


    if (
        !emailPattern.test(
            value
        )
    ) {

        return "Enter a valid email address.";

    }


    return "";

}


/* =========================================================
   PASSWORD VALIDATION
========================================================= */

function loginValidatePassword() {

    if (
        loginPassword
            .value
        ===
        ""
    ) {

        return "Password is required.";

    }


    return "";

}


/* =========================================================
   PASSWORD VISIBILITY
========================================================= */

if (
    loginPasswordToggle
    &&
    loginPassword
) {

    loginPasswordToggle
        .addEventListener(
            "click",
            function() {


                const hidden =
                    loginPassword
                        .type
                    ===
                    "password";


                loginPassword.type =
                    hidden
                        ?
                        "text"
                        :
                        "password";


                loginPasswordToggle
                    .setAttribute(
                        "aria-pressed",
                        hidden
                            ?
                            "true"
                            :
                            "false"
                    );


                loginPasswordToggle
                    .setAttribute(
                        "aria-label",
                        hidden
                            ?
                            "Hide password"
                            :
                            "Show password"
                    );


                loginPassword.focus();

            }
        );

}


/* =========================================================
   LIVE VALIDATION
========================================================= */

if (
    loginEmail
) {

    loginEmail.addEventListener(
        "input",
        function() {

            if (
                !loginEmail
                    .closest(
                        ".login-field"
                    )
                    ?.classList
                    .contains(
                        "has-error"
                    )
            ) {

                return;

            }


            const message =
                loginValidateEmail();


            if (
                message === ""
            ) {

                loginClearError(
                    loginEmail
                );

            }

        }
    );

}


if (
    loginPassword
) {

    loginPassword.addEventListener(
        "input",
        function() {

            if (
                !loginPassword
                    .closest(
                        ".login-field"
                    )
                    ?.classList
                    .contains(
                        "has-error"
                    )
            ) {

                return;

            }


            const message =
                loginValidatePassword();


            if (
                message === ""
            ) {

                loginClearError(
                    loginPassword
                );

            }

        }
    );

}


/* =========================================================
   LOGIN SUBMIT
========================================================= */

if (
    loginForm
) {

    loginForm.addEventListener(
        "submit",
        function(event) {

            event.preventDefault();


            if (
                loginSubmitting
            ) {

                return;

            }


            const emailError =
                loginValidateEmail();


            const passwordError =
                loginValidatePassword();


            if (
                emailError !== ""
            ) {

                loginSetError(
                    loginEmail,
                    emailError
                );

            }
            else {

                loginClearError(
                    loginEmail
                );

            }


            if (
                passwordError !== ""
            ) {

                loginSetError(
                    loginPassword,
                    passwordError
                );

            }
            else {

                loginClearError(
                    loginPassword
                );

            }


            if (
                emailError !== ""
                ||
                passwordError !== ""
            ) {

                const firstInvalid =
                    emailError !== ""
                        ?
                        loginEmail
                        :
                        loginPassword;


                firstInvalid.focus();


                return;

            }


            /* =============================================
               START LOGIN ANIMATION
            ============================================= */

            loginSubmitting =
                true;


            document.body.classList.add(
                "is-logging-in"
            );


            if (
                loginSubmit
            ) {

                loginSubmit.disabled =
                    true;

            }


            if (
                loginTransition
            ) {

                loginTransition
                    .classList
                    .add(
                        "is-visible"
                    );


                loginTransition
                    .setAttribute(
                        "aria-hidden",
                        "false"
                    );

            }


            /*
             * Small delay allows the visual transition
             * to begin before PHP handles the request.
             */

            window.setTimeout(
                function() {

                    HTMLFormElement
                        .prototype
                        .submit
                        .call(
                            loginForm
                        );

                },
                320
            );

        }
    );

}


/* =========================================================
   RESET AFTER BACK NAVIGATION
========================================================= */

window.addEventListener(
    "pageshow",
    function() {

        loginSubmitting =
            false;


        document.body
            .classList
            .remove(
                "is-logging-in"
            );


        if (
            loginSubmit
        ) {

            loginSubmit.disabled =
                false;

        }


        if (
            loginTransition
        ) {

            loginTransition
                .classList
                .remove(
                    "is-visible"
                );


            loginTransition
                .setAttribute(
                    "aria-hidden",
                    "true"
                );

        }

    }
);



/* =========================================================
   PASSWORD CHANGED SUCCESSFULLY
========================================================= */

<?php if (($_GET["password_changed"] ?? "") === "1"): ?>

document.addEventListener(
    "DOMContentLoaded",
    function() {

        Swal.fire({
            icon: "success",
            title: "Password Updated!",
            text: "Your password has been changed. Please log in again.",
            confirmButtonColor: "#8CA15B"
        });

    }
);

<?php endif; ?>


/* =========================================================
   LOGOUT SUCCESS
========================================================= */


<?php

if (
    isset(
        $_GET[
            "logout"
        ]
    )
) {

?>

document.addEventListener(
    "DOMContentLoaded",
    function() {

        Swal.fire({

            icon:
                "success",

            title:
                "Logged Out Successfully!",

            text:
                "Your SPIS session has been closed.",

            showConfirmButton:
                false,

            timer:
                1600,

            timerProgressBar:
                true

        });

    }
);

<?php

}

?>

</script>


</body>

</html>