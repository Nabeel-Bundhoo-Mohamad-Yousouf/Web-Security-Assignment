<?php
session_start();
include "includes/db_connect.php";

if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    echo "Your cart is empty.";
    exit;
}

$cart = $_SESSION["cart"];
$items = [];
$total = 0;

// Fetch product info
$ids = implode(",", array_keys($cart));
$result = $conn->query("SELECT * FROM Book WHERE book_ID IN ($ids)");

while ($row = $result->fetch_assoc()) {
    $id = $row["book_ID"];
    $row["qty"] = $cart[$id]["qty"];
    $row["type"] = $cart[$id]["type"];
    $row["subtotal"] = $row["qty"] * $row["price"];
    $total += $row["subtotal"];
    $items[] = $row;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST["name"];
    $email = $_POST["email"];
    $address = $_POST["address"];
    $card_name = $_POST["card_name"];
    $card_number = $_POST["card_number"];
    $payment = "Card";

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (name, email, address, payment, total) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $name, $email, $address, $payment, $total);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert order items
    foreach ($items as $it) {
        $stmt2 = $conn->prepare("INSERT INTO order_items (order_id, book_id, title, price, qty, type) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("iisdis",
            $order_id,
            $it["book_ID"],
            $it["title"],
            $it["price"],
            $it["qty"],
            $it["type"]
        );
        $stmt2->execute();
    }

    // Clear cart
    unset($_SESSION["cart"]);

    // Redirect to payment confirmation
    header("Location: payment_completed.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Checkout</title>
<style>
body { font-family: Arial; padding: 20px; }
input, textarea, select { width: 300px; padding: 8px; margin: 10px 0; display:block; }
button { padding: 10px 20px; }
table { border-collapse: collapse; width: 80%; margin-bottom:20px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
</style>
</head>
<body>

<h2>Checkout</h2>

<h3>Your Order</h3>
<table>
<tr>
<th>Title</th>
<th>Price</th>
<th>Quantity</th>
<th>Type</th>
<th>Subtotal</th>
</tr>
<?php foreach ($items as $item): ?>
<tr>
    <td><?= htmlspecialchars($item['title']) ?></td>
    <td>Rs <?= $item['price'] ?></td>
    <td><?= $item['qty'] ?></td>
    <td><?= $item['type'] ?></td>
    <td>Rs <?= $item['subtotal'] ?></td>
</tr>
<?php endforeach; ?>
<tr>
    <td colspan="4" style="text-align:right;"><strong>Total:</strong></td>
    <td>Rs <?= $total ?></td>
</tr>
</table>

<form method="POST">
<h3>Billing & Payment Details</h3>

<label>Name</label>
<input type="text" name="name" required>

<label>Email</label>
<input type="email" name="email" required>

<label>Address</label>
<textarea name="address" required></textarea>

<label>Cardholder Name</label>
<input type="text" name="card_name" required>

<label>Card Number</label>
<input type="text" name="card_number" maxlength="16" required placeholder="XXXX XXXX XXXX XXXX">

<p>Payment Method: <strong>Card (Online Payment)</strong></p>

<button type="submit">Pay Rs <?= $total ?></button>
</form>

</body>
</html>


