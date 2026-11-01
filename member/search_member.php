<?php

if(
    session_status()
    ===
    PHP_SESSION_NONE
){
    session_start();
}

date_default_timezone_set(
    'Asia/Manila'
);

require_once '../db.php';

mysqli_set_charset(
    $conn,
    'utf8mb4'
);


/* =========================================================
   ACCESS
========================================================= */

if(
    !isset(
        $_SESSION['user_id']
    )
    ||
    !isset(
        $_SESSION['members_access']
    )
    ||
    (int)$_SESSION['members_access'] !== 1
){
    http_response_code(
        403
    );

    exit(
        'Access denied.'
    );
}


/* =========================================================
   HELPERS
========================================================= */

function searchMemberEscape(
    $value
): string {

    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );

}


function searchMemberDisplayDate(
    $value
): string {

    $value =
        trim(
            (string)$value
        );


    if(
        $value === ''
    ){
        return '—';
    }


    $timestamp =
        strtotime(
            $value
        );


    return $timestamp === false
        ?
        '—'
        :
        date(
            'm-d-Y',
            $timestamp
        );

}


function searchMemberContact(
    $value
): string {

    $digits =
        preg_replace(
            '/\D/',
            '',
            (string)$value
        );


    if(
        $digits === ''
    ){
        return '—';
    }


    if(
        strlen(
            $digits
        ) === 11
    ){

        return
            substr(
                $digits,
                0,
                4
            )
            .
            '-'
            .
            substr(
                $digits,
                4,
                3
            )
            .
            '-'
            .
            substr(
                $digits,
                7
            );

    }


    return $digits;

}


function searchMemberTin(
    $value
): string {

    $tin =
        preg_replace(
            '/\D/',
            '',
            (string)$value
        );


    if(
        $tin === ''
    ){
        return '—';
    }


    if(
        strlen(
            $tin
        ) === 9
    ){

        return
            substr(
                $tin,
                0,
                3
            )
            .
            '-'
            .
            substr(
                $tin,
                3,
                3
            )
            .
            '-'
            .
            substr(
                $tin,
                6,
                3
            );

    }


    if(
        strlen(
            $tin
        ) === 12
    ){

        return
            substr(
                $tin,
                0,
                3
            )
            .
            '-'
            .
            substr(
                $tin,
                3,
                3
            )
            .
            '-'
            .
            substr(
                $tin,
                6,
                3
            )
            .
            '-'
            .
            substr(
                $tin,
                9,
                3
            );

    }


    return $tin;

}

function searchMemberRsbsa(
    $value
): string {

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


function searchMemberFarmField(
    $value
): string {

    if(
        $value === null
        ||
        trim(
            (string)$value
        ) === ''
    ){
        return '—';
    }


    $formatted =
        number_format(
            (float)$value,
            4,
            '.',
            ''
        );


    $formatted =
        rtrim(
            rtrim(
                $formatted,
                '0'
            ),
            '.'
        );


    return
        $formatted
        .
        ' ha';

}


/* =========================================================
   SEARCH
========================================================= */

$search =
    trim(
        $_GET['search']
        ??
        ''
    );


$like =
    '%'
    .
    $search
    .
    '%';


$stmt =
    mysqli_prepare(
        $conn,
        "
        SELECT *
        FROM members

        WHERE archived = 0

        AND
        (
            ? = ''

            OR CONCAT_WS(
                ' ',
                member_code,
                full_name,
                date_registered,
                birth_date,
                gender,
                address,
                tin_no,
                rsbsa_no,
                contact_no,
                social_media_name,
                farm_field_hectares,
                cbu_balance,
                status,
                classification
            ) LIKE ?
        )

        ORDER BY member_id ASC

        LIMIT 20
        "
    );


if(
    !$stmt
){

    http_response_code(
        500
    );

    exit(
        'Unable to search members.'
    );

}


mysqli_stmt_bind_param(
    $stmt,
    'ss',
    $search,
    $like
);


if(
    !mysqli_stmt_execute(
        $stmt
    )
){

    mysqli_stmt_close(
        $stmt
    );

    http_response_code(
        500
    );

    exit(
        'Unable to search members.'
    );

}


$result =
    mysqli_stmt_get_result(
        $stmt
    );


/* =========================================================
   RESULTS
========================================================= */

if(
    $result
):


while(
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
        searchMemberRsbsa(
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
        searchMemberDisplayDate(
            $member['date_registered']
            ??
            ''
        );


    $birthDateDisplay =
        searchMemberDisplayDate(
            $member['birth_date']
            ??
            ''
        );


    $contactDisplay =
        searchMemberContact(
            $member['contact_no']
            ??
            ''
        );


    $tinDisplay =
        searchMemberTin(
            $member['tin_no']
            ??
            ''
        );


    $farmFieldDisplay =
        searchMemberFarmField(
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

data-status="<?= searchMemberEscape(
    strtolower(
        $memberStatus
    )
); ?>"

data-classification="<?= searchMemberEscape(
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
                ? searchMemberEscape(
                    'serve_member_file.php?type=profile&member_id='
                    .
                    $memberID
                )
                : '';
            ?>"

            data-name="<?= searchMemberEscape(
                $memberName
            ); ?>"

            data-gender="<?= searchMemberEscape(
                $gender
            ); ?>"

            data-membership-date="<?= searchMemberEscape(
                $membershipDateForEdit
            ); ?>"

            data-birth="<?= searchMemberEscape(
                $member['birth_date']
                ??
                ''
            ); ?>"

            data-address="<?= searchMemberEscape(
                $address
            ); ?>"

            data-tin="<?= searchMemberEscape(
                $member['tin_no']
                ??
                ''
            ); ?>"

            data-rsbsa="<?= searchMemberEscape(
                $rsbsa
            ); ?>"

            data-contact="<?= searchMemberEscape(
                $member['contact_no']
                ??
                ''
            ); ?>"

            data-social="<?= searchMemberEscape(
                $social
            ); ?>"

            data-farm-field="<?= searchMemberEscape(
                $member['farm_field_hectares']
                ??
                ''
            ); ?>"

            data-cbu="<?= searchMemberEscape(
                number_format(
                    $currentCbu,
                    2,
                    '.',
                    ''
                )
            ); ?>"

            data-status="<?= searchMemberEscape(
                $memberStatus
            ); ?>"

            data-classification="<?= searchMemberEscape(
                $memberClassification
            ); ?>"

            title="Edit Member">


                <i class="bi bi-pencil-fill"></i>


            </button>


