<?php
// Enable comprehensive error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Start output buffering to ensure clean JSON responses
ob_start();

try {
    // Log initialization
    error_log("Cart API: Starting initialization");
    
    // Start session
    session_start();
    error_log("Cart API: Session started, ID: " . session_id());
    
    // Initialize database
    require_once __DIR__ . '/../config/json_database.php';
    error_log("Cart API: JsonDatabase class loaded");
    
    $db = new JsonDatabase();
    error_log("Cart API: JsonDatabase instance created");
    
} catch (Exception $e) {
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Log the error with full details
    error_log("Cart API Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Cart API Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    exit;
} catch (Error $e) {
    // Handle PHP fatal errors
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Cart API Fatal Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Cart API Fatal Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Fatal error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

try {
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    error_log("Cart API: Processing $method request");

    if ($method === 'GET') {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Please log in to view your cart']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        error_log("Cart API: Getting cart for user $userId");

        // Get user's cart
        $cartItems = $db->select('cart', ['user_id' => $userId]);
        error_log("Cart API: Found " . count($cartItems) . " cart items");

        // Get product details for each cart item
        $cartWithProducts = [];
        foreach ($cartItems as $cartItem) {
            $product = $db->select('products', ['id' => $cartItem['product_id']]);
            if (!empty($product)) {
                $productData = $product[0];
                $cartWithProducts[] = [
                    'id' => $cartItem['id'],
                    'product_id' => $cartItem['product_id'],
                    'quantity' => $cartItem['quantity'],
                    'product' => $productData
                ];
            }
        }

        echo json_encode($cartWithProducts);

    } elseif ($method === 'POST') {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Please log in to manage your cart']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['product_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            exit;
        }

        $productId = $input['product_id'];
        $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 1;

        // Check if product exists
        $product = $db->select('products', ['id' => $productId]);
        if (empty($product)) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found']);
            exit;
        }

        // Check if item already exists in cart
        $existingItem = $db->select('cart', ['user_id' => $userId, 'product_id' => $productId]);

        if (!empty($existingItem)) {
            // Update quantity
            $newQuantity = $existingItem[0]['quantity'] + $quantity;
            $db->update('cart', ['quantity' => $newQuantity], ['id' => $existingItem[0]['id']]);
        } else {
            // Add new item
            $db->insert('cart', [
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Item added to cart']);

    } elseif ($method === 'DELETE') {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Please log in to manage your cart']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['product_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Product ID is required']);
            exit;
        }

        $productId = $input['product_id'];

        // Remove item from cart
        $db->delete('cart', ['user_id' => $userId, 'product_id' => $productId]);

        echo json_encode(['success' => true, 'message' => 'Item removed from cart']);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Log the error with full details
    error_log("Cart API Exception in main logic: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Cart API Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} catch (Error $e) {
    // Handle PHP fatal errors in main logic
    if (ob_get_level()) {
        ob_clean();
    }
    
    error_log("Cart API Fatal Error in main logic: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    error_log("Cart API Fatal Stack trace: " . $e->getTraceAsString());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Fatal error',
        'message' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>

