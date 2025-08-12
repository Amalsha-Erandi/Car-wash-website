<?php
session_start();
header('Content-Type: application/json');
require_once 'Database_Connection.php';

// Default count
$count = 0;

if (isset($_SESSION['customer_id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Get cart count
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE customer_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $_SESSION['customer_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $count = $row['count'] ? (int)$row['count'] : 0;
        
        $db->closeConnection();
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
    }
}

echo json_encode(['count' => $count]);
?>