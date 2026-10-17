<?php
session_start();
// Include the database connection file
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Redirect to the login page if not logged in
    header("Location: new.php");
    exit();
}

// Get customer data from the session
$customerID = $_SESSION['customer_id'];
$customerName = $_SESSION['customer_name'];
$customerEmail = $_SESSION['customer_email'];
$customerPhone = $_SESSION['customer_phone'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding: 2rem;
            line-height: 1.6;
        }

        .profile-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }

        .profile-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .profile-subtitle {
            opacity: 0.9;
            font-size: 1rem;
        }

        .profile-content {
            padding: 2rem;
        }

        .info-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .info-icon {
            font-size: 1.2rem;
            margin-right: 1rem;
            width: 25px;
            text-align: center;
        }

        .info-content {
            flex: 1;
        }

        .info-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 0.2rem;
        }

        .info-value {
            font-size: 1rem;
            color: #333;
            font-weight: 600;
        }

        /* --- START: Added Styles --- */
        .button-container {
            display: flex;
            gap: 1rem; /* Creates space between buttons */
            margin-top: 1rem;
        }

        .home-button, .edit-button {
            flex: 1; /* Makes both buttons take up equal space */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .home-button {
            background: linear-gradient(45deg, #566573, #2C3E50);
        }

        .home-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(86, 101, 115, 0.3);
        }
        /* --- END: Added Styles --- */

        .edit-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            /* margin-top: 1rem; <-- This is now handled by .button-container */
        }

        .edit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .profile-content {
                padding: 1.5rem;
            }
            
            .info-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-icon {
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar" id="avatar">JS</div>
            <h1 class="profile-title" id="customerName"><?php echo htmlspecialchars($customerName); ?></h1>
            <p class="profile-subtitle">Customer Profile</p>
        </div>

        <div class="profile-content">
            <div class="info-section">
                <h2 class="section-title">Customer Information</h2>
                
                <div class="info-item">
                    <div class="info-icon">👤</div>
                    <div class="info-content">
                        <div class="info-label">Full Name</div>
                        <div class="info-value" id="displayName"><?php echo htmlspecialchars($customerName); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">🆔</div>
                    <div class="info-content">
                        <div class="info-label">Customer ID</div>
                        <div class="info-value" id="displayId"><?php echo htmlspecialchars($customerID); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">📧</div>
                    <div class="info-content">
                        <div class="info-label">Email Address</div>
                        <div class="info-value" id="displayEmail"><?php echo htmlspecialchars($customerEmail); ?></div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">📞</div>
                    <div class="info-content">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value" id="displayPhone"><?php echo htmlspecialchars($customerPhone); ?></div>
                    </div>
                </div>
            </div>

            <div class="button-container">
                <button class="home-button" onclick="window.location.href='homepage.php'">Home</button>
                <button class="edit-button" onclick="window.location.href='edit_profile.php'">Edit Profile</button>
            </div>
            </div>
    </div>

    <script>
        // Function to update avatar with initials
        function updateAvatar() {
            const customerName = "<?php echo htmlspecialchars($customerName); ?>";
            const initials = customerName.split(' ').map(n => n[0]).join('').toUpperCase();
            document.getElementById('avatar').textContent = initials;
        }

        // Function to simulate edit functionality
        function editProfile() {
            alert('Edit functionality would redirect to edit form or open edit modal');
        }

        // Initialize the profile display
        document.addEventListener('DOMContentLoaded', updateAvatar);
    </script>
</body>
</html>