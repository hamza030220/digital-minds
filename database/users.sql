-- Users and authentication tables for Green Admin MVC

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME DEFAULT NULL,
    INDEX `idx_username` (`username`),
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create password reset tokens table
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token` VARCHAR(64) NOT NULL,
    `expiry` DATETIME NOT NULL,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE INDEX `idx_token` (`token`),
    INDEX `idx_expiry` (`expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create default admin user (username: admin, password: admin123)
-- Password hash for 'admin123'
INSERT INTO `users` (`username`, `email`, `password`, `role`, `created_at`) 
VALUES ('admin', 'admin@example.com', '$2y$10$mL7VZpnvOGN3aTvqHEoF9e.Uy4R/qfm3gR67HZgK0C0J8gAFo.xVK', 'admin', NOW())
ON DUPLICATE KEY UPDATE `id` = `id`;

