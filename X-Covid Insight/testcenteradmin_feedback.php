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

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    try {
        $feedbackData = [
            'title' => $_POST['title'],
            'message' => $_POST['message'],
            'user' => $testCenter['name'], // Automatically get from session
            'created_at' => new MongoDB\BSON\UTCDateTime(time() * 1000),
            'user_type' => 'test_center'
        ];

        $result = $db->user_feedback->insertOne($feedbackData);
        
        if ($result->getInsertedCount() > 0) {
            $_SESSION['success'] = "Feedback submitted successfully!";
        } else {
            $_SESSION['error'] = "Failed to submit feedback";
        }
        
        header("Location: testcenteradmin_feedback.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error submitting feedback: " . $e->getMessage();
        header("Location: testcenteradmin_feedback.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <a href="testcenteradmin_reports.php" class="flex items-center p-3 text-gray-700 hover:bg-blue-50 rounded-xl group">
                        <i class="fas fa-chart-bar text-lg text-orange-600 group-hover:text-orange-700"></i>
                        <span class="ml-4 sidebar-text hidden font-medium">Reports</span>
                    </a>
                    <a href="testcenteradmin_feedback.php" class="flex items-center p-3 bg-blue-100 text-blue-700 hover:bg-blue-50 rounded-xl group">
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
                    <h1 class="text-2xl font-bold text-gray-800">Product Feedback</h1>
                    <p class="text-gray-500">Share your experience with our platform</p>
                </div>
            </div>

            <!-- Notifications -->
            <?php if(isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <p class="text-green-700"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <p class="text-red-700"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Feedback Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl mx-auto">
                <form method="POST">
                    <div class="space-y-6">
                        <!-- Title Dropdown -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Feedback Type</label>
                            <select name="title" 
                                    required
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500">
                                <option value="" disabled selected>Select feedback type</option>
                                <option value="Issue Encountered">Issue Encountered</option>
                                <option value="Product/Service Suggestion">Product/Service Suggestion</option>
                                <option value="Complaint">Complaint</option>
                                <option value="Praise">Praise</option>
                                <option value="Bug Report">Bug Report</option>
                            </select>
                        </div>

                        <!-- Message -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your Feedback</label>
                            <textarea name="message" 
                                    rows="5"
                                    required
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500"
                                    placeholder="Share your thoughts about our platform..."></textarea>
                        </div>

                        <!-- Submit Button -->
                        <div class="border-t pt-6">
                            <button type="submit" 
                                    name="submit_feedback"
                                    class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Feedback
                            </button>
                        </div>

                        <!-- Info Note -->
                        <p class="text-sm text-gray-500 text-center">
                            Feedback submitted as: <span class="font-medium"><?= $testCenter['name'] ?></span>
                        </p>
                    </div>
                </form>
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