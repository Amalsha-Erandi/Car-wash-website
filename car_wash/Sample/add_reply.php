<?php
session_start();
include('Database_Connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['review_id']) || !isset($data['reply']) || empty(trim($data['reply']))) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$review_id = intval($data['review_id']);
$reply = trim($data['reply']);
$admin_id = $_SESSION['admin_id'];

// First check if the review exists
$check_stmt = $conn->prepare("SELECT id FROM reviews WHERE id = ?");
$check_stmt->bind_param("i", $review_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Review not found']);
    exit;
}

// Add the reply
$stmt = $conn->prepare("INSERT INTO review_replies (review_id, admin_id, reply, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $review_id, $admin_id, $reply);

if ($stmt->execute()) {
    $reply_id = $stmt->insert_id;
    
    // Get the admin name for the response
    $admin_stmt = $conn->prepare("SELECT name FROM staff WHERE id = ?");
    $admin_stmt->bind_param("i", $admin_id);
    $admin_stmt->execute();
    $admin_result = $admin_stmt->get_result();
    $admin_row = $admin_result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'reply' => [
            'id' => $reply_id,
            'admin_name' => htmlspecialchars($admin_row['name']),
            'reply' => htmlspecialchars($reply),
            'created_at' => date('M d, Y h:i A')
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Failed to add reply']);
}

$db->closeConnection();
?> 
