<?php
session_start();
require_once 'Database_Connection.php';

$services = [];
$error = null;

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get all active services
    $stmt = $conn->prepare("SELECT * FROM services WHERE status = 'active' ORDER BY price ASC");
    $stmt->execute();
    $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $stmt->close();
    $db->closeConnection();
    
} catch (Exception $e) {
    error_log("Services error: " . $e->getMessage());
    $error = "An error occurred while loading services. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <style>
        .service-card {
            transition: all 0.3s ease;
            height: 100%;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .service-icon {
            font-size: 2.5rem;
            color: #0d6efd;
        }
        .vehicle-type-badge {
            font-size: 0.8rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        .service-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        .service-duration {
            color: #6c757d;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include('customer-navbar.php'); ?>

    <!-- Hero Section -->
    <div style="background-image: url('images/philipp-katzenberger-_DSom3ySpow-unsplash.jpg'); background-size: cover; background-position: center; position: relative; color: white; padding: 3rem 0;">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);"></div>
        <div class="container" style="position: relative; z-index: 1;">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-3">Professional Car Wash Services</h1>
                    <p class="lead mb-4">Experience the finest car wash services with our expert team and premium products. We take pride in making your vehicle shine!</p>
                    <a href="#services" class="btn btn-light btn-lg">
                        <i class="bi bi-arrow-down-circle"></i> View Services
                    </a>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-water display-1"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Services -->
    <section class="py-5" id="services">
        <div class="container">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($services as $service): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card service-card">
                                <?php if ($service['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($service['image_url']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($service['name']); ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="text-center mb-4">
                                        <i class="bi bi-water service-icon"></i>
                                        <h3 class="h4 mt-3"><?php echo htmlspecialchars($service['name']); ?></h3>
                                    </div>
                                    
                                    <p class="text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                                    
                                    <div class="mb-3">
                                        <h6 class="mb-2">Available for:</h6>
                                        <?php 
                                        $vehicle_types = json_decode($service['vehicle_types'], true);
                                        if (is_array($vehicle_types)) {
                                            foreach ($vehicle_types as $type) {
                                                echo '<span class="badge bg-primary vehicle-type-badge">' . ucfirst($type) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="service-price">LKR <?php echo number_format($service['price'], 2); ?></div>
                                        <div class="service-duration">
                                            <i class="bi bi-clock"></i> <?php echo $service['duration']; ?> minutes
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($_SESSION['customer_id'])): ?>
                                        <a href="book_service.php?id=<?php echo $service['id']; ?>" class="btn btn-primary w-100">
                                            <i class="bi bi-calendar-plus"></i> Book Now
                                        </a>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-outline-primary w-100">
                                            <i class="bi bi-box-arrow-in-right"></i> Login to Book
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Smart Wash?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-droplet-fill text-primary display-4"></i>
                        <h3 class="h4 mt-3">Eco-Friendly Products</h3>
                        <p>We use environmentally safe cleaning products that are tough on dirt but gentle on your car's finish.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-people-fill text-primary display-4"></i>
                        <h3 class="h4 mt-3">Expert Team</h3>
                        <p>Our professional team is trained to deliver the highest quality service for all types of vehicles.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <i class="bi bi-shield-check text-primary display-4"></i>
                        <h3 class="h4 mt-3">Satisfaction Guaranteed</h3>
                        <p>We stand behind our work with a 100% satisfaction guarantee on all our services.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

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