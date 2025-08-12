<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'Database_Connection.php';

echo "<h1>Products Check</h1>";

try {
    // Initialize database connection
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<p>Database connection successful!</p>";
    
    // Check if products table exists
    $check_table = $conn->query("SHOW TABLES LIKE 'products'");
    if ($check_table->num_rows > 0) {
        echo "<p>Products table exists.</p>";
        
        // Count products
        $query = "SELECT COUNT(*) as total FROM products";
        $result = $conn->query($query);
        $row = $result->fetch_assoc();
        echo "<p>Total products in database: " . $row['total'] . "</p>";
        
        if ($row['total'] > 0) {
            // Display products
            $query = "SELECT * FROM products";
            $result = $conn->query($query);
            
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Status</th></tr>";
            
            while ($product = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $product['id'] . "</td>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>LKR " . $product['price'] . "</td>";
                echo "<td>" . $product['stock_quantity'] . "</td>";
                echo "<td>" . $product['status'] . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No products found. Adding sample products...</p>";
            
            // Insert sample products
            $query = "INSERT INTO products (name, description, price, stock_quantity, min_quantity, category, status, image_url) VALUES 
            ('Car Shampoo', 'Premium car washing shampoo - 1L', 299, 50, 10, 'Cleaning', 'active', 'images/product-placeholder.jpg'),
            ('Wax Polish', 'Long-lasting wax polish - 500ml', 499, 30, 5, 'Polish', 'active', 'images/product-placeholder.jpg'),
            ('Microfiber Cloth', 'High-quality microfiber cleaning cloth', 199, 100, 20, 'Accessories', 'active', 'images/product-placeholder.jpg'),
            ('Dashboard Cleaner', 'Interior dashboard and vinyl cleaner - 500ml', 349, 40, 8, 'Interior', 'active', 'images/product-placeholder.jpg')";
            
            if ($conn->query($query)) {
                echo "<p>Sample products added successfully!</p>";
                
                // Show the added products
                $query = "SELECT * FROM products";
                $result = $conn->query($query);
                
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Status</th></tr>";
                
                while ($product = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $product['id'] . "</td>";
                    echo "<td>" . $product['name'] . "</td>";
                    echo "<td>LKR " . $product['price'] . "</td>";
                    echo "<td>" . $product['stock_quantity'] . "</td>";
                    echo "<td>" . $product['status'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>Error adding products: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p>Products table doesn't exist. Creating table...</p>";
        
        // Create products table
        $query = "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            min_quantity INT NOT NULL DEFAULT 5,
            category VARCHAR(50) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            image_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($query)) {
            echo "<p>Products table created successfully!</p>";
            
            // Insert sample products
            $query = "INSERT INTO products (name, description, price, stock_quantity, min_quantity, category, status, image_url) VALUES 
            ('Car Shampoo', 'Premium car washing shampoo - 1L', 299, 50, 10, 'Cleaning', 'active', 'images/product-placeholder.jpg'),
            ('Wax Polish', 'Long-lasting wax polish - 500ml', 499, 30, 5, 'Polish', 'active', 'images/product-placeholder.jpg'),
            ('Microfiber Cloth', 'High-quality microfiber cleaning cloth', 199, 100, 20, 'Accessories', 'active', 'images/product-placeholder.jpg'),
            ('Dashboard Cleaner', 'Interior dashboard and vinyl cleaner - 500ml', 349, 40, 8, 'Interior', 'active', 'images/product-placeholder.jpg')";
            
            if ($conn->query($query)) {
                echo "<p>Sample products added successfully!</p>";
                
                // Show the added products
                $query = "SELECT * FROM products";
                $result = $conn->query($query);
                
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th><th>Status</th></tr>";
                
                while ($product = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $product['id'] . "</td>";
                    echo "<td>" . $product['name'] . "</td>";
                    echo "<td>LKR " . $product['price'] . "</td>";
                    echo "<td>" . $product['stock_quantity'] . "</td>";
                    echo "<td>" . $product['status'] . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>Error adding products: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>Error creating products table: " . $conn->error . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='products.php'>Go to Products Page</a></p>";
?> 