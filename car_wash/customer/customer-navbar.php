<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get pending bookings count
if (isset($_SESSION['customer_id'])) {
    require_once 'Database_Connection.php';
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            SELECT COUNT(*) as pending_count 
            FROM bookings 
            WHERE customer_id = ? AND status = 'pending'
        ");
        $stmt->bind_param("i", $_SESSION['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $_SESSION['pending_bookings'] = $row['pending_count'];
        
        $stmt->close();
        $db->closeConnection();
    } catch (Exception $e) {
        error_log("Error getting pending bookings: " . $e->getMessage());
        $_SESSION['pending_bookings'] = 0;
    }
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Navbar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    .cart-badge {
      font-size: 12px;
      position: absolute;
      top: -4px; 
      right: -10px; 
      background-color: blue;
      color: white;
      border-radius: 50%;
      padding: 3px 10px;
      min-width: 18px;
      text-align: center;
    }
    
    /* Fix for button dropdown to look like a link */
    .nav-item.dropdown .nav-link.dropdown-toggle {
      background: none;
      border: none;
      padding: 0.4rem 0.7rem;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 500;
      letter-spacing: 0.2px;
      color: rgba(255,255,255,0.85);
      text-align: left;
    }
    
    .nav-item.dropdown .nav-link.dropdown-toggle:focus {
      box-shadow: none;
      outline: none;
    }
    
    .nav-item.dropdown .nav-link.dropdown-toggle.active {
      color: #ffffff;
      font-weight: 600;
      background-color: rgba(255,255,255,0.15);
      border-radius: 3px;
    }
    
    /* E-commerce style compact navbar */
    .navbar {
      padding: 0.35rem 0; /* Reduced padding for height */
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .container {
      max-width: 1140px; /* Standard e-commerce container width */
    }
    
    .navbar-brand {
      font-size: 1.2rem; /* Smaller brand text */
      font-weight: 600;
      padding: 0;
      margin-right: 1rem;
    }
    
    .navbar-nav .nav-item .nav-link {
      padding: 0.4rem 0.7rem; /* Compact padding */
      font-size: 0.85rem; /* Smaller font size */
      font-weight: 500; /* Medium weight for all items */
      letter-spacing: 0.2px; /* Better readability */
      color: rgba(255,255,255,0.85);
    }
    
    .navbar-nav .nav-item .nav-link i {
      font-size: 0.85rem; /* Smaller icons */
      margin-right: 3px; /* Less space after icons */
    }
    
    .navbar-nav .nav-item .nav-link.active {
      color: #ffffff;
      font-weight: 600;
      background-color: rgba(255,255,255,0.15);
      border-radius: 3px;
    }
    
    .navbar-nav .nav-item .nav-link:hover {
      color: #ffffff;
      background-color: rgba(255,255,255,0.1);
      border-radius: 3px;
    }
    
    /* More compact spacing between nav items */
    .navbar-nav .nav-item {
      margin: 0 1px;
    }
    
    /* Dropdown styling */
    .dropdown-menu {
      border: none;
      border-radius: 4px;
      box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
      padding: 0.4rem 0;
      min-width: 10rem;
      margin-top: 0.5rem;
    }
    
    .dropdown-item {
      font-size: 0.85rem;
      padding: 0.4rem 1rem;
    }
    
    .dropdown-item:active, .dropdown-item:focus {
      background-color: #0d6efd;
      color: white;
    }
    
    /* Badge styling */
    .badge {
      font-size: 0.65rem;
      padding: 0.2rem 0.4rem;
    }
    
    /* Cart icon positioning */
    .position-absolute.top-0.start-100 {
      transform: translate(-70%, -30%) !important;
    }
    
    /* Mobile adjustments */
    @media (max-width: 991.98px) {
      .navbar-collapse {
        padding-top: 0.5rem;
      }
      
      .navbar-nav .nav-item {
        margin: 0;
      }
      
      .navbar-nav .nav-item .nav-link {
        padding: 0.5rem 1rem;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand" href="home.php">
        <i class="bi bi-droplet"></i> Smart Wash
      </a>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'home.php' ? 'active' : ''; ?>" href="home.php">
              <i class="bi bi-house"></i> Home
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'services.php' ? 'active' : ''; ?>" href="services.php">
              <i class="bi bi-tools"></i> Services
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'products.php' ? 'active' : ''; ?>" href="products.php">
              <i class="bi bi-box-seam"></i> Products
            </a>
          </li>
          <?php if (isset($_SESSION['customer_id'])): ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
              <i class="bi bi-calendar"></i> My Bookings
              <?php if (isset($_SESSION['pending_bookings']) && $_SESSION['pending_bookings'] > 0): ?>
                <span class="badge bg-danger"><?php echo $_SESSION['pending_bookings']; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'my-orders.php' ? 'active' : ''; ?>" href="my-orders.php">
              <i class="bi bi-bag"></i> My Orders
            </a>
          </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">
              <i class="bi bi-star"></i> Reviews
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?php echo $current_page === 'contact.php' ? 'active' : ''; ?>" href="contact.php">
              <i class="bi bi-envelope"></i> Contact
            </a>
          </li>
        </ul>
        
        <?php if (isset($_SESSION['customer_id'])): ?>
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link position-relative <?php echo $current_page === 'cart.php' ? 'active' : ''; ?>" href="cart.php">
                <i class="bi bi-cart3"></i>
                <?php
                // Get cart count
                $cart_count = 0;
                try {
                    $db = new Database();
                    $conn = $db->getConnection();
                    
                    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE customer_id = ? AND status = 'pending'");
                    $stmt->bind_param("i", $_SESSION['customer_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $cart_count = $row['count'] ? (int)$row['count'] : 0;
                    
                    $db->closeConnection();
                } catch (Exception $e) {
                    error_log("Cart count error: " . $e->getMessage());
                }
                ?>
                <?php if ($cart_count > 0): ?>
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count">
                    <?php echo $cart_count; ?>
                  </span>
                <?php endif; ?>
              </a>
            </li>
            <li class="nav-item dropdown">
              <button class="nav-link dropdown-toggle <?php echo $current_page === 'customer_profile.php' ? 'active' : ''; ?>" 
                 type="button" id="navbarDropdown" 
                 data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i> 
                <?php echo isset($_SESSION['customer_name']) ? htmlspecialchars($_SESSION['customer_name']) : 'Account'; ?>
              </button>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li>
                  <a class="dropdown-item" href="customer_profile.php">
                    <i class="bi bi-person"></i> My Profile
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        <?php else: ?>
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="login.php">
                <i class="bi bi-box-arrow-in-right"></i> Login
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" href="register.php">
                <i class="bi bi-person-plus"></i> Register
              </a>
            </li>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Cart Modal -->
  <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cartModalLabel">Your Cart</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <ul class="list-group" id="cart-items">
            <li class="list-group-item text-center">Loading...</li>
          </ul>
          <div class="mt-3 text-end">
            <strong>Total: $<span id="cart-total">0.00</span></strong>
          </div>
        </div>
        <div class="modal-footer">
          <a href="cart.php" class="btn btn-primary">View Cart</a>
          <a href="checkout.php" class="btn btn-success">Checkout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS - Updated to latest version -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Initialize when DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
      // Direct fix for dropdown functionality
      document.querySelectorAll('.dropdown-toggle').forEach(function(element) {
        element.addEventListener('click', function(e) {
          e.preventDefault();
          var dropdownMenu = this.nextElementSibling;
          if (dropdownMenu.classList.contains('show')) {
            dropdownMenu.classList.remove('show');
            this.setAttribute('aria-expanded', 'false');
          } else {
            // Close any open dropdowns first
            document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
              menu.classList.remove('show');
              menu.previousElementSibling.setAttribute('aria-expanded', 'false');
            });
            
            // Open this dropdown
            dropdownMenu.classList.add('show');
            this.setAttribute('aria-expanded', 'true');
          }
        });
      });
      
      // Close dropdowns when clicking outside
      document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
          document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
            menu.classList.remove('show');
            menu.previousElementSibling.setAttribute('aria-expanded', 'false');
          });
        }
      });
      
      // Rest of the cart functionality
      function loadCartItems() {
        fetch("cart_items.php")
          .then(response => response.json())
          .then(data => {
            let cartItems = document.getElementById("cart-items");
            let cartTotal = document.getElementById("cart-total");

            cartItems.innerHTML = "";
            let totalPrice = 0;

            if (data.items.length === 0) {
              cartItems.innerHTML = '<li class="list-group-item text-center">Your cart is empty.</li>';
            } else {
              data.items.forEach(item => {
                totalPrice += item.price * item.quantity;

                let itemHTML = `
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <img src="/uploads/${item.image}" width="50" height="50" class="rounded">
                    ${item.name} (x${item.quantity})
                    <strong>$${(item.price * item.quantity).toFixed(2)}</strong>
                    <button class="btn btn-danger btn-sm remove-btn" data-product-id="${item.id}">Remove</button>
                  </li>`;
                cartItems.innerHTML += itemHTML;
              });

              // Attach event listeners for remove buttons
              document.querySelectorAll('.remove-btn').forEach(button => {
                button.addEventListener('click', function() {
                  removeFromCart(this.getAttribute('data-product-id'));
                });
              });
            }
            cartTotal.textContent = totalPrice.toFixed(2);
          });
      }

      function removeFromCart(productId) {
        fetch("remove_from_cart.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ product_id: productId })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            loadCartItems();
            updateCartCount();
          }
        });
      }

      function updateCartCount() {
        fetch("cart_count.php")
          .then(response => response.json())
          .then(data => {
            let cartCountElement = document.querySelector(".cart-count");
            if (data.count > 0) {
              if (cartCountElement) {
                cartCountElement.textContent = data.count;
              } else {
                let cartLink = document.querySelector(".nav-link[href='cart.php']");
                if (cartLink) {
                  let badge = document.createElement("span");
                  badge.className = "position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-count";
                  badge.style.fontSize = "0.65rem";
                  badge.style.transform = "translate(-50%, -25%)";
                  badge.textContent = data.count;
                  cartLink.appendChild(badge);
                }
              }
            } else if (cartCountElement) {
              cartCountElement.remove();
            }
          });
      }

      // Initialize cart functionality if user is logged in
      <?php if (isset($_SESSION['customer_id'])): ?>
      // Load cart items when modal is opened
      document.getElementById('cartModal').addEventListener('show.bs.modal', function () {
        loadCartItems();
      });
      <?php endif; ?>
    });
  </script>
</body>
</html>