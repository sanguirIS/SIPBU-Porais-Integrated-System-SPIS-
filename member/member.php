<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');
error_reporting(E_ALL);
ini_set('display_errors', 0);

include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_SESSION['members_access']) || (int)$_SESSION['members_access'] !== 1) {
    http_response_code(403);
    exit('Access denied.');
}

include '../includes/sidebar.php';

function memberPageEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function memberPageDisplayDate($value): string
{
    $value = trim((string)$value);

    if ($value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp === false
        ? '—'
        : date('m-d-Y', $timestamp);
}

function memberPageContact($value): string
{
    $digits = preg_replace('/\D/', '', (string)$value);

    if ($digits === '') {
        return '—';
    }

    if (strlen($digits) === 11) {
        return substr($digits, 0, 4)
            . '-'
            . substr($digits, 4, 3)
            . '-'
            . substr($digits, 7);
    }

    return $digits;
}

function memberPageTin($value): string
{
    $tin = preg_replace('/\D/', '', (string)$value);

    if ($tin === '') {
        return '—';
    }

    if (strlen($tin) === 9) {
        return substr($tin, 0, 3)
            . '-'
            . substr($tin, 3, 3)
            . '-'
            . substr($tin, 6, 3);
    }

    if (strlen($tin) === 12) {
        return substr($tin, 0, 3)
            . '-'
            . substr($tin, 3, 3)
            . '-'
            . substr($tin, 6, 3)
            . '-'
            . substr($tin, 9, 3);
    }

    return $tin;
}

function memberPageRsbsa($value): string
{
    $compact =
        strtoupper(
            (string)preg_replace(
                '/[^A-Za-z0-9]/',
                '',
                trim(
                    (string)$value
                )
            )
        );


    if(
        $compact === ''
    ){
        return '—';
    }


    $compact =
        substr(
            $compact,
            0,
            26
        );


    $parts = [

        substr(
            $compact,
            0,
            2
        ),

        substr(
            $compact,
            2,
            2
        ),

        substr(
            $compact,
            4,
            2
        ),

        substr(
            $compact,
            6,
            3
        ),

        substr(
            $compact,
            9
        )

    ];


    $parts =
        array_values(
            array_filter(
                $parts,
                static fn($part) =>
                    $part !== ''
            )
        );


    return implode(
        '-',
        $parts
    );
}

function memberPageFarmField($value): string
{
    if ($value === null || trim((string)$value) === '') {
        return '—';
    }

    $formatted = number_format((float)$value, 4, '.', '');
    $formatted = rtrim(rtrim($formatted, '0'), '.');

    return $formatted . ' ha';
}


/* =========================================================
   PAGINATION
========================================================= */

$limit = 20;

$page = isset($_GET['page'])
    ? max(1, (int)$_GET['page'])
    : 1;

$offset = ($page - 1) * $limit;


/* =========================================================
   SEARCH + FILTERS
========================================================= */

$search =
    trim(
        (string)(
            $_GET['search']
            ??
            ''
        )
    );


$statusFilter =
    trim(
        (string)(
            $_GET['status']
            ??
            'all'
        )
    );


$classificationFilter =
    trim(
        (string)(
            $_GET['classification']
            ??
            'all'
        )
    );


$allowedStatusFilters = [
    'all',
    'Active',
    'Inactive'
];


$allowedClassificationFilters = [
    'all',
    'Regular',
    'Irregular'
];


if(
    !in_array(
        $statusFilter,
        $allowedStatusFilters,
        true
    )
){
    $statusFilter = 'all';
}


if(
    !in_array(
        $classificationFilter,
        $allowedClassificationFilters,
        true
    )
){
    $classificationFilter = 'all';
}


$searchSql =
    mysqli_real_escape_string(
        $conn,
        $search
    );


$where =
    'WHERE archived = 0';


if(
    $search !== ''
){

    $where .= "
    AND (
        member_code LIKE '%$searchSql%'
        OR full_name LIKE '%$searchSql%'
        OR date_registered LIKE '%$searchSql%'
        OR birth_date LIKE '%$searchSql%'
        OR gender LIKE '%$searchSql%'
        OR address LIKE '%$searchSql%'
        OR tin_no LIKE '%$searchSql%'
        OR rsbsa_no LIKE '%$searchSql%'
        OR contact_no LIKE '%$searchSql%'
        OR social_media_name LIKE '%$searchSql%'
        OR farm_field_hectares LIKE '%$searchSql%'
        OR cbu_balance LIKE '%$searchSql%'
        OR status LIKE '%$searchSql%'
        OR classification LIKE '%$searchSql%'
    )";
}


if(
    $statusFilter !== 'all'
){

    $safeStatusFilter =
        mysqli_real_escape_string(
            $conn,
            $statusFilter
        );


    $where .= "
    AND UPPER(status) =
        UPPER('$safeStatusFilter')
    ";
}


if(
    $classificationFilter !== 'all'
){

    $safeClassificationFilter =
        mysqli_real_escape_string(
            $conn,
            $classificationFilter
        );


    $where .= "
    AND UPPER(classification) =
        UPPER('$safeClassificationFilter')
    ";
}


/* =========================================================
   MEMBERS
========================================================= */

$sql = "
SELECT *
FROM members
$where
ORDER BY member_id ASC
LIMIT $offset,$limit
";

$result = mysqli_query(
    $conn,
    $sql
);

$countQuery = mysqli_query(
    $conn,
    "
    SELECT COUNT(*) AS total
    FROM members
    $where
    "
);

$totalRows = $countQuery
    ? (int)(mysqli_fetch_assoc($countQuery)['total'] ?? 0)
    : 0;

$totalPages = max(
    1,
    (int)ceil(
        $totalRows / $limit
    )
);

if ($page > $totalPages) {


    $page = $totalPages;

    $offset = ($page - 1) * $limit;

    $sql = "
    SELECT *
    FROM members
    $where
    ORDER BY member_id ASC
    LIMIT $offset,$limit
    ";

    $result = mysqli_query(
        $conn,
        $sql
    );
}

$prevPage = $page - 1;
$nextPage = $page + 1;

?>


<link
rel="stylesheet"
href="../css/member.css?v=<?= time(); ?>">

<link
rel="stylesheet"
href="../css/member_modal.css?v=<?= time(); ?>">


<!-- =====================================================
     PAGE HEADER
===================================================== -->

<div class="page-header">

    <h2>
        MEMBERS
    </h2>

</div>


<hr>


<!-- =====================================================
     TOOLBAR
===================================================== -->

<div class="member-toolbar">


    <div class="toolbar-left">


        <!-- SEARCH -->

        <div class="search-box">

            <i class="bi bi-search"></i>


            <form
            id="memberSearchForm"
            method="GET">

                <input
                type="text"
                id="searchMember"
                name="search"
                class="search-input"
                placeholder="Search Members..."
                value="<?= memberPageEscape($search); ?>">


                <input
                type="hidden"
                name="status"
                value="<?= memberPageEscape($statusFilter); ?>">


                <input
                type="hidden"
                name="classification"
                value="<?= memberPageEscape($classificationFilter); ?>">

            </form>

        </div>


        <!-- STATUS FILTER -->

        <div class="filter-wrapper">

            <input
            type="text"
            id="selectedStatus"
            class="filter-input"
            value="<?= memberPageEscape(
                $statusFilter === 'all'
                    ? 'All Status'
                    : $statusFilter
            ); ?>"
            readonly>


            <input
            type="hidden"
            id="statusFilter"
            value="<?= memberPageEscape($statusFilter); ?>">


            <div
            id="statusPopup"
            class="product-popup">

                <div
                class="status-item"
                data-value="all">
                    All Status
                </div>

                <div
                class="status-item"
                data-value="Active">
                    Active
                </div>

                <div
                class="status-item"
                data-value="Inactive">
                    Inactive
                </div>

            </div>

        </div>


        <!-- CLASSIFICATION FILTER -->

        <div class="filter-wrapper">

            <input
            type="text"
            id="selectedClassification"
            class="filter-input"
            value="<?= memberPageEscape(
                $classificationFilter === 'all'
                    ? 'All Classification'
                    : $classificationFilter
            ); ?>"
            readonly>


            <input
            type="hidden"
            id="classificationFilter"
            value="<?= memberPageEscape($classificationFilter); ?>">


            <div
            id="classificationPopup"
            class="product-popup">

                <div
                class="classification-item"
                data-value="all">
                    All Classification
                </div>

                <div
                class="classification-item"
                data-value="Regular">
                    Regular
                </div>

                <div
                class="classification-item"
                data-value="Irregular">
                    Irregular
                </div>

            </div>

        </div>


    </div>


    <button
    class="btn-add-member"
    data-bs-toggle="modal"
    data-bs-target="#addMemberModal">

        <i class="bi bi-person-plus-fill"></i>

        ADD MEMBER

    </button>


