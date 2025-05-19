<?php
session_start();
require 'db.php';
use MongoDB\BSON\ObjectId;

if (!isset($_SESSION["id"])) {
    echo json_encode(["success" => false, "error" => "User not logged in."]);
    exit;
}

if (!isset($_POST["doctorId"])) {
    echo json_encode(["success" => false, "error" => "Missing doctor ID."]);
    exit;
}

try {
    $doctorId = new ObjectId($_POST["doctorId"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Invalid ObjectId format."]);
    exit;
}

$updateData = [];
$profileUpdated = false;

if (isset($_POST["name"])) {
    $updateData["name"] = $_POST["name"];
    $profileUpdated = true;
}

if (isset($_POST["phone"])) {
    $updateData["phone"] = $_POST["phone"];
    $profileUpdated = true;
}

if (!$profileUpdated) {
    echo json_encode(["success" => false, "error" => "No fields to update."]);
    exit;
}

$collection = $db->doctor;
$updateResult = $collection->updateOne(
    ["_id" => $doctorId],
    ['$set' => $updateData]
);

if ($updateResult->getModifiedCount() > 0 || $profileUpdated) {
    echo json_encode(["success" => true, "message" => "Profile updated successfully."]);
} else {
    echo json_encode(["success" => false, "error" => "No changes made to the profile."]);
}
?>