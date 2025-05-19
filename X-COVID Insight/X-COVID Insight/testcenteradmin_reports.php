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

$testCenter = $db->test_centre->findOne(['email' => $_SESSION['email']]);
if (!$testCenter) {
    die("Test center not found");
}

// Get COVID statistics from xray_records
try {
    $testCenterName = $testCenter['name'];
    
    // Total tests count
    $totalTests = $db->xray_records->countDocuments([
        'test_center' => $testCenterName
    ]);
    
    // Positive cases count (COVID predictions)
    $positiveCases = $db->xray_records->countDocuments([
        'test_center' => $testCenterName,
        'predictionResult' => 'COVID'
    ]);
    
    // Calculate positivity rate
    $positivityRate = $totalTests > 0 ? ($positiveCases / $totalTests) * 100 : 0;

} catch (Exception $e) {
    // Handle errors gracefully
    $totalTests = 0;
    $positiveCases = 0;
    $positivityRate = 0;
    $_SESSION['error'] = "Error fetching statistics: " . $e->getMessage();
}

// Get revenue data (you'll need to implement this based on your payment records)
$totalRevenue = 0; // Add your revenue calculation logic here

// Get average rating (you'll need to implement this based on reviews)
$averageRating = 0; // Add your rating calculation logic here

try {
    // Get daily test counts for current week
    $dailyTests = [];
    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    // Get start and end of current week
    $monday = strtotime('last monday', strtotime('tomorrow'));
    
    foreach ($days as $index => $day) {
        $dayStart = date('Y-m-d 00:00:00', $monday + ($index * 86400));
        $dayEnd = date('Y-m-d 23:59:59', $monday + ($index * 86400));
        
        $count = $db->xray_records->countDocuments([
            'test_center' => $testCenterName,
            'date' => [
                '$gte' => new MongoDB\BSON\UTCDateTime(strtotime($dayStart) * 1000),
                '$lte' => new MongoDB\BSON\UTCDateTime(strtotime($dayEnd) * 1000)
            ]
        ]);
        
        $dailyTests[] = $count;
    }
} catch (Exception $e) {
    $dailyTests = array_fill(0, 7, 0);
}
// Get reviews for this test center
try {
    $reviews = $db->clinic_review->find([
        'test_centre_id' => (string)$testCenter['_id']
    ])->toArray();
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching reviews: " . $e->getMessage();
}
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Center Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                    <a href="testcenteradmin_reviews.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-star text-lg text-yellow-600 group-hover:text-yellow-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Reviews</span>
                    </a>
                    <a href="testcenteradmin_reports.php" class="flex items-center p-3 bg-blue-100 text-blue-700 hover:bg-blue-50 rounded-xl group">
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
        <div id="logoMenu" class="hidden absolute left-20 top-16 w-48 bg-white rounded-lg shadow-xl z-50 border border-gray-100">
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
                    <h1 class="text-2xl font-bold text-gray-800">Analytics Report</h1>
                    <p class="text-gray-500"> As of <?php echo date('l, F j, Y'); ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- <button onclick="exportReport()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-file-export mr-2"></i>Export Report
                    </button> 
                    <div class="relative w-96">
                        <input type="text" placeholder="Search reports..." 
                               class="w-full pl-10 pr-4 py-2 bg-white border border-gray-200 rounded-xl focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div> -->
                </div>
            </div>

            <!-- Updated Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Tests</p>
                            <p class="text-3xl font-bold text-gray-800"><?= $totalTests ?></p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-vial text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-gray-500">
                        <i class="fas fa-minus mr-2"></i>0% from yesterday
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Total Revenue</p>
                            <p class="text-3xl font-bold text-gray-800">$<?= number_format($totalRevenue) ?></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-dollar-sign text-green-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-gray-500">
                        <i class="fas fa-minus mr-2"></i>0% from last month
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Positive Cases</p>
                            <p class="text-3xl font-bold text-gray-800"><?= $positiveCases ?></p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-lg">
                            <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center text-sm text-gray-500">
                        <i class="fas fa-minus mr-2"></i><?= number_format($positivityRate, 1) ?>% positivity rate
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Average Rating</p>
                           <h3 class="text-2xl font-bold text-gray-800">
                            <?php if(empty($reviews)): ?>
                                <div class="p-8 text-center text-gray-500">
                                 <i class="fas fa-comment-slash text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg">No reviews found for your clinic</p>
                                    </div>
                                    <?php else: ?>
                                    <?= number_format(array_sum(array_column($reviews, 'review_rating')) / count($reviews), decimals: 1) ?>/5                        
                            <?php endif; ?>
                                </h3>
                        </div>
                        <?php if(!empty($reviews)): ?>
                        <div class="bg-yellow-100 p-3 rounded-lg">
                            <i class="fas fa-star text-yellow-600 text-2xl"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Test Volume Trend</h3>
                    <canvas id="testsChart" class="w-full h-64"></canvas>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-semibold mb-4">Positive Case Rate</h3>
                    <canvas id="positivityChart" class="w-full h-64"></canvas>
                </div>
            </div>
        </main>
    </div>

    <script>
    // Dynamic chart data with actual values
    const testsCtx = document.getElementById('testsChart').getContext('2d');
    new Chart(testsCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($days) ?>,
            datasets: [{
                label: 'Tests Conducted',
                data: <?= json_encode($dailyTests) ?>,
                borderColor: '#3B82F6',
                tension: 0.4,
                fill: false
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Tests'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Days of the Week'
                    }
                }
            }
        }
    });

    const positivityCtx = document.getElementById('positivityChart').getContext('2d');
    new Chart(positivityCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Positive Rate %',
                data: [<?= number_format($positivityRate, 1) ?>, 0, 0, 0, 0, 0],
                backgroundColor: '#EF4444'
            }]
        }
    });

    function toggleLogoMenu() {
        const menu = document.getElementById('logoMenu');
        menu.classList.toggle('hidden');
    }
</script>
</body>
</html>
