-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 30, 2025 at 01:24 AM
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
-- Database: `ci4_iseng`
--

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` bigint UNSIGNED NOT NULL,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price` decimal(10,0) DEFAULT '0',
  `has_variants` tinyint(1) NOT NULL DEFAULT '0',
  `order_type` enum('manual','auto') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'manual',
  `target_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `icon_filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `user_id`, `product_name`, `description`, `price`, `has_variants`, `order_type`, `target_url`, `icon_filename`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'you', 'yyy', '1000', 0, 'auto', NULL, '1761723730_1dfbab08a98c3c6c404e.jpg', 1, '2025-10-29 14:42:10', '2025-10-29 14:42:10');

-- --------------------------------------------------------

--
-- Table structure for table `product_stock`
--

CREATE TABLE `product_stock` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `variant_id` int UNSIGNED DEFAULT NULL,
  `stock_data` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `buyer_email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `transaction_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_stock`
--

INSERT INTO `product_stock` (`id`, `product_id`, `variant_id`, `stock_data`, `is_used`, `buyer_email`, `transaction_id`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '{\"email\": \"akun2@mail.com\", \"password\": \"pass2\"}', 0, NULL, NULL, '2025-10-29 14:42:18', '2025-10-29 14:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10,0) NOT NULL DEFAULT '0',
  `stock` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int UNSIGNED NOT NULL,
  `order_id` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED DEFAULT NULL,
  `variant_id` int UNSIGNED DEFAULT NULL,
  `variant_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `buyer_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `buyer_email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `transaction_type` enum('premium','product') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'product',
  `amount` decimal(10,0) NOT NULL,
  `quantity` int UNSIGNED NOT NULL DEFAULT '1',
  `status` enum('pending','success','failed','expired','challenge') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `snap_token` text COLLATE utf8mb4_general_ci,
  `tripay_reference` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tripay_pay_url` text COLLATE utf8mb4_general_ci,
  `tripay_raw` text COLLATE utf8mb4_general_ci,
  `payment_gateway` enum('midtrans','tripay','orderkuota') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'midtrans',
  `midtrans_key_source` enum('default','user') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'default',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `zeppelin_reference_id` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zeppelin_paid_amount` decimal(15,0) DEFAULT NULL,
  `zeppelin_qr_url` text COLLATE utf8mb4_general_ci,
  `zeppelin_expiry_date` datetime DEFAULT NULL,
  `zeppelin_raw_response` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `order_id`, `user_id`, `product_id`, `variant_id`, `variant_name`, `buyer_name`, `buyer_email`, `transaction_type`, `amount`, `quantity`, `status`, `snap_token`, `tripay_reference`, `tripay_pay_url`, `tripay_raw`, `payment_gateway`, `midtrans_key_source`, `created_at`, `updated_at`, `zeppelin_reference_id`, `zeppelin_paid_amount`, `zeppelin_qr_url`, `zeppelin_expiry_date`, `zeppelin_raw_response`) VALUES
