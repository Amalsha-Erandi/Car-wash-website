<?php
session_start();
require_once '../Sample/Database_Connection.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff-login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND staff_id = ?");
    $stmt->bind_param("sii", $new_status, $booking_id, $_SESSION['staff_id']);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
    } else {
        $error_message = "Error updating booking status.";
    }
}

// Get filter values
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query with filters
$query = "SELECT b.*, c.name as customer_name, c.phone as customer_phone,
          s.name as service_name, s.duration as service_duration
          FROM bookings b
          JOIN customers c ON b.customer_id = c.id
          JOIN services s ON b.service_id = s.id
          WHERE b.staff_id = ?";

$params = [$_SESSION['staff_id']];
$types = "i";

if ($status_filter) {
    $query .= " AND b.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $query .= " AND b.booking_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

$query .= " ORDER BY b.booking_date DESC, b.booking_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$bookings = $result->fetch_all(MYSQLI_ASSOC);

$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .booking-card {
            transition: transform 0.3s;
            border-left: 5px solid #ddd;
        }
        .booking-card:hover {
            transform: translateY(-5px);
        }
        .booking-card.pending {
            border-left-color: #ffc107;
        }
        .booking-card.in_progress {
            border-left-color: #0dcaf0;
        }
        .booking-card.completed {
            border-left-color: #198754;
        }
        .booking-card.cancelled {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('staff-navbar.php'); ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Bookings</h1>
            
            <!-- Filters -->
            <div class="d-flex gap-3">
                <form class="d-flex gap-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    
                    <input type="date" name="date" class="form-control" value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                    
                    <?php if ($status_filter || $date_filter): ?>
                        <a href="?" class="btn btn-outline-secondary">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if (empty($bookings)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        No bookings found matching your criteria.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="col-md-6">
                        <div class="card booking-card <?php echo $booking['status']; ?>">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="card-title mb-1">
                                            <?php echo htmlspecialchars($booking['service_name']); ?>
                                        </h5>
                                        <p class="text-muted mb-0">
                                            <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?> at 
                                            <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo $booking['status'] == 'pending' ? 'warning' : 
                                            ($booking['status'] == 'in_progress' ? 'info' : 
                                            ($booking['status'] == 'completed' ? 'success' : 'danger')); 
                                    ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Customer:</strong>
                                    <div class="text-muted">
                                        <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Vehicle:</strong>
                                    <div class="text-muted">
                                        <?php echo ucfirst($booking['vehicle_type']); ?> - 
                                        <?php echo strtoupper($booking['vehicle_number']); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>Duration:</strong>
                                    <div class="text-muted">
                                        <?php echo $booking['service_duration']; ?> minutes
                                    </div>
                                </div>
                                
                                <?php if ($booking['status'] != 'completed' && $booking['status'] != 'cancelled'): ?>
                                    <button class="btn btn-primary btn-sm" 
                                            onclick="updateStatus(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                        <i class="bi bi-arrow-clockwise"></i> Update Status
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Booking Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" id="booking_id" name="booking_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(booking) {
            document.getElementById('booking_id').value = booking.id;
            document.getElementById('status').value = booking.status;
            
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }
    </script>
</body>
</html> 