<?php

session_start([
    'cookie_lifetime' => 86400, // 24 hours
    'cookie_secure'   => true,  // If using HTTPS
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'  // Adjust based on your needs
]);
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require 'db.php';

// Load Cloudinary Dependencies
require __DIR__ . '/vendor/autoload.php';
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;

$response = ['status' => 'error', 'message' => 'Unknown error occurred'];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $userType = $_POST['userType'];
        $email = strtolower(trim($_POST['email']));
        $verificationCode = preg_replace('/[^0-9]/', '', $_POST['verificationCode']);
        $verificationCode = substr($verificationCode, 0, 6);

        // Check verification code
        if (empty($_SESSION['verification'])) {
            throw new Exception('No verification code requested');
        }

        if (time() > $_SESSION['verification']['expires']) {
            unset($_SESSION['verification']);
            throw new Exception('Verification code expired');
        }

        if ($_SESSION['verification']['email'] !== $email) {
            throw new Exception('Email does not match verification request');
        }

        if ($_SESSION['verification']['code'] !== $verificationCode) {
            error_log("Code mismatch. Stored: {$_SESSION['verification']['code']} vs Received: $verificationCode");
            throw new Exception('Invalid verification code');
        }

        // Clear verification after successful check
        unset($_SESSION['verification']);

        if ($userType == 'patient') {
            // Patient registration
            $requiredFields = ['name', 'email', 'phone', 'dob', 'gender', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
        
            $patientData = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'dob' => $_POST['dob'],
                'gender' => $_POST['gender'],
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'address' => ''
            ];
        
            $collection = $db->patient;
            $result = $collection->insertOne($patientData);
        
            if ($result->getInsertedCount() == 1) {
                $response = ['status' => 'success', 'message' => 'Patient registered successfully'];
            } else {
                throw new Exception('Failed to register patient');
            }
        } elseif ($userType == 'test_center') {
            // Update required fields
            $requiredFields = ['testCenterName', 'email', 'testers', 'hbp', 'uen', 'subscription_plan', 'billing_plan', 'postal_code', 'street_address', 'unit_number'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $certificationUrl = "";

            if (isset($_FILES['hpb_certification']) && $_FILES['hpb_certification']['error'] == UPLOAD_ERR_OK) {
                // Configure Cloudinary
                Configuration::instance([
                    'cloud' => [
                        'cloud_name' => 'dj2uhnamu',   // Replace with your Cloudinary Cloud Name
                        'api_key'    => '472466731316343',  // Replace with your Cloudinary API Key
                        'api_secret' => '9lrevj01NB327_jbX5YJ_3yI0o8'  // Replace with your Cloudinary API Secret
                    ]
                ]);

                // Upload file to Cloudinary
                $upload = new UploadApi();
                $uploadResponse = $upload->upload($_FILES['hpb_certification']['tmp_name'], [
                    'folder' => 'test_center_certifications'
                ]);

                // Retrieve Cloudinary URL
                $certificationUrl = $uploadResponse['secure_url'];
            }

            // Construct full address
            $fullAddress = sprintf("%s, #%s, %s Singapore",
            $_POST['street_address'],
            $_POST['unit_number'],
            $_POST['postal_code']
            );

            $testCenterData = [
                'name' => $_POST['testCenterName'],
                'email' => $_POST['email'],
                'testers' => (int)$_POST['testers'],
                'hbp' => $_POST['hbp'],
                'uen' => $_POST['uen'],
                'address' => $fullAddress,
                'subscription_plan' => $_POST['subscription_plan'],
                'billing_plan' => $_POST['billing_plan'],
                'hpb_certification' => $certificationUrl,
                'status' => 'Pending',
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];

            $collection = $db->test_centre_applications;
            $result = $collection->insertOne($testCenterData);

            unset($_SESSION['verification_code'][$email]);


            if ($result->getInsertedCount() == 1) {
                $response = ['status' => 'success', 'message' => 'Test center application submitted successfully'];
            } else {
                throw new Exception('Failed to submit test center application');
            }
        } else {
            throw new Exception('Invalid user type');
        }
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>