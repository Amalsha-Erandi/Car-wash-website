<?php
session_start();
require_once 'Database_Connection.php';

// Debug logging
error_log("Session customer_id: " . (isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 'not set'));
error_log("Product ID from URL: " . (isset($_GET['id']) ? $_GET['id'] : 'not set'));

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    error_log("User not logged in, redirecting to login.php");
    header("Location: login.php");
    exit;
}

// Check if product ID is provided in URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    error_log("No product ID provided, redirecting to products.php");
    header('Location: products.php');
    exit;
}

$product_id = (int)$_GET['id'];
error_log("Fetching details for product ID: " . $product_id);

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();

    // Fetch product details from database
    $query = "SELECT * FROM products WHERE id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("Failed to prepare statement: " . mysqli_error($conn));
        throw new Exception("Database error");
    }
    
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Failed to execute statement: " . mysqli_stmt_error($stmt));
        throw new Exception("Database error");
    }
    
    $result = mysqli_stmt_get_result($stmt);

    // Check if product exists
    if (mysqli_num_rows($result) === 0) {
        error_log("Product not found: " . $product_id);
        header('Location: products.php');
        exit;
    }

    $product = mysqli_fetch_assoc($result);
    error_log("Product found: " . $product['name']);

    // Calculate final price after discount (with null coalescing operator)
    $product['discount'] = $product['discount'] ?? 0;  // Set default to 0 if not set
    $discounted_price = $product['price'] - ($product['price'] * $product['discount'] / 100);

    // Get related products
    $related_query = "SELECT * FROM products WHERE id != ? AND status = 'active' AND stock_quantity > 0 ORDER BY RAND() LIMIT 4";
    $related_stmt = mysqli_prepare($conn, $related_query);
    
    if (!$related_stmt) {
        error_log("Failed to prepare related products statement: " . mysqli_error($conn));
        throw new Exception("Database error");
    }
    
    mysqli_stmt_bind_param($related_stmt, "i", $product_id);
    
    if (!mysqli_stmt_execute($related_stmt)) {
        error_log("Failed to execute related products statement: " . mysqli_stmt_error($related_stmt));
        throw new Exception("Database error");
    }
    
    $related_result = mysqli_stmt_get_result($related_stmt);

} catch (Exception $e) {
    error_log("Error in product-details.php: " . $e->getMessage());
    header('Location: products.php');
    exit;
} finally {
    // Close database connection
    if (isset($db)) {
        $db->closeConnection();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($product['name']); ?> - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="customer.css">
  <link rel="stylesheet" href="modern-products.css">
  <link rel="stylesheet" href="product-details.css">
  <style>
    .stock-quantity {
      display: block;
      margin-top: 5px;
      font-size: 0.9rem;
      color: #2c7be5;
      font-weight: 500;
    }
    
    .stock-warning {
      color: #e63757;
      font-weight: 500;
      margin-top: 5px;
      display: block;
    }
    
    .in-stock {
      color: #00d97e;
      font-weight: 600;
    }
    
    .out-of-stock {
      color: #e63757;
      font-weight: 600;
    }
  </style>
</head>
<body>
  <?php include('customer-navbar.php'); ?>
  
  <main>
    <!-- Breadcrumb -->
    <div class="container-fluid px-4 py-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item"><a href="products.php">Products</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
      </nav>
    </div>
    
    <!-- Product Details Section -->
    <section class="product-details-section">
      <div class="container-fluid px-4">
        <div class="product-details-wrapper">
          <div class="row g-4">
            <!-- Product Image -->
            <div class="col-lg-5">
              <div class="product-image-wrapper">
                <?php if (isset($product['discount']) && $product['discount'] > 0): ?>
                  <div class="discount-badge">-<?php echo $product['discount']; ?>%</div>
                <?php endif; ?>
                <div class="product-main-image">
                  <img src="<?php 
                    if (!empty($product['image_url'])) {
                        // If image starts with http, it's an external URL
                        if (strpos($product['image_url'], 'http') === 0) {
                            echo htmlspecialchars($product['image_url']);
                        } else {
                            // Use the admin's upload directory
                            echo '../Sample/' . htmlspecialchars($product['image_url']);
                        }
                    } else {
                        // Default placeholder image
                        echo '../Sample/uploads/products/placeholder.jpg';
                    }
                  ?>" 
                      alt="<?php echo htmlspecialchars($product['name']); ?>"
                      onerror="this.src='../Sample/uploads/products/placeholder.jpg'">
                </div>
              </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-lg-7">
              <div class="product-info">
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-pricing">
                  <?php if (isset($product['discount']) && $product['discount'] > 0): ?>
                    <div class="price-with-discount">
                      <span class="original-price">LKR <?php echo number_format($product['price'], 2); ?></span>
                      <span class="current-price">LKR <?php echo number_format($discounted_price, 2); ?></span>
                    </div>
                  <?php else: ?>
                    <div class="price-regular">
                      <span class="current-price">LKR <?php echo number_format($product['price'], 2); ?></span>
                    </div>
                  <?php endif; ?>
                </div>
                
                <div class="product-description">
                  <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
                <div class="product-meta">
                  <div class="stock-info">
                    <?php if ($product['stock_quantity'] > 0): ?>
                      <div class="stock-badge in-stock">
                        <i class="bi bi-check-circle-fill"></i> In Stock
                      </div>
                      <span class="stock-quantity">Available Quantity: <?php echo $product['stock_quantity']; ?></span>
                      <?php if ($product['stock_quantity'] < 10): ?>
                        <span class="stock-warning">Only <?php echo $product['stock_quantity']; ?> left</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <div class="stock-badge out-of-stock">
                        <i class="bi bi-x-circle-fill"></i> Out of Stock
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <div class="product-actions">
                  <?php if ($product['stock_quantity'] > 0): ?>
                    <div class="quantity-selector">
                      <label for="quantity">Quantity</label>
                      <div class="quantity-input-group">
                        <button type="button" class="quantity-btn minus" id="decrease-quantity">
                          <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" readonly>
                        <button type="button" class="quantity-btn plus" id="increase-quantity">
                          <i class="bi bi-plus"></i>
                        </button>
                      </div>
                    </div>
                    
                    <div class="action-buttons">
                      <button class="btn-add-to-cart" 
                              data-product-id="<?php echo $product['id']; ?>" 
                              data-product-name="<?php echo htmlspecialchars($product['name']); ?>" 
                              data-product-price="<?php echo $product['price']; ?>" 
                              data-product-image="<?php echo htmlspecialchars($product['image_url']); ?>"
                              data-product-quantity="<?php echo $product['stock_quantity']; ?>">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                      </button>
                      
                      <a href="products.php" class="btn-continue-shopping">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                      </a>
                    </div>
                  <?php else: ?>
                    <div class="out-of-stock-message">
                      <p>This product is currently out of stock. Please check back later or browse our other products.</p>
                      <a href="products.php" class="btn-continue-shopping">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                      </a>
                    </div>
                  <?php endif; ?>
                </div>
                
                <div class="product-additional-info">
                  <div class="info-item">
                    <i class="bi bi-truck"></i>
                    <span>Free shipping for orders over $50</span>
                  </div>
                  <div class="info-item">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>30-day money-back guarantee</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
    
    <!-- Related Products Section -->
    <?php if (mysqli_num_rows($related_result) > 0): ?>
    <section class="related-products-section">
      <div class="container-fluid px-4">
        <div class="section-header">
          <h2>Related Products</h2>
          <p>You might also be interested in these products</p>
        </div>
        
        <div class="related-products">
          <div class="row g-4">
            <?php while ($related = mysqli_fetch_assoc($related_result)): 
              // Set default discount to 0 if not set
              $related['discount'] = $related['discount'] ?? 0;
              $related_discounted = $related['price'] * (1 - $related['discount']/100);
            ?>
              <div class="col-lg-3 col-md-6">
                <div class="product-card">
                  <?php if (isset($related['discount']) && $related['discount'] > 0): ?>
                    <div class="discount-badge">-<?php echo $related['discount']; ?>%</div>
                  <?php endif; ?>
                  
                  <div class="product-image">
                    <a href="product-details.php?id=<?php echo $related['id']; ?>">
                      <img src="<?php echo htmlspecialchars($related['image_url']); ?>" 
                           alt="<?php echo htmlspecialchars($related['name']); ?>">
                    </a>
                  </div>
                  
                  <div class="product-details">
                    <h3 class="product-title">
                      <a href="product-details.php?id=<?php echo $related['id']; ?>">
                        <?php echo htmlspecialchars($related['name']); ?>
                      </a>
                    </h3>
                    
                    <div class="product-meta">
                      <div class="product-price">
                        <?php if (isset($related['discount']) && $related['discount'] > 0): ?>
                          <span class="original-price">LKR <?php echo number_format($related['price'], 2); ?></span>
                          <span class="current-price">LKR <?php echo number_format($related_discounted, 2); ?></span>
                        <?php else: ?>
                          <span class="current-price">LKR <?php echo number_format($related['price'], 2); ?></span>
                        <?php endif; ?>
                      </div>
                      
                      <div class="stock-indicator <?php echo $related['stock_quantity'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $related['stock_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                      </div>
                    </div>
                    
                    <div class="product-actions">
                      <button class="add-to-cart" 
                              <?php echo $related['stock_quantity'] <= 0 ? 'disabled' : ''; ?>
                              data-product-id="<?php echo $related['id']; ?>" 
                              data-product-name="<?php echo htmlspecialchars($related['name']); ?>" 
                              data-product-price="<?php echo $related['price']; ?>" 
                              data-product-image="<?php echo htmlspecialchars($related['image_url']); ?>"
                              data-product-quantity="<?php echo $related['stock_quantity']; ?>">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                      </button>
                      <a href="product-details.php?id=<?php echo $related['id']; ?>" class="view-details">
                        <i class="bi bi-eye"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </section>
    <?php endif; ?>
  </main>
  
  <!-- Notification Element -->
  <div id="notification" class="notification"></div>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Custom Script -->
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Quantity selector
      const quantityInput = document.getElementById('quantity');
      const decreaseBtn = document.getElementById('decrease-quantity');
      const increaseBtn = document.getElementById('increase-quantity');
      
      if (quantityInput && decreaseBtn && increaseBtn) {
        const maxQuantity = parseInt(<?php echo $product['stock_quantity']; ?>);
        
        decreaseBtn.addEventListener('click', function() {
          let currentValue = parseInt(quantityInput.value);
          if (currentValue > 1) {
            quantityInput.value = currentValue - 1;
          }
        });
        
        increaseBtn.addEventListener('click', function() {
          let currentValue = parseInt(quantityInput.value);
          if (currentValue < maxQuantity) {
            quantityInput.value = currentValue + 1;
          }
        });
        
        // Ensure input is always a valid number
        quantityInput.addEventListener('change', function() {
          let value = parseInt(this.value);
          if (isNaN(value) || value < 1) {
            this.value = 1;
          } else if (value > maxQuantity) {
            this.value = maxQuantity;
          }
        });
      }
      
      // Cart functionality
      function updateCartCount() {
        fetch('cart_count.php')
          .then(response => response.json())
          .then(data => {
            const cartCountElement = document.getElementById('cart-count');
            if (cartCountElement) {
              cartCountElement.textContent = data.count;
            }
          })
          .catch(error => console.error('Error updating cart count:', error));
      }

      // Initial cart count
      updateCartCount();
      
      // Show notification function
      function showNotification(message, type) {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${type} show`;
        
        setTimeout(() => {
          notification.classList.remove('show');
        }, 3000);
      }
      
      // Add to cart for main product
      const addToCartBtn = document.querySelector('.btn-add-to-cart');
      if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
          const productId = this.getAttribute('data-product-id');
          const productName = this.getAttribute('data-product-name');
          const productPrice = this.getAttribute('data-product-price');
          const productImage = this.getAttribute('data-product-image');
          const availableQuantity = parseInt(this.getAttribute('data-product-quantity'));
          
          // Get quantity from input
          const quantity = parseInt(quantityInput.value);
          
          if (isNaN(quantity) || quantity <= 0) {
            showNotification('Please enter a valid quantity.', 'error');
            return;
          }
          
          if (quantity > availableQuantity) {
            showNotification('Sorry, we don\'t have enough stock for this product.', 'error');
            return;
          }
          
          // Add animation
          this.classList.add('adding');
          
          // Add product to cart
          fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id: productId,
              name: productName,
              price: productPrice,
              image: productImage,
              quantity: quantity
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateCartCount();
              showNotification(`${quantity} × ${productName} added to cart!`, 'success');
              
              // Remove animation
              this.classList.remove('adding');
              this.classList.add('added');
              setTimeout(() => {
                this.classList.remove('added');
              }, 1500);
            } else {
              showNotification('Failed to add product: ' + data.message, 'error');
              this.classList.remove('adding');
            }
          })
          .catch(error => {
            console.error('Error adding to cart:', error);
            showNotification('An error occurred. Please try again.', 'error');
            this.classList.remove('adding');
          });
        });
      }
      
      // Add to cart for related products
      document.querySelectorAll('.related-products .add-to-cart').forEach(button => {
        button.addEventListener('click', function() {
          if (this.disabled) return;
          
          const productId = this.getAttribute('data-product-id');
          const productName = this.getAttribute('data-product-name');
          const productPrice = this.getAttribute('data-product-price');
          const productImage = this.getAttribute('data-product-image');
          const availableQuantity = parseInt(this.getAttribute('data-product-quantity'));
          
          // Default quantity for related products is 1
          const quantity = 1;
          
          // Add animation
          this.classList.add('adding');
          
          // Add product to cart
          fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              id: productId,
              name: productName,
              price: productPrice,
              image: productImage,
              quantity: quantity
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateCartCount();
              showNotification(`${quantity} × ${productName} added to cart!`, 'success');
              
              // Remove animation
              this.classList.remove('adding');
              this.classList.add('added');
              setTimeout(() => {
                this.classList.remove('added');
              }, 1500);
            } else {
              showNotification('Failed to add product: ' + data.message, 'error');
              this.classList.remove('adding');
            }
          })
          .catch(error => {
            console.error('Error adding to cart:', error);
            showNotification('An error occurred. Please try again.', 'error');
            this.classList.remove('adding');
          });
        });
      });
    });
  </script>
</body>
</html>