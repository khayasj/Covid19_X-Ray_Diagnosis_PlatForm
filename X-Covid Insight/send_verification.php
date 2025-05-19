<?php
session_start([
    'cookie_lifetime' => 86400, // 24 hours
    'cookie_secure'   => true,  // If using HTTPS
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'  // Adjust based on your needs
]);
require 'vendor/autoload.php';

$response = ['status' => 'error', 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $email = strtolower(trim($_POST['email']));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Generate 6-digit code
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Store in session with expiration (5 minutes)
    $_SESSION['verification'] = [
        'email' => $email,
        'code' => $code,
        'expires' => time() + 300
    ];

    // Send email using SendGrid
    $emailObj = new \SendGrid\Mail\Mail();
    $emailObj->setFrom("xcovidinsight@gmail.com", "X COVID INSIGHT");
    $emailObj->setSubject("Verification Code");
    $emailObj->addTo($email);
    $emailObj->addContent("text/plain", "Your verification code is: $code (valid for 5 minutes)");

    $sendgrid = new \SendGrid('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');
    $sendgrid->send($emailObj);

    $response = ['status' => 'success', 'message' => 'Verification code sent'];
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
exit;