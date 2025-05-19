<?php
require 'db.php';
require 'vendor/autoload.php';
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
session_start();

// Authentication check
if (!isset($_SESSION['email'])) {
    header("Location: loginPage.html");
    exit();
}

// Get logged-in test center
$testCenter = $db->test_centre->findOne(['email' => $_SESSION['email']]);
if (!$testCenter) {
    session_unset();
    session_destroy();
    header("Location: loginPage.html");
    exit();
}
$testCenterName = $testCenter['name'];

// Validate doctor ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Doctor ID required";
    header("Location: testcenteradmin_doctor.php");
    exit();
}

try {
    $doctorId = new MongoDB\BSON\ObjectId($_GET['id']);
} catch (Exception $e) {
    $_SESSION['error'] = "Invalid doctor ID";
    header("Location: testcenteradmin_doctor.php");
    exit();
}

// Fetch doctor data
$doctor = $db->doctor->findOne([
    '_id' => $doctorId,
    'location' => $testCenterName // Ensure ownership
]);

if (!$doctor) {
    $_SESSION['error'] = "Doctor not found";
    header("Location: testcenteradmin_doctor.php");
    exit();
}

// Form handling
$name = $doctor['name'];
$email = $doctor['email'];
$experience = $doctor['experience'];
$phone = $doctor['phone'] ?? '';
$currentCertification = $doctor['certification'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $experience = $_POST['experience'];
    $phone = $_POST['phone'];
    $certificationUrl = $currentCertification;

    // Validation
    $requiredFields = ['name', 'email', 'experience', 'phone'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . " is required";
        }
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    $existingDoctor = $db->doctor->findOne([
        'email' => $email,
        '_id' => ['$ne' => $doctorId]
    ]);

    if ($existingDoctor) {
        $errors[] = "Email already exists";
    }

    // Handle file upload
    if (isset($_FILES['certification']) && $_FILES['certification']['error'] == UPLOAD_ERR_OK) {
        try {
            Configuration::instance([
                'cloud' => [
                    'cloud_name' => 'dj2uhnamu',
                    'api_key'    => '472466731316343',
                    'api_secret' => '9lrevj01NB327_jbX5YJ_3yI0o8'
                ]
            ]);

            $upload = new UploadApi();
            $uploadResponse = $upload->upload($_FILES['certification']['tmp_name'], [
                'folder' => 'doctor_certifications'
            ]);
            $certificationUrl = $uploadResponse['secure_url'];
        } catch (Exception $e) {
            $errors[] = "Error uploading certification: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $updateData = [
                'name' => $name,
                'email' => $email,
                'experience' => (int)$experience,
                'phone' => $phone,
                'certification' => $certificationUrl
            ];

            $result = $db->doctor->updateOne(
                ['_id' => $doctorId],
                ['$set' => $updateData]
            );

            if ($result->getModifiedCount() > 0) {
                $_SESSION['success'] = "Doctor updated successfully";
            } else {
                $_SESSION['info'] = "No changes made";
            }
            header("Location: testcenteradmin_doctor.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "Update error: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto p-6">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Edit Doctor</h1>
                <a href="testcenteradmin_doctor.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Years of Experience</label>
                        <input type="number" name="experience" min="0" value="<?= htmlspecialchars($experience) ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Current Certification</label>
                        <a href="<?= htmlspecialchars($currentCertification) ?>" target="_blank" 
                           class="text-blue-600 hover:underline block mb-2">
                            View Current Certification
                        </a>
                        <label class="block text-sm font-medium text-gray-700">Update Certification (optional)</label>
                        <input type="file" name="certification" accept=".pdf,.jpg,.png"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-2 text-sm text-gray-500">PDF, JPG, or PNG (max 5MB)</p>
                    </div>
                </div>

                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </form>
        </div>
    </div>
</body>
</html>