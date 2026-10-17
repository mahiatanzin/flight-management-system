<?php
session_start();
// Add cache control headers to prevent browser caching issues
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
header("Pragma: no-cache");

require_once 'config.php';

// --- PRG PATTERN START ---
// Check for a result message from a previous POST request
if (isset($_SESSION['form_result'])) {
    $message = $_SESSION['form_result']['message'];
    $message_type = $_SESSION['form_result']['message_type'];
    $receipt_details = $_SESSION['form_result']['receipt_details'] ?? [];
    unset($_SESSION['form_result']); // Clear the message after displaying it
} else {
    $message = null;
    $message_type = '';
    $receipt_details = [];
}
// --- PRG PATTERN END ---

// Unset any previous booking details if the user starts over
if(basename($_SERVER['PHP_SELF']) == 'book_flight.php' && !$_POST) {
    unset($_SESSION['booking_details']);
}

// Check if the user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: new.php");
    exit();
}

// Fetch all available flights for the dropdown
$flights = [];
$flight_query = "SELECT FlightID, Path, DepartureTime, ArrivalTime FROM Flight ORDER BY Path";
$result = $conn->query($flight_query);
while ($row = $result->fetch_assoc()) {
    $flights[] = $row;
}

// Shared logic for both form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $flight_id = $_POST['flight_id'];
    $class_type = $_POST['class'];
    $group_size = isset($_POST['group_size']) ? (int)$_POST['group_size'] : 1;
    $flight_date = $_POST['flight_date'];
    if ($group_size < 1) $group_size = 1;

    try {
        $conn->begin_transaction();
        
        $flight_details_query = "SELECT f.FlightID, f.Path, f.DepartureTime, f.ArrivalTime, p.* FROM Flight f JOIN Plane p ON f.PlaneID = p.PlaneID WHERE f.FlightID = ?";
        $stmt = $conn->prepare($flight_details_query);
        $stmt->bind_param("i", $flight_id);
        $stmt->execute();
        $flight_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$flight_data) throw new Exception("Flight not found.");

        $price_per_ticket = 0; $seat_type = '';
        switch ($class_type) {
            case 'First': $seat_type = 'First Class'; $price_column = 'Fprice'; $seats_column = 'Fseats'; break;
            case 'Business': $seat_type = 'Business Class'; $price_column = 'Bprice'; $seats_column = 'Bseats'; break;
            case 'Economy': $seat_type = 'Economy Class'; $price_column = 'Eprice'; $seats_column = 'Eseats'; break;
            default: throw new Exception("Invalid class selected.");
        }
        
        $total_seats = $flight_data[$seats_column];
        
        $bookings_query = "SELECT IFNULL(SUM(GroupSize), 0) AS booked_seats FROM Booking WHERE FlightID = ? AND SeatClass = ? AND DATE(BookingDate) = ?";
        $stmt = $conn->prepare($bookings_query);
        $stmt->bind_param("iss", $flight_id, $seat_type, $flight_date);
        $stmt->execute();
        $booked_count = $stmt->get_result()->fetch_assoc()['booked_seats'];
        $stmt->close();
        
        $seats_left = $total_seats - $booked_count;

        // --- RESTORED SEAT SUGGESTION LOGIC ---
        if ($seats_left < $group_size) {
            $suggestions = [];
            $classes_to_check = ['First', 'Business', 'Economy'];
            foreach ($classes_to_check as $c) {
                if ($c === $class_type) continue; 

                $sugg_flag_col = ($c == 'First') ? 'Fflag' : (($c == 'Business') ? 'Bflag' : 'Eflag');
                $sugg_seats_col = ($c == 'First') ? 'Fseats' : (($c == 'Business') ? 'Bseats' : 'Eseats');
                $sugg_price_col = ($c == 'First') ? 'Fprice' : (($c == 'Business') ? 'Bprice' : 'Eprice');
                $sugg_seat_type = ($c == 'First') ? 'First Class' : (($c == 'Business') ? 'Business Class' : 'Economy Class');

                if ($flight_data[$sugg_flag_col]) {
                    $sugg_bookings_query = "SELECT IFNULL(SUM(GroupSize), 0) AS booked_seats FROM Booking WHERE FlightID = ? AND SeatClass = ? AND DATE(BookingDate) = ?";
                    $stmt_sugg = $conn->prepare($sugg_bookings_query);
                    $stmt_sugg->bind_param("iss", $flight_id, $sugg_seat_type, $flight_date);
                    $stmt_sugg->execute();
                    $sugg_booked_count = $stmt_sugg->get_result()->fetch_assoc()['booked_seats'];
                    $stmt_sugg->close();

                    $sugg_seats_left = $flight_data[$sugg_seats_col] - $sugg_booked_count;

                    if ($sugg_seats_left >= $group_size) {
                        $suggestions[] = $sugg_seat_type . ' (BDT ' . number_format($flight_data[$sugg_price_col]) . ' each)';
                    }
                }
            }
            
            $error_message = "Sorry, only $seats_left seat(s) are available in " . $seat_type . " for the selected date.";
            if (!empty($suggestions)) {
                $error_message .= " You can try these classes instead: " . implode(', ', $suggestions) . ".";
            } else {
                 $error_message = "Sorry, this flight is fully booked for a group of your size on the selected date, and no other classes have sufficient seats available.";
            }
            throw new Exception($error_message);
        }
        
        $seat_numbers = [];
        while (count($seat_numbers) < $group_size) {
            $new_seat = strtoupper(substr($class_type, 0, 1)) . rand(1, $total_seats);
            if (!in_array($new_seat, $seat_numbers)) $seat_numbers[] = $new_seat;
        }
        $seat_numbers_str = implode(', ', $seat_numbers);

        if (isset($_POST['proceed_to_discounts'])) {
            $_SESSION['booking_details'] = [
                'flight_id' => $flight_id, 'flight_path' => $flight_data['Path'],
                'flight_date' => $flight_date,
                'departure_time' => date('H:i', strtotime($flight_data['DepartureTime'])),
                'arrival_time' => date('H:i', strtotime($flight_data['ArrivalTime'])),
                'group_size' => $group_size, 'seat_type' => $seat_type,
                'price_per_ticket' => $flight_data[$price_column],
                'seat_numbers_str' => $seat_numbers_str
            ];
            $conn->commit();
            header("Location: select_discount.php");
            exit();
        }

        if (isset($_POST['confirm_booking_no_discount'])) {
            $price_per_ticket_val = $flight_data[$price_column];
            $total_price_paid = $price_per_ticket_val * $group_size;

            $insert_booking_query = "INSERT INTO Booking (GroupSize, BookingDate, PricePaid, SeatNo, SeatClass, CustomerID, FlightID) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_booking_query);
            $booking_datetime = $flight_date . ' ' . date('H:i:s');
            $stmt->bind_param("isdssii", $group_size, $booking_datetime, $total_price_paid, $seat_numbers_str, $seat_type, $_SESSION['customer_id'], $flight_id);
            
            if (!$stmt->execute()) throw new Exception("Error creating booking: " . $stmt->error);
            
            $booking_id = $conn->insert_id;
            $stmt->close();
            
            // Set the last booking ID in the session
            $_SESSION['last_booking_id'] = $booking_id;
            
            $conn->commit();
            
            $_SESSION['form_result'] = [
                'message' => "Your booking was successful!",
                'message_type' => 'success',
                'receipt_details' => [
                    'booking_id' => $booking_id, 'customer_name' => $_SESSION['customer_name'],
                    'flight_path' => $flight_data['Path'],
                    'flight_date' => date('M j, Y', strtotime($flight_date)),
                    'departure_time' => date('H:i', strtotime($flight_data['DepartureTime'])),
                    'arrival_time' => date('H:i', strtotime($flight_data['ArrivalTime'])),
                    'group_size' => $group_size,
                    'seat_numbers' => $seat_numbers_str, 'seat_class' => $seat_type,
                    'price_per_ticket' => number_format($price_per_ticket_val, 2),
                    'total_cost' => number_format($total_price_paid, 2)
                ]
            ];
            header("Location: book_flight.php");
            exit();
        }

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['form_result'] = [ 'message' => $e->getMessage(), 'message_type' => 'error' ];
        header("Location: book_flight.php");
        exit();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Flight - SkyWings Airlines</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .container { width: 100%; max-width: 800px; background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 2rem; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding-bottom: 1rem; border-bottom: 1px solid #e9ecef; margin-bottom: 1.5rem; }
        .navbar a { text-decoration: none; background: #e9ecef; padding: 0.5rem 1rem; border-radius: 10px; color: #495057; font-weight: 500; }
        h1 { text-align: center; color: #1a202c; margin: 0; }
        .form-section { display: grid; grid-template-columns: 1fr; gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 0.5rem; }
        .form-group select, .form-group input { padding: 0.8rem; border-radius: 8px; border: 1px solid #cbd5e0; font-size: 1rem; }
        .class-options { display: flex; gap: 1rem; }
        .class-option { background: #e9ecef; border-radius: 12px; padding: 1rem; cursor: pointer; flex-grow: 1; display: flex; justify-content: space-between; align-items: center; border: 2px solid transparent; transition: all 0.3s ease; }
        .class-option.active { border-color: #667eea; background-color: #e6eafb; }
        .class-option h3, .class-option p { margin: 0; }
        .button-group { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .submit-btn { border: none; padding: 1rem; border-radius: 8px; font-size: 1.1rem; font-weight: 600; cursor: pointer; width: 100%; }
        .confirm-btn { background: #28a745; color: white; }
        .discount-btn { background: linear-gradient(45deg, #667eea, #764ba2); color: white; }
        #priceDisplay { text-align: center; font-size: 1.5rem; font-weight: 700; margin-top: 1.5rem; padding: 1rem; background: #f1f3f4; border-radius: 12px; }
        #priceDisplay span { color: #28a745; }
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 1rem; }
        .modal-content { background-color: #fefefe; padding: 2rem; border-radius: 15px; width: 100%; max-width: 500px; text-align: center; max-height: 90vh; overflow-y: auto; }
        .receipt-section { text-align: left; margin-top: 1rem; }
        .receipt-item { display: flex; justify-content: space-between; padding: 0.25rem 0; border-bottom: 1px dashed #ced4da; }
        #modal-action-btn { text-decoration: none; padding: 0.7rem 1.5rem; color: white; border-radius: 8px; display: inline-block; margin-top: 1rem; }
        @media (min-width: 768px) { .form-section { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <nav class="navbar"><a href="homepage.php">← Back to Home</a><h1>Book Your Flight</h1><div></div></nav>
    <form method="POST">
        <div class="form-section">
            <div class="form-group"><label for="flight_id">Choose a Flight</label><select id="flight_id" name="flight_id" required><option value="" disabled selected>Select a route</option><?php foreach ($flights as $flight): ?><option value="<?php echo htmlspecialchars($flight['FlightID']); ?>"><?php echo htmlspecialchars($flight['Path'] . ' (' . date('H:i', strtotime($flight['DepartureTime'])) . ' - ' . date('H:i', strtotime($flight['ArrivalTime'])) . ')'); ?></option><?php endforeach; ?></select></div>
            
            <div class="form-group">
                <label for="flight_date">Select Date</label>
                <input type="date" id="flight_date" name="flight_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group"><label for="group_size">Number of Passengers</label><input type="number" id="group_size" name="group_size" value="1" min="1" max="10" required></div>
            
            <div class="form-group" style="grid-column: 1 / -1;"><label>Select Class</label><div class="class-options" id="class-options-container"><div class="class-option" data-class="First"><input type="radio" name="class" value="First" class="hidden" required><h3>First Class</h3><p id="first_price">BDT 0</p></div><div class="class-option" data-class="Business"><input type="radio" name="class" value="Business" class="hidden"><h3>Business</h3><p id="business_price">BDT 0</p></div><div class="class-option" data-class="Economy"><input type="radio" name="class" value="Economy" class="hidden"><h3>Economy</h3><p id="economy_price">BDT 0</p></div></div></div>
        </div>
        <div id="priceDisplay">Total Cost: BDT <span>0.00</span></div>
        <div class="button-group">
            <button type="submit" name="confirm_booking_no_discount" class="submit-btn confirm-btn">Confirm Booking</button>
            <button type="submit" name="proceed_to_discounts" class="submit-btn discount-btn">Apply Discount & Proceed</button>
        </div>
    </form>
</div>

<div id="statusModal" class="modal">
    <div class="modal-content">
        <h2 id="modal-title"></h2>
        <p id="modal-message"></p>
        <div id="receipt" class="receipt-section" style="display: none;"></div><br>
        <a href="#" id="modal-action-btn"></a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const flightSelect = document.getElementById('flight_id');
    const classOptionsContainer = document.getElementById('class-options-container');
    const groupSizeInput = document.getElementById('group_size');
    const priceDisplay = document.getElementById('priceDisplay');

    function updateTotalPrice() {
        const selectedClass = document.querySelector('.class-option.active');
        const groupSize = parseInt(groupSizeInput.value) || 1;
        if (selectedClass) {
            const basePrice = parseFloat(selectedClass.dataset.price) || 0;
            const totalCost = basePrice * groupSize;
            priceDisplay.querySelector('span').textContent = totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
    
    flightSelect.addEventListener('change', function() {
        const flightId = this.value;
        if (!flightId) return;
        fetch('get_flight_details.php?flight_id=' + flightId)
            .then(response => response.json())
            .then(data => {
                document.querySelector('.class-option[data-class="First"]').dataset.price = data.Fprice;
                document.querySelector('#first_price').textContent = 'BDT ' + parseFloat(data.Fprice).toLocaleString();
                document.querySelector('.class-option[data-class="Business"]').dataset.price = data.Bprice;
                document.querySelector('#business_price').textContent = 'BDT ' + parseFloat(data.Bprice).toLocaleString();
                document.querySelector('.class-option[data-class="Economy"]').dataset.price = data.Eprice;
                document.querySelector('#economy_price').textContent = 'BDT ' + parseFloat(data.Eprice).toLocaleString();
                updateTotalPrice();
            });
    });
    groupSizeInput.addEventListener('input', updateTotalPrice);
    classOptionsContainer.addEventListener('click', function(e) {
        const selectedOption = e.target.closest('.class-option');
        if (selectedOption) {
            document.querySelectorAll('.class-option').forEach(opt => opt.classList.remove('active'));
            selectedOption.classList.add('active');
            selectedOption.querySelector('input[type="radio"]').checked = true;
            updateTotalPrice();
        }
    });

    <?php if ($message): ?>
    const modal = document.getElementById('statusModal');
    const modalTitle = document.getElementById('modal-title');
    const modalMessage = document.getElementById('modal-message');
    const modalButton = document.getElementById('modal-action-btn');
    const receiptDiv = document.getElementById('receipt');
    
    const messageType = '<?php echo $message_type; ?>';
    modalMessage.textContent = '<?php echo addslashes($message); ?>';

    if (messageType === 'success') {
        modalTitle.textContent = 'Booking Confirmed!';
        modalButton.textContent = 'Go to Homepage';
        modalButton.href = 'homepage.php';
        modalButton.style.background = '#28a745';
        
        <?php if (!empty($receipt_details)): ?>
        const receiptData = <?php echo json_encode($receipt_details); ?>;
        let html = '<h3>Booking Receipt</h3>';
        html += `<div class="receipt-item"><span>Booking ID:</span><span>#${receiptData.booking_id}</span></div>`;
        html += `<div class="receipt-item"><span>Customer Name:</span><span>${receiptData.customer_name}</span></div>`;
        html += `<div class="receipt-item"><span>Flight Date:</span><span>${receiptData.flight_date}</span></div>`;
        html += `<div class="receipt-item"><span>Flight Route:</span><span>${receiptData.flight_path}</span></div>`;
        html += `<div class="receipt-item"><span>Departure:</span><span>${receiptData.departure_time}</span></div>`;
        html += `<div class="receipt-item"><span>Arrival:</span><span>${receiptData.arrival_time}</span></div>`;
        html += `<div class="receipt-item"><span>Passengers:</span><span>${receiptData.group_size}</span></div>`;
        html += `<div class="receipt-item"><span>Seat Class:</span><span>${receiptData.seat_class}</span></div>`;
        html += `<div class="receipt-item"><span>Seat Numbers:</span><span>${receiptData.seat_numbers}</span></div>`;
        html += `<div class="receipt-item"><span>Price per Ticket:</span><span>BDT ${receiptData.price_per_ticket}</span></div>`;
        html += `<div class="receipt-item"><strong>Total Cost:</strong><strong>BDT ${receiptData.total_cost}</strong></div>`;
        receiptDiv.innerHTML = html;
        receiptDiv.style.display = 'block';
        <?php endif; ?>
    } else {
        modalTitle.textContent = 'Booking Failed';
        modalButton.textContent = 'Back';
        modalButton.href = 'book_flight.php';
        modalButton.style.background = '#dc3545';
        receiptDiv.style.display = 'none';
    }

    modal.style.display = 'flex';
    <?php endif; ?>
});
</script>
</body>
</html>
