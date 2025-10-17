<?php
/**
 * Theme Update API
 * Handles theme switching requests with database persistence
 */

session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['theme'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$theme_key = $input['theme'];

// Validate theme
if (!array_key_exists($theme_key, $themes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid theme']);
    exit;
}

// Update session
$_SESSION['theme'] = $theme_key;

// Save to database if user is logged in
if (isset($_SESSION['user_id'])) {
    $success = saveUserTheme($pdo, $_SESSION['user_id'], $theme_key);
    if (!$success) {
        error_log("Failed to save theme to database for user " . $_SESSION['user_id']);
    }
}

// Return success with theme data
echo json_encode([
    'success' => true,
    'message' => 'Theme updated successfully',
    'theme' => $themes[$theme_key]
]);
?>


