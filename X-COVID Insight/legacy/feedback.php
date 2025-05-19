<?php
// feedback.php (PHP to fetch feedback data from MongoDB)
require 'db.php';

// Fetch feedback data from the database
$feedbackCollection = $db->feedback;
$feedbacks = $feedbackCollection->find(); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="bg-white text-gray-900 flex flex-col min-h-screen">
 <!-- Navbar Section -->
<header class="flex items-center justify-between p-4 border-b bg-white shadow-md">
    <div class="flex items-center space-x-2">
        <i class="fas fa-virus text-2xl text-blue-600"></i>
        <span class="text-2xl font-semibold text-blue-600">X-COVID Insight</span>
    </div>
    <nav class="flex space-x-4 ml-auto">
        <a href="product.html" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">Product</a>
        <a href="pricing.php" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">Pricing</a>
        <a href="faq.php" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">FAQ</a>
        <a href="feedback.php" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">Feedback</a>
        <a href="contact.html" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">Contact</a>
        <a href="about.html" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">About</a>
    </nav>
    <!-- Sign In Button -->
    <a href="loginPage.html" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 ml-4">Sign In</a>
    <!-- Register Button -->
    <a href="userregister.html" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 ml-4">Register</a>
</header>


    <section id="feedback" class="py-10 px-6 flex-grow"> <!-- Add flex-grow here -->
    <h1 class="text-3xl font-bold mb-6 text-center">User Ratings and Feedback</h1>

        <div class="space-y-6">
    <?php foreach ($feedbacks as $feedback) : ?>
        <div class="bg-gray-50 p-6 rounded-lg shadow-md">
            <div class="flex items-center space-x-2 mb-2">
                <div class="w-10 h-10 rounded-full bg-gray-400"></div>
                <span class="font-semibold"><?= htmlspecialchars($feedback['name']) ?></span>
            </div>
            <h3 class="text-lg font-bold mb-2"><?= htmlspecialchars($feedback['title']) ?></h3> <!-- Title placed below the name -->
            <div class="text-sm text-gray-700 mb-4">
                <?= htmlspecialchars($feedback['message']) ?>
            </div>
            <div class="flex items-center">
                <span class="text-yellow-500">
                    <?php for ($i = 0; $i < $feedback['rating']; $i++) : ?>
                        <i class="fas fa-star"></i>
                    <?php endfor; ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

    </section>
     <!-- Footer Section -->
     <footer class="bg-gray-900 text-white py-6 mt-auto flex-shrink-0">
        <div class="text-center">
            <p class="text-sm">&copy; 2025 X-COVID Insight. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
