-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 30, 2026 at 11:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lime_dime_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(60) NOT NULL DEFAULT 'Home',
  `line1` varchar(200) NOT NULL,
  `city` varchar(100) NOT NULL DEFAULT '',
  `state` varchar(60) NOT NULL DEFAULT '',
  `zip` varchar(20) NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED DEFAULT NULL,
  `order_line_id` int(10) UNSIGNED DEFAULT NULL,
  `payment_method` varchar(60) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT '',
  `email` varchar(255) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','archived') NOT NULL DEFAULT 'unread',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `first_name`, `last_name`, `email`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Jack', 'Tax', 'jax@gmail.com', 'Account help', 'Hello', 'archived', '2026-05-28 01:14:11');

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `courier_id` int(10) UNSIGNED NOT NULL,
  `dropoff_address` varchar(300) DEFAULT NULL,
  `distance_km` decimal(6,2) DEFAULT NULL,
  `payout` decimal(8,2) NOT NULL DEFAULT 0.00,
  `eta_minutes` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('assigned','picked_up','out_for_delivery','delivered') NOT NULL DEFAULT 'assigned',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivered_at` timestamp NULL DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `order_id`, `courier_id`, `dropoff_address`, `distance_km`, `payout`, `eta_minutes`, `status`, `assigned_at`, `delivered_at`, `rating`) VALUES
(5, 5, 8, 'Prime Living, Famagusta', 0.00, 5.00, 30, 'delivered', '2026-05-27 16:16:20', '2026-05-27 16:47:43', NULL),
(6, 6, 8, 'N/A', 0.00, 5.00, 30, 'assigned', '2026-05-27 16:54:57', NULL, NULL),
(7, 7, 8, 'N/A', 0.00, 5.00, 30, 'picked_up', '2026-05-27 16:54:58', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(100) NOT NULL,
  `file_path` varchar(300) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(10) UNSIGNED NOT NULL,
  `customer_id` int(10) UNSIGNED NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','packed','out_for_delivery','delivered','cancelled','cancel_requested') NOT NULL DEFAULT 'pending',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cancel_note` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_date`, `status`, `total`, `tax`, `cancel_note`) VALUES
(5, 2, '2026-05-27 16:15:41', 'delivered', 20.40, 0.00, NULL),
(6, 2, '2026-05-27 16:50:40', 'cancel_requested', 19.00, 0.00, 'cancel please'),
(7, 2, '2026-05-27 16:50:57', 'out_for_delivery', 16.90, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_line`
--

CREATE TABLE `order_line` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `amount` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_line`
--

INSERT INTO `order_line` (`id`, `order_id`, `product_id`, `amount`, `total_price`) VALUES
(1, 5, 1, 2, 10.00),
(2, 5, 3, 1, 3.50),
(3, 5, 5, 2, 2.00),
(4, 6, 1, 1, 5.00),
(5, 7, 4, 1, 3.00),
(6, 7, 2, 3, 9.00);

-- --------------------------------------------------------

--
-- Table structure for table `payment_cards`
--

CREATE TABLE `payment_cards` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `brand` varchar(30) NOT NULL DEFAULT 'Visa',
  `last4` char(4) NOT NULL,
  `exp_month` tinyint(4) NOT NULL,
  `exp_year` smallint(6) NOT NULL,
  `name_on_card` varchar(120) NOT NULL DEFAULT '',
  `is_default` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `platform_settings`
--

CREATE TABLE `platform_settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `platform_settings`
--

