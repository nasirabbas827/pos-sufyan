-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 17, 2026 at 09:01 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pos_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(2, 'Electronics', '2026-01-17 10:51:01'),
(3, 'Groceries', '2026-01-17 10:51:01'),
(4, 'Beverages', '2026-01-17 10:51:01'),
(5, 'Bakery', '2026-01-17 10:51:01'),
(6, 'Dairy Products', '2026-01-17 10:51:01'),
(7, 'Frozen Foods', '2026-01-17 10:51:01'),
(8, 'Fruits', '2026-01-17 10:51:01'),
(9, 'Vegetables', '2026-01-17 10:51:01'),
(10, 'Meat & Poultry', '2026-01-17 10:51:01'),
(11, 'Seafood', '2026-01-17 10:51:01'),
(12, 'Snacks', '2026-01-17 10:51:01'),
(13, 'Confectionery', '2026-01-17 10:51:01'),
(14, 'Personal Care', '2026-01-17 10:51:01'),
(15, 'Household Items', '2026-01-17 10:51:01'),
(16, 'Cleaning Supplies', '2026-01-17 10:51:01'),
(17, 'Stationery', '2026-01-17 10:51:01'),
(18, 'Baby Products', '2026-01-17 10:51:01'),
(19, 'Pet Supplies', '2026-01-17 10:51:01'),
(20, 'Pharmacy', '2026-01-17 10:51:01'),
(21, 'Hardware', '2026-01-17 10:51:01');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `address`, `email`, `phone_number`, `created_at`) VALUES
(1, 'Alice Johnson', '123 Main St', 'alice@example.com', '1234567890', '2026-01-17 11:05:28'),
(2, 'Bob Smith', '456 Oak Ave', 'bob@example.com', '2345678901', '2026-01-17 11:05:28'),
(3, 'Carol Davis', '789 Pine Rd', 'carol@example.com', '3456789012', '2026-01-17 11:05:28'),
(4, 'David Brown', '321 Maple St', 'david@example.com', '4567890123', '2026-01-17 11:05:28'),
(5, 'Eve Wilson', '654 Cedar Ave', 'eve@example.com', '5678901234', '2026-01-17 11:05:28'),
(6, 'Frank Miller', '987 Birch Rd', 'frank@example.com', '6789012345', '2026-01-17 11:05:28'),
(7, 'Grace Lee', '147 Elm St', 'grace@example.com', '7890123456', '2026-01-17 11:05:28'),
(8, 'Hank Taylor', '258 Spruce Ave', 'hank@example.com', '8901234567', '2026-01-17 11:05:28'),
(9, 'Ivy Anderson', '369 Walnut Rd', 'ivy@example.com', '9012345678', '2026-01-17 11:05:28'),
(10, 'Jack Thomas', '741 Chestnut St', 'jack@example.com', '0123456789', '2026-01-17 11:05:28'),
(11, 'Karen White', '852 Hickory Ave', 'karen@example.com', '1234509876', '2026-01-17 11:05:28'),
(12, 'Leo Harris', '963 Poplar Rd', 'leo@example.com', '2345610987', '2026-01-17 11:05:28'),
(13, 'Mia Martin', '159 Aspen St', 'mia@example.com', '3456721098', '2026-01-17 11:05:28'),
(14, 'Nick Clark', '753 Willow Ave', 'nick@example.com', '4567832109', '2026-01-17 11:05:28'),
(15, 'Olivia Lewis', '852 Cypress Rd', 'olivia@example.com', '5678943210', '2026-01-17 11:05:28'),
(16, 'Paul Walker', '951 Redwood St', 'paul@example.com', '6789054321', '2026-01-17 11:05:28'),
(17, 'Quinn Hall', '357 Fir Ave', 'quinn@example.com', '7890165432', '2026-01-17 11:05:28'),
(18, 'Rachel Allen', '654 Magnolia Rd', 'rachel@example.com', '8901276543', '2026-01-17 11:05:28'),
(19, 'Sam Young', '852 Dogwood St', 'sam@example.com', '9012387654', '2026-01-17 11:05:28'),
(20, 'Tina King', '159 Sequoia Ave', 'tina@example.com', '0123498765', '2026-01-17 11:05:28'),
(23, 'Walk-in Customer', NULL, NULL, NULL, '2026-01-17 11:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 5,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `image`, `category_id`, `product_name`, `cost_price`, `selling_price`, `stock_quantity`, `low_stock_threshold`, `created_at`, `updated_at`) VALUES
(1, '1768629508_App.jpg', 2, 'Organic Apples', 10.00, 15.00, 10, 10, '2026-01-17 11:02:34', '2026-01-17 12:47:17'),
(2, '1768629508_App.jpg', 3, 'Whole Wheat Bread', 8.50, 12.00, 40, 5, '2026-01-17 11:02:34', '2026-01-17 11:39:47'),
(3, '1768629508_App.jpg', 4, 'Fresh Orange Juice', 5.00, 7.50, 181, 20, '2026-01-17 11:02:34', '2026-01-17 11:54:39'),
(4, '1768629508_App.jpg', 5, 'Cheddar Cheese', 20.00, 30.00, 80, 10, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(5, '1768629508_App.jpg', 6, 'Frozen Peas', 12.50, 18.00, 11, 8, '2026-01-17 11:02:34', '2026-01-17 12:47:02'),
(6, '1768629508_App.jpg', 7, 'Bananas', 7.00, 10.00, 0, 15, '2026-01-17 11:02:34', '2026-01-17 12:47:30'),
(7, '1768629508_App.jpg', 8, 'Tomatoes', 15.00, 22.50, 2, 5, '2026-01-17 11:02:34', '2026-01-17 12:47:25'),
(8, '1768629508_App.jpg', 9, 'Chicken Breast', 6.00, 9.00, 15, 12, '2026-01-17 11:02:34', '2026-01-17 12:47:09'),
(9, '1768629508_App.jpg', 10, 'Salmon Fillet', 3.50, 5.00, 300, 25, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(10, '1768629508_App.jpg', 11, 'Potato Chips', 18.00, 27.00, 70, 10, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(11, '1768629508_App.jpg', 12, 'Chocolate Bar', 4.50, 6.50, 180, 15, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(12, '1768629508_App.jpg', 13, 'Shampoo', 9.00, 13.50, 90, 10, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(13, '1768629508_App.jpg', 14, 'Dishwashing Liquid', 2.50, 4.00, 250, 20, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(14, '1768629508_App.jpg', 15, 'Laundry Detergent', 11.00, 16.50, 60, 8, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(15, '1768629508_App.jpg', 16, 'Notebook', 5.50, 8.00, 140, 12, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(16, '1768629508_App.jpg', 17, 'Baby Diapers', 7.50, 11.00, 100, 10, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(17, '1768629508_App.jpg', 18, 'Dog Food', 6.00, 9.00, 200, 15, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(18, '1768629508_App.jpg', 19, 'Paracetamol Tablets', 8.00, 12.00, 80, 10, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(19, '1768629508_App.jpg', 20, 'Hammer', 10.00, 15.00, 120, 20, '2026-01-17 11:02:34', '2026-01-17 11:02:34'),
(20, '1768629508_App.jpg', 21, 'Screwdriver Set', 3.00, 5.00, 300, 30, '2026-01-17 11:02:34', '2026-01-17 11:02:34');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payable_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('Cash','Card','Online') NOT NULL DEFAULT 'Cash',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `customer_id`, `cashier_id`, `total_amount`, `discount_amount`, `payable_amount`, `payment_method`, `created_at`) VALUES
(1, 'INV-260117075044-002', 3, 2, 1080.00, 30.00, 1050.00, 'Card', '2026-01-17 11:50:44'),
(3, 'INV-260117075439-C02', 2, 2, 142.50, 0.00, 142.50, 'Cash', '2026-01-17 11:54:39');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 5, 60, 18.00, 1080.00),
(3, 3, 3, 19, 7.50, 142.50);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `usertype` enum('admin','manager','cashier') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `usertype`, `status`, `created_at`) VALUES
(1, 'Manager', 'manager@gmail.com', '$2y$10$PyWMZAHpEw6l/m8vDO58P./L9nMLZYVD9lnJX3bGd93UXkQrDmx..', '03176526827', 'manager', 'active', '2026-01-17 10:45:11'),
(2, 'Cashier', 'cashier@gmail.com', '$2y$10$ZjpO2CM2V0N2Psk4D/HDMu9UDUsD/I/Yz6UArwJ9eyLyedJvAK.nS', '3176526823', 'cashier', 'active', '2026-01-17 10:47:05'),
(3, 'Cashier2', 'cashier2@gmail.com', '$2y$10$knJiqzBefcFG1oIlNrr80.AIwNYXrHVIJXQn3RXxUvP3eBOdS6OsO', '03120558090', 'cashier', 'active', '2026-01-17 11:18:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_category_name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
