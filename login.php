<?php
session_start();

// Redirect already logged-in users
if (isset($_SESSION['logged_in'])) {
    header("Location: welcome.php");
    exit;
}

// Initialize variables
$username = $userpassword = "";
$usernameErr = $passwordErr = $loginErr = "";

// Sanitize input
function clean_input($data) {
    $data = trim($data);
    $data = addslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Regex patterns
$namePattern = "/^[A-Za-z ]{3,30}$/";   //only letters 3 to 30 characters

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    // Username validation
    if (empty($_POST["txt_username"])) {
        $usernameErr = "Username is required.";
        $valid = false;
    } else {
        $username = clean_input($_POST["txt_username"]);
        if (!preg_match($namePattern, $username)) {
            $usernameErr = "Only letters and spaces (3–30 chars).";
            $valid = false;
        }
    }

    // Password validation
    if (empty($_POST["txt_password"])) {
        $passwordErr = "Password is required.";
        $valid = false;
    } else {
        $userpassword = clean_input($_POST["txt_password"]);
    }

    if ($valid) {
        // DATABASE CONNECTION
        require_once "includes/db_connect.php";

        try {
            // 1. Authenticate against the Users table and retrieve user_ID
            $stmt_user = $conn->prepare("SELECT user_ID, password FROM Users WHERE username = ?");
            $stmt_user->execute([$username]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($userpassword, $user['password'])) {
                
                // 2. User authenticated, now get the customer_ID from the Customer table
                $user_ID = $user['user_ID'];
                $stmt_customer = $conn->prepare("SELECT customer_ID FROM Customer WHERE user_ID = ?");
                $stmt_customer->execute([$user_ID]);
                $customer = $stmt_customer->fetch(PDO::FETCH_ASSOC);

                if ($customer) {
                    // Login success, set session variables
                    $_SESSION['logged_in'] = $username;
                    $_SESSION['user_id'] = $user_ID;
                    $_SESSION['customer_id'] = $customer['customer_ID']; 
                    header("Location: welcome.php");
                    exit;
                } else {
                    $loginErr = "Account found, but requires specific access (not registered as a customer)."; 
                }

            } else {
                $loginErr = "Invalid username or password.";
            }
        } catch(PDOException $e) {
            $loginErr = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<link rel="stylesheet" href="style.css">

</head>
<body class="center-page">
    <div class="container">

<h2>Login</h2>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <fieldset>
        <legend>Login Details</legend>

        Username: <br>
        <input type="text" name="txt_username" value="<?php echo $username; ?>"><br>
        <?php echo $usernameErr; ?><br>

        Password: <br>
        <input type="password" name="txt_password"><br>
        <?php echo $passwordErr; ?><br>

        <?php echo $loginErr; ?><br>

        <input type="submit" value="Login">
    </fieldset>
</form>

<p>Don't have an account? <a href="register.php">Register here</a></p>

</div>
</body>
</html>