<?php

/* =========================================================
   MONETARY LOAN LEDGER
========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Manila");

error_reporting(E_ALL);
ini_set("display_errors", "0");

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: same-origin");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");


/* =========================================================
   AUTHENTICATION AND MODULE ACCESS
========================================================= */

$userID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

if ($userID <= 0) {
    header("Location: /spis/login.php");
    exit();
}

if (
    (int) ($_SESSION["lending_access"] ?? 0)
    !== 1
) {
    header("Location: /spis/dashboard.php?access=denied");
    exit();
}


/* =========================================================
   CSRF TOKEN FOUNDATION
========================================================= */

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($_SESSION["csrf_token"])
) {
    $_SESSION["csrf_token"] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION["csrf_token"];


/* =========================================================
   DATABASE
========================================================= */

require_once "../../db.php";

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    http_response_code(500);
    exit("Database connection is unavailable.");
}

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* =========================================================
   HELPERS
========================================================= */

function monetaryLoanEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


function monetaryLoanMoney($value): string
{
    return "₱" .
        number_format(
            (float) $value,
            2
        );
}


function monetaryLoanDisplayDate($date): string
{
    if (
        empty($date) ||
        $date === "0000-00-00"
    ) {
        return "—";
    }

    $timestamp =
        strtotime(
            (string) $date
        );

    if ($timestamp === false) {
        return "—";
    }

    return date(
        "m-d-Y",
        $timestamp
    );
}


function monetaryLoanElapsedDays(
    ?string $fromDate,
    DateTimeImmutable $today,
    DateTimeZone $timezone
): int {

    if (
        empty($fromDate) ||
        $fromDate === "0000-00-00"
    ) {
        return 0;
    }

    try {

        $from =
            new DateTimeImmutable(
                $fromDate,
                $timezone
            );

    } catch (Throwable $exception) {

        return 0;

    }

    if ($today <= $from) {
        return 0;
    }

    return (int)
        $from
            ->diff($today)
            ->days;
}


/* =========================================================
   CURRENT USER LENDING PERMISSION
========================================================= */

$lendingPermission =
    "limited";

$permissionStatement =
    mysqli_prepare(
        $conn,
        "
        SELECT lending_permission

        FROM users

        WHERE user_id = ?

        LIMIT 1
        "
    );

if ($permissionStatement) {

    mysqli_stmt_bind_param(
        $permissionStatement,
        "i",
        $userID
    );

    mysqli_stmt_execute(
        $permissionStatement
    );

    $permissionResult =
        mysqli_stmt_get_result(
            $permissionStatement
        );

    $permissionRow =
        mysqli_fetch_assoc(
            $permissionResult
        );

    if (
        strtolower(
            trim(
                (string) (
                    $permissionRow[
                        "lending_permission"
                    ]
                    ?? "limited"
                )
            )
        ) === "full"
    ) {
        $lendingPermission =
            "full";
    }

    mysqli_stmt_close(
        $permissionStatement
    );
}


/* =========================================================
   SERVER DATE
========================================================= */

$manilaTimezone =
    new DateTimeZone(
        "Asia/Manila"
    );

$today =
    new DateTimeImmutable(
        date("Y-m-d"),
        $manilaTimezone
    );

$serverDate =
    $today->format("Y-m-d");


/* =========================================================
   RETRIEVE ACTIVE MONETARY LOANS
========================================================= */

$monetaryLoans = [];
$ledgerLoadError = false;

$loanQuery =
    mysqli_query(
        $conn,
        "
        SELECT

            loan.*,

            member.member_code,
            member.full_name,

            approver.name
                AS approved_by_name

        FROM monetary_loans AS loan

        INNER JOIN members AS member
            ON member.member_id =
               loan.member_id

        LEFT JOIN users AS approver
            ON approver.user_id =
               loan.approved_by

        WHERE loan.is_archived = 0

        ORDER BY
            loan.application_date DESC,
            loan.monetary_loan_id DESC
        "
    );

if ($loanQuery) {

    while (
        $loan =
            mysqli_fetch_assoc(
                $loanQuery
            )
    ) {
        $monetaryLoans[] =
            $loan;
    }

} else {

    $ledgerLoadError = true;

    error_log(
        "Monetary Loan ledger error: " .
        mysqli_error($conn)
    );

}


/* =========================================================
   SIDEBAR
========================================================= */

require_once "../../includes/sidebar.php";

?>

