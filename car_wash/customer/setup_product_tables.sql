-- Create products table if it doesn't exist
CREATE TABLE IF NOT EXISTS products (
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
);

-- Create cart table if it doesn't exist
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create orders table if it doesn't exist
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_postal_code VARCHAR(20) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Create order_items table if it doesn't exist
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample products if the products table is empty
INSERT INTO products (name, description, price, stock_quantity, min_quantity, category, status, image_url)
SELECT * FROM (
    SELECT 'Car Shampoo', 'Premium car washing shampoo - 1L', 299.00, 50, 10, 'Cleaning', 'active', 'images/product-placeholder.jpg' UNION ALL
    SELECT 'Wax Polish', 'Long-lasting wax polish - 500ml', 499.00, 30, 5, 'Polish', 'active', 'images/product-placeholder.jpg' UNION ALL
    SELECT 'Microfiber Cloth', 'High-quality microfiber cleaning cloth', 199.00, 100, 20, 'Accessories', 'active', 'images/product-placeholder.jpg' UNION ALL
    SELECT 'Dashboard Cleaner', 'Interior dashboard and vinyl cleaner - 500ml', 349.00, 40, 8, 'Interior', 'active', 'images/product-placeholder.jpg' UNION ALL
    SELECT 'Tire Shine', 'Long-lasting tire shine gel - 250ml', 399.00, 35, 7, 'Exterior', 'active', 'images/product-placeholder.jpg' UNION ALL
    SELECT 'Glass Cleaner', 'Streak-free glass cleaner - 500ml', 249.00, 45, 10, 'Cleaning', 'active', 'images/product-placeholder.jpg' UNION ALL
    SELECT 'Leather Conditioner', 'Premium leather seat conditioner - 250ml', 599.00, 25, 5, 'Interior', 'active', 'images/product-placeholder.jpg' UNION ALL
    SELECT 'Car Freshener', 'Long-lasting car air freshener', 149.00, 80, 15, 'Accessories', 'active', 'images/product-placeholder.jpg'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM products LIMIT 1); 