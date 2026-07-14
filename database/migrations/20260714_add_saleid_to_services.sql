-- Migration: add sale_id to services to link created sales
ALTER TABLE services
ADD COLUMN IF NOT EXISTS sale_id INT DEFAULT NULL,
ADD INDEX (sale_id);

SELECT 'Migration ready: added sale_id to services (if not present)' AS message;
