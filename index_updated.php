<?php
session_start();

//Holds state of whether or not user is logged in
$is_logged_in = isset($_SESSION['logged_in']);

//Initiates custom execption handler
set_exception_handler("customExceptionHandler");

//Variable initialisation
$exception = $last_genre = $search = $genre = $filter = "";
$results=[];

error_reporting(E_ALL);                         //logs all the errors
ini_set("display_errors", 0);                   //hides errors from display
ini_set("log_errors", 1);                       //enables logging
ini_set("error_log", __DIR__. "/php_error.log");

class CustomException extends Exception{
    public function errorMessage () {
        return "Error: {$this->getMessage()} in {$this->getFile()} on line 
        {$this->getLine()} \n | Trace {$this->getTraceAsString()} \n";
    }
}

//Handles exception; logs exceptions instead of printing on browser.
function customExceptionHandler($exception) {
    if (method_exists($exception, "errorMessage")) {
        $msg = $exception->error_message();
    } else {
        $msg = "Error: {$exception->getMessage()} in {$exception->getFile()} on line 
        {$exception->getLine()} \n | Trace {$exception->getTraceAsString()} \n";
    }

    error_log($msg. "\n",3, __DIR__ ."/index_php_errors.log");
    echo "An unexpected error occured. Please try again.";
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

</head>

<body>
    <!--Include header.php-->
    <?php
    $activemenu = "home";
    include "includes/header.php";
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
                        <input type="text" name="search" id="search" placeholder="Search books or authors.." 
                        style="padding-left: 0%;">
                    </div>
                </div>
                <!--Search Genre-->
                <div class="col-6 col-md-3">
                    <select class = "search-input dropdown" id="filter" name="filter">
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
                    <select class = "search-input dropdown" id="sort" name="sort" >
                    <option value="title">Title A-Z</option>
                    <option value="author">Author A-Z</option>
                    <option value="price_asc">Price: Low to High</option>
                    <option value="price_desc">Price: High to Low</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!--Display search results-->
    <div class="container-fluid" id="display-search-results" style="display: none;">

    </div>

    <!--Display books-->
    <div class="container-fluid" id="display-books">
        
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
    
    let search = "";
        let filter = "";
        let sort = "";
        let page = 1;

    $(document).ready(function() {
        //Load page 1 on load but not recommended
        loadBooks(1);

        $("#search").on("keyup", function() {
            search = $(this).val();
            loadBooks(1);
        });

        $("#filter").on("change", function() {
            filter = $(this).val();
            loadBooks(1);
        });

        $("#sort").on("change", function() {
            sort = $(this).val();
            loadBooks(1);
        });

        $(document).on("click",".book_details", function() {
            let book_id = $(this).data("id");
            loadBookDetails(book_id);
        });

        //Listen for pagination link click when document is fully loaded
        $(document).on("click", ".pagination li a", function(e){
            e.preventDefault();
            page = $(this).data("id");
            loadBooks(page);
        });

        $(document).on("click", ".footer_link", function(e) {
            e.preventDefault();
            let footer_filter = $(this).data("filter");
            let footer_sort = $(this).data("sort");

            if (footer_filter !== undefined) {
                filter = footer_filter;
            }

            if (footer_sort !== undefined) {
                sort = footer_sort;
            }
            loadBooks(1);
        });
        
    });

    function loadBooks(page=1){
        $.ajax({
            url: "load_books.php",
            type: "GET",
            data: {page: page, search: search, sort: sort, filter: filter},
            success: function(response) {
                $("div#display-books").html(response);
            },
            error: function(xhr) {
                console.error("AJAX Error:", xhr);
                $("#display-books").html("<p class='error'>Failed to display books. Please try again.</p>");
            }
        });
    }

    function loadBookDetails(book_id) {
        $.getJSON ("load_book_details.php", {book_id: book_id})
        
        .done (function (response) {
            if (response.status !== "success") {
                $("#book-details-modal").html("Sorry! Book not found.");
            }

            let book = response.data;

            //let star_rating =
            
            $(".preview_modal-title").text(book.title);
            $(".preview-card__image").attr("src", `images/${book.img_url}`);
            $(".preview-card__image").attr("alt", book.title);
            $(".preview-card__title").text(book.title);
            $(".preview-card__author").text(book.author);
            $(".preview-card__star-rating").text();
            $(".preview-card__rating-num").text(book.rating_num);
            $(".preview-card__description").text(book.description);

            //if (book.reviews)

        })
        .fail (function() {

        });
    }

</script>

<!--Include footer.html-->
<?php include "includes/footer.html" ?>

</body>
</html>
