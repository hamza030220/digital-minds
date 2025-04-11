-- Create stations table
CREATE TABLE IF NOT EXISTS stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create trajets table with route coordinates and descriptions
CREATE TABLE IF NOT EXISTS trajets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_station_id INT NOT NULL,
    end_station_id INT NOT NULL,
    distance DECIMAL(10,2) NOT NULL COMMENT 'Distance in kilometers',
    description TEXT,
    route_coordinates TEXT COMMENT 'JSON array of waypoints for the route',
    route_description TEXT COMMENT 'Detailed description of the route',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (start_station_id) REFERENCES stations(id) ON DELETE RESTRICT,
    FOREIGN KEY (end_station_id) REFERENCES stations(id) ON DELETE RESTRICT,
    INDEX idx_start_station (start_station_id),
    INDEX idx_end_station (end_station_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
