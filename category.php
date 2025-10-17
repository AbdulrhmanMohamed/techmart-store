<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';

// Get category ID from URL parameter
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Get category information
$category = getCategoryById($category_id);

if (!$category) {
    header('Location: index.php');
    exit;
}

// Get products for this category
$products = getProductsByCategory($category_id);

// Add brand and category names to products
foreach ($products as &$product) {
    if ($product['brand_id']) {
        $brand = getBrandById($product['brand_id']);
        $product['brand_name'] = $brand ? $brand['name'] : '';
    }
    $product['category_name'] = $category['name'];
}

// Get subcategories (for demo, we'll filter categories with parent_id)
$all_categories = getAllCategories();
$subcategories = array_filter($all_categories, function($cat) use ($category_id) {
    return isset($cat['parent_id']) && $cat['parent_id'] == $category_id;
});

// Get parent category if this is a subcategory
$parent_category = null;
if (isset($category['parent_id']) && $category['parent_id']) {
    $parent_category = getCategoryById($category['parent_id']);
}

// SEO Data for category page
$seo_data = [
    'title' => htmlspecialchars($category['name']) . ' - Shop ' . htmlspecialchars($category['name']) . ' Products | TechMart',
    'description' => 'Shop ' . htmlspecialchars($category['name']) . ' products at TechMart. Find the best deals and quality ' . strtolower(htmlspecialchars($category['name'])) . ' items with fast, free delivery.',
    'keywords' => htmlspecialchars($category['name']) . ', ' . strtolower(htmlspecialchars($category['name'])) . ' products, online shopping, deals, TechMart',
    'image' => '/assets/images/og-category.jpg',
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
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
    <?php include 'includes/header.php'; ?>
    
    <main class="min-h-screen">
        <!-- Breadcrumb -->
        <div class="bg-primary border-b" style="background-color: var(--bg-primary); border-color: var(--border-light);">
            <div class="container mx-auto px-4 py-4">
                <nav class="flex items-center space-x-2 text-sm">
                    <a href="index.php" class="text-primary hover:underline">Home</a>
                    <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <?php if ($parent_category): ?>
                        <a href="category.php?id=<?php echo $parent_category['id']; ?>" class="text-primary hover:underline"><?php echo htmlspecialchars($parent_category['name']); ?></a>
                        <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    <?php endif; ?>
                    <span class="text-secondary"><?php echo htmlspecialchars($category['name']); ?></span>
                </nav>
            </div>
        </div>

        <!-- Category Header -->
        <div class="py-16 text-white" style="background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-orange) 100%);">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-4"><?php echo htmlspecialchars($category['name']); ?></h1>
                <?php if ($category['description']): ?>
                    <p class="text-xl opacity-90"><?php echo htmlspecialchars($category['description']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Subcategories -->
        <?php if (!empty($subcategories)): ?>
        <div class="py-8 bg-secondary transition-colors duration-300" style="background-color: var(--bg-secondary);">
            <div class="container mx-auto px-4">
                <h2 class="text-xl font-semibold text-primary mb-6 text-center">Shop by Subcategory</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <?php foreach ($subcategories as $subcategory): ?>
                        <a href="category.php?id=<?php echo $subcategory['id']; ?>" 
                           class="card p-4 text-center hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                            <h3 class="font-medium text-primary"><?php echo htmlspecialchars($subcategory['name']); ?></h3>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <div class="py-16 bg-primary transition-colors duration-300" style="background-color: var(--bg-primary);">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-2xl font-bold text-primary">
                        Products in <?php echo htmlspecialchars($category['name']); ?>
                        <span class="text-lg font-normal text-muted">(<?php echo count($products); ?> items)</span>
                    </h2>
                    
                    <!-- Sort Options -->
                    <div class="flex items-center space-x-4">
                        <label for="sort" class="text-sm font-medium text-secondary">Sort by:</label>
                        <select id="sort" class="input px-3 py-2">
                            <option value="featured">Featured</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                            <option value="rating">Customer Rating</option>
                            <option value="newest">Newest</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-primary mb-2">No products found</h3>
                        <p class="text-secondary">There are no products in this category yet.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="card product-card" 
                                 data-price="<?php echo $product['sale_price'] ?: $product['price']; ?>" 
                                 data-rating="<?php echo $product['rating']; ?>"
                                 data-date="<?php echo strtotime($product['created_at']); ?>">
                                
                                <!-- Product Image -->
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

                                <!-- Product Info -->
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold mb-2 text-primary line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    
                                    <!-- Brand -->
                                    <?php if ($product['brand_name']): ?>
                                        <p class="text-sm text-muted mb-2">by <?php echo htmlspecialchars($product['brand_name']); ?></p>
                                    <?php endif; ?>

                                    <!-- Rating -->
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

                                    <!-- Price -->
                                    <div class="flex items-center space-x-2 mb-3">
                                        <?php if ($product['sale_price']): ?>
                                            <span class="text-2xl font-bold text-error">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                            <span class="text-lg text-muted line-through">$<?php echo number_format($product['price'], 2); ?></span>
                                        <?php else: ?>
                                            <span class="text-2xl font-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Actions -->
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
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Sorting functionality
        document.getElementById('sort').addEventListener('change', function() {
            const sortBy = this.value;
            const productsGrid = document.getElementById('products-grid');
            const products = Array.from(productsGrid.children);

            products.sort((a, b) => {
                switch(sortBy) {
                    case 'price-low':
                        return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                    case 'price-high':
                        return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                    case 'rating':
                        return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                    case 'newest':
                        return parseInt(b.dataset.date) - parseInt(a.dataset.date);
                    default: // featured
                        return 0;
                }
            });

            // Clear and re-append sorted products
            productsGrid.innerHTML = '';
            products.forEach(product => productsGrid.appendChild(product));
        });

        // Cart and Wishlist functionality
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
                        const totalItems = result.totals.items;
                        
                        if (totalItems > 0) {
                            cartCount.textContent = totalItems;
                            cartCount.classList.remove('hidden');
                        } else {
                            cartCount.classList.add('hidden');
                        }
                    }
                } catch (error) {
                    console.error('Error updating cart count:', error);
                }
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
        
        // Initialize counts on page load
        updateCartCount();
        updateWishlistCount();
    </script>
    
    <!-- Breadcrumb Structured Data -->
    <?php 
    $breadcrumbs = [
        ['name' => 'Home', 'url' => 'https://phpstore.com/'],
        ['name' => htmlspecialchars($category['name']), 'url' => 'https://phpstore.com/category.php?id=' . $category['id']]
    ];
    if ($parent_category) {
        array_splice($breadcrumbs, 1, 0, [['name' => htmlspecialchars($parent_category['name']), 'url' => 'https://phpstore.com/category.php?id=' . $parent_category['id']]]);
    }
    echo generateBreadcrumbStructuredData($breadcrumbs);
    ?>
</body>
</html>
