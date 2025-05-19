<?php
require 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}
$filteredAppointments = [];
$appCount = 0;
$timeSlotLabel = '';

date_default_timezone_set("Asia/Singapore");
$error = '';
$missedAppointments = [];
$currentAppointments = [];
$upcomingAppointments = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';


$filter = $_GET['filter'] ?? 'current';

try {
    if (!isset($_SESSION['id'])) {
        throw new Exception("Tester not logged in.");
    }

    $testerId = $_SESSION['id'];
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');

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

    $filteredAppointments = [];

    foreach ($appointments as $appt) {
        $slotId = $appt['timeslot_id'];
        $slot = $db->timeslot->findOne(['_id' => new MongoDB\BSON\ObjectId($slotId)]);
        if (!$slot) continue;
    
        $startTime = $slot['StartTime'];
        $endTime = $slot['EndTime'];
    
        if ($currentTime > $endTime) {
            $missedAppointments[] = $appt;
        } elseif ($currentTime >= $startTime && $currentTime <= $endTime) {
            $currentAppointments[] = $appt;
        } elseif ($currentTime < $startTime) {
            $upcomingAppointments[] = $appt;
        }
    }

    switch ($filter) {
        case 'missed':
            $filteredAppointments = $missedAppointments;
            break;
        case 'upcoming':
            $filteredAppointments = $upcomingAppointments;
            break;
        case 'current':
        default:
            $filteredAppointments = $currentAppointments;
            break;
    }

    $appCount = count($filteredAppointments);

    $timeSlotLabel = '';

    if ($filter === 'current') {
        $activeSlot = $db->timeslot->findOne([
            'StartTime' => ['$lte' => $currentTime],
            'EndTime' => ['$gte' => $currentTime]
        ]);

        if ($activeSlot) {
            $timeSlotLabel = date('h:i A', strtotime($activeSlot['StartTime'])) . ' - ' . date('h:i A', strtotime($activeSlot['EndTime']));
        } else {
            $timeSlotLabel = "No active time slot";
        }

        $startSlotTime = $activeSlot['StartTime'] ?? null;
        $endSlotTime = $activeSlot['EndTime'] ?? null;

    } elseif ($filter === 'missed') {
        $timeSlotLabel = "Past time slots";
    } elseif ($filter === 'upcoming') {
        $timeSlotLabel = "Future time slots";
    }

    if (!empty($search)) {
        $regex = new MongoDB\BSON\Regex(preg_quote($search), 'i');
    
        $filteredAppointments = array_filter($filteredAppointments, function($appt) use ($db, $regex) {
            try {
                $patient = $db->patient->findOne(['_id' => new MongoDB\BSON\ObjectId($appt['patient_id'])]);
                return $patient && preg_match($regex, $patient['name'] ?? '');
            } catch (Exception $e) {
                return false;
            }
        });
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    try {
        $db->appointment->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_POST['appointment_id'])],
            ['$set' => ['present' => true]]
        );
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Patient | X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
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
            <div class="mb-2 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-gray-800">Patient Appointment Register</h1>
                </div>

                <form method="GET" class="flex flex-col md:flex-row items-center gap-2 w-full md:w-auto">
                    <select name="filter" onchange="this.form.submit()" class="px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-red-200 focus:border-red-300">
                        <option value="current" <?= (isset($_GET['filter']) && $_GET['filter'] === 'current') ? 'selected' : '' ?>>Current Slot</option>
                        <option value="missed" <?= (isset($_GET['filter']) && $_GET['filter'] === 'missed') ? 'selected' : '' ?>>Missed Slot</option>
                        <option value="upcoming" <?= (isset($_GET['filter']) && $_GET['filter'] === 'upcoming') ? 'selected' : '' ?>>Upcoming Slot</option>
                    </select>

                    <div class="relative w-full md:w-64">
                        <input type="text" 
                            name="search"
                            value="<?= htmlspecialchars($search ?? '') ?>"
                            class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-200 focus:border-red-300 focus:ring-2 focus:ring-red-200 transition-all"
                            placeholder="Search by name...">
                        <button type="submit" class="absolute left-3 top-3 text-gray-400">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($appCount > 0): ?>
                <p class="text-gray-600 mt-1">
                    <?= htmlspecialchars($timeSlotLabel) ?> time slot has <?= $appCount ?> pending patient appointments.
                </p>
            <?php else: ?>
                <p class="text-gray-500 mt-1 italic">No pending patient appointments.</p>
            <?php endif; ?>
        

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($filteredAppointments as $appt): ?>
                    <?php
                        try {
                            $patient = $db->patient->findOne([
                                '_id' => new MongoDB\BSON\ObjectId($appt['patient_id'])
                            ]);
                        } catch (Exception $e) {
                            continue;
                        }

                        if (!$patient) continue;

                        $name = $patient['name'] ?? 'No name provided';
                        $phone = $patient['phone'] ?? 'No phone';
                        $dob = $patient['dob'] ?? 'Unknown';
                        $gender = $patient['gender'] ?? 'Not specified';
                        $remark = !empty($appt['remarks']) ? $appt['remarks'] : 'No remarks';
                        $patientId = (string)$patient['_id'];
                        $appointmentId = (string)$appt['_id'];
                    ?>
                    <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow cursor-pointer patient-card"
                        onclick="showPatientDetails(
                            '<?= $patientId ?>',
                            '<?= htmlspecialchars($name) ?>',
                            '<?= htmlspecialchars($phone) ?>',
                            '<?= htmlspecialchars($dob) ?>',
                            '<?= htmlspecialchars($gender) ?>',
                            '<?= htmlspecialchars($remark) ?>',
                            '<?= $appointmentId ?>'
                        )">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($name) ?></h3>
                            <p class="text-sm text-gray-600 mt-2">
                                <i class="fas fa-phone-alt mr-1"></i><?= htmlspecialchars($phone) ?>
                            </p>
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
                            <div>
                                <p class="text-sm text-gray-500">Appointment Remark</p>
                                <p class="font-medium" id="modalRemark">-</p>
                            </div>

                        </div>

                        <!-- Action Buttons - Side by Side -->
                        <div class="mt-6 flex flex-row gap-3">
                        <button onclick="confirmAttendance()" 
                                    class="flex-1 px-4 py-2.5 border-2 border-blue-500 text-blue-600 rounded-lg
                                        hover:bg-blue-50 transition-colors flex items-center justify-center">
                                <i class="fas fa-history mr-2"></i>
                                Confirm Present
                            </button>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($filter === 'current' && isset($startSlotTime) && isset($endSlotTime)): ?>
        <script>
            const startTimeStr = "<?= $startSlotTime ?>";
            const endTimeStr = "<?= $endSlotTime ?>";

            function parseTime(timeStr) {
                const [hour, minute] = timeStr.split(":").map(Number);
                const now = new Date();
                now.setHours(hour, minute, 0, 0);
                return now;
            }

            const originalSlotStart = parseTime(startTimeStr);
            const originalSlotEnd = parseTime(endTimeStr);

            function checkSlotChange() {
                const now = new Date();
                if (!(now >= originalSlotStart && now <= originalSlotEnd)) {
                    location.reload();
                }
            }

            setInterval(checkSlotChange, 30000);
        </script>
        <?php endif; ?>


        <script>
            let currentPatientId = null;
            let currentAppointmentId = null;

            function showPatientDetails(patientId, name, phone, dob, gender, remark, appointmentId) {
                currentPatientId = patientId;
                currentAppointmentId = appointmentId;

                document.getElementById('modalName').textContent = name;
                document.getElementById('modalPhone').textContent = phone;
                document.getElementById('modalDob').textContent = dob;
                document.getElementById('modalGender').textContent = gender;
                document.getElementById('modalRemark').textContent = remark;

                document.getElementById('patientModal').classList.remove('hidden');
            }

            function closeModal() {
                document.getElementById('patientModal').classList.add('hidden');
            }

            function confirmAttendance() {
                if (!currentAppointmentId || !currentPatientId) return;

                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'appointment_id=' + encodeURIComponent(currentAppointmentId)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = `covidTester_XRayUpload.html?patient_id=${currentPatientId}`;
                    } else {
                        alert("Failed to confirm attendance: " + data.message);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Network error");
                });
            }
        </script>

</body>
</html>