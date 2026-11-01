<?php

/* =========================================================
   LENDING ARCHIVE PARTIAL
   Separate Product Loan and Monetary Loan views
========================================================= */

if (
    !isset($conn) ||
    !($conn instanceof mysqli)
) {
    exit;
}

mysqli_set_charset(
    $conn,
    "utf8mb4"
);

mysqli_query(
    $conn,
    "SET SESSION group_concat_max_len = 20000"
);

if (
    session_status() ===
    PHP_SESSION_NONE
) {
    session_start();
}

if (
    empty($_SESSION["csrf_token"]) ||
    !is_string($_SESSION["csrf_token"])
) {
    $_SESSION["csrf_token"] =
        bin2hex(
            random_bytes(32)
        );
}

$lendingArchiveCsrfToken =
    $_SESSION["csrf_token"];


/* =========================================================
   HELPERS
========================================================= */

if (!function_exists("lendingArchiveEscape")) {

    function lendingArchiveEscape(
        $value
    ): string {

        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            "UTF-8"
        );

    }

}


if (!function_exists("lendingArchiveMoney")) {

    function lendingArchiveMoney(
        $value
    ): string {

        return "₱" .
            number_format(
                (float) $value,
                2
            );

    }

}


if (!function_exists("lendingArchiveDate")) {

    function lendingArchiveDate(
        $value,
        bool $includeTime = false
    ): string {

        if (
            empty($value) ||
            $value === "0000-00-00" ||
            $value === "0000-00-00 00:00:00"
        ) {
            return "—";
        }

        $timestamp =
            strtotime(
                (string) $value
            );

        if ($timestamp === false) {
            return "—";
        }

        return date(
            $includeTime
                ? "m-d-Y h:i A"
                : "m-d-Y",
            $timestamp
        );

    }

}


if (!function_exists("lendingArchiveCount")) {

    function lendingArchiveCount(
        mysqli $conn,
        string $table
    ): int {

        $allowedTables = [
            "product_loans",
            "monetary_loans"
        ];

        if (
            !in_array(
                $table,
                $allowedTables,
                true
            )
        ) {
            return 0;
        }

        $result =
            mysqli_query(
                $conn,
                "
                SELECT COUNT(*) AS total
                FROM {$table}
                WHERE is_archived = 1
                "
            );

        if (!$result) {
            return 0;
        }

        $row =
            mysqli_fetch_assoc(
                $result
            );

        return (int) (
            $row["total"]
            ?? 0
        );

    }

}


/* =========================================================
   SELECTED LENDING TYPE
========================================================= */

$lendingArchiveType =
    strtolower(
        trim(
            (string) (
                $_GET["lending_type"]
                ?? "product"
            )
        )
    );

if (
    !in_array(
        $lendingArchiveType,
        [
            "product",
            "monetary"
        ],
        true
    )
) {
    $lendingArchiveType =
        "product";
}

$archivedProductLoanCount =
    lendingArchiveCount(
        $conn,
        "product_loans"
    );

$archivedMonetaryLoanCount =
    lendingArchiveCount(
        $conn,
        "monetary_loans"
    );


/* =========================================================
   CURRENT USER PERMISSION
========================================================= */

$archiveUserID =
    isset($_SESSION["user_id"])
        ? (int) $_SESSION["user_id"]
        : 0;

$canRestoreLendingLoan =
    false;

if ($archiveUserID > 0) {

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
            $archiveUserID
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

        mysqli_stmt_close(
            $permissionStatement
        );

        $canRestoreLendingLoan =
            strtolower(
                trim(
                    (string) (
                        $permissionRow[
                            "lending_permission"
                        ]
                        ?? "limited"
                    )
                )
            ) === "full";

    }

}

?>

