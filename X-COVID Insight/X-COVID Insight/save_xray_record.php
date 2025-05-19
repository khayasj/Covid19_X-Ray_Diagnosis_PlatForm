<?php
ob_start();

// Load Dependencies
$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    echo json_encode(['success' => false, 'error' => 'Missing composer dependencies']);
    exit;
}
require $autoloadPath;

use MongoDB\BSON\UTCDateTime;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

try {
    // Validate session
    @session_start();
    if (!isset($_SESSION["id"])) {
        throw new Exception("Authentication required", 401);
    }

    // Validate inputs
    header('Content-Type: application/json');
    $requiredFields = ['patientId', 'prediction','confidence', 'originalImage'];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field", 400);
        }
    }

    // Configure Cloudinary
    Configuration::instance([
        'cloud' => [
            'cloud_name' => 'dj2uhnamu',
            'api_key'    => '472466731316343',
            'api_secret' => '9lrevj01NB327_jbX5YJ_3yI0o8'
        ]
    ]);


    $originalImage = $_POST['originalImage'];
    
    // Extract base64 data
    if (preg_match('/^data:image\/(jpe?g|png);base64,/i', $originalImage, $matches)) {
        $imageType = $matches[1];
        $cleanData = substr($originalImage, strpos($originalImage, ',') + 1);
        $decodedData = base64_decode($cleanData);
        
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'original'). '.' . $imageType;
        file_put_contents($tempFile, $decodedData);
        
        // Validate image
        $size = getimagesize($tempFile);
        if (!$size || !in_array($size['mime'], ['image/jpeg', 'image/png'])) {
            throw new Exception("Invalid image format", 400);
        }
    } else {
        throw new Exception("Invalid heatmap data format. Must be JPEG or PNG", 400);
    }

    // Upload to Cloudinary
    $upload = new UploadApi();
    $uploadResponse = $upload->upload($tempFile, [
        'folder' => 'xray_heatmaps',
        'transformation' => [
            ['quality' => 'auto', 'fetch_format' => 'auto']
        ]
    ]);

    // Cleanup temp file
    unlink($tempFile);


    // MongoDB Integration
    require 'db.php';
    $collection = $db->xray_records;

    // Generate X-Ray ID
    $lastRecord = $collection->findOne([], ['sort' => ['_id' => -1]]);
    $xrayId = 'XR-' . str_pad(($lastRecord ? intval(substr($lastRecord['XRayImageId'], 3)) : 0) + 1, 2, '0', STR_PAD_LEFT);

    $records = $db->xray_records->find(['alreadyTrain' => false]);
    $classCounts = [];

    foreach ($records as $rec) {
        $class = $rec['predictionResult'] ?? null;
        if ($class) {
            $classCounts[$class] = ($classCounts[$class] ?? 0) + 1;
        }
    }

    // Sampling logic
    $needsReview = false;
    $prediction = $_POST['prediction'];
    $confidence = isset($_POST['confidence']) ? (float)$_POST['confidence'] : 0.0;
    if ($confidence < 80) {
        $needsReview = true;
    } elseif (!empty($classCounts)) {
        $currentCount = $classCounts[$prediction] ?? 0;
        $minCount = min($classCounts);
        $threshold = $minCount + intval(0.08 * array_sum($classCounts));
        $needsReview = $currentCount <= $threshold ? true : (mt_rand(0, 9) === 0);
    }
    // Create document
    $document = [
        'XRayImageId' => $xrayId,
        'patient_id' => $_POST['patientId'],
        'dateUploaded' => date('Y-m-d'),
        'predictionResult' => substr($_POST['prediction'], 0, 50),
        'confidence' => isset($_POST['confidence']) ? (float)$_POST['confidence'] : 0.0,
        'image' => $uploadResponse['secure_url'],
        'tester_id' => $_SESSION["id"],
        'test_center' => $_SESSION["location"] ?? 'Unknown Location', 
        'expiresAt' => time() + 900
    ];
    if ($needsReview) {
        $document['needs_review'] = true;
        $document['hasBeenVal'] = false;
        $document['trueLabel'] = "";
        $document['alreadyTrain'] = false;
    }

    $result = $collection->insertOne($document);
    if (!$result->getInsertedId()) {
        throw new Exception("Database insertion failed", 500);
    }

    echo json_encode([
        'success' => true,
        'xrayId' => $xrayId,
        'heatmapUrl' => $uploadResponse['secure_url'],
        'expires' => 900,
        'needsReview' => $needsReview
    ]);

} catch (Throwable $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} finally {
    ob_end_flush();
    exit;
}
