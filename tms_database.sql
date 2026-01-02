-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 02, 2026 at 03:00 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tms_database`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `measurements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON field for bust, waist, hips, length, etc.' CHECK (json_valid(`measurements`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `user_id`, `name`, `phone`, `address`, `measurements`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'kapil tamang', '9779769707475', '123 Main Street, City, Country', '{\"bust\":\"36\",\"waist\":\"30\",\"hips\":\"38\",\"shoulder\":\"16\",\"sleeve_length\":\"24\",\"shirt_length\":\"28\",\"pants_length\":\"32\",\"notes\":\"Standard measurements\"}', 'kapil', '2025-11-09 13:52:52', '2025-11-10 02:45:13'),
(2, 4, 'kapil tamang', '9769707475', 'morang khorsane', NULL, NULL, '2025-11-09 14:10:58', '2025-11-09 14:10:58');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `admin_response` text DEFAULT NULL COMMENT 'Admin response to customer feedback',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `type` enum('fabric','material','accessory') NOT NULL DEFAULT 'fabric',
  `description` text DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'meters' COMMENT 'meters, pieces, kg, etc.',
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `low_stock_threshold` decimal(10,2) NOT NULL DEFAULT 10.00,
  `supplier` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `status` enum('available','out_of_stock','discontinued') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_name`, `type`, `description`, `quantity`, `unit`, `price`, `low_stock_threshold`, `supplier`, `color`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cotton Fabric', 'fabric', 'Premium quality cotton fabric', 98.00, 'meters', 25.50, 20.00, 'Fabric Supplier Co.', 'White', 'available', '2025-11-09 13:52:52', '2026-01-01 15:45:47'),
(2, 'Silk Fabric', 'fabric', 'Luxury silk fabric', 50.00, 'meters', 85.00, 10.00, 'Luxury Fabrics Ltd.', 'Red', 'available', '2025-11-09 13:52:52', '2025-11-09 13:52:52'),
(3, 'Buttons', 'material', 'Standard shirt buttons', 500.00, 'pieces', 0.50, 100.00, 'Material Supply Inc.', 'White', 'available', '2025-11-09 13:52:52', '2025-11-09 13:52:52'),
(4, 'Zipper', 'material', 'Standard zipper 12 inch', 200.00, 'pieces', 3.50, 50.00, 'Material Supply Inc.', 'Black', 'available', '2025-11-09 13:52:52', '2025-11-09 13:52:52'),
(5, 'Thread', 'material', 'Polyester thread', 1000.00, 'meters', 2.00, 200.00, 'Material Supply Inc.', 'Various', 'available', '2025-11-09 13:52:52', '2025-11-09 13:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order_update','payment_received','task_assigned','delivery_scheduled','system','feedback') NOT NULL DEFAULT 'system',
  `related_id` int(11) DEFAULT NULL COMMENT 'ID of related order, task, etc.',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `type`, `related_id`, `is_read`, `read_at`, `created_at`) VALUES
(1, 3, 'Delivery date scheduled for Order #ORD-20251110-0001: Nov 12, 2025', 'delivery_scheduled', 1, 1, '2025-11-10 03:45:00', '2025-11-10 03:44:45'),
(2, 3, 'Delivery date scheduled for Order #ORD-20251110-0001: Nov 12, 2025', 'delivery_scheduled', 1, 1, '2025-11-11 05:07:23', '2025-11-11 05:06:37'),
(3, 3, 'Delivery date scheduled for Order #ORD-20251110-0001: Nov 11, 2025', 'delivery_scheduled', 1, 1, '2025-11-11 05:07:23', '2025-11-11 05:07:00'),
(4, 3, 'Order #ORD-20251110-0001: Your order has been completed!', 'order_update', 1, 1, '2025-11-11 05:07:23', '2025-11-11 05:07:04'),
(5, 3, 'Order #ORD-20251110-0001: Your order has been completed!', 'order_update', 1, 1, '2025-11-11 05:07:23', '2025-11-11 05:07:06'),
(6, 2, 'New task assigned for Order #ORD-20260101-0001: do it', 'task_assigned', 2, 0, NULL, '2026-01-01 15:53:44'),
(7, 3, 'Order #ORD-20260101-0001: Your order is now in progress.', 'order_update', 2, 0, NULL, '2026-01-01 15:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','in-progress','completed','cancelled','delivered') NOT NULL DEFAULT 'pending',
  `delivery_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `advance_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `average_rating` decimal(3,2) DEFAULT NULL COMMENT 'Average rating from customer feedback (1.00 to 5.00)',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_number`, `description`, `status`, `delivery_date`, `total_amount`, `advance_amount`, `remaining_amount`, `average_rating`, `notes`, `created_at`, `updated_at`, `completed_at`) VALUES
