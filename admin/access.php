<?php
session_start();
require_once '../config/database_auto.php';

// Simple admin access for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'admin' && $password === 'admin123') {
        // Create a test admin user
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user) {
                // Create admin user
                $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, first_name, last_name, email, password, is_admin) 
                    VALUES ('admin', 'Admin', 'User', 'admin@techmart.com', ?, 1)
                ");
                $stmt->execute([$hashed_password]);
            }
            
            // Set session
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'admin';
            $_SESSION['is_admin'] = true;
            
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Invalid credentials";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access - TechMart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../assets/css/theme.css">
</head>
<body class="bg-primary min-h-screen flex items-center justify-center">
    <div class="card max-w-md w-full mx-4">
        <div class="p-8">
            <h1 class="text-2xl font-bold text-primary text-center mb-6">Admin Access</h1>
            
            <?php if (isset($error)): ?>
                <div class="mb-4 p-3 bg-error/20 text-error rounded-lg">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Username</label>
                    <input type="text" name="username" value="admin" required 
                           class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-muted mb-2">Password</label>
                    <input type="password" name="password" value="admin123" required 
                           class="w-full px-3 py-2 border border-primary/20 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary/20">
                </div>
                
                <button type="submit" class="btn btn-primary w-full">Access Admin Dashboard</button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm text-muted">Default credentials: admin / admin123</p>
                <a href="../index.php" class="text-sm text-secondary hover:text-secondary/80">Back to Store</a>
            </div>
        </div>
    </div>
</body>
</html>