<div
class="lending-archive-section"
id="lendingArchiveSection"
data-csrf-token="<?= lendingArchiveEscape($lendingArchiveCsrfToken) ?>"
data-selected-type="<?= lendingArchiveEscape($lendingArchiveType) ?>">

    <!-- LENDING TYPE BUTTONS -->

    <div
    class="lending-archive-types"
    aria-label="Lending archive type">

        <a
        href="archive.php?module=lending&amp;lending_type=product"
        class="lending-archive-type-btn <?= $lendingArchiveType === "product" ? "active" : "" ?>">

            <i class="bi bi-box-seam"></i>

            <span>
                PRODUCT LOAN
            </span>

            <strong>
                <?= $archivedProductLoanCount ?>
            </strong>

        </a>

        <a
        href="archive.php?module=lending&amp;lending_type=monetary"
        class="lending-archive-type-btn <?= $lendingArchiveType === "monetary" ? "active" : "" ?>">

            <i class="bi bi-cash-stack"></i>

            <span>
                MONETARY LOAN
            </span>

            <strong>
                <?= $archivedMonetaryLoanCount ?>
            </strong>

        </a>

    </div>


    <?php if ($lendingArchiveType === "product") { ?>


        <?php

        /* =================================================
           PRODUCT LOAN ARCHIVE
        ================================================= */

        $archivedProductLoans =
            mysqli_query(
                $conn,
                "
                SELECT
                    loan.product_loan_id,
                    loan.application_date,
                    loan.interest_rate,
                    loan.term_days,
                    loan.principal_amount,
                    loan.scheduled_total_amount,
                    loan.outstanding_principal_amount,
                    loan.unpaid_interest_amount,
                    loan.status,
                    loan.fully_paid_at,
                    loan.archived_at,

                    member.member_code,
                    member.full_name,

                    archiver.name
                        AS archived_by_name,

                    item_summary.product_names

                FROM product_loans AS loan

                INNER JOIN members AS member
                    ON member.member_id =
                       loan.member_id

                LEFT JOIN users AS archiver
                    ON archiver.user_id =
                       loan.archived_by

                LEFT JOIN (

                    SELECT
                        product_loan_id,

                        GROUP_CONCAT(
                            product_name
                            ORDER BY product_loan_item_id
                            SEPARATOR '|||'
                        ) AS product_names

                    FROM product_loan_items

                    GROUP BY product_loan_id

                ) AS item_summary
                    ON item_summary.product_loan_id =
                       loan.product_loan_id

                WHERE loan.is_archived = 1

                ORDER BY
                    loan.archived_at DESC,
                    loan.product_loan_id DESC
                "
            );

        ?>


        <div class="archive-table lending-archive-table">

            <div class="archive-header lending-archive-header">

                <div>LOAN ID</div>

                <div>MEMBER</div>

                <div>PRODUCTS</div>

                <div>PRINCIPAL</div>

                <div>INTEREST</div>

                <div>TOTAL AMOUNT</div>

                <div>STATUS</div>

                <div>REMARKS</div>

                <div>ARCHIVED BY</div>

                <div>ACTIONS</div>

            </div>

            <div class="lending-archive-rows">

            <?php

            if (
                $archivedProductLoans &&
                mysqli_num_rows(
                    $archivedProductLoans
                ) > 0
            ) {

                while (
                    $loan =
                        mysqli_fetch_assoc(
                            $archivedProductLoans
                        )
                ) {

                    $loanDisplayID =
                        "PL-" .
                        str_pad(
                            (string) $loan[
                                "product_loan_id"
                            ],
                            6,
                            "0",
                            STR_PAD_LEFT
                        );

                    $productNames =
                        !empty(
                            $loan["product_names"]
                        )
                            ? explode(
                                "|||",
                                (string) $loan[
                                    "product_names"
                                ]
                            )
                            : [];

                    $status =
                        (string) $loan[
                            "status"
                        ];

                    $isPaid =
                        (
                            $status === "Delivered" &&
                            !empty(
                                $loan["fully_paid_at"]
                            ) &&
                            (float) $loan[
                                "outstanding_principal_amount"
                            ] <= 0.009 &&
                            (float) $loan[
                                "unpaid_interest_amount"
                            ] <= 0.009
                        );

                    $remarks =
                        $isPaid
                            ? "Paid"
                            : "—";

                    $statusClass =
                        $status === "Rejected"
                            ? "lending-archive-status-rejected"
                            : "lending-archive-status-delivered";

                    $archivedBy =
                        trim(
                            (string) (
                                $loan[
                                    "archived_by_name"
                                ]
                                ?? ""
                            )
                        );

                    if ($archivedBy === "") {
                        $archivedBy =
                            "Not recorded";
                    }

            ?>

                    <div class="archive-row lending-archive-row">

                        <div class="lending-archive-loan-id">

                            <strong>
                                <?= lendingArchiveEscape($loanDisplayID) ?>
                            </strong>

                            <small>
                                <?= lendingArchiveDate($loan["application_date"]) ?>
                            </small>

                        </div>


                        <div class="lending-archive-member">

                            <strong>
                                <?= lendingArchiveEscape($loan["full_name"]) ?>
                            </strong>

                            <small>
                                <?= lendingArchiveEscape($loan["member_code"] ?? "") ?>
                            </small>

                        </div>


                        <div class="lending-archive-products">

                            <?php if (count($productNames) === 0) { ?>

                                <span>—</span>

                            <?php } else { ?>

                                <?php foreach ($productNames as $productName) { ?>

                                    <span>
                                        <?= lendingArchiveEscape($productName) ?>
                                    </span>

                                <?php } ?>

                            <?php } ?>

                        </div>


                        <div class="lending-archive-money">

                            <?= lendingArchiveMoney($loan["principal_amount"]) ?>

                        </div>


                        <div class="lending-archive-interest">

                            <strong>

                                <?= number_format(
                                    (float) $loan["interest_rate"],
                                    0
                                ) ?>%

                            </strong>

                            <small>

                                <?= (int) $loan["term_days"] ?> Days

                            </small>

                        </div>


                        <div class="lending-archive-money">

                            <?= lendingArchiveMoney(
                                $loan["scheduled_total_amount"]
                            ) ?>

                        </div>


                        <div>

                            <span
                            class="lending-archive-status <?= lendingArchiveEscape($statusClass) ?>">

                                <?= lendingArchiveEscape($status) ?>

                            </span>

                        </div>


                        <div>

                            <span
                            class="lending-archive-remarks <?= $remarks === "Paid" ? "lending-archive-remarks-paid" : "lending-archive-remarks-none" ?>">

                                <?= lendingArchiveEscape($remarks) ?>

                            </span>

                        </div>


                        <div class="lending-archive-by">

                            <strong>
                                <?= lendingArchiveEscape($archivedBy) ?>
                            </strong>

                            <small>

                                <?= lendingArchiveDate(
                                    $loan["archived_at"],
                                    true
                                ) ?>

                            </small>

                        </div>


                        <div>

                            <?php if ($canRestoreLendingLoan) { ?>

                                <button
                                type="button"
                                class="retrieve-btn lending-retrieve-btn"
                                data-id="<?= (int) $loan["product_loan_id"] ?>"
                                data-loan-type="product"
                                data-loan-id="<?= lendingArchiveEscape($loanDisplayID) ?>"
                                data-member="<?= lendingArchiveEscape($loan["full_name"]) ?>">

                                    RETRIEVE

                                </button>

                            <?php } else { ?>

                                <button
                                type="button"
                                class="retrieve-btn lending-retrieve-btn-disabled"
                                title="Full Lending Access is required"
                                disabled>

                                    RETRIEVE

                                </button>

                            <?php } ?>

                        </div>

                    </div>

            <?php

                }

            } else {

            ?>

                <div class="archive-empty">

                    No archived Product Loans found.

                </div>

            <?php } ?>

        </div> <!-- lending-archive-rows -->

    </div> <!-- lending-archive-table -->


    <?php } else { ?>


        <?php

        /* =================================================
           MONETARY LOAN ARCHIVE
        ================================================= */

        $archivedMonetaryLoans =
            mysqli_query(
                $conn,
                "
                SELECT
                    loan.monetary_loan_id,
                    loan.application_date,
                    loan.loan_purpose,
                    loan.loan_term_years,
                    loan.interest_rate,
                    loan.term_days,
                    loan.principal_amount,
                    loan.scheduled_total_amount,
                    loan.outstanding_principal_amount,
                    loan.unpaid_interest_amount,
                    loan.status,
                    loan.fully_paid_at,
                    loan.archived_at,

                    member.member_code,
                    member.full_name,

                    archiver.name
                        AS archived_by_name

                FROM monetary_loans AS loan

                INNER JOIN members AS member
                    ON member.member_id =
                       loan.member_id

                LEFT JOIN users AS archiver
                    ON archiver.user_id =
                       loan.archived_by

                WHERE loan.is_archived = 1

                ORDER BY
                    loan.archived_at DESC,
                    loan.monetary_loan_id DESC
                "
            );

        ?>


        <div class="archive-table lending-archive-table">

            <div class="archive-header lending-archive-header">

                <div>LOAN ID</div>

                <div>MEMBER</div>

                <div>LOAN TYPE</div>

                <div>PRINCIPAL</div>

                <div>INTEREST</div>

                <div>TOTAL AMOUNT</div>

                <div>STATUS</div>

                <div>REMARKS</div>

                <div>ARCHIVED BY</div>

                <div>ACTIONS</div>

            </div>

            <div class="lending-archive-rows">

            <?php

            if (
                $archivedMonetaryLoans &&
                mysqli_num_rows(
                    $archivedMonetaryLoans
                ) > 0
            ) {

                while (
                    $loan =
                        mysqli_fetch_assoc(
                            $archivedMonetaryLoans
                        )
                ) {

                    $loanDisplayID =
                        "ML-" .
                        str_pad(
                            (string) $loan[
                                "monetary_loan_id"
                            ],
                            6,
                            "0",
                            STR_PAD_LEFT
                        );

                    $status =
                        (string) $loan[
                            "status"
                        ];

                    $isPaid =
                        (
                            $status === "Accepted" &&
                            !empty(
                                $loan["fully_paid_at"]
                            ) &&
                            (float) $loan[
                                "outstanding_principal_amount"
                            ] <= 0.009 &&
                            (float) $loan[
                                "unpaid_interest_amount"
                            ] <= 0.009
                        );

                    $remarks =
                        $isPaid
                            ? "Paid"
                            : "—";

                    $statusClass =
                        $status === "Rejected"
                            ? "lending-archive-status-rejected"
                            : "lending-archive-status-accepted";

                    $archivedBy =
                        trim(
                            (string) (
                                $loan[
                                    "archived_by_name"
                                ]
                                ?? ""
                            )
                        );

                    if ($archivedBy === "") {
                        $archivedBy =
                            "Not recorded";
                    }

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

                    $termDays =
                        (int) $loan["term_days"];

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

                    ?>

                    <div class="archive-row lending-archive-row">

                        <div class="lending-archive-loan-id">

                            <strong>
                                <?= lendingArchiveEscape($loanDisplayID) ?>
                            </strong>

                            <small>
                                <?= lendingArchiveDate($loan["application_date"]) ?>
                            </small>

                        </div>


                        <div class="lending-archive-member">

                            <strong>
                                <?= lendingArchiveEscape($loan["full_name"]) ?>
                            </strong>

                            <small>
                                <?= lendingArchiveEscape($loan["member_code"] ?? "") ?>
                            </small>

                        </div>


                        <div
                        class="lending-archive-purpose"
                        title="<?= lendingArchiveEscape($loan["loan_purpose"]) ?>">

                            <?= lendingArchiveEscape($loan["loan_purpose"]) ?>

                        </div>


                        <div class="lending-archive-money">

                            <?= lendingArchiveMoney($loan["principal_amount"]) ?>

                        </div>


                        <div class="lending-archive-interest">

                            <strong>

                                <?= number_format(
                                    (float) $loan["interest_rate"],
                                    0
                                ) ?>%

                            </strong>

                            <small>

                                <?php if (
                                    $isLongTermLoan &&
                                    $loanTermYears > 0 &&
                                    $paymentPeriods > 0
                                ) { ?>

                                    <?= lendingArchiveEscape(
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
                                    ) ?>

                                <?php } else { ?>

                                    <?= $termDays ?> Days

                                <?php } ?>

                            </small>

                        </div>


                        <div class="lending-archive-money">

                            <?= lendingArchiveMoney(
                                $loan["scheduled_total_amount"]
                            ) ?>

                        </div>


                        <div>

                            <span
                            class="lending-archive-status <?= lendingArchiveEscape($statusClass) ?>">

                                <?= lendingArchiveEscape($status) ?>

                            </span>

                        </div>


                        <div>

                            <span
                            class="lending-archive-remarks <?= $remarks === "Paid" ? "lending-archive-remarks-paid" : "lending-archive-remarks-none" ?>">

                                <?= lendingArchiveEscape($remarks) ?>

                            </span>

                        </div>


                        <div class="lending-archive-by">

                            <strong>
                                <?= lendingArchiveEscape($archivedBy) ?>
                            </strong>

                            <small>

                                <?= lendingArchiveDate(
                                    $loan["archived_at"],
                                    true
                                ) ?>

                            </small>

                        </div>


                        <div>

                            <?php if ($canRestoreLendingLoan) { ?>

                                <button
                                type="button"
                                class="retrieve-btn lending-retrieve-btn"
                                data-id="<?= (int) $loan["monetary_loan_id"] ?>"
                                data-loan-type="monetary"
                                data-loan-id="<?= lendingArchiveEscape($loanDisplayID) ?>"
                                data-member="<?= lendingArchiveEscape($loan["full_name"]) ?>">

                                    RETRIEVE

                                </button>

                            <?php } else { ?>

                                <button
                                type="button"
                                class="retrieve-btn lending-retrieve-btn-disabled"
                                title="Full Lending Access is required"
                                disabled>

                                    RETRIEVE

                                </button>

                            <?php } ?>

                        </div>

                    </div>

            <?php

                }

            } else {

            ?>

                <div class="archive-empty">

                    No archived Monetary Loans found.

                </div>

            <?php } ?>

        </div> <!-- lending-archive-rows -->

    </div> <!-- lending-archive-table -->


    <?php } ?>

