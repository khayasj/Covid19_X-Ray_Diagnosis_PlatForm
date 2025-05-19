<?php
require 'db.php';
session_start();

// Check if test center admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: loginPage.html");
    exit();
}

// Get logged-in test center's details
$testCenter = $db->test_centre->findOne(['email' => $_SESSION['email']]);

if (!$testCenter) {
    session_unset();
    session_destroy();
    header("Location: loginPage.html");
    exit();
}

$testCenterName = $testCenter['name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $errors = [];

        // Validate required fields (removed 'test_centre' from required fields)
        $requiredFields = ['name', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }

        // If no errors, proceed with registration
        if (empty($errors)) {
            // Hash password
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // Create tester document
            $testerData = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'password' => $password,
                'location' => $testCenterName, // Use the test center name from session
                'status' => 'active',
                'phone' => $_POST['phone'] ?? '',
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];

            // Insert into MongoDB
            $result = $db->covid_tester->insertOne($testerData);

            if ($result->getInsertedCount() > 0) {
                $_SESSION['success'] = "COVID Tester account created successfully!";
                header("Location: testcenteradmin_covidtesters.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error creating account: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create COVID Tester Account</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto p-6">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Create COVID Tester Account</h1>
                <a href="testcenteradmin_covidtesters.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Testers
                </a>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" name="password" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone Number</label>
                        <input type="tel" name="phone"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Test Centre Display -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Test Centre</label>
                        <p class="mt-1 block w-full rounded-md bg-gray-100 p-2 border border-gray-300">
                            <?php echo htmlspecialchars($testCenterName); ?>
                        </p>
                        <input type="hidden" name="test_centre" value="<?php echo htmlspecialchars($testCenterName); ?>">
                    </div>
                </div>

                <!-- Hidden Status Field -->
                <input type="hidden" name="status" value="active">

                <!-- Submit Button -->
                <div class="mt-8">
                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>Create Tester Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>