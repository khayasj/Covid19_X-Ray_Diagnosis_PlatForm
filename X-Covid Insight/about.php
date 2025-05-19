<?php
require 'admindb.php'; // Your existing MongoDB connection
session_start();

$collection = $db2->about_us; // Access about_us collection
$aboutData = $collection->findOne(); // Get the about us content
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="bg-gray-50 text-gray-900">

    <!-- Navbar Section -->
    <header class="flex items-center justify-between p-4 border-b bg-white shadow-md">
        <div class="flex items-center space-x-2">
            <i class="fas fa-virus text-2xl text-blue-600"></i>
            <span class="text-2xl font-semibold text-blue-600">X-COVID Insight</span>
        </div>
        <nav class="flex space-x-4 ml-auto">
            <a href="homepage.php" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">Product</a>
            <a href="pricing.php" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">Pricing</a>
            <a href="faq.php" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">FAQ</a>
            <a href="contact.html" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">Contact</a>
            <a href="about.php" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">About</a>
        </nav>
        <!-- Sign In Button -->
        <a href="loginPage.html" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 ml-4">Sign In</a>
        <!-- Register Button -->
        <a href="userregister.html" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 ml-4">Register</a>
    </header>

    <!-- About Section -->
    <section class="py-16 px-6 max-w-7xl mx-auto">
        <h1 class="text-4xl font-bold text-center mb-8 text-blue-600">About Us</h1>
        <p class="text-lg text-center mb-12 max-w-3xl mx-auto leading-relaxed">
            <?php echo htmlspecialchars($aboutData['main_content'] ?? 'At X-COVID Insight, we are on a mission to leverage cutting-edge technology...'); ?>
        </p>

        <!-- Vision Section -->
        <div class="my-12 bg-white shadow-lg rounded-lg p-8 max-w-3xl mx-auto">
            <h3 class="text-2xl font-semibold text-blue-600 mb-4">Our Vision</h3>
            <p class="text-lg text-gray-700">
                <?php echo htmlspecialchars($aboutData['vision'] ?? 'We envision a world where healthcare is smarter, faster, and more accessible...'); ?>
            </p>
        </div>

        <!-- Product Section -->
        <div class="my-12 bg-white shadow-lg rounded-lg p-8 max-w-3xl mx-auto">
            <h3 class="text-2xl font-semibold text-blue-600 mb-4">Our Product</h3>
            <p class="text-lg text-gray-700">
                <?php echo htmlspecialchars($aboutData['product'] ?? 'We provide an integrated platform where test centres can upload X-ray images...'); ?>
            </p>
        </div>

        <!-- Why Choose Us Section -->
        <div class="my-12 bg-white shadow-lg rounded-lg p-8 max-w-3xl mx-auto">
            <h3 class="text-2xl font-semibold text-blue-600 mb-4">Why Choose Us?</h3>
            <ul class="list-disc pl-6 text-lg text-gray-700 space-y-2">
                <?php
                $whyChooseUs = $aboutData['why_choose_us'] ?? [
                    'Fast and reliable COVID-19 prediction based on X-ray images',
                    'Seamless integration with test centres',
                    'Continuous system improvement through feedback',
                    'Our models are trained to detect not just COVID-19, but also pneumonia and other respiratory diseases'
                ];
                
                foreach ($whyChooseUs as $item) {
                    echo '<li>' . htmlspecialchars($item) . '</li>';
                }
                ?>
            </ul>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="bg-gray-900 text-white py-6">
        <div class="text-center">
            <p class="text-sm">&copy; 2025 X-COVID Insight. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
