<?php
require 'db.php';
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$patient_id = (string)$_SESSION['id'];
$appointmentCollection = $db->appointment;
$testCentreCollection = $db->test_centre;
$timeSlotCollection = $db->timeslot;

$appointments = $appointmentCollection->find(["patient_id" => $patient_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments | X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .animate-fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="bg-gray-50">

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
        <h1 class="text-3xl font-bold text-gray-900">My Appointments</h1>
        <a href="book_appointment.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
            <i class="fas fa-plus-circle mr-2"></i> New Appointment
        </a>
    </div>

    <div class="space-y-4">
        <?php foreach ($appointments as $appointment): ?>
        <?php
        if (!isset($appointment['appointment_date'], $appointment['test_centre_id'])) continue;
        
        $dateObject = new DateTime($appointment['appointment_date']);
        $formattedDate = $dateObject->format('j M Y');
        $timeSlot = $timeSlotCollection->findOne(["_id" => new MongoDB\BSON\ObjectId($appointment['timeslot_id'])]);
        $formattedTime = isset($timeSlot['StartTime'], $timeSlot['EndTime'])
            ? date('g:i A', strtotime($timeSlot['StartTime'])) . ' - ' . date('g:i A', strtotime($timeSlot['EndTime']))
            : 'Time Unavailable';
        $testCentre = $testCentreCollection->findOne(["_id" => new MongoDB\BSON\ObjectId($appointment['test_centre_id'])]);
        $isCompleted = strtotime($appointment['appointment_date']) < time();
        ?>
        
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 <?= $isCompleted ? 'opacity-75' : 'hover:shadow-md' ?> transition-all">
            <div class="flex items-start p-6">
                <div class="flex-shrink-0 bg-gradient-to-br from-blue-600 to-blue-400 text-white rounded-xl p-4 text-center w-24">
                    <div class="text-2xl font-bold"><?= $dateObject->format('j') ?></div>
                    <div class="text-sm uppercase tracking-wide"><?= $dateObject->format('M') ?></div>
                    <div class="text-xs mt-1"><?= $dateObject->format('Y') ?></div>
                </div>
                
                <div class="ml-6 flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900"><?= $testCentre['name'] ?? 'Unknown Centre' ?></h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?= $isCompleted ? 'bg-gray-100 text-gray-600' : 'bg-green-100 text-green-700' ?>">
                            <?= $isCompleted ? 'Completed' : 'Upcoming' ?>
                        </span>
                    </div>
                    
                    <div class="mt-2 text-gray-600">
                    <p class="flex items-center">
                        <i class="fas fa-clock text-gray-400 mr-2"></i>
                        <?= htmlspecialchars($formattedTime) ?>
                    </p>

                    </div>
                    
                    <div class="mt-4 flex items-center space-x-3">
                        <?php if ($isCompleted): ?>
                        <button data-appointment-id="<?= $appointment['_id'] ?>" 
                                class="review-btn inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            <i class="fas fa-comment-medical mr-2"></i> Write Review
                        </button>
                        <?php else: ?>
                        <button onclick="openModal('<?= $appointment['_id'] ?>')" 
                                class="cancel-btn inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors">
                            <i class="fas fa-times-circle mr-2"></i> Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

 <!-- Modals - Updated with proper data attributes -->
 <div id="cancelModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Cancel Appointment</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <p class="text-gray-600 mb-4">Are you sure you want to cancel this appointment? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">No</button>
                <button id="confirmCancel" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Review Modal - Fixed data binding -->
    <div id="reviewModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Write a Review</h3>
                <button onclick="closeReviewModal()" class="text-gray-500 hover:text-gray-700">&times;</button>
            </div>
            <div id="starRating" class="flex justify-center space-x-2 text-3xl mb-4">
                <?php for ($i=1; $i<=5; $i++): ?>
                <span class="star cursor-pointer text-gray-300" data-value="<?= $i ?>">★</span>
                <?php endfor; ?>
            </div>
            <textarea id="reviewText" class="w-full border rounded-lg p-3 mb-4" placeholder="Share your experience..." rows="4"></textarea>
            <div class="flex justify-end space-x-3">
                <button onclick="closeReviewModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button id="submitReview" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Submit</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md text-center">
            <div class="text-green-500 text-5xl mb-4">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 class="text-lg font-semibold mb-2">Success!</h3>
            <p class="text-gray-600 mb-4">Your action was completed successfully.</p>
            <button onclick="closeSuccessModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Close</button>
        </div>
    </div>

    <script>
// Updated JavaScript with proper event delegation and fixes
let appointmentIdToDelete = null;
let selectedRating = 0;
let selectedAppointmentId = null;

// Cancel Appointment Logic
function openModal(appointmentId) {
    appointmentIdToDelete = appointmentId;
    document.getElementById('cancelModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('cancelModal').classList.add('hidden');
}

document.getElementById('confirmCancel').addEventListener('click', () => {
    if (!appointmentIdToDelete) return;

    fetch("deleteAppointment.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ appointment_id: appointmentIdToDelete })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert("Error: " + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
    })
    .finally(() => closeModal());
});

// Review Logic
document.addEventListener('click', (e) => {
    if (e.target.closest('.review-btn')) {
        selectedAppointmentId = e.target.closest('.review-btn').dataset.appointmentId;
        document.getElementById('reviewModal').classList.remove('hidden');
    }
});

document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.value);
        document.querySelectorAll('.star').forEach((s, index) => {
            s.style.color = index < selectedRating ? '#f59e0b' : '#e5e7eb';
        });
    });
});

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
    selectedRating = 0;
    document.getElementById('reviewText').value = '';
    document.querySelectorAll('.star').forEach(s => s.style.color = '#e5e7eb');
}

document.getElementById('submitReview').addEventListener('click', () => {
    const reviewText = document.getElementById('reviewText').value.trim();
    
    if (!selectedRating || !reviewText) {
        alert('Please provide both a rating and review text');
        return;
    }

    fetch("submitClinicReview.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            appointment_id: selectedAppointmentId,
            rating: selectedRating,
            review: reviewText
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeReviewModal();
            document.getElementById('successModal').classList.remove('hidden');
            setTimeout(() => location.reload(), 2000);
        } else {
            alert("Error: " + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred. Please try again.");
    });
});

// Success Modal
function closeSuccessModal() {
    document.getElementById('successModal').classList.add('hidden');
}

// UI Enhancements
document.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('mouseenter', () => btn.classList.add('transform', 'scale-95'));
    btn.addEventListener('mouseleave', () => btn.classList.remove('transform', 'scale-95'));
});
</script>

</body>
</html>