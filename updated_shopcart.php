<?php
session_start();
require_once "includes/db_connection.php";

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ================= ADD TO CART =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $book_id = (int)$_POST['book_id'];
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $type = $_POST['type'] ?? 'buy';
    
    // Get book from database
    $stmt = $db_conn->prepare("SELECT * FROM Book WHERE book_ID = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if ($book) {
        $price = ($type === 'rent') ? $book['rental_fee'] : $book['price'];
        $key = $book_id . '_' . $type;
        
        if (isset($_SESSION['cart'][$key])) {
            $_SESSION['cart'][$key]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$key] = [
                'id' => $book_id,
                'title' => $book['title'],
                'author' => $book['author'],
                'price' => floatval($price),
                'type' => $type,
                'quantity' => $quantity,
                'image' => $book['img_url']
            ];
        }
        
        $_SESSION['message'] = "Book added to cart!";
    }
    
    header("Location: shopcart.php");
    exit();
}

// ================= UPDATE CART =================
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $key => $qty) {
        $qty = (int)$qty;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
        } else {
            $_SESSION['cart'][$key]['quantity'] = $qty;
        }
    }
    header("Location: shopcart.php");
    exit();
}

// ================= REMOVE ITEM =================
if (isset($_GET['remove'])) {
    $key = $_GET['remove'];
    unset($_SESSION['cart'][$key]);
    header("Location: shopcart.php");
    exit();
}

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Bibliohaha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .cart-table img { width: 60px; height: 80px; object-fit: cover; }
        .quantity-input { width: 80px; text-align: center; }
        .cart-total { font-size: 1.5rem; font-weight: bold; color: #27ae60; }
        .btn-checkout { background: #27ae60; color: white; padding: 12px 30px; }
        .btn-checkout:hover { background: #219a52; color: white; }
    </style>
</head>
<body>

<?php include "includes/header.php"; ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>
    
    <?php if (empty($_SESSION['cart'])): ?>
        <div class="alert alert-info text-center p-5">
            <i class="fas fa-shopping-basket fa-3x mb-3"></i>
            <h4>Your cart is empty</h4>
            <p>Browse our collection and add some books!</p>
            <a href="index.php" class="btn btn-primary mt-3">Continue Shopping</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Book</th>
                            <th>Type</th>
                            <th>Price (Rs)</th>
                            <th>Quantity</th>
                            <th>Subtotal (Rs)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $key => $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($item['image']): ?>
                                        <img src="images/<?= htmlspecialchars($item['image']) ?>" 
                                             alt="<?= htmlspecialchars($item['title']) ?>"
                                             class="me-3">
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($item['title']) ?></strong><br>
                                        <small class="text-muted">by <?= htmlspecialchars($item['author']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $item['type'] == 'rent' ? 'bg-warning' : 'bg-success' ?>">
                                    <?= strtoupper($item['type']) ?>
                                </span>
                            </td>
                            <td>Rs <?= number_format($item['price'], 2) ?></td>
                            <td>
                                <input type="number" 
                                       name="quantity[<?= $key ?>]" 
                                       value="<?= $item['quantity'] ?>" 
                                       min="1" 
                                       max="99"
                                       class="form-control quantity-input">
                            </td>
                            <td>Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            <td>
                                <a href="?remove=<?= urlencode($key) ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Remove this item?')">
                                    <i class="fas fa-trash"></i> Remove
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total:</strong></td>
                            <td colspan="2"><strong class="cart-total">Rs <?= number_format($total, 2) ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <button type="submit" name="update_cart" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Update Cart
                </button>
                <div>
                    <a href="index.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-book"></i> Continue Shopping
                    </a>
                    <a href="checkout.php" class="btn btn-checkout">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php include "includes/footer.html"; ?>
</body>
</html>
