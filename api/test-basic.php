<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Basic PHP execution works',
    'php_version' => phpversion(),
    'current_directory' => getcwd(),
    'files_in_current_dir' => scandir('.'),
    'parent_dir_files' => scandir('..'),
    'data_dir_exists' => is_dir('../data'),
    'config_dir_exists' => is_dir('../config')
]);
?>