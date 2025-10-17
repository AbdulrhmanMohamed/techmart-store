<?php
header('Content-Type: application/json');

$dataPath = __DIR__ . '/../data/';
$files = ['users.json', 'products.json', 'cart.json', 'wishlists.json'];

$result = [
    'data_path' => $dataPath,
    'data_path_exists' => is_dir($dataPath),
    'files' => []
];

foreach ($files as $file) {
    $filePath = $dataPath . $file;
    $result['files'][$file] = [
        'exists' => file_exists($filePath),
        'readable' => is_readable($filePath),
        'size' => file_exists($filePath) ? filesize($filePath) : 0
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>