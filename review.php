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

if (!$customer_ID) {
    $_SESSION['Msg'] = "Customer record not found. Please contact support.";
    header("Location: home.php");
    exit(); 
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    $old_inputs = [];

    if (empty($_POST['txt_name'])) {
        $errors['nameErr'] = "Name is required";
    } else {
        $old_inputs['name'] = test_input($_POST['txt_name']);
    }

    if (empty($_POST['txt_rating'])) {
        $errors['ratingErr'] = "Rating is required";
    } else {
        $old_inputs['rating'] = test_input($_POST['txt_rating']);
    }

    if (!empty($_POST['txt_comment'])) {
        $old_inputs['comment'] = test_input($_POST['txt_comment']);
    }

    if (empty($_POST['txt_book'])) {
        $errors['bookErr'] = "Please select a book";
    } else {
        $old_inputs['book'] = $_POST['txt_book'];
    }

    $_SESSION['errors'] = $errors;
    $_SESSION['old_inputs'] = $old_inputs;
    
if (empty($errors)) {
    list($book_ID, $title, $description) = explode("|", $_POST['txt_book']);
    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->beginTransaction();

        $sInsert = "INSERT INTO Review (customer_ID, book_ID, rating, review, date)
        VALUES (:customer_ID, :book_ID, :rating, :review, CURDATE())";

        $stmtInsert = $conn->prepare($sInsert);
        $stmtInsert->execute([
            ':customer_ID' => $customer_ID,
            ':book_ID' => $book_ID,
            ':rating' => $old_inputs['rating'],
            ':review' => $old_inputs['comment'] ?? ''
        ]);

        $conn->commit();
        $Msg = "Review submitted successfully!";
        $showForm = false;

        unset($_SESSION['errors']);
        unset($_SESSION['old_inputs']);
     } catch (Exception $e) {
        $conn->rollBack();
        $Msg = "Error: " . $e->getMessage();
    }
    }
}

$errors = $_SESSION['errors'] ?? [];
$old_inputs = $_SESSION['old_inputs'] ?? [];
$books = [];
if ($customer_ID) {
    $sQuery = "SELECT DISTINCT b.book_ID, b.description, b.price
     FROM Book b
     LEFT JOIN Review rv 
     ON b.book_ID = rv.book_ID AND rv.customer_ID = :customer_ID
     LEFT JOIN Rental r 
     ON b.book_ID = r.Book_ID AND r.customer_ID = :customer_ID
     LEFT JOIN Purchase p 
     ON b.book_ID = p.Book_ID AND p.customer_ID = :customer_ID
     WHERE rv.review_ID IS NULL
     AND (r.Book_ID IS NOT NULL OR p.Book_ID IS NOT NULL)";

$stmt = $conn->prepare($sQuery);
$stmt->execute(['customer_ID' => $customer_ID]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

