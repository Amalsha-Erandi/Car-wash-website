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

// Get current week's start and end dates
$week_start = isset($_GET['week_start']) ? $_GET['week_start'] : date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Get staff's bookings for the week
$query = "SELECT b.*, c.name as customer_name, c.phone as customer_phone,
          s.name as service_name, s.duration as service_duration
          FROM bookings b
          JOIN customers c ON b.customer_id = c.id
          JOIN services s ON b.service_id = s.id
          WHERE b.staff_id = ?
          AND b.booking_date BETWEEN ? AND ?
          AND b.status NOT IN ('cancelled')
          ORDER BY b.booking_date ASC, b.booking_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $_SESSION['staff_id'], $week_start, $week_end);
$stmt->execute();
$result = $stmt->get_result();

// Organize bookings by day
$schedule = array();
while ($booking = $result->fetch_assoc()) {
    $date = $booking['booking_date'];
    if (!isset($schedule[$date])) {
        $schedule[$date] = array();
    }
    $schedule[$date][] = $booking;
}

$db->closeConnection();

// Helper function to format time slots
function formatTimeSlot($booking) {
    $start_time = date('h:i A', strtotime($booking['booking_time']));
    $end_time = date('h:i A', strtotime($booking['booking_time'] . ' +' . $booking['service_duration'] . ' minutes'));
    return $start_time . ' - ' . $end_time;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Smart Wash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .schedule-card {
            height: 100%;
            min-height: 300px;
        }
        .booking-item {
            border-left: 4px solid #0d6efd;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            transition: transform 0.2s;
        }
        .booking-item:hover {
            transform: translateX(5px);
        }
        .booking-item.pending {
            border-left-color: #ffc107;
        }
        .booking-item.in_progress {
            border-left-color: #0dcaf0;
        }
        .booking-item.completed {
            border-left-color: #198754;
            opacity: 0.7;
        }
        .day-header {
            background-color: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .week-navigation {
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include('staff-navbar.php'); ?>

    <div class="container py-4">
        <div class="week-navigation d-flex justify-content-between align-items-center">
            <a href="?week_start=<?php echo date('Y-m-d', strtotime($week_start . ' -1 week')); ?>" 
               class="btn btn-outline-primary">
                <i class="bi bi-chevron-left"></i> Previous Week
            </a>
            
            <h2 class="mb-0">
                <?php 
                echo date('M d', strtotime($week_start)) . ' - ' . 
                     date('M d, Y', strtotime($week_end)); 
                ?>
            </h2>
            
            <a href="?week_start=<?php echo date('Y-m-d', strtotime($week_start . ' +1 week')); ?>" 
               class="btn btn-outline-primary">
                Next Week <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php
            $current_date = new DateTime($week_start);
            $end_date = new DateTime($week_end);
            
            while ($current_date <= $end_date) {
                $date = $current_date->format('Y-m-d');
                $is_today = $date == date('Y-m-d');
                ?>
                <div class="col-md-6">
                    <div class="card schedule-card <?php echo $is_today ? 'border-primary' : ''; ?>">
                        <div class="card-body">
                            <div class="day-header <?php echo $is_today ? 'bg-primary text-white' : ''; ?>">
                                <h5 class="card-title mb-0">
                                    <?php 
                                    echo $current_date->format('l, M d'); 
                                    echo $is_today ? ' (Today)' : '';
                                    ?>
                                </h5>
                            </div>

                            <?php if (isset($schedule[$date]) && !empty($schedule[$date])): ?>
                                <?php foreach ($schedule[$date] as $booking): ?>
                                    <div class="booking-item <?php echo $booking['status']; ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo formatTimeSlot($booking); ?></strong>
                                                <div class="text-primary">
                                                    <?php echo htmlspecialchars($booking['service_name']); ?>
                                                </div>
                                            </div>
                                            <span class="badge bg-<?php 
                                                echo $booking['status'] == 'pending' ? 'warning' : 
                                                    ($booking['status'] == 'in_progress' ? 'info' : 'success'); 
                                            ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> 
                                                <?php echo htmlspecialchars($booking['customer_name']); ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="bi bi-telephone"></i> 
                                                <?php echo htmlspecialchars($booking['customer_phone']); ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="bi bi-car-front"></i> 
                                                <?php echo ucfirst($booking['vehicle_type']); ?> - 
                                                <?php echo strtoupper($booking['vehicle_number']); ?>
                                            </small>
                                        </div>
                                        
                                        <?php if ($booking['status'] != 'completed'): ?>
                                            <div class="mt-2">
                                                <a href="my-bookings.php?booking_id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-arrow-clockwise"></i> Update Status
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No bookings scheduled</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
                $current_date->modify('+1 day');
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 