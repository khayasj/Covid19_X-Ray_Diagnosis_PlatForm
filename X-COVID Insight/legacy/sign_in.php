<?php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Connect to your database
    require 'db.php'; 

    // Get form data
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the user exists and the password is correct (use prepared statements)
    $user = $db->users->findOne(['email' => $email]);

    if ($user && password_verify($password, $user['password'])) {
        // Start session and store user info
        session_start();
        $_SESSION['user_id'] = $user['_id'];
        $_SESSION['email'] = $user['email'];
        
        // Redirect to user dashboard or homepage
        header("Location: patient_homepage.php");
        exit();
    } else {
        // Invalid login
        $error = "Invalid email or password.";
    }
}
?>
