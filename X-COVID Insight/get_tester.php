<?php
require 'db.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Tester ID required');
    }

    $tester = $db->covid_tester->findOne([
        '_id' => new MongoDB\BSON\ObjectId($_GET['id'])
    ]);

    if (!$tester) {
        throw new Exception('Tester not found');
    }

    // Convert BSON to array
    $tester = iterator_to_array($tester);
    // Convert UTCDateTime to string
    $tester['created_at'] = $tester['created_at']->toDateTime()->format('c');

    echo json_encode($tester);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}