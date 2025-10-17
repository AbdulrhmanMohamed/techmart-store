<?php
// Enable error logging for debugging production issues
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Start output buffering to catch any unexpected output
ob_start();

try {
    session_start();
    
    // Log session info for debugging
    error_log("Wishlist API - Session ID: " . session_id());
    error_log("Wishlist API - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    
    require_once '../config/json_database.php';

    // Initialize JSON database and make it global
    $GLOBALS['jsonDb'] = new JsonDatabase();
    $jsonDb = $GLOBALS['jsonDb'];
    
    error_log("Wishlist API - JsonDatabase initialized successfully");

    // Clean any unexpected output and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
} catch (Exception $e) {
    // Clean output buffer and send error response
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Wishlist API - Fatal error during initialization: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error during initialization', 'error' => $e->getMessage()]);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to manage your wishlist']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // Add item to wishlist
            $data = json_decode(file_get_contents('php://input'), true);
            $product_id = (int)$data['product_id'];
            
            if (!$product_id) {
                throw new Exception('Product ID is required');
            }
            
            // Check if product exists
            $product = $jsonDb->selectOne('products', ['id' => $product_id, 'status' => 'active']);
            
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Check if already in wishlist
            $existing_wishlist = $jsonDb->selectOne('wishlists', ['user_id' => $user_id, 'product_id' => $product_id]);
            
            if ($existing_wishlist) {
                echo json_encode(['success' => false, 'message' => 'Product already in wishlist']);
                exit;
            }
            
            // Add to wishlist
            $wishlist_data = [
                'user_id' => $user_id,
                'product_id' => $product_id,
                'created_at' => date('Y-m-d H:i:s')
            ];
            $jsonDb->insert('wishlists', $wishlist_data);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added to wishlist',
                'product' => [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'sale_price' => $product['sale_price'],
                    'image_url' => $product['image_url']
                ]
            ]);
            break;
            
        case 'DELETE':
            // Remove item from wishlist
            $data = json_decode(file_get_contents('php://input'), true);
            $product_id = (int)$data['product_id'];
            
            if (!$product_id) {
                throw new Exception('Product ID is required');
            }
            
            $deleted = $jsonDb->delete('wishlists', ['user_id' => $user_id, 'product_id' => $product_id]);
            
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'Product removed from wishlist']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Product not found in wishlist']);
            }
            break;
            
        case 'GET':
            // Get user's wishlist
            $wishlist_items = $jsonDb->select('wishlists', ['user_id' => $user_id]);
            $wishlist = [];
            
            foreach ($wishlist_items as $item) {
                $product = $jsonDb->selectOne('products', ['id' => $item['product_id'], 'status' => 'active']);
                if ($product) {
                    $wishlist[] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'sale_price' => $product['sale_price'],
                        'image_url' => $product['image_url'],
                        'rating' => $product['rating'] ?? 0,
                        'review_count' => $product['review_count'] ?? 0,
                        'created_at' => $item['created_at']
                    ];
                }
            }
            
            // Sort by created_at DESC
            usort($wishlist, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            echo json_encode(['success' => true, 'wishlist' => $wishlist]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    error_log("Wishlist API Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    error_log("Wishlist API Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'method' => $_SERVER['REQUEST_METHOD'],
            'user_id' => $user_id ?? 'not set'
        ]
    ]);
} catch (Error $e) {
    error_log("Wishlist API Fatal Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    error_log("Wishlist API Fatal Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal server error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'method' => $_SERVER['REQUEST_METHOD']
        ]
    ]);
}
?>




