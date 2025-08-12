<?php
// this is cash-orders.php
session_start();
include('Database_Connection.php'); // Corrected file name and path

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Handle payment status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $booking_id = $_POST['booking_id'];
    $payment_status = $_POST['payment_status'];
    $payment_method = $_POST['payment_method'];
    $amount_paid = $_POST['amount_paid'];
    
    $stmt = $conn->prepare("UPDATE bookings SET payment_status = ?, payment_method = ?, amount_paid = ?, 
                           payment_date = NOW() WHERE id = ?");
    $stmt->bind_param("ssdi", $payment_status, $payment_method, $amount_paid, $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Payment status updated successfully!";
    } else {
        $error_message = "Error updating payment status: " . $conn->error;
    }
}

// Get all cash bookings with customer and service details
$bookings_query = "SELECT b.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   s.name as service_name, s.price as service_price,
                   st.name as staff_name
                   FROM bookings b
                   JOIN customers c ON b.customer_id = c.id
                   JOIN services s ON b.service_id = s.id
                   LEFT JOIN staff st ON b.staff_id = st.id
                   WHERE b.payment_method = 'cash'
                   ORDER BY b.booking_date DESC";
$bookings_result = mysqli_query($conn, $bookings_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cash Orders - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="admin-styles.css" rel="stylesheet">
  <style>
    .payment-status { font-size: 0.875rem; }
  </style>
</head>
<body class="bg-light">
  <?php include('admin-navbar.php'); ?>

  <main class="py-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Cash Orders</h1>
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
            switch ($booking['payment_status']) {
              case 'paid':
                $statusClass = 'success';
                break;
              case 'pending':
                $statusClass = 'warning';
                break;
              case 'cancelled':
                $statusClass = 'danger';
                break;
            }
            ?>
            <div class="col-md-6">
              <div class="card booking-card h-100">
                <div class="card-body">
                  <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                    <?php echo ucfirst($booking['payment_status']); ?>
                  </span>
                  
                  <h5 class="card-title mb-3">Booking #<?php echo $booking['id']; ?></h5>
                  
                  <div class="mb-3">
                    <h6 class="text-primary"><?php echo htmlspecialchars($booking['service_name']); ?></h6>
                    <div class="price">LKR <?php echo number_format($booking['service_price'], 2); ?></div>
                  </div>

                  <div class="mb-3">
                    <strong>Customer Details:</strong>
                    <div class="text-muted">
                      <div><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                      <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($booking['customer_email']); ?></div>
                      <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?></div>
                    </div>
                  </div>

                  <?php if (!empty($booking['staff_name'])): ?>
                    <div class="mb-3">
                      <strong>Assigned Staff:</strong>
                      <div class="text-muted"><?php echo htmlspecialchars($booking['staff_name']); ?></div>
                    </div>
                  <?php endif; ?>

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
                    </div>
                  </div>

                  <?php if ($booking['payment_status'] !== 'paid'): ?>
                    <button class="btn btn-primary w-100" 
                            onclick="updatePayment(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                      <i class="bi bi-cash"></i> Update Payment
                    </button>
                  <?php else: ?>
                    <div class="payment-status text-success">
                      <i class="bi bi-check-circle"></i> 
                      Paid on <?php echo date('M d, Y h:i A', strtotime($booking['payment_date'])); ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php
            }
        } else {
          echo '<div class="col-12"><div class="alert alert-info">No cash orders found.</div></div>';
        }
        ?>
      </div>
    </div>
  </main>

  <!-- Update Payment Modal -->
  <div class="modal fade" id="updatePaymentModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Payment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="" method="POST">
          <input type="hidden" id="booking_id" name="booking_id">
          <div class="modal-body">
            <div class="mb-3">
              <label for="payment_status" class="form-label">Payment Status</label>
              <select class="form-select" id="payment_status" name="payment_status" required>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="payment_method" class="form-label">Payment Method</label>
              <select class="form-select" id="payment_method" name="payment_method" required>
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="upi">UPI</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="amount_paid" class="form-label">Amount Paid</label>
              <div class="input-group">
                <span class="input-group-text">LKR</span>
                <input type="number" class="form-control" id="amount_paid" name="amount_paid" 
                       step="0.01" required>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_payment" class="btn btn-primary">Update Payment</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include('admin-footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function updatePayment(booking) {
      document.getElementById('booking_id').value = booking.id;
      document.getElementById('payment_status').value = booking.payment_status;
      document.getElementById('payment_method').value = booking.payment_method;
      document.getElementById('amount_paid').value = booking.service_price;
      
      new bootstrap.Modal(document.getElementById('updatePaymentModal')).show();
    }
  </script>
</body>
</html>
<?php
$db->closeConnection();
?>
