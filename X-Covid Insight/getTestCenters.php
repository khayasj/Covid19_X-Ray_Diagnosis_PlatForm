<?php
// Example: getTestCenters.php

// Connect to MongoDB
require 'db.php'; // Ensure this connects to MongoDB

// Fetch the test centers from the database
$collection = $db->test_centre; // Assuming you have a 'test_centers' collection
$testCenters = $collection->find([], ['projection' => ['name' => 1]]); // Get only the 'name' field

// Convert MongoDB result to an array of names
$centersArray = [];
foreach ($testCenters as $center) {
    $centersArray[] = $center['name'];
}

// Return the data as a JSON response
echo json_encode($centersArray);
?>

