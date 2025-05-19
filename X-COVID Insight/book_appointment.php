<?php
require 'db.php';

session_start();
$patientId = $_SESSION['id'];
// Initialize collections
$testCentre = $db->test_centre;
$blockedDatesCollection = $db->blocked_dates;

// Get rating filter
$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : null;
$targetDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('+1 day'));

// Get all centers first
try {
    $allCentres = $testCentre->find()->toArray();
} catch (MongoDB\Exception\Exception $e) {
    die("Error fetching test centers: " . $e->getMessage());
}

// Filter and calculate ratings
$filteredCentres = [];
foreach ($allCentres as $centre) {
    $reviews = $db->clinic_review->find([
        'test_centre_id' => (string)$centre['_id']
    ])->toArray();
    
    $totalReviews = count($reviews);
    $averageRating = $totalReviews > 0 
        ? number_format(array_sum(array_column($reviews, 'review_rating')) / $totalReviews, 1)
        : 0;

    // Apply rating filter
    if (!$ratingFilter || $averageRating >= $ratingFilter) {
        $centre['calculated_rating'] = $averageRating;
        $centre['total_reviews'] = $totalReviews;
        $filteredCentres[] = $centre;
    }
}

// Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) throw new Exception("Invalid request");

        // Convert IDs to ObjectId
        $testCentreId = $data['test_center_id'];
        $patientId = $data['patient_id'];
        $timeslotId = new MongoDB\BSON\ObjectId($data['timeslot_id']);

        $appointmentDate = $data['appointment_date'];

        // Create appointment document
        $appointment = [
            "patient_id" => $patientId,
            "test_centre_id" => $testCentreId,
            "timeslot_id" => $timeslotId,
            "appointment_date" => $appointmentDate,
            "remarks" => $data['remarks'] ?? '',
            "creation_date" => new MongoDB\BSON\UTCDateTime(),
            "present" => false
        ];

        $insertResult = $db->appointment->insertOne($appointment);

        if ($insertResult->getInsertedCount() > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Appointment booked successfully",
                "data" => [
                    "test_centre" => $data['test_centre_name'],
                    "date" => $appointmentDate,
                    "time" => $data['appointment_time']
                ]
            ]);
        } else {
            throw new Exception("Failed to save appointment");
        }

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="bg-gray-100">

    <!-- Navigation Bar -->
    <header class="fixed top-0 left-0 w-full flex items-center justify-between p-4 border-b bg-white shadow-md z-50">
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


     <!-- Sort & Filter Modal -->
     <div id="filterModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center">
        <div class="bg-white p-6 rounded-lg shadow-lg w-[400px]">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold">Rating Filter</h2>
                <button id="closeFilter" class="text-black text-2xl">&times;</button>
            </div>

            <!-- Rating Filter -->
            <div class="star-rating">
                 <input type="radio" id="star5" name="rating" value="5">
                <label for="star5">★</label>
    
                <input type="radio" id="star4" name="rating" value="4">
                <label for="star4">★</label>
    
                <input type="radio" id="star3" name="rating" value="3">
                <label for="star3">★</label>
    
                <input type="radio" id="star2" name="rating" value="2">
                <label for="star2">★</label>
    
                <input type="radio" id="star1" name="rating" value="1">
                <label for="star1">★</label>
            </div>

            <!-- Apply & Cancel Buttons -->
            <div class="flex justify-between mt-6">
                <button id="cancelFilter" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                <button id="applyFilter" class="px-4 py-2 bg-black text-white rounded">Apply</button>
            </div>
        </div>
    </div>

    <div class="container mx-auto p-6 mt-20">
        <h1 class="text-3xl font-bold mb-4">Book Appointment</h1>

        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-2">
                <label for="targetDate" class="font-semibold text-gray-700 whitespace-nowrap">Select Date:</label>
                <input type="date" id="targetDate" name="targetDate"
                    class="p-2 border rounded h-10"
                    value="<?= isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('+1 day')) ?>"
                    min="<?= date('Y-m-d') ?>">

                    <?php if ($ratingFilter): ?>
                    <div class="bg-blue-100 px-3 py-1 rounded-full text-sm flex items-center gap-2">
                        <span>⭐ <?= $ratingFilter ?>+ Stars</span>
                        <a href="?date=<?= $targetDate ?>" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <button id="filterButton" class="px-4 py-2 h-10 bg-gray-300 hover:bg-gray-400 rounded flex items-center gap-2">
                <i class="fas fa-sort"></i>
                <i class="fas fa-filter"></i>
                Rating Filter
            </button>
        </div>

                    <!-- Test Centres List -->
                    <?php 
                    foreach ($filteredCentres as $centre):
                        $centreId = $centre['_id'];

                        // Fetch reviews for this center
                        $reviews = $db->clinic_review->find(['test_centre_id' => (string)$centreId])->toArray();
                        $totalReviews = count($reviews);
                        $averageRating = $totalReviews > 0 
                        ? number_format(array_sum(array_column($reviews, 'review_rating')) / $totalReviews, 1)
                        : 'N/A';

                        $isBlocked = $blockedDatesCollection->findOne([
                            'test_center_id' => $centreId,
                            'date' => $targetDate
                        ]);
                        
                        $statusText = $isBlocked ? 'Closed' : ($centre['status'] ?? 'Closed');
                        $statusClass = $isBlocked ? 'text-red-600' : 'text-green-600';

                        $availableSlots = $db->available_slots->find(['test_center_id' => $centreId])->toArray();
                        $availableDates = array_unique(array_column($availableSlots, 'date'));
                        $availableDatesJson = htmlspecialchars(json_encode($availableDates), ENT_QUOTES, 'UTF-8');

                        // Get current test center timeslot
                        $timeSlots = $db->timeslot->find(
                            ['TestCenterID' => $centreId],
                            ['sort' => ['StartTime' => 1]]
                        )->toArray();
                    ?>
                        <div class="bg-white p-4 rounded-lg shadow-md mb-4" 
                            data-centre="<?= (string)$centreId ?>"
                            data-available-dates="<?= $availableDatesJson ?>">

                            <h2 class="text-xl font-semibold"><?= $centre['name'] ?></h2>
                            <p class="text-gray-600 text-sm">⭐ <?= $averageRating ?> (<?= $totalReviews ?> reviews)</p>
                            <p class="text-gray-500"><?= $centre['address'] ?? '' ?></p>
                            <p class="<?= $statusClass ?>">
                                <?= $statusText ?>
                            </p>

                            <!-- show timeslot -->
                            <div class="mt-2 time-slots flex flex-wrap gap-3">
                            <?php if ($isBlocked): ?>
                                
                            <?php elseif (empty($timeSlots)): ?>
                                <p class="text-gray-500 text-sm">No available slots</p>
                                <?php else: ?>
                                    <?php foreach ($timeSlots as $slot): 
                                        $startTime = $slot['StartTime'];
                                        $endTime = $slot['EndTime'];
                                        $peopleAllowed = $slot['PeopleAllowed'];
                                        $slotId = $slot['_id'];

                                        $bookedCount = $db->appointment->countDocuments([
                                            'test_centre_id' => (string)$centreId,
                                            'timeslot_id' => $slotId,
                                            'appointment_date' => $targetDate
                                        ]);

                                        $remaining = max(0, $peopleAllowed - $bookedCount);

                                        $formattedStart = date('h:i A', strtotime($startTime));
                                        $formattedEnd = date('h:i A', strtotime($endTime));
                                    ?>
                                    <button 
                                        class="px-4 py-2 rounded-md text-sm 
                                            <?= $remaining > 0 ? 'bg-gray-200 text-black hover:bg-gray-300' : 'bg-gray-100 text-gray-400 cursor-not-allowed' ?>"
                                        style="min-width: 150px;"
                                        <?= $remaining > 0 ? 
                                            "onclick=\"openBookingModal(
                                                '".(string)$centreId."',
                                                '$formattedStart - $formattedEnd',
                                                '".addslashes($centre['name'])."',
                                                '".addslashes($centre['address'] ?? '')."',
                                                '".(string)$slotId."')\"" 
                                            : 'disabled' ?>
                                    >
                                        <div class="font-medium"><?= $formattedStart ?> - <?= $formattedEnd ?></div>
                                        <div class="text-xs text-gray-600">Remaining: <?= $remaining ?></div>
                                    </button>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>


        <!-- Booking Modal -->
        <div id="bookingModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                <h2 class="text-xl font-semibold mb-2">Confirm Appointment</h2>
                <div class="text-gray-600 space-y-1 mb-2">
                    <p><strong>Test Centre:</strong> <span id="centreName"></span></p>
                    <p><strong>Address:</strong> <span id="centreAddress"></span></p>
                    <p><strong>Time Slot:</strong> <span id="selectedTime"></span></p>
                </div>
                <label class="block mt-3">Remarks (Optional):</label>
                <textarea id="appointmentRemarks" class="p-2 border rounded w-full" placeholder="Enter remarks..."></textarea>

                <div class="flex justify-end mt-4">
                    <button onclick="closeModal()" class="px-4 py-2 bg-gray-400 text-white rounded-md mr-2">Cancel</button>
                    <button id="confirmBookingBtn" class="px-4 py-2 bg-blue-500 text-white rounded-md">Confirm</button>
                </div>
                <div id="availableDates" class="hidden" data-dates=""></div>
            </div>
        </div>

        <!-- Success Modal -->
        <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center">
            <div class="bg-white p-6 rounded-lg shadow-lg w-96">
                <div class="text-center">
                    <div class="text-green-500 text-5xl mb-4">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="text-xl font-semibold mb-2">Appointment Booked Successfully!</h2>
                    <div class="text-left">
                        <p class="mt-2"><strong>Test Centre:</strong> <span id="modalTestCentre"></span></p>
                        <p class="mt-2"><strong>Service Type:</strong> Chest X-Ray Diagnosis</p>
                        <p class="mt-2"><strong>Date:</strong> <span id="modalDate"></span></p>
                        <p class="mt-2"><strong>Time:</strong> <span id="modalTime"></span></p>
                    </div>
                    <button onclick="closeSuccessModal()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
    let selectedCentreId = "";
    let selectedTime = "";
    let selectedCentreName = "";
    let selectedTimeslotId = "";

    // Add after initializing selectedCentreId
    let blockedDates = [];

    // Fetch blocked dates when opening modal
    async function openBookingModal(centreId, time, centreName, centreAddress, timeslotId){
        selectedCentreId = centreId;
        selectedTime = time;
        selectedCentreName = centreName;
        selectedTimeslotId = timeslotId;
        
        try {
            // Fetch blocked dates
            const response = await fetch('get_blocked_dates.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({patient_id: "<?= $patientId ?>",
                    appointment_date: document.getElementById("targetDate").value,
                    appointment_time: selectedTime,
                    test_center_id: selectedCentreId,
                    test_centre_name: selectedCentreName,
                    timeslot_id: selectedTimeslotId })
            });
            blockedDates = await response.json();

        } catch (error) {
            console.error('Error:', error);
        }

        document.getElementById("centreName").textContent = centreName;
        document.getElementById("centreAddress").textContent = centreAddress;
        document.getElementById("selectedTime").textContent = time;
        document.getElementById("bookingModal").classList.remove("hidden");
    }

