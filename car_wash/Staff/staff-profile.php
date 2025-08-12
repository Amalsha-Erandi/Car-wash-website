<?php
session_start();
require_once '../Sample/Database_Connection.php';

// Check if staff is logged in
if (!isset($_SESSION['staff_id'])) {
    header("Location: staff-login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$success_message = '';
$error_message = '';

// Get staff details
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->bind_param("i", $_SESSION['staff_id']);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        // Check if email is already taken by another staff
        $check_stmt = $conn->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $_SESSION['staff_id']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = "Email is already taken by another staff member.";
        } else {
            $update_stmt = $conn->prepare("UPDATE staff SET name = ?, email = ?, phone = ? WHERE id = ?");
            $update_stmt->bind_param("sssi", $name, $email, $phone, $_SESSION['staff_id']);
            
            if ($update_stmt->execute()) {
                $success_message = "Profile updated successfully!";
                $_SESSION['staff_name'] = $name;
                
                // Refresh staff details
                $stmt->execute();
                $staff = $stmt->get_result()->fetch_assoc();
            } else {
                $error_message = "Error updating profile.";
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } elseif (!password_verify($current_password, $staff['password_hash'])) {
            $error_message = "Current password is incorrect.";
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $pwd_stmt = $conn->prepare("UPDATE staff SET password_hash = ? WHERE id = ?");
            $pwd_stmt->bind_param("si", $new_password_hash, $_SESSION['staff_id']);
            
            if ($pwd_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password.";
            }
        }
    }
}

$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php include('staff-navbar.php'); ?>

    <div class="container py-4">
        <h1 class="mb-4">My Profile</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($staff['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($staff['phone']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo ucfirst($staff['role']); ?>" readonly>
                            </div>
                            
                            <?php if ($staff['role'] == 'washer'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Shift</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ucfirst($staff['shift']); ?>" readonly>
                                </div>
                                
                                <?php if (!empty($staff['specialization'])): ?>
                                    <div class="mb-3">
                                        <label class="form-label">Specialization</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo htmlspecialchars($staff['specialization']); ?>" readonly>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Change Password -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="bi bi-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 