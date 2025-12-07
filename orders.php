<?php

// Update order status 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $purchaseId = (int)($_POST['purchase_id'] ?? 0);
    $newStatus  = $_POST['status'] ?? '';

    // Whitelist validation to prevents invalid values
    $allowedStatuses = ['pending', 'completed', 'cancelled'];
    if ($purchaseId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE Purchase SET status = ? WHERE purchase_ID = ?");
        $stmt->execute([$newStatus, $purchaseId]);
    }

    // Prevent resubmit on refresh
    header("Location: owner_dashboard.php?tab=orders");
    exit;
}

// Fetch orders 
$rows = $pdo->query("
    SELECT p.purchase_ID, p.status, p.date_time, p.quantity, p.price AS item_price,
           c.customer_name, u.username AS email, b.title
    FROM Purchase p
    JOIN Customer c ON p.customer_ID = c.customer_ID
    JOIN Users u ON c.user_ID = u.user_ID
    JOIN Book b ON p.book_ID = b.book_ID
    ORDER BY p.date_time DESC
")->fetchAll();

// Group items by order
$orders = [];
foreach ($rows as $r) {
    $id = $r['purchase_ID'];
    if (!isset($orders[$id])) {
        $orders[$id] = [
            'id'       => $id,
            'date'     => date('M j, Y', strtotime($r['date_time'])),
            'time'     => date('g:i A', strtotime($r['date_time'])),
            'customer' => $r['customer_name'],
            'email'    => $r['email'],
            'status'   => $r['status'] ?? 'pending',
            'items'    => [],
            'total'    => 0
        ];
    }
    $orders[$id]['items'][] = $r['title'] . ' (x' . $r['quantity'] . ')';
    $orders[$id]['total']   += $r['item_price'] * $r['quantity'];
}
?>

<div class="table-container">
    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
        <h3>Customer Orders</h3>
    </div>
    <div style="padding: 1.5rem; overflow-x:auto;">
        <table class="table" style="width:100%; background:white; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th style="width: 30%;">Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 600;">#<?= sprintf("%04d", $o['id']) ?></td>
                        <td>
                            <div style="font-weight: 500;"><?= htmlspecialchars($o['customer'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-size: 0.875rem; color: #666;"><?= htmlspecialchars($o['email'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <?php foreach ($o['items'] as $item): ?>
                                <div style="margin: 0.25rem 0; font-size: 0.875rem;">
                                    • <?= htmlspecialchars($item, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td style="font-weight: 600;">MUR <?= number_format($o['total'], 2) ?></td>
                        <td>
                            <span class="badge badge-<?= htmlspecialchars($o['status']) ?>">
                                <?= ucfirst(htmlspecialchars($o['status'])) ?>
                            </span>
                        </td>
                        <td style="font-size: 0.875rem;">
                            <?= $o['date'] ?><br>
                            <small style="color: #6b7280;"><?= $o['time'] ?></small>
                        </td>
                        <td>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="purchase_id" value="<?= $o['id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" class="select" onchange="this.form.submit()">
                                    <option value="pending"    <?= $o['status'] === 'pending'    ? 'selected' : '' ?>>Pending</option>
                                    <option value="completed"  <?= $o['status'] === 'completed'  ? 'selected' : '' ?>>Completed</option>
                                    <option value="cancelled"  <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 3rem; color: #6b7280;">
                            No orders yet, customers will appear here when they buy!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>