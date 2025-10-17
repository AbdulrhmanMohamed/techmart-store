<?php
// includes/seo.php - SEO Helper Functions

/**
 * Generate SEO meta tags for pages
 */
function generateSEOTags($page_data = []) {
    $defaults = [
        'title' => 'TechMart - Your Online Shopping Destination',
        'description' => 'Discover millions of products with fast, free delivery. Shop electronics, fashion, home & garden, and more at TechMart.',
        'keywords' => 'online shopping, ecommerce, electronics, fashion, home, garden, deals, free shipping',
        'image' => '/assets/images/og-image.jpg',
        'url' => 'https://techmart.com',
        'type' => 'website',
        'site_name' => 'TechMart',
        'author' => 'TechMart Team',
        'robots' => 'index, follow',
        'canonical' => '',
        'noindex' => false
    ];
    
    $data = array_merge($defaults, $page_data);
    
    // Set canonical URL
    if (empty($data['canonical'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $data['canonical'] = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    $output = '';
    
    // Basic Meta Tags
    $output .= '<title>' . htmlspecialchars($data['title']) . '</title>' . "\n";
    $output .= '<meta name="description" content="' . htmlspecialchars($data['description']) . '">' . "\n";
    $output .= '<meta name="keywords" content="' . htmlspecialchars($data['keywords']) . '">' . "\n";
    $output .= '<meta name="author" content="' . htmlspecialchars($data['author']) . '">' . "\n";
    
    // Robots
    if ($data['noindex']) {
        $output .= '<meta name="robots" content="noindex, nofollow">' . "\n";
    } else {
        $output .= '<meta name="robots" content="' . htmlspecialchars($data['robots']) . '">' . "\n";
    }
    
    // Canonical URL
    $output .= '<link rel="canonical" href="' . htmlspecialchars($data['canonical']) . '">' . "\n";
    
    // Open Graph Tags
    $output .= '<meta property="og:title" content="' . htmlspecialchars($data['title']) . '">' . "\n";
    $output .= '<meta property="og:description" content="' . htmlspecialchars($data['description']) . '">' . "\n";
    $output .= '<meta property="og:image" content="' . htmlspecialchars($data['url'] . $data['image']) . '">' . "\n";
    $output .= '<meta property="og:url" content="' . htmlspecialchars($data['canonical']) . '">' . "\n";
    $output .= '<meta property="og:type" content="' . htmlspecialchars($data['type']) . '">' . "\n";
    $output .= '<meta property="og:site_name" content="' . htmlspecialchars($data['site_name']) . '">' . "\n";
    
    // Twitter Card Tags
    $output .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $output .= '<meta name="twitter:title" content="' . htmlspecialchars($data['title']) . '">' . "\n";
    $output .= '<meta name="twitter:description" content="' . htmlspecialchars($data['description']) . '">' . "\n";
    $output .= '<meta name="twitter:image" content="' . htmlspecialchars($data['url'] . $data['image']) . '">' . "\n";
    
    // Additional Meta Tags
    $output .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    $output .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
    $output .= '<meta name="format-detection" content="telephone=no">' . "\n";
    $output .= '<meta name="theme-color" content="#2563eb">' . "\n";
    
    // Favicon
    $output .= '<link rel="icon" type="image/x-icon" href="/assets/images/favicon/favicon.ico">' . "\n";
    $output .= '<link rel="icon" type="image/svg+xml" href="/assets/images/favicon/favicon.svg">' . "\n";
    $output .= '<link rel="apple-touch-icon" href="/assets/images/favicon/apple-touch-icon.png">' . "\n";
    $output .= '<link rel="manifest" href="/assets/images/favicon/site.webmanifest">' . "\n";
    
    return $output;
}

/**
 * Generate structured data for products
 */
function generateProductStructuredData($product) {
    $structured_data = [
        "@context" => "https://schema.org/",
        "@type" => "Product",
        "name" => $product['name'],
        "description" => $product['description'],
        "image" => $product['image_url'] ?: '/assets/images/placeholder.jpg',
        "brand" => [
            "@type" => "Brand",
            "name" => $product['brand_name'] ?? 'TechMart'
        ],
        "offers" => [
            "@type" => "Offer",
            "price" => $product['sale_price'] ?: $product['price'],
            "priceCurrency" => "USD",
            "availability" => $product['in_stock'] ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
            "seller" => [
                "@type" => "Organization",
                "name" => "TechMart"
            ]
        ],
        "aggregateRating" => [
            "@type" => "AggregateRating",
            "ratingValue" => $product['rating'],
            "reviewCount" => $product['review_count']
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate structured data for organization
 */
function generateOrganizationStructuredData() {
    $structured_data = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => "TechMart",
        "url" => "https://techmart.com",
        "logo" => "https://techmart.com/assets/images/logo.png",
        "description" => "Your one-stop shop for quality products at great prices",
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => "123 E-commerce St, Suite 400",
            "addressLocality" => "Tech City",
            "addressRegion" => "TX",
            "postalCode" => "78701",
            "addressCountry" => "US"
        ],
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => "+1-555-123-4567",
            "contactType" => "customer service",
            "email" => "info@phpstore.com"
        ],
        "sameAs" => [
            "https://twitter.com/phpstore",
            "https://facebook.com/phpstore",
            "https://instagram.com/phpstore"
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate breadcrumb structured data
 */
function generateBreadcrumbStructuredData($breadcrumbs) {
    $structured_data = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => []
    ];
    
    $position = 1;
    foreach ($breadcrumbs as $breadcrumb) {
        $structured_data["itemListElement"][] = [
            "@type" => "ListItem",
            "position" => $position,
            "name" => $breadcrumb['name'],
            "item" => $breadcrumb['url']
        ];
        $position++;
    }
    
    return '<script type="application/ld+json">' . json_encode($structured_data, JSON_PRETTY_PRINT) . '</script>';
}
?>
