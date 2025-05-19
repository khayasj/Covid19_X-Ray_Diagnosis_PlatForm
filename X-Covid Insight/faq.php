<?php
require 'admindb.php'; // MongoDB connection file
session_start();

$collection = $db2->faq;
$faqs = $collection->find();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ - X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <style>
        .accordion-item {
            transition: all 0.3s ease;
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">

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

    <!-- FAQ Section -->
    <section class="py-16 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto flex-grow">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Frequently Asked Questions</h1>
            <p class="text-lg text-gray-600">Find quick answers to common questions about our platform and services.</p>
        </div>

        <!-- Featured Questions -->
        <div class="grid md:grid-cols-2 gap-6 mb-12">
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-3">
                    <div class="bg-blue-100 p-2 rounded-full mr-3">
                        <i class="fas fa-chart-line text-blue-600"></i>
                    </div>
                    <h3 class="font-semibold">AI Prediction Accuracy</h3>
                </div>
                <p class="text-gray-600 text-sm">Our models achieve 90% accuracy through continuous learning from global datasets.</p>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                <div class="flex items-center mb-3">
                    <div class="bg-green-100 p-2 rounded-full mr-3">
                        <i class="fas fa-shield-alt text-green-600"></i>
                    </div>
                    <h3 class="font-semibold">Data Security</h3>
                </div>
                <p class="text-gray-600 text-sm">All data is encrypted using SHA-256 and processed in secure environments.</p>
            </div>
        </div>

        <!-- FAQ Accordion -->
        <div class="space-y-4">
            <?php foreach ($faqs as $index => $faq): ?>
                <div class="accordion-item bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow">
                    <button 
                        class="w-full text-left p-6 flex items-center justify-between"
                        onclick="toggleAccordion(this)"
                    >
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-2 rounded-full mr-4">
                                <i class="fas fa-question text-blue-600"></i>
                            </div>
                            <span class="font-semibold text-lg"><?= htmlspecialchars($faq['question']) ?></span>
                        </div>
                        <i class="fas fa-plus text-gray-400 ml-4 transition-transform"></i>
                    </button>
                    <div class="accordion-content px-6">
                        <div class="pb-6 pl-14 pr-6 text-gray-600">
                            <?= htmlspecialchars($faq['answer']) ?>
                            <div class="mt-4 border-t pt-4">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Support Section -->
        <div class="mt-16 bg-blue-600 text-white rounded-2xl p-8 text-center shadow-xl">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Still have questions?</h2>
                <p class="text-lg mb-6 opacity-90">Our support team is here to help 24/7</p>
                <div class="flex justify-center space-x-4">
                <a href="contact.html">
                    <button class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold hover:bg-gray-100 transition">
                        <i class="fas fa-envelope mr-2"></i> Contact Support
                    </button>
                </a>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleAccordion(button) {
            const item = button.closest('.accordion-item');
            const content = item.querySelector('.accordion-content');
            const icon = button.querySelector('.fa-plus');

            // Toggle content
            content.style.maxHeight = content.style.maxHeight ? null : `${content.scrollHeight}px`;
            
            // Toggle icon
            icon.classList.toggle('fa-plus');
            icon.classList.toggle('fa-minus');
            
            // Toggle background color
            item.classList.toggle('bg-blue-50');
        }
    </script>

    <!-- Footer Section -->
    <footer class="bg-gray-900 text-white py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm">&copy; 2025 X-COVID Insight. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
