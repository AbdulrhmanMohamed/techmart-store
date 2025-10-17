<?php
/**
 * Theme Switcher Component
 * Allows easy switching between different color themes
 */

// Include theme configuration
require_once __DIR__ . '/../config/theme.php';
?>

<!-- Theme Switcher Modal -->
<div id="theme-switcher-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-primary rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-primary">Choose Theme</h3>
                <button onclick="closeThemeSwitcher()" class="text-muted hover:text-primary">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <?php foreach ($themes as $key => $theme): ?>
                <button onclick="switchTheme('<?php echo $key; ?>')" 
                        class="theme-option p-4 rounded-lg border-2 transition-all duration-200 <?php echo $key === $current_theme ? 'border-primary bg-tertiary' : 'border-light hover:border-medium'; ?>">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm" 
                             style="background: linear-gradient(135deg, <?php echo $theme['primary']; ?> 0%, <?php echo $theme['secondary']; ?> 100%);">
                            <?php echo strtoupper(substr($theme['name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="font-medium text-primary"><?php echo $theme['name']; ?></div>
                            <div class="text-xs text-muted">Primary & Secondary</div>
                        </div>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button onclick="closeThemeSwitcher()" class="btn-outline px-4 py-2 text-sm">
                    Cancel
                </button>
                <button onclick="applyTheme()" class="btn-primary px-4 py-2 text-sm">
                    Apply Theme
                </button>
            </div>
        </div>
    </div>
</div>


<script>
// Theme switching functionality
let selectedTheme = '<?php echo $current_theme; ?>';

function openThemeSwitcher() {
    // Sync selectedTheme with localStorage
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        selectedTheme = savedTheme;
    }
    
    // Update the modal to show current selection
    updateThemeSelection();
    
    document.getElementById('theme-switcher-modal').classList.remove('hidden');
}

function closeThemeSwitcher() {
    document.getElementById('theme-switcher-modal').classList.add('hidden');
}

function switchTheme(themeKey) {
    // Remove active class from all options
    document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('border-primary', 'bg-tertiary');
        option.classList.add('border-light');
    });
    
    // Add active class to selected option
    event.target.closest('.theme-option').classList.add('border-primary', 'bg-tertiary');
    event.target.closest('.theme-option').classList.remove('border-light');
    
    selectedTheme = themeKey;
}

function updateThemeSelection() {
    // Remove active class from all options
    document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('border-primary', 'bg-tertiary');
        option.classList.add('border-light');
    });
    
    // Add active class to currently selected theme
    const activeOption = document.querySelector(`[onclick="switchTheme('${selectedTheme}')"]`);
    if (activeOption) {
        activeOption.classList.add('border-primary', 'bg-tertiary');
        activeOption.classList.remove('border-light');
    }
}

function applyTheme() {
    // Send AJAX request to update theme
    fetch('api/update-theme.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ theme: selectedTheme })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update CSS variables immediately
            updateThemeVariables(data.theme);
            
            // Save theme to localStorage as backup
            localStorage.setItem('theme', selectedTheme);
            
            closeThemeSwitcher();
            
            // Show success message
            showNotification('Theme updated successfully!', 'success');
            
            // Reload page to ensure all elements are properly themed
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification('Failed to update theme', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to update theme', 'error');
    });
}

function updateThemeVariables(theme) {
    const root = document.documentElement;
    
    // Update CSS custom properties
    root.style.setProperty('--primary-blue', theme.primary);
    root.style.setProperty('--primary-orange', theme.secondary);
    root.style.setProperty('--white', theme.white);
    root.style.setProperty('--light-gray', theme.light_gray);
    
    // Update secondary colors (derived from primary)
    const primaryHsl = hexToHsl(theme.primary);
    const secondaryHsl = hexToHsl(theme.secondary);
    
    root.style.setProperty('--secondary-blue', hslToHex(primaryHsl.h, primaryHsl.s, Math.max(0, primaryHsl.l - 0.1)));
    root.style.setProperty('--secondary-orange', hslToHex(secondaryHsl.h, secondaryHsl.s, Math.max(0, secondaryHsl.l - 0.1)));
}

// Utility functions for color conversion
function hexToHsl(hex) {
    const r = parseInt(hex.slice(1, 3), 16) / 255;
    const g = parseInt(hex.slice(3, 5), 16) / 255;
    const b = parseInt(hex.slice(5, 7), 16) / 255;
    
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    let h, s, l = (max + min) / 2;
    
    if (max === min) {
        h = s = 0;
    } else {
        const d = max - min;
        s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
        switch (max) {
            case r: h = (g - b) / d + (g < b ? 6 : 0); break;
            case g: h = (b - r) / d + 2; break;
            case b: h = (r - g) / d + 4; break;
        }
        h /= 6;
    }
    
    return { h: h * 360, s: s, l: l };
}

function hslToHex(h, s, l) {
    h /= 360;
    const a = s * Math.min(l, 1 - l);
    const f = n => {
        const k = (n + h * 12) % 12;
        const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
        return Math.round(255 * color).toString(16).padStart(2, '0');
    };
    return `#${f(0)}${f(8)}${f(4)}`;
}

function showNotification(message, type) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-success text-white' : 'bg-error text-white'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Close modal when clicking outside
document.getElementById('theme-switcher-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeThemeSwitcher();
    }
});

// Load theme from localStorage on page load
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        // Always prioritize localStorage theme over server theme
        applyThemeFromStorage(savedTheme);
    }
});

// Apply theme from localStorage without server request
function applyThemeFromStorage(themeKey) {
    // Get theme data from the PHP themes array
    const themes = {
        'default': { primary: '#2563eb', secondary: '#f97316', white: '#ffffff', light_gray: '#f8fafc' },
        'ocean': { primary: '#0ea5e9', secondary: '#06b6d4', white: '#ffffff', light_gray: '#f0f9ff' },
        'forest': { primary: '#059669', secondary: '#10b981', white: '#ffffff', light_gray: '#f0fdf4' },
        'sunset': { primary: '#dc2626', secondary: '#f97316', white: '#ffffff', light_gray: '#fef2f2' },
        'purple': { primary: '#7c3aed', secondary: '#a855f7', white: '#ffffff', light_gray: '#faf5ff' }
    };
    
    const theme = themes[themeKey];
    if (theme) {
        updateThemeVariables(theme);
        selectedTheme = themeKey;
    }
}
</script>


