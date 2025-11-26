<?php
/*Enable all errors ;remove later*/
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

//end


session_start();

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

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$bookID = $_GET['id'] ?? null;
if (!$bookID || !ctype_digit($bookID)) {
    header("Location: owner_dashboard.php");
    exit;
}

$errors = [];
$success = false;

$stmt = $pdo->prepare("SELECT * FROM Book WHERE book_ID = ?");
$stmt->execute([$bookID]);
$book = $stmt->fetch();

if (!$book) {
    header("Location: owner_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $rental_fee = trim($_POST['rental_fee'] ?? '');
    $stockQty = trim($_POST['inStock'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $coverUrl = trim($_POST['coverUrl'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '') {
        $errors[] = "Title is required.";
    }
    if ($author === '') {
        $errors[] = "Author is required.";
    }
    if (!is_numeric($price) || (float)$price < 0) {
        $errors[] = "Purchase Price must be a valid non-negative number.";
    }
    if (!is_numeric($rental_fee) || (float)$rental_fee < 0) {
        $errors[] = "Rental Fee must be a valid non-negative number.";
    }
    if (!ctype_digit($stockQty)) {
        $errors[] = "Stock Quantity must be a non-negative integer.";
    }
    if ($category === '') {
        $errors[] = "Category is required.";
    }
    if ($coverUrl !== '' && !filter_var($coverUrl, FILTER_VALIDATE_URL)) {
        $errors[] = "Cover Image URL is invalid.";
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE Book 
            SET title = :title, author = :author, price = :price, rental_fee = :rental_fee, stock_quantity = :stockQty, genre = :category, image_url = :coverUrl, description = :description 
            WHERE book_ID = :bookID
        ");
        $stmt->execute([
            ':title' => $title,
            ':author' => $author,
            ':price' => $price,
            ':rental_fee' => $rental_fee,
            ':stockQty' => $stockQty,
            ':category' => $category,
            ':coverUrl' => $coverUrl !== '' ? $coverUrl : null,
            ':description' => $description !== '' ? $description : null,
            ':bookID' => $bookID,
        ]);
        $success = true;
        $book = array_merge($book, $_POST);
    }
} else {
    $_POST = [
        'title' => $book['title'],
        'author' => $book['author'],
        'price' => $book['price'],
        'rental_fee' => $book['rental_fee'],
        'inStock' => $book['stock_quantity'],
        'category' => $book['genre'],
        'coverUrl' => $book['image_url'],
        'description' => $book['description'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Book - Bibliohaha Owner Dashboard</title>
    <link rel="stylesheet" href="styles.css" />
    <style>
        .form-group { margin-bottom: 1rem; }
        .form-row { display: flex; gap: 1rem; }
        .form-row .form-group { flex: 1; }
        .form-actions { margin-top: 1.5rem; display: flex; gap: 1rem; }
        .alert { padding: 0.75rem 1rem; border-radius: 0.375rem; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-destructive { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
<main class="container py-6">
    <h1>Edit Book</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            Book has been updated successfully.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-destructive">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit_book.php?id=<?= htmlspecialchars($bookID) ?>" novalidate>
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required placeholder="Enter book title" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" />
        </div>
        <div class="form-group">
            <label for="author">Author *</label>
            <input type="text" id="author" name="author" required placeholder="Enter author name" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" />
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="price">Purchase Price (MUR) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required placeholder="0.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label for="rental_fee">Rental Fee (MUR) *</label>
                <input type="number" id="rental_fee" name="rental_fee" step="0.01" min="0" required placeholder="0.00" value="<?= htmlspecialchars($_POST['rental_fee'] ?? '') ?>" />
            </div>
        </div>
        <div class="form-group">
            <label for="inStock">Stock Quantity *</label>
            <input type="number" id="inStock" name="inStock" min="0" required placeholder="0" value="<?= htmlspecialchars($_POST['inStock'] ?? '') ?>" />
        </div>
        <div class="form-group">
            <label for="category">Category *</label>
            <select id="category" name="category" required>
                <option value="" <?= empty($_POST['category']) ? 'selected' : '' ?>>Select a category</option>
                <?php
                    $categories = ["Fiction","Non-Fiction","Mystery","Romance","Science Fiction","Fantasy","Biography","History","Self-Help","Educational"];
                    foreach ($categories as $cat) {
                        $selected = (($_POST['category'] ?? '') === $cat) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($cat) . "\" $selected>" . htmlspecialchars($cat) . "</option>";
                    }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="coverUrl">Cover Image URL</label>
            <input type="url" id="coverUrl" name="coverUrl" placeholder="https://example.com/book-cover.jpg (optional)" value="<?= htmlspecialchars($_POST['coverUrl'] ?? '') ?>" />
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" placeholder="Enter book description (optional)"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-actions">
            <a href="owner_dashboard.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Book</button>
        </div>
    </form>
</main>
</body>
</html>
