<?php
session_start();
require_once 'Database_Connection.php';

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Get orders with complete details
    $stmt = $conn->prepare("
        SELECT o.*, 
               oi.quantity, oi.price as item_price,
               p.name as product_name, p.image_url,
               cv.make, cv.model, cv.license_plate, cv.vehicle_type
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN customer_vehicles cv ON o.vehicle_id = cv.id
        WHERE o.customer_id = ?
        ORDER BY o.created_at DESC
    ");
    
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Group orders and their items
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $order_id = $row['id'];
        if (!isset($orders[$order_id])) {
            $orders[$order_id] = [
                'id' => $row['id'],
                'total_amount' => $row['total_amount'],
                'payment_method' => $row['payment_method'],
                'payment_status' => $row['payment_status'],
                'shipping_address' => $row['shipping_address'],
                'shipping_city' => $row['shipping_city'],
                'shipping_postal_code' => $row['shipping_postal_code'],
                'contact_phone' => $row['contact_phone'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'booking_date' => $row['booking_date'],
                'booking_time' => $row['booking_time'],
                'vehicle_make' => $row['make'],
                'vehicle_model' => $row['model'],
                'vehicle_type' => $row['vehicle_type'],
                'license_plate' => $row['license_plate'],
                'items' => []
            ];
        }
        if ($row['product_name']) {
            $orders[$order_id]['items'][] = [
                'name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'price' => $row['item_price'],
                'image_url' => $row['image_url']
            ];
        }
    }
    
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <style>
        .order-card {
            transition: transform 0.2s;
            margin-bottom: 2rem;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .vehicle-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include('customer-navbar.php'); ?>

    <main class="container py-5">
        <h2 class="mb-4">My Orders</h2>

        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <div class="card order-card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order #<?php echo $order['id']; ?></h5>
                        <span class="badge bg-<?php echo $status_colors[$order['status']]; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Order Details</h5>
                                <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
                                <p class="mb-1"><strong>Order Status:</strong> <?php echo ucfirst($order['status']); ?></p>
                                <p class="mb-1"><strong>Payment Method:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
                                <p class="mb-1">
                                    <strong>Payment Status:</strong> 
                                    <span class="badge bg-<?php echo $payment_colors[$order['payment_status']]; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Shipping Information</h5>
                                <p class="mb-1"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                <p class="mb-1"><?php echo htmlspecialchars($order['shipping_city']) . ' ' . htmlspecialchars($order['shipping_postal_code']); ?></p>
                                <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_phone']); ?></p>
                            </div>
                        </div>

                        <?php if ($order['booking_date']): ?>
                        <div class="vehicle-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5><i class="bi bi-car-front"></i> Vehicle Details</h5>
                                    <p class="mb-1">
                                        <?php echo htmlspecialchars($order['vehicle_make'] . ' ' . $order['vehicle_model']); ?>
                                        <span class="text-muted">(<?php echo ucfirst($order['vehicle_type']); ?>)</span>
                                    </p>
                                    <p class="mb-0 text-muted">License Plate: <?php echo htmlspecialchars($order['license_plate']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h5><i class="bi bi-calendar-check"></i> Booking Details</h5>
                                    <p class="mb-1">Date: <?php echo date('M d, Y', strtotime($order['booking_date'])); ?></p>
                                    <p class="mb-0">Time: <?php echo date('h:i A', strtotime($order['booking_time'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <h5>Order Summary</h5>
                        <div class="table-responsive">
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
                                    <?php foreach ($order['items'] as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3" style="width: 50px; height: 50px; overflow: hidden;">
                                                        <?php 
                                                            if (!empty($item['image_url'])) {
                                                                if (strpos($item['image_url'], 'http') === 0) {
                                                                    $imageUrl = $item['image_url'];
                                                                } else {
                                                                    $imageUrl = '../Sample/' . $item['image_url'];
                                                                }
                                                            } else {
                                                                $imageUrl = '../Sample/uploads/products/placeholder.jpg';
                                                            }
                                                        ?>
                                                        <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                             class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;"
                                                             onerror="this.src='../Sample/uploads/products/placeholder.jpg'">
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

                        <div class="mt-3">
                            <a href="order-tracking.php?order_id=<?php echo $order['id']; ?>" 
                               class="btn btn-primary">
                                <i class="bi bi-truck"></i> Track Order
                            </a>
                            <a href="products.php" class="btn btn-outline-primary">
                                <i class="bi bi-cart-plus"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> You haven't placed any orders yet.
                <div class="mt-3">
                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </main>

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