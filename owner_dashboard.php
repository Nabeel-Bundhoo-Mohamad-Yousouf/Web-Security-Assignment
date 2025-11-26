<?php
session_start();


// CONFIGURATION
$host = 'localhost';
$db   = 'Bibliohaha';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';



$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];


try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}


// Process order status update when form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $purchaseId = (int)$_POST['purchase_id'];
    $newStatus = $_POST['status'];

    // Validate newStatus against allowed ENUM values
    $allowedStatuses = ['pending', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        $newStatus = 'pending'; // default fallback
    }

    $stmt = $pdo->prepare("UPDATE Purchase SET status = ? WHERE purchase_ID = ?");
    $stmt->execute([$newStatus, $purchaseId]);

    // Redirect back to orders tab after update
    header("Location: owner_dashboard.php?tab=orders");
    exit;
}



// login check - demo auto-login admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
}


// Delete a book
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM Book WHERE book_ID = ?")->execute([$id]);
    header("Location: dashboard.php");
    exit;
}


// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php"); // owner login page
    exit;
}


// Fetch dashboard stats
$totalBooks = $pdo->query("SELECT COUNT(*) FROM Book")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM Purchase WHERE status IS NULL OR status = 'pending'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT total_revenue FROM vw_total_revenue")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM Book WHERE stock_quantity IS NULL OR stock_quantity <= 5")->fetchColumn();



$tab = $_GET['tab'] ?? 'books';


// Fetch all books
$books = $pdo->query("
    SELECT book_ID, title, author, genre, price, stock_quantity, image_url 
    FROM Book 
    ORDER BY title
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Owner Dashboard - Bibliohaha</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        :root {
            --border: #e5e7eb;
            --radius: 0.5rem;
            --destructive: #ef4444;
        }
        .badge-destructive { background: #fee2e2; color: #991b1b; }
        .badge-default { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <!-- Logo svg -->
                    <svg style="width: 1.5rem; height: 1.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <h2 class="logo">Bibliohaha</h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-primary btn-sm">
                            <svg style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                            </svg>
                            Dashboard
                        </button>
                    </div>
                </div>
                <div class="nav-buttons">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="badge badge-secondary" style="font-size: 0.875rem;">Store Owner</span>
                    </div>
                    <a href="?logout=1" class="btn btn-outline btn-sm">
                        <svg style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </header>



    <main class="container py-6">
        <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1>Owner Dashboard</h1>
            <a href="add_book.php" class="btn btn-primary">
                <svg style="width: 1rem; height: 1rem; margin-right: 0.5rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add New Book
            </a>
        </div>



        <!-- Stats Cards -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 3rem;">
            <div class="stat-card">
                <div class="stat-header" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span class="stat-label">Total Books</span>
                    <svg style="width: 1rem; height: 1rem; color: #717182;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $totalBooks; ?></div>
            </div>



            <div class="stat-card">
                <div class="stat-header" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span class="stat-label">Pending Orders</span>
                    <svg style="width: 1rem; height: 1rem; color: #717182;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <div class="stat-value"><?php echo $pendingOrders; ?></div>
            </div>



            <div class="stat-card">
                <div class="stat-header" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span class="stat-label">Total Revenue</span>
                    <svg style="width: 1.8rem; height: 1.8rem; color: #717182;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="stat-value">MUR <?php echo number_format($totalRevenue, 2); ?></div>
            </div>
            
            <div class="stat-card" style="background: #fef3c7;">
                <div class="stat-header" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span class="stat-label">Low Stock Items</span>
                    <svg style="width:1rem;height:1rem;color:#717182;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-2.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="stat-value" style="color:var(--destructive);"><?= $lowStockCount ?></div>
            </div>



        </div>



        <!-- Tabs -->
        <div class="tabs">
            <div class="tabs-list" style="border-bottom: 1px solid var(--border); margin-bottom: 2rem;">
                <a href="?tab=books" class="tab-trigger <?= $tab === 'books' ? 'active' : '' ?>" style="padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 600;<?= $tab === 'books' ? 'border-bottom: 2px solid #3b82f6; color: #3b82f6;' : 'color: #6b7280;' ?>">Manage Books</a>
                <a href="?tab=orders" class="tab-trigger <?= $tab === 'orders' ? 'active' : '' ?>" style="padding: 0.75rem 1.5rem; text-decoration: none; font-weight: 600;<?= $tab === 'orders' ? 'border-bottom: 2px solid #3b82f6; color: #3b82f6;' : 'color: #6b7280;' ?>">Orders</a>
            </div>



            <?php if ($tab === 'books'): ?>
                <div class="table-container">
                    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                        <h3>Book Inventory</h3>
                    </div>
                    <div style="padding: 1.5rem;">
                        <?php foreach ($books as $book): ?>
                            <div style="display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 1rem;">
                                <img src="<?php echo htmlspecialchars($book['image_url'] ?? 'https://via.placeholder.com/80x100?text=No+Image'); ?>" 
                                     alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                     style="width: 4rem; height: 5rem; object-fit: cover; border-radius: 0.375rem; background: #f3f4f6;" />
                                
                                <div style="flex: 1; min-width: 0;">
                                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="text-sm text-muted">by <?php echo htmlspecialchars($book['author'] ?? 'Unknown'); ?></p>
                                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.25rem;">
                                        <span class="badge badge-secondary"><?php echo htmlspecialchars($book['genre'] ?? 'Uncategorized'); ?></span>
                                        <span style="font-size: 0.875rem;">MUR <?php echo number_format($book['price'], 2); ?></span>
                                        <span class="badge <?= ($book['stock_quantity'] ?? 0) <= 5 ? 'badge-destructive' : 'badge-default'; ?>" style="margin-left: auto;">
                                            <?php echo ($book['stock_quantity'] ?? 0); ?> in stock
                                        </span>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 0.5rem;">
                                    <a href="edit_book.php?id=<?php echo $book['book_ID']; ?>" class="btn btn-outline btn-sm" title="Edit">
                                        <svg style="width: 0.75rem; height: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <a href="?delete=<?php echo $book['book_ID']; ?>" 
                                       onclick="return confirm('Are you sure you want to delete this book?');"
                                       class="btn btn-destructive btn-sm" title="Delete">
                                        <svg style="width: 0.75rem; height: 0.75rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($books)): ?>
                            <p style="text-align:center; color:#6b7280; padding:2rem;">No books in inventory yet. <a href="add_book.php">Add your first book</a></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($tab === 'orders'): ?>
                <?php include 'orders.php'; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
