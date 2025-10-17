<?php
// Suppress all error output to prevent HTML in JSON responses
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database_auto.php';

// Initialize JSON database
if (!isset($jsonDb)) {
    $jsonDb = new JsonDatabase(__DIR__ . '/../data/');
}

header('Content-Type: application/json');

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
    error_log("Wishlist API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>




