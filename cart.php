<?php
session_start();
require_once 'config/theme.php';
require_once 'includes/seo.php';

// SEO Data for cart page
$seo_data = [
    'title' => 'Shopping Cart - Review Your Items | TechMart',
    'description' => 'Review your shopping cart items, update quantities, and proceed to checkout at TechMart. Secure and easy shopping experience.',
    'keywords' => 'shopping cart, checkout, review items, secure shopping, TechMart cart',
    'image' => '/assets/images/og-cart.jpg',
    'type' => 'website',
    'noindex' => true // Don't index cart pages
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
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen py-16">
        <div class="container mx-auto px-4">
            <h1 class="text-3xl font-bold text-center mb-12 text-primary">Shopping Cart</h1>
            
            <!-- Cart Items -->
            <div id="cart-items" class="max-w-4xl mx-auto">
                <!-- Cart items will be populated by JavaScript -->
            </div>
            
            <!-- Empty Cart Message -->
            <div id="empty-cart" class="text-center py-16 hidden">
                <div class="text-muted mb-4">
                    <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-semibold text-secondary mb-4">Your cart is empty</h2>
                <p class="text-muted mb-8">Add some products to get started!</p>
                <a href="products.php" class="btn-primary px-6 py-3 rounded-lg">
                    Continue Shopping
                </a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>

        // Cart Functionality
        let cart = [];
        let cartData = JSON.parse(localStorage.getItem('cart')) || [];
        
        async function loadCart() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/cart.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        cart = result.cart;
                        renderCart();
                        updateCartCount();
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error loading cart:', error);
                    showNotification('Error loading cart', 'error');
                }
            <?php else: ?>
                cart = cartData;
                renderCart();
                updateCartCount();
            <?php endif; ?>
        }
        

        
        async function updateQuantity(id, change) {
            <?php if (isset($_SESSION['user_id'])): ?>
                const item = cart.find(item => item.id === id);
                if (item) {
                    const newQuantity = item.quantity + change;
                    
                    if (newQuantity <= 0) {
                        await removeItem(id);
                        return;
                    }
                    
                    try {
                        const response = await fetch('api/cart.php', {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                product_id: id,
                                quantity: newQuantity
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            item.quantity = newQuantity;
                            renderCart();
                            
                            // Update cart count with a small delay to ensure proper update
                            setTimeout(() => {
                                updateCartCount();
                            }, 100);
                            
                            // Emit quantity change event
                            syncQuantity(id, newQuantity, 'cart');
                            
                            // Emit cart change event
                            const cartEvent = new CustomEvent('cartChanged', {
                                detail: { 
                                    productId: id, 
                                    quantity: newQuantity, 
                                    action: 'update' 
                                }
                            });
                            document.dispatchEvent(cartEvent);
                        } else {
                            showNotification(result.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error updating quantity:', error);
                        showNotification('Error updating quantity', 'error');
                    }
                }
            <?php else: ?>
                const item = cart.find(item => item.id === id);
                if (item) {
                    item.quantity += change;
                    if (item.quantity <= 0) {
                        cart = cart.filter(item => item.id !== id);
                        // Emit remove event
                        const cartEvent = new CustomEvent('cartChanged', {
                            detail: { 
                                productId: id, 
                                quantity: 0, 
                                action: 'remove' 
                            }
                        });
                        document.dispatchEvent(cartEvent);
                    } else {
                        // Emit quantity change event
                        syncQuantity(id, item.quantity, 'cart');
                        
                        // Emit cart change event
                        const cartEvent = new CustomEvent('cartChanged', {
                            detail: { 
                                productId: id, 
                                quantity: item.quantity, 
                                action: 'update' 
                            }
                        });
                        document.dispatchEvent(cartEvent);
                    }
                    localStorage.setItem('cart', JSON.stringify(cart));
                    renderCart();
                    
                    // Update cart count with a small delay to ensure proper update
                    setTimeout(() => {
                        updateCartCount();
                    }, 100);
                }
            <?php endif; ?>
        }
        
        async function removeItem(id) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/cart.php', {
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
                        // Reload cart from server to ensure data consistency
                        await loadCart();
                        
                        showNotification('Item removed from cart');
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error removing item:', error);
                    showNotification('Error removing item', 'error');
                }
            <?php else: ?>
                cart = cart.filter(item => item.id !== id);
                localStorage.setItem('cart', JSON.stringify(cart));
                renderCart();
                
                // Update cart count with a small delay to ensure proper update
                setTimeout(() => {
                    updateCartCount();
                }, 100);
            <?php endif; ?>
        }
        
        function renderCart() {
            const cartItems = document.getElementById('cart-items');
            const emptyCart = document.getElementById('empty-cart');
            
            if (cart.length === 0) {
                cartItems.innerHTML = '';
                emptyCart.classList.remove('hidden');
                return;
            }
            
            emptyCart.classList.add('hidden');
            
            const total = cart.reduce((sum, item) => {
                const price = item.sale_price || item.price;
                return sum + (price * item.quantity);
            }, 0);
            
            cartItems.innerHTML = `
                <div class="card">
                    <div class="px-6 py-4 border-b border-primary/20">
                        <h2 class="text-xl font-semibold text-primary">Cart Items</h2>
                    </div>
                    <div class="divide-y divide-primary/20">
                        ${cart.map(item => {
                            const price = item.sale_price || item.price;
                            return `
                            <div class="p-6 flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0">
                                        ${item.image_url ? 
                                            `<img src="${item.image_url}" alt="${item.name}" class="w-full h-full object-cover">` :
                                            `<div class="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm">${item.name.charAt(0)}</div>`
                                        }
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-primary">${item.name}</h3>
                                        <div class="flex items-center space-x-2">
                                            ${item.sale_price ? `
                                                <span class="text-error font-semibold">$${parseFloat(item.sale_price).toFixed(2)}</span>
                                                <span class="text-muted line-through text-sm">$${parseFloat(item.price).toFixed(2)}</span>
                                            ` : `
                                                <span class="text-muted">$${parseFloat(item.price).toFixed(2)} each</span>
                                            `}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="updateQuantity(${item.id}, -1)" class="w-8 h-8 rounded-full bg-secondary text-primary hover:bg-tertiary transition duration-200 flex items-center justify-center">
                                            -
                                        </button>
                                        <span class="w-8 text-center text-primary">${item.quantity}</span>
                                        <button onclick="updateQuantity(${item.id}, 1)" class="w-8 h-8 rounded-full bg-secondary text-primary hover:bg-tertiary transition duration-200 flex items-center justify-center">
                                            +
                                        </button>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-semibold text-primary">$${(parseFloat(price) * item.quantity).toFixed(2)}</p>
                                    </div>
                                    <button onclick="removeItem(${item.id})" class="text-error hover:text-error/80 transition duration-200">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        `;
                        }).join('')}
                    </div>
                    <div class="px-6 py-4 bg-tertiary">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-lg font-semibold text-primary">Total: $${parseFloat(total).toFixed(2)}</p>
                                <p class="text-sm text-muted">${cart.reduce((sum, item) => sum + item.quantity, 0)} items</p>
                            </div>
                            <div class="space-x-4">
                                <a href="products.php" class="btn btn-outline">
                                    Continue Shopping
                                </a>
                                <a href="checkout.php" class="btn btn-primary">
                                    Checkout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        
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
        
        
        // Initialize cart on page load
        loadCart();
    </script>
</body>
</html>
