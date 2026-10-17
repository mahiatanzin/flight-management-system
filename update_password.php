<?php
session_start();
require_once 'config.php';

// Security check: If the user hasn't been verified, redirect them away.
if (!isset($_SESSION['customer_id_for_reset'])) {
    header("Location: new.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $customer_id = $_SESSION['customer_id_for_reset'];

    // Validation
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['reset_error'] = "Please fill in both password fields.";
        header("Location: reset_password.php");
        exit();
    }

    if ($new_password !== $confirm_password) {
        $_SESSION['reset_error'] = "Passwords do not match.";
        header("Location: reset_password.php");
        exit();
    }

    if (strlen($new_password) < 6) {
        $_SESSION['reset_error'] = "Password must be at least 6 characters long.";
        header("Location: reset_password.php");
        exit();
    }

    // Hash the new password for security
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the database
    $stmt = $conn->prepare("UPDATE Customer SET Password = ? WHERE CustomerID = ?");
    $stmt->bind_param("si", $hashed_password, $customer_id);
    
    if ($stmt->execute()) {
        // Success
        unset($_SESSION['customer_id_for_reset']); // Clean up session
        $_SESSION['reset_success'] = "Password updated successfully. Please log in.";
        header("Location: new.php");
        exit();
    } else {
        // Database error
        $_SESSION['reset_error'] = "An error occurred. Please try again.";
        header("Location: reset_password.php");
        exit();
    }

    $stmt->close();
    $conn->close();

} else {
    header("Location: new.php");
    exit();
}
?>