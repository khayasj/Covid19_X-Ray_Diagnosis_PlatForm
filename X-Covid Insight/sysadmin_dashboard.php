<?php
require 'db.php';
session_start();

// Redirect to login if not logged in or not a system admin
if (
    !isset($_SESSION['email']) ||
    !isset($_SESSION['user_type']) ||
    $_SESSION['user_type'] !== 'system_admin'
) {
    header("Location: loginPage.html");
    exit();
}

// Model Performance Calculations
$totalPredictions = $db->xray_records->countDocuments(['confidence' => ['$gt' => 80]]);
$correctCount = $db->xray_records->countDocuments([
    'confidence' => ['$gt' => 80],
    'validatedResult' => 'correct'
]);
$incorrectCount = $db->xray_records->countDocuments([
    'confidence' => ['$gt' => 80],
    'validatedResult' => 'incorrect'
]);

// Get validated xray count
$validatedCount = $db->validated_xrays->countDocuments();
$enableTrainingButton = $validatedCount > 100;

$accuracy = $totalPredictions > 0 ? round(($correctCount / $totalPredictions) * 100, 2) : 0;
$errorRate = $totalPredictions > 0 ? round(($incorrectCount / $totalPredictions) * 100, 2) : 0;

$pendingApplicationsCount = $db->test_centre_applications->countDocuments(['status' => 'Pending']);
$activeClinicsCount = $db->test_centre->countDocuments();
$newFeedbacksCount = $db->feedback->countDocuments();

// 计算correlation
$samplesCursor = $db->xray_records->find([
    'needs_review' => true,
    'hasBeenVal' => true,
    'alreadyTrain' => false
]);

$samples = iterator_to_array($samplesCursor);
$sampleCount = count($samples);
$correlation = null;

if ($sampleCount >= 40) {
    $confidences = [];
    $binaryMatch = [];

    foreach ($samples as $s) {
        if (isset($s['confidence'], $s['predictionResult'], $s['trueLabel'])) {
            $conf = floatval($s['confidence']);
            $confidences[] = $conf;
            $binaryMatch[] = $s['predictionResult'] === $s['trueLabel'] ? 1 : 0;
        }
    }

    // Bin data into 10 buckets and compute average confidence and accuracy in each
    $bins = array_fill(0, 10, ['conf' => [], 'acc' => []]);
    foreach ($confidences as $i => $c) {
        $binIndex = min(intval($c / 10), 9); // 0-9 bins
        $bins[$binIndex]['conf'][] = $c;
        $bins[$binIndex]['acc'][] = $binaryMatch[$i];
    }

    $avgConfs = [];
    $avgAccs = [];
    for ($i = 0; $i < 10; $i++) {
        if (count($bins[$i]['conf']) > 0) {
            $avgConfs[] = array_sum($bins[$i]['conf']) / count($bins[$i]['conf']);
            $avgAccs[] = array_sum($bins[$i]['acc']) / count($bins[$i]['acc']);
        }
    }

    // Pearson correlation (manual)
    $n = count($avgConfs);
    if ($n > 1) {
        $meanX = array_sum($avgConfs) / $n;
        $meanY = array_sum($avgAccs) / $n;
        $numerator = 0;
        $denX = 0;
        $denY = 0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $avgConfs[$i] - $meanX;
            $dy = $avgAccs[$i] - $meanY;
            $numerator += $dx * $dy;
            $denX += $dx * $dx;
            $denY += $dy * $dy;
        }

        $correlation = ($denX > 0 && $denY > 0) ? round($numerator / sqrt($denX * $denY), 4) : null;
    }
}
$enableTrainingButton = ($sampleCount >= 100) && (!is_null($correlation) && $correlation < 0.7);

$pendingDoctorReview = $db->xray_records->countDocuments([
    'needs_review' => true,
    'hasBeenVal' => false
]);

$doctorValidatedCursor = $db->xray_records->find([
    'needs_review' => true,
    'hasBeenVal' => true
]);

$totalPending = 0;
$mismatchCount = 0;

foreach ($doctorValidatedCursor as $record) {
    if (isset($record['predictionResult'], $record['trueLabel'])) {
        $totalPending++;
        if ($record['predictionResult'] !== $record['trueLabel']) {
            $mismatchCount++;
        }
    }
}

$mismatchRate = $totalPending > 0 ? round(($mismatchCount / $totalPending) * 100, 2) : null;

