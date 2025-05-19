<?php
require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit;
}

try {
    $deleteResult = $db->timeslot->deleteOne(['_id' => new MongoDB\BSON\ObjectId($input['id'])]);

    if ($deleteResult->getDeletedCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}