<?php
// Database configuration
$servername = "localhost";
$username = "root";  // Change this to your MySQL username
$password = "";  // Change this to your MySQL password
$dbname = "Flight";              // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//Set charset to utf8
$conn->set_charset("utf8");
?>