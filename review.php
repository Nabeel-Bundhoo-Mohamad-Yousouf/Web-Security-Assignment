<?php
session_start();
require_once "includes/db_connect.php"; 

/*
// Stored Procedures 

// 1. Procedure for Customer ID Fetch (GetCustomerID)
DELIMITER //
CREATE PROCEDURE GetCustomerID(IN username VARCHAR(50))
BEGIN
    SELECT c.customer_ID
    FROM Customer c
    JOIN Users u ON c.user_ID = u.user_ID
    WHERE u.username = username;
END //
DELIMITER ;

// 2. Procedure for Unreviewed Books Fetch (GetUnreviewedBooks)
DELIMITER //
CREATE PROCEDURE GetUnreviewedBooks(IN customer INT)
BEGIN
    -- Rentals
    SELECT 'rental' AS type, r.Rent_ID AS record_ID, b.book_ID, b.description
    FROM Rental r
    JOIN Book b ON r.Book_ID = b.book_ID
    WHERE r.customer_ID = customer AND r.reviewed = 0
    UNION ALL

    -- Purchases
    SELECT 'purchase' AS type, p.purchase_ID AS record_ID, b.book_ID, b.description
    FROM Purchase p
    JOIN Book b ON p.Book_ID = b.book_ID
    WHERE p.customer_ID = customer AND p.reviewed = 0;
END //
DELIMITER ;

// 3. Procedure for Review Submission (AddReviewAndMarkReviewed)
DELIMITER //
CREATE PROCEDURE AddReviewAndMarkReviewed(
    IN p_customer_ID INT,
    IN p_book_ID INT,
    IN p_rating INT,
    IN p_review TEXT,
    IN p_type VARCHAR(10),
    IN p_record_ID INT
)
BEGIN
    START TRANSACTION;
    
-- Insert review
    INSERT INTO Review(customer_ID, book_ID, rating, review, date)
    VALUES(p_customer_ID, p_book_ID, p_rating, p_review, CURDATE());
    
-- Mark rented books as reviewed 
    UPDATE Rental
    SET reviewed = 1
    WHERE Rent_ID = p_record_ID
    AND p_type = 'rental'; 

-- Mark Purchased book as reviwed
    UPDATE Purchase
    SET reviewed = 1
    WHERE purchase_ID = p_record_ID
    AND p_type = 'purchase';

    COMMIT;
END //
DELIMITER ;
*/

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Session message
$Msg = $_SESSION['Msg'] ?? "";
unset($_SESSION['Msg']);
$errors = $_SESSION['errors'] ?? [];
$old_inputs = $_SESSION['old_inputs'] ?? [];
unset($_SESSION['errors']);
unset($_SESSION['old_inputs']);

// Fetch customer_ID (GetCustomerID)
try {
    $stmt = $conn->prepare("CALL GetCustomerID(?)");
    $stmt->execute([$_SESSION['username']]);
    $customer_ID = $stmt->fetchColumn();
    $stmt->closeCursor(); 

} catch (PDOException $e) {
    error_log("DB Error fetching customer ID: " . $e->getMessage());
    $customer_ID = null;
}

if (!$customer_ID) {
    $_SESSION['Msg'] = "User data error. Please log in again.";
    header("Location: index.php");
    exit(); 
}

// Input sanitizer function
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

// Regex pattern
$namePattern = "/^[A-Za-z' ]{3,30}$/";

// --- Form Submission Handling 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];
    $old_inputs = [];
    
    // Validation
    if (empty($_POST['txt_name'])) { $errors['nameErr'] = "Name is required"; } 
    else { 
        $name = test_input($_POST['txt_name']);
        if (!preg_match($namePattern, $name)) { $errors['nameErr'] = "Name should only contain letters and spaces"; }
        else { $old_inputs['name'] = $name; }
    }
    if (empty($_POST['txt_rating'])) { $errors['ratingErr'] = "Rating is required"; } 
    else {
        $rating = (int)$_POST['txt_rating'];
        if ($rating < 1 || $rating > 5) { $errors['ratingErr'] = "Rating must be between 1 and 5"; }
        else { $old_inputs['rating'] = $rating; }
    }
    $comment = '';
    if (!empty($_POST['txt_comment'])) {
        $comment = test_input($_POST['txt_comment']);
        if (strlen($comment) > 500) { $errors['commentErr'] = "Comment cannot exceed 500 characters"; }
        else { $old_inputs['comment'] = $comment; }
    }
    if (empty($_POST['txt_book'])) { $errors['bookErr'] = "Please select a book"; } 
    else { $old_inputs['book'] = $_POST['txt_book']; }

    // Redirect in case of validation failure
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old_inputs'] = $old_inputs;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    // Database Processing
    list($type, $record_ID, $book_ID) = explode("|", $_POST['txt_book']);

    try {
        // Calling the Stored Procedures
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
            ':p_rating'      => $rating,
            ':p_review'      => $comment,
            ':p_type'        => $type,
            ':p_record_ID'   => $record_ID
        ]);
        $stmtCall->closeCursor();

        $_SESSION['Msg'] = "Review submitted successfully!";
        header("Location: index.php"); 
        exit(); 

    } catch (Exception $e) {
        $_SESSION['Msg'] = "Failed to submit review. Please try again. (Database Error)";
        header("Location: " . $_SERVER['PHP_SELF']); 
        exit();
    }
}

$books = [];

if ($customer_ID) {
    try {
        $stmtItems = $conn->prepare("CALL GetUnreviewedBooks(?)");
        $stmtItems->execute([$customer_ID]);

        $unreviewed_items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        $stmtItems->closeCursor(); 

        foreach ($unreviewed_items as $item) {
            $books[] = [
                'type' => $item['type'],
                'record_ID' => $item['record_ID'], 
                'book_ID' => $item['book_ID'], 
                'description' => $item['description']
            ];
        }
        
    } catch (PDOException $e) {
        error_log("DB Error fetching unreviewed items: " . $e->getMessage());
        $Msg = "Could not load book list.";
    }
}

$showForm = (strpos($Msg, 'successfully') === false);
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
    
    <?php if ($Msg != ""): ?>
        <h5 class="mt-3 text-center <?php echo strpos($Msg, 'Failed') !== false || strpos($Msg, 'error') !== false ? 'text-danger' : 'text-success'; ?>">
            <?php echo $Msg; ?>
        </h5>
    <?php endif; ?>

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
                    $val = $book['type'] . "|" . $book['record_ID'] . "|" . $book['book_ID']; 
                ?>
                <option value="<?php echo $val; ?>" <?php if (($old_inputs['book'] ?? '') == $val) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($book['description']) . " (" . ($book['type']) . ")"; ?>
                </option>
                <?php endforeach; ?>
                </select> 
                <div class="text-danger"><?php echo $errors['bookErr'] ?? ''; ?></div>
            </div>
            <div class="mb-3">
                <label>Comment:</label>
                <textarea class="form-control" name="txt_comment" rows="4"><?php echo $old_inputs['comment'] ?? ''; ?></textarea>
            <div class="text-danger"><?php echo $errors['commentErr'] ?? ''; ?></div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
        </form>
        <?php else: ?>
            <h5 class="text-success text-center mt-4">You have no books to review. Thank you!</h5>
        <?php endif; ?>
    <?php endif; ?>

</div>
</body>
</html>
