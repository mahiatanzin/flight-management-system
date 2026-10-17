<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    // Validate input
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT CustomerID FROM Customer WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }
    
    // If no errors, insert new customer
    if (empty($errors)) {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO Customer (Name, Email, Password, Phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $phone);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Account created successfully! You can now log in.";
            header("Location: new.php");
            exit();
        } else {
            $errors[] = "Error creating account. Please try again.";
        }
        $stmt->close();
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['signup_errors'] = $errors;
        $_SESSION['form_data'] = $_POST; // Keep form data to repopulate
        header("Location: new.php");
        exit();
    }
}

$conn->close();
?>