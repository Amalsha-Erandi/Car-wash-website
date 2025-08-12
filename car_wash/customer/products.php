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

// Get sort parameters
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter_price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$filter_price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 10000;

// Build query for products with filters and sorting
$query = "SELECT * FROM products WHERE status = 'active'";

// Add search condition
if (!empty($search)) {
    $query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}

// Add price range filter
$query .= " AND price BETWEEN $filter_price_min AND $filter_price_max";

// Add sorting
switch ($sort_by) {
    case 'price_asc':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_desc':
        $query .= " ORDER BY price DESC";
        break;
    case 'name_desc':
        $query .= " ORDER BY name DESC";
        break;
    default: // name_asc
        $query .= " ORDER BY name ASC";
}

// Execute the query
try {
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products_result = $stmt->get_result();
    $total_products = $products_result->num_rows;
    
    // Get price range for filter
    $price_range_stmt = $conn->prepare("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE status = 'active'");
    $price_range_stmt->execute();
    $price_range = $price_range_stmt->get_result()->fetch_assoc();
    
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $total_products = 0;
}

// Close database connection
$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Care Products - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <link rel="stylesheet" href="customer.css">
  <link rel="stylesheet" href="modern-products.css">
    <style>
        /* Additional styles for products page */
        .hero-section {
            background-image: url('images/organic-cosmetic-product-with-dreamy-aesthetic-fresh-background.jpg');
            background-size: cover;
            background-position: center;
            height: 300px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 20px;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .search-container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            background: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 250px;
            overflow: hidden;
            background: #f8f9fa;
            position: relative;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
            padding: 10px;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .product-title a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .product-title a:hover {
            color: #0d6efd;
        }
        
        .product-price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 15px;
        }
        
        .product-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
            flex-grow: 1;
        }
        
        .stock-info {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .stock-info i {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        /* Product actions styling is now in modern-products.css */
        
        .filters-sidebar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            margin-bottom: 20px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .filter-group:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        
        .filter-group h4 {
            margin-bottom: 15px;
            padding-bottom: 5px;
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
        }
        
        .sort-options {
            list-style: none;
            padding: 0;
        }
        
        .sort-options li {
            margin-bottom: 10px;
        }
        
        .sort-options a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            transition: color 0.3s;
        }
        
        .sort-options a:hover, .sort-options a.active {
            color: #0d6efd;
        }
        
        .sort-options a i {
            margin-right: 8px;
        }
        
        .price-inputs {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .separator {
            color: #666;
        }
        
        .no-results {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        .no-results-icon {
            font-size: 3rem;
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .results-summary {
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .filters-toggle {
                display: block;
                width: 100%;
                margin-bottom: 15px;
            }
            
            .filters-sidebar {
                display: none;
                margin-bottom: 20px;
            }
            
            .filters-sidebar.show {
                display: block;
            }
        }
        
        /* Media query for product-actions is now in modern-products.css */
    </style>
</head>
<body>
  <?php include('customer-navbar.php'); ?>

  <main>
        <!-- Hero Section -->
        <section class="hero-section" style="background-image: url('images/organic-cosmetic-product-with-dreamy-aesthetic-fresh-background.jpg');">
        <div class="hero-content">
                <h1>Car Care Products</h1>
          <div class="search-container">
            <form action="" method="GET" class="search-form">
              <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search for products..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
              </div>
            </form>
          </div>
        </div>
            <div class="hero-overlay"></div>
    </section>

        <!-- Products Section -->
        <section class="py-5">
          <div class="container-fluid">
            <div class="row">
              <!-- Mobile filters toggle button -->
              <div class="col-12 d-md-none mb-3">
                <button class="btn btn-outline-primary filters-toggle" type="button">
                  <i class="bi bi-funnel"></i> Show Filters
                </button>
              </div>
              
              <!-- Sidebar with filters - Left side -->
              <div class="col-lg-3 col-md-4">
                <div class="filters-sidebar d-md-block">
                  <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="m-0">Filters</h3>
                    <a href="products.php" class="btn btn-sm btn-outline-secondary">Reset All</a>
                  </div>
                  
                  <div class="filter-group">
                    <h4>Sort By</h4>
                    <ul class="sort-options">
                      <li>
                        <a href="?sort=name_asc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="<?php echo $sort_by == 'name_asc' ? 'active' : ''; ?>">
                          <i class="bi bi-sort-alpha-down"></i> Name (A-Z)
                        </a>
                      </li>
                      <li>
                        <a href="?sort=name_desc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="<?php echo $sort_by == 'name_desc' ? 'active' : ''; ?>">
                          <i class="bi bi-sort-alpha-up"></i> Name (Z-A)
                        </a>
                      </li>
                      <li>
                        <a href="?sort=price_asc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="<?php echo $sort_by == 'price_asc' ? 'active' : ''; ?>">
                          <i class="bi bi-sort-numeric-down"></i> Price (Low to High)
                        </a>
                      </li>
                      <li>
                        <a href="?sort=price_desc<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                           class="<?php echo $sort_by == 'price_desc' ? 'active' : ''; ?>">
                          <i class="bi bi-sort-numeric-up"></i> Price (High to Low)
                        </a>
                      </li>
                    </ul>
                  </div>
                  
                  <div class="filter-group">
                    <h4>Price Range</h4>
                    <form action="" method="GET" class="price-range-form">
                      <?php if(!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                      <?php endif; ?>
                      <?php if($sort_by != 'name_asc'): ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                      <?php endif; ?>
                      
                      <div class="price-inputs">
                        <div class="input-group">
                          <span class="input-group-text">LKR</span>
                          <input type="number" name="price_min" id="price-min" min="0" 
                                 value="<?php echo $filter_price_min; ?>" class="form-control">
                        </div>
                        <span class="separator">to</span>
                        <div class="input-group">
                          <span class="input-group-text">LKR</span>
                          <input type="number" name="price_max" id="price-max" 
                                 value="<?php echo $filter_price_max; ?>" class="form-control">
                        </div>
                      </div>
                      
                      <button type="submit" class="btn btn-primary w-100 mt-3">Apply Filter</button>
                    </form>
                  </div>
                </div>
              </div>
              
              <!-- Products Grid - Right side -->
              <div class="col-lg-9 col-md-8">
                <div class="results-summary">
                  <p>Showing <?php echo $total_products; ?> products</p>
                  <?php if (!empty($search)): ?>
                    <div class="alert alert-info">
                      Search results for: "<?php echo htmlspecialchars($search); ?>"
                      <a href="products.php" class="float-end">Clear search</a>
                    </div>
                  <?php endif; ?>
                </div>
            
                <?php if ($total_products > 0): ?>
                  <div class="products-grid">
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                      <div class="product-card">
                        <div class="product-image">
                          <a href="product-details.php?id=<?php echo $product['id']; ?>">
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
                                onerror="this.src='../Sample/uploads/products/placeholder.jpg';">
                          </a>
                        </div>
                        
                        <div class="product-info">
                          <h3 class="product-title">
                            <a href="product-details.php?id=<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                          </h3>
                          
                          <div class="product-price">
                            <span>LKR <?php echo number_format($product['price'], 2); ?></span>
                          </div>
                            
                          <div class="stock-info mb-2">
                            <small class="text-muted">
                                <i class="bi bi-box-seam"></i>
                                Available: <?php echo $product['stock_quantity']; ?> units
                            </small>
                          </div>
                          
                          <p class="product-description">
                            <?php 
                                $desc = $product['description'];
                                echo strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc;
                            ?>
                          </p>
                          
                          <div class="product-actions">
                            <button class="btn btn-primary add-to-cart" 
                                    data-product-id="<?php echo $product['id']; ?>" 
                                    data-product-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                    data-product-price="<?php echo $product['price']; ?>" 
                                    data-product-image="<?php echo htmlspecialchars($product['image_url']); ?>"
                                    data-product-stock="<?php echo $product['stock_quantity']; ?>">
                              <i class="bi bi-cart-plus"></i> Add to Cart
                            </button>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" 
                               class="btn btn-outline-primary">
                              <i class="bi bi-eye"></i> View Details
                            </a>
                          </div>
                        </div>
                      </div>
                    <?php endwhile; ?>
                  </div>
                <?php else: ?>
                  <div class="no-results">
                    <div class="no-results-icon">
                      <i class="bi bi-search"></i>
                    </div>
                    <h2>No Products Found</h2>
                    <p>We couldn't find any products matching your criteria.</p>
                    <a href="products.php" class="btn btn-primary">View All Products</a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
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
    document.addEventListener("DOMContentLoaded", function () {
        // Filter toggle for mobile
        const filtersToggle = document.querySelector('.filters-toggle');
        if (filtersToggle) {
            filtersToggle.addEventListener('click', function() {
                const filtersSidebar = document.querySelector('.filters-sidebar');
                filtersSidebar.classList.toggle('show');
                
                if (filtersSidebar.classList.contains('show')) {
                    this.innerHTML = '<i class="bi bi-funnel-fill"></i> Hide Filters';
                } else {
                    this.innerHTML = '<i class="bi bi-funnel"></i> Show Filters';
                }
            });
        }
        
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const productPrice = this.dataset.productPrice;
                const productImage = this.dataset.productImage;
                const stockQuantity = parseInt(this.dataset.productStock);
                
                // Create quantity selector modal
                const modalHtml = `
                    <div class="modal fade" id="quantityModal_${productId}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Select Quantity</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="quantity-selector d-flex align-items-center justify-content-center gap-3">
                                        <button type="button" class="btn btn-outline-secondary quantity-btn minus">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" class="form-control text-center quantity-input" 
                                               value="1" min="1" max="${stockQuantity}" style="width: 80px" readonly>
                                        <button type="button" class="btn btn-outline-secondary quantity-btn plus">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">Available: ${stockQuantity} units</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary confirm-add">Add to Cart</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Remove existing modal if any
                const existingModal = document.querySelector(`#quantityModal_${productId}`);
                if (existingModal) {
                    existingModal.remove();
                }
                
                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                
                const modal = new bootstrap.Modal(document.querySelector(`#quantityModal_${productId}`));
                const modalElement = document.querySelector(`#quantityModal_${productId}`);
                
                // Setup quantity buttons
                const quantityInput = modalElement.querySelector('.quantity-input');
                const minusBtn = modalElement.querySelector('.minus');
                const plusBtn = modalElement.querySelector('.plus');
                
                minusBtn.addEventListener('click', () => {
                    const currentValue = parseInt(quantityInput.value);
                    if (currentValue > 1) {
                        quantityInput.value = currentValue - 1;
                    }
                    updateButtonStates();
                });
                
                plusBtn.addEventListener('click', () => {
                    const currentValue = parseInt(quantityInput.value);
                    if (currentValue < stockQuantity) {
                        quantityInput.value = currentValue + 1;
                    }
                    updateButtonStates();
                });
                
                function updateButtonStates() {
                    const value = parseInt(quantityInput.value);
                    minusBtn.disabled = value <= 1;
                    plusBtn.disabled = value >= stockQuantity;
                }
                
                // Handle confirm button
                modalElement.querySelector('.confirm-add').addEventListener('click', () => {
                    const quantity = parseInt(quantityInput.value);
                    
                    if (quantity > stockQuantity) {
                        alert("Sorry, we don't have enough stock for this product.");
                        return;
                    }

                    // Add animation
                    this.classList.add('adding');
                    
                    fetch('add_to_cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.remove('adding');
                            this.classList.add('added');
                            setTimeout(() => this.classList.remove('added'), 2000);
                            
                            // Update cart count
                            const cartCountElement = document.querySelector('.cart-count');
                            if (cartCountElement) {
                                cartCountElement.textContent = data.cart_count;
                            }
                            
                            modal.hide();
                            alert(quantity + ' × ' + productName + ' added to cart!');
                        } else {
                            alert('Error: ' + data.message);
                            this.classList.remove('adding');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                        this.classList.remove('adding');
                    });
                });

                // Show modal
                modal.show();
                updateButtonStates();
            });
        });
    });
  </script>
</body>
</html>