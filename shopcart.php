<?php
session_start();
include "db.php";

if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    echo "Your cart is empty.";
    exit;
}

// Handle updates from the cart form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST['qty'] as $id => $qty) {
        $type = $_POST['type'][$id]; // Buy or Rent
        $_SESSION["cart"][$id] = [
            "qty" => intval($qty),
            "type" => $type
        ];
    }
    header("Location: cart.php");
    exit;
}

$cart = $_SESSION["cart"];
$items = [];
$total = 0;

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
?>

<!DOCTYPE html>
<html>
<head>
<title>Shopping Cart</title>
<style>
body { font-family: Arial; padding: 20px; }
input, select { padding: 5px; margin: 5px; }
button { padding: 8px 15px; }
table { border-collapse: collapse; width: 80%; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
</style>
</head>
<body>

<h2>Shopping Cart</h2>

<form method="POST" action="cart.php">
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
    <td>
        <input type="number" name="qty[<?= $item['book_ID'] ?>]" value="<?= $item['qty'] ?>" min="1" required>
    </td>
    <td>
        <select name="type[<?= $item['book_ID'] ?>]">
            <option value="Buy" <?= $item['type'] == 'Buy' ? 'selected' : '' ?>>Buy</option>
            <option value="Rent" <?= $item['type'] == 'Rent' ? 'selected' : '' ?>>Rent</option>
        </select>
    </td>
    <td>Rs <?= $item['subtotal'] ?></td>
</tr>
<?php endforeach; ?>

<tr>
    <td colspan="4" style="text-align:right;"><strong>Total:</strong></td>
    <td>Rs <?= $total ?></td>
</tr>
</table>

<button type="submit">Update Cart</button>
<a href="checkout.php"><button type="button">Proceed to Checkout</button></a>
</form>

</body>
</html>
