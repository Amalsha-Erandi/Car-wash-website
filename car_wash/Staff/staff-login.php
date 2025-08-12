<?php
session_start();
require_once '../Sample/Database_Connection.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['staff_id'])) {
    header("Location: staff-dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT id, name, password_hash, role FROM staff WHERE email = ? AND status = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $staff = $result->fetch_assoc();
        if (password_verify($password, $staff['password_hash'])) {
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['staff_name'] = $staff['name'];
            $_SESSION['role'] = $staff['role'];
            
            header("Location: staff-dashboard.php");
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Invalid email or account inactive";
    }
    
    $db->closeConnection();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="bi bi-person-circle"></i> Staff Login</h4>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-circle-fill"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 