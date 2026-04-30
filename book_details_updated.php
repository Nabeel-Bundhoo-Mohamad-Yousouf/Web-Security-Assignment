<?php 
session_start();

//Holds state of whether or not user is logged in
$is_logged_in = isset($_SESSION['logged_in']);

use Opis\JsonSchema\{
   		 Validator, ValidationResult, ValidationError, Schema
};

//Load content from load_book_details.php
//url = 'http://localhost/bibliohaha/load_book_details.php';
url = 'http://localhost/main/load_book_details.php';

$json = file_get_contents($url);
//echo $json;
	
$response = json_decode($json, true);

$schema = Schema::fromJsonString(file_get_contents('JSONSchema/load_book_detailsDefinition.json'));
$validator = new Validator();

$result = $validator->schemaValidation($response, $schema);

if (!$result->isValid()) {

    $error = $result->getErrors();
    echo '$data is invalid', PHP_EOL;
    
    foreach ($error as $key => $value) {
        # code...
        echo "Error: ", $value->keyword(), PHP_EOL;
        echo json_encode($value->keywordArgs(), JSON_PRETTY_PRINT), PHP_EOL;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php $book = $response['data']['book']; ?>
    <title><?php echo "{$book['title']} by {$book['author']} | Bibliohaha"?></title>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name = "description" content="<?php echo "{$book['title']} by {$book['author']}" ?> - available to buy & rent at affordable prices. Fast delivery and best customer reviews.">
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
    <?php include "includes/header.php"; ?>

    <!--Book Preview-->
    <div class="container-fluid preview_container">
        <p class="preview-title">Preview</p>
        <div class="row justify-content-center preview_grid">

            <!-- Image + Buttons -->
            <div class="col-lg-5 col-md-6 col-sm-12 flex-column align-items-center ">

                <img class="img-responsive rounded preview-card__image" 
                    src="images/<?php echo $book["img_url"] ?>" alt="<?php echo $book["title"]?>">

                <!--Display buy and rental fees-->
                <div class="btn-tabs-container pt-3 justify-content-start">
                    <div class="btn-tabs" id="tab-btn" role="tablist">

                        <!-- Buy Tab -->
                        <button type="button" class="tab_btn active" id="buy_tab"
                            data-bs-toggle="tab" data-bs-target="#buy" role="tab"
                            aria-controls="buy" aria-selected="true">
                            <span style=" font-weight: var(--font-weight-normal);">Buy </span>
                            &nbsp;
                            <span class="book-card__price"> <?php echo " Rs ". $book["price"]?> </span>
                        </button>

                        <!-- Rent Tab -->
                        <button type="button" class="tab_btn" id="borrow_tab"
                            data-bs-toggle="tab" data-bs-target="#borrow" role="tab"
                            aria-controls="borrow" aria-selected="false">
                            <span style=" font-weight: var(--font-weight-normal);">Rent </span>
                            &nbsp;
                            <span class="book-card__price_borrow"> <?php echo " Rs ". $book["rental_fee"]?> </span>
                        </button>
                    </div>
                </div>

                <div class="tab-content" id="tab_content">
                    <!-- Buy Content -->
                    <div class="tab-pane fade show active p-3" id="buy" role="tabpanel"
                        aria-labelledby="buy_tab">

                        <!--Submits book to cart to buy only if user is logged in-->
                        <?php if ($is_logged_in) { ?>
                            <form action="shopcart.php" method="post">
                                <input type="hidden" name="id" value="<?php echo $book["book_ID"]?>">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($book["title"])?>">
                                <input type="hidden" name="price" value="<?php echo $book["price"]?>">
                                <input type="hidden" name="image" value="<?php echo $book["img_url"]?>">
                                <input type="hidden" name="author" value="<?php echo htmlspecialchars($book["author"])?>">
                                <input type="hidden" name="qty" value="1" min="1">
                                
                                <button type="submit" class="button add_to_cart_btn w-70 align-center">
                                    + Add to cart
                                </button>
                            </form>
                        <?php } else { ?>
                        <p>Please <a href="login.php">log in</a> to add items to your cart.</p>
                        <?php }
                        ?>
                    </div>

                    <!-- Rent Content -->
                    <div class="tab-pane fade p-3" id="borrow" role="tabpanel"
                        aria-labelledby="borrow_tab">

                        <!--Submits book to cart to rent only if user is logged in-->
                        <?php if ($is_logged_in) { ?>
                            <form action="shopcart.php" method="post">
                                <input type="hidden" name="id" value="<?php echo $book["book_ID"]?>">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($book["title"])?>">
                                <input type="hidden" name="price" value="<?php echo $book["rental_fee"]?>">
                                <input type="hidden" name="image" value="<?php echo $book["img_url"]?>">
                                <input type="hidden" name="author" value="<?php echo htmlspecialchars($book["author"])?>">
                                <input type="hidden" name="qty" value="1" min="1">

                                <button type="submit" class="button add_to_cart_btn w-70">
                                    + Add to cart
                                </button>
                            </form>
                        <?php } else { ?>
                        <p>Please <a href="login.php">log in</a> to add items to your cart.</p>
                        <?php }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Book Info -->
            <div class="col-lg-6 col-md-6 col-sm-12">
                <p class="preview-card__title"><?php echo $book["title"]?></p>
                <p class="preview-card__author"><?php echo "By ". $book["author"]?></p>

                <!--Star ratings-->
                <div class="star-rating">
                    <?php
                        $star_rating= floor($book["avg_rating"] ?? 0);
                        for ($i=1; $i<= 5; $i++){
                            $icon = ($i <= $star_rating) ? "★" : "☆";
                            $stars.= "<span class='star'> {$icon} </span>";
                        }
                    ?>
                    <span> <?php echo $stars ?> </span>
                    <span style="color: var(--text-primary);"> (<?php echo $book["rating_num"]?>)</span>
                </div>

                <p class="preview-card__description">
                    <?php echo $book["book_description"]?>
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
                <?php
                    foreach ($response['data']['reviews'] as $review) {
                ?>

                <!--Review Card-->
                <div class="carousel-item active">
                     <div class="review-card mx-auto">
                        <!--User star ratings-->
                        <div class="star-rating">
                            <?php
                                $star_rating= floor($review["rating"] ?? 0);
                                for ($i=1; $i<= 5; $i++){
                                    $icon = ($i <= $star_rating) ? "★" : "☆";
                                    $stars.= "<span class='star'> {$icon} </span>";
                                }
                            ?>
                            <span> <?php echo $stars ?> </span>
                            
                        </div>

                        <!--Review card body-->
                        <p style="font-weight: 600; margin-bottom: 5px; color: #777;">
                            <?php echo "customerName • {$review['date']}" ?>
                        </p>
                        <p class="review-title"><?php echo $review["review"] ?> </p>
                        <p class="review-text truncate_multi_line">
                            <?php echo $review["review_description"] ?>
                        </p>
                    </div>
                </div>
                <?php
                    }
                ?>
            </div>

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
    <?php include "includes/footer.html"?>
</body>
</html>