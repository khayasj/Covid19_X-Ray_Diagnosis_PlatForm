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
$testCenter = $db->test_centre->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['id'])]);

if (!$testCenter) {
    die("Test center not found");
}

// Get reviews for this test center
try {
    $reviews = $db->clinic_review->find([
        'test_centre_id' => (string)$testCenter['_id']
    ])->toArray();
    
    // Get patient names for reviews
    foreach ($reviews as $key => $review) {
        $patient = $db->patient->findOne([
            '_id' => new MongoDB\BSON\ObjectId($review['patient_id'])
        ]);
        $reviews[$key]['patient_name'] = $patient ? $patient['name'] : 'Anonymous';
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching reviews: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Reviews</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .star-rating { direction: rtl; }
        .star-rating input { display: none; }
        .star-rating label { color: #ddd; font-size: 1.5rem; padding: 0 2px; }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label { color: #ffd700; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
                <!-- Sidebar -->
        <aside class="bg-white w-25 hover:w-72 fixed h-full shadow-lg transition-all duration-300 ease-in-out overflow-y-auto overflow-x-hidden z-40">
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
                    <a href="testcenteradmin_homepage.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
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
                    <a href="testcenteradmin_reviews.php" class="flex items-center p-3 bg-blue-100 text-blue-700 hover:bg-blue-50 rounded-xl group">
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
                    <h1 class="text-2xl font-bold text-gray-800">Clinic Reviews</h1>
                    <p class="text-gray-500">Showing all patient feedback for <?= $testCenter['name'] ?></p>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <?php if(!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-100 p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-800">
                                        <?= htmlspecialchars($review['patient_name']) ?>
                                    </h3>
                                    <div class="flex items-center mt-1">
                                        <div class="star-rating">
                                            <?php for($i = 5; $i >= 1; $i--): ?>
                                                <span class="text-<?= $i <= $review['review_rating'] ? 'yellow' : 'gray' ?>-400">
                                                    <i class="fas fa-star"></i>
                                                </span>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="ml-2 text-gray-500">
                                            (<?= $review['review_rating'] ?>/5)
                                        </span>
                                    </div>
                                </div>
                                <span class="text-sm text-gray-500">
                                    <?= date('M j, Y', $review['creation_date']->toDateTime()->getTimestamp()) ?>
                                </span>
                            </div>
                            <p class="mt-4 text-gray-600">
                                "<?= htmlspecialchars($review['review_description']) ?>"
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-comment-slash text-4xl text-gray-300 mb-4"></i>
                        <p class="text-lg">No reviews found for your clinic</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Review Stats -->
            <?php if(!empty($reviews)): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <!-- Average Rating -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-3 rounded-lg">
                                <i class="fas fa-star text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Average Rating</p>
                                <h3 class="text-2xl font-bold text-gray-800">
                                    <?= number_format(array_sum(array_column($reviews, 'review_rating')) / count($reviews), 1) ?>/5
                                </h3>
                            </div>
                        </div>
                    </div>

                    <!-- Total Reviews -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-3 rounded-lg">
                                <i class="fas fa-comment-dots text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Total Reviews</p>
                                <h3 class="text-2xl font-bold text-gray-800">
                                    <?= count($reviews) ?>
                                </h3>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="bg-purple-100 p-3 rounded-lg">
                                <i class="fas fa-clock text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-500">Last Review</p>
                                <h3 class="text-2xl font-bold text-gray-800">
                                    <?= date('M j', end($reviews)['creation_date']->toDateTime()->getTimestamp()) ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
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