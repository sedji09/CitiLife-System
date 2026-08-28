CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `proof_of_payment_path` varchar(255) NOT NULL,
  `status` enum('Pending Verification', 'Verified', 'Rejected') NOT NULL DEFAULT 'Pending Verification',
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_payment_request` (`request_id`),
  KEY `fk_payment_verifier` (`verified_by`),
  CONSTRAINT `fk_payment_request` FOREIGN KEY (`request_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
