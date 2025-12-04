<?php
session_start();

//Holds state of whether or not user is logged in
$is_logged_in = isset($_SESSION['logged_in']);

//Initiates custom execption handler
set_exception_handler("custom_exception_handler");

//Variable initialisation
$exception = $last_genre = $search = $genre = $filter = "";
$results=[];

error_reporting(E_ALL);                         //logs all the errors
ini_set("display_errors", 0);                   //hides errors from display
ini_set("log_errors", 1);                       //enables logging
ini_set("error_log", __DIR__. "/php_error.log");

class CustomException extends Exception{
    public function error_message () {
        return "Error: {$this->getMessage()} in {$this->getFile()} on line 
        {$this->getLine()} \n | Trace {$this->getTraceAsString()} \n";
    }
}

//Handles exception; logs exceptions instead of printing on browser.
function custom_exception_handler($exception) {
    if (method_exists($exception, "error_message")) {
        $msg = $exception->error_message();
    } else {
        $msg = "Error: {$exception->getMessage()} in {$exception->getFile()} on line 
        {$exception->getLine()} \n | Trace {$exception->getTraceAsString()} \n";
    }

    error_log($msg. "\n",3, __DIR__ ."/index_php_errors.log");
    echo "An unexpected error occured. Please try again.";
}

//Function to sanitise and clean input
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

