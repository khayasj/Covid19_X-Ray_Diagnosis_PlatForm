<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $message = $_POST['message'];
    
    $contactData = [
        'name' => $name,
        'surname' => $surname,
        'email' => $email,
        'message' => $message
    ];
    $db->contact_us->insertOne($contactData);
    
    // Show success confirmation in styled HTML
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Message Received - X-COVID Insight</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <meta http-equiv="refresh" content="10;url=contact.html"> <!-- Optional auto-redirect -->
    </head>
    <body class="bg-blue-50 text-gray-800 flex items-center justify-center h-screen">
        <div class="bg-white shadow-xl p-10 rounded-xl max-w-xl text-center">
            <h1 class="text-3xl font-bold text-blue-600 mb-4">Thank You, ' . htmlspecialchars($name) . '!</h1>
            <p class="text-lg mb-6">Your message has been received. We will get back to you shortly.</p>
            
            <div class="flex justify-center gap-4">
                <a href="contact.html" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">Go Back</a>
                <a href="homepage.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 transition">Return to Homepage</a>
            </div>

            <p class="text-sm text-gray-500 mt-6">You will be redirected to the contact page in 10 seconds...</p>
        </div>
    </body>
    </html>
    ';
}
?>

