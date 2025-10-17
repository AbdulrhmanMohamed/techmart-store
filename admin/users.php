<?php
session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';
require_once '../includes/seo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=admin/users');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_user') {
        $user_id = intval($_POST['user_id']);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $is_admin = isset($_POST['is_admin']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username=?, email=?, first_name=?, last_name=?, phone=?, is_admin=? 
                WHERE id=?
            ");
            $stmt->execute([$username, $email, $first_name, $last_name, $phone, $is_admin, $user_id]);
            $success_message = "User updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id']);
        try {
            // Don't actually delete, just deactivate
            $stmt = $pdo->prepare("UPDATE users SET email = CONCAT(email, '_deleted_', UNIX_TIMESTAMP()) WHERE id = ?");
            $stmt->execute([$user_id]);
            $success_message = "User deactivated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get users with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    if ($role_filter === 'admin') {
        $where_conditions[] = "is_admin = 1";
    } elseif ($role_filter === 'customer') {
        $where_conditions[] = "is_admin = 0";
    }
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM users $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Get users
$sql = "
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN o.total_amount ELSE 0 END), 0) as total_spent
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    $where_clause 
    GROUP BY u.id
    ORDER BY u.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// SEO Data
$seo_data = [
    'title' => 'Users Management - TechMart Admin',
    'description' => 'Manage users in TechMart e-commerce store',
    'keywords' => 'admin, users, management, ecommerce',
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
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20 text-white">
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
            <header class="header-gradient shadow-xl border-b border-primary/20 backdrop-blur-sm">
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
                                    👥 Users Management
                                </h2>
                                <p class="text-gray-600 mt-1">Manage your platform users and administrators</p>
                            </div>
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
                <div class="bg-white/70 backdrop-blur-sm rounded-2xl shadow-xl border border-white/20 p-8 mb-8">
                    <div class="mb-6">
                        <h3 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent mb-2">
                            🔍 Filter Users
                        </h3>
                        <p class="text-gray-600 text-sm">Search and filter users by name, email, or role</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search users..." 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300 hover:border-purple-300">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Role</label>
                            <select name="role" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-300 hover:border-purple-300">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white font-semibold py-3 px-6 rounded-xl hover:from-purple-700 hover:to-blue-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                                🔎 Apply Filters
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-6">
                        <h3 class="text-2xl font-bold text-white mb-2">👥 Users Directory</h3>
                        <p class="text-white/80 text-sm">Manage and monitor all platform users</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">👤 User</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📧 Contact</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">🛒 Orders</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">💰 Total Spent</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">🏷️ Role</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📅 Joined</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">⚙️ Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-12">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                </svg>
                                                <p class="text-gray-500 text-lg font-medium">No users found</p>
                                                <p class="text-gray-400 text-sm mt-1">Try adjusting your search criteria</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr class="border-b border-gray-100 hover:bg-gradient-to-r hover:from-purple-50 hover:to-blue-50 transition-all duration-300">
                                            <td class="py-5 px-6">
                                                <div class="flex items-center space-x-4">
                                                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-500 rounded-full flex items-center justify-center text-white font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                                        <?php echo strtoupper(substr($user['first_name'] ?? '', 0, 1) . substr($user['last_name'] ?? '', 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></p>
                                                        <p class="text-sm text-purple-600 font-medium">@<?php echo htmlspecialchars($user['username'] ?? ''); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div>
                                                    <p class="text-gray-800 font-semibold"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                                                    <?php if (!empty($user['phone'])): ?>
                                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($user['phone']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="text-center">
                                                    <span class="text-2xl font-bold text-blue-600"><?php echo $user['total_orders'] ?? 0; ?></span>
                                                    <p class="text-xs text-gray-500 mt-1">orders</p>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="text-center">
                                                    <span class="text-2xl font-bold text-green-600">$<?php echo number_format($user['total_spent'] ?? 0, 2); ?></span>
                                                    <p class="text-xs text-gray-500 mt-1">total spent</p>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <span class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-full border-2 <?php 
                                                    echo ($user['is_admin'] ?? false) ? 'bg-orange-100 text-orange-800 border-orange-200' : 'bg-blue-100 text-blue-800 border-blue-200'; 
                                                ?>">
                                                    <?php echo ($user['is_admin'] ?? false) ? '👑 Admin' : '👤 Customer'; ?>
                                                </span>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="text-center">
                                                    <p class="text-gray-800 font-semibold"><?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('g:i A', strtotime($user['created_at'] ?? 'now')); ?></p>
                                                </div>
                                            </td>
                                            <td class="py-5 px-6">
                                                <div class="flex space-x-3">
                                                    <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                            class="bg-blue-100 text-blue-600 hover:bg-blue-200 hover:text-blue-700 p-2 rounded-lg transition-all duration-300 transform hover:scale-110">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <?php if (($user['id'] ?? 0) != $_SESSION['user_id']): ?>
                                                        <button onclick="deleteUser(<?php echo $user['id'] ?? 0; ?>, '<?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>')" 
                                                                class="bg-red-100 text-red-600 hover:bg-red-200 hover:text-red-700 p-2 rounded-lg transition-all duration-300 transform hover:scale-110">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                            </svg>
                                                        </button>
                                                    <?php endif; ?>
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
                        <div class="bg-gradient-to-r from-purple-50 to-blue-50 px-8 py-6 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-purple-700">
                                    👥 Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_users); ?> of <span class="font-bold"><?php echo $total_users; ?></span> users
                                </p>
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                                           class="px-4 py-2 text-sm bg-white border-2 border-purple-200 text-purple-600 rounded-lg hover:bg-purple-50 hover:border-purple-300 transition-all duration-300">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                                           class="px-4 py-2 text-sm border-2 rounded-lg transition-all duration-300 <?php echo $i === $page ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white border-transparent' : 'bg-white border-purple-200 text-purple-600 hover:bg-purple-50 hover:border-purple-300'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                                           class="px-4 py-2 text-sm bg-white border-2 border-purple-200 text-purple-600 rounded-lg hover:bg-purple-50 hover:border-purple-300 transition-all duration-300">
                                            Next
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

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-black/50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full">
                <form method="POST" class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-primary">Edit User</h3>
                        <button type="button" onclick="closeEditUserModal()" class="text-muted hover:text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id" value="">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Username *</label>
                            <input type="text" name="username" id="edit_username" required 
                                   class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Email *</label>
                            <input type="email" name="email" id="edit_email" required 
                                   class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">First Name *</label>
                                <input type="text" name="first_name" id="edit_first_name" required 
                                       class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-muted mb-2">Last Name *</label>
                                <input type="text" name="last_name" id="edit_last_name" required 
                                       class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-muted mb-2">Phone</label>
                            <input type="tel" name="phone" id="edit_phone" 
                                   class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_admin" id="edit_is_admin" 
                                   class="w-4 h-4 text-primary border-primary/20 rounded focus:ring-primary/20">
                            <label for="edit_is_admin" class="ml-2 text-sm text-muted">Admin User</label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeEditUserModal()" class="btn btn-outline">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editUser(user) {
            document.getElementById('editUserModal').classList.remove('hidden');
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_is_admin').checked = user.is_admin == 1;
        }

        function closeEditUserModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }

        function deleteUser(id, name) {
            if (confirm(`Are you sure you want to deactivate "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
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

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEditUserModal();
            }
        });
    </script>
</body>
</html>
