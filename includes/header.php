<?php
// Include theme configuration
require_once __DIR__ . '/../config/theme.php';

// Generate immediate theme CSS to prevent flickering
echo generateThemeCSS($current_theme);
?>

<script>
// Prevent theme flickering by applying theme immediately
(function() {
    const theme = '<?php echo $current_theme; ?>';
    const themes = <?php echo json_encode($themes); ?>;
    
    if (themes[theme]) {
        const root = document.documentElement;
        const t = themes[theme];
        
        // Apply theme variables immediately
        root.style.setProperty('--primary-blue', t.primary);
        root.style.setProperty('--secondary-blue', t.primary);
        root.style.setProperty('--primary-orange', t.secondary);
        root.style.setProperty('--secondary-orange', t.secondary);
        root.style.setProperty('--white', t.white);
        root.style.setProperty('--light-gray', t.light_gray);
        root.style.setProperty('--bg-primary', t.bg_primary);
        root.style.setProperty('--bg-secondary', t.bg_secondary);
        root.style.setProperty('--text-primary', t.text_primary);
        root.style.setProperty('--text-secondary', t.text_secondary);
        root.style.setProperty('--text-muted', t.text_muted);
        root.style.setProperty('--border-light', t.border_light);
        
        // Prevent body flickering
        document.body.style.opacity = '1';
        document.body.style.transition = 'none';
    }
})();
</script>
<header class="header sticky top-0 z-50">

    <!-- Main Header -->
    <div class="container mx-auto px-4">
        <!-- Mobile Header -->
        <div class="flex items-center justify-between py-3 md:hidden">
            <!-- Mobile Menu Button -->
            <button id="mobile-menu-toggle" class="p-2 rounded-lg bg-tertiary hover:bg-hover-gray transition-colors duration-300 text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            
            <!-- Logo -->
            <div class="flex-1 text-center">
                <a href="index.php" class="text-xl font-bold transition-colors duration-300" style="color: var(--primary-blue);">
                    Tech<span style="color: var(--primary-orange);">Mart</span>
                </a>
            </div>
            
            <!-- Mobile Search Button -->
            <button id="mobile-search-toggle" class="p-2 rounded-lg bg-tertiary hover:bg-hover-gray transition-colors duration-300 text-primary">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </button>
            
            <!-- Mobile Wishlist -->
            <a href="wishlist.php" class="nav-link relative p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                <span id="wishlist-count-mobile" class="badge absolute -top-1 -right-1 hidden">0</span>
            </a>
            
            <!-- Mobile Cart -->
            <a href="cart.php" class="nav-link relative p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                </svg>
                <span id="cart-count-mobile" class="badge absolute -top-1 -right-1 hidden">0</span>
            </a>
        </div>

        <!-- Desktop Header -->
        <div class="hidden md:flex items-center justify-between py-3">
            <!-- Logo -->
            <div class="flex-shrink-0">
                <a href="index.php" class="text-2xl font-bold transition-colors duration-300" style="color: var(--primary-blue);">
                    Tech<span style="color: var(--primary-orange);">Mart</span>
                </a>
            </div>

            <!-- Search Bar - Responsive -->
            <div class="flex-1 max-w-lg lg:max-w-xl xl:max-w-2xl mx-4 lg:mx-8">
                <form action="search.php" method="GET" class="flex">
                    <div class="flex-1 relative">
                        <input type="text" name="q" placeholder="Search..." 
                               class="input w-full px-3 lg:px-4 py-2 lg:py-2.5 text-sm lg:text-base">
                        <button type="submit" class="absolute right-2 lg:right-3 top-1/2 transform -translate-y-1/2 text-muted hover:text-secondary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                    <button type="submit" class="btn-secondary px-3 lg:px-4 py-2 lg:py-2.5 text-sm lg:text-base font-medium hidden sm:block">
                        Search
                    </button>
                </form>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-2 lg:space-x-3">
                <!-- All Categories -->
                <div class="relative group">
                    <button class="flex items-center space-x-1 lg:space-x-1.5 bg-tertiary px-2 lg:px-3 py-2 rounded-lg hover:bg-hover-gray transition-colors duration-300 text-sm text-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <span class="hidden lg:block">Categories</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <!-- Mega Menu -->
                    <div class="fixed left-1/2 transform -translate-x-1/2 mt-2 w-screen max-w-4xl lg:max-w-5xl bg-primary rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50" style="top: 100px;">
                        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 lg:gap-6 p-4 lg:p-6 justify-items-center">
                            <!-- Electronics -->
                            <div class="text-center">
                                <h3 class="font-semibold text-primary mb-3 text-sm">Electronics</h3>
                                <ul class="space-y-1.5">
                                    <li><a href="category.php?id=1" class="nav-link text-sm">Audio & Video</a></li>
                                    <li><a href="category.php?id=2" class="nav-link text-sm">Camera & Photo</a></li>
                                    <li><a href="category.php?id=3" class="nav-link text-sm">Cell Phones</a></li>
                                    <li><a href="category.php?id=4" class="nav-link text-sm">Headphones</a></li>
                                </ul>
                            </div>
                            <!-- Computers -->
                            <div class="text-center">
                                <h3 class="font-semibold text-primary mb-3 text-sm">Computers</h3>
                                <ul class="space-y-1.5">
                                    <li><a href="category.php?id=5" class="nav-link text-sm">Laptops</a></li>
                                    <li><a href="category.php?id=6" class="nav-link text-sm">Desktops</a></li>
                                    <li><a href="category.php?id=7" class="nav-link text-sm">Tablets</a></li>
                                    <li><a href="category.php?id=8" class="nav-link text-sm">Accessories</a></li>
                                </ul>
                            </div>
                            <!-- Fashion -->
                            <div class="text-center">
                                <h3 class="font-semibold text-primary mb-3 text-sm">Fashion</h3>
                                <ul class="space-y-1.5">
                                    <li><a href="category.php?id=9" class="nav-link text-sm">Men's Clothing</a></li>
                                    <li><a href="category.php?id=10" class="nav-link text-sm">Women's Clothing</a></li>
                                    <li><a href="category.php?id=11" class="nav-link text-sm">Shoes</a></li>
                                    <li><a href="category.php?id=12" class="nav-link text-sm">Jewelry</a></li>
                                </ul>
                            </div>
                            <!-- Home & Garden -->
                            <div class="text-center">
                                <h3 class="font-semibold text-primary mb-3 text-sm">Home & Garden</h3>
                                <ul class="space-y-1.5">
                                    <li><a href="category.php?id=13" class="nav-link text-sm">Kitchen</a></li>
                                    <li><a href="category.php?id=14" class="nav-link text-sm">Furniture</a></li>
                                    <li><a href="category.php?id=15" class="nav-link text-sm">Tools</a></li>
                                    <li><a href="category.php?id=16" class="nav-link text-sm">Garden</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wishlist -->
                <a href="wishlist.php" class="nav-link relative p-1.5 lg:p-2">
                    <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                    <span id="wishlist-count" class="badge absolute -top-1 -right-1 hidden">0</span>
                </a>

                <!-- Cart -->
                <a href="cart.php" class="nav-link relative p-1.5 lg:p-2">
                    <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                    </svg>
                    <span id="cart-count" class="badge absolute -top-1 -right-1 hidden">0</span>
                </a>

                <!-- Theme Manager - Admin Only -->
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                    <button onclick="openThemeSwitcher()" class="nav-link flex items-center space-x-1.5 p-2">
                        <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                        </svg>
                        <span class="hidden sm:block text-sm">Theme</span>
                    </button>
                <?php endif; ?>

                <!-- User Account -->
                <div class="relative group">
                    <button class="nav-link flex items-center space-x-1.5 p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="hidden sm:block text-sm">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            <?php else: ?>
                                Account
                            <?php endif; ?>
                        </span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <!-- Dropdown Menu -->
                    <div class="absolute right-0 mt-2 w-48 bg-primary rounded-lg shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                        <div class="py-2">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="profile.php" class="nav-link block px-4 py-2 text-sm">My Profile</a>
                                <a href="orders.php" class="nav-link block px-4 py-2 text-sm">My Orders</a>
                                <a href="wishlist.php" class="nav-link block px-4 py-2 text-sm">My Wishlist</a>
                                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                    <hr class="my-2" style="border-color: var(--border-light);">
                                    <a href="admin/index.php" class="nav-link block px-4 py-2 text-sm font-semibold" style="color: var(--primary-blue);">
                                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Admin Dashboard
                                    </a>
                                <?php endif; ?>
                                <hr class="my-2" style="border-color: var(--border-light);">
                                <a href="logout.php" class="nav-link block px-4 py-2 text-sm">Sign Out</a>
                            <?php else: ?>
                                <a href="login.php" class="nav-link block px-4 py-2 text-sm">Sign In</a>
                                <a href="register.php" class="nav-link block px-4 py-2 text-sm">Create Account</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div id="mobile-search" class="hidden md:hidden py-3 border-t" style="border-color: var(--border-light);">
            <form action="search.php" method="GET" class="flex">
                <div class="flex-1 relative">
                    <input type="text" name="q" placeholder="Search for products, brands, and more..." 
                           class="input w-full px-4 py-3 text-sm">
                    <button type="submit" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-muted hover:text-secondary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
                <button type="submit" class="btn-secondary px-4 py-3 text-sm font-medium ml-2">
                    Search
                </button>
            </form>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-primary border-t" style="background-color: var(--bg-primary); border-color: var(--border-light);">
            <div class="px-4 py-4 space-y-4">
                <!-- Categories -->
                <div>
                    <h3 class="font-semibold text-primary mb-3">Categories</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="category.php?id=1" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">Electronics</a>
                        <a href="category.php?id=2" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">Computers</a>
                        <a href="category.php?id=5" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">Fashion</a>
                        <a href="category.php?id=4" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">Home & Kitchen</a>
                        <a href="category.php?id=6" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">Sports</a>
                        <a href="category.php?id=7" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">Books</a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="font-semibold text-primary mb-3">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="products.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">All Products</a>
                        <a href="deals.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">Today's Deals</a>
                        <a href="wishlist.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">My Wishlist</a>
                        <a href="profile.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary">My Account</a>
                        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                            <a href="admin/index.php" class="nav-link block px-3 py-2 rounded-lg hover:bg-tertiary font-semibold" style="color: var(--primary-blue);">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Admin Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="pt-4 border-t" style="border-color: var(--border-light);">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="space-y-2">
                            <p class="text-sm text-secondary text-center">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
                            <div class="flex space-x-3">
                                <a href="profile.php" class="btn-outline flex-1 text-center py-2">Profile</a>
                                <a href="logout.php" class="btn-primary flex-1 text-center py-2">Sign Out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="flex space-x-3">
                            <a href="login.php" class="btn-outline flex-1 text-center py-2">Sign In</a>
                            <a href="register.php" class="btn-primary flex-1 text-center py-2">Sign Up</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


    </div>
