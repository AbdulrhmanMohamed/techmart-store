<?php
require_once 'config/database_auto.php';

echo "Testing categories query...\n\n";

try {
    // Test the same query used in categories.php
    $sql = "
        SELECT c.*, 
               p.name as parent_name,
               COUNT(pr.id) as product_count
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.id 
        LEFT JOIN products pr ON c.id = pr.category_id 
        GROUP BY c.id
        ORDER BY c.name ASC 
        LIMIT 10
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echo "Found " . count($categories) . " categories:\n\n";
    
    foreach ($categories as $category) {
        echo "Category: " . $category['name'] . "\n";
        echo "  ID: " . $category['id'] . "\n";
        echo "  Slug: " . ($category['slug'] ?? 'null') . "\n";
        echo "  Parent Name: " . ($category['parent_name'] ?? 'null') . "\n";
        echo "  Product Count: " . ($category['product_count'] ?? 'null') . "\n";
        echo "  Status: " . $category['status'] . "\n";
        echo "  Created: " . $category['created_at'] . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nCategories test completed!\n";
?>