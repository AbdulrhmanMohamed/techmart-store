<?php
session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';
require_once '../includes/seo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=admin/categories');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');
        $parent_id = intval($_POST['parent_id'] ?? 0);
        $status = $_POST['status'] ?? 'active';
        
        // Generate slug if not provided
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        }
        
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("
                    INSERT INTO categories (name, description, slug, image_url, parent_id, status) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $slug, $image_url, $parent_id ?: null, $status]);
                $success_message = "Category added successfully!";
            } else {
                $category_id = intval($_POST['category_id']);
                $stmt = $pdo->prepare("
                    UPDATE categories 
                    SET name=?, description=?, slug=?, image_url=?, parent_id=?, status=? 
                    WHERE id=?
                ");
                $stmt->execute([$name, $description, $slug, $image_url, $parent_id ?: null, $status, $category_id]);
                $success_message = "Category updated successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $category_id = intval($_POST['category_id']);
        try {
            // Check if category has products
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$category_id]);
            $product_count = $stmt->fetchColumn();
            
            if ($product_count > 0) {
                $error_message = "Cannot delete category with products. Please move or delete products first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $success_message = "Category deleted successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
}

// Get categories with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter) {
    $where_conditions[] = "c.status = ?";
    $params[] = $status_filter;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_sql = "SELECT COUNT(*) FROM categories c $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_categories = $stmt->fetchColumn();
$total_pages = ceil($total_categories / $limit);

// Get categories
$sql = "
    SELECT c.*, 
           p.name as parent_name,
           COUNT(pr.id) as product_count
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    LEFT JOIN products pr ON c.id = pr.category_id 
    $where_clause 
    GROUP BY c.id
    ORDER BY c.name ASC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll();

// Get all categories for parent selection
$all_categories = $pdo->query("SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name")->fetchAll();

// SEO Data
$seo_data = [
    'title' => 'Categories Management - TechMart Admin',
    'description' => 'Manage categories in TechMart e-commerce store',
    'keywords' => 'admin, categories, management, ecommerce',
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
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Users</span>
                    </a>
                    <a href="categories.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20 text-white">
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
                                    🏷️ Categories Management
                                </h2>
                                <p class="text-gray-600 mt-1">Organize and manage your product categories</p>
                            </div>
                        </div>
                        <button onclick="openCategoryModal()" class="bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span>➕ Add Category</span>
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
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-purple-100/50 p-6 mb-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent">
                            🔍 Filter Categories
                        </h3>
                        <p class="text-gray-600 text-sm">Search and filter your categories</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search categories..." 
                                   class="w-full px-4 py-3 border border-purple-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all duration-300 hover:border-purple-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select name="status" class="w-full px-4 py-3 border border-purple-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-400 transition-all duration-300 hover:border-purple-300">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white px-6 py-3 rounded-xl font-semibold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300 flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                <span>🔍 Apply Filters</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Categories Table -->
                <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl border border-purple-100/50 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 px-6 py-4">
                        <h3 class="text-xl font-bold text-white">🏷️ Categories Catalog</h3>
                        <p class="text-white/80 text-sm">Manage your product categories</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50/80 border-b border-purple-100">
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">🏷️ Category</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">👨‍👩‍👧‍👦 Parent</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📦 Products</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📊 Status</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">📅 Created</th>
                                    <th class="text-left py-4 px-6 text-sm font-bold text-gray-700">⚡ Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-gray-500">
                                            <div class="flex flex-col items-center space-y-2">
                                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                </svg>
                                                <span class="font-medium">No categories found</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="border-b border-purple-100/50 hover:bg-purple-50/50 transition-all duration-300">
                                            <td class="py-4 px-6">
                                                <div class="flex items-center space-x-4">
                                                    <?php if ($category['image_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($category['image_url']); ?>" 
                                                             alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                                             class="w-14 h-14 rounded-xl object-cover shadow-lg hover:shadow-xl transition-all duration-300">
                                                    <?php else: ?>
                                                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-blue-500 rounded-xl flex items-center justify-center text-white font-bold text-lg shadow-lg hover:shadow-xl transition-all duration-300">
                                                            <?php echo strtoupper(substr($category['name'], 0, 1)); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <p class="font-bold text-gray-800 text-lg"><?php echo htmlspecialchars($category['name']); ?></p>
                                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($category['description']); ?></p>
                                                        <p class="text-xs text-purple-600 font-medium">/<?php echo htmlspecialchars($category['slug'] ?? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $category['name']))); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6 text-gray-600 font-medium">
                                                <?php echo isset($category['parent_name']) && $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '—'; ?>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="text-purple-600 font-bold text-lg"><?php echo $category['product_count'] ?? 0; ?></span>
                                                <span class="text-gray-500 text-sm ml-1">items</span>
                                            </td>
                                            <td class="py-4 px-6">
                                                <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full border <?php 
                                                    echo $category['status'] === 'active' 
                                                        ? 'bg-green-50 text-green-700 border-green-200' 
                                                        : 'bg-red-50 text-red-700 border-red-200'; 
                                                ?>">
                                                    <?php echo $category['status'] === 'active' ? '✅' : '❌'; ?>
                                                    <?php echo ucfirst($category['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-4 px-6 text-gray-600 font-medium">
                                                <div class="flex flex-col">
                                                    <span><?php echo date('M j, Y', strtotime($category['created_at'])); ?></span>
                                                    <span class="text-xs text-gray-400"><?php echo date('g:i A', strtotime($category['created_at'])); ?></span>
                                                </div>
                                            </td>
                                            <td class="py-4 px-6">
                                                <div class="flex space-x-3">
                                                    <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                            class="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-all duration-300 border border-blue-200">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                        </svg>
                                                    </button>
                                                    <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['product_count'] ?? 0; ?>)" 
                                                            class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 hover:text-red-700 transition-all duration-300 border border-red-200">
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
                        <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-blue-50 border-t border-purple-100">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-purple-700">
                                    📊 Showing <span class="font-bold"><?php echo $offset + 1; ?></span> to <span class="font-bold"><?php echo min($offset + $limit, $total_categories); ?></span> of <span class="font-bold"><?php echo $total_categories; ?></span> categories
                                </p>
                                <div class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                           class="px-4 py-2 text-sm bg-white border border-purple-200 rounded-lg hover:bg-purple-50 hover:border-purple-300 transition-all duration-300 text-purple-700 font-medium">
                                            ← Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                           class="px-4 py-2 text-sm border rounded-lg transition-all duration-300 font-medium <?php echo $i === $page ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white border-purple-600' : 'bg-white border-purple-200 hover:bg-purple-50 hover:border-purple-300 text-purple-700'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                           class="px-4 py-2 text-sm bg-white border border-purple-200 rounded-lg hover:bg-purple-50 hover:border-purple-300 transition-all duration-300 text-purple-700 font-medium">
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

    <!-- Category Modal -->
    <div id="categoryModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 transition-all duration-300">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl max-w-3xl w-full max-h-[95vh] overflow-y-auto shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
                <form method="POST" class="relative">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-t-2xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 id="modalTitle" class="text-2xl font-bold">✨ Add Category</h3>
                                    <p class="text-white/80 text-sm">Create a new product category</p>
                                </div>
                            </div>
                            <button type="button" onclick="closeCategoryModal()" class="w-10 h-10 bg-white/20 hover:bg-white/30 rounded-xl flex items-center justify-center transition-all duration-200 hover:scale-105">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <input type="hidden" name="action" id="action" value="add">
                    <input type="hidden" name="category_id" id="category_id" value="">

                    <!-- Modal Body -->
                    <div class="p-8 space-y-6">
                        <!-- Basic Information Section -->
                        <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-xl p-6 border border-purple-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Basic Information
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Category Name *</label>
                                    <div class="relative">
                                        <input type="text" name="name" id="name" required 
                                               class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                               placeholder="Enter category name">
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Description</label>
                                    <textarea name="description" id="description" rows="4" 
                                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm resize-none"
                                              placeholder="Describe this category..."></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Slug</label>
                                    <input type="text" name="slug" id="slug" 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                           placeholder="auto-generated">
                                    <p class="text-xs text-gray-500 mt-2">Leave empty to auto-generate from name</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">Parent Category</label>
                                    <select name="parent_id" id="parent_id" 
                                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm">
                                        <option value="">🏠 No Parent (Top Level)</option>
                                        <?php foreach ($all_categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">📁 <?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Image Section -->
                        <div class="bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl p-6 border border-emerald-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                Category Image
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
                                <div class="bg-white/60 rounded-xl p-4 border-2 border-dashed border-emerald-200 hover:border-emerald-400 transition-all duration-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">📁 Upload Image</label>
                                    <input type="file" id="imageFile" accept="image/*" 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm">
                                    <p class="text-xs text-gray-500 mt-2">📏 Max 5MB • 🖼️ JPEG, PNG, GIF, WebP</p>
                                </div>
                                
                                <!-- URL Input -->
                                <div class="bg-white/60 rounded-xl p-4 border-2 border-dashed border-emerald-200 hover:border-emerald-400 transition-all duration-200">
                                    <label class="block text-sm font-semibold text-gray-700 mb-3">🔗 Or Image URL</label>
                                    <input type="url" name="image_url" id="image_url" 
                                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm"
                                           placeholder="https://example.com/image.jpg">
                                </div>
                            </div>
                            
                            <!-- Upload Progress -->
                            <div id="uploadProgress" class="hidden mt-4">
                                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                                    <div id="progressBar" class="bg-gradient-to-r from-emerald-500 to-teal-500 h-3 rounded-full transition-all duration-300 shadow-sm" style="width: 0%"></div>
                                </div>
                                <p class="text-sm text-gray-600 mt-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 animate-spin text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Uploading...
                                </p>
                            </div>
                        </div>

                        <!-- Settings Section -->
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl p-6 border border-amber-100">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Settings
                            </h4>
                            <div class="bg-white/60 rounded-xl p-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-3">Status</label>
                                <select name="status" id="status" 
                                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all duration-200 bg-white/80 backdrop-blur-sm">
                                    <option value="active">✅ Active</option>
                                    <option value="inactive">❌ Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 rounded-b-2xl px-8 py-6 border-t border-gray-100">
                        <div class="flex justify-end space-x-4">
                            <button type="button" onclick="closeCategoryModal()" class="px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 transition-all duration-200 font-semibold flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                <span>Cancel</span>
                            </button>
                            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl hover:from-purple-700 hover:to-blue-700 transition-all duration-200 transform hover:scale-105 shadow-lg font-semibold flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>Save Category</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCategoryModal() {
            const modal = document.getElementById('categoryModal');
            const modalContent = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            document.getElementById('modalTitle').textContent = '✨ Add Category';
            document.getElementById('action').value = 'add';
            document.getElementById('category_id').value = '';
            
            // Reset form
            const form = modal.querySelector('form');
            form.reset();
            
            // Clear image preview
            const imagePreview = document.getElementById('imagePreview');
            if (imagePreview) {
                imagePreview.classList.add('hidden');
            }
            
            // Animate modal entrance
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            const modalContent = document.getElementById('modalContent');
            
            // Animate modal exit
            modalContent.classList.remove('scale-100', 'opacity-100');
            modalContent.classList.add('scale-95', 'opacity-0');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        function editCategory(category) {
            const modal = document.getElementById('categoryModal');
            const modalContent = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            document.getElementById('modalTitle').textContent = '✏️ Edit Category';
            document.getElementById('action').value = 'edit';
            document.getElementById('category_id').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('description').value = category.description || '';
            document.getElementById('slug').value = category.slug || '';
            document.getElementById('parent_id').value = category.parent_id || '';
            document.getElementById('image_url').value = category.image_url || '';
            document.getElementById('status').value = category.status;
            
            // Show image preview if image exists
            if (category.image_url) {
                const imagePreview = document.getElementById('imagePreview');
                const previewImg = document.getElementById('previewImg');
                previewImg.src = category.image_url;
                imagePreview.classList.remove('hidden');
            } else {
                const imagePreview = document.getElementById('imagePreview');
                if (imagePreview) {
                    imagePreview.classList.add('hidden');
                }
            }
            
            // Animate modal entrance
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        }

        function deleteCategory(id, name, productCount) {
            if (productCount > 0) {
                alert(`Cannot delete "${name}" because it has ${productCount} product(s). Please move or delete the products first.`);
                return;
            }
            
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-generate slug from name
        document.getElementById('name').addEventListener('input', function() {
            const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
            document.getElementById('slug').value = slug;
        });

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

            // Simulate progress
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
                    document.getElementById('image_url').value = data.url;
                    showImagePreview(data.url);
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

        // Update editCategory function to show image preview
        const originalEditCategory = editCategory;
        editCategory = function(category) {
            originalEditCategory(category);
            if (category.image_url) {
                showImagePreview(category.image_url);
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
                closeCategoryModal();
            }
        });
    </script>
</body>
</html>
