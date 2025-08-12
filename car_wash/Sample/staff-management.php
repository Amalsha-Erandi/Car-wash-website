<?php
// Start session at the beginning
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

// Include the database connection class
include('Database_Connection.php');

$db = new Database();
$conn = $db->getConnection(); // Get the database connection

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_staff'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];
        $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $shift = $_POST['shift'];
        $specialization = $_POST['specialization'];

        $stmt = $conn->prepare("INSERT INTO staff (name, email, phone, role, password_hash, shift, specialization, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->bind_param("sssssss", $name, $email, $phone, $role, $password_hash, $shift, $specialization);

        if ($stmt->execute()) {
            $success_message = "Staff member added successfully!";
        } else {
            $error_message = "Error adding staff member: " . $conn->error;
        }
    } elseif (isset($_POST['update_staff'])) {
        $id = $_POST['staff_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $role = $_POST['role'];
        $shift = $_POST['shift'];
        $specialization = $_POST['specialization'];
        $status = $_POST['status'];

        if (!empty($_POST['password'])) {
            $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE staff SET name = ?, email = ?, phone = ?, role = ?, 
                                   password_hash = ?, shift = ?, specialization = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssssssssi", $name, $email, $phone, $role, $password_hash, $shift, $specialization, $status, $id);
        } else {
            $stmt = $conn->prepare("UPDATE staff SET name = ?, email = ?, phone = ?, role = ?, 
                                   shift = ?, specialization = ?, status = ? WHERE id = ?");
            $stmt->bind_param("sssssssi", $name, $email, $phone, $role, $shift, $specialization, $status, $id);
        }

        if ($stmt->execute()) {
            $success_message = "Staff member updated successfully!";
        } else {
            $error_message = "Error updating staff member: " . $conn->error;
        }
    } elseif (isset($_POST['delete_staff'])) {
        $id = $_POST['staff_id'];

        // Check if staff member has any assigned bookings
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE staff_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            // Staff has bookings, just mark as inactive
            $stmt = $conn->prepare("UPDATE staff SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Staff member marked as inactive as they have assigned bookings.";
            } else {
                $error_message = "Error updating staff status: " . $conn->error;
            }
        } else {
            // Staff has no bookings, safe to delete
            $stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $success_message = "Staff member deleted successfully!";
            } else {
                $error_message = "Error deleting staff member: " . $conn->error;
            }
        }
    }
}

