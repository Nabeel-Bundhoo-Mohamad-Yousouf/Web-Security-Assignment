<?php
require_once "includes/db_connect.php";
header('Content-Type: application/json');

/*--> Acts as an API endpoint */
/*Returns JSON array*/
$sql = "SELECT message_ID, sender_name, message_text, like_count 
        FROM Contact_Message 
        ORDER BY message_ID DESC";

try {
    $Result = $conn->query($sql); // Runs SQL query in DB 

    $messages = [];

    while ($row = $Result->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = $row; //each retrieved row is added to $messages array
    }

    echo json_encode($messages); //json response sent to contact&about.php

} catch(PDOException $e) {
    echo json_encode(["error" => "failed"]);
}
?>