<?php
session_start();
require_once 'config.php';

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: new.php");
    exit();
}

$customerID = $_SESSION['customer_id'];
$bookings = [];
$error_message = '';

try {
    // Updated query to get booking details with review counts and individual reviews
    $sql = "
        SELECT 
            b.BookingID,
            b.BookingDate,
            b.PricePaid,
            b.SeatClass,
            b.GroupSize,
            f.FlightID,
            f.Path,
            f.DepartureTime,
            f.ArrivalTime,
            DATE(b.BookingDate) as FlightDate,
            (SELECT COUNT(*) FROM Reviews WHERE BookingID = b.BookingID AND CustomerID = b.CustomerID) as review_count,
            (SELECT GROUP_CONCAT(CONCAT(Rating, ':', IFNULL(Comment, '')) SEPARATOR '||') FROM Reviews WHERE BookingID = b.BookingID AND CustomerID = b.CustomerID) as reviews_data
        FROM Booking b
        JOIN Flight f ON b.FlightID = f.FlightID
        WHERE b.CustomerID = ?
        ORDER BY b.BookingDate DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $customerID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Parse reviews data
            $reviews = [];
            if ($row['reviews_data']) {
                $review_entries = explode('||', $row['reviews_data']);
                foreach ($review_entries as $entry) {
                    $parts = explode(':', $entry, 2);
                    $reviews[] = [
                        'rating' => (int)$parts[0],
                        'comment' => isset($parts[1]) ? $parts[1] : ''
                    ];
                }
            }
            $row['reviews'] = $reviews;
            $bookings[] = $row;
        }
    } else {
        $error_message = "You have no booking history to display.";
    }
    $stmt->close();
} catch (Exception $e) {
    $error_message = "Error fetching your travel history: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Travel History - SkyWings Airlines</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            color: #333;
            margin: 0;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .header h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
            font-weight: 300;
        }
        
        .header p {
            margin: 0;
            opacity: 0.9;
        }
        
        .back-link {
            position: absolute;
            top: 2rem;
            left: 2rem;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }
        
        .bookings-container {
            padding: 2rem;
        }
        
        .booking-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .booking-id {
            font-size: 1.2rem;
            font-weight: 600;
            color: #495057;
        }
        
        .booking-date {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .flight-info {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-group h4 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-group p {
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
            color: #495057;
        }
        
        .review-section {
            background: white;
            border-radius: 8px;
            padding: 1rem;
        }
        
        .review-progress {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .progress-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 6px;
            width: 100px;
            overflow: hidden;
            margin: 0 1rem;
        }
        
        .progress-fill {
            background: linear-gradient(45deg, #28a745, #20c997);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .existing-reviews {
            margin-bottom: 1rem;
        }
        
        .review-item {
            background: #f1f3f4;
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .stars {
            color: #FFD700;
            margin-right: 0.5rem;
        }
        
        .action-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .action-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .completed-badge {
            background: #d4edda;
            color: #155724;
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 450px;
            width: 90%;
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
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }
        
        @media (max-width: 768px) {
            .flight-info {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .booking-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .review-progress {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .progress-bar {
                width: 100%;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <a href="homepage.php" class="back-link">← Back to Home</a>
    
    <div class="container">
        <div class="header">
            <h1>My Travel History</h1>
            <p>Track your journeys and share your experiences</p>
        </div>

        <div class="bookings-container">
            <?php if (!empty($error_message)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✈️</div>
                    <h3>No Travel History</h3>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php elseif (empty($bookings)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">✈️</div>
                    <h3>No Bookings Yet</h3>
                    <p>Your travel history will appear here once you make your first booking.</p>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <?php 
                    // Fixed flight departure check logic
                    $flight_date = $booking['FlightDate']; // This is the flight date from BookingDate
                    $departure_time = $booking['DepartureTime']; // This is just the time (HH:MM:SS)
                    
                    // Combine flight date with departure time
                    $flight_datetime = $flight_date . ' ' . $departure_time;
                    $flight_timestamp = strtotime($flight_datetime);
                    $now = time();
                    
                    // Check if flight has departed
                    $has_departed = $flight_timestamp < $now;
                    
                    $has_reviews = $booking['review_count'] > 0;
                    $can_add_more = $booking['review_count'] < $booking['GroupSize'];
                    $progress_percent = ($booking['review_count'] / $booking['GroupSize']) * 100;
                    ?>
                    
                    <div class="booking-card">
                        <div class="booking-header">
                            <div>
                                <div class="booking-id">Booking #<?php echo htmlspecialchars($booking['BookingID']); ?></div>
                                <div class="booking-date"><?php echo date('F j, Y', strtotime($booking['BookingDate'])); ?></div>
                            </div>
                            <?php if ($has_reviews && !$can_add_more): ?>
                                <span class="completed-badge">✓ Reviews Complete</span>
                            <?php endif; ?>
                        </div>

                        <div class="flight-info">
                            <div class="info-group">
                                <h4>Flight Route</h4>
                                <p><?php echo htmlspecialchars($booking['Path']); ?></p>
                                <p style="font-size: 0.9rem; color: #6c757d; margin-top: 0.3rem;">
                                    <?php echo htmlspecialchars($booking['SeatClass']); ?> • Departure: <?php echo date('H:i', strtotime($booking['DepartureTime'])); ?>
                                </p>
                                <p style="font-size: 0.8rem; color: #6c757d; margin-top: 0.2rem;">
                                    Flight Date: <?php echo date('M j, Y', strtotime($booking['FlightDate'])); ?>
                                </p>
                            </div>
                            <div class="info-group">
                                <h4>Passengers</h4>
                                <p><?php echo htmlspecialchars($booking['GroupSize']); ?> passenger<?php echo $booking['GroupSize'] > 1 ? 's' : ''; ?></p>
                            </div>
                            <div class="info-group">
                                <h4>Total Price</h4>
                                <p>BDT <?php echo number_format($booking['PricePaid'], 2); ?></p>
                            </div>
                        </div>

                        <div class="review-section">
                            <?php if ($has_reviews): ?>
                                <div class="existing-reviews">
                                    <h4 style="margin: 0 0 0.8rem 0; font-size: 0.9rem; color: #6c757d;">Your Reviews</h4>
                                    <?php foreach ($booking['reviews'] as $review): ?>
                                        <div class="review-item">
                                            <span class="stars"><?php echo str_repeat('★', $review['rating']); ?></span>
                                            <?php if ($review['comment']): ?>
                                                <?php echo htmlspecialchars($review['comment']); ?>
                                            <?php else: ?>
                                                <em>No comment provided</em>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="review-progress">
                                <span class="progress-info">
                                    <?php echo $booking['review_count']; ?> of <?php echo $booking['GroupSize']; ?> reviews
                                </span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                                </div>
                                <?php if ($has_departed): ?>
                                    <?php if ($can_add_more): ?>
                                        <a href="rate_flight.php?booking_id=<?php echo htmlspecialchars($booking['BookingID']); ?>" class="action-button">
                                            <?php echo $has_reviews ? 'Add Review' : 'Rate Flight'; ?>
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="action-button" onclick="showFlightNotDepartedModal('<?php echo htmlspecialchars($booking['Path']); ?>', '<?php echo date('M j, Y \a\t H:i', $flight_timestamp); ?>')">
                                        Rate Flight
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for flights that haven't departed -->
    <div id="flightNotDepartedModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">🛫</div>
            <h3>Flight Not Departed Yet</h3>
            <p id="modalFlightRoute"></p>
            <p id="modalFlightTime"></p>
            <p>You can share your travel experience after your flight departs. We look forward to hearing about your journey with SkyWings Airlines!</p>
            <button class="modal-button" onclick="closeModal()">Got it</button>
        </div>
    </div>

    <script>
        function showFlightNotDepartedModal(flightRoute, flightTime) {
            document.getElementById('modalFlightRoute').textContent = `Your flight to ${flightRoute}`;
            document.getElementById('modalFlightTime').textContent = `Departure: ${flightTime}`;
            document.getElementById('flightNotDepartedModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('flightNotDepartedModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('flightNotDepartedModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>