<link
rel="stylesheet"
href="/spis/css/lending/monetary_loan.css?v=<?php echo time(); ?>">

<link
rel="stylesheet"
href="/spis/css/lending/edit_monetary_loan.css?v=<?php echo time(); ?>">

<link
rel="stylesheet"
href="generate_monetary_loan/report_monetary_loan.css?v=<?php echo time(); ?>">


<!-- =======================================================
     PAGE HEADER
======================================================== -->

<div class="page-header">

    <h2>
        LENDING - MONETARY LOAN
    </h2>

    <button
    type="button"
    id="generateMonetaryLoanReportBtn"
    class="btn-generate-monetary-report"
    data-bs-toggle="modal"
    data-bs-target="#generateMonetaryLoanReportModal">

        <i class="bi bi-printer-fill"></i>

        GENERATE REPORT

    </button>

</div>

<hr>


<!-- =======================================================
     TOOLBAR
======================================================== -->

<div class="monetary-loan-toolbar">

    <div class="monetary-loan-toolbar-left">

        <a
        href="../lending.php"
        class="monetary-loan-back-btn"
        title="Back to Lending">

            <i class="bi bi-arrow-left"></i>

        </a>

        <div class="monetary-loan-search-box">

            <i class="bi bi-search"></i>

            <input
            type="text"
            id="searchMonetaryLoan"
            placeholder="Search"
            autocomplete="off">

        </div>

    </div>


    <div class="monetary-loan-toolbar-right">

        <button
        type="button"
        class="monetary-loan-date-filter-btn"
        data-bs-toggle="modal"
        data-bs-target="#monetaryLoanDateFilterModal"
        title="Filter application date">

            <i class="bi bi-calendar3"></i>

        </button>


        <select
        id="monetaryLoanInterestFilter"
        class="monetary-loan-filter">

            <option value="all">
                All Interest
            </option>

            <option value="10">
                10% - 180 Days
            </option>

            <option value="20">
                20% - 360 Days
            </option>

        </select>


        <select
        id="monetaryLoanStatusFilter"
        class="monetary-loan-filter">

            <option value="all">
                All Status
            </option>

            <option value="Pending">
                Pending
            </option>

            <option value="Accepted">
                Accepted
            </option>

            <option value="Rejected">
                Rejected
            </option>

        </select>


        <select
        id="monetaryLoanRemarksFilter"
        class="monetary-loan-filter">

            <option value="all">
                All Remarks
            </option>

            <option value="On Going">
                On Going
            </option>

            <option value="Overdue">
                Overdue
            </option>

            <option value="Paid">
                Paid
            </option>

        </select>


        <button
        type="button"
        class="btn-add-monetary-loan"
        data-bs-toggle="modal"
        data-bs-target="#addMonetaryLoanModal">

            ADD MONETARY LOAN

        </button>

    </div>

</div>


<!-- =======================================================
     MONETARY LOAN LEDGER
======================================================== -->

