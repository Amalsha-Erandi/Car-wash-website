<?php
session_start();
include('Database_Connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();

    // Check if user exists in the staff table
    $sql = "SELECT * FROM staff WHERE email = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password using password_verify
        if (password_verify($password, $user['password_hash'])) {
            // Check if the user is an admin
            if ($user['role'] == 'admin') {
                $_SESSION['admin_id'] = $user['id'];  
                $_SESSION['admin_name'] = $user['name']; 
                $_SESSION['role'] = $user['role'];

                // Log the activity
                $log_sql = "INSERT INTO activity_logs (staff_id, action, description) VALUES (?, 'login', 'Admin logged in successfully')";
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("i", $user['id']);
                $log_stmt->execute();
                $log_stmt->close();

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "You are not authorized to access this page.";
            }
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "No active user found with that email!";
    }

    $db->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0061f2, #00c6f2);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        .login-card {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
        }
        .login-card h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #0061f2;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo h1 {
            color: #0061f2;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .login-logo p {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        .btn-primary {
            background-color: #0061f2;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056d6;
            transform: translateY(-2px);
        }
        .error-msg {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 5px;
            background-color: rgba(220, 53, 69, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #0061f2;
            box-shadow: 0 0 0 0.2rem rgba(0, 97, 242, 0.25);
        }
        .input-group {
            margin-bottom: 1rem;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
            border-right: none;
        }
        .input-group .form-control {
            border-left: none;
        }
        .input-group .form-control:focus {
            border-color: #ced4da;
            box-shadow: none;
        }
        .input-group:focus-within {
            box-shadow: 0 0 0 0.2rem rgba(0, 97, 242, 0.25);
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="login-logo">
            <h1>Smart Wash</h1>
            <p>Admin Dashboard</p>
        </div>
        
        <?php if (isset($error)) : ?>
            <div class="error-msg">
                <i class="bi bi-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="admin-login.php" class="needs-validation" novalidate>
            <div class="input-group mb-3">
                <span class="input-group-text">
                    <i class="bi bi-envelope"></i>
                </span>
                <input type="email" name="email" class="form-control" required 
                       placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="input-group mb-4">
                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>
                <input type="password" name="password" class="form-control" required 
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Login
            </button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>

