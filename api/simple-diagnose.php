<?php
header('Content-Type: application/json');

$result = [
    'status' => 'working',
    'timestamp' => date('Y-m-d H:i:s'),
    'data_dir_check' => is_dir(__DIR__ . '/../data'),
    'files_check' => []
];

$dataFiles = ['users.json', 'products.json', 'cart.json', 'wishlists.json'];
foreach ($dataFiles as $file) {
    $filePath = __DIR__ . '/../data/' . $file;
    $result['files_check'][$file] = file_exists($filePath);
}

echo json_encode($result);
?>