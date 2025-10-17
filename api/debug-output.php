<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

try {
    echo "Starting PHP execution...\n";
    
    // Test basic output
    echo "Basic output works\n";
    
    // Test JSON output
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'PHP execution successful',
        'php_version' => phpversion(),
        'working_directory' => getcwd()
    ]);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage();
}

// Flush output
ob_end_flush();
?>