<?php
$host = "localhost";
$user = "root";
$pass = ""; // your MySQL password
$dbname = "online_exam_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
