<?php
header('Content-Type: application/json');

$diagnostics = [];

// Check if data directory exists
$dataDir = __DIR__ . '/../data';
$diagnostics['data_dir_exists'] = is_dir($dataDir);
$diagnostics['data_dir_path'] = $dataDir;
$diagnostics['data_dir_realpath'] = realpath($dataDir);

// Check if data files exist
$dataFiles = ['users.json', 'products.json', 'cart.json', 'wishlists.json'];
$diagnostics['data_files'] = [];
foreach ($dataFiles as $file) {
    $filePath = $dataDir . '/' . $file;
    $diagnostics['data_files'][$file] = [
        'exists' => file_exists($filePath),
        'readable' => is_readable($filePath),
        'path' => $filePath
    ];
}

// Check if config directory exists
$configDir = __DIR__ . '/../config';
$diagnostics['config_dir_exists'] = is_dir($configDir);
$diagnostics['config_dir_path'] = $configDir;

// Check if json_database.php exists
$jsonDbFile = $configDir . '/json_database.php';
$diagnostics['json_database_file'] = [
    'exists' => file_exists($jsonDbFile),
    'readable' => is_readable($jsonDbFile),
    'path' => $jsonDbFile
];

// Try to include the JsonDatabase class
try {
    require_once $jsonDbFile;
    $diagnostics['json_database_class_loaded'] = true;
    $diagnostics['json_database_class_exists'] = class_exists('JsonDatabase');
    
    // Try to instantiate JsonDatabase
    try {
        $db = new JsonDatabase();
        $diagnostics['json_database_instantiated'] = true;
    } catch (Exception $e) {
        $diagnostics['json_database_instantiated'] = false;
        $diagnostics['json_database_error'] = $e->getMessage();
    }
} catch (Exception $e) {
    $diagnostics['json_database_class_loaded'] = false;
    $diagnostics['json_database_load_error'] = $e->getMessage();
}

// List contents of parent directory
$parentDir = __DIR__ . '/..';
$diagnostics['parent_dir_contents'] = [];
if (is_dir($parentDir)) {
    $contents = scandir($parentDir);
    $diagnostics['parent_dir_contents'] = array_filter($contents, function($item) {
        return $item !== '.' && $item !== '..';
    });
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT);
?>