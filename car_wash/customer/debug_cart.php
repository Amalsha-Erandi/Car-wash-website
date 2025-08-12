<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'Database_Connection.php';

echo "<h1>Cart Debug</h1>";

// Check login status
echo "<h2>Session Status</h2>";
if (isset($_SESSION['customer_id'])) {
    echo "<p>Logged in as Customer ID: " . $_SESSION['customer_id'] . "</p>";
    if (isset($_SESSION['customer_name'])) {
        echo "<p>Customer Name: " . $_SESSION['customer_name'] . "</p>";
    }
} else {
    echo "<p>Not logged in. <a href='login.php'>Login here</a></p>";
    exit;
}

// Test database connection
echo "<h2>Database Connection</h2>";
try {
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p>Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p>Database connection error: " . $e->getMessage() . "</p>";
    exit;
}

// Check if products table exists
echo "<h2>Products Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'products'");
if ($result->num_rows > 0) {
    echo "<p>Products table exists.</p>";
    
    // Count products
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    echo "<p>Total products: " . $row['count'] . "</p>";
    
    // Show sample products
    $result = $conn->query("SELECT * FROM products LIMIT 3");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th></tr>";
    while ($product = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $product['id'] . "</td>";
        echo "<td>" . $product['name'] . "</td>";
        echo "<td>LKR " . $product['price'] . "</td>";
        echo "<td>" . $product['stock_quantity'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Products table does not exist!</p>";
}

// Check if cart table exists
echo "<h2>Cart Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'cart'");
if ($result->num_rows > 0) {
    echo "<p>Cart table exists.</p>";
    
    // Check cart table structure
    $result = $conn->query("DESCRIBE cart");
    echo "<p>Cart table structure:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($field = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "<td>" . $field['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check cart items for this user
    $stmt = $conn->prepare("SELECT c.*, p.name FROM cart c JOIN products p ON c.product_id = p.id WHERE c.customer_id = ?");
    $stmt->bind_param("i", $_SESSION['customer_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "<p>Your cart items:</p>";
    if ($result->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th><th>Status</th></tr>";
        $total = 0;
        while ($item = $result->fetch_assoc()) {
            $item_total = $item['price'] * $item['quantity'];
            $total += $item_total;
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . $item['name'] . "</td>";
            echo "<td>" . $item['quantity'] . "</td>";
            echo "<td>LKR " . $item['price'] . "</td>";
            echo "<td>LKR " . $item_total . "</td>";
            echo "<td>" . $item['status'] . "</td>";
            echo "</tr>";
        }
        echo "<tr><td colspan='4' align='right'><strong>Total:</strong></td><td>LKR " . $total . "</td><td></td></tr>";
        echo "</table>";
    } else {
        echo "<p>Your cart is empty.</p>";
    }
} else {
    echo "<p>Cart table does not exist!</p>";
}

// Test add to cart functionality
echo "<h2>Test Add to Cart</h2>";
echo "<form method='post' action='add_to_cart.php'>";
echo "<label>Product ID: <input type='number' name='product_id' required></label><br>";
echo "<label>Quantity: <input type='number' name='quantity' value='1' min='1' required></label><br>";
echo "<button type='submit'>Add to Cart</button>";
echo "</form>";

// Test remove from cart functionality
echo "<h2>Test Remove from Cart</h2>";
echo "<form method='post' action='remove_from_cart.php'>";
echo "<label>Cart Item ID: <input type='number' name='id' required></label><br>";
echo "<button type='submit'>Remove from Cart</button>";
echo "</form>";

// Close database connection
$db->closeConnection();

echo "<p><a href='products.php'>Go to Products Page</a> | <a href='cart.php'>Go to Cart</a></p>";
?> 