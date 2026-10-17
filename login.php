<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    }
    
    // If no validation errors, check credentials
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT CustomerID, Name, Email, Password, Phone FROM Customer WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['Password'])) {
                // Login successful
                $_SESSION['customer_id'] = $user['CustomerID'];
                $_SESSION['customer_name'] = $user['Name'];
                $_SESSION['customer_email'] = $user['Email'];
                $_SESSION['customer_phone'] = $user['Phone'];
                $_SESSION['logged_in'] = true;
                
                // Redirect to customer info page
                header("Location: homepage.php");
                exit();
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
        $stmt->close();
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['login_errors'] = $errors;
        // CORRECTED: Redirect back to the login page (new.php)
        header("Location: new.php"); 
        exit();
    }
}

$conn->close();
?>