<?php
// Suppress all error output to prevent HTML in JSON responses
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
require_once '../config/database_auto.php';

// Initialize database connection
if (!isset($pdo) && !isset($jsonDb)) {
    $jsonDb = new JsonDatabase(__DIR__ . '/../data/');
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        // Migrate cart from localStorage to database
        $data = json_decode(file_get_contents('php://input'), true);
        $localStorageCart = $data['cart'] ?? [];
        
        if (empty($localStorageCart)) {
            echo json_encode(['success' => true, 'message' => 'No items to migrate']);
            exit;
        }
        
        $migrated_items = 0;
        $errors = [];
        
        foreach ($localStorageCart as $item) {
            $product_id = (int)$item['id'];
            $quantity = (int)$item['quantity'];
            
            if (!$product_id || $quantity < 1) {
                continue;
            }
            
            // Check if product exists and is in stock
            $stmt = $pdo->prepare("SELECT id, name, price, sale_price, stock_quantity, in_stock FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            
            if (!$product) {
                $errors[] = "Product ID {$product_id} not found";
                continue;
            }
            
            if (!$product['in_stock']) {
                $errors[] = "Product '{$product['name']}' is out of stock";
                continue;
            }
            
            // Check if already in cart
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing_item = $stmt->fetch();
            
            if ($existing_item) {
                // Update quantity (add to existing)
                $new_quantity = $existing_item['quantity'] + $quantity;
                if ($product['stock_quantity'] > 0 && $new_quantity > $product['stock_quantity']) {
                    $new_quantity = $product['stock_quantity'];
                }
                
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$new_quantity, $user_id, $product_id]);
            } else {
                // Add new item to cart
                if ($product['stock_quantity'] > 0 && $quantity > $product['stock_quantity']) {
                    $quantity = $product['stock_quantity'];
                }
                
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }
            
            $migrated_items++;
        }
        
        $response = [
            'success' => true,
            'message' => "Migrated {$migrated_items} items to your cart",
            'migrated_items' => $migrated_items
        ];
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
        }
        
        echo json_encode($response);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Cart Migration API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>



