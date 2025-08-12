<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'Database_Connection.php';

session_start();

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$customer = null;
$bookings = array();
$vehicles = array();

// Define vehicle types array
$vehicle_types = array('car', 'suv', 'van', 'bike');

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Get customer details
    $stmt = $conn->prepare("
        SELECT * FROM customers 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $_SESSION['customer_id']);
$stmt->execute();
$result = $stmt->get_result();
    $customer = $result->fetch_assoc();
    $stmt->close();

    if (!$customer) {
        throw new Exception("Customer not found");
    }

    // Get customer's vehicles
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

    // Get recent bookings
    $stmt = $conn->prepare("
        SELECT 
            b.*,
            s.name as service_name,
            s.description as service_description,
            s.duration as service_duration
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.id
        WHERE b.customer_id = ?
        ORDER BY b.booking_date DESC, b.booking_time DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($booking = $result->fetch_assoc()) {
        $bookings[] = $booking;
    }
$stmt->close();

    // Handle form submission for adding/updating vehicle
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_vehicle':
                    if (!isset($_POST['vehicle_type']) || !isset($_POST['make']) || 
                        !isset($_POST['model']) || !isset($_POST['year']) || !isset($_POST['license_plate'])) {
                        throw new Exception("All vehicle fields are required");
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO customer_vehicles (
                            customer_id, 
                            vehicle_type,
                            make,
                            model,
                            year,
                            color,
                            license_plate,
                            status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                    ");

                    $stmt->bind_param(
                        "issssss",
                        $_SESSION['customer_id'],
                        $_POST['vehicle_type'],
                        $_POST['make'],
                        $_POST['model'],
                        $_POST['year'],
                        $_POST['color'],
                        $_POST['license_plate']
                    );

                    if (!$stmt->execute()) {
                        throw new Exception("Failed to add vehicle: " . $stmt->error);
                    }

                    $success = "Vehicle added successfully!";
                    header("refresh:2;url=customer_profile.php");
                    break;

                case 'remove_vehicle':
                    if (!isset($_POST['vehicle_id'])) {
                        throw new Exception("Vehicle ID is required");
                    }

                    // Verify vehicle belongs to user
                    $stmt = $conn->prepare("
                        SELECT id FROM customer_vehicles 
                        WHERE id = ? AND customer_id = ?
                    ");
                    $stmt->bind_param("ii", $_POST['vehicle_id'], $_SESSION['customer_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if (!$result->fetch_assoc()) {
                        throw new Exception("Invalid vehicle selected");
                    }

                    // Soft delete the vehicle
                    $stmt = $conn->prepare("
                        UPDATE customer_vehicles 
                        SET status = 'inactive' 
                        WHERE id = ? AND customer_id = ?
                    ");
                    $stmt->bind_param("ii", $_POST['vehicle_id'], $_SESSION['customer_id']);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to remove vehicle: " . $stmt->error);
                    }

                    $success = "Vehicle removed successfully!";
                    header("refresh:2;url=customer_profile.php");
                    break;
            }
        }
    }

} catch (Exception $e) {
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
    <title>My Profile - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        .vehicle-card {
            transition: all 0.3s ease;
        }
        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .booking-card {
            transition: all 0.3s ease;
        }
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('customer-navbar.php'); ?>

    <div class="container profile-container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-circle"></i> Profile Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="profileForm">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($customer['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" 
                                       value="<?php echo htmlspecialchars($customer['email']); ?>" readonly>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password">
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-check-circle"></i> Update Profile
                                    </button>
                        </form>
                                </div>
                                </div>
                            </div>
                            
            <!-- Vehicles -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="bi bi-car-front-fill"></i> My Vehicles</h4>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                            <i class="bi bi-plus-circle"></i> Add Vehicle
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($vehicles)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-car-front display-1 text-muted"></i>
                                <h3 class="mt-3">No Vehicles Added</h3>
                                <p class="text-muted">Add your first vehicle to start booking services</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                                    <i class="bi bi-plus-circle"></i> Add Vehicle
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <?php if ($vehicle['status'] === 'active'): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card vehicle-card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <h5 class="card-title"><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></h5>
                                                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this vehicle?');">
                                                            <input type="hidden" name="action" value="remove_vehicle">
                                                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                        </form>
                                                    </div>
                                                    <p class="card-text">
                                                        <small class="text-muted">
                                                            Type: <?php echo $vehicle['vehicle_type']; ?><br>
                                                            Year: <?php echo $vehicle['year']; ?><br>
                                                            Color: <?php echo $vehicle['color']; ?><br>
                                                            License: <?php echo $vehicle['license_plate']; ?>
                                                        </small>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-clock-history"></i> Recent Bookings</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-calendar-x display-1 text-muted"></i>
                                <h3 class="mt-3">No Bookings Yet</h3>
                                <p class="text-muted">Book your first car wash service now</p>
                                <a href="services.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle"></i> Book Service
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($bookings as $booking): ?>
                                <div class="card booking-card mb-3">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h5 class="card-title mb-1">
                                                    <?php echo $booking['service_name']; ?>
                                                </h5>
                                                <p class="mb-1"><small class="text-muted"><?php echo $booking['service_description']; ?></small></p>
                                                <p class="mb-0">
                                                    <small>
                                                        <i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> at <?php echo date('h:i A', strtotime($booking['booking_time'])); ?><br>
                                                        <i class="bi bi-clock"></i> <?php echo $booking['service_duration']; ?> minutes
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="col-md-3 text-md-center">
                                                <h5 class="mb-1">LKR <?php echo number_format($booking['amount'], 2); ?></h5>
                                                <span class="badge bg-<?php 
                                                    echo match($booking['payment_status']) {
                                                        'paid' => 'success',
                                                        'pending' => 'warning',
                                                        'failed' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?> status-badge">
                                                    <?php echo ucfirst($booking['payment_status']); ?>
                                                </span>
                                        </div>
                                            <div class="col-md-3 text-md-end">
                                            <span class="badge bg-<?php 
                                                    echo match($booking['booking_status']) {
                                                        'completed' => 'success',
                                                        'in_progress' => 'primary',
                                                        'scheduled' => 'info',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?> status-badge mb-2">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                                <br>
                                                <a href="order-tracking.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i> View Details
                                                </a>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            
                            <div class="text-center mt-3">
                                <a href="bookings.php" class="btn btn-outline-primary">
                                    <i class="bi bi-clock-history"></i> View All Bookings
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-car-front"></i> Add New Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="" id="addVehicleForm">
                        <input type="hidden" name="action" value="add_vehicle">
                        
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type</label>
                            <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                <option value="">Select vehicle type</option>
                                <?php foreach ($vehicle_types as $type): ?>
                                    <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="make" class="form-label">Make</label>
                                <input type="text" class="form-control" id="make" name="make" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="model" class="form-label">Model</label>
                                <input type="text" class="form-control" id="model" name="model" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="year" class="form-label">Year</label>
                                <input type="number" class="form-control" id="year" name="year" 
                                       min="1900" max="<?php echo date('Y') + 1; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="color" name="color" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="license_plate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="license_plate" name="license_plate" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-plus-circle"></i> Add Vehicle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer style="background-color: #1b1f23; color: #ccc; padding: 40px 10%; display: flex; flex-wrap: wrap; justify-content: space-between; gap: 30px; font-size: 15px;">
  
        <div style="flex: 1; min-width: 200px;">
            <h2 style="color: #2FA5EB; font-family: 'Forte', cursive; font-size: 26px;">Car Medic</h2>
            <p style="margin: 15px 0;">We care for your car like doctors care for patients. Trusted by thousands, driven by excellence.</p>
            <p>&copy; 2025 Car Medic Automotive Repair</p>
        </div>
    
        <div style="flex: 1; min-width: 200px;">
            <h3 style="color: #2FA5EB; margin-bottom: 15px;">Quick Links</h3>
            <ul style="list-style: none; padding: 0;">
                <li><a href="home.php" style="color: #ccc; text-decoration: none;">Home</a></li>
                <li><a href="services.php" style="color: #ccc; text-decoration: none;">Services</a></li>
                <li><a href="book_service.php" style="color: #ccc; text-decoration: none;">Appointment</a></li>
                <li><a href="register.php" style="color: #ccc; text-decoration: none;">User Sign Up</a></li>
                <li><a href="../Sample/admin-login.php" style="color: #ccc; text-decoration: none;">Admin Panel</a></li>
            </ul>
        </div>
    
        <div style="flex: 1; min-width: 200px;">
            <h3 style="color: #2FA5EB; margin-bottom: 15px;">Contact Us</h3>
            <p><i class="fas fa-map-marker-alt"></i> 250 Galle Road, Colombo 3</p>
            <p><i class="fas fa-phone"></i> ‪+94 0112525456‬</p>
            <p><i class="fas fa-envelope"></i> CarMedicAutomotiveRepair@gmail.com</p>

            <div style="margin-top: 15px;">
                <a href="#" style="color: #2FA5EB; margin-right: 10px;"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" style="color: #2FA5EB; margin-right: 10px;"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="#" style="color: #2FA5EB; margin-right: 10px;"><i class="fab fa-twitter fa-lg"></i></a>
                <a href="#" style="color: #2FA5EB;"><i class="fab fa-youtube fa-lg"></i></a>
            </div>
        </div>
    
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(event) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            
            if (currentPassword && !newPassword) {
                event.preventDefault();
                alert('Please enter a new password');
                return;
            }
            
            if (!currentPassword && newPassword) {
                event.preventDefault();
                alert('Please enter your current password');
                return;
            }
            
            if (newPassword && newPassword.length < 8) {
                event.preventDefault();
                alert('New password must be at least 8 characters long');
                return;
            }
        });
        
        // Clear form on modal close
        document.getElementById('addVehicleModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('addVehicleForm').reset();
        });
        
        // Set max year to current year + 1
        document.getElementById('year').max = new Date().getFullYear() + 1;
    </script>
</body>
</html>