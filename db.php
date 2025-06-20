<?php
// Update the username, password, and database name as needed
$host = "sql204.infinityfree.com";
$username = "if0_39212989";
$password = "gs4cj2uRQZwyN"; // Replace with your actual vPanel password
$database = "if0_39212989_admins"; // Use your actual database name for admins

// Create connection
$con = mysqli_connect($host, $username, $password, $database);

// Check connection
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit();
}
?>
