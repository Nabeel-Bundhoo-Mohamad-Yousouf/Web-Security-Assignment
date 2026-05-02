<?php
session_start();
require_once "includes/db_connect.php"; 

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    echo '<div style="text-align:center; margin:50px auto; max-width:400px; font-family:Segoe UI, sans-serif;">
    <h3 style="color:#e74c3c; margin-bottom:20px;">You must be logged in to write a review.</h3>
    <a href="login.php" style="display:inline-block; padding:10px 20px; background:#3498db; color:#fff; text-decoration:none; border-radius:5px;">Login Here</a>
    <a href="index.php" style="display:inline-block; padding:10px 20px; background:#2ecc71; color:#fff; text-decoration:none; border-radius:5px;">Go to Home</a>
    </div>';
   exit();
}

// Fetch customer_ID
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
    echo "User data error. Please log in again.";
    exit(); 
}

$books = [];

// Fetch Unreviewed Books
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
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Submit Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
    </style> 
</head>
<body>
<div class="review-container">
    <h3 class="mb-3">Submit a Review</h3>

    <div id="messageBox" class="mt-3 mb-3" style="display: none;"></div>

    <div id="formContainer">
        <?php if (!empty($books)): ?>
            <form id="reviewForm">
                <div class="mb-3">
                    <label>Name:</label>
                    <input type="text" class="form-control" name="txt_name" pattern="[A-Za-z' ]{3,30}" title="Name must be 3-30 letters or spaces" required> 
                </div>

                <div class="mb-3">
                    <label>Rating:</label>
                    <div class="stars">
                        <input type="radio" id="star5" name="txt_rating" value="5">
                        <label for="star5" class="fa fa-star"></label>

                        <input type="radio" id="star4" name="txt_rating" value="4">
                        <label for="star4" class="fa fa-star"></label>

                        <input type="radio" id="star3" name="txt_rating" value="3">
                        <label for="star3" class="fa fa-star"></label>

                        <input type="radio" id="star2" name="txt_rating" value="2">
                        <label for="star2" class="fa fa-star"></label>

                        <input type="radio" id="star1" name="txt_rating" value="1">
                        <label for="star1" class="fa fa-star"></label>
                    </div>
                </div>

                <div class="mb-3">
                    <label>Select Book:</label>
                    <select class="form-select" name="txt_book" required>
                    <option value="">--Select--</option>
                    <?php foreach ($books as $book): 
                        $val = $book['type'] . "|" . $book['record_ID'] . "|" . $book['book_ID'] ; 
                    ?>
                    <option value="<?php echo htmlspecialchars($val); ?>">
                        <?php echo htmlspecialchars($book['description']) . " (" . htmlspecialchars($book['type']) . ")"; ?>
                    </option>
                    <?php endforeach; ?>
                    </select> 
                </div>

                <div class="mb-3">
                    <label>Comment:</label>
                    <textarea class="form-control" name="txt_comment" rows="4" minlength="5" maxlength="500"></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary" style="width: 48%;">Submit</button>
                    <button type="reset" class="btn btn-secondary" style="width: 48%;">Reset</button>
                </div>
            </form>

        <?php else: ?>
            <div class="text-center mt-4">
                <h5 class="text-success mb-3">You have no books to review. Thank you!</h5>
                <a href="index.php" class="btn btn-primary">Return to Home Page</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#reviewForm').on('submit', function(e) {
        e.preventDefault(); 
        
        $('#messageBox').removeClass().hide().html('');

        let selectedRating = $('input[name="txt_rating"]:checked').val();
        if (!selectedRating) {
            $('#messageBox').addClass('alert alert-danger').html('Please select a star rating before submitting.').fadeIn();
            return; 
        }

        // Grab the actual text of the selected book from the dropdown
        let selectedBookText = $('select[name="txt_book"] option:selected').text().trim();
        let commentText = $('textarea[name="txt_comment"]').val();

        // Build the JSON Payload
        const reviewData = {
            name: $('input[name="txt_name"]').val(),
            rating: parseInt($('input[name="txt_rating"]:checked').val(), 10), 
            book: $('select[name="txt_book"]').val(),
            comment: commentText
        };

        // AJAX Request
        $.ajax({
            url: 'process_review_api.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(reviewData),
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    $('#formContainer').slideUp();
                    
                    // Build the success HTML dynamically using the variables
                    let successHtml = `
                        <h5 class="text-success text-center mb-3">Review submitted successfully!</h5>
                        <div class="p-3 bg-light border rounded">
                            <p><strong>Book:</strong> ${selectedBookText}</p>
                            <p><strong>Name:</strong> ${response.data.name}</p>
                            <p><strong>Rating:</strong> ${response.data.rating} / 5</p>
                            <p><strong>Comment:</strong> ${commentText ? commentText : '<em>No comment provided</em>'}</p>
                            <a href="index.php" class="btn btn-primary mt-3">Return to Home Page</a>
                        </div>
                    `;
                    $('#messageBox').html(successHtml).fadeIn();
                }
            },
            error: function(xhr) {
                let res = xhr.responseJSON;
                let errorHtml = '<strong>Error submitting review:</strong><br><ul>';
                
                if (res && res.errors) {
                    $.each(res.errors, function(index, err) {
                        errorHtml += '<li>' + err + '</li>';
                    });
                } else {
                    errorHtml += '<li>A server error occurred. Please try again.</li>';
                }
                errorHtml += '</ul>';
                
                $('#messageBox').addClass('alert alert-danger').html(errorHtml).fadeIn();
            }
        });
    });
});
</script>
</body>
</html>
