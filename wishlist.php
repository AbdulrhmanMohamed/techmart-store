<?php
session_start();
require_once 'config/theme.php';
require_once 'includes/seo.php';

// SEO Data for wishlist page
$seo_data = [
    'title' => 'My Wishlist - Save Items for Later | TechMart',
    'description' => 'View and manage your wishlist items at TechMart. Save products you love and get notified when prices drop.',
    'keywords' => 'wishlist, save items, favorites, saved products, price alerts, TechMart wishlist',
    'image' => '/assets/images/og-wishlist.jpg',
    'type' => 'website',
    'noindex' => true // Don't index wishlist pages
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
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: bold;
            font-size: 1.2rem;
        }
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen py-16">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold text-center mb-12 text-primary">My Wishlist</h1>
            
            <!-- Wishlist Items -->
            <div id="wishlist-items" class="max-w-6xl mx-auto">
                <!-- Wishlist items will be populated by JavaScript -->
            </div>
            
            <!-- Empty Wishlist Message -->
            <div id="empty-wishlist" class="text-center py-16 hidden">
                <div class="text-gray-400 dark:text-gray-500 mb-4">
                    <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-gray-600 dark:text-gray-400 mb-4">Your wishlist is empty</h2>
                <p class="text-gray-500 dark:text-gray-500 mb-8">Add some products to your wishlist to save them for later!</p>
                <a href="products.php" class="bg-blue-600 dark:bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300">
                    Start Shopping
                </a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>

        // Wishlist and Cart Functionality
        let wishlist = [];
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
        async function loadWishlist() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/wishlist.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        wishlist = result.wishlist;
                        renderWishlist();
                        updateWishlistCount();
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error loading wishlist:', error);
                    showNotification('Error loading wishlist', 'error');
                }
            <?php else: ?>
                showNotification('Please log in to view your wishlist', 'error');
                document.getElementById('empty-wishlist').classList.remove('hidden');
            <?php endif; ?>
        }
        
        function updateWishlistCount() {
            const wishlistCount = document.getElementById('wishlist-count');
            if (wishlistCount) {
                if (wishlist.length > 0) {
                    wishlistCount.textContent = wishlist.length;
                    wishlistCount.classList.remove('hidden');
                } else {
                    wishlistCount.classList.add('hidden');
                }
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
                            const totalItems = result.cart.reduce((sum, item) => sum + item.quantity, 0);
                            
                            if (totalItems > 0) {
                                cartCount.textContent = totalItems;
                                cartCount.classList.remove('hidden');
                            } else {
                                cartCount.classList.add('hidden');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error updating cart count:', error);
                    });
            <?php else: ?>
                // For non-logged-in users, use localStorage
                const cartCount = document.getElementById('cart-count');
                const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
                
                if (totalItems > 0) {
                    cartCount.textContent = totalItems;
                    cartCount.classList.remove('hidden');
                } else {
                    cartCount.classList.add('hidden');
                }
            <?php endif; ?>
        }
        
        async function toggleCart(id, name, price) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    // Check if item is in cart first
                    const cartResponse = await fetch('api/cart.php');
                    const cartResult = await cartResponse.json();
                    const isInCart = cartResult.success && cartResult.cart.some(item => item.id === id);
                    
                    const method = isInCart ? 'DELETE' : 'POST';
                    const response = await fetch('api/cart.php', {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: id,
                            quantity: 1
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification(result.message);
                        
                        // Update cart count with a small delay to ensure proper update
                        setTimeout(() => {
                            updateCartCount();
                        }, 100);
                        
                        renderWishlist(); // Re-render to update button states
                        
                        // Emit cart change event
                        const cartEvent = new CustomEvent('cartChanged', {
                            detail: { 
                                productId: id, 
                                quantity: isInCart ? 0 : 1, 
                                action: isInCart ? 'remove' : 'add' 
                            }
                        });
                        document.dispatchEvent(cartEvent);
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error updating cart:', error);
                    showNotification('Error updating cart', 'error');
                }
            <?php else: ?>
                // Fallback to localStorage for non-logged-in users
                const existingItem = cart.find(item => item.id === id);
                
                if (existingItem) {
                    // Remove from cart
                    cart = cart.filter(item => item.id !== id);
                    showNotification(`${name} removed from cart!`);
                } else {
                    // Add to cart
                    cart.push({
                        id: id,
                        name: name,
                        price: price,
                        quantity: 1
                    });
                    showNotification(`${name} added to cart!`);
                }
                
                localStorage.setItem('cart', JSON.stringify(cart));
                
                // Update cart count with a small delay to ensure proper update
                setTimeout(() => {
                    updateCartCount();
                }, 100);
                
                renderWishlist(); // Re-render to update button states
                
                // Emit cart change event
                const cartEvent = new CustomEvent('cartChanged', {
                    detail: { 
                        productId: id, 
                        quantity: existingItem ? 0 : 1, 
                        action: existingItem ? 'remove' : 'add' 
                    }
                });
                document.dispatchEvent(cartEvent);
            <?php endif; ?>
        }
        
        async function removeFromWishlist(id) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/wishlist.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: id
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        wishlist = wishlist.filter(item => item.id !== id);
                        renderWishlist();
                        updateWishlistCount();
                        showNotification('Item removed from wishlist');
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error removing from wishlist:', error);
                    showNotification('Error removing from wishlist', 'error');
                }
            <?php endif; ?>
        }
        
        function renderWishlist() {
            const wishlistItems = document.getElementById('wishlist-items');
            const emptyWishlist = document.getElementById('empty-wishlist');
            
            if (wishlist.length === 0) {
                wishlistItems.innerHTML = '';
                emptyWishlist.classList.remove('hidden');
                return;
            }
            
            emptyWishlist.classList.add('hidden');
            
            wishlistItems.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    ${wishlist.map(item => `
                        <div class="card relative">
                            <button onclick="removeFromWishlist(${item.id})" class="absolute top-2 right-2 z-10 bg-error text-white rounded-full p-2 hover:bg-error/80 transition-colors duration-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                            <div class="w-full h-48 overflow-hidden">
                                ${item.image_url ? 
                                    `<img src="${item.image_url}" alt="${item.name}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">` :
                                    `<div class="w-full h-full product-image flex items-center justify-center text-white font-bold text-sm">${item.name}</div>`
                                }
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold mb-2 text-primary line-clamp-2">${item.name}</h3>
                                <div class="flex items-center mb-2">
                                    <div class="flex text-yellow-400">
                                        ${Array.from({length: 5}, (_, i) => `
                                            <svg class="w-4 h-4 ${i < item.rating ? 'text-yellow-400' : 'text-gray-300'}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        `).join('')}
                                    </div>
                                    <span class="text-sm text-muted ml-2">(${item.review_count || 0})</span>
                                </div>
                                <div class="flex items-center space-x-2 mb-3">
                                    ${item.sale_price ? `
                                        <span class="text-2xl font-bold text-error">$${parseFloat(item.sale_price).toFixed(2)}</span>
                                        <span class="text-lg text-muted line-through">$${parseFloat(item.price).toFixed(2)}</span>
                                    ` : `
                                        <span class="text-2xl font-bold text-primary">$${parseFloat(item.price).toFixed(2)}</span>
                                    `}
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="toggleCart(${item.id}, '${item.name}', ${item.sale_price || item.price})" 
                                            class="flex-1 btn btn-primary cart-btn-${item.id}">
                                        <span class="cart-text-${item.id}">Add to Cart</span>
                                    </button>
                                    <a href="product.php?id=${item.id}" 
                                       class="btn btn-outline">
                                        View
                                    </a>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            // Update cart button states after rendering
            updateCartButtonStates();
        }
        
        async function updateCartButtonStates() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/cart.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        const cartIds = result.cart.map(item => item.id);
                        
                        wishlist.forEach(item => {
                            const btn = document.querySelector(`.cart-btn-${item.id}`);
                            const text = document.querySelector(`.cart-text-${item.id}`);
                            
                            if (btn && text) {
                                if (cartIds.includes(item.id)) {
                                    btn.className = 'flex-1 btn btn-outline';
                                    text.textContent = 'Remove from Cart';
                                } else {
                                    btn.className = 'flex-1 btn btn-primary';
                                    text.textContent = 'Add to Cart';
                                }
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error updating cart button states:', error);
                }
            <?php else: ?>
                // For non-logged-in users, check localStorage
                const cartIds = cart.map(item => item.id);
                
                wishlist.forEach(item => {
                    const btn = document.querySelector(`.cart-btn-${item.id}`);
                    const text = document.querySelector(`.cart-text-${item.id}`);
                    
                    if (btn && text) {
                        if (cartIds.includes(item.id)) {
                            btn.className = 'flex-1 btn btn-outline';
                            text.textContent = 'Remove from Cart';
                        } else {
                            btn.className = 'flex-1 btn btn-primary';
                            text.textContent = 'Add to Cart';
                        }
                    }
                });
            <?php endif; ?>
        }
        
        function showNotification(message) {
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full transition-transform duration-300';
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
        
        // Initialize on page load
        loadWishlist();
        updateCartCount();
    </script>
</body>
</html>
