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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Welcome</title>
<style>
    /* --- Background & Base --- */
    body {
        margin: 0;
        padding: 0;
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: "Segoe UI", Arial, sans-serif;
        background: linear-gradient(135deg, #f3e7e9, #e3eeff); /* pastel gradient */
        color: #333;
    }

    /* --- Card --- */
    .card {
        background: rgba(255,255,255,0.95);
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        text-align: center;
        max-width: 400px;
    }

    .card h2 {
        color: #7f66c8; /* pastel purple heading */
        margin-bottom: 15px;
    }

    .card p {
        margin-bottom: 20px;
    }

    .btn-logout {
        display: inline-block;
        padding: 12px 25px;
        background: #b39ddb; /* pastel purple button */
        color: white;
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        transition: background 0.2s ease;
    }

    .btn-logout:hover {
        background: #9a81d6; /* darker on hover */
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
