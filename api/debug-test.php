<?php
// Simple debug test for Vercel deployment
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$response = [
    'status' => 'testing',
    'php_version' => phpversion(),
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => []
];

// Test 1: Basic PHP functionality
$response['tests']['basic_php'] = 'OK';

// Test 2: File paths
$data_path = __DIR__ . '/../data/';
$response['tests']['data_path'] = $data_path;
$response['tests']['data_path_exists'] = is_dir($data_path) ? 'YES' : 'NO';

// Test 3: Config files
$config_path = __DIR__ . '/../config/';
$response['tests']['config_path'] = $config_path;
$response['tests']['config_path_exists'] = is_dir($config_path) ? 'YES' : 'NO';

// Test 4: Specific config files
$db_auto_file = $config_path . 'database_auto.php';
$response['tests']['database_auto_exists'] = file_exists($db_auto_file) ? 'YES' : 'NO';

$json_db_file = $config_path . 'json_database.php';
$response['tests']['json_database_exists'] = file_exists($json_db_file) ? 'YES' : 'NO';

// Test 5: Try to include files step by step
try {
    if (file_exists($json_db_file)) {
        require_once $json_db_file;
        $response['tests']['json_database_included'] = 'OK';
        
        // Test JsonDatabase class
        if (class_exists('JsonDatabase')) {
            $response['tests']['json_database_class'] = 'EXISTS';
            
            // Try to create instance
            $jsonDb = new JsonDatabase($data_path);
            $response['tests']['json_database_instance'] = 'CREATED';
        } else {
            $response['tests']['json_database_class'] = 'NOT_FOUND';
        }
    }
} catch (Exception $e) {
    $response['tests']['json_database_error'] = $e->getMessage();
}

// Test 6: Try database_auto.php
try {
    if (file_exists($db_auto_file)) {
        require_once $db_auto_file;
        $response['tests']['database_auto_included'] = 'OK';
    }
} catch (Exception $e) {
    $response['tests']['database_auto_error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>