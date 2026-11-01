<?php

/* =========================================================
   SPIS - ACCOUNT MANAGEMENT
========================================================= */


/* =========================================================
   ACCESS / SESSION
========================================================= */

require_once "../includes/check_access.php";

checkAccess(
    "account_access"
);


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

require_once "../db.php";


mysqli_set_charset(
    $conn,
    "utf8mb4"
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   CSRF TOKEN

   This will be used by Add / Edit / Archive endpoints
   in our next security steps.
========================================================= */

if (
    empty(
        $_SESSION[
            "account_csrf_token"
        ]
    )
) {

    $_SESSION[
        "account_csrf_token"
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );

}


$accountCsrfToken =
    $_SESSION[
        "account_csrf_token"
    ];


/* =========================================================
   HELPERS
========================================================= */

function accountEscape(
    $value
): string {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );

}


function accountDisplayDate(
    $date
): string {

    if (
        empty($date)
        ||
        $date === "0000-00-00"
        ||
        $date === "0000-00-00 00:00:00"
    ) {

        return "—";

    }


    $timestamp =
        strtotime(
            $date
        );


    if (
        $timestamp === false
    ) {

        return "—";

    }


    return date(
        "m-d-Y",
        $timestamp
    );

}


/* =========================================================
   LOAD ACTIVE ACCOUNTS

   Password and archive metadata are intentionally excluded.
========================================================= */

$accountLoadError =
    false;


$sql = "
    SELECT

        user_id,
        name,
        email,
        role,

        inventory_access,
        sales_access,
        members_access,
        lending_access,
        rental_access,

        archives_access,
        account_access,
        password_reset_access,

        lending_permission,

        created_at,
        status

    FROM users

    WHERE archived = 0

    ORDER BY user_id ASC
";


$result =
    mysqli_query(
        $conn,
        $sql
    );


if (
    $result === false
) {

    $accountLoadError =
        true;

}


/* =========================================================
   PAGE COMPONENTS
========================================================= */

include "../includes/sidebar.php";

?>


<link
    rel="stylesheet"
    href="account.css?v=<?= time(); ?>">


<div class="page-header account-header-top">

    <h2>
        ACCOUNT MANAGEMENT
    </h2>


    <button
        type="button"
        class="btn-add-account"
        data-bs-toggle="modal"
        data-bs-target="#addAccountModal">

        ADD ACCOUNT

    </button>

</div>


<hr>


<div class="account-ledger">


    <div class="account-header">

        <div>NAME</div>

        <div>EMAIL</div>

        <div>ROLE</div>

        <div>MODULE ACCESS</div>

        <div>LENDING ACCESS</div>

        <div>DATE CREATED</div>

        <div>STATUS</div>

        <div>EDIT</div>

    </div>


    <div class="account-body">


<?php

if (
    !$accountLoadError
    &&
    $result
    &&
    mysqli_num_rows(
        $result
    ) > 0
) {

    while (
        $user =
            mysqli_fetch_assoc(
                $result
            )
    ) {

        $modules =
            [];


        if (
            (int) $user[
                "inventory_access"
            ] === 1
        ) {

            $modules[] =
                "Inventory";

        }


        if (
            (int) $user[
                "sales_access"
            ] === 1
        ) {

            $modules[] =
                "Sales";

        }


        if (
            (int) $user[
                "members_access"
            ] === 1
        ) {

            $modules[] =
                "Members";

        }


        if (
            (int) $user[
                "lending_access"
            ] === 1
        ) {

            $modules[] =
                "Lending";

        }


        if (
            (int) $user[
                "rental_access"
            ] === 1
        ) {

            $modules[] =
                "Rental";

        }


        if (
            (int) $user[
                "archives_access"
            ] === 1
        ) {

            $modules[] =
                "Archives";

        }



        if (
            (int) $user[
                "account_access"
            ] === 1
        ) {

            $modules[] =
                "Account";

        }


        if (
            (int) $user[
                "password_reset_access"
            ] === 1
        ) {

            $modules[] =
                "Password Reset";

        }


        $moduleText =

            !empty(
                $modules
            )
                ? implode(
                    ", ",
                    $modules
                )
                : "—";


        $hasLendingAccess =
            (int) $user[
                "lending_access"
            ] === 1;


        $lendingPermission =
            $hasLendingAccess
                ? strtoupper(
                    trim(
                        (string) (
                            $user[
                                "lending_permission"
                            ]
                            ??
                            ""
                        )
                    )
                )
                : "—";


        if (
            $lendingPermission === ""
            ||
            $lendingPermission === "NONE"
        ) {

            $lendingPermission =
                "—";

        }


        $status =
            trim(
                (string) (
                    $user[
                        "status"
                    ]
                    ??
                    ""
                )
            );


        $statusClass =
            $status === "Active"
                ? "status-active"
                : "status-inactive";


?>


        <div class="account-row">


            <div>

                <?= accountEscape(
                    $user[
                        "name"
                    ]
                ); ?>

            </div>


            <div>

                <?= accountEscape(
                    $user[
                        "email"
                    ]
                ); ?>

            </div>


            <div>

                <?= accountEscape(
                    $user[
                        "role"
                    ]
                ); ?>

            </div>


            <div class="module-text">

                <?= accountEscape(
                    $moduleText
                ); ?>

            </div>


            <div>

                <?= accountEscape(
                    $lendingPermission
                ); ?>

            </div>


            <div>

                <?= accountEscape(
                    accountDisplayDate(
                        $user[
                            "created_at"
                        ]
                    )
                ); ?>

            </div>


            <div>

                <span
                    class="
                        account-status
                        <?= accountEscape(
                            $statusClass
                        ); ?>
                    ">

                    <?= accountEscape(
                        strtoupper(
                            $status
                        )
                    ); ?>

                </span>

            </div>


            <div>

                <button
                    type="button"
                    class="account-edit-btn"
                    data-id="<?= (int) $user[
                        "user_id"
                    ]; ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#editAccountModal"
                    title="Edit Account"
                    aria-label="Edit account">

                    <i class="bi bi-pencil-fill"></i>

                </button>

            </div>


        </div>


<?php

    }

}
elseif (
    $accountLoadError
) {

?>


        <div class="account-empty">

            Unable to load accounts.

        </div>


<?php

}
else {

?>


        <div class="account-empty">

            No accounts found.

        </div>


<?php

}

