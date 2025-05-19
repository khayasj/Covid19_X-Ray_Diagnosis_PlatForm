<?php
require 'db.php';
require 'vendor/autoload.php';

use MongoDB\BSON\ObjectId;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $validated = $_POST['validated'] ?? null;
    $trueLabel = $_POST['trueLabel'] ?? null;

    if (!$id || $validated === null || !$trueLabel) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing required parameters'
        ]);
        exit;
    }

    try {
        $collection = $db->xray_records;

        $result = $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => [
                'hasBeenVal' => true,
                'trueLabel' => $trueLabel
            ]]
        );

        // 发邮件
        $isDisapproved = !filter_var($validated, FILTER_VALIDATE_BOOLEAN);
        if($isDisapproved){
            $updatedRecord = $collection->findOne(['_id' => new ObjectId($id)]);
            if ($updatedRecord && isset($updatedRecord['patient_id'])) {
                $patientId = $updatedRecord['patient_id'];
                $patient = $db->patient->findOne(['_id' => new ObjectId($patientId)]);

                if ($patient && isset($patient['email'])) {
                    $to = $patient['email'];
                    $subject = "Diagnosis Update for Your X-Ray";
                    $message = "Dear Patient,\n\nAfter review, your diagnosis has been updated to: {$trueLabel}.\n\nPlease log into the portal for more details.\n\nBest regards,\nX-COVID Insight";

                    $emailObj = new \SendGrid\Mail\Mail();
                    $emailObj->setFrom("xcovidinsight@gmail.com", "X-COVID Insight");
                    $emailObj->setSubject($subject);
                    $emailObj->addTo($to);
                    $emailObj->addContent("text/plain", $message);

                    $sendgrid = new \SendGrid('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');
                    try {
                        $response = $sendgrid->send($emailObj);
                    } catch (Exception $e) {
                        error_log('SendGrid Error: ' . $e->getMessage());
                    }
                }
            }
        }

        echo json_encode([
            'success' => $result->getModifiedCount() > 0
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
?>
