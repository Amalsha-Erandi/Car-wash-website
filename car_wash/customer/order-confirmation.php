<?php
session_start();
require_once 'Database_Connection.php';

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: cart.php");
    exit;
}

$order_id = (int)$_GET['order_id'];

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, c.name, c.email, c.phone
        FROM orders o
        JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $stmt->bind_param("ii", $order_id, $_SESSION['customer_id']);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    
    // If order not found or doesn't belong to this customer
    if (!$order) {
        header("Location: cart.php");
        exit;
    }
    
    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name, p.image_url
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items = $stmt->get_result();
    
    $items = [];
    $total_items = 0;
    
    while ($item = $order_items->fetch_assoc()) {
        $items[] = $item;
        $total_items += $item['quantity'];
    }
    
} catch (Exception $e) {
    error_log("Error fetching order: " . $e->getMessage());
}

// Close database connection
$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="customer.css">
</head>
<body>
    <?php include('customer-navbar.php'); ?>

    <main>
        <section class="confirmation-section py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <div class="confirmation-icon mb-4">
                        <i class="bi bi-check-circle-fill text-success display-1"></i>
                    </div>
                    <h1>Thank You for Your Order!</h1>
                    <p class="lead">Your order has been placed successfully.</p>
                </div>
                
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h3 class="mb-0">Order #<?php echo $order_id; ?></h3>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5>Order Details</h5>
                                        <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                        <p class="mb-1"><strong>Order Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                                        <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Shipping Information</h5>
                                        <p class="mb-1"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                        <p class="mb-1"><?php echo htmlspecialchars($order['shipping_city']) . ' ' . htmlspecialchars($order['shipping_postal_code']); ?></p>
                                        <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_phone']); ?></p>
                                    </div>
                                </div>
                                
                                <h5>Order Summary</h5>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
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
                                                                    // If image starts with http, it's an external URL
                                                                    if (strpos($item['image_url'], 'http') === 0) {
                                                                        $imageUrl = $item['image_url'];
                                                                    } else {
                                                                        // Use the admin's upload directory
                                                                        $imageUrl = '../Sample/' . $item['image_url'];
                                                                    }
                                                                } else {
                                                                    // Default placeholder image
                                                                    $imageUrl = '../Sample/uploads/products/placeholder.jpg';
                                                                }
                                                            ?>
                                                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                                 class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;"
                                                                 onerror="this.src='../Sample/uploads/products/placeholder.jpg';">
                                                        </div>
                                                        <div>
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td>LKR <?php echo number_format($item['price'], 2); ?></td>
                                                <td>LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td><strong>LKR <?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="products.php" class="btn btn-primary me-2">Continue Shopping</a>
                            <a href="order-tracking.php" class="btn btn-outline-primary">Track Order</a>
                        </div>
                    </div>
                </div>
        </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>