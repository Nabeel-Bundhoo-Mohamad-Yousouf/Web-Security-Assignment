
<?php
session_start();
$cart = $_SESSION["cart"] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Shopping Cart</title>

<style>
body {
    font-family: Arial;
    background: #f2f2f2;
    padding: 20px;
}
.container {
    width: 80%;
    margin: auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
}
h2 {
    text-align: center;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ccc;
}
.book-img {
    width: 60px;
}
.btn {
    padding: 8px 15px;
    background: #0077cc;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
}
.btn:hover {
    background: #005fa3;
}
.qty-btn {
    padding: 5px 8px;
    background: #ddd;
    text-decoration: none;
    margin: 0 3px;
    border-radius: 5px;
}
.remove {
    color: red;
    text-decoration: none;
}
.total {
    text-align: right;
    font-size: 20px;
    margin-top: 20px;
}
</style>

</head>
<body>

<div class="container">
    <h2>Your Shopping Cart</h2>

    <table>
        <tr>
            <th>Book</th>
            <th>Title</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Subtotal</th>
            <th>Action</th>
        </tr>

        <?php
        $total = 0;
        if (empty($cart)) {
            echo "<tr><td colspan='6' style='text-align:center;'>Your cart is empty</td></tr>";
        }

        foreach ($cart as $id => $item):
            $subtotal = $item["price"] * $item["qty"];
            $total += $subtotal;
        ?>
        <tr>
            <td><img src="<?= $item['image'] ?>" class="book-img"></td>
            <td><?= $item["title"] ?><br><small><?= $item["author"] ?></small></td>
            <td>Rs <?= number_format($item["price"], 2) ?></td>

            <td>
                <a class="qty-btn" href="update_cart.php?id=<?= $id ?>&action=decrease">-</a>
                <?= $item["qty"] ?>
                <a class="qty-btn" href="update_cart.php?id=<?= $id ?>&action=increase">+</a>
            </td>

            <td>Rs <?= number_format($subtotal, 2) ?></td>

            <td><a class="remove" href="update_cart.php?id=<?= $id ?>&action=remove">Remove</a></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="total">
        <strong>Total: Rs <?= number_format($total, 2) ?></strong>
    </div>

    <?php if (!empty($cart)): ?>
        <a class="btn" href="checkout.php">Proceed to Checkout</a>
    <?php endif; ?>
</div>

</body>
</html>
