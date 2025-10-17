<?php
/**
 * Payment Processing API
 * Handles Stripe and PayPal payments in test mode
 */

// Suppress all PHP errors and warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config/database_auto.php';
require_once '../config/json_database.php';
require_once '../config/theme.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['payment_method']) || !isset($input['payment_data'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$payment_method = $input['payment_method'];
$payment_data = $input['payment_data'];

try {
    // Get cart items and calculate total
    $jsonDb = new JsonDatabase();
    $cart_data = $jsonDb->select('cart', ['user_id' => $user_id]);
    $cart_items = [];
    
    foreach ($cart_data as $cart_item) {
        $product = $jsonDb->selectOne('products', ['id' => $cart_item['product_id']]);
        if ($product) {
            $cart_items[] = array_merge($cart_item, [
                'name' => $product['name'],
                'price' => $product['price'],
                'sale_price' => $product['sale_price']
            ]);
        }
    }
    
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
        $subtotal += $price * $item['quantity'];
    }
    
    $shipping = $subtotal >= 50 ? 0 : 9.99;
    $tax = $subtotal * 0.08;
    $total = $subtotal + $shipping + $tax;
    
    // Process payment based on method
    $payment_result = null;
    
    switch ($payment_method) {
        case 'stripe':
            $payment_result = processStripePayment($payment_data, $total);
            break;
        case 'paypal':
            $payment_result = processPayPalPayment($payment_data, $total);
            break;
        case 'bank_transfer':
            $payment_result = processBankTransfer($payment_data, $total);
            break;
        default:
            throw new Exception('Invalid payment method');
    }
    
    if (!$payment_result['success']) {
        echo json_encode($payment_result);
        exit;
    }
    
    // Create order
    // Generate order number
    $order_number = 'ORD-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Insert order
    $order_data = [
        'user_id' => $user_id,
        'order_number' => $order_number,
        'total_amount' => $total,
        'status' => 'processing',
        'shipping_address' => $payment_data['shipping_address'],
        'billing_address' => $payment_data['billing_address'],
        'payment_method' => $payment_method,
        'payment_status' => 'paid',
        'payment_id' => $payment_result['payment_id'],
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
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'order_id' => $order_id,
        'order_number' => $order_number,
        'payment_id' => $payment_result['payment_id']
    ]);
    
} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()]);
}

/**
 * Process Stripe payment (test mode)
 */
function processStripePayment($payment_data, $amount) {
    // In test mode, we'll simulate Stripe payment
    // In production, you would use Stripe's API
    
    $stripe_test_key = 'sk_test_51234567890abcdef'; // Test key
    $stripe_publishable_key = 'pk_test_51234567890abcdef'; // Test publishable key
    
    // Simulate payment processing
    $payment_intent_id = 'pi_test_' . uniqid();
    
    // Simulate payment success (in test mode, always succeeds)
    if (isset($payment_data['stripe_token']) && !empty($payment_data['stripe_token'])) {
        return [
            'success' => true,
            'payment_id' => $payment_intent_id,
            'message' => 'Stripe payment processed successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Invalid Stripe token'
    ];
}

/**
 * Process PayPal payment (test mode)
 */
function processPayPalPayment($payment_data, $amount) {
    // In test mode, we'll simulate PayPal payment
    // In production, you would use PayPal's API
    
    $paypal_client_id = 'test_client_id';
    $paypal_client_secret = 'test_client_secret';
    
    // Simulate PayPal payment
    $paypal_payment_id = 'PAY-' . strtoupper(uniqid());
    
    // Simulate payment success (in test mode, always succeeds)
    if (isset($payment_data['paypal_order_id']) && !empty($payment_data['paypal_order_id'])) {
        return [
            'success' => true,
            'payment_id' => $paypal_payment_id,
            'message' => 'PayPal payment processed successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Invalid PayPal order ID'
    ];
}

/**
 * Process bank transfer payment
 */
function processBankTransfer($payment_data, $amount) {
    // Bank transfer is always successful (manual processing)
    $transfer_id = 'BT-' . strtoupper(uniqid());
    
    return [
        'success' => true,
        'payment_id' => $transfer_id,
        'message' => 'Bank transfer initiated successfully'
    ];
}
?>



