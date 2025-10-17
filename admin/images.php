<?php
session_start();
require_once '../config/database_auto.php';
require_once '../config/theme.php';
require_once '../includes/seo.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php?redirect=admin/images');
    exit;
}

// Get uploaded images
$upload_dir = '../assets/images/uploads/';
$images = [];

if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && !is_dir($upload_dir . $file)) {
            $file_path = $upload_dir . $file;
            $file_size = filesize($file_path);
            $file_time = filemtime($file_path);
            
            $images[] = [
                'name' => $file,
                'url' => '/assets/images/uploads/' . $file,
                'size' => $file_size,
                'modified' => $file_time
            ];
        }
    }
    
    // Sort by modification time (newest first)
    usort($images, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $image_name = $_POST['image_name'];
    $image_path = $upload_dir . $image_name;
    
    if (file_exists($image_path)) {
        unlink($image_path);
        $success_message = "Image deleted successfully!";
        // Refresh the page to update the list
        header('Location: images.php');
        exit;
    } else {
        $error_message = "Image not found!";
    }
}

// SEO Data
$seo_data = [
    'title' => 'Image Management - TechMart Admin',
    'description' => 'Manage uploaded images in TechMart e-commerce store',
    'keywords' => 'admin, images, management, uploads',
    'image' => '/assets/images/og-admin.jpg',
    'type' => 'website',
    'noindex' => true
];
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <?php echo generateSEOTags($seo_data); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <script src="../assets/js/quantity-sync.js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
        }
        .admin-sidebar {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 40;
        }
        .main-content {
            margin-left: 16rem; /* 256px = w-64 */
        }
    </style>
</head>
<body class="bg-gray-100 transition-colors duration-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="admin-sidebar w-64 text-white shadow-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold mb-8">TechMart Admin</h1>
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                    <a href="products.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <span>Products</span>
                    </a>
                    <a href="orders.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <span>Orders</span>
                    </a>
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        <span>Users</span>
                    </a>
                    <a href="categories.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <span>Categories</span>
                    </a>
                    <a href="analytics.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <span>Analytics</span>
                    </a>
                    <a href="images.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-white/20 text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Images</span>
                    </a>
                    <a href="../index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-white/10 text-white transition-colors duration-300 mt-8">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        <span>Back to Store</span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-white shadow-sm border-b border-primary/20">
                <div class="px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-primary">Image Management</h2>
                        <div class="text-sm text-muted">
                            <?php echo count($images); ?> image(s) uploaded
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-6">
                <?php if (isset($success_message)): ?>
                    <div class="mb-6 p-4 bg-success/20 text-success rounded-lg">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="mb-6 p-4 bg-error/20 text-error rounded-lg">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Images Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php if (empty($images)): ?>
                        <div class="col-span-full text-center py-12">
                            <svg class="w-16 h-16 text-muted mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-muted mb-2">No images uploaded yet</h3>
                            <p class="text-muted">Upload images from the Products or Categories pages</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($images as $image): ?>
                            <div class="card p-4">
                                <div class="relative group">
                                    <img src="<?php echo htmlspecialchars($image['url']); ?>" 
                                         alt="<?php echo htmlspecialchars($image['name']); ?>" 
                                         class="w-full h-48 object-cover rounded-lg mb-3">
                                    
                                    <!-- Image Actions -->
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <div class="flex space-x-1">
                                            <button onclick="copyImageUrl('<?php echo htmlspecialchars($image['url']); ?>')" 
                                                    class="p-2 bg-white/90 text-primary rounded-lg hover:bg-white shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                            <button onclick="deleteImage('<?php echo htmlspecialchars($image['name']); ?>')" 
                                                    class="p-2 bg-white/90 text-error rounded-lg hover:bg-white shadow-sm">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <h3 class="font-medium text-primary truncate"><?php echo htmlspecialchars($image['name']); ?></h3>
                                    <p class="text-sm text-muted"><?php echo number_format($image['size'] / 1024, 1); ?> KB</p>
                                    <p class="text-xs text-muted"><?php echo date('M j, Y H:i', $image['modified']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-primary mb-4">Delete Image</h3>
                <p class="text-muted mb-6">Are you sure you want to delete this image? This action cannot be undone.</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeleteModal()" class="btn btn-outline">Cancel</button>
                    <button onclick="confirmDelete()" class="btn btn-error">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let imageToDelete = '';

        function copyImageUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                // Show success message
                const button = event.target.closest('button');
                const originalContent = button.innerHTML;
                button.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                button.classList.add('text-success');
                
                setTimeout(() => {
                    button.innerHTML = originalContent;
                    button.classList.remove('text-success');
                }, 2000);
            }).catch(err => {
                alert('Failed to copy URL');
            });
        }

        function deleteImage(imageName) {
            imageToDelete = imageName;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            imageToDelete = '';
        }

        function confirmDelete() {
            if (imageToDelete) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_image" value="1">
                    <input type="hidden" name="image_name" value="${imageToDelete}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>



