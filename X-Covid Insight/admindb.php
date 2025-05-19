<?php
require 'vendor/autoload.php'; // Load MongoDB library

$mongoClient = new MongoDB\Client("mongodb+srv://xcovidinsight:FYP25S108@cluster0.vqesy.mongodb.net/");

$db2 = $mongoClient->db2Admin; // Database name

// Define separate variables for each collection

?>