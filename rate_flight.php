<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: new.php");
    exit();
}

$customerID = $_SESSION['customer_id'];
$bookingID = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$flight_details = null;
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $bookingID > 0) {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    if ($rating < 1 || $rating > 5) {
        $_SESSION['review_message_type'] = 'error';
        $_SESSION['review_message'] = "Invalid rating provided. Please select 1 to 5 stars.";
        header("Location: rate_flight.php?booking_id=" . $bookingID);
        exit();
    }
    
    try {
        // Get booking details and verify flight has departed
        $verify_sql = "SELECT 
                b.GroupSize, 
                f.FlightID, 
                DATE(b.BookingDate) as FlightDate,
                f.DepartureTime,
                f.Path
            FROM Booking b 
            JOIN Flight f ON b.FlightID = f.FlightID 
            WHERE b.BookingID = ? AND b.CustomerID = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("ii", $bookingID, $customerID);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            $_SESSION['review_message_type'] = 'error';
            $_SESSION['review_message'] = "Invalid booking or you don't have permission to review this flight.";
            header("Location: rate_flight.php?booking_id=" . $bookingID);
            exit();
        }
        
        $booking_data = $verify_result->fetch_assoc();
        $group_size = $booking_data['GroupSize'];
        
        // Check if flight has departed
        $flight_datetime = $booking_data['FlightDate'] . ' ' . $booking_data['DepartureTime'];
        $flight_timestamp = strtotime($flight_datetime);
        $now = time();
        
        if ($flight_timestamp >= $now) {
            $_SESSION['review_message_type'] = 'error';
            $_SESSION['review_message'] = "You can only rate flights after they have departed. Your flight to " . $booking_data['Path'] . " is scheduled for " . date('M j, Y \a\t H:i', $flight_timestamp) . ".";
            header("Location: rate_flight.php?booking_id=" . $bookingID);
            exit();
        }
        
        $verify_stmt->close();

        // Count existing reviews for this booking
        $count_sql = "SELECT COUNT(*) as review_count FROM Reviews WHERE CustomerID = ? AND BookingID = ?";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param("ii", $customerID, $bookingID);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $review_count = $count_result->fetch_assoc()['review_count'];
        $count_stmt->close();

        // Check if user has already submitted maximum reviews (one per passenger)
        if ($review_count >= $group_size) {
            $_SESSION['review_message_type'] = 'error';
            $_SESSION['review_message'] = "You have already submitted the maximum number of reviews for this booking. You can submit " . $group_size . " review(s) for " . $group_size . " passenger(s).";
        } else {
            // Insert the new review
            $insert_sql = "INSERT INTO Reviews (CustomerID, BookingID, Rating, Comment, ReviewDate) VALUES (?, ?, ?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iiis", $customerID, $bookingID, $rating, $comment);

            if ($insert_stmt->execute()) {
                $remaining_reviews = $group_size - ($review_count + 1);
                $_SESSION['review_message_type'] = 'success';
                
                $base_message = '';
                switch ($rating) {
                    case 5: $base_message = 'Thank you for the 5-star rating! We are thrilled you had a fantastic experience.'; break;
                    case 4: $base_message = 'Thank you for your valuable feedback! We are happy you enjoyed your flight.'; break;
                    case 3: $base_message = 'Thank you for your honest feedback. We will work to improve your next experience.'; break;
                    case 2: $base_message = 'We apologize for the issues you faced. Your feedback will help us improve.'; break;
                    case 1: $base_message = 'We are very sorry for your poor experience. We will use your feedback to ensure this does not happen again.'; break;
                }
                
                if ($remaining_reviews > 0) {
                    $_SESSION['review_message'] = $base_message . " You can submit " . $remaining_reviews . " more review(s) for the other passenger(s) in your booking.";
                } else {
                    $_SESSION['review_message'] = $base_message . " You have submitted reviews for all passengers in your booking.";
                }
            } else {
                $_SESSION['review_message_type'] = 'error';
                $_SESSION['review_message'] = "Database error: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
    } catch (Exception $e) {
        $_SESSION['review_message_type'] = 'error';
        $_SESSION['review_message'] = "An unexpected error occurred: " . $e->getMessage();
    }
    
    header("Location: rate_flight.php?booking_id=" . $bookingID);
    exit();
}

