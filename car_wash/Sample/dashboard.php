<?php
// Start session at the very beginning
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin-login.php");
    exit;
}

// Include database connection
include('Database_Connection.php');

$db = new Database();
$conn = $db->getConnection();

// Get total bookings
$totalBookingsQuery = "SELECT COUNT(*) as total_bookings FROM bookings";
$totalBookingsResult = mysqli_query($conn, $totalBookingsQuery);
$totalBookings = 0;
if ($totalBookingsResult) {
    $row = mysqli_fetch_assoc($totalBookingsResult);
    $totalBookings = $row['total_bookings'];
}

// Get today's bookings
$todayBookingsQuery = "SELECT COUNT(*) as today_bookings FROM bookings WHERE DATE(booking_date) = CURDATE()";
$todayBookingsResult = mysqli_query($conn, $todayBookingsQuery);
$todayBookings = 0;
if ($todayBookingsResult) {
    $row = mysqli_fetch_assoc($todayBookingsResult);
    $todayBookings = $row['today_bookings'];
}

// Get total revenue
$totalRevenueQuery = "SELECT SUM(amount_paid) as total_revenue FROM bookings WHERE payment_status = 'paid'";
$totalRevenueResult = mysqli_query($conn, $totalRevenueQuery);
$totalRevenue = 0;
if ($totalRevenueResult) {
    $row = mysqli_fetch_assoc($totalRevenueResult);
    $totalRevenue = $row['total_revenue'] ? $row['total_revenue'] : 0;
}

// Get pending bookings
$pendingBookingsQuery = "SELECT COUNT(*) as pending_bookings FROM bookings WHERE status = 'pending'";
$pendingBookingsResult = mysqli_query($conn, $pendingBookingsQuery);
$pendingBookings = 0;
if ($pendingBookingsResult) {
    $row = mysqli_fetch_assoc($pendingBookingsResult);
    $pendingBookings = $row['pending_bookings'];
}

// Get staff count
$staffCountQuery = "SELECT COUNT(*) as staff_count FROM staff WHERE role = 'washer' AND status = 'active'";
$staffCountResult = mysqli_query($conn, $staffCountQuery);
$staffCount = 0;
if ($staffCountResult) {
    $row = mysqli_fetch_assoc($staffCountResult);
    $staffCount = $row['staff_count'];
}

// Get services count
$servicesCountQuery = "SELECT COUNT(*) as services_count FROM services WHERE status = 'active'";
$servicesCountResult = mysqli_query($conn, $servicesCountQuery);
$servicesCount = 0;
if ($servicesCountResult) {
    $row = mysqli_fetch_assoc($servicesCountResult);
    $servicesCount = $row['services_count'];
}

// Get recent bookings
$recentBookingsQuery = "SELECT b.id as booking_id, 
                               c.name as customer_name, 
                               s.name as service_name,
                               b.booking_date, 
                               b.booking_time, 
                               b.amount_paid,
                               b.status,
                               b.vehicle_type,
                               b.vehicle_number
                        FROM bookings b
                        JOIN customers c ON b.customer_id = c.id
                        JOIN services s ON b.service_id = s.id
                        ORDER BY b.created_at DESC 
                        LIMIT 5";
$recentBookingsResult = mysqli_query($conn, $recentBookingsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Smart Wash</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="admin-styles.css" rel="stylesheet">
  <style>
    .icon-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 15px;
    }
    .status-badge {
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 0.875rem;
    }
    .vehicle-info {
      font-size: 0.875rem;
      color: #6c757d;
    }
  </style>
