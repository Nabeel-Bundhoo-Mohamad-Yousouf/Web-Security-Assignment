<?php 
session_start();

//Holds state of whether or not user is logged in
$is_logged_in = isset($_SESSION['logged_in']);

//Initiates custom execption handler
set_exception_handler("custom_exception_handler");

error_reporting(E_ALL);                         //logs all the errors
ini_set("display_errors", 0);                   //hides errors from display
ini_set("log_errors", 1);                       //enables logging
ini_set("error_log", __DIR__. "/php_error.log");

class CustomException extends Exception{
    public function error_message () {
        return "Error: {$this->getMessage()} in {$this->getFile()} on line {$this->getLine()} \n | Trace {$this->getTraceAsString()} \n";
    }
}
//Handles exception; logs exceptions insttead of printing on browser.
function custom_exception_handler($exception) {
    if (method_exists($exception, "error_message")) {
        $msg = $exception->error_message();
    } else {
        $msg = "Error: {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()} \n | Trace {$exception->getTraceAsString()} \n";
    }

    error_log($msg. "\n",3, __DIR__ ."/book_details_php_errors.log");
    echo "An unexpected error occured. Please try again.";
}


// Reads search and genre values submitted (via GET) when book is clicked
if ($_SERVER ["REQUEST_METHOD"] == "GET") {
    $genre = htmlspecialchars($_GET["genre"]);
    $search_title = htmlspecialchars($_GET["title"]);

    if (!empty($search_title)) {
        try{
            // Include the database connection file
            require_once "includes/db_conn.php"; 
            $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $statement_prepd = $db_conn->prepare("CALL book_preview_search(?)");
            $statement_prepd -> execute([$search_title]);
            $result = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
            $statement_prepd->closeCursor();

        } catch (PDOException $e) {
        throw new CustomException($e->getMessage());
        }
    }
}
if (!empty($result)){
    foreach ($result as $row) {
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $row["title"]. " by " .$row["author"]. " | Bibliohaha"?></title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name = "description" content="<?php echo $row["title"]. " by " .$row["author"]?> - available to buy & rent at affordable prices. Fast delivery and best customer reviews.">
    <meta name = "robots" content="index, follow">

    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
    
</head>
<body>
    <!--Include header.html/header.php-->
    <?php include("includes/header.php");?>

    <!--Book Preview-->
    <div class="container-fluid preview_container">
        <p class="preview-title">Preview</p>
        <div class="row justify-content-center preview_grid">

            <!-- Image + Buttons -->
            <div class="col-lg-5 col-md-6 col-sm-12 flex-column align-items-center ">

                <img class="img-responsive rounded preview-card__image" 
                    src="images/<?php echo $row["img_url"] ?>" alt="Image of <?php echo $row["title"]?>">

                <!--Display buy and rental fees-->
                <div class="btn-tabs-container pt-3 justify-content-start">
                    <div class="btn-tabs" id="tab-btn" role="tablist">

                        <!-- Buy Tab -->
                        <button type="button" class="tab_btn active" id="buy_tab"
                            data-bs-toggle="tab" data-bs-target="#buy" role="tab"
                            aria-controls="buy" aria-selected="true">
                            <span style=" font-weight: var(--font-weight-normal);">Buy </span>
                            &nbsp;
                            <span class="book-card__price"> <?php echo " Rs ". $row["price"]?> </span>
                        </button>

                        <!-- Rent Tab -->
                        <button type="button" class="tab_btn" id="borrow_tab"
                            data-bs-toggle="tab" data-bs-target="#borrow" role="tab"
                            aria-controls="borrow" aria-selected="false">
                            <span style=" font-weight: var(--font-weight-normal);">Rent </span>
                            &nbsp;
                            <span class="book-card__price_borrow"> <?php echo " Rs ". $row["rental_fee"]?> </span>
                        </button>
                    </div>
                </div>

                <div class="tab-content" id="tab_content">
                    <!-- Buy Content -->
                    <div class="tab-pane fade show active p-3" id="buy" role="tabpanel"
                        aria-labelledby="buy_tab">

                        <!--Submits book to cart to buy only if user is logged in-->
                            <form action="shopcart.php" method="post">
                                <input type="hidden" name="id" value="<?php echo $row["book_ID"]?>">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($row["title"])?>">
                                <input type="hidden" name="price" value="<?php echo $row["price"]?>">
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($row["img_url"])?>">
                                <input type="hidden" name="author" value="<?php echo htmlspecialchars($row["author"])?>">
                                <input type="hidden" name="qty" value="1">
                                
                                <?php if ($is_logged_in) { ?>
                                <button type="submit" class="button add_to_cart_btn w-70 align-center">
                                    + Add to cart
                                </button>

                                <?php } else { ?>
                                    <a href="login.php" class="btn add_to_cart_btn">
                                        + Add to cart
                                    </a>
                                <?php }
                                ?>
                            </form>
                    </div>

                    <!-- Rent Content -->
                    <div class="tab-pane fade p-3" id="borrow" role="tabpanel"
                        aria-labelledby="borrow_tab">

                        <!--Submits book to cart to rent only if user is logged in-->
                            <form action="shopcart.php" method="post">
                                <input type="hidden" name="id" value="<?php echo $row["book_ID"]?>">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($row["title"])?>">
                                <input type="hidden" name="price" value="<?php echo $row["rental_fee"]?>">
                                <input type="hidden" name="image" value="<?php echo htmlspecialchars($row["img_url"])?>">
                                <input type="hidden" name="author" value="<?php echo htmlspecialchars($row["author"])?>">
                                <input type="hidden" name="qty" value="1">

                                <?php if ($is_logged_in) { ?>
                                <button type="submit" class="button add_to_cart_btn w-70 align-center">
                                    + Add to cart
                                </button>

                                <?php } else { ?>
                                    <a href="login.php" class="btn add_to_cart_btn">
                                        + Add to cart
                                    </a>
                                <?php }
                                ?>
                            </form>
                    </div>
                </div>
            </div>

            <!-- Book Info -->
            <div class="col-lg-6 col-md-6 col-sm-12">
                <p class="preview-card__title"><?php echo $row["title"]?></p>
                <p class="preview-card__author"><?php echo "By ". $row["author"]?></p>

                <!--Star ratings-->
                <div class="star-rating">
                    <?php
                        $star_rating= floor($row["avg_rating"]);
                        for ($i=1; $i<= 5; $i++){
                            if ($i <= $star_rating)
                                $icon = "&#9733;";
                            else
                                $icon = "&#9734;";
                    ?>
                        <span> <?php echo $icon ?> </span>
                    <?php }
                    ?>
                    <span style="color: var(--text-primary);"> (<?php echo $row["rating_num"]?>)</span>
                </div>

                <p class="preview-card__description">
                    <?php echo $row["book_description"]?>
                </p>
            </div>
        </div>
    </div>

    <!--Customer Reviews-->
    <div class="container-fluid preview_container align-items-center pt-3 py-5">
        <hr class="footer_divider">

        <!--Reviews Header-->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0 preview-title">Customer Reviews</h4>

            <!-- Write Review btn -->
            <a href="review.php" class="btn primary_btn review_btn"> Write a review </a> 
        </div>

        <!--User review cards-->
        <div id="carousel_controls" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner pt-3 pb-3">

                <!--Review Card-->
                <div class="carousel-item active">
                     <div class="review-card mx-auto">
                        <!--User star ratings-->
                        <div class="star-rating">
                            <?php
                                $star_rating= floor($row["avg_rating"]);
                                for ($i=1; $i<= 5; $i++){
                                    if ($i <= $star_rating)
                                        $icon = "&#9733;";
                                    else
                                        $icon = "&#9734;";
                            ?>
                                <span> <?php echo $icon ?> </span>
                            <?php }
                            ?>
                        </div>

                        <!--Review card body-->
                        <p style="font-weight: 600; margin-bottom: 5px; color: #777;">
                            <?php echo $row["customer_name"]. " • "  .$row["date"] ?>
                        </p>
                        <p class="review-title"><?php echo $row["review"] ?> </p>
                        <p class="review-text truncate_multi_line">
                            <?php echo $row["review_description"] ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <?php
    }        
            }
            ?> 
            <!--Carousel Controls-->
            <button type="btn" class="carousel-control-prev" data-bs-target="#carousel_controls" role="btn" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </btn>
            <button type="btn" class="carousel-control-next" data-bs-target="#carousel_controls" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
            </btn>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous" defer></script>

    <!--Include footer.html-->
    <?php include("includes/footer.html")?>
</body>
</html>
