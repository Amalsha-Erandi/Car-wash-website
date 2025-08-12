<?php
session_start();
include('Database_Connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle staff assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_staff'])) {
    $booking_id = $_POST['booking_id'];
    $staff_id = $_POST['staff_id'];
    
    // First check if staff is available (not assigned to another booking at same time)
    $check_query = "SELECT b2.id 
                    FROM bookings b1 
                    JOIN services s1 ON b1.service_id = s1.id
                    JOIN bookings b2 ON b2.staff_id = ? 
                    JOIN services s2 ON b2.service_id = s2.id
                    WHERE b1.id = ? 
                    AND b2.booking_date = b1.booking_date 
                    AND b2.status NOT IN ('completed', 'cancelled')
                    AND (
                        (b2.booking_time BETWEEN b1.booking_time 
                            AND ADDTIME(b1.booking_time, SEC_TO_TIME(s1.duration * 60)))
                        OR 
                        (ADDTIME(b2.booking_time, SEC_TO_TIME(s2.duration * 60)) 
                            BETWEEN b1.booking_time 
                            AND ADDTIME(b1.booking_time, SEC_TO_TIME(s1.duration * 60)))
                    )";
    
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $staff_id, $booking_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "Staff member is already assigned to another booking at this time.";
    } else {
        // Update booking with assigned staff
        $stmt = $conn->prepare("UPDATE bookings SET staff_id = ?, status = 'confirmed' WHERE id = ?");
        $stmt->bind_param("ii", $staff_id, $booking_id);
        
        if ($stmt->execute()) {
            $success_message = "Staff assigned successfully!";
        } else {
            $error_message = "Error assigning staff: " . $conn->error;
        }
    }
}

// Handle booking status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Booking status updated successfully!";
    } else {
        $error_message = "Error updating booking status: " . $conn->error;
    }
}

// Get all bookings with customer and service details
$bookings_query = "SELECT b.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   s.name as service_name, s.price as service_price, s.duration as service_duration,
                   st.name as staff_name
                   FROM bookings b
                   JOIN customers c ON b.customer_id = c.id
                   JOIN services s ON b.service_id = s.id
                   LEFT JOIN staff st ON b.staff_id = st.id
                   ORDER BY b.booking_date DESC, b.booking_time DESC";
$bookings_result = mysqli_query($conn, $bookings_query);

// Get all available staff for assignment
$staff_query = "SELECT id, name FROM staff WHERE status = 'active' AND role = 'washer' ORDER BY name";
$staff_result = mysqli_query($conn, $staff_query);
$staff_list = [];
while ($staff = mysqli_fetch_assoc($staff_result)) {
    $staff_list[] = $staff;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Bookings - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="admin-styles.css" rel="stylesheet">
  <style>
    .duration {
      font-size: 0.875rem;
      color: #0d6efd;
    }
  </style>
</head>
<body class="bg-light">
  <?php include('admin-navbar.php'); ?>

  <main class="py-5">
    <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>View Bookings</h1>
        </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
            <?php endif; ?>

            <div class="row g-4">
              <?php
                if ($bookings_result && mysqli_num_rows($bookings_result) > 0) {
                    while ($booking = mysqli_fetch_assoc($bookings_result)) {
                        $statusClass = 'secondary';
                        switch ($booking['status']) {
                            case 'confirmed':
                                $statusClass = 'success';
                                break;
                            case 'pending':
                                $statusClass = 'warning';
                                break;
                            case 'cancelled':
                                $statusClass = 'danger';
                                break;
                            case 'completed':
                                $statusClass = 'info';
                                break;
                        }
                        ?>
                        <div class="col-md-6">
                            <div class="card booking-card h-100">
                                <div class="card-body">
                                    <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                    
                                    <h5 class="card-title mb-3">Booking #<?php echo $booking['id']; ?></h5>
                                    
                                    <div class="mb-3">
                                        <h6 class="text-primary"><?php echo htmlspecialchars($booking['service_name']); ?></h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="price">Rs.<?php echo number_format($booking['service_price'], 2); ?></div>
                                            <div class="duration">
                                                <i class="bi bi-clock"></i> <?php echo $booking['service_duration']; ?> mins
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Customer Details:</strong>
                                        <div class="text-muted">
                                            <div><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                            <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                            <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <strong>Booking Details:</strong>
                                        <div class="text-muted">
                                            <div>
                                                <i class="bi bi-calendar"></i> 
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                            </div>
                                            <div>
                                                <i class="bi bi-clock"></i> 
                                                <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                            </div>
                                            <?php if (!empty($booking['vehicle_type'])): ?>
                                                <div>
                                                    <i class="bi bi-car-front"></i> 
                                                    <?php echo ucfirst($booking['vehicle_type']); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($booking['vehicle_number'])): ?>
                                                <div>
                                                    <i class="bi bi-hash"></i> 
                                                    <?php echo strtoupper($booking['vehicle_number']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if (!empty($booking['staff_name'])): ?>
                                        <div class="mb-3">
                                            <strong>Assigned Staff:</strong>
                                            <div class="text-muted"><?php echo htmlspecialchars($booking['staff_name']); ?></div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex gap-2">
                                        <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'completed'): ?>
                                            <button class="btn btn-outline-primary flex-grow-1" 
                                                    onclick="updateStatus(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                                <i class="bi bi-arrow-clockwise"></i> Update Status
                                            </button>
                                            <?php if (empty($booking['staff_id'])): ?>
                                                <button class="btn btn-outline-success" 
                                                        onclick="assignStaff(<?php echo $booking['id']; ?>)">
                                                    <i class="bi bi-person-plus"></i> Assign Staff
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12"><div class="alert alert-info">No bookings found.</div></div>';
                }
                ?>
            </div>
        </div>
    </main>

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
                                <option value="confirmed">Confirmed</option>
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

    <!-- Assign Staff Modal -->
    <div class="modal fade" id="assignStaffModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" id="assign_booking_id" name="booking_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="staff_id" class="form-label">Select Staff</label>
                            <select class="form-select" id="staff_id" name="staff_id" required>
                                <option value="">Select a staff member...</option>
                                <?php foreach ($staff_list as $staff): ?>
                                    <option value="<?php echo $staff['id']; ?>">
                                        <?php echo htmlspecialchars($staff['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_staff" class="btn btn-primary">Assign Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include('admin-footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(booking) {
            document.getElementById('booking_id').value = booking.id;
            document.getElementById('status').value = booking.status;
            
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }

        function assignStaff(bookingId) {
            document.getElementById('assign_booking_id').value = bookingId;
            
            new bootstrap.Modal(document.getElementById('assignStaffModal')).show();
        }
  </script>
</body>
</html>
<?php
$db->closeConnection();
?>

