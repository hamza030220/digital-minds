-- SQL script to add route_coordinates and route_description columns to the trajets table
-- Execute this in phpMyAdmin or mysql client to update the database structure

ALTER TABLE `trajets` 
ADD COLUMN `route_coordinates` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'JSON string containing route path coordinates',
ADD COLUMN `route_description` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Detailed description of the route with instructions';

-- Verify the changes
-- SHOW COLUMNS FROM trajets LIKE 'route%';

-- If you prefer using JSON type instead of TEXT (requires MySQL 5.7+):
-- ALTER TABLE `trajets` 
-- MODIFY COLUMN `route_coordinates` JSON NULL DEFAULT NULL COMMENT 'JSON containing route path coordinates';

