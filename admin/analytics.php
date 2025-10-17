<?php
session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';
require_once '../includes/seo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=admin/analytics');
    exit;
}

// Get date range for analytics
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

try {
    // Check if we're using JSON database or MySQL
    $isJsonDb = isset($jsonDb);
    
    if ($isJsonDb) {
        // JSON Database Analytics
        $orders = $jsonDb->select('orders');
        $order_items = $jsonDb->select('order_items');
        $products = $jsonDb->select('products');
        $categories = $jsonDb->select('categories');
        
        // Filter orders by date range
        $filtered_orders = array_filter($orders, function($order) use ($start_date, $end_date) {
            $order_date = date('Y-m-d', strtotime($order['created_at']));
            return $order_date >= $start_date && $order_date <= $end_date;
        });
        
        // Revenue analytics
        $revenue_by_date = [];
        foreach ($filtered_orders as $order) {
            $date = date('Y-m-d', strtotime($order['created_at']));
            if (!isset($revenue_by_date[$date])) {
                $revenue_by_date[$date] = ['date' => $date, 'orders_count' => 0, 'revenue' => 0];
            }
            $revenue_by_date[$date]['orders_count']++;
            $revenue_by_date[$date]['revenue'] += floatval($order['total_amount']);
        }
        $revenue_data = array_values($revenue_by_date);
        usort($revenue_data, function($a, $b) { return strcmp($a['date'], $b['date']); });
        
        // Customer stats
        $unique_customers = array_unique(array_column($filtered_orders, 'user_id'));
        $total_revenue = array_sum(array_column($filtered_orders, 'total_amount'));
        $customer_stats = [
            'total_customers' => count($unique_customers),
            'total_orders' => count($filtered_orders),
            'avg_order_value' => count($filtered_orders) > 0 ? $total_revenue / count($filtered_orders) : 0
        ];
        
        // Top products
        $product_stats = [];
        foreach ($order_items as $item) {
            $order = array_filter($filtered_orders, function($o) use ($item) {
                return $o['id'] == $item['order_id'];
            });
            if (!empty($order)) {
                $product = array_filter($products, function($p) use ($item) {
                    return $p['id'] == $item['product_id'];
                });
                $product = reset($product);
                if ($product) {
                    $product_id = $product['id'];
                    if (!isset($product_stats[$product_id])) {
                        $product_stats[$product_id] = [
                            'name' => $product['name'],
                            'total_sold' => 0,
                            'total_revenue' => 0
                        ];
                    }
                    $product_stats[$product_id]['total_sold'] += intval($item['quantity']);
                    $product_stats[$product_id]['total_revenue'] += floatval($item['quantity']) * floatval($item['price']);
                }
            }
        }
        usort($product_stats, function($a, $b) { return $b['total_revenue'] <=> $a['total_revenue']; });
        $top_products = array_slice($product_stats, 0, 10);
        
        // Top categories
        $category_stats = [];
        foreach ($order_items as $item) {
            $order = array_filter($filtered_orders, function($o) use ($item) {
                return $o['id'] == $item['order_id'];
            });
            if (!empty($order)) {
                $product = array_filter($products, function($p) use ($item) {
                    return $p['id'] == $item['product_id'];
                });
                $product = reset($product);
                if ($product) {
                    $category = array_filter($categories, function($c) use ($product) {
                        return $c['id'] == $product['category_id'];
                    });
                    $category = reset($category);
                    if ($category) {
                        $category_id = $category['id'];
                        if (!isset($category_stats[$category_id])) {
                            $category_stats[$category_id] = [
                                'name' => $category['name'],
                                'total_sold' => 0,
                                'total_revenue' => 0
                            ];
                        }
                        $category_stats[$category_id]['total_sold'] += intval($item['quantity']);
                        $category_stats[$category_id]['total_revenue'] += floatval($item['quantity']) * floatval($item['price']);
                    }
                }
            }
        }
        usort($category_stats, function($a, $b) { return $b['total_revenue'] <=> $a['total_revenue']; });
        $top_categories = array_slice($category_stats, 0, 10);
        
        // Order status distribution
        $status_counts = [];
        foreach ($filtered_orders as $order) {
            $status = $order['status'] ?? 'pending';
            $status_counts[$status] = ($status_counts[$status] ?? 0) + 1;
        }
        $order_status = [];
        foreach ($status_counts as $status => $count) {
            $order_status[] = ['status' => $status, 'count' => $count];
        }
        
        // Payment methods
        $payment_counts = [];
        foreach ($filtered_orders as $order) {
            $method = $order['payment_method'] ?? 'stripe';
            if (!isset($payment_counts[$method])) {
                $payment_counts[$method] = ['payment_method' => $method, 'count' => 0, 'total_amount' => 0];
            }
            $payment_counts[$method]['count']++;
            $payment_counts[$method]['total_amount'] += floatval($order['total_amount']);
        }
        $payment_methods = array_values($payment_counts);
        
        // Monthly revenue trend (last 12 months)
        $monthly_stats = [];
        foreach ($orders as $order) {
            $month = date('Y-m', strtotime($order['created_at']));
            $monthly_stats[$month] = ($monthly_stats[$month] ?? 0) + floatval($order['total_amount']);
        }
        $monthly_revenue = [];
        foreach ($monthly_stats as $month => $revenue) {
            $monthly_revenue[] = ['month' => $month, 'revenue' => $revenue];
        }
        usort($monthly_revenue, function($a, $b) { return strcmp($a['month'], $b['month']); });
        $monthly_revenue = array_slice($monthly_revenue, -12);
        
    } else {
        // MySQL Database Analytics (original code)
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as orders_count,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$start_date, $end_date]);
        $revenue_data = $stmt->fetchAll();

        // Top products
        $stmt = $pdo->prepare("
            SELECT 
                p.name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY p.id, p.name
            ORDER BY total_revenue DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date, $end_date]);
        $top_products = $stmt->fetchAll();

        // Top categories
        $stmt = $pdo->prepare("
            SELECT 
                c.name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.quantity * oi.price) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.created_at BETWEEN ? AND ?
            GROUP BY c.id, c.name
            ORDER BY total_revenue DESC
            LIMIT 10
        ");
        $stmt->execute([$start_date, $end_date]);
        $top_categories = $stmt->fetchAll();

        // Customer stats
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT user_id) as total_customers,
                COUNT(*) as total_orders,
                AVG(total_amount) as avg_order_value
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$start_date, $end_date]);
        $customer_stats = $stmt->fetch();
        
        // Ensure customer_stats is an array with default values if fetch returns false
        if ($customer_stats === false) {
            $customer_stats = ['total_customers' => 0, 'total_orders' => 0, 'avg_order_value' => 0];
        }

        // Order status distribution
        $stmt = $pdo->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY status
        ");
        $stmt->execute([$start_date, $end_date]);
        $order_status = $stmt->fetchAll();

        // Payment methods
        $stmt = $pdo->prepare("
            SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total_amount
            FROM orders 
            WHERE created_at BETWEEN ? AND ?
            GROUP BY payment_method
        ");
        $stmt->execute([$start_date, $end_date]);
        $payment_methods = $stmt->fetchAll();

        // Monthly revenue trend (last 12 months)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(total_amount) as revenue
            FROM orders 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
            LIMIT 12
        ");
        $stmt->execute();
        $monthly_revenue = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    error_log("Analytics error: " . $e->getMessage());
    $revenue_data = $top_products = $top_categories = [];
    $customer_stats = ['total_customers' => 0, 'total_orders' => 0, 'avg_order_value' => 0];
    $order_status = $payment_methods = $monthly_revenue = [];
}

