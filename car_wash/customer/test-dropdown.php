<?php
session_start();
$_SESSION['customer_name'] = 'Test User';
$_SESSION['customer_id'] = 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dropdown Test</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
  <div class="container mt-5">
    <h1>Dropdown Test</h1>
    
    <div class="d-flex justify-content-end">
      <div class="dropdown">
        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-person-circle"></i> Test User
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
          <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> My Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="#"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
        </ul>
      </div>
    </div>
    
    <p class="mt-5">This page tests the dropdown functionality using Bootstrap's native implementation.</p>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 