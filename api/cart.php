<?php
// Simplified cart.php for testing
header('Content-Type: application/json');

// Test if we can get this far
echo json_encode([
    'status' => 'cart endpoint working',
    'timestamp' => time(),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);
exit;
?>