</div>


<!-- =====================================================
     LEDGER HEADER
===================================================== -->

<div class="member-header">

    <div>
        MEMBER
    </div>

    <div>
        DATE
    </div>

    <div>
        CONTACT
    </div>

    <div>
        ADDRESS
    </div>

    <div>
        IDENTIFICATION
    </div>

    <div>
        CBU & FARM FIELD
    </div>

    <div>
        STATUS
    </div>

    <div>
        CLASSIFICATION
    </div>

</div>


<!-- =====================================================
     LEDGER BODY
===================================================== -->

<div
id="memberBody"
class="member-body">


<?php if ($result): ?>


<?php

while (
    $member =
        mysqli_fetch_assoc(
            $result
        )
):


    $memberID =
        (int)(
            $member['member_id']
            ??
            0
        );


    $memberCode =
        trim(
            (string)(
                $member['member_code']
                ??
                ''
            )
        );


    $memberName =
        trim(
            (string)(
                $member['full_name']
                ??
                ''
            )
        );


    $gender =
        trim(
            (string)(
                $member['gender']
                ??
                ''
            )
        );


    $address =
        trim(
            (string)(
                $member['address']
                ??
                ''
            )
        );


    $social =
        trim(
            (string)(
                $member['social_media_name']
                ??
                ''
            )
        );


    $rsbsa =
        trim(
            (string)(
                $member['rsbsa_no']
                ??
                ''
            )
        );

    $rsbsaDisplay =
        memberPageRsbsa(
            $rsbsa
        );

    $profilePath =
        trim(
            (string)(
                $member['profile_image']
                ??
                ''
            )
        );


    $memberStatus =
        strtoupper(
            trim(
                (string)(
                    $member['status']
                    ??
                    ''
                )
            )
        );


    $memberClassification =
        ucfirst(
            strtolower(
                trim(
                    (string)(
                        $member['classification']
                        ??
                        'Irregular'
                    )
                )
            )
        );


    /* =====================================================
       MEMBERSHIP DATE
    ===================================================== */

    $membershipDateForEdit =
        '';


    if(
        !empty(
            $member['date_registered']
        )
    ){

        $membershipTs =
            strtotime(
                (string)$member['date_registered']
            );


        if(
            $membershipTs !== false
        ){

            $membershipDateForEdit =
                date(
                    'Y-m-d',
                    $membershipTs
                );

        }

    }


    $membershipDateDisplay =
        memberPageDisplayDate(
            $member['date_registered']
            ??
            ''
        );


    $birthDateDisplay =
        memberPageDisplayDate(
            $member['birth_date']
            ??
            ''
        );


    $contactDisplay =
        memberPageContact(
            $member['contact_no']
            ??
            ''
        );


    $tinDisplay =
        memberPageTin(
            $member['tin_no']
            ??
            ''
        );


    $farmFieldDisplay =
        memberPageFarmField(
            $member['farm_field_hectares']
            ??
            null
        );


    $currentCbu =
        (float)(
            $member['cbu_balance']
            ??
            0
        );

