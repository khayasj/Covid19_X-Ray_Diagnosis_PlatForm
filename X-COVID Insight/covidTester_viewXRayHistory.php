<?php
require 'db.php';
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check authentication
if (!isset($_SESSION['id'])) {
    die("User is not logged in!");
}
$testerId = $_SESSION['id'];

// Get patient ID from URL
$patientId = $_GET['patient_id'] ?? null;

// Validate patient ID format
if (!$patientId || !preg_match('/^[a-f\d]{24}$/i', $patientId)) {
    die("Invalid patient ID");
}

try {
    $collection = $db->xray_records;
    
    $records = $collection->find([
        'patient_id' => $patientId,
        'tester_id' => $testerId
    ])->toArray();

    $patientCollection = $db->patient;
    $patient = $patientCollection->findOne([
        '_id' => new MongoDB\BSON\ObjectId($patientId)
    ]);
    $patientName = $patient['name'] ?? 'Unknown';


} catch (Exception $e) {
    die("Error connecting to database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-Ray History | X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">

    <!-- Navigation Bar -->
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

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">X-Ray History</h1>
                    <p class="text-gray-600">Showing <?= count($records) ?> records for Patient: <span class="font-semibold"><?= htmlspecialchars($patientName) ?></span></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="grid grid-cols-4 gap-4 font-semibold text-gray-700">
                        <div>X-Ray ID</div>
                        <div>Submitted On</div>
                        <div>Confidence Value</div>
                        <div>Diagnosis</div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-200">
                    <?php foreach ($records as $record): ?>
                        <?php
                        // Format date from string
                        $date = new DateTime($record['dateUploaded']);
                        $formattedDate = $date->format('d M Y');
                        $confidence = isset($record['confidence']) ? round($record['confidence'], 2) : 'N/A';
                        ?>
                        <div class="grid grid-cols-4 gap-4 px-6 py-4 hover:bg-gray-50 transition-colors">
                            <button onclick="showImageModal('<?= $record['image'] ?>')"
                                    class="font-mono text-purple-600 underline hover:text-purple-800 text-left w-full">
                                <?= htmlspecialchars($record['XRayImageId']) ?>
                            </button>

                            <div class="text-gray-500">
                                <?= $formattedDate ?>
                            </div>

                            <div class="text-gray-600">
                                <?= $confidence ?>%
                            </div>

                            <div>
                                <?php
                                    $displayLabel = $record['predictionResult'];
                                    if (
                                        isset($record['needs_review'], $record['hasBeenVal'], $record['trueLabel']) &&
                                        $record['needs_review'] === true &&
                                        $record['hasBeenVal'] === true
                                    ) {
                                        $displayLabel = $record['trueLabel'];
                                    }

                                    $labelClass = str_contains($displayLabel, 'COVID') ? 
                                        'text-red-600 bg-red-50' : 'text-green-600 bg-green-50';
                                ?>
                                <span class="<?= $labelClass ?> px-3 py-1 rounded-full text-sm">
                                    <?= htmlspecialchars($displayLabel) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($records)): ?>
                        <div class="px-6 py-8 text-center text-gray-500">
                            No X-Ray records found for this patient
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-lg max-w-3xl w-full relative">
                <img id="modalImage" src="" class="max-w-full max-h-[70vh] mx-auto rounded shadow">
                <button onclick="closeModal()" class="absolute top-2 right-2 bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">×</button>
            </div>
        </div>
    </main>
</body>

    <script>
        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            const modal = document.getElementById('imageModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</html>