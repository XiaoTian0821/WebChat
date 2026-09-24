-- WebConnect - Voice Call Tables
-- Run this SQL to create the necessary tables for WebRTC calls

USE `webconnect`;

-- Voice call sessions table
CREATE TABLE IF NOT EXISTS `voice_call_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `caller_id` INT UNSIGNED NOT NULL,
  `receiver_id` INT UNSIGNED NOT NULL,
  `call_type` ENUM('voice','video') NOT NULL DEFAULT 'voice',
  `status` ENUM('ringing','accepted','ended','rejected','missed') NOT NULL DEFAULT 'ringing',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `started_at` TIMESTAMP NULL DEFAULT NULL,
  `ended_at` TIMESTAMP NULL DEFAULT NULL,
  `duration` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `caller_id` (`caller_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `status` (`status`),
  CONSTRAINT `vcs_caller_fk` FOREIGN KEY (`caller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `vcs_receiver_fk` FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Voice call signals table (for WebRTC signaling)
CREATE TABLE IF NOT EXISTS `voice_call_signals` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `call_id` INT UNSIGNED NOT NULL,
  `sender_id` INT UNSIGNED NOT NULL,
  `type` ENUM('offer','answer','ice_candidate') NOT NULL,
  `signal_data` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `call_id` (`call_id`),
  KEY `sender_id` (`sender_id`),
  CONSTRAINT `vcs_signal_call_fk` FOREIGN KEY (`call_id`) REFERENCES `voice_call_sessions`(`id`) ON DELETE CASCADE,
  CONSTRAINT `vcs_signal_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
