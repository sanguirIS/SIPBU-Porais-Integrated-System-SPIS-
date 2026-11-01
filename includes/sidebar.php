<?php

if(session_status() === PHP_SESSION_NONE){

    session_start();

}


/* ======================================================
   SYSTEM BASE PATH
   Works kahit nasa nested folders tulad ng:
   /spis/rental/tractor/tractor.php
====================================================== */

$base = "/spis/";



$currentPage =
basename(
    $_SERVER['PHP_SELF']
);


/* ======================================================
   INVENTORY STOCK ALERT
   DISPLAY ONCE PER LOGIN SESSION
====================================================== */

$spisStockAlert = null;

if (
    (int) ($_SESSION['inventory_access'] ?? 0) === 1 &&
    empty($_SESSION['spis_stock_alert_shown'])
) {
    try {
        if (!isset($conn) || !($conn instanceof mysqli)) {
            require_once __DIR__ . '/../db.php';
        }

        if (isset($conn) && $conn instanceof mysqli) {
            $stockResult = mysqli_query(
                $conn,
                "SELECT product_id, product_name, quantity
                FROM inventory
                WHERE archived = 0 AND quantity <= 5
                ORDER BY quantity ASC, product_name ASC"
            );

            $outOfStock = [];
            $lowOnStock = [];

            while ($stockRow = mysqli_fetch_assoc($stockResult)) {
                
                $stockItem = [
                    'id' => (int) $stockRow['product_id'],
                    'name' => (string) $stockRow['product_name'],
                    'quantity' => (int) $stockRow['quantity']
                ];

                if ($stockItem['quantity'] <= 0) {
                    $outOfStock[] = $stockItem;
                } else {
                    $lowOnStock[] = $stockItem;
                }
            }

            mysqli_free_result($stockResult);

            $_SESSION['spis_stock_alert_shown'] = true;

            if ($outOfStock || $lowOnStock) {
                $spisStockAlert = [
                    'out' => $outOfStock,
                    'low' => $lowOnStock
                ];
            }
        }
    } catch (Throwable $stockError) {
        error_log(
            'SPIS stock alert query failed: ' .
            $stockError->getMessage()
        );
    }
}

?>

<!DOCTYPE html>


<html>


<head>


    <title>

        SPIS

    </title>


    <link
    href="<?= $base ?>assets/bootstrap/css/bootstrap.min.css"
    rel="stylesheet">


    <link
    rel="stylesheet"
    href="<?= $base ?>assets/sweetalert/sweetalert2.min.css">


    <link
    rel="stylesheet"
    href="<?= $base ?>assets/bootstrap-icons/bootstrap-icons.css">


    <link
    rel="stylesheet"
    href="<?= $base ?>css/fonts.css">


    <link
    rel="stylesheet"
    href="<?= $base ?>css/sidebar.css">



    <link
    rel="stylesheet"
    href="<?= $base ?>css/inventory.css">

    <style>
    .spis-stock-alert {
        position: fixed;
        top: 20px;
        right: 24px;
        z-index: 1045;
        display: flex;
        flex-direction: column;
        width: min(370px, calc(100vw - 32px));
        max-height: calc(100vh - 40px);
        padding: 12px;
        color: #344233;
        background: #FFFFFF;
        border: 1px solid #D5DCC2;
        border-radius: 17px;
        box-shadow: 0 14px 40px rgba(33, 43, 21, .16);

        font-family: inherit;
        animation: spisStockFadeIn 0.5s ease-out both;
    }

    @keyframes spisStockFadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .spis-stock-alert {
            animation: none;
        }
    }

    .spis-stock-heading {

        display: flex;
        align-items: center;
        gap: 9px;
        padding: 5px 2px 13px;
        border-bottom: 1px solid #E2E5D9;
    }

    .spis-stock-dot {
        width: 16px;
        height: 16px;
        flex: none;
        border-radius: 50%;
        background: #FFF0CE;
    }

    .spis-stock-title {
        font-size: 15px;
        font-weight: 700;
        line-height: 1.3;
    }

    .spis-stock-summary {
        font-size: 12px;
        color: #64705E;
        margin-top: 3px;
    }

    .spis-stock-close {
        margin-left: auto;
        border: 0;
        background: transparent;
        color: #7D8678;
        font-size: 21px;
        cursor: pointer;
        padding: 2px 7px;
    }

    .spis-stock-scroll {
        overflow-y: auto;
        min-height: 0;
        padding: 13px 0 10px;
        scrollbar-width: thin;
    }

    .spis-stock-group + .spis-stock-group {
        margin-top: 14px;
    }

    .spis-stock-label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 9px;
        font-size: 12px;
        font-weight: 700;
    }

    .spis-stock-label.danger {
        color: #BE2D2D;
    }

    .spis-stock-label.warning {
        color: #AC7118;
    }

    .spis-stock-count {
        min-width: 20px;
        height: 20px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 12px;
    }

    .spis-stock-label.danger .spis-stock-count {
        background: #FDE1E1;
        color: #CE3333;
    }

    .spis-stock-label.warning .spis-stock-count {
        background: #FFE9D8;
        color: #D76729;
    }

    .spis-stock-items {
        padding: 6px 8px;
        border-radius: 10px;
    }

    .spis-stock-items.danger {
        background: #FFF0F0;
    }

    .spis-stock-items.warning {
        background: #FFF6E6;
    }

    .spis-stock-product {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        padding: 9px 2px;
        text-decoration: none;
        font-size: 13px;
    }

    .spis-stock-product:hover {
        text-decoration: underline;
    }

    .spis-stock-items.danger .spis-stock-product {
        color: #704242;
    }

    .spis-stock-items.warning .spis-stock-product {
        color: #665537;
    }

    .spis-stock-product-name {
        overflow-wrap: anywhere;
        font-weight: 600;
    }

    .spis-stock-product-quantity {
        flex: none;
        font-size: 12px;
    }

    .spis-stock-view {
        display: block;
        padding: 14px 12px;
        border-radius: 9px;
        background: #8CA15B;
        color: #FFFFFF;
        text-align: center;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
    }

    .spis-stock-view:hover {
        background: #708449;
        color: #FFFFFF;
    }

    @media (max-width: 480px) {
        .spis-stock-alert {
            top: 12px;
            right: 12px;
        }
    }
    </style>

