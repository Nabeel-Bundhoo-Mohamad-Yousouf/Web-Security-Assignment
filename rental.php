<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_rental'])) {
    $rentId = (int)$_POST['return_rental'];
    $bookId = (int)$_POST['book_id'];

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE Book SET stock_num = stock_num + 1 WHERE book_ID = ?")->execute([$bookId]);
    $pdo->prepare("UPDATE Rental SET reviewed = 1 WHERE Rent_ID = ?")->execute([$rentId]);
    $pdo->commit();

    header("Location: owner_dashboard.php?tab=rentals");
    exit;
}

$rentals = $pdo->query("
    SELECT r.Rent_ID, r.start_date, r.End_date,
           c.customer_name, b.title, b.book_ID,
           DATEDIFF(r.End_date, CURDATE()) AS days_left
    FROM Rental r
    JOIN Customer c ON r.customer_ID = c.customer_ID
    JOIN Book b ON r.Book_ID = b.book_ID
    WHERE r.End_date >= CURDATE()
    ORDER BY r.End_date ASC
")->fetchAll();
?>

<div class="table-container">
    <div style="padding:1.5rem;border-bottom:1px solid var(--border);"><h3>Active Rentals & Returns</h3></div>
    <div style="padding:1.5rem;overflow-x:auto;">
        <table class="table" style="width:100%;background:white;border-collapse:collapse;">
        <thead>
            <tr>
                <th>Rental ID</th>
                <th>Customer</th>
                <th>Book Title</th>
                <th>Start</th>
                <th>Due</th>
                <th>Days Left</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rentals as $r):
                $overdue = $r['days_left'] < 0;
            ?>
            <tr class="<?= $overdue ? 'bg-red-50' : '' ?>">
                <td style="font-family:monospace;font-weight:600;">#<?= sprintf("%04d", $r['Rent_ID']) ?></td>
                <td><?= htmlspecialchars($r['customer_name']) ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= date('M j, Y', strtotime($r['start_date'])) ?></td>
                <td><?= date('M j, Y', strtotime($r['End_date'])) ?></td>
                <td class="<?= $overdue ? 'text-red-600 font-bold' : '' ?>">
                    <?= $overdue ? 'Overdue by '.abs($r['days_left']).' days' : $r['days_left'].' days' ?>
                </td>
                <td><span class="badge <?= $overdue ? 'badge-destructive' : 'badge-default' ?>"><?= $overdue ? 'Overdue' : 'Active' ?></span></td>
                <td>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="return_rental" value="<?= $r['Rent_ID'] ?>">
                        <input type="hidden" name="book_id" value="<?= $r['book_ID'] ?>">
                        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Mark returned?')">Mark Returned</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($rentals)): ?>
            <tr><td colspan="8" style="text-align:center;padding:3rem;color:#6b7280;">No active rentals.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>