?>


<div
class="member-row"

data-status="<?= memberPageEscape(
    strtolower(
        $memberStatus
    )
); ?>"

data-classification="<?= memberPageEscape(
    strtolower(
        $memberClassification
    )
); ?>">


    <!-- =================================================
         MEMBER
    ================================================== -->

    <div
    class="member-column member-main-column">


        <div class="member-profile-wrapper">


            <button
            type="button"
            class="member-edit-btn"

            data-id="<?= $memberID; ?>"

            data-image="<?= $profilePath !== ''
                ? memberPageEscape(
                    'serve_member_file.php?type=profile&member_id='
                    .
                    $memberID
                )
                : '';
            ?>"

            data-name="<?= memberPageEscape(
                $memberName
            ); ?>"

            data-gender="<?= memberPageEscape(
                $gender
            ); ?>"

            data-membership-date="<?= memberPageEscape(
                $membershipDateForEdit
            ); ?>"

            data-birth="<?= memberPageEscape(
                $member['birth_date']
                ??
                ''
            ); ?>"

            data-address="<?= memberPageEscape(
                $address
            ); ?>"

            data-tin="<?= memberPageEscape(
                $member['tin_no']
                ??
                ''
            ); ?>"

            data-rsbsa="<?= memberPageEscape(
                $rsbsa
            ); ?>"

            data-contact="<?= memberPageEscape(
                $member['contact_no']
                ??
                ''
            ); ?>"

            data-social="<?= memberPageEscape(
                $social
            ); ?>"

            data-farm-field="<?= memberPageEscape(
                $member['farm_field_hectares']
                ??
                ''
            ); ?>"

            data-cbu="<?= memberPageEscape(
                number_format(
                    $currentCbu,
                    2,
                    '.',
                    ''
                )
            ); ?>"

            data-status="<?= memberPageEscape(
                $memberStatus
            ); ?>"

            data-classification="<?= memberPageEscape(
                $memberClassification
            ); ?>"

            title="Edit Member">


                <i class="bi bi-pencil-fill"></i>


            </button>


