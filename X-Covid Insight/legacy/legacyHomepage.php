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
    <title>Homepage - X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
</head>
<body class="bg-gradient-to-r from-blue-100 to-blue-200 text-gray-900 font-sans">

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
            <a href="about.html" class="px-4 py-2 rounded-lg text-gray-700 hover:bg-gray-200 transition">About</a>
        </nav>
        <!-- Sign In Button -->
        <a href="loginPage.html" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 ml-4">Sign In</a>
        <!-- Register Button -->
        <a href="userregister.html" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 ml-4">Register</a>
    </header>

    <!-- Hero Section with Videos -->
<section class="bg-blue-100 py-16 px-6 text-center pt-25"> <!-- Added pt-32 to provide top padding -->
    <h1 class="text-5xl font-bold text-blue-800 mb-6">Welcome to X-COVID Insight</h1>
    <p class="text-lg text-gray-700 mb-10">Revolutionizing COVID-19 Diagnosis with AI-powered X-Ray Analysis</p>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Video 1 -->
        <div class="rounded-lg overflow-hidden shadow-lg bg-white">
            <video class="w-full h-48 object-cover" controls>
                <source src="video1.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-800">How Our AI Works</h3>
            </div>
        </div>
        <!-- Video 2 -->
        <div class="rounded-lg overflow-hidden shadow-lg bg-white">
            <video class="w-full h-48 object-cover" controls>
                <source src="video2.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-800">Patient Appointment System</h3>
            </div>
        </div>
        <!-- Video 3 -->
        <div class="rounded-lg overflow-hidden shadow-lg bg-white">
            <video class="w-full h-48 object-cover" controls>
                <source src="video3.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="p-4">
                <h3 class="text-lg font-semibold text-gray-800">Doctor’s Validation & Feedback</h3>
            </div>
        </div>
    </div>
</section>


    <!-- Product Features Section -->
    <section class="py-20 px-6 bg-white">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">Key Features of Our Product</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
            <!-- Feature 1 -->
            <div class="bg-gradient-to-tl from-blue-300 to-blue-500 p-8 rounded-2xl shadow-xl text-white transform transition hover:scale-105 hover:shadow-2xl">
                <h3 class="text-2xl font-semibold mb-4">AI-Powered X-Ray Analysis</h3>
                <p class="text-lg">Advanced AI technology that predicts COVID-19 likelihood through X-ray image analysis.</p>
            </div>
            <!-- Feature 2 -->
            <div class="bg-gradient-to-tl from-green-300 to-green-500 p-8 rounded-2xl shadow-xl text-white transform transition hover:scale-105 hover:shadow-2xl">
                <h3 class="text-2xl font-semibold mb-4">Patient Appointment System</h3>
                <p class="text-lg">Book and manage appointments based on test center location, ratings, and availability.</p>
            </div>
            <!-- Feature 3 -->
            <div class="bg-gradient-to-tl from-yellow-300 to-yellow-500 p-8 rounded-2xl shadow-xl text-white transform transition hover:scale-105 hover:shadow-2xl">
                <h3 class="text-2xl font-semibold mb-4">Doctor Validation & Feedback</h3>
                <p class="text-lg">Doctors validate AI predictions, ensuring higher accuracy and reliable results.</p>
            </div>
        </div>
    </section>

    <!-- Product Comparison Section -->
<section id="product-comparison" class="py-10 px-6 bg-gradient-to-r from-blue-50 to-blue-100">
    <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Product Comparison</h1>

    <div class="overflow-x-auto bg-white rounded-lg shadow-lg">
        <table class="min-w-full table-auto border-collapse">
            <thead>
                <tr class="bg-blue-600 text-white">
                    <th class="border p-4 text-left">Product Features</th>
                    <th class="border p-4 text-left">Lunit Insight Covid-19</th>
                    <th class="border p-4 text-left">Qure.ai</th>
                    <th class="border p-4 text-left">Satori</th>
                    <th class="border p-4 text-left">Aidoc</th>
                    <th class="border p-4 text-left">Radiobotics</th>
                    <th class="border p-4 text-left">Envisionit Deep AI</th>
                    <th class="border p-4 text-left font-semibold">Our Product</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1 -->
                <tr class="hover:bg-gray-100 transition-all">
                    <td class="border p-4">Login/Logout</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500 bg-green-200 font-semibold"><i class="fas fa-check-circle"></i> Yes</td>
                </tr>
                <!-- Row 2 -->
                <tr class="hover:bg-gray-100 transition-all">
                    <td class="border p-4">Image Upload</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500 bg-green-200 font-semibold"><i class="fas fa-check-circle"></i> Yes</td>
                </tr>
                <!-- Row 3 -->
                <tr class="hover:bg-gray-100 transition-all">
                    <td class="border p-4">Identifies non X-Ray images</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-red-600 bg-red-200 font-semibold"><i class="fas fa-times-circle"></i> No</td>
                </tr>
                <!-- Row 4 -->
                <tr class="hover:bg-gray-100 transition-all">
                    <td class="border p-4">Generates Reports</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500 bg-green-200 font-semibold"><i class="fas fa-check-circle"></i> Yes</td>
                </tr>
                <!-- Row 5 -->
                <tr class="hover:bg-gray-100 transition-all">
                    <td class="border p-4">Model Performance Monitoring</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-green-500"><i class="fas fa-check-circle"></i> Yes</td>
                    <td class="border p-4 text-green-500 bg-green-200 font-semibold"><i class="fas fa-check-circle"></i> Yes</td>
                </tr>
                <!-- Row 6 -->
                <tr class="hover:bg-gray-100 transition-all">
                    <td class="border p-4">Enhance Accuracy through doctors in real-time</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-red-600"><i class="fas fa-times-circle"></i> No</td>
                    <td class="border p-4 text-green-500 bg-green-200 font-semibold"><i class="fas fa-check-circle"></i> Yes</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>



    <!-- User Reviews Section -->
    <section class="py-20 px-6 bg-gradient-to-r from-blue-100 to-blue-200">
        <h2 class="text-4xl font-bold text-center text-gray-800 mb-12">What Our Users Are Saying</h2>

        <div class="flex flex-wrap justify-center gap-12">
            <?php foreach ($feedbacks as $feedback) : ?>
                <div class="bg-white p-8 rounded-xl shadow-lg w-80">
                    <div class="flex items-center space-x-4 mb-6">
                        <div class="w-14 h-14 rounded-full bg-gray-400"></div>
                        <div>
                            <span class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($feedback['name']) ?></span>
                            <div class="text-sm text-gray-500"><?= date('F j, Y', strtotime($feedback['date'])) ?></div>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-4"><?= htmlspecialchars($feedback['title']) ?></h3>
                    <p class="text-gray-600 mb-6"><?= htmlspecialchars($feedback['message']) ?></p>
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
    <footer class="bg-gray-900 text-white py-6 mt-auto text-center">
        <p class="text-sm">&copy; 2025 X-COVID Insight. All rights reserved.</p>
    </footer>

</body>
</html>