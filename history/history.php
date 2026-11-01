<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


date_default_timezone_set('Asia/Manila');


require_once '../db.php';


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* ======================================================
   VERIFY CURRENT ACCOUNT + REFRESH LIVE MODULE ACCESS
====================================================== */

$userID =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


if ($userID <= 0) {

    header('Location: ../login.php');

    exit;
}


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
            account_access

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

    mysqli_stmt_close($accessStmt);

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


$_SESSION['inventory_access'] =
    (int) ($currentAccess['inventory_access'] ?? 0);

$_SESSION['sales_access'] =
    (int) ($currentAccess['sales_access'] ?? 0);

$_SESSION['members_access'] =
    (int) ($currentAccess['members_access'] ?? 0);

$_SESSION['lending_access'] =
    (int) ($currentAccess['lending_access'] ?? 0);

$_SESSION['rental_access'] =
    (int) ($currentAccess['rental_access'] ?? 0);

$_SESSION['archives_access'] =
    (int) ($currentAccess['archives_access'] ?? 0);

$_SESSION['account_access'] =
    (int) ($currentAccess['account_access'] ?? 0);


/* ======================================================
   MODULE ACCESS
====================================================== */

$moduleAccessMap = [

    'inventory' => [
        'label'   => 'Inventory',
        'session' => 'inventory_access',
        'icon'    => 'bi-box-seam'
    ],

    'sales' => [
        'label'   => 'Sales',
        'session' => 'sales_access',
        'icon'    => 'bi-cart3'
    ],

    'members' => [
        'label'   => 'Members',
        'session' => 'members_access',
        'icon'    => 'bi-people-fill'
    ],

    'lending' => [
        'label'   => 'Lending',
        'session' => 'lending_access',
        'icon'    => 'bi-cash-stack'
    ],

    'rental' => [
        'label'   => 'Rental',
        'session' => 'rental_access',
        'icon'    => 'bi-tools'
    ],

    'archive' => [
        'label'   => 'Archive',
        'session' => 'archives_access',
        'icon'    => 'bi-archive'
    ],

    'account' => [
        'label'   => 'Account',
        'session' => 'account_access',
        'icon'    => 'bi-person-circle'
    ]

];


$allowedModules = [];


foreach ($moduleAccessMap as $slug => $moduleData) {

    $accessColumn = $moduleData['session'];

    if ((int)($_SESSION[$accessColumn] ?? 0) === 1) {

        $allowedModules[$slug] = $moduleData;
    }
}


/* ======================================================
   FILTER VALUES
====================================================== */

$selectedModule = strtolower(
    trim($_GET['module'] ?? 'all')
);


if (
    $selectedModule !== 'all'
    &&
    !isset($allowedModules[$selectedModule])
) {
    $selectedModule = 'all';
}


$search = trim(
    $_GET['search'] ?? ''
);


$search = substr(
    $search,
    0,
    100
);


$selectedAction = trim(
    $_GET['action'] ?? ''
);


$dateFrom = trim(
    $_GET['date_from'] ?? ''
);


$dateTo = trim(
    $_GET['date_to'] ?? ''
);


$todayDate =
    date('Y-m-d');


/* ======================================================
   VALIDATE DATES
====================================================== */

function isValidHistoryDate($date)
{
    if ($date === '') {
        return false;
    }


    $parsedDate = DateTime::createFromFormat(
        'Y-m-d',
        $date
    );


    return (
        $parsedDate
        &&
        $parsedDate->format('Y-m-d') === $date
    );
}


if (
    !isValidHistoryDate($dateFrom)
    ||
    $dateFrom > $todayDate
) {
    $dateFrom = '';
}


if (
    !isValidHistoryDate($dateTo)
    ||
    $dateTo > $todayDate
) {
    $dateTo = '';
}


/*
If From is later than To,
automatically arrange the date range.
*/

if (
    $dateFrom !== ''
    &&
    $dateTo !== ''
    &&
    $dateFrom > $dateTo
) {

    $temporaryDate = $dateFrom;

    $dateFrom = $dateTo;

    $dateTo = $temporaryDate;
}