<?php

if(
    $profilePath !== ''
):

?>


            <img
            src="<?= searchMemberEscape(
                'serve_member_file.php?type=profile&member_id='
                .
                $memberID
                .
                '&v='
                .
                time()
            ); ?>"
            class="member-profile"
            alt="<?= searchMemberEscape(
                $memberName
            ); ?>">


<?php

else:

?>


            <div class="member-avatar">

                <?= searchMemberEscape(
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

                <?= searchMemberEscape(
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

                    <?= searchMemberEscape(
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

                    <?= searchMemberEscape(
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

    <div class="member-column">


        <strong
        class="member-primary-value member-nowrap-value">

            <?= searchMemberEscape(
                $membershipDateDisplay
            ); ?>

        </strong>


        <span
        class="member-secondary-value member-nowrap-value">

            <span class="member-detail-label">
                Birth:
            </span>

            <?= searchMemberEscape(
                $birthDateDisplay
            ); ?>

        </span>


    </div>


    <!-- =================================================
         CONTACT
    ================================================== -->

    <div class="member-column">


        <strong
        class="member-primary-value member-nowrap-value">

            <?= searchMemberEscape(
                $contactDisplay
            ); ?>

        </strong>


        <span
        class="member-secondary-value member-wrap-value">

            <span class="member-detail-label">
                FB:
            </span>

            <?= searchMemberEscape(
                $social !== ''
                ?
                $social
                :
                '—'
            ); ?>

        </span>


    </div>


    <!-- =================================================
         LOCATION
    ================================================== -->

    <div
    class="member-column member-location-column">


        <span class="member-location-value">

            <?= searchMemberEscape(
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

                <?= searchMemberEscape(
                    $rsbsaDisplay
                ); ?>

            </span>

        </span>


        <span class="member-id-row">

            <span class="member-detail-label">
                TIN:
            </span>

            <span class="member-id-value">

                <?= searchMemberEscape(
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

            ₱<?= searchMemberEscape(
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

            <?= searchMemberEscape(
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


            <?= searchMemberEscape(
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


            <?= searchMemberEscape(
                $memberClassification
            ); ?>


        </span>


    </div>


</div>


<?php

endwhile;

endif;


/* =========================================================
   EMPTY SEARCH
========================================================= */

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

        <i class="bi bi-search"></i>

        <h4>
            No Members Found
        </h4>

        <p>
            Try searching using a different keyword.
        </p>

    </div>

</div>


<?php

endif;


mysqli_stmt_close(
    $stmt
);

?>