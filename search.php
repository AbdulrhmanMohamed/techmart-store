<?php
session_start();
require_once 'config/database_auto.php';
require_once 'includes/functions.php';
require_once 'config/theme.php';
require_once 'includes/seo.php';

// Get search query and filters
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$brand = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'relevance';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;

// Build search query
$where_conditions = ["p.status = 'active'"];
$params = [];

if (!empty($query)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $search_term = "%$query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category;
}

if ($brand > 0) {
    $where_conditions[] = "p.brand_id = ?";
    $params[] = $brand;
}

if ($min_price > 0) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $max_price;
}

// Build sorting
$order_by = "p.featured DESC, p.rating DESC, p.review_count DESC";
switch ($sort) {
    case 'price_low':
        $order_by = "p.price ASC";
        break;
    case 'price_high':
        $order_by = "p.price DESC";
        break;
    case 'rating':
        $order_by = "p.rating DESC, p.review_count DESC";
        break;
    case 'newest':
        $order_by = "p.created_at DESC";
        break;
    case 'name':
        $order_by = "p.name ASC";
        break;
}

// Use JSON-based search
$searchFilters = [];
if ($query) {
    $searchFilters['search'] = $query;
}
if ($category) {
    $searchFilters['category_id'] = $category;
}
if ($brand) {
    $searchFilters['brand_id'] = $brand;
}
if ($min_price) {
    $searchFilters['min_price'] = $min_price;
}
if ($max_price) {
    $searchFilters['max_price'] = $max_price;
}

// Get all products and apply filters
$all_products = searchProducts($searchFilters);

// Apply sorting
if ($sort === 'price_low') {
    usort($all_products, function($a, $b) { return $a['price'] <=> $b['price']; });
} elseif ($sort === 'price_high') {
    usort($all_products, function($a, $b) { return $b['price'] <=> $a['price']; });
} elseif ($sort === 'name') {
    usort($all_products, function($a, $b) { return strcmp($a['name'], $b['name']); });
}

// Calculate pagination
$total_products = count($all_products);
$total_pages = ceil($total_products / $per_page);
$offset = ($page - 1) * $per_page;

// Get products for current page
$products = array_slice($all_products, $offset, $per_page);

// Add brand and category names to products
foreach ($products as &$product) {
    if ($product['brand_id']) {
        $brand = getBrandById($product['brand_id']);
        $product['brand_name'] = $brand ? $brand['name'] : '';
    }
    if ($product['category_id']) {
        $category = getCategoryById($product['category_id']);
        $product['category_name'] = $category ? $category['name'] : '';
    }
}

// Get categories and brands for filter
$categories = getAllCategories();
$brands = getAllBrands();

// SEO Data for search page
$search_title = !empty($query) ? "Search Results for \"$query\"" : "Search Products";
$search_description = !empty($query) 
    ? "Find the best results for \"$query\" at TechMart. Browse " . count($products) . " products with filters and sorting options."
    : "Search and discover products at TechMart. Use filters to find exactly what you're looking for.";

