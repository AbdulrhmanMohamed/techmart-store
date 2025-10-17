-- Add missing fields for admin dashboard functionality

-- Add fields to products table
ALTER TABLE products ADD COLUMN sku VARCHAR(100);
ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0;
ALTER TABLE products ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';
ALTER TABLE products ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE products ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add fields to categories table
ALTER TABLE categories ADD COLUMN slug VARCHAR(255);
ALTER TABLE categories ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add fields to brands table
ALTER TABLE brands ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';
ALTER TABLE brands ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing products to have default values
UPDATE products SET sku = CONCAT('SKU-', id) WHERE sku IS NULL;
UPDATE products SET stock_quantity = 10 WHERE stock_quantity IS NULL;
UPDATE products SET status = 'active' WHERE status IS NULL;

-- Update existing categories to have default values
UPDATE categories SET slug = LOWER(REPLACE(name, ' ', '-')) WHERE slug IS NULL;
UPDATE categories SET status = 'active' WHERE status IS NULL;

-- Update existing brands to have default values
UPDATE brands SET status = 'active' WHERE status IS NULL;
