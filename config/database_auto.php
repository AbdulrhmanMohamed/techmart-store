<?php
// Auto-detect environment and use appropriate database configuration

// Check if MySQL is explicitly requested
$useMySQL = !empty($_ENV['USE_MYSQL']) || 
            !empty($_ENV['DATABASE_URL']) ||
            !empty($_ENV['RAILWAY_ENVIRONMENT']) || 
            !empty($_ENV['HEROKU_APP_NAME']);

// Check if we're on Vercel or want JSON by default
$useJSON = !empty($_ENV['VERCEL']) || 
           !empty($_ENV['USE_JSON_DB']) ||
           (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'vercel.app') !== false) ||
           !$useMySQL; // Default to JSON if no MySQL environment detected

if ($useJSON) {
    // Use JSON database configuration (default)
    require_once __DIR__ . '/database_vercel.php';
} else {
    // Use MySQL database configuration (only when explicitly requested)
    require_once __DIR__ . '/database.php';
}
?>