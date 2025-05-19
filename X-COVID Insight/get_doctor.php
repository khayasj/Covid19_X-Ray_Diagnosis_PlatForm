<?php
require 'db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Doctor ID required');
    }

    $doctor = $db->doctor->findOne([
        '_id' => new MongoDB\BSON\ObjectId($_GET['id'])
    ]);

    if (!$doctor) {
        throw new Exception('Doctor not found');
    }

    // Convert BSON to array
    $doctor = iterator_to_array($doctor);
    // Convert UTCDateTime to string
    $doctor['created_at'] = $doctor['created_at']->toDateTime()->format('c');

    echo json_encode($doctor);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}