<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Flight";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$route = isset($_GET['route']) ? $_GET['route'] : '';
$models = [];

if (!empty($route)) {
    // Query to get distinct models for the selected route
    $query = "
        SELECT DISTINCT p.Model
        FROM Plane p
        JOIN Flight f ON p.PlaneID = f.PlaneID
        WHERE f.Path = ?
        ORDER BY p.Model ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $route);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $models[] = $row['Model'];
    }
}

// Set the content type to JSON and output the data
header('Content-Type: application/json');
echo json_encode($models);

$conn->close();
?>