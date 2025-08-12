<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'Database_Connection.php';

$error = '';
$success = '';
$cart_data = [];
$vehicles = [];
$customer = null;
$cart_total = 0;
$cart_count = 0;
$total_duration = 0;

// Get booking details from session if they exist
$selected_date = isset($_SESSION['booking_date']) ? $_SESSION['booking_date'] : '';
$selected_time = isset($_SESSION['booking_time']) ? $_SESSION['booking_time'] : '';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get cart items
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.description, p.image_url, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.customer_id = ? AND c.status = 'pending'
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare cart items query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['customer_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute cart items query: " . $stmt->error);
    }
    
    $cart_items = $stmt->get_result();
    
    // Calculate cart total
    while ($item = $cart_items->fetch_assoc()) {
        $cart_data[] = $item;
        $cart_total += $item['price'] * $item['quantity'];
        $cart_count += $item['quantity'];
    }
    
    // Get customer details
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare customer query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['customer_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute customer query: " . $stmt->error);
    }
    
    $customer = $stmt->get_result()->fetch_assoc();
    
    if (!$customer) {
        throw new Exception("Customer not found");
    }

    // Get customer's vehicles
    $stmt = $conn->prepare("
        SELECT * FROM customer_vehicles 
        WHERE customer_id = ? AND status = 'active'
        ORDER BY created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare vehicles query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['customer_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute vehicles query: " . $stmt->error);
    }
    
    $vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Process order
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Start transaction
            if (!$conn->begin_transaction()) {
                throw new Exception("Failed to start transaction");
            }
            
            // Create order
            $stmt = $conn->prepare("
                INSERT INTO orders (
                    customer_id, 
                    total_amount, 
                    payment_method,
                    shipping_address,
                    shipping_city,
                    shipping_postal_code,
                    contact_phone,
                    booking_date,
                    booking_time,
                    vehicle_id,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare order creation query: " . $conn->error);
            }
            
            $payment_method = $_POST['payment_method'];
            $shipping_address = $_POST['shipping_address'];
            $shipping_city = $_POST['shipping_city'];
            $shipping_postal_code = $_POST['shipping_postal_code'];
            $contact_phone = $_POST['contact_phone'];
            $booking_date = $_POST['booking_date'];
            $booking_time = $_POST['booking_time'];
            $vehicle_id = $_POST['vehicle_id'];
            
            $stmt->bind_param(
                "idsssssssi", 
                $_SESSION['customer_id'], 
                $cart_total, 
                $payment_method,
                $shipping_address,
                $shipping_city,
                $shipping_postal_code,
                $contact_phone,
                $booking_date,
                $booking_time,
                $vehicle_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create order: " . $stmt->error);
            }
            
            $order_id = $conn->insert_id;
            
            // Add order items
            $stmt = $conn->prepare("
                INSERT INTO order_items (
                    order_id, 
                    product_id, 
                    quantity, 
                    price
                ) VALUES (?, ?, ?, ?)
            ");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare order items query: " . $conn->error);
            }
            
            foreach ($cart_data as $item) {
                $stmt->bind_param(
                    "iiid", 
                    $order_id, 
                    $item['product_id'], 
                    $item['quantity'], 
                    $item['price']
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to add order item: " . $stmt->error);
                }
                
                // Update product stock
                $update_stock = $conn->prepare("
                    UPDATE products 
                    SET stock_quantity = stock_quantity - ? 
                    WHERE id = ?
                ");
                
                if (!$update_stock) {
                    throw new Exception("Failed to prepare stock update query: " . $conn->error);
                }
                
                $update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                
                if (!$update_stock->execute()) {
                    throw new Exception("Failed to update product stock: " . $update_stock->error);
                }
            }
            
            // Mark cart items as completed
            $stmt = $conn->prepare("
                UPDATE cart 
                SET status = 'completed' 
                WHERE customer_id = ? AND status = 'pending'
            ");
            
            if (!$stmt) {
                throw new Exception("Failed to prepare cart update query: " . $conn->error);
            }
            
            $stmt->bind_param("i", $_SESSION['customer_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update cart status: " . $stmt->error);
            }
            
            // Commit transaction
            if (!$conn->commit()) {
                throw new Exception("Failed to commit transaction");
            }
            
            // Redirect to order confirmation
            header("Location: order-confirmation.php?order_id=" . $order_id);
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            error_log("Order processing error: " . $e->getMessage());
            $error = "An error occurred while processing your order: " . $e->getMessage();
        }
    }
    
    if ($stmt) {
        $stmt->close();
    }
    $db->closeConnection();

} catch (Exception $e) {
    error_log("Error in checkout.php: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <link rel="stylesheet" href="cart.css">
    <style>
        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
        }
        .service-item {
            border-bottom: 1px solid #eee;
            padding: 1rem 0;
        }
        .service-item:last-child {
            border-bottom: none;
        }
        .time-slot {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .time-slot:hover {
            background-color: #e9ecef;
        }
        .time-slot.selected {
            background-color: #0d6efd;
            color: white;
        }
        .vehicle-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .vehicle-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .vehicle-card.selected {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('customer-navbar.php'); ?>

    <div class="container checkout-container">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

        <?php if (empty($cart_data)): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted"></i>
                <h3 class="mt-3">Your cart is empty</h3>
                <p class="text-muted">Please add products to your cart before checkout</p>
                <a href="services.php" class="btn btn-primary">View Services</a>
            </div>
        <?php else: ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="checkoutForm">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Booking Date & Time -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0"><i class="bi bi-calendar-check"></i> Select Date & Time</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="booking_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="booking_date" name="booking_date" 
                                               min="<?php echo date('Y-m-d'); ?>" 
                                               max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" 
                                               value="<?php echo htmlspecialchars($selected_date); ?>"
                                               required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Time Slot</label>
                                        <div class="row g-2">
                                            <?php 
                                            $time_slots = [
                                                '08:00', '09:00', '10:00', '11:00', '12:00',
                                                '13:00', '14:00', '15:00', '16:00', '17:00',
                                                '18:00', '19:00', '20:00'
                                            ];
                                            foreach ($time_slots as $slot): 
                                            ?>
                                                <div class="col-4">
                                                    <div class="time-slot p-2 text-center border rounded <?php echo ($slot === $selected_time) ? 'selected' : ''; ?>" 
                                                         onclick="selectTimeSlot(this, '<?php echo $slot; ?>')">
                                                        <?php echo $slot; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <input type="hidden" name="booking_time" id="booking_time" 
                                               value="<?php echo htmlspecialchars($selected_time); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Vehicle Selection -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0"><i class="bi bi-car-front-fill"></i> Select Vehicle</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($vehicles as $vehicle): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card vehicle-card" onclick="selectVehicle(this, <?php echo $vehicle['id']; ?>)">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?></h5>
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
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="vehicle_id" id="vehicle_id" required>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0"><i class="bi bi-credit-card"></i> Payment Method</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" checked required>
                                    <label class="form-check-label" for="cash">
                                        <i class="bi bi-cash"></i> Cash
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0"><i class="bi bi-person-lines-fill"></i> Contact Information</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shipping_address" class="form-label">Address</label>
                                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="contact_phone" class="form-label">Contact Phone</label>
                                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                                               value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shipping_city" class="form-label">City</label>
                                        <input type="text" class="form-control" id="shipping_city" name="shipping_city" 
                                               value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="shipping_postal_code" class="form-label">Postal Code</label>
                                        <input type="text" class="form-control" id="shipping_postal_code" name="shipping_postal_code" 
                                               value="<?php echo htmlspecialchars($customer['postal_code'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Order Summary -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h4 class="mb-0"><i class="bi bi-receipt"></i> Order Summary</h4>
                            </div>
                            <div class="card-body">
                                <?php foreach ($cart_data as $item): ?>
                                    <div class="service-item">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-muted mb-1">
                                            <small><?php echo $item['quantity']; ?> × LKR <?php echo number_format($item['price'], 2); ?></small>
                                        </p>
                                        <p class="mb-0">LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                    </div>
                                <?php endforeach; ?>

                                <hr>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Items:</span>
                                    <span><?php echo $cart_count; ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="fw-bold">Total Amount:</span>
                                    <span class="fw-bold">LKR <?php echo number_format($cart_total, 2); ?></span>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-check-circle"></i> Place Order
                                </button>
                            </div>
                        </div>

                        <!-- Need Help -->
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-question-circle"></i> Need Help?</h5>
                                <p class="card-text">Our customer service team is available 24/7 to assist you with your order.</p>
                                <a href="contact.php" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-headset"></i> Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
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
        // Vehicle selection
        function selectVehicle(element, vehicleId) {
            document.querySelectorAll('.vehicle-card').forEach(card => {
                card.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('vehicle_id').value = vehicleId;
        }

        // Time slot selection
        function selectTimeSlot(element, time) {
            document.querySelectorAll('.time-slot').forEach(slot => {
                slot.classList.remove('selected');
            });
            element.classList.add('selected');
            document.getElementById('booking_time').value = time;
        }

        // Initialize pre-selected time slot if it exists
        document.addEventListener('DOMContentLoaded', function() {
            const selectedTime = '<?php echo $selected_time; ?>';
            if (selectedTime) {
                const timeSlots = document.querySelectorAll('.time-slot');
                timeSlots.forEach(slot => {
                    if (slot.textContent.trim() === selectedTime) {
                        slot.classList.add('selected');
                    }
                });
            }
        });

        // Form validation
        const checkoutForm = document.getElementById('checkoutForm');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(event) {
                const vehicleId = document.getElementById('vehicle_id').value;
                const bookingDate = document.getElementById('booking_date').value;
                const bookingTime = document.getElementById('booking_time').value;
                const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                const shippingAddress = document.getElementById('shipping_address').value.trim();
                const contactPhone = document.getElementById('contact_phone').value.trim();
                const shippingCity = document.getElementById('shipping_city').value.trim();
                const shippingPostalCode = document.getElementById('shipping_postal_code').value.trim();

                if (!vehicleId) {
                    event.preventDefault();
                    alert('Please select a vehicle');
                    return;
                }

                if (!bookingDate) {
                    event.preventDefault();
                    alert('Please select a booking date');
                    return;
                }
                        
                if (!bookingTime) {
                    event.preventDefault();
                    alert('Please select a time slot');
                    return;
                }

                if (!paymentMethod) {
                    event.preventDefault();
                    alert('Please select a payment method');
                    return;
                }

                if (!shippingAddress) {
                    event.preventDefault();
                    alert('Please enter your address');
                    return;
                }

                if (!contactPhone) {
                    event.preventDefault();
                    alert('Please enter your contact phone number');
                    return;
                }

                if (!shippingCity) {
                    event.preventDefault();
                    alert('Please enter your city');
                    return;
                }

                if (!shippingPostalCode) {
                    event.preventDefault();
                    alert('Please enter your postal code');
                    return;
                }
            });

            // Disable past dates
            const bookingDate = document.getElementById('booking_date');
            const today = new Date().toISOString().split('T')[0];
            bookingDate.setAttribute('min', today);
        }
    </script>
</body>
</html>