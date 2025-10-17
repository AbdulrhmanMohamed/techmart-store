<?php
session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';
require_once '../includes/seo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=admin');
    exit;
}

// Get dashboard statistics
try {
    // Total products
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $result = $stmt->fetch();
    $total_products = (int)($result ? $result['total'] : 0);
    
    // Total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $result = $stmt->fetch();
    $total_orders = (int)($result ? $result['total'] : 0);
    
    // Total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'");
    $result = $stmt->fetch();
    $total_revenue = (float)($result && $result['total'] ? $result['total'] : 0);
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    $total_users = (int)($result ? $result['total'] : 0);
    
    // Recent orders
    $stmt = $pdo->query("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll();
    
    // Low stock products
    $stmt = $pdo->query("
        SELECT * FROM products 
        WHERE stock_quantity <= 5 AND status = 'active' 
        ORDER BY stock_quantity ASC 
        LIMIT 5
    ");
    $low_stock_products = $stmt->fetchAll();
    
    // Top selling products
    $stmt = $pdo->query("
        SELECT p.*, COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
        WHERE p.status = 'active'
        GROUP BY p.id
        ORDER BY total_sold DESC, p.name ASC
        LIMIT 5
    ");
    $top_products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_products = $total_orders = $total_revenue = $total_users = 0;
    $recent_orders = $low_stock_products = $top_products = [];
}

// SEO Data
$seo_data = [
    'title' => 'Admin Dashboard - TechMart',
    'description' => 'Admin dashboard for managing TechMart e-commerce store',
    'keywords' => 'admin, dashboard, ecommerce, management',
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
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            color: white;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0.05) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stat-card:hover::before {
            opacity: 1;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .stat-card:nth-child(1) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        /* Chart Cards */
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
        
        /* Animated Icons */
        .stat-icon {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        /* Pulse Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        /* Glass Card Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        
        /* Number Counter Animation */
        .counter {
            transition: all 0.5s ease;
        }
        
        /* Header Gradient */
        .header-gradient {
            background: linear-gradient(135deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.8) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Floating animation for icons */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .stat-card .p-3 {
            animation: float 3s ease-in-out infinite;
        }
        
        /* Pulse animation for stats */
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.3); }
            50% { box-shadow: 0 0 30px rgba(59, 130, 246, 0.5); }
        }
        
        .stat-card:hover {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        
        /* Gradient text animation */
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .gradient-text-animated {
            background: linear-gradient(-45deg, #3b82f6, #8b5cf6, #ec4899, #10b981);
            background-size: 400% 400%;
            animation: gradient-shift 3s ease infinite;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
                    <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="products.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
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
                                    🏠 Admin Dashboard
                                </h2>
                                <p class="text-gray-600 mt-1">Manage your store with ease and efficiency</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="glass-card p-3 rounded-xl">
                                <span class="text-gray-700 font-medium hidden sm:block">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> ✨</span>
                            </div>
                            <a href="../logout.php" class="px-6 py-2 bg-gradient-to-r from-red-500 to-pink-600 text-white rounded-lg hover:from-red-600 hover:to-pink-700 transition-all transform hover:scale-105 shadow-lg">
                                🚪 Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">📦 Total Products</p>
                                <p class="text-3xl font-bold counter"><?php echo number_format($total_products); ?></p>
                                <p class="text-white/70 text-xs mt-1">📈 Active inventory</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl pulse-animation">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">🛒 Total Orders</p>
                                <p class="text-3xl font-bold counter"><?php echo number_format($total_orders); ?></p>
                                <p class="text-white/70 text-xs mt-1">🚀 Orders processed</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">💰 Total Revenue</p>
                                <p class="text-3xl font-bold counter">$<?php echo number_format($total_revenue, 2); ?></p>
                                <p class="text-white/70 text-xs mt-1">💎 Total earnings</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">👥 Total Users</p>
                                <p class="text-3xl font-bold counter"><?php echo number_format($total_users); ?></p>
                                <p class="text-white/70 text-xs mt-1">🎯 Registered users</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Orders -->
                    <div class="card p-8 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                                    🛒 Recent Orders
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Latest customer orders and transactions</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-purple-100 to-blue-100 rounded-xl">
                                <a href="orders.php" class="text-purple-600 hover:text-purple-700 font-medium text-sm transition-colors">View All →</a>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <?php if (empty($recent_orders)): ?>
                                <p class="text-muted text-center py-4">No orders yet</p>
                            <?php else: ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="flex items-center justify-between p-3 bg-tertiary rounded-lg">
                                        <div>
                                            <p class="font-medium text-primary">#<?php echo htmlspecialchars($order['order_number']); ?></p>
                                            <p class="text-sm text-muted"><?php echo htmlspecialchars($order['username'] ?: $order['email']); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-primary">$<?php echo number_format($order['total_amount'] ?: 0, 2); ?></p>
                                            <span class="inline-block px-2 py-1 text-xs rounded-full <?php 
                                                echo $order['status'] === 'processing' ? 'bg-warning/20 text-warning' : 
                                                    ($order['status'] === 'delivered' ? 'bg-success/20 text-success' : 'bg-info/20 text-info'); 
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Low Stock Products -->
                    <div class="card p-8 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-red-600 to-orange-600 bg-clip-text text-transparent">
                                    ⚠️ Low Stock Alert
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Products that need restocking attention</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-red-100 to-orange-100 rounded-xl">
                                <a href="products.php" class="text-red-600 hover:text-red-700 font-medium text-sm transition-colors">Manage →</a>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <?php if (empty($low_stock_products)): ?>
                                <p class="text-muted text-center py-4">All products are well stocked</p>
                            <?php else: ?>
                                <?php foreach ($low_stock_products as $product): ?>
                                    <div class="flex items-center justify-between p-3 bg-error/10 rounded-lg">
                                        <div>
                                            <p class="font-medium text-primary"><?php echo htmlspecialchars($product['name']); ?></p>
                                            <p class="text-sm text-muted">SKU: <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-error"><?php echo isset($product['stock_quantity']) ? $product['stock_quantity'] : 0; ?> left</p>
                                            <span class="text-xs text-muted">Low Stock</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="mt-8">
                    <div class="card p-8 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                                    🏆 Top Selling Products
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Best performing products in your store</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-green-100 to-teal-100 rounded-xl">
                                <a href="products.php" class="text-green-600 hover:text-green-700 font-medium text-sm transition-colors">View All →</a>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b border-primary/20">
                                        <th class="text-left py-3 px-4 text-sm font-medium text-muted">Product</th>
                                        <th class="text-left py-3 px-4 text-sm font-medium text-muted">Price</th>
                                        <th class="text-left py-3 px-4 text-sm font-medium text-muted">Sold</th>
                                        <th class="text-left py-3 px-4 text-sm font-medium text-muted">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_products)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-8 text-muted">No sales data available</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr class="border-b border-primary/10">
                                                <td class="py-3 px-4">
                                                    <div class="flex items-center space-x-3">
                                                        <?php if ($product['image_url']): ?>
                                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                                 class="w-10 h-10 rounded-lg object-cover">
                                                        <?php else: ?>
                                                            <div class="w-10 h-10 bg-gradient-to-br from-primary to-secondary rounded-lg flex items-center justify-center text-white font-bold text-sm">
                                                                <?php echo strtoupper(substr($product['name'], 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <p class="font-medium text-primary"><?php echo htmlspecialchars($product['name']); ?></p>
                                                            <p class="text-sm text-muted">SKU: <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="py-3 px-4 text-primary">$<?php echo number_format($product['price'] ?: 0, 2); ?></td>
                                <td class="py-3 px-4 text-primary"><?php echo number_format(isset($product['total_sold']) ? $product['total_sold'] : 0); ?></td>
                                <td class="py-3 px-4 text-primary">$<?php echo number_format((isset($product['total_sold']) ? $product['total_sold'] : 0) * (isset($product['price']) ? $product['price'] : 0), 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Responsive sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                overlay.classList.toggle('show');
            }
        }

        // Close sidebar when clicking on nav links on mobile
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
                // Desktop view - ensure sidebar is visible
                sidebar.classList.remove('mobile-open', 'mobile-hidden');
                overlay.classList.remove('show');
                mainContent.classList.remove('mobile-full');
            } else {
                // Mobile view - ensure sidebar is hidden by default
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
    </script>
</body>
</html>
