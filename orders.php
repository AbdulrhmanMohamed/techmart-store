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

// Get user's orders with order items
try {
    // Check if we're using MySQL or JSON database
    if (isset($pdo) && $pdo instanceof PDO) {
        // MySQL database
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.name) SEPARATOR ', ') as items_summary,
                   SUM(oi.quantity * oi.price) as calculated_total
            FROM orders o 
            LEFT JOIN order_items oi ON o.id = oi.order_id 
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE o.user_id = ? 
            GROUP BY o.id 
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $orders = $stmt->fetchAll();
        
        // Use calculated total if available, otherwise use stored total
        foreach ($orders as &$order) {
            $order['total_amount'] = $order['calculated_total'] ?? $order['total_amount'] ?? 0;
        }
    } else {
        // JSON database
        require_once 'config/json_database.php';
        $jsonDb = new JsonDatabase();
        
        // Get user's orders
        $orders = $jsonDb->select('orders', ['user_id' => $user_id]);
        
        // Sort by created_at DESC
        usort($orders, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        // Get order items and products for each order
        foreach ($orders as &$order) {
            $order_items = $jsonDb->select('order_items', ['order_id' => $order['id']]);
            $items_summary = [];
            
            foreach ($order_items as $item) {
                $product = $jsonDb->selectOne('products', ['id' => $item['product_id']]);
                if ($product) {
                    $items_summary[] = $item['quantity'] . 'x ' . $product['name'];
                }
            }
            
            $order['items_summary'] = !empty($items_summary) ? implode(', ', $items_summary) : 'No items';
            // total_amount is already stored in the order
        }
    }
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $orders = [];
}

// Get order details for a specific order
$order_details = null;
if (isset($_GET['order_id'])) {
    $order_id = (int)$_GET['order_id'];
    try {
        if (isset($pdo) && $pdo instanceof PDO) {
            // MySQL database
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
            $stmt->execute([$order_id, $user_id]);
            $order_details = $stmt->fetch();
            
            if ($order_details) {
                // Get order items
                $stmt = $pdo->prepare("
                    SELECT oi.*, p.name, p.image_url, p.short_description
                    FROM order_items oi 
                    LEFT JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order_id]);
                $order_items = $stmt->fetchAll();
            }
        } else {
            // JSON database
            if (!isset($jsonDb)) {
                require_once 'config/json_database.php';
                $jsonDb = new JsonDatabase();
            }
            
            // Get order info
            $order_details = $jsonDb->selectOne('orders', ['id' => $order_id, 'user_id' => $user_id]);
            
            if ($order_details) {
                // Get order items with product details
                $order_items_raw = $jsonDb->select('order_items', ['order_id' => $order_id]);
                $order_items = [];
                
                foreach ($order_items_raw as $item) {
                    $product = $jsonDb->selectOne('products', ['id' => $item['product_id']]);
                    if ($product) {
                        $order_items[] = array_merge($item, [
                            'name' => $product['name'],
                            'image_url' => $product['image_url'],
                            'short_description' => $product['short_description']
                        ]);
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching order details: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - TechMart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .order-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);
        }
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen">
        <!-- Page Header -->
        <section class="order-header text-white py-16">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto text-center">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">Order History</h1>
                    <p class="text-xl opacity-90">Track and manage your orders</p>
                </div>
            </div>
        </section>

        <!-- Orders Content -->
        <section class="py-16 bg-primary transition-colors duration-300" style="background-color: var(--bg-primary);">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <?php if (empty($orders)): ?>
                        <!-- No Orders -->
                        <div class="text-center py-16">
                            <div class="text-muted mb-4">
                                <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-semibold text-primary mb-4">No orders yet</h2>
                            <p class="text-secondary mb-8">Start shopping to see your orders here!</p>
                            <a href="products.php" class="btn-primary px-6 py-3">
                                Start Shopping
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Orders List -->
                        <div class="space-y-6">
                            <?php foreach ($orders as $order): ?>
                            <div class="card p-6">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-xl font-semibold text-primary mb-2">Order #<?php echo $order['id']; ?></h3>
                                        <p class="text-sm text-muted">Placed on <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="mt-2 md:mt-0 flex items-center gap-4">
                                        <span class="text-2xl font-bold text-primary">$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                                        <span class="badge badge-success"><?php echo ucfirst($order['status']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="border-t pt-4" style="border-color: var(--border-light);">
                                    <p class="text-sm text-secondary mb-4"><?php echo htmlspecialchars($order['items_summary'] ?? 'No items'); ?></p>
                                    
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <a href="orders.php?order_id=<?php echo $order['id']; ?>" class="btn-outline text-sm">
                                            View Details
                                        </a>
                                        <?php if ($order['status'] === 'pending'): ?>
                                        <button class="btn-secondary text-sm" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                            Cancel Order
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($order['status'] === 'delivered'): ?>
                                        <button class="btn-primary text-sm" onclick="reorder(<?php echo $order['id']; ?>)">
                                            Reorder
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Order Details Modal -->
        <?php if ($order_details): ?>
        <div id="order-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-primary rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto" style="background-color: var(--bg-primary);">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-primary">Order #<?php echo $order_details['id']; ?></h2>
                        <button onclick="closeOrderModal()" class="text-muted hover:text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="font-semibold text-primary mb-2">Order Information</h3>
                            <p class="text-sm text-secondary">Order Date: <?php echo date('M j, Y \a\t g:i A', strtotime($order_details['created_at'])); ?></p>
                            <p class="text-sm text-secondary">Status: <span class="badge badge-success"><?php echo ucfirst($order_details['status']); ?></span></p>
                            <p class="text-sm text-secondary">Total: $<?php echo number_format($order_details['total_amount'], 2); ?></p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-primary mb-2">Shipping Address</h3>
                            <p class="text-sm text-secondary"><?php echo htmlspecialchars($order_details['shipping_address']); ?></p>
                        </div>
                    </div>
                    
                    <div class="border-t pt-6" style="border-color: var(--border-light);">
                        <h3 class="font-semibold text-primary mb-4">Order Items</h3>
                        <div class="space-y-4">
                            <?php foreach ($order_items as $item): ?>
                            <div class="flex items-center gap-4 p-4 border rounded-lg" style="border-color: var(--border-light);">
                                <div class="w-16 h-16 bg-tertiary rounded-lg flex items-center justify-center">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="w-full h-full object-cover rounded-lg">
                                    <?php else: ?>
                                        <svg class="w-8 h-8 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-medium text-primary"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <p class="text-sm text-muted">Quantity: <?php echo $item['quantity']; ?></p>
                                    <p class="text-sm text-secondary">$<?php echo number_format($item['price'], 2); ?> each</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-primary">$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

        // Order Modal Functions
        function closeOrderModal() {
            document.getElementById('order-modal').style.display = 'none';
            // Remove order_id from URL
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                // Here you would typically make an AJAX request to cancel the order
                alert('Order cancellation feature coming soon!');
            }
        }

        function reorder(orderId) {
            if (confirm('Add all items from this order to your cart?')) {
                // Here you would typically make an AJAX request to reorder
                alert('Reorder feature coming soon!');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('order-modal');
            if (e.target === modal) {
                closeOrderModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOrderModal();
            }
        });
    </script>
</body>
</html>


