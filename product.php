<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get product details
$product = getProductById($product_id);

if (!$product) {
    header('Location: products.php');
    exit;
}

// Get brand name for SEO
$brand_name = '';
if (isset($product['brand_id']) && $product['brand_id']) {
    $brand = getBrandById($product['brand_id']);
    $brand_name = $brand ? $brand['name'] : '';
}

// SEO Data for product page
$seo_data = [
    'title' => htmlspecialchars($product['name']) . ' - ' . ($brand_name ? $brand_name . ' | ' : '') . 'TechMart',
    'description' => htmlspecialchars($product['short_description'] ?: substr(strip_tags($product['description']), 0, 160)) . ' - Shop now at TechMart with free shipping and great prices.',
    'keywords' => htmlspecialchars($product['name']) . ', ' . ($brand_name ? $brand_name . ', ' : '') . 'online shopping, ecommerce, deals, free shipping',
    'image' => $product['image_url'] ?: '/assets/images/og-product.jpg',
    'type' => 'product'
];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <?php echo generateSEOTags($seo_data); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
    <script src="assets/js/quantity-sync.js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .product-image {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
            font-size: 2rem;
        }
    </style>
</head>
<body class="bg-primary transition-colors duration-300">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen py-16">
        <div class="container mx-auto px-4">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 text-sm text-muted">
                    <li><a href="index.php" class="hover:text-primary">Home</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li><a href="products.php" class="hover:text-primary">Products</a></li>
                    <li><span class="mx-2">/</span></li>
                    <li class="text-primary"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>

            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Product Image -->
                    <div>
                        <div class="w-full h-96 rounded-lg shadow-lg overflow-hidden">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full product-image flex items-center justify-center">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Product Details -->
                    <div class="space-y-6">
                        <div>
                            <h1 class="text-4xl font-bold text-primary mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                            <div class="flex items-center space-x-4 mb-6">
                                <span class="text-3xl font-bold text-secondary">$<?php echo number_format($product['price'], 2); ?></span>
                                <span class="px-3 py-1 bg-success text-white rounded-full text-sm font-medium">In Stock</span>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold text-primary mb-3">Description</h2>
                            <p class="text-muted leading-relaxed"><?php echo htmlspecialchars($product['description']); ?></p>
                        </div>

                        <div class="space-y-4">
                            <h2 class="text-xl font-semibold text-primary">Quantity</h2>
                            <div class="flex items-center space-x-4">
                                <button onclick="updateQuantity(-1)" class="w-12 h-12 rounded-full btn btn-secondary hover:btn-secondary/80 transition duration-200 flex items-center justify-center text-lg font-bold">
                                    −
                                </button>
                                <span id="quantity" class="w-16 text-center text-xl font-bold text-primary bg-tertiary rounded-lg py-2 px-4">1</span>
                                <button onclick="updateQuantity(1)" class="w-12 h-12 rounded-full btn btn-secondary hover:btn-secondary/80 transition duration-200 flex items-center justify-center text-lg font-bold">
                                    +
                                </button>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <button onclick="toggleCart()" id="cart-btn" class="w-full btn btn-primary py-4 px-6 rounded-lg transition duration-300 font-semibold text-lg">
                                <span id="cart-text">Add to Cart - $<span id="total-price"><?php echo number_format($product['price'], 2); ?></span></span>
                            </button>
                            <button onclick="toggleWishlist()" id="wishlist-btn" class="w-full btn btn-outline py-4 px-6 rounded-lg transition duration-300 font-semibold text-lg">
                                <span id="wishlist-text">Add to Wishlist</span>
                            </button>
                            <button onclick="buyNow()" class="w-full btn btn-secondary py-4 px-6 rounded-lg transition duration-300 font-semibold text-lg">
                                Buy Now
                            </button>
                        </div>

                        <!-- Product Features -->
                        <div class="border-t border-primary/20 pt-6">
                            <h2 class="text-xl font-semibold text-primary mb-4">Features</h2>
                            <ul class="space-y-2 text-muted">
                                <li class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>High Quality Materials</span>
                                </li>
                                <li class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>Fast Shipping</span>
                                </li>
                                <li class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>30-Day Return Policy</span>
                                </li>
                                <li class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-success" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span>24/7 Customer Support</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>

        // Cart and Wishlist Functionality
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        let quantity = 1;
        const productPrice = <?php echo $product['price']; ?>;
        const productId = <?php echo $product['id']; ?>;
        const productName = '<?php echo addslashes($product['name']); ?>';
        let isInWishlist = false;
        let isInCart = false;
        
        function updateQuantity(change) {
            quantity = Math.max(1, quantity + change);
            updateQuantityDisplay();
            updateCartButton();
            
            // Emit global quantity change
            syncQuantity(productId, quantity, 'product');
        }
        
        function updateQuantityDisplay() {
            // Safely update quantity display
            const quantityElement = document.getElementById('quantity');
            if (quantityElement) {
                quantityElement.textContent = quantity;
            }
            
            // Safely update total price display
            const totalPriceElement = document.getElementById('total-price');
            if (totalPriceElement) {
                totalPriceElement.textContent = (productPrice * quantity).toFixed(2);
            }
        }
        
        async function toggleCart() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const method = isInCart ? 'DELETE' : 'POST';
                    const response = await fetch('api/cart.php', {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId,
                            quantity: quantity
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        isInCart = !isInCart;
                        updateCartButton();
                        
                        // Update cart count with a small delay to ensure proper update
                        setTimeout(() => {
                            updateCartCount();
                        }, 100);
                        
                        showNotification(result.message);
                        
                        // Emit cart change event
                        const cartEvent = new CustomEvent('cartChanged', {
                            detail: { 
                                productId: productId, 
                                quantity: quantity, 
                                action: isInCart ? 'add' : 'remove' 
                            }
                        });
                        document.dispatchEvent(cartEvent);
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error updating cart:', error);
                    showNotification('Error updating cart. Please try again.', 'error');
                }
            <?php else: ?>
                // Fallback to localStorage for non-logged-in users
                const existingItem = cart.find(item => item.id === productId);
                
                if (existingItem) {
                    // Remove from cart
                    cart = cart.filter(item => item.id !== productId);
                    isInCart = false;
                    showNotification(`${productName} removed from cart!`);
                } else {
                    // Add to cart
                    cart.push({
                        id: productId,
                        name: productName,
                        price: productPrice,
                        quantity: quantity
                    });
                    isInCart = true;
                    showNotification(`${productName} added to cart!`);
                }
                
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartButton();
                
                // Update cart count with a small delay to ensure proper update
                setTimeout(() => {
                    updateCartCount();
                }, 100);
            <?php endif; ?>
        }
        
        async function toggleWishlist() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const method = isInWishlist ? 'DELETE' : 'POST';
                    const response = await fetch('api/wishlist.php', {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: productId
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        isInWishlist = !isInWishlist;
                        updateWishlistButton();
                        updateWishlistCount();
                        showNotification(result.message);
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error updating wishlist:', error);
                    showNotification('Error updating wishlist. Please try again.', 'error');
                }
            <?php else: ?>
                showNotification('Please log in to add items to your wishlist', 'error');
                // Optionally redirect to login page
                setTimeout(() => {
                    if (confirm('Would you like to log in to add items to your wishlist?')) {
                        window.location.href = 'login.php';
                    }
                }, 2000);
            <?php endif; ?>
        }
        
        function updateWishlistButton() {
            const btn = document.getElementById('wishlist-btn');
            const text = document.getElementById('wishlist-text');
            
            if (isInWishlist) {
                btn.className = 'w-full btn btn-outline py-4 px-6 rounded-lg transition duration-300 font-semibold text-lg';
                text.textContent = 'Remove from Wishlist';
            } else {
                btn.className = 'w-full btn btn-outline py-4 px-6 rounded-lg transition duration-300 font-semibold text-lg';
                text.textContent = 'Add to Wishlist';
            }
        }
        
        function updateCartButton() {
            const btn = document.getElementById('cart-btn');
            const text = document.getElementById('cart-text');
            const totalPriceElement = document.getElementById('total-price');
            
            if (btn && text) {
                if (isInCart) {
                    btn.className = 'w-full btn btn-outline py-4 px-6 rounded-lg transition duration-300 font-semibold text-lg';
                    text.textContent = 'Remove from Cart';
                } else {
                    btn.className = 'w-full btn btn-primary py-4 px-6 rounded-lg transition duration-300 font-semibold text-lg';
                    text.textContent = 'Add to Cart - $' + (productPrice * quantity).toFixed(2);
                    
                    // Also update the total-price element if it exists
                    if (totalPriceElement) {
                        totalPriceElement.textContent = (productPrice * quantity).toFixed(2);
                    }
                }
            }
        }
        
        async function checkWishlistStatus() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/wishlist.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        isInWishlist = result.wishlist.some(item => item.id === productId);
                        updateWishlistButton();
                    }
                } catch (error) {
                    console.error('Error checking wishlist status:', error);
                }
            <?php endif; ?>
        }
        
        async function checkCartStatus() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/cart.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        const cartItem = result.cart.find(item => item.id === productId);
                        isInCart = !!cartItem;
                        
                        // If item is in cart, sync the quantity
                        if (cartItem) {
                            quantity = cartItem.quantity;
                            updateQuantityDisplay();
                        }
                        
                        updateCartButton();
                    }
                } catch (error) {
                    console.error('Error checking cart status:', error);
                }
            <?php else: ?>
                // For non-logged-in users, check localStorage
                const cartItem = cart.find(item => item.id === productId);
                isInCart = !!cartItem;
                
                // If item is in cart, sync the quantity
                if (cartItem) {
                    quantity = cartItem.quantity;
                    updateQuantityDisplay();
                }
                
                updateCartButton();
            <?php endif; ?>
        }
        
        function buyNow() {
            if (!isInCart) {
                toggleCart().then(() => {
                    window.location.href = 'cart.php';
                });
            } else {
                window.location.href = 'cart.php';
            }
        }
        
        function updateCartCount() {
            <?php if (isset($_SESSION['user_id'])): ?>
                // For logged-in users, fetch cart from database
                fetch('api/cart.php')
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            const cartCount = document.getElementById('cart-count');
                            const mobileCartCount = document.getElementById('cart-count-mobile');
                            const totalItems = result.totals.items;
                            
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
                    })
                    .catch(error => {
                        console.error('Error updating cart count:', error);
                    });
            <?php else: ?>
                // For non-logged-in users, use localStorage
                const cartCount = document.getElementById('cart-count');
                const mobileCartCount = document.getElementById('cart-count-mobile');
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                
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
        
        function updateWishlistCount() {
            <?php if (isset($_SESSION['user_id'])): ?>
                // Fetch current wishlist and update header badges
                fetch('api/wishlist.php')
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            const wishlistCount = document.getElementById('wishlist-count');
                            const wishlistCountMobile = document.getElementById('wishlist-count-mobile');
                            const count = result.wishlist.length;
                            
                            // Update desktop badge
                            if (wishlistCount) {
                                if (count > 0) {
                                    wishlistCount.textContent = count;
                                    wishlistCount.classList.remove('hidden');
                                } else {
                                    wishlistCount.classList.add('hidden');
                                }
                            }
                            
                            // Update mobile badge
                            if (wishlistCountMobile) {
                                if (count > 0) {
                                    wishlistCountMobile.textContent = count;
                                    wishlistCountMobile.classList.remove('hidden');
                                } else {
                                    wishlistCountMobile.classList.add('hidden');
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error updating wishlist count:', error);
                    });
            <?php endif; ?>
        }
        
        function showNotification(message, type = 'success') {
            // Create notification element
            const notification = document.createElement('div');
            const bgColor = type === 'error' ? 'bg-red-500' : 'bg-green-500';
            notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        
        // Initialize on page load
        updateCartCount();
        checkWishlistStatus();
        checkCartStatus();
        
        // Listen for external quantity changes
        onQuantityChange(productId, function(newQuantity, source) {
            if (source !== 'product') { // Don't update if we're the source
                quantity = newQuantity;
                updateQuantityDisplay();
                updateCartButton();
            }
        });
        
        // Listen for cart changes
        document.addEventListener('cartChanged', function(e) {
            const { productId: changedProductId, quantity: newQuantity, action } = e.detail;
            if (changedProductId === productId) {
                if (action === 'add' || action === 'update') {
                    isInCart = true;
                    quantity = newQuantity;
                    updateQuantityDisplay();
                    updateCartButton();
                } else if (action === 'remove') {
                    isInCart = false;
                    updateCartButton();
                }
            }
        });
        
        // Refresh cart status when page becomes visible (e.g., browser back button)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkCartStatus();
                updateCartCount();
            }
        });
        
        // Also refresh when window gains focus
        window.addEventListener('focus', function() {
            checkCartStatus();
            updateCartCount();
        });
    </script>
    
    <!-- Product Structured Data -->
    <?php 
    $product_with_brand = $product;
    $product_with_brand['brand_name'] = $brand_name;
    echo generateProductStructuredData($product_with_brand);
    ?>
    
    <!-- Breadcrumb Structured Data -->
    <?php 
    $breadcrumbs = [
        ['name' => 'Home', 'url' => 'https://phpstore.com/'],
        ['name' => 'Products', 'url' => 'https://phpstore.com/products.php'],
        ['name' => htmlspecialchars($product['name']), 'url' => 'https://phpstore.com/product.php?id=' . $product['id']]
    ];
    echo generateBreadcrumbStructuredData($breadcrumbs);
    ?>
</body>
</html>