// Fetch staff data
$staff_query = "SELECT * FROM staff ORDER BY role, name";
$staff_result = mysqli_query($conn, $staff_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Staff Management - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="admin-styles.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css" rel="stylesheet">
  <style>
    .staff-card {
        transition: transform 0.3s;
    }
    .staff-card:hover {
        transform: translateY(-5px);
    }
    .status-badge {
        position: absolute;
        top: 10px;
        right: 10px;
    }
    .role-badge {
        font-size: 0.875rem;
    }
    .shift-badge {
        font-size: 0.875rem;
        color: #0d6efd;
    }
  </style>
</head>
<body class="bg-light">

<?php include('admin-navbar.php'); ?>

<main class="py-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Staff Management</h1>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
            <i class="bi bi-plus-lg"></i> Add New Staff
        </button>
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

      <div class="row g-4">
        <?php
        if ($staff_result && mysqli_num_rows($staff_result) > 0) {
            while ($staff = mysqli_fetch_assoc($staff_result)) {
                $statusClass = 'secondary';
                switch ($staff['status']) {
                    case 'active':
                        $statusClass = 'success';
                        break;
                    case 'inactive':
                        $statusClass = 'danger';
                        break;
                }

                $roleClass = 'info';
                switch ($staff['role']) {
                    case 'admin':
                        $roleClass = 'danger';
                        break;
                    case 'manager':
                        $roleClass = 'warning';
                        break;
                    case 'washer':
                        $roleClass = 'primary';
                        break;
                }
                ?>
                <div class="col-md-4">
                    <div class="card card-hover h-100">
                        <div class="card-body">
                            <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                <?php echo ucfirst($staff['status']); ?>
                            </span>
                            <h5 class="card-title mb-3"><?php echo htmlspecialchars($staff['name']); ?></h5>
                            <div class="mb-3">
                                <div class="text-muted mb-1">
                                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($staff['email']); ?>
                                </div>
                                <div class="text-muted">
                                    <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($staff['phone']); ?>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-<?php echo $roleClass; ?> role-badge">
                                    <?php echo ucfirst($staff['role']); ?>
                                </span>
                                <?php if (!empty($staff['shift'])): ?>
                                <span class="shift-badge">
                                    <i class="bi bi-clock"></i> <?php echo ucfirst($staff['shift']); ?> Shift
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($staff['specialization'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="bi bi-tools"></i> <?php echo htmlspecialchars($staff['specialization']); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary flex-grow-1" 
                                        onclick="editStaff(<?php echo htmlspecialchars(json_encode($staff)); ?>)">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button class="btn btn-outline-danger" 
                                        onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['name']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="col-12"><div class="alert alert-info">No staff members found.</div></div>';
        }
        ?>
      </div>
    </div>
</main>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="" method="POST">
          <div class="modal-body">
            <div class="mb-3">
              <label for="name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="phone" class="form-label">Phone</label>
              <input type="tel" class="form-control" id="phone" name="phone" required>
            </div>
            <div class="mb-3">
              <label for="role" class="form-label">Role</label>
              <select class="form-select" id="role" name="role" required>
                <option value="washer">Car Washer</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
              <label for="shift" class="form-label">Shift</label>
              <select class="form-select" id="shift" name="shift" required>
                <option value="morning">Morning</option>
                <option value="afternoon">Afternoon</option>
                <option value="evening">Evening</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="specialization" class="form-label">Specialization</label>
              <select class="form-select" id="specialization" name="specialization" required>
                <option value="general">General Washing</option>
                <option value="detailing">Detailing</option>
                <option value="polishing">Polishing</option>
                <option value="interior">Interior Cleaning</option>
                <option value="ceramic">Ceramic Coating</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_staff" class="btn btn-primary">Add Staff</button>
          </div>
        </form>
      </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="" method="POST">
          <input type="hidden" id="edit_staff_id" name="staff_id">
          <div class="modal-body">
            <div class="mb-3">
              <label for="edit_name" class="form-label">Full Name</label>
              <input type="text" class="form-control" id="edit_name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="edit_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="edit_email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="edit_phone" class="form-label">Phone</label>
              <input type="tel" class="form-control" id="edit_phone" name="phone" required>
            </div>
            <div class="mb-3">
              <label for="edit_role" class="form-label">Role</label>
              <select class="form-select" id="edit_role" name="role" required>
                <option value="washer">Car Washer</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_password" class="form-label">Password (leave blank to keep current)</label>
              <input type="password" class="form-control" id="edit_password" name="password">
            </div>
            <div class="mb-3">
              <label for="edit_shift" class="form-label">Shift</label>
              <select class="form-select" id="edit_shift" name="shift" required>
                <option value="morning">Morning</option>
                <option value="afternoon">Afternoon</option>
                <option value="evening">Evening</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_specialization" class="form-label">Specialization</label>
              <select class="form-select" id="edit_specialization" name="specialization" required>
                <option value="general">General Washing</option>
                <option value="detailing">Detailing</option>
                <option value="polishing">Polishing</option>
                <option value="interior">Interior Cleaning</option>
                <option value="ceramic">Ceramic Coating</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_status" class="form-label">Status</label>
              <select class="form-select" id="edit_status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="update_staff" class="btn btn-primary">Update Staff</button>
          </div>
        </form>
      </div>
    </div>
</div>

<!-- Delete Staff Modal -->
<div class="modal fade" id="deleteStaffModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delete Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form action="" method="POST">
          <input type="hidden" id="delete_staff_id" name="staff_id">
          <div class="modal-body">
            <p>Are you sure you want to delete <strong id="delete_staff_name"></strong>?</p>
            <p class="text-danger mb-0">If the staff member has any assigned bookings, they will be marked as inactive instead of being deleted.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="delete_staff" class="btn btn-danger">Delete Staff</button>
          </div>
        </form>
      </div>
    </div>
</div>

<?php include('admin-footer.php'); ?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
  function editStaff(staff) {
      document.getElementById('edit_staff_id').value = staff.id;
      document.getElementById('edit_name').value = staff.name;
      document.getElementById('edit_email').value = staff.email;
      document.getElementById('edit_phone').value = staff.phone;
      document.getElementById('edit_role').value = staff.role;
      document.getElementById('edit_shift').value = staff.shift;
      document.getElementById('edit_specialization').value = staff.specialization;
      document.getElementById('edit_status').value = staff.status;
      document.getElementById('edit_password').value = '';
      
      new bootstrap.Modal(document.getElementById('editStaffModal')).show();
  }

  function deleteStaff(id, name) {
      document.getElementById('delete_staff_id').value = id;
      document.getElementById('delete_staff_name').textContent = name;
      
      new bootstrap.Modal(document.getElementById('deleteStaffModal')).show();
  }

  // Display success message if available
  const message = new URLSearchParams(window.location.search).get('message');
  if (message) {
      Swal.fire({
          icon: 'success',
          title: message,
          showConfirmButton: false,
          timer: 1500
      }).then(() => {
          // Remove message parameter from URL
          const url = new URL(window.location);
          url.searchParams.delete('message');
          window.history.replaceState({}, '', url);
      });
  }
  
  // Display error message if available
  const error = new URLSearchParams(window.location.search).get('error');
  if (error) {
      Swal.fire({
          icon: 'error',
          title: 'Error',
          text: error,
          showConfirmButton: true
      }).then(() => {
          // Remove error parameter from URL
          const url = new URL(window.location);
          url.searchParams.delete('error');
          window.history.replaceState({}, '', url);
      });
  }
</script>
</body>
</html>

<?php
// Close database connection at the end of the file
$db->closeConnection();
?>
