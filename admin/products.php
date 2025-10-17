<?php
session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';
require_once '../includes/seo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=admin/products');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $short_description = trim($_POST['short_description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $sale_price = floatval($_POST['sale_price'] ?? 0);
        $sku = trim($_POST['sku'] ?? '');
        $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $brand_id = intval($_POST['brand_id'] ?? 0);
        $image_url = trim($_POST['image_url'] ?? '');
        $featured = isset($_POST['featured']) ? 1 : 0;
        $status = $_POST['status'] ?? 'active';
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, description, short_description, price, sale_price, sku, stock_quantity, category_id, brand_id, image_url, featured, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $short_description, $price, $sale_price, $sku, $stock_quantity, $category_id, $brand_id, $image_url, $featured, $status]);
                $success_message = "Product added successfully!";
            } else {
                $product_id = intval($_POST['product_id']);
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET name=?, description=?, short_description=?, price=?, sale_price=?, sku=?, stock_quantity=?, category_id=?, brand_id=?, image_url=?, featured=?, status=? 
                    WHERE id=?
                ");
                $stmt->execute([$name, $description, $short_description, $price, $sale_price, $sku, $stock_quantity, $category_id, $brand_id, $image_url, $featured, $status, $product_id]);
                $success_message = "Product updated successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $product_id = intval($_POST['product_id']);
        try {
            $stmt = $pdo->prepare("UPDATE products SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$product_id]);
            $success_message = "Product deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get products with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}

if ($status_filter) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM products p $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Get products
$sql = "
    SELECT p.*, c.name as category_name, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    $where_clause 
    ORDER BY p.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories and brands for filters
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name")->fetchAll();

// SEO Data
$seo_data = [
    'title' => 'Products Management - TechMart Admin',
    'description' => 'Manage products in TechMart e-commerce store',
    'keywords' => 'admin, products, management, ecommerce',
    'image' => '/assets/images/og-admin.jpg',
    'type' => 'website',
    'noindex' => true
];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <?php echo generateSEOTags($seo_data); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <script src="../assets/js/quantity-sync.js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 40;
            transition: transform 0.3s ease-in-out;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .admin-sidebar.mobile-hidden {
            transform: translateX(-100%);
        }
        .main-content {
            margin-left: 16rem; /* 256px = w-64 */
            transition: margin-left 0.3s ease-in-out;
        }
        .main-content.mobile-full {
            margin-left: 0;
        }
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 35;
        }
        .mobile-overlay.show {
            display: block;
        }
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                z-index: 40;
            }
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Modern Gradient Cards */
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        /* Header Gradient */
        .header-gradient {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.8) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Form Cards */
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .form-card:hover {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        /* Table styling */
        .table-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        /* Button enhancements */
        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
        
        /* Glassmorphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
    </style>
</head>
<body class="bg-gray-100 transition-colors duration-300">
    <div class="flex h-screen">
        <!-- Mobile Overlay -->
        <div id="mobileOverlay" class="mobile-overlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <div id="sidebar" class="admin-sidebar w-64 text-white shadow-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-8">TechMart Admin</h1>
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="products.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Products</span>
                    </a>
                    <a href="orders.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span>Orders</span>
                    </a>
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Users</span>
                    </a>
                    <a href="categories.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span>Categories</span>
                    </a>
                    <a href="analytics.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>Analytics</span>
                    </a>
                    <a href="../index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300 mt-8">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Back to Store</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="header-gradient shadow-lg">
                <div class="px-6 py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <!-- Mobile Menu Button -->
                            <button id="menuButton" onclick="toggleSidebar()" class="md:hidden p-2 rounded-lg hover:bg-white/20 transition-colors">
                                <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <div>
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                                    📦 Products Management
                                </h2>
                                <p class="text-gray-600 mt-1">Manage your product catalog and inventory</p>
                            </div>
                        </div>
                        <button onclick="openProductModal()" class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all transform hover:scale-105 shadow-lg flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>✨ Add Product</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($success_message)): ?>
                    <div class="mb-6 p-4 bg-success/20 text-success rounded-lg">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="mb-6 p-4 bg-error/20 text-error rounded-lg">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card p-8 mb-8 rounded-2xl shadow-xl">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent flex items-center">
                            🔍 Filter Products
                        </h3>
                        <p class="text-gray-600 mt-1">Search and filter your product catalog</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">🔎 Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search products..." 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-purple-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">📂 Category</label>
                            <select name="category" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-purple-300 shadow-sm">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">📊 Status</label>
                            <select name="status" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all hover:border-purple-300 shadow-sm">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-purple-500 to-blue-600 text-white rounded-xl hover:from-purple-600 hover:to-blue-700 transition-all transform hover:scale-105 shadow-lg font-semibold">
                                ✨ Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Products Table -->
                <div class="card rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-6 bg-gradient-to-r from-gray-50 to-white border-b border-gray-200">
                        <h3 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent flex items-center">
                            📦 Products Catalog
                        </h3>
                        <p class="text-gray-600 mt-1">Manage your product inventory</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-200">
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📦 Product</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">🏷️ SKU</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">💰 Price</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📊 Stock</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📂 Category</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📈 Status</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">⚙️ Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-12 text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                                </svg>
                                                <p class="text-lg font-medium">No products found</p>
                                                <p class="text-sm">Try adjusting your filters or add a new product</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 transition-all duration-300 group">
                                            <td class="py-4 px-6">
                                                <div class="flex items-center space-x-3">
                                                    <?php if ($product['image_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                             class="w-14 h-14 rounded-xl object-cover shadow-md group-hover:shadow-lg transition-shadow">
                                                    <?php else: ?>
                                                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-bold shadow-md group-hover:shadow-lg transition-shadow">
                                                            <?php echo strtoupper(substr($product['name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <p class="font-semibold text-gray-800 group-hover:text-purple-600 transition-colors"><?php echo htmlspecialchars($product['name']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($product['short_description']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-gray-600 font-medium"><?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></td>
                                            <td class="py-4 px-6">
                                                <div>
                                                    <span class="text-green-600 font-bold text-lg">$<?php echo number_format($product['price'], 2); ?></span>
                                                    <?php if ($product['sale_price'] > 0): ?>
                                                        <span class="text-emerald-500 text-sm ml-2 bg-emerald-50 px-2 py-1 rounded-full">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="<?php echo $product['stock_quantity'] <= 5 ? 'text-red-600 bg-red-50 px-3 py-1 rounded-full font-semibold' : 'text-blue-600 bg-blue-50 px-3 py-1 rounded-full font-semibold'; ?>">
                                                    <?php echo $product['stock_quantity']; ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6 text-gray-600 font-medium"><?php echo htmlspecialchars($product['category_name'] ?: 'N/A'); ?></td>
                                            <td class="py-4 px-6">
                                                <span class="inline-block px-3 py-1 text-sm font-semibold rounded-full <?php 
                                                    echo $product['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; 
                                                ?>">
                                                    <?php echo ucfirst($product['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="flex space-x-3">
                                                    <button onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                                            class="p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-all transform hover:scale-110">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')" 
                                                            class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-all transform hover:scale-110">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-6 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                            <div class="flex items-center justify-between">
                                <p class="text-sm text-gray-600 font-medium">
                                    📊 Showing <span class="font-bold text-purple-600"><?php echo $offset + 1; ?></span> to <span class="font-bold text-purple-600"><?php echo min($offset + $limit, $total_products); ?></span> of <span class="font-bold text-purple-600"><?php echo $total_products; ?></span> products
                                </p>
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                           class="px-4 py-2 text-sm bg-white border-2 border-gray-200 rounded-lg hover:bg-purple-50 hover:border-purple-300 transition-all font-medium">
                                            ← Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                           class="px-4 py-2 text-sm border-2 rounded-lg font-medium transition-all <?php echo $i === $page ? 'bg-gradient-to-r from-purple-500 to-blue-600 text-white border-transparent shadow-lg' : 'bg-white border-gray-200 hover:bg-purple-50 hover:border-purple-300'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                           class="px-4 py-2 text-sm bg-white border-2 border-gray-200 rounded-lg hover:bg-purple-50 hover:border-purple-300 transition-all font-medium">
                                            Next →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 transition-all duration-300">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[95vh] overflow-y-auto shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="productModalContent">
                <form method="POST" class="relative">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-t-2xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 id="modalTitle" class="text-2xl font-bold">📦 Add Product</h3>
                                    <p class="text-white/80 text-sm">Create a new product for your store</p>
                                </div>
                            </div>
                            <button type="button" onclick="closeProductModal()" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-105">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="product_id" id="product_id" value="">

                    <!-- Modal Body -->
                    <div class="p-8 space-y-6">
                        <!-- Basic Information Section -->
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Basic Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Product Name *</label>
                                    <div class="relative">
                                        <input type="text" name="name" id="name" required 
                                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                               placeholder="Enter product name">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Short Description</label>
                                    <input type="text" name="short_description" id="short_description" 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                           placeholder="Brief product description">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Description</label>
                                    <textarea name="description" id="description" rows="4" 
                                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm resize-none"
                                              placeholder="Detailed product description..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing & Inventory Section -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Pricing & Inventory
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Price * 💰</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500 text-lg">$</span>
                                        </div>
                                        <input type="number" name="price" id="price" step="0.01" min="0" required 
                                               class="w-full pl-8 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Sale Price 🏷️</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                            <span class="text-gray-500 text-lg">$</span>
                                        </div>
                                        <input type="number" name="sale_price" id="sale_price" step="0.01" min="0" 
                                               class="w-full pl-8 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">SKU 🏷️</label>
                                    <input type="text" name="sku" id="sku" 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                           placeholder="Product SKU">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Stock Quantity * 📦</label>
                                    <input type="number" name="stock_quantity" id="stock_quantity" min="0" required 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                           placeholder="0">
                                </div>
                            </div>
                        </div>

                        <!-- Categories & Organization Section -->
                        <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                </svg>
                                Categories & Organization
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Category 📁</label>
                                    <select name="category_id" id="category_id" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm">
                                        <option value="">📂 Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>">📁 <?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Brand 🏢</label>
                                    <select name="brand_id" id="brand_id" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm">
                                        <option value="">🏪 Select Brand</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?php echo $brand['id']; ?>">🏢 <?php echo htmlspecialchars($brand['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Image Section -->
                        <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-6 border border-orange-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Product Image
                            </h4>
                            
                            <!-- Image Preview -->
                            <div id="imagePreview" class="hidden mb-6">
                                <div class="relative inline-block">
                                    <img id="previewImg" src="" alt="Preview" class="w-40 h-40 object-cover rounded-2xl border-4 border-white shadow-lg">
                                    <button type="button" onclick="clearImage()" class="absolute -top-2 -right-2 w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-all duration-200 hover:scale-110 shadow-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Upload Options -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- File Upload -->
                                <div class="bg-white/60 rounded-xl p-4 border-2 border-dashed border-orange-200 hover:border-orange-400 transition-all duration-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">📁 Upload Image</label>
                                    <input type="file" id="imageFile" accept="image/*" 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm">
                                    <p class="text-xs text-gray-500 mt-2">📏 Max 5MB • 🖼️ JPEG, PNG, GIF, WebP</p>
                                </div>
                                
                                <!-- URL Input -->
                                <div class="bg-white/60 rounded-xl p-4 border-2 border-dashed border-orange-200 hover:border-orange-400 transition-all duration-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">🔗 Or Image URL</label>
                                    <input type="url" name="image_url" id="image_url" 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                           placeholder="https://example.com/image.jpg">
                                </div>
                            </div>
                            
                            <!-- Upload Progress -->
                            <div id="uploadProgress" class="hidden mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div id="progressBar" class="bg-gradient-to-r from-orange-500 to-red-500 h-3 rounded-full transition-all duration-300 shadow-sm" style="width: 0%"></div>
                                </div>
                                <p class="text-sm text-gray-600 mt-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 animate-spin text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Uploading...
                                </p>
                            </div>
                        </div>

                        <!-- Settings Section -->
                        <div class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-xl p-6 border border-gray-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Settings
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-white/60 rounded-xl p-4">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Status</label>
                                    <select name="status" id="status" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm">
                                        <option value="active">✅ Active</option>
                                        <option value="inactive">❌ Inactive</option>
                                    </select>
                                </div>

                                <div class="bg-white/60 rounded-xl p-4 flex items-center justify-center">
                                    <div class="flex items-center space-x-3">
                                        <input type="checkbox" name="featured" id="featured" 
                                               class="w-5 h-5 text-emerald-600 border-2 border-gray-300 rounded-lg focus:ring-emerald-500 focus:ring-2">
                                        <label for="featured" class="text-sm font-semibold text-gray-700 flex items-center">
                                            ⭐ Featured Product
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 rounded-b-2xl px-8 py-6 border-t border-gray-100">
                        <div class="flex justify-end space-x-4">
                            <button type="button" onclick="closeProductModal()" class="px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 font-semibold flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Cancel</span>
                            </button>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all duration-200 transform hover:scale-105 shadow-lg font-semibold flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Save Product</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openProductModal() {
            const modal = document.getElementById('productModal');
            const modalContent = document.getElementById('productModalContent');
            
            modal.classList.remove('hidden');
            
            // Add entrance animation
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
            
            document.getElementById('modalTitle').innerHTML = '📦 Add Product';
            document.getElementById('action').value = 'add';
            document.getElementById('product_id').value = '';
            document.querySelector('#productModal form').reset();
            clearImage();
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            const modalContent = document.getElementById('productModalContent');
            
            // Add exit animation
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function editProduct(product) {
            const modal = document.getElementById('productModal');
            const modalContent = document.getElementById('productModalContent');
            
            modal.classList.remove('hidden');
            
            // Add entrance animation
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
            
            document.getElementById('modalTitle').innerHTML = '✏️ Edit Product';
            document.getElementById('action').value = 'edit';
            document.getElementById('product_id').value = product.id;
            document.getElementById('name').value = product.name;
            document.getElementById('description').value = product.description || '';
            document.getElementById('short_description').value = product.short_description || '';
            document.getElementById('price').value = product.price;
            document.getElementById('sale_price').value = product.sale_price || '';
            document.getElementById('sku').value = product.sku || '';
            document.getElementById('stock_quantity').value = product.stock_quantity;
            document.getElementById('category_id').value = product.category_id || '';
            document.getElementById('brand_id').value = product.brand_id || '';
            document.getElementById('image_url').value = product.image_url || '';
            document.getElementById('status').value = product.status;
            document.getElementById('featured').checked = product.featured == 1;
            
            // Show image preview if image URL exists
            if (product.image_url) {
                document.getElementById('previewImg').src = product.image_url;
                document.getElementById('imagePreview').classList.remove('hidden');
            }
        }

        function deleteProduct(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Image upload functionality
        document.getElementById('imageFile').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                uploadImage(file);
            }
        });

        function uploadImage(file) {
            const formData = new FormData();
            formData.append('image', file);

            // Show progress
            document.getElementById('uploadProgress').classList.remove('hidden');
            document.getElementById('progressBar').style.width = '0%';

            // Simulate progress (since we can't track real progress with fetch)
            let progress = 0;
            const progressInterval = setInterval(() => {
                progress += 10;
                document.getElementById('progressBar').style.width = progress + '%';
                if (progress >= 90) {
                    clearInterval(progressInterval);
                }
            }, 100);

            fetch('upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                document.getElementById('progressBar').style.width = '100%';
                
                if (data.success) {
                    // Set the URL input with the uploaded image URL
                    document.getElementById('image_url').value = data.url;
                    
                    // Show preview
                    showImagePreview(data.url);
                    
                    // Hide progress after a delay
                    setTimeout(() => {
                        document.getElementById('uploadProgress').classList.add('hidden');
                    }, 1000);
                } else {
                    alert('Upload failed: ' + data.error);
                    document.getElementById('uploadProgress').classList.add('hidden');
                }
            })
            .catch(error => {
                clearInterval(progressInterval);
                console.error('Error:', error);
                alert('Upload failed: ' + error.message);
                document.getElementById('uploadProgress').classList.add('hidden');
            });
        }

        function showImagePreview(url) {
            document.getElementById('previewImg').src = url;
            document.getElementById('imagePreview').classList.remove('hidden');
        }

        function clearImage() {
            document.getElementById('image_url').value = '';
            document.getElementById('imageFile').value = '';
            document.getElementById('imagePreview').classList.add('hidden');
        }

        // Update editProduct function to show image preview
        const originalEditProduct = editProduct;
        editProduct = function(product) {
            originalEditProduct(product);
            if (product.image_url) {
                showImagePreview(product.image_url);
            } else {
                document.getElementById('imagePreview').classList.add('hidden');
            }
        };

        // Responsive sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('show');
            }
        }

        function closeSidebarOnMobile() {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('mobileOverlay');
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('show');
            }
        }

        // Add click listeners to all nav links
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.admin-sidebar nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', closeSidebarOnMobile);
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('mobile-open', 'mobile-hidden');
                overlay.classList.remove('show');
                mainContent.classList.remove('mobile-full');
            } else {
                sidebar.classList.add('mobile-hidden');
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('show');
                mainContent.classList.add('mobile-full');
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth <= 768) {
                const sidebar = document.getElementById('sidebar');
                const mainContent = document.querySelector('.main-content');
                const overlay = document.getElementById('mobileOverlay');
                sidebar.classList.add('mobile-hidden');
                mainContent.classList.add('mobile-full');
                overlay.classList.remove('show');
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProductModal();
            }
        });
    </script>
</body>
</html>
