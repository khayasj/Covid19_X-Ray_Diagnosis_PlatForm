<?php
require 'db.php';  // Include database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userType = $_POST['userType'];  // Get user type (test_center or patient)
    
    if ($userType == 'test_center') {
        // Handling Test Centre registration
        $email = $_POST['email'];
        $testers = $_POST['testers'];
        $hbp = $_POST['hbp'];
        $uen = $_POST['uen'];
        $subscription_plan = $_POST['subscription_plan'];
        $billing_plan = $_POST['billing_plan'];
        $hpb_certification = $_FILES['hpb_certification'];  // Handle file upload

        // Create an associative array with the form data
        $userData = [
            'email' => $email,
            'testers' => $testers,
            'hbp' => $hbp,
            'uen' => $uen,
            'subscription_plan' => $subscription_plan,
            'billing_plan' => $billing_plan,
            'hpb_certification' => $hpb_certification['name'],  // Store file name
            'userType' => $userType
        ];
        
        // Insert into MongoDB Test Centre collection
        $db->test_centers->insertOne($userData);
    } elseif ($userType == 'patient') {
        // Handling Patient registration
        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $dob = $_POST['dob'];
        $sex = $_POST['sex'];
        $allergies = $_POST['allergies'];
        $diagnosis = $_POST['diagnosis'];

        // Create an associative array with the form data
        $patientData = [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'dob' => $dob,
            'sex' => $sex,
            'allergies' => $allergies,
            'diagnosis' => $diagnosis,
            'userType' => $userType
        ];

        // Insert into MongoDB Patients collection
        $db->patients->insertOne($patientData);
    }

    echo "Registration successful!";
}
?>

