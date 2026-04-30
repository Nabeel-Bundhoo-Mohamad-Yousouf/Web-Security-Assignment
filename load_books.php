<?php
session_start();

//Holds state of whether or not user is logged in
$is_logged_in = isset($_SESSION['logged_in']);

//Variable initialisation
$book_limit = 4;
$star_rating = 0;
$exception = $page_num = $search = $filter = $sort = $html_output = $stars = "";

error_reporting(E_ALL);                         //logs all the errors
ini_set("display_errors", 1);                   //hides errors from display
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

    error_log($msg. "\n",3, __DIR__ ."/load_books_php_errors.log");
    echo "An unexpected error occured. Please try again.";
}


//Function to sanitise and clean input
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

//Include the database connection file
require_once "includes/db_connect.php";
$db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $page_num = filter_input(INPUT_GET, "page", FILTER_VALIDATE_INT);
    $page_num = $page_num ? $page_num : 1;
    $offset = ($page_num -1) * $book_limit;

    //Handles search book request
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        //Read data from GET (at direct load or redirect)
        $search = clean_input($_GET["search"] ?? "");
        $filter = clean_input($_GET["filter"] ?? "");
        $sort = clean_input($_GET["sort"] ?? "");
        
        $prepd_select = $db_conn->prepare("CALL load_books(?, ?, ?, ?, ?)");
        $prepd_select -> execute([$search, $filter, $sort, $book_limit, $offset]);
        $results = $prepd_select->fetchAll(PDO::FETCH_ASSOC);
        $prepd_select->closeCursor();


        //Get total books count
        $prepd_count = "SELECT COUNT(*) AS total_books FROM view_books WHERE
                                        (title LIKE :search_term OR author LIKE :search_term)
                                        AND (:filter IS NULL OR :filter = '' OR genre = :filter)";
        $prepd_count = $db_conn->prepare($prepd_count);
        $search_term = "%" .$search. "%";
        $prepd_count -> execute([":search_term" => $search_term, ":filter" => $filter]);
        // Unlike fetch() and fetchAll() that need PDO::FETCH_ASSOC to fetch an associative array, fetchColumn() does not need it because it returns a single value
        $total_books = $prepd_count->fetchColumn();
        $prepd_count->closeCursor();
    }
    else {
        //Get books
        $prepd_select = $db_conn->prepare("SELECT * FROM view_books LIMIT $book_limit OFFSET ?");
        $prepd_select -> execute([$offset]);
        $results = $prepd_select->fetchAll(PDO::FETCH_ASSOC);
        $prepd_select->closeCursor();

        //Get total books count
        $prepd_count = $db_conn->query ("SELECT COUNT(*) AS total_books FROM view_books");
        $total_books = $prepd_count->fetchColumn();
    }

    $total_pages = ceil($total_books / $book_limit);
}
catch (PDOException $e) {
    throw new CustomException($e->getMessage());
}
if (empty($results)) {
    //Shows the number of books on display
    $html_output = "<h2 id= 'hero-section__title' style='color: var(--text-primary);'> Sorry! No books found. </h2>";
} else {
    //Shows the number of books on display
    $html_output = "<p style='color:  #99a1af; margin-top: 10px;'> Showing " .count($results). " books </p>
                    <div class='row g-3'>";
    foreach ($results as $book)
        {
            $html_output .= "<!--Book Card-->
                            <div class='col-lg-2 col-md-4 col-sm-6 col-6 book-card'>

                                <!--Wraps card in link to redirect to book_details page when clicked-->
                                <a href='#' class='book_details' data-id='{$book['book_ID']}' style='text-decoration: none;'>

                                <!--Card Image-->
                                <img class= 'card-img-top img-responsive rounded book-card__image' src='images/" .htmlspecialchars($book['img_url']). "'
                                alt='Image of {$book['title']} by {$book['author']}'/>
                    
                                <!--Card Body-->
                                <div class= 'card-body ms-3 ms-sm-0'>

                                    <div style='display: flex;'>
                                        <p class='book-card__badge' class= 'badge'>{$book['genre']}</p>
                                        <div class= 'star-rating'>";
                                            $stars = "";
                                            $star_rating = floor($book['avg_rating'] ?? 0);
                                            for ($i=1; $i<= 5; $i++){
                                                $icon = ($i <= $star_rating) ? "★" : "☆";
                                                $stars.= "<span class='star'> {$icon} </span>";
                                            }
                                            $html_output.= $stars;

                                            $html_output.= "<span style='color: var(--text-primary);'> ({$book['rating_num']})</span>
                                        </div>
                                    </div>
                                    <p class='book-card__title'>" .htmlspecialchars($book['title']). "</p>
                                    <p class='book-card__author'>" .htmlspecialchars($book['author']). "</p>
                                    <p class='book-card__description truncate_multi_line'>" .htmlspecialchars($book['book_description']). "</p>
                                    <p> Buy: <span class='book-card__price'> Rs {$book['price']}</span></p>
                                    <p> Borrow (7 days): <span class='book-card__price_borrow'> Rs {$book['rental_fee']}</span></p>
                                    <p class='book-card__stock'>{$book['stock_num']} in stock</p>
                                </div>
                    
                                <!--Card Footer-->
                                <div class='mt-3'>

                                    <!--Submits book to cart to buy only if user is logged in-->";

                                    if ($is_logged_in) {
                                        $html_output.= "
                                        <form action='shopcart.php' method='post'>
                                            <input type='hidden' name='id' value='{$book['book_ID']}'>
                                            <input type='hidden' name='title' value='" .htmlspecialchars($book['title']). "'>
                                            <input type='hidden' name='price' value='{$book['price']}'>
                                            <input type='hidden' name='image' value='{$book['img_url']}'>
                                            <input type='hidden' name='author' value='" .htmlspecialchars($book['author']). "'>
                                            <input type='hidden' name='qty' value='1' min='1'>

                                            <button type='submit' class='primary_btn'>
                                                <i class='bi bi-cart-plus icons'> Buy </i>
                                            </button>
                                        </form>";
                                    }
                                    else {
                                        $html_output.= " <p>Please <a href='login.php'>log in</a> to add items to your cart.</p>";
                                    }

                                    $html_output.= "<!--Submits book to cart to rent only if user is logged in-->";
                                    if ($is_logged_in) {
                                        $html_output.= " 
                                        <form action='shopcart.php' method='post'>
                                            <input type='hidden' name='id' value='{$book['book_ID']}'>
                                            <input type='hidden' name='title' value='" .htmlspecialchars($book['title']). "'>
                                            <input type='hidden' name='price' value='{$book['rental_fee']}'>
                                            <input type='hidden' name='image' value='{$book['img_url']}'>
                                            <input type='hidden' name='author' value='" .htmlspecialchars($book['author']). "'>
                                            <input type='hidden' name='qty' value='1' min='1'>


                                            <button type='submit' class='secondary_btn'>
                                                <i class='bi bi-calendar-week icons'> Borrow </i>
                                            </button>
                                        </form>";
                                    }
                                    else {
                                        $html_output.= "<p>Please <a href='login.php'>log in</a> to add items to your cart.</p>";
                                    }
                                $html_output.= "
                                </div>
                                </a>
                            </div>";
        }
        $html_output.= "</div>
        <div>
            <nav aria-label='Page navigation'>
                <ul class='pagination justify-content-center'>";
                //Previous
                if ($page_num > 1) {
                    $prev_page = $page_num - 1;
                    $html_output.= "
                        <li class='page-item'>
                            <a class='page-link' href='#' aria-label='Previous' data-id='{$prev_page}'>
                                <span aria-hidden='true'>&laquo;</span>
                            </a>
                        </li>";
                }
                else {
                    $html_output.= " 
                        <li class='page-item disabled'>
                            <a class='page-link' href='#' aria-label='Previous'>
                                <span aria-hidden='true'>&laquo;</span>
                            </a>
                        </li>";
                }
                //Page numbers
                for ($i = 1; $i <= $total_pages; $i++) {
                    $active = ($i == $page_num) ? "active" : "";
                    $html_output.= "
                        <li class='page-item {$active}'>
                            <a class='page-link' href='#' data-id='{$i}'>
                                {$i}
                            </a>
                        </li>";
                }

                //Next
                if ($page_num < $total_pages) {
                    $next_page = $page_num + 1;
                    $html_output.= " 
                        <li class='page-item'>
                            <a class='page-link' href='#' aria-label='Next' data-id='{$next_page}'>
                                <span aria-hidden='true'>&raquo;</span>
                            </a>
                        </li>";
                }
                else {
                    $html_output.= " 
                        <li class='page-item disabled'>
                            <a class='page-link' href='#' aria-label='Next'>
                                <span aria-hidden='true'>&raquo;</span>
                            </a>
                        </li>";
                }
                $html_output.= "
                </ul>
            </nav>
        </div>";
}

echo $html_output;
exit;
