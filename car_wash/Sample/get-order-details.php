<?php
session_start();
include('Database_Connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo '<div class="alert alert-danger">Invalid order ID</div>';
    exit;
}

$order_id = intval($_GET['order_id']);

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Get order details
    $order_query = "SELECT o.*, 
                    c.name as customer_name, c.email as customer_email, c.phone as customer_phone
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    WHERE o.id = ?";
    
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        echo '<div class="alert alert-danger">Order not found</div>';
        exit;
    }
    
    $order = $order_result->fetch_assoc();
    
    // Get order items
    $items_query = "SELECT oi.*, p.name as product_name, p.image_url
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($items_query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $items = [];
    $subtotal = 0;
    
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
        $subtotal += ($item['price'] * $item['quantity']);
    }
    
    // Status badge colors
    $status_colors = array(
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    );
    
    // Payment status colors
    $payment_colors = array(
        'pending' => 'warning',
        'paid' => 'success',
        'failed' => 'danger'
    );
    
    // Output order details
    ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <h5>Order Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Order ID:</th>
                    <td>#<?php echo $order['id']; ?></td>
                </tr>
                <tr>
                    <th>Date:</th>
                    <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <span class="badge bg-<?php echo $status_colors[$order['status']]; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Payment Method:</th>
                    <td><?php echo ucfirst($order['payment_method']); ?></td>
                </tr>
                <tr>
                    <th>Payment Status:</th>
                    <td>
                        <span class="badge bg-<?php echo $payment_colors[$order['payment_status']]; ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="col-md-6">
            <h5>Customer Information</h5>
            <table class="table table-sm">
                <tr>
                    <th>Name:</th>
                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                </tr>
                <tr>
                    <th>Email:</th>
                    <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                </tr>
                <tr>
                    <th>Phone:</th>
                    <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                </tr>
                <tr>
                    <th>Shipping Address:</th>
                    <td>
                        <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                        <?php echo htmlspecialchars($order['shipping_city']); ?>, 
                        <?php echo htmlspecialchars($order['shipping_postal_code']); ?>
                    </td>
                </tr>
                <tr>
                    <th>Contact Phone:</th>
                    <td><?php echo htmlspecialchars($order['contact_phone']); ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <h5>Order Items</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="me-3" style="width: 50px; height: 50px; overflow: hidden;">
                                    <?php 
                                        if (!empty($item['image_url'])) {
                                            if (strpos($item['image_url'], 'http') === 0) {
                                                $imageUrl = $item['image_url'];
                                            } else {
                                                $imageUrl = 'uploads/products/' . $item['image_url'];
                                            }
                                        } else {
                                            $imageUrl = 'uploads/products/placeholder.jpg';
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;"
                                         onerror="this.src='uploads/products/placeholder.jpg'">
                                </div>
                                <div>
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </div>
                            </div>
                        </td>
                        <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="text-end">LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                    <td class="text-end">LKR <?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php if (isset($order['shipping_fee']) && $order['shipping_fee'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-end">Shipping Fee:</td>
                    <td class="text-end">LKR <?php echo number_format($order['shipping_fee'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (isset($order['tax_amount']) && $order['tax_amount'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-end">Tax:</td>
                    <td class="text-end">LKR <?php echo number_format($order['tax_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (isset($order['discount_amount']) && $order['discount_amount'] > 0): ?>
                <tr>
                    <td colspan="3" class="text-end">Discount:</td>
                    <td class="text-end">-LKR <?php echo number_format($order['discount_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td class="text-end"><strong>LKR <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <?php if (!empty($order['notes'])): ?>
    <div class="mt-3">
        <h5>Order Notes</h5>
        <div class="p-3 bg-light rounded">
            <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mt-3 d-flex justify-content-between">
        <a href="admin-manage-orders.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
        <div>
            <button class="btn btn-outline-success" 
                    onclick="updateStatus(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                <i class="bi bi-arrow-clockwise"></i> Update Status
            </button>
            <button class="btn btn-outline-info" 
                    onclick="updatePayment(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                <i class="bi bi-credit-card"></i> Update Payment
            </button>
        </div>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
}

// Close database connection
$db->closeConnection();
?> 