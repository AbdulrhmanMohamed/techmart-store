<?php
session_start();
require_once 'config/database_auto.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $user = authenticateUser($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Set flag to migrate cart on next page load
            $_SESSION['migrate_cart'] = true;
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<?php
// SEO Data for login page
$seo_data = [
    'title' => 'Sign In - Login to Your Account | TechMart',
    'description' => 'Sign in to your TechMart account to access your orders, wishlist, and personalized shopping experience.',
    'keywords' => 'login, sign in, account, user login, TechMart login',
    'image' => '/assets/images/og-login.jpg',
    'type' => 'website',
    'noindex' => true // Don't index login pages
];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <?php echo generateSEOTags($seo_data); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-md mx-auto">
                <div class="card p-8">
                    <div class="text-center mb-8">
                        <h1 class="text-3xl font-bold text-primary">Sign In</h1>
                        <p class="text-secondary mt-2">Welcome back! Please sign in to your account.</p>
                    </div>

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
                        <div>
                            <label for="username" class="block text-sm font-medium text-secondary mb-2">Username</label>
                            <input type="text" id="username" name="username" required 
                                   class="input w-full px-4 py-3"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-secondary mb-2">Password</label>
                            <input type="password" id="password" name="password" required 
                                   class="input w-full px-4 py-3">
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-medium text-primary focus:ring-primary">
                                <span class="ml-2 text-sm text-secondary">Remember me</span>
                            </label>
                            <a href="forgot-password.php" class="text-sm text-primary hover:underline">Forgot password?</a>
                        </div>

                        <button type="submit" class="btn-primary w-full py-3 px-4 font-semibold">
                            Sign In
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-gray-600 dark:text-gray-400">Don't have an account? 
                            <a href="register.php" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">Create one here</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>

        // Cart Functionality
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        function updateCartCount() {
            const cartCount = document.getElementById('cart-count');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            if (totalItems > 0) {
                cartCount.textContent = totalItems;
                cartCount.classList.remove('hidden');
            } else {
                cartCount.classList.add('hidden');
            }
        }
        
        updateCartCount();
    </script>
</body>
</html>
