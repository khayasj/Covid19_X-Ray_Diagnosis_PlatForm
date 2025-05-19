<?php
require 'admindb.php';

// Get about_us data (assuming single document)
$aboutUs = $db2->about_us->findOne([]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Process why choose us points (remove empty values)
        $whyChooseUs = array_values(array_filter($_POST['why_choose_us'] ?? []));

        $updateResult = $db2->about_us->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($aboutUs['_id'])],
            ['$set' => [
                'main_content' => filter_input(INPUT_POST, 'main_content', FILTER_SANITIZE_STRING),
                'vision' => filter_input(INPUT_POST, 'vision', FILTER_SANITIZE_STRING),
                'product' => filter_input(INPUT_POST, 'product', FILTER_SANITIZE_STRING),
                'why_choose_us' => $whyChooseUs
            ]]
        );

        if ($updateResult->getMatchedCount() === 0) {
            throw new Exception("❌ No matching document found.");
        }

        echo json_encode(['success' => true, 'message' => '✅ About Us updated successfully']);
        exit;

    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Us - X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .animate-card { animation: slideIn 0.3s ease-out; }
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
                        <a href="sysadmin_edit_homepage.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 text-gray-600 hover:text-gray-900 transition-colors">
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
                        <a href="sysadmin_edit_aboutus.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600 font-medium">
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
                        <h1 class="text-2xl font-bold text-gray-800">About Us Manager</h1>
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

            <!-- About Us Editor -->
            <div class="bg-white rounded-2xl shadow-sm">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Edit About Us Content</h2>
                </div>
                
                <form method="POST" action="" class="p-6 space-y-8">
                    <!-- Main Content -->
                    <div class="space-y-4">
                        <label class="block text-lg font-semibold text-gray-800">Main Content</label>
                        <textarea name="main_content" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 h-48 resize-none"><?= htmlspecialchars($aboutUs['main_content']) ?></textarea>
                    </div>

                    <!-- Vision -->
                    <div class="space-y-4">
                        <label class="block text-lg font-semibold text-gray-800">Our Vision</label>
                        <textarea name="vision" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 h-48 resize-none"><?= htmlspecialchars($aboutUs['vision']) ?></textarea>
                    </div>

                    <!-- Product -->
                    <div class="space-y-4">
                        <label class="block text-lg font-semibold text-gray-800">Our Product</label>
                        <textarea name="product" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 h-48 resize-none"><?= htmlspecialchars($aboutUs['product']) ?></textarea>
                    </div>

                    <!-- Why Choose Us -->
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <label class="block text-lg font-semibold text-gray-800">Why Choose Us</label>
                            <button type="button" onclick="addNewPoint()" class="flex items-center gap-2 text-blue-500 hover:text-blue-600">
                                <i class="fas fa-plus"></i>
                                Add New Point
                            </button>
                        </div>
                        
                        <div id="whyChooseUsContainer" class="space-y-4">
                            <?php foreach ($aboutUs['why_choose_us'] as $point): ?>
                            <div class="flex gap-4">
                                <input type="text" name="why_choose_us[]" value="<?= htmlspecialchars($point) ?>" 
                                       class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                <button type="button" onclick="removePoint(this)" class="text-red-500 hover:text-red-600 px-2">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-6">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl text-lg font-semibold transition-colors">
                            Save All Changes
                        </button>
                    </div>
                </form>
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

    <script>
    // Dynamic points management
    function addNewPoint() {
        const container = document.getElementById('whyChooseUsContainer');
        const newPoint = document.createElement('div');
        newPoint.className = 'flex gap-4';
        newPoint.innerHTML = `
            <input type="text" name="why_choose_us[]" 
                   class="flex-1 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            <button type="button" onclick="removePoint(this)" class="text-red-500 hover:text-red-600 px-2">
                <i class="fas fa-times"></i>
            </button>
        `;
        container.appendChild(newPoint);
    }

    function removePoint(button) {
        button.closest('div').remove();
    }

    // Form submission handling
    document.querySelector('form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = e.submitter;
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
        submitBtn.disabled = true;

        try {
            const formData = new FormData(e.target);
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.error || 'Error occurred', 'error');
            }

        } catch (error) {
            showToast('Successfully Updated the Information', 'success');
            console.error('Error:', error);
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const colors = {
            success: { bg: 'bg-green-500', icon: 'fa-check-circle' },
            error: { bg: 'bg-red-500', icon: 'fa-times-circle' }
        };

        toast.className = `${colors[type].bg} fixed bottom-4 right-4 p-4 text-white rounded-lg shadow-xl flex items-center gap-3 transform translate-y-20 opacity-0 transition-all duration-300`;
        toast.innerHTML = `
            <i class="fas ${colors[type].icon}"></i>
            <span class="font-medium">${message}</span>
        `;

        setTimeout(() => {
            toast.classList.remove('translate-y-20', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
        }, 100);

        setTimeout(() => {
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-20', 'opacity-0');
        }, 3000);
    }
    </script>
</body>
</html>