<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Flight";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distinct routes for the dropdown
$route_options = [];
$route_query = "SELECT DISTINCT Path FROM Flight WHERE Path IS NOT NULL AND Path != '' ORDER BY Path ASC";
$route_result = $conn->query($route_query);
if ($route_result->num_rows > 0) {
    while ($row = $route_result->fetch_assoc()) {
        $route_options[] = $row['Path'];
    }
}

// Only populate models if a route was already submitted (for preserving state after search)
$model_options = [];
if (isset($_POST['route']) && !empty($_POST['route'])) {
    $selected_route = $_POST['route'];
    $model_query = "SELECT DISTINCT p.Model FROM Plane p JOIN Flight f ON p.PlaneID = f.PlaneID WHERE f.Path = ? ORDER BY p.Model ASC";
    $stmt = $conn->prepare($model_query);
    $stmt->bind_param("s", $selected_route);
    $stmt->execute();
    $model_result = $stmt->get_result();
    if ($model_result->num_rows > 0) {
        while($row = $model_result->fetch_assoc()) {
            $model_options[] = $row['Model'];
        }
    }
}

$flight_data = null;
$location_data = null;
$current_location = null;
$error_message = null;

// Process form submission
if ($_POST && isset($_POST['route']) && isset($_POST['flight_model']) && isset($_POST['query_time'])) {
    $route = $_POST['route'];
    $flight_model = $_POST['flight_model'];
    $query_time = $_POST['query_time'];
    
    $flight_query = "
        SELECT f.*, p.Model, a.Name as AirlineName, r.Source, r.Destination
        FROM Flight f
        JOIN Plane p ON f.PlaneID = p.PlaneID
        JOIN Airline a ON p.AirlineID = a.AirlineID
        LEFT JOIN Route r ON f.FlightID = r.FlightID
        WHERE f.Path = ? AND p.Model = ?
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($flight_query);
    $stmt->bind_param("ss", $route, $flight_model);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $flight_data = $result->fetch_assoc();
        $flight_id = $flight_data['FlightID'];
        
        $location_query = "SELECT * FROM Location_Map WHERE FlightID = ? ORDER BY Times ASC";
        $stmt2 = $conn->prepare($location_query);
        $stmt2->bind_param("i", $flight_id);
        $stmt2->execute();
        $location_result = $stmt2->get_result();
        
        $location_data = [];
        while ($row = $location_result->fetch_assoc()) {
            $location_data[] = $row;
        }
        
        $current_location = findCurrentLocation($location_data, $query_time);
        
        if (!$current_location && !empty($location_data)) {
            $first_time = $location_data[0]['Times'];
            $last_time = end($location_data)['Times'];
            
            if ($query_time < $first_time) {
                $current_location = ['location' => 'Preparing for departure from ' . $flight_data['Source'], 'status' => 'Pre-flight', 'time' => $query_time];
            } else if ($query_time > $last_time) {
                $current_location = ['location' => 'Arrived at ' . $flight_data['Destination'], 'status' => 'Completed', 'time' => $query_time];
            }
        }
        
    } else {
        $error_message = "Flight not found. Please check the route and aircraft model.";
    }
}

