<?php
require 'admindb.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['video_submit'])) {
            $updateFields = [
                'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
                'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
                'video_url' => filter_input(INPUT_POST, 'video_url', FILTER_SANITIZE_STRING),
            ];
            
            // Handle thumbnail file upload if provided
            if (!empty($_FILES['thumbnail']['name'])) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFile)) {
                    $updateFields['thumbnail'] = $targetFile;
                }
            } else {
                $updateFields['thumbnail'] = filter_input(INPUT_POST, 'thumbnail_url', FILTER_SANITIZE_STRING);
            }
            
            $updateResult = $db2->video_cards->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($_POST['id'])],
                ['$set' => $updateFields]
            );
        } elseif (isset($_POST['feature_submit'])) {
            $updateResult = $db2->key_features->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($_POST['id'])],
                ['$set' => [
                    'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
                    'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
                    'icon' => filter_input(INPUT_POST, 'icon', FILTER_SANITIZE_STRING),
                    'icon_color' => filter_input(INPUT_POST, 'icon_color', FILTER_SANITIZE_STRING),
                    'bg_color' => filter_input(INPUT_POST, 'bg_color', FILTER_SANITIZE_STRING)
                ]]
            );
        } elseif (isset($_POST['add_video'])) {
            // Handle thumbnail file upload
            $thumbnailPath = '';
            if (!empty($_FILES['thumbnail']['name'])) {
                $uploadDir = 'uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $fileName = uniqid() . '_' . basename($_FILES['thumbnail']['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $targetFile)) {
                    $thumbnailPath = $targetFile;
                }
            }
            
            $db2->video_cards->insertOne([
                'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
                'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
                'video_url' => filter_input(INPUT_POST, 'video_url', FILTER_SANITIZE_STRING),
                'thumbnail' => $thumbnailPath
            ]);
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } elseif (isset($_POST['add_feature'])) {
            $db2->key_features->insertOne([
                'title' => filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING),
                'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
                'icon' => filter_input(INPUT_POST, 'icon', FILTER_SANITIZE_STRING),
                'icon_color' => filter_input(INPUT_POST, 'icon_color', FILTER_SANITIZE_STRING),
                'bg_color' => filter_input(INPUT_POST, 'bg_color', FILTER_SANITIZE_STRING)
            ]);
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        } elseif (isset($_POST['delete'])) {
            $collectionName = $_POST['collection'];
            $db2->$collectionName->deleteOne(
                ['_id' => new MongoDB\BSON\ObjectId($_POST['id'])]
            );
        }
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
}