// 启动了增量训练

$trainingData = [];

foreach ($samples as $s) {
    if (!empty($s['image']) && !empty($s['trueLabel'])) {
        $trainingData[] = [
            'image_path' => $s['image'],
            'true_label' => $s['trueLabel']
        ];
    }
}

$payload = [
    'triggered_by' => $_SESSION['email'],
    'timestamp' => date('Y-m-d H:i:s'),
    'samples' => $trainingData
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'incremental_training') {
        try {
            
            // Fix validation check (should be >= 100)
            if ($sampleCount < 100) {
                throw new Exception("Not enough validated samples (requires 100, current: $sampleCount)");
            }
    
            //New hugging face docker space with FastAPI interface
            $apiUrl = 'https://jobentan-xcovid-incremental-train.hf.space/trigger_incremental_train'; 
    
            
            // Call API using cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    // Uncomment if using private space
                    // 'Authorization: Bearer ' . $hfToken,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($payload)
            ]);
    
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
    
            // Handle API errors
            if ($error) {
                throw new Exception("API connection failed: $error");
            }
    
            if ($httpCode !== 200) {
                throw new Exception("API returned error code $httpCode");
            }
    
            // Process API response
            $result = json_decode($response, true) ?? $response;
            $_SESSION['message'] = "Training started successfully! Response: " . print_r($result, true);

            // modify the alreadytrain field
            $idsToUpdate = array_map(fn($s) => $s['_id'], $samples);
            $db->xray_records->updateMany(
                ['_id' => ['$in' => $idsToUpdate]],
                ['$set' => ['alreadyTrain' => true]]
            );
    
        } catch (Exception $e) {
            $_SESSION['error'] = "Training failed: " . $e->getMessage();
        }
        
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=model_performance");
        exit();
    }
}

