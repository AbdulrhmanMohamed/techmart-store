-- Add missing fields for admin dashboard functionality (safe version)

-- Add fields to categories table if they don't exist
ALTER TABLE categories ADD COLUMN slug VARCHAR(255);
ALTER TABLE categories ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';
ALTER TABLE categories ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add fields to brands table if they don't exist
ALTER TABLE brands ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active';
ALTER TABLE brands ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Update existing categories to have default values
UPDATE categories SET slug = LOWER(REPLACE(name, ' ', '-')) WHERE slug IS NULL;
UPDATE categories SET status = 'active' WHERE status IS NULL;

-- Update existing brands to have default values
UPDATE brands SET status = 'active' WHERE status IS NULL;



