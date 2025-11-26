<?php


session_start();

// Database config (adjust as needed)
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

$errors = [];
$success = false;

// Process POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and trim inputs
    $title       = trim($_POST['title'] ?? '');
    $author      = trim($_POST['author'] ?? '');
    $price       = trim($_POST['price'] ?? '');
    $borrowPrice = trim($_POST['borrowPrice'] ?? '');
    $inStock     = trim($_POST['inStock'] ?? '');
    $category    = trim($_POST['category'] ?? '');
    $coverUrl    = trim($_POST['coverUrl'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate required fields
    if ($title === '') $errors[] = "Title is required.";
    if ($author === '') $errors[] = "Author is required.";
    if ($price === '' || !is_numeric($price) || $price < 0) $errors[] = "Purchase Price must be a non-negative number.";
    if ($borrowPrice === '' || !is_numeric($borrowPrice) || $borrowPrice < 0) $errors[] = "Borrow Price must be a non-negative number.";
    if ($inStock === '' || !ctype_digit($inStock)) $errors[] = "Stock Quantity must be a non-negative integer.";
    if ($category === '') $errors[] = "Category is required.";
    if ($coverUrl !== '' && !filter_var($coverUrl, FILTER_VALIDATE_URL)) $errors[] = "Cover Image URL is not valid.";

    if (empty($errors)) {
        //removed book_ID from column list – let AUTO_INCREMENT handle it
        $stmt = $pdo->prepare("INSERT INTO Book (title, author, price, rental_fee, stock_quantity, genre, image_url, description) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $title,
            $author,
            $price,
            $borrowPrice,
            $inStock,
            $category,
            $coverUrl ?: null,
            $description ?: null,
        ]);

        $success = true;
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
</head>
<body>
    <main class="container py-6">
        <h1>Add New Book</h1>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                Book added successfully.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-destructive" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_book.php" class="modal-form" novalidate>
            <div class="form-group">
                <label for="title">Title *</label>
                <input type="text" id="title" name="title" placeholder="Enter book title" required
                       value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" />
            </div>

            <div class="form-group">
                <label for="author">Author *</label>
                <input type="text" id="author" name="author" placeholder="Enter author name" required
                       value="<?= htmlspecialchars($_POST['author'] ?? '') ?>" />
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price">Purchase Price (MUR) *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" placeholder="0.00" required 
                           value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" />
                </div>

                <div class="form-group">
                    <label for="borrowPrice">Borrow Price (7 days, MUR) *</label>
                    <input type="number" id="borrowPrice" name="borrowPrice" step="0.01" min="0" placeholder="0.00" required
                           value="<?= htmlspecialchars($_POST['borrowPrice'] ?? '') ?>" />
                </div>
            </div>

            <div class="form-group">
                <label for="inStock">Stock Quantity *</label>
                <input type="number" id="inStock" name="inStock" min="0" placeholder="0" required
                       value="<?= htmlspecialchars($_POST['inStock'] ?? '') ?>" />
            </div>

            <div class="form-group">
                <label for="category">Category *</label>
                <select id="category" name="category" required>
                    <option value="">Select a category</option>
                    <?php
                    $categories = ["Fiction", "Non-Fiction", "Mystery", "Romance", "Science Fiction", "Fantasy", "Biography", "History", "Self-Help", "Educational"];
                    $selectedCategory = $_POST['category'] ?? '';
                    foreach ($categories as $cat) {
                        $sel = ($cat === $selectedCategory) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($cat) . "\" $sel>" . htmlspecialchars($cat) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="coverUrl">Cover Image URL</label>
                <input type="url" id="coverUrl" name="coverUrl" placeholder="https://example.com/book-cover.jpg (optional)"
                       value="<?= htmlspecialchars($_POST['coverUrl'] ?? '') ?>" />
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" placeholder="Enter book description (optional)"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="form-actions">
                <a href="owner_dashboard.php" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Book</button>
            </div>
        </form>
    </main>
</body>
</html>