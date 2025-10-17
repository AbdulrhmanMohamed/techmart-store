<?php
// Vercel PHP Entry Point
// This file handles all incoming requests and routes them to the appropriate parts of the application

// Debug mode for API issues
if (isset($_GET['debug']) && $_GET['debug'] === 'api') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'debug_mode',
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not_set',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'not_set',
        'php_version' => phpversion(),
        'timestamp' => date('Y-m-d H:i:s'),
        'server_vars' => [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'not_set',
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'not_set',
            'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'not_set'
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

// Database test mode
if (isset($_GET['debug']) && $_GET['debug'] === 'db') {
    header('Content-Type: application/json');
    $response = ['status' => 'testing_database'];
    
    try {
        // Test file paths
        $config_path = __DIR__ . '/../config/database_auto.php';
        $response['config_path'] = $config_path;
        $response['config_exists'] = file_exists($config_path);
        
        if (file_exists($config_path)) {
            require_once $config_path;
            $response['config_loaded'] = true;
            
            // Test JsonDatabase
            if (class_exists('JsonDatabase')) {
                $response['json_db_class_exists'] = true;
                $jsonDb = new JsonDatabase(__DIR__ . '/../data/');
                $response['json_db_created'] = true;
                
                // Test a simple query
                $users = $jsonDb->select('users', [], 1);
                $response['test_query_success'] = true;
                $response['users_count'] = count($users);
            } else {
                $response['json_db_class_exists'] = false;
            }
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        $response['error_file'] = $e->getFile();
        $response['error_line'] = $e->getLine();
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Get the request URI and remove the /api prefix if present
$request_uri = $_GET['path'] ?? $_SERVER['REQUEST_URI'] ?? '/';
if (!isset($_GET['path'])) {
    $request_uri = parse_url($request_uri, PHP_URL_PATH);
    // Remove /api prefix if present
    if (strpos($request_uri, '/api') === 0) {
        $request_uri = substr($request_uri, 4);
    }
} else {
    // If path is provided via query parameter, use it directly
    $request_uri = '/' . ltrim($_GET['path'], '/');
}

// If empty or just /, return API info
if (empty($request_uri) || $request_uri === '/') {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'TechMart API',
        'version' => '1.0',
        'endpoints' => [
            '/api/cart.php',
            '/api/wishlist.php',
            '/api/process-payment.php',
            '/api/update-theme.php',
            '/api/migrate-cart.php'
        ]
    ]);
    exit;
}

// Handle API routes (existing API files)
if (strpos($request_uri, '/cart.php') === 0) {
    require __DIR__ . '/../includes/api-endpoints/cart.php';
    exit;
}

if (strpos($request_uri, '/migrate-cart.php') === 0) {
    require __DIR__ . '/../includes/api-endpoints/migrate-cart.php';
    exit;
}

if (strpos($request_uri, '/process-payment.php') === 0) {
    require __DIR__ . '/../includes/api-endpoints/process-payment.php';
    exit;
}

if (strpos($request_uri, '/update-theme.php') === 0) {
    require __DIR__ . '/../includes/api-endpoints/update-theme.php';
    exit;
}

if (strpos($request_uri, '/wishlist.php') === 0) {
    require __DIR__ . '/../includes/api-endpoints/wishlist.php';
    exit;
}

if (strpos($request_uri, '/debug-test.php') === 0) {
    require __DIR__ . '/../includes/api-endpoints/debug-test.php';
    exit;
}

// Handle admin routes
if (strpos($request_uri, '/admin') === 0) {
    $admin_file = substr($request_uri, 7); // Remove /admin
    if (empty($admin_file) || $admin_file === '/') {
        $admin_file = '/index.php';
    }
    
    $admin_path = __DIR__ . '/../admin' . $admin_file;
    if (file_exists($admin_path)) {
        // Set the correct working directory for admin files
        $original_cwd = getcwd();
        chdir(__DIR__ . '/../');
        
        // Capture output to handle any redirects or headers properly
        ob_start();
        include $admin_path;
        $output = ob_get_clean();
        
        // Restore original working directory
        chdir($original_cwd);
        
        echo $output;
        exit;
    }
}

// Handle main application routes
$main_file = ltrim($request_uri, '/');
if (empty($main_file)) {
    $main_file = 'index.php';
}

// Add .php extension if not present and file doesn't exist
if (!pathinfo($main_file, PATHINFO_EXTENSION) && !file_exists(__DIR__ . '/../' . $main_file)) {
    $main_file .= '.php';
}

$main_path = __DIR__ . '/../' . $main_file;

// Check if the file exists in the main directory
if (file_exists($main_path)) {
    // Set the correct working directory for main files
    $original_cwd = getcwd();
    chdir(__DIR__ . '/..');
    
    // Capture output to handle any redirects or headers properly
    ob_start();
    include $main_path;
    $output = ob_get_clean();
    
    // Restore original working directory
    chdir($original_cwd);
    
    echo $output;
    exit;
}

// If no file found, try index.php as fallback
$original_cwd = getcwd();
chdir(__DIR__ . '/..');

ob_start();
include __DIR__ . '/../index.php';
$output = ob_get_clean();

chdir($original_cwd);
echo $output;