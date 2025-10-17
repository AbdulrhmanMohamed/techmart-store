<?php
session_start();
require_once 'config/database_auto.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';
require_once 'includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username can only contain letters, numbers, and underscores.';
    } else {
        $userData = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $result = registerUser($userData);
        
        if ($result['success']) {
            $success = 'Account created successfully! You can now sign in.';
            header('Location: login.php?success=' . urlencode($success));
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<?php
// SEO Data for register page
$seo_data = [
    'title' => 'Create Account - Join TechMart | Free Registration',
    'description' => 'Create your free TechMart account to enjoy personalized shopping, order tracking, wishlist, and exclusive deals.',
    'keywords' => 'register, create account, sign up, free registration, TechMart account, join',
    'image' => '/assets/images/og-register.jpg',
    'type' => 'website',
    'noindex' => true // Don't index registration pages
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
                        <h1 class="text-3xl font-bold text-primary">Create Account</h1>
                        <p class="text-secondary mt-2">Join TechMart and start shopping today!</p>
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
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-secondary mb-2">First Name *</label>
                                <input type="text" id="first_name" name="first_name" required 
                                       class="input w-full px-4 py-3"
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                            </div>
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-secondary mb-2">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required 
                                       class="input w-full px-4 py-3"
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                            </div>
                        </div>

                        <div>
                            <label for="username" class="block text-sm font-medium text-secondary mb-2">Username *</label>
                            <input type="text" id="username" name="username" required 
                                   class="input w-full px-4 py-3"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            <p class="text-sm text-muted mt-1">3-50 characters, letters, numbers, and underscores only</p>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-secondary mb-2">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   class="input w-full px-4 py-3"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-secondary mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   class="input w-full px-4 py-3"
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-secondary mb-2">Password *</label>
                            <input type="password" id="password" name="password" required 
                                   class="input w-full px-4 py-3">
                            <p class="text-sm text-muted mt-1">Must be at least 6 characters long</p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-secondary mb-2">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="input w-full px-4 py-3">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="terms" required 
                                   class="rounded border-medium text-primary focus:ring-primary">
                            <label for="terms" class="ml-2 text-sm text-secondary">
                                I agree to the <a href="terms.php" class="text-primary hover:underline">Terms of Service</a> and <a href="privacy.php" class="text-primary hover:underline">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn-primary w-full py-3 px-4 font-semibold">
                            Create Account
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-secondary">Already have an account? 
                            <a href="login.php" class="text-primary hover:underline font-medium">Sign in here</a>
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
