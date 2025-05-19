<?php
require 'db.php';
session_start();

$collection = $db->xray_records;

// Fetch records by category
$doctorId = $_SESSION['id'] ?? null;
$doctor = $db->doctor->findOne(['_id' => new MongoDB\BSON\ObjectId($doctorId)]);
$location = $doctor['location'] ?? null;

$validationRecords = $collection->find([
    'needs_review' => true,
    'hasBeenVal' => false,
    'confidence' => ['$lt' => 80],
    'test_center' => $location
]);

$approvalRecords = $collection->find([
    'needs_review' => true,
    'hasBeenVal' => false,
    'confidence' => ['$gte' => 80],
    'test_center' => $location
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="bg-gray-100 text-gray-900 invisible">

    <!-- Navigation Bar -->
    <header class="flex items-center justify-between p-4 border-b bg-white shadow-md">
        <!-- Logo Section -->
        <div class="flex items-center space-x-2">
            <i class="fas fa-virus text-2xl text-blue-600"></i>
            <span class="text-xl font-semibold text-gray-800">X-COVID Insight</span>
        </div>

        <!-- Navigation Links -->
        <nav class="flex space-x-4 ml-auto">
            <a href="pendingXRay.php" class="px-4 py-2 rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition duration-300">X-Ray Records</a>
            <a href="doctorProfile.html" class="px-4 py-2 rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200 transition duration-300">My Profile</a>
        </nav>

        <!-- Profile & Logout -->
        <div class="flex items-center space-x-4">
            <a href="Doctor_Homepage.html">
                <img src="assets/images/doctorProfilePicture.jpg" class="w-10 h-10 rounded-full object-cover cursor-pointer" alt="Profile">
            </a>
            <a href="logout.php" class="px-4 py-2 rounded-full bg-red-600 text-white hover:bg-red-700 transition duration-300">Logout</a>
        </div>
    </header>

    <div class="container mx-auto p-6">
        <!-- Tabs Navigation -->
        <div class="mb-6 border-b border-gray-200">
            <div class="flex space-x-4">
                <button onclick="switchTab('validation')" data-tab="validation" class="tab-button px-4 py-2 text-sm font-medium border-b-2">
                    Low confidence validation
                </button>

                <button onclick="switchTab('approval')" data-tab="approval" class="tab-button px-4 py-2 text-sm font-medium border-b-2">
                    High confidence sample
                </button>
            </div>
        </div>

        <!-- Validation Tab Content -->
        <div id="validation-tab" class="tab-content hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <?php foreach ($validationRecords as $record): ?>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <img src="<?= htmlspecialchars($record['image']) ?>" alt="X-Ray Image" class="rounded w-full h-48 object-cover">
                    <h2 class="font-bold mt-2">X-Ray ID: <?= htmlspecialchars($record['XRayImageId']) ?></h2>
                    <p class="text-sm text-gray-600">Low confidence: <?= htmlspecialchars($record['confidence']) ?>%</p>
                    <p class="text-sm text-gray-600">Upload Date: <?= htmlspecialchars($record['dateUploaded']) ?></p>
                    <button class="mt-3 px-4 py-2 bg-black text-white rounded-md" onclick="openModal('<?= $record['_id'] ?>', '<?= htmlspecialchars($record['image']) ?>', '<?= htmlspecialchars($record['predictionResult']) ?>')">Review</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Approval Tab Content -->
        <div id="approval-tab" class="tab-content hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <?php foreach ($approvalRecords as $record): ?>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <img src="<?= htmlspecialchars($record['image']) ?>" alt="X-Ray Image" class="rounded w-full h-48 object-cover">
                    <h2 class="font-bold mt-2">X-Ray ID: <?= htmlspecialchars($record['XRayImageId']) ?></h2>
                    <p class="text-sm text-gray-600">Confidence: <?= htmlspecialchars($record['confidence']) ?>%</p>
                    <p class="text-sm text-gray-600">Upload Date: <?= htmlspecialchars($record['dateUploaded']) ?></p>
                    <button class="mt-3 px-4 py-2 bg-black text-white rounded-md" onclick="openModal('<?= $record['_id'] ?>', '<?= htmlspecialchars($record['image']) ?>', '<?= htmlspecialchars($record['predictionResult']) ?>')">Review</button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="modal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">X-Ray ID: <span id="modalXRayID"></span></h2>
                <button onclick="closeModal()" class="text-xl font-bold">&times;</button>
            </div>

            <!-- Hidden ObjectId -->
            <span id="modalObjectId" class="hidden"></span>

            <img id="modalImage" src="" alt="X-Ray Image" class="my-4 rounded w-full object-cover">
            <p class="text-lg font-semibold">Model Prediction: <span id="modalPrediction" class="text-red-600"></span></p>

            <div class="flex justify-between mt-4">
                <button class="px-4 py-2 bg-red-600 text-white rounded-md" onclick="showDisapproval()">Disapprove</button>
                <button class="px-4 py-2 bg-green-600 text-white rounded-md" onclick="showValidation()">Approve</button>
            </div>
        </div>
    </div>

    <!-- Disapproval Feedback Modal -->
    <div id="disapprovalModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
        <h2 class="text-xl font-bold mb-4">Select Actual Diagnosis</h2>
        <div class="space-y-2" id="diagnosisOptions">
            <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded">
                <input type="radio" name="diagnosis" value="COVID" class="form-radio">
                <span>COVID</span>
            </label>
            <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded">
                <input type="radio" name="diagnosis" value="Lung_Opacity" class="form-radio">
                <span>Lung_Opacity</span>
            </label>
            <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded">
                <input type="radio" name="diagnosis" value="Normal" class="form-radio">
                <span>Normal</span>
            </label>
            <label class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded">
                <input type="radio" name="diagnosis" value="Viral Pneumonia" class="form-radio">
                <span>Viral Pneumonia</span>
            </label>
        </div>
        <div class="flex justify-between mt-6">
            <button class="px-4 py-2 bg-gray-400 text-white rounded-md" onclick="closeDisapproval()">Cancel</button>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-md" onclick="submitDisapproval()">Submit</button>
        </div>
    </div>
</div>

    <!-- Prediction Approved Modal -->
    <div id="approvedModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
        <div class="bg-[#D1FFBD] text-black p-6 rounded-lg shadow-lg max-w-md w-full text-center">
            <h2 class="text-2xl font-bold">Prediction Approved</h2>
            <button class="mt-4 px-4 py-2 bg-black text-white font-semibold rounded-md" onclick="closeApproved()">OK</button>
        </div>
    </div>


    <!-- Feedback Received Modal -->
    <div id="feedbackModal" class="fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50 hidden">
        <div class="bg-[#D1FFBD] text-black p-6 rounded-lg shadow-lg max-w-md w-full text-center">
            <h2 class="text-2xl font-bold">Feedback Received</h2>
            <button class="mt-4 px-4 py-2 bg-black text-white font-semibold rounded-md" onclick="closeFeedback()">OK</button>
        </div>
    </div>

    <script>
    // Tab switching functionality
    function switchTab(tabName) {
        localStorage.setItem('activeTab', tabName);

        document.querySelectorAll('.tab-button').forEach(button => {
            const isActive = button.dataset.tab === tabName;
            button.classList.toggle('active', isActive);
            button.classList.toggle('text-blue-600', isActive);
            button.classList.toggle('border-blue-600', isActive);
            button.classList.toggle('text-gray-500', !isActive);
            button.classList.toggle('border-transparent', !isActive);
        });

        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('active');
        });

        const target = document.getElementById(`${tabName}-tab`);
        if (target) {
            target.classList.remove('hidden');
            target.classList.add('active');
        }
    }


    // Keep existing modal functions unchanged
    function openModal(id, imageUrl, prediction) {
        document.getElementById('modalObjectId').innerText = id;
        document.getElementById('modalImage').src = imageUrl;
        document.getElementById('modalPrediction').innerText = prediction;
        document.getElementById('modal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modal').classList.add('hidden');
    }

    function updateValidation(id, validated, reason = "") {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('validated', validated);

        if (validated) {
            const prediction = document.getElementById('modalPrediction').innerText;
            formData.append('trueLabel', prediction);
        } else{
            formData.append('trueLabel', reason);
        }

        fetch("update_validation.php", {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (validated) {
                    document.getElementById('approvedModal').classList.remove('hidden');
                } else {
                    document.getElementById('feedbackModal').classList.remove('hidden');
                }
                closeDisapproval();
                closeModal();
            } else {
                alert("Update failed: " + (data.error || "Unknown error"));
            }
        })
        .catch(error => console.error("Error:", error));
    }


    function showValidation() {
        let id = document.getElementById('modalObjectId').innerText;
        updateValidation(id, true);
    }

    function showDisapproval() {
        const prediction = document.getElementById('modalPrediction').innerText.trim();
        const options = document.querySelectorAll('#diagnosisOptions input[name="diagnosis"]');

        options.forEach(option => {
            const label = option.closest('label');
            if (option.value === prediction) {
                label.classList.add('hidden');
                option.checked = false;
            } else {
                label.classList.remove('hidden');
            }
        });

        document.getElementById('disapprovalModal').classList.remove('hidden');
        document.getElementById('modal').classList.add('hidden');
    }

    function closeDisapproval() {
        document.getElementById('disapprovalModal').classList.add('hidden');
        document.getElementById('modal').classList.remove('hidden');
    }

    function submitDisapproval() {
        const selectedDiagnosis = document.querySelector('input[name="diagnosis"]:checked');
        if (!selectedDiagnosis) {
            alert("Please select a diagnosis");
            return;
        }

        let objectId = document.getElementById('modalObjectId').innerText;
        updateValidation(objectId, false, selectedDiagnosis.value);
    }

    function closeFeedback() {
        document.getElementById('feedbackModal').classList.add('hidden');
        window.location.reload(); // Refresh to update the list
    }

    function closeApproved() {
        document.getElementById('approvedModal').classList.add('hidden');
        window.location.reload(); // Refresh after approval
    }

    window.addEventListener('DOMContentLoaded', () => {
        const savedTab = localStorage.getItem('activeTab');
        if (savedTab === 'validation' || savedTab === 'approval') {
            switchTab(savedTab);
        } else {
            switchTab('validation');
        }
        document.body.classList.remove('invisible');
    });


    </script>

</body>
</html>