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

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Order status updated successfully!";
    } else {
        $error_message = "Error updating order status: " . $conn->error;
    }
}

// Handle payment status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $order_id = $_POST['order_id'];
    $payment_status = $_POST['payment_status'];
    
    $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $payment_status, $order_id);
    
    if ($stmt->execute()) {
        $success_message = "Payment status updated successfully!";
    } else {
        $error_message = "Error updating payment status: " . $conn->error;
    }
}

// Get all orders with customer and product details
$orders_query = "SELECT o.*, 
                c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                COUNT(oi.id) as item_count
                FROM orders o
                JOIN customers c ON o.customer_id = c.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                GROUP BY o.id
                ORDER BY o.created_at DESC";
$orders_result = mysqli_query($conn, $orders_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Orders - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="admin-styles.css" rel="stylesheet">
  <style>
    .payment-badge {
      font-size: 0.875rem;
    }
  </style>
</head>
<body class="bg-light">
  <?php include('admin-navbar.php'); ?>

  <main class="py-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Manage Product Orders</h1>
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
        if ($orders_result && mysqli_num_rows($orders_result) > 0) {
          while ($order = mysqli_fetch_assoc($orders_result)) {
            $statusClass = 'secondary';
            switch ($order['status']) {
              case 'pending':
                $statusClass = 'warning';
                break;
              case 'processing':
                $statusClass = 'info';
                break;
              case 'shipped':
                $statusClass = 'primary';
                break;
              case 'delivered':
                $statusClass = 'success';
                break;
              case 'cancelled':
                $statusClass = 'danger';
                break;
            }
            
            $paymentClass = 'secondary';
            switch ($order['payment_status']) {
              case 'pending':
                $paymentClass = 'warning';
                break;
              case 'paid':
                $paymentClass = 'success';
                break;
              case 'failed':
                $paymentClass = 'danger';
                break;
            }
            ?>
            <div class="col-md-6">
              <div class="card order-card h-100">
                <div class="card-body">
                  <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                    <?php echo ucfirst($order['status']); ?>
                  </span>
                  
                  <h5 class="card-title mb-3">Order #<?php echo $order['id']; ?></h5>
                  
                  <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="price">LKR <?php echo number_format($order['total_amount'], 2); ?></div>
                      <span class="badge bg-<?php echo $paymentClass; ?> payment-badge">
                        <?php echo ucfirst($order['payment_status']); ?>
                      </span>
                    </div>
                    <small class="text-muted">
                      <?php echo $order['item_count']; ?> item(s) | 
                      <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                    </small>
                  </div>

                  <div class="mb-3">
                    <strong>Customer Details:</strong>
                    <div class="text-muted">
                      <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                      <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($order['customer_email']); ?></div>
                      <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <strong>Shipping Information:</strong>
                    <div class="text-muted">
                      <div><?php echo htmlspecialchars($order['shipping_address']); ?></div>
                      <div>
                        <?php echo htmlspecialchars($order['shipping_city']); ?>, 
                        <?php echo htmlspecialchars($order['shipping_postal_code']); ?>
                      </div>
                      <div><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['contact_phone']); ?></div>
                    </div>
                  </div>

                  <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary flex-grow-1" 
                            onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                      <i class="bi bi-eye"></i> View Details
                    </button>
                    <button class="btn btn-outline-success" 
                            onclick="updateStatus(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                      <i class="bi bi-arrow-clockwise"></i> Update Status
                    </button>
                    <button class="btn btn-outline-info" 
                            onclick="updatePayment(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                      <i class="bi bi-credit-card"></i> Payment
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <?php
          }
        } else {
          echo '<div class="col-12"><div class="alert alert-info">No orders found.</div></div>';
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
          <h5 class="modal-title">Update Order Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="" method="POST">
          <input type="hidden" id="order_id" name="order_id">
          <div class="modal-body">
            <div class="mb-3">
              <label for="status" class="form-label">Status</label>
              <select class="form-select" id="status" name="status" required>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
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

  <!-- Update Payment Modal -->
  <div class="modal fade" id="updatePaymentModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Update Payment Status</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="" method="POST">
          <input type="hidden" id="payment_order_id" name="order_id">
          <div class="modal-body">
            <div class="mb-3">
              <label for="payment_status" class="form-label">Payment Status</label>
              <select class="form-select" id="payment_status" name="payment_status" required>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="failed">Failed</option>
              </select>
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

  <!-- Order Details Modal -->
  <div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Order Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="orderDetailsContent">
          <div class="text-center">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <?php include('admin-footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function updateStatus(order) {
      document.getElementById('order_id').value = order.id;
      document.getElementById('status').value = order.status;
      
      new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
    }

    function updatePayment(order) {
      document.getElementById('payment_order_id').value = order.id;
      document.getElementById('payment_status').value = order.payment_status;
      
      new bootstrap.Modal(document.getElementById('updatePaymentModal')).show();
    }

    function viewOrderDetails(orderId) {
      const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
      modal.show();
      
      // Load order details via AJAX
      fetch('get-order-details.php?order_id=' + orderId)
        .then(response => response.text())
        .then(data => {
          document.getElementById('orderDetailsContent').innerHTML = data;
        })
        .catch(error => {
          document.getElementById('orderDetailsContent').innerHTML = 
            '<div class="alert alert-danger">Error loading order details: ' + error.message + '</div>';
        });
    }
  </script>
</body>
</html>
<?php
$db->closeConnection();
?> 