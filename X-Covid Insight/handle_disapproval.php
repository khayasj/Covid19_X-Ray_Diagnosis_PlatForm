<?php
require 'db.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate inputs
    $requiredFields = ['id', 'diagnosis', 'image_path', 'category'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field", 400);
        }
    }

    if (!in_array($data['category'], ['approvalNeeded', 'validation'])) {
        throw new Exception("Invalid category value", 400);
    }

    $collection = $db->xray_records;
    $validatedCollection = $db->validated_xrays;

    if ($data['category'] === 'approvalNeeded') {
        if ($data['diagnosis'] === 'Others') {
            // Delete approvalNeeded record
            $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($data['id'])]);
            
            if ($result->getDeletedCount() === 0) {
                throw new Exception("Record not found", 404);
            }
        } else {
            // Add to validated_xrays
            $insertResult = $validatedCollection->insertOne([
                'image_path' => $data['image_path'],
                'true_label' => $data['diagnosis'],
                'validation_date' => new MongoDB\BSON\UTCDateTime(),
            ]);
        
            // Update original approvalNeeded record
            $updateResult = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($data['id'])],
                ['$set' => [
                    'category' => 'disapproved',
                    'disapproval_reason' => 'Manual diagnosis: ' . $data['diagnosis']
                ]]
            );
        
            if ($updateResult->getModifiedCount() === 0) {
                throw new Exception("Failed to update record status", 500);
            }
        }
    } elseif ($data['category'] === 'validation') {
        if ($data['diagnosis'] === 'Others') {
            // Delete validation record
            $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($data['id'])]);
            
            if ($result->getDeletedCount() === 0) {
                throw new Exception("Record not found", 404);
            }
        } else {
            // Add to validated_xrays
            $insertResult = $validatedCollection->insertOne([
                'image_path' => $data['image_path'],
                'true_label' => $data['diagnosis'],
                'validation_date' => new MongoDB\BSON\UTCDateTime(),
            ]);
        
            // Update original validation record
            $updateResult = $collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($data['id'])],
                ['$set' => [
                    'category' => 'disapproved',
                    'validatedResult' => 'incorrect',  // Fixed missing comma here
                    'disapproval_reason' => 'Manual diagnosis: ' . $data['diagnosis']
                ]]
            );
        
            if ($updateResult->getModifiedCount() === 0) {
                throw new Exception("Failed to update record status", 500);
            }
        }
    }

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}