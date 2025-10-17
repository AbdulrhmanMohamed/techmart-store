<?php
require_once __DIR__ . '/../config/json_database.php';

function getFeaturedProducts($jsonDb = null) {
    global $jsonDb;
    try {
        // Get first 8 products as featured (since we don't have featured field in JSON)
        $products = $jsonDb->select('products', [], 8);
        return $products;
    } catch (Exception $e) {
        error_log("Error fetching featured products: " . $e->getMessage());
        return [];
    }
}

function getProductById($id, $jsonDb = null) {
    global $jsonDb;
    try {
        $product = $jsonDb->selectOne('products', ['id' => (int)$id]);
        return $product ?: false;
    } catch (Exception $e) {
        error_log("Error fetching product: " . $e->getMessage());
        return false;
    }
}

function getAllProducts($jsonDb = null, $limit = null) {
    global $jsonDb;
    try {
        $products = $jsonDb->select('products', [], $limit);
        return $products;
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
        return [];
    }
}

function getProductsByCategory($categoryId, $limit = null) {
    global $jsonDb;
    try {
        $products = $jsonDb->select('products', ['category_id' => (int)$categoryId], $limit);
        return $products;
    } catch (Exception $e) {
        error_log("Error fetching products by category: " . $e->getMessage());
        return [];
    }
}

function getProductsByBrand($brandId, $limit = null) {
    global $jsonDb;
    try {
        $products = $jsonDb->select('products', ['brand_id' => (int)$brandId], $limit);
        return $products;
    } catch (Exception $e) {
        error_log("Error fetching products by brand: " . $e->getMessage());
        return [];
    }
}

function searchProducts($filters = [], $limit = null) {
    global $jsonDb;
    try {
        $products = $jsonDb->select('products');
        
        // Apply filters
        if (!empty($filters)) {
            $products = array_filter($products, function($product) use ($filters) {
                // Search term filter
                if (isset($filters['search']) && !empty($filters['search'])) {
                    $searchTerm = strtolower($filters['search']);
                    $productName = strtolower($product['name']);
                    $productDesc = strtolower($product['description'] ?? '');
                    if (strpos($productName, $searchTerm) === false && strpos($productDesc, $searchTerm) === false) {
                        return false;
                    }
                }
                
                // Category filter
                if (isset($filters['category_id']) && $filters['category_id'] > 0) {
                    if ($product['category_id'] != $filters['category_id']) {
                        return false;
                    }
                }
                
                // Brand filter
                if (isset($filters['brand_id']) && $filters['brand_id'] > 0) {
                    if ($product['brand_id'] != $filters['brand_id']) {
                        return false;
                    }
                }
                
                // Price filters
                if (isset($filters['min_price']) && $filters['min_price'] > 0) {
                    if ($product['price'] < $filters['min_price']) {
                        return false;
                    }
                }
                
                if (isset($filters['max_price']) && $filters['max_price'] > 0) {
                    if ($product['price'] > $filters['max_price']) {
                        return false;
                    }
                }
                
                return true;
            });
        }
        
        // Apply limit if specified
        if ($limit) {
            $products = array_slice($products, 0, $limit);
        }
        
        return array_values($products);
    } catch (Exception $e) {
        error_log("Error searching products: " . $e->getMessage());
        return [];
    }
}

function getAllCategories($jsonDb = null) {
    global $jsonDb;
    try {
        $categories = $jsonDb->select('categories');
        return $categories;
    } catch (Exception $e) {
        error_log("Error fetching categories: " . $e->getMessage());
        return [];
    }
}

function getAllBrands($jsonDb = null) {
    global $jsonDb;
    try {
        $brands = $jsonDb->select('brands');
        return $brands;
    } catch (Exception $e) {
        error_log("Error fetching brands: " . $e->getMessage());
        return [];
    }
}

function getCategoryById($id) {
    global $jsonDb;
    try {
        $category = $jsonDb->selectOne('categories', ['id' => (int)$id]);
        return $category ?: false;
    } catch (Exception $e) {
        error_log("Error fetching category: " . $e->getMessage());
        return false;
    }
}

function getBrandById($id, $jsonDb = null) {
    global $jsonDb;
    try {
        $brand = $jsonDb->selectOne('brands', ['id' => (int)$id]);
        return $brand ?: false;
    } catch (Exception $e) {
        error_log("Error fetching brand by ID: " . $e->getMessage());
        return false;
    }
}

function getUserByEmail($email, $jsonDb = null) {
    global $jsonDb;
    try {
        $user = $jsonDb->selectOne('users', ['email' => $email]);
        return $user ?: false;
    } catch (Exception $e) {
        error_log("Error fetching user by email: " . $e->getMessage());
        return false;
    }
}

function getUserById($id, $jsonDb = null) {
    global $jsonDb;
    try {
        $user = $jsonDb->selectOne('users', ['id' => (int)$id]);
        return $user ?: false;
    } catch (Exception $e) {
        error_log("Error fetching user by ID: " . $e->getMessage());
        return false;
    }
}

function createUser($userData, $jsonDb = null) {
    global $jsonDb;
    try {
        $userId = $jsonDb->insert('users', $userData);
        return $userId;
    } catch (Exception $e) {
        error_log("Error creating user: " . $e->getMessage());
        return false;
    }
}

function updateUser($userId, $userData, $jsonDb = null) {
    global $jsonDb;
    try {
        $result = $jsonDb->update('users', $userData, ['id' => (int)$userId]);
        return $result;
    } catch (Exception $e) {
        error_log("Error updating user: " . $e->getMessage());
        return false;
    }
}
?>