?>


    </div>

</div>


<?php

include "add_account_modal.php";

include "edit_account_modal.php";

?>


<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="../assets/sweetalert/sweetalert2.min.js"></script>


<?php

/* =========================================================
   SUCCESS MESSAGES
========================================================= */

$successMessages =
    [

        "added" =>
        [
            "title" =>
                "Account Created!",

            "text" =>
                "The account has been created successfully."
        ],

        "updated" =>
        [
            "title" =>
                "Account Updated!",

            "text" =>
                "The account has been updated successfully."
        ],

        "archived" =>
        [
            "title" =>
                "Account Archived!",

            "text" =>
                "The account has been archived successfully."
        ],

        "restored" =>
        [
            "title" =>
                "Account Restored!",

            "text" =>
                "The account has been restored successfully."
        ]

    ];


$successCode =
    trim(
        $_GET[
            "success"
        ]
        ??
        ""
    );


if (
    isset(
        $successMessages[
            $successCode
        ]
    )
) {

    $successTitle =
        $successMessages[
            $successCode
        ][
            "title"
        ];


    $successText =
        $successMessages[
            $successCode
        ][
            "text"
        ];

?>


<script>

Swal.fire({

    icon:
        "success",

    title:
        <?= json_encode(
            $successTitle
        ); ?>,

    text:
        <?= json_encode(
            $successText
        ); ?>,

    confirmButtonColor:
        "#8CA15B",

    showConfirmButton:
        false,

    timer:
        1800,

    timerProgressBar:
        true

});

</script>


<?php

}


/* =========================================================
   ERROR MESSAGES
========================================================= */

$errorMessages =
    [
        "duplicate" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Email Already Exists",

            "text" =>
                "An account with this email already exists."
        ],

        "duplicate_name" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Account Already Exists",

            "text" =>
                "An active account with this name already exists."
        ],


        "required" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Incomplete Information",

            "text" =>
                "Please complete all required fields."
        ],


        "email" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Invalid Email",

            "text" =>
                "Only @spis.com email addresses are allowed."
        ],


        "password" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Weak Password",

            "text" =>
                "Password must be at least 8 characters."
        ],


        "password_match" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Password Mismatch",

            "text" =>
                "Passwords do not match."
        ],


        "modules" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Module Required",

            "text" =>
                "Please select at least one module."
        ],


        "lending_permission" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Lending Permission Required",

            "text" =>
                "Select Limited or Full Access when Lending is enabled."
        ],


        "self_archive" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Action Not Allowed",

            "text" =>
                "You cannot archive your own account."
        ],


        "self_lockout" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Change Not Allowed",

            "text" =>
                "You cannot deactivate your own account or remove your own Account access."
        ],


        "last_manager" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Account Access Required",

            "text" =>
                "At least one active account must keep access to the Account module."
        ],


        "invalid_security_token" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Security Check Failed",

            "text" =>
                "The request could not be verified. Please try again."
        ],


        "csrf" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Security Check Failed",

            "text" =>
                "The security token has expired. Refresh the page and try again."
        ],


        "invalid_request" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Invalid Request",

            "text" =>
                "The requested account action is not allowed."
        ],


        "session" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Session Expired",

            "text" =>
                "Your session has expired. Please sign in again."
        ],


        "forbidden" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Access Denied",

            "text" =>
                "You do not have permission to manage accounts."
        ],


        "account_not_found" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Account Not Found",

            "text" =>
                "The account record could not be found."
        ],


        "invalid_account" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Account Unavailable",

            "text" =>
                "The selected account could not be found or is no longer active in the Account ledger."
        ],


        "invalid_input" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Invalid Information",

            "text" =>
                "One or more account details are invalid."
        ],


        "invalid_status" =>
        [
            "icon" =>
                "warning",

            "title" =>
                "Invalid Status",

            "text" =>
                "Please select a valid account status."
        ],


        "update_failed" =>
        [
            "icon" =>
                "error",

            "title" =>
                "Update Failed",

            "text" =>
                "The account could not be updated. No changes were saved."
        ]

    ];


$errorCode =
    trim(
        $_GET[
            "error"
        ]
        ??
        ""
    );


if (
    isset(
        $errorMessages[
            $errorCode
        ]
    )
) {

    $errorIcon =
        $errorMessages[
            $errorCode
        ][
            "icon"
        ];


    $errorTitle =
        $errorMessages[
            $errorCode
        ][
            "title"
        ];


    $errorText =
        $errorMessages[
            $errorCode
        ][
            "text"
        ];

?>


<script>

Swal.fire({

    icon:
        <?= json_encode(
            $errorIcon
        ); ?>,

    title:
        <?= json_encode(
            $errorTitle
        ); ?>,

    text:
        <?= json_encode(
            $errorText
        ); ?>,

    confirmButtonColor:
        "#8CA15B",

    showConfirmButton:
        false,

    timer:
        2500,

    timerProgressBar:
        true

});

</script>


<?php

}

?>


<script src="account.js?v=<?= time(); ?>"></script>

<script src="edit_account.js?v=<?= time(); ?>"></script>

<script src="../js/sidebar.js"></script>


</body>

</html>