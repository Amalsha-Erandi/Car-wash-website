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
$payment_status = isset($_POST['payment_status']) ? $_POST['payment_status'] : '';
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$amount_paid = isset($_POST['amount_paid']) ? floatval($_POST['amount_paid']) : 0;

// Validate input
if (!$booking_id || empty($payment_status) || empty($payment_method) || $amount_paid <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

// Validate payment status
$valid_payment_statuses = ['pending', 'paid', 'cancelled'];
if (!in_array($payment_status, $valid_payment_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment status']);
    exit;
}

// Validate payment method
$valid_payment_methods = ['cash', 'card', 'upi'];
if (!in_array($payment_method, $valid_payment_methods)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment method']);
    exit;
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

try {
    // Start transaction
    $conn->begin_transaction();

    // Update payment status
    $stmt = $conn->prepare("UPDATE bookings SET 
                           payment_status = ?, 
                           payment_method = ?, 
                           amount_paid = ?, 
                           payment_date = CASE WHEN ? = 'paid' THEN NOW() ELSE NULL END,
                           updated_at = NOW() 
                           WHERE id = ?");
    
    $stmt->bind_param("ssdsi", $payment_status, $payment_method, $amount_paid, $payment_status, $booking_id);
    $stmt->execute();

    // If payment is marked as paid, update booking status to confirmed if it's pending
    if ($payment_status === 'paid') {
        $status_stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' 
                                     WHERE id = ? AND status = 'pending'");
        $status_stmt->bind_param("i", $booking_id);
        $status_stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    // Send success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment status updated successfully',
        'booking_id' => $booking_id,
        'payment_status' => $payment_status,
        'payment_method' => $payment_method,
        'amount_paid' => $amount_paid
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Send error response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to update payment status: ' . $e->getMessage()
    ]);
}

// Close database connection
$db->closeConnection();
?>
