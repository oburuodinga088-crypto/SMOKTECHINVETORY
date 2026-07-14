-- Migration: create stock_movements table used by adjustStock()
CREATE TABLE IF NOT EXISTS stock_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  quantity_change INT NOT NULL,
  movement_type VARCHAR(50),
  reference_type VARCHAR(50),
  reference_id INT,
  note TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Migration ready: stock_movements table' AS message;
