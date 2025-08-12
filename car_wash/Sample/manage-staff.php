<?php
// Include database connection class
include('Database_Connection.php');

// Create an instance of the Database class
$db = new Database();
$conn = $db->getConnection(); // Get the database connection

// Fetch all staff data
$query = "SELECT * FROM staff";

// Execute the query
$result = mysqli_query($conn, $query);

// Check if the query was successful
if (!$result) {
    // If query fails, show the error message and stop the script
    die('Error executing query: ' . mysqli_error($conn));
}

// Check if we have any results
if (mysqli_num_rows($result) == 0) {
    echo "No staff members found.";
}

// Close the database connection
$db->closeConnection();
?>

<?php include('admin-navbar.php'); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Staff - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
  <div id="admin-navbar"></div>

  <main class="py-5">
    <div class="container">
      <h1 class="mb-4">Manage Staff Accounts</h1>
      <div class="mb-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
          <i class="bi bi-plus"></i> Add New Staff
        </button>
      </div>

      <!-- Staff Table -->
      <div class="card">
        <div class="card-body">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = mysqli_fetch_assoc($result)) : ?>
              <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['role']; ?></td>
                <td>
                  <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editStaffModal" 
                          onclick="populateEditForm(<?php echo $row['id']; ?>, '<?php echo $row['name']; ?>', '<?php echo $row['email']; ?>', '<?php echo $row['role']; ?>')">
                    <i class="bi bi-pencil"></i> Edit
                  </button>
                  <a href="manageStaff.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger">
                    <i class="bi bi-trash"></i> Delete
                  </a>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Add Staff Modal -->
  <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addStaffModalLabel">Add New Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="manageStaff.php">
            <div class="mb-3">
              <label for="staffName" class="form-label">Name</label>
              <input type="text" class="form-control" id="staffName" name="staffName" required>
            </div>
            <div class="mb-3">
              <label for="staffEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="staffEmail" name="staffEmail" required>
            </div>
            <div class="mb-3">
              <label for="staffRole" class="form-label">Role</label>
              <select class="form-select" id="staffRole" name="staffRole" required>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" name="addStaff">Save</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Staff Modal -->
  <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editStaffModalLabel">Edit Staff</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form method="POST" action="manageStaff.php">
            <input type="hidden" id="staffId" name="staffId">
            <div class="mb-3">
              <label for="editStaffName" class="form-label">Name</label>
              <input type="text" class="form-control" id="editStaffName" name="staffName" required>
            </div>
            <div class="mb-3">
              <label for="editStaffEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="editStaffEmail" name="staffEmail" required>
            </div>
            <div class="mb-3">
              <label for="editStaffRole" class="form-label">Role</label>
              <select class="form-select" id="editStaffRole" name="staffRole" required>
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" name="editStaff">Save Changes</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <?php include('admin-footer.php'); ?>

  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.20/dist/sweetalert2.min.js"></script>

  <script>
    // Show SweetAlert if the message parameter is present
    <?php if (isset($_GET['message'])): ?>
      Swal.fire({
        icon: 'success',
        title: '<?php echo htmlspecialchars($_GET['message']); ?>',
        showConfirmButton: false,
        timer: 1500
      }).then(() => {
        // Optional: Redirect after the message
        window.location.href = "manage-staff.php";
      });
    <?php endif; ?>
  </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

