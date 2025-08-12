<?php
// Start session if needed for cart functionality
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit;
}

require_once 'Database_Connection.php';
$db = new Database();
$conn = $db->getConnection();

$customer_id = $_SESSION['customer_id'];
$recommendations = [];
$customer_vehicles = [];
$error = '';

// Get customer's vehicles
try {
    $stmt = $conn->prepare("SELECT * FROM customer_vehicles WHERE customer_id = ? AND status = 'active'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $customer_vehicles[] = $row;
    }
} catch (Exception $e) {
    $error = "Error fetching vehicles: " . $e->getMessage();
}

// Get recommendations based on vehicle type if selected
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vehicle_id'])) {
    try {
        // Get vehicle type
        $stmt = $conn->prepare("SELECT vehicle_type FROM customer_vehicles WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $_POST['vehicle_id'], $customer_id);
        $stmt->execute();
        $vehicle = $stmt->get_result()->fetch_assoc();

        if ($vehicle) {
            // Get services suitable for the vehicle type
            $stmt = $conn->prepare("SELECT * FROM services WHERE JSON_CONTAINS(vehicle_types, ?) AND status = 'active' ORDER BY price");
            $vehicle_type_json = json_encode($vehicle['vehicle_type']);
            $stmt->bind_param("s", $vehicle_type_json);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $recommendations[] = $row;
            }
        }
    } catch (Exception $e) {
        $error = "Error fetching recommendations: " . $e->getMessage();
    }
}

// Close database connection
$conn->close();
include('customer-navbar.php');

// Define image base path - try multiple possible locations
$possible_paths = [
    "../staff/uploads/products/",
    "../admin/uploads/products/",
    "staff/uploads/products/",
    "admin/uploads/products/",
    "/staff/uploads/products/",
    "/admin/uploads/products/"
];

$image_base_path = "../staff/uploads/products/"; // Default path
$debug_image_info = []; // Store debug info

// Check all possible directories
foreach ($possible_paths as $path) {
    if (is_dir($path)) {
        $image_base_path = $path;
        $debug_image_info[] = "Found directory: " . $path;
        break;
    } else {
        $debug_image_info[] = "Directory not found: " . $path;
    }
}

// Debug folder exists
$debug_image_info[] = "Using base path: " . $image_base_path;
$debug_image_info[] = "Current working directory: " . getcwd();

