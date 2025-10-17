<?php
/**
 * Theme Configuration and Management
 * Handles theme persistence via database and session
 */

// Available themes
$themes = [
    'default' => [
        'name' => 'Default',
        'primary' => '#2563eb',
        'secondary' => '#f97316',
        'white' => '#ffffff',
        'light_gray' => '#f8fafc',
        'bg_primary' => '#ffffff',
        'bg_secondary' => '#f8fafc',
        'text_primary' => '#0f172a',
        'text_secondary' => '#475569',
        'text_muted' => '#64748b',
        'border_light' => '#e2e8f0'
    ],
    'ocean' => [
        'name' => 'Ocean',
        'primary' => '#0ea5e9',
        'secondary' => '#06b6d4',
        'white' => '#ffffff',
        'light_gray' => '#f0f9ff',
        'bg_primary' => '#ffffff',
        'bg_secondary' => '#f0f9ff',
        'text_primary' => '#0c4a6e',
        'text_secondary' => '#0369a1',
        'text_muted' => '#0284c7',
        'border_light' => '#bae6fd'
    ],
    'forest' => [
        'name' => 'Forest',
        'primary' => '#059669',
        'secondary' => '#10b981',
        'white' => '#ffffff',
        'light_gray' => '#f0fdf4',
        'bg_primary' => '#ffffff',
        'bg_secondary' => '#f0fdf4',
        'text_primary' => '#064e3b',
        'text_secondary' => '#047857',
        'text_muted' => '#059669',
        'border_light' => '#bbf7d0'
    ],
    'sunset' => [
        'name' => 'Sunset',
        'primary' => '#dc2626',
        'secondary' => '#f97316',
        'white' => '#ffffff',
        'light_gray' => '#fef2f2',
        'bg_primary' => '#ffffff',
        'bg_secondary' => '#fef2f2',
        'text_primary' => '#7f1d1d',
        'text_secondary' => '#dc2626',
        'text_muted' => '#ef4444',
        'border_light' => '#fecaca'
    ],
    'purple' => [
        'name' => 'Purple',
        'primary' => '#7c3aed',
        'secondary' => '#a855f7',
        'white' => '#ffffff',
        'light_gray' => '#faf5ff',
        'bg_primary' => '#ffffff',
        'bg_secondary' => '#faf5ff',
        'text_primary' => '#581c87',
        'text_secondary' => '#7c3aed',
        'text_muted' => '#8b5cf6',
        'border_light' => '#ddd6fe'
    ]
];

/**
 * Get user's theme from database or session
 */
function getUserTheme($pdo, $user_id = null) {
    global $themes;
    
    // First check session
    if (isset($_SESSION['theme'])) {
        return $_SESSION['theme'];
    }
    
    // If user is logged in, get from database
    if ($user_id) {
        try {
            $stmt = $pdo->prepare("SELECT theme FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch();
            
            if ($result && isset($themes[$result['theme']])) {
                $_SESSION['theme'] = $result['theme'];
                return $result['theme'];
            }
        } catch (PDOException $e) {
            error_log("Theme database error: " . $e->getMessage());
        }
    }
    
    // Fallback to default
    $_SESSION['theme'] = 'default';
    return 'default';
}

/**
 * Save theme to database
 */
function saveUserTheme($pdo, $user_id, $theme) {
    global $themes;
    
    if (!isset($themes[$theme])) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET theme = ? WHERE id = ?");
        $result = $stmt->execute([$theme, $user_id]);
        
        if ($result) {
            $_SESSION['theme'] = $theme;
            return true;
        }
    } catch (PDOException $e) {
        error_log("Theme save error: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Generate inline CSS for immediate theme application
 */
function generateThemeCSS($theme_name) {
    global $themes;
    
    $theme = $themes[$theme_name] ?? $themes['default'];
    
    return "
    <style>
        :root {
            --primary-blue: {$theme['primary']};
            --secondary-blue: {$theme['primary']};
            --primary-orange: {$theme['secondary']};
            --secondary-orange: {$theme['secondary']};
            --white: {$theme['white']};
            --light-gray: {$theme['light_gray']};
            --bg-primary: {$theme['bg_primary']};
            --bg-secondary: {$theme['bg_secondary']};
            --text-primary: {$theme['text_primary']};
            --text-secondary: {$theme['text_secondary']};
            --text-muted: {$theme['text_muted']};
            --border-light: {$theme['border_light']};
        }
        
        /* Prevent flickering */
        body {
            opacity: 1 !important;
            transition: none !important;
        }
    </style>";
}

// Get current theme
$current_theme = getUserTheme($pdo ?? null, $_SESSION['user_id'] ?? null);
$theme_config = $themes[$current_theme] ?? $themes['default'];
?>