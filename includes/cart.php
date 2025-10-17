<?php
require_once __DIR__ . '/../config/json_database.php';

function addToCart($userId, $productId, $quantity = 1) {
    $jsonDb = $GLOBALS['jsonDb'];
    
    // Check if product exists and is in stock
    $product = $jsonDb->selectOne('products', ['id' => $productId]);
    if (!$product || $product['stock'] < $quantity) {
        return ['success' => false, 'message' => 'Product not available or insufficient stock'];
    }
    
    // Check if item already exists in cart
    $existingItem = $jsonDb->selectOne('cart', ['user_id' => $userId, 'product_id' => $productId]);
    
    if ($existingItem) {
        // Update quantity
        $newQuantity = $existingItem['quantity'] + $quantity;
        if ($newQuantity > $product['stock']) {
            return ['success' => false, 'message' => 'Not enough stock available'];
        }
        
        $jsonDb->update('cart', 
            ['quantity' => $newQuantity, 'updated_at' => date('Y-m-d H:i:s')], 
            ['user_id' => $userId, 'product_id' => $productId]
        );
    } else {
        // Add new item
        $cartData = [
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $jsonDb->insert('cart', $cartData);
    }
    
    return ['success' => true, 'message' => 'Item added to cart'];
}

function updateCartQuantity($userId, $productId, $quantity) {
    $jsonDb = $GLOBALS['jsonDb'];
    
    if ($quantity <= 0) {
        return removeFromCart($userId, $productId);
    }
    
    // Check stock
    $product = $jsonDb->selectOne('products', ['id' => $productId]);
    if (!$product || $product['stock'] < $quantity) {
        return ['success' => false, 'message' => 'Not enough stock available'];
    }
    
    $result = $jsonDb->update('cart', 
        ['quantity' => $quantity, 'updated_at' => date('Y-m-d H:i:s')], 
        ['user_id' => $userId, 'product_id' => $productId]
    );
    
    return ['success' => $result, 'message' => $result ? 'Cart updated' : 'Failed to update cart'];
}

function removeFromCart($userId, $productId) {
    $jsonDb = $GLOBALS['jsonDb'];
    
    $result = $jsonDb->delete('cart', ['user_id' => $userId, 'product_id' => $productId]);
    
    return ['success' => $result, 'message' => $result ? 'Item removed from cart' : 'Failed to remove item'];
}

function getCartItems($userId) {
    $jsonDb = $GLOBALS['jsonDb'];
    
    $cartItems = $jsonDb->select('cart', ['user_id' => $userId]);
    $items = [];
    
    foreach ($cartItems as $cartItem) {
        $product = $jsonDb->selectOne('products', ['id' => $cartItem['product_id']]);
        if ($product) {
            $items[] = [
                'id' => $product['id'], // For JavaScript compatibility
                'cart_id' => $cartItem['id'],
                'product_id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'sale_price' => $product['price'] * 0.9, // Demo sale price
                'image_url' => $product['image_url'],
                'quantity' => $cartItem['quantity'],
                'stock_quantity' => $product['stock'],
                'in_stock' => $product['stock'] > 0
            ];
        }
    }
    
    return $items;
}

function getCartTotal($userId) {
    $items = getCartItems($userId);
    $total = 0;
    
    foreach ($items as $item) {
        $price = isset($item['sale_price']) ? $item['sale_price'] : $item['price'];
        $total += $price * $item['quantity'];
    }
    
    return $total;
}

function getCartCount($userId) {
    $jsonDb = $GLOBALS['jsonDb'];
    
    $cartItems = $jsonDb->select('cart', ['user_id' => $userId]);
    $count = 0;
    
    foreach ($cartItems as $item) {
        $count += $item['quantity'];
    }
    
    return $count;
}

function clearCart($userId) {
    $jsonDb = $GLOBALS['jsonDb'];
    
    return $jsonDb->delete('cart', ['user_id' => $userId]);
}
?>