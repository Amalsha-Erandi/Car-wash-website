<?php
session_start();
header('Content-Type: application/json');
require_once 'Database_Connection.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to remove items from cart']);
    exit;
}

// Check if request is JSON or form data
$input = file_get_contents('php://input');
$isJson = !empty($input) && substr($input, 0, 1) === '{';

if ($isJson) {
    // Handle JSON request
    $data = json_decode($input, true);
    $cart_id = isset($data['id']) ? (int)$data['id'] : 0;
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    
    if ($cart_id === 0 && isset($data['remove_id'])) {
        $cart_id = (int)$data['remove_id'];
    }
} else {
    // Handle form POST request
    $cart_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
}

$customer_id = $_SESSION['customer_id'];

try {
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    if ($cart_id > 0) {
        // Delete specific cart item by cart ID
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND customer_id = ?");
        $stmt->bind_param("ii", $cart_id, $customer_id);
    } elseif ($product_id > 0) {
        // Delete cart item by product ID
        $stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ? AND customer_id = ? AND status = 'pending' LIMIT 1");
        $stmt->bind_param("ii", $product_id, $customer_id);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request: Missing cart item ID or product ID']);
        exit;
    }
    
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        // Get updated cart count
        $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE customer_id = ? AND status = 'pending'");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $cart_count = $row['count'] ? $row['count'] : 0;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Item removed from cart', 
            'count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Item not found in cart']);
    }
    
    $db->closeConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    error_log("Remove from cart error: " . $e->getMessage());
}
?>
