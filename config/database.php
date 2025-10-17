<?php
// MySQL Database configuration
$host = 'localhost';
$dbname = 'phpstore';
$username = 'root';
$password = 'root';
$port = 3306;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
} catch (PDOException $e) {
    // Fallback to JSON database if MySQL is not available
    error_log("MySQL connection failed: " . $e->getMessage());
    error_log("Falling back to JSON database");
    
    require_once __DIR__ . '/json_database.php';
    
    // Create a wrapper class that mimics PDO methods for JSON database
    class JsonPDOWrapper {
        private $jsonDb;
        
        public function __construct($jsonDb) {
            $this->jsonDb = $jsonDb;
        }
        
        public function prepare($query) {
            return new JsonStatementWrapper($this->jsonDb, $query);
        }
        
        public function query($query) {
            return new JsonStatementWrapper($this->jsonDb, $query);
        }
        
        public function lastInsertId() {
            return $this->lastId ?? null;
        }
        
        public function setLastInsertId($id) {
            $this->lastId = $id;
        }
    }
    
    class JsonStatementWrapper {
         private $jsonDb;
         private $query;
         private $params = [];
         private $executedParams = [];
         private $dataPath;
         
         public function __construct($jsonDb, $query) {
             $this->jsonDb = $jsonDb;
             $this->query = $query;
             $this->dataPath = __DIR__ . '/../data/';
         }
         
         private function readJsonFile($filename) {
             $filepath = $this->dataPath . $filename . '.json';
             if (file_exists($filepath)) {
                 $content = file_get_contents($filepath);
                 return json_decode($content, true) ?: [];
             }
             return [];
         }
         
         private function ensureStringValue($value) {
             return ($value === null) ? '' : (string)$value;
         }
         
         public function bindParam($param, $value) {
             $this->params[$param] = $value;
             return true;
         }
         
         public function bindValue($param, $value) {
             $this->params[$param] = $value;
             return true;
         }
         
         public function execute($params = []) {
             if (!empty($params)) {
                 $this->params = array_merge($this->params, $params);
             }
             
             // Store the executed parameters for use in fetch methods
             $this->executedParams = $params;
             return true;
         }
         
         public function fetch() {
             // Handle COUNT queries
             if (strpos($this->query, 'COUNT') !== false) {
                 $count = $this->getCount();
                 return $count !== null ? ['total' => $count] : null;
             }
             
             // Handle SUM queries for revenue
             if (strpos($this->query, 'SUM') !== false && strpos($this->query, 'total_amount') !== false) {
                 $orders = $this->readJsonFile('orders');
                 $total = 0;
                 foreach ($orders as $order) {
                     if (isset($order['total_amount'])) {
                         // Check if query has payment_status filter
                         if (strpos($this->query, 'payment_status') !== false) {
                             // Only include paid orders
                             if (isset($order['payment_status']) && $order['payment_status'] === 'paid') {
                                 $total += floatval($order['total_amount']);
                             }
                         } else {
                             // Include all orders if no payment_status filter
                             $total += floatval($order['total_amount']);
                         }
                     }
                 }
                 return ['total' => $total];
             }
             
             return null;
         }
         
         private function getCount() {
             if (strpos($this->query, 'products') !== false) {
                 $products = $this->readJsonFile('products');
                 return count($products);
             } elseif (strpos($this->query, 'orders') !== false) {
                 $orders = $this->readJsonFile('orders');
                 return count($orders);
             } elseif (strpos($this->query, 'users') !== false) {
                 $users = $this->readJsonFile('users');
                 return count($users);
             } elseif (strpos($this->query, 'categories') !== false) {
                 $categories = $this->readJsonFile('categories');
                 return count($categories);
             }
             return 0;
         }
         
         public function fetchColumn($column = 0) {
             // Return count values for COUNT queries
             if (strpos($this->query, 'COUNT') !== false) {
                 return $this->getCount();
             }
             
             // Handle SUM queries for revenue
             if (strpos($this->query, 'SUM') !== false && strpos($this->query, 'total_amount') !== false) {
                 $orders = $this->readJsonFile('orders');
                 $total = 0;
                 foreach ($orders as $order) {
                     if (isset($order['total_amount'])) {
                         // Check if query has payment_status filter
                         if (strpos($this->query, 'payment_status') !== false) {
                             // Only include paid orders
                             if (isset($order['payment_status']) && $order['payment_status'] === 'paid') {
                                 $total += floatval($order['total_amount']);
                             }
                         } else {
                             // Include all orders if no payment_status filter
                             $total += floatval($order['total_amount']);
                         }
                     }
                 }
                 return $total;
             }
             
             return null;
         }
        
        public function fetchAll() {
             // Handle SELECT queries for listing data
             $query = strtolower($this->query);
             
             // Top selling products query with JOIN and SUM (check this first before general products query)
             if (strpos($query, 'total_sold') !== false && (strpos($query, 'coalesce') !== false || strpos($query, 'sum') !== false)) {
                 error_log("DEBUG: Top selling products query detected");
                 $products = $this->readJsonFile('products');
                 $orderItems = $this->readJsonFile('order_items');
                 $orders = $this->readJsonFile('orders');
                 error_log("DEBUG: Loaded " . count($products) . " products, " . count($orderItems) . " order items, " . count($orders) . " orders");
                 
                 // Create order lookup for paid orders only
                 $paidOrderIds = [];
                 foreach ($orders as $order) {
                     if (isset($order['payment_status']) && $order['payment_status'] === 'paid') {
                         $paidOrderIds[] = $order['id'];
                     }
                 }
                 
                 // Calculate total_sold for each product
                 foreach ($products as &$product) {
                     $totalSold = 0;
                     
                     // Find all order items for this product from paid orders
                     foreach ($orderItems as $item) {
                         if (isset($item['product_id']) && $item['product_id'] == $product['id'] && 
                             isset($item['order_id']) && in_array($item['order_id'], $paidOrderIds)) {
                             $totalSold += isset($item['quantity']) ? intval($item['quantity']) : 0;
                         }
                     }
                     
                     $product['total_sold'] = $totalSold;
                 }
                 
                 // Filter active products that have been sold (total_sold > 0)
                 $activeProducts = array_filter($products, function($product) {
                     return isset($product['status']) && $product['status'] === 'active' && 
                            isset($product['total_sold']) && $product['total_sold'] > 0;
                 });
                 
                 // Sort by total_sold DESC, then by name ASC
                 usort($activeProducts, function($a, $b) {
                     if ($a['total_sold'] == $b['total_sold']) {
                         return strcmp($a['name'] ?? '', $b['name'] ?? '');
                     }
                     return $b['total_sold'] - $a['total_sold'];
                 });
                 
                 // Apply limit
                 preg_match('/limit\s+(\d+)/i', $this->query, $matches);
                 $limit = isset($matches[1]) ? intval($matches[1]) : 5;
                 
                 return array_slice($activeProducts, 0, $limit);
             }
             
             // Products listing with JOIN
             if (strpos($query, 'select') !== false && strpos($query, 'products') !== false) {
                 $products = $this->readJsonFile('products');
                 
                 // Handle JOIN with categories and brands
                 if (strpos($query, 'join') !== false || strpos($query, 'category_name') !== false || strpos($query, 'brand_name') !== false) {
                     $categories = $this->readJsonFile('categories');
                     $brands = $this->readJsonFile('brands');
                     
                     // Create lookup maps
                     $categoryMap = [];
                     foreach ($categories as $category) {
                         $categoryMap[$category['id']] = $category;
                     }
                     
                     $brandMap = [];
                     foreach ($brands as $brand) {
                         $brandMap[$brand['id']] = $brand;
                     }
                     
                     // Add category_name and brand_name to products
                     foreach ($products as &$product) {
                         // Add category_name
                         if (isset($product['category_id']) && isset($categoryMap[$product['category_id']])) {
                             $product['category_name'] = $categoryMap[$product['category_id']]['name'];
                         } else {
                             $product['category_name'] = null;
                         }
                         
                         // Add brand_name
                         if (isset($product['brand_id']) && isset($brandMap[$product['brand_id']])) {
                             $product['brand_name'] = $brandMap[$product['brand_id']]['name'];
                         } else {
                             $product['brand_name'] = null;
                         }
                         
                         // Map stock to stock_quantity for compatibility
                         if (isset($product['stock'])) {
                             $product['stock_quantity'] = $product['stock'];
                         }
                     }
                 }
                 
                 return $this->applyPagination($products);
             }
            
            // Orders listing
             if (strpos($query, 'select') !== false && strpos($query, 'orders') !== false) {
                 $orders = $this->readJsonFile('orders');
                 
                 // Handle JOIN with users
                 if (strpos($query, 'users') !== false || strpos($query, 'join') !== false) {
                     $users = $this->readJsonFile('users');
                     $userMap = [];
                     foreach ($users as $user) {
                         $userMap[$user['id']] = $user;
                     }
                     
                     foreach ($orders as &$order) {
                         if (isset($order['user_id']) && isset($userMap[$order['user_id']])) {
                             $user = $userMap[$order['user_id']];
                             $order['username'] = $this->ensureStringValue($user['username'] ?? '');
                             $order['email'] = $this->ensureStringValue($user['email'] ?? '');
                             $order['first_name'] = $this->ensureStringValue($user['first_name'] ?? '');
                             $order['last_name'] = $this->ensureStringValue($user['last_name'] ?? '');
                         } else {
                             // Provide default values to prevent undefined key warnings
                             $order['username'] = '';
                             $order['email'] = '';
                             $order['first_name'] = '';
                             $order['last_name'] = '';
                         }
                     }
                 }
                 
                 return $this->applyPagination($orders);
             }
             
             // Handle order items query
             if (strpos($query, 'order_items') !== false) {
                 $orderItems = $this->readJsonFile('order_items');
                 $products = $this->readJsonFile('products');
                 
                 // Create product lookup map
                 $productMap = [];
                 foreach ($products as $product) {
                     $productMap[$product['id']] = $product;
                 }
                 
                 // Filter order items by order_id if specified in params or WHERE clause
                 $filteredItems = [];
                 $targetOrderId = null;
                 
                 // Check if we have a WHERE order_id = ? parameter
                 if (!empty($this->executedParams) && is_array($this->executedParams)) {
                     $targetOrderId = $this->executedParams[0] ?? null;
                 }
                 
                 foreach ($orderItems as $item) {
                     // Filter by order_id if specified
                     if ($targetOrderId !== null && isset($item['order_id']) && $item['order_id'] != $targetOrderId) {
                         continue;
                     }
                     
                     // Add product information
                     if (isset($item['product_id']) && isset($productMap[$item['product_id']])) {
                         $product = $productMap[$item['product_id']];
                         $item['product_name'] = $this->ensureStringValue($product['name'] ?? '');
                         $item['image_url'] = $this->ensureStringValue($product['image_url'] ?? '');
                     } else {
                         $item['product_name'] = $this->ensureStringValue('');
                         $item['image_url'] = $this->ensureStringValue('');
                     }
                     $filteredItems[] = $item;
                 }
                 
                 return $filteredItems;
             }
            
            // Users listing
            if (strpos($query, 'select') !== false && strpos($query, 'users') !== false) {
                $users = $this->readJsonFile('users');
                
                // If this is a complex query with JOIN and aggregation
                if (strpos($query, 'total_orders') !== false || strpos($query, 'total_spent') !== false) {
                    $orders = $this->readJsonFile('orders');
                    
                    // Calculate aggregated data for each user
                    foreach ($users as &$user) {
                        $userOrders = array_filter($orders, function($order) use ($user) {
                            return isset($order['user_id']) && $order['user_id'] == $user['id'];
                        });
                        
                        $user['total_orders'] = count($userOrders);
                        
                        $totalSpent = 0;
                        foreach ($userOrders as $order) {
                            if (isset($order['payment_status']) && $order['payment_status'] === 'paid' && isset($order['total_amount'])) {
                                $totalSpent += (float)$order['total_amount'];
                            }
                        }
                        $user['total_spent'] = $totalSpent;
                    }
                }
                
                return $this->applyPagination($users);
            }
            
            // Categories listing
            if (strpos($query, 'select') !== false && strpos($query, 'categories') !== false) {
                $categories = $this->readJsonFile('categories');
                
                // Handle JOIN with parent categories and product count
                $hasJoin = strpos($query, 'parent_name') !== false || strpos($query, 'product_count') !== false || strpos(strtolower($query), 'join') !== false;
                if ($hasJoin) {
                    $products = $this->readJsonFile('products');
                    
                    // Create category lookup map for parent names
                    $categoryMap = [];
                    foreach ($categories as $category) {
                        $categoryMap[$category['id']] = $category;
                    }
                    
                    // Add parent_name and product_count to each category
                    foreach ($categories as &$category) {
                        // Add parent_name
                        if (isset($category['parent_id']) && $category['parent_id'] && isset($categoryMap[$category['parent_id']])) {
                            $category['parent_name'] = $this->ensureStringValue($categoryMap[$category['parent_id']]['name']);
                        } else {
                            $category['parent_name'] = null;
                        }
                        
                        // Add product_count
                        $productCount = 0;
                        foreach ($products as $product) {
                            if (isset($product['category_id']) && $product['category_id'] == $category['id']) {
                                $productCount++;
                            }
                        }
                        $category['product_count'] = $productCount;
                        
                        // Ensure slug is not null to prevent warnings
                        if (!isset($category['slug']) || $category['slug'] === null) {
                            $category['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $category['name']));
                        }
                    }
                }
                
                return $this->applyPagination($categories);
            }
            

            
            // Low stock products query
            if (strpos($query, 'stock_quantity') !== false && strpos($query, '<=') !== false) {
                $products = $this->readJsonFile('products');
                
                // Filter products with low stock (<=5) and active status
                $lowStockProducts = array_filter($products, function($product) {
                    $stock = isset($product['stock']) ? intval($product['stock']) : 0;
                    $status = isset($product['status']) ? $product['status'] : '';
                    return $stock <= 5 && $status === 'active';
                });
                
                // Add stock_quantity field for compatibility
                foreach ($lowStockProducts as &$product) {
                    $product['stock_quantity'] = isset($product['stock']) ? $product['stock'] : 0;
                }
                
                // Sort by stock ascending
                usort($lowStockProducts, function($a, $b) {
                    return ($a['stock'] ?? 0) - ($b['stock'] ?? 0);
                });
                
                // Apply limit
                preg_match('/limit\s+(\d+)/i', $this->query, $matches);
                $limit = isset($matches[1]) ? intval($matches[1]) : 5;
                
                return array_slice($lowStockProducts, 0, $limit);
            }
            
            // Recent orders for dashboard
            if (strpos($query, 'order by') !== false && strpos($query, 'limit') !== false) {
                if (strpos($query, 'orders') !== false) {
                    $orders = $this->readJsonFile('orders');
                    // Sort by created_at or id descending
                    usort($orders, function($a, $b) {
                        $timeA = isset($a['created_at']) ? strtotime($a['created_at']) : $a['id'];
                        $timeB = isset($b['created_at']) ? strtotime($b['created_at']) : $b['id'];
                        return $timeB - $timeA;
                    });
                    
                    // Get limit from query
                    preg_match('/limit\s+(\d+)/i', $this->query, $matches);
                    $limit = isset($matches[1]) ? intval($matches[1]) : 5;
                    
                    return array_slice($orders, 0, $limit);
                }
            }
            
            return [];
        }
        
        private function applyPagination($data) {
            // Extract LIMIT and OFFSET from query if present
            $limit = null;
            $offset = 0;
            
            if (preg_match('/limit\s+(\d+)(?:\s+offset\s+(\d+))?/i', $this->query, $matches)) {
                $limit = intval($matches[1]);
                $offset = isset($matches[2]) ? intval($matches[2]) : 0;
            } elseif (preg_match('/limit\s+(\d+)\s*,\s*(\d+)/i', $this->query, $matches)) {
                $offset = intval($matches[1]);
                $limit = intval($matches[2]);
            }
            
            if ($limit !== null) {
                return array_slice($data, $offset, $limit);
            }
            
            return $data;
        }
        
        public function rowCount() {
            return 1;
        }
    }
    
    $pdo = new JsonPDOWrapper($jsonDb);
}
?>
