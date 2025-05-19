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
$testCenterId = $testCenter['_id'];

$existingSlots = iterator_to_array($db->timeslot->find([
    'TestCenterID' => $testCenterId
]));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_date'])) {
    try {
        $db->blocked_dates->insertOne([
            'test_center_id' => $testCenterId,
            'date' => $_POST['block_date'],
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Date marked as unavailable successfully'];
        header("Location: testcenteradmin_timeslot.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error blocking date: ' . $e->getMessage()];
        header("Location: testcenteradmin_timeslot.php");
        exit();
    }
}


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    // Add New Timeslot
    if (isset($_GET['api']) && $_GET['api'] === 'add_slot') {
        if (!isset($input['start'], $input['end'], $input['limit'])) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }

        $conflict = $db->timeslot->findOne([
            'TestCenterID' => $testCenterId,
            '$or' => [[
                'StartTime' => ['$lt' => $input['end']],
                'EndTime'   => ['$gt' => $input['start']]
            ]]
        ]);
        if ($conflict) {
            echo json_encode(['success' => false, 'message' => 'Time conflict with existing slot.']);
            exit;
        }

        try {
            $result = $db->timeslot->insertOne([
                'TestCenterID' => $testCenterId,
                'StartTime' => $input['start'],
                'EndTime' => $input['end'],
                'PeopleAllowed' => intval($input['limit']),
                'CreatedAt' => new MongoDB\BSON\UTCDateTime()
            ]);
            echo json_encode(['success' => true, 'id' => (string)$result->getInsertedId()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Update Timeslot
    elseif ($_GET['api'] === 'update_slot') {
        if (!isset($input['id'], $input['start'], $input['end'], $input['limit'])) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }

        $conflict = $db->timeslot->findOne([
            'TestCenterID' => $testCenterId,
            '_id' => ['$ne' => new MongoDB\BSON\ObjectId($input['id'])],
            '$or' => [[
                'StartTime' => ['$lt' => $input['end']],
                'EndTime'   => ['$gt' => $input['start']]
            ]]
        ]);
        if ($conflict) {
            echo json_encode(['success' => false, 'message' => 'Time conflict with existing slot.']);
            exit;
        }

        try {
            $updateResult = $db->timeslot->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($input['id'])],
                ['$set' => [
                    'StartTime' => $input['start'],
                    'EndTime' => $input['end'],
                    'PeopleAllowed' => intval($input['limit'])
                ]]
            );

            if ($updateResult->getModifiedCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes occurred']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // Delete Timeslot
    elseif ($_GET['api'] === 'delete_slot') {
        if (!isset($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }

        try {
            $deleteResult = $db->timeslot->deleteOne([
                '_id' => new MongoDB\BSON\ObjectId($input['id'])
            ]);

            if ($deleteResult->getDeletedCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    elseif (isset($_GET['api']) && $_GET['api'] === 'delete_blocked_date') {
        if (!isset($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'Missing ID']);
            exit;
        }

        try {
            $deleteResult = $db->blocked_dates->deleteOne([
                '_id' => new MongoDB\BSON\ObjectId($input['id']),
                'test_center_id' => $testCenterId
            ]);

            if ($deleteResult->getDeletedCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Blocked date not found']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}



// Get existing data
$availableSlots = $db->available_slots->find(['test_center_id' => $testCenterId]);
$blockedDates = iterator_to_array(
    $db->blocked_dates->find(['test_center_id' => $testCenterId])
);

// Get blocked dates array
$blockedDatesArray = [];
foreach ($blockedDates as $bd) {
    $blockedDatesArray[] = $bd['date'];
}

if (isset($_GET['flash']) && isset($_GET['type'])) {
    $_SESSION['flash'] = [
        'type' => $_GET['type'],
        'message' => $_GET['flash']
    ];
    header("Location: testcenteradmin_timeslot.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Time Slots</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="bg-white w-20 hover:w-64 fixed h-full shadow-lg transition-all duration-300 ease-in-out sidebar">
            <div class="p-4 flex flex-col h-full">
                <!-- Logo with Dropdown -->
                <div class="mb-8 relative group">
                    <button onclick="toggleLogoMenu()" class="focus:outline-none">
                        <img src="https://cdn-icons-png.flaticon.com/512/6681/6681204.png" 
                            class="w-12 h-12 rounded-xl cursor-pointer hover:opacity-80 transition-opacity" 
                            alt="Logo">
                    </button>
                    
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

        <!-- Main Content -->
        <main class="flex-1 ml-20 transition-all duration-300 p-8">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Manage Time Slots & Availability</h1>
                    <p class="text-gray-500"><?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>

            <!-- Notifications -->
            <?php if (isset($_SESSION['flash'])): ?>
            <div class="p-4 mb-6 rounded-lg <?= $_SESSION['flash']['type'] === 'success' ? 'bg-green-50 border-l-4 border-green-400 text-green-700' : 'bg-red-50 border-l-4 border-red-400 text-red-700' ?>">
                <div class="flex items-center">
                    <i class="fas <?= $_SESSION['flash']['type'] === 'success' ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600' ?> mr-3"></i>
                    <p><?= $_SESSION['flash']['message'] ?></p>
                </div>
            </div>
            <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <!-- Add Time Slots Section -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Manage Time Slots</h2>
                    <button type="button" onclick="addSlotRow()" 
                            class="px-3 py-1 bg-green-600 text-white rounded-md hover:bg-green-700 text-sm">
                        + Add New
                    </button>
                </div>

                <div id="slotContainer" class="space-y-2">
                    <?php foreach ($existingSlots as $i => $slot): ?>
                        <div class="grid grid-cols-6 gap-2 items-center slot-row" data-id="<?= $slot['_id'] ?>">
                            <input type="time" value="<?= $slot['StartTime'] ?>" class="border p-2 rounded-md" readonly>
                            <input type="time" value="<?= $slot['EndTime'] ?>" class="border p-2 rounded-md" readonly>
                            <input type="number" value="<?= $slot['PeopleAllowed'] ?>" class="border p-2 rounded-md" readonly>

                            <button type="button" onclick="enableEdit(this)" class="text-blue-600 hover:text-blue-800">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="deleteSlot(this)" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                            <span></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- Block Dates Section -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Mark Dates as Unavailable</h2>
                <form method="POST">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <input type="date" name="block_date" 
                                   class="w-full p-2 border rounded-md"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <button type="submit" 
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Mark as Unavailable
                        </button>
                    </div>
                </form>
            </div>


            <!-- Blocked Dates -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Unavailable Dates</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($blockedDates as $bd): ?>
                        <div class="p-3 bg-red-50 text-red-800 rounded-md flex items-center justify-between">
                            <span><?= date('M j, Y', strtotime($bd['date'])) ?></span>
                            <button onclick="deleteBlockedDate('<?= $bd['_id'] ?>')" 
                                    class="text-red-600 hover:text-red-800 ml-2">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sidebar toggle functionality (same as original)
        function toggleLogoMenu() {
            const menu = document.getElementById('logoMenu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const logoMenu = document.getElementById('logoMenu');
            const logoButton = document.querySelector('.mb-8 button');
            
            if (!logoButton.contains(event.target) && !logoMenu.contains(event.target)) {
                logoMenu.classList.add('hidden');
            }
        });

        window.addEventListener('DOMContentLoaded', () => {
            const flash = sessionStorage.getItem('flash');
            if (flash) {
                const { type, message } = JSON.parse(flash);
                const container = document.createElement('div');
                container.className = `p-4 mb-6 rounded-lg ${type === 'success' ? 'bg-green-50 border-l-4 border-green-400 text-green-700' : 'bg-red-50 border-l-4 border-red-400 text-red-700'}`;
                container.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas ${type === 'success' ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600'} mr-3"></i>
                        <p>${message}</p>
                    </div>
                `;
                document.querySelector('main').prepend(container);
                sessionStorage.removeItem('flash');
            }
        });


        let slotIndex = <?= count($existingSlots) ?>;


        function addSlotRow() {
            const container = document.getElementById('slotContainer');
            const row = document.createElement('div');
            row.className = "grid grid-cols-6 gap-2 items-center slot-row";

            const hoursOptions = [...Array(24).keys()].map(h => 
                `<option value="${String(h).padStart(2, '0')}">${String(h).padStart(2, '0')}</option>`).join('');
            const minutesOptions = [...Array(60).keys()].map(m => 
                `<option value="${String(m).padStart(2, '0')}">${String(m).padStart(2, '0')}</option>`).join('');

            row.innerHTML = `
                <div class="flex items-center gap-1">
                    <select class="start-hour border p-2 rounded-md">${hoursOptions}</select> :
                    <select class="start-minute border p-2 rounded-md">${minutesOptions}</select>
                </div>
                <div class="flex items-center gap-1">
                    <select class="end-hour border p-2 rounded-md">${hoursOptions}</select> :
                    <select class="end-minute border p-2 rounded-md">${minutesOptions}</select>
                </div>
                <input type="number" class="border p-2 rounded-md limit-input" min="1" required>
                <button type="button" onclick="saveSlot(this)" class="text-green-600 hover:text-green-800"><i class="fas fa-check"></i></button>
                <button type="button" onclick="removeSlot(this)" class="text-red-600 hover:text-red-800"><i class="fas fa-times"></i></button>
                <span></span>
            `;
            container.appendChild(row);
        }

        function saveSlot(btn) {
            const row = btn.closest('.slot-row');
            const start = row.querySelector('.start-hour').value + ':' + row.querySelector('.start-minute').value;
            const end = row.querySelector('.end-hour').value + ':' + row.querySelector('.end-minute').value;
            const limit = row.querySelector('.limit-input').value;

            if (!start || !end || start >= end) {
                sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'End time must be after start time.' }));
                location.reload();
                return;
            }
            if (!/^\d+$/.test(limit) || parseInt(limit) <= 0) {
                sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'Limit must be a positive integer.' }));
                location.reload();
                return;
            }

            fetch('testcenteradmin_timeslot.php?api=add_slot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    start: start,
                    end: end,
                    limit: parseInt(limit)
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('flash', JSON.stringify({ type: 'success', message: 'Slot saved successfully.' }));
                    location.reload();
                } else {
                    sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'Save failed: ' + data.message }));
                    location.reload();
                }
            });
        }

        function enableEdit(btn) {
            const row = btn.closest('.slot-row');
            const inputs = row.querySelectorAll('input');
            const startTime = inputs[0].value.split(":");
            const endTime = inputs[1].value.split(":");
            const currentLimit = inputs[2].value;

            const hoursOptions = [...Array(24).keys()].map(h =>
                `<option value="${String(h).padStart(2, '0')}" ${startTime[0] == String(h).padStart(2, '0') ? 'selected' : ''}>${String(h).padStart(2, '0')}</option>`).join('');
            const minutesOptions = [...Array(60).keys()].map(m =>
                `<option value="${String(m).padStart(2, '0')}" ${startTime[1] == String(m).padStart(2, '0') ? 'selected' : ''}>${String(m).padStart(2, '0')}</option>`).join('');
            const endHoursOptions = [...Array(24).keys()].map(h =>
                `<option value="${String(h).padStart(2, '0')}" ${endTime[0] == String(h).padStart(2, '0') ? 'selected' : ''}>${String(h).padStart(2, '0')}</option>`).join('');
            const endMinutesOptions = [...Array(60).keys()].map(m =>
                `<option value="${String(m).padStart(2, '0')}" ${endTime[1] == String(m).padStart(2, '0') ? 'selected' : ''}>${String(m).padStart(2, '0')}</option>`).join('');

            row.innerHTML = `
                <div class="flex gap-1 items-center">
                    <select class="start-hour border p-2 rounded-md">${hoursOptions}</select> :
                    <select class="start-minute border p-2 rounded-md">${minutesOptions}</select>
                </div>
                <div class="flex gap-1 items-center">
                    <select class="end-hour border p-2 rounded-md">${endHoursOptions}</select> :
                    <select class="end-minute border p-2 rounded-md">${endMinutesOptions}</select>
                </div>
                <input type="number" class="border p-2 rounded-md limit-input" value="${currentLimit}" min="1" required>
                <button type="button" onclick="updateSlot(this)" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" onclick="location.reload()" class="text-gray-600 hover:text-red-600">
                    <i class="fas fa-times"></i>
                </button>
                <span></span>
            `;
        }

        function updateSlot(btn) {
            const row = btn.closest('.slot-row');
            const id = row.getAttribute('data-id');
            const inputs = row.querySelectorAll('input');
            const start = row.querySelector('.start-hour').value + ':' + row.querySelector('.start-minute').value;
            const end = row.querySelector('.end-hour').value + ':' + row.querySelector('.end-minute').value;
            const limit = row.querySelector('.limit-input').value;

            if (!start || !end || start >= end) {
                sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'End time must be after start time.' }));
                location.reload();
                return;
            }
            if (!/^[0-9]+$/.test(limit) || parseInt(limit) <= 0) {
                sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'Limit must be a positive number.' }));
                location.reload();
                return;
            }

            fetch('testcenteradmin_timeslot.php?api=update_slot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, start: start, end: end, limit: parseInt(limit) })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('flash', JSON.stringify({ type: 'success', message: 'Slot updated successfully.' }));
                    location.reload();
                } else {
                    sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'Invaliad update: ' + data.message }));
                    location.reload();
                }
            });
        }

        function deleteSlot(btn) {
            const row = btn.closest('.slot-row');
            const slotId = row.getAttribute('data-id');


            fetch('testcenteradmin_timeslot.php?api=delete_slot', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: slotId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('flash', JSON.stringify({ type: 'success', message: 'Slot deleted successfully.' }));
                    location.reload();
                } else {
                    sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'Delete failed: ' + data.message }));
                    location.reload();
                }
            })
            .catch(error => {
                sessionStorage.setItem('flash', JSON.stringify({ type: 'error', message: 'An error occurred while deleting the time slot.' }));
                location.reload();
            });
        }

        function removeSlot(btn) {
            const row = btn.closest('.slot-row');
            if (row) {
                row.remove();
            }
        }

        function deleteBlockedDate(blockedDateId) {
            fetch('testcenteradmin_timeslot.php?api=delete_blocked_date', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: blockedDateId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    sessionStorage.setItem('flash', JSON.stringify({ 
                        type: 'success', 
                        message: 'Date unblocked successfully' 
                    }));
                    location.reload();
                } else {
                    sessionStorage.setItem('flash', JSON.stringify({ 
                        type: 'error', 
                        message: 'Error: ' + (data.message || 'Failed to unblock date')
                    }));
                    location.reload();
                }
            })
            .catch(error => {
                sessionStorage.setItem('flash', JSON.stringify({ 
                    type: 'error', 
                    message: 'Network error - please try again'
                }));
                location.reload();
            });
        }


    </script>
</body>
</html>