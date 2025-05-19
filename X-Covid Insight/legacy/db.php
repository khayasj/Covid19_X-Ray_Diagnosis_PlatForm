<?php
require 'vendor/autoload.php'; // Ensure Composer autoload is working

// MongoDB Atlas connection string
$uri = "mongodb+srv://xcovidinsight:FYP25S108@cluster0.vqesy.mongodb.net/";
$client = new MongoDB\Client($uri);

// Select the database
$db = $client->xcovidinsight;
?>
