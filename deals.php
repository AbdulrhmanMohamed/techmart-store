<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Get total count of deals
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE status = 'active' AND sale_price IS NOT NULL");
$count_stmt->execute();
$total_deals = $count_stmt->fetchColumn();
$total_pages = ceil($total_deals / $per_page);

// Get products with sale prices (paginated)
$stmt = $pdo->prepare("SELECT p.*, b.name as brand_name, c.name as category_name 
                       FROM products p 
                       LEFT JOIN brands b ON p.brand_id = b.id 
                       LEFT JOIN categories c ON p.category_id = c.id 
                       WHERE p.status = 'active' AND p.sale_price IS NOT NULL 
                       ORDER BY ((p.price - p.sale_price) / p.price) DESC, p.created_at DESC
                       LIMIT " . (int)$per_page . " OFFSET " . (int)$offset);
$stmt->execute();
$deals = $stmt->fetchAll();

// SEO Data for deals page
$seo_data = [
    'title' => 'Today\'s Deals - Up to 70% Off | TechMart',
    'description' => 'Discover amazing deals and discounts at TechMart. Save up to 70% on top products with limited-time offers. Don\'t miss out on these incredible savings!',
    'keywords' => 'deals, discounts, sales, offers, savings, limited time, TechMart, today\'s deals',
    'image' => '/assets/images/og-deals.jpg',
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
        .deal-badge {
            background: linear-gradient(45deg, var(--error), var(--warning));
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen">
        <!-- Page Header -->
        <section class="text-white py-16" style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-4">Today's Deals</h1>
                <p class="text-xl md:text-2xl mb-8 opacity-90">Limited time offers - Don't miss out!</p>
                <div class="flex items-center justify-center space-x-8 text-lg">
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Ends in 23:59:59</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <span>Up to 70% off</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Deal -->
        <?php if (!empty($deals)): ?>
            <?php $featured_deal = $deals[0]; ?>
            <section class="py-16 bg-primary transition-colors duration-300" style="background-color: var(--bg-primary);">
                <div class="container mx-auto px-4">
                    <div class="card p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-3xl font-bold text-primary">Deal of the Day</h2>
                            <div class="deal-badge text-white px-4 py-2 rounded-full font-bold">
                                <?php echo round((($featured_deal['price'] - $featured_deal['sale_price']) / $featured_deal['price']) * 100); ?>% OFF
                            </div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                            <div>
                                <h3 class="text-2xl font-bold text-primary mb-4"><?php echo htmlspecialchars($featured_deal['name']); ?></h3>
                                <p class="text-secondary mb-6"><?php echo htmlspecialchars($featured_deal['description']); ?></p>
                                <div class="flex items-center space-x-4 mb-6">
                                    <span class="text-4xl font-bold text-error">$<?php echo number_format($featured_deal['sale_price'], 2); ?></span>
                                    <span class="text-2xl text-muted line-through">$<?php echo number_format($featured_deal['price'], 2); ?></span>
                                    <span class="bg-error/10 text-error px-3 py-1 rounded-full text-sm font-medium">
                                        Save $<?php echo number_format($featured_deal['price'] - $featured_deal['sale_price'], 2); ?>
                                    </span>
                                </div>
                                <button onclick="addToCart(<?php echo $featured_deal['id']; ?>, '<?php echo htmlspecialchars($featured_deal['name']); ?>', <?php echo $featured_deal['sale_price']; ?>)" 
                                        class="bg-primary text-white px-8 py-3 rounded-lg font-semibold hover:opacity-90 transition-all duration-300 transform hover:scale-105"
                                        style="background-color: var(--primary-blue);">
                                    Shop Now
                                </button>
                            </div>
                            <div class="w-full h-64 bg-tertiary flex items-center justify-center overflow-hidden rounded-lg">
                                <?php if ($featured_deal['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($featured_deal['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($featured_deal['name']); ?>" 
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
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <!-- All Deals -->
        <section class="py-16 bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-3xl font-bold text-primary">All Deals</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-secondary">Sort by:</span>
                        <select class="input px-4 py-2">
                            <option>Discount %</option>
                            <option>Price Low to High</option>
                            <option>Price High to Low</option>
                            <option>Newest</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($deals)): ?>
                    <div class="text-center py-12">
                        <svg class="w-24 h-24 mx-auto text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-primary mb-2">No deals available</h3>
                        <p class="text-secondary">Check back later for amazing deals!</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php foreach ($deals as $deal): ?>
                            <?php $discount = round((($deal['price'] - $deal['sale_price']) / $deal['price']) * 100); ?>
                            <div class="card relative">
                                <div class="absolute top-2 left-2 deal-badge text-white px-2 py-1 rounded text-sm font-bold z-10">
                                    -<?php echo $discount; ?>%
                                </div>
                                <div class="w-full h-48 bg-tertiary flex items-center justify-center overflow-hidden">
                                    <?php if ($deal['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($deal['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($deal['name']); ?>" 
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
                                    <h3 class="text-lg font-semibold mb-2 text-primary line-clamp-2"><?php echo htmlspecialchars($deal['name']); ?></h3>
                                    <?php if ($deal['brand_name']): ?>
                                        <p class="text-sm text-muted mb-2">by <?php echo htmlspecialchars($deal['brand_name']); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center mb-2">
                                        <div class="flex text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg class="w-4 h-4 <?php echo $i <= $deal['rating'] ? 'text-warning' : 'text-light'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="text-sm text-muted ml-2">(<?php echo $deal['review_count']; ?>)</span>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2 mb-3">
                                        <span class="text-2xl font-bold text-error">$<?php echo number_format($deal['sale_price'], 2); ?></span>
                                        <span class="text-lg text-muted line-through">$<?php echo number_format($deal['price'], 2); ?></span>
                                    </div>
                                    
                                    <div class="text-sm text-success mb-3 font-medium">
                                        You save $<?php echo number_format($deal['price'] - $deal['sale_price'], 2); ?>
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <button onclick="addToCart(<?php echo $deal['id']; ?>, '<?php echo htmlspecialchars($deal['name']); ?>', <?php echo $deal['sale_price']; ?>)" 
                                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-lg font-medium hover:opacity-90 transition-all duration-300 transform hover:scale-105"
                                                style="background-color: var(--primary-blue);">
                                            Add to Cart
                                        </button>
                                        <button onclick="addToWishlist(<?php echo $deal['id']; ?>, '<?php echo htmlspecialchars($deal['name']); ?>', <?php echo $deal['sale_price']; ?>, <?php echo $deal['rating']; ?>, <?php echo $deal['review_count']; ?>)" 
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
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="flex justify-center mt-12">
                        <nav class="flex items-center space-x-2" aria-label="Pagination">
                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" 
                                   class="px-3 py-2 rounded-lg bg-tertiary text-primary hover:bg-hover-gray transition-colors duration-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </a>
                            <?php else: ?>
                                <span class="px-3 py-2 rounded-lg bg-tertiary text-muted cursor-not-allowed">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </span>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            if ($start_page > 1): ?>
                                <a href="?page=1" class="px-3 py-2 rounded-lg bg-tertiary text-primary hover:bg-hover-gray transition-colors duration-300">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span class="px-3 py-2 text-muted">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="px-3 py-2 rounded-lg bg-primary text-white font-semibold" style="background-color: var(--primary-blue);"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>" class="px-3 py-2 rounded-lg bg-tertiary text-primary hover:bg-hover-gray transition-colors duration-300"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="px-3 py-2 text-muted">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $total_pages; ?>" class="px-3 py-2 rounded-lg bg-tertiary text-primary hover:bg-hover-gray transition-colors duration-300"><?php echo $total_pages; ?></a>
                            <?php endif; ?>

                            <!-- Next Button -->
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" 
                                   class="px-3 py-2 rounded-lg bg-tertiary text-primary hover:bg-hover-gray transition-colors duration-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            <?php else: ?>
                                <span class="px-3 py-2 rounded-lg bg-tertiary text-muted cursor-not-allowed">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </nav>
                    </div>
                    
                    <!-- Pagination Info -->
                    <div class="text-center mt-4">
                        <p class="text-muted">
                            Showing <?php echo (($page - 1) * $per_page) + 1; ?>-<?php echo min($page * $per_page, $total_deals); ?> of <?php echo $total_deals; ?> deals
                        </p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Newsletter Signup -->
        <section class="py-16 text-white" style="background-color: var(--primary-blue);">
            <div class="container mx-auto px-4 text-center">
                <h2 class="text-3xl font-bold mb-4">Never Miss a Deal!</h2>
                <p class="text-xl mb-8 opacity-90">Get exclusive deals and offers delivered to your inbox</p>
                <form class="max-w-md mx-auto flex">
                    <input type="email" placeholder="Enter your email" 
                           class="flex-1 px-4 py-3 rounded-l-lg text-primary focus:outline-none focus:ring-2 focus:ring-white/50"
                           style="background-color: var(--white); color: var(--text-primary);">
                    <button type="submit" class="px-6 py-3 rounded-r-lg font-semibold transition-colors duration-300 hover:opacity-90"
                            style="background-color: var(--primary-orange);">
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
        
        function addToCart(id, name, price) {
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
        }
        
        async function addToWishlist(id, name, price, rating, reviewCount) {
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
        
        function updateCartCount() {
            const cartCount = document.getElementById('cart-count');
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            
            if (totalItems > 0) {
                cartCount.textContent = totalItems;
                cartCount.classList.remove('hidden');
            } else {
                cartCount.classList.add('hidden');
            }
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
        
        // Initialize counts
        updateCartCount();
        updateWishlistCount();
    </script>
</body>
</html>
