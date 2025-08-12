<?php
session_start();
require_once 'Database_Connection.php';

$error = '';
$success = '';
$customer = null;

// Get customer details if logged in
if (isset($_SESSION['customer_id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        $db->closeConnection();
    } catch (Exception $e) {
        error_log("Error fetching customer details: " . $e->getMessage());
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['name']) || !isset($_POST['email']) || !isset($_POST['subject']) || !isset($_POST['message'])) {
            throw new Exception("All fields are required");
        }

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("
            INSERT INTO contact_messages (
                customer_id,
                name,
                email,
                subject,
                message,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
        ");

        $customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
        $stmt->bind_param("issss", $customer_id, $name, $email, $subject, $message);

        if (!$stmt->execute()) {
            throw new Exception("Failed to send message: " . $stmt->error);
        }

        $success = "Your message has been sent successfully! We'll get back to you soon.";
        
        $stmt->close();
        $db->closeConnection();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <style>
        .contact-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .contact-info i {
            font-size: 1.5rem;
            color: #0d6efd;
            margin-right: 1rem;
        }
        .map-container {
            height: 300px;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .map-container iframe {
            width: 100%;
            height: 100%;
            border: 0;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('customer-navbar.php'); ?>

    <div class="container py-5">
        <div class="contact-container">
            <h1 class="text-center mb-4">Contact Us</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Contact Information -->
            <div class="contact-info mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <p><i class="bi bi-geo-alt"></i> 123 Car Wash Street, City</p>
                        <p><i class="bi bi-telephone"></i> +94 123456789</p>
                        <p><i class="bi bi-envelope"></i> info@smartwash.com</p>
                    </div>
                    <div class="col-md-6">
                        <p><i class="bi bi-clock"></i> Mon-Sun: 8:00 AM - 8:00 PM</p>
                        <p><i class="bi bi-calendar-check"></i> Open all days</p>
                    </div>
                </div>
            </div>

            <!-- Google Maps -->
            <div class="map-container">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.798467128219!2d79.97075731477253!3d6.914682895003844!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae256db1a6771c5%3A0x2c63e344ab9a7536!2sSri%20Lanka%20Institute%20of%20Information%20Technology!5e0!3m2!1sen!2slk!4v1659885433251!5m2!1sen!2slk"
                    allowfullscreen="" 
                    loading="lazy">
                </iframe>
            </div>

            <!-- Contact Form -->
            <form method="POST" action="">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo isset($customer) ? htmlspecialchars($customer['name']) : ''; ?>" 
                               <?php echo isset($customer) ? 'readonly' : 'required'; ?>>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo isset($customer) ? htmlspecialchars($customer['email']) : ''; ?>"
                               <?php echo isset($customer) ? 'readonly' : 'required'; ?>>
                    </div>
                    <div class="col-12">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="col-12">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Send Message
                        </button>
                    </div>
                </div>
            </form>
        </div>
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
</body>
</html> 