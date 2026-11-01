<?php

/* =====================================================
   PHILIPPINE TIMEZONE FOR PHP
===================================================== */

date_default_timezone_set(
    'Asia/Manila'
);


/* =====================================================
   DATABASE CONFIGURATION
===================================================== */

$host =
'localhost';


$username =
'root';


$password =
'';


$database =
'spis_db';


/* =====================================================
   CONNECT TO DATABASE
===================================================== */

$conn =
mysqli_connect(
    $host,
    $username,
    $password,
    $database
);


if(!$conn){

    error_log(
        'SPIS database connection failed: '
        .
        mysqli_connect_error()
    );

    die(
        'Unable to connect to the database.'
    );

}


/* =====================================================
   DATABASE CHARACTER SET

   Supports Philippine peso sign, names, and other
   special characters.
===================================================== */

if(
    !mysqli_set_charset(
        $conn,
        'utf8mb4'
    )
){

    die(
        'Unable to configure database character encoding.'
    );

}


/* =====================================================
   PHILIPPINE TIMEZONE FOR MYSQL CONNECTION

   Philippines uses UTC+08:00 throughout the year.
===================================================== */

if(
    !mysqli_query(
        $conn,
        "SET time_zone = '+08:00'"
    )
){

    die(
        'Unable to configure the database timezone.'
    );

}

?>