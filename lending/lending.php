<?php

/* =========================================================
   LENDING LANDING PAGE
========================================================= */

if (
    session_status() ===
    PHP_SESSION_NONE
) {
    session_start();
}

date_default_timezone_set(
    "Asia/Manila"
);

error_reporting(E_ALL);

ini_set(
    "display_errors",
    "0"
);

header(
    "X-Content-Type-Options: nosniff"
);

header(
    "X-Frame-Options: SAMEORIGIN"
);

header(
    "Referrer-Policy: same-origin"
);

header(
    "Cache-Control: no-store, no-cache, must-revalidate, max-age=0"
);


/* =========================================================
   AUTHENTICATION AND ACCESS
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {

    header(
        "Location: /spis/login.php"
    );

    exit();

}

if (
    (int) (
        $_SESSION["lending_access"]
        ?? 0
    ) !== 1
) {

    header(
        "Location: /spis/dashboard.php?access=denied"
    );

    exit();

}


/* =========================================================
   DATABASE
========================================================= */

require_once "../db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    http_response_code(500);

    exit(
        "Database connection is unavailable."
    );
}

mysqli_set_charset(
    $conn,
    "utf8mb4"
);


/* =========================================================
   LENDING CARD COUNTS
========================================================= */

$pendingMonetaryCount = 0;
$overdueMonetaryCount = 0;

$pendingProductCount = 0;
$overdueProductCount = 0;

$todayManila =
    (
        new DateTimeImmutable(
            "now",
            new DateTimeZone(
                "Asia/Manila"
            )
        )
    )->format(
        "Y-m-d"
    );


/* =========================================================
   MONETARY LOAN COUNTS
========================================================= */

$monetaryCountQuery =
    mysqli_query(
        $conn,
        "
        SELECT

            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ) AS pending_total,

            SUM(
                CASE
                    WHEN status = 'Accepted'
                         AND due_date IS NOT NULL
                         AND due_date < '{$todayManila}'
                         AND (
                             outstanding_principal_amount > 0.009
                             OR unpaid_interest_amount > 0.009
                         )
                    THEN 1
                    ELSE 0
                END
            ) AS overdue_total

        FROM monetary_loans

        WHERE is_archived = 0
        "
    );

if ($monetaryCountQuery) {

    $monetaryCounts =
        mysqli_fetch_assoc(
            $monetaryCountQuery
        );

    $pendingMonetaryCount =
        (int) (
            $monetaryCounts[
                "pending_total"
            ]
            ?? 0
        );

    $overdueMonetaryCount =
        (int) (
            $monetaryCounts[
                "overdue_total"
            ]
            ?? 0
        );

}


/* =========================================================
   PRODUCT LOAN COUNTS
========================================================= */

$productCountQuery =
    mysqli_query(
        $conn,
        "
        SELECT

            SUM(
                CASE
                    WHEN status = 'Pending'
                    THEN 1
                    ELSE 0
                END
            ) AS pending_total,

            SUM(
                CASE
                    WHEN status = 'Delivered'
                         AND due_date IS NOT NULL
                         AND due_date < '{$todayManila}'
                         AND (
                             outstanding_principal_amount > 0.009
                             OR unpaid_interest_amount > 0.009
                         )
                    THEN 1
                    ELSE 0
                END
            ) AS overdue_total

        FROM product_loans

        WHERE is_archived = 0
        "
    );

if ($productCountQuery) {

    $productCounts =
        mysqli_fetch_assoc(
            $productCountQuery
        );

    $pendingProductCount =
        (int) (
            $productCounts[
                "pending_total"
            ]
            ?? 0
        );

    $overdueProductCount =
        (int) (
            $productCounts[
                "overdue_total"
            ]
            ?? 0
        );

}


/* =========================================================
   SIDEBAR
========================================================= */

include "../includes/sidebar.php";

?>

<link
    rel="stylesheet"
    href="../css/lending/lending.css?v=<?php echo time(); ?>"
>

<div class="page-header">

    <div>

        <h2>LENDING</h2>

    </div>

</div>

<hr>