/* ======================================================
   CHECK HISTORY TABLE
====================================================== */

$historyTableExists = false;


$historyTableQuery = mysqli_query(
    $conn,
    "SHOW TABLES LIKE 'history_logs'"
);


if (
    $historyTableQuery
    &&
    mysqli_num_rows($historyTableQuery) > 0
) {
    $historyTableExists = true;
}


/* ======================================================
   BUILD ALLOWED MODULE VALUES
====================================================== */

$allowedModuleLabels = [];


foreach ($allowedModules as $moduleData) {

    $allowedModuleLabels[] =
        $moduleData['label'];
}


$moduleSQLValues = [];


foreach ($allowedModuleLabels as $allowedModuleLabel) {

    $moduleSQLValues[] =
        "'" .
        mysqli_real_escape_string(
            $conn,
            $allowedModuleLabel
        ) .
        "'";
}


/* ======================================================
   BASE CONDITIONS
====================================================== */

$whereConditions = [];


if (count($moduleSQLValues) > 0) {

    $whereConditions[] =
        'module IN (' .
        implode(',', $moduleSQLValues) .
        ')';

} else {

    $whereConditions[] = '1 = 0';
}


if ($selectedModule !== 'all') {

    $selectedModuleLabel =
        mysqli_real_escape_string(
            $conn,
            $allowedModules[$selectedModule]['label']
        );


    $whereConditions[] =
        "module = '$selectedModuleLabel'";
}


/* ======================================================
   GET AVAILABLE ACTIONS
====================================================== */

$availableActions = [];


if (
    $historyTableExists
    &&
    count($moduleSQLValues) > 0
) {

    $actionConditions = [

        'module IN (' .
        implode(',', $moduleSQLValues) .
        ')'

    ];


    if ($selectedModule !== 'all') {

        $actionModuleLabel =
            mysqli_real_escape_string(
                $conn,
                $allowedModules[$selectedModule]['label']
            );


        $actionConditions[] =
            "module = '$actionModuleLabel'";
    }


    $actionWhereSQL =
        implode(
            ' AND ',
            $actionConditions
        );


    $actionSQL = "
        SELECT DISTINCT action
        FROM history_logs
        WHERE $actionWhereSQL
        ORDER BY action ASC
    ";


    $actionResult = mysqli_query(
        $conn,
        $actionSQL
    );


    if ($actionResult) {

        while (
            $actionRow =
            mysqli_fetch_assoc($actionResult)
        ) {

            $availableActions[] =
                $actionRow['action'];
        }
    }
}


if (
    $selectedAction !== ''
    &&
    !in_array(
        $selectedAction,
        $availableActions,
        true
    )
) {
    $selectedAction = '';
}


if ($selectedAction !== '') {

    $escapedAction =
        mysqli_real_escape_string(
            $conn,
            $selectedAction
        );


    $whereConditions[] =
        "action = '$escapedAction'";
}


/* ======================================================
   SEARCH FILTER
====================================================== */

if ($search !== '') {

    $matchedSearchModule = '';


    foreach (
        $allowedModules
        as $allowedModule
    ) {

        if (
            strtolower($search)
            ===
            strtolower($allowedModule['label'])
        ) {

            $matchedSearchModule =
                $allowedModule['label'];

            break;
        }
    }


    if ($matchedSearchModule !== '') {

        $escapedSearchModule =
            mysqli_real_escape_string(
                $conn,
                $matchedSearchModule
            );


        $whereConditions[] =
            "module = '$escapedSearchModule'";

    } else {

        $escapedSearch =
            mysqli_real_escape_string(
                $conn,
                $search
            );


        $whereConditions[] = "
            (
                user_name LIKE '%$escapedSearch%'
                OR user_role LIKE '%$escapedSearch%'
                OR message LIKE '%$escapedSearch%'
                OR record_id LIKE '%$escapedSearch%'
            )
        ";
    }
}


/* ======================================================
   DATE FILTER
====================================================== */

