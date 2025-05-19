<?php
require 'db.php';
session_start();

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: loginPage.html");
    exit();
}

// Get test center details
$testCenter = $db->test_centre->findOne(['email' => $_SESSION['email']]);

if (!$testCenter) {
    die("Test center not found");
}

// Handle test center update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_center'])) {
    try {
        $updateData = [
            'name' => $_POST['name'],
            'hbp' => $_POST['hbp'],
            'uen' => $_POST['uen'],
            'testers' => (int)$_POST['testers'],
            'subscription_plan' => $_POST['subscription_plan'],
            'billing_plan' => $_POST['billing_plan']
        ];
        
        $result = $db->test_centre->updateOne(
            ['_id' => $testCenter['_id']],
            ['$set' => $updateData]
        );
        
        if ($result->getModifiedCount() > 0) {
            $_SESSION['success'] = "Test center details updated successfully!";
            // Refresh test center data
            $testCenter = $db->test_centre->findOne(['_id' => $testCenter['_id']]);
        } else {
            $_SESSION['error'] = "No changes made or error updating details";
        }
        
        header("Location: testcenteradminhomepage.php?section=edit-center");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating details: " . $e->getMessage();
        header("Location: testcenteradminhomepage.php?section=edit-center");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Center Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="bg-white w-25 hover:w-64 fixed h-full shadow-lg transition-all duration-300 ease-in-out sidebar overflow-y-auto">
            <div class="p-4 flex flex-col h-full">
                <!-- Logo with Dropdown -->
                <div class="mb-8 relative group">
                    <button onclick="toggleLogoMenu()" class="focus:outline-none">
                        <img src="https://cdn-icons-png.flaticon.com/512/6681/6681204.png" 
                            class="w-12 h-12 rounded-xl cursor-pointer hover:opacity-80 transition-opacity" 
                            alt="Logo">
                    </button>
                    
                </div>

                <!-- Navigation -->
                <nav class="space-y-4 flex-1">
                    <a href="testcenteradmin_homepage.php" class="flex items-center p-3 text-gray-700 bg-blue-100 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-home text-lg text-blue-600 group-hover:text-blue-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Dashboard</span>
                    </a>
                    <a href="testcenteradmin_doctor.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-user-md text-lg text-green-600 group-hover:text-green-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Doctors</span>
                    </a>
                    <a href="testcenteradmin_covidtesters.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-vial text-lg text-purple-600 group-hover:text-purple-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Testers</span>
                    </a>
                    <a href="testcenteradmin_patients.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-user-injured text-lg text-blue-600 group-hover:text-blue-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Patients</span>
                    </a>
                    <a href="testcenteradmin_reviews.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-star text-lg text-yellow-600 group-hover:text-yellow-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Reviews</span>
                    </a>
                    <a href="testcenteradmin_reports.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-chart-bar text-lg text-orange-600 group-hover:text-orange-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Reports</span>
                    </a>
                    <a href="testcenteradmin_feedback.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-comment-dots text-lg text-red-600 group-hover:text-red-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Feedback</span>
                    </a>
                    <a href="testcenteradmin_timeslot.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-calendar text-lg text-pink-600 group-hover:text-pink-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Timeslot</span>
                    </a>
                </nav>

                <!-- Logout Button -->
                <div class="mt-auto border-t pt-4">
                    <a href="?action=logout" 
                    class="flex items-center p-3 text-red-600 hover:bg-red-50 rounded-xl group transition-colors">
                        <i class="fas fa-sign-out-alt text-lg"></i>
                        <span class="ml-4 sidebar-text hidden text-sm font-medium text-gray-700 group-hover:text-red-700">
                            Logout
                        </span>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Dropdown Menu -->
        <div id="logoMenu" class="hidden absolute left-20 top-0 w-48 bg-white rounded-lg shadow-xl z-50 border border-gray-100">
            <div class="py-2">
                <a href="testcenteradmin_updateprofile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
                    <i class="fas fa-user-edit mr-2 text-blue-600"></i>Update Profile
                </a>
              <!---  <a href="testcenteradmin_makepayment.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50">
                    <i class="fas fa-credit-card mr-2 text-green-600"></i>Make Payment
                </a> -->
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1 ml-20 transition-all duration-300 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Welcome, <?php echo $testCenter['name']; ?></h1>
                    <p class="text-gray-500"><?php echo date('l, F j, Y'); ?></p>
                </div>
                <!-- Removed searchbox
                <div class="relative w-96">
                    <input type="text" 
                           placeholder="Search..." 
                           class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div> -->
            </div>

            <!-- Notifications -->
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <p class="text-green-700"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <p class="text-red-700"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
            $cards = [
                ['icon' => 'user-md', 'title' => 'Doctors', 'color' => 'bg-green-100', 'text' => 'text-green-800', 'link' => 'testcenteradmin_doctor.php'],
                ['icon' => 'vial', 'title' => 'COVID Testers', 'color' => 'bg-purple-100', 'text' => 'text-purple-800', 'link' => 'testcenteradmin_covidtesters.php'],
                ['icon' => 'user-injured', 'title' => 'Patient Records', 'color' => 'bg-blue-100', 'text' => 'text-blue-800', 'link' => 'testcenteradmin_patients.php'],
                ['icon' => 'star', 'title' => 'Clinic Reviews', 'color' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'link' => 'testcenteradmin_reviews.php'],
                ['icon' => 'chart-bar', 'title' => 'View Reports', 'color' => 'bg-orange-100', 'text' => 'text-orange-800', 'link' => 'testcenteradmin_reports.php'],
                ['icon' => 'comment-dots', 'title' => 'Product Feedback', 'color' => 'bg-red-100', 'text' => 'text-red-800', 'link' => 'testcenteradmin_feedback.php'],
                ['icon' => 'calendar', 'title' => 'Create Timeslot', 'color' => 'bg-pink-100', 'text' => 'text-pink-800', 'link' => 'testcenteradmin_timeslot.php']
            ];

            foreach ($cards as $card): ?>
                <a href="<?php echo $card['link']; ?>" class="block">
                    <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition-shadow border border-gray-100">
                        <div class="flex items-center mb-4">
                            <div class="<?php echo $card['color']; ?> p-3 rounded-lg">
                                <i class="fas fa-<?php echo $card['icon']; ?> text-lg <?php echo $card['text']; ?>"></i>
                            </div>
                            <h3 class="ml-4 font-semibold text-gray-800"><?php echo $card['title']; ?></h3>
                        </div>
                        <p class="text-gray-500 text-sm"><?php echo $card['description'] ?? 'Manage and view related records'; ?></p>
                        <button class="mt-4 text-sm text-gray-500 hover:text-blue-600 flex items-center">
                            Explore
                            <i class="fas fa-chevron-right ml-2 text-xs"></i>
                        </button>
                    </div>
                </a>
            <?php endforeach; ?>

            </div>
        </main>
    </div>

    <script>
    function toggleLogoMenu() {
        const menu = document.getElementById('logoMenu');
        menu.classList.toggle('hidden');
    }

    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        const logoMenu = document.getElementById('logoMenu');
        const logoButton = document.querySelector('.mb-8 button');
        
        if (!logoButton.contains(event.target) && !logoMenu.contains(event.target)) {
            logoMenu.classList.add('hidden');
        }
    });
</script>
</body>
</html>