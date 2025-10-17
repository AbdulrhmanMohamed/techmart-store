-- PHPStore Database Schema and Sample Data
-- Updated with all current features and admin functionality

-- Create database
CREATE DATABASE IF NOT EXISTS phpstore;
USE phpstore;

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500),
    slug VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create brands table
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    country VARCHAR(100) DEFAULT 'US',
    is_admin BOOLEAN DEFAULT FALSE,
    email_verified BOOLEAN DEFAULT FALSE,
    theme VARCHAR(50) DEFAULT 'default',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2),
    sku VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    category_id INT,
    brand_id INT,
    image_url VARCHAR(500),
    gallery_images TEXT,
    weight DECIMAL(8,2),
    dimensions VARCHAR(100),
    status ENUM('active', 'inactive', 'draft') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0,
    review_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
);

-- Create cart table
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Create wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_wishlist (user_id, product_id)
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_id VARCHAR(255),
    subtotal DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    shipping_address TEXT NOT NULL,
    billing_address TEXT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    verified_purchase BOOLEAN DEFAULT FALSE,
    helpful_votes INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert sample categories
INSERT INTO categories (name, description, image_url, slug, status) VALUES
('Electronics', 'Latest electronic devices and gadgets', 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=300&h=200&fit=crop', 'electronics', 'active'),
('Clothing', 'Fashion and apparel for all ages', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=300&h=200&fit=crop', 'clothing', 'active'),
('Home & Garden', 'Everything for your home and garden', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=300&h=200&fit=crop', 'home-garden', 'active'),
('Sports', 'Sports equipment and fitness gear', 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=300&h=200&fit=crop', 'sports', 'active'),
('Books', 'Books and educational materials', 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=300&h=200&fit=crop', 'books', 'active'),
('Toys', 'Toys and games for children', 'https://images.unsplash.com/photo-1558060370-9c4d0c3b8b5c?w=300&h=200&fit=crop', 'toys', 'active');

-- Insert sample brands
INSERT INTO brands (name, logo_url, description, status) VALUES
('Apple', 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=100&h=100&fit=crop', 'Innovative technology products', 'active'),
('Samsung', 'https://images.unsplash.com/photo-1610945265064-0e34e7109d44?w=100&h=100&fit=crop', 'Electronics and home appliances', 'active'),
('Nike', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=100&h=100&fit=crop', 'Athletic footwear and apparel', 'active'),
('Adidas', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=100&h=100&fit=crop', 'Sports and lifestyle brand', 'active'),
('Sony', 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=100&h=100&fit=crop', 'Entertainment and technology', 'active'),
('Microsoft', 'https://images.unsplash.com/photo-1633419465907-5b4b4b4b4b4b?w=100&h=100&fit=crop', 'Software and hardware solutions', 'active');

-- Insert sample users
INSERT INTO users (username, first_name, last_name, email, password, phone, address, city, state, zip_code, country, is_admin, email_verified, theme) VALUES
('john_doe', 'John', 'Doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0123', '123 Main St', 'New York', 'NY', '10001', 'US', FALSE, TRUE, 'default'),
('jane_smith', 'Jane', 'Smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0456', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 'US', FALSE, TRUE, 'ocean'),
('admin', 'Admin', 'User', 'admin@example.com', '$2y$12$DwwFT5lkVq2w5CKd0gsq5etYe0fKjjiaZ/.NKjpXjhQ9o5E5ZoyDK', '555-0789', '789 Pine St', 'Chicago', 'IL', '60601', 'US', TRUE, TRUE, 'default');

-- Insert sample products
INSERT INTO products (name, description, short_description, price, sale_price, sku, stock_quantity, category_id, brand_id, image_url, weight, dimensions, status, featured, rating, review_count) VALUES
('iPhone 15 Pro', 'Latest iPhone with advanced camera system and A17 Pro chip', 'Premium smartphone with titanium design', 999.99, 899.99, 'IPHONE15PRO', 50, 1, 1, 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=300&h=200&fit=crop', 0.187, '6.1 x 2.78 x 0.32 inches', 'active', TRUE, 4.8, 1250),
('Samsung Galaxy S24', 'Flagship Android smartphone with AI-powered features', 'Next-generation mobile experience', 799.99, 699.99, 'GALAXYS24', 75, 1, 2, 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&h=200&fit=crop', 0.168, '6.2 x 2.96 x 0.30 inches', 'active', TRUE, 4.6, 980),
('Nike Air Max 270', 'Comfortable running shoes with Max Air cushioning', 'All-day comfort for active lifestyle', 150.00, 120.00, 'NIKE270', 100, 4, 3, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&h=200&fit=crop', 0.8, '12 x 8 x 4 inches', 'active', TRUE, 4.4, 650),
('Sony WH-1000XM5', 'Industry-leading noise canceling headphones', 'Premium audio experience', 399.99, 349.99, 'SONYWH1000XM5', 30, 1, 5, 'https://images.unsplash.com/photo-1505740420928-5e560c05d565?w=300&h=200&fit=crop', 0.25, '8.3 x 7.2 x 3.0 inches', 'active', TRUE, 4.7, 420),
('MacBook Pro 16"', 'Powerful laptop for professionals and creators', 'M3 Pro chip with stunning display', 2499.99, 2299.99, 'MBP16M3', 25, 1, 1, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=300&h=200&fit=crop', 2.14, '14.01 x 9.77 x 0.66 inches', 'active', TRUE, 4.9, 890),
('Adidas Ultraboost 22', 'Responsive running shoes with Boost technology', 'Energy return with every step', 180.00, 144.00, 'ADIDASUB22', 80, 4, 4, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=300&h=200&fit=crop', 0.7, '12 x 8 x 4 inches', 'active', FALSE, 4.3, 320),
('Samsung 4K Smart TV', '55-inch 4K UHD Smart TV with HDR', 'Immersive viewing experience', 899.99, 799.99, 'SAMSUNG55TV', 15, 1, 2, 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?w=300&h=200&fit=crop', 15.2, '48.4 x 27.8 x 2.4 inches', 'active', TRUE, 4.5, 210),
('Microsoft Surface Pro 9', '2-in-1 laptop and tablet with detachable keyboard', 'Versatile computing solution', 1299.99, 1199.99, 'SURFACEPRO9', 20, 1, 6, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=300&h=200&fit=crop', 0.88, '11.3 x 8.2 x 0.37 inches', 'active', FALSE, 4.2, 180),
('Nike Dri-FIT T-Shirt', 'Moisture-wicking athletic t-shirt', 'Stay dry and comfortable', 29.99, 24.99, 'NIKEDRIFIT', 200, 2, 3, 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=300&h=200&fit=crop', 0.2, 'M', 'active', FALSE, 4.1, 95),
('Fisher-Price Baby Swing', 'Electronic baby swing with 6 speeds, 2 recline positions, and 16 melodies', 'Soothing motion for babies', 89.99, 69.99, 'FISHER-PRICE-SWING', 20, 6, NULL, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop', 15.0, '24 x 20 x 36 inches', 'active', FALSE, 4.5, 3200);

-- Insert sample reviews
INSERT INTO reviews (user_id, product_id, rating, title, comment, verified_purchase, helpful_votes) VALUES
(1, 1, 5, 'Amazing phone!', 'The camera quality is outstanding and the battery life is great.', TRUE, 15),
(2, 1, 4, 'Good but expensive', 'Great features but the price is quite high.', TRUE, 8),
(1, 3, 5, 'Very comfortable', 'These shoes are perfect for long runs.', TRUE, 12),
(2, 4, 5, 'Best headphones ever', 'The noise canceling is incredible.', TRUE, 20),
(1, 5, 5, 'Perfect for work', 'Great performance and battery life.', TRUE, 18);

-- Insert sample orders
INSERT INTO orders (user_id, order_number, status, payment_method, payment_status, subtotal, tax_amount, shipping_amount, total_amount, shipping_address, billing_address) VALUES
(1, 'ORD-2024-001', 'delivered', 'stripe', 'paid', 899.99, 72.00, 0.00, 971.99, '123 Main St, New York, NY 10001', '123 Main St, New York, NY 10001'),
(2, 'ORD-2024-002', 'shipped', 'paypal', 'paid', 120.00, 9.60, 10.00, 139.60, '456 Oak Ave, Los Angeles, CA 90210', '456 Oak Ave, Los Angeles, CA 90210'),
(1, 'ORD-2024-003', 'processing', 'bank_transfer', 'pending', 349.99, 28.00, 15.00, 392.99, '123 Main St, New York, NY 10001', '123 Main St, New York, NY 10001');

-- Insert sample order items
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 899.99),
(2, 3, 1, 120.00),
(3, 4, 1, 349.99);

-- Insert sample cart items
INSERT INTO cart (user_id, product_id, quantity) VALUES
(1, 2, 1),
(1, 5, 1),
(2, 6, 2);

-- Insert sample wishlist items
INSERT INTO wishlist (user_id, product_id) VALUES
(1, 7),
(1, 8),
(2, 1),
(2, 4);



