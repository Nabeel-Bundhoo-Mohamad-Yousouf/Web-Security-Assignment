<?php
header('Content-Type: application/json');

require_once "vendor/autoload.php";
require_once "includes/db_connect.php";

use Opis\JsonSchema\Validator;
use Opis\JsonSchema\Schema;

$response = ["status" => "error", "errors" => [], "message" => ""];

function clean_input($data) {
    return htmlspecialchars(trim($data)); //Prevents XSS attacks
}

/* STEP 1: READ FORM DATA*/
$name = clean_input($_POST["name"] ?? "");
$email = clean_input($_POST["email"] ?? "");
$message = clean_input($_POST["message"] ?? "");

/* STEP 2: BASIC VALIDATION */
if ($name === "" || $email === "" || $message === "") {
    $response["errors"]["general"] = "All fields required";
    echo json_encode($response);
    exit;
}

/* STEP 3: OPIS SCHEMA VALIDATION */
try {

    $schemaFile = __DIR__ . "/contact_schema.json"; // This schema defines the expected data format

    if (file_exists($schemaFile)) {

        $schema = Schema::fromJsonString(file_get_contents($schemaFile));
        $validator = new Validator(); //enforce strict data structure

        $data = (object)[
            "name" => $name,
            "email" => $email,
            "message" => $message
        ];

        $result = $validator->validate($data, $schema);

        if (!$result->isValid()) {
            $response["errors"]["schema"] = "Schema validation failed";
            echo json_encode($response);
            exit;
        }
    }

} catch (Exception $e) {
    // do NOT crash app if schema fails
}

/*  STEP 4: Regex expression --> name, email & message validation*/
if (!preg_match("/^[a-zA-Z0-9 '.-]{2,50}$/", $name)) {
    $response['errors']['name'] = "Invalid name format.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors']['email'] = "Invalid email format.";
}

if (strlen($message) < 5) {
    $response['errors']['message'] = "Message too short.";
}

/* STEP 5: INSERT INTO DB */
  if (empty($response['errors'])) {
        try {

            //Generates message_ID MANUALLY
            $stmt_max = $conn->prepare("SELECT MAX(message_ID) AS max_id FROM Contact_Message");
            $stmt_max->execute();
            $result = $stmt_max->fetch(PDO::FETCH_ASSOC);
            $message_ID = ($result['max_id'] === null) ? 1 : $result['max_id'] + 1;

            //Insert data in table Contact_Message
            $stmt = $conn->prepare("INSERT INTO Contact_Message (message_ID, sender_name, sender_email, message_text, date_sent) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$message_ID, $name, $email, $message]);

            $response['status'] = "success";
            $response['message'] = "Thank you $name! Your message has been saved.";
        } catch(PDOException $e) {
            $response['message'] = "Database error: " . $e->getMessage();
        }
    }



/* OUTPUT JSON */
echo json_encode($response);