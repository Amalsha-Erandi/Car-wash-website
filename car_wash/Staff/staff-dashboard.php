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

// Get today's bookings for this staff
$today_query = "SELECT b.*, c.name as customer_name, c.phone as customer_phone,
                s.name as service_name, s.duration as service_duration
                FROM bookings b
                JOIN customers c ON b.customer_id = c.id
                JOIN services s ON b.service_id = s.id
                WHERE b.staff_id = ? 
                AND b.booking_date = CURDATE()
                AND b.status NOT IN ('completed', 'cancelled')
                ORDER BY b.booking_time ASC";

$today_stmt = $conn->prepare($today_query);
$today_stmt->bind_param("i", $_SESSION['staff_id']);
$today_stmt->execute();
$today_result = $today_stmt->get_result();
$today_bookings = $today_result->fetch_all(MYSQLI_ASSOC);

// Get upcoming bookings
$upcoming_query = "SELECT b.*, c.name as customer_name, c.phone as customer_phone,
                   s.name as service_name, s.duration as service_duration
                   FROM bookings b
                   JOIN customers c ON b.customer_id = c.id
                   JOIN services s ON b.service_id = s.id
                   WHERE b.staff_id = ? 
                   AND (b.booking_date > CURDATE() 
                        OR (b.booking_date = CURDATE() AND b.booking_time > CURTIME()))
                   AND b.status NOT IN ('completed', 'cancelled')
                   ORDER BY b.booking_date ASC, b.booking_time ASC
                   LIMIT 5";

$upcoming_stmt = $conn->prepare($upcoming_query);
$upcoming_stmt->bind_param("i", $_SESSION['staff_id']);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();
$upcoming_bookings = $upcoming_result->fetch_all(MYSQLI_ASSOC);

// Get booking statistics
$stats_query = "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as ongoing_bookings,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_bookings
                FROM bookings 
                WHERE staff_id = ? 
                AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $_SESSION['staff_id']);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

$db->closeConnection();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stats-card {
            transition: transform 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .booking-card {
            border-left: 5px solid #0d6efd;
        }
        .booking-card.pending {
            border-left-color: #ffc107;
        }
        .booking-card.in_progress {
            border-left-color: #0dcaf0;
        }
        .booking-card.completed {
            border-left-color: #198754;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('staff-navbar.php'); ?>

    <div class="container py-4">
        <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['staff_name']); ?>!</h1>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stats-card h-100 bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Bookings</h5>
                        <h2><?php echo $stats['total_bookings']; ?></h2>
                        <p class="mb-0">Last 30 days</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card h-100 bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Completed</h5>
                        <h2><?php echo $stats['completed_bookings']; ?></h2>
                        <p class="mb-0">Services completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card h-100 bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">In Progress</h5>
                        <h2><?php echo $stats['ongoing_bookings']; ?></h2>
                        <p class="mb-0">Current services</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card h-100 bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Pending</h5>
                        <h2><?php echo $stats['pending_bookings']; ?></h2>
                        <p class="mb-0">Upcoming services</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Bookings -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-check"></i> Today's Bookings
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($today_bookings)): ?>
                    <p class="text-muted mb-0">No bookings scheduled for today.</p>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($today_bookings as $booking): ?>
                            <div class="col-md-6">
                                <div class="card booking-card h-100 <?php echo $booking['status']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 class="card-title mb-1">
                                                    <?php echo htmlspecialchars($booking['service_name']); ?>
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    <?php echo date('h:i A', strtotime($booking['booking_time'])); ?> - 
                                                    <?php echo $booking['duration']; ?> mins
                                                </p>
                                            </div>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] == 'pending' ? 'warning' : 
                                                    ($booking['status'] == 'in_progress' ? 'info' : 'success'); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Customer:</strong>
                                            <div class="text-muted">
                                                <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <strong>Vehicle:</strong>
                                            <div class="text-muted">
                                                <?php echo ucfirst($booking['vehicle_type']); ?> - 
                                                <?php echo strtoupper($booking['vehicle_number']); ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($booking['status'] != 'completed'): ?>
                                            <a href="update-booking.php?id=<?php echo $booking['id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="bi bi-arrow-clockwise"></i> Update Status
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Bookings -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calendar-week"></i> Upcoming Bookings
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_bookings)): ?>
                    <p class="text-muted mb-0">No upcoming bookings.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Service</th>
                                    <th>Customer</th>
                                    <th>Vehicle</th>
                                    <th>Duration</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?><br>
                                            <small class="text-muted">
                                                <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo ucfirst($booking['vehicle_type']); ?><br>
                                            <small class="text-muted">
                                                <?php echo strtoupper($booking['vehicle_number']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo $booking['duration']; ?> mins</td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] == 'pending' ? 'warning' : 
                                                    ($booking['status'] == 'in_progress' ? 'info' : 'success'); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="my-bookings.php" class="btn btn-primary btn-sm">
                            <i class="bi bi-calendar3"></i> View All Bookings
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 