<?php

if(
    $profilePath !== ''
    &&
    file_exists(
        '../'
        .
        $profilePath
    )
):

?>


            <img
            src="<?= memberPageEscape(
                'serve_member_file.php?type=profile&member_id='
                .
                $memberID
                .
                '&v='
                .
                time()
            ); ?>"
            class="member-profile"
            alt="<?= memberPageEscape(
                $memberName
            ); ?>">


<?php

else:

?>


            <div class="member-avatar">

                <?= memberPageEscape(
                    strtoupper(
                        substr(
                            $memberName,
                            0,
                            1
                        )
                    )
                ); ?>

            </div>


<?php

endif;

?>


        </div>


        <div class="member-main-text">


            <strong class="member-primary-value">

                <?= memberPageEscape(
                    $memberName !== ''
                    ?
                    $memberName
                    :
                    '—'
                ); ?>

            </strong>


            <span
            class="member-secondary-value member-code-gender">


                <span>

                    <?= memberPageEscape(
                        $memberCode !== ''
                        ?
                        $memberCode
                        :
                        '—'
                    ); ?>

                </span>


<?php

if(
    $gender !== ''
):

?>


                <span class="member-separator">
                    •
                </span>


                <span>

                    <?= memberPageEscape(
                        $gender
                    ); ?>

                </span>


<?php

endif;