function showAvailableDates(dates) {
    const datesList = dates.map(d => new Date(d).toLocaleDateString()).join(', ');
    const helpText = dates.length > 0 
        ? `Available dates: ${datesList}`
        : 'No available dates for this center';
    
    // Add or update help text
    let helpElement = document.getElementById('dateHelpText');
    if (!helpElement) {
        helpElement = document.createElement('p');
        helpElement.id = 'dateHelpText';
        helpElement.className = 'text-sm text-gray-500 mt-2';
        document.querySelector('#bookingModal .modal-content').appendChild(helpElement);
    }
    helpElement.textContent = helpText;
}

    function closeModal() {
        document.getElementById("bookingModal").classList.add("hidden");
    }

    function closeSuccessModal() {
        document.getElementById("successModal").classList.add("hidden");
        location.reload();
    }

    // Filter Modal Handling
    document.getElementById("filterButton").addEventListener("click", () => {
        document.getElementById("filterModal").classList.remove("hidden");
    });

    document.getElementById("closeFilter").addEventListener("click", () => {
        document.getElementById("filterModal").classList.add("hidden");
    });

    document.getElementById("cancelFilter").addEventListener("click", () => {
        document.getElementById("filterModal").classList.add("hidden");
    });

    document.getElementById("applyFilter").addEventListener("click", () => {
    const selectedRating = document.querySelector('input[name="rating"]:checked');
    const params = new URLSearchParams();
    
    if (selectedRating) {
        params.set('rating', selectedRating.value);
    }
    
    // Preserve date filter
    const currentDate = document.getElementById("targetDate").value;
    if (currentDate) {
        params.set('date', currentDate);
    }
    
    window.location.search = params.toString();
});

    // Initialize selected rating from URL
    const urlParams = new URLSearchParams(window.location.search);
    const selectedRating = urlParams.get('rating');
    if (selectedRating) {
        document.querySelector(`input[value="${selectedRating}"]`).checked = true;
    }

    // Booking Form Submission
    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("confirmBookingBtn").addEventListener("click", () => {
            const appointmentDate = document.getElementById("targetDate").value;
            const remarks = document.getElementById("appointmentRemarks").value;

            if (!appointmentDate) {
                alert("Please select a date");
                return;
            }

            fetch('book_appointment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    patient_id: <?= json_encode($patientId) ?>,
                    appointment_date: appointmentDate,
                    appointment_time: selectedTime,
                    test_center_id: selectedCentreId,
                    test_centre_name: selectedCentreName,
                    timeslot_id: selectedTimeslotId,
                    remarks: remarks
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("modalTestCentre").textContent = data.data.test_centre;
                    document.getElementById("modalDate").textContent = data.data.date;
                    document.getElementById("modalTime").textContent = data.data.time;
                    closeModal();
                    document.getElementById("successModal").classList.remove("hidden");
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while booking the appointment.");
            });
        });
    });

    // Check if date is available
    async function checkDateAvailability(selectedDate) {
        try {
            const response = await fetch('check_availability.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    test_center_id: selectedCentreId,
                    date: selectedDate
                })
            });
            
            const data = await response.json();
            
            if (!data.available) {
                alert('This date is not available for booking');
                document.getElementById('appointmentDate').value = '';
            }
        } catch (error) {
            console.error('Error checking date:', error);
        }
    }

    // Update time slot display
    async function updateTimeSlots(centreId) {
    try {
        const response = await fetch('get_timeslots.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                test_center_id: centreId 
            })
        });
        
        const data = await response.json();
        const timeContainer = document.querySelector(`#testCentreList [data-centre="${centreId}"] .time-slots`);
        
        timeContainer.innerHTML = '';
        
        if (data.times && data.times.length > 0) {
            data.times.forEach(time => {
                timeContainer.innerHTML += `
                    <button class="px-3 py-1 bg-gray-200 rounded-md mr-2 hover:bg-gray-300"
                        onclick="openBookingModal('${centreId}', '${time.display}', '${selectedCentreName}')"
                        data-time="${time.storage}">
                        ${time.display}
                    </button>
                `;
            });
        } else {
            timeContainer.innerHTML = '<p class="text-gray-500 text-sm">No available slots</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        timeContainer.innerHTML = '<p class="text-red-500 text-sm">Error loading slots</p>';
    }
}

function isDateBlocked(dateString) {
    return blockedDates.includes(dateString);
}


document.addEventListener("DOMContentLoaded", () => {
    const targetDateInput = document.getElementById("targetDate");
    if (targetDateInput) {
        targetDateInput.addEventListener("change", function () {
            const selectedDate = this.value;
            const params = new URLSearchParams(window.location.search);
            params.set('date', selectedDate);
            window.location.search = '?' + params.toString();
        });
    } else {
        console.warn("targetDate input not found!");
    }
});
    </script>

<style>
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: space-between;
        width: 100%;
    }

    .star-rating input {
        display: none;
    }

    .star-rating label {
        font-size: 40px;
        color: lightgray;
        cursor: pointer;
        transition: color 0.2s;
        flex: 1;
        text-align: center;
    }

    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: #f59e0b;
    }

    input[type="date"]:disabled {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

    /* Make invalid dates appear grayed out (works in some browsers) */
    input[type="date"]:not([min])::-webkit-calendar-picker-indicator,
    input[type="date"]:invalid::-webkit-calendar-picker-indicator {
        filter: grayscale(100%);
        opacity: 0.5;
    }
</style>

</body>
</html>