// Fetch flight details for the rating form
if ($bookingID > 0) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                f.Path, 
                b.GroupSize,
                DATE(b.BookingDate) as FlightDate,
                f.DepartureTime,
                (SELECT COUNT(*) FROM Reviews WHERE CustomerID = ? AND BookingID = ?) as submitted_reviews
            FROM Flight f
            JOIN Booking b ON f.FlightID = b.FlightID
            WHERE b.BookingID = ? AND b.CustomerID = ?
        ");
        $stmt->bind_param("iiii", $customerID, $bookingID, $bookingID, $customerID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $flight_details = $result->fetch_assoc();
            
            // Check if flight has departed
            $flight_datetime = $flight_details['FlightDate'] . ' ' . $flight_details['DepartureTime'];
            $flight_timestamp = strtotime($flight_datetime);
            $now = time();
            
            if ($flight_timestamp >= $now) {
                $error_message = "You can only rate flights after they have departed. Your flight to " . $flight_details['Path'] . " is scheduled for " . date('M j, Y \a\t H:i', $flight_timestamp) . ".";
            }
        } else {
            $error_message = "Booking not found or you are not authorized to review this flight.";
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
} else {
    header("Location: history_and_ratings.php");
    exit();
}

$message = isset($_SESSION['review_message']) ? $_SESSION['review_message'] : '';
$message_type = isset($_SESSION['review_message_type']) ? $_SESSION['review_message_type'] : '';
unset($_SESSION['review_message'], $_SESSION['review_message_type']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Your Flight - SkyWings Airlines</title>
    <style>
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 2rem; 
        }
        .container { 
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1); 
            padding: 2.5rem; 
            max-width: 550px; 
            width: 100%; 
        }
        .flight-info { 
            background-color: #f8f9fa; 
            border-left: 4px solid #667eea; 
            padding: 1rem 1.5rem; 
            border-radius: 8px; 
            margin-bottom: 2rem; 
            text-align: left; 
        }
        .flight-info p { 
            margin: 0.5rem 0; 
            font-size: 1.1rem; 
        }
        .review-counter { 
            background-color: #e3f2fd; 
            border: 1px solid #2196f3; 
            padding: 1rem; 
            border-radius: 8px; 
            margin-bottom: 1.5rem; 
            text-align: center; 
            color: #1976d2; 
        }
        h1 { 
            color: #1a202c; 
            text-align: center; 
            margin-bottom: 0.5rem; 
        }
        .subtitle { 
            text-align: center; 
            color: #666; 
            margin-bottom: 2rem; 
        }
        .rating-stars { 
            display: flex; 
            justify-content: center; 
            gap: 0.5rem; 
            margin: 2rem 0; 
        }
        .rating-stars svg { 
            width: 2.5rem; 
            height: 2.5rem; 
            color: #ccc; 
            cursor: pointer; 
            transition: all 0.2s ease; 
        }
        .rating-stars svg:hover, .rating-stars svg.active { 
            color: #FFD700; 
            transform: scale(1.1); 
        }
        textarea { 
            width: 100%; 
            box-sizing: border-box; 
            min-height: 120px; 
            padding: 1rem; 
            border-radius: 10px; 
            border: 1px solid #ced4da; 
            font-size: 1rem; 
            resize: vertical; 
            margin-bottom: 1.5rem; 
        }
        .button-group { 
            display: flex; 
            justify-content: center; 
            gap: 1rem; 
        }
        button, .cancel-link { 
            text-decoration: none; 
            padding: 0.9rem 1.8rem; 
            border-radius: 10px; 
            font-size: 1.1rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: all 0.3s; 
        }
        button[type="submit"] { 
            background: linear-gradient(45deg, #667eea, #764ba2); 
            color: white; 
            border: none; 
        }
        button[type="submit"]:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 4px 10px rgba(0,0,0,0.1); 
        }
        button[type="submit"]:disabled { 
            background: #ccc; 
            cursor: not-allowed; 
            transform: none; 
        }
        .cancel-link { 
            background-color: #e9ecef; 
            color: #495057; 
        }
        .cancel-link:hover { 
            background-color: #dee2e6; 
        }
        .modal { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.6); 
            display: none; 
            justify-content: center; 
            align-items: center; 
            z-index: 100; 
        }
        .modal-content { 
            background: white; 
            padding: 2rem; 
            border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.3); 
            text-align: center; 
            max-width: 400px; 
        }
        .modal-content.error { 
            border-top: 5px solid #dc3545; 
        }
        .modal-content.success { 
            border-top: 5px solid #28a745; 
        }
        .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .modal h3 {
            margin: 0 0 1rem 0;
            color: #495057;
        }
        .modal p {
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 2rem;
        }
        .modal-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .modal-button:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error_message): ?>
            <div class="modal-content error">
                <h3>Unable to Rate Flight</h3>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <a href="history_and_ratings.php" class="modal-button">Back to History</a>
            </div>
        <?php else: ?>
            <h1>Rate Your Flight</h1>
            <p class="subtitle">Share your experience with SkyWings Airlines</p>
            
            <?php if ($flight_details): ?>
                <div class="flight-info">
                    <p><strong>Flight Route:</strong> <?php echo htmlspecialchars($flight_details['Path']); ?></p>
                    <p><strong>Flight Date:</strong> <?php echo date('M j, Y', strtotime($flight_details['FlightDate'])); ?></p>
                    <p><strong>Departure Time:</strong> <?php echo date('H:i', strtotime($flight_details['DepartureTime'])); ?></p>
                </div>
                
                <div class="review-counter">
                    <p>Reviews submitted: <?php echo $flight_details['submitted_reviews']; ?> of <?php echo $flight_details['GroupSize']; ?></p>
                </div>

                <form method="POST" action="rate_flight.php?booking_id=<?php echo $bookingID; ?>" id="ratingForm">
                    <div class="rating-stars">
                        <svg data-rating="1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="star-icon">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg data-rating="2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="star-icon">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg data-rating="3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="star-icon">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg data-rating="4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="star-icon">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        <svg data-rating="5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="star-icon">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                    </div>
                    
                    <input type="hidden" name="rating" id="ratingInput" required>
                    
                    <textarea name="comment" placeholder="Share your experience with us (optional)..." maxlength="500"></textarea>
                    
                    <div class="button-group">
                        <button type="submit" id="submitBtn" disabled>Submit Review</button>
                        <a href="history_and_ratings.php" class="cancel-link">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Success/Error Modal -->
    <?php if ($message): ?>
    <div id="messageModal" class="modal" style="display: flex;">
        <div class="modal-content <?php echo $message_type; ?>">
            <div class="modal-icon">
                <?php echo $message_type === 'success' ? '✅' : '❌'; ?>
            </div>
            <h3><?php echo $message_type === 'success' ? 'Thank You!' : 'Oops!'; ?></h3>
            <p><?php echo htmlspecialchars($message); ?></p>
            <button class="modal-button" onclick="closeModal()">
                <?php echo $message_type === 'success' ? 'Continue' : 'Try Again'; ?>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        const stars = document.querySelectorAll('.star-icon');
        const ratingInput = document.getElementById('ratingInput');
        const submitBtn = document.getElementById('submitBtn');
        let selectedRating = 0;

        stars.forEach((star, index) => {
            star.addEventListener('click', () => {
                selectedRating = parseInt(star.dataset.rating);
                ratingInput.value = selectedRating;
                
                // Update star display
                stars.forEach((s, i) => {
                    if (i < selectedRating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Enable submit button
                submitBtn.disabled = false;
            });

            // Hover effect
            star.addEventListener('mouseenter', () => {
                const hoverRating = parseInt(star.dataset.rating);
                stars.forEach((s, i) => {
                    if (i < hoverRating) {
                        s.style.color = '#FFD700';
                    } else {
                        s.style.color = '#ccc';
                    }
                });
            });
        });

        // Reset hover effect on mouse leave
        document.querySelector('.rating-stars').addEventListener('mouseleave', () => {
            stars.forEach((s, i) => {
                if (i < selectedRating) {
                    s.style.color = '#FFD700';
                } else {
                    s.style.color = '#ccc';
                }
            });
        });

        function closeModal() {
            document.getElementById('messageModal').style.display = 'none';
            // If success, redirect to history page
            <?php if ($message_type === 'success'): ?>
            window.location.href = 'history_and_ratings.php';
            <?php endif; ?>
        }

        // Form validation
        document.getElementById('ratingForm').addEventListener('submit', function(e) {
            if (!selectedRating) {
                e.preventDefault();
                alert('Please select a star rating before submitting.');
                return false;
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('messageModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>