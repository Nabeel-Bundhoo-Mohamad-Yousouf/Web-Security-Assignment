<?php
session_start();
require_once "includes/db_connection.php";

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: shopcart.php");
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'checkout.php';
    header("Location: login.php");
    exit();
}

// Calculate cart total
$cart_items = $_SESSION['cart'];
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$delivery_charge = 100; // Flat delivery fee
$total = $subtotal + $delivery_charge;

// Get user info
$user_id = $_SESSION['user_id'];
$stmt = $db_conn->prepare("SELECT c.customer_name, u.username 
                           FROM Customer c 
                           JOIN Users u ON c.user_ID = u.user_ID 
                           WHERE c.user_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$customer_name = $user['customer_name'] ?? $user['username'] ?? 'Customer';

// Process order
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_address = trim($_POST['shipping_address']);
    $payment_method = $_POST['payment_method'] ?? 'card';
    
    if (empty($shipping_address)) {
        $error = "Please enter a shipping address.";
    } else {
        try {
            $db_conn->beginTransaction();
            
            // Generate unique order number
            $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
            
            // Insert into orders table
            $stmt = $db_conn->prepare("
                INSERT INTO orders (order_number, user_id, customer_name, total_amount, 
                                   shipping_address, payment_method, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $order_number,
                $user_id,
                $customer_name,
                $total,
                $shipping_address,
                $payment_method
            ]);
            $order_id = $db_conn->lastInsertId();
            
            // Insert order items
            $stmt = $db_conn->prepare("
                INSERT INTO order_items (order_id, book_id, title, price, quantity, type, subtotal)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($cart_items as $item) {
                $subtotal_item = $item['price'] * $item['quantity'];
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['title'],
                    $item['price'],
                    $item['quantity'],
                    $item['type'],
                    $subtotal_item
                ]);
                
                // Update stock for purchased items (not for rent)
                if ($item['type'] === 'buy') {
                    $update_stock = $db_conn->prepare("UPDATE Book SET stock_num = stock_num - ? WHERE book_ID = ?");
                    $update_stock->execute([$item['quantity'], $item['id']]);
                }
            }
            
            $db_conn->commit();
            
            // Clear cart
            unset($_SESSION['cart']);
            
            // Redirect to success page
            header("Location: payment_completed.php?order_id=" . $order_id);
            exit();
            
        } catch (Exception $e) {
            $db_conn->rollBack();
            $error = "Order failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bibliohaha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-summary { background: #f8f9fa; padding: 20px; border-radius: 10px; }
        .total-amount { font-size: 1.8rem; font-weight: bold; color: #27ae60; }
    </style>
</head>
<body>

<?php include "includes/header.php"; ?>

<div class="container mt-5 mb-5">
    <h2 class="mb-4"><i class="fas fa-credit-card"></i> Checkout</h2>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Order Summary -->
        <div class="col-md-5 order-md-2 mb-4">
            <div class="order-summary">
                <h4 class="d-flex justify-content-between align-items-center mb-3">
                    <span>Your Order</span>
                    <span class="badge bg-primary rounded-pill"><?= count($cart_items) ?> items</span>
                </h4>
                
                <ul class="list-group mb-3">
                    <?php foreach ($cart_items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between lh-sm">
                        <div>
                            <h6 class="my-0"><?= htmlspecialchars($item['title']) ?></h6>
                            <small class="text-muted">
                                <?= ucfirst($item['type']) ?> × <?= $item['quantity'] ?>
                            </small>
                        </div>
                        <span class="text-muted">Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </li>
                    <?php endforeach; ?>
                    
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Subtotal</span>
                        <strong>Rs <?= number_format($subtotal, 2) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Delivery Fee</span>
                        <strong>Rs <?= number_format($delivery_charge, 2) ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Total (MUR)</span>
                        <strong class="total-amount">Rs <?= number_format($total, 2) ?></strong>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Billing Form -->
        <div class="col-md-7 order-md-1">
            <form method="POST">
                <div class="mb-3">
                    <label for="customer_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="customer_name" 
                           value="<?= htmlspecialchars($customer_name) ?>" readonly disabled>
                </div>
                
                <div class="mb-3">
                    <label for="shipping_address" class="form-label">Shipping Address *</label>
                    <textarea class="form-control" id="shipping_address" name="shipping_address" 
                              rows="3" required placeholder="Enter your full address"></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="card" value="card" checked>
                        <label class="form-check-label" for="card">
                            <i class="fas fa-credit-card"></i> Credit/Debit Card
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="cod" value="cod">
                        <label class="form-check-label" for="cod">
                            <i class="fas fa-money-bill"></i> Cash on Delivery
                        </label>
                    </div>
                </div>
                
                <div id="card_details" class="card-details">
                    <div class="mb-3">
                        <label for="card_name" class="form-label">Cardholder Name</label>
                        <input type="text" class="form-control" name="card_name" placeholder="John Doe">
                    </div>
                    <div class="mb-3">
                        <label for="card_number" class="form-label">Card Number</label>
                        <input type="text" class="form-control" name="card_number" 
                               placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="expiry" class="form-label">Expiry Date</label>
                            <input type="text" class="form-control" name="expiry" placeholder="MM/YY">
                        </div>
                        <div class="col-md-6">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" name="cvv" placeholder="123" maxlength="3">
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="d-flex justify-content-between">
                    <a href="shopcart.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                    <button type="submit" name="place_order" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle"></i> Place Order - Rs <?= number_format($total, 2) ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle card details based on payment method
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const cardDetails = document.getElementById('card_details');
            if (this.value === 'card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
    });
</script>

<?php include "includes/footer.html"; ?>
</body>
</html>
