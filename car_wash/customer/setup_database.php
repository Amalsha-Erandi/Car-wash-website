<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'Database_Connection.php';

echo "<h1>Database Setup</h1>";

try {
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<p>Database connection successful!</p>";
    
    // Read SQL file
    $sql = file_get_contents('setup_product_tables.sql');
    
    // Split SQL file into individual statements
    $statements = explode(';', $sql);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if ($conn->query($statement)) {
                echo "<p>Success: " . substr($statement, 0, 50) . "...</p>";
            } else {
                echo "<p>Error: " . $conn->error . " in statement: " . substr($statement, 0, 50) . "...</p>";
            }
        }
    }
    
    echo "<p>Database setup completed!</p>";
    
    // Check if products table exists and has data
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>Products in database: " . $row['count'] . "</p>";
        
        if ($row['count'] > 0) {
            // Display some products
            $result = $conn->query("SELECT * FROM products LIMIT 5");
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Category</th></tr>";
            
            while ($product = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>LKR " . $product['price'] . "</td>";
                echo "<td>" . $product['stock_quantity'] . "</td>";
                echo "<td>" . $product['category'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
    }
    
    $db->closeConnection();
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='products.php'>Go to Products Page</a></p>";
?> 