<?php
$servername = "localhost";
$username = "root";   // your MySQL username
$password = "Raga@143";       // your MySQL password (if any)
$dbname = "finance_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>


