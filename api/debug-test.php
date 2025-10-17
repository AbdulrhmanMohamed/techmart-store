<?php
// Simple debug test for Vercel deployment
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Test basic functionality
    $response = [
        'status' => 'success',
        'message' => 'API is working',
        'php_version' => phpversion(),
        'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Test file access
    $data_path = __DIR__ . '/../data/';
    $response['data_path'] = $data_path;
    $response['data_path_exists'] = is_dir($data_path);
    
    if (is_dir($data_path)) {
        $response['data_files'] = scandir($data_path);
    }
    
    // Test database config
    if (file_exists(__DIR__ . '/../config/database_auto.php')) {
        $response['database_config_exists'] = true;
        require_once __DIR__ . '/../config/database_auto.php';
        $response['database_loaded'] = true;
    } else {
        $response['database_config_exists'] = false;
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}
?>