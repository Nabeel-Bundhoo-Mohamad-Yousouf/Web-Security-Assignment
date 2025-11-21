<?php 
// Include the database connection file
include_once "includes/db_conn.php";
    
//Checks whether search form is submitted
if ($_SERVER["REQUEST_METHOD"]== "POST") {
    $search = htmlspecialchars($_POST["search"] ?? "");
    $genre = htmlspecialchars($_POST["genre"] ?? "");
    $filter = htmlspecialchars($_POST["filter"] ?? "title");

    $query = $search_param = "";

    if (!empty($search)) {
        // Basic query to fetch names that contain the search term (case-insensitive match)
        $query = "SELECT b.*, COUNT(r.rating) AS rating_num, AVG(r.rating) AS avg_rating,  
                  FROM book b 
                  LEFT JOIN review r ON b.book_ID = r.book_ID 
                  WHERE (title LIKE ? OR author LIKE ?) ";

        $search_param = '%' . $search . '%';

        if (!empty($genre)) {
            $query.= " AND genre =  ? ";

            switch($filter) {
                case "author":
                    $query.= " ORDER BY author ASC LIMIT 5 ";
                    break;
                case "price_asc":
                    $query.= " ORDER BY price ASC LIMIT 5 ";
                    break;
                case "price_desc":
                    $query.= " ORDER BY price DESC LIMIT 5 ";
                    break;
                default:
                $query.= " ORDER BY title ASC LIMIT 5 ";
            }

            $statement_prepd = $db_conn->prepare($query);
            $statement_prepd -> execute([$search_param, $search_param, $genre]);
            $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);

        } else {
            switch($filter) {
                case "author":
                    $query.= " ORDER BY author ASC LIMIT 5 ";
                    break;
                case "price_asc":
                    $query.= " ORDER BY price ASC LIMIT 5 ";
                    break;
                case "price_desc":
                    $query.= " ORDER BY price DESC LIMIT 5 ";
                    break;
                default:
                $query.= " ORDER BY title ASC LIMIT 5 ";
            }

            $statement_prepd = $db_conn->prepare($query);
            $statement_prepd -> execute([$search_param, $search_param]);
            $results = $statement_prepd->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} else{
    //Checks whether user accessed page 
    if ($_SERVER["REQUEST_METHOD"] == "GET"){
        
        $query = "SELECT b.*, COUNT(r.rating) AS rating_num, AVG(r.rating) AS avg_rating, 
        FROM book b 
        LEFT JOIN review r ON b.book_ID = r.book_ID 
        ORDER BY r.rating
        LIMIT 8";

        $display_result = $db_conn->query($query);
        $results = $display_result->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>bibliohaha- Home</title>
<!--https://gist.github.com/david-bakin/255660af79c386460cdf0ee6a2a96291?permalink_comment_id=3540313 -->
    <meta charset="UTF-8">
    <meta name = "keywords" content="">
    <meta name = "description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name = "robots" content="index, archive, follow">
    <link rel="stylesheet" href="css/external_style.css"> 
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
    <!-- Embed external file-->
    
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
                    <option value="">Fiction</option>
                    <option value="">Non-fiction</option>
                    <option value="">Science fiction</option>
                    <option value="">Mystery</option>
                    <option value="">Romance</option>
                    <option value="">Fantasy</option>
                    <option value="">Biography</option>
                    </select>
                </div>
                <!--Search Filter-->
                <div class="col-3">
                    <select class = "search-input dropdown" id="filter" name="filter" >
                    <option value="">Title A-Z</option>
                    <option value="">Author A-Z</option>
                    <option value="">Price: Low to High</option>
                    <option value="">Price: High to Low</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!--Display books-->
    <div class="container-fluid">
        <p style="color:  #99a1af; margin-top: 10px;"> <?php echo "Showing " .count($results). " books" ?></p>
        <div class="row books-grid">
    <!--<div class="col book-card col-lg-3 col-md-6 col-sm-12">-->
            <?php 
            //Iterates through results and displays them
            foreach ($results as $row)    
                {
            ?>
            <a href="book_details.php?id=<?php echo $row["book_ID"]?>"
            style="text-decoration: none;">
                <div class="col book-card">
                <!--Card Header-->
                    <img class=" card-img-top img-responsive rounded book-card__image" src="images/<?php echo $row["img_url"] ?>" alt="Image of <?php echo $row["title"]?>"/>
                    <!--Card Body-->
                    <div>
                        <div style="display: inline-block;">
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
                            </div>
                        </div>
                        <p class="book-card__title"> <?php echo $row["title"]?> </p>
                        <p class="book-card__author"> <?php echo $row["author"]?> </p>
                        <p class="book-card__description truncate_multi_line"> <?php echo $row["description"]?> </p>
                        <p> Buy: <span class="book-card__price"> <?php echo "MUR ". $row["price"]?> </span></p>
                        <p> Borrow (7 days): <span class="book-card__price_borrow"> <?php echo "MUR ". $row["rental_fee"] ?> </span></p>
                        <p class="book-card__stock"> <?php echo $row["stock_num"] ." in stock"?> </p>

                        <!--Card Footer-->
                        <!--link added must add to cart-->
                        <div>
                            <a href="" class="btn primary_btn"><i class="bi bi-cart-plus icons"> Buy </i></a>
                            <a href="" class="btn primary_btn"> <i class="bi bi-calendar-week icons"> Rent </i></a>
                        </div>
                    </div>

                <?php 
                }
                ?>
            </div>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

</body>
</html>