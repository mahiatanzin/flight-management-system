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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SkyWings Airlines - Your Journey Begins Here</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        /* Background with plane image */
        .hero-section {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                        url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2074&q=80') center/cover no-repeat;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* Navigation */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links li {
            position: relative;
        }

        .nav-links a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Hero Content */
        .hero-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #fff;
            padding: 0 2rem;
            margin-top: 80px;
        }

        .hero-title {
            font-size: 4rem;
            font-weight: bold;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
            animation: fadeInUp 1s ease-out;
            position: relative;
            z-index: 2;
        }

        /* The CSS rule that placed a faint plane image behind the title has been removed from here. */

        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            max-width: 600px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .cta-button {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: #fff;
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0, 123, 255, 0.4);
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0, 123, 255, 0.6);
            background: linear-gradient(45deg, #0056b3, #004494);
        }
        
        /* New review button styling */
        .review-button {
            background: linear-gradient(45deg, #FFD700, #FFA500); /* Gold/Orange gradient */
            color: #fff;
            padding: 1rem 2.5rem;
            font-size: 1.2rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.4);
            animation: fadeInUp 1s ease-out 0.9s both;
            margin-top: 1rem;
        }

        .review-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(255, 215, 0, 0.6);
            background: linear-gradient(45deg, #FFA500, #FF8C00);
        }

        /* Feature Cards */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 4rem 5%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 20px;
            text-align: center;
            color: #fff;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }

        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .feature-description {
            line-height: 1.6;
            opacity: 0.9;
        }

        /* Mobile Menu */
        .mobile-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .mobile-menu span {
            width: 25px;
            height: 3px;
            background: #fff;
            margin: 3px 0;
            transition: 0.3s;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu {
                display: flex;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.2rem;
            }

            .navbar {
                padding: 1rem 3%;
            }

            .features {
                padding: 3rem 3%;
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">✈️ SkyWings</div>
    </nav>

    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Fly Beyond Horizons</h1>
            <p class="hero-subtitle">Experience the world with SkyWings Airlines - Your trusted partner for comfortable, safe, and memorable journeys across the globe.</p>
            <a href="book_flight.php" class="cta-button">Book Your Flight Now</a>
        </div>
    </section>

    <section class="features">
        <div class="feature-card" onclick="window.location.href='Customerinfo.php'">
            <span class="feature-icon">👤</span>
            <h3 class="feature-title">Customer Profile</h3>
            <p class="feature-description">Manage your personal information, preferences, and travel history in one convenient place.</p>
        </div>
        
        <div class="feature-card" onclick="window.location.href='book_flight.php'">
            <span class="feature-icon">✈️</span>
            <h3 class="feature-title">Easy Booking</h3>
            <p class="feature-description">Book flights effortlessly with our intuitive booking system. Compare prices and choose the perfect flight.</p>
        </div>
        
        <div class="feature-card" onclick="window.location.href='aboutus.php'">
            <span class="feature-icon">ℹ️</span>
            <h3 class="feature-title">About Us</h3>
            <p class="feature-description">Learn about our commitment to excellence, safety, and providing world-class aviation services.</p>
        </div>
        
        <div class="feature-card" onclick="window.location.href='flight_status.php'">
            <span class="feature-icon">📍</span>
            <h3 class="feature-title">Flight Status</h3>
            <p class="feature-description">Real-time flight information, delays, and updates. Stay informed about your journey.</p>
        </div>
        
        <div class="feature-card" onclick="window.location.href='history_and_ratings.php'">
            <span class="feature-icon">📜</span>
            <h3 class="feature-title">Travel History & Reviews</h3>
            <p class="feature-description">View your past bookings and share your experience with us by leaving a review.</p>
        </div>
    </section>

    <script>
        // Smooth scrolling and section handling
        function showSection(section) {
            // In a real application, this would navigate to different pages or show different content
            // The functions were already updated to use hrefs, so this is no longer used
        }

        // Mobile menu toggle (no longer needed)
        function toggleMobileMenu() {
            // Function kept for compatibility but not used
        }

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const logo = document.querySelector('.logo');
            const navLinks = document.querySelectorAll('.nav-links a');

            if (window.scrollY > 50) {
                navbar.style.background = 'rgba(255, 255, 255, 0.95)';
                navbar.style.backdropFilter = 'blur(20px)';
                logo.style.color = '#333';
                logo.style.textShadow = 'none';
                navLinks.forEach(el => {
                    el.style.color = '#333';
                    el.style.textShadow = 'none';
                });
            } else {
                navbar.style.background = 'rgba(255, 255, 255, 0.1)';
                navbar.style.backdropFilter = 'blur(10px)';
                logo.style.color = '#fff';
                logo.style.textShadow = '2px 2px 4px rgba(0, 0, 0, 0.5)';
                navLinks.forEach(el => {
                    el.style.color = '#fff';
                    el.style.textShadow = '2px 2px 4px rgba(0, 0, 0, 0.5)';
                });
            }
        });

        // Add some interactivity to cards
        document.querySelectorAll('.feature-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>