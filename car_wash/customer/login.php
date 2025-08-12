<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['customer_id'])) {
    // Check if there's a redirect parameter
    if (isset($_GET['redirect'])) {
        header("Location: " . $_GET['redirect']);
    } else {
        header("Location: home.php");
    }
    exit;
}

require_once 'Database_Connection.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $db = new Database();
    $conn = $db->getConnection();
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, email, password_hash FROM customers WHERE email = ? AND status = 'active'");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $customer = $result->fetch_assoc();
                
                if (password_verify($password, $customer['password_hash'])) {
                    // Set session variables
                    $_SESSION['customer_id'] = $customer['id'];
                    $_SESSION['customer_name'] = $customer['name'];
                    $_SESSION['customer_email'] = $customer['email'];
                    
                    // Update last login timestamp
                    $update_stmt = $conn->prepare("UPDATE customers SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
                    $update_stmt->bind_param("i", $customer['id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                    
                    // Redirect to home page or specified redirect URL
                    if (isset($_GET['redirect'])) {
                        header("Location: " . $_GET['redirect']);
                    } else {
                        header("Location: home.php");
                    }
                    exit;
                } else {
                    $error = "Invalid email or password";
                }
            } else {
                $error = "Invalid email or password";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
            error_log("Login error: " . $e->getMessage());
        }
    }
    $db->closeConnection();
}

// Store the redirect URL in a hidden field if it exists
$redirect = isset($_GET['redirect']) ? htmlspecialchars($_GET['redirect']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Wash</title>
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
        .login-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h1 {
            color: #fff;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
            font-weight: 600;
        }
        .login-header p {
            color: rgba(255, 255, 255, 0.9);
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
        .form-floating {
            margin-bottom: 1rem;
        }
        .form-floating > .form-control,
        .form-floating > .form-control:focus {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .form-floating > label {
            color: #495057;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #495057;
        }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            font-size: 1.1rem;
            margin-top: 1rem;
            background-color: #2FA5EB;
            border-color: #2FA5EB;
            box-shadow: 0 4px 15px rgba(47, 165, 235, 0.3);
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: #2589c7;
            border-color: #2589c7;
            box-shadow: 0 6px 20px rgba(47, 165, 235, 0.4);
            transform: translateY(-2px);
        }
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: rgba(255, 255, 255, 0.9);
        }
        .divider::before, .divider::after {
            content: "";
            position: absolute;
            top: 50%;
            width: 45%;
            height: 1px;
            background-color: rgba(255, 255, 255, 0.3);
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }
        .divider span {
            background-color: transparent;
            color: rgba(255, 255, 255, 0.9);
            padding: 0 10px;
        }
        .form-check-label {
            color: rgba(255, 255, 255, 0.9);
        }
        .form-check-input {
            background-color: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .form-check-input:checked {
            background-color: #2FA5EB;
            border-color: #2FA5EB;
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
    </style>
</head>

<body>
    <?php include('customer-navbar.php'); ?>

    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1>Welcome Back!</h1>
                <p class="text-muted">Sign in to your Smart Wash account</p>
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

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . ($redirect ? '?redirect=' . $redirect : '')); ?>">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <label for="email">Email address</label>
                </div>

                <div class="form-floating mb-3 position-relative">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <i class="bi bi-eye-slash password-toggle" id="togglePassword"></i>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="text-decoration-none">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </button>
            </form>

            <div class="divider">
                <span class="px-2">or</span>
            </div>

            <div class="text-center">
                <p>Don't have an account? <a href="register.php" class="text-decoration-none">Create one now</a></p>
            </div>
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
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>