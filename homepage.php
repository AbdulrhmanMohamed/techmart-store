<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12; // Products per page
$offset = ($page - 1) * $per_page;

// Get total products count
try {
    $count_stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
    $total_products = $count_stmt->fetchColumn();
    $total_pages = ceil($total_products / $per_page);
} catch (PDOException $e) {
    error_log("Error counting products: " . $e->getMessage());
    $total_products = 0;
    $total_pages = 0;
}

// Get products with pagination
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE status = 'active' ORDER BY featured DESC, created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
}

// SEO Data for products page
$seo_data = [
    'title' => 'All Products - Shop Electronics, Fashion, Home & More | TechMart',
    'description' => 'Browse our complete collection of products including electronics, fashion, home & garden, computers, and more. Find the best deals and quality products at TechMart.',
    'keywords' => 'products, electronics, fashion, home, garden, computers, all products, shop online, deals, quality products',
    'image' => '/assets/images/og-products.jpg',
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
    
    <main class="min-h-screen">
        <!-- Page Header -->
        <section class="text-white py-16" style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4">Our Products</h1>
                <p class="text-xl opacity-90">Discover our complete collection of amazing products</p>
            </div>
        </section>

        <!-- Products Grid -->
        <section class="py-16 bg-primary transition-colors duration-300" style="background-color: var(--bg-primary);">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    <?php foreach ($products as $product): ?>
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
                            <div class="p-6">
                                <h3 class="text-lg font-semibold mb-2 text-primary line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="text-secondary mb-4 line-clamp-2"><?php echo htmlspecialchars($product['short_description'] ?: substr($product['description'], 0, 100)); ?></p>
                                <div class="flex items-center mb-2">
                                    <div class="flex text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg class="w-4 h-4 <?php echo $i <= $product['rating'] ? 'text-warning' : 'text-light'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-muted ml-2">(<?php echo $product['review_count']; ?>)</span>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center space-x-2">
                                        <?php if ($product['sale_price']): ?>
                                            <span class="text-2xl font-bold text-error">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                            <span class="text-lg text-muted line-through">$<?php echo number_format($product['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-2xl font-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['sale_price'] ?: $product['price']; ?>)" 
                                                class="flex-1 bg-primary text-white px-4 py-2.5 rounded-lg font-medium hover:opacity-90 transition-all duration-300 transform hover:scale-105"
                                                style="background-color: var(--primary-blue);">
                                            Add to Cart
                                        </button>
                                        <button onclick="addToWishlist(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['sale_price'] ?: $product['price']; ?>, <?php echo $product['rating']; ?>, <?php echo $product['review_count']; ?>)" 
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
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <?php if ($i == $page): ?>
                                <span class="px-4 py-2 rounded-lg text-white font-semibold" style="background-color: var(--primary-blue);">
                                    <?php echo $i; ?>
                                </span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="px-4 py-2 rounded-lg bg-tertiary text-primary hover:bg-hover-gray transition-colors duration-300">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

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
                        Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_products); ?> of <?php echo $total_products; ?> products
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('theme-toggle');
        const html = document.documentElement;
        
        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        html.classList.toggle('dark', currentTheme === 'dark');
        
        themeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            const newTheme = html.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
        });

        // Cart Functionality
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
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
            <?php endif; ?>
        }
        
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
        
        // Wishlist Functionality
        let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
        
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
                showNotification('Please log in to add items to your wishlist', 'error');
                // Optionally redirect to login page
                setTimeout(() => {
                    if (confirm('Would you like to log in to add items to your wishlist?')) {
                        window.location.href = 'login.php';
                    }
                }, 2000);
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
        
        // Initialize cart and wishlist counts on page load
        updateCartCount();
        updateWishlistCount();
    </script>
</body>
</html>
