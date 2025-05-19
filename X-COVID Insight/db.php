<?php
require 'vendor/autoload.php'; // Load MongoDB library

$mongoClient = new MongoDB\Client("mongodb+srv://xcovidinsight:FYP25S108@cluster0.vqesy.mongodb.net/");

$db = $mongoClient->db1; // Database name

// Define separate variables for each collection
$patientCollection = $db->patient;  
$testCentre = $db->test_centre;
$doctorCollection = $db->doctor;
?>