</div> <!-- lending-archive-section -->


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const archiveSection =
            document.getElementById(
                "lendingArchiveSection"
            );

        if (!archiveSection) {
            return;
        }

        const csrfToken =
            archiveSection.dataset.csrfToken ||
            "";

        archiveSection
            .querySelectorAll(
                ".lending-retrieve-btn"
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        async function () {

                            if (
                                button.dataset.retrieving ===
                                "true"
                            ) {
                                return;
                            }

                            const loanID =
                                Number(
                                    button.dataset.id
                                );

                            const loanType =
                                button.dataset.loanType ===
                                "monetary"
                                    ? "monetary"
                                    : "product";

                            const loanLabel =
                                loanType === "monetary"
                                    ? "Monetary Loan"
                                    : "Product Loan";

                            const loanDisplayID =
                                button.dataset.loanId ||
                                loanLabel;

                            const memberName =
                                button.dataset.member ||
                                "the selected member";

                            const endpoint =
                                loanType === "monetary"
                                    ? "retrieve_monetary_loan.php"
                                    : "retrieve_product_loan.php";

                            if (
                                !Number.isInteger(loanID) ||
                                loanID <= 0
                            ) {

                                await showLendingArchiveError(
                                    "The selected " +
                                    loanLabel +
                                    " is invalid."
                                );

                                return;

                            }

                            let confirmed;

                            if (
                                typeof Swal !==
                                "undefined"
                            ) {

                                const confirmation =
                                    await Swal.fire({
                                        icon:
                                            "question",

                                        title:
                                            "Retrieve " +
                                            loanLabel +
                                            "?",

                                        text:
                                            loanDisplayID +
                                            " for " +
                                            memberName +
                                            " will be returned to the active " +
                                            loanLabel +
                                            " ledger. Its payments, reversals, attachments, and audit records will remain unchanged.",

                                        showCancelButton:
                                            true,

                                        confirmButtonText:
                                            "RETRIEVE",

                                        cancelButtonText:
                                            "CANCEL",

                                        confirmButtonColor:
                                            "#8ca15b",

                                        cancelButtonColor: 
                                            "#d64545",

                                        reverseButtons:
                                            true,

                                        focusCancel:
                                            true,

                                        allowOutsideClick:
                                            false
                                    });

                                confirmed =
                                    confirmation.isConfirmed;

                            } else {

                                confirmed =
                                    window.confirm(
                                        "Retrieve " +
                                        loanDisplayID +
                                        " for " +
                                        memberName +
                                        "?"
                                    );

                            }

                            if (!confirmed) {
                                return;
                            }

                            button.dataset.retrieving =
                                "true";

                            button.disabled =
                                true;

                            try {

                                const formData =
                                    new FormData();

                                formData.append(
                                    "loan_id",
                                    String(loanID)
                                );

                                formData.append(
                                    "csrf_token",
                                    csrfToken
                                );

                                const response =
                                    await fetch(
                                        endpoint,
                                        {
                                            method:
                                                "POST",

                                            body:
                                                formData,

                                            headers: {
                                                "X-Requested-With":
                                                    "XMLHttpRequest"
                                            },

                                            cache:
                                                "no-store"
                                        }
                                    );

                                const responseText =
                                    await response.text();

                                let result;

                                try {

                                    result =
                                        JSON.parse(
                                            responseText
                                        );

                                } catch (jsonError) {

                                    console.error(
                                        "Invalid retrieve response:",
                                        responseText
                                    );

                                    throw new Error(
                                        "The server returned an invalid response."
                                    );

                                }

                                if (
                                    !response.ok ||
                                    !result.success
                                ) {

                                    throw new Error(
                                        result.message ||
                                        (
                                            "Unable to retrieve the " +
                                            loanLabel +
                                            "."
                                        )
                                    );

                                }

                                if (
                                    typeof Swal !==
                                    "undefined"
                                ) {

                                    await Swal.fire({
                                        icon:
                                            "success",

                                        title:
                                            loanLabel +
                                            " retrieved",

                                        text:
                                            result.message ||
                                            (
                                                loanDisplayID +
                                                " was returned to the active ledger."
                                            ),

                                        confirmButtonText:
                                            "OK",

                                        confirmButtonColor:
                                            "#8ca15b",

                                        allowOutsideClick:
                                            false
                                    });

                                } else {

                                    window.alert(
                                        result.message ||
                                        (
                                            loanLabel +
                                            " retrieved successfully."
                                        )
                                    );

                                }

                                window.location.href =
                                    "archive.php?module=lending&lending_type=" +
                                    encodeURIComponent(
                                        loanType
                                    );

                            } catch (error) {

                                console.error(
                                    "Retrieve Lending Loan error:",
                                    error
                                );

                                await showLendingArchiveError(
                                    error.message ||
                                    (
                                        "Something went wrong while retrieving the " +
                                        loanLabel +
                                        "."
                                    )
                                );

                                button.dataset.retrieving =
                                    "false";

                                button.disabled =
                                    false;

                            }

                        }
                    );

                }
            );


        async function showLendingArchiveError(
            message
        ) {

            if (
                typeof Swal !==
                "undefined"
            ) {

                await Swal.fire({
                    icon:
                        "error",

                    title:
                        "Unable to retrieve",

                    text:
                        message,

                    confirmButtonText:
                        "OK",

                    confirmButtonColor:
                        "#8ca15b"
                });

            } else {

                window.alert(
                    message
                );

            }

        }

    }
);

</script>