<div class="lending-container">

    <!-- MONETARY LOAN -->

    <a
        href="monetary/"
        class="lending-card<?php
            echo $pendingMonetaryCount > 0
                ? " has-pending"
                : "";
        ?>"
        aria-label="Open Monetary Loan. <?php
            echo $pendingMonetaryCount;
        ?> pending application<?php
            echo $pendingMonetaryCount === 1
                ? ""
                : "s";
        ?>."
    >

        <div class="lending-card-status">

            <?php if ($overdueMonetaryCount > 0): ?>

                <span class="lending-overdue-badge">

                    <span class="lending-overdue-signal">
                        !
                    </span>

                    <?php
                    echo
                        $overdueMonetaryCount .
                        " OVERDUE";
                    ?>

                </span>

            <?php endif; ?>

            <?php if ($pendingMonetaryCount > 0): ?>

                <span class="lending-pending-badge">

                    <span class="lending-pending-signal"></span>

                    <?php
                    echo
                        $pendingMonetaryCount .
                        " PENDING";
                    ?>

                </span>

            <?php endif; ?>

            <?php if (
                $pendingMonetaryCount === 0 &&
                $overdueMonetaryCount === 0
            ): ?>

                <span class="lending-clear-badge">

                    <i class="bi bi-check-circle-fill"></i>

                    ALL CLEAR

                </span>

            <?php endif; ?>

        </div>

        <div class="lending-card-main">

            <span class="lending-icon-box">

                <i
                    class="bi bi-cash-stack lending-icon"
                    aria-hidden="true"
                ></i>

            </span>

            <h3>MONETARY LOAN</h3>

            <p>
                Manage cash loan applications,
                payments, and balances.
            </p>

        </div>

        <div class="lending-card-hint">

            <span class="lending-hint-icon">

                <i class="bi bi-arrow-right"></i>

            </span>

            <span class="lending-hint-text">

                <strong>Open Monetary Loan</strong>

                <small>
                    <?php if ($pendingMonetaryCount > 0): ?>

                        <?php
                        echo $pendingMonetaryCount;
                        ?>
                        application<?php
                        echo $pendingMonetaryCount === 1
                            ? ""
                            : "s";
                        ?> waiting for review

                    <?php else: ?>

                        View active loans and payment records

                    <?php endif; ?>
                </small>

            </span>

        </div>

    </a>


    <!-- PRODUCT LOAN -->

    <a
        href="product/"
        class="lending-card<?php
            echo $pendingProductCount > 0
                ? " has-pending"
                : "";
        ?>"
        aria-label="Open Product Loan. <?php
            echo $pendingProductCount;
        ?> pending application<?php
            echo $pendingProductCount === 1
                ? ""
                : "s";
        ?>."
    >

        <div class="lending-card-status">

            <?php if ($overdueProductCount > 0): ?>

                <span class="lending-overdue-badge">

                    <span class="lending-overdue-signal">
                        !
                    </span>

                    <?php
                    echo
                        $overdueProductCount .
                        " OVERDUE";
                    ?>

                </span>

            <?php endif; ?>

            <?php if ($pendingProductCount > 0): ?>

                <span class="lending-pending-badge">

                    <span class="lending-pending-signal"></span>

                    <?php
                    echo
                        $pendingProductCount .
                        " PENDING";
                    ?>

                </span>

            <?php endif; ?>

            <?php if (
                $pendingProductCount === 0 &&
                $overdueProductCount === 0
            ): ?>

                <span class="lending-clear-badge">

                    <i class="bi bi-check-circle-fill"></i>

                    ALL CLEAR

                </span>

            <?php endif; ?>

        </div>

        <div class="lending-card-main">

            <span class="lending-icon-box">

                <i
                    class="bi bi-box-seam lending-icon"
                    aria-hidden="true"
                ></i>

            </span>

            <h3>PRODUCT LOAN</h3>

            <p>
                Manage product requests,
                deliveries, and payments.
            </p>

        </div>

        <div class="lending-card-hint">

            <span class="lending-hint-icon">

                <i class="bi bi-arrow-right"></i>

            </span>

            <span class="lending-hint-text">

                <strong>Open Product Loan</strong>

                <small>
                    <?php if ($pendingProductCount > 0): ?>

                        <?php
                        echo $pendingProductCount;
                        ?>
                        application<?php
                        echo $pendingProductCount === 1
                            ? ""
                            : "s";
                        ?> waiting for review

                    <?php else: ?>

                        View requests and delivery records

                    <?php endif; ?>
                </small>

            </span>

        </div>

    </a>

</div>

<script src="../js/sidebar.js"></script>

</body>
</html>