//Include the database connection file
require_once "includes/db_conn.php";
$db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    //Handles search form request
    if (isset ($_GET["search"]) && !empty($_GET["search"])) {
        //Read data from GET (at direct load or redirect)
        $search = clean_input($_GET["search"]);
        $genre = clean_input($_GET["genre"] ?? "");
        $filter = clean_input($_GET["filter"] ?? "title");

        $statement_prepd = $db_conn->prepare("CALL search_books(?, ?, ?)");
        $statement_prepd -> execute([$search, $genre, $filter]);

        //Retrieve results and frees connection
        $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
        $statement_prepd->closeCursor();
    
    } //Handles links in footer
    elseif (isset($_GET["referer"]) && $_GET["referer"] === "footer") {
    $genre = clean_input($_GET["genre"] ?? "");

    $statement_prepd = $db_conn->prepare("CALL footer_filters(?)");
    $statement_prepd -> execute([$genre]);

    $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
    $statement_prepd->closeCursor();

    } 
    else {
        //Handles direct homepage access
        $statement_prepd = $db_conn->query("SELECT * FROM view_books");
        $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
        $statement_prepd->closeCursor();
    }
} catch (PDOException $e) {
    throw new CustomException($e->getMessage());
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Buy & Rent Books Online | Bibliohaha</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name = "description" content="Buy & Rent books online at affordable prices. Fast delivery and best customer experience.">
    <meta name = "robots" content="index, follow">

    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 

</head>

<body>
    <!--Include header.php-->
    <?php 
    $activemenu = "home";
    include("includes/header.php");
    ?>
    
    <!--hero section-->
    <div class="container-fluid" id= "hero-section">
        <h2 id= "hero-section__title">Welcome to Bibliohaha!</h2>
        <p id= "hero-section__description">Discover your next favorite book from our curated collection</p>
    </div>
    
    <!--search form-->
    <div class="container-fluid mt-3">
        <form action="<?php echo $_SERVER["PHP_SELF"];?>" method="get">
            <div class="row g-2">
                <!--Search Input-->
                <div class="col-12 col-md-6">
                    <div class="search">
                        <button type="submit" name="search_form"><i class="fa fa-search"></i></button>
                        <input type="text" name="search" placeholder="Search books or authors.." 
                        style="padding-left: 0%;">
                    </div>
                </div>
                <!--Search Genre-->
                <div class="col-6 col-md-3">
                    <select class = "search-input dropdown" id="genre" name="genre">
                    <option value="">All</option>
                    <option value="fiction">Fiction</option>
                    <option value="non-fiction">Non-fiction</option>
                    <option value="sci-fi">Science fiction</option>
                    <option value="mystery">Mystery</option>
                    <option value="romance">Romance</option>
                    <option value="fantasy">Fantasy</option>
                    <option value="biography">Biography</option>
                    </select>
                </div>
                <!--Search Filter-->
                <div class="col-6 col-md-3">
                    <select class = "search-input dropdown" id="filter" name="filter" >
                    <option value="title">Title A-Z</option>
                    <option value="author">Author A-Z</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!--Display books-->
    <div class="container-fluid">
        
        <!--Shows the number of books on display--> 
            <?php 
            if (empty($results)) {
            ?>
                <h2 id= "hero-section__title" style="color: var(--text-primary);"> Sorry! No books found. </h2>
            <?php
            } else {
                ?>
                <p style="color:  #99a1af; margin-top: 10px;">
                    <?php echo "Showing " .count($results). " books" ?>
                </p>
        
        <div class="row g-3">

            <?php 
            //Iterates through results and displays them
                foreach ($results as $row)    
                {
            ?>
            
            <!--Book Card-->
            <div class="col-lg-2 col-md-4 col-sm-6 col-6 book-card">
                
            <!--Wraps card in link to redirect to book_details page when clicked-->
                <a href="book_details.php?genre=<?php echo $row["genre"]?>&title=<?php echo $row["title"]?>"
                style="text-decoration: none;">

                    <!--Card Image-->
                    <img class=" card-img-top img-responsive rounded book-card__image" src="images/<?php echo $row["img_url"] ?>" 
                    alt="Image of <?php echo $row["title"]. " by " .$row["author"]?>"/>
                    
                    <!--Card Body-->
                    <div class="card-body ms-3 ms-sm-0">

                        <div style="display: flex;">
                            <p class="book-card__badge" class= "badge"> <?php echo $row["genre"] ?> </p>
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
                        </div>
                        <p class="book-card__title"> <?php echo $row["title"]?> </p>
                        <p class="book-card__author"> <?php echo $row["author"]?> </p>
                        <p class="book-card__description truncate_multi_line"> <?php echo $row["book_description"]?> </p>
                        <p> Buy: <span class="book-card__price"> <?php echo "Rs ". $row["price"]?> </span></p>
                        <p> Borrow (7 days): <span class="book-card__price_borrow"> <?php echo "Rs ". $row["rental_fee"] ?> </span></p>
                        <p class="book-card__stock"> <?php echo $row["stock_num"] ." in stock"?> </p>
                    </div>
                    
                    <!--Card Footer-->
                    <div class="mt-3">
                        <!--Submits book to cart to buy only if user is logged in-->
                        <?php if ($is_logged_in) { ?>
                            <form action="shopcart.php" method="post">
                                <input type="hidden" name="id" value="<?php echo $row["book_ID"]?>">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($row["title"])?>">
                                <input type="hidden" name="price" value="<?php echo $row["price"]?>">
                                <input type="hidden" name="image" value="<?php echo $row["img_url"]?>">
                                <input type="hidden" name="author" value="<?php echo htmlspecialchars($row["author"])?>">
                                <input type="hidden" name="qty" value="1" min="1">

                                <button type="submit" value="<?php echo "Rs ". $row["price"] ?>" class="primary_btn">
                                    <i class="bi bi-cart-plus icons"> Buy </i>
                                </button>
                            </form>
                        <?php } else { ?>
                        <p>Please <a href="login.php">log in</a> to add items to your cart.</p>
                        <?php }
                        ?>

                        <!--Submits book to cart to rent only if user is logged in-->
                        <?php if ($is_logged_in) { ?>
                            <form action="shopcart.php" method="post">
                                <input type="hidden" name="id" value="<?php echo $row["book_ID"]?>">
                                <input type="hidden" name="title" value="<?php echo htmlspecialchars($row["title"])?>">
                                <input type="hidden" name="price" value="<?php echo $row["rental_fee"]?>">
                                <input type="hidden" name="image" value="<?php echo $row["img_url"]?>">
                                <input type="hidden" name="author" value="<?php echo htmlspecialchars($row["author"])?>">
                                <input type="hidden" name="qty" value="1" min="1">

                                <button type="submit" value="<?php echo "Rs ". $row["rental_fee"] ?>" class="secondary_btn">
                                    <i class="bi bi-calendar-week icons"> Borrow </i>
                                </button>
                            </form>
                        <?php } else { ?>
                        <p>Please <a href="login.php">log in</a> to add items to your cart.</p>
                        <?php }
                        ?>
                    </div>
                </div>

                <?php 
                }
                ?>
                </a>
            </div>
        </div>
            <?php
            } 
            ?>
            

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous" defer></script>

<!--Include footer.html-->
<?php require("includes/footer.html")?>

</body>
</html>



