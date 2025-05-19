<?php
require 'db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $testCentreId = new MongoDB\BSON\ObjectId($data['test_center_id']);

    $timeslots = $db->available_slots->find([
        'test_center_id' => $testCentreId
    ])->toArray();

    $uniqueTimes = array_unique(array_column($timeslots, 'time'));
    
    $formattedTimes = array_map(function($time) {
        return [
            'storage' => $time,
            'display' => date('h:i A', strtotime($time))
        ];
    }, $uniqueTimes);

    echo json_encode([
        'times' => $formattedTimes
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>