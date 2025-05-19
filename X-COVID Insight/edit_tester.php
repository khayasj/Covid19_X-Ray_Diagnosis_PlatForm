<?php
require 'db.php';
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

// Validate tester ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Tester ID required";
    header("Location: testcenteradmin_covidtesters.php");
    exit();
}

try {
    $testerId = new MongoDB\BSON\ObjectId($_GET['id']);
} catch (Exception $e) {
    $_SESSION['error'] = "Invalid tester ID";
    header("Location: testcenteradmin_covidtesters.php");
    exit();
}

// Fetch tester data
$tester = $db->covid_tester->findOne([
    '_id' => $testerId,
    'location' => $testCenterName // Ensure ownership
]);

if (!$tester) {
    $_SESSION['error'] = "Tester not found";
    header("Location: testcenteradmin_covidtesters.php");
    exit();
}

// Form handling
$name = $tester['name'];
$email = $tester['email'];
$phone = $tester['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    // Validation
    if (empty($name) || empty($email) || empty($phone)) {
        $errors[] = "All fields are required";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    $existingTester = $db->covid_tester->findOne([
        'email' => $email,
        '_id' => ['$ne' => $testerId]
    ]);

    if ($existingTester) {
        $errors[] = "Email already exists";
    }

    if (empty($errors)) {
        try {
            $result = $db->covid_tester->updateOne(
                ['_id' => $testerId],
                ['$set' => [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone
                ]]
            );

            if ($result->getModifiedCount() > 0) {
                $_SESSION['success'] = "Tester updated successfully";
            } else {
                $_SESSION['info'] = "No changes made";
            }
            header("Location: testcenteradmin_covidtesters.php");
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
    <title>Edit Tester</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto p-6">
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Edit Tester</h1>
                <a href="testcenteradmin_covidtesters.php" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-lg">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
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

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($phone) ?>" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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