<?php
require_once 'config/database_auto.php';

echo "Testing top selling products query...\n\n";

try {
    // Test the top selling products query
    $query = "
        SELECT p.*, COALESCE(SUM(oi.quantity), 0) as total_sold
        FROM products p
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
        WHERE p.status = 'active'
        GROUP BY p.id
        ORDER BY total_sold DESC, p.name ASC
        LIMIT 5
    ";
    
    echo "Query: " . trim($query) . "\n\n";
    echo "Query lowercase: " . strtolower($query) . "\n\n";
    echo "Contains 'total_sold': " . (strpos(strtolower($query), 'total_sold') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains 'coalesce': " . (strpos(strtolower($query), 'coalesce') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains 'sum': " . (strpos(strtolower($query), 'sum') !== false ? 'YES' : 'NO') . "\n\n";
    
    $stmt = $pdo->query($query);
    $top_products = $stmt->fetchAll();
    
    echo "Top selling products:\n";
    foreach ($top_products as $product) {
        $revenue = (isset($product['total_sold']) ? $product['total_sold'] : 0) * (isset($product['price']) ? $product['price'] : 0);
        echo "- {$product['name']}: {$product['total_sold']} sold, Revenue: $" . number_format($revenue, 2) . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>