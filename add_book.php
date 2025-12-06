<?php
session_start();

// Admin protection 
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Database connection
$host = 'localhost';
$db = 'Bibliohaha';
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
    die("Connection failed: " . htmlspecialchars($e->getMessage()));
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collecting inputs
    $title       = trim($_POST['title'] ?? '');
    $author      = trim($_POST['author'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $borrowPrice = trim($_POST['borrowPrice'] ?? '');
    $inStock     = trim($_POST['inStock'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $coverUrl    = trim($_POST['coverUrl'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Server side validation
    if ($title === '')                                      $errors[] = "Title is required.";
    if ($author === '')                                     $errors[] = "Author is required.";
    if ($price === '' || !is_numeric($price) || $price < 0) $errors[] = "Purchase Price must be a non-negative number.";
    if ($borrowPrice === '' || !is_numeric($borrowPrice) || $borrowPrice < 0) $errors[] = "Borrow Price must be a non-negative number.";
    if ($inStock === '' || !ctype_digit($inStock))          $errors[] = "Stock Quantity must be a non-negative integer.";
    if ($category === '')                                   $errors[] = "Category is required.";
    if ($coverUrl !== '' && !filter_var($coverUrl, FILTER_VALIDATE_URL)) {
        $errors[] = "Cover Image URL is not valid.";
    }

    if (empty($errors)) {
        //safe prepared statement
        $stmt = $pdo->prepare("
            INSERT INTO Book 
                (title, author, price, rental_fee, stock_quantity, genre, image_url, description)
            VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $title,
            $author,
            $price,
            $borrowPrice,
            (int)$inStock,
            $category,
            $coverUrl !== '' ? $coverUrl : null,
            $description !== '' ? $description : null
        ]);

        $success = true;
        // Clear form after success
        $_POST = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add New Book - Bibliohaha</title>
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
    <h1>Add New Book</h1>

    <?php if ($success): ?>
        <div class="alert alert-success">Book added successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-destructive">
            <ul style="margin:0.5rem 0;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="add_book.php" novalidate>
        <div class="form-group">
            <label for="title">Title *</label>
            <input type="text" id="title" name="title" required placeholder="Enter book title"
                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" />
        </div>

        <div class="form-group">
            <label for="author">Author *</label>
            <input type="text" id="author" name="author" required placeholder="Enter author name"
                   value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" />
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="price">Purchase Price (MUR) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" />
            </div>
            <div class="form-group">
                <label for="borrowPrice">Borrow Price (7 days, MUR) *</label>
                <input type="number" id="borrowPrice" name="borrowPrice" step="0.01" min="0" required
                       value="<?= htmlspecialchars($_POST['borrowPrice'] ?? '') ?>" />
            </div>
        </div>

        <div class="form-group">
            <label for="inStock">Stock Quantity *</label>
            <input type="number" id="inStock" name="inStock" min="0" required
                   value="<?= htmlspecialchars($_POST['inStock'] ?? '') ?>" />
        </div>

        <div class="form-group">
            <label for="category">Category *</label>
            <select id="category" name="category" required>
                <option value="">Select a category</option>
                <?php
                $categories = ["Fiction", "Non-Fiction", "Mystery", "Romance", "Science Fiction", "Fantasy", "Biography", "History", "Self-Help", "Educational"];
                foreach ($categories as $cat):
                    $selected = ($_POST['category'] ?? '') === $cat ? 'selected' : '';
                ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $selected ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="coverUrl">Cover Image URL (optional)</label>
            <input type="url" id="coverUrl" name="coverUrl" placeholder="https://example.com/book-cover.jpg"
                   value="<?= htmlspecialchars($_POST['coverUrl'] ?? '') ?>" />
        </div>

        <div class="form-group">
            <label for="description">Description (optional)</label>
            <textarea id="description" name="description" rows="3" placeholder="Enter book description"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <a href="owner_dashboard.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Book</button>
        </div>
    </form>
</main>
</body>
</html>