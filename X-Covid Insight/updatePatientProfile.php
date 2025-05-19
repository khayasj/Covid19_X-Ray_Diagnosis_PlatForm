<?php
session_start();
require 'db.php'; // Ensure this connects to MongoDB

// Import MongoDB ObjectId
use MongoDB\BSON\ObjectId;

// Check if the user is logged in
if (!isset($_SESSION["id"])) {
    echo json_encode(["success" => false, "error" => "User not logged in."]);
    exit;
}

// Decode JSON request
$data = json_decode(file_get_contents("php://input"), true);

// Ensure patientId exists in the request
if (!isset($data["patientId"])) {
    echo json_encode(["success" => false, "error" => "Missing patient ID."]);
    exit;
}

// Convert the patientId to a MongoDB ObjectId
try {
    $userId = new ObjectId($data["patientId"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Invalid ObjectId format."]);
    exit;
}

// Prepare update data
$updateData = [];
if (isset($data["name"])) $updateData["name"] = $data["name"];
if (isset($data["address"])) $updateData["address"] = $data["address"];
if (isset($data["phone"])) $updateData["phone"] = $data["phone"];
if (isset($data["gender"])) $updateData["gender"] = $data["gender"];
if (isset($data["dob"])) $updateData["dob"] = $data["dob"];

if (empty($updateData)) {
    echo json_encode(["success" => false, "error" => "No fields to update."]);
    exit;
}

// Update the MongoDB collection
$collection = $db->patient; // Change this to your actual collection name
$updateResult = $collection->updateOne(
    ["_id" => $userId],
    ['$set' => $updateData]
);

if ($updateResult->getModifiedCount() > 0) {
    echo json_encode(["success" => true, "message" => "Profile updated successfully."]);
} else {
    echo json_encode(["success" => false, "error" => "No changes made."]);
}

?>

