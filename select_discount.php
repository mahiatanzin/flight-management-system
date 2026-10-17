<?php
session_start();
require_once 'config.php';

// If no booking details are in the session, redirect back to the start.
if (!isset($_SESSION['booking_details'])) {
    header("Location: book_flight.php");
    exit();
}

$booking_details = $_SESSION['booking_details'];
$receipt_details = [];
$message = null;
$message_type = '';

// Fetch all active discounts based on the selected flight date
$discounts = [];
$flight_date = $booking_details['flight_date'];
$query = "SELECT DiscountID, DiscountName, Percentage FROM Discount WHERE ? BETWEEN StartDate AND EndDate ORDER BY Percentage DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $flight_date);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $discounts[] = $row;
    }
}
$stmt->close();


// Handle the final booking confirmation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking_with_discount'])) {
    $selected_discount_id = isset($_POST['discount_id']) ? (int)$_POST['discount_id'] : 0;

    try {
        $conn->begin_transaction();

        $price_per_ticket = $booking_details['price_per_ticket'];
        $group_size = $booking_details['group_size'];

        // Apply discount if one was selected and is valid
        $discount_percentage = 0;
        $discount_name = 'No Discount';
        if ($selected_discount_id > 0) {
            $disc_query = "SELECT DiscountName, Percentage FROM Discount WHERE DiscountID = ? AND ? BETWEEN StartDate AND EndDate";
            $stmt = $conn->prepare($disc_query);
            $stmt->bind_param("is", $selected_discount_id, $flight_date);
            $stmt->execute();
            $discount_data = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($discount_data) {
                $discount_percentage = $discount_data['Percentage'];
                $discount_name = $discount_data['DiscountName'];
            }
        }

        $discount_amount = $price_per_ticket * ($discount_percentage / 100);
        $final_price_per_ticket = $price_per_ticket - $discount_amount;
        $total_price_paid = $final_price_per_ticket * $group_size;

        // Insert the final booking with the selected date
        $insert_booking_query = "INSERT INTO Booking (GroupSize, BookingDate, PricePaid, SeatNo, SeatClass, CustomerID, FlightID) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_booking_query);
        $booking_datetime = $flight_date . ' ' . date('H:i:s');
        $stmt->bind_param("isdssii", 
            $group_size, 
            $booking_datetime, 
            $total_price_paid, 
            $booking_details['seat_numbers_str'], 
            $booking_details['seat_type'], 
            $_SESSION['customer_id'], 
            $booking_details['flight_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Database error: Could not save the booking.");
        }
        $booking_id = $conn->insert_id;
        $stmt->close();
        $conn->commit();

        // Prepare receipt for display, including the flight date
        $receipt_details = [
            'booking_id' => $booking_id,
            'customer_name' => $_SESSION['customer_name'],
            'flight_path' => $booking_details['flight_path'],
            'flight_date' => date('M j, Y', strtotime($flight_date)),
            'departure_time' => $booking_details['departure_time'],
            'arrival_time' => $booking_details['arrival_time'],
            'group_size' => $group_size,
            'seat_numbers' => $booking_details['seat_numbers_str'],
            'seat_class' => $booking_details['seat_type'],
            'price_per_ticket' => number_format($price_per_ticket, 2),
            'discount_applied' => number_format($discount_amount * $group_size, 2),
            'discount_name' => $discount_name,
            'total_cost' => number_format($total_price_paid, 2)
        ];

        $message = "Your booking was successful!";
        $message_type = 'success';
        unset($_SESSION['booking_details']);

    } catch (Exception $e) {
        $conn->rollback();
        $message_type = 'error';
        $message = $e->getMessage();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Discount - SkyWings Airlines</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .container { width: 100%; max-width: 800px; background: rgba(255, 255, 255, 0.95); border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); padding: 2rem; }
        .header { text-align: center; margin-bottom: 1.5rem; }
        .summary, .discounts-section { margin-bottom: 2rem; background: #f8f9fa; padding: 1.5rem; border-radius: 15px; }
        .summary h2, .discounts-section h2 { margin-top: 0; text-align: center; color: #343a40; border-bottom: 1px solid #dee2e6; padding-bottom: 1rem; margin-bottom: 1rem; }
        .summary-item { display: flex; justify-content: space-between; font-size: 1.1rem; padding: 0.5rem 0; }
        .discount-options { display: flex; flex-direction: column; gap: 0.75rem; }
        .discount-option { background: #fff; border: 2px solid #e9ecef; border-radius: 8px; padding: 1rem; cursor: pointer; display: flex; align-items: center; gap: 1rem; }
        .discount-option.selected { border-color: #667eea; background-color: #e6eafb; }
        .discount-option input { width: 1.2em; height: 1.2em; accent-color: #667eea; }
        .discount-option label { font-weight: 600; flex-grow: 1; }
        .discount-option .percentage { font-weight: 700; color: #e67700; }
        .total-price { text-align: center; font-size: 1.75rem; font-weight: 700; margin: 2rem 0; padding: 1rem; background: #f1f3f4; border-radius: 12px; }
        .total-price span { color: #28a745; }
        .submit-btn { border: none; padding: 1rem; border-radius: 8px; font-size: 1.2rem; font-weight: 600; cursor: pointer; width: 100%; background: #28a745; color: white; }
        .back-link { display: inline-block; margin-top: 1.5rem; text-decoration: none; background: #6c757d; color: white; padding: 0.7rem 1.5rem; border-radius: 8px; }
        
        .modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); align-items: center; justify-content: center; padding: 1rem; }
        .modal-content { background-color: #fefefe; padding: 2rem; border-radius: 15px; width: 100%; max-width: 500px; text-align: center; max-height: 90vh; overflow-y: auto; }
        .receipt-section { text-align: left; }
        .receipt-item { display: flex; justify-content: space-between; padding: 0.3rem 0; border-bottom: 1px dashed #ced4da; }
        #modal-action-btn { display: inline-block; margin-top: 1.5rem; text-decoration: none; background: #007bff; color: white; padding: 0.7rem 1.5rem; border-radius: 8px; }
    </style>
</head>
<body>

<!-- RESTRUCTURED: Use a single if/else block to show either the form or the receipt modal -->
<?php if ($message_type === 'success'): ?>
    <div id="receiptModal" class="modal" style="display:flex;">
        <div class="modal-content">
            <h2>Booking Confirmed!</h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <div class="receipt-section">
                <h3>Booking Receipt</h3>
                <div class="receipt-item"><span>Booking ID:</span><span>#<?php echo $receipt_details['booking_id']; ?></span></div>
                <div class="receipt-item"><span>Customer Name:</span><span><?php echo htmlspecialchars($receipt_details['customer_name']); ?></span></div>
                <div class="receipt-item"><span>Flight Date:</span><span><?php echo htmlspecialchars($receipt_details['flight_date']); ?></span></div>
                <div class="receipt-item"><span>Flight Route:</span><span><?php echo htmlspecialchars($receipt_details['flight_path']); ?></span></div>
                <div class="receipt-item"><span>Departure:</span><span><?php echo htmlspecialchars($receipt_details['departure_time']); ?></span></div>
                <div class="receipt-item"><span>Arrival:</span><span><?php echo htmlspecialchars($receipt_details['arrival_time']); ?></span></div>
                <div class="receipt-item"><span>Passengers:</span><span><?php echo htmlspecialchars($receipt_details['group_size']); ?></span></div>
                <div class="receipt-item"><span>Seat Class:</span><span><?php echo htmlspecialchars($receipt_details['seat_class']); ?></span></div>
                <div class="receipt-item"><span>Seat Numbers:</span><span><?php echo htmlspecialchars($receipt_details['seat_numbers']); ?></span></div>
                <div class="receipt-item"><span>Price per Ticket:</span><span>BDT <?php echo $receipt_details['price_per_ticket']; ?></span></div>
                <?php if ($receipt_details['discount_applied'] > 0): ?>
                    <div class="receipt-item"><span>Discount (<?php echo htmlspecialchars($receipt_details['discount_name']); ?>):</span><span>- BDT <?php echo $receipt_details['discount_applied']; ?></span></div>
                <?php endif; ?>
                <div class="receipt-item"><strong>Total Cost:</strong><strong>BDT <?php echo $receipt_details['total_cost']; ?></strong></div>
            </div>
            <a href="homepage.php" id="modal-action-btn">Go to Homepage</a>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div class="header"><h1>Confirm Your Booking</h1></div>
        <?php if ($message && $message_type === 'error'): ?>
            <div class="alert error" style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <div class="summary">
            <h2>Flight Summary</h2>
            <div class="summary-item"><span>Route:</span> <strong><?php echo htmlspecialchars($booking_details['flight_path']); ?></strong></div>
            <div class="summary-item"><span>Date:</span> <strong><?php echo htmlspecialchars(date('M j, Y', strtotime($booking_details['flight_date']))); ?></strong></div>
            <div class="summary-item"><span>Passengers:</span> <strong><?php echo htmlspecialchars($booking_details['group_size']); ?></strong></div>
            <div class="summary-item"><span>Class:</span> <strong><?php echo htmlspecialchars($booking_details['seat_type']); ?></strong></div>
            <div class="summary-item"><span>Price per Ticket:</span> <strong>BDT <?php echo number_format($booking_details['price_per_ticket'], 2); ?></strong></div>
        </div>
        <form method="POST">
            <div class="discounts-section">
                <h2>Select a Discount</h2>
                <div class="discount-options" id="discount-options">
                    <div class="discount-option selected"><input type="radio" id="disc-0" name="discount_id" value="0" data-percentage="0" checked><label for="disc-0">No Discount</label></div>
                    <?php foreach ($discounts as $discount): ?>
                    <div class="discount-option"><input type="radio" id="disc-<?php echo $discount['DiscountID']; ?>" name="discount_id" value="<?php echo $discount['DiscountID']; ?>" data-percentage="<?php echo $discount['Percentage']; ?>"><label for="disc-<?php echo $discount['DiscountID']; ?>"><?php echo htmlspecialchars($discount['DiscountName']); ?></label><span class="percentage"><?php echo number_format($discount['Percentage'], 0); ?>% OFF</span></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="total-price">Total Cost: <span id="final-price">BDT 0.00</span></div>
            <button type="submit" name="confirm_booking_with_discount" class="submit-btn">Confirm & Pay</button>
        </form>
        <a href="book_flight.php" class="back-link">Go Back</a>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const discountOptions = document.getElementById('discount-options');
    if (discountOptions) {
        const pricePerTicket = <?php echo $booking_details['price_per_ticket']; ?>;
        const groupSize = <?php echo $booking_details['group_size']; ?>;
        const finalPriceEl = document.getElementById('final-price');

        function calculateTotal() {
            const selectedDiscountRadio = document.querySelector('input[name="discount_id"]:checked');
            const discountPercentage = parseFloat(selectedDiscountRadio.dataset.percentage) || 0;
            const finalPricePerTicket = pricePerTicket - (pricePerTicket * (discountPercentage / 100));
            const totalCost = finalPricePerTicket * groupSize;
            finalPriceEl.textContent = 'BDT ' + totalCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.querySelectorAll('.discount-option').forEach(opt => opt.classList.remove('selected'));
            selectedDiscountRadio.closest('.discount-option').classList.add('selected');
        }
        discountOptions.addEventListener('change', calculateTotal);
        calculateTotal();
    }
});
</script>
</body>
</html>

