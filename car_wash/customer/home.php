<?php
session_start();
require_once 'Database_Connection.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection(); 

// Check if user is logged in
$isLoggedIn = isset($_SESSION['customer_id']);
$vehicle = null;

// Fetch services from the database
try {
    $stmt = $conn->prepare("SELECT * FROM services WHERE status = 'active' ORDER BY price ASC");
    $stmt->execute();
    $services = $stmt->get_result();

    // Fetch user's vehicle information if logged in
    if ($isLoggedIn) {
        $stmt = $conn->prepare("SELECT * FROM customer_vehicles WHERE customer_id = ? AND status = 'active' LIMIT 1");
        $stmt->bind_param("i", $_SESSION['customer_id']);
        $stmt->execute();
        $vehicle = $stmt->get_result()->fetch_assoc();
    }

} catch (Exception $e) {
    error_log("Error fetching data: " . $e->getMessage());
}

// Close database connection
$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
  <link rel="stylesheet" href="customer.css">
  <link rel="stylesheet" href="modern-home.css">
</head>
<body>
  <?php include('customer-navbar.php'); ?>

  <main>
    <!-- Hero Section -->
        <section class="hero-section" style="background-image: url('images/close-up-car-care-washing.jpg');">
      <div class="hero-content">
                <h1 class="animate__animated animate__fadeInDown">Welcome to Smart Wash</h1>
                <p class="animate__animated animate__fadeInUp animate__delay-1s">Professional Car Wash & Detailing Services</p>
                <?php if ($isLoggedIn): ?>
                <a href="#services" class="btn btn-primary btn-lg animate__animated animate__fadeInUp animate__delay-2s">View Services</a>
                <?php else: ?>
                <div class="animate__animated animate__fadeInUp animate__delay-2s">
                    <a href="register.php" class="btn btn-primary btn-lg me-2">Join Now</a>
                    <a href="#services" class="btn btn-outline-light btn-lg">Explore Services</a>
                </div>
                <?php endif; ?>
      </div>
      <div class="hero-overlay"></div>
    </section>

    <!-- Benefits Section -->
        <section class="benefits-section py-5">
      <div class="container">
        <div class="row g-4">
          <div class="col-md-4">
                        <div class="benefit-card text-center p-4 bg-white rounded shadow-sm">
                            <i class="bi bi-droplet-fill text-primary fs-1"></i>
                            <h3 class="mt-3">Eco-Friendly</h3>
                            <p>We use environmentally safe cleaning products</p>
            </div>
          </div>
          <div class="col-md-4">
                        <div class="benefit-card text-center p-4 bg-white rounded shadow-sm">
                            <i class="bi bi-clock-fill text-primary fs-1"></i>
                            <h3 class="mt-3">Quick Service</h3>
                            <p>Most services completed in under an hour</p>
            </div>
          </div>
          <div class="col-md-4">
                        <div class="benefit-card text-center p-4 bg-white rounded shadow-sm">
                            <i class="bi bi-shield-check text-primary fs-1"></i>
                            <h3 class="mt-3">Quality Guarantee</h3>
              <p>100% satisfaction or your money back</p>
            </div>
          </div>
        </div>
      </div>
    </section>

        <!-- Your Vehicle Section -->
        <?php if ($isLoggedIn && $vehicle): ?>
        <section class="vehicle-section py-5 bg-light">
      <div class="container">
                <h2 class="section-title text-center mb-4">Your Vehicle</h2>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5 class="card-title"><?php echo ucfirst($vehicle['make']) . ' ' . ucfirst($vehicle['model']); ?></h5>
                                        <p class="card-text">
                                            <strong>Type:</strong> <?php echo ucfirst($vehicle['vehicle_type']); ?><br>
                                            <strong>Year:</strong> <?php echo $vehicle['year']; ?><br>
                                            <strong>Color:</strong> <?php echo ucfirst($vehicle['color']); ?><br>
                                            <strong>License Plate:</strong> <?php echo strtoupper($vehicle['license_plate']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <a href="customer_profile.php" class="btn btn-outline-primary">Manage Vehicles</a>
                                    </div>
                                </div>
                  </div>
                </div>
                  </div>
                </div>
            </div>
        </section>
        <?php elseif (!$isLoggedIn): ?>
        <section class="visitor-section py-5 bg-light">
            <div class="container">
                <h2 class="section-title text-center mb-4">Join Car Medic Today</h2>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body text-center p-4">
                                <h5 class="card-title mb-3">Create an account to enjoy these benefits:</h5>
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="feature-item">
                                            <i class="bi bi-calendar-check text-primary fs-1"></i>
                                            <h6 class="mt-2">Easy Booking</h6>
                                            <p class="small">Schedule services with just a few clicks</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="feature-item">
                                            <i class="bi bi-clock-history text-primary fs-1"></i>
                                            <h6 class="mt-2">Service History</h6>
                                            <p class="small">Track all your previous services</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="feature-item">
                                            <i class="bi bi-tag text-primary fs-1"></i>
                                            <h6 class="mt-2">Special Offers</h6>
                                            <p class="small">Access exclusive deals and promotions</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="register.php" class="btn btn-primary">Register Now</a>
                                    <a href="login.php" class="btn btn-outline-primary">Login</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Services Section -->
        <section id="services" class="services-section py-5">
            <div class="container">
                <h2 class="section-title text-center mb-4">Our Services</h2>
                <div class="row g-4">
                    <?php while ($service = $services->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="service-card card h-100">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($service['name']); ?></h3>
                                <p class="card-text"><?php echo htmlspecialchars($service['description']); ?></p>
                                <div class="service-details">
                                    <div class="price">$<?php echo number_format($service['price'], 2); ?></div>
                                    <div class="duration"><i class="bi bi-clock"></i> <?php echo $service['duration']; ?> mins</div>
                                </div>
                                <?php if ($isLoggedIn): ?>
                                <a href="services.php?id=<?php echo $service['id']; ?>" class="btn btn-primary mt-3">Book Now</a>
                                <?php else: ?>
                                <a href="login.php?redirect=services.php?id=<?php echo $service['id']; ?>" class="btn btn-primary mt-3">Login to Book</a>
                                <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
        <section class="testimonials-section py-5 bg-light">
      <div class="container">
                <h2 class="section-title text-center mb-4">What Our Customers Say</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="testimonial-card card h-100">
                            <div class="card-body">
                                <p class="testimonial-text">"Smart Wash provides excellent service! My car looks brand new every time."</p>
              <div class="testimonial-author">
                                    <div class="author-rating text-warning">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                </div>
                <div class="author-name">Sarah Johnson</div>
              </div>
            </div>
          </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimonial-card card h-100">
                            <div class="card-body">
                                <p class="testimonial-text">"Professional staff and great attention to detail. Highly recommended!"</p>
              <div class="testimonial-author">
                                    <div class="author-rating text-warning">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-half"></i>
                </div>
                <div class="author-name">Michael Rodriguez</div>
              </div>
            </div>
          </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimonial-card card h-100">
                            <div class="card-body">
                                <p class="testimonial-text">"Quick service and amazing results. Best car wash in town!"</p>
              <div class="testimonial-author">
                                    <div class="author-rating text-warning">
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                  <i class="bi bi-star-fill"></i>
                </div>
                <div class="author-name">Emily Chen</div>
              </div>
            </div>
          </div>
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
</body>
</html>