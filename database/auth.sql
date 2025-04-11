-- Authentication tables for Green Admin MVC
-- This file creates the necessary tables for user authentication and password reset

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create password_resets table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expiry` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert a default admin user (username: admin, password: admin123)
-- In a production environment, this should be changed immediately after installation
INSERT INTO `users` (`username`, `email`, `password`, `role`) 
VALUES (
    'admin', 
    'admin@example.com', 
    '$2y$10$mL7VZpnvOGN3aTvqHEoF9e.Uy4R/qfm3gR67HZgK0C0J8gAFo.xVK', 
    'admin'
) ON DUPLICATE KEY UPDATE `id` = `id`;

-- Note: The password hash above corresponds to 'admin123'

