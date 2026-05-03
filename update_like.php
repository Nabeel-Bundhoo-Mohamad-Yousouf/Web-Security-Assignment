<?php
if(isset($_POST['message_id'])) { //following code executed only if contact&about sent message ID via AJAX request

    require_once "includes/db_connect.php";

    $id = $_POST['message_id'];

//Updates like count in DB 
    $sql = "UPDATE Contact_Message 
            SET like_count = like_count + 1 
            WHERE message_ID = :message_id";

    try {
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':message_id', $id);
        $stmt->execute();

        echo "success";

    } catch(PDOException $e) {
        echo "error";
    }

} else {
    echo "error";
}
?>