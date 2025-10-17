<?php
// Suppress all PHP errors and warnings to ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../config/json_database.php';
require_once '../includes/cart.php';

// Initialize JSON database and make it global
$GLOBALS['jsonDb'] = new JsonDatabase();
$jsonDb = $GLOBALS['jsonDb'];

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage your cart']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Add item to cart
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['product_id'])) {
                throw new Exception('Invalid JSON data or missing product_id');
            }
            
            $product_id = (int)$data['product_id'];
            $quantity = (int)($data['quantity'] ?? 1);
            
            if (!$product_id || $quantity < 1) {
                throw new Exception('Product ID and valid quantity are required');
            }
            
            $result = addToCart($user_id, $product_id, $quantity);
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            // Get product details for response
            $product = $jsonDb->selectOne('products', ['id' => $product_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => $result['message'],
                'product' => [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'sale_price' => $product['price'] * 0.9, // Demo sale price
                    'image_url' => $product['image_url'],
                    'quantity' => $quantity
                ]
            ]);
            break;
            
        case 'PUT':
            // Update cart item quantity
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['product_id']) || !isset($data['quantity'])) {
                throw new Exception('Invalid JSON data or missing required fields');
            }
            
            $product_id = (int)$data['product_id'];
            $quantity = (int)$data['quantity'];
            
            if (!$product_id || $quantity < 0) {
                throw new Exception('Product ID and valid quantity are required');
            }
            
            $result = updateCartQuantity($user_id, $product_id, $quantity);
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            echo json_encode(['success' => true, 'message' => $result['message']]);
            break;
            
        case 'DELETE':
            // Remove item from cart
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || !isset($data['product_id'])) {
                throw new Exception('Invalid JSON data or missing product_id');
            }
            
            $product_id = (int)$data['product_id'];
            
            if (!$product_id) {
                throw new Exception('Product ID is required');
            }
            
            $result = removeFromCart($user_id, $product_id);
            
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            
            echo json_encode(['success' => true, 'message' => $result['message']]);
            break;
            
        case 'GET':
            // Get user's cart
            $cart = getCartItems($user_id);
            $total_items = getCartCount($user_id);
            $total_price = getCartTotal($user_id);
            
            echo json_encode([
                'success' => true, 
                'cart' => $cart,
                'totals' => [
                    'items' => $total_items,
                    'price' => $total_price
                ]
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Cart API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

