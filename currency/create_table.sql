-- Create currencies table
CREATE TABLE IF NOT EXISTS `currencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_name` varchar(255) NOT NULL,
  `code` varchar(3) NOT NULL,
  `symbol_left` varchar(10) DEFAULT NULL,
  `symbol_right` varchar(10) DEFAULT NULL,
  `decimal_place` int(1) DEFAULT 2,
  `status` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create store_currency junction table
CREATE TABLE IF NOT EXISTS `store_currency` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `store_id` int(11) NOT NULL,
  `currency_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `store_currency_unique` (`store_id`, `currency_id`),
  KEY `store_id` (`store_id`),
  KEY `currency_id` (`currency_id`),
  CONSTRAINT `store_currency_store_fk` FOREIGN KEY (`store_id`) REFERENCES `stores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `store_currency_currency_fk` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample currencies
INSERT INTO `currencies` (`currency_name`, `code`, `symbol_left`, `symbol_right`, `decimal_place`, `status`, `sort_order`) VALUES
('United States Dollar', 'USD', '$', '', 2, 1, 1),
('Pakistani Rupee', 'PKR', 'Rs.', '', 2, 1, 2),
('Pound Sterling', 'GBP', '£', '', 2, 1, 3),
('Euro', 'EUR', '', '€', 2, 1, 4),
('Indian Rupee', 'INR', '₹', '', 2, 1, 5),
('Bangladeshi Taka', 'BDT', '৳', '', 2, 1, 6);

