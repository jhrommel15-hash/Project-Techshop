
<?php

$host = "sql304.infinityfree.com";
$dbname = "if0_42696753_techstore";
$username = "if0_42696753";
$password = "b9nnkpjeZ42JsR";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    http_response_code(500);
    exit("Database connection failed. Please try again later.");
}

$conn->set_charset("utf8mb4");

?>
