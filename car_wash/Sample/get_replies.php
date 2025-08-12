<?php
session_start();
include('Database_Connection.php');

// Check if user is logged in and has admin role
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['review_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Review ID is required']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$review_id = intval($_GET['review_id']);

// Get all replies for the review with admin details
$stmt = $conn->prepare("SELECT rr.*, s.name as admin_name 
                       FROM review_replies rr
                       JOIN staff s ON rr.admin_id = s.id
                       WHERE rr.review_id = ?
                       ORDER BY rr.created_at DESC");
$stmt->bind_param("i", $review_id);
$stmt->execute();
$result = $stmt->get_result();

$replies = [];
while ($row = $result->fetch_assoc()) {
    $replies[] = [
        'id' => $row['id'],
        'admin_name' => htmlspecialchars($row['admin_name']),
        'reply' => htmlspecialchars($row['reply']),
        'created_at' => date('M d, Y h:i A', strtotime($row['created_at']))
    ];
}

header('Content-Type: application/json');
echo json_encode($replies);

$db->closeConnection();
?> 
