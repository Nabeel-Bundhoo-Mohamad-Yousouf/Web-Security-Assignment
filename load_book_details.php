<?php
session_start();

//Holds state of whether or not user is logged in
$is_logged_in = isset($_SESSION['logged_in']);

$book_id = $results = $jsonData = "";
$response = array("message"=>"", "data"=>"");

//Initiates custom execption handler
set_exception_handler("customExceptionHandler");

error_reporting(E_ALL);                         //logs all the errors
ini_set("display_errors", 0);                   //hides errors from display
ini_set("log_errors", 1);                       //enables logging
ini_set("error_log", __DIR__. "/php_error.log");

class CustomException extends Exception{
    public function errorMessage () {
        return "Error: {$this->getMessage()} in {$this->getFile()} on line {$this->getLine()} \n | Trace {$this->getTraceAsString()} \n";
    }
}
//Handles exception; logs exceptions insttead of printing on browser.
function customExceptionHandler($exception) {
    if (method_exists($exception, "errorMessage")) {
        $msg = $exception->error_message();
    } else {
        $msg = "Error: {$exception->getMessage()} in {$exception->getFile()} on line {$exception->getLine()} \n | Trace {$exception->getTraceAsString()} \n";
    }

    error_log($msg. "\n",3, __DIR__ ."/load_book_details_php_errors.log");
    echo "An unexpected error occured. Please try again.";
}

// Reads book_id submitted (via GET) when book is clicked
if (isset($_GET['book_id']) && is_numeric($_GET['book_id'])) {
    $book_id = $_GET["book_id"];
    
    try{
        // Include the database connection file
        require_once "includes/db_connect.php"; 
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_prepd = $db_conn->prepare("CALL book_preview_search(?)");
        $stmt_prepd -> execute([$book_id]);
        $results = $stmt_prepd->fetchAll(PDO::FETCH_ASSOC);
        $stmt_prepd->closeCursor();

    } catch (PDOException $e) {
        throw new CustomException($e->getMessage());
    }
    
    if ($results) {
        $response["message"] = "success";
        $response["data"] = $results;
    }
    else {
        $response["message"] = "error";
        $response["data"] = "Book not found";
    }

    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
}

