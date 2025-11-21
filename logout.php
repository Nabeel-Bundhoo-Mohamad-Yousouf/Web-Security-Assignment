<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="2;url=register.php">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <style>
        /* Same background as register page */
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
        .message-box {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <h2>You have been logged out.</h2>
        <p>Redirecting to the register page...</p>
    </div>
</body>
</html>
