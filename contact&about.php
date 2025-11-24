<?php
session_start();

//Initialize Database
require_once "includes/db_connect.php";

// Initialize variables
$name = $email = $message = "";
$nameErr = $emailErr = $messageErr = "";
$successMsg = "";

// Sanitize input
function clean_input($data) {
    $data = trim($data);
    $data = addslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {

    // --- Name validation ---
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
    } else {
        $name = clean_input($_POST["name"]);
        if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
            $nameErr = "Only letters and spaces allowed";
        }
    }

    // --- Email validation ---
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = clean_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    // --- Message validation ---
    if (empty($_POST["message"])) {
        $messageErr = "Message is required";
    } else {
        $message = clean_input($_POST["message"]);
        if (strlen($message) < 5) {
            $messageErr = "Message must be at least 5 characters";
        }
    }

    // If no errors → process form and save to database
    if (empty($nameErr) && empty($emailErr) && empty($messageErr)) {
        
        try {
            // 1. Manually generate the next unique message_ID
            $stmt_max_msg = $conn->prepare("SELECT MAX(message_ID) AS max_id FROM Contact_Message");
            $stmt_max_msg->execute();
            $result_msg = $stmt_max_msg->fetch(PDO::FETCH_ASSOC);
            $message_ID = ($result_msg['max_id'] === null) ? 1 : $result_msg['max_id'] + 1;

            // 2. Insert into the new Contact_Message table
            $stmt = $conn->prepare("INSERT INTO Contact_Message (message_ID, sender_name, sender_email, message_text, date_sent) 
                                    VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$message_ID, $name, $email, $message]);

            $_SESSION['success'] = "Thank you $name! Your message has been sent and saved.";
            $_SESSION['form_data'] = []; // Clear form data
            header("Location: contact&about.php");
            exit;
            
        } catch(PDOException $e) {
            // Display database error to help debugging
            echo "<div style='background:red; color:white; padding:10px; margin:10px;'>";
            echo "Database Error: Failed to save message. " . $e->getMessage() . "<br>";
            echo "</div>";
        }
        
    } else {
        // Keep entered values in session
        $_SESSION['form_data'] = [
            'name' => $name,
            'email' => $email,
            'message' => $message
        ];
    }
}

// Retrieve previous session data if available
if (isset($_SESSION['form_data'])) {
    $name = $_SESSION['form_data']['name'] ?? "";
    $email = $_SESSION['form_data']['email'] ?? "";
    $message = $_SESSION['form_data']['message'] ?? "";
    unset($_SESSION['form_data']); // Clear form data after retrieval
}

if (isset($_SESSION['success'])) {
    $successMsg = $_SESSION['success'];
    unset($_SESSION['success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliohaha - About & Contact</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="center-page">
    <div class="container">
    <header>
        <h1>Bibliohaha</h1>
        <nav>
            <a href="index.php">Home</a> |
            <a href="contact&about.php">About & Contact</a>
        </nav>
    </header>

    <main>
        <h2>About Bibliohaha</h2>
        <p>Welcome to <strong>Bibliohaha</strong> — your favourite Boookstore, from fiction to Romance.</p>
        <p>We believe reading should be accessible and enjoyable for everyone. Our store offers:</p>
        <ul>
            <li>Easy browsing and secure checkout.</li>
            <li>Affordable prices and quick delivery.</li>
            <li>Variety of books to meet your preferences.</li>
        </ul>
        <p>We’re passionate about connecting readers to stories that inspire, educate, and entertain.</p>

        <hr>

        <h2>Contact Us</h2>
        <p>If you have any questions or inquiries, you can reach us directly at:</p>
        <ul>
            <li><strong>General Inquiries:</strong> contact@bibliohaha.com</li>
            <li><strong>Support:</strong> support@bibliohaha.com</li>
            <li><strong>Business:</strong> business@bibliohaha.com</li>
        </ul>
        <?php if ($successMsg): ?>
            <p style="color:green;"><?= $successMsg ?></p>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <fieldset>
                <legend>Send us a Message</legend>

                <label for="name">Name:</label><br>
                <input type="text" name="name" value="<?= $name ?>"><br>
                <span style="color:red;"><?= $nameErr ?></span><br><br>

                <label for="email">Email:</label><br>
                <input type="text" name="email" value="<?= $email ?>"><br>
                <span style="color:red;"><?= $emailErr ?></span><br><br>

                <label for="message">Message:</label><br>
                <textarea name="message" rows="5" cols="40"><?= $message ?></textarea><br>
                <span style="color:red;"><?= $messageErr ?></span><br><br>

                <input type="submit" name="submit" value="Send Message">
            </fieldset>
        </form>
    </main>

    <footer>
        <p>&copy; 2025 Bibliohaha. All rights reserved.</p>
    </footer>
        </div>
</body>
</html>