<?php
//order-tracking.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'Database_Connection.php';

$error = '';
$success = '';
$booking = null;
$order = null;
$timeline = [];

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if we're tracking an order or a booking
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    
    if ($order_id > 0) {
        // Get order details
        $stmt = $conn->prepare("
            SELECT o.*, 
                   oi.quantity, oi.price as item_price,
                   p.name as product_name, p.image_url,
                   c.name as customer_name, c.phone as customer_phone
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE o.id = ? AND o.customer_id = ?
        ");
        $stmt->bind_param("ii", $order_id, $_SESSION['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Order not found");
        }
        
        // Group order items
        $order = null;
        while ($row = $result->fetch_assoc()) {
            if (!$order) {
                $order = $row;
                $order['items'] = [];
            }
            if ($row['product_name']) {
                $order['items'][] = [
                    'name' => $row['product_name'],
                    'quantity' => $row['quantity'],
                    'price' => $row['item_price'],
                    'image_url' => $row['image_url']
                ];
            }
        }
        
    } elseif ($booking_id > 0) {
        // Get booking details
        $stmt = $conn->prepare("
            SELECT b.*, 
                   GROUP_CONCAT(s.name SEPARATOR ', ') as services,
                   GROUP_CONCAT(bs.price SEPARATOR ', ') as service_prices,
                   GROUP_CONCAT(bs.duration SEPARATOR ', ') as service_durations,
                   v.make, v.model, v.vehicle_type, v.license_plate,
                   c.name as customer_name, c.phone as customer_phone,
                   COALESCE(st.name, '') as staff_name,
                   COALESCE(st.phone, '') as staff_phone
            FROM bookings b
            JOIN booking_services bs ON b.id = bs.booking_id
            JOIN services s ON bs.service_id = s.id
            JOIN customer_vehicles v ON b.vehicle_id = v.id
            JOIN customers c ON b.customer_id = c.id
            LEFT JOIN staff st ON b.staff_id = st.id
            WHERE b.id = ? AND b.customer_id = ?
            GROUP BY b.id
        ");
        $stmt->bind_param("ii", $booking_id, $_SESSION['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Booking not found");
        }
        
        $booking = $result->fetch_assoc();
        
        // Get booking timeline
        $stmt = $conn->prepare("
            SELECT * FROM booking_status_history 
            WHERE booking_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();
        $timeline = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
    } else {
        throw new Exception("Please provide a valid order or booking ID");
    }
    
    $stmt->close();
    $db->closeConnection();
    
} catch (Exception $e) {
    error_log("Tracking error: " . $e->getMessage());
    $error = $e->getMessage();
}

// Status badge colors
$status_colors = array(
    'pending' => 'warning',
    'processing' => 'info',
    'shipped' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger',
    'scheduled' => 'info',
    'confirmed' => 'primary',
    'in_progress' => 'warning',
    'completed' => 'success'
);

// Payment status colors
$payment_colors = array(
    'pending' => 'warning',
    'paid' => 'success',
    'failed' => 'danger',
    'refunded' => 'info'
);

// Format services array for bookings
$services = [];
$prices = [];
$durations = [];
if ($booking && isset($booking['services'])) {
    $services = explode(', ', $booking['services']);
    $prices = explode(', ', $booking['service_prices']);
    $durations = explode(', ', $booking['service_durations']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track <?php echo $order_id ? "Order #$order_id" : "Booking #$booking_id"; ?> - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="customer.css">
    <style>
        .tracking-container {
            max-width: 1000px;
            margin: 2rem auto;
        }
        .timeline {
            position: relative;
            padding-left: 50px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        .timeline-marker {
            position: absolute;
            left: -50px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #0d6efd;
            text-align: center;
            line-height: 36px;
            color: #0d6efd;
            z-index: 1;
        }
        .timeline-marker.completed {
            background: #0d6efd;
            color: #fff;
        }
        .timeline-content {
            background: #fff;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .service-item {
            transition: all 0.3s ease;
        }
        .service-item:hover {
            transform: translateX(5px);
        }
    </style>
</head>
<body class="bg-light">
    <?php include('customer-navbar.php'); ?>

    <div class="container tracking-container">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                <div class="mt-3">
                    <a href="<?php echo $order_id ? 'my-orders.php' : 'bookings.php'; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Back to <?php echo $order_id ? 'Orders' : 'Bookings'; ?>
                    </a>
                </div>
            </div>
        <?php elseif ($order): ?>
            <!-- Order Tracking View -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-box"></i> Order #<?php echo $order_id; ?>
                        </h4>
                        <span class="badge bg-<?php echo $status_colors[$order['status']]; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Order Details</h5>
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y', strtotime($order['created_at'])); ?></p>
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

                    <h5>Order Items</h5>
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

                    <div class="mt-4">
                        <h5>Order Status</h5>
                        <div class="timeline">
                            <?php
                            $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                            $currentFound = false;
                            foreach ($statuses as $status):
                                $isCompleted = !$currentFound && $status != $order['status'];
                                if ($status == $order['status']) {
                                    $currentFound = true;
                                }
                            ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?php echo $isCompleted ? 'completed' : ''; ?>">
                                    <i class="bi bi-<?php 
                                        echo match($status) {
                                            'pending' => 'clock',
                                            'processing' => 'gear',
                                            'shipped' => 'truck',
                                            'delivered' => 'check-lg',
                                            default => 'circle'
                                        };
                                    ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-0"><?php echo ucfirst($status); ?></h6>
                                    <?php if ($status == $order['status']): ?>
                                        <small class="text-muted">Current Status</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="my-orders.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-arrow-left"></i> Back to Orders
                </a>
            </div>

        <?php elseif ($booking): ?>
            <!-- Booking Header -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-info-circle"></i> Booking #<?php echo $booking_id; ?>
                        </h4>
                        <span class="badge bg-<?php echo $status_colors[$booking['booking_status']]; ?>">
                            <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h5><i class="bi bi-car-front"></i> Vehicle Details</h5>
                            <p class="mb-1">
                                <?php echo $booking['make'] . ' ' . $booking['model']; ?>
                                <span class="text-muted">(<?php echo $booking['vehicle_type']; ?>)</span>
                            </p>
                            <p class="mb-0 text-muted">
                                License Plate: <?php echo $booking['license_plate']; ?>
                            </p>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <h5><i class="bi bi-calendar-check"></i> Booking Details</h5>
                            <p class="mb-1">
                                Date: <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                            </p>
                            <p class="mb-0">
                                Time: <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                            </p>
                        </div>
                        
                        <?php if (!empty($booking['staff_name'])): ?>
                            <div class="col-md-6">
                                <h5><i class="bi bi-person"></i> Assigned Staff</h5>
                                <p class="mb-1"><?php echo $booking['staff_name']; ?></p>
                                <p class="mb-0 text-muted">
                                    <i class="bi bi-telephone"></i> <?php echo $booking['staff_phone']; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="col-md-6">
                            <h5><i class="bi bi-credit-card"></i> Payment Details</h5>
                            <p class="mb-1">
                                Method: <?php echo ucfirst($booking['payment_method']); ?>
                            </p>
                            <p class="mb-0">
                                Status: 
                                <span class="badge bg-<?php echo $payment_colors[$booking['payment_status']]; ?>">
                                    <?php echo ucfirst($booking['payment_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Services -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-water"></i> Services</h4>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <?php for ($i = 0; $i < count($services); $i++): ?>
                            <div class="list-group-item service-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $services[$i]; ?></h6>
                                        <p class="mb-0 text-muted">
                                            <small>
                                                <i class="bi bi-clock"></i> <?php echo $durations[$i]; ?> minutes
                                            </small>
                                        </p>
                                    </div>
                                    <span class="h5 mb-0">LKR <?php echo number_format($prices[$i], 2); ?></span>
                                </div>
                            </div>
                        <?php endfor; ?>
                        
                        <div class="list-group-item bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Total Duration</h6>
                                    <p class="mb-0 text-muted">
                                        <small>Estimated service time</small>
                                    </p>
                                </div>
                                <span class="h5 mb-0"><?php echo $booking['total_duration']; ?> minutes</span>
                            </div>
                        </div>
                        
                        <div class="list-group-item bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Total Amount</h6>
                                    <p class="mb-0 text-muted">
                                        <small>Including all services</small>
                                    </p>
                                </div>
                                <span class="h5 mb-0">LKR <?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-clock-history"></i> Booking Timeline</h4>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($timeline as $event): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker <?php 
                                    echo in_array($event['status'], ['completed', 'cancelled']) ? 'completed' : ''; 
                                ?>">
                                    <i class="bi bi-<?php 
                                        echo match($event['status']) {
                                            'scheduled' => 'calendar-check',
                                            'confirmed' => 'check-circle',
                                            'in_progress' => 'gear',
                                            'completed' => 'check-lg',
                                            'cancelled' => 'x-lg',
                                            default => 'circle'
                                        };
                                    ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="mb-0"><?php echo ucfirst($event['status']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($event['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php if (!empty($event['notes'])): ?>
                                        <p class="mb-0 text-muted"><?php echo $event['notes']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-4">
                <a href="bookings.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-arrow-left"></i> Back to Bookings
                </a>
                <?php if ($booking['booking_status'] === 'completed' && empty($booking['review_id'])): ?>
                    <a href="reviews.php" class="btn btn-primary">
                        <i class="bi bi-star"></i> Write a Review
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> No tracking information available.
                <div class="mt-3">
                    <a href="my-orders.php" class="btn btn-outline-primary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Orders
                    </a>
                    <a href="bookings.php" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>