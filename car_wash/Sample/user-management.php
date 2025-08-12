<?php
// Start session and include your PHP backend code at the top
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

// Include the database connection class
include('includes/config.php');

// Get database connection using the function from config.php
$conn = getDbConnection();

// Define upload directory for profile images
$profileImagesDir = 'uploads/profile_images/';

// Create directory if it doesn't exist
if (!file_exists($profileImagesDir)) {
    mkdir($profileImagesDir, 0777, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new user
    if (isset($_POST['addUser'])) {
        $firstName = mysqli_real_escape_string($conn, $_POST['userFirstName']);
        $lastName = mysqli_real_escape_string($conn, $_POST['userLastName']);
        $email = mysqli_real_escape_string($conn, $_POST['userEmail']);
        $password = $_POST['userPassword'];
        $confirmPassword = $_POST['confirmPassword'];
        $role = mysqli_real_escape_string($conn, $_POST['userRole']);
        $gender = mysqli_real_escape_string($conn, $_POST['userGender']);
        $contactNumber = mysqli_real_escape_string($conn, $_POST['userContactNumber']);
        $telephone = mysqli_real_escape_string($conn, $_POST['userTelephone']);
        $birthDate = mysqli_real_escape_string($conn, $_POST['userBirthDate']);
        $status = mysqli_real_escape_string($conn, $_POST['userStatus']);
        
        // Handle profile image upload
        $profileImage = null;
        
        if (isset($_FILES['userImage']) && $_FILES['userImage']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $uploadedFile = $_FILES['userImage'];
            
            // Validate file type
            if (in_array($uploadedFile['type'], $allowedTypes)) {
                // Generate unique filename
                $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                $newFileName = 'profile_' . time() . '_' . uniqid() . '.' . $fileExtension;
                $targetFile = $profileImagesDir . $newFileName;
                
                // Move uploaded file
                if (move_uploaded_file($uploadedFile['tmp_name'], $targetFile)) {
                    $profileImage = $newFileName;
                } else {
                    $error = "Error uploading profile image!";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
            }
        }
        
        // Validate input
        if (empty($firstName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = "Please fill in all required fields!";
        } elseif ($password !== $confirmPassword) {
            $error = "Passwords do not match!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address!";
        } else {
            // Check if email already exists
            $checkEmailQuery = "SELECT id FROM users WHERE email = '$email'";
            $checkEmailResult = mysqli_query($conn, $checkEmailQuery);
            
            if (mysqli_num_rows($checkEmailResult) > 0) {
                $error = "Email address already in use!";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $insertQuery = "INSERT INTO users (first_name, last_name, email, password, role, gender, contact_number, telephone, birth_date, profile_image, status) 
                VALUES ('$firstName', '$lastName', '$email', '$hashedPassword', '$role', '$gender', '$contactNumber', '$telephone', " . 
                (!empty($birthDate) ? "'$birthDate'" : "NULL") . ", " . 
                ($profileImage ? "'$profileImage'" : "NULL") . ", '$status')";
                
                if (mysqli_query($conn, $insertQuery)) {
                    $success = "User added successfully!";
                } else {
                    $error = "Error adding user: " . mysqli_error($conn);
                    
                    // Delete uploaded image if database insert fails
                    if ($profileImage && file_exists($profileImagesDir . $profileImage)) {
                        unlink($profileImagesDir . $profileImage);
                    }
                }
            }
        }
    }
    
    // Edit existing user
    if (isset($_POST['editUser'])) {
        $userId = mysqli_real_escape_string($conn, $_POST['userId']);
        $firstName = mysqli_real_escape_string($conn, $_POST['editUserFirstName']);
        $lastName = mysqli_real_escape_string($conn, $_POST['editUserLastName']);
        $email = mysqli_real_escape_string($conn, $_POST['editUserEmail']);
        $password = $_POST['editUserPassword'];
        $confirmPassword = $_POST['editConfirmPassword'];
        $role = mysqli_real_escape_string($conn, $_POST['editUserRole']);
        $gender = mysqli_real_escape_string($conn, $_POST['editUserGender']);
        $contactNumber = mysqli_real_escape_string($conn, $_POST['editUserContactNumber']);
        $telephone = mysqli_real_escape_string($conn, $_POST['editUserTelephone']);
        $birthDate = mysqli_real_escape_string($conn, $_POST['editUserBirthDate']);
        $status = mysqli_real_escape_string($conn, $_POST['editUserStatus']);
        $currentProfileImage = mysqli_real_escape_string($conn, $_POST['currentProfileImage']);
        
        // Handle profile image upload
        $profileImage = $currentProfileImage; // Keep existing image by default
        
        if (isset($_FILES['editUserImage']) && $_FILES['editUserImage']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $uploadedFile = $_FILES['editUserImage'];
            
            // Validate file type
            if (in_array($uploadedFile['type'], $allowedTypes)) {
                // Generate unique filename
                $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
                $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                $targetFile = $profileImagesDir . $newFileName;
                
                // Move uploaded file
                if (move_uploaded_file($uploadedFile['tmp_name'], $targetFile)) {
                    // Delete old profile image if it exists and is not the default
                    if (!empty($currentProfileImage) && file_exists($profileImagesDir . $currentProfileImage)) {
                        unlink($profileImagesDir . $currentProfileImage);
                    }
                    
                    $profileImage = $newFileName;
                } else {
                    $error = "Error uploading profile image!";
                }
            } else {
                $error = "Invalid file type. Only JPG, PNG, and GIF images are allowed.";
            }
        }
        
        // Start building the update query
        $updateQuery = "UPDATE users SET 
                        first_name = '$firstName', 
                        last_name = '$lastName', 
                        email = '$email', 
                        role = '$role', 
                        gender = '$gender',
                        contact_number = '$contactNumber',
                        telephone = '$telephone',
                        birth_date = " . (!empty($birthDate) ? "'$birthDate'" : "NULL") . ",
                        profile_image = " . (!empty($profileImage) ? "'$profileImage'" : "NULL") . ",
                        status = '$status'";
        
        // Add password update if provided
        if (!isset($error) && !empty($password)) {
            // Validate password match
            if ($password !== $confirmPassword) {
                $error = "Passwords do not match!";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateQuery .= ", password = '$hashedPassword'";
            }
        }
        
        // Complete the query
        $updateQuery .= " WHERE id = $userId";
        
        // Execute update if no errors
        if (!isset($error)) {
            if (mysqli_query($conn, $updateQuery)) {
                $success = "User updated successfully!";
            } else {
                $error = "Error updating user: " . mysqli_error($conn);
                
                // Delete newly uploaded image if database update fails
                if ($profileImage != $currentProfileImage && !empty($profileImage) && file_exists($profileImagesDir . $profileImage)) {
                    unlink($profileImagesDir . $profileImage);
                }
            }
        }
    }
    
    // Handle approve/reject action
    if (isset($_POST['confirmAction'])) {
        $userId = mysqli_real_escape_string($conn, $_POST['actionUserId']);
        $actionType = mysqli_real_escape_string($conn, $_POST['actionType']);
        
        $newStatus = ($actionType === 'approve') ? 'active' : 'inactive';
        
        $actionQuery = "UPDATE users SET status = '$newStatus' WHERE id = $userId";
        
        if (mysqli_query($conn, $actionQuery)) {
            $success = "User " . ($actionType === 'approve' ? 'approved' : 'rejected') . " successfully!";
        } else {
            $error = "Error updating user status: " . mysqli_error($conn);
        }
    }
}

// Handle delete operation
if (isset($_GET['delete'])) {
    $userId = mysqli_real_escape_string($conn, $_GET['delete']);
    
    // Don't allow deleting your own account
    if ($userId != $_SESSION['admin_id']) {
        $deleteQuery = "DELETE FROM users WHERE id = $userId";
        
        if (mysqli_query($conn, $deleteQuery)) {
            $success = "User deleted successfully!";
        } else {
            $error = "Error deleting user: " . mysqli_error($conn);
        }
    } else {
        $error = "You cannot delete your own account!";
    }
}

// Handle search and filters
$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? mysqli_real_escape_string($conn, $_GET['role']) : '';
$statusFilter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build the query based on filters
$query = "SELECT * FROM users WHERE 1=1";

if (!empty($searchTerm)) {
    $query .= " AND (first_name LIKE '%$searchTerm%' OR last_name LIKE '%$searchTerm%' OR email LIKE '%$searchTerm%')";
}

if (!empty($roleFilter) && $roleFilter !== 'all') {
    $query .= " AND role = '$roleFilter'";
}

if (!empty($statusFilter) && $statusFilter !== 'all') {
    $query .= " AND status = '$statusFilter'";
}

$query .= " ORDER BY id DESC";

// Pagination
$resultsPerPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startFrom = ($page - 1) * $resultsPerPage;

$countQuery = str_replace("SELECT *", "SELECT COUNT(*) as total", $query);
$countResult = mysqli_query($conn, $countQuery);
$totalRows = mysqli_fetch_assoc($countResult)['total'];
$totalPages = ceil($totalRows / $resultsPerPage);

$query .= " LIMIT $startFrom, $resultsPerPage";

$result = mysqli_query($conn, $query);
if (!$result) {
    die('Error executing query: ' . mysqli_error($conn));
}

$noDataMessage = mysqli_num_rows($result) == 0 ? "No users found matching your criteria." : "";

// Count total users for the dashboard
$totalUsersQuery = "SELECT COUNT(*) as total FROM users";
$totalUsersResult = mysqli_query($conn, $totalUsersQuery);
$totalUsers = mysqli_fetch_assoc($totalUsersResult)['total'];

// Get role options from the database
$rolesQuery = "SELECT DISTINCT role FROM users ORDER BY role";
$rolesResult = mysqli_query($conn, $rolesQuery);
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>

  
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management - Lab Project Administration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <?php include('nav.php'); ?>

  <main class="py-5">
    <div class="container">
      <!-- Welcome Banner -->
      <div class="welcome-banner mb-4">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h1 class="display-5 fw-bold">User Management</h1>
            <p class="lead mb-0">Manage user accounts, roles, and permissions</p>
          </div>
          <div class="col-md-4 text-md-end">
            <div class="d-inline-block">
              <span class="fs-6"><i class="bi bi-people"></i> Total Users: <span id="total-users"><?php echo $totalUsers; ?></span></span>
            </div>
          </div>
        </div>
      </div>

      <!-- Display Messages -->
      <?php if (isset($success)): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php endif; ?>

      <!-- Button to trigger Add User Modal -->
      <div class="mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
          <i class="bi bi-plus"></i> Add New User
        </button>
      </div>

      <!-- Search Bar and Filter Options -->
      <div class="card dashboard-card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">Search & Filter</h5>
          <form class="mb-3" method="GET" action="user-management.php">
            <div class="row g-2">
                <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search by name or email">
                    <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                    </button>
                </div>
                </div>
                <div class="col-md-3">
                <select class="form-select" name="role" onchange="this.form.submit()">
                    <option value="all">All Roles</option>
                    <?php while($roleRow = mysqli_fetch_assoc($rolesResult)): ?>
                    <option value="<?php echo htmlspecialchars($roleRow['role']); ?>" <?php echo ($roleFilter == $roleRow['role']) ? 'selected' : ''; ?>>
                        <?php echo ucfirst(htmlspecialchars($roleRow['role'])); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                </div>
                <div class="col-md-3">
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="all">All Status</option>
                    <option value="active" <?php echo ($statusFilter == 'active') ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($statusFilter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    <option value="pending" <?php echo ($statusFilter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                </select>
                </div>
            </div>
            </form>
        </div>
      </div>

      <!-- User Table -->
      <div class="card dashboard-card user-card">
        <div class="card-header bg-white">
          <h5 class="card-title mb-0">User Accounts</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Gender</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="userTableBody">
                <?php if ($noDataMessage): ?>
                    <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <p class="mt-2 mb-0"><?php echo $noDataMessage; ?></p>
                    </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td>
                            <?php 
                            $profileImagesDir = 'uploads/profile_images/';
                            $profileImagePath = !empty($row['profile_image']) ? 
                                $profileImagesDir . $row['profile_image'] : 
                                'https://via.placeholder.com/40';
                            ?>
                            <img src="<?php echo $profileImagePath; ?>" alt="Profile" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                        </td>
                        <td><?php echo htmlspecialchars($row['first_name'], ENT_QUOTES); ?> <?php echo htmlspecialchars($row['last_name'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?></td>
                        <td>
                        <span class="badge bg-<?php 
                            if ($row['role'] === 'admin') echo 'primary';
                            else if ($row['role'] === 'doctor') echo 'success';
                            else if ($row['role'] === 'nurse') echo 'info';
                            else if ($row['role'] === 'patient') echo 'secondary';
                            else echo 'light text-dark';
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($row['role'], ENT_QUOTES)); ?>
                        </span>
                        </td>
                        <td><?php echo $row['gender'] ? ucfirst(htmlspecialchars($row['gender'], ENT_QUOTES)) : '-'; ?></td>
                        <td>
                        <span class="badge bg-<?php 
                            if ($row['status'] === 'active') echo 'success';
                            else if ($row['status'] === 'pending') echo 'warning text-dark';
                            else echo 'danger';
                        ?>">
                            <?php echo ucfirst(htmlspecialchars($row['status'], ENT_QUOTES)); ?>
                        </span>
                        </td>
                        <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success approve-btn" data-bs-toggle="modal" data-bs-target="#approveRejectModal" 
                                    onclick="setAction('approve', <?php echo $row['id']; ?>)">
                            <i class="bi bi-check-circle"></i> Approve
                            </button>
                            <button class="btn btn-sm btn-danger reject-btn" data-bs-toggle="modal" data-bs-target="#approveRejectModal" 
                                    onclick="setAction('reject', <?php echo $row['id']; ?>)">
                            <i class="bi bi-x-circle"></i> Reject
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editUserModal" 
                                onclick="populateEditForm(<?php echo $row['id']; ?>, 
                                                        '<?php echo htmlspecialchars($row['first_name'], ENT_QUOTES); ?>', 
                                                        '<?php echo htmlspecialchars($row['last_name'], ENT_QUOTES); ?>', 
                                                        '<?php echo htmlspecialchars($row['email'], ENT_QUOTES); ?>', 
                                                        '<?php echo htmlspecialchars($row['role'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['gender'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['contact_number'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['telephone'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['birth_date'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>',
                                                        '<?php echo htmlspecialchars($row['profile_image'], ENT_QUOTES); ?>')">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <?php if ($row['id'] != $_SESSION['admin_id']): ?>
                            <a href="user-management.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger delete-btn">
                            <i class="bi bi-trash"></i> Delete
                            </a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="You cannot delete your own account">
                            <i class="bi bi-trash"></i> Delete
                            </button>
                        <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <nav aria-label="User pagination">
                <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page='.($page-1).'&search='.$searchTerm.'&role='.$roleFilter.'&status='.$statusFilter; ?>">Previous</a>
                </li>
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo $searchTerm; ?>&role=<?php echo $roleFilter; ?>&status=<?php echo $statusFilter; ?>">
                        <?php echo $i; ?>
                    </a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo ($page >= $totalPages) ? '#' : '?page='.($page+1).'&search='.$searchTerm.'&role='.$roleFilter.'&status='.$statusFilter; ?>">Next</a>
                </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
      </div>
    </div>
  </main>


  <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="userFirstName" class="form-label">First Name</label>
                <input type="text" class="form-control" id="userFirstName" name="userFirstName" required>
            </div>
            <div class="mb-3">
                <label for="userLastName" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="userLastName" name="userLastName" required>
            </div>
            <div class="mb-3">
                <label for="userEmail" class="form-label">Email</label>
                <input type="email" class="form-control" id="userEmail" name="userEmail" required>
            </div>
            <div class="mb-3">
                <label for="userPassword" class="form-label">Password</label>
                <input type="password" class="form-control" id="userPassword" name="userPassword" required>
            </div>
            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
            </div>
            <div class="mb-3">
                <label for="userRole" class="form-label">Role</label>
                <select class="form-select" id="userRole" name="userRole" required>
                <option value="admin">Admin</option>
                <option value="doctor">Doctor</option>
                <option value="nurse" selected>Nurse</option>
                <option value="patient">Patient</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="userGender" class="form-label">Gender</label>
                <select class="form-select" id="userGender" name="userGender">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="userContactNumber" class="form-label">Contact Number</label>
                <input type="text" class="form-control" id="userContactNumber" name="userContactNumber">
            </div>
            <div class="mb-3">
                <label for="userTelephone" class="form-label">Telephone</label>
                <input type="text" class="form-control" id="userTelephone" name="userTelephone">
            </div>
            <div class="mb-3">
                <label for="userBirthDate" class="form-label">Birth Date</label>
                <input type="date" class="form-control" id="userBirthDate" name="userBirthDate" max="<?php echo date('Y-m-d'); ?>">
                <div class="form-text">Birth date cannot be in the future</div>
            </div>
            <div class="mb-3">
                <label for="userImage" class="form-label">Profile Image</label>
                <input type="file" class="form-control" id="userImage" name="userImage" accept="image/jpeg, image/png, image/gif">
                <div class="form-text">Accepted formats: JPG, PNG, GIF. Max size: 2MB.</div>
            </div>
            <div class="mb-3">
                <label for="userStatus" class="form-label">Status</label>
                <select class="form-select" id="userStatus" name="userStatus" required>
                <option value="active" selected>Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
                </select>
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" name="addUser">Save User</button>
            </div>
            </form>
        </div>
        </div>
    </div>
    </div>

  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" id="userId" name="userId">
            <input type="hidden" id="currentProfileImage" name="currentProfileImage">
            <div class="row mb-3">
              <div class="col-md-8">
            <div class="mb-3">
                  <label for="editUserFirstName" class="form-label">First Name</label>
                  <input type="text" class="form-control" id="editUserFirstName" name="editUserFirstName" required>
                </div>
                <div class="mb-3">
                  <label for="editUserLastName" class="form-label">Last Name</label>
                  <input type="text" class="form-control" id="editUserLastName" name="editUserLastName" required>
            </div>
            <div class="mb-3">
              <label for="editUserEmail" class="form-label">Email</label>
                  <input type="email" class="form-control" id="editUserEmail" name="editUserEmail" required>
                </div>
              </div>
              <div class="col-md-4 text-center">
                <div id="editProfileImageContainer" class="mb-3">
                  <img id="editProfileImagePreview" src="https://via.placeholder.com/100" alt="Profile" class="img-thumbnail rounded-circle mb-2" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <div class="mb-3">
                  <label for="editUserImage" class="form-label">Change Image</label>
                  <input type="file" class="form-control form-control-sm" id="editUserImage" name="editUserImage" accept="image/jpeg, image/png, image/gif">
                </div>
              </div>
            </div>
            <div class="mb-3">
              <label for="editUserPassword" class="form-label">Password (leave blank to keep current)</label>
              <input type="password" class="form-control" id="editUserPassword" name="editUserPassword">
            </div>
            <div class="mb-3">
              <label for="editConfirmPassword" class="form-label">Confirm Password</label>
              <input type="password" class="form-control" id="editConfirmPassword" name="editConfirmPassword">
            </div>
            <div class="mb-3">
              <label for="editUserRole" class="form-label">Role</label>
              <select class="form-select" id="editUserRole" name="editUserRole" required>
                <option value="admin">Admin</option>
                <option value="doctor">Doctor</option>
                <option value="nurse">Nurse</option>
                <option value="patient">Patient</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="editUserGender" class="form-label">Gender</label>
              <select class="form-select" id="editUserGender" name="editUserGender">
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="editUserContactNumber" class="form-label">Contact Number</label>
              <input type="text" class="form-control" id="editUserContactNumber" name="editUserContactNumber">
            </div>
            <div class="mb-3">
              <label for="editUserTelephone" class="form-label">Telephone</label>
              <input type="text" class="form-control" id="editUserTelephone" name="editUserTelephone">
            </div>
            <div class="mb-3">
              <label for="editUserBirthDate" class="form-label">Birth Date</label>
              <input type="date" class="form-control" id="editUserBirthDate" name="editUserBirthDate" max="<?php echo date('Y-m-d'); ?>">
              <div class="form-text">Birth date cannot be in the future</div>
            </div>
            <div class="mb-3">
              <label for="editUserStatus" class="form-label">Status</label>
              <select class="form-select" id="editUserStatus" name="editUserStatus" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
              </select>
            </div>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-warning" name="editUser">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Approve/Reject User Modal -->
  <div class="modal fade" id="approveRejectModal" tabindex="-1" aria-labelledby="approveRejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-info text-white">
          <h5 class="modal-title" id="approveRejectModalLabel">Approve or Reject User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST">
            <input type="hidden" id="actionUserId" name="actionUserId">
            <input type="hidden" id="actionType" name="actionType">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <span id="actionMessage">Are you sure you want to approve/reject this user?</span>
          </div>
          <div class="d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" id="confirmActionBtn" class="btn btn-success" name="confirmAction">Confirm</button>
          </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <?php include('footer.php'); ?>
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Example dynamic actions for buttons and modals
    function setAction(action, userId) {
      const actionMessage = action === 'approve' ? 'Are you sure you want to approve this user?' : 'Are you sure you want to reject this user?';
      document.getElementById('actionMessage').innerText = actionMessage;
      
      // Set hidden form values
      document.getElementById('actionUserId').value = userId;
      document.getElementById('actionType').value = action;
      
      // Change confirm button color based on action
      const confirmBtn = document.getElementById('confirmActionBtn');
      if (action === 'approve') {
        confirmBtn.classList.remove('btn-danger');
        confirmBtn.classList.add('btn-success');
        confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Approve';
      } else {
        confirmBtn.classList.remove('btn-success');
        confirmBtn.classList.add('btn-danger');
        confirmBtn.innerHTML = '<i class="bi bi-x-circle"></i> Reject';
      }
    }

    function confirmAction() {
      // Implement confirm action (approve/reject logic)
      alert('Action confirmed!');
      // Close modal after action
      const modal = bootstrap.Modal.getInstance(document.getElementById('approveRejectModal'));
      modal.hide();
      
      // Show success toast notification
      showToast('User status updated successfully!', 'success');
    }

    function editUser(userId) {
      // Fetch and pre-fill the user details for editing
      let userData = {
        1: { name: 'John Doe', email: 'john@labproject.com', role: 'patient' },
        2: { name: 'Jane Smith', email: 'jane@labproject.com', role: 'provider' },
        3: { name: 'Robert Johnson', email: 'robert@labproject.com', role: 'admin' }
      };
      
      let user = userData[userId];
      
      document.getElementById('editUserFirstName').value = user.name.split(' ')[0];
      document.getElementById('editUserLastName').value = user.name.split(' ')[1] || '';
      document.getElementById('editUserEmail').value = user.email;
      document.getElementById('editUserRole').value = user.role;
    }
    
    // Function to show toast notifications
    function showToast(message, type = 'info') {
      // Create toast container if it doesn't exist
      let toastContainer = document.querySelector('.toast-container');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
      }
      
      // Create toast element
      const toastEl = document.createElement('div');
      toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
      toastEl.setAttribute('role', 'alert');
      toastEl.setAttribute('aria-live', 'assertive');
      toastEl.setAttribute('aria-atomic', 'true');
      
      // Create toast content
      toastEl.innerHTML = `
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-triangle' : 'info-circle'}"></i>
            ${message}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      `;
      
      // Add toast to container
      toastContainer.appendChild(toastEl);
      
      // Initialize and show toast
      const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
      toast.show();
      
      // Remove toast after it's hidden
      toastEl.addEventListener('hidden.bs.toast', function() {
        toastEl.remove();
      });
    }
    
    // Search functionality
    document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.getElementById('searchInput');
      const roleFilter = document.getElementById('roleFilter');
      const statusFilter = document.getElementById('statusFilter');
      
      if (searchInput) {
        searchInput.addEventListener('keyup', filterUsers);
      }
      
      if (roleFilter) {
        roleFilter.addEventListener('change', filterUsers);
      }
      
      if (statusFilter) {
        statusFilter.addEventListener('change', filterUsers);
      }
      
      function filterUsers() {
        // This would be replaced with actual filtering logic in a real application
        console.log('Filtering users with:');
        console.log('Search:', searchInput.value);
        console.log('Role:', roleFilter.value);
        console.log('Status:', statusFilter.value);
        
        // Example of showing a message when no results are found
        if (searchInput.value.length > 0 && searchInput.value.toLowerCase() === 'noresults') {
          document.getElementById('userTableBody').innerHTML = `
            <tr>
              <td colspan="8" class="text-center py-4">
                <i class="bi bi-search fs-1 text-muted"></i>
                <p class="mt-2 mb-0">No users found matching your search criteria.</p>
              </td>
            </tr>
          `;
        }
      }
    });

    // Set max date for birth date inputs to today
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        
        // For Add User form
        const userBirthDate = document.getElementById('userBirthDate');
        if (userBirthDate) {
            userBirthDate.max = today;
            userBirthDate.addEventListener('input', function() {
                if (this.value > today) {
                    this.value = today;
                    alert('Birth date cannot be in the future');
                }
            });
        }
        
        // For Edit User form
        const editUserBirthDate = document.getElementById('editUserBirthDate');
        if (editUserBirthDate) {
            editUserBirthDate.max = today;
            editUserBirthDate.addEventListener('input', function() {
                if (this.value > today) {
                    this.value = today;
                    alert('Birth date cannot be in the future');
                }
            });
        }
    });
  </script>
</body>
</html>

<script>
function populateEditForm(id, firstName, lastName, email, role, gender, contactNumber, telephone, birthDate, status, currentProfileImage) {
    document.getElementById('userId').value = id;
    document.getElementById('editUserFirstName').value = firstName;
    document.getElementById('editUserLastName').value = lastName;
    document.getElementById('editUserEmail').value = email;
    document.getElementById('editUserRole').value = role;
    document.getElementById('editUserGender').value = gender || '';
    document.getElementById('editUserContactNumber').value = contactNumber || '';
    document.getElementById('editUserTelephone').value = telephone || '';
    document.getElementById('editUserBirthDate').value = birthDate || '';
    document.getElementById('editUserStatus').value = status;
    document.getElementById('currentProfileImage').value = currentProfileImage || '';
    
    // Update profile image preview
    const profileImagesDir = 'uploads/profile_images/';
    const profileImagePreview = document.getElementById('editProfileImagePreview');
    
    if (currentProfileImage) {
        profileImagePreview.src = profileImagesDir + currentProfileImage;
    } else {
        profileImagePreview.src = 'https://via.placeholder.com/100';
    }
    
    // Clear password fields
    document.getElementById('editUserPassword').value = '';
    document.getElementById('editConfirmPassword').value = '';
}
</script>

