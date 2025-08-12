-- Drop shipping-related columns if they exist
ALTER TABLE orders
DROP COLUMN IF EXISTS shipping_address,
DROP COLUMN IF EXISTS shipping_city,
DROP COLUMN IF EXISTS shipping_postal_code,
DROP COLUMN IF EXISTS contact_phone;

-- Add booking-related columns to orders table
ALTER TABLE orders
ADD COLUMN booking_date DATE,
ADD COLUMN booking_time TIME,
ADD COLUMN vehicle_id INT,
ADD FOREIGN KEY (vehicle_id) REFERENCES customer_vehicles(id); 