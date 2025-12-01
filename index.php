<?php 
session_start();

// Include the database connection file
require_once "includes/../db_connect.php";

$last_genre = $search = $genre = $filter = "";
$results=[];

function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

//Checks and reads cookie of 
if (isset($_COOKIE["last_genre"])) {
    $last_genre = $_COOKIE["last_genre"];
}

//POST -> Redirect -> GET
//Checks whether search form is submitted via POST
if ($_SERVER["REQUEST_METHOD"]== "POST") {
    $search = clean_input($_POST["search"] ?? "");
    $genre = clean_input($_POST["genre"] ?? "");
    $filter = clean_input($_POST["filter"] ?? "title");

    //Redirects to avoid POST resubmission
    header("Location:index.php?search=" .urlencode($search). "&genre=" .urlencode($genre). "&filter=" .urlencode($filter));
    exit;
} else {

    //Read data from GET (at direct load or redirect)
    $search = htmlspecialchars($_GET["search"] ?? "");
    $genre = htmlspecialchars($_GET["genre"] ?? "");
    $filter = htmlspecialchars($_GET["filter"] ?? "title");

    //Handles search query
    if (!empty($search)) {
        
        $statement_prepd = $conn->prepare("CALL search_books(?, ?, ?)");
        $statement_prepd -> execute([$search, $genre, $filter]);

        //Retrieve results and frees connection
        $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
        $statement_prepd->closeCursor();
    
    } //Handles links in footer
    elseif (isset($_GET['referer'])) {
    $genre = $_GET["genre"] ?? "";
    $statement_prepd = $conn->prepare("CALL footer_filters(?)");
    $statement_prepd -> execute([$genre]);

    $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
    $statement_prepd->closeCursor();

    } 
    else {
        //Handles direct homepage access  
        $statement_prepd = $conn->query("SELECT * FROM view_books");
        
        $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
        $statement_prepd->closeCursor();
    }
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

    <link rel="preload" href="../css/external_style.css" as="style">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 
    
    <style>
        .search {
            display: flex;
            align-items: center;
            background-color:var(--bg-gray);
            border-radius: var(--radius-sm);
            padding-left:10px ;
            border: none;
            outline: none;
        }
        
    </style>

</head>
<body>
    <!--Include header.html/header.php-->
    <?php 
    $activemenu = "home";
    include("includes/../header.php");
    ?>
    
    <!--hero section-->
    <div class="container-fluid" id= "hero-section">
        <h2 id= "hero-section__title">Welcome to Bibliohaha!</h2>
        <p id= "hero-section__description">Discover your next favorite book from our curated collection</p>
    </div>
    
    <!--search form-->
    <div class="container-fluid mt-3">
        <div class="row align-items-start">
            <form action="<?php echo $_SERVER["PHP_SELF"];?>" method="post">
                <!--Search Input-->
                <div class="search col-6">
                    <button type="submit" name="search_form"><i class="fa fa-search"></i></button>
                    <input type="text" name="search" placeholder="Search books or authors.." 
                    style="padding-left: 0%;">
                </div>
                <!--Search Genre-->
                <div class="col-3">
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
                <div class="col-3">
                    <select class = "search-input dropdown" id="filter" name="filter" >
                    <option value="title">Title A-Z</option>
                    <option value="author">Author A-Z</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!--Display books-->
    <div class="container-fluid">
        
        <!--Shows the number of books on display-->
        <p style="color:  #99a1af; margin-top: 10px;"> 
            <?php 
            if (empty($results)) {
            ?>
                <h2 id= "hero-section__title" style="color: var(--text-primary);"> Sorry! No books found. </h2>
            <?php
            } else {
                echo "Showing " .count($results). " books";
            ?>
        </p>
        
        <div class="row g-3 books-grid">

            <?php 
            //Iterates through results and displays them
                foreach ($results as $row)    
                {
            ?>
            <!--Wraps card in link to redirect to book_details page when clicked-->
            <a href="book_details.php?id=<?php echo $row["book_ID"]?>&genre=<?php echo $row["genre"]?>"
            style="text-decoration: none;">
            
            <!--Book Card-->
            <div class="col-lg-2 col-md-4 col-sm-6 col-12 book-card">

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
                        <p class="book-card__description truncate_multi_line"> <?php echo $row["description"]?> </p>
                        <p> Buy: <span class="book-card__price"> <?php echo "Rs ". $row["price"]?> </span></p>
                        <p> Borrow (7 days): <span class="book-card__price_borrow"> <?php echo "Rs ". $row["rental_fee"] ?> </span></p>
                        <p class="book-card__stock"> <?php echo $row["stock_num"] ." in stock"?> </p>
                    </div>
                    
                    <!--Card Footer-->
                    <div class="mt-3">
                        <!--Submits name and price of selected book to cart-->
                        <form action="add_to_cart.php" method="post">
                            <input type="hidden" name="title" value="<?php echo $row["title"]?>">
                            <button type="submit" name="buy" value="<?php echo "Rs ". $row["price"] ?>" class="primary_btn">
                                <i class="bi bi-cart-plus icons"> Buy </i>
                            </button>
                            <button type="submit" name="rent" value="<?php echo "Rs ". $row["rental_fee"] ?>" class="secondary_btn">
                                <i class="bi bi-calendar-week icons"> Borrow </i>
                            </button>
                        </form>
                    </div>
                </div>

                <?php 
                }
                ?>
            </div>
            </a>
        </div>
    </div>
            <?php
            } 
            ?>
            

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous" defer></script>

    <!--Include footer.html-->
    <?php require("includes/../footer.html")?>

</body>

</html>
