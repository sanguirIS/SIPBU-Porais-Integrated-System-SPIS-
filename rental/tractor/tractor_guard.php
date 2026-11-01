<?php

/* ======================================================
   TRACTOR MODULE ACCESS GUARD
====================================================== */

if(!isset($conn)){

    include dirname(__DIR__, 2) . '/db.php';

}


$allowedTractorTypes = [
    'Hauling',
    'Rotor',
    'Reaper'
];


if(
    !isset($requiredTractorType)
    ||
    !in_array(
        $requiredTractorType,
        $allowedTractorTypes,
        true
    )
){

    die(
        'Invalid tractor access configuration.'
    );

}


/* ======================================================
   CHECK REGISTERED TRACTOR

   Tractor Type is classification only.
   Any active registered tractor may be used across
   Hauling, Rotor, and Reaper.
====================================================== */

$stmt = mysqli_prepare(
    $conn,
    "
    SELECT tractor_id
    FROM tractors
    WHERE archived = 0
    LIMIT 1
    "
);


if(!$stmt){

    die(
        'Unable to check registered tractors.'
    );

}


mysqli_stmt_execute(
    $stmt
);


$result = mysqli_stmt_get_result(
    $stmt
);


$hasTractor =
$result &&
mysqli_num_rows($result) > 0;


mysqli_stmt_close(
    $stmt
);


/* ======================================================
   BLOCK MODULE
====================================================== */

if(!$hasTractor){

    header(
        'Location: rental.php?error=no_tractor&type='
        .
        urlencode($requiredTractorType)
    );

    exit();

}