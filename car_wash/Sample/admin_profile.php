<?php
// admin_profile.php - Improved Admin profile management page

// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'Database_Connection.php'; // Fixed spellingDatabase_Conection
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin-login.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$admin_id = $_SESSION['admin_id'];
$success_message = '';
$error_message = '';

// Get admin details
$stmt = $conn->prepare("SELECT * FROM staff WHERE id = ? AND role = 'admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: login.php');
    exit();
}

$admin = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Handle profile image upload
    $profile_image_path = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($_FILES['profile_image']['type'], $allowed_types) && $_FILES['profile_image']['size'] <= $max_size) {
            $upload_dir = __DIR__ . '/uploads/profile_images/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'admin_' . $admin_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            // Store relative path in database for display
            $db_image_path = 'uploads/profile_images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_image_path = $db_image_path;
                
                // Delete old profile image if exists
                if (!empty($admin['profile_image']) && file_exists(__DIR__ . '/' . $admin['profile_image'])) {
                    unlink(__DIR__ . '/' . $admin['profile_image']);
                }
            } else {
                $error_message = "Failed to upload profile image.";
            }
        } else {
            $error_message = "Invalid image file. Please upload a JPEG, PNG, or GIF file under 5MB.";
        }
    }

    // Get current admin data
    $stmt = $conn->prepare("SELECT password_hash FROM staff WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin_data = $result->fetch_assoc();

    if (password_verify($current_password, $admin_data['password_hash'])) {
        // Update name and email
        $update_query = "UPDATE staff SET name = ?, email = ?";
        $params = array($name, $email);
        $types = "ss";

        // If profile image was uploaded
        if ($profile_image_path) {
            $update_query .= ", profile_image = ?";
            $params[] = $profile_image_path;
            $types .= "s";
        }

        // If new password is provided
        if (!empty($new_password)) {
            if ($new_password == $confirm_password) {
                $update_query .= ", password_hash = ?";
                $params[] = password_hash($new_password, PASSWORD_BCRYPT);
                $types .= "s";
            } else {
                $error_message = "New passwords do not match!";
            }
        }

        $update_query .= " WHERE id = ?";
        $params[] = $admin_id;
        $types .= "i";

        if (empty($error_message)) {
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $_SESSION['admin_name'] = $name;
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Error updating profile. Please try again.";
            }
        }
    } else {
        $error_message = "Current password is incorrect!";
    }
}

// Helper function to log admin actions
function logAdminAction($conn, $staff_id, $action, $details) {
    $log_stmt = $conn->prepare("INSERT INTO admin_logs (staff_id, action, details) VALUES (?, ?, ?)");
    if ($log_stmt === false) {
        // Handle the prepare error - just log to error log since this is not critical
        error_log("Failed to prepare log statement: " . $conn->error);
        return false;
    }
    $log_stmt->bind_param("iss", $staff_id, $action, $details);
    $result = $log_stmt->execute();
    $log_stmt->close();
    return $result;
}

// Get last login time (if you have a login_logs table)
$last_login = "Not available";
try {
    // Check if login_logs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'login_logs'");
    if ($table_check && $table_check->num_rows > 0) {
        $login_query = $conn->prepare("SELECT login_time FROM login_logs WHERE staff_id = ? ORDER BY login_time DESC LIMIT 1");
        if ($login_query) {
            $login_query->bind_param("i", $admin_id);
            $login_query->execute();
            $login_result = $login_query->get_result();
            if ($login_result && $login_result->num_rows > 0) {
                $login_data = $login_result->fetch_assoc();
                $last_login = date('F j, Y, g:i a', strtotime($login_data['login_time']));
            }
            $login_query->close();
        }
    }
} catch (Exception $e) {
    // If there's an error getting the last login time, just use the default
    error_log("Error getting last login time: " . $e->getMessage());
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin-bottom: 1rem;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: #6c757d;
            margin: 0 auto;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-image-container {
            position: relative;
            display: inline-block;
        }
        .form-label {
            font-weight: 500;
        }
        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('admin-navbar.php'); ?>

    <main class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="profile-section">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="profile-header">
                                <div class="profile-image-container mb-3">
                                    <?php if (!empty($admin['profile_image']) && file_exists(__DIR__ . '/' . $admin['profile_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($admin['profile_image']); ?>" 
                                             alt="Profile Image" 
                                             class="profile-image rounded-circle"
                                             id="profileImagePreview">
                                    <?php else: ?>
                                        <div class="profile-avatar" id="profileImagePreview">
                                            <i class="bi bi-person"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <input type="file" class="form-control" id="profile_image" 
                                           name="profile_image" accept="image/*" 
                                           onchange="previewImage(this)">
                                    <div class="form-text">Upload JPG, PNG or GIF image (max 5MB)</div>
                                </div>
                                <h2><?php echo htmlspecialchars($admin['name']); ?></h2>
                                <p class="text-muted mb-0">Administrator</p>
                            </div>
                        
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

                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                            </div>
                                    
                                    <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                                    </div>
                                    
                                    <hr class="my-4">
                            <h5 class="mb-3">Change Password</h5>
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" 
                                       name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" 
                                       name="new_password">
                                <small class="text-muted">Leave blank if you don't want to change the password</small>
                                    </div>
                                    
                            <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" 
                                       name="confirm_password">
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

    <?php include('admin-footer.php'); ?>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('profileImagePreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview.tagName.toLowerCase() === 'div') {
                        // Replace the div with an img element
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Profile Image';
                        img.className = 'profile-image rounded-circle';
                        img.id = 'profileImagePreview';
                        preview.parentNode.replaceChild(img, preview);
                    } else {
                        // Update existing img src
                        preview.src = e.target.result;
                    }
                };
                
                reader.readAsDataURL(file);
            }
        }

        // File size validation
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = this.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (file && file.size > maxSize) {
                alert('File size must be less than 5MB');
                this.value = ''; // Clear the input
                return false;
            }
            
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (file && !allowedTypes.includes(file.type)) {
                alert('Please upload a valid image file (JPG, PNG, or GIF)');
                this.value = '';
                return false;
            }
        });
    </script>
</body>
</html>
