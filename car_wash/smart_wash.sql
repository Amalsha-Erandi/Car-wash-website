-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 09, 2025 at 09:52 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `smart_wash`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `staff_id`, `action`, `description`, `created_at`) VALUES
(1, 1, 'login', 'Admin logged in successfully', '2025-07-08 09:37:06'),
(2, 1, 'login', 'Admin logged in successfully', '2025-07-08 15:43:17'),
(3, 1, 'login', 'Admin logged in successfully', '2025-07-08 15:54:26'),
(4, 1, 'login', 'Admin logged in successfully', '2025-07-09 09:26:26'),
(5, 1, 'login', 'Admin logged in successfully', '2025-07-09 09:34:51'),
(6, 1, 'login', 'Admin logged in successfully', '2025-07-09 09:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `vehicle_type` enum('car','suv','van','bike') NOT NULL,
  `vehicle_number` varchar(20) NOT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_method` enum('cash','card','upi') DEFAULT 'cash',
  `amount_paid` decimal(10,2) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `customer_id`, `service_id`, `amount`, `staff_id`, `booking_date`, `booking_time`, `vehicle_type`, `vehicle_number`, `status`, `payment_status`, `payment_method`, `amount_paid`, `payment_date`, `notes`, `completed_at`, `created_at`, `updated_at`) VALUES
(4, 5, 1, 499.00, 4, '2025-07-10', '16:12:00', 'car', '12-1213', 'confirmed', 'pending', 'cash', NULL, NULL, '2333', NULL, '2025-07-09 09:42:44', '2025-07-09 09:46:19');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`id`, `customer_id`, `product_id`, `quantity`, `price`, `status`, `created_at`, `updated_at`) VALUES
(5, 4, 7, 1, 444.00, 'completed', '2025-07-09 09:35:50', '2025-07-09 09:36:14'),
(6, 5, 7, 3, 444.00, 'completed', '2025-07-09 09:43:05', '2025-07-09 09:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `status` enum('pending','replied','closed') DEFAULT 'pending',
  `staff_id` int(11) DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `reply_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `last_logout` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `phone`, `password_hash`, `status`, `last_login`, `last_logout`, `created_at`, `updated_at`) VALUES
(4, 'Wijenayaka', 'mtulanka@gmail.com', '0716025266', '$2y$10$9QKHNKGcqDWGd.oihDojMe7kUSHDB4UmVuCLs.0BuH8IDuhnHeijC', 'active', '2025-07-09 09:34:16', '2025-07-09 09:39:19', '2025-07-09 09:32:19', '2025-07-09 09:39:19'),
(5, 'Saduni', 'saduni@gmail.com', '0716025277', '$2y$10$T.UHwLAVixQjM9BidxYraOE3EjKBgWUa6EhrQlRGxmOX.pTTscsTO', 'active', '2025-07-09 09:41:14', NULL, '2025-07-09 09:41:06', '2025-07-09 09:41:14');

-- --------------------------------------------------------

--
-- Table structure for table `customer_vehicles`
--

CREATE TABLE `customer_vehicles` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `vehicle_type` enum('car','suv','van','bike') NOT NULL,
  `make` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` varchar(4) NOT NULL,
  `color` varchar(30) NOT NULL,
  `license_plate` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_vehicles`
--

INSERT INTO `customer_vehicles` (`id`, `customer_id`, `vehicle_type`, `make`, `model`, `year`, `color`, `license_plate`, `status`, `created_at`, `updated_at`) VALUES
(4, 4, 'car', '1', '1', '1111', '1', '1', 'active', '2025-07-09 09:32:19', '2025-07-09 09:32:19'),
(5, 5, 'car', 'Toyota', 'K1', '2022', 'Red', '12-1213', 'active', '2025-07-09 09:41:06', '2025-07-09 09:41:06');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `shipping_postal_code` varchar(20) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `booking_date` date NOT NULL,
  `booking_time` time NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `total_amount`, `payment_method`, `payment_status`, `shipping_address`, `shipping_city`, `shipping_postal_code`, `contact_phone`, `booking_date`, `booking_time`, `vehicle_id`, `status`, `created_at`, `updated_at`) VALUES
(4, 4, 444.00, 'cash', 'pending', '442/d1/raddoluwa,raddolugama', 'Seeduwa', '11400', '0716025266', '2025-07-09', '08:00:00', 4, 'pending', '2025-07-09 09:36:14', '2025-07-09 09:36:14'),
(5, 5, 1332.00, 'cash', 'pending', '442/d1/raddoluwa,raddolugama', 'Seeduwa', '11400', '0716025277', '2025-07-10', '08:00:00', 5, 'pending', '2025-07-09 09:43:47', '2025-07-09 09:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`) VALUES
(4, 4, 7, 1, 444.00, '2025-07-09 09:36:14'),
(5, 5, 7, 3, 444.00, '2025-07-09 09:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_minutes` int(11) NOT NULL,
  `services_included` text DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `vehicle_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `min_quantity` int(11) NOT NULL DEFAULT 5,
  `category` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `stock_quantity`, `min_quantity`, `category`, `status`, `image_url`, `created_at`, `updated_at`) VALUES
(7, 'ddd', '444', 444.00, 40, 344, 'Polish', 'active', 'uploads/products/686e37e39d73e.png', '2025-07-09 09:35:31', '2025-07-09 09:43:47');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `review_replies`
--

CREATE TABLE `review_replies` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `reply` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration` int(11) NOT NULL COMMENT 'Duration in minutes',
  `vehicle_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of supported vehicle types' CHECK (json_valid(`vehicle_types`)),
  `status` enum('active','inactive') DEFAULT 'active',
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `price`, `duration`, `vehicle_types`, `status`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 'Basic Wash', 'Exterior wash with foam cleaning and basic interior vacuum', 499.00, 30, '[\"car\", \"suv\"]', 'active', NULL, '2025-07-07 18:53:04', '2025-07-07 18:53:04'),
(2, 'Premium Wash', 'Complete exterior & interior cleaning with wax polish', 999.00, 60, '[\"car\", \"suv\", \"van\"]', 'active', NULL, '2025-07-07 18:53:04', '2025-07-07 18:53:04'),
(3, 'Express Bike Wash', 'Quick but thorough bike cleaning service', 299.00, 20, '[\"bike\"]', 'active', NULL, '2025-07-07 18:53:04', '2025-07-07 18:53:04'),
(4, 'Full Detailing', 'Complete detailing service with ceramic coating', 4999.00, 180, '[\"car\", \"suv\"]', 'active', NULL, '2025-07-07 18:53:04', '2025-07-07 18:53:04'),
(5, 'test services', 'test services1', 1500.00, 50, '[\"car\",\"suv\",\"van\"]', 'active', NULL, '2025-07-08 09:59:45', '2025-07-08 09:59:51'),
(6, 'Oil changing', 'Oil changingOil changingOil changing', 500.00, 15, '[\"car\"]', 'active', NULL, '2025-07-09 09:47:36', '2025-07-09 09:47:36');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','manager','washer') NOT NULL,
  `shift` enum('morning','afternoon','evening') DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `specialization` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `name`, `email`, `phone`, `profile_image`, `password_hash`, `role`, `shift`, `status`, `specialization`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin@smartwash.com', '1234567890', 'uploads/profile_images/admin_1_1751990991.jpg', '$2y$12$v4JfcrphFPNQV.RbE6aL4.RX/.n3A1PfECiCQx9tS2JljS01jCXty', 'admin', NULL, 'active', NULL, '2025-07-07 18:53:04', '2025-07-08 16:09:52'),
(4, 'Moshan Wijenayaka', 'mtulanka@gmail.com', '0716025266', NULL, '$2y$10$IDumbhMER4U8NgQeAfyMc./gsL5tJoffcpP9X8aeHUlDKK08wVz3W', 'washer', 'morning', 'active', 'general', '2025-07-09 09:26:53', '2025-07-09 09:26:53');

-- --------------------------------------------------------

--
-- Table structure for table `staff_replies`
--

CREATE TABLE `staff_replies` (
  `id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('active','deleted') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `service_id` (`service_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer_vehicles`
--
ALTER TABLE `customer_vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff_replies`
--
ALTER TABLE `staff_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `review_id` (`review_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customer_vehicles`
--
ALTER TABLE `customer_vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `review_replies`
--
ALTER TABLE `review_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff_replies`
--
ALTER TABLE `staff_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `bookings_ibfk_3` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD CONSTRAINT `contact_messages_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `contact_messages_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `customer_vehicles`
--
ALTER TABLE `customer_vehicles`
  ADD CONSTRAINT `customer_vehicles_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `review_replies`
--
ALTER TABLE `review_replies`
  ADD CONSTRAINT `review_replies_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`),
  ADD CONSTRAINT `review_replies_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);

--
-- Constraints for table `staff_replies`
--
ALTER TABLE `staff_replies`
  ADD CONSTRAINT `staff_replies_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`),
  ADD CONSTRAINT `staff_replies_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
