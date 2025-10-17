<?php
// Vercel PHP Entry Point
// This file handles all incoming requests and routes them to the appropriate parts of the application

// Get the request URI and remove the /api prefix if present
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$request_uri = parse_url($request_uri, PHP_URL_PATH);

// Remove /api prefix if present
if (strpos($request_uri, '/api') === 0) {
    $request_uri = substr($request_uri, 4);
}

// If empty, default to home
if (empty($request_uri) || $request_uri === '/') {
    $request_uri = '/index.php';
}

// Handle API routes (existing API files)
if (strpos($request_uri, '/cart.php') === 0) {
    require __DIR__ . '/cart.php';
    exit;
}

if (strpos($request_uri, '/migrate-cart.php') === 0) {
    require __DIR__ . '/migrate-cart.php';
    exit;
}

if (strpos($request_uri, '/process-payment.php') === 0) {
    require __DIR__ . '/process-payment.php';
    exit;
}

if (strpos($request_uri, '/update-theme.php') === 0) {
    require __DIR__ . '/update-theme.php';
    exit;
}

if (strpos($request_uri, '/wishlist.php') === 0) {
    require __DIR__ . '/wishlist.php';
    exit;
}

if (strpos($request_uri, '/debug.php') === 0) {
    require __DIR__ . '/debug.php';
    exit;
}

if (strpos($request_uri, '/test-files.php') === 0) {
    require __DIR__ . '/test-files.php';
    exit;
}

if (strpos($request_uri, '/diagnose.php') === 0) {
    require __DIR__ . '/diagnose.php';
    exit;
}

if (strpos($request_uri, '/simple-diagnose.php') === 0) {
    require __DIR__ . '/simple-diagnose.php';
    exit;
}

if (strpos($request_uri, '/simple-test.php') === 0) {
    require __DIR__ . '/simple-test.php';
    exit;
}

if (strpos($request_uri, '/basic.php') === 0) {
    require __DIR__ . '/basic.php';
    exit;
}

// Handle admin routes
if (strpos($request_uri, '/admin') === 0) {
    $admin_file = substr($request_uri, 7); // Remove /admin
    if (empty($admin_file) || $admin_file === '/') {
        $admin_file = '/index.php';
    }
    
    $admin_path = __DIR__ . '/../admin' . $admin_file;
    if (file_exists($admin_path)) {
        // Set the correct working directory for admin files
        $original_cwd = getcwd();
        chdir(__DIR__ . '/../');
        
        // Capture output to handle any redirects or headers properly
        ob_start();
        include $admin_path;
        $output = ob_get_clean();
        
        // Restore original working directory
        chdir($original_cwd);
        
        echo $output;
        exit;
    }
}

// Handle main application routes
$main_file = ltrim($request_uri, '/');
if (empty($main_file)) {
    $main_file = 'index.php';
}

// Add .php extension if not present and file doesn't exist
if (!pathinfo($main_file, PATHINFO_EXTENSION) && !file_exists(__DIR__ . '/../' . $main_file)) {
    $main_file .= '.php';
}

$main_path = __DIR__ . '/../' . $main_file;

// Check if the file exists in the main directory
if (file_exists($main_path)) {
    // Set the correct working directory for main files
    $original_cwd = getcwd();
    chdir(__DIR__ . '/..');
    
    // Capture output to handle any redirects or headers properly
    ob_start();
    include $main_path;
    $output = ob_get_clean();
    
    // Restore original working directory
    chdir($original_cwd);
    
    echo $output;
    exit;
}

// If no file found, try index.php as fallback
$original_cwd = getcwd();
chdir(__DIR__ . '/..');

ob_start();
include __DIR__ . '/../index.php';
$output = ob_get_clean();

chdir($original_cwd);
echo $output;