(1, 1, 'ORD-20251110-0001', 'suit', 'completed', '2025-11-11', 25.50, 0.00, 25.50, NULL, 'thankyou', '2025-11-10 03:28:44', '2025-11-11 05:07:06', '2025-11-11 00:22:06'),
(2, 1, 'ORD-20260101-0001', 'wedding dress', 'in-progress', '2026-01-03', 25.50, 0.00, 25.50, NULL, 'kapil', '2026-01-01 15:45:47', '2026-01-01 15:53:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_inventory`
--

CREATE TABLE `order_inventory` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_used` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `order_inventory`
--
DELIMITER $$
CREATE TRIGGER `trg_update_inventory_quantity` AFTER INSERT ON `order_inventory` FOR EACH ROW BEGIN
    UPDATE inventory 
    SET quantity = quantity - NEW.quantity_used,
        status = CASE 
            WHEN (quantity - NEW.quantity_used) <= 0 THEN 'out_of_stock'
            WHEN (quantity - NEW.quantity_used) <= low_stock_threshold THEN 'available'
            ELSE status
        END
    WHERE id = NEW.inventory_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `item_name`, `description`, `quantity`, `price`, `subtotal`, `created_at`) VALUES
(1, 1, 'Cotton Fabric', NULL, 1, 25.50, 25.50, '2025-11-10 03:28:44'),
(2, 2, 'Cotton Fabric', NULL, 1, 25.50, 25.50, '2026-01-01 15:45:47');

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `trg_calculate_item_subtotal` BEFORE INSERT ON `order_items` FOR EACH ROW BEGIN
    SET NEW.subtotal = NEW.quantity * NEW.price;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','bank_transfer','mobile_payment','cheque') NOT NULL DEFAULT 'cash',
  `receipt_url` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `trg_update_order_remaining` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
    UPDATE orders 
    SET remaining_amount = total_amount - (
        SELECT COALESCE(SUM(amount), 0) 
        FROM payments 
        WHERE order_id = NEW.order_id
    )
    WHERE id = NEW.order_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `staff_tasks`
--

CREATE TABLE `staff_tasks` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `task_description` text NOT NULL,
  `status` enum('assigned','in-progress','completed','cancelled') NOT NULL DEFAULT 'assigned',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_tasks`
--

INSERT INTO `staff_tasks` (`id`, `staff_id`, `order_id`, `task_description`, `status`, `priority`, `assigned_at`, `started_at`, `completed_at`, `due_date`, `notes`) VALUES
(1, 2, 1, 'do it', 'cancelled', 'high', '2025-11-10 03:29:24', '2025-11-09 22:45:45', '2025-11-11 00:21:44', '2025-11-11', ''),
(2, 2, 2, 'do it', 'assigned', 'urgent', '2026-01-01 15:53:44', NULL, NULL, '2026-01-03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@tms.com', '$2y$10$h5FW3zuPG6nsT2LV1Xyog.GlyGcxWOvgV6D5LGzxTVp.ydVttoDgq', 'admin', 'active', '2025-11-09 13:52:52', '2025-11-09 13:59:55'),
(2, 'staff1', 'staff@tms.com', '$2y$10$8XIEV3EgWhW/I3IDqQNw8eazqJogscF8KN5N5kjgt5PmbnSb7SvHi', 'staff', 'active', '2025-11-09 13:52:52', '2025-11-09 13:59:55'),
(3, 'customer1', 'customer@tms.com', '$2y$10$Ah1MuAwGcs.1V0LD4lUfY.DLidSuoFifdSWBmJGjOzm8OI0Gc.tsS', 'customer', 'active', '2025-11-09 13:52:52', '2025-11-09 13:59:56'),
(4, 'kapil123', 'kapil123@gmail.com', '$2y$10$4IiXIzKaqJrAsOcPDca9h.xTdAPKGwBJZAZgS.P8GRjrVoPHloq86', 'customer', 'active', '2025-11-09 14:10:58', '2025-11-09 14:10:58');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_low_stock`
-- (See below for the actual view)
--
CREATE TABLE `v_low_stock` (
`id` int(11)
,`item_name` varchar(100)
,`type` enum('fabric','material','accessory')
,`quantity` decimal(10,2)
,`low_stock_threshold` decimal(10,2)
,`stock_difference` decimal(11,2)
,`status` enum('available','out_of_stock','discontinued')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_order_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_order_summary` (
`id` int(11)
,`order_number` varchar(50)
,`customer_id` int(11)
,`customer_name` varchar(100)
,`customer_phone` varchar(20)
,`status` enum('pending','in-progress','completed','cancelled','delivered')
,`total_amount` decimal(10,2)
,`advance_amount` decimal(10,2)
,`remaining_amount` decimal(10,2)
,`delivery_date` date
,`created_at` timestamp
,`item_count` bigint(21)
,`total_paid` decimal(32,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_payment_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_payment_summary` (
`id` int(11)
,`payment_number` varchar(50)
,`order_id` int(11)
,`order_number` varchar(50)
,`customer_name` varchar(100)
,`amount` decimal(10,2)
,`payment_method` enum('cash','card','bank_transfer','mobile_payment','cheque')
,`paid_at` timestamp
,`payment_date` date
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_staff_task_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_staff_task_summary` (
`id` int(11)
,`staff_id` int(11)
,`staff_name` varchar(50)
,`order_id` int(11)
,`order_number` varchar(50)
,`task_description` text
,`status` enum('assigned','in-progress','completed','cancelled')
,`priority` enum('low','medium','high','urgent')
,`due_date` date
,`assigned_at` timestamp
,`completed_at` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `v_low_stock`
--
DROP TABLE IF EXISTS `v_low_stock`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_low_stock`  AS SELECT `inventory`.`id` AS `id`, `inventory`.`item_name` AS `item_name`, `inventory`.`type` AS `type`, `inventory`.`quantity` AS `quantity`, `inventory`.`low_stock_threshold` AS `low_stock_threshold`, `inventory`.`quantity`- `inventory`.`low_stock_threshold` AS `stock_difference`, `inventory`.`status` AS `status` FROM `inventory` WHERE `inventory`.`quantity` <= `inventory`.`low_stock_threshold` AND `inventory`.`status` = 'available' ;

-- --------------------------------------------------------

--
-- Structure for view `v_order_summary`
--
DROP TABLE IF EXISTS `v_order_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_order_summary`  AS SELECT `o`.`id` AS `id`, `o`.`order_number` AS `order_number`, `o`.`customer_id` AS `customer_id`, `c`.`name` AS `customer_name`, `c`.`phone` AS `customer_phone`, `o`.`status` AS `status`, `o`.`total_amount` AS `total_amount`, `o`.`advance_amount` AS `advance_amount`, `o`.`remaining_amount` AS `remaining_amount`, `o`.`delivery_date` AS `delivery_date`, `o`.`created_at` AS `created_at`, count(`oi`.`id`) AS `item_count`, coalesce(sum(`p`.`amount`),0) AS `total_paid` FROM (((`orders` `o` left join `customers` `c` on(`o`.`customer_id` = `c`.`id`)) left join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) left join `payments` `p` on(`o`.`id` = `p`.`order_id`)) GROUP BY `o`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_payment_summary`
--
DROP TABLE IF EXISTS `v_payment_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_payment_summary`  AS SELECT `p`.`id` AS `id`, `p`.`payment_number` AS `payment_number`, `p`.`order_id` AS `order_id`, `o`.`order_number` AS `order_number`, `c`.`name` AS `customer_name`, `p`.`amount` AS `amount`, `p`.`payment_method` AS `payment_method`, `p`.`paid_at` AS `paid_at`, cast(`p`.`paid_at` as date) AS `payment_date` FROM ((`payments` `p` left join `orders` `o` on(`p`.`order_id` = `o`.`id`)) left join `customers` `c` on(`o`.`customer_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `v_staff_task_summary`
--
DROP TABLE IF EXISTS `v_staff_task_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_staff_task_summary`  AS SELECT `st`.`id` AS `id`, `st`.`staff_id` AS `staff_id`, `u`.`username` AS `staff_name`, `st`.`order_id` AS `order_id`, `o`.`order_number` AS `order_number`, `st`.`task_description` AS `task_description`, `st`.`status` AS `status`, `st`.`priority` AS `priority`, `st`.`due_date` AS `due_date`, `st`.`assigned_at` AS `assigned_at`, `st`.`completed_at` AS `completed_at` FROM ((`staff_tasks` `st` left join `users` `u` on(`st`.`staff_id` = `u`.`id`)) left join `orders` `o` on(`st`.`order_id` = `o`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_item_name` (`item_name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_delivery_date` (`delivery_date`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_average_rating` (`average_rating`);

--
-- Indexes for table `order_inventory`
--
ALTER TABLE `order_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_order_inventory` (`order_id`,`inventory_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_inventory_id` (`inventory_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_number` (`payment_number`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_paid_at` (`paid_at`);

--
-- Indexes for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_inventory`
--
ALTER TABLE `order_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `fk_feedback_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_feedback_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `order_inventory`
--
ALTER TABLE `order_inventory`
  ADD CONSTRAINT `fk_order_inventory_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_inventory_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD CONSTRAINT `fk_staff_tasks_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_staff_tasks_staff` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
