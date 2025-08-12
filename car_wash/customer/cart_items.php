<?php
session_start();
header('Content-Type: application/json');
require_once 'Database_Connection.php';

// Default response
$response = [
    'success' => false,
    'items' => [],
    'total' => 0
];

// Check if user is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode($response);
    exit;
}

try {
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    // Get cart items
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.description, p.image_url, p.stock_quantity 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.customer_id = ? AND c.status = 'pending'
    ");
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    $total = 0;
    
    while ($item = $result->fetch_assoc()) {
        $item_total = $item['price'] * $item['quantity'];
        $total += $item_total;
        
        $items[] = [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'name' => $item['name'],
            'description' => $item['description'],
            'price' => (float)$item['price'],
            'quantity' => (int)$item['quantity'],
            'total' => $item_total,
            'image' => !empty($item['image_url']) ? $item['image_url'] : 'images/product-placeholder.jpg',
            'stock' => (int)$item['stock_quantity']
        ];
    }
    
    $response = [
        'success' => true,
        'items' => $items,
        'total' => $total
    ];
    
    $db->closeConnection();
    
} catch (Exception $e) {
    error_log("Cart items error: " . $e->getMessage());
    $response['message'] = 'An error occurred while fetching cart items';
}

echo json_encode($response);
?>