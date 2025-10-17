-- Drop existing tables if they exist
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create brands table
CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    logo_url VARCHAR(500),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT DEFAULT NULL,
    image_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Create products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10, 2) NOT NULL,
    sale_price DECIMAL(10, 2) DEFAULT NULL,
    sku VARCHAR(100) UNIQUE,
    brand_id INT,
    category_id INT,
    featured BOOLEAN DEFAULT FALSE,
    in_stock BOOLEAN DEFAULT TRUE,
    stock_quantity INT DEFAULT 0,
    weight DECIMAL(8, 2),
    dimensions VARCHAR(100),
    color VARCHAR(50),
    size VARCHAR(50),
    rating DECIMAL(3, 2) DEFAULT 0.00,
    review_count INT DEFAULT 0,
    image_url VARCHAR(500),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create wishlists table
CREATE TABLE IF NOT EXISTS wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    billing_address TEXT NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_id VARCHAR(100),
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
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert brands
INSERT INTO brands (name, description) VALUES
('Apple', 'Technology company known for innovative products'),
('Samsung', 'Global technology leader in electronics'),
('Sony', 'Japanese multinational conglomerate'),
('Nike', 'American multinational corporation for athletic wear'),
('Adidas', 'German multinational corporation for athletic wear'),
('Dell', 'American multinational computer technology company'),
('HP', 'American multinational information technology company'),
('Canon', 'Japanese multinational corporation specializing in imaging'),
('Bose', 'American audio equipment company'),
('Logitech', 'Swiss-American multinational manufacturer of computer peripherals'),
('Microsoft', 'American multinational technology corporation'),
('Amazon Basics', 'Amazon\'s private label brand'),
('Philips', 'Dutch multinational technology company'),
('LG', 'South Korean multinational electronics company'),
('Panasonic', 'Japanese multinational electronics corporation'),
('Levi\'s', 'American clothing company known for denim'),
('Patagonia', 'American clothing company focused on outdoor wear'),
('Yeti', 'American manufacturer of outdoor products'),
('Oral-B', 'Oral care brand owned by Procter & Gamble'),
('Fisher-Price', 'American toy company owned by Mattel');

-- Insert main categories
INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and accessories'),
('Computers & Tablets', 'Laptops, desktops, tablets and accessories'),
('Cell Phones & Accessories', 'Smartphones, cases, chargers and more'),
('Home & Kitchen', 'Home improvement and kitchen supplies'),
('Clothing, Shoes & Jewelry', 'Fashion and apparel for men, women and kids'),
('Sports & Outdoors', 'Sports equipment and outdoor gear'),
('Books', 'Books, magazines and digital content'),
('Health & Personal Care', 'Health, beauty and personal care products'),
('Toys & Games', 'Toys, games and entertainment'),
('Automotive', 'Car parts, tools and accessories'),
('Tools & Home Improvement', 'Tools, hardware and home improvement'),
('Pet Supplies', 'Pet food, toys and accessories'),
('Beauty & Personal Care', 'Beauty products and personal care'),
('Grocery & Gourmet Food', 'Food, beverages and gourmet items'),
('Baby', 'Baby products, clothing and accessories');

-- Insert subcategories
INSERT INTO categories (name, description, parent_id) VALUES
-- Electronics subcategories
('Audio & Video', 'Audio and video equipment', 1),
('Camera & Photo', 'Cameras and photography equipment', 1),
('Car & Vehicle Electronics', 'Car electronics and accessories', 1),
('GPS & Navigation', 'GPS devices and navigation', 1),
('Headphones', 'Headphones and earphones', 1),
('Home Audio', 'Home audio systems', 1),
('Portable Audio & Video', 'Portable audio and video devices', 1),
('Television & Video', 'TVs and video equipment', 1),
('Video Game Consoles', 'Gaming consoles and accessories', 1),

-- Computers subcategories
('Laptops', 'Laptop computers', 2),
('Desktops', 'Desktop computers', 2),
('Tablets', 'Tablet computers', 2),
('Computer Accessories', 'Computer peripherals and accessories', 2),
('Data Storage', 'Hard drives and storage devices', 2),
('Monitors', 'Computer monitors and displays', 2),
('Networking', 'Network equipment and accessories', 2),
('Printers', 'Printers and printing supplies', 2),
('Software', 'Computer software', 2),

