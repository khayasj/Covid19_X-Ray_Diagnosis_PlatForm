<?php
require 'db.php';
session_start();

// Check authentication
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = (string)$_SESSION['id'];
$collection = $db->xray_records;

// Modified sorting logic
$sort_option = $_GET['sort'] ?? 'date';
$sort_order = ($sort_option === 'date') ? 
    ['dateUploaded' => -1] : 
    ['test_center' => 1]; // Changed to sort by test_center name

$cursor = $collection->find(['patient_id' => $patient_id], ['sort' => $sort_order]);
$records = iterator_to_array($cursor);
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-Ray Records | X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .animate-fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">

<!-- Navigation Bar -->
<header class="flex items-center justify-between p-4 border-b relative z-10 bg-white shadow-md">
        <!-- Logo Section -->
        <div class="flex items-center space-x-2">
            <i class="fas fa-virus text-2xl"></i>
            <span class="text-xl font-semibold">X-COVID Insight</span>
        </div>
    
        <!-- Navigation Links (Moved Right) -->
        <nav class="flex space-x-3 ml-auto">
            <a href="patientXRayRecords.php" class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">X-Ray Records</a>
            <a href="patientAppointments.php" class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">Appointments</a>
            <a href="patientProfile.html" class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">My Profile</a>
        </nav>
    
        <!-- Profile & Logout -->
        <div class="flex items-center space-x-4">
            <a href="Patient_Homepage.html">
            <img src="assets/images/patientProfilePicture.png" class="w-10 h-10 rounded-full object-cover cursor-pointer" alt="Profile">
            </a>
            <a href="logout.php" class="px-4 py-2 rounded-full bg-black text-white hover:bg-gray-800">Logout</a>
        </div>
    </header>

<main class="max-w-4xl mx-auto pt-24 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900 flex items-center">
            <a href="Patient_Homepage.html" class="mr-3 text-blue-600 hover:text-blue-700">
                <i class="fas fa-chevron-left"></i>
            </a>
            X-Ray Records
        </h1>
        
        <!-- Sorting Controls -->
        <div class="flex space-x-2">
            <button onclick="sortRecords('date')" 
                    class="px-4 py-2 text-sm font-medium <?= $sort_option === 'date' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?> rounded-md transition-colors">
                <i class="fas fa-calendar-day mr-2"></i>Newest First
            </button>
            <button onclick="sortRecords('test_center')"
                    class="px-4 py-2 text-sm font-medium <?= $sort_option === 'test_center' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300' ?> rounded-md transition-colors">
                <i class="fas fa-hospital mr-2"></i>By Clinic
            </button>
        </div>
    </div>

    <!-- Records Container -->
    <div class="space-y-4">
        <?php if (count($records) === 0): ?>
            <div class="text-center py-12">
                <div class="text-gray-400 text-5xl mb-4">
                    <i class="fas fa-folder-open"></i>
                </div>
                <p class="text-gray-500">No X-Ray records found</p>
            </div>
        <?php else: ?>
            <?php foreach ($records as $record): ?>
            <?php
            // Check if test_centre_id exists in the record
             // Modified clinic name retrieval
             $clinicName = $record['test_center'] ?? 'Unknown Centre'; // Directly get test_center name
             $uploadDate = new DateTime($record['dateUploaded']);
             ?>
             
             <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all group">
                 <div class="flex items-start p-6">
                    <div class="flex-shrink-0 bg-gradient-to-br from-blue-100 to-blue-50 p-4 rounded-lg text-blue-600">
                        <i class="fas fa-x-ray text-2xl"></i>
                    </div>
                    
                    <div class="ml-6 flex-1">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <?= htmlspecialchars($clinicName) ?>
                        </h3>
                        <span class="text-sm text-gray-500">
                            <?= $uploadDate->format('d M Y') ?>
                        </span>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <?php
                            $labelToShow = $record['predictionResult'];
                            if (
                                isset($record['needs_review'], $record['hasBeenVal'], $record['trueLabel']) &&
                                $record['needs_review'] === true &&
                                $record['hasBeenVal'] === true
                            ) {
                                $labelToShow = $record['trueLabel'];
                            }

                            $labelColor = ($labelToShow === 'COVID') ? 'text-red-600' : 'text-green-600';

                            $confidenceText = isset($record['confidence']) ? round($record['confidence'], 2) . '%' : 'N/A';
                        ?>
                        <div class="flex items-center text-sm <?= $labelColor ?>">
                            <i class="fas fa-diagnoses mr-2"></i>
                            <?= htmlspecialchars($labelToShow) ?> 
                            <span class="ml-2 text-gray-500 font-medium">(Confidence: <?= $confidenceText ?>)</span>
                        </div>

                        <?php if (
                            isset($record['needs_review'], $record['hasBeenVal']) &&
                            $record['needs_review'] === true &&
                            $record['hasBeenVal'] === false
                        ): ?>
                            <div class="mt-2 flex items-center text-sm text-yellow-700 bg-yellow-50 px-3 py-1.5 rounded-md">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                The result is awaiting second confirmation by the doctor
                            </div>
                        <?php endif; ?>
                    </div>


                    <div class="mt-4 flex items-center space-x-3">
                        <?php if (!empty($record['image'])): ?>
                        <a href="<?= htmlspecialchars($record['image']) ?>" 
                        target="_blank"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <i class="fas fa-expand mr-2"></i>View Full Image
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
function sortRecords(sortBy) {
    window.location.href = `patientXRayRecords.php?sort=${sortBy}`;
}

// Add UI interactions
document.addEventListener('DOMContentLoaded', () => {
    // Add hover effects to all buttons
    document.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('mouseenter', () => {
            if (!btn.classList.contains('bg-blue-600')) {
                btn.classList.add('shadow-md', 'transform', 'transition', 'duration-100', 'scale-[0.98]');
            }
        });
        btn.addEventListener('mouseleave', () => {
            btn.classList.remove('shadow-md', 'transform', 'transition', 'duration-100', 'scale-[0.98]');
        });
    });

    // Add smooth scroll behavior
    document.documentElement.style.scrollBehavior = 'smooth';
});
</script>

</body>
</html>