if ($dateFrom !== '') {

    $escapedDateFrom =
        mysqli_real_escape_string(
            $conn,
            $dateFrom
        );


    $whereConditions[] =
        "created_at >= '$escapedDateFrom 00:00:00'";
}


if ($dateTo !== '') {

    $escapedDateTo =
        mysqli_real_escape_string(
            $conn,
            $dateTo
        );


    $whereConditions[] = "
        created_at < DATE_ADD(
            '$escapedDateTo 00:00:00',
            INTERVAL 1 DAY
        )
    ";
}


$whereSQL = implode(
    ' AND ',
    $whereConditions
);


/* ======================================================
   PAGINATION
====================================================== */

$logsPerPage = 20;


$currentHistoryPage = max(
    1,
    (int)($_GET['page'] ?? 1)
);


$totalLogs = 0;

$totalPages = 1;

$historyResult = false;


if ($historyTableExists) {

    $countResult = mysqli_query(
        $conn,
        "
        SELECT COUNT(*) AS total
        FROM history_logs
        WHERE $whereSQL
        "
    );


    if ($countResult) {

        $countRow =
            mysqli_fetch_assoc($countResult);


        $totalLogs =
            (int)($countRow['total'] ?? 0);
    }


    $totalPages = max(
        1,
        (int)ceil(
            $totalLogs / $logsPerPage
        )
    );


    if ($currentHistoryPage > $totalPages) {

        $currentHistoryPage = $totalPages;
    }


    $offset =
        ($currentHistoryPage - 1)
        *
        $logsPerPage;


    $historyResult = mysqli_query(
        $conn,
        "
        SELECT
            history_id,
            user_id,
            user_name,
            user_role,
            module,
            action,
            message,
            record_id,
            created_at
        FROM history_logs
        WHERE $whereSQL
        ORDER BY
            created_at DESC,
            history_id DESC
        LIMIT $logsPerPage
        OFFSET $offset
        "
    );
}


/* ======================================================
   BUILD FILTER URL
====================================================== */

function buildHistoryURL($changes = [])
{
    $query = $_GET;


    foreach ($changes as $key => $value) {

        if ($value === null || $value === '') {

            unset($query[$key]);

        } else {

            $query[$key] = $value;
        }
    }


    $queryString =
        http_build_query($query);


    return 'history.php' .
        (
            $queryString !== ''
            ? '?' . $queryString
            : ''
        );
}


include '../includes/sidebar.php';

?>


<link
rel="stylesheet"
href="../css/history.css?v=<?= time() ?>">


<!-- =====================================================
     HEADER
====================================================== -->

<div class="history-page-header">

    <h2>HISTORY</h2>

</div>


<hr>


<!-- =====================================================
     MODULE TABS
====================================================== -->

<div class="history-module-tabs">

    <a
    href="<?= htmlspecialchars(
        buildHistoryURL([
            'module' => 'all',
            'action' => null,
            'page'   => 1
        ])
    ) ?>"
    class="history-module-tab
    <?= $selectedModule === 'all'
        ? 'active'
        : '' ?>">

        <i class="bi bi-grid-fill"></i>

        <span>ALL</span>

    </a>


    <?php foreach (
        $allowedModules
        as $slug => $moduleData
    ): ?>

        <a
        href="<?= htmlspecialchars(
            buildHistoryURL([
                'module' => $slug,
                'action' => null,
                'page'   => 1
            ])
        ) ?>"
        class="history-module-tab
        <?= $selectedModule === $slug
            ? 'active'
            : '' ?>">

            <i class="bi <?= htmlspecialchars(
                $moduleData['icon']
            ) ?>"></i>

            <span>
                <?= strtoupper(
                    htmlspecialchars(
                        $moduleData['label']
                    )
                ) ?>
            </span>

        </a>

    <?php endforeach; ?>


    <div class="history-total">

        <span>VISIBLE RECORDS:</span>

        <strong>
            <?= number_format($totalLogs) ?>
        </strong>

    </div>

</div>


<!-- =====================================================
     FILTER TOOLBAR
