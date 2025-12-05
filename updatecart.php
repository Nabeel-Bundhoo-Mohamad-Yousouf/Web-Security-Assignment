
<?php
session_start();


$id = $_POST["id"];
$action = $_POST["action"] ?? "add";

// Fetch book from DB
$sql = "SELECT * FROM books WHERE id = $id LIMIT 1";
$result = $conn->query($sql);
$book = $result->fetch_assoc();

if (!$book) {
    header("Location: index.php");
    exit;
}

// add books
if ($action === "add") {
    if (isset($_SESSION["cart"][$id])) {
        $_SESSION["cart"][$id]["qty"]++;
    } else {
        $_SESSION["cart"][$id] = $books[$id];
        $_SESSION["cart"][$id]["qty"] = 1;
    }
}

// increase
if ($action === "increase") {
    $_SESSION["cart"][$id]["qty"]++;
}

// decrease
if ($action === "decrease") {
    $_SESSION["cart"][$id]["qty"]--;
    if ($_SESSION["cart"][$id]["qty"] <= 0) {
        unset($_SESSION["cart"][$id]);
    }
}

// remove
if ($action === "remove") {
    unset($_SESSION["cart"][$id]);
}

// Return to main page
header("Location: index.php");
exit;
