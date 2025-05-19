<?php
// pricing.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selectedPlan = $_POST['plan'];

    // Connect to DB and store the selected plan or process checkout
    require 'db.php'; // Include DB connection file
    $db->pricing_selections->insertOne([
        'plan' => $selectedPlan,
        'date' => new MongoDB\BSON\UTCDateTime()
    ]);

    echo "You selected the $selectedPlan plan!";
}
?>