====================================================== -->

<form
method="GET"
class="history-filter-toolbar">

    <input
    type="hidden"
    name="module"
    value="<?= htmlspecialchars(
        $selectedModule
    ) ?>">


    <div class="history-search-box">

        <i class="bi bi-search"></i>

        <input
        type="text"
        name="search"
        value="<?= htmlspecialchars(
            $search
        ) ?>"
        placeholder="Search activity, user, or record">

    </div>


    <select
    name="action"
    class="history-filter-select">

        <option value="">
            All Activities
        </option>


        <?php foreach (
            $availableActions
            as $availableAction
        ): ?>

            <option
            value="<?= htmlspecialchars(
                $availableAction
            ) ?>"
            <?= $selectedAction === $availableAction
                ? 'selected'
                : '' ?>>

                <?= htmlspecialchars(
                    $availableAction
                ) ?>

            </option>

        <?php endforeach; ?>

    </select>


    <label class="history-date-field">

        <span>FROM</span>

        <input
        type="date"
        name="date_from"
        max="<?= htmlspecialchars(
            $todayDate
        ) ?>"
        value="<?= htmlspecialchars(
            $dateFrom
        ) ?>">

    </label>


    <label class="history-date-field">

        <span>TO</span>

        <input
        type="date"
        name="date_to"
        max="<?= htmlspecialchars(
            $todayDate
        ) ?>"
        value="<?= htmlspecialchars(
            $dateTo
        ) ?>">

    </label>


    <button
    type="submit"
    class="history-filter-btn">

        FILTER

    </button>


    <a
    href="history.php?module=<?= urlencode(
        $selectedModule
    ) ?>"
    class="history-clear-btn"
    title="Clear filters"
    aria-label="Clear filters">

        <i class="bi bi-arrow-counterclockwise"></i>

    </a>


    <!-- TOOLBAR PAGINATION -->

    <?php if (
        $historyTableExists
        &&
        $totalLogs > 0
    ): ?>

        <div class="history-toolbar-pagination">

            <a
            href="<?= $currentHistoryPage > 1
                ? htmlspecialchars(
                    buildHistoryURL([
                        'page' =>
                            $currentHistoryPage - 1
                    ])
                )
                : '#'
            ?>"
            class="history-page-arrow
            <?= $currentHistoryPage <= 1
                ? 'disabled'
                : '' ?>"
            aria-label="Previous page"
            <?= $currentHistoryPage <= 1
                ? 'aria-disabled="true"'
                : '' ?>>

                <i class="bi bi-chevron-left"></i>

            </a>


            <span class="history-page-info">

                Page

                <strong>
                    <?= $currentHistoryPage ?>
                </strong>

                of

                <strong>
                    <?= $totalPages ?>
                </strong>

            </span>


            <a
            href="<?= $currentHistoryPage < $totalPages
                ? htmlspecialchars(
                    buildHistoryURL([
                        'page' =>
                            $currentHistoryPage + 1
                    ])
                )
                : '#'
            ?>"
            class="history-page-arrow
            <?= $currentHistoryPage >= $totalPages
                ? 'disabled'
                : '' ?>"
            aria-label="Next page"
            <?= $currentHistoryPage >= $totalPages
                ? 'aria-disabled="true"'
                : '' ?>>

                <i class="bi bi-chevron-right"></i>

            </a>

        </div>

    <?php endif; ?>

</form>


<!-- =====================================================
     HISTORY FEED
====================================================== -->

<div class="history-feed">


<?php if (!$historyTableExists): ?>

    <div class="history-empty-state">

        <i class="bi bi-database-exclamation"></i>

        <h3>
            History table is not ready yet
        </h3>

        <p>
            Run the history_logs.sql file in phpMyAdmin first.
        </p>

    </div>


<?php elseif (
    !$historyResult
    ||
    mysqli_num_rows($historyResult) === 0
): ?>

    <div class="history-empty-state">

        <i class="bi bi-clock-history"></i>

        <h3>
            No history records found
        </h3>

        <p>
            New activities from your accessible modules will appear here.
        </p>

    </div>


