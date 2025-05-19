<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT); // Securely hash password

    $existingUser = $db->system_admin->findOne(["email" => $email]);

    if ($existingUser) {
        echo "Email already registered!";
    } else {
        $insertResult = $db->system_admin->insertOne([
            "name" => $name,
            "email" => $email,
            "password" => $password
        ]);
        if ($insertResult->getInsertedCount() > 0) {
            echo "Registration successful!";
        } else {
            echo "Registration failed.";
        }
    }
}
?>
