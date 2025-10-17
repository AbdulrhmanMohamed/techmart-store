<?php
session_start();
require_once '../config/database_auto.php';
require_once '../includes/cart.php';

// Initialize JSON database and make it global
$GLOBALS['jsonDb'] = new JsonDatabase();
$jsonDb = $GLOBALS['jsonDb'];

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
            
            // Check if product exists and is active
            $product = $jsonDb->selectOne('products', ['id' => $product_id, 'status' => 'active']);
            
            if (!$product) {
                $errors[] = "Product ID {$product_id} not found";
                continue;
            }
            
            // Use cart functions to add items (handles existing items automatically)
            $result = addToCart($user_id, $product_id, $quantity);
            
            if ($result['success']) {
                $migrated_items++;
            } else {
                $errors[] = $result['message'];
            }
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



