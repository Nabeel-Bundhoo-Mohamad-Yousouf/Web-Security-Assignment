<?php
session_start();
header('Content-Type: application/json');

require_once "includes/db_connect.php";

/* PREVENTS UNDEFINED INDEX ERRORS --> same as api_register */
$username = trim($_POST["txt_username"] ?? "");
$password = trim($_POST["txt_password"] ?? "");

/*Required fields */
if ($username === "" || $password === "") {
    echo json_encode(["status"=>"error","message"=>"All fields required"]);
    exit;
}

/*Input sanitisation (regex) */
if (!preg_match("/^[A-Za-z ]{3,30}$/", $username)) {
    echo json_encode(["status"=>"error","message"=>"Invalid username format"]);
    exit;
}

try {
    /*Authentication query --> Checks if username exists */
    $stmt = $conn->prepare("SELECT user_ID, password FROM Users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    /*Verifies password */
    if ($user && password_verify($password, $user['password'])) {

        /*Maintains authentication state and enables access to certain pages*/    
        $_SESSION['logged_in'] = $username;
        $_SESSION['user_id'] = $user['user_ID'];

        echo json_encode(["status"=>"success","message"=>"Login successful"]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Invalid credentials"]);
    }

} catch(PDOException $e) {
    echo json_encode(["status"=>"error","message"=>"Database error"]);
}