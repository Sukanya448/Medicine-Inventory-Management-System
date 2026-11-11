
<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "MedicineDB";

// Connect to MySQL
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: "
        . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>
