<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    header("Location: home.php");
    exit;
}

require_once 'Database_Connection.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get form data
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Vehicle information
    $vehicle_type = trim($_POST['vehicle_type']);
    $vehicle_make = trim($_POST['vehicle_make']);
    $vehicle_model = trim($_POST['vehicle_model']);
    $vehicle_year = trim($_POST['vehicle_year']);
    $vehicle_color = trim($_POST['vehicle_color']);
    $license_plate = trim($_POST['license_plate']);
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email address is already registered";
            } else {
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Insert customer
                    $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, password_hash, status, created_at) VALUES (?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)");
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->bind_param("ssss", $name, $email, $phone, $password_hash);
                    $stmt->execute();
                    $customer_id = $stmt->insert_id;
                    
                    // Insert vehicle information
                    $stmt = $conn->prepare("INSERT INTO customer_vehicles (customer_id, vehicle_type, make, model, year, color, license_plate, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");
                    $stmt->bind_param("issssss", $customer_id, $vehicle_type, $vehicle_make, $vehicle_model, $vehicle_year, $vehicle_color, $license_plate);
                    $stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Set success message
                    $success = "Registration successful! Please login to continue.";
                    
                    // Clear form data
                    $_POST = array();
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    throw $e;
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
    $db->closeConnection();
}

// Get vehicle types from database schema
$vehicle_types = array('car', 'suv', 'van', 'bike');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css">
    <style>
        body {
            background-image: url('images/brian-lundquist-dTsSLALhjoc-unsplash.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
        }
        .register-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h1 {
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-weight: 600;
        }
        .register-header p {
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        .form-section:last-child {
            border-bottom: none;
        }
        .section-title {
            margin-bottom: 1.5rem;
            color: #fff;
            font-size: 1.2rem;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        .form-label {
            color: #000;
            font-weight: 500;
            text-shadow: 0 0 1px rgba(255, 255, 255, 0.5);
        }
        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.95);
            border-color: #2FA5EB;
            box-shadow: 0 0 0 0.25rem rgba(47, 165, 235, 0.25);
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #495057;
            z-index: 10;
        }
        .btn-primary {
            background-color: #2FA5EB;
            border-color: #2FA5EB;
            box-shadow: 0 4px 15px rgba(47, 165, 235, 0.3);
            transition: all 0.3s ease;
            padding: 0.8rem;
            font-size: 1.1rem;
        }
        .btn-primary:hover {
            background-color: #2589c7;
            border-color: #2589c7;
            box-shadow: 0 6px 20px rgba(47, 165, 235, 0.4);
            transform: translateY(-2px);
        }
        .text-decoration-none {
            color: rgba(255, 255, 255, 0.9) !important;
            transition: color 0.3s ease;
        }
        .text-decoration-none:hover {
            color: #2FA5EB !important;
        }
        .text-center p {
            color: rgba(255, 255, 255, 0.9);
        }
        .container {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        .bi {
            color: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>
<body>
    <?php include('customer-navbar.php'); ?>

    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>Create Account</h1>
                <p class="text-muted">Join Smart Wash for a spotless car experience</p>
            </div>

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

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                <!-- Personal Information -->
                <div class="form-section">
                    <h2 class="section-title"><i class="bi bi-person-fill"></i> Personal Information</h2>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                            </div>
                            <div class="password-strength mt-2"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                <i class="bi bi-eye-slash password-toggle" id="toggleConfirmPassword"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Information -->
                <div class="form-section">
                    <h2 class="section-title"><i class="bi bi-car-front-fill"></i> Vehicle Information</h2>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type</label>
                            <select class="form-select" id="vehicle_type" name="vehicle_type" required>
                                <option value="">Select vehicle type</option>
                                <?php foreach ($vehicle_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] === $type) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_make" class="form-label">Make</label>
                            <input type="text" class="form-control" id="vehicle_make" name="vehicle_make" value="<?php echo isset($_POST['vehicle_make']) ? htmlspecialchars($_POST['vehicle_make']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="vehicle_model" name="vehicle_model" value="<?php echo isset($_POST['vehicle_model']) ? htmlspecialchars($_POST['vehicle_model']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_year" class="form-label">Year</label>
                            <input type="text" class="form-control" id="vehicle_year" name="vehicle_year" value="<?php echo isset($_POST['vehicle_year']) ? htmlspecialchars($_POST['vehicle_year']) : ''; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="vehicle_color" class="form-label">Color</label>
                            <input type="text" class="form-control" id="vehicle_color" name="vehicle_color" value="<?php echo isset($_POST['vehicle_color']) ? htmlspecialchars($_POST['vehicle_color']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="license_plate" class="form-label">License Plate</label>
                            <input type="text" class="form-control" id="license_plate" name="license_plate" value="<?php echo isset($_POST['license_plate']) ? htmlspecialchars($_POST['license_plate']) : ''; ?>" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">Create Account</button>
            </form>

            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php" class="text-decoration-none">Sign in</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const password = document.getElementById('confirm_password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>