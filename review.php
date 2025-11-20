<?php
session_start();
require_once "includes/db_connect.php";

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: home.php?referer=review");
    exit();
}

$Msg = "";
$showForm = true;
if (!isset($_SESSION['errors'])) $_SESSION['errors'] = [];
if (!isset($_SESSION['old_inputs'])) $_SESSION['old_inputs'] = [];

// Fetch customer_ID for logged-in user
$stmt = $conn->prepare("
    SELECT c.customer_ID 
    FROM Customer c
    JOIN Users u ON c.user_ID = u.user_ID
    WHERE u.username = :username
");
$stmt->execute(['username' => $_SESSION['username']]);
$customer_ID = $stmt->fetchColumn();