-- Clothing subcategories
('Men\'s Clothing', 'Men\'s fashion and apparel', 5),
('Women\'s Clothing', 'Women\'s fashion and apparel', 5),
('Kids\' Clothing', 'Children\'s clothing', 5),
('Shoes', 'Footwear for all ages', 5),
('Jewelry', 'Jewelry and accessories', 5),
('Watches', 'Watches and timepieces', 5),
('Handbags & Wallets', 'Bags and wallets', 5),
('Luggage & Travel', 'Luggage and travel accessories', 5);

-- Insert comprehensive sample products with real data and tested images
INSERT INTO products (name, description, short_description, price, sale_price, sku, brand_id, category_id, featured, in_stock, stock_quantity, rating, review_count, image_url) VALUES
-- Electronics - Smartphones
('iPhone 15 Pro', 'The most advanced iPhone with titanium design, A17 Pro chip, and Pro camera system with 48MP main camera, 12MP ultra-wide, and 12MP telephoto with 3x optical zoom.', 'Pro camera system with 48MP main camera', 999.00, 899.00, 'IPH15PRO-128', 1, 3, TRUE, TRUE, 50, 4.8, 1250, 'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=300&h=200&fit=crop'),
('Samsung Galaxy S24 Ultra', 'Premium Android smartphone with S Pen, 200MP camera system, and advanced AI features. Features a 6.8-inch Dynamic AMOLED display and titanium construction.', 'Advanced smartphone with built-in S Pen', 1199.00, NULL, 'SGS24U-256', 2, 3, TRUE, TRUE, 75, 4.7, 980, 'https://images.unsplash.com/photo-1511707171631-9ed0a806be3a?w=300&h=200&fit=crop'),
('Google Pixel 8 Pro', 'Google\'s flagship smartphone with advanced AI features, 50MP camera system, and pure Android experience with 7 years of updates.', 'AI-powered smartphone with pure Android', 999.00, 899.00, 'PIXEL8PRO-128', 12, 3, TRUE, TRUE, 40, 4.6, 750, 'https://images.unsplash.com/photo-1511707171631-9ed0a806be3a?w=300&h=200&fit=crop'),

