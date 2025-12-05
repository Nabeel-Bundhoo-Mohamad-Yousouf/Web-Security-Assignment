
<?php
session_start();

if (!isset($_POST["id"]) || !isset($_POST["qty"])) {
    header("Location: shopcart.php");
    exit;
}

$id = intval($_POST["id"]);
$qty = intval($_POST["qty"]);

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

if ($qty <= 0) {
    unset($_SESSION["cart"][$id]); // Remove item
} else {
    $_SESSION["cart"][$id] = $qty; // Update quantity
}

header("Location: shopcart.php");
exit;
