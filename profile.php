<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header('Location: login.php');
    exit;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_profile') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error = 'Please fill in all required fields.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $phone, $user_id]);
                
                // Update session
                $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                $_SESSION['user_email'] = $email;
                
                $success = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } catch (PDOException $e) {
                $error = 'An error occurred while updating your profile.';
            }
        }
    } elseif ($_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill in all password fields.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success = 'Password changed successfully!';
            } catch (PDOException $e) {
                $error = 'An error occurred while changing your password.';
            }
        }
    }
}

// Get user's recent orders
try {
    $stmt = $pdo->prepare("
        SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.quantity * oi.price) as total_amount
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $recent_orders = [];
}

// Get user's wishlist count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as wishlist_count FROM wishlists WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $wishlist_data = $stmt->fetch();
    $wishlist_count = $wishlist_data['wishlist_count'];
} catch (PDOException $e) {
    $wishlist_count = 0;
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TechMart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .profile-section {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);
        }
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen">
        <!-- Profile Header -->
        <section class="profile-section text-white py-16">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="flex flex-col md:flex-row items-center gap-8">
                        <!-- Profile Avatar -->
                        <div class="flex-shrink-0">
                            <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-3xl font-bold">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        
                        <!-- Profile Info -->
                        <div class="flex-1 text-center md:text-left">
                            <h1 class="text-3xl md:text-4xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                            <p class="text-xl opacity-90 mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                            <div class="flex flex-wrap justify-center md:justify-start gap-6 text-sm">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                                    </svg>
                                    <span>Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                    <span><?php echo $wishlist_count; ?> items in wishlist</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Profile Content -->
        <section class="py-16 bg-primary transition-colors duration-300" style="background-color: var(--bg-primary);">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Sidebar -->
                        <div class="lg:col-span-1">
                            <div class="card p-6 sticky top-24">
                                <h3 class="text-lg font-semibold text-primary mb-4">Account Menu</h3>
                                <nav class="space-y-2">
                                    <a href="#profile-info" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary transition-colors duration-300">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Profile Information
                                    </a>
                                    <a href="#change-password" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary transition-colors duration-300">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                        </svg>
                                        Change Password
                                    </a>
                                    <a href="orders.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary transition-colors duration-300">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        Order History
                                    </a>
                                    <a href="wishlist.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary transition-colors duration-300">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                        My Wishlist
                                    </a>
                                    <a href="logout.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary transition-colors duration-300 text-error">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Sign Out
                                    </a>
                                </nav>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Profile Information -->
                            <div id="profile-info" class="card p-6">
                                <h2 class="text-2xl font-bold text-primary mb-6">Profile Information</h2>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-error">
                                        <?php echo htmlspecialchars($error); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <?php echo htmlspecialchars($success); ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="space-y-6">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="first_name" class="block text-sm font-medium text-secondary mb-2">First Name *</label>
                                            <input type="text" id="first_name" name="first_name" required 
                                                   class="input w-full px-4 py-3"
                                                   value="<?php echo htmlspecialchars($user['first_name']); ?>">
                                        </div>
                                        <div>
                                            <label for="last_name" class="block text-sm font-medium text-secondary mb-2">Last Name *</label>
                                            <input type="text" id="last_name" name="last_name" required 
                                                   class="input w-full px-4 py-3"
                                                   value="<?php echo htmlspecialchars($user['last_name']); ?>">
                                        </div>
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium text-secondary mb-2">Email Address *</label>
                                        <input type="email" id="email" name="email" required 
                                               class="input w-full px-4 py-3"
                                               value="<?php echo htmlspecialchars($user['email']); ?>">
                                    </div>

                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-secondary mb-2">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" 
                                               class="input w-full px-4 py-3"
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="btn-primary px-6 py-3">
                                            Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Change Password -->
                            <div id="change-password" class="card p-6">
                                <h2 class="text-2xl font-bold text-primary mb-6">Change Password</h2>
                                
                                <form method="POST" class="space-y-6">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div>
                                        <label for="current_password" class="block text-sm font-medium text-secondary mb-2">Current Password *</label>
                                        <input type="password" id="current_password" name="current_password" required 
                                               class="input w-full px-4 py-3">
                                    </div>

                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-secondary mb-2">New Password *</label>
                                        <input type="password" id="new_password" name="new_password" required 
                                               class="input w-full px-4 py-3">
                                        <p class="text-sm text-muted mt-1">Must be at least 6 characters long</p>
                                    </div>

                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-secondary mb-2">Confirm New Password *</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required 
                                               class="input w-full px-4 py-3">
                                    </div>

                                    <div class="flex justify-end">
                                        <button type="submit" class="btn-secondary px-6 py-3">
                                            Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Recent Orders -->
                            <?php if (!empty($recent_orders)): ?>
                            <div class="card p-6">
                                <h2 class="text-2xl font-bold text-primary mb-6">Recent Orders</h2>
                                
                                <div class="space-y-4">
                                    <?php foreach ($recent_orders as $order): ?>
                                    <div class="border rounded-lg p-4" style="border-color: var(--border-light);">
                                        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-primary">Order #<?php echo $order['id']; ?></h3>
                                                <p class="text-sm text-muted">Placed on <?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                                <p class="text-sm text-secondary"><?php echo ($order['item_count'] ?? 0); ?> item(s) - $<?php echo number_format($order['total_amount'] ?? 0, 2); ?></p>
                                            </div>
                                            <div class="mt-2 md:mt-0">
                                                <span class="badge badge-success"><?php echo ucfirst($order['status']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-6 text-center">
                                    <a href="orders.php" class="btn-outline">
                                        View All Orders
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        
        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        html.classList.toggle('dark', currentTheme === 'dark');
        
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            const newTheme = html.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('input[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('border-red-500');
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>


