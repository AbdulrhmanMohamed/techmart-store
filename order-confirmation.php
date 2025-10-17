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
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// Get order details
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        // MySQL database
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            header('Location: orders.php');
            exit;
        }
        
        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, p.name, p.image_url, p.price, p.sale_price
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    } else {
        // JSON database
        require_once 'config/json_database.php';
        $jsonDb = new JsonDatabase();
        
        // Get order info
        $order = $jsonDb->selectOne('orders', ['id' => $order_id, 'user_id' => $user_id]);
        
        if (!$order) {
            header('Location: orders.php');
            exit;
        }
        
        // Get order items with product details
        $order_items_raw = $jsonDb->select('order_items', ['order_id' => $order_id]);
        $order_items = [];
        
        foreach ($order_items_raw as $item) {
            $product = $jsonDb->selectOne('products', ['id' => $item['product_id']]);
            if ($product) {
                $order_items[] = array_merge($item, [
                    'name' => $product['name'],
                    'image_url' => $product['image_url'],
                    'price' => $product['price'],
                    'sale_price' => $product['sale_price']
                ]);
            }
        }
    }
} catch (Exception $e) {
    error_log("Error fetching order: " . $e->getMessage());
    header('Location: orders.php');
    exit;
}

// Set page title
$page_title = "Order Confirmation - TechMart";
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="Your order has been confirmed. Thank you for your purchase!">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon/favicon.svg">
    <link rel="apple-touch-icon" href="assets/images/favicon/favicon-192x192.png">
    <link rel="manifest" href="assets/images/favicon/site.webmanifest">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
        }
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main>
        <!-- Success Header -->
        <section class="bg-primary py-16 transition-colors duration-300" style="background-color: var(--bg-primary);">
            <div class="container mx-auto px-4">
                <div class="text-center">
                    <div class="w-20 h-20 bg-success rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-primary mb-4">Order Confirmed!</h1>
                    <p class="text-lg text-muted">Thank you for your purchase. Your order has been successfully placed.</p>
                </div>
            </div>
        </section>

        <!-- Order Details -->
        <section class="py-16 bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Order Information -->
                        <div class="card p-6">
                            <h2 class="text-2xl font-semibold text-primary mb-6">Order Information</h2>
                            <div class="space-y-4">
                                <div class="flex justify-between">
                                    <span class="text-muted">Order Number:</span>
                                    <span class="font-medium text-primary"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">Order Date:</span>
                                    <span class="font-medium text-primary"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">Status:</span>
                                    <span class="px-3 py-1 bg-warning/20 text-warning rounded-full text-sm font-medium">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">Payment Method:</span>
                                    <span class="font-medium text-primary"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted">Total Amount:</span>
                                    <span class="font-bold text-primary text-lg">$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="card p-6">
                            <h2 class="text-2xl font-semibold text-primary mb-6">Shipping Address</h2>
                            <div class="text-muted">
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div class="card p-6 mt-8">
                        <h2 class="text-2xl font-semibold text-primary mb-6">Order Items</h2>
                        <div class="space-y-4">
                            <?php foreach ($order_items as $item): ?>
                                <div class="flex items-center space-x-4 p-4 border rounded-lg" style="border-color: var(--border-light);">
                                    <div class="w-20 h-20 bg-tertiary rounded-lg flex items-center justify-center overflow-hidden">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <svg class="w-10 h-10 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="font-medium text-primary"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="text-sm text-muted">Quantity: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-primary">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></p>
                                        <p class="text-sm text-muted">$<?php echo number_format($item['price'], 2); ?> each</p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mt-8">
                        <a href="index.php" class="btn btn-outline flex-1 text-center py-3">
                            🏠 Go to Home
                        </a>
                        <a href="orders.php" class="btn btn-outline flex-1 text-center py-3">
                            📋 View All Orders
                        </a>
                        <a href="products.php" class="btn btn-primary flex-1 text-center py-3">
                            🛍️ Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>



