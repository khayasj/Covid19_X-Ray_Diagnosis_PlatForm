<?php
require 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(["success" => false, "error" => "User not logged in"]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

// Validate input
if (!isset($input['appointment_id'], $input['rating'], $input['review'])) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

$patient_id = $_SESSION['id'];
$appointment_id = $input['appointment_id'];
$rating = (int) $input['rating'];
$reviewText = trim($input['review']);
$creation_date = new MongoDB\BSON\UTCDateTime();

// Fetch test centre ID from appointment
$appointment = $db->appointment->findOne(["_id" => new MongoDB\BSON\ObjectId($appointment_id)]);

if (!$appointment) {
    echo json_encode(["success" => false, "error" => "Appointment not found"]);
    exit;
}

$test_centre_id = $appointment['test_centre_id'];

// Insert review into database
$reviewData = [
    "patient_id" => $patient_id,
    "appointment_id" => $appointment_id,
    "test_centre_id" => $test_centre_id,
    "creation_date" => $creation_date,
    "review_rating" => $rating,
    "review_description" => $reviewText
];

$db->clinic_review->insertOne($reviewData);

echo json_encode(["success" => true]);
?>
