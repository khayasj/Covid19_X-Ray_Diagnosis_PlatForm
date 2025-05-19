<?php
require 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    // Removed: $userType = $_POST["user_type"];

    $error = '';
    // Removed user_type from params
    $params = http_build_query(['email' => urlencode($email)]);

    $user = null;
    $userType = null;

    // ======== STEP 2 ADDITION START ======== //
    // Check all collections in order of priority
    $collections = [
        'system_admin' => $db->system_admin,
        'patient' => $patientCollection,
        'doctor' => $doctorCollection,
        'covid_tester' => $db->covid_tester,
        'test_center_admin' => $db->test_centre,
    ];

    // Search for user in all collections
    foreach ($collections as $type => $collection) {
        $user = $collection->findOne(["email" => $email]);
        if ($user) {
            $userType = $type;
            break;
        }
    }
    // ======== STEP 2 ADDITION END ======== //

    // Removed: user_type validation check

    if (!$user) {
        $error = "Invalid email or password."; // Updated error message
        header("Location: loginPage.html?error=" . urlencode($error) . "&" . $params);
        exit();
    }

    // Check if the account is suspended (existing logic remains same)
    if (isset($user["status"]) && strtolower($user["status"]) == "suspended") {
        if (in_array($userType, ["test_center_admin", "covid_tester", "doctor"])) {
            $error = "Your account has been suspended. Please contact support.";
            header("Location: loginPage.html?error=" . urlencode($error) . "&" . $params);
            exit();
        }
    }


    // Verify user credentials
    if (password_verify($password, $user["password"])) {
        // Store user session
        $_SESSION["user_type"] = $userType;
        $_SESSION["email"] = $user["email"];
        $_SESSION["name"] = $user["name"];

        // Store additional details based on user type
        if ($userType == "doctor") {
            $_SESSION["id"] = (string) $user["_id"];
            $_SESSION["location"] = $user["location"];
            $_SESSION["experience"] = $user["experience"];
            $_SESSION["phone"] = $user["phone"];
            $_SESSION["certificateImg"] = $user["certification"];


            // Inject sessionStorage for doctor
            echo "<script>
                sessionStorage.setItem('userType', 'doctor');
                sessionStorage.setItem('doctorId', '" . $user['_id'] . "');
                sessionStorage.setItem('doctorName', '" . $user['name'] . "');
                sessionStorage.setItem('doctorEmail', '" . $user['email'] . "');
                sessionStorage.setItem('doctorPhone', '" . $user['phone'] . "');
                sessionStorage.setItem('doctorExperience', '" . $user['experience'] . "');
                sessionStorage.setItem('doctorTestCenterId', '" . $user['location'] . "');
                sessionStorage.setItem('doctorCertificateImg', '" . $user['certification'] . "');
                window.location.href = 'Doctor_Homepage.html'; // Redirect to doctor homepage
            </script>";
        } elseif ($userType == "patient") {
            $_SESSION["id"] = (string) $user["_id"];
            $_SESSION["address"] = $user["address"];
            $_SESSION["phone"] = $user["phone"];
            $_SESSION["gender"] = $user["gender"];
            $_SESSION["dob"] = $user["dob"];

            // Inject sessionStorage for patient
            echo "<script>
                sessionStorage.setItem('userType', 'patient');
                sessionStorage.setItem('patientId', '" . $user['_id'] . "');
                sessionStorage.setItem('patientName', '" . $user['name'] . "');
                sessionStorage.setItem('patientEmail', '" . $user['email'] . "');
                sessionStorage.setItem('patientAddress', '" . $user['address'] . "');
                sessionStorage.setItem('patientPhone', '" . $user['phone'] . "');
                sessionStorage.setItem('patientGender', '" . $user['gender'] . "');
                sessionStorage.setItem('patientDob', '" . $user['dob'] . "');
                window.location.href = 'Patient_Homepage.html'; // Redirect to patient homepage
            </script>";
        }
        elseif ($userType == "covid_tester") {
            $_SESSION["id"] = (string) $user["_id"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["location"] = $user["location"];

            // Inject sessionStorage for covid_tester
            echo "<script>
                sessionStorage.setItem('userType', 'covid_tester');
                sessionStorage.setItem('covidTesterId', '" . $user['_id'] . "');
                sessionStorage.setItem('covidTesterName', '" . $user['name'] . "');
                sessionStorage.setItem('covidTesterEmail', '" . $user['email'] . "');
                window.location.href = 'covidTester_Homepage.php'; // Redirect to covid tester homepage
            </script>";
        }
        elseif ($userType == "system_admin") {
            $_SESSION["id"] = (string) $user["_id"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["name"] = $user["name"];

            // Inject sessionStorage for system_admin
            echo "<script>
                sessionStorage.setItem('userType', 'system_admin');
                sessionStorage.setItem('covidTesterId', '" . $user['_id'] . "');
                sessionStorage.setItem('covidTesterName', '" . $user['name'] . "');
                sessionStorage.setItem('covidTesterEmail', '" . $user['email'] . "');
                window.location.href = 'sysadmin_dashboard.php'; // Redirect to covid tester homepage
            </script>";
        } elseif ($userType == "test_center_admin") {
            $_SESSION["id"] = (string) $user["_id"];
            $_SESSION["email"] = $user["email"];
            $_SESSION["name"] = $user["name"];
            $_SESSION["testers"] = $user["testers"];
            $_SESSION["hbp"] = $user["hbp"];
            $_SESSION["uen"] = $user["uen"];
            $_SESSION["address"] = $user["address"];
            $_SESSION["subscription_plan"] = $user["subscription_plan"];
            $_SESSION["billing_plan"] = $user["billing_plan"];
            $_SESSION["hpb_certification"] = $user["hpb_certification"];
            $_SESSION["status"] = $user["status"];

            // Inject sessionStorage for test_centre_admin_admin
            echo "<script>
                sessionStorage.setItem('userType', 'test_center_admin');
                sessionStorage.setItem('testCenterAdminId', '" . $user['_id'] . "');
                sessionStorage.setItem('testCenterAdminName', '" . $user['name'] . "');
                sessionStorage.setItem('testCenterAdminEmail', '" . $user['email'] . "');
                sessionStorage.setItem('testCenterAdminTesters', '" . $user['testers'] . "');
                sessionStorage.setItem('testCenterAdminHBP', '" . $user['hbp'] . "');
                sessionStorage.setItem('testCenterAdminUEN', '" . $user['uen'] . "');
                sessionStorage.setItem('testCenterAdminAddress', '" . $user['address'] . "');
                sessionStorage.setItem('testCenterAdminSubscriptionPlan', '" . $user['subscription_plan'] . "');
                sessionStorage.setItem('testCenterAdminBillingPlan', '" . $user['billing_plan'] . "');
                sessionStorage.setItem('testCenterAdminHPBCertification', '" . $user['hpb_certification'] . "');
                sessionStorage.setItem('testCenterAdminStatus', '" . $user['status'] . "');
                window.location.href = 'testcenteradmin_homepage.php'; // Redirect to covid tester homepage
            </script>";
        }
        exit();
    } else {
        // 🔺 Modified invalid credentials handling
        $error = "Invalid email or password.";
        header("Location: loginPage.html?{$params}&error=" . urlencode($error));
        exit();
    }
}
?>