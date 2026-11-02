<?php
$host = "localhost";
$user = "root"; // your db username
$pass = "";     // your db password
$dbname = "feedback_system";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>