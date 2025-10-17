<?php
// Testing JsonDatabase loading
header('Content-Type: application/json');

$result = ['status' => 'testing'];

try {
    // Test 1: Check if config file exists
    $configPath = __DIR__ . '/../config/json_database.php';
    $result['config_file_exists'] = file_exists($configPath);
    
    if ($result['config_file_exists']) {
        // Test 2: Try to require the file
        require_once $configPath;
        $result['config_loaded'] = true;
        
        // Test 3: Check if class exists
        $result['class_exists'] = class_exists('JsonDatabase');
        
        if ($result['class_exists']) {
            // Test 4: Try to instantiate
            $db = new JsonDatabase();
            $result['instance_created'] = true;
            
            // Test 5: Check data directory
            $dataPath = __DIR__ . '/../data/';
            $result['data_dir_exists'] = is_dir($dataPath);
            
            if ($result['data_dir_exists']) {
                // Test 6: List data files
                $files = scandir($dataPath);
                $result['data_files'] = array_filter($files, function($file) {
                    return $file !== '.' && $file !== '..';
                });
            }
        }
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
    $result['file'] = $e->getFile();
    $result['line'] = $e->getLine();
    echo json_encode($result);
} catch (Error $e) {
    $result['fatal_error'] = $e->getMessage();
    $result['file'] = $e->getFile();
    $result['line'] = $e->getLine();
    echo json_encode($result);
}
?>

