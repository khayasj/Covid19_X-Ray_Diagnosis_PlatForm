<?php
require 'vendor/autoload.php';
require 'db.php'; // Use existing MongoDB connection

use SendGrid\Mail\Mail;
use MongoDB\Client;

// Use the existing MongoDB connection
$appointmentCollection = $db->appointment;
$patientCollection = $db->patient;
$timeSlotCollection = $db->timeslot;


// Get current date and time
$now = new DateTime('now', new DateTimeZone('UTC')); // Ensure UTC timezone
$now->modify('+2 days'); // Add 48 hours
$targetDate = $now->format('Y-m-d'); // Format to match MongoDB date format

// Find appointments that are 48 hours away
$appointments = $appointmentCollection->find([
    'appointment_date' => $targetDate // Match appointments by date
]);

// Send email reminders
foreach ($appointments as $appointment) {
    $patientId = $appointment['patient_id']; // Get patient ID

    // Fetch patient details using patient_id
    $patient = $patientCollection->findOne(['_id' => new MongoDB\BSON\ObjectId($patientId)]);

    if (!$patient || !isset($patient['email'])) {
        continue; // Skip if no patient found or email is missing
    }

    // ✅ Get timeslot ID from the appointment
    if (!isset($appointment['timeslot_id'])) continue;

    $timeslot = $timeSlotCollection->findOne([
        '_id' => new MongoDB\BSON\ObjectId($appointment['timeslot_id'])
    ]);

    // If timeslot is not found, skip
    if (!$timeslot || !isset($timeslot['StartTime'])) {
        continue;
    }

    // ✅ Compose time string
    $time = $timeslot['StartTime'] . ' - ' . $timeslot['EndTime'];

    // Get patient details
    $email = $patient['email'];
    $name = $patient['name'] ?? 'Patient'; // Default to "Patient" if name is missing
    $date = $appointment['appointment_date'];
    $time = $timeslot['StartTime'] . ' - ' . $timeslot['EndTime'];

    // Prepare email
    $emailObj = new Mail();
    $emailObj->setFrom("xcovidinsight@gmail.com", "X COVID INSIGHT");
    $emailObj->setSubject("Appointment Reminder");
    $emailObj->addTo($email, $name);
    $emailObj->addContent(
        "text/plain",
        "Hello $name,\n\nThis is a reminder that you have an appointment scheduled on $date at $time.\n\nThank you."
    );

    // Send the email using SendGrid
    $sendgrid = new \SendGrid("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");
    try {
        $response = $sendgrid->send($emailObj);
        echo "Reminder sent to $email\n";
    } catch (Exception $e) {
        echo "Error sending email: " . $e->getMessage() . "\n";
    }
}
?>



