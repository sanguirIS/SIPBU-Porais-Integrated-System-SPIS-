<?php

/* ======================================================
   SPIS - TRACTOR STATUS SYNC

   SHARED / CROSS-RENTAL PHYSICAL STATUS RULES

   ALL REGISTERED TRACTORS:
   - Ongoing today in Hauling / Rotor / Reaper = In Use
   - Pending today in Reaper = In Use
   - Pending today in Hauling / Rotor = Reserved
   - No Pending/Ongoing rental today = Available

   IMPORTANT:
   - Tractor Type is classification only.
   - Tractor Type does NOT restrict Rental assignment.
   - Primary + Secondary tractors are included.
   - Maintenance is NEVER overwritten automatically.
   - Only TODAY can affect the physical Tractor Registry.
   - In Use takes priority over Reserved if legacy conflicts exist.
====================================================== */


/* ======================================================
   INCLUDE-ONLY PROTECTION

   This helper changes Tractor Registry status and must
   never be executed directly from the browser.
====================================================== */

$syncScriptFile =
realpath(
    (string)(
        $_SERVER['SCRIPT_FILENAME']
        ??
        ''
    )
);


$syncCurrentFile =
realpath(
    __FILE__
);


if(
    $syncScriptFile !== false
    &&
    $syncCurrentFile !== false
    &&
    $syncScriptFile === $syncCurrentFile
){

    http_response_code(404);

    exit(
        'Not found.'
    );
}


/* ======================================================
   DATABASE CONNECTION
====================================================== */

if (
    !isset($conn)
) {

    require_once dirname(
        __DIR__,
        2
    ) . '/db.php';
}


/* ======================================================
   MANILA TIME / DATABASE SETTINGS
====================================================== */

date_default_timezone_set(
    'Asia/Manila'
);


mysqli_set_charset(
    $conn,
    'utf8mb4'
);


mysqli_query(
    $conn,
    "SET time_zone = '+08:00'"
);


/* ======================================================
   SHARED TRACTOR STATUS SYNC

   Priority:
   1. In Use
   2. Reserved
   3. Available

   Maintenance tractors are excluded from this update.
====================================================== */

mysqli_query(
    $conn,
    "
    UPDATE tractors t

    SET t.status =
    CASE

        /* =================================================
           IN USE

           Any Ongoing Rental today means the tractor is
           physically in use.

           Reaper keeps its agreed behavior where Pending
           today also means In Use.
        ================================================= */

        WHEN
        (
            EXISTS
            (
                SELECT 1
                FROM hauling h
                WHERE DATE(h.rental_date) = CURDATE()
                AND h.status = 'Ongoing'
                AND
                (
                    h.tractor_id = t.tractor_id
                    OR
                    h.secondary_tractor_id = t.tractor_id
                )
            )

            OR

            EXISTS
            (
                SELECT 1
                FROM rotor r
                WHERE DATE(r.rental_date) = CURDATE()
                AND r.status = 'Ongoing'
                AND
                (
                    r.tractor_id = t.tractor_id
                    OR
                    r.secondary_tractor_id = t.tractor_id
                )
            )

            OR

            EXISTS
            (
                SELECT 1
                FROM reaper rp
                WHERE DATE(rp.rental_date) = CURDATE()
                AND rp.status IN
                (
                    'Pending',
                    'Ongoing'
                )
                AND
                (
                    rp.tractor_id = t.tractor_id
                    OR
                    rp.secondary_tractor_id = t.tractor_id
                )
            )
        )
        THEN 'In Use'


        /* =================================================
           RESERVED

           Pending Hauling / Rotor Rental today means the
           tractor is reserved for today's service.
        ================================================= */

        WHEN
        (
            EXISTS
            (
                SELECT 1
                FROM hauling h
                WHERE DATE(h.rental_date) = CURDATE()
                AND h.status = 'Pending'
                AND
                (
                    h.tractor_id = t.tractor_id
                    OR
                    h.secondary_tractor_id = t.tractor_id
                )
            )

            OR

            EXISTS
            (
                SELECT 1
                FROM rotor r
                WHERE DATE(r.rental_date) = CURDATE()
                AND r.status = 'Pending'
                AND
                (
                    r.tractor_id = t.tractor_id
                    OR
                    r.secondary_tractor_id = t.tractor_id
                )
            )
        )
        THEN 'Reserved'


        /* =================================================
           AVAILABLE
        ================================================= */

        ELSE 'Available'

    END

    WHERE t.archived = 0
    AND t.status != 'Maintenance'
    "
);

?>