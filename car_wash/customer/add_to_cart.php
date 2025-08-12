<?php
session_start();
header('Content-Type: application/json');
require_once 'Database_Connection.php';

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    $response = array('success' => false, 'message' => 'Please log in to add items to cart');
    echo json_encode($response);
    exit;
}

// Check if request is JSON or form data
$input = file_get_contents('php://input');
$isJson = !empty($input) && substr($input, 0, 1) === '{';

if ($isJson) {
    // Handle JSON request
    $data = json_decode($input, true);
    $product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 0;
} else {
    // Handle form POST request
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
}

// Check if product_id and quantity are provided
if ($product_id <= 0 || $quantity <= 0) {
    $response = array('success' => false, 'message' => 'Invalid request: Missing product_id or quantity');
    echo json_encode($response);
    exit;
}

$customer_id = $_SESSION['customer_id'];

try {
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if product exists and has enough stock
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response = array('success' => false, 'message' => 'Product not found');
        echo json_encode($response);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    if ($product['stock_quantity'] < $quantity) {
        $response = array('success' => false, 'message' => 'Not enough stock available');
        echo json_encode($response);
        exit;
    }
    
    // Check if product already exists in cart
    $stmt = $conn->prepare("SELECT * FROM cart WHERE customer_id = ? AND product_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $customer_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity if product already in cart
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($new_quantity > $product['stock_quantity']) {
            $response = array('success' => false, 'message' => 'Cannot add more of this product (exceeds available stock)');
            echo json_encode($response);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt->execute();
    } else {
        // Add new item to cart
        $stmt = $conn->prepare("INSERT INTO cart (customer_id, product_id, quantity, price, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iiid", $customer_id, $product_id, $quantity, $product['price']);
        $stmt->execute();
    }
    
    // Get updated cart count
    $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE customer_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cart_count = $row['count'] ? $row['count'] : 0;
    
    $response = array(
        'success' => true, 
        'message' => 'Product added to cart', 
        'cart_count' => $cart_count
    );
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = array('success' => false, 'message' => 'An error occurred: ' . $e->getMessage());
    echo json_encode($response);
    error_log("Add to cart error: " . $e->getMessage());
} finally {
    if (isset($db)) {
        $db->closeConnection();
    }
}
?>