<div
class="monetary-loan-ledger"
id="monetaryLoanPageData"
data-lending-permission="<?php
echo monetaryLoanEscape(
    $lendingPermission
);
?>"
data-server-date="<?php
echo monetaryLoanEscape(
    $serverDate
);
?>"
data-csrf-token="<?php
echo monetaryLoanEscape(
    $csrfToken
);
?>">


    <!-- LEDGER HEADER -->

    <div class="monetary-loan-ledger-header">

        <div>LOAN ID</div>

        <div>MEMBER</div>

        <div>LOAN TYPE</div>

        <div>PRINCIPAL</div>

        <div>INTEREST</div>

        <div>TOTAL AMOUNT</div>

        <div>BALANCE</div>

        <div>DUE DATE</div>

        <div>APPROVED BY</div>

        <div>STATUS</div>

        <div>REMARKS</div>

        <div>ACTIONS</div>

    </div>


    <!-- LEDGER BODY -->

    <div class="monetary-loan-ledger-body">

        <?php if ($ledgerLoadError) { ?>

            <div class="monetary-loan-empty">

                Unable to load the Monetary Loan records.

            </div>

        <?php } elseif (count($monetaryLoans) === 0) { ?>

            <div class="monetary-loan-empty">

                No Monetary Loan records found.

            </div>

        <?php } else { ?>

            <?php foreach ($monetaryLoans as $loan) { ?>

                <?php

                $monetaryLoanID =
                    (int)
                    $loan["monetary_loan_id"];

                $loanDisplayID =
                    "ML-" .
                    str_pad(
                        (string) $monetaryLoanID,
                        6,
                        "0",
                        STR_PAD_LEFT
                    );

                $status =
                    (string)
                    $loan["status"];

                $interestRate =
                    (float)
                    $loan["interest_rate"];

                $termDays =
                    max(
                        1,
                        (int)
                        $loan["term_days"]
                    );

                $isLongTermLoan =
                    trim(
                        (string) (
                            $loan["loan_purpose"]
                            ?? ""
                        )
                    ) ===
                    "Long-Term Loan";

                $loanTermYears =
                    isset(
                        $loan["loan_term_years"]
                    ) &&
                    $loan["loan_term_years"] !== null
                        ? (int) $loan["loan_term_years"]
                        : 0;

                $paymentPeriods =
                    $isLongTermLoan &&
                    $loanTermYears > 0 &&
                    in_array(
                        $termDays,
                        [180, 360],
                        true
                    )
                        ? $loanTermYears *
                        (
                            $termDays === 180
                                ? 2
                                : 1
                        )
                        : 0;

                $outstandingPrincipal =
                    round(
                        (float)
                        $loan[
                            "outstanding_principal_amount"
                        ],
                        2
                    );

                $storedUnpaidInterest =
                    round(
                        (float)
                        $loan[
                            "unpaid_interest_amount"
                        ],
                        2
                    );

                $currentBalance =
                    null;

                $newAccruedInterest =
                    0.00;

                $elapsedDays =
                    0;

                $remarks =
                    "—";

                if ($status === "Accepted") {

                    $calculationFromDate =
                        !empty(
                            $loan["last_interest_date"]
                        )
                            ? (string)
                              $loan["last_interest_date"]
                            : (string)
                              $loan["release_date"];

                    $elapsedDays =
                        monetaryLoanElapsedDays(
                            $calculationFromDate,
                            $today,
                            $manilaTimezone
                        );

                    if (
                        $outstandingPrincipal > 0 &&
                        $elapsedDays > 0
                    ) {

                        $newAccruedInterest =
                            round(
                                $outstandingPrincipal *
                                ($interestRate / 100) *
                                $elapsedDays /
                                $termDays,
                                2
                            );

                    }

                    $currentBalance =
                        round(
                            $outstandingPrincipal +
                            $storedUnpaidInterest +
                            $newAccruedInterest,
                            2
                        );

                    $isPaid =
                        (
                            $outstandingPrincipal <= 0.009 &&
                            $storedUnpaidInterest <= 0.009 &&
                            $newAccruedInterest <= 0.009 &&
                            !empty(
                                $loan["fully_paid_at"]
                            )
                        );

                    if ($isPaid) {

                        $remarks =
                            "Paid";

                    } elseif (
                        !empty($loan["due_date"]) &&
                        $serverDate >
                        $loan["due_date"]
                    ) {

                        $remarks =
                            "Overdue";

                    } else {

                        $remarks =
                            "On Going";

                    }

                }

                $statusClass =
                    "monetary-status-" .
                    strtolower(
                        $status
                    );

                $remarksClass =
                    $remarks === "Paid"
                        ? "monetary-remarks-paid"
                        : (
                            $remarks === "Overdue"
                                ? "monetary-remarks-overdue"
                                : (
                                    $remarks === "On Going"
                                        ? "monetary-remarks-ongoing"
                                        : "monetary-remarks-none"
                                )
                        );

                $rowStateClass = "";
                    if ($remarks === "Overdue") {
                        $rowStateClass =
                            "monetary-loan-row-overdue";
                    }        

                $canArchive =
                    (
                        $lendingPermission === "full" &&
                        (
                            $status === "Rejected" ||
                            (
                                $status === "Accepted" &&
                                $remarks === "Paid"
                            )
                        )
                    );

                $approvedByName =
                    trim(
                        (string) (
                            $loan["approved_by_name"]
                            ?? ""
                        )
                    );

                ?>


                <div
                class="monetary-loan-row <?php
                echo monetaryLoanEscape(
                    $rowStateClass
                );
                ?>"
                data-loan-id="<?php
                echo $monetaryLoanID;
                ?>"
                data-search="<?php
                echo monetaryLoanEscape(
                    strtolower(
                        $loanDisplayID . " " .
                        $loan["member_code"] . " " .
                        $loan["full_name"] . " " .
                        $loan["loan_purpose"] . " " .
                        $approvedByName . " " .
                        $status . " " .
                        $remarks
                    )
                );
                ?>"
                data-interest="<?php
                echo monetaryLoanEscape(
                    number_format(
                        $interestRate,
                        0,
                        ".",
                        ""
                    )
                );
                ?>"
                data-status="<?php
                echo monetaryLoanEscape(
                    $status
                );
                ?>"
                data-remarks="<?php
                echo monetaryLoanEscape(
                    $remarks
                );
                ?>"
                data-application-date="<?php
                echo monetaryLoanEscape(
                    $loan["application_date"]
                );
                ?>">


                    <!-- LOAN ID -->

                    <div class="monetary-loan-id-cell">

                        <strong>
                            <?php
                            echo monetaryLoanEscape(
                                $loanDisplayID
                            );
                            ?>
                        </strong>

                        <small>
                            <?php
                            echo monetaryLoanDisplayDate(
                                $loan[
                                    "application_date"
                                ]
                            );
                            ?>
                        </small>

                        <?php

                        if (
                            $remarks ===
                            "Overdue"
                        ) {

                        ?>

                            <span class="monetary-loan-id-overdue-badge">

                                <i class="bi bi-calendar-x"></i>

                                Overdue

                            </span>

                        <?php

                        }

                        ?>

                    </div>


                    <!-- MEMBER -->

                    <div class="monetary-loan-member-cell">

                        <strong>
                            <?php
                            echo monetaryLoanEscape(
                                $loan["full_name"]
                            );
                            ?>
                        </strong>

                        <small>
                            <?php
                            echo monetaryLoanEscape(
                                $loan["member_code"]
                            );
                            ?>
                        </small>

                    </div>


                    <!-- LOAN TYPE -->

                    <div
                    class="monetary-loan-purpose-cell"
                    title="<?php
                    echo monetaryLoanEscape(
                        $loan["loan_purpose"]
                    );
                    ?>">

                        <?php
                        echo monetaryLoanEscape(
                            $loan["loan_purpose"]
                        );
                        ?>

                    </div>


                    <!-- PRINCIPAL -->

                    <div class="monetary-loan-money-cell">

                        <?php
                        echo monetaryLoanMoney(
                            $loan["principal_amount"]
                        );
                        ?>

                    </div>


                    <!-- INTEREST -->

                    <div class="monetary-loan-interest-cell">

                        <strong>

                            <?php
                            echo number_format(
                                $interestRate,
                                0
                            );
                            ?>%

                        </strong>

                        <small>

                            <?php if (
                                $isLongTermLoan &&
                                $loanTermYears > 0 &&
                                $paymentPeriods > 0
                            ) { ?>

                                <?php
                                echo monetaryLoanEscape(
                                    $loanTermYears .
                                    (
                                        $loanTermYears === 1
                                            ? " Year"
                                            : " Years"
                                    ) .
                                    " · " .
                                    $paymentPeriods .
                                    (
                                        $paymentPeriods === 1
                                            ? " Payment"
                                            : " Payments"
                                    ) .
                                    " · Every " .
                                    $termDays .
                                    " Days"
                                );
                                ?>

                            <?php } else { ?>

                                <?php
                                echo $termDays;
                                ?> Days

                            <?php } ?>

                        </small>

                    </div>


                    <!-- TOTAL AMOUNT -->

                    <div class="monetary-loan-money-cell">

                        <?php
                        echo monetaryLoanMoney(
                            $loan[
                                "scheduled_total_amount"
                            ]
                        );
                        ?>

                    </div>


                    <!-- CURRENT BALANCE -->

                    <div class="monetary-loan-money-cell">

                        <?php

                        if ($currentBalance === null) {

                            echo "—";

                        } else {

                            echo monetaryLoanMoney(
                                $currentBalance
                            );

                        }

                        ?>

                    </div>


                    <!-- DUE DATE -->

                    <div class="monetary-loan-date-cell">

                        <?php

                        if ($status === "Pending") {

                            echo "Upon acceptance";

                        } elseif (
                            $status === "Rejected"
                        ) {

                            echo "—";

                        } else {

                            echo monetaryLoanDisplayDate(
                                $loan["due_date"]
                            );

                        }

                        ?>

                    </div>


                    <!-- APPROVED BY -->

                    <div class="monetary-loan-approved-cell">

                        <?php if (
                            $status === "Accepted" &&
                            $approvedByName !== ""
                        ) { ?>

                            <strong>

                                <?php
                                echo monetaryLoanEscape(
                                    $approvedByName
                                );
                                ?>

                            </strong>

                            <small>

                                <?php
                                echo monetaryLoanDisplayDate(
                                    $loan["release_date"]
                                );
                                ?>

                            </small>

                        <?php } else { ?>

                            —

                        <?php } ?>

                    </div>


                    <!-- STATUS -->

                    <div>

                        <span class="<?php
                        echo monetaryLoanEscape(
                            $statusClass
                        );
                        ?>">

                            <?php
                            echo monetaryLoanEscape(
                                $status
                            );
                            ?>

                        </span>

                    </div>


                    <!-- REMARKS -->

                    <div>

                        <span class="<?php
                        echo monetaryLoanEscape(
                            $remarksClass
                        );
                        ?>">

                            <?php
                            echo monetaryLoanEscape(
                                $remarks
                            );
                            ?>

                        </span>

                    </div>


                    <!-- ACTIONS -->

                    <div class="monetary-loan-actions-cell">

                        <button
                        type="button"
                        class="monetary-loan-row-action monetary-loan-edit-btn"
                        data-loan-id="<?php
                        echo $monetaryLoanID;
                        ?>"
                        title="<?php
                        echo $status === "Pending"
                            ? "Edit Monetary Loan"
                            : "View Monetary Loan";
                        ?>">

                            <i class="bi bi-pencil-fill"></i>

                        </button>


                        <button
                        type="button"
                        class="monetary-loan-row-action monetary-loan-archive-btn"
                        data-loan-id="<?php
                        echo $monetaryLoanID;
                        ?>"
                        title="<?php
                        echo $canArchive
                            ? "Archive Monetary Loan"
                            : "Only Paid or Rejected loans can be archived";
                        ?>"
                        <?php
                        echo $canArchive
                            ? ""
                            : "disabled";
                        ?>>

                            <i class="bi bi-archive-fill"></i>

                        </button>

                    </div>

                </div>

            <?php } ?>

        <?php } ?>


        <div
        id="monetaryLoanFilteredEmpty"
        class="monetary-loan-empty"
        style="display:none;">

            No Monetary Loan records match the selected filters.

        </div>

    </div>

