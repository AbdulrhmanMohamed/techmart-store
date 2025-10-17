<?php
session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';
require_once '../includes/seo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=admin/orders');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['status'];
        $payment_status = $_POST['payment_status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$status, $payment_status, $order_id]);
            $success_message = "Order status updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get orders with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$payment_status_filter = $_GET['payment_status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

if ($payment_status_filter) {
    $where_conditions[] = "o.payment_status = ?";
    $params[] = $payment_status_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Get orders
$sql = "
    SELECT o.*, u.username, u.email, u.first_name, u.last_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    $where_clause 
    ORDER BY o.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order items for each order
foreach ($orders as &$order) {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.name as product_name, p.image_url 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
    
    // Ensure items is always an array and has proper structure
    if (!is_array($items)) {
        $items = [];
    }
    
    // Ensure each item has the required fields
    foreach ($items as &$item) {
        if (!isset($item['product_name'])) {
            $item['product_name'] = '';
        }
        if (!isset($item['image_url'])) {
            $item['image_url'] = '';
        }
    }
    
    $order['items'] = $items;
}

// SEO Data
$seo_data = [
    'title' => 'Orders Management - TechMart Admin',
    'description' => 'Manage orders in TechMart e-commerce store',
    'keywords' => 'admin, orders, management, ecommerce',
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
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
            backdrop-filter: blur(10px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 40;
            transition: transform 0.3s ease-in-out;
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
        
        /* Status badges */
        .status-badge {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
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
                    <a href="products.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Products</span>
                    </a>
                    <a href="orders.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20 text-white">
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
                                <h2 class="text-3xl font-bold bg-gradient-to-r from-orange-600 to-red-600 bg-clip-text text-transparent">
                                    📋 Orders Management
                                </h2>
                                <p class="text-gray-600 mt-1">Track and manage customer orders</p>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <button class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all transform hover:scale-105 shadow-lg flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>📊 Export Orders</span>
                            </button>
                        </div>
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
                <div class="card rounded-2xl p-8 mb-8 shadow-xl">
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent mb-2">
                            🔍 Filter Orders
                        </h3>
                        <p class="text-muted text-sm">Search and filter orders by status, payment, and more</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-muted mb-3">🔎 Search Orders</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by order ID, customer..." 
                                   class="w-full px-4 py-3 border-2 border-purple-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300 hover:border-purple-300">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-muted mb-3">📋 Order Status</label>
                            <select name="status" class="w-full px-4 py-3 border-2 border-purple-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300 hover:border-purple-300">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>⚙️ Processing</option>
                                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>🚚 Shipped</option>
                                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>✅ Delivered</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-muted mb-3">💳 Payment Status</label>
                            <select name="payment_status" class="w-full px-4 py-3 border-2 border-purple-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300 hover:border-purple-300">
                                <option value="">All Payment Status</option>
                                <option value="pending" <?php echo $payment_status_filter === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                <option value="paid" <?php echo $payment_status_filter === 'paid' ? 'selected' : ''; ?>>✅ Paid</option>
                                <option value="failed" <?php echo $payment_status_filter === 'failed' ? 'selected' : ''; ?>>❌ Failed</option>
                                <option value="refunded" <?php echo $payment_status_filter === 'refunded' ? 'selected' : ''; ?>>🔄 Refunded</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white font-semibold rounded-xl hover:from-purple-700 hover:to-blue-700 transform hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                                🔍 Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Orders Table -->
                <div class="card rounded-2xl shadow-xl">
                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-6 rounded-t-2xl">
                        <h3 class="text-2xl font-bold mb-2">📦 Orders Management</h3>
                        <p class="text-purple-100 text-sm">Track and manage all customer orders</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b-2 border-purple-100 bg-gradient-to-r from-purple-50 to-blue-50">
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">📋 Order</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">👤 Customer</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">📦 Items</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">💰 Total</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">📊 Status</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">💳 Payment</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">📅 Date</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-purple-800">⚙️ Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-12 text-muted">
                                            <div class="flex flex-col items-center">
                                                <div class="text-6xl mb-4">📦</div>
                                                <p class="text-xl font-semibold text-purple-600 mb-2">No orders found</p>
                                                <p class="text-sm text-gray-500">Orders will appear here once customers start placing them</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr class="border-b border-purple-100 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 transition-all duration-300">
                                            <td class="py-4 px-6">
                                                <div>
                                                    <p class="font-bold text-purple-700 text-lg">#<?php echo htmlspecialchars($order['order_number'] ?? ''); ?></p>
                                                    <p class="text-sm text-gray-600 font-medium"><?php echo htmlspecialchars($order['payment_method'] ?? ''); ?></p>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div>
                                                    <p class="font-bold text-gray-800 text-base">
                                                        <?php echo htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')); ?>
                                                    </p>
                                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($order['email'] ?? ''); ?></p>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="flex -space-x-2">
                                                    <?php 
                                                    $orderItems = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];
                                                    foreach (array_slice($orderItems, 0, 3) as $item): 
                                                        $itemImageUrl = isset($item['image_url']) ? $item['image_url'] : '';
                                                        $itemProductName = isset($item['product_name']) ? $item['product_name'] : '';
                                                    ?>
                                                        <?php if ($itemImageUrl): ?>
                                                            <img src="<?php echo htmlspecialchars($itemImageUrl); ?>" 
                                                                 alt="<?php echo htmlspecialchars($itemProductName); ?>" 
                                                                 class="w-10 h-10 rounded-full border-3 border-white object-cover shadow-lg hover:scale-110 transition-transform duration-200">
                                                        <?php else: ?>
                                                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full border-3 border-white flex items-center justify-center text-white font-bold text-sm shadow-lg hover:scale-110 transition-transform duration-200">
                                                                <?php echo $itemProductName ? strtoupper(substr($itemProductName, 0, 1)) : '?'; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                    <?php if (count($orderItems) > 3): ?>
                                                        <div class="w-10 h-10 bg-gradient-to-br from-gray-400 to-gray-600 rounded-full border-3 border-white flex items-center justify-center text-white font-bold text-sm shadow-lg">
                                                            +<?php echo count($orderItems) - 3; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-sm text-gray-600 mt-2 font-medium"><?php echo count($orderItems); ?> item(s)</p>
                                            </td>
                                            <td class="py-4 px-6">
                                                <p class="font-bold text-green-600 text-xl">$<?php echo number_format($order['total_amount'], 2); ?></p>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-full <?php 
                                                    echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                                        ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                                                        ($order['status'] === 'cancelled' ? 'bg-red-100 text-red-800 border border-red-200' : 
                                                        ($order['status'] === 'shipped' ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-gray-100 text-gray-800 border border-gray-200'))); 
                                                ?>">
                                                    <?php 
                                                    $statusEmojis = [
                                                        'pending' => '⏳',
                                                        'processing' => '⚙️',
                                                        'shipped' => '🚚',
                                                        'delivered' => '✅',
                                                        'cancelled' => '❌'
                                                    ];
                                                    echo ($statusEmojis[$order['status']] ?? '📋') . ' ' . ucfirst($order['status']); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-full <?php 
                                                    echo $order['payment_status'] === 'paid' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                                        ($order['payment_status'] === 'failed' ? 'bg-red-100 text-red-800 border border-red-200' : 
                                                        ($order['payment_status'] === 'refunded' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 'bg-gray-100 text-gray-800 border border-gray-200')); 
                                                ?>">
                                                    <?php 
                                                    $paymentEmojis = [
                                                        'pending' => '⏳',
                                                        'paid' => '✅',
                                                        'failed' => '❌',
                                                        'refunded' => '🔄'
                                                    ];
                                                    echo ($paymentEmojis[$order['payment_status']] ?? '💳') . ' ' . ucfirst($order['payment_status']); 
                                                    ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="text-gray-700 font-medium">
                                                    <p class="text-base"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                                    <p class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($order['created_at'])); ?></p>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="flex space-x-3">
                                                    <button onclick="viewOrder(<?php echo htmlspecialchars(json_encode($order)); ?>)" 
                                                            class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 hover:text-blue-700 transition-all duration-200 hover:scale-105">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                    </button>
                                                    <button onclick="editOrder(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>', '<?php echo $order['payment_status']; ?>')" 
                                                            class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 hover:text-purple-700 transition-all duration-200 hover:scale-105">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
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
                        <div class="px-8 py-6 bg-gradient-to-r from-purple-50 to-blue-50 border-t-2 border-purple-100 rounded-b-2xl">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-purple-700">
                                    📊 Showing <span class="font-bold text-purple-800"><?php echo $offset + 1; ?></span> to <span class="font-bold text-purple-800"><?php echo min($offset + $limit, $total_orders); ?></span> of <span class="font-bold text-purple-800"><?php echo $total_orders; ?></span> orders
                                </p>
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment_status=<?php echo urlencode($payment_status_filter); ?>" 
                                           class="px-4 py-2 text-sm font-medium bg-white border-2 border-purple-200 text-purple-700 rounded-xl hover:bg-purple-100 hover:border-purple-300 transition-all duration-200">
                                            ← Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment_status=<?php echo urlencode($payment_status_filter); ?>" 
                                           class="px-4 py-2 text-sm font-medium rounded-xl transition-all duration-200 <?php echo $i === $page ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg' : 'bg-white border-2 border-purple-200 text-purple-700 hover:bg-purple-100 hover:border-purple-300'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&payment_status=<?php echo urlencode($payment_status_filter); ?>" 
                                           class="px-4 py-2 text-sm font-medium bg-white border-2 border-purple-200 text-purple-700 rounded-xl hover:bg-purple-100 hover:border-purple-300 transition-all duration-200">
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

    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 bg-black/50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 id="orderModalTitle" class="text-xl font-semibold text-primary">Order Details</h3>
                        <button type="button" onclick="closeOrderModal()" class="text-muted hover:text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="orderDetails"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Order Modal -->
    <div id="editOrderModal" class="fixed inset-0 bg-black/50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <form method="POST" class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-primary">Update Order Status</h3>
                        <button type="button" onclick="closeEditOrderModal()" class="text-muted hover:text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_id" id="edit_order_id" value="">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Order Status</label>
                            <select name="status" id="edit_status" 
                                    class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Payment Status</label>
                            <select name="payment_status" id="edit_payment_status" 
                                    class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                                <option value="refunded">Refunded</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeEditOrderModal()" class="btn btn-outline">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewOrder(order) {
            document.getElementById('orderModal').classList.remove('hidden');
            document.getElementById('orderModalTitle').textContent = `Order #${order.order_number}`;
            
            let itemsHtml = '';
            order.items.forEach(item => {
                itemsHtml += `
                    <div class="flex items-center space-x-3 p-3 bg-tertiary rounded-lg mb-2">
                        ${item.image_url ? 
                            `<img src="${item.image_url}" alt="${item.product_name}" class="w-12 h-12 rounded-lg object-cover">` :
                            `<div class="w-12 h-12 bg-gradient-to-br from-primary to-secondary rounded-lg flex items-center justify-center text-white font-bold">
                                ${item.product_name.charAt(0).toUpperCase()}
                            </div>`
                        }
                        <div class="flex-1">
                            <p class="font-medium text-primary">${item.product_name}</p>
                            <p class="text-sm text-muted">Quantity: ${item.quantity}</p>
                        </div>
                        <p class="font-semibold text-primary">$${parseFloat(item.price).toFixed(2)}</p>
                    </div>
                `;
            });

            document.getElementById('orderDetails').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-primary mb-3">Order Information</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="text-muted">Order Number:</span> <span class="text-primary">#${order.order_number}</span></p>
                            <p><span class="text-muted">Total Amount:</span> <span class="text-primary">$${parseFloat(order.total_amount).toFixed(2)}</span></p>
                            <p><span class="text-muted">Payment Method:</span> <span class="text-primary">${order.payment_method}</span></p>
                            <p><span class="text-muted">Order Date:</span> <span class="text-primary">${new Date(order.created_at).toLocaleDateString()}</span></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-primary mb-3">Customer Information</h4>
                        <div class="space-y-2 text-sm">
                            <p><span class="text-muted">Name:</span> <span class="text-primary">${order.first_name} ${order.last_name}</span></p>
                            <p><span class="text-muted">Email:</span> <span class="text-primary">${order.email}</span></p>
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <h4 class="font-semibold text-primary mb-3">Order Items</h4>
                    ${itemsHtml}
                </div>
            `;
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }

        function editOrder(id, status, payment_status) {
            document.getElementById('editOrderModal').classList.remove('hidden');
            document.getElementById('edit_order_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_payment_status').value = payment_status;
        }

        function closeEditOrderModal() {
            document.getElementById('editOrderModal').classList.add('hidden');
        }

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

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOrderModal();
                closeEditOrderModal();
            }
        });
    </script>
</body>
</html>
