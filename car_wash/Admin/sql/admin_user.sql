-- Create database if not exists
CREATE DATABASE IF NOT EXISTS smart_wash;
USE smart_wash;

-- Create staff table
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'washer') NOT NULL,
    shift ENUM('morning', 'afternoon', 'evening') NULL,
    specialization VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create customer_vehicles table
CREATE TABLE IF NOT EXISTS customer_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    vehicle_type ENUM('car', 'suv', 'van', 'bike') NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year VARCHAR(4) NOT NULL,
    color VARCHAR(30) NOT NULL,
    license_plate VARCHAR(20) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration INT NOT NULL COMMENT 'Duration in minutes',
    vehicle_types JSON COMMENT 'Array of supported vehicle types',
    status ENUM('active', 'inactive') DEFAULT 'active',
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create packages table
CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_minutes INT NOT NULL,
    services_included TEXT,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    vehicle_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    service_id INT NOT NULL,
    staff_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    vehicle_type ENUM('car', 'suv', 'van', 'bike') NOT NULL,
    vehicle_number VARCHAR(20) NOT NULL,
    status ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'upi') DEFAULT 'cash',
    amount_paid DECIMAL(10,2),
    payment_date TIMESTAMP NULL,
    notes TEXT,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Create products table
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

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- Create review_replies table
CREATE TABLE IF NOT EXISTS review_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    staff_id INT NOT NULL,
    reply TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id)
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Insert default admin user with bcrypt hashed password
-- Password: admin123
INSERT INTO staff (name, email, phone, password_hash, role, status) VALUES 
('Admin User', 'admin@smartwash.com', '1234567890', '$2y$12$v4JfcrphFPNQV.RbE6aL4.RX/.n3A1PfECiCQx9tS2JljS01jCXty', 'admin', 'active');

-- Insert sample services
INSERT INTO services (name, description, price, duration, vehicle_types, status) VALUES 
('Basic Wash', 'Exterior wash with foam cleaning and basic interior vacuum', 499, 30, '["car", "suv"]', 'active'),
('Premium Wash', 'Complete exterior & interior cleaning with wax polish', 999, 60, '["car", "suv", "van"]', 'active'),
('Express Bike Wash', 'Quick but thorough bike cleaning service', 299, 20, '["bike"]', 'active'),
('Full Detailing', 'Complete detailing service with ceramic coating', 4999, 180, '["car", "suv"]', 'active');

-- Insert sample products
INSERT INTO products (name, description, price, stock_quantity, min_quantity, category, status) VALUES 
('Car Shampoo', 'Premium car washing shampoo - 1L', 299, 50, 10, 'Cleaning', 'active'),
('Wax Polish', 'Long-lasting wax polish - 500ml', 499, 30, 5, 'Polish', 'active'),
('Microfiber Cloth', 'High-quality microfiber cleaning cloth', 199, 100, 20, 'Accessories', 'active'),
('Dashboard Cleaner', 'Interior dashboard and vinyl cleaner - 500ml', 349, 40, 8, 'Interior', 'active');

-- Default login credentials:
-- Email: admin@smartwash.com
-- Password: admin123 