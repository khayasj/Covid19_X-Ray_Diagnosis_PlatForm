<?php
require 'admindb.php'; // MongoDB connection file
session_start();

$collection = $db2->billing_plans;

// Fetch billing plans from MongoDB
$plans = $collection->find();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pricing - X-COVID Insight</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet"/>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
        }
        .plan-card:hover .price {
            color: #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen">
    <!-- Navbar Section (Keep original) -->
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

    <section class="py-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto flex-grow">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold mb-4">Flexible Pricing Plans</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">Choose the perfect plan for your testing center. Start with a free trial and upgrade anytime. All plans include full platform access and AI-powered insights.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <?php foreach ($plans as $plan): ?>
                <div class="relative bg-white rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl">
                    <div class="absolute inset-0 gradient-bg opacity-10"></div>
                    <div class="p-8 relative">
                        <div class="mb-6">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <p class="text-gray-600"><?php echo htmlspecialchars($plan['description']); ?></p>
                        </div>
                        <div class="price mb-8 transition-colors">
                            <span class="text-4xl font-bold"><?php echo htmlspecialchars('$' . number_format($plan['price'], 2)); ?></span>
                            <span class="text-gray-600">/month</span>
                        </div>
                        <div class="mb-8">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-users text-blue-500 mr-3"></i>
                                <span class="font-medium"><?php echo htmlspecialchars($plan['user_limit']); ?> Testers</span>
                            </div>
                            <div class="space-y-3">
                                <h4 class="text-lg font-semibold mb-3">Billing Options:</h4>
                                <?php foreach ($plan['billing_durations'] as $duration): ?>
                                    <div class="flex items-center bg-gray-50 p-3 rounded-lg">
                                        <i class="fas fa-calendar-check text-blue-500 mr-3"></i>
                                        <span><?php echo htmlspecialchars($duration); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <a href="userregister.html" class="block text-center text-blue-600 font-semibold hover:underline">
                        Register to Get Started
                        </a>

                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Free Trial Section -->
        <div class="gradient-bg rounded-2xl p-8 text-center text-white shadow-xl">
            <div class="max-w-2xl mx-auto">
                <h2 class="text-3xl font-bold mb-4">Start Your Free Trial</h2>
                <p class="text-lg mb-6 opacity-90">Experience full platform access with no commitment. Cancel anytime during your trial period.</p>
                <div class="flex justify-center items-center space-x-4">
                    <a href="userregister.html" class="text-white font-semibold underline hover:opacity-90 transition">
                     Register to Start Free Trial
                    </a>
                    <span class="text-sm opacity-85">No credit card required</span>
                </div>
            </div>
        </div>

        <!-- Feature Comparison -->
        <div class="mt-16 bg-white rounded-xl shadow-md p-8">
            <h3 class="text-2xl font-bold mb-8 text-center">All Plans Include</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div class="p-4">
                    <i class="fas fa-brain text-3xl text-blue-500 mb-4"></i>
                    <h4 class="font-semibold mb-2">AI Predictions</h4>
                    <p class="text-sm text-gray-600">Advanced machine learning models</p>
                </div>
                <div class="p-4">
                    <i class="fas fa-shield-alt text-3xl text-blue-500 mb-4"></i>
                    <h4 class="font-semibold mb-2">Data Security</h4>
                    <p class="text-sm text-gray-600">Enterprise-grade encryption</p>
                </div>
                <div class="p-4">
                    <i class="fas fa-headset text-3xl text-blue-500 mb-4"></i>
                    <h4 class="font-semibold mb-2">24/7 Support</h4>
                    <p class="text-sm text-gray-600">Priority technical support</p>
                </div>
                <div class="p-4">
                    <i class="fas fa-sync text-3xl text-blue-500 mb-4"></i>
                    <h4 class="font-semibold mb-2">Auto Updates</h4>
                    <p class="text-sm text-gray-600">Regular feature updates</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="bg-gray-900 text-white py-8 mt-auto flex-shrink-0">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm">&copy; 2025 X-COVID Insight. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>