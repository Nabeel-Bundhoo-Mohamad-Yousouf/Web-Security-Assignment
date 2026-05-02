<?php
session_start();

require_once "includes/db_connect.php"; 

require_once 'vendor/autoload.php'; 

use Opis\JsonSchema\Validator;

header('Content-Type: application/json');

// Ensure user is authorized before processing anything
if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'errors' => ['Unauthorized access. Please log in.']]);
    exit;
}

// 1. Consume the JSON Payload
$jsonInput = file_get_contents('php://input');
$requestData = json_decode($jsonInput);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'errors' => ['Invalid JSON payload.']]);
    exit;
}

// 2. Validate against Opis JSON Schema
$validator = new Validator();
$schema = file_get_contents('review_schema.json');
$result = $validator->validate($requestData, $schema);

if (!$result->isValid()) {
    $error = $result->error();
    http_response_code(422); 
    echo json_encode([
        'status' => 'error', 
        'errors' => ["Validation failed: " . $error->keyword() . " error on " . implode('.', $error->data()->fullPath())]
    ]);
    exit;
}

// 3. Process to Database using PDO
try {
    //  Fetch customer ID based on session username
    $stmt = $conn->prepare("CALL GetCustomerID(?)");
    $stmt->execute([$_SESSION['username']]);
    $customer_ID = $stmt->fetchColumn();
    $stmt->closeCursor();

    if (!$customer_ID) {
        throw new Exception("Customer ID not found for the current user.");
    }

    //Parse the book dropdown string (Format: type|record_ID|book_ID)
    list($type, $record_ID, $book_ID) = explode("|", $requestData->book);

    //  Execute the stored procedure to insert the review
    $stmtCall = $conn->prepare("
        CALL AddReviewAndMarkReviewed(
            :p_customer_ID, 
            :p_book_ID, 
            :p_rating, 
            :p_review, 
            :p_type, 
            :p_record_ID
        )
    ");
    
    $stmtCall->execute([
        ':p_customer_ID' => $customer_ID,
        ':p_book_ID'     => $book_ID,
        ':p_rating'      => $requestData->rating,
        ':p_review'      => $requestData->comment ?? '', 
        ':p_type'        => $type,
        ':p_record_ID'   => $record_ID
    ]);
    
    $stmtCall->closeCursor();

    // 4. Return Success Response
    http_response_code(201); 
    echo json_encode([
        'status' => 'success',
        'message' => 'Review successfully saved to the database.',
        'data' => [
            'name' => htmlspecialchars($requestData->name),
            'rating' => $requestData->rating
        ]
    ]);

} catch (PDOException $e) {

    error_log("Database Error in API: " . $e->getMessage());
    
    // Return a clean error to the client
    http_response_code(500); 
    echo json_encode(['status' => 'error', 'errors' => ['A database error occurred while saving your review.']]);

} catch (Exception $e) {
    //error_log("General Error in API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'errors' => [$e->getMessage()]]);
}
?>