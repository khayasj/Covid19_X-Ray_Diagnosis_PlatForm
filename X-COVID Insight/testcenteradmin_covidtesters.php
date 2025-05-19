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

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tester_id'])) {
    try {
        $tester = $db->covid_tester->findOne(['_id' => new MongoDB\BSON\ObjectId($_POST['tester_id'])]);
        $newStatus = $tester['status'] === 'active' ? 'suspended' : 'active';
        
        $result = $db->covid_tester->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['tester_id'])],
            ['$set' => ['status' => $newStatus]]
        );
        
        if ($result->getModifiedCount() > 0) {
            $_SESSION['success'] = "Tester status updated to $newStatus!";
        } else {
            $_SESSION['error'] = "No changes made";
        }
        header("Location: testcenteradmin_covidtesters.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error updating status: " . $e->getMessage();
        header("Location: testcenteradmin_covidtesters.php");
        exit();
    }
}

try {
    // Build the filter based on location and search term
    $filter = ['location' => $testCenter['name']];
    
    if (isset($_GET['search']) && $_GET['search'] !== '') {
        $searchTerm = $_GET['search'];
        $filter['$or'] = [
            ['name' => ['$regex' => $searchTerm, '$options' => 'i']],
            ['email' => ['$regex' => $searchTerm, '$options' => 'i']],
            ['phone' => ['$regex' => $searchTerm, '$options' => 'i']],
        ];
    }

    // Get testers based on the filter
    $testers = $db->covid_tester->find($filter)->toArray();
} catch (Exception $e) {
    $_SESSION['error'] = "Error fetching COVID testers: " . $e->getMessage();
    $testers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage COVID Testers</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-row:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- View Tester Modal -->
    <div id="testerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl w-full max-w-2xl p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Tester Details</h3>
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
                        <label class="font-medium">Location:</label>
                        <p id="modal-location" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Phone:</label>
                        <p id="modal-phone" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Status:</label>
                        <p id="modal-status" class="mt-1"></p>
                    </div>
                    <div>
                        <label class="font-medium">Created At:</label>
                        <p id="modal-created" class="mt-1"></p>
                    </div>
                </div>
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
                    <a href="testcenteradmin_covidtesters.php" class="flex items-center p-3 bg-blue-100 text-blue-700 hover:bg-blue-50 rounded-xl group">
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
                <h1 class="text-2xl font-bold text-gray-800">Manage COVID Testers</h1>
                <a href="testcenteradmin_createcovidtester.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add New Tester
                </a>
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

            <!-- Search Form -->
            <div class="mb-6">
                <form method="GET" action="testcenteradmin_covidtesters.php" class="flex gap-4">
                    <input type="text" name="search" placeholder="Search by name, email, or phone" 
                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                        class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <?php if (isset($_GET['search']) && $_GET['search'] !== ''): ?>
                        <a href="testcenteradmin_covidtesters.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

             <!-- Testers Table -->
             <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (!empty($testers)): ?>
                            <?php foreach ($testers as $tester): ?>
                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo $tester['name'] ?? 'N/A'; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $tester['email'] ?? 'N/A'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $tester['phone'] ?? 'N/A'; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button onclick="viewTester('<?= $tester['_id'] ?>')" 
                                    class="text-blue-600 hover:text-blue-900 mr-4">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </button>
                                    <!-- Add Edit Button -->
                                    <a href="edit_tester.php?id=<?= $tester['_id'] ?>" 
                                    class="text-green-600 hover:text-green-800 mr-4">
                                        <i class="fas fa-edit mr-1"></i>Edit
                                    </a>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="tester_id" value="<?= $tester['_id'] ?>">
                                        <button type="submit" class="<?= $tester['status'] === 'active' ? 'text-yellow-600 hover:text-yellow-800' : 'text-green-600 hover:text-green-800' ?>">
                                            <i class="fas fa-power-off mr-1"></i>
                                            <?= $tester['status'] === 'active' ? 'Suspend' : 'Unsuspend' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    No COVID testers found in the database
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        async function viewTester(testerId) {
            try {
                const response = await fetch(`get_tester.php?id=${testerId}`);
                const tester = await response.json();
                
                document.getElementById('modal-name').textContent = tester.name;
                document.getElementById('modal-email').textContent = tester.email;
                document.getElementById('modal-location').textContent = tester.location;
                document.getElementById('modal-phone').textContent = tester.phone;
                document.getElementById('modal-status').textContent = tester.status;
                
                const createdAt = new Date(tester.created_at).toLocaleString();
                document.getElementById('modal-created').textContent = createdAt;
                
                document.getElementById('testerModal').classList.remove('hidden');
            } catch (error) {
                console.error('Error fetching tester details:', error);
                alert('Error loading tester details');
            }
        }

        function closeModal() {
            document.getElementById('testerModal').classList.add('hidden');
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