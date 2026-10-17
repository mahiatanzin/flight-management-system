<?php
require_once 'config.php';

header('Content-Type: application/json');

$flight_id = isset($_GET['flight_id']) ? intval($_GET['flight_id']) : 0;

$data = [
    'Fflag' => false, 'Fprice' => 0, 'Bflag' => false, 'Bprice' => 0, 'Eflag' => false, 'Eprice' => 0
];

if ($flight_id > 0) {
    $query = "
        SELECT p.Fflag, p.Fprice, p.Bflag, p.Bprice, p.Eflag, p.Eprice
        FROM Flight f
        JOIN Plane p ON f.PlaneID = p.PlaneID
        WHERE f.FlightID = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $flight_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
    }
    
    $stmt->close();
}

echo json_encode($data);

$conn->close();
?>

