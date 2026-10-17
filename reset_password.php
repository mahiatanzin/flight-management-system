<?php
session_start();

// Security check: If the user hasn't been verified, redirect them away.
if (!isset($_SESSION['customer_id_for_reset'])) {
    header("Location: new.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Flight Booking</title>
    <style>
        /* Paste the same style block from forgot_password.php here */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px; /* Smaller container for this form */
            min-height: auto;
        }

        .form-section {
            padding: 60px 50px;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            text-align: center;
        }

        p.subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 50px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        input:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 45px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 10px;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="form-section">
            <h2>Reset Your Password</h2>
            <p class="subtitle">Enter and confirm your new password.</p>

            <?php
                if (isset($_SESSION['reset_error'])) {
                    echo '<p style="color: red; text-align: center; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['reset_error']) . '</p>';
                    unset($_SESSION['reset_error']);
                }
            ?>

            <form action="update_password.php" method="POST">
                <div class="form-group">
                    <input type="password" name="new_password" placeholder="New Password" minlength="6" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                </div>
                <button type="submit" class="btn">Update Password</button>
            </form>
        </div>
    </div>
</body>
</html>