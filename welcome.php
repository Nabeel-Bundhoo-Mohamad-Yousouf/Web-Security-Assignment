<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    header("Location: register.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome</title>
<style>
    body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background-color: var(--background);
  color: var(--foreground);
  line-height: 1.5;
  font-size: 16px;
}

    .book-card {
  background: white;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  transition: box-shadow 0.3s;
  display: flex;
  flex-direction: column;
  height: 100%;
}

    .text-center {
  text-align: center;
}

</style>
</head>
<body>
    <div class="card">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['logged_in']); ?>!</h2>
        <p>Your registration was successful.</p>
        <a class="btn-logout" href="logout.php">Logout</a>
    </div>
</body>
</html>