-- Electronics - Headphones
('Sony WH-1000XM5 Headphones', 'Industry-leading noise canceling wireless headphones with 30-hour battery life, LDAC codec support, and crystal clear hands-free calling.', 'Premium noise canceling headphones', 399.99, 349.99, 'SONY-WH1000XM5', 3, 5, TRUE, TRUE, 100, 4.6, 2100, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop'),
('Bose QuietComfort 45', 'Premium noise canceling headphones with 24-hour battery life, comfortable fit, and world-class noise canceling technology.', 'Comfortable noise canceling headphones', 329.00, NULL, 'BOSE-QC45', 9, 5, TRUE, TRUE, 80, 4.5, 1800, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop'),
('Apple AirPods Pro', 'Active noise cancellation wireless earbuds with spatial audio, adaptive transparency, and up to 6 hours of listening time.', 'Wireless earbuds with noise cancellation', 249.00, 199.00, 'APPLE-AIRPODS-PRO', 1, 5, TRUE, TRUE, 200, 4.7, 3200, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop'),

-- Computers - Laptops
('MacBook Pro 16-inch', 'Powerful laptop for professionals with M3 Pro chip, 16GB unified memory, and 512GB SSD. Features a stunning Liquid Retina XDR display.', '16-inch MacBook Pro with M3 chip', 2499.00, NULL, 'MBP16-M3', 1, 10, TRUE, TRUE, 25, 4.9, 450, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop'),
('Dell XPS 13', 'Ultrabook with stunning 13.4-inch 4K display, 11th Gen Intel Core i7 processor, and premium build quality with carbon fiber palm rest.', '13-inch ultrabook with 4K display', 1299.00, 1199.00, 'DELL-XPS13-4K', 6, 10, TRUE, TRUE, 40, 4.6, 890, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop'),
('HP Spectre x360', '2-in-1 laptop with 13.5-inch 4K OLED touchscreen, Intel Core i7 processor, and 360-degree hinge for versatile use.', '2-in-1 laptop with OLED touchscreen', 1399.00, 1199.00, 'HP-SPECTRE-X360', 7, 10, TRUE, TRUE, 30, 4.5, 650, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop'),

-- Computers - Tablets
('iPad Pro 12.9-inch', 'Professional tablet for creative work with M2 chip, 12.9-inch Liquid Retina XDR display, and Apple Pencil support.', '12.9-inch iPad Pro with M2 chip', 1099.00, 999.00, 'IPAD-PRO-129', 1, 12, TRUE, TRUE, 60, 4.8, 1200, 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop'),
('Microsoft Surface Laptop 5', 'Premium Windows laptop with 13.5-inch PixelSense touchscreen, Intel Core i7 processor, and all-day battery life.', '13.5-inch Surface Laptop with touchscreen', 999.00, NULL, 'SURFACE-LAPTOP-5', 11, 10, TRUE, TRUE, 35, 4.5, 650, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop'),

-- Home & Kitchen
('Instant Pot Duo 7-in-1', 'Multi-functional electric pressure cooker that replaces 7 kitchen appliances. Features 7-in-1 functionality and smart cooking programs.', '7-in-1 electric pressure cooker', 99.95, 79.95, 'INSTANT-POT-DUO', 12, 4, TRUE, TRUE, 150, 4.7, 4500, 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&h=200&fit=crop'),
('Dyson V15 Detect Vacuum', 'Cordless vacuum with laser dust detection, powerful suction, and up to 60 minutes of runtime. Features advanced filtration system.', 'Advanced cordless vacuum cleaner', 649.99, 599.99, 'DYSON-V15-DETECT', 12, 4, TRUE, TRUE, 30, 4.8, 1200, 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&h=200&fit=crop'),
('KitchenAid Stand Mixer', 'Professional stand mixer for baking with 5-quart bowl, 10 speeds, and multiple attachments for various cooking tasks.', '5-quart stand mixer with attachments', 329.99, 279.99, 'KITCHENAID-5QT', 12, 4, TRUE, TRUE, 45, 4.9, 2800, 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&h=200&fit=crop'),

-- Clothing - Shoes
('Nike Air Max 270', 'Comfortable running shoes with Max Air unit for all-day comfort. Features breathable mesh upper and rubber outsole.', 'Breathable running shoes with Max Air', 150.00, 120.00, 'NIKE-AM270', 4, 5, TRUE, TRUE, 200, 4.6, 3200, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&h=200&fit=crop'),
('Adidas Ultraboost 22', 'Premium running shoes with Boost midsole technology for energy return and Primeknit upper for comfort.', 'High-performance running shoes', 180.00, 150.00, 'ADIDAS-UB22', 5, 5, TRUE, TRUE, 180, 4.7, 2800, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&h=200&fit=crop'),
('Converse Chuck Taylor All Star', 'Classic canvas sneakers with rubber toe cap and vulcanized rubber sole. Available in multiple colors.', 'Classic canvas sneakers', 65.00, 55.00, 'CONVERSE-CHUCK', 12, 5, TRUE, TRUE, 500, 4.5, 15000, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&h=200&fit=crop'),

-- Clothing - Men's Clothing
('Levi\'s 501 Original Jeans', 'Classic straight-fit jeans made from 100% cotton denim. Features button fly and five-pocket styling.', 'Original straight-fit denim jeans', 89.50, 69.50, 'LEVIS-501-ORIG', 16, 17, TRUE, TRUE, 300, 4.5, 1500, 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=300&h=200&fit=crop'),
('Patagonia Better Sweater', 'Fleece jacket made from recycled polyester. Features full-zip front and zippered chest pocket.', 'Recycled polyester fleece jacket', 99.00, 79.00, 'PATAGONIA-BETTER-SWEATER', 17, 17, TRUE, TRUE, 80, 4.6, 1800, 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=300&h=200&fit=crop'),

-- Sports & Outdoors
('Yeti Rambler 30oz Tumbler', 'Insulated stainless steel tumbler that keeps drinks cold for hours. Features double-wall vacuum insulation.', 'Double-wall insulated tumbler', 35.00, NULL, 'YETI-RAMBLER-30', 18, 6, TRUE, TRUE, 500, 4.8, 4200, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Patagonia Down Sweater', 'Lightweight down jacket with 800-fill-power down insulation. Features water-resistant shell and zippered pockets.', 'Lightweight down jacket', 199.00, 159.00, 'PATAGONIA-DOWN-SWEATER', 17, 6, TRUE, TRUE, 60, 4.7, 1200, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),

-- Books
('Atomic Habits by James Clear', 'An Easy & Proven Way to Build Good Habits & Break Bad Ones. New York Times bestseller with practical strategies.', 'Self-help book on building better habits', 16.99, 12.99, 'ATOMIC-HABITS', 12, 7, TRUE, TRUE, 1000, 4.8, 15000, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=200&fit=crop'),
('The Seven Husbands of Evelyn Hugo', 'Fiction novel by Taylor Jenkins Reid about a reclusive Hollywood icon who finally decides to tell her story.', 'Bestselling fiction novel', 14.99, 10.99, 'SEVEN-HUSBANDS', 12, 7, TRUE, TRUE, 800, 4.7, 8500, 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=200&fit=crop'),

-- Health & Personal Care
('Oral-B Pro 1000 Electric Toothbrush', 'Rechargeable electric toothbrush with 3D cleaning action, pressure sensor, and 2-minute timer.', '3D cleaning action toothbrush', 49.94, 39.94, 'ORALB-PRO1000', 19, 8, TRUE, TRUE, 400, 4.5, 12000, 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=300&h=200&fit=crop'),
('Philips Sonicare DiamondClean', 'Premium electric toothbrush with DiamondClean technology, 5 cleaning modes, and smart sensor technology.', 'DiamondClean smart toothbrush', 199.99, 149.99, 'PHILIPS-DIAMONDCLEAN', 13, 8, TRUE, TRUE, 100, 4.7, 3500, 'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=300&h=200&fit=crop'),

-- Toys & Games
('LEGO Creator 3-in-1 Deep Sea Creatures', 'Buildable sea creature models that can be rebuilt into 3 different creatures. Includes 230 pieces.', '3-in-1 LEGO set with sea creatures', 24.99, 19.99, 'LEGO-DEEP-SEA', 12, 9, TRUE, TRUE, 200, 4.8, 1200, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Nintendo Switch Console', 'Hybrid gaming console that can be played at home or on the go. Includes Joy-Con controllers and dock.', 'Hybrid gaming console for home and travel', 299.99, 279.99, 'NINTENDO-SWITCH', 12, 9, TRUE, TRUE, 50, 4.9, 8500, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),

-- Automotive
('WeatherTech FloorLiners', 'Custom-fit floor mats laser-measured for your specific vehicle. Features all-weather protection and easy cleaning.', 'Laser-measured floor mats for your vehicle', 149.95, 129.95, 'WEATHERTECH-FLOORLINERS', 12, 10, TRUE, TRUE, 100, 4.6, 2800, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Anker PowerDrive 2 Car Charger', 'Dual USB car charger with PowerIQ technology for fast charging. Compatible with all USB devices.', 'Fast charging dual USB car charger', 19.99, 15.99, 'ANKER-POWERDRIVE-2', 12, 10, TRUE, TRUE, 500, 4.7, 4500, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),

-- Tools & Home Improvement
('DeWalt 20V Max Cordless Drill', 'Professional cordless drill with 20V MAX battery, 1/2-inch chuck, and LED work light.', '20V MAX cordless drill with battery', 99.00, 79.00, 'DEWALT-20V-DRILL', 12, 11, TRUE, TRUE, 75, 4.8, 3200, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Black+Decker 20V Max Cordless Vacuum', 'Cordless handheld vacuum with 20V MAX battery and washable filter. Perfect for quick cleanups.', '20V MAX cordless handheld vacuum', 79.99, 59.99, 'BLACKDECKER-20V-VAC', 12, 11, TRUE, TRUE, 120, 4.5, 1800, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),

-- Pet Supplies
('Purina Pro Plan Dog Food', 'High-protein dog food with real chicken as the first ingredient. Formulated for adult dogs of all sizes.', 'Adult dry dog food with real chicken', 59.99, 49.99, 'PURINA-PRO-PLAN', 12, 12, TRUE, TRUE, 200, 4.6, 8500, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Kong Classic Dog Toy', 'Durable rubber dog toy designed for chewing and play. Available in multiple sizes and colors.', 'Red rubber dog toy for chewing', 12.99, 9.99, 'KONG-CLASSIC', 12, 12, TRUE, TRUE, 300, 4.7, 4200, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),

-- Beauty & Personal Care
('Olay Regenerist Micro-Sculpting Cream', 'Anti-aging face cream with amino-peptides and niacinamide. Fragrance-free formula for sensitive skin.', 'Fragrance-free anti-aging moisturizer', 24.99, 19.99, 'OLAY-REGENERIST', 12, 13, TRUE, TRUE, 150, 4.4, 6800, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Maybelline Fit Me Foundation', 'Matte foundation for all skin types with natural finish. Oil-free formula that won\'t clog pores.', 'Oil-free foundation with natural finish', 7.99, 5.99, 'MAYBELLINE-FITME', 12, 13, TRUE, TRUE, 400, 4.3, 12000, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),

-- Grocery & Gourmet Food
('Starbucks Coffee Beans', 'Premium whole bean coffee with rich, bold flavor. Dark roast blend perfect for morning brewing.', 'Dark roast whole bean coffee', 12.99, 9.99, 'STARBUCKS-DARK-ROAST', 12, 14, TRUE, TRUE, 1000, 4.5, 2500, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Lindt Excellence Dark Chocolate', 'Premium dark chocolate bars with 70% cocoa content. Smooth texture and intense chocolate flavor.', '70% cocoa dark chocolate', 4.99, 3.99, 'LINDT-EXCELLENCE-70', 12, 14, TRUE, TRUE, 500, 4.7, 1800, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),

-- Baby
('Pampers Baby Dry Diapers', 'Overnight protection diapers with extra absorbency. Size 3 for babies 16-28 lbs, 128 count.', 'Size 3 baby diapers, 128 count', 29.99, 24.99, 'PAMPERS-BABY-DRY', 12, 15, TRUE, TRUE, 200, 4.6, 15000, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop'),
('Fisher-Price Baby Swing', 'Electronic baby swing with 6 speeds, 2 recline positions, and 16 melodies. Soothing motion for babies.', 'Electronic baby swing with music', 89.99, 69.99, 'FISHER-PRICE-SWING', 20, 15, TRUE, TRUE, 50, 4.5, 3200, 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=300&h=200&fit=crop');

-- Insert sample users
INSERT INTO users (first_name, last_name, email, password, phone, address, city, state, zip_code, country, is_admin) VALUES
('John', 'Doe', 'john.doe@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0123', '123 Main St', 'New York', 'NY', '10001', 'US', FALSE),
('Jane', 'Smith', 'jane.smith@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0456', '456 Oak Ave', 'Los Angeles', 'CA', '90210', 'US', FALSE),
('Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '555-0789', '789 Pine St', 'Chicago', 'IL', '60601', 'US', TRUE);

-- Insert sample reviews
INSERT INTO reviews (user_id, product_id, rating, title, comment, verified_purchase, helpful_votes) VALUES
(1, 1, 5, 'Amazing phone!', 'The iPhone 15 Pro is incredible. The camera quality is outstanding and the performance is smooth.', TRUE, 12),
(2, 1, 4, 'Great upgrade', 'Upgraded from iPhone 12. The new features are nice, but the price is quite high.', TRUE, 8),
(1, 2, 5, 'Best Android phone', 'Samsung Galaxy S24 Ultra is the best Android phone I\'ve used. The S Pen is very useful.', TRUE, 15),
(2, 4, 5, 'Excellent headphones', 'Sony WH-1000XM5 has amazing noise cancellation. Perfect for travel and work.', TRUE, 20),
(1, 7, 5, 'Perfect laptop', 'MacBook Pro 16-inch is perfect for my work. The M3 chip is incredibly fast.', TRUE, 25);