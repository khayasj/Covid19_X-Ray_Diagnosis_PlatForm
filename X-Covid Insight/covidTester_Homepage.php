<?php
require 'db.php';

session_start();

$testerId = $_SESSION['id'];
date_default_timezone_set("Asia/Singapore");

$receivedCount = 0;
$waitingCount = 0;
$todayString = date('Y-m-d');

try {
    $testerId = $_SESSION['id'];
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');

    $todayStart = new MongoDB\BSON\UTCDateTime(strtotime($currentDate . ' 00:00:00') * 1000);
    $tomorrowStart = new MongoDB\BSON\UTCDateTime(strtotime($currentDate . ' 00:00:00 +1 day') * 1000);

    $receivedCount = $db->xray_records->countDocuments([
        'tester_id' => $testerId,
        'dateUploaded' => $todayString
    ]);

    $tester = $db->covid_tester->findOne(['_id' => new MongoDB\BSON\ObjectId($testerId)]);
    if (!$tester) throw new Exception("Tester not found");

    $testCentre = $db->test_centre->findOne(['name' => $tester['location']]);
    if (!$testCentre) throw new Exception("Test centre not found");

    $testCentreId = (string)$testCentre['_id'];

    $appointments = $db->appointment->find([
        'test_centre_id' => $testCentreId,
        'appointment_date' => $currentDate,
        'present' => false
    ]);

    $currentAppointments = [];

    foreach ($appointments as $appt) {
        $slotId = $appt['timeslot_id'];
        $slot = $db->timeslot->findOne(['_id' => new MongoDB\BSON\ObjectId($slotId)]);
        if (!$slot) continue;

        $start = $slot['StartTime'];
        $end = $slot['EndTime'];

        if ($currentTime >= $start && $currentTime <= $end) {
            $currentAppointments[] = $appt;
        }
    }

    $waitingCount = count($currentAppointments);

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-purple-50 font-[Inter]">

    <!-- Navigation Bar (Enhanced) -->
    <header class="sticky top-0 flex items-center justify-between px-8 py-4 border-b backdrop-blur-lg bg-white/90 shadow-sm">
        <!-- Logo Section -->
        <div class="flex items-center space-x-3">
            <i class="fas fa-virus text-2xl text-purple-600 animate-pulse"></i>
            <a href="covidTester_Homepage.php" class="text-xl font-bold text-gray-800 hover:text-purple-600 transition-colors">
                X-COVID Insight
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="hidden md:flex space-x-4 ml-12">
            <a href="covidTester_ViewAllPatients.php" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-purple-50 hover:text-purple-600 transition-all flex items-center">
                <i class="fas fa-clipboard-list mr-2"></i>
                Patient Records
            </a>
            <a href="covidTester_registerPatient.php" class="px-4 py-2 rounded-lg text-gray-600 hover:bg-purple-50 hover:text-purple-600 transition-all flex items-center">
                <i class="fas fa-user-plus mr-2"></i>
                Register Patient
            </a>
        </nav>

        <!-- Profile & Logout -->
        <div class="flex items-center space-x-6">
            <a href="covidTester_Homepage.php" class="group relative">
                <img src="assets/images/covidTesterProfilePicture.png" 
                     class="w-10 h-10 rounded-full object-cover cursor-pointer border-2 border-purple-200 hover:border-purple-400 transition-all">

            </a>
            <a href="logout.php" class="px-4 py-2 rounded-lg bg-gradient-to-r from-purple-600 to-blue-500 text-white hover:from-purple-700 hover:to-blue-600 transition-all shadow-lg hover:shadow-purple-200">
                Logout <i class="fas fa-sign-out-alt ml-2"></i>
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <div class="relative py-20 px-6">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-8 shadow-xl border border-white/20">
                <h1 class="text-4xl md:text-4xl font-bold bg-gradient-to-r from-purple-600 to-blue-500 bg-clip-text text-transparent mb-4">
                    Welcome back, <span id="username-display" class="animate-text">Covid Tester Name</span>!
                </h1>
                <p class="text-lg text-gray-600 mb-8">
                    Streamline patient management and X-ray diagnostics with intelligent insights
                </p>
                <div class="flex justify-center space-x-4">
                    <div class="bg-purple-100 rounded-lg p-1.5 shadow-inner">
                        <div class="bg-white px-6 py-2 rounded-md text-purple-600 font-medium">
                            <i class="fas fa-user-check mr-2"></i>
                            <?= $receivedCount ?> Patients Seen Today
                        </div>
                    </div>
                    
                    <div class="bg-blue-100 rounded-lg p-1.5 shadow-inner">
                    <div class="bg-white px-6 py-2 rounded-md text-blue-600 font-medium">
                            <i class="fas fa-user-clock mr-2"></i>
                            <?= $waitingCount ?> Waiting This Slot
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Actions Grid -->
    <div class="container mx-auto px-6 py-12">
        <div class="grid md:grid-cols-2 gap-8 max-w-6xl mx-auto">

            <!-- View Patients Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/20 to-blue-500/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="p-8">
                    <div class="mb-6">
                        <div class="w-16 h-16 rounded-xl bg-purple-100 flex items-center justify-center mb-4">
                            <i class="fas fa-clipboard-list text-3xl text-purple-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Patient Records</h3>
                        <p class="text-gray-600">Access and manage all patient records with advanced filtering and search capabilities</p>
                    </div>
                    <button onclick="window.location.href='covidTester_ViewAllPatients.php'" 
                            class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                        Browse Records
                        <i class="fas fa-arrow-right ml-2 animate-bounce-horizontal"></i>
                    </button>
                </div>
            </div>

            <!-- Register Patient Card -->
            <div class="group relative overflow-hidden rounded-2xl bg-white shadow-xl hover:shadow-2xl transition-all duration-300">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-purple-500/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                <div class="p-8">
                    <div class="mb-6">
                        <div class="w-16 h-16 rounded-xl bg-blue-100 flex items-center justify-center mb-4">
                            <i class="fas fa-user-plus text-3xl text-blue-600"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-800 mb-2">Patient Registration</h3>
                        <p class="text-gray-600">Register patient appointments and upload X-ray image</p>
                    </div>
                    <button onclick="window.location.href='covidTester_registerPatient.php'" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                        Start Registration
                        <i class="fas fa-plus ml-2 animate-bounce-horizontal"></i>
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-24">
        <div class="container mx-auto px-6 py-8">
            <div class="text-center text-gray-500 text-sm">
                <p>© 2025 X-COVID Insight. All rights reserved.</p>
                <div class="mt-2">
                    <a href="#" class="hover:text-purple-600 transition-colors">Privacy Policy</a>
                    <span class="mx-2">•</span>
                    <a href="#" class="hover:text-purple-600 transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Dynamic username with session storage check
            const username = sessionStorage.getItem("covidTesterName") || "Guest User";
            document.getElementById("username-display").textContent = username;

            // Add hover animation class to all elements with animate-bounce-horizontal
            document.querySelectorAll('.animate-bounce-horizontal').forEach(icon => {
                icon.addEventListener('mouseenter', () => {
                    icon.classList.add('animate-bounce');
                });
                icon.addEventListener('mouseleave', () => {
                    icon.classList.remove('animate-bounce');
                });
            });
        });
    </script>

    <style>
        .animate-text {
            background-size: 200% auto;
            animation: gradient 3s ease infinite;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .animate-bounce-horizontal {
            animation: bounceX 1s infinite;
        }

        @keyframes bounceX {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(5px); }
        }
    </style>

</body>
</html>