// Debug flag - set to true to display image debugging info
$debug_images = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Service Recommendations - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/modern-products.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #17a2b8;
      --secondary-color: #6c757d;
      --accent-color: #ffc107;
      --success-color: #28a745;
      --border-radius: 10px;
    }
    
    body {
      background-color: #f8f9fa;
    }
    
    .page-header {
      background-color: #fff;
      border-radius: var(--border-radius);
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      padding: 20px;
      margin-bottom: 25px;
    }
    
    .filter-section {
      background-color: #fff;
      padding: 25px;
      border-radius: var(--border-radius);
      box-shadow: 0 2px 15px rgba(0,0,0,0.05);
      margin-bottom: 30px;
      border-top: 4px solid var(--primary-color);
    }
    
    .filter-title {
      font-weight: 600;
      margin-bottom: 20px;
      color: #212529;
      font-size: 1.25rem;
    }
    
    .filter-row {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .filter-item {
      flex: 1;
      min-width: 200px;
    }
    
    .product-card {
      background-color: #fff;
      border: none;
      border-radius: var(--border-radius);
      box-shadow: 0 3px 10px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      height: 100%;
      overflow: hidden;
    }
    
    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    
    .card-img-container {
      height: 220px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f8f9fa;
    }
    
    .card-img-top {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s;
    }
    
    .product-card:hover .card-img-top {
      transform: scale(1.05);
    }
    
    .card-body {
      padding: 1.25rem;
    }
    
    .card-title {
      font-weight: 600;
      margin-bottom: 0.5rem;
      font-size: 1.15rem;
    }
    
    .price {
      color: var(--primary-color);
      font-weight: 700;
      font-size: 1.2rem;
      margin-bottom: 0.75rem;
    }
    
    .card-text {
      color: #6c757d;
      margin-bottom: 1.25rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .badge {
      font-weight: 500;
      padding: 0.5em 0.75em;
      border-radius: 50px;
      font-size: 0.75rem;
    }
    
    .badge-skin {
      background-color: rgba(23, 162, 184, 0.15);
      color: var(--primary-color);
    }
    
    .badge-bleached {
      background-color: rgba(255, 193, 7, 0.15);
      color: #d6a206;
    }
    
    .badge-pimpled {
      background-color: rgba(220, 53, 69, 0.15);
      color: #dc3545;
    }
    
    .btn-filter {
      padding: 8px 20px;
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      transition: all 0.2s;
    }
    
    .btn-filter:hover {
      background-color: #138496;
      border-color: #117a8b;
    }
    
    .btn-reset {
      padding: 8px 20px;
      color: var(--secondary-color);
      border-color: var(--secondary-color);
    }
    
    .btn-cart {
      padding: 8px 16px;
      border-radius: 50px;
      font-weight: 500;
      transition: all 0.3s;
    }
    
    .btn-cart:hover {
      transform: translateY(-2px);
    }
    
    .no-results {
      padding: 40px;
      text-align: center;
      background-color: #fff;
      border-radius: var(--border-radius);
      box-shadow: 0 2px 15px rgba(0,0,0,0.05);
    }
    
    .filter-active-text {
      background-color: #e9f8fb;
      padding: 15px;
      border-radius: var(--border-radius);
      margin-bottom: 25px;
      border-left: 4px solid var(--primary-color);
    }
    
    .stock-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 2;
      padding: 5px 10px;
      border-radius: 50px;
      font-size: 0.75rem;
      font-weight: 500;
    }
    
    .in-stock {
      background-color: rgba(40, 167, 69, 0.2);
      color: var(--success-color);
    }
    
    .out-stock {
      background-color: rgba(220, 53, 69, 0.2);
      color: #dc3545;
    }
  </style>
</head>
<body>
  <div id="customer-navbar"></div>
  
  <main class="py-5">
    <div class="container">
      <div class="page-header">
        <h1 class="mb-0">Service Recommendations</h1>
      </div>
      
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <?php if (empty($customer_vehicles)): ?>
        <div class="alert alert-info">
            Please add a vehicle to your profile to get personalized service recommendations.
            <a href="customer_profile.php" class="btn btn-primary mt-2">Add Vehicle</a>
        </div>
      <?php else: ?>
        <form method="POST" class="mb-4">
            <div class="form-group">
                <label for="vehicle_id">Select Your Vehicle:</label>
                <select name="vehicle_id" id="vehicle_id" class="form-control" onchange="this.form.submit()">
                    <option value="">Choose a vehicle...</option>
                    <?php foreach ($customer_vehicles as $vehicle): ?>
                        <option value="<?php echo htmlspecialchars($vehicle['id']); ?>"
                                <?php echo (isset($_POST['vehicle_id']) && $_POST['vehicle_id'] == $vehicle['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model'] . ' (' . $vehicle['vehicle_type'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (!empty($recommendations)): ?>
            <div class="row">
                <?php foreach ($recommendations as $service): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if ($service['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($service['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($service['name']); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($service['description']); ?></p>
                                <p class="card-text">
                                    <small class="text-muted">
                                        Duration: <?php echo htmlspecialchars($service['duration']); ?> minutes<br>
                                        Price: LKR <?php echo htmlspecialchars(number_format($service['price'], 2)); ?>
                                    </small>
                                </p>
                                <a href="services.php?service_id=<?php echo $service['id']; ?>" class="btn btn-primary">View Details</a>
                                <form action="add_to_cart.php" method="POST" class="d-inline">
                                    <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                    <button type="submit" class="btn btn-success">Add to Cart</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($_POST['vehicle_id'])): ?>
            <div class="alert alert-info">No specific recommendations found for your vehicle type. Please check our <a href="services.php">services page</a> for all available services.</div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </main>
  
  <?php include('customer-footer.php'); ?>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>