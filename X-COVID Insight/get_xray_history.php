<?php
require 'db.php';
session_start();

// Check authentication
if (!isset($_SESSION['email'])) {
    die(json_encode(['error' => 'User is not logged in!']));
}

// Get patient ID
$patientId = $_GET['patient_id'] ?? null;

// Validate patient ID
if (!$patientId || !preg_match('/^[a-f\d]{24}$/i', $patientId)) {
    die(json_encode(['error' => 'Invalid patient ID']));
}

try {
    $collection = $db->xray_records;
    $records = $collection->find(['patient_id' => $patientId])->toArray();

    ob_start(); // Start output buffering
?>
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="grid grid-cols-5 gap-4 font-semibold text-gray-700 text-sm">
                <div>X-Ray ID</div>
                <div>Submitted On</div>
                <div>Diagnosis</div>
                <div>Confidence</div>
                <div>Details</div>
            </div>
        </div>

        <div class="divide-y divide-gray-200">
            <?php foreach ($records as $record): ?>
                <?php
                    $date = new DateTime($record['dateUploaded']);
                    $formattedDate = $date->format('d M Y');

                    // 诊断显示逻辑
                    $labelToShow = $record['predictionResult'];
                    if (
                        isset($record['needs_review'], $record['hasBeenVal'], $record['trueLabel']) &&
                        $record['needs_review'] === true &&
                        $record['hasBeenVal'] === true
                    ) {
                        $labelToShow = $record['trueLabel'];
                    }

                    // 样式
                    $labelClass = str_contains($labelToShow, 'COVID') 
                        ? 'text-red-600 bg-red-50' 
                        : 'text-green-600 bg-green-50';

                    $confidenceText = isset($record['confidence']) ? round($record['confidence'], 2) . '%' : 'N/A';
                ?>
                <div class="grid grid-cols-5 gap-4 px-6 py-4 hover:bg-gray-50 transition-colors text-sm">
                    <div class="font-mono text-purple-600">
                        <?= htmlspecialchars($record['XRayImageId']) ?>
                    </div>
                    <div class="text-gray-500">
                        <?= $formattedDate ?>
                    </div>
                    <div>
                        <span class="<?= $labelClass ?> px-3 py-1 rounded-full text-xs">
                            <?= htmlspecialchars($labelToShow) ?>
                        </span>
                    </div>
                    <div class="text-gray-600">
                        <?= $confidenceText ?>
                    </div>
                    <div>
                        <button onclick="viewXrayDetails('<?= htmlspecialchars($record['image']) ?>')" 
                            class="text-blue-600 hover:text-blue-800 text-sm">
                            View X-Ray Image
                        </button>
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


<?php
    $output = ob_get_clean();
    echo $output;

} catch (Exception $e) {
    die(json_encode(['error' => 'Error connecting to database: ' . $e->getMessage()]));
}
?>