// Handle application actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            $applicationId = new MongoDB\BSON\ObjectId($_POST['application_id']);
            $collection = $db->test_centre_applications;
            
            if ($_POST['action'] === 'disapprove') {
                $updateResult = $collection->updateOne(
                    ['_id' => $applicationId],
                    ['$set' => ['status' => 'Rejected']] // Should be 'Rejected' (match your collection's values)
                );
                
                if ($updateResult->getModifiedCount() > 0) {
                    $_SESSION['message'] = "Application rejected successfully!";
                } else {
                    $_SESSION['error'] = "No changes made. Application might already be processed.";
                }
                
            } elseif ($_POST['action'] === 'approve') {
                // Create new test centre
                $testCentreCollection = $db->test_centre;
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                $application = $collection->findOne(['_id' => $applicationId]);
                
                // Validate required fields
                if (!isset($application->name)) {
                    throw new Exception("Test center name missing in application");
                }
            
                // Create new test centre using APPLICATION DATA
                $insertResult = $testCentreCollection->insertOne([
                "name" => $application->name,
                "email" => $application->email,
                "password" => $password,
                "testers" => $application->testers,
                "hbp" => $application->hbp,
                "uen" => $application->uen,
                "address" => $application->address,
                "subscription_plan" => $application->subscription_plan,
                "billing_plan" => $application->billing_plan,
                "hpb_certification" => $application->hpb_certification,
                "status" => "Open"
            ]);
            
            if ($insertResult->getInsertedCount() > 0) {
                $collection->updateOne(
                    ['_id' => $applicationId],
                    ['$set' => ['status' => 'Approved']]
                );

                // Send approval email with login details
                $emailObj = new \SendGrid\Mail\Mail();
                $emailObj->setFrom("xcovidinsight@gmail.com", "X COVID INSIGHT");
                $emailObj->setSubject("Application Approved");
                $emailObj->addTo($application->email, $application->name);
                $emailObj->addContent(
                    "text/plain",
                    "Hello {$application->name},\n\n" .
                    "Your test center application has been approved. Below are your login details:\n\n" .
                    "Email: {$application->email}\n" .
                    "Password: {$_POST['password']}\n\n" .
                    "Login here: https://xcovidinsight.onrender.com/loginPage.html\n\n" .
                    "Thank you."
                );

                $sendgrid = new \SendGrid("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");
                    }
                    try {
                        $response = $sendgrid->send($emailObj);
                        $_SESSION['message'] = "Application approved and login details sent to {$application->email}!";
                    } catch (Exception $e) {
                        $_SESSION['error'] = "Application approved but email failed: " . $e->getMessage();
                    }
        }
        // Redirect back to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=applications");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error processing request: " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF'] . "?tab=applications");
        exit();
    }
    }
}

        // Add this code at the top with other POST handlers
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clinic_action'])) {
            try {
                $clinicId = new MongoDB\BSON\ObjectId($_POST['clinic_id']);
                $collection = $db->test_centre;
                
                $newStatus = ($_POST['clinic_action'] === 'suspend') ? 'Suspended' : 'Open';
                
                $updateResult = $collection->updateOne(
                    ['_id' => $clinicId],
                    ['$set' => ['status' => $newStatus]]
                );
                
                if ($updateResult->getModifiedCount() > 0) {
                    $_SESSION['message'] = "Clinic status updated successfully!";
                } else {
                    $_SESSION['error'] = "No changes made. Clinic status might already be updated.";
                }
                
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=clinics");
                exit();
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error processing request: " . $e->getMessage();
                header("Location: " . $_SERVER['PHP_SELF'] . "?tab=clinics");
                exit();
            }
        }

        // Handle billing email action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['billing_action']) && $_POST['billing_action'] === 'send_billing') {
            try {
                $clinicId = new MongoDB\BSON\ObjectId($_POST['clinic_id']);
                $clinic = $db->test_centre->findOne(['_id' => $clinicId]);
                if (!$clinic) {
                    throw new Exception("Clinic not found.");
                }

                // Compose billing email content
                $clinicName = $clinic->name ?? 'Clinic';
                $clinicEmail = $clinic->email ?? '';
                $billingPlan = $clinic->billing_plan ?? 'N/A';
                $subscriptionPlan = $clinic->subscription_plan ?? 'N/A';

                // Customize the billing details
                $emailContent = "Dear {$clinicName},\n\n"
                    . "This is a reminder regarding your billing for the X-COVID Insights platform.\n\n"
                    . "Subscription Plan: {$subscriptionPlan}\n"
                    . "Billing Plan: {$billingPlan}\n"
                    . "Please ensure your payment is up to date. If you have any questions, contact our support team.\n\n"
                    . "Thank you for using X-COVID Insights.\n\n"
                    . "Best regards,\n"
                    . "X-COVID Insights Admin Team";

                $emailObj = new \SendGrid\Mail\Mail();
                $emailObj->setFrom("xcovidinsight@gmail.com", "X COVID INSIGHT");
                $emailObj->setSubject("Billing Notification - X-COVID Insights");
                $emailObj->addTo($clinicEmail, $clinicName);
                $emailObj->addContent("text/plain", $emailContent);

                $sendgrid = new \SendGrid("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");

                try {
                    $response = $sendgrid->send($emailObj);
                    $_SESSION['message'] = "Billing email sent to {$clinicEmail}!";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Failed to send billing email: " . $e->getMessage();
                }
            } catch (Exception $e) {
                $_SESSION['error'] = "Error processing billing request: " . $e->getMessage();
            }
            header("Location: " . $_SERVER['PHP_SELF'] . "?tab=clinics");
            exit();
        }

// handle feedbacks
$collection2 = $db->user_feedback;
$feedbacks = $collection2->find();

