<?php
echo "Debug: Reading categories.json directly...\n\n";

$categoriesFile = 'data/categories.json';
if (file_exists($categoriesFile)) {
    $content = file_get_contents($categoriesFile);
    $categories = json_decode($content, true);
    
    echo "Found " . count($categories) . " categories in JSON file:\n\n";
    
    foreach ($categories as $category) {
        echo "Category: " . $category['name'] . "\n";
        echo "  ID: " . $category['id'] . "\n";
        echo "  Slug: " . ($category['slug'] ?? 'null') . "\n";
        echo "  Parent ID: " . ($category['parent_id'] ?? 'null') . "\n";
        echo "---\n";
    }
} else {
    echo "Categories file not found!\n";
}

echo "\nNow testing through database connection...\n\n";

require_once 'config/database_auto.php';

try {
    $sql = "SELECT * FROM categories ORDER BY name ASC LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll();
    
    echo "Found " . count($categories) . " categories through database:\n\n";
    
    foreach ($categories as $category) {
        echo "Category: " . $category['name'] . "\n";
        echo "  ID: " . $category['id'] . "\n";
        echo "  Slug: " . ($category['slug'] ?? 'null') . "\n";
        echo "---\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>