<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($email) || empty($phone)) {
        $_SESSION['verify_error'] = "Email and phone number are required.";
        header("Location: forgot_password.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT CustomerID FROM Customer WHERE Email = ? AND Phone = ?");
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // User is verified
        $user = $result->fetch_assoc();
        $_SESSION['customer_id_for_reset'] = $user['CustomerID'];
        header("Location: reset_password.php");
        exit();
    } else {
        // Verification failed
        $_SESSION['verify_error'] = "No account found with that email and phone number.";
        header("Location: forgot_password.php");
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    // Redirect if accessed directly
    header("Location: new.php");
    exit();
}
?>