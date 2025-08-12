<?php
session_start();
include('Database_Connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Page specific code goes here
$page_title = "Admin Template";
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $page_title; ?> - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="admin-styles.css" rel="stylesheet">
</head>
<body>
  <?php include('admin-navbar.php'); ?>

  <main class="py-5">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $page_title; ?></h1>
      </div>

      <!-- Page content goes here -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <p>This is a template file. Replace this content with your actual page content.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <?php include('admin-footer.php'); ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Page specific scripts go here -->
  <script>
    // Your JavaScript code here
  </script>
</body>
</html>
<?php
$db->closeConnection();
?> 