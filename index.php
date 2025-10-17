<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';

// Get featured products
$featured_products = getFeaturedProducts();

// SEO Data for homepage
$seo_data = [
    'title' => 'TechMart - Your Online Shopping Destination | Electronics, Fashion, Home & More',
    'description' => 'Discover millions of products with fast, free delivery at TechMart. Shop electronics, fashion, home & garden, computers, and more. Best deals and customer service guaranteed.',
    'keywords' => 'online shopping, ecommerce, electronics, fashion, home, garden, computers, deals, free shipping, best prices, customer service',
    'image' => '/assets/images/og-homepage.jpg',
    'type' => 'website'
];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <?php echo generateSEOTags($seo_data); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/theme.css">
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
    
    <main>
        <!-- Hero Carousel -->
        <section class="relative text-white py-20" style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">Welcome to TechMart</h1>
                        <p class="text-xl md:text-2xl mb-8 opacity-90">Discover millions of products with fast, free delivery</p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="products.php" class="btn-primary px-8 py-3 rounded-lg font-semibold transform hover:scale-105 inline-block text-center" style="background-color: var(--white); color: var(--primary-blue);">Shop Now</a>
                            <a href="deals.php" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white transition duration-300 inline-block text-center" style="color: var(--white); border-color: var(--white);" onmouseover="this.style.color='var(--primary-blue)'; this.style.backgroundColor='var(--white)';" onmouseout="this.style.color='var(--white)'; this.style.backgroundColor='transparent';">Today's Deals</a>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8">
                            <h3 class="text-2xl font-bold mb-4">Free Shipping</h3>
                            <p class="text-lg mb-4">On orders over $50</p>
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold">Fast Delivery</p>
                                    <p class="text-sm opacity-80">Same day delivery available</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Today's Deals -->
        <section class="py-16 transition-colors duration-300" style="background-color: var(--light-gray);">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-3xl font-bold text-primary">Today's Deals</h2>
                    <a href="deals.php" class="text-primary hover:underline font-medium">See all deals</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php 
                    $deals = array_slice($featured_products, 0, 4);
                    foreach ($deals as $product): 
                        // For demo purposes, create a random discount for some products
                        $discount = ($product['id'] % 3 == 0) ? rand(10, 30) : 0;
                        $sale_price = $discount > 0 ? $product['price'] * (1 - $discount / 100) : null;
                        $review_count = rand(50, 500); // Random review count for demo
                    ?>
                        <div class="card product-card relative">
                            <?php if ($discount > 0): ?>
                                <div class="badge absolute top-2 left-2 z-10" style="background-color: var(--error);">
                                    -<?php echo $discount; ?>%
                                </div>
                            <?php endif; ?>
                            <div class="w-full h-48 bg-tertiary flex items-center justify-center overflow-hidden">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                <?php else: ?>
                                    <div class="text-muted text-center p-4">
                                        <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-sm">No Image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold mb-2 text-primary line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="flex items-center mb-2">
                                    <div class="flex text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-4 h-4 <?php echo $i <= $product['rating'] ? 'text-warning' : 'text-light'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-muted ml-2">(<?php echo $review_count; ?>)</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($sale_price): ?>
                                            <span class="text-2xl font-bold text-error">$<?php echo number_format($sale_price, 2); ?></span>
                                            <span class="text-lg text-muted line-through">$<?php echo number_format($product['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-2xl font-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $sale_price ?: $product['price']; ?>)" 
                                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-lg font-medium hover:opacity-90 transition-all duration-300 transform hover:scale-105"
                                                style="background-color: var(--primary-blue);">
                                            Add to Cart
                                        </button>
                                        <button onclick="addToWishlist(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $sale_price ?: $product['price']; ?>, <?php echo $product['rating']; ?>, <?php echo $review_count; ?>)" 
                                                class="ml-3 p-2.5 rounded-lg border-2 border-primary hover:bg-primary hover:text-white transition-all duration-300 transform hover:scale-110"
                                                style="border-color: var(--primary-blue); color: var(--primary-blue);"
                                                onmouseover="this.style.backgroundColor='var(--primary-blue)'; this.style.color='white'"
                                                onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--primary-blue)'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

            <!-- Categories Section -->
            <section class="py-16 bg-primary transition-colors duration-300" style="background-color: var(--bg-primary);">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-center mb-12 text-primary">Shop by Category</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                        <a href="category.php?id=1" class="group">
                            <div class="rounded-lg p-6 text-center text-white hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1" style="background-color: var(--primary-blue);">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <h3 class="font-semibold">Electronics</h3>
                            </div>
                        </a>
                        <a href="category.php?id=2" class="group">
                            <div class="rounded-lg p-6 text-center text-white hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1" style="background-color: var(--primary-orange);">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <h3 class="font-semibold">Computers</h3>
                            </div>
                        </a>
                        <a href="category.php?id=5" class="group">
                            <div class="rounded-lg p-6 text-center text-white hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1" style="background-color: var(--primary-blue);">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <h3 class="font-semibold">Fashion</h3>
                            </div>
                        </a>
                        <a href="category.php?id=4" class="group">
                            <div class="rounded-lg p-6 text-center text-white hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1" style="background-color: var(--primary-orange);">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                </svg>
                                <h3 class="font-semibold">Home & Kitchen</h3>
                            </div>
                        </a>
                        <a href="category.php?id=6" class="group">
                            <div class="rounded-lg p-6 text-center text-white hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1" style="background-color: var(--primary-blue);">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <h3 class="font-semibold">Sports</h3>
                            </div>
                        </a>
                        <a href="category.php?id=7" class="group">
                            <div class="rounded-lg p-6 text-center text-white hover:shadow-lg transition-all duration-300 transform group-hover:-translate-y-1" style="background-color: var(--primary-orange);">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                                <h3 class="font-semibold">Books</h3>
                            </div>
                        </a>
                    </div>
                </div>
            </section>

        <!-- Best Sellers -->
        <section class="py-16 bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-3xl font-bold text-primary">Best Sellers</h2>
                    <a href="bestsellers.php" class="text-primary hover:underline font-medium">View all</a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach (array_slice($featured_products, 0, 4) as $product): ?>
                        <div class="card product-card">
                            <div class="w-full h-48 bg-tertiary flex items-center justify-center overflow-hidden">
                                <?php if ($product['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                         class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                <?php else: ?>
                                    <div class="text-muted text-center p-4">
                                        <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-sm">No Image</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-semibold mb-2 text-primary line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <div class="flex items-center mb-2">
                                    <div class="flex text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-4 h-4 <?php echo $i <= $product['rating'] ? 'text-warning' : 'text-light'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-muted ml-2">(<?php echo $review_count; ?>)</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xl font-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)" 
                                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-lg font-medium hover:opacity-90 transition-all duration-300 transform hover:scale-105"
                                                style="background-color: var(--primary-blue);">
                                            Add to Cart
                                        </button>
                                        <button onclick="addToWishlist(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>, <?php echo $product['rating']; ?>, <?php echo $review_count; ?>)" 
                                                class="ml-3 p-2.5 rounded-lg border-2 border-primary hover:bg-primary hover:text-white transition-all duration-300 transform hover:scale-110"
                                                style="border-color: var(--primary-blue); color: var(--primary-blue);"
                                                onmouseover="this.style.backgroundColor='var(--primary-blue)'; this.style.color='white'"
                                                onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--primary-blue)'">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

            <!-- Newsletter Signup -->
            <section class="py-16 text-white" style="background-color: var(--primary-blue);">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold mb-4">Stay Updated</h2>
                    <p class="text-xl mb-8 opacity-90">Get the latest deals and product updates delivered to your inbox</p>
                    <form class="max-w-md mx-auto flex">
                        <input type="email" placeholder="Enter your email" class="input flex-1 px-4 py-3 rounded-l-lg text-primary focus:outline-none focus:ring-2 focus:ring-white/50" style="background-color: var(--white); color: var(--text-primary);">
                        <button type="submit" class="px-6 py-3 rounded-r-lg font-semibold transition-colors duration-300 hover:opacity-90" style="background-color: var(--primary-orange); color: var(--white);">
                            Subscribe
                        </button>
                    </form>
                </div>
            </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>

        // Cart and Wishlist Functionality
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
        
        // Check if item is in cart
        async function isInCart(productId) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/cart.php');
                    const result = await response.json();
                    if (result.success) {
                        return result.cart.some(item => item.id == productId);
                    }
                } catch (error) {
                    console.error('Error checking cart:', error);
                }
                return false;
            <?php else: ?>
                return cart.some(item => item.id == productId);
            <?php endif; ?>
        }
        
        // Check if item is in wishlist
        async function isInWishlist(productId) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/wishlist.php');
                    const result = await response.json();
                    if (result.success) {
                        return result.wishlist.some(item => item.id == productId);
                    }
                } catch (error) {
                    console.error('Error checking wishlist:', error);
                }
                return false;
            <?php else: ?>
                return false; // Non-logged-in users can't have wishlist items
            <?php endif; ?>
        }
        
        // Update button states
        async function updateButtonStates() {
            const cartButtons = document.querySelectorAll('[onclick*="addToCart"]');
            const wishlistButtons = document.querySelectorAll('[onclick*="addToWishlist"]');
            
            for (let button of cartButtons) {
                const onclick = button.getAttribute('onclick');
                const match = onclick.match(/addToCart\((\d+),/);
                if (match) {
                    const productId = parseInt(match[1]);
                    const inCart = await isInCart(productId);
                    
                    if (inCart) {
                        button.disabled = true;
                        button.textContent = 'In Cart';
                        button.classList.add('opacity-50', 'cursor-not-allowed');
                        button.classList.remove('hover:opacity-90', 'hover:scale-105');
                    } else {
                        button.disabled = false;
                        button.textContent = 'Add to Cart';
                        button.classList.remove('opacity-50', 'cursor-not-allowed');
                        button.classList.add('hover:opacity-90', 'hover:scale-105');
                    }
                }
            }
            
            for (let button of wishlistButtons) {
                const onclick = button.getAttribute('onclick');
                const match = onclick.match(/addToWishlist\((\d+),/);
                if (match) {
                    const productId = parseInt(match[1]);
                    const inWishlist = await isInWishlist(productId);
                    
                    if (inWishlist) {
                        button.disabled = true;
                        button.classList.add('opacity-50', 'cursor-not-allowed');
                        button.classList.remove('hover:bg-primary', 'hover:text-white', 'hover:scale-110');
                        // Change heart icon to filled
                        const svg = button.querySelector('svg');
                        if (svg) {
                            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" fill="currentColor"></path>';
                        }
                    } else {
                        button.disabled = false;
                        button.classList.remove('opacity-50', 'cursor-not-allowed');
                        button.classList.add('hover:bg-primary', 'hover:text-white', 'hover:scale-110');
                        // Change heart icon to outline
                        const svg = button.querySelector('svg');
                        if (svg) {
                            svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>';
                        }
                    }
                }
            }
        }
        
        async function addToCart(id, name, price) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/cart.php', {
                        method: 'POST',
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
                        showNotification(`${name} added to cart!`);
                        
                        // Update cart count with a small delay to ensure proper update
                        setTimeout(() => {
                            updateCartCount();
                        }, 100);
                        updateButtonStates();
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error adding to cart:', error);
                    showNotification('Error adding to cart', 'error');
                }
            <?php else: ?>
                // Fallback to localStorage for non-logged-in users
                const existingItem = cart.find(item => item.id === id);
                
                if (existingItem) {
                    existingItem.quantity += 1;
                } else {
                    cart.push({
                        id: id,
                        name: name,
                        price: price,
                        quantity: 1
                    });
                }
                
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartCount();
                showNotification(`${name} added to cart!`);
                updateButtonStates();
            <?php endif; ?>
        }
        
        async function addToWishlist(id, name, price, rating = 0, reviewCount = 0) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/wishlist.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            product_id: id
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        showNotification(`${name} added to wishlist!`);
                        updateWishlistCount();
                        updateButtonStates();
                    } else {
                        showNotification(result.message, 'error');
                    }
                } catch (error) {
                    console.error('Error adding to wishlist:', error);
                    showNotification('Error adding to wishlist', 'error');
                }
            <?php else: ?>
                showNotification('Please login to add items to your wishlist', 'error');
            <?php endif; ?>
        }
        
        async function updateCartCount() {
            <?php if (isset($_SESSION['user_id'])): ?>
                // For logged-in users, fetch cart from database
                try {
                    const response = await fetch('api/cart.php');
                    const result = await response.json();
                    
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
                } catch (error) {
                    console.error('Error updating cart count:', error);
                }
            <?php else: ?>
                // For non-logged-in users, use localStorage
                const cart = JSON.parse(localStorage.getItem('cart')) || [];
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
        
        async function updateWishlistCount() {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('api/wishlist.php');
                    const result = await response.json();
                    
                    if (result.success) {
                        const wishlistCount = document.getElementById('wishlist-count');
                        if (wishlistCount) {
                            if (result.wishlist.length > 0) {
                                wishlistCount.textContent = result.wishlist.length;
                                wishlistCount.classList.remove('hidden');
                            } else {
                                wishlistCount.classList.add('hidden');
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
            <?php endif; ?>
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
        
        // Initialize counts and button states on page load
        updateCartCount();
        updateWishlistCount();
        updateButtonStates();
    </script>
    
    <!-- Structured Data -->
    <?php echo generateOrganizationStructuredData(); ?>
    
    <!-- Breadcrumb Structured Data -->
    <?php 
    $breadcrumbs = [
        ['name' => 'Home', 'url' => 'https://phpstore.com/']
    ];
    echo generateBreadcrumbStructuredData($breadcrumbs);
    ?>
</body>
</html>