?>


            </span>


        </div>


    </div>


    <!-- =================================================
        MEMBERSHIP
    ================================================== -->

    <div class="member-column member-date-column">

        <div class="member-date-item">

            <span class="member-date-label">
                Membership Date
            </span>

            <strong class="member-date-value">
                <?= memberPageEscape(
                    $membershipDateDisplay
                ); ?>
            </strong>

        </div>


        <div class="member-date-item">

            <span class="member-date-label">
                Birth Date
            </span>

            <span class="member-date-value">
                <?= memberPageEscape(
                    $birthDateDisplay
                ); ?>
            </span>

        </div>

    </div>


    <!-- =================================================
        CONTACT
    ================================================== -->

    <div class="member-column member-contact-column">


        <div class="member-date-item">

            <span class="member-date-label">
                Contact Number
            </span>

            <strong class="member-date-value">
                <?= memberPageEscape(
                    $contactDisplay
                ); ?>
            </strong>

        </div>


        <div class="member-date-item">

            <span class="member-date-label">
                Facebook
            </span>

            <span class="member-date-value member-contact-facebook">
                <?= memberPageEscape(
                    $social !== ''
                    ?
                    $social
                    :
                    '—'
                ); ?>
            </span>

        </div>


    </div>


    <!-- =================================================
         LOCATION
    ================================================== -->

    <div
    class="member-column member-location-column">


        <span class="member-location-value">

            <?= memberPageEscape(
                $address !== ''
                ?
                $address
                :
                '—'
            ); ?>

        </span>


    </div>


    <!-- =================================================
         IDENTIFICATION
    ================================================== -->

    <div
    class="member-column member-identification-column">


        <span class="member-id-row">

            <span class="member-detail-label">
                RSBSA:
            </span>

            <span
            class="member-id-value"
            style="overflow-wrap:anywhere; word-break:break-word;">

                <?= memberPageEscape(
                    $rsbsaDisplay
                ); ?>

            </span>

        </span>


        <span class="member-id-row">

            <span class="member-detail-label">
                TIN:
            </span>

            <span class="member-id-value">

                <?= memberPageEscape(
                    $tinDisplay
                ); ?>

            </span>

        </span>


    </div>


    <!-- =================================================
         CBU & FARM
    ================================================== -->

    <div
    class="member-column member-cbu-column">


        <strong class="member-cbu-value">

            ₱<?= memberPageEscape(
                number_format(
                    $currentCbu,
                    2
                )
            ); ?>

        </strong>


        <span
        class="member-secondary-value member-wrap-value">

            <span class="member-detail-label">
                Farm:
            </span>

            <?= memberPageEscape(
                $farmFieldDisplay
            ); ?>

        </span>


    </div>


    <!-- =================================================
         STATUS
    ================================================== -->

    <div
    class="member-column member-chip-column">


        <span
        class="member-state-chip <?= strtolower(
            $memberStatus
        ) === 'active'
            ?
            'member-state-active'
            :
            'member-state-inactive'; ?>">


            <span class="member-state-dot"></span>


            <?= memberPageEscape(
                ucfirst(
                    strtolower(
                        $memberStatus !== ''
                        ?
                        $memberStatus
                        :
                        'Inactive'
                    )
                )
            ); ?>


        </span>


    </div>


    <!-- =================================================
         CLASSIFICATION
    ================================================== -->

    <div
    class="member-column member-chip-column">


        <span
        class="member-state-chip <?= strtolower(
            $memberClassification
        ) === 'regular'
            ?
            'member-state-regular'
            :
            'member-state-irregular'; ?>">


            <span class="member-state-dot"></span>


            <?= memberPageEscape(
                $memberClassification
            ); ?>


        </span>


    </div>


</div>


<?php

endwhile;

?>


<?php endif; ?>


<?php

if(
    !$result
    ||
    mysqli_num_rows(
        $result
    ) === 0
):

?>


<div class="member-empty">

    <div class="member-empty-state">

        <i class="bi bi-people"></i>

        <h4>
            No Members Found
        </h4>

        <p>
            No member records are available.
        </p>

    </div>

</div>


<?php endif; ?>


</div>

<!-- =====================================================
     BOTTOM PAGINATION
