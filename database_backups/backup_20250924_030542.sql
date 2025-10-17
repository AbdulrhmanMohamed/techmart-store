-- MySQL dump 10.13  Distrib 8.0.42, for Linux (aarch64)
--
-- Host: localhost    Database: phpstore
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `brands` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (1,'Apple',NULL,'Technology company known for innovative products','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(2,'Samsung',NULL,'Global technology leader in electronics','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(3,'Sony',NULL,'Japanese multinational conglomerate','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(4,'Nike',NULL,'American multinational corporation for athletic wear','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(5,'Adidas',NULL,'German multinational corporation for athletic wear','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(6,'Dell',NULL,'American multinational computer technology company','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(7,'HP',NULL,'American multinational information technology company','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(8,'Canon',NULL,'Japanese multinational corporation specializing in imaging','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(9,'Bose',NULL,'American audio equipment company','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(10,'Logitech',NULL,'Swiss-American multinational manufacturer of computer peripherals','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(11,'Microsoft',NULL,'American multinational technology corporation','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(12,'Amazon Basics',NULL,'Amazon\'s private label brand','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(13,'Philips',NULL,'Dutch multinational technology company','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(14,'LG',NULL,'South Korean multinational electronics company','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(15,'Panasonic',NULL,'Japanese multinational electronics corporation','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(16,'Levi\'s',NULL,'American clothing company known for denim','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(17,'Patagonia',NULL,'American clothing company focused on outdoor wear','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(18,'Yeti',NULL,'American manufacturer of outdoor products','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(19,'Oral-B',NULL,'Oral care brand owned by Procter & Gamble','2025-09-21 19:21:34','active','2025-09-23 21:16:34'),(20,'Fisher-Price',NULL,'American toy company owned by Mattel','2025-09-21 19:21:34','active','2025-09-23 21:16:34');
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart`
--

DROP TABLE IF EXISTS `cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart`
--

LOCK TABLES `cart` WRITE;
/*!40000 ALTER TABLE `cart` DISABLE KEYS */;
INSERT INTO `cart` VALUES (8,4,3,1,'2025-09-23 20:34:19','2025-09-23 20:41:38'),(10,3,1,1,'2025-09-24 00:01:20','2025-09-24 00:01:20');
/*!40000 ALTER TABLE `cart` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `parent_id` int DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `slug` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Electronics','Electronic devices and accessories',NULL,NULL,'2025-09-21 19:21:34','electronics','active','2025-09-23 21:16:34'),(2,'Computers & Tablets','Laptops, desktops, tablets and accessories',NULL,NULL,'2025-09-21 19:21:34','computers-&-tablets','active','2025-09-23 21:16:34'),(3,'Cell Phones & Accessories','Smartphones, cases, chargers and more',NULL,NULL,'2025-09-21 19:21:34','cell-phones-&-accessories','active','2025-09-23 21:16:34'),(4,'Home & Kitchen','Home improvement and kitchen supplies',NULL,NULL,'2025-09-21 19:21:34','home-&-kitchen','active','2025-09-23 21:16:34'),(5,'Clothing, Shoes & Jewelry','Fashion and apparel for men, women and kids',NULL,NULL,'2025-09-21 19:21:34','clothing,-shoes-&-jewelry','active','2025-09-23 21:16:34'),(6,'Sports & Outdoors','Sports equipment and outdoor gear',NULL,NULL,'2025-09-21 19:21:34','sports-&-outdoors','active','2025-09-23 21:16:34'),(7,'Books','Books, magazines and digital content',NULL,NULL,'2025-09-21 19:21:34','books','active','2025-09-23 21:16:34'),(8,'Health & Personal Care','Health, beauty and personal care products',NULL,NULL,'2025-09-21 19:21:34','health-&-personal-care','active','2025-09-23 21:16:34'),(9,'Toys & Games','Toys, games and entertainment',NULL,NULL,'2025-09-21 19:21:34','toys-&-games','active','2025-09-23 21:16:34'),(10,'Automotive','Car parts, tools and accessories',NULL,NULL,'2025-09-21 19:21:34','automotive','active','2025-09-23 21:16:34'),(11,'Tools & Home Improvement','Tools, hardware and home improvement',NULL,NULL,'2025-09-21 19:21:34','tools-&-home-improvement','active','2025-09-23 21:16:34'),(12,'Pet Supplies','Pet food, toys and accessories',NULL,NULL,'2025-09-21 19:21:34','pet-supplies','active','2025-09-23 21:16:34'),(13,'Beauty & Personal Care','Beauty products and personal care',NULL,NULL,'2025-09-21 19:21:34','beauty-&-personal-care','active','2025-09-23 21:16:34'),(14,'Grocery & Gourmet Food','Food, beverages and gourmet items',NULL,NULL,'2025-09-21 19:21:34','grocery-&-gourmet-food','active','2025-09-23 21:16:34'),(15,'Baby','Baby products, clothing and accessories',NULL,NULL,'2025-09-21 19:21:34','baby','active','2025-09-23 21:16:34'),(16,'Audio & Video','Audio and video equipment',1,NULL,'2025-09-21 19:21:34','audio-&-video','active','2025-09-23 21:16:34'),(17,'Camera & Photo','Cameras and photography equipment',1,NULL,'2025-09-21 19:21:34','camera-&-photo','active','2025-09-23 21:16:34'),(18,'Car & Vehicle Electronics','Car electronics and accessories',1,NULL,'2025-09-21 19:21:34','car-&-vehicle-electronics','active','2025-09-23 21:16:34'),(19,'GPS & Navigation','GPS devices and navigation',1,NULL,'2025-09-21 19:21:34','gps-&-navigation','active','2025-09-23 21:16:34'),(20,'Headphones','Headphones and earphones',1,NULL,'2025-09-21 19:21:34','headphones','active','2025-09-23 21:16:34'),(21,'Home Audio','Home audio systems',1,NULL,'2025-09-21 19:21:34','home-audio','active','2025-09-23 21:16:34'),(22,'Portable Audio & Video','Portable audio and video devices',1,NULL,'2025-09-21 19:21:34','portable-audio-&-video','active','2025-09-23 21:16:34'),(23,'Television & Video','TVs and video equipment',1,NULL,'2025-09-21 19:21:34','television-&-video','active','2025-09-23 21:16:34'),(24,'Video Game Consoles','Gaming consoles and accessories',1,NULL,'2025-09-21 19:21:34','video-game-consoles','active','2025-09-23 21:16:34'),(25,'Laptops','Laptop computers',2,NULL,'2025-09-21 19:21:34','laptops','active','2025-09-23 21:16:34'),(26,'Desktops','Desktop computers',2,NULL,'2025-09-21 19:21:34','desktops','active','2025-09-23 21:16:34'),(27,'Tablets','Tablet computers',2,NULL,'2025-09-21 19:21:34','tablets','active','2025-09-23 21:16:34'),(28,'Computer Accessories','Computer peripherals and accessories',2,NULL,'2025-09-21 19:21:34','computer-accessories','active','2025-09-23 21:16:34'),(29,'Data Storage','Hard drives and storage devices',2,NULL,'2025-09-21 19:21:34','data-storage','active','2025-09-23 21:16:34'),(30,'Monitors','Computer monitors and displays',2,NULL,'2025-09-21 19:21:34','monitors','active','2025-09-23 21:16:34'),(31,'Networking','Network equipment and accessories',2,NULL,'2025-09-21 19:21:34','networking','active','2025-09-23 21:16:34'),(32,'Printers','Printers and printing supplies',2,NULL,'2025-09-21 19:21:34','printers','active','2025-09-23 21:16:34'),(33,'Software','Computer software',2,NULL,'2025-09-21 19:21:34','software','active','2025-09-23 21:16:34'),(34,'Men\'s Clothing','Men\'s fashion and apparel',5,NULL,'2025-09-21 19:21:34','men\'s-clothing','active','2025-09-23 21:16:34'),(35,'Women\'s Clothing','Women\'s fashion and apparel',5,NULL,'2025-09-21 19:21:34','women\'s-clothing','active','2025-09-23 21:16:34'),(36,'Kids\' Clothing','Children\'s clothing',5,NULL,'2025-09-21 19:21:34','kids\'-clothing','active','2025-09-23 21:16:34'),(37,'Shoes','Footwear for all ages',5,NULL,'2025-09-21 19:21:34','shoes','active','2025-09-23 21:16:34'),(38,'Jewelry','Jewelry and accessories',5,NULL,'2025-09-21 19:21:34','jewelry','active','2025-09-23 21:16:34'),(39,'Watches','Watches and timepieces',5,NULL,'2025-09-21 19:21:34','watches','active','2025-09-23 21:16:34'),(40,'Handbags & Wallets','Bags and wallets',5,NULL,'2025-09-21 19:21:34','handbags-&-wallets','active','2025-09-23 21:16:34'),(41,'Luggage & Travel','Luggage and travel accessories',5,NULL,'2025-09-21 19:21:34','luggage-&-travel','active','2025-09-23 21:16:34');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,1,2,899.00,'2025-09-23 20:06:04'),(2,2,2,1,1299.99,'2025-09-22 10:00:00'),(3,3,3,2,299.99,'2025-09-21 15:30:00'),(4,4,4,1,299.99,'2025-09-20 09:15:00'),(5,5,31,2,59.99,'2025-09-23 22:20:05');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `shipping_address` text NOT NULL,
  `billing_address` text NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,4,'ORD-20250923-0760',1941.84,'delivered','N Teseen St, alex, القاهرة 11835, US','N Teseen St, alex, القاهرة 11835, US','credit_card','paid','2025-09-23 20:06:04','2025-09-23 21:54:09'),(2,4,'ORD-20250923-0761',1299.99,'delivered','123 Main St, City, State 12345, US','123 Main St, City, State 12345, US','paypal','paid','2025-09-22 10:00:00','2025-09-23 21:54:20'),(3,4,'ORD-20250923-0762',599.99,'shipped','456 Oak Ave, City, State 12345, US','456 Oak Ave, City, State 12345, US','stripe','paid','2025-09-21 15:30:00','2025-09-23 21:54:20'),(4,4,'ORD-20250923-0763',299.99,'processing','789 Pine St, City, State 12345, US','789 Pine St, City, State 12345, US','bank_transfer','paid','2025-09-20 09:15:00','2025-09-23 21:54:20'),(5,1,'ORD-20250923-0855',129.58,'pending','123 Main St, New York, NY 10001, US','123 Main St, New York, NY 10001, US','paypal','pending','2025-09-23 22:20:05','2025-09-23 22:20:05');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `brand_id` int DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `featured` tinyint(1) DEFAULT '0',
  `in_stock` tinyint(1) DEFAULT '1',
  `stock_quantity` int DEFAULT '0',
  `weight` decimal(8,2) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT '0.00',
  `review_count` int DEFAULT '0',
  `image_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `brand_id` (`brand_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'iPhone 15 Pro','The most advanced iPhone with titanium design, A17 Pro chip, and Pro camera system with 48MP main camera, 12MP ultra-wide, and 12MP telephoto with 3x optical zoom.','Pro camera system with 48MP main camera',999.00,899.00,'IPH15PRO-128',1,3,1,1,50,NULL,NULL,NULL,NULL,4.80,1250,'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:21:34'),(2,'Samsung Galaxy S24 Ultra','Premium Android smartphone with S Pen, 200MP camera system, and advanced AI features. Features a 6.8-inch Dynamic AMOLED display and titanium construction.','Advanced smartphone with built-in S Pen',1199.00,NULL,'SGS24U-256',2,3,1,1,75,NULL,NULL,NULL,NULL,4.70,980,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:22:15'),(3,'Google Pixel 8 Pro','Google\'s flagship smartphone with advanced AI features, 50MP camera system, and pure Android experience with 7 years of updates.','AI-powered smartphone with pure Android',999.00,899.00,'PIXEL8PRO-128',12,3,1,1,40,NULL,NULL,NULL,NULL,4.60,750,'https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(4,'Sony WH-1000XM5 Headphones','Industry-leading noise canceling wireless headphones with 30-hour battery life, LDAC codec support, and crystal clear hands-free calling.','Premium noise canceling headphones',399.99,349.99,'SONY-WH1000XM5',3,1,1,1,100,NULL,NULL,NULL,NULL,4.60,2100,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:24:56'),(5,'Bose QuietComfort 45','Premium noise canceling headphones with 24-hour battery life, comfortable fit, and world-class noise canceling technology.','Comfortable noise canceling headphones',329.00,NULL,'BOSE-QC45',9,1,1,1,80,NULL,NULL,NULL,NULL,4.50,1800,'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(6,'Apple AirPods Pro','Active noise cancellation wireless earbuds with spatial audio, adaptive transparency, and up to 6 hours of listening time.','Wireless earbuds with noise cancellation',249.00,199.00,'APPLE-AIRPODS-PRO',1,1,1,1,200,NULL,NULL,NULL,NULL,4.70,3200,'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(7,'MacBook Pro 16-inch','Powerful laptop for professionals with M3 Pro chip, 16GB unified memory, and 512GB SSD. Features a stunning Liquid Retina XDR display.','16-inch MacBook Pro with M3 chip',2499.00,NULL,'MBP16-M3',1,2,1,1,25,NULL,NULL,NULL,NULL,4.90,450,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:24:56'),(8,'Dell XPS 13','Ultrabook with stunning 13.4-inch 4K display, 11th Gen Intel Core i7 processor, and premium build quality with carbon fiber palm rest.','13-inch ultrabook with 4K display',1299.00,1199.00,'DELL-XPS13-4K',6,2,1,1,40,NULL,NULL,NULL,NULL,4.60,890,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(9,'HP Spectre x360','2-in-1 laptop with 13.5-inch 4K OLED touchscreen, Intel Core i7 processor, and 360-degree hinge for versatile use.','2-in-1 laptop with OLED touchscreen',1399.00,1199.00,'HP-SPECTRE-X360',7,2,1,1,30,NULL,NULL,NULL,NULL,4.50,650,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(10,'iPad Pro 12.9-inch','Professional tablet for creative work with M2 chip, 12.9-inch Liquid Retina XDR display, and Apple Pencil support.','12.9-inch iPad Pro with M2 chip',1099.00,999.00,'IPAD-PRO-129',1,2,1,1,60,NULL,NULL,NULL,NULL,4.80,1200,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:24:56'),(11,'Microsoft Surface Laptop 5','Premium Windows laptop with 13.5-inch PixelSense touchscreen, Intel Core i7 processor, and all-day battery life.','13.5-inch Surface Laptop with touchscreen',999.00,NULL,'SURFACE-LAPTOP-5',11,4,1,1,35,NULL,NULL,NULL,NULL,4.50,650,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:24:56'),(12,'Instant Pot Duo 7-in-1','Multi-functional electric pressure cooker that replaces 7 kitchen appliances. Features 7-in-1 functionality and smart cooking programs.','7-in-1 electric pressure cooker',99.95,79.95,'INSTANT-POT-DUO',12,4,1,1,150,NULL,NULL,NULL,NULL,4.70,4500,'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:21:34'),(13,'Dyson V15 Detect Vacuum','Cordless vacuum with laser dust detection, powerful suction, and up to 60 minutes of runtime. Features advanced filtration system.','Advanced cordless vacuum cleaner',649.99,599.99,'DYSON-V15-DETECT',12,4,1,1,30,NULL,NULL,NULL,NULL,4.80,1200,'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(14,'KitchenAid Stand Mixer','Professional stand mixer for baking with 5-quart bowl, 10 speeds, and multiple attachments for various cooking tasks.','5-quart stand mixer with attachments',329.99,279.99,'KITCHENAID-5QT',12,5,1,1,45,NULL,NULL,NULL,NULL,4.90,2800,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(15,'Nike Air Max 270','Comfortable running shoes with Max Air unit for all-day comfort. Features breathable mesh upper and rubber outsole.','Breathable running shoes with Max Air',150.00,120.00,'NIKE-AM270',4,5,1,1,200,NULL,NULL,NULL,NULL,4.60,3200,'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:21:34'),(16,'Adidas Ultraboost 22','Premium running shoes with Boost midsole technology for energy return and Primeknit upper for comfort.','High-performance running shoes',180.00,150.00,'ADIDAS-UB22',5,5,1,1,180,NULL,NULL,NULL,NULL,4.70,2800,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(17,'Converse Chuck Taylor All Star','Classic canvas sneakers with rubber toe cap and vulcanized rubber sole. Available in multiple colors.','Classic canvas sneakers',65.00,55.00,'CONVERSE-CHUCK',12,5,1,1,500,NULL,NULL,NULL,NULL,4.50,15000,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(18,'Levi\'s 501 Original Jeans','Classic straight-fit jeans made from 100% cotton denim. Features button fly and five-pocket styling.','Original straight-fit denim jeans',89.50,69.50,'LEVIS-501-ORIG',16,5,1,1,300,NULL,NULL,NULL,NULL,4.50,1500,'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:24:56'),(19,'Patagonia Better Sweater','Fleece jacket made from recycled polyester. Features full-zip front and zippered chest pocket.','Recycled polyester fleece jacket',99.00,79.00,'PATAGONIA-BETTER-SWEATER',17,5,1,1,80,NULL,NULL,NULL,NULL,4.60,1800,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(20,'Yeti Rambler 30oz Tumbler','Insulated stainless steel tumbler that keeps drinks cold for hours. Features double-wall vacuum insulation.','Double-wall insulated tumbler',35.00,NULL,'YETI-RAMBLER-30',18,6,1,1,500,NULL,NULL,NULL,NULL,4.80,4200,'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(21,'Patagonia Down Sweater','Lightweight down jacket with 800-fill-power down insulation. Features water-resistant shell and zippered pockets.','Lightweight down jacket',199.00,159.00,'PATAGONIA-DOWN-SWEATER',17,6,1,1,60,NULL,NULL,NULL,NULL,4.70,1200,'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(22,'Atomic Habits by James Clear','An Easy & Proven Way to Build Good Habits & Break Bad Ones. New York Times bestseller with practical strategies.','Self-help book on building better habits',16.99,12.99,'ATOMIC-HABITS',12,7,1,1,1000,NULL,NULL,NULL,NULL,4.80,15000,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:21:34'),(23,'The Seven Husbands of Evelyn Hugo','Fiction novel by Taylor Jenkins Reid about a reclusive Hollywood icon who finally decides to tell her story.','Bestselling fiction novel',14.99,10.99,'SEVEN-HUSBANDS',12,7,1,1,800,NULL,NULL,NULL,NULL,4.70,8500,'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(24,'Oral-B Pro 1000 Electric Toothbrush','Rechargeable electric toothbrush with 3D cleaning action, pressure sensor, and 2-minute timer.','3D cleaning action toothbrush',49.94,39.94,'ORALB-PRO1000',19,8,1,1,400,NULL,NULL,NULL,NULL,4.50,12000,'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:21:34'),(25,'Philips Sonicare DiamondClean','Premium electric toothbrush with DiamondClean technology, 5 cleaning modes, and smart sensor technology.','DiamondClean smart toothbrush',199.99,149.99,'PHILIPS-DIAMONDCLEAN',13,8,1,1,100,NULL,NULL,NULL,NULL,4.70,3500,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(26,'LEGO Creator 3-in-1 Deep Sea Creatures','Buildable sea creature models that can be rebuilt into 3 different creatures. Includes 230 pieces.','3-in-1 LEGO set with sea creatures',24.99,19.99,'LEGO-DEEP-SEA',12,9,1,1,200,NULL,NULL,NULL,NULL,4.80,1200,'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(27,'Nintendo Switch Console','Hybrid gaming console that can be played at home or on the go. Includes Joy-Con controllers and dock.','Hybrid gaming console for home and travel',299.99,279.99,'NINTENDO-SWITCH',12,9,1,1,50,NULL,NULL,NULL,NULL,4.90,8500,'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(28,'WeatherTech FloorLiners','Custom-fit floor mats laser-measured for your specific vehicle. Features all-weather protection and easy cleaning.','Laser-measured floor mats for your vehicle',149.95,129.95,'WEATHERTECH-FLOORLINERS',12,10,1,1,100,NULL,NULL,NULL,NULL,4.60,2800,'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(29,'Anker PowerDrive 2 Car Charger','Dual USB car charger with PowerIQ technology for fast charging. Compatible with all USB devices.','Fast charging dual USB car charger',19.99,15.99,'ANKER-POWERDRIVE-2',12,10,1,1,500,NULL,NULL,NULL,NULL,4.70,4500,'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(30,'DeWalt 20V Max Cordless Drill','Professional cordless drill with 20V MAX battery, 1/2-inch chuck, and LED work light.','20V MAX cordless drill with battery',99.00,79.00,'DEWALT-20V-DRILL',12,11,1,1,75,NULL,NULL,NULL,NULL,4.80,3200,'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(31,'Black+Decker 20V Max Cordless Vacuum','Cordless handheld vacuum with 20V MAX battery and washable filter. Perfect for quick cleanups.','20V MAX cordless handheld vacuum',79.99,59.99,'BLACKDECKER-20V-VAC',12,11,1,1,120,NULL,NULL,NULL,NULL,4.50,1800,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(32,'Purina Pro Plan Dog Food','High-protein dog food with real chicken as the first ingredient. Formulated for adult dogs of all sizes.','Adult dry dog food with real chicken',59.99,49.99,'PURINA-PRO-PLAN',12,12,1,1,200,NULL,NULL,NULL,NULL,4.60,8500,'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(33,'Kong Classic Dog Toy','Durable rubber dog toy designed for chewing and play. Available in multiple sizes and colors.','Red rubber dog toy for chewing',12.99,9.99,'KONG-CLASSIC',12,12,1,1,300,NULL,NULL,NULL,NULL,4.70,4200,'https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(34,'Olay Regenerist Micro-Sculpting Cream','Anti-aging face cream with amino-peptides and niacinamide. Fragrance-free formula for sensitive skin.','Fragrance-free anti-aging moisturizer',24.99,19.99,'OLAY-REGENERIST',12,13,1,1,150,NULL,NULL,NULL,NULL,4.40,6800,'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(35,'Maybelline Fit Me Foundation','Matte foundation for all skin types with natural finish. Oil-free formula that won\'t clog pores.','Oil-free foundation with natural finish',7.99,5.99,'MAYBELLINE-FITME',12,13,1,1,400,NULL,NULL,NULL,NULL,4.30,12000,'https://images.unsplash.com/photo-1592750475338-74b7b21085ab?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(36,'Starbucks Coffee Beans','Premium whole bean coffee with rich, bold flavor. Dark roast blend perfect for morning brewing.','Dark roast whole bean coffee',12.99,9.99,'STARBUCKS-DARK-ROAST',12,14,1,1,1000,NULL,NULL,NULL,NULL,4.50,2500,'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(37,'Lindt Excellence Dark Chocolate','Premium dark chocolate bars with 70% cocoa content. Smooth texture and intense chocolate flavor.','70% cocoa dark chocolate',4.99,3.99,'LINDT-EXCELLENCE-70',12,14,1,1,500,NULL,NULL,NULL,NULL,4.70,1800,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20'),(38,'Pampers Baby Dry Diapers','Overnight protection diapers with extra absorbency. Size 3 for babies 16-28 lbs, 128 count.','Size 3 baby diapers, 128 count',29.99,24.99,'PAMPERS-BABY-DRY',12,15,1,1,200,NULL,NULL,NULL,NULL,4.60,15000,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:27:39'),(39,'Fisher-Price Baby Swing','Electronic baby swing with 6 speeds, 2 recline positions, and 16 melodies. Soothing motion for babies.','Electronic baby swing with music',89.99,69.99,'FISHER-PRICE-SWING',20,15,1,1,50,NULL,NULL,NULL,NULL,4.50,3200,'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=300&h=200&fit=crop','active','2025-09-21 19:21:34','2025-09-21 19:28:20');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `rating` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `comment` text,
  `verified_purchase` tinyint(1) DEFAULT '0',
  `helpful_votes` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_chk_1` CHECK (((`rating` >= 1) and (`rating` <= 5)))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,1,5,'Amazing phone!','The iPhone 15 Pro is incredible. The camera quality is outstanding and the performance is smooth.',1,12,'2025-09-21 19:21:34'),(2,2,1,4,'Great upgrade','Upgraded from iPhone 12. The new features are nice, but the price is quite high.',1,8,'2025-09-21 19:21:34'),(3,1,2,5,'Best Android phone','Samsung Galaxy S24 Ultra is the best Android phone I\'ve used. The S Pen is very useful.',1,15,'2025-09-21 19:21:34'),(4,2,4,5,'Excellent headphones','Sony WH-1000XM5 has amazing noise cancellation. Perfect for travel and work.',1,20,'2025-09-21 19:21:34'),(5,1,7,5,'Perfect laptop','MacBook Pro 16-inch is perfect for my work. The M3 chip is incredibly fast.',1,25,'2025-09-21 19:21:34');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `theme_settings`
--

DROP TABLE IF EXISTS `theme_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `theme_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `theme_settings`
--

LOCK TABLES `theme_settings` WRITE;
/*!40000 ALTER TABLE `theme_settings` DISABLE KEYS */;
INSERT INTO `theme_settings` VALUES (1,'current_theme','default','2025-09-23 22:51:15','2025-09-23 23:31:58');
/*!40000 ALTER TABLE `theme_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(100) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'US',
  `is_admin` tinyint(1) DEFAULT '0',
  `email_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `theme` varchar(50) DEFAULT 'default',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'johndoe','John','Doe','john.doe@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','555-0123','123 Main St','New York','NY','10001','US',0,0,'2025-09-21 19:21:34','2025-09-23 11:56:45','default'),(2,'janesmith','Jane','Smith','jane.smith@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','555-0456','456 Oak Ave','Los Angeles','CA','90210','US',0,0,'2025-09-21 19:21:34','2025-09-23 11:56:45','default'),(3,'admin','Admin','User','admin@example.com','$2y$12$DwwFT5lkVq2w5CKd0gsq5etYe0fKjjiaZ/.NKjpXjhQ9o5E5ZoyDK','555-0789','789 Pine St','Chicago','IL','60601','US',1,0,'2025-09-21 19:21:34','2025-09-23 22:24:33','default'),(4,'testuser','user','test','test@user.com','$2y$12$lvCwj6ILjWGL0YJQIQuCfeGMYraJS8gRORFMP.na/fpdpuqmK17FC','01201392000',NULL,NULL,NULL,NULL,'US',0,0,'2025-09-23 12:02:53','2025-09-23 12:02:53','default');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wishlists`
--

DROP TABLE IF EXISTS `wishlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wishlists` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_wishlist` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wishlists`
--

LOCK TABLES `wishlists` WRITE;
/*!40000 ALTER TABLE `wishlists` DISABLE KEYS */;
INSERT INTO `wishlists` VALUES (6,4,3,'2025-09-23 20:27:48'),(7,1,31,'2025-09-23 22:14:08'),(8,3,2,'2025-09-23 23:32:11');
/*!40000 ALTER TABLE `wishlists` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-09-24  0:05:42
