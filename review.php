<?php
session_start();
require_once "includes/db_connect.php"; 

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
//if not message is displayed and user can to login page or home page
    echo '<div style="text-align:center; margin:50px auto; max-width:400px; font-family:Segoe UI, sans-serif;">
    <h3 style="color:#e74c3c; margin-bottom:20px;">You must be logged in to write a review.</h3>
    <a href="login.php" style="display:inline-block; padding:10px 20px; background:#3498db; color:#fff; text-decoration:none; border-radius:5px;">Login Here</a>
    <a href="index.php" style="display:inline-block; padding:10px 20px; background:#2ecc71; color:#fff; text-decoration:none; border-radius:5px;">Go to Home</a>
    </div>';
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
    $data = strip_tags($data);
    return htmlspecialchars($data);
}

// Regex pattern
$namePattern = "/^[A-Za-z' ]{3,30}$/";

$submitted_review = null;
$books = [];
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

        // Store submitted review data to show
        $submitted_review = [
            'book_desc' => '',
            'name' => $name,
            'rating' => $rating,
            'comment' => $comment
        ];

        // Find the book description from $books array
        foreach ($books as $b) {
            if ($b['book_ID'] == $book_ID) {
                $submitted_review['book_desc'] = $b['description'];
                break;
            }
        }

        // Hide the form and show success message
        $showForm = false;
        $Msg = "Review submitted successfully!";

    } catch (Exception $e) {
        $_SESSION['Msg'] = "Failed to submit review. Please try again. (Database Error)";
        header("Location: " . $_SERVER['PHP_SELF']); 
        exit();
    }
}


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
    
    <?php if ($submitted_review !== null): ?>
        <div class="mt-4 p-3 bg-light border rounded">
            <p><strong>Book:</strong> <?php echo htmlspecialchars($submitted_review['book_desc']); ?></p>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($submitted_review['name']); ?></p>
            <p><strong>Rating:</strong> <?php echo $submitted_review['rating']; ?> / 5</p>
            <?php if (!empty($submitted_review['comment'])): ?>
                <p><strong>Comment:</strong> <?php echo htmlspecialchars($submitted_review['comment']); ?></p>
            <?php endif; ?>
            <a href="index.php" class="btn btn-primary mt-2">Return to Home Page</a>
        </div>
    <?php endif; ?>

    <?php if ($showForm && !empty($errors)): ?>
        <p class="text-danger mb-3">* required field</p>
    <?php endif; ?>
    
    <?php if ($showForm): ?>
        <?php if (!empty($books)): ?>
        <form method="post">
            <div class="mb-3">
                <label>Name:</label>
                <input type="text" class="form-control" name="txt_name" value="<?php echo $old_inputs['name'] ?? ''; ?>" pattern="[A-Za-z' ]{3,30}" title="Name must be 3-30 letters or spaces" required> 
                <div class="text-danger"><?php echo $errors['nameErr'] ?? ''; ?></div>
            </div>

            <div class="mb-3">
                <label>Rating:</label>
                <div class="stars">
                    <input type="radio" id="star5" name="txt_rating" value="5" required <?php if (($old_inputs['rating'] ?? '') == '5') echo 'checked'; ?>>
                    <label for="star5" class="fa fa-star"></label>

                    <input type="radio" id="star4" name="txt_rating" value="4" required<?php if (($old_inputs['rating'] ?? '') == '4') echo 'checked'; ?>>
                    <label for="star4" class="fa fa-star"></label>

                    <input type="radio" id="star3" name="txt_rating" value="3" required<?php if (($old_inputs['rating'] ?? '') == '3') echo 'checked'; ?>>
                    <label for="star3" class="fa fa-star"></label>

                    <input type="radio" id="star2" name="txt_rating" value="2" required<?php if (($old_inputs['rating'] ?? '') == '2') echo 'checked'; ?>>
                    <label for="star2" class="fa fa-star"></label>

                    <input type="radio" id="star1" name="txt_rating" value="1" required<?php if (($old_inputs['rating'] ?? '') == '1') echo 'checked'; ?>>
                    <label for="star1" class="fa fa-star"></label>
                </div>
                <div class="text-danger"><?php echo $errors['ratingErr'] ?? ''; ?></div>
            </div>

            <div class="mb-3">
                <label>Select Book:</label>
                <select class="form-select" name="txt_book" required>
                <option value="">--Select--</option>
                <?php foreach ($books as $book):
                    $val = $book['type'] . "|" . $book['record_ID'] . "|" . $book['book_ID'] ; 
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
                <textarea class="form-control" name="txt_comment" rows="4" minlength="5" maxlength="500"><?php echo $old_inputs['comment'] ?? ''; ?></textarea>
            <div class="text-danger"><?php echo $errors['commentErr'] ?? ''; ?></div>
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Submit</button>
                <button type="reset" class="btn btn-secondary">Reset</button>
            </div>
        </form>
       <?php else: ?>
            <div class="text-center mt-4">
                <h5 class="text-success mb-3">You have no books to review. Thank you!</h5>
                <a href="index.php" class="btn btn-primary">Return to Home Page</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>
</body>
</html>
