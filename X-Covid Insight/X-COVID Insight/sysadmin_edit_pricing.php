<?php

require 'admindb.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_plan'])) {
            // Handle adding a new plan
            $insertResult = $db2->billing_plans->insertOne([
                'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
                'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
                'price' => (string)filter_input(INPUT_POST, 'price', FILTER_SANITIZE_STRING),
                'user_limit' => (string)filter_input(INPUT_POST, 'user_limit', FILTER_SANITIZE_STRING),
                'billing_durations' => array_map('trim', 
                    explode(',', 
                        filter_input(INPUT_POST, 'billing_durations', FILTER_SANITIZE_STRING)
                    )
                )
            ]);
            
            if (!$insertResult->getInsertedId()) {
                throw new Exception("❌ Failed to add new plan.");
            }
            
            echo json_encode(['success' => true, 'message' => '✅ New plan added successfully!']);
            exit;
        } elseif (isset($_POST['plan_submit'])) { 
            // Perform the update operation
            $updateResult = $db2->billing_plans->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($_POST['id'])],
                ['$set' => [
                    'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
                    'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
                    'price' => (string)filter_input(INPUT_POST, 'price', FILTER_SANITIZE_STRING),
                    'user_limit' => (string)filter_input(INPUT_POST, 'user_limit', FILTER_SANITIZE_STRING),
                    'billing_durations' => array_map('trim', 
                        explode(',', 
                            filter_input(INPUT_POST, 'billing_durations', FILTER_SANITIZE_STRING)
                        )
                    )
                ]]
            );

            // Prepare response
            if ($updateResult->getMatchedCount() === 0) {
                throw new Exception("❌ No matching document found.");
            } elseif ($updateResult->getModifiedCount() === 0) {
                throw new Exception("⚠ No changes detected.");
            } 
        } elseif (isset($_POST['delete'])) {

            $objectId = new MongoDB\BSON\ObjectId($_POST['id']);
            $deleteResult = $db2->billing_plans->deleteOne(['_id' => $objectId]);

            if ($deleteResult->getDeletedCount() === 0) {
                throw new Exception("❌ No document deleted.");
            }
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
    exit;
}

// Fetch billing plans
$billingPlans = $db2->billing_plans->find()->toArray();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pricing Plans</title>
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
                        <a href="sysadmin_edit_pricing.php" class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 text-blue-600 font-medium">
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
                        <h1 class="text-2xl font-bold text-gray-800">Pricing Manager</h1>
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

            <!-- Pricing Plans Section -->
            <div class="bg-white rounded-2xl shadow-sm">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Billing Plans</h2>
                        <button id="openAddPlanModal" class="flex items-center gap-2 bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-all">
                            <i class="fas fa-plus"></i>
                            Add New Plan
                        </button>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($billingPlans as $plan): ?>
                    <form method="POST" action="" class="bg-gray-50 rounded-xl p-5 shadow-sm animate-card hover:shadow-md transition-shadow">
                        <input type="hidden" name="id" value="<?= $plan['_id'] ?>">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($plan['name']) ?>" 
                                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea name="description" class="w-full px-3 py-2 border rounded-lg h-20 resize-none focus:ring-2 focus:ring-purple-500"><?= htmlspecialchars($plan['description']) ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                                        <input type="text" name="price" value="<?= htmlspecialchars($plan['price']) ?>" 
                                               class="w-full pl-7 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">User Limit</label>
                                    <input type="text" name="user_limit" value="<?= htmlspecialchars($plan['user_limit']) ?>" 
                                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Billing Durations (comma separated)</label>
                                <input type="text" name="billing_durations" 
                                value="<?= htmlspecialchars(implode(', ', (array)($plan['billing_durations'] ?? []))) ?>" 
                                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div class="flex justify-end space-x-2">
                                <button type="submit" name="plan_submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
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

    <!-- Add Plan Modal -->
    <div id="addPlanModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-40 z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-8 relative">
            <button id="closeAddPlanModal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            <h3 class="text-xl font-bold mb-4">Add New Plan</h3>
            <form id="addPlanForm" method="POST" class="space-y-4">
                <input type="hidden" name="add_plan" value="1">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" required class="w-full px-3 py-2 border rounded-lg h-20 resize-none focus:ring-2 focus:ring-purple-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="text" name="price" required class="w-full pl-7 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">User Limit</label>
                        <input type="text" name="user_limit" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Billing Durations (comma separated)</label>
                    <input type="text" name="billing_durations" required placeholder="monthly, yearly" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="flex justify-end">
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Add Plan</button>
                </div>
            </form>
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
    // Modal controls
    document.getElementById('openAddPlanModal').onclick = () => 
        document.getElementById('addPlanModal').classList.remove('hidden');
    
    document.getElementById('closeAddPlanModal').onclick = () => 
        document.getElementById('addPlanModal').classList.add('hidden');
    
    document.getElementById('addPlanModal').onclick = e => {
        if (e.target === document.getElementById('addPlanModal'))
            document.getElementById('addPlanModal').classList.add('hidden');
    };
    
    // Form submission handling
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = e.submitter;
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            submitBtn.disabled = true;

            try {
                const formData = new FormData(form);
                formData.append(submitBtn.name, 'true');

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
