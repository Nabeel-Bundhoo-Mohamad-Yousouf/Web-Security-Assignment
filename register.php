<?php
session_start();



// Redirect if already logged in
if (isset($_SESSION['logged_in'])) {
    header("Location: welcome.php");
    exit;
}

// Initialize variables
$username = $userpassword = "";
$usernameErr = $passwordErr = "";

// Sanitize input
function clean_input($data) {
    $data = trim($data);
    $data = addslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Regex patterns
$namePattern = "/^[A-Za-z ]{3,30}$/";  // Only letters 3 to 30 characters
$passPattern = "/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d!@#\$%\^&\*]{6,}$/";  // Password must contain letters and digits, min 6 chars

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $valid = true;

    // Validate username
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

    // Validate password
    if (empty($_POST["txt_password"])) {
        $passwordErr = "Password is required.";
        $valid = false;
    } else {
        $userpassword = clean_input($_POST["txt_password"]);
        if (!preg_match($passPattern, $userpassword)) {
            $passwordErr = "Password must contain letters and numbers, min 6 chars.";
            $valid = false;
        }
    }

    if ($valid) {
        $hashed_password = password_hash($userpassword, PASSWORD_DEFAULT);

        // DATABASE CONNECTION
        require_once "includes/db_connect.php";

        try {
            // 1. Check if username already exists in the Users table
            $check_stmt = $conn->prepare("SELECT user_ID FROM Users WHERE username = ?");
            $check_stmt->execute([$username]);

            if ($check_stmt->rowCount() > 0) {
                $usernameErr = "This username is already taken.";
            } else {
                // 2. Insert into Users (Supertype)
                // NOTE: Users table primary key is also non-auto-incrementing. 
                // We must manually generate a user_ID. Assuming user_ID is also sequential.
                $stmt_max_user = $conn->prepare("SELECT MAX(user_ID) AS max_id FROM Users");
                $stmt_max_user->execute();
                $result_user = $stmt_max_user->fetch(PDO::FETCH_ASSOC);
                $user_ID = ($result_user['max_id'] === null) ? 1 : $result_user['max_id'] + 1;
                
                $stmt_user = $conn->prepare("INSERT INTO Users (user_ID, username, password) VALUES (?, ?, ?)");
                $stmt_user->execute([$user_ID, $username, $hashed_password]);

                // 3. Insert into Customer (Subtype)
                // Use the new user_ID for both user_ID (FK) and customer_ID (Unique PK-like field)
                $customer_ID_pk = $user_ID; 

                $stmt_customer = $conn->prepare("INSERT INTO Customer (user_ID, customer_ID, customer_name) VALUES (?, ?, ?)");
                $stmt_customer->execute([$user_ID, $customer_ID_pk, $username]);

                // 4. Manually generate the next unique Registration_No
                $stmt_max_reg = $conn->prepare("SELECT MAX(Registration_No) AS max_id FROM Registration_Form");
                $stmt_max_reg->execute();
                $result_reg = $stmt_max_reg->fetch(PDO::FETCH_ASSOC);
                $registration_no = ($result_reg['max_id'] === null) ? 1 : $result_reg['max_id'] + 1;

                // 5. Insert into Registration_Form (using the generated Registration_No)
                $stmt_reg = $conn->prepare("INSERT INTO Registration_Form (Registration_No, customer_ID, date_time) VALUES (?, ?, NOW())");
                $stmt_reg->execute([$registration_no, $customer_ID_pk]);

                // Login success
                $_SESSION['logged_in'] = $username;
                $_SESSION['user_id'] = $user_ID;
                $_SESSION['customer_id'] = $customer_ID_pk;
                header("Location: welcome.php");
                exit;
            }
        } catch(PDOException $e) {
            echo "<div style='background:red; color:white; padding:10px; margin:10px;'>";
            echo "Database Error: " . $e->getMessage() . "<br>";
            echo "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register</title>

<link rel="stylesheet" href="style.css">
</head>
<body class="center-page">
    <div class="container">

<h2>Register</h2>

<?php if (!isset($_SESSION['logged_in'])): ?>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <fieldset>
        <legend>Account Details</legend>

        Username: <br>
        <input type="text" name="txt_username" value="<?php echo $username; ?>"><br>
        <?php echo $usernameErr; ?><br>

        Password: <br>
        <input type="password" name="txt_password"><br>
        <?php echo $passwordErr; ?><br>

        <input type="submit" value="Register">
    </fieldset>
</form>

<p>Already have an account? <a href="login.php">Login here</a></p>

<?php else: ?>
<p>You are already registered and logged in.</p>
<a href="welcome.php">Go to Welcome Page</a>
<?php endif; ?>
</div>
</body>
</html>