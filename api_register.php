<?php
session_start();
header('Content-Type: application/json');

require_once "includes/db_connect.php";

/*Prevents undefined index errors*/
$username = trim($_POST["txt_username"] ?? "");
$password = trim($_POST["txt_password"] ?? "");

/*Required fields username + password CHECK*/
if ($username === "" || $password === "") {
    
    //returns JSON  response for AJAX to display on register.php
    echo json_encode(["status"=>"error","message"=>"All fields required"]);
    exit;
}

/*Input sanitisation --> Regex Expressions*/
if (!preg_match("/^[A-Za-z ]{3,30}$/", $username)) {

    
    echo json_encode(["status"=>"error","message"=>"Invalid username format"]);
    exit;
}



try {
/*Series of database operations begans here--> if one fails all cancels*/

/*FIRST*/
    $conn->beginTransaction();

    /* 1. CHECK IF USER EXISTS*/
    
    /*Use prepared statements to prevent SQL injection*/
    $check = $conn->prepare("SELECT user_ID FROM Users WHERE username = ?");
    $check->execute([$username]);

    if ($check->fetch()) {
        echo json_encode(["status"=>"error","message"=>"Username already exists"]);
        exit;
    }

    /* 2. GENERATE user_ID MANUALLY  */
    $stmt = $conn->query("SELECT MAX(user_ID) AS max_id FROM Users");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $user_ID = ($row['max_id'] === null || $row['max_id'] == 0)
        ? 1
        : $row['max_id'] + 1;

    /*stores hashed password --> Protects credentials if leaked*/
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    /*  3. INSERT INTO USERS */
    $stmt = $conn->prepare("INSERT INTO Users (user_ID, username, password) VALUES (?, ?, ?)");
    $stmt->execute([$user_ID, $username, $hashed]);

    /* 4. GENERATE customer_ID */
    $stmt = $conn->query("SELECT MAX(customer_ID) AS max_id FROM Customer");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $customer_ID = ($row['max_id'] === null || $row['max_id'] == 0)
        ? 1
        : $row['max_id'] + 1;

    /* 5. INSERT INTO CUSTOMER */
    $stmt = $conn->prepare("
        INSERT INTO Customer (customer_ID, user_ID, customer_name)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$customer_ID, $user_ID, $username]);

    /* 6. GENERATE Registration_No*/
    $stmt = $conn->query("SELECT MAX(Registration_No) AS max_id FROM Registration_Form");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $registration_no = ($row['max_id'] === null || $row['max_id'] == 0)
        ? 1
        : $row['max_id'] + 1;

    /*  7. INSERT INTO REGISTRATION FORM */
    $stmt = $conn->prepare("
        INSERT INTO Registration_Form (Registration_No, customer_ID, date_time)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$registration_no, $customer_ID]);

    /* 8. COMMIT */
     
    /*SECOND*/
    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Registration successful"
    ]);

} catch(PDOException $e) {

/*THIRD*/
    $conn->rollBack();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}