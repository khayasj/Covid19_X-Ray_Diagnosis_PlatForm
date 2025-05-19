<?php
require 'db.php';
require 'vendor/autoload.php'; // Include Composer autoload
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
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
        // Initialize variables
        $certificationUrl = "";
        $errors = [];

        // Validate required fields
        $requiredFields = ['name', 'email', 'password', 'location', 'experience'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
            }
        }

        // Process file upload
        if (isset($_FILES['hpb_certification']) && $_FILES['hpb_certification']['error'] == UPLOAD_ERR_OK) {
            // Configure Cloudinary
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => 'dj2uhnamu',
                    'api_key'    => '472466731316343',
                    'api_secret' => '9lrevj01NB327_jbX5YJ_3yI0o8'
                ]
            ]);

            // Upload file to Cloudinary
            $upload = new UploadApi();
            $uploadResponse = $upload->upload($_FILES['hpb_certification']['tmp_name'], [
                'folder' => 'doctor_certifications'
            ]);
            $certificationUrl = $uploadResponse['secure_url'];
        } else {
            $errors[] = "Certification file is required";
        }

        // If no errors, proceed with registration
        if (empty($errors)) {
            // Hash password
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // Create doctor document
            $doctorData = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'password' => $password,
                'location' => $testCenterName, // Use the test center name from session
                'experience' => (int)$_POST['experience'],
                'certification' => $certificationUrl,
                'status' => 'active',
                'specialization' => 'Radiologist',
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];

            // Insert into MongoDB
            $result = $db->doctor->insertOne($doctorData);

            if ($result->getInsertedCount() > 0) {
                $_SESSION['success'] = "Doctor account created successfully!";
                header("Location: testcenteradmin_doctor.php");
                exit();
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error creating account: " . $e->getMessage();
    }
}

// Get test centres for dropdown
$testCentres = $db->test_centre->find([]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Doctor Account</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto p-6">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Create Doctor Account</h1>
                <a href="testcenteradmin_doctor.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Doctors
                </a>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
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

                    <!-- Test Centre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Test Centre</label>
                        <p class="mt-1 block w-full rounded-md bg-gray-100 p-2 border border-gray-300">
                            <?php echo htmlspecialchars($testCenterName); ?>
                        </p>
                        <input type="hidden" name="location" value="<?php echo htmlspecialchars($testCenterName); ?>">
                    </div>

                    <!-- Years of Experience -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                        <input type="number" name="experience" min="0" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Certification Upload -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Doctor Certification</label>
                        <div class="mt-1 flex items-center">
                            <input type="file" name="hpb_certification" accept=".pdf,.jpg,.png" required
                                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <p class="mt-2 text-sm text-gray-500">PDF, JPG, or PNG (max 5MB)</p>
                    </div>
                </div>

                <!-- Hidden Status Field -->
                <input type="hidden" name="status" value="active">

                <!-- Submit Button -->
                <div class="mt-8">
                    <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>Create Doctor Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
