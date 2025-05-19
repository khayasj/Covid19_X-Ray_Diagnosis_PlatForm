<?php
require 'db.php';
session_start();
use MongoDB\BSON\ObjectId;

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: loginPage.html");
    exit();
}

// Get logged-in test center details
$testCenter = null;
try {
    if (isset($_SESSION['email'])) {
        $testCenter = $db->test_centre->findOne(['email' => $_SESSION['email']]);
    }
    if (!$testCenter) {
        throw new Exception("Test center not found");
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching test center: " . $e->getMessage();
    header("Location: testcenteradmin_doctor.php");
    exit();
}

try {
    // Get search query
    $search = $_GET['search'] ?? '';

    // Ensure test center ID is an ObjectId
    $testCenterId = new ObjectId($testCenter['_id']);

    // Step 1: Fetch all appointments for this test center
    $appointmentQuery = ['test_centre_id' => (string) $testCenterId];
    $appointments = $db->appointment->find($appointmentQuery)->toArray();

    // Extract unique patient IDs from appointments
    $patientIds = array_unique(array_map(fn($appointment) => (string) $appointment['patient_id'], $appointments));

    // Step 2: Query only patients who have booked with this test center
    if (!empty($patientIds)) {
        $patientIds = array_map(fn($id) => new ObjectId($id), $patientIds);
        $query = ['_id' => ['$in' => $patientIds]];

        // Step 3: Apply search filter (if search is not empty)
        if (!empty($search)) {
            $query['name'] = ['$regex' => $search, '$options' => 'i']; // Case-insensitive name search
        }

        // Fetch patients matching the query
        $patients = $db->patient->find($query)->toArray();
    } else {
        // If no patients booked, return an empty array
        $patients = [];
    }


} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching patients: " . $e->getMessage();
    $patients = [];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-row:hover { background-color: #f8fafc; }
        /* Add to existing style block */
        .modal-content {
            max-height: 70vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Patient Details Modal -->
    <div id="patientModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Patient Details</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="font-medium">Full Name:</label>
                        <p id="modal-name" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Email:</label>
                        <p id="modal-email" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Address:</label>
                        <p id="modal-address" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Phone:</label>
                        <p id="modal-phone" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Gender:</label>
                        <p id="modal-gender" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Date of Birth:</label>
                        <p id="modal-dob" class="mt-1"></p>
                    </div>
                </div>
                <div class="mt-6">
                    <button onclick="showXRayHistory()" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-x-ray mr-2"></i>Show X-Ray History
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- X-Ray History Modal -->
    <div id="xrayModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-4xl p-6 flex flex-col" style="max-height: 90vh;"> <!-- Parent container constraint -->
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">X-Ray History</h3>
                <button onclick="closeXrayModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Add scroll to this content area -->
            <div id="xrayContent" class="overflow-y-auto flex-1"> <!-- flex-1 allows it to fill available space -->
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
    
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
                    <a href="testcenteradmin_patients.php" class="flex items-center p-3 bg-blue-100 text-blue-700 hover:bg-blue-50 rounded-xl group">
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
                <h1 class="text-2xl font-bold text-gray-800">Manage Patients</h1>
                <form method="GET" class="w-72">
                    <div class="relative">
                        <input type="text" 
                               name="search" 
                               placeholder="Search patients..."
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </form>
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

            <!-- Patients Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date of Birth</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medical Info</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $patient): ?>
                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo $patient['name'] ?? 'N/A'; ?>
                                            </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo isset($patient['dob']) ? date('M j, Y', strtotime($patient['dob'])) : 'N/A'; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Age: <?php echo isset($patient['dob']) ? floor((time() - strtotime($patient['dob'])) / 31556926) : 'N/A'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $patient['phone'] ?? 'N/A'; ?></div>
                                    <div class="text-sm text-gray-500"><?php echo $patient['email'] ?? ''; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Blood Type: <?php echo $patient['blood_type'] ?? 'N/A'; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Allergies: <?php echo isset($patient['allergies']) ? implode(', ', $patient['allergies']) : 'None'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                <button onclick="viewPatient('<?= $patient['_id'] ?>')" 
                                   class="text-blue-600 hover:text-blue-900 mr-4">
                                    <i class="fas fa-eye mr-1"></i>View
                                </button>
                            </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No patients found in the database
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // In the viewPatient function
        let currentPatientId = null; // This should be at the top

        async function viewPatient(patientId) {
            try {
                currentPatientId = patientId; // Store the ID FIRST
                const response = await fetch(`get_patient.php?id=${patientId}`);
                const patient = await response.json();
                
                document.getElementById('modal-name').textContent = patient.name;
                document.getElementById('modal-email').textContent = patient.email;
                document.getElementById('modal-address').textContent = patient.address;
                document.getElementById('modal-phone').textContent = patient.phone;
                document.getElementById('modal-gender').textContent = patient.gender;
                
                // Format string date "2018-07-10" to "July 10, 2018"
                const dobDate = new Date(patient.dob);
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                const formattedDob = dobDate.toLocaleDateString('en-US', options);
                document.getElementById('modal-dob').textContent = formattedDob;
                
                document.getElementById('patientModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error fetching patient details:', error);
                alert('Error loading patient details');
            }
        }

        function closeModal() {
            document.getElementById('patientModal').classList.add('hidden');
        }

        async function showXRayHistory() {
            try {
                const response = await fetch(`get_xray_history.php?patient_id=${currentPatientId}`);
                const data = await response.text();
                
                document.getElementById('xrayContent').innerHTML = data;
                document.getElementById('patientModal').classList.add('hidden');
                document.getElementById('xrayModal').classList.remove('hidden');
                
            } catch (error) {
                console.error('Error fetching X-Ray history:', error);
                alert('Error loading X-Ray history');
            }
        }

        function closeXrayModal() {
            document.getElementById('xrayModal').classList.add('hidden');
            document.getElementById('patientModal').classList.remove('hidden');
        }

        function viewXrayDetails(imageUrl) {
            if (imageUrl) {
                window.open(imageUrl, '_blank'); // Opens image in a new tab
            } else {
                alert("No image available.");
            }
        }
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
