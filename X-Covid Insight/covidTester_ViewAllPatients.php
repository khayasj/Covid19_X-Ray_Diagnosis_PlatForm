<?php
require 'db.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verify session and location
if (!isset($_SESSION['id']) || !isset($_SESSION['location'])) {
    die("Authentication failed! Please login again.");
}

$currentTesterId = $_SESSION['id'];
$collection = $db->patient;
$xrayCollection = $db->xray_records;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $xrayRecords = $xrayCollection->find([
        'tester_id' => $currentTesterId
    ]);

    $patientIds = [];
    foreach ($xrayRecords as $record) {
        try {
            $patientIds[] = new MongoDB\BSON\ObjectId($record['patient_id']);
        } catch (Exception $e) {
            continue;
        }
    }

    $query = ['_id' => ['$in' => $patientIds]];

    if (!empty($search)) {
        $regex = new MongoDB\BSON\Regex(preg_quote($search), 'i');
        $query['name'] = $regex;
    }

    $patients = $collection->find($query);
    $patientCount = $collection->countDocuments($query);

} catch (Exception $e) {
    die("Query error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-COVID Insight | Patient Records</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        .patient-card:hover .patient-name {
            color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">

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

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Patient Records</h1>
                    <p class="text-gray-600">Showing <?= $patientCount ?> records</p>
                </div>
                <form method="GET" class="mt-4 md:mt-0 w-full md:w-64">
                    <div class="relative">
                        <input type="text" 
                               name="search"
                               value="<?= htmlspecialchars($search) ?>"
                               class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 focus:border-red-300 focus:ring-2 focus:ring-red-200 transition-all"
                               placeholder="Search by name...">
                        <button type="submit" class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($patients as $patient): ?>
                    <?php
                    // Add Patient ID retrieval
                    $patientId = (string)$patient['_id'];
                    
                    $name = $patient['name'] ?? 'No name provided';
                    $email = $patient['email'] ?? 'No email';
                    $phone = $patient['phone'] ?? 'No phone';
                    $gender = $patient['gender'] ?? 'Not specified';
                    $dobString = $patient['dob'] ?? 'Unknown';
                    
                    try {
                        $dobDate = new DateTime($dobString);
                        $formattedDob = $dobDate->format('d M Y');
                        $shortDob = $dobDate->format('M Y');
                    } catch (Exception $e) {
                        $formattedDob = $dobString;
                        $shortDob = $dobString;
                    }
                    ?>
                    
                    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow cursor-pointer patient-card"
                        data-patient-id="<?= htmlspecialchars($patientId) ?>"
                        onclick="showPatientDetails(
                            '<?= htmlspecialchars($patientId) ?>',
                            '<?= htmlspecialchars($name) ?>',
                            '<?= htmlspecialchars($email) ?>',
                            '<?= htmlspecialchars($phone) ?>',
                            '<?= htmlspecialchars($formattedDob) ?>',
                            '<?= htmlspecialchars($gender) ?>'
                        )">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800 patient-name">
                                        <?= htmlspecialchars($name) ?>
                                    </h3>
                                </div>
                            </div>
                            <div class="mt-4 text-sm text-gray-600">
                                <p class="truncate">
                                    <i class="fas fa-phone-alt mr-2"></i>
                                    <?= htmlspecialchars($phone) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Patient Detail Modal -->
        <div id="patientModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-50">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-md w-full transform transition-all">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-800">Patient Details</h3>
                        <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Full Name</p>
                                <p class="font-medium" id="modalName">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Date of Birth</p>
                                <p class="font-medium" id="modalDob">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Gender</p>
                                <p class="font-medium" id="modalGender">-</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Phone</p>
                                <p class="font-medium" id="modalPhone">-</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-sm text-gray-500">Email</p>
                                <p class="font-medium" id="modalEmail">-</p>
                            </div>
                        </div>

                        <!-- Action Buttons - Side by Side -->
                        <div class="mt-6 flex flex-row gap-3">
                            <button onclick="viewXRayHistory()" 
                                    class="flex-1 px-4 py-2.5 border-2 border-blue-500 text-blue-600 rounded-lg
                                        hover:bg-blue-50 transition-colors flex items-center justify-center">
                                <i class="fas fa-history mr-2"></i>
                                History
                            </button>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function showPatientDetails(patientId, name, email, phone, dob, gender) {
            currentPatientId = patientId;
            
            document.getElementById('modalName').textContent = name;
            document.getElementById('modalEmail').textContent = email;
            document.getElementById('modalPhone').textContent = phone;
            document.getElementById('modalDob').textContent = dob;
            document.getElementById('modalGender').textContent = gender;
            
            document.getElementById('patientModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('patientModal').classList.add('hidden');
        }

        function viewXRayHistory() {
            const patientId = document.getElementById('patientModal').dataset.patientId;
            window.location.href = `covidTester_viewXRayHistory.php?patient_id=${currentPatientId}`;
        }


        </script>

</body>
</html>