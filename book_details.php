<?php 
session_start();

// Include the database connection file
require_once "includes/../db_connect.php"; 

// Reads search and genre values submitted (via GET) when book is clicked
if ($_SERVER ["REQUEST_METHOD"] == "GET") {
    $search = htmlspecialchars($_GET["id"]);
    $genre = $_GET["genre"];

    //Save genre in cookie for 10 days
    setcookie("last_genre", $genre, time()+ (10*24*60*60), "/");

    if (!empty($search)) {

/*
DELIMITER $$

CREATE PROCEDURE book_preview_search (IN search_ID INT)
BEGIN
    -- First query: Book details with aggregate ratings
    SELECT 
        b.*, 
        COUNT(r.rating) AS rating_num, 
        AVG(r.rating) AS avg_rating
    FROM book AS b 
    LEFT JOIN review AS r ON b.book_ID = r.book_ID 
    WHERE b.book_ID = search_ID 
    GROUP BY b.book_ID;
    
    -- Second query: Individual reviews for the book
    SELECT 
        r.review, 
        r.description, 
        r.date, 
        c.customer_name
    FROM review AS r
    JOIN customer AS c ON r.customer_ID = c.customer_ID
    WHERE r.book_ID = search_ID;
END $$

DELIMITER ;
 */

        $statement_prepd = $conn->prepare("CALL book_preview_search(?)");
        $statement_prepd -> execute([$search]);
        $result = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
        $statement_prepd->closeCursor();
    }
}
if (!empty($result)){
    foreach ($result as $row) {

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $row["title"]. " by " .$row["author"]. " | bibliohaha"?></title>
<!--https://gist.github.com/david-bakin/255660af79c386460cdf0ee6a2a96291?permalink_comment_id=3540313 -->
<!--https://nxdigitalagency.com/en/blog-en/seo-optimisation-of-product-cards-in-an-online-store-step-by-step-guide-->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name = "keywords" content="">
    <meta name = "description" content="Buy & Rent <?php echo $row["title"]. " by " .$row["author"]?> at the best and affordable prices. Read customer reviews.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name = "robots" content="index, archive, follow">
    <link rel="stylesheet" href="css/external_style.css"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">

</head>
<body>
    <!--Include header.html/header.php-->
    <?php include("includes/../header.php");?>

    <!--Book Preview-->
    <div class="container-fluid preview_container">
        <p class="preview-title">Preview</p>
        <div class="row justify-content-center preview_grid">

            <!-- Image + Buttons -->
            <div class="col-lg-5 col-md-6 col-sm-12 flex-column align-items-center ">

                <img class=" card-img-top img-responsive rounded book-card__image" 
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
                        <form action="add_to_cart.php" method="post">
                            <input type="hidden" name="title" value="<?php echo $row["title"]?>">
                            <button type="submit" name="add_to_cart" class="button add_to_cart_btn w-70 align-center">
                                + Add to cart
                            </button>
                        </form>
                    </div>

                    <!-- Rent Content -->
                    <div class="tab-pane fade p-3" id="borrow" role="tabpanel"
                        aria-labelledby="borrow_tab">
                        <form action="add_to_cart.php" method="post">
                            <input type="hidden" name="title" value="<?php echo $row["title"]?>">
                            <button type="submit" name="add_to_cart" class="button add_to_cart_btn w-70">
                                + Add to cart
                            </button>
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
                    <?php echo $row["description"]?>
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
            <a href="Bstp_review.php" class="btn primary_btn review_btn"> Write a review </a> 
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

    <!--Include footer.html-->
    <?php include("includes/../footer.html")?>
</body>
</html>

