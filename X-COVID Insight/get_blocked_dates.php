<?php
require 'db.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $testCenterId = new MongoDB\BSON\ObjectId($data['test_center_id']);
    
    $blockedDates = $db->blocked_dates->find(
        ['test_center_id' => $testCenterId],
        ['projection' => ['date' => 1, '_id' => 0]]
    )->toArray();

    echo json_encode(array_column($blockedDates, 'date'));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>