// Fetch data
$videoCards = $db2->video_cards->find();
$keyFeatures = $db2->key_features->find();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-card { animation: slideIn 0.3s ease-out; }
        .custom-scroll::-webkit-scrollbar { width: 8px; }
        .custom-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        .custom-scroll::-webkit-scrollbar-thumb:hover { background: #555; }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="flex gap-6 p-6 min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white p-6 rounded-xl shadow-xl h-fit sticky top-6">
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Admin Console</h2>
                <div class="h-1 bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full"></div>
            </div>
            
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="sysadmin_edit_homepage.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600 font-medium">
                            <i class="fas fa-desktop w-5 text-center text-blue-500"></i>
                            Edit Homepage
                        </a>
                    </li>
                    <li>
                        <a href="sysadmin_edit_pricing.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 text-gray-600 hover:text-gray-900 transition-colors">
                            <i class="fas fa-money-bill-wave w-5 text-center text-green-500"></i>
                            Edit Pricing
                        </a>
                    </li>
                    <li>
                        <a href="sysadmin_edit_faq.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 text-gray-600 hover:text-gray-900 transition-colors">
                            <i class="fas fa-cube w-5 text-center text-purple-500"></i>
                            Edit FAQ
                        </a>
                    </li>
                    <li>
                        <a href="sysadmin_edit_aboutus.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 text-gray-600 hover:text-gray-900 transition-colors">
                            <i class="fas fa-info-circle w-5 text-center text-orange-500"></i>
                            Edit About Us
                        </a>
                    </li>
                    <li>
                        <a href="sysadmin_dashboard.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 text-gray-600 hover:text-gray-900 transition-colors">
                            <i class="fas fa-arrow-left w-5 text-center"></i>
                            Back To Dashboard
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 space-y-8">
            <!-- Header -->
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Homepage Manager</h1>
                        <p class="text-gray-500 mt-1">Last updated: <?= date('M d, Y H:i') ?></p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="h-8 w-px bg-gray-200"></div>
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="font-medium text-gray-700">Admin User</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Video Cards Section -->
            <div class="bg-white rounded-2xl shadow-sm">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Video Cards</h2>
                        <button id="openAddVideoModal" class="flex items-center gap-2 bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition-all">
                            <i class="fas fa-plus"></i>
                            Add New Video
                        </button>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($videoCards as $video): ?>
                    <form method="POST" enctype="multipart/form-data" class="bg-gray-50 rounded-xl p-5 shadow-sm animate-card hover:shadow-md transition-shadow">
                        <input type="hidden" name="id" value="<?= $video['_id'] ?>">
                        <input type="hidden" name="collection" value="video_cards">
                        
                        <div class="mb-4 relative group">
                            <div class="absolute inset-0 bg-black bg-opacity-40 hidden group-hover:flex items-center justify-center rounded-xl">
                                <label class="cursor-pointer text-white">
                                    <i class="fas fa-camera mr-2"></i>
                                    Change Thumbnail
                                    <input type="file" name="thumbnail" accept="image/*" class="hidden">
                                </label>
                            </div>
                            <img src="<?= $video['thumbnail'] ?>" class="w-full h-40 object-cover rounded-xl">
                            <input type="hidden" name="thumbnail_url" value="<?= htmlspecialchars($video['thumbnail']) ?>">
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" name="title" value="<?= htmlspecialchars($video['title']) ?>" 
                                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" class="w-full px-3 py-2 border rounded-lg h-24 resize-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($video['description']) ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Video URL</label>
                                <input type="text" name="video_url" value="<?= htmlspecialchars($video['video_url']) ?>" 
                                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="submit" name="video_submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Save Changes
                                </button>
                                <button type="submit" name="delete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Key Features Section -->
            <div class="bg-white rounded-2xl shadow-sm">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Key Features</h2>
                        <button id="openAddFeatureModal" class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-all">
                            <i class="fas fa-plus"></i>
                            Add New Feature
                        </button>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($keyFeatures as $feature): ?>
                    <form method="POST" class="bg-gray-50 rounded-xl p-5 shadow-sm animate-card hover:shadow-md transition-shadow">
                        <input type="hidden" name="id" value="<?= $feature['_id'] ?>">
                        <input type="hidden" name="collection" value="key_features">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" name="title" value="<?= htmlspecialchars($feature['title']) ?>" 
                                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" class="w-full px-3 py-2 border rounded-lg h-24 resize-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($feature['description']) ?></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon</label>
                                    <div class="relative">
                                        <select name="icon" class="w-full pl-3 pr-8 py-2 border rounded-lg appearance-none focus:ring-2 focus:ring-blue-500">
                                            <?php $icons = ['brain', 'calendar-check', 'user-md']; ?>
                                            <?php foreach ($icons as $icon): ?>
                                            <option value="fas fa-<?= $icon ?>" <?= $feature['icon'] === "fas fa-$icon" ? 'selected' : '' ?>>
                                                <?= ucfirst(str_replace('-', ' ', $icon)) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Icon Color</label>
                                    <input type="text" name="icon_color" value="<?= htmlspecialchars($feature['icon_color']) ?>" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">BG Color</label>
                                    <input type="text" name="bg_color" value="<?= htmlspecialchars($feature['bg_color']) ?>" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                </div>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="submit" name="feature_submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    Save Changes
                                </button>
                                <button type="submit" name="delete" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 p-4 text-white rounded-lg shadow-xl transform translate-y-20 opacity-0 transition-all duration-300">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle"></i>
            <span class="font-medium"></span>
        </div>
    </div>

    <!-- Add Video Modal -->
    <div id="addVideoModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-8 relative">
            <button id="closeAddVideoModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            <h3 class="text-xl font-bold mb-4">Add New Video</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="add_video" value="1">
                <div>
                    <label class="block text-sm font-medium mb-2">Title</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" required class="w-full px-3 py-2 border rounded-lg h-24 resize-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Video URL</label>
                    <input type="text" name="video_url" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Thumbnail Image</label>
                    <input type="file" name="thumbnail" accept="image/*" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Add Video</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Feature Modal -->
    <div id="addFeatureModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-8 relative">
            <button id="closeAddFeatureModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            <h3 class="text-xl font-bold mb-4">Add New Feature</h3>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="add_feature" value="1">
                <div>
                    <label class="block text-sm font-medium mb-2">Title</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Description</label>
                    <textarea name="description" required class="w-full px-3 py-2 border rounded-lg h-24 resize-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Icon</label>
                    <div class="relative">
                        <select name="icon" required class="w-full pl-3 pr-8 py-2 border rounded-lg appearance-none focus:ring-2 focus:ring-blue-500">
                            <option value="fas fa-brain">Brain</option>
                            <option value="fas fa-calendar-check">Calendar Check</option>
                            <option value="fas fa-user-md">User MD</option>
                            <option value="fas fa-chart-line">Chart Line</option>
                            <option value="fas fa-cogs">Cogs</option>
                            <option value="fas fa-shield-alt">Shield</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Icon Color</label>
                        <input type="text" name="icon_color" required placeholder="#hex or color name" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">BG Color</label>
                        <input type="text" name="bg_color" required placeholder="#hex or color name" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add Feature</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            // Skip for add_video and add_feature forms which use regular form submission
            if (form.querySelector('[name="add_video"]') || form.querySelector('[name="add_feature"]')) {
                return;
            }
            
            e.preventDefault();
            const submitBtn = e.submitter;
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(form);
                formData.append(submitBtn.name, 'true');

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                if (submitBtn.name === 'delete' && response.ok) {
                    window.location.reload();
                    return;
                }

                if (response.ok) {
                    showToast('Changes saved successfully!', 'green');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showToast('Error saving changes', 'red');
                }
            } catch (error) {
                showToast('Network error occurred', 'red');
                console.error('Error:', error);
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    });

    // Modal handling
    document.getElementById('openAddVideoModal').onclick = () => 
        document.getElementById('addVideoModal').classList.remove('hidden');
    
    document.getElementById('closeAddVideoModal').onclick = () => 
        document.getElementById('addVideoModal').classList.add('hidden');
    
    document.getElementById('openAddFeatureModal').onclick = () => 
        document.getElementById('addFeatureModal').classList.remove('hidden');
    
    document.getElementById('closeAddFeatureModal').onclick = () => 
        document.getElementById('addFeatureModal').classList.add('hidden');
    
    // Close modals when clicking outside
    document.getElementById('addVideoModal').onclick = e => {
        if (e.target === document.getElementById('addVideoModal')) 
            document.getElementById('addVideoModal').classList.add('hidden');
    };
    
    document.getElementById('addFeatureModal').onclick = e => {
        if (e.target === document.getElementById('addFeatureModal')) 
            document.getElementById('addFeatureModal').classList.add('hidden');
    };

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const colors = {
            success: { bg: 'bg-green-500', icon: 'fa-check-circle' },
            error: { bg: 'bg-red-500', icon: 'fa-times-circle' }
        };

        toast.className = `fixed bottom-4 right-4 p-4 ${colors[type].bg} text-white rounded-lg shadow-xl transform translate-y-20 opacity-0 transition-all duration-300 flex items-center gap-3`;
        toast.innerHTML = `
            <i class="fas ${colors[type].icon}"></i>
            <span class="font-medium">${message}</span>
        `;

        // Show toast
        setTimeout(() => {
            toast.classList.remove('translate-y-20', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        }, 100);

        // Hide after 3 seconds
        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }
    </script>
</body>
</html>
