<?php
$servername = 'localhost';
$db_username = 'root'; // Renamed to avoid conflict with session $display_username
$password = '';
$dbname = 'tampering';

// Create connection
$con = mysqli_connect($host, $username, $password, $database);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
?>