<?php else: ?>


    <?php

    $lastDate = '';


    while (
        $history =
        mysqli_fetch_assoc($historyResult)
    ):


        $historyTimestamp =
            strtotime($history['created_at']);


        $historyDate =
            date(
                'Y-m-d',
                $historyTimestamp
            );


        if ($historyDate !== $lastDate):


            $today =
                date('Y-m-d');


            $yesterday =
                date(
                    'Y-m-d',
                    strtotime('-1 day')
                );


            if ($historyDate === $today) {

                $dateLabel = 'TODAY';

            } elseif ($historyDate === $yesterday) {

                $dateLabel = 'YESTERDAY';

            } else {

                $dateLabel = strtoupper(
                    date(
                        'F d, Y',
                        $historyTimestamp
                    )
                );
            }

    ?>

        <div class="history-date-heading">

            <span>
                <?= htmlspecialchars(
                    $dateLabel
                ) ?>
            </span>

        </div>

    <?php

            $lastDate = $historyDate;

        endif;


        $historyModuleSlug =
            strtolower($history['module']);


        $historyIcon =
            $moduleAccessMap[
                $historyModuleSlug
            ]['icon']
            ??
            'bi-clock-history';

    ?>


        <div class="history-item">

            <div
            class="history-icon
            history-module-<?= htmlspecialchars(
                $historyModuleSlug
            ) ?>">

                <i class="bi <?= htmlspecialchars(
                    $historyIcon
                ) ?>"></i>

            </div>


            <div class="history-item-content">

                <div class="history-item-top">

                    <span class="history-module-name">

                        <?= strtoupper(
                            htmlspecialchars(
                                $history['module']
                            )
                        ) ?>

                    </span>


                    <span class="history-action-badge">

                        <?= strtoupper(
                            htmlspecialchars(
                                $history['action']
                            )
                        ) ?>

                    </span>

                </div>


                <div class="history-message">

                    <strong>

                        <?= htmlspecialchars(
                            $history['user_name']
                        ) ?>

                        (<?= htmlspecialchars(
                            $history['user_role']
                        ) ?>)

                    </strong>

                    <?= htmlspecialchars(
                        $history['message']
                    ) ?>

                </div>


                <div class="history-meta">

                    <i class="bi bi-calendar3"></i>

                    <span>
                        <?= date(
                            'm-d-y',
                            $historyTimestamp
                        ) ?>
                    </span>


                    <span class="history-meta-divider">
                        |
                    </span>


                    <i class="bi bi-clock"></i>

                    <span>
                        <?= date(
                            'h:i A',
                            $historyTimestamp
                        ) ?>
                    </span>

                </div>

            </div>

        </div>


    <?php endwhile; ?>


<?php endif; ?>


</div>


</div>

</div>

<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const historyDateFrom =
            document.querySelector(
                'input[name="date_from"]'
            );

        const historyDateTo =
            document.querySelector(
                'input[name="date_to"]'
            );

        const historyToday =
            "<?= htmlspecialchars(
                $todayDate,
                ENT_QUOTES,
                'UTF-8'
            ) ?>";


        if (
            !historyDateFrom
            ||
            !historyDateTo
        ) {
            return;
        }


        function syncHistoryDateRange() {

            historyDateTo.max =
                historyToday;


            if (
                historyDateFrom.value
            ) {

                historyDateTo.min =
                    historyDateFrom.value;

            } else {

                historyDateTo.removeAttribute(
                    "min"
                );

            }


            if (
                historyDateTo.value
                &&
                historyDateTo.value < historyToday
            ) {

                historyDateFrom.max =
                    historyDateTo.value;

            } else {

                historyDateFrom.max =
                    historyToday;

            }

        }


        historyDateFrom.addEventListener(
            "change",
            syncHistoryDateRange
        );


        historyDateTo.addEventListener(
            "change",
            syncHistoryDateRange
        );


        syncHistoryDateRange();

    }
);

</script>

</body>
</html>