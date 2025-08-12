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
$bookings = array();

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Handle booking cancellation
    if (isset($_POST['cancel_booking']) && isset($_POST['booking_id'])) {
        $stmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'cancelled', 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ? 
            AND customer_id = ? 
            AND status = 'pending'
        ");
        $stmt->bind_param("ii", $_POST['booking_id'], $_SESSION['customer_id']);
        
        if ($stmt->execute()) {
            $success = "Booking cancelled successfully!";
        } else {
            $error = "Failed to cancel booking. Please try again.";
        }
        $stmt->close();
    }

    // Get all bookings for the customer
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
    ");

    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($booking = $result->fetch_assoc()) {
        $bookings[] = $booking;
    }
    
    $stmt->close();

} catch (Exception $e) {
    error_log("Bookings error: " . $e->getMessage());
    $error = "An error occurred while fetching your bookings.";
}

if (isset($db)) {
    $db->closeConnection();
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'in_progress':
            return 'bg-primary';
        case 'completed':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <style>
        .bookings-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .booking-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.2s;
        }
        .booking-card:hover {
            transform: translateY(-2px);
        }
        .vehicle-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('customer-navbar.php'); ?>

    <div class="container">
        <div class="bookings-container">
            <h1 class="text-center mb-4">My Bookings</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x display-1 text-muted"></i>
                    <h3 class="mt-3">No Bookings Found</h3>
                    <p class="text-muted">You haven't made any bookings yet.</p>
                    <a href="services.php" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-circle"></i> Book a Service
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="card booking-card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                                    <p class="text-muted mb-2"><?php echo htmlspecialchars($booking['service_description']); ?></p>
                                    
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <span class="badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="bi bi-calendar"></i>
                                            <?php 
                                                $date = new DateTime($booking['booking_date'] . ' ' . $booking['booking_time']);
                                                echo $date->format('M j, Y g:i A'); 
                                            ?>
                                        </span>
                                        <span class="text-muted">
                                            <i class="bi bi-clock"></i>
                                            <?php echo $booking['service_duration']; ?> minutes
                                        </span>
                                    </div>

                                    <div class="vehicle-info">
                                        <h6 class="mb-2">Vehicle Details</h6>
                                        <p class="mb-0">
                                            <?php echo ucfirst($booking['vehicle_type']); ?><br>
                                            Vehicle Number: <?php echo strtoupper($booking['vehicle_number']); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <h5 class="mb-3">LKR <?php echo number_format($booking['amount'], 2); ?></h5>
                                    
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <button type="submit" name="cancel_booking" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                <i class="bi bi-x-circle"></i> Cancel Booking
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['status'] === 'completed'): ?>
                                        <a href="reviews.php?booking_id=<?php echo $booking['id']; ?>" class="btn btn-outline-primary">
                                            <i class="bi bi-star"></i> Write Review
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
</body>
</html> 