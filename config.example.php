<?php

$servername = "localhost";
$username = "your_username";
$password = "your_password";
$database = "your_database";


$conn = mysqli_connect(
    $servername,
    $username,
    $password,
    $database
);


if(!$conn){

    die("Database connection failed");

}

?>