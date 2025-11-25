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

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body { 
        background: #ebefff;
        font-family: 'Segoe UI', sans-serif;
    }
    .review-container {
        max-width: 500px;
        background: #fff;
        padding: 30px;
        border-radius: 15px;
        margin: 50px auto;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
    .stars {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
        gap: 5px;
    }
    .stars input {
        display: none;
    }
    .stars label {
        font-size: 30px;
        color: #ccc;
        cursor: pointer;
        transition: 0.2s;
    }
    .stars input:checked ~ label,
    .stars label:hover,
    .stars label:hover ~ label {
        color: #f7d106;
    }
    .text-danger {
        font-size: 0.9em;
        margin-top: 5px;
    }
    button {
        width: 48%; 
    } 
    </style> 
</head>
<body>
<div class="review-container">
    <h3 class="mb-3">Submit a Review</h3>
    <?php if ($showForm && !empty($errors)): ?>
    <p class="text-danger mb-3">* required field</p>
    <?php endif; ?>
    
    <?php if ($showForm): ?>
    <?php if (!empty($books)): ?>
    <form method="post">
        <div class="mb-3">
        <label>Name:</label>
        <input type="text" class="form-control" name="txt_name" value="<?php echo $old_inputs['name'] ?? ''; ?>">
        <div class="text-danger"><?php echo $errors['nameErr'] ?? ''; ?></div>
        </div>

        <div class="mb-3">
        <label>Rating:</label>
        <div class="stars">
        <input type="radio" id="star5" name="txt_rating" value="5" <?php if (($old_inputs['rating'] ?? '') == '5') echo 'checked'; ?>>
        <label for="star5" class="fa fa-star"></label>

        <input type="radio" id="star4" name="txt_rating" value="4" <?php if (($old_inputs['rating'] ?? '') == '4') echo 'checked'; ?>>
        <label for="star4" class="fa fa-star"></label>

        <input type="radio" id="star3" name="txt_rating" value="3" <?php if (($old_inputs['rating'] ?? '') == '3') echo 'checked'; ?>>
        <label for="star3" class="fa fa-star"></label>

        <input type="radio" id="star2" name="txt_rating" value="2" <?php if (($old_inputs['rating'] ?? '') == '2') echo 'checked'; ?>>
        <label for="star2" class="fa fa-star"></label>

        <input type="radio" id="star1" name="txt_rating" value="1" <?php if (($old_inputs['rating'] ?? '') == '1') echo 'checked'; ?>>
        <label for="star1" class="fa fa-star"></label>
        </div>
        <div class="text-danger"><?php echo $errors['ratingErr'] ?? ''; ?></div>
        </div>

        <div class="mb-3">
        <label>Select Book:</label>
        <select class="form-select" name="txt_book">
            <option value="">--Select--</option>
            <?php foreach ($books as $book):
            $val = $book['book_ID'] . "|" . $book['description'] . "|" . $book['price']; ?>
            <option value="<?php echo $val; ?>" <?php if (($old_inputs['book'] ?? '') == $val) echo 'selected'; ?>>
            <?php echo htmlspecialchars($book['description']); ?>
            </option>
            <?php endforeach; ?>
        </select> 
        <div class="text-danger"><?php echo $errors['bookErr'] ?? ''; ?></div>
        </div>

        <div class="mb-3">
        <label>Comment:</label>
        <textarea class="form-control" name="txt_comment" rows="4"><?php echo $old_inputs['comment'] ?? ''; ?></textarea>
        </div>

        <div class="d-flex justify-content-between">
        <button type="submit" class="btn btn-primary">Submit</button>
        <button type="reset" class="btn btn-secondary">Reset</button>
        </div>
        </form>
        <?php else: ?>
            <h5 class="text-success">You have no books to review. Thank you!</h5>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($Msg != ""): ?>
        <h5 class="mt-3"><?php echo $Msg; ?></h5>
    <?php endif; ?>
</div>
</body>
</html>

