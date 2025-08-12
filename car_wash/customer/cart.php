<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'Database_Connection.php';

// Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
// Handle quantity updates
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $quantity) {
        if ($quantity > 0) {
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND customer_id = ?");
            $stmt->bind_param("iii", $quantity, $cart_id, $_SESSION['customer_id']);
            $stmt->execute();
        }
    }
}

// Handle item removal
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $cart_id = (int)$_GET['remove'];
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $cart_id, $_SESSION['customer_id']);
    $stmt->execute();
}

// Get cart items
try {
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.description, p.image_url, p.stock_quantity 
        FROM cart c
        JOIN products p ON c.product_id = p.id 
        WHERE c.customer_id = ? AND c.status = 'pending'
    ");
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $cart_items = $stmt->get_result();
    
    // Calculate cart total
    $cart_total = 0;
    $cart_count = 0;
    $cart_data = [];
    
    while ($item = $cart_items->fetch_assoc()) {
        $cart_data[] = $item;
        $cart_total += $item['price'] * $item['quantity'];
        $cart_count += $item['quantity'];
    }
    
} catch (Exception $e) {
    error_log("Error fetching cart: " . $e->getMessage());
}

// Close database connection
$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <link rel="stylesheet" href="cart.css">
</head>
<body class="bg-light">
  <?php include('customer-navbar.php'); ?>

    <main>
        <section class="cart-section py-5">
            <div class="container">
                <h1 class="mb-4">Shopping Cart</h1>
                
                <?php if (count($cart_data) > 0): ?>
                    <form method="post" action="cart.php">
                        <div class="cart-table-wrapper">
                            <table class="table cart-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_data as $item): ?>
                                        <tr>
                                            <td class="product-info">
                                                <div class="d-flex align-items-center">
                                                    <div class="product-image">
                                                        <img src="<?php 
                                                            if (!empty($item['image_url'])) {
                                                                // If image starts with http, it's an external URL
                                                                if (strpos($item['image_url'], 'http') === 0) {
                                                                    echo htmlspecialchars($item['image_url']);
                                                                } else {
                                                                    // Use the admin's upload directory
                                                                    echo '../Sample/' . htmlspecialchars($item['image_url']);
                                                                }
                                                            } else {
                                                                // Default placeholder image
                                                                echo '../Sample/uploads/products/placeholder.jpg';
                                                            }
                                                        ?>" 
                                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                             onerror="this.src='../Sample/uploads/products/placeholder.jpg'">
                                                    </div>
                                                    <div class="product-details">
                                                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                                                        <p class="text-muted small"><?php echo substr(htmlspecialchars($item['description']), 0, 50); ?>...</p>
        </div>
              </div>
                                            </td>
                                            <td class="price">LKR <?php echo number_format($item['price'], 2); ?></td>
                                            <td class="quantity">
                                                <div class="quantity-input">
                                                    <button type="button" class="quantity-btn minus" onclick="updateQuantity(this, -1)">
                                                        <i class="bi bi-dash"></i>
                                                    </button>
                                                    <input type="number" name="quantity[<?php echo $item['id']; ?>]" 
                                                           value="<?php echo $item['quantity']; ?>" 
                                                           min="1" max="<?php echo $item['stock_quantity']; ?>" 
                                                           class="form-control" readonly>
                                                    <button type="button" class="quantity-btn plus" onclick="updateQuantity(this, 1)">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
            </div>
                                                <small class="text-muted d-block mt-1">
                                                    Available: <?php echo $item['stock_quantity']; ?> units
                                                </small>
                                            </td>
                                            <td class="total">
                                                <div class="price-calculation">
                                                    <span class="current-price">LKR <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                                    <?php if ($item['quantity'] > 1): ?>
                                                        <small class="text-muted d-block">
                                                            (<?php echo $item['quantity']; ?> × LKR <?php echo number_format($item['price'], 2); ?>)
                                                        </small>
                        <?php endif; ?>
                              </div>
                                            </td>
                                            <td class="actions">
                                                <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="cart-actions d-flex justify-content-between align-items-center mt-4">
                            <button type="submit" name="update_cart" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-clockwise"></i> Update Cart
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary">
                                <i class="bi bi-cart-plus"></i> Continue Shopping
                            </a>
                        </div>
                        
                        <div class="cart-summary mt-5">
                            <div class="row">
                                <div class="col-md-6 offset-md-6">
                                    <div class="card">
                    <div class="card-body">
                                            <h3 class="card-title">Cart Summary</h3>
                                            <table class="table table-borderless">
                                                <tr>
                                                    <td>Subtotal</td>
                                                    <td class="text-end">LKR <?php echo number_format($cart_total, 2); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Shipping</td>
                                                    <td class="text-end">Free</td>
                                                </tr>
                                                <tr class="fw-bold">
                                                    <td>Total</td>
                                                    <td class="text-end">LKR <?php echo number_format($cart_total, 2); ?></td>
                                                </tr>
                                            </table>
                                            <div class="d-grid gap-2">
                                                <a href="checkout.php" class="btn btn-primary">
                                                    Proceed to Checkout <i class="bi bi-arrow-right"></i>
                  </a>
                </div>
              </div>
            </div>
        </div>
      </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="empty-cart text-center py-5">
                        <div class="empty-cart-icon mb-4">
                            <i class="bi bi-cart-x display-1 text-muted"></i>
                        </div>
                        <h2>Your cart is empty</h2>
                        <p class="text-muted">Looks like you haven't added any products to your cart yet.</p>
                        <a href="products.php" class="btn btn-primary mt-3">
                            <i class="bi bi-cart-plus"></i> Browse Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
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
  <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize button states
            document.querySelectorAll('.quantity-input').forEach(container => {
                const input = container.querySelector('input');
                const minusBtn = container.querySelector('.minus');
                const plusBtn = container.querySelector('.plus');
                
                updateButtonStates(input, minusBtn, plusBtn);
            });
        });

        function updateQuantity(button, change) {
            const container = button.closest('.quantity-input');
            const input = container.querySelector('input');
            const minusBtn = container.querySelector('.minus');
            const plusBtn = container.querySelector('.plus');
            
            const currentValue = parseInt(input.value);
            const minValue = parseInt(input.min);
            const maxValue = parseInt(input.max);
            
            let newValue = currentValue + change;
            
            // Ensure value stays within min-max range
            if (newValue < minValue) newValue = minValue;
            if (newValue > maxValue) newValue = maxValue;
            
            // Update input value
            input.value = newValue;
            
            // Update button states
            updateButtonStates(input, minusBtn, plusBtn);
            
            // Update row total
            updateRowTotal(input);
            
            // Auto-submit the form after a short delay
            clearTimeout(window.updateTimeout);
            window.updateTimeout = setTimeout(() => {
                button.closest('form').submit();
            }, 500);
        }

        function updateButtonStates(input, minusBtn, plusBtn) {
            const currentValue = parseInt(input.value);
            const minValue = parseInt(input.min);
            const maxValue = parseInt(input.max);
            
            minusBtn.disabled = currentValue <= minValue;
            plusBtn.disabled = currentValue >= maxValue;
        }

        function updateRowTotal(input) {
            const row = input.closest('tr');
            const priceCell = row.querySelector('.price');
            const totalCell = row.querySelector('.price-calculation .current-price');
            const calculationInfo = row.querySelector('.price-calculation small');
            
            const quantity = parseInt(input.value);
            const price = parseFloat(priceCell.textContent.replace('LKR ', '').replace(',', ''));
            const total = price * quantity;
            
            // Update total
            totalCell.textContent = 'LKR ' + total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            
            // Update calculation info
            if (quantity > 1) {
                calculationInfo.textContent = `(${quantity} × LKR ${price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})})`;
                calculationInfo.style.display = 'block';
                            } else {
                calculationInfo.style.display = 'none';
            }
            
            // Update cart total
            updateCartTotal();
        }

        function updateCartTotal() {
            let total = 0;
            document.querySelectorAll('.price-calculation .current-price').forEach(cell => {
                const amount = parseFloat(cell.textContent.replace('LKR ', '').replace(',', ''));
                total += amount;
            });
            
            // Update subtotal and total
            const subtotalCell = document.querySelector('.cart-summary .table tr:first-child td:last-child');
            const totalCell = document.querySelector('.cart-summary .table tr:last-child td:last-child');
            
            if (subtotalCell && totalCell) {
                const formattedTotal = total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                subtotalCell.textContent = 'LKR ' + formattedTotal;
                totalCell.textContent = 'LKR ' + formattedTotal;
            }
        }
  </script>
</body>
</html>