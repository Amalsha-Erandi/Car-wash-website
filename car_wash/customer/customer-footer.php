<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Footer</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/customer.css">
</head>
<style>
  body {
      padding-bottom: 70px; 
    }

  footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    text-align: center;
    background-color: #343a40;
    color: white;
  }
</style>
<body>
    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="mb-3">
                        <i class="bi bi-water me-2"></i>Smart Wash
                    </h5>
                    <p class="text-muted">
                        Your trusted partner for professional car wash services. We provide high-quality cleaning and detailing services for all types of vehicles.
                    </p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-light"><i class="bi bi-whatsapp"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 mb-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="home.php" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> Home
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="services.php" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> Services
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="bookings.php" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> My Bookings
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="reviews.php" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> Reviews
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-3 mb-4">
                    <h5 class="mb-3">Services</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="services.php#exterior" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> Exterior Wash
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="services.php#interior" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> Interior Cleaning
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="services.php#detailing" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> Full Detailing
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="services.php#polish" class="text-muted text-decoration-none">
                                <i class="bi bi-chevron-right"></i> Polish & Wax
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="col-lg-3 mb-4">
                    <h5 class="mb-3">Contact Info</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt text-primary"></i> 123 Car Wash Street, City
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone text-primary"></i> +91 1234567890
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-envelope text-primary"></i> info@smartwash.com
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-clock text-primary"></i> Mon-Sun: 8:00 AM - 8:00 PM
                        </li>
                    </ul>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Smart Wash. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="privacy.php" class="text-muted text-decoration-none me-3">Privacy Policy</a>
                    <a href="terms.php" class="text-muted text-decoration-none me-3">Terms of Service</a>
                    <a href="faq.php" class="text-muted text-decoration-none">FAQ</a>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>