INSERT INTO `platform_settings` (`key`, `value`) VALUES
('commission_featured_boost', '24'),
('commission_new_seller', '5'),
('commission_standard', '8.5'),
('regions', 'Portland,Brooklyn,Seattle,Austin,Chicago,Denver,Boston,Oakland');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `shop_id` int(10) UNSIGNED NOT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 4.50,
  `reviews_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `category` varchar(60) NOT NULL DEFAULT 'food',
  `tag` varchar(60) DEFAULT NULL,
  `illu_type` varchar(40) NOT NULL DEFAULT 'jar',
  `palette_1` varchar(20) NOT NULL DEFAULT '#E1DD8F',
  `palette_2` varchar(20) NOT NULL DEFAULT '#C8C36A',
  `status` enum('live','draft','archived') NOT NULL DEFAULT 'live',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `product_image` varchar(200) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `quantity`, `shop_id`, `rating`, `reviews_count`, `category`, `tag`, `illu_type`, `palette_1`, `palette_2`, `status`, `created_at`, `product_image`) VALUES
(1, 'Burger', '', 5.00, 9, 1, 0.00, 0, 'food', NULL, 'jar', '#E0777D', '#8E3B46', 'live', '2026-05-27 11:33:42', '1.png'),
(2, 'French Fries', '', 3.00, 3, 1, 0.00, 0, 'food', NULL, 'jar', '#E0777D', '#8E3B46', 'live', '2026-05-27 12:36:46', '2.png'),
(3, 'Milkshake', '', 3.50, 10, 1, 0.00, 0, 'food', NULL, 'jar', '#E0777D', '#8E3B46', 'live', '2026-05-27 12:37:16', '3.png'),
(4, 'Waffle', '', 3.00, 10, 1, 0.00, 0, 'food', NULL, 'jar', '#E0777D', '#8E3B46', 'live', '2026-05-27 12:37:34', '4.png'),
(5, 'Icecream Sundae', '', 7.50, 10, 1, 0.00, 0, 'food', NULL, 'jar', '#E0777D', '#8E3B46', 'live', '2026-05-27 12:38:32', '5.png');

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `type` varchar(60) NOT NULL DEFAULT 'food',
  `location` varchar(200) DEFAULT NULL,
  `manager_id` int(10) UNSIGNED NOT NULL,
  `rating` decimal(3,2) NOT NULL DEFAULT 4.50,
  `followers_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `accent_color` varchar(20) NOT NULL DEFAULT '#E0777D',
  `avatar` varchar(10) NOT NULL DEFAULT '??',
  `pattern` varchar(40) NOT NULL DEFAULT 'dots',
  `status` enum('active','pending','suspended') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `cover_image` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `business_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`id`, `name`, `type`, `location`, `manager_id`, `rating`, `followers_count`, `accent_color`, `avatar`, `pattern`, `status`, `created_at`, `cover_image`, `description`, `business_type`) VALUES
(1, 'Amigos Frozen Food', 'food', 'Famagusta 99450', 5, 4.50, 0, '#98aea2', '🌮', 'dots', 'active', '2026-05-27 06:42:53', '1.png', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `shop_follows`
--

CREATE TABLE `shop_follows` (
  `user_id` int(10) UNSIGNED NOT NULL,
  `shop_id` int(10) UNSIGNED NOT NULL,
  `followed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `role` enum('customer','seller','courier','admin') NOT NULL DEFAULT 'customer',
  `status` enum('active','pending','suspended') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `avatar` varchar(200) DEFAULT NULL,
  `is_online` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `dob`, `city`, `role`, `status`, `created_at`, `avatar`, `is_online`) VALUES
(2, 'Demo Customer', 'customer@limedime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '', NULL, NULL, 'customer', 'active', '2026-05-27 05:55:33', NULL, 0),
(4, 'Admin 1', 'admin@limedime.com', '$2y$10$HyyPCBDA7yaTbYeNmApYYePD1s8rxikrp0LIOHvF.1KijtD4r6Aiu', '', NULL, NULL, 'admin', 'active', '2026-05-27 06:06:18', '4.png', 0),
(5, 'Miguel Angel Feo', 'seller@limedime.com', '$2y$10$tjz/lwNCaQAC5T009pDAbOccgJ3KLivLtOoh2oIXeRUg5Akyu2FWi', '', NULL, NULL, 'seller', 'active', '2026-05-27 06:42:53', NULL, 0),
(8, 'Sam', 'courier@limedime.com', '$2y$10$DMiFV58E0y9kQJMe9o41pO8Hb4EY32LmeTaCaUSVxajyx00403uke', '', '1989-05-27', 'Famagusta', 'courier', 'active', '2026-05-27 10:46:18', NULL, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `order_line_id` (`order_line_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `courier_id` (`courier_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_line`
--
ALTER TABLE `order_line`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payment_cards`
--
ALTER TABLE `payment_cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `platform_settings`
--
ALTER TABLE `platform_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shop_id` (`shop_id`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`id`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `shop_follows`
--
ALTER TABLE `shop_follows`
  ADD PRIMARY KEY (`user_id`,`shop_id`),
  ADD KEY `idx_shop` (`shop_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `order_line`
--
ALTER TABLE `order_line`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_cards`
--
ALTER TABLE `payment_cards`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cart_ibfk_3` FOREIGN KEY (`order_line_id`) REFERENCES `order_line` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`courier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_line`
--
ALTER TABLE `order_line`
  ADD CONSTRAINT `order_line_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_line_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_cards`
--
ALTER TABLE `payment_cards`
  ADD CONSTRAINT `payment_cards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`shop_id`) REFERENCES `shops` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shops`
--
ALTER TABLE `shops`
  ADD CONSTRAINT `shops_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
