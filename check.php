<?php 
session_start();
$cart = $_SESSION["cart"] ?? [];

// total calculation
$total = 0;
foreach ($cart as $item) {
    $total += $item["price"] * $item["qty"];
}

// CONNECT TO DATABASE
$db = new mysqli("localhost", "root", "", "your_database_name");

// When order is placed
if (isset($_POST["place_order"])) {

    $name    = $_POST["name"];
    $email   = $_POST["email"];
    $address = $_POST["address"];
    $payment = $_POST["payment"];

    // Insert order
    $stmt = $db->prepare("INSERT INTO orders (name, email, address, payment, total) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $name, $email, $address, $payment, $total);
    $stmt->execute();

    // Get order ID
    $order_id = $stmt->insert_id;

    // Insert each item
    foreach ($cart as $item) {
        $stmt2 = $db->prepare("INSERT INTO order_items (order_id, book_id, title, price, qty) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("iisdi", $order_id, $item["id"], $item["title"], $item["price"], $item["qty"]);
        $stmt2->execute();
    }

    // Clear cart
    $_SESSION["cart"] = [];
    $success = true;
}
?>