</head>
<body class="bg-light">
  <?php include('admin-navbar.php'); ?>
  
  <main class="py-5">
    <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Dashboard</h1>
                <div class="text-end">
                    <p class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></p>
                    <small class="text-muted"><?php echo date('l, F j, Y'); ?></small>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="icon-circle bg-white text-primary">
                                <i class="bi bi-calendar-check fs-4"></i>
                            </div>
                            <h6 class="card-title">Today's Bookings</h6>
                            <h2 class="mb-0"><?php echo $todayBookings; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
                    <div class="card stat-card bg-success text-white h-100">
            <div class="card-body">
                            <div class="icon-circle bg-white text-success">
                                <i class="bi bi-currency-rupee fs-4"></i>
                            </div>
                            <h6 class="card-title">Total Revenue</h6>
                            <h2 class="mb-0">LKR <?php echo number_format($totalRevenue, 2); ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
                    <div class="card stat-card bg-warning text-white h-100">
            <div class="card-body">
                            <div class="icon-circle bg-white text-warning">
                                <i class="bi bi-hourglass-split fs-4"></i>
                            </div>
                            <h6 class="card-title">Pending Bookings</h6>
                            <h2 class="mb-0"><?php echo $pendingBookings; ?></h2>
            </div>
          </div>
        </div>
        <div class="col-md-3">
                    <div class="card stat-card bg-info text-white h-100">
            <div class="card-body">
                            <div class="icon-circle bg-white text-info">
                                <i class="bi bi-people fs-4"></i>
                            </div>
                            <h6 class="card-title">Active Staff</h6>
                            <h2 class="mb-0"><?php echo $staffCount; ?></h2>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
            <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="staff-management.php" class="btn btn-outline-primary w-100">
                                        <i class="bi bi-people-fill"></i> Manage Staff
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="admin-treatment-management.php" class="btn btn-outline-success w-100">
                                        <i class="bi bi-gear-fill"></i> Manage Services
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="view-orders.php" class="btn btn-outline-warning w-100">
                                        <i class="bi bi-calendar2-check"></i> View Bookings
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="admin-review-managment.php" class="btn btn-outline-info w-100">
                                        <i class="bi bi-star-fill"></i> Reviews
                                    </a>
            </div>
          </div>
        </div>
            </div>
          </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Service Statistics</h5>
        </div>
            <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h3 class="text-primary"><?php echo $servicesCount; ?></h3>
                                    <p class="text-muted mb-0">Active Services</p>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-success"><?php echo $totalBookings; ?></h3>
                                    <p class="text-muted mb-0">Total Bookings</p>
                                </div>
                                <div class="col-4">
                                    <h3 class="text-info"><?php echo number_format(($totalBookings > 0 ? ($pendingBookings / $totalBookings) * 100 : 0), 1); ?>%</h3>
                                    <p class="text-muted mb-0">Pending Rate</p>
                                </div>
            </div>
          </div>
        </div>
      </div>
            </div>

            <!-- Recent Bookings Table -->
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Bookings</h5>
                    <a href="view-orders.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                        <table class="table table-hover align-middle">
                  <thead>
                    <tr>
                                    <th>Booking ID</th>
                      <th>Customer</th>
                                    <th>Service</th>
                                    <th>Vehicle</th>
                                    <th>Date & Time</th>
                      <th>Amount</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                                if ($recentBookingsResult && mysqli_num_rows($recentBookingsResult) > 0) {
                                    while ($booking = mysqli_fetch_assoc($recentBookingsResult)) {
                            $statusClass = 'secondary';
                                        switch ($booking['status']) {
                                            case 'confirmed':
                                                $statusClass = 'success';
                                                break;
                                case 'pending':
                                    $statusClass = 'warning';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'danger';
                                    break;
                                            case 'completed':
                                                $statusClass = 'info';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td>#<?php echo $booking['booking_id']; ?></td>
                                            <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                            <td>
                                                <div class="vehicle-info">
                                                    <i class="bi bi-car-front"></i> 
                                                    <?php echo ucfirst($booking['vehicle_type']); ?>
                                                    <br>
                                                    <i class="bi bi-hash"></i>
                                                    <?php echo strtoupper($booking['vehicle_number']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php 
                                                echo date('M d, Y', strtotime($booking['booking_date'])) . '<br>';
                                                echo date('h:i A', strtotime($booking['booking_time']));
                                                ?>
                                            </td>
                                            <td>LKR <?php echo number_format($booking['amount_paid'] ?? 0, 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-orders.php?id=<?php echo $booking['booking_id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                        }
                    } else {
                                    echo '<tr><td colspan="8" class="text-center">No recent bookings found</td></tr>';
                    }
                    ?>
                  </tbody>
                </table>
              </div>
                </div>
      </div>
    </div>
  </main>

  <?php include('admin-footer.php'); ?>
</body>
</html>
<?php
$db->closeConnection();
?>