function findCurrentLocation($locations, $query_time) {
    if (empty($locations)) return null;
    $query_timestamp = strtotime($query_time);
    for ($i = 0; $i < count($locations); $i++) {
        $current_time = strtotime($locations[$i]['Times']);
        if ($query_timestamp <= $current_time) {
            return ['location' => $locations[$i]['Location'], 'status' => 'En route to', 'time' => $locations[$i]['Times'], 'index' => $i];
        }
        if ($i < count($locations) - 1) {
            $next_time = strtotime($locations[$i + 1]['Times']);
            if ($query_timestamp > $current_time && $query_timestamp < $next_time) {
                return ['location' => 'En route from ' . $locations[$i]['Location'] . ' to ' . $locations[$i + 1]['Location'], 'status' => 'In transit', 'time' => $query_time, 'index' => $i];
            }
        }
    }
     if ($query_timestamp > strtotime(end($locations)['Times'])) {
        return ['location' => end($locations)['Location'], 'status' => 'Reached destination point', 'time' => end($locations)['Times'], 'index' => count($locations) - 1];
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Status - SkyWings Airlines</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-bottom: 1px solid rgba(255, 255, 255, 0.2); position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 2rem; font-weight: bold; color: #fff; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); cursor: pointer; }
        .back-btn { background: rgba(255, 255, 255, 0.2); color: #fff; border: none; padding: 0.5rem 1.5rem; border-radius: 25px; cursor: pointer; transition: all 0.3s ease; text-decoration: none; font-weight: 500; }
        .back-btn:hover { background: rgba(255, 255, 255, 0.3); transform: translateY(-2px); }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .page-title { text-align: center; color: #fff; font-size: 3rem; margin-bottom: 2rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); animation: fadeInUp 0.8s ease-out; }
        .search-section { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 2rem; margin-bottom: 2rem; border: 1px solid rgba(255, 255, 255, 0.2); animation: fadeInUp 0.8s ease-out 0.2s both; }
        .search-form { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 1rem; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { color: #fff; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { padding: 0.8rem; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 10px; background: rgba(255, 255, 255, 0.1); color: #fff; font-size: 1rem; transition: all 0.3s ease; }
        .form-group select { -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2220%22%20height%3D%2220%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M5%208l5%205%205-5z%22%20fill%3D%22%23ffffff%22%2F%3E%3C%2Fsvg%3E'); background-repeat: no-repeat; background-position: right 0.7em top 50%; background-size: 1.2em auto; }
        .form-group select option { background: #3c3c7a; color: #fff; }
        .form-group input::placeholder { color: rgba(255, 255, 255, 0.7); }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #007bff; background: rgba(255, 255, 255, 0.2); box-shadow: 0 0 20px rgba(0, 123, 255, 0.3); }
        .form-group select:disabled { background-color: rgba(255,255,255,0.05); opacity: 0.5; cursor: not-allowed; }
        .search-btn { background: linear-gradient(45deg, #007bff, #0056b3); color: #fff; border: none; padding: 0.8rem 2rem; border-radius: 10px; cursor: pointer; font-size: 1rem; font-weight: 500; transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3); }
        .search-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0, 123, 255, 0.4); background: linear-gradient(45deg, #0056b3, #004494); }
        .results-section { background: rgba(255, 255, 255, 0.95); border-radius: 20px; padding: 2rem; margin-top: 2rem; box-shadow: 0 10px 30px rgba(0,0,0,0.1); animation: fadeInUp 0.5s ease-out; }
        .flight-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
        .info-card { background: linear-gradient(135deg, #f8f9ff, #e3f2fd); padding: 1.5rem; border-radius: 15px; text-align: center; border: 2px solid #e3f2fd; transition: all 0.3s ease; }
        .info-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0, 123, 255, 0.1); }
        .info-card h3 { color: #007bff; margin-bottom: 0.5rem; font-size: 1.1rem; }
        .info-card p { font-size: 1.3rem; font-weight: 600; color: #333; }
        .current-status { background: linear-gradient(135deg, #28a745, #20c997); color: #fff; padding: 2rem; border-radius: 15px; text-align: center; margin: 2rem 0; box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3); }
        .current-status h2 { font-size: 2rem; margin-bottom: 1rem; }
        .current-status p { font-size: 1.3rem; opacity: 0.9; }
        .location-timeline { background: #f8f9ff; border-radius: 15px; padding: 2rem; margin-top: 2rem; }
        .timeline-title { text-align: center; color: #007bff; font-size: 1.8rem; margin-bottom: 2rem; }
        .timeline-item { display: flex; align-items: center; margin-bottom: 1.5rem; padding: 1rem; background: #fff; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s ease; position: relative; }
        .timeline-item:hover { transform: translateX(10px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .timeline-item.current { background: linear-gradient(135deg, #e8f5e8, #d4edda); border-left: 4px solid #28a745; }
        .timeline-dot { width: 16px; height: 16px; border-radius: 50%; background: #007bff; margin-right: 1rem; flex-shrink: 0; }
        .timeline-dot.current { background: #28a745; box-shadow: 0 0 20px rgba(40, 167, 69, 0.5); animation: pulse 2s infinite; }
        .timeline-content { flex: 1; }
        .timeline-time { font-weight: 600; color: #007bff; font-size: 1.1rem; }
        .timeline-location { color: #666; margin-top: 0.25rem; }
        .error-message { background: linear-gradient(135deg, #dc3545, #c82333); color: #fff; padding: 1rem 2rem; border-radius: 10px; text-align: center; margin: 2rem 0; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.1); opacity: 0.7; } 100% { transform: scale(1); opacity: 1; } }
        @media (max-width: 992px) { .search-form { grid-template-columns: 1fr 1fr; } .search-form .form-group:last-of-type { grid-column: 1 / 2; } .search-form button { grid-column: 2 / 3; align-self: end; } }
        @media (max-width: 768px) { .search-form { grid-template-columns: 1fr; gap: 1rem; } .search-form .form-group:last-of-type, .search-form button { grid-column: auto; } .page-title { font-size: 2rem; } .container { padding: 1rem; } .navbar { padding: 1rem 3%; } }
    </style>
</head>
<body>
    <nav class="navbar"><div class="logo">✈️ SkyWings</div><a href="homepage.php" class="back-btn">← Back to Home</a></nav>

    <div class="container">
        <h1 class="page-title">Flight Status Tracker</h1>
        <div class="search-section">
            <form method="POST" class="search-form">
                <div class="form-group">
                    <label for="route">Route</label>
                    <select id="route" name="route" required>
                        <option value="" disabled <?php echo !isset($_POST['route']) ? 'selected' : ''; ?>>Select a Route</option>
                        <?php foreach ($route_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php if (isset($_POST['route']) && $_POST['route'] == $option) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="flight_model">Aircraft Model</label>
                    <select id="flight_model" name="flight_model" required <?php if (empty($model_options)) echo 'disabled'; ?>>
                        <option value="" disabled <?php if (!isset($_POST['flight_model'])) echo 'selected'; ?>>
                            <?php echo empty($model_options) ? 'Select a route first' : 'Select an Aircraft'; ?>
                        </option>
                        <?php foreach ($model_options as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>" <?php if (isset($_POST['flight_model']) && $_POST['flight_model'] == $option) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="query_time">Query Time</label>
                    <input type="time" id="query_time" name="query_time" value="<?php echo isset($_POST['query_time']) ? htmlspecialchars($_POST['query_time']) : date('H:i'); ?>" required>
                </div>
                
                <button type="submit" class="search-btn">Track Flight</button>
            </form>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($flight_data): ?>
            <div class="results-section">
                <div class="flight-info">
                    <div class="info-card"><h3>Flight</h3><p>SW-<?php echo $flight_data['FlightID']; ?></p></div>
                    <div class="info-card"><h3>Airline</h3><p><?php echo htmlspecialchars($flight_data['AirlineName']); ?></p></div>
                    <div class="info-card"><h3>Aircraft</h3><p><?php echo htmlspecialchars($flight_data['Model']); ?></p></div>
                    <div class="info-card"><h3>Route</h3><p><?php echo htmlspecialchars($flight_data['Path']); ?></p></div>
                    <div class="info-card"><h3>Departure</h3><p><?php echo date('H:i', strtotime($flight_data['DepartureTime'])); ?></p></div>
                    <div class="info-card"><h3>Arrival</h3><p><?php echo date('H:i', strtotime($flight_data['ArrivalTime'])); ?></p></div>
                </div>

                <?php if ($current_location): ?>
                    <div class="current-status">
                        <h2>Current Status at <?php echo htmlspecialchars($_POST['query_time']); ?></h2>
                        <p><?php echo htmlspecialchars($current_location['status'] . ' ' . $current_location['location']); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($location_data)): ?>
                    <div class="location-timeline">
                        <h3 class="timeline-title">Flight Timeline</h3>
                        <?php foreach ($location_data as $index => $location): ?>
                            <?php 
                            $is_current = false;
                            if ($current_location && isset($current_location['index'])) {
                                $is_current = ($index == $current_location['index']);
                            }
                            ?>
                            <div class="timeline-item <?php echo $is_current ? 'current' : ''; ?>">
                                <div class="timeline-dot <?php echo $is_current ? 'current' : ''; ?>"></div>
                                <div class="timeline-content">
                                    <div class="timeline-time"><?php echo date('H:i', strtotime($location['Times'])); ?></div>
                                    <div class="timeline-location">
                                        <?php echo htmlspecialchars($location['Location']); ?>
                                        <?php if ($is_current && $current_location['status'] != 'In transit'): ?>
                                            <strong style="color: #28a745;"> ← Current Position</strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const routeSelect = document.getElementById('route');
            const modelSelect = document.getElementById('flight_model');

            routeSelect.addEventListener('change', function() {
                const selectedRoute = this.value;
                
                modelSelect.innerHTML = '<option value="">Loading...</option>';
                modelSelect.disabled = true;

                if (selectedRoute) {
                    fetch(`get_models.php?route=${encodeURIComponent(selectedRoute)}`)
                        .then(response => response.json())
                        .then(models => {
                            modelSelect.innerHTML = '<option value="" disabled selected>Select an Aircraft</option>';
                            if (models.length > 0) {
                                models.forEach(model => {
                                    const option = document.createElement('option');
                                    option.value = model;
                                    option.textContent = model;
                                    modelSelect.appendChild(option);
                                });
                                modelSelect.disabled = false;
                            } else {
                                modelSelect.innerHTML = '<option value="">No aircraft found</option>';
                            }
                        })
                        .catch(error => {
                            console.error('Error fetching models:', error);
                            modelSelect.innerHTML = '<option value="">Error loading</option>';
                        });
                }
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>