<?php
require 'admindb.php';
require 'db.php';

$clinicReviewCollection = $db->clinic_review;
$reviews = $clinicReviewCollection->find(
    ['review_rating' => 5],
    [
        'sort' => ['creation_date' => -1],
        'limit' => 3
    ]
);

// Create an array to store combined review+patient data
$reviewData = [];

foreach ($reviews as $review) {
    // Convert patient_id string to MongoDB ObjectId
    $patientId = new MongoDB\BSON\ObjectId($review['patient_id']);
    
    // Find matching patient
    $patient = $db->patient->findOne(['_id' => $patientId]);
    
    // Only include reviews with existing patients
    if ($patient) {
        $reviewData[] = [
            'description' => $review['review_description'],
            'name' => $patient['name'],
            'rating' => $review['review_rating'],
            'date' => $review['creation_date']
        ];
    }
}

// Fetch video cards from database
$videoCardsCollection = $db2->video_cards;
$videoCards = $videoCardsCollection->find();

// Fetch key features from database
$keyFeaturesCollection = $db2->key_features;
$keyFeatures = $keyFeaturesCollection->find();

$pricingCollection = $db2->billing_plans;
$pricing = $pricingCollection->find();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-COVID Insight - AI-Powered Diagnosis</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
        .gradient-text { background: linear-gradient(45deg, #2563eb, #0694a2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100">

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
        <a href="loginPage.html" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 ml-4">Sign In</a>
        <a href="userregister.html" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 ml-4">Register</a>
    </header>

    <!-- Hero Section -->
    <section class="pt-28 pb-20 px-6">
        <div class="max-w-7xl mx-auto text-center animate-fade-in">
            <h1 class="text-5xl md:text-6xl font-extrabold gradient-text mb-8">
                AI-Driven COVID-19 Detection
            </h1>
            <p class="text-xl text-gray-600 mb-12 max-w-3xl mx-auto">
                Combining deep learning with medical expertise for accurate X-Ray analysis
            </p>

            <!-- Video Cards Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16">
            <?php foreach ($videoCards as $video): ?>
            <div class="group bg-white rounded-2xl shadow-xl hover:shadow-2xl transition-shadow duration-300">
                <div class="overflow-hidden rounded-t-2xl">
                    <?php
                    // Extract YouTube video ID from URL
                    if (strpos($video['video_url'], 'youtube.com') !== false || strpos($video['video_url'], 'youtu.be') !== false) {
                        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $video['video_url'], $matches);
                        $youtubeId = $matches[1] ?? null;
                        if ($youtubeId):
                    ?>
                        <iframe class="w-full h-52 rounded-t-2xl" src="https://www.youtube.com/embed/<?= $youtubeId; ?>" frameborder="0" allowfullscreen></iframe>
                    <?php endif; } else { ?>
                        <video class="w-full h-52 object-cover transform group-hover:scale-105 transition duration-300" controls>
                            <source src="<?= $video['video_url']; ?>" type="video/mp4">
                        </video>
                    <?php } ?>
                </div>
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2"><?= $video['title']; ?></h3>
                    <p class="text-gray-600 text-sm"><?= $video['description']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </section>

    <!-- Key Features Section -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <h2 class="text-4xl font-bold text-center gradient-text mb-16">Core Features</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($keyFeatures as $feature): ?>
                <div class="p-8 bg-white rounded-2xl border border-gray-200 hover:border-gray-300 transition-all shadow-lg hover:shadow-xl">
                    
                    <!-- Icon with dynamic background color -->
                    <div class="w-14 h-14 <?= $feature['bg_color']; ?> rounded-xl flex items-center justify-center mb-6">
                        <i class="<?= $feature['icon']; ?> text-2xl <?= $feature['icon_color']; ?>"></i>
                    </div>

                    <h3 class="text-2xl font-bold mb-4"><?= $feature['title']; ?></h3>
                    <p class="text-gray-600"><?= $feature['description']; ?></p>
                    
                    <!-- Features for Patient Appointment System (Tags) -->
                    <?php if ($feature['title'] === 'For Patients' && !empty($feature['features'])): ?>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <?php foreach ($feature['features'] as $item): ?>
                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm"><?= $item; ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Features for Doctor Validation & Feedback (List Items) -->
                    <?php if ($feature['title'] === 'For Test Centers' && !empty($feature['features'])): ?>
                        <ul class="mt-4 space-y-2 text-sm text-gray-600">
                            <?php foreach ($feature['features'] as $item): ?>
                                <li class="flex items-center">
                                    <i class="fas fa-check-circle text-purple-600 mr-2"></i>
                                    <?= $item; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<?php
$patientsCount = $db->patient->countDocuments();
$centersCount = $db->test_centre->countDocuments();
$covidtestersCount = $db->covid_tester->countDocuments();
$doctorsCount = $db->doctor->countDocuments();
?>
<section class="py-20 bg-gradient-to-br from-blue-50 to-indigo-100 text-center">
    <div class="max-w-7xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-blue-600 mb-2">Our Impact</h2>
        <p class="text-6xl font-bold text-blue-600 mb-12">2 + N = ∞</p>

        <!-- Horizontal Formula Row -->
        <div class="flex flex-col md:flex-row items-stretch justify-center gap-6 text-left">

            <!-- Block 1 -->
            <div class="bg-white rounded-2xl p-6 shadow-lg max-w-sm w-full min-h-[250px]">
                <p class="text-gray-700 text-base">
                    We adopt a <span class="text-blue-600 font-bold text-2xl">2 Models</span> approach: 
                    a segmentation model extracts precise lung contours from X-ray images, 
                    while a classification model focuses on the masked lung regions, minimizing noise 
                    from irrelevant background and eliminating misleading non-lung features.
                </p>
            </div>

            <!-- Plus Sign -->
            <div class="text-5xl font-bold text-green-600 self-center">+</div>

            <!-- Block 2 -->
            <div class="bg-white rounded-2xl p-6 shadow-lg max-w-sm w-full min-h-[250px]">
                <p class="text-gray-700 text-base">
                    To date, 
                    <span class="text-blue-600 font-bold text-2xl"><?= $patientsCount ?></span> <span class="text-blue-600 font-bold text-lg">Patients</span> 
                    and <span class="text-blue-600 font-bold text-2xl"><?= $centersCount ?></span> <span class="text-blue-600 font-bold text-lg">Test Centers</span> 
                    (supported by 
                    <span class="text-blue-600 font-bold text-2xl"><?= $covidtestersCount ?></span> <span class="text-blue-600 font-bold text-lg">Testers</span> 
                    and <span class="text-blue-600 font-bold text-2xl"><?= $doctorsCount ?></span> <span class="text-blue-600 font-bold text-lg">Doctors</span>) 
                    have joined our platform, forming a robust ecosystem that completes the loop between patient diagnosis and test center service delivery.
                </p>
            </div>

            <!-- Equals Sign -->
            <div class="text-5xl font-bold text-purple-600 self-center">=</div>

            <!-- Block 3 -->
            <div class="bg-white rounded-2xl p-6 shadow-lg max-w-sm w-full min-h-[250px]">
                <p class="text-gray-700 text-base">
                    This combination of cutting-edge technology and a thriving user base unlocks 
                    <span class="text-blue-600 font-bold text-2xl">Infinite</span> possibilities. 
                    Together, we’re embracing a smarter, faster future empowered by machine learning in medical diagnostics.
                </p>
            </div>

        </div>

    </div>
</section>




<script>
    // Registration Chart
    const ctx = document.getElementById('registrationChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Patients', 'Test Centers', 'COVID Testers', 'Doctors'],
            datasets: [{
                data: [
                    <?= $patientsCount ?>,
                    <?= $centersCount ?>,
                    <?= $covidtestersCount ?>,
                    <?= $doctorsCount ?>
                ],
                backgroundColor: [
                    '#3b82f6',   // Blue for patients
                    '#6366f1',    // Indigo for centers
                    '#f59e0b',    // Orange for testers
                    '#ef4444'     // Red for doctors
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = <?= $total ?>;
                            return `${context.label}: ${context.formattedValue} (${((context.raw / total) * 100).toFixed(1)}%)`;
                        }
                    }
                }
            }
        }
    });