===================================================== -->

<?php if($totalPages > 1): ?>

<div
id="memberPagination"
class="pagination-container">

    <a
    class="page-btn <?= $page <= 1
        ? 'disabled'
        : ''; ?>"

    href="<?= $page <= 1
    ? '#'
    : '?'
        . http_build_query([
            'page' => $prevPage,
            'search' => $search,
            'status' => $statusFilter,
            'classification' => $classificationFilter
        ]); ?>"

    aria-label="Previous page">

        <i class="bi bi-chevron-left"></i>

    </a>


    <div class="page-indicator">

        Page
        <?= $page; ?>
        of
        <?= $totalPages; ?>

    </div>


    <a
    class="page-btn <?= $page >= $totalPages
        ? 'disabled'
        : ''; ?>"

    href="<?= $page >= $totalPages
    ? '#'
    : '?'
        . http_build_query([
            'page' => $nextPage,
            'search' => $search,
            'status' => $statusFilter,
            'classification' => $classificationFilter
        ]); ?>"

    aria-label="Next page">

        <i class="bi bi-chevron-right"></i>

    </a>

</div>

<?php endif; ?>

<!-- =====================================================
     MEMBER MODAL
===================================================== -->

<?php

include 'add_member_modal.php';

?>


<!-- =====================================================
     SUCCESS MESSAGE
===================================================== -->

<?php

if(
    isset(
        $_GET['success']
    )
):

?>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function(){

        let title =
            '';

        switch(
            <?= json_encode(
                (string)(
                    $_GET['success']
                    ??
                    ''
                ),
                JSON_UNESCAPED_UNICODE
                |
                JSON_UNESCAPED_SLASHES
            ); ?>
        ){

            case 'added':

                title =
                    'Member Added Successfully!';

            break;


            case 'updated':

                title =
                    'Member Updated Successfully!';

            break;


            case 'archived':

                title =
                    'Member Archived Successfully!';

            break;

        }


        if(
            title !== ''
        ){

            Swal.fire({

                icon:
                    'success',

                title:
                    title,

                showConfirmButton:
                    false,

                timer:
                    1800,

                timerProgressBar:
                    true

            })
            .then(
                () => {

                    const cleanUrl =
                        new URL(
                            window.location.href
                        );


                    /*
                    * Remove only the temporary
                    * SweetAlert flag.
                    *
                    * Keep:
                    * page
                    * search
                    */

                    cleanUrl.searchParams.delete(
                        "success"
                    );


                    window.history.replaceState(
                        {},
                        document.title,
                        cleanUrl.pathname
                        +
                        cleanUrl.search
                    );

                }
            );

        }

    }
);

</script>


<?php

endif;

?>


<!-- =====================================================
     VALIDATION MESSAGE
===================================================== -->

<?php

if(
    isset(
        $_GET['error']
    )
):


    $message =
        '';


    switch(
        $_GET['error']
    ){

        case 'tin':

            $message =
                'TIN Number must contain exactly 9 or 12 digits.';

        break;


        case 'rsbsa':

            $message =
                'Invalid RSBSA Number.';

        break;


        case 'contact':

            $message =
                'Invalid Contact Number.';

        break;

    }

?>


<?php

if(
    $message !== ''
):

?>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function(){

        const modal =
            new bootstrap.Modal(
                document.getElementById(
                    'addMemberModal'
                )
            );


        modal.show();


        Swal.fire({

            icon:
                'warning',

            title:
                'Validation Error',

            text:
                <?= json_encode(
                    $message,
                    JSON_UNESCAPED_UNICODE
                    |
                    JSON_UNESCAPED_SLASHES
                ); ?>

        });

    }
);

</script>


<?php

endif;

endif;

?>


<script src="../assets/sweetalert/sweetalert2.min.js"></script>

<script src="../assets/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="../js/sidebar.js"></script>

<script src="../js/member.js"></script>


</body>

</html>