</div>


<!-- =======================================================
     DATE FILTER MODAL
======================================================== -->

<div
class="modal fade"
id="monetaryLoanDateFilterModal"
tabindex="-1"
aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content monetary-loan-date-window">

            <div class="monetary-loan-date-header">

                <h1>
                    FILTER APPLICATION DATE
                </h1>

                <button
                type="button"
                class="monetary-loan-date-close-btn"
                data-bs-dismiss="modal"
                aria-label="Close">

                    ×

                </button>

            </div>

            <hr>

            <div class="monetary-loan-date-field">

                <label for="monetaryLoanStartDateFilter">
                    START DATE
                </label>

                <input
                type="date"
                id="monetaryLoanStartDateFilter"
                class="monetary-loan-date-input">

            </div>

            <div class="monetary-loan-date-field">

                <label for="monetaryLoanEndDateFilter">
                    END DATE
                </label>

                <input
                type="date"
                id="monetaryLoanEndDateFilter"
                class="monetary-loan-date-input">

            </div>

            <div class="monetary-loan-date-actions">

                <button
                type="button"
                id="monetaryLoanClearDateFilter"
                class="monetary-loan-clear-date-btn"
                data-bs-dismiss="modal">

                    CLEAR FILTER

                </button>

                <button
                type="button"
                id="monetaryLoanApplyDateFilter"
                class="monetary-loan-apply-date-btn"
                data-bs-dismiss="modal">

                    APPLY

                </button>

            </div>

        </div>

    </div>

</div>


<?php

require_once
    "add_monetary_loan_modal.php";

require_once
    "edit_monetary_loan_modal.php";

require_once
    "generate_monetary_loan/report_monetary_loan_modal.php";

?>

<script src="generate_monetary_loan/report_monetary_loan.js?v=<?php echo time(); ?>"></script>

<script src="/spis/js/monetary_loan.js?v=<?php echo time(); ?>"></script>

<script src="/spis/js/edit_monetary_loan.js?v=<?php echo time(); ?>"></script>

<script src="/spis/js/monetary_loan_ledger.js?v=<?php echo time(); ?>"></script>

</body>
</html>