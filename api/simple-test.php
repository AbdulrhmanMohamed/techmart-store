<?php
// Minimal test endpoint
echo "OK - PHP is working";

// Check if data directory exists
$dataDir = __DIR__ . '/../data/';
if (is_dir($dataDir)) {
    echo " - Data directory exists";
} else {
    echo " - Data directory MISSING";
}

// List what's in the parent directory
$parentDir = __DIR__ . '/../';
$contents = scandir($parentDir);
echo " - Parent dir contents: " . implode(', ', array_filter($contents, function($item) {
    return $item !== '.' && $item !== '..';
}));
?>