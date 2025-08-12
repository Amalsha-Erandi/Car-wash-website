<?php
session_start();
include('Database_Connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;

// Validate input
if (!$booking_id || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

// Validate status
$valid_statuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->begin_transaction();

    // Update booking status
    $status_stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $status_stmt->bind_param("si", $status, $booking_id);
    $status_stmt->execute();

    // If staff is being assigned
    if ($staff_id > 0) {
        $staff_stmt = $conn->prepare("UPDATE bookings SET staff_id = ? WHERE id = ?");
        $staff_stmt->bind_param("ii", $staff_id, $booking_id);
        $staff_stmt->execute();
    }

    // If status is completed, update completion time
    if ($status === 'completed') {
        $complete_stmt = $conn->prepare("UPDATE bookings SET completed_at = NOW() WHERE id = ?");
        $complete_stmt->bind_param("i", $booking_id);
        $complete_stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Send success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Booking status updated successfully',
        'booking_id' => $booking_id,
        'new_status' => $status
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Send error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update booking status: ' . $e->getMessage()
    ]);
}

// Close database connection
$db->closeConnection();
?>
