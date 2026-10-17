<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: new.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customerID = $_SESSION['customer_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['update_error'] = "Invalid email format.";
        header("Location: edit_profile.php");
        exit();
    }

    // Prepare and execute the update statement
    $stmt = $conn->prepare("UPDATE Customer SET Name = ?, Email = ?, Phone = ? WHERE CustomerID = ?");
    $stmt->bind_param("sssi", $name, $email, $phone, $customerID);

    if ($stmt->execute()) {
        // Update session variables with new data
        $_SESSION['customer_name'] = $name;
        $_SESSION['customer_email'] = $email;
        $_SESSION['customer_phone'] = $phone;
        $_SESSION['update_success'] = "Profile updated successfully!";
    } else {
        $_SESSION['update_error'] = "Error updating profile: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    // Redirect back to the customer profile page
    header("Location: Customerinfo.php");
    exit();
} else {
    // If not a POST request, redirect
    header("Location: Customerinfo.php");
    exit();
}
?>