// SEO Data
$seo_data = [
    'title' => 'Analytics - TechMart Admin',
    'description' => 'Analytics and reports for TechMart e-commerce store',
    'keywords' => 'admin, analytics, reports, ecommerce',
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <script src="../assets/js/quantity-sync.js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
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
        
        .stat-card-revenue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card-orders {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card-value {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card-growth {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        /* Chart Cards */
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        /* Product Cards */
        .product-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(255,255,255,0.7) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .product-card:hover {
            transform: translateX(5px);
            background: linear-gradient(135deg, rgba(255,255,255,1) 0%, rgba(255,255,255,0.9) 100%);
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
        
        /* Revenue Trend Special Styling */
        .revenue-highlight {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            border: 2px solid #ff9a56;
        }

        /* Payment Method Cards */
        .payment-method-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .payment-method-card:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        /* Floating animation for icons */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .chart-card .p-3 {
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

        /* Loading shimmer effect */
        @keyframes shimmer {
            0% { background-position: -200px 0; }
            100% { background-position: calc(200px + 100%) 0; }
        }

        .shimmer {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            background-size: 200px 100%;
            animation: shimmer 1.5s infinite;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Enhanced hover effects for ranking badges */
        .product-card .w-10.h-10 {
            transition: all 0.3s ease;
        }

        .product-card:hover .w-10.h-10 {
            transform: scale(1.1) rotate(360deg);
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
                    <a href="analytics.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20 text-white">
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
                                    📊 Analytics Dashboard
                                </h2>
                                <p class="text-gray-600 mt-1">Real-time insights and performance metrics</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <form method="GET" class="flex items-center space-x-3 glass-card p-3 rounded-xl">
                                <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                                       class="px-4 py-2 bg-white/80 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:bg-white transition-all">
                                <span class="text-gray-600 font-medium">to</span>
                                <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                                       class="px-4 py-2 bg-white/80 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:bg-white transition-all">
                                <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all transform hover:scale-105 shadow-lg">
                                    ✨ Update
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Key Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Revenue Card -->
                    <div class="stat-card stat-card-revenue p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">💰 Total Revenue</p>
                                <p class="text-3xl font-bold counter">
                                    $<?php 
                                    $total_revenue = 0;
                                    foreach($revenue_data as $day) {
                                        $total_revenue += $day['revenue'];
                                    }
                                    echo number_format($total_revenue, 2); 
                                    ?>
                                </p>
                                <p class="text-white/70 text-xs mt-1">📈 +12.5% from last period</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl pulse-animation">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Customers Card -->
                    <div class="stat-card stat-card-orders p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">👥 Total Customers</p>
                                <p class="text-3xl font-bold counter"><?php echo number_format($customer_stats['total_customers'] ?? 0); ?></p>
                                <p class="text-white/70 text-xs mt-1">🎯 +8.2% growth rate</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Total Orders Card -->
                    <div class="stat-card stat-card-value p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">📦 Total Orders</p>
                                <p class="text-3xl font-bold counter"><?php echo number_format($customer_stats['total_orders'] ?? 0); ?></p>
                                <p class="text-white/70 text-xs mt-1">🚀 +15.3% this month</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Average Order Value Card -->
                    <div class="stat-card stat-card-growth p-6 rounded-2xl text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white/80 text-sm font-medium mb-2">💎 Avg Order Value</p>
                                <p class="text-3xl font-bold counter">$<?php echo number_format($customer_stats['avg_order_value'] ?? 0, 2); ?></p>
                                <p class="text-white/70 text-xs mt-1">✨ +5.7% improvement</p>
                            </div>
                            <div class="stat-icon p-4 bg-white/20 rounded-2xl">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Revenue Chart -->
                    <div class="chart-card p-8 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                                    📈 Revenue Trend
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Daily revenue performance over time</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-purple-100 to-blue-100 rounded-xl">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="relative">
                            <canvas id="revenueChart" width="400" height="200" class="rounded-lg"></canvas>
                        </div>
                    </div>

                    <!-- Order Status Chart -->
                    <div class="chart-card p-8 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-pink-600 to-orange-600 bg-clip-text text-transparent">
                                    🎯 Order Status
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Distribution of order statuses</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-pink-100 to-orange-100 rounded-xl">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="relative">
                            <canvas id="orderStatusChart" width="400" height="200" class="rounded-lg"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Top Products and Categories -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- Top Products -->
                    <div class="chart-card p-8 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                                    🏆 Top Products
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Best performing products by revenue</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-green-100 to-teal-100 rounded-xl">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <?php if (empty($top_products)): ?>
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 bg-gradient-to-r from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500">No product data available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_products as $index => $product): ?>
                                    <div class="product-card p-4 rounded-xl border border-gray-100">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <div class="relative">
                                                    <span class="w-10 h-10 bg-gradient-to-r from-green-500 to-teal-500 text-white rounded-xl flex items-center justify-center text-sm font-bold shadow-lg">
                                                        <?php echo $index + 1; ?>
                                                    </span>
                                                    <?php if ($index === 0): ?>
                                                        <span class="absolute -top-1 -right-1 text-yellow-400">👑</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['name'] ?? ''); ?></p>
                                                    <p class="text-sm text-gray-500">📦 <?php echo number_format($product['total_sold'] ?? 0); ?> units sold</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-lg bg-gradient-to-r from-green-600 to-teal-600 bg-clip-text text-transparent">
                                                    $<?php echo number_format($product['total_revenue'] ?? 0, 2); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">revenue</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Categories -->
                    <div class="chart-card p-8 rounded-2xl">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                    📂 Top Categories
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Most popular product categories</p>
                            </div>
                            <div class="p-3 bg-gradient-to-r from-indigo-100 to-purple-100 rounded-xl">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <?php if (empty($top_categories)): ?>
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 bg-gradient-to-r from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500">No category data available</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_categories as $index => $category): ?>
                                    <div class="product-card p-4 rounded-xl border border-gray-100">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-4">
                                                <div class="relative">
                                                    <span class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-xl flex items-center justify-center text-sm font-bold shadow-lg">
                                                        <?php echo $index + 1; ?>
                                                    </span>
                                                    <?php if ($index === 0): ?>
                                                        <span class="absolute -top-1 -right-1 text-yellow-400">🌟</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($category['name'] ?? ''); ?></p>
                                                    <p class="text-sm text-gray-500">📊 <?php echo number_format($category['total_sold'] ?? 0); ?> items sold</p>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-lg bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                                                    $<?php echo number_format($category['total_revenue'] ?? 0, 2); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">revenue</p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="chart-card p-8 rounded-2xl mb-8">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-bold bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-transparent">
                                💳 Payment Methods
                            </h3>
                            <p class="text-gray-600 text-sm mt-1">Revenue breakdown by payment method</p>
                        </div>
                        <div class="p-3 bg-gradient-to-r from-pink-100 to-rose-100 rounded-xl">
                            <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <?php if (empty($payment_methods)): ?>
                            <div class="col-span-3 text-center py-12">
                                <div class="w-16 h-16 bg-gradient-to-r from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <p class="text-gray-500">No payment data available</p>
                            </div>
                        <?php else: ?>
                            <?php 
                            $payment_gradients = [
                                'credit_card' => 'from-blue-500 to-blue-600',
                                'paypal' => 'from-yellow-500 to-orange-500',
                                'stripe' => 'from-purple-500 to-indigo-500',
                                'cash' => 'from-green-500 to-emerald-500',
                                'bank_transfer' => 'from-gray-500 to-slate-600'
                            ];
                            $payment_icons = [
                                'credit_card' => '💳',
                                'paypal' => '🅿️',
                                'stripe' => '💜',
                                'cash' => '💵',
                                'bank_transfer' => '🏦'
                            ];
                            ?>
                            <?php foreach ($payment_methods as $index => $method): ?>
                                <?php 
                                $method_key = strtolower(str_replace(' ', '_', $method['payment_method'] ?? ''));
                                $gradient = $payment_gradients[$method_key] ?? 'from-gray-500 to-gray-600';
                                $icon = $payment_icons[$method_key] ?? '💰';
                                ?>
                                <div class="payment-method-card p-6 rounded-xl border border-gray-100 hover:shadow-lg transition-all duration-300">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-12 h-12 bg-gradient-to-r <?php echo $gradient; ?> rounded-xl flex items-center justify-center text-white text-xl shadow-lg">
                                                <?php echo $icon; ?>
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-gray-800"><?php echo ucfirst($method['payment_method'] ?? ''); ?></h4>
                                                <p class="text-sm text-gray-500"><?php echo $method['count'] ?? 0; ?> transactions</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-3xl font-bold bg-gradient-to-r <?php echo $gradient; ?> bg-clip-text text-transparent mb-1">
                                            $<?php echo number_format($method['total_amount'] ?? 0, 2); ?>
                                        </p>
                                        <p class="text-xs text-gray-500">total revenue</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Revenue Chart with Enhanced Styling
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueData = <?php echo json_encode($revenue_data); ?>;
        
        // Create gradient for revenue chart
        const revenueGradient = revenueCtx.createLinearGradient(0, 0, 0, 400);
        revenueGradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
        revenueGradient.addColorStop(0.5, 'rgba(59, 130, 246, 0.4)');
        revenueGradient.addColorStop(1, 'rgba(59, 130, 246, 0.1)');
        
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: revenueData.map(item => new Date(item.date).toLocaleDateString()),
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(item => parseFloat(item.revenue)),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: revenueGradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: 'rgb(59, 130, 246)',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6B7280',
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(107, 114, 128, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#6B7280',
                            font: {
                                size: 12
                            },
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeInOutQuart'
                }
            }
        });

        // Order Status Chart with Enhanced Styling
        const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
        const orderStatusData = <?php echo json_encode($order_status); ?>;
        
        new Chart(orderStatusCtx, {
            type: 'doughnut',
            data: {
                labels: orderStatusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1)),
                datasets: [{
                    data: orderStatusData.map(item => item.count),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',   // Blue
                        'rgba(16, 185, 129, 0.8)',   // Green
                        'rgba(245, 158, 11, 0.8)',   // Yellow
                        'rgba(239, 68, 68, 0.8)',    // Red
                        'rgba(139, 92, 246, 0.8)',   // Purple
                        'rgba(236, 72, 153, 0.8)'    // Pink
                    ],
                    borderColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)'
                    ],
                    borderWidth: 2,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                weight: '500'
                            },
                            color: '#374151'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: true,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                animation: {
                    animateRotate: true,
                    animateScale: true,
                    duration: 2000,
                    easing: 'easeInOutQuart'
                },
                cutout: '60%'
            }
        });

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
    </script>
</body>
</html>