</head>




<body>

<?php if ($spisStockAlert !== null): ?>
    <?php
        $stockOutCount = count($spisStockAlert['out']);
        $stockLowCount = count($spisStockAlert['low']);
        $stockTotalCount = $stockOutCount + $stockLowCount;
    ?>

    <aside
        class="spis-stock-alert"
        aria-label="Inventory Stock Alert"
        role="region">

        <div class="spis-stock-heading">
            <span
                class="spis-stock-dot"
                aria-hidden="true"></span>

            <div>
                <div class="spis-stock-title">
                    Inventory Stock Alert
                </div>

                <div class="spis-stock-summary">
                    <?= $stockTotalCount; ?> products need attention
                </div>
            </div>

            <button
                type="button"
                class="spis-stock-close"
                aria-label="Close stock alert"
                onclick="this.closest('.spis-stock-alert').remove()">
                &times;
            </button>
        </div>

        <div class="spis-stock-scroll">

            <?php if ($stockOutCount > 0): ?>
                <section class="spis-stock-group">

                    <div class="spis-stock-label danger">
                        <span>OUT OF STOCK</span>
                        <span class="spis-stock-count">
                            <?= $stockOutCount; ?>
                        </span>
                    </div>

                    <div class="spis-stock-items danger">
                        <?php foreach ($spisStockAlert['out'] as $stockItem): ?>   
                            <a
                                class="spis-stock-product"
                                href="<?= $base ?>inventory/inventory.php#inventory-product-<?= $stockItem['id']; ?>">
                                <span class="spis-stock-product-name">
                                    <?= htmlspecialchars(
                                        $stockItem['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <span class="spis-stock-product-quantity">
                                    0 left
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>


            <?php if ($stockLowCount > 0): ?>
                <section class="spis-stock-group">

                    <div class="spis-stock-label warning">
                        <span>LOW STOCK</span>
                        <span class="spis-stock-count">
                            <?= $stockLowCount; ?>
                        </span>
                    </div>

                    <div class="spis-stock-items warning">
                        <?php foreach ($spisStockAlert['low'] as $stockItem): ?>                          
                                <a
                                    class="spis-stock-product"
                                    href="<?= $base ?>inventory/inventory.php#inventory-product-<?= $stockItem['id']; ?>">

                                <span class="spis-stock-product-name">
                                    <?= htmlspecialchars(
                                        $stockItem['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                                <span class="spis-stock-product-quantity">
                                    <?= number_format($stockItem['quantity']); ?> left
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </div>

        <a
            class="spis-stock-view"
            href="<?= $base ?>inventory/inventory.php">

            <i
                class="bi bi-arrow-up-right"
                aria-hidden="true"></i>

            &nbsp; View Inventory
        </a>

    </aside>
<?php endif; ?>


<div class="wrapper">



    <div class="sidebar">


        <!-- =================================================
             LOGO
        ================================================== -->

        <div class="logo">


            <img
            src="<?= $base ?>assets/logo/triangle-logo.png"
            class="logo-img">


            <div class="logo-text">

                SIPBU-PORAIS

                <br>

                Integrated System

            </div>


        </div>


        <!-- =================================================
             MAIN MENU
        ================================================== -->

        <ul>


            <!-- DASHBOARD -->

            <li>

                <a
                href="<?= $base ?>dashboard.php"
                class="sidebar-item <?= ($currentPage == 'dashboard.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-speedometer2"></i>

                    <span>
                        DASHBOARD
                    </span>

                </a>

            </li>


            <!-- INVENTORY -->

            <?php if($_SESSION['inventory_access'] == 1){ ?>

            <li>

                <a
                href="<?= $base ?>inventory/inventory.php"
                class="sidebar-item <?= ($currentPage == 'inventory.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-box-seam"></i>

                    <span>
                        INVENTORY
                    </span>

                </a>

            </li>

            <?php } ?>


            <!-- SALES -->

            <?php if($_SESSION['sales_access'] == 1){ ?>

            <li>

                <a
                href="<?= $base ?>sales/sales.php"
                class="sidebar-item <?= ($currentPage == 'sales.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-cart3"></i>

                    <span>
                        SALES
                    </span>

                </a>

            </li>

            <?php } ?>


            <!-- MEMBERS -->

            <?php if($_SESSION['members_access'] == 1){ ?>

            <li>

                <a
                href="<?= $base ?>member/member.php"
                class="sidebar-item <?= ($currentPage == 'member.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-people-fill"></i>

                    <span>
                        MEMBERS
                    </span>

                </a>

            </li>

            <?php } ?>


            <!-- LENDING -->

            <?php if($_SESSION['lending_access'] == 1){ ?>

            <li>

                <a
                href="<?= $base ?>lending/lending.php"
                class="sidebar-item <?= ($currentPage == 'lending.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-cash-stack"></i>

                    <span>
                        LENDING
                    </span>

                </a>

            </li>

            <?php } ?>


            <!-- RENTAL -->

            <?php if($_SESSION['rental_access'] == 1){ ?>

            <li>

                <a
                href="<?= $base ?>rental/rental.php"
                class="sidebar-item <?= in_array(
                    $currentPage,
                    [
                        'rental.php',
                        'hauling.php',
                        'reaper.php',
                        'rotor.php',
                        'tractor.php'
                    ]
                ) ? 'active' : 'inactive' ?>">

                    <i class="bi bi-tools"></i>

                    <span>
                        RENTAL
                    </span>

                </a>

            </li>

            <?php } ?>


            <!-- HISTORY -->

            <li>

                <a
                href="<?= $base ?>history/history.php"
                class="sidebar-item <?= ($currentPage == 'history.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-clock-history"></i>

                    <span>
                        HISTORY
                    </span>

                </a>

            </li>


            <!-- ARCHIVE -->

            <?php if($_SESSION['archives_access'] == 1){ ?>

            <li>

                <a
                href="<?= $base ?>archive/archive.php"
                class="sidebar-item <?= ($currentPage == 'archive.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-archive"></i>

                    <span>
                        ARCHIVE
                    </span>

                </a>

            </li>

            <?php } ?>


        </ul>


        <!-- =================================================
             BOTTOM MENU
        ================================================== -->

        <ul class="bottom-menu">


            <!-- ACCOUNT -->

            <?php if($_SESSION['account_access'] == 1){ ?>

            <li>

                <a
                href="<?= $base ?>account/account.php"
                class="sidebar-item <?= ($currentPage == 'account.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-person-circle"></i>

                    <span>
                        ACCOUNT
                    </span>

                </a>

            </li>


            <?php } ?>


            <!-- PASSWORD RESET -->

            <?php if ((int) ($_SESSION['password_reset_access'] ?? 0) === 1) { ?>

            <li>

                <a
                href="<?= $base ?>account/password_reset.php"
                class="sidebar-item <?= ($currentPage == 'password_reset.php') ? 'active' : 'inactive' ?>">

                    <i class="bi bi-key-fill"></i>

                    <span>
                        PASSWORD RESET
                    </span>

                </a>

            </li>

            <?php } ?>


            <!-- LOGOUT -->


            <li>

                <a
                href="#"
                id="logoutBtn"
                class="sidebar-item">

                    <i class="bi bi-box-arrow-right"></i>

                    <span>
                        LOGOUT
                    </span>

                </a>

            </li>


        </ul>


    </div>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <div class="main-content">


    <!-- =====================================================
         GLOBAL SCRIPTS
    ====================================================== -->

    <script
    src="<?= $base ?>assets/sweetalert/sweetalert2.min.js">
    </script>


    <script
    src="<?= $base ?>assets/bootstrap/js/bootstrap.bundle.min.js">
    </script>


    <script
    src="<?= $base ?>js/sidebar.js">
    </script>