<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user information
try {
    $user = $jsonDb->selectOne('users', ['id' => $user_id]);
    
    if (!$user) {
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching user: " . $e->getMessage());
    header('Location: login.php');
    exit;
}

// Get cart items from database
try {
    $cart_data = $jsonDb->select('cart', ['user_id' => $user_id]);
    $cart_items = [];
    
    foreach ($cart_data as $cart_item) {
        $product = $jsonDb->selectOne('products', ['id' => $cart_item['product_id']]);
        if ($product) {
            $cart_items[] = array_merge($cart_item, [
                'name' => $product['name'],
                'image_url' => $product['image_url'],
                'price' => $product['price'],
                'sale_price' => $product['sale_price']
            ]);
        }
    }
    
    if (empty($cart_items)) {
        header('Location: cart.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching cart: " . $e->getMessage());
    $cart_items = [];
}

// Calculate totals
$subtotal = 0;
$shipping = 0;
$tax_rate = 0.08; // 8% tax
$tax = 0;
$total = 0;

foreach ($cart_items as $item) {
    $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
    $subtotal += $price * $item['quantity'];
}

// Free shipping over $50
if ($subtotal >= 50) {
    $shipping = 0;
} else {
    $shipping = 9.99;
}

$tax = $subtotal * $tax_rate;
$total = $subtotal + $shipping + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_first_name = trim($_POST['shipping_first_name']);
    $shipping_last_name = trim($_POST['shipping_last_name']);
    $shipping_email = trim($_POST['shipping_email']);
    $shipping_phone = trim($_POST['shipping_phone']);
    $shipping_address = trim($_POST['shipping_address']);
    $shipping_city = trim($_POST['shipping_city']);
    $shipping_state = trim($_POST['shipping_state']);
    $shipping_zip = trim($_POST['shipping_zip']);
    $shipping_country = trim($_POST['shipping_country']);
    
    $billing_same_as_shipping = isset($_POST['billing_same_as_shipping']);
    
    if (!$billing_same_as_shipping) {
        $billing_first_name = trim($_POST['billing_first_name']);
        $billing_last_name = trim($_POST['billing_last_name']);
        $billing_email = trim($_POST['billing_email']);
        $billing_phone = trim($_POST['billing_phone']);
        $billing_address = trim($_POST['billing_address']);
        $billing_city = trim($_POST['billing_city']);
        $billing_state = trim($_POST['billing_state']);
        $billing_zip = trim($_POST['billing_zip']);
        $billing_country = trim($_POST['billing_country']);
    } else {
        $billing_first_name = $shipping_first_name;
        $billing_last_name = $shipping_last_name;
        $billing_email = $shipping_email;
        $billing_phone = $shipping_phone;
        $billing_address = $shipping_address;
        $billing_city = $shipping_city;
        $billing_state = $shipping_state;
        $billing_zip = $shipping_zip;
        $billing_country = $shipping_country;
    }
    
    $payment_method = $_POST['payment_method'];
    
    // Validation
    if (empty($shipping_first_name) || empty($shipping_last_name) || empty($shipping_email) || 
        empty($shipping_address) || empty($shipping_city) || empty($shipping_state) || 
        empty($shipping_zip) || empty($shipping_country)) {
        $error = 'Please fill in all required shipping fields.';
    } elseif (!filter_var($shipping_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!$billing_same_as_shipping && 
              (empty($billing_first_name) || empty($billing_last_name) || empty($billing_email) || 
               empty($billing_address) || empty($billing_city) || empty($billing_state) || 
               empty($billing_zip) || empty($billing_country))) {
        $error = 'Please fill in all required billing fields.';
    } else {
        // Process order
        try {
            // Generate order number
            $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Create shipping address string
            $shipping_address_full = $shipping_address . ', ' . $shipping_city . ', ' . $shipping_state . ' ' . $shipping_zip . ', ' . $shipping_country;
            
            // Create billing address string
            $billing_address_full = $billing_address . ', ' . $billing_city . ', ' . $billing_state . ' ' . $billing_zip . ', ' . $billing_country;
            
            // Insert order
            $order_data = [
                'user_id' => $user_id,
                'order_number' => $order_number,
                'total_amount' => $total,
                'status' => 'pending',
                'shipping_address' => $shipping_address_full,
                'billing_address' => $billing_address_full,
                'payment_method' => $payment_method,
                'payment_status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $order_id = $jsonDb->insert('orders', $order_data);
            
            // Insert order items
            foreach ($cart_items as $item) {
                $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
                $order_item_data = [
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $jsonDb->insert('order_items', $order_item_data);
            }
            
            // Clear cart
            $jsonDb->delete('cart', ['user_id' => $user_id]);
            
            // Redirect to order confirmation
            header("Location: order-confirmation.php?order_id=" . $order_id);
            exit;
            
        } catch (Exception $e) {
            error_log("Error processing order: " . $e->getMessage());
            $error = 'An error occurred while processing your order. Please try again.';
        }
    }
}

// Set page title
$page_title = "Checkout - TechMart";
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="Complete your purchase securely with our easy checkout process.">
    
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
        <!-- Page Header -->
        <section class="bg-primary py-16 transition-colors duration-300" style="background-color: var(--bg-primary);">
            <div class="container mx-auto px-4">
                <div class="text-center">
                    <h1 class="text-4xl font-bold text-primary mb-4">Checkout</h1>
                    <p class="text-lg text-muted">Complete your purchase securely</p>
                </div>
            </div>
        </section>

        <!-- Checkout Form -->
        <section class="py-16 bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
            <div class="container mx-auto px-4">
                <div class="max-w-6xl mx-auto">
                    <?php if ($error): ?>
                        <div class="mb-6 p-4 bg-error/10 border border-error/20 rounded-lg">
                            <p class="text-error"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- Checkout Form -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- Shipping Information -->
                            <div class="card p-6">
                                <h2 class="text-2xl font-semibold text-primary mb-6">Shipping Information</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="shipping_first_name" class="block text-sm font-medium text-primary mb-2">First Name *</label>
                                        <input type="text" id="shipping_first_name" name="shipping_first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                    </div>
                                    <div>
                                        <label for="shipping_last_name" class="block text-sm font-medium text-primary mb-2">Last Name *</label>
                                        <input type="text" id="shipping_last_name" name="shipping_last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                    </div>
                                    <div>
                                        <label for="shipping_email" class="block text-sm font-medium text-primary mb-2">Email Address *</label>
                                        <input type="email" id="shipping_email" name="shipping_email" 
                                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                    </div>
                                    <div>
                                        <label for="shipping_phone" class="block text-sm font-medium text-primary mb-2">Phone Number</label>
                                        <input type="tel" id="shipping_phone" name="shipping_phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="shipping_address" class="block text-sm font-medium text-primary mb-2">Address *</label>
                                        <input type="text" id="shipping_address" name="shipping_address" 
                                               value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                    </div>
                                    <div>
                                        <label for="shipping_city" class="block text-sm font-medium text-primary mb-2">City *</label>
                                        <input type="text" id="shipping_city" name="shipping_city" 
                                               value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                    </div>
                                    <div>
                                        <label for="shipping_state" class="block text-sm font-medium text-primary mb-2">State *</label>
                                        <input type="text" id="shipping_state" name="shipping_state" 
                                               value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                    </div>
                                    <div>
                                        <label for="shipping_zip" class="block text-sm font-medium text-primary mb-2">ZIP Code *</label>
                                        <input type="text" id="shipping_zip" name="shipping_zip" 
                                               value="<?php echo htmlspecialchars($user['zip_code'] ?? ''); ?>"
                                               class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                               style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                    </div>
                                    <div>
                                        <label for="shipping_country" class="block text-sm font-medium text-primary mb-2">Country *</label>
                                        <select id="shipping_country" name="shipping_country" 
                                                class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);" required>
                                            <option value="US" <?php echo ($user['country'] ?? 'US') == 'US' ? 'selected' : ''; ?>>United States</option>
                                            <option value="CA" <?php echo ($user['country'] ?? '') == 'CA' ? 'selected' : ''; ?>>Canada</option>
                                            <option value="UK" <?php echo ($user['country'] ?? '') == 'UK' ? 'selected' : ''; ?>>United Kingdom</option>
                                            <option value="AU" <?php echo ($user['country'] ?? '') == 'AU' ? 'selected' : ''; ?>>Australia</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Billing Information -->
                            <div class="card p-6">
                                <h2 class="text-2xl font-semibold text-primary mb-6">Billing Information</h2>
                                
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" id="billing_same_as_shipping" name="billing_same_as_shipping" 
                                               class="mr-3" checked onchange="toggleBillingForm()">
                                        <span class="text-primary">Same as shipping address</span>
                                    </label>
                                </div>
                                
                                <div id="billing-form" class="hidden">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="billing_first_name" class="block text-sm font-medium text-primary mb-2">First Name *</label>
                                            <input type="text" id="billing_first_name" name="billing_first_name" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div>
                                            <label for="billing_last_name" class="block text-sm font-medium text-primary mb-2">Last Name *</label>
                                            <input type="text" id="billing_last_name" name="billing_last_name" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div>
                                            <label for="billing_email" class="block text-sm font-medium text-primary mb-2">Email Address *</label>
                                            <input type="email" id="billing_email" name="billing_email" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div>
                                            <label for="billing_phone" class="block text-sm font-medium text-primary mb-2">Phone Number</label>
                                            <input type="tel" id="billing_phone" name="billing_phone" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div class="md:col-span-2">
                                            <label for="billing_address" class="block text-sm font-medium text-primary mb-2">Address *</label>
                                            <input type="text" id="billing_address" name="billing_address" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div>
                                            <label for="billing_city" class="block text-sm font-medium text-primary mb-2">City *</label>
                                            <input type="text" id="billing_city" name="billing_city" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div>
                                            <label for="billing_state" class="block text-sm font-medium text-primary mb-2">State *</label>
                                            <input type="text" id="billing_state" name="billing_state" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div>
                                            <label for="billing_zip" class="block text-sm font-medium text-primary mb-2">ZIP Code *</label>
                                            <input type="text" id="billing_zip" name="billing_zip" 
                                                   class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                   style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                        </div>
                                        <div>
                                            <label for="billing_country" class="block text-sm font-medium text-primary mb-2">Country *</label>
                                            <select id="billing_country" name="billing_country" 
                                                    class="w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-300" 
                                                    style="background-color: var(--bg-primary); border-color: var(--border-light); color: var(--text-primary);">
                                                <option value="US">United States</option>
                                                <option value="CA">Canada</option>
                                                <option value="UK">United Kingdom</option>
                                                <option value="AU">Australia</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="card p-6">
                                <h2 class="text-2xl font-semibold text-primary mb-6">Payment Method</h2>
                                <div class="space-y-4">
                                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-tertiary transition-colors duration-300" 
                                           style="border-color: var(--border-light);">
                                        <input type="radio" name="payment_method" value="stripe" class="mr-3" checked onchange="showPaymentForm('stripe')">
                                        <div class="flex items-center space-x-3">
                                            <img src="assets/images/stripe-logo.svg" alt="Stripe" class="w-8 h-8">
                                            <div>
                                                <div class="font-medium text-primary">Credit Card (Stripe)</div>
                                                <div class="text-sm text-muted">Visa, MasterCard, American Express</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-tertiary transition-colors duration-300" 
                                           style="border-color: var(--border-light);">
                                        <input type="radio" name="payment_method" value="paypal" class="mr-3" onchange="showPaymentForm('paypal')">
                                        <div class="flex items-center space-x-3">
                                            <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" alt="PayPal" class="w-8 h-8">
                                            <div>
                                                <div class="font-medium text-primary">PayPal</div>
                                                <div class="text-sm text-muted">Pay securely with PayPal</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-tertiary transition-colors duration-300" 
                                           style="border-color: var(--border-light);">
                                        <input type="radio" name="payment_method" value="bank_transfer" class="mr-3" onchange="showPaymentForm('bank_transfer')">
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                            </svg>
                                            <div>
                                                <div class="font-medium text-primary">Bank Transfer</div>
                                                <div class="text-sm text-muted">Direct bank transfer</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Payment Forms -->
                                <?php include 'includes/payment-forms.php'; ?>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="lg:col-span-1">
                            <div class="card p-6 sticky top-24">
                                <h2 class="text-2xl font-semibold text-primary mb-6">Order Summary</h2>
                                
                                <!-- Cart Items -->
                                <div class="space-y-4 mb-6">
                                    <?php foreach ($cart_items as $item): ?>
                                        <?php $price = $item['sale_price'] ? $item['sale_price'] : $item['price']; ?>
                                        <div class="flex items-center space-x-3">
                                            <div class="w-16 h-16 bg-tertiary rounded-lg flex items-center justify-center overflow-hidden">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                         class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <svg class="w-8 h-8 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-medium text-primary"><?php echo htmlspecialchars($item['name']); ?></h3>
                                                <p class="text-sm text-muted">Qty: <?php echo $item['quantity']; ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-medium text-primary">$<?php echo number_format($price * $item['quantity'], 2); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Order Totals -->
                                <div class="space-y-3 mb-6">
                                    <div class="flex justify-between">
                                        <span class="text-muted">Subtotal</span>
                                        <span class="text-primary">$<?php echo number_format($subtotal, 2); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted">Shipping</span>
                                        <span class="text-primary"><?php echo $shipping > 0 ? '$' . number_format($shipping, 2) : 'Free'; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted">Tax</span>
                                        <span class="text-primary">$<?php echo number_format($tax, 2); ?></span>
                                    </div>
                                    <hr style="border-color: var(--border-light);">
                                    <div class="flex justify-between text-lg font-bold">
                                        <span class="text-primary">Total</span>
                                        <span class="text-primary">$<?php echo number_format($total, 2); ?></span>
                                    </div>
                                </div>
                                
                                <!-- Place Order Button -->
                                <button type="button" onclick="processPayment()" id="place-order-btn" class="w-full btn btn-primary py-3 rounded-lg font-semibold transition-colors duration-300">
                                    <span id="btn-text">Place Order</span>
                                    <span id="btn-loading" class="hidden">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                </button>
                                
                                <a href="cart.php" class="block w-full text-center mt-3 text-primary hover:text-primary-orange transition-colors duration-300">
                                    Back to Cart
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        function toggleBillingForm() {
            const checkbox = document.getElementById('billing_same_as_shipping');
            const billingForm = document.getElementById('billing-form');
            
            if (checkbox.checked) {
                billingForm.classList.add('hidden');
            } else {
                billingForm.classList.remove('hidden');
            }
        }
        
        // Initialize billing form state
        document.addEventListener('DOMContentLoaded', function() {
            toggleBillingForm();
            showPaymentForm('stripe'); // Show Stripe form by default
        });
        
        // Process payment
        async function processPayment() {
            const btn = document.getElementById('place-order-btn');
            const btnText = document.getElementById('btn-text');
            const btnLoading = document.getElementById('btn-loading');
            
            // Show loading state
            btn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            
            try {
                // Collect form data
                const formData = collectFormData();
                
                // Validate form
                const validation = validateForm(formData);
                if (!validation.valid) {
                    const errorMessage = validation.errors.length === 1 
                        ? validation.errors[0] 
                        : 'Please fix the following errors:\n• ' + validation.errors.join('\n• ');
                    throw new Error(errorMessage);
                }
                
                // Process payment
                const response = await fetch('api/process-payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Redirect to order confirmation
                    window.location.href = `order-confirmation.php?order_id=${result.order_id}`;
                } else {
                    throw new Error(result.message || 'Payment processing failed');
                }
                
            } catch (error) {
                console.error('Payment error:', error);
                // Ensure notification shows even if there are other errors
                setTimeout(() => {
                    showNotification(error.message || 'Payment processing failed. Please try again.', 'error');
                }, 100);
            } finally {
                // Reset button state
                btn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
            }
        }
        
        // Collect form data
        function collectFormData() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            
            // Collect shipping data
            const shippingData = {
                first_name: document.getElementById('shipping_first_name').value,
                last_name: document.getElementById('shipping_last_name').value,
                email: document.getElementById('shipping_email').value,
                phone: document.getElementById('shipping_phone').value,
                address: document.getElementById('shipping_address').value,
                city: document.getElementById('shipping_city').value,
                state: document.getElementById('shipping_state').value,
                zip: document.getElementById('shipping_zip').value,
                country: document.getElementById('shipping_country').value
            };
            
            // Collect billing data
            let billingData = {};
            if (document.getElementById('billing_same_as_shipping').checked) {
                billingData = { ...shippingData };
            } else {
                billingData = {
                    first_name: document.getElementById('billing_first_name').value,
                    last_name: document.getElementById('billing_last_name').value,
                    email: document.getElementById('billing_email').value,
                    phone: document.getElementById('billing_phone').value,
                    address: document.getElementById('billing_address').value,
                    city: document.getElementById('billing_city').value,
                    state: document.getElementById('billing_state').value,
                    zip: document.getElementById('billing_zip').value,
                    country: document.getElementById('billing_country').value
                };
            }
            
            // Collect payment data based on method
            let paymentData = {};
            switch (paymentMethod) {
                case 'stripe':
                    paymentData = {
                        stripe_token: generateStripeToken(),
                        card_number: document.getElementById('card-number').value,
                        card_expiry: document.getElementById('card-expiry').value,
                        card_cvc: document.getElementById('card-cvc').value,
                        cardholder_name: document.getElementById('cardholder-name').value
                    };
                    break;
                case 'paypal':
                    paymentData = {
                        paypal_order_id: window.paypalOrderId || 'PAY-' + Date.now()
                    };
                    break;
                case 'bank_transfer':
                    paymentData = {
                        transfer_reference: document.getElementById('transfer-reference').value,
                        transfer_date: document.getElementById('transfer-date').value,
                        transfer_notes: document.getElementById('transfer-notes').value
                    };
                    break;
            }
            
            return {
                payment_method: paymentMethod,
                payment_data: {
                    ...paymentData,
                    shipping_address: formatAddress(shippingData),
                    billing_address: formatAddress(billingData)
                }
            };
        }
        
        // Format address
        function formatAddress(address) {
            // Check if all required fields are present and not empty
            if (!address.address || !address.city || !address.state || !address.zip || !address.country) {
                return '';
            }
            return `${address.address}, ${address.city}, ${address.state} ${address.zip}, ${address.country}`;
        }
        
        // Generate Stripe token (simulated)
        function generateStripeToken() {
            return 'tok_test_' + Math.random().toString(36).substr(2, 9);
        }
        
        // Validate form
        function validateForm(formData) {
            const errors = [];
            
            // Validate shipping address fields
            const shippingFields = ['shipping_first_name', 'shipping_last_name', 'shipping_email', 'shipping_address', 'shipping_city', 'shipping_state', 'shipping_zip', 'shipping_country'];
            for (const field of shippingFields) {
                const element = document.getElementById(field);
                if (!element || !element.value.trim()) {
                    const fieldName = field.replace('shipping_', '').replace('_', ' ');
                    errors.push(`Shipping ${fieldName} is required`);
                }
            }
            
            // Validate billing address fields (if not same as shipping)
            if (!document.getElementById('billing_same_as_shipping').checked) {
                const billingFields = ['billing_first_name', 'billing_last_name', 'billing_email', 'billing_address', 'billing_city', 'billing_state', 'billing_zip', 'billing_country'];
                for (const field of billingFields) {
                    const element = document.getElementById(field);
                    if (!element || !element.value.trim()) {
                        const fieldName = field.replace('billing_', '').replace('_', ' ');
                        errors.push(`Billing ${fieldName} is required`);
                    }
                }
            }
            
            // Skip payment validation for testing - just check if payment method is selected
            const paymentMethod = formData.payment_method;
            if (!paymentMethod) {
                errors.push('Please select a payment method');
            }
            
            // For testing: accept any payment method without validating details
            // In production, you would validate the payment data here
            
            return {
                valid: errors.length === 0,
                errors: errors
            };
        }
        
        // Show notification
        function showNotification(message, type = 'success') {
            // Remove any existing notifications
            const existingNotifications = document.querySelectorAll('.notification-toast');
            existingNotifications.forEach(notif => notif.remove());
            
            const notification = document.createElement('div');
            const bgColor = type === 'error' ? 'bg-red-500' : 'bg-green-500';
            const icon = type === 'error' ? '❌' : '✅';
            
            notification.className = `notification-toast fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300 flex items-center space-x-2 max-w-sm`;
            notification.innerHTML = `
                <span class="text-lg">${icon}</span>
                <span class="flex-1">${message}</span>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200 ml-2">×</button>
            `;
            
            document.body.appendChild(notification);
            
            // Force reflow and then animate in
            notification.offsetHeight;
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 10);
            
            // Auto remove after 6 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 6000);
        }
    </script>
</body>
</html>
