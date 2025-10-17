<?php
// Enable error logging for debugging production issues
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Start output buffering to catch any unexpected output
ob_start();

try {
    session_start();
    
    // Log basic info
    error_log("Debug API - Session ID: " . session_id());
    error_log("Debug API - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    error_log("Debug API - Current working directory: " . getcwd());
    error_log("Debug API - __DIR__: " . __DIR__);
    
    // Test file access
    $dataPath = __DIR__ . '/../data/';
    error_log("Debug API - Data path: " . $dataPath);
    error_log("Debug API - Data path exists: " . (is_dir($dataPath) ? 'yes' : 'no'));
    error_log("Debug API - Data path readable: " . (is_readable($dataPath) ? 'yes' : 'no'));
    
    // Test JsonDatabase inclusion
    require_once '../config/json_database.php';
    error_log("Debug API - JsonDatabase class included successfully");
    
    // Test JsonDatabase initialization
    $jsonDb = new JsonDatabase();
    error_log("Debug API - JsonDatabase initialized successfully");
    
    // Test reading a simple file
    $users = $jsonDb->select('users', [], 1);
    error_log("Debug API - Users query result count: " . count($users));
    
    // Clean any unexpected output and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
    echo json_encode([
        'success' => true,
        'message' => 'Debug endpoint working',
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'data_path' => $dataPath,
        'data_path_exists' => is_dir($dataPath),
        'data_path_readable' => is_readable($dataPath),
        'users_count' => count($users),
        'php_version' => PHP_VERSION,
        'current_dir' => getcwd()
    ]);
    
} catch (Exception $e) {
    // Clean output buffer and send error response
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Debug API - Exception: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    echo json_encode([
        'success' => false, 
        'message' => 'Exception: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
} catch (Error $e) {
    // Clean output buffer and send error response
    ob_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    error_log("Debug API - Fatal Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine());
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>