</header>

<?php include 'theme-switcher.php'; ?>

<script>
// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileSearch = document.getElementById('mobile-search');
    const mobileSearchToggle = document.getElementById('mobile-search-toggle');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            mobileSearch.classList.add('hidden');
        });
    }
    
    if (mobileSearchToggle && mobileSearch) {
        mobileSearchToggle.addEventListener('click', function() {
            mobileSearch.classList.toggle('hidden');
            mobileMenu.classList.add('hidden');
        });
    }
});

// Update cart count
async function updateCartCount() {
    <?php if (isset($_SESSION['user_id'])): ?>
        // For logged-in users, read from database
        try {
            const response = await fetch('api/cart.php');
            const result = await response.json();
            
            if (result.success) {
                const totalItems = result.totals.items;
                const cartCount = document.getElementById('cart-count');
                const mobileCartCount = document.getElementById('cart-count-mobile');
                
                if (cartCount) {
                    if (totalItems > 0) {
                        cartCount.textContent = totalItems;
                        cartCount.classList.remove('hidden');
                    } else {
                        cartCount.classList.add('hidden');
                    }
                }
                
                if (mobileCartCount) {
                    if (totalItems > 0) {
                        mobileCartCount.textContent = totalItems;
                        mobileCartCount.classList.remove('hidden');
                    } else {
                        mobileCartCount.classList.add('hidden');
                    }
                }
            }
        } catch (error) {
            console.error('Error updating cart count:', error);
        }
    <?php else: ?>
        // For non-logged-in users, read from localStorage
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        const cartCount = document.getElementById('cart-count');
        const mobileCartCount = document.getElementById('cart-count-mobile');
        
        if (cartCount) {
            if (totalItems > 0) {
                cartCount.textContent = totalItems;
                cartCount.classList.remove('hidden');
            } else {
                cartCount.classList.add('hidden');
            }
        }
        
        if (mobileCartCount) {
            if (totalItems > 0) {
                mobileCartCount.textContent = totalItems;
                mobileCartCount.classList.remove('hidden');
            } else {
                mobileCartCount.classList.add('hidden');
            }
        }
    <?php endif; ?>
}

