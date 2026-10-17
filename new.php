<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Booking - Login & Signup</title>
    <style>
        /* Your original CSS, no changes needed */
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
            max-width: 900px;
            min-height: 600px;
            display: flex;
        }

        .form-section {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-toggle {
            display: flex;
            margin-bottom: 30px;
            background: #f1f3f4;
            border-radius: 50px;
            padding: 5px;
        }

        .toggle-btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 50px;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #666;
        }

        .toggle-btn.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .form-container {
            display: none;
        }

        .form-container.active {
            display: block;
        }

        h2 {
            font-size: 2rem;
            margin-bottom: 30px;
            color: #333;
            font-weight: 600;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
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
            margin-top: 20px;
            width: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .info-section {
            flex: 1;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 50px;
        }

        .info-section h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .info-section p {
            font-size: 1.2rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
                max-width: 400px;
            }

            .info-section {
                padding: 30px;
                min-height: 200px;
            }

            .form-section {
                padding: 40px 30px;
            }

            .info-section h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="form-section">
            <div class="form-toggle">
                <button class="toggle-btn active" onclick="showForm('login')">Login</button>
                <button class="toggle-btn" onclick="showForm('signup')">Sign Up</button>
            </div>

            <div id="loginForm" class="form-container active">
                <h2>Welcome Back</h2>

                <?php
                    // For showing login errors
                    if (isset($_SESSION['login_errors'])) {
                        echo '<p style="color: red; text-align: center; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['login_errors'][0]) . '</p>';
                        unset($_SESSION['login_errors']);
                    }
                    // For showing password reset success message
                    if (isset($_SESSION['reset_success'])) {
                        echo '<p style="color: green; text-align: center; margin-bottom: 15px;">' . htmlspecialchars($_SESSION['reset_success']) . '</p>';
                        unset($_SESSION['reset_success']);
                    }
                ?>

                <form action="login.php" method="POST">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn">Login</button>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="forgot_password.php" style="color: #667eea; text-decoration: none; font-weight: 500;">Forgot Password?</a>
                    </div>
                </form>
            </div>

            <div id="signupForm" class="form-container">
                <h2>Create Account</h2>
                <form action="signup.php" method="POST">
                    <div class="form-group">
                        <input type="text" name="username" placeholder="Full Name" required>
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone" placeholder="Phone Number" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" minlength="6" required>
                    </div>
                    <button type="submit" class="btn">Sign Up</button>
                </form>
            </div>
        </div>

        <div class="info-section">
            <h1>✈️ Flight Booking</h1>
            <p>Join thousands of travelers who trust us for their flight bookings. Sign up today and start your journey!</p>
        </div>
    </div>

    <script>
        function showForm(formType) {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const toggleBtns = document.querySelectorAll('.toggle-btn');

            toggleBtns.forEach(btn => btn.classList.remove('active'));

            if (formType === 'login') {
                loginForm.classList.add('active');
                signupForm.classList.remove('active');
                toggleBtns[0].classList.add('active');
            } else {
                signupForm.classList.add('active');
                loginForm.classList.remove('active');
                toggleBtns[1].classList.add('active');
            }
        }
    </script>
</body>
</html>