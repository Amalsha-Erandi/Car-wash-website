-- Create database if not exists
CREATE DATABASE IF NOT EXISTS car_wash_3;
USE car_wash_3;

-- Create customer_vehicles table if not exists
CREATE TABLE IF NOT EXISTS customer_vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    make VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    color VARCHAR(30) NOT NULL,
    license_plate VARCHAR(20) NOT NULL,
    vehicle_type VARCHAR(30) NOT NULL,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table if not exists
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_postal_code VARCHAR(20) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    vehicle_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES customer_vehicles(id)
); 