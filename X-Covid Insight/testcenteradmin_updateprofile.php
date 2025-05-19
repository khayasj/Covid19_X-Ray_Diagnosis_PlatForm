<?php
require 'db.php';
session_start();


$testCenter = null;
$error = '';
$success = '';

try {
    // Fetch current test center data
    $testCenter = $db->test_centre->findOne(['_id' => new MongoDB\BSON\ObjectId($_SESSION['id'])]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle form submission
        $name = $_POST['name'];
        $address = $_POST['address'];
        $subscriptionPlan = $_POST['subscription_plan'];

        // Update database
        $result = $db->test_centre->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($_SESSION['id'])],
            ['$set' => [
                'name' => $name,
                'address' => $address,
                'subscription_plan' => $subscriptionPlan
            ]]
        );

        if ($result->getModifiedCount() > 0) {
            // Update session data
            $_SESSION['name'] = $name;
            $_SESSION['subscription_plan'] = $subscriptionPlan;
            
            // Redirect on success
            header("Location: testcenteradmin_homepage.php");
            exit();
        } else {
            $error = "No changes made or error updating profile";
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
            <!-- Header with Back Button -->
            <div class="mb-8">
                <a href="testcenteradmin_homepage.php" class="mb-4 inline-block text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                </a>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Update Profile</h1>
                <p class="text-gray-600">Manage your test center's information</p>
            </div>

            <!-- Notifications -->
            <?php if($error): ?>
            <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                    <p class="text-red-700"><?= $error ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Profile Form -->
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Test Center Name</label>
                        <div class="relative">
                            <input type="text" name="name" value="<?= htmlspecialchars($testCenter['name'] ?? '') ?>" 
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            <i class="fas fa-building absolute right-3 top-3.5 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <div class="relative">
                            <input type="email" 
                                name="email" 
                                value="<?= htmlspecialchars($testCenter['email'] ?? '') ?>" 
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100 cursor-not-allowed"
                                readonly
                                title="Email cannot be changed"
                                disabled>
                            <i class="fas fa-envelope absolute right-3 top-3.5 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                        <div class="relative">
                            <textarea name="address" rows="3"
                                      class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($testCenter['address'] ?? '') ?></textarea>
                            <i class="fas fa-map-marker-alt absolute right-3 top-3.5 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Subscription Plan -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subscription Plan</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php $plans = ['basic', 'intermediate', 'premium']; ?>
                            <?php foreach ($plans as $plan): ?>
                            <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:border-blue-500 <?= ($testCenter['subscription_plan'] ?? '') === $plan ? 'border-2 border-blue-500 bg-blue-50' : '' ?>">
                                <input type="radio" name="subscription_plan" value="<?= $plan ?>" 
                                       class="h-5 w-5 text-blue-600" 
                                       <?= ($testCenter['subscription_plan'] ?? '') === $plan ? 'checked' : '' ?> required>
                                <span class="ml-3 capitalize font-medium"><?= $plan ?> Plan</span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-6">
                    <button type="submit" 
                            class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

<!-- Success Redirect Script -->
<?php if(isset($_GET['success'])): ?>
    <script>
        setTimeout(function() {
            window.location.href = 'testcenteradmin_homepage.php';
        }, 2000);
    </script>
    <?php endif; ?>
</body>
</html>