// Update wishlist count
async function updateWishlistCount() {
    <?php if (isset($_SESSION['user_id'])): ?>
        // For logged-in users, read from database
        try {
            const response = await fetch('api/wishlist.php');
            const result = await response.json();
            
            if (result.success) {
                // Update desktop wishlist count
                const wishlistCount = document.getElementById('wishlist-count');
                if (wishlistCount) {
                    if (result.wishlist.length > 0) {
                        wishlistCount.textContent = result.wishlist.length;
                        wishlistCount.classList.remove('hidden');
                    } else {
                        wishlistCount.classList.add('hidden');
                    }
                }
                
                // Update mobile wishlist count
                const mobileWishlistCount = document.getElementById('wishlist-count-mobile');
                if (mobileWishlistCount) {
                    if (result.wishlist.length > 0) {
                        mobileWishlistCount.textContent = result.wishlist.length;
                        mobileWishlistCount.classList.remove('hidden');
                    } else {
                        mobileWishlistCount.classList.add('hidden');
                    }
                }
            }
        } catch (error) {
            console.error('Error updating wishlist count:', error);
        }
    <?php else: ?>
        // For non-logged-in users, always show 0 (hidden)
        const wishlistCount = document.getElementById('wishlist-count');
        if (wishlistCount) {
            wishlistCount.classList.add('hidden');
        }
        
        const mobileWishlistCount = document.getElementById('wishlist-count-mobile');
        if (mobileWishlistCount) {
            mobileWishlistCount.classList.add('hidden');
        }
    <?php endif; ?>
}

// Migrate cart from localStorage to database when user logs in
async function migrateCartIfNeeded() {
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['migrate_cart']) && $_SESSION['migrate_cart']): ?>
        const localStorageCart = JSON.parse(localStorage.getItem('cart')) || [];
        
        if (localStorageCart.length > 0) {
            try {
                const response = await fetch('api/migrate-cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        cart: localStorageCart
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Clear localStorage cart after successful migration
                    localStorage.removeItem('cart');
                    
                    // Show success message
                    if (result.migrated_items > 0) {
                        showNotification(result.message, 'success');
                    }
                    
                    // Update cart count
                    updateCartCount();
                } else {
                    console.error('Cart migration failed:', result.message);
                }
            } catch (error) {
                console.error('Error migrating cart:', error);
            }
        }
        
        // Clear the migration flag
        <?php unset($_SESSION['migrate_cart']); ?>
    <?php endif; ?>
}

// Show notification function
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    const bgColor = type === 'error' ? 'bg-red-500' : 'bg-green-500';
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

// Initialize counts and migrate cart on page load
migrateCartIfNeeded().then(() => {
    updateCartCount();
    updateWishlistCount();
});
</script>

