<?php
require 'db.php'; // MongoDB connection
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request method"]);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['appointment_id'])) {
    echo json_encode(["success" => false, "error" => "Appointment ID is required"]);
    exit;
}

$appointment_id = $data['appointment_id'];

try {
    $appointmentCollection = $db->appointment; // Ensure the correct collection name

    // Delete the appointment
    $deleteResult = $appointmentCollection->deleteOne([
        "_id" => new MongoDB\BSON\ObjectId($appointment_id)
    ]);

    if ($deleteResult->getDeletedCount() > 0) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Appointment not found"]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
