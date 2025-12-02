<?php
// Database connection settings
$host = "mysql:host=localhost; dbname=bibliohaha"; 
$user = "root";         
$password = "";        


try{
    //the DB connection becomes an object instance making it easier to call in code.
    $db_conn = new PDO($host, $user, $password);

    // set the PDO error mode attribute to exception i.e. if an error occurs it must be handled as an exception.
    $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //grabs the exception (info about error) and assigns to $e and prints it 
} catch (PDOException $e) {
    echo "Connectionn failed:" .$e ->getMessage();
}

?>