(1, 'PROD-1-1761725490-YAQG', 1, 1, NULL, NULL, 'gt', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', 'fd820c15-a146-44a1-9a7f-e4331b2f8e58', NULL, NULL, NULL, 'midtrans', 'user', '2025-10-29 15:11:30', '2025-10-29 15:11:34', NULL, NULL, NULL, NULL, NULL),
(2, 'PROD-1-1761725551-1XSM', 1, 1, NULL, NULL, 'gt', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', '7800a74e-dbb0-40a3-a5c5-24553ae5eeff', NULL, NULL, NULL, 'midtrans', 'user', '2025-10-29 15:12:31', '2025-10-29 15:12:32', NULL, NULL, NULL, NULL, NULL),
(3, 'PROD-1-1761725577-VGCW', 1, 1, NULL, NULL, 'gt', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', '6b060c21-2614-4ea8-ba1d-155759e14e00', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 15:12:57', '2025-10-29 15:12:57', NULL, NULL, NULL, NULL, NULL),
(4, 'PROD-1-1761725632-Q7TO', 1, 1, NULL, NULL, 'gt', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', 'abae23eb-71f2-4c74-9b05-1bb01b42a4a7', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 15:13:52', '2025-10-29 15:13:52', NULL, NULL, NULL, NULL, NULL),
(5, 'PROD-1-1761725682-BIWW', 1, 1, NULL, NULL, 'tar', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', '9d9fa6af-727a-4a8f-98a6-7a45c211b135', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 15:14:42', '2025-10-29 15:14:42', NULL, NULL, NULL, NULL, NULL),
(6, 'PROD-1-1761725741-0S6W', 1, 1, NULL, NULL, 'taremanuk', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', '8411ea04-5ead-4e35-8b7f-91d93a69bf12', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 15:15:41', '2025-10-29 15:15:41', NULL, NULL, NULL, NULL, NULL),
(7, 'PROD-1-1761725998-5A1B', 1, 1, NULL, NULL, 'tar', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', 'd91af534-e3f8-462a-a96f-330eef3a8121', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 15:19:58', '2025-10-29 15:19:58', NULL, NULL, NULL, NULL, NULL),
(8, 'PROD-1-1761726018-F5CI', 1, 1, NULL, NULL, 'tar', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', '9620cb0a-635c-4a65-9521-ea71abc4083c', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 15:20:18', '2025-10-29 15:20:19', NULL, NULL, NULL, NULL, NULL),
(9, 'PROD-1-1761726054-M5RD', 1, 1, NULL, NULL, 'tar', 'sindadi53@gmail.com', 'product', '1000', 1, 'pending', 'd3c47a42-5d48-42a8-82ab-3e38a092df72', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 15:20:54', '2025-10-29 15:20:54', NULL, NULL, NULL, NULL, NULL),
(13, 'PROD-1-1761733322-P0JC', 1, 1, NULL, NULL, 'tar', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', '6fd438c3-0b5c-481b-ae27-09bb8aa4be4c', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 17:22:02', '2025-10-29 17:22:02', NULL, NULL, NULL, NULL, NULL),
(14, 'PROD-1-1761733534-OZO2', 1, 1, NULL, NULL, 'tar', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', 'e7199c04-5212-4467-ac0e-46bf31eb4d74', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 17:25:34', '2025-10-29 17:25:34', NULL, NULL, NULL, NULL, NULL),
(17, 'PROD-1-1761735334-VC5S', 1, 1, NULL, NULL, 'tar', 'taremanuk@gmail.com', 'product', '1000', 1, 'pending', '598f2b20-7abf-499a-a77e-4e5e79b8850e', NULL, NULL, NULL, 'midtrans', 'default', '2025-10-29 17:55:34', '2025-10-29 17:55:34', NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `store_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profile_subtitle` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo_filename` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reset_token_hash` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `is_premium` tinyint(1) NOT NULL DEFAULT '0',
  `balance` decimal(15,0) NOT NULL DEFAULT '0',
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_name` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `midtrans_server_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `midtrans_client_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tripay_api_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tripay_private_key` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tripay_merchant_code` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gateway_active` enum('system','midtrans','tripay','orderkuota') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'system',
  `zeppelin_auth_username` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `zeppelin_auth_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `whatsapp_link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `store_name`, `profile_subtitle`, `logo_filename`, `email`, `password_hash`, `reset_token_hash`, `reset_token_expires_at`, `is_admin`, `is_premium`, `balance`, `bank_name`, `account_number`, `account_name`, `midtrans_server_key`, `midtrans_client_key`, `tripay_api_key`, `tripay_private_key`, `tripay_merchant_code`, `gateway_active`, `zeppelin_auth_username`, `zeppelin_auth_token`, `whatsapp_link`, `created_at`, `updated_at`) VALUES
(1, 'danish', NULL, NULL, NULL, 'danish@gmail.com', '$2y$10$oXW3JD08h3s.mxcL9zgT8.iiCWWrrBLx..v.Fjsj/erpLJHs8x72m', NULL, NULL, 1, 1, '0', NULL, NULL, NULL, NULL, NULL, 'X5Uiab1PhZQZ39AP0mypTqZC8UfNDIHP2bkVOMdM', 'gf1Bj-HKSSw-SmkRO-ABMRu-99yQR', 'T44799', 'orderkuota', 'sidanzku', '2677295:FhkqzclRLPd3Ug0YEDVZrCi2fbHa9nKA', NULL, '2025-10-29 14:41:04', '2025-10-29 17:55:54');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `amount` decimal(15,0) NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `bank_details` text COLLATE utf8mb4_general_ci,
  `processed_at` datetime DEFAULT NULL,
  `admin_notes` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `products_user_id_foreign` (`user_id`);

--
-- Indexes for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_stock_product_id_foreign` (`product_id`),
  ADD KEY `product_stock_transaction_id_foreign` (`transaction_id`),
  ADD KEY `product_id` (`product_id`,`is_used`),
  ADD KEY `product_stock_variant_id_foreign` (`variant_id`),
  ADD KEY `variant_id` (`variant_id`,`is_used`);

--
-- Indexes for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_variants_product_id_foreign` (`product_id`),
  ADD KEY `product_id` (`product_id`,`name`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `transactions_user_id_foreign` (`user_id`),
  ADD KEY `transactions_product_id_foreign` (`product_id`),
  ADD KEY `transactions_variant_id_foreign` (`variant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `reset_token_hash` (`reset_token_hash`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `withdrawal_requests_user_id_foreign` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_stock`
--
ALTER TABLE `product_stock`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_stock`
--
ALTER TABLE `product_stock`
  ADD CONSTRAINT `product_stock_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_stock_transaction_id_foreign` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `product_stock_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE SET NULL,
  ADD CONSTRAINT `transactions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `transactions_variant_id_foreign` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE SET NULL;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