</script>

<!-- Pricing Plans Preview -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <div class="text-center mb-16">
            <h2 class="text-4xl font-bold gradient-text mb-4">Competitive Pricing</h2>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">Flexible plans tailored for testing centers of all sizes</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            foreach ($pricing as $plan): 
            ?>
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-shadow p-8 border border-gray-100">
                <h3 class="text-2xl font-bold mb-4"><?= htmlspecialchars($plan['name']) ?></h3>
                <div class="text-4xl font-bold text-blue-600 mb-6">
                    <?= htmlspecialchars('$'.number_format($plan['price'], 2)) ?>
                    <span class="text-lg text-gray-500">/month</span>
                </div>
                <div class="space-y-4 mb-8">
                    <div class="flex items-center">
                        <i class="fas fa-users text-blue-500 mr-3"></i>
                        <span><?= htmlspecialchars($plan['user_limit']) ?> Testers Included</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-12 text-center">
            <a href="pricing.php" class="inline-flex items-center px-6 py-3 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                <span>View Full Pricing Details</span>
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

    <!-- Testimonials -->
    <section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-6">
        <h2 class="text-4xl font-bold text-center gradient-text mb-16">User Experiences</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (!empty($reviewData)): ?>
                <?php foreach ($reviewData as $review): ?>
                <div class="bg-white p-6 rounded-2xl shadow-lg hover:shadow-xl transition-shadow border border-blue-50">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-blue-600"></i>
                        </div>
                        <div>
                            <h4 class="font-bold"><?= htmlspecialchars($review['name']) ?></h4>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <?php for ($i = 0; $i < 5; $i++): ?>
                            <i class="fas fa-star text-yellow-400 mr-1"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="text-gray-600"><?= htmlspecialchars($review['description']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full text-center text-gray-500">
                    No reviews found
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-6 mt-auto">
        <div class="text-center">
            <p class="text-sm">&copy; 2025 X-COVID Insight. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Registration Chart
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('registrationChart');
    
    // Verify element existence
    if (!ctx) {
        console.error('Could not find chart element');
        return;
    }

    new Chart(ctx.getContext('2d'), {
        type: 'pie',
        data: {
            labels: ['Patients', 'Test Centers', 'COVID Testers', 'Doctors'],
            datasets: [{
                data: [
                    <?= (int)$patientsCount ?>,
                    <?= (int)$centersCount ?>,
                    <?= (int)$covidtestersCount ?>,
                    <?= (int)$doctorsCount ?>
                ],
                backgroundColor: [
                    '#43A047',   // Blue
                    '#6366f1',   // Indigo
                    '#f59e0b',   // Orange
                    '#ef4444'    // Red
                ],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Add this
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = <?= (int)$total ?>;
                            if (total === 0) return 'No data available';
                            return `${context.label}: ${context.formattedValue} (${((context.raw / total) * 100).toFixed(1)}%)`;
                        }
                    }
                }
            }
        }
    });
});
</script>

</body>
</html>