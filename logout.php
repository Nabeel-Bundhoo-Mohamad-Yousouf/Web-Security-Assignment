<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="2;url=register.php">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <style>
       
       body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background-color: var(--background);
  color: var(--foreground);
  line-height: 1.5;
  font-size: 16px;
}
        ..text-center {
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