// Get pending applications
$activeTab = $_GET['tab'] ?? 'dashboard';
$applicationsCollection = $db->test_centre_applications;
$pendingApplications = $applicationsCollection->find(['status' => 'Pending']);

        // Handle clinic search and filtering
        $searchName = $_GET['search_name'] ?? '';
        $subscriptionPlan = $_GET['subscription_plan'] ?? '';

        $query = [];
        if (!empty($searchName)) {
            $query['name'] = new MongoDB\BSON\Regex($searchName, 'i');
        }
        if (!empty($subscriptionPlan)) {
            $query['subscription_plan'] = $subscriptionPlan;
        }

        $clinicsCollection = $db->test_centre;
        $clinics = $clinicsCollection->find($query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>X-COVID Insights Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="adminDashboardStyle.css">
    <style>
        .metrics-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .metrics-table th,
        .metrics-table td {
            text-align: center;
            vertical-align: middle;
            padding: 12px 16px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="logo">
            <i class="fas fa-virus"></i>
            <span class="logo-text">X-COVID Insights</span>
        </div>
        <ul class="nav-menu">
            <li class="nav-item <?= $activeTab === 'dashboard' ? 'active' : '' ?>">
                <a href="?tab=dashboard" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
            </li>
            <li class="nav-item <?= $activeTab === 'applications' ? 'active' : '' ?>">
                <a href="?tab=applications" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-file-medical"></i> Applications
                </a>
            </li>
            <li class="nav-item <?= $activeTab === 'clinics' ? 'active' : '' ?>">
                <a href="?tab=clinics" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-clinic-medical"></i> Clinics
                </a>
            </li>
            <li class="nav-item <?= $activeTab === 'feedback' ? 'active' : '' ?>">
                <a href="?tab=feedback" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-comment-medical"></i> Feedback
                </a>
            </li>
            <li class="nav-item <?= $activeTab === 'model_performance' ? 'active' : '' ?>">
                <a href="?tab=model_performance" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-chart-bar"></i> Model Performance
                </a>
            </li>
            <li class="nav-item">
                <a href="sysadmin_edit_homepage.php" style="color: inherit; text-decoration: none;">
                    <i class="fas fa-edit"></i> Content Editor
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="header">
            <h1>Administration Dashboard</h1>
            <div class="header-actions">
                <div class="user-profile">
                    <span>Admin User</span>
                    <img src="https://xsgames.co/randomusers/avatar.php?g=male" class="user-avatar" alt="profile">
                </div>
                <button class="btn btn-logout" onclick="window.location.href='logout.php';">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>

            </div>
        </div>

        <!-- ALERTS START -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <!-- ALERTS END -->

        <?php if($activeTab === 'feedback'): ?>
        <!-- Feedback Content -->
        <h2>User Feedback</h2>
        <div class="data-table feedback-scrollable">
            <?php foreach($feedbacks as $feedback): ?>
                <div class="feedback-card">
                    <div class="feedback-header">
                        <div>
                            <h3><?= htmlspecialchars($feedback['user']) ?></h3>
                        </div>
                    </div>
                    <h4><?= htmlspecialchars($feedback['title']) ?></h4>
                    <p class="feedback-message"><?= htmlspecialchars($feedback['message']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Model perofrmance tab -->
        <?php elseif($activeTab === 'model_performance'): ?>
        <div class="model-performance">
            <h2>Classification Model Statistics</h2>
            
            <!-- Metrics Cards -->
            <div class="stats-container grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="stat-card flex flex-col items-center text-center">
                    <div class="stat-icon text-4xl text-blue-600 mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>

                    <p class="stat-number text-3xl font-bold mb-1">
                        <?= $sampleCount >= 40 && !is_null($correlation) ? round($correlation * 100, 2) . '%' : 'N/A' ?>
                    </p>

                    <div class="stat-details">
                        <span class="stat-label font-semibold block">Calibration Correlation</span>
                        <div class="stat-formula text-sm text-gray-500">
                            Pearson correlation between confidence & actual accuracy
                        </div>
                    </div>
                </div>
                
                <div class="stat-card flex flex-col items-center text-center">
                    <div class="stat-icon text-4xl text-blue-600 mb-2">
                        <i class="fas fa-times-circle"></i>
                    </div>

                    <p class="stat-number text-3xl font-bold mb-1">
                        <?= !is_null($mismatchRate) ? $mismatchRate . '%' : 'N/A' ?>
                    </p>

                    <div class="stat-details">
                        <span class="stat-label font-semibold block">Prediction-Label Mismatch Rate</span>
                        <div class="stat-formula text-sm text-gray-500">
                            <?= $mismatchCount ?> / <?= $totalPending ?> × 100%
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($sampleCount < 40): ?>
                <div class="flex justify-center mt-4">
                    <span class="text-gray-500 text-sm font-medium">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Requires minimum 40 validated records to compute calibration correlation. (Current: <?= $sampleCount ?>)
                    </span>
                </div>
            <?php endif; ?>




            <!-- Key Metrics Table -->
            <div class="metrics-table">
                <table>
                    <tr>
                        <th>Total X-Ray Records</th>
                        <td><?= $db->xray_records->countDocuments() ?></td>
                    </tr>
                    <tr>
                        <th>Pending Doctor Verify</th>
                        <td><?= $pendingDoctorReview ?></td>
                    </tr>
                    <tr>
                        <th>Doctor Validated Records</th>
                        <td><?= $totalPending ?></td>
                    </tr>
                    <tr>
                        <th>Ready for Incremental Training</th>
                        <td><?= $sampleCount ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="training-section">
        <form method="POST" action="">
            <input type="hidden" name="action" value="incremental_training">
            <div class="training-button-wrapper">
                <button type="submit" class="btn btn-training" 
                    <?= $enableTrainingButton ? '' : 'disabled' ?>>
                    <i class="fas fa-brain"></i> Activate Model Incremental Training
                </button>
            </div>
            <div class="training-info">
                <?php if (!$enableTrainingButton): ?>
                    <?php if ($sampleCount < 100): ?>
                        <span class="text-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Requires at least 100 validated samples for training (current: <?= $sampleCount ?>)
                        </span>
                    <?php else: ?>
                        <span class="text-yellow-600">
                            <i class="fas fa-info-circle"></i>
                            Correlation is already high (<?= round($correlation * 100, 2) ?>%), no retraining needed.
                        </span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="text-success">
                        <i class="fas fa-check-circle"></i>
                        Ready for training (<?= $sampleCount ?> samples, correlation = <?= round($correlation * 100, 2) ?>%)
                    </span>
                <?php endif; ?>
            </div>
        </form>
    </div>

        <?php elseif($activeTab === 'applications'): ?>
        <div class="data-table">
            <h2>Pending Applications</h2>
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>UEN</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingApplications as $app): ?>
                        <tr>
                            <td><?= htmlspecialchars($app['email']) ?></td>
                            <td><?= htmlspecialchars($app['uen']) ?></td>
                            <td><?= $app['created_at']->toDateTime()->format('Y-m-d H:i') ?></td>
                            <td>
                                <button class="btn btn-primary view-application" 
                                        data-id="<?= (string)$app['_id'] ?>"
                                        data-details='<?= json_encode(array_merge($app->getArrayCopy(), ['_id' => (string)$app['_id']])) ?>'>
                                    <i class="fas fa-eye"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Application Details Modal -->
        <div class="modal" id="applicationModal">
        <div class="modal-content">
            <form id="applicationForm" method="POST">
                <input type="hidden" name="application_id" id="applicationId">
                <div id="applicationDetails"></div>
                
                <div class="approve-fields" id="approveFields">
                <div class="form-group">
                    <label for="name">Test Center Name:</label>
                    <input type="text" name="name" id="name" readonly>
                </div>
                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" name="password" id="password">
                    </div>
                </div>
                
                <div class="form-group" style="margin-top: 1rem;">
                    <button type="submit" name="action" value="approve" class="btn btn-primary">Approve</button>
                    <button type="submit" name="action" value="disapprove" class="btn btn-danger">Disapprove</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                </div>
            </form>
        </div>
    </div>

    <?php elseif($activeTab === 'clinics'): ?>

    <div class="data-table">
        <!-- Search and Filter Form -->
        <div class="search-filter" style="margin-bottom: 20px;">
            <form method="GET" action="">
                <input type="hidden" name="tab" value="clinics">
                <div class="filter-group">
                    <input type="text" name="search_name" placeholder="Search by clinic name..." 
                        value="<?= htmlspecialchars($searchName) ?>" class="form-input">
                    <select name="subscription_plan" class="form-select">
                        <option value="">All Subscription Plans</option>
                        <option value="basic" <?= $subscriptionPlan === 'basic' ? 'selected' : '' ?>>Basic</option>
                        <option value="intermediate" <?= $subscriptionPlan === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
                        <option value="premium" <?= $subscriptionPlan === 'premium' ? 'selected' : '' ?>>Premium</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Clinics Table -->
        <h2>Registered Clinics</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Subscription Plan</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($clinics as $clinic): 
                if (!$clinic instanceof MongoDB\Model\BSONDocument) continue;
                
                $clinicData = $clinic->getArrayCopy();
                $clinicData = [
                    '_id' => (string)($clinicData['_id'] ?? ''),
                    'name' => $clinicData['name'] ?? 'N/A',
                    'email' => $clinicData['email'] ?? 'N/A',
                    'subscription_plan' => $clinicData['subscription_plan'] ?? 'N/A',
                    'status' => $clinicData['status'] ?? 'Unknown',
                    'testers' => $clinicData['testers'] ?? 0,
                    'hbp' => $clinicData['hbp'] ?? 'N/A',
                    'uen' => $clinicData['uen'] ?? 'N/A',
                    'billing_plan' => $clinicData['billing_plan'] ?? 'N/A',
                    'hpb_certification' => $clinicData['hpb_certification'] ?? '',
                    'address' => $clinicData['address'] ?? 'N/A',
                    'rating' => $clinicData['rating'] ?? 0,
                    'reviews' => $clinicData['reviews'] ?? 0,
                    'availableTimings' => iterator_to_array($clinicData['availableTimings'] ?? [])
                ];
            ?>
                    <tr>
                        <td><?= htmlspecialchars($clinicData['name']) ?></td>
                        <td><?= htmlspecialchars($clinicData['email']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($clinicData['subscription_plan'])) ?></td>
                        <td>
                            <span class="status-badge <?= strtolower($clinicData['status']) ?>">
                                <?= htmlspecialchars($clinicData['status']) ?>
                            </span>
                        </td>
                        <td>
                        <button class="btn btn-primary view-clinic" 
                                data-details='<?= htmlspecialchars(
                                    json_encode($clinicData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), 
                                    ENT_QUOTES, 
                                    'UTF-8', 
                                    true
                                ) ?>'>
                            <i class="fas fa-eye"></i> View
                        </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Clinic Details Modal -->
    <div class="modal" id="clinicModal">
    <div class="modal-content" style="max-width: 600px;">
        <span class="close-btn" onclick="closeClinicModal()">&times;</span>
        <div id="clinicDetails" class="modal-body">
        </div>
    </div>

    <?php else: ?>
        <!-- Stats Overview -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <p class="stat-number"><?= $pendingApplicationsCount ?></p>
                <span class="stat-label">Pending Applications</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-hospital"></i></div>
                <p class="stat-number"><?= $activeClinicsCount ?></p>
                <span class="stat-label">Active Clinics</span>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <p class="stat-number"><?= $newFeedbacksCount ?></p>
                <span class="stat-label">New Feedbacks</span>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-container">
            <canvas id="covidChart"></canvas>
        </div>
        <?php endif; ?>
    </main>

    <script>
        // COVID Chart
        <?php if($activeTab === 'dashboard'): ?>
        const ctx = document.getElementById('covidChart');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Positive Cases',
                    data: [65, 59, 80, 81],
                    backgroundColor: 'rgba(42, 92, 130, 0.8)',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    title: { 
                        display: true,
                        text: 'Weekly COVID-19 Cases Report'
                    }
                }
            }
        });
        <?php endif; ?>

        document.addEventListener('click', function (event) {
    // Handling the application modal
    if (event.target.classList.contains('view-application')) {
        try {
            console.log('Raw data (Application):', event.target.dataset.details);
            const details = JSON.parse(event.target.dataset.details);
            document.getElementById('name').value = details.name || '';
            const modal = document.getElementById('applicationModal');
            const detailsDiv = document.getElementById('applicationDetails');

            // Use the string ID from data-id attribute
            document.getElementById('applicationId').value = event.target.dataset.id;

            let html = `<h3>Application Details</h3>`;
            for (const [key, value] of Object.entries(details)) {
                if (key === '_id') continue;

                if (key === 'created_at') {
                    let formattedDate = "Unknown Date";
                    if (typeof value === 'object' && value.hasOwnProperty('$date')) {
                        if (typeof value.$date === 'object' && value.$date.hasOwnProperty('$numberLong')) {
                            formattedDate = new Date(parseInt(value.$date.$numberLong)).toLocaleString();
                        } else {
                            formattedDate = new Date(value.$date).toLocaleString();
                        }
                    }
                    html += `<p><strong>${key}:</strong> ${formattedDate}</p>`;
                } else if (key === 'hpb_certification') {
                    html += `
                        <p><strong>${key}:</strong></p>
                        <img src="${value}" alt="Certification Image" style="max-width: 100%; height: auto; border: 1px solid #ccc; padding: 5px;">
                    `;
                } else {
                    html += `<p><strong>${key}:</strong> ${value}</p>`;
                }
            }
            detailsDiv.innerHTML = html;
            modal.style.display = 'flex';
        } catch (e) {
            console.error('Error parsing application data:', e);
        }
    }

    // Handling the clinic modal
    if (event.target.classList.contains('view-clinic')) {
        try {
            console.log('Raw data (Clinic):', event.target.dataset.details);
            const details = JSON.parse(event.target.dataset.details);
            const modal = document.getElementById('clinicModal');
            const detailsDiv = document.getElementById('clinicDetails');

            const statusAction = (details.status === 'Suspended') ? 'unsuspend' : 'suspend';
            const actionButtonText = (details.status === 'Suspended') ? 'Unsuspend Account' : 'Suspend Account';
            const actionButtonClass = (details.status === 'Suspended') ? 'btn-success' : 'btn-danger';


            let html = `
                <div class="clinic-header">
                    <h3>${details.name}</h3>
                    <p class="clinic-status ${details.status.toLowerCase()}">${details.status}</p>
                </div>
                <div class="clinic-details">
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value">${details.email}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Testers Capacity:</span>
                        <span class="detail-value">${details.testers}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">HBP Number:</span>
                        <span class="detail-value">${details.hbp}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">UEN Number:</span>
                        <span class="detail-value">${details.uen}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Subscription Plan:</span>
                        <span class="detail-value">${details.subscription_plan}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Billing Plan:</span>
                        <span class="detail-value">${details.billing_plan}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value">${details.address}</span>
                    </div>
                    <div class="certification-image">
                        <p><strong>HPB Certification:</strong></p>
                        <img src=${details.hpb_certification} 
                        alt="HPB Certification">
                    </div>
                </div>
                <div class="modal-actions" style="margin-top: 20px; display: flex; gap: 10px;">
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="clinic_id" value="${details._id}">
                        <input type="hidden" name="clinic_action" value="${statusAction}">
                        <button type="submit" class="btn ${actionButtonClass}">${actionButtonText}</button>
                    </form>
                    <form method="POST" action="" style="margin: 0;">
                        <input type="hidden" name="clinic_id" value="${details._id}">
                        <input type="hidden" name="billing_action" value="send_billing">
                        <button type="submit" class="btn btn-primary">Send Billing</button>
                    </form>
                </div>
            `;

            detailsDiv.innerHTML = html;
            modal.style.display = 'flex';
        } catch (e) {
            console.error('Error parsing clinic data:', e);
        }
    }
});


        function closeModal() {
            document.getElementById('applicationModal').style.display = 'none';
        }

        // Modify the submit handler to ensure form submission
        document.getElementById('applicationForm').addEventListener('submit', function (e) {
        const action = e.submitter.value; // Get which button was clicked

        if (action === 'approve') {
            document.getElementById('name').required = true;
            document.getElementById('password').required = true;
            
            if (!document.getElementById('name').value || !document.getElementById('password').value) {
                e.preventDefault();
                document.getElementById('approveFields').style.display = 'block';
            }
        } else if (action === 'disapprove') {
            // REMOVE required attributes so Disapprove can submit
            document.getElementById('name').removeAttribute('required');
            document.getElementById('password').removeAttribute('required');
        }
    });


        // Show approve fields when approve is clicked
        document.querySelectorAll('[name="action"]').forEach(button => {
            button.addEventListener('click', function() {
                if (this.value === 'approve') {
                    document.getElementById('approveFields').style.display = 'block';
                } else {
                    document.getElementById('approveFields').style.display = 'none';
                }
            });
        });

        

        function closeClinicModal() {
            document.getElementById('clinicModal').style.display = 'none';
        }

        <?php if($activeTab === 'model_performance'): ?>
<script>
    const modelCtx = document.getElementById('modelChart').getContext('2d');
    new Chart(modelCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(function($d) {
                return $d['timestamp']->toDateTime()->format('M d');
            }, $historicalData)) ?>,
            datasets: [{
                label: 'Accuracy',
                data: <?= json_encode(array_column($historicalData, 'accuracy')) ?>,
                borderColor: '#2A5C82',
                tension: 0.4
            }, {
                label: 'F1 Score',
                data: <?= json_encode(array_column($historicalData, 'f1_score')) ?>,
                borderColor: '#4CAF50',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Model Performance Over Time'
                }
            },
            scales: {
                y: {
                    min: 0,
                    max: 1,
                    ticks: {
                        callback: value => (value * 100).toFixed(0) + '%'
                    }
                }
            }
        }
    });
</script>
<?php endif; ?>

document.querySelector('form[action=""]').addEventListener('submit', function(e) {
    if (e.target.querySelector('[name="action"][value="incremental_training"]')) {
        const btn = e.target.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Training...';
        btn.disabled = true;
    }
});

    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
