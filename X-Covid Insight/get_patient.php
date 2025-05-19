<?php
// get_patient.php
require 'db.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Patient ID required');
    }

    $patient = $db->patient->findOne([
        '_id' => new MongoDB\BSON\ObjectId($_GET['id'])
    ]);

    if (!$patient) {
        throw new Exception('Patient not found');
    }

    // Convert BSON to array
    $patient = iterator_to_array($patient);
    
    // Keep dob as string
    echo json_encode($patient);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}