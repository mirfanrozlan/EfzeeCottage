-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 20, 2025 at 04:19 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `efzeecottage`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `homestay_id` int DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_guests` int NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `user_id`, `homestay_id`, `check_in_date`, `check_out_date`, `total_guests`, `status`) VALUES
(5, 9, 1, '2025-06-21', '2025-06-22', 6, 'pending'),
(6, 10, 1, '2025-06-18', '2025-06-19', 6, 'pending'),
(7, 11, 2, '2025-06-28', '2025-06-29', 5, 'confirmed');

-- --------------------------------------------------------

--
-- Table structure for table `discount_codes`
--

CREATE TABLE `discount_codes` (
  `code_id` int NOT NULL,
  `code` varchar(20) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `valid_from` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `max_uses` int DEFAULT NULL,
  `current_uses` int DEFAULT '0',
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `homestays`
--

CREATE TABLE `homestays` (
  `homestay_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `address` text NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `max_guests` int NOT NULL,
  `bedrooms` int NOT NULL,
  `bathrooms` int NOT NULL,
  `status` enum('available','booked','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `homestays`
--

INSERT INTO `homestays` (`homestay_id`, `name`, `description`, `address`, `price_per_night`, `max_guests`, `bedrooms`, `bathrooms`, `status`) VALUES
(1, 'EFZEE COTTAGE MAIN UNIT', 'Spacious and modern homestay unit with garden view', 'Jalan Zabedah, Taman Zabedah, 83000 Batu Pahat, Johor', '500.00', 10, 4, 3, 'available'),
(2, 'EFZEE COTTAGE SECOND UNIT', 'Spacious and modern homestay unit with garden view', 'Jalan Zabedah, Taman Zabedah, 83000 Batu Pahat, Johor', '350.00', 8, 3, 2, 'available');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('credit_card','debit_card','bank_transfer','qr_code') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `payment_date` date NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `amount`, `payment_method`, `status`, `payment_date`, `discount`) VALUES
(1, 1, '500.00', 'qr_code', 'pending', '2025-06-20', '0.00'),
(2, 2, '500.00', 'qr_code', 'pending', '2025-06-20', '0.00'),
(3, 3, '500.00', 'qr_code', 'pending', '2025-06-20', '0.00'),
(4, 4, '330.00', 'qr_code', 'pending', '2025-06-20', '20.00'),
(5, 5, '500.00', 'qr_code', 'pending', '2025-06-21', '0.00'),
(6, 6, '500.00', 'qr_code', 'pending', '2025-06-21', '0.00'),
(7, 7, '350.00', 'qr_code', 'pending', '2025-06-21', '0.00');

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `receipt_id` int NOT NULL,
  `payment_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `upload_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_receipts`
--

INSERT INTO `payment_receipts` (`receipt_id`, `payment_id`, `file_path`, `file_type`, `upload_date`) VALUES
(1, 1, 'uploads/payment_receipts/2025/06/receipt_68558212dffa8_1750434322.jpg', 'jpg', '2025-06-20'),
(2, 2, 'uploads/payment_receipts/2025/06/receipt_68558287d3e04_1750434439.jpg', 'jpg', '2025-06-20'),
(3, 3, 'uploads/payment_receipts/2025/06/receipt_685583383e222_1750434616.jpg', 'jpg', '2025-06-20'),
(4, 4, 'uploads/payment_receipts/2025/06/receipt_685583a5b6a2c_1750434725.jpg', 'jpg', '2025-06-20'),
(5, 5, 'uploads/payment_receipts/2025/06/receipt_685586475bec7_1750435399.jpg', 'jpg', '2025-06-21'),
(6, 6, 'uploads/payment_receipts/2025/06/receipt_6855869575d1c_1750435477.jpg', 'jpg', '2025-06-21'),
(7, 7, 'uploads/payment_receipts/2025/06/receipt_685588a0a2b70_1750436000.jpg', 'jpg', '2025-06-21');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `homestay_id` int DEFAULT NULL,
  `ratings` varchar(255) DEFAULT NULL,
  `comment` text,
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `booking_id`, `user_id`, `homestay_id`, `ratings`, `comment`, `status`) VALUES
(1, 6, 10, 1, '5', 'Very Mature, Very Demure', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('guest','admin') DEFAULT 'guest',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `role`, `created_at`) VALUES
(1, 'Encik Fadzil', 'admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'admin', '2025-06-02 15:15:24'),
(9, 'Farhan Danial', 'farhandanial@gmail.com', '$2y$10$TMp7xJ2ofdKBrHiEcwsQpOKA/PFZ7dqndKKjCEvYWccVwWWxfiJt2', '0123456789', 'guest', '2025-06-20 15:16:20'),
(10, 'Mr Meow', 'meow@gmail.com', '$2y$10$BzJUypNQx9DDuLoi2xdgxuS8HAcOIfh10VKB1X4CrJ3UHhyfTJC1q', '0163636087', 'guest', '2025-06-20 16:03:55'),
(11, 'Muhammad', 'muhammadirfanrozlan@gmail.com', '$2y$10$2RnOkCcJFGfQM0XLnjQdPexK4el.bZ1BNlBxY3K7n4x3F1o.7o79W', '0163636087', 'guest', '2025-06-20 16:09:39');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `homestay_id` (`homestay_id`);

--
-- Indexes for table `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD PRIMARY KEY (`code_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `homestays`
--
ALTER TABLE `homestays`
  ADD PRIMARY KEY (`homestay_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `booking_id` (`booking_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `homestay_id` (`homestay_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `discount_codes`
--
ALTER TABLE `discount_codes`
  MODIFY `code_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `homestays`
--
ALTER TABLE `homestays`
  MODIFY `homestay_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `receipt_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`homestay_id`) REFERENCES `homestays` (`homestay_id`);

--
-- Constraints for table `discount_codes`
--
ALTER TABLE `discount_codes`
  ADD CONSTRAINT `discount_codes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD CONSTRAINT `payment_receipts_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`homestay_id`) REFERENCES `homestays` (`homestay_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