$seo_data = [
    'title' => $search_title . ' | TechMart',
    'description' => $search_description,
    'keywords' => $query . ', search, products, ' . ($query ? 'results, ' : '') . 'online shopping, ecommerce, filters',
    'image' => '/assets/images/og-search.jpg',
    'type' => 'website',
    'noindex' => !empty($query) ? false : true // Don't index empty search pages
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
    
    <main class="min-h-screen py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Filters Sidebar -->
                <div class="lg:w-1/4">
                    <div class="card p-6 sticky top-24">
                        <h3 class="text-lg font-semibold text-primary mb-4">Filters</h3>
                        
                        <!-- Search Query -->
                        <?php if (!empty($query)): ?>
                            <div class="mb-6">
                                <h4 class="font-medium text-secondary mb-2">Search Results for:</h4>
                                <p class="text-primary font-medium">"<?php echo htmlspecialchars($query); ?>"</p>
                                <p class="text-sm text-muted mt-1"><?php echo number_format($total_products); ?> results found</p>
                            </div>
                        <?php endif; ?>

                        <!-- Categories Filter -->
                        <div class="mb-6">
                            <h4 class="font-medium text-secondary mb-3">Categories</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="category" value="0" <?php echo $category == 0 ? 'checked' : ''; ?> 
                                           class="mr-2" onchange="applyFilters()">
                                    <span class="text-sm text-muted">All Categories</span>
                                </label>
                                <?php foreach ($categories as $cat): ?>
                                    <label class="flex items-center">
                                        <input type="radio" name="category" value="<?php echo $cat['id']; ?>" 
                                               <?php echo $category == $cat['id'] ? 'checked' : ''; ?> 
                                               class="mr-2" onchange="applyFilters()">
                                        <span class="text-sm text-muted"><?php echo htmlspecialchars($cat['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Brands Filter -->
                        <div class="mb-6">
                            <h4 class="font-medium text-secondary mb-3">Brands</h4>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="brand" value="0" <?php echo $brand == 0 ? 'checked' : ''; ?> 
                                           class="mr-2" onchange="applyFilters()">
                                    <span class="text-sm text-muted">All Brands</span>
                                </label>
                                <?php foreach ($brands as $brand_item): ?>
                                    <label class="flex items-center">
                                        <input type="radio" name="brand" value="<?php echo $brand_item['id']; ?>" 
                                               <?php echo $brand == $brand_item['id'] ? 'checked' : ''; ?> 
                                               class="mr-2" onchange="applyFilters()">
                                        <span class="text-sm text-muted"><?php echo htmlspecialchars($brand_item['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Price Range Filter -->
                        <div class="mb-6">
                            <h4 class="font-medium text-secondary mb-3">Price Range</h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm text-muted mb-1">Min Price</label>
                                    <input type="number" name="min_price" value="<?php echo $min_price; ?>" 
                                           class="input w-full" 
                                           placeholder="0" onchange="applyFilters()">
                                </div>
                                <div>
                                    <label class="block text-sm text-muted mb-1">Max Price</label>
                                    <input type="number" name="max_price" value="<?php echo $max_price; ?>" 
                                           class="input w-full" 
                                           placeholder="1000" onchange="applyFilters()">
                                </div>
                            </div>
                        </div>

                        <!-- Clear Filters -->
                        <button onclick="clearFilters()" class="btn-secondary w-full">
                            Clear All Filters
                        </button>
                    </div>
                </div>

                <!-- Results -->
                <div class="lg:w-3/4">
                    <!-- Results Header -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-primary">
                                <?php if (!empty($query)): ?>
                                    Search Results for "<?php echo htmlspecialchars($query); ?>"
                                <?php else: ?>
                                    All Products
                                <?php endif; ?>
                            </h1>
                            <p class="text-muted mt-1">
                                Showing <?php echo number_format($offset + 1); ?>-<?php echo number_format(min($offset + $per_page, $total_products)); ?> of <?php echo number_format($total_products); ?> results
                            </p>
                        </div>
                        
                        <!-- Sort Dropdown -->
                        <div class="mt-4 sm:mt-0">
                            <select name="sort" onchange="applyFilters()" class="select">
                                <option value="relevance" <?php echo $sort == 'relevance' ? 'selected' : ''; ?>>Sort by Relevance</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="rating" <?php echo $sort == 'rating' ? 'selected' : ''; ?>>Customer Rating</option>
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                            </select>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <?php if (empty($products)): ?>
                        <div class="text-center py-12">
                            <svg class="w-24 h-24 mx-auto text-muted mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-secondary mb-2">No products found</h3>
                            <p class="text-muted">Try adjusting your search or filter criteria</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($products as $product): ?>
                                <div class="card">
                                    <div class="w-full h-48 overflow-hidden">
                                        <?php if ($product['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                        <?php else: ?>
                                            <div class="w-full h-full product-image flex items-center justify-center text-white font-bold text-sm">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="text-lg font-semibold mb-2 text-primary line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <?php if ($product['brand_name']): ?>
                                            <p class="text-sm text-muted mb-2">by <?php echo htmlspecialchars($product['brand_name']); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="flex items-center mb-2">
                                            <div class="flex text-yellow-400">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <svg class="w-4 h-4 <?php echo $i <= $product['rating'] ? 'text-yellow-400' : 'text-muted'; ?>" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                                    </svg>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="text-sm text-muted ml-2">(<?php echo $product['review_count']; ?>)</span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2 mb-3">
                                            <?php if ($product['sale_price']): ?>
                                                <span class="text-2xl font-bold text-red-600">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                                <span class="text-lg text-muted line-through">$<?php echo number_format($product['price'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-2xl font-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex space-x-2">
                                            <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['sale_price'] ?: $product['price']; ?>)" 
                                                    class="btn-primary flex-1">
                                                Add to Cart
                                            </button>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" 
                                               class="btn-secondary">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="mt-8 flex justify-center">
                                <nav class="flex space-x-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                           class="btn-secondary">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                           class="px-3 py-2 rounded-lg <?php echo $i == $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                           class="btn-secondary">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>

        // Cart Functionality
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        
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
        
        function applyFilters() {
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'search.php';
            
            // Add search query
            if (document.querySelector('input[name="q"]')) {
                const qInput = document.createElement('input');
                qInput.type = 'hidden';
                qInput.name = 'q';
                qInput.value = document.querySelector('input[name="q"]').value;
                form.appendChild(qInput);
            }
            
            // Add filters
            const category = document.querySelector('input[name="category"]:checked');
            if (category) {
                const categoryInput = document.createElement('input');
                categoryInput.type = 'hidden';
                categoryInput.name = 'category';
                categoryInput.value = category.value;
                form.appendChild(categoryInput);
            }
            
            const brand = document.querySelector('input[name="brand"]:checked');
            if (brand) {
                const brandInput = document.createElement('input');
                brandInput.type = 'hidden';
                brandInput.name = 'brand';
                brandInput.value = brand.value;
                form.appendChild(brandInput);
            }
            
            const minPrice = document.querySelector('input[name="min_price"]');
            if (minPrice && minPrice.value) {
                const minPriceInput = document.createElement('input');
                minPriceInput.type = 'hidden';
                minPriceInput.name = 'min_price';
                minPriceInput.value = minPrice.value;
                form.appendChild(minPriceInput);
            }
            
            const maxPrice = document.querySelector('input[name="max_price"]');
            if (maxPrice && maxPrice.value) {
                const maxPriceInput = document.createElement('input');
                maxPriceInput.type = 'hidden';
                maxPriceInput.name = 'max_price';
                maxPriceInput.value = maxPrice.value;
                form.appendChild(maxPriceInput);
            }
            
            const sort = document.querySelector('select[name="sort"]');
            if (sort) {
                const sortInput = document.createElement('input');
                sortInput.type = 'hidden';
                sortInput.name = 'sort';
                sortInput.value = sort.value;
                form.appendChild(sortInput);
            }
            
            document.body.appendChild(form);
            form.submit();
        }
        
        function clearFilters() {
            window.location.href = 'search.php';
        }
        
        updateCartCount();
    </script>
</body>
</html>
