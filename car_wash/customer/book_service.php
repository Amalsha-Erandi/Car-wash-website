<?php
session_start();
require_once 'Database_Connection.php';

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$service = null;
$vehicles = array();

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get service details
    if (isset($_GET['id'])) {
        $stmt = $conn->prepare("SELECT * FROM services WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $service = $result->fetch_assoc();
        $stmt->close();

        if (!$service) {
            throw new Exception("Service not found or inactive");
        }

        // Get user's vehicles
        $stmt = $conn->prepare("
            SELECT * FROM customer_vehicles 
            WHERE customer_id = ? AND status = 'active'
            ORDER BY id DESC
        ");
        $stmt->bind_param("i", $_SESSION['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($vehicle = $result->fetch_assoc()) {
            $vehicles[] = $vehicle;
        }
        $stmt->close();

        if (empty($vehicles)) {
            throw new Exception("Please add a vehicle in your profile first");
        }

        // Check if any vehicle type is supported
        $supported_types = json_decode($service['vehicle_types'], true);
        if (!is_array($supported_types)) {
            $supported_types = array(); // Handle invalid JSON or empty vehicle_types
        }
        
        $has_supported_vehicle = false;
        foreach ($vehicles as $vehicle) {
            if (in_array(strtolower($vehicle['vehicle_type']), array_map('strtolower', $supported_types))) {
                $has_supported_vehicle = true;
                break;
            }
        }

        if (!$has_supported_vehicle) {
            throw new Exception("This service is not available for any of your registered vehicles");
        }
    } else {
        throw new Exception("Invalid service selected");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
        if (!isset($_POST['vehicle_id']) || !isset($_POST['booking_date']) || !isset($_POST['booking_time'])) {
            throw new Exception("Please select vehicle, date and time");
        }

        // Verify vehicle belongs to user
        $vehicle_found = false;
        $selected_vehicle = null;
        foreach ($vehicles as $vehicle) {
            if ($vehicle['id'] == $_POST['vehicle_id']) {
                $vehicle_found = true;
                $selected_vehicle = $vehicle;
                break;
            }
        }

        if (!$vehicle_found) {
            throw new Exception("Invalid vehicle selected");
        }

        // Verify vehicle type is supported
        if (!in_array($selected_vehicle['vehicle_type'], $supported_types)) {
            throw new Exception("Selected vehicle type is not supported for this service");
        }

        $date = $_POST['booking_date'];
        $time = $_POST['booking_time'];
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        // Validate date and time
        $booking_datetime = new DateTime($date . ' ' . $time);
        $now = new DateTime();

        if ($booking_datetime <= $now) {
            throw new Exception("Please select a future date and time");
        }

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Create booking
            $query = "
                INSERT INTO bookings (
                    customer_id,
                    service_id,
                    booking_date,
                    booking_time,
                    vehicle_type,
                    vehicle_number,
                    status,
                    amount,
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)
            ";

            // Debug information
            error_log("Query: " . $query);
            error_log("Parameters: " . json_encode([
                $_SESSION['customer_id'],
                $service['id'],
                $date,
                $time,
                $selected_vehicle['vehicle_type'],
                $selected_vehicle['license_plate'],
                $service['price'],
                $notes
            ]));

            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $vehicle_number = $selected_vehicle['license_plate'];
            
            // Bind parameters
            $result = $stmt->bind_param(
                "iissssds", // Changed from iissssdss to iissssds
                $_SESSION['customer_id'],
                $service['id'],
                $date,
                $time,
                $selected_vehicle['vehicle_type'],
                $vehicle_number,
                $service['price'],
                $notes
            );

            if (!$result) {
                throw new Exception("Bind failed: " . $stmt->error);
            }

            if (!$stmt->execute()) {
                throw new Exception("Failed to create booking: " . $stmt->error);
            }

            // Commit transaction
            $conn->commit();

            $success = "Booking created successfully! You will be redirected to your bookings.";
            header("refresh:3;url=bookings.php");

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

} catch (Exception $e) {
    error_log("Booking error: " . $e->getMessage());
    $error = $e->getMessage();
}

if (isset($db)) {
    $db->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="customer.css">
    <style>
        .booking-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .service-details {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .vehicle-details {
            background-color: #e9ecef;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .vehicle-option {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .vehicle-option:hover {
            border-color: #0d6efd;
            box-shadow: 0 0 10px rgba(13, 110, 253, 0.1);
        }
        .vehicle-option.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .vehicle-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 100%;
            width: 100%;
            left: 0;
            top: 0;
            margin: 0;
            z-index: 1;
        }
        .vehicle-option label {
            cursor: pointer;
            margin: 0;
            width: 100%;
            position: relative;
            z-index: 2;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('customer-navbar.php'); ?>

    <div class="container">
        <div class="booking-container">
            <h1 class="text-center mb-4">Book Service</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                    <?php if (strpos($error, "add a vehicle") !== false): ?>
                        <br><br>
                        <a href="customer_profile.php" class="btn btn-danger">Add Vehicle</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($service && !$error): ?>
                <!-- Service Details -->
                <div class="service-details">
                    <h3><?php echo htmlspecialchars($service['name']); ?></h3>
                    <p class="text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Price:</strong> LKR <?php echo number_format($service['price'], 2); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Duration:</strong> <?php echo $service['duration']; ?> minutes</p>
                        </div>
                    </div>
                </div>

                <!-- Booking Form -->
                <form method="POST" action="" id="bookingForm" novalidate>
                    <!-- Vehicle Selection -->
                    <div class="mb-4">
                        <h5 class="mb-3">Select Your Vehicle</h5>
                        <div class="vehicle-selection">
                            <?php 
                            if (!is_array($supported_types)) {
                                $supported_types = array();
                            }
                            foreach ($vehicles as $vehicle): 
                                $is_supported = in_array(strtolower($vehicle['vehicle_type']), array_map('strtolower', $supported_types));
                            ?>
                                <div class="vehicle-option <?php echo !$is_supported ? 'opacity-50' : ''; ?>">
                                    <input type="radio" name="vehicle_id" id="vehicle_<?php echo $vehicle['id']; ?>" 
                                        value="<?php echo $vehicle['id']; ?>" 
                                        <?php echo !$is_supported ? 'disabled' : ''; ?> 
                                        required
                                        aria-label="Select <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?>">
                                    <label for="vehicle_<?php echo $vehicle['id']; ?>" class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?>
                                                <?php if (!$is_supported): ?>
                                                    <span class="badge bg-warning">Not Supported</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="mb-0 text-muted">
                                                Type: <?php echo ucfirst($vehicle['vehicle_type']); ?><br>
                                                License Plate: <?php echo strtoupper($vehicle['license_plate']); ?><br>
                                                Year: <?php echo $vehicle['year']; ?>
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($has_supported_vehicle): ?>
                            <div class="invalid-feedback">
                                Please select a vehicle
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <i class="bi bi-exclamation-triangle"></i> None of your vehicles are supported for this service.
                                Please add a supported vehicle in your profile.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="booking_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="booking_time" class="form-label">Time</label>
                            <input type="time" class="form-control" id="booking_time" name="booking_time" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Special Instructions (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-calendar-check"></i> Confirm Booking
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('bookingForm');
            
            // Date and time validation
            const dateInput = document.getElementById('booking_date');
            const timeInput = document.getElementById('booking_time');
            
            if (dateInput && timeInput) {
                // Set minimum date to today
                dateInput.min = new Date().toISOString().split('T')[0];
                
                dateInput.addEventListener('change', function() {
                    const today = new Date().toISOString().split('T')[0];
                    
                    if (dateInput.value === today) {
                        const now = new Date();
                        let hours = now.getHours().toString().padStart(2, '0');
                        let minutes = now.getMinutes().toString().padStart(2, '0');
                        timeInput.min = `${hours}:${minutes}`;
                    } else {
                        timeInput.min = '';
                    }
                });

                // Set initial min time if today's date is selected
                if (dateInput.value === new Date().toISOString().split('T')[0]) {
                    const now = new Date();
                    let hours = now.getHours().toString().padStart(2, '0');
                    let minutes = now.getMinutes().toString().padStart(2, '0');
                    timeInput.min = `${hours}:${minutes}`;
                }
            }

            // Vehicle selection handling
            const vehicleOptions = document.querySelectorAll('.vehicle-option');
            const vehicleRadios = document.querySelectorAll('input[name="vehicle_id"]');

            // Add keyboard navigation for vehicle options
            vehicleOptions.forEach(option => {
                const radio = option.querySelector('input[type="radio"]');
                if (radio && !radio.disabled) {
                    // Handle click events
                    option.addEventListener('click', function() {
                        selectVehicle(option, radio);
                    });

                    // Handle keyboard events
                    radio.addEventListener('keydown', function(e) {
                        if (e.key === ' ' || e.key === 'Enter') {
                            e.preventDefault();
                            selectVehicle(option, radio);
                        }
                    });
                }
            });

            function selectVehicle(option, radio) {
                // Remove selected class from all options
                vehicleOptions.forEach(opt => opt.classList.remove('selected'));
                // Add selected class to clicked option
                option.classList.add('selected');
                // Check the radio button
                radio.checked = true;
                // Trigger the input event for form validation
                radio.dispatchEvent(new Event('input'));
            }

            // Form validation
            if (form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });

                // Real-time validation feedback
                vehicleRadios.forEach(radio => {
                    radio.addEventListener('input', function() {
                        const vehicleSelection = this.closest('.vehicle-selection');
                        const feedback = vehicleSelection.nextElementSibling;
                        
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.style.display = this.validity.valid ? 'none' : 'block';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html> 