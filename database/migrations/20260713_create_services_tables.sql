-- Migration: Create services and service_parts tables
-- Run this SQL in your smoketech_inventory database (phpMyAdmin or mysql client)

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_code VARCHAR(50) UNIQUE NOT NULL,
  service_name VARCHAR(255) NOT NULL,
  service_category VARCHAR(150) DEFAULT NULL,
  customer_id INT DEFAULT NULL,
  device_type VARCHAR(100) DEFAULT NULL,
  imei_serial VARCHAR(100) DEFAULT NULL,
  technician_id INT DEFAULT NULL,
  labour_cost DECIMAL(12,2) DEFAULT 0,
  service_charge DECIMAL(12,2) DEFAULT 0,
  discount DECIMAL(12,2) DEFAULT 0,
  tax DECIMAL(12,2) DEFAULT 0,
  total_amount DECIMAL(12,2) DEFAULT 0,
  payment_status VARCHAR(50) DEFAULT 'Pending',
  warranty_period VARCHAR(100) DEFAULT NULL,
  service_status VARCHAR(50) DEFAULT 'Pending',
  date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
  completion_date DATETIME DEFAULT NULL,
  notes TEXT,
  created_by INT DEFAULT NULL,
  INDEX (customer_id),
  INDEX (technician_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS service_parts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  unit_price DECIMAL(12,2) DEFAULT 0,
  total_price DECIMAL(12,2) DEFAULT 0,
  INDEX (service_id),
  INDEX (product_id),
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: If your schema includes a stock_movements table (used by adjustStock), the existing functions will log movements automatically.

SELECT 'Migration created: services and service_parts tables' AS message;
