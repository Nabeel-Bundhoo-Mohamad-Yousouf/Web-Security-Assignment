<!DOCTYPE html>
<html>
<head>
<title>Login</title>

<link rel="stylesheet" href="style.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<script>
$(document).ready(function(){

    $("#loginForm").submit(function(e){
        e.preventDefault(); // enables AJAX

        //AJAX post
        $.ajax({
            url: "api_login.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",

            success: function(res){
                $("#msg").text(res.message);

                if(res.status === "success"){
                    setTimeout(() => window.location = "index.php", 1000);
                }
            }
        });
    });

});
</script>
</head>

<!--LOGIN FORM DISPLAY-->
<body class="center-page">
<div class="container">

<h2>Login</h2>

<form id="loginForm">
<fieldset>
<legend>Login Details</legend>

Username:<br>
<input type="text" name="txt_username"><br><br>

Password:<br>
<input type="password" name="txt_password"><br><br>

<input type="submit" value="Login">

</fieldset>
</form>

<div id="msg"></div>

<p>Don't have an account? <a href="register.php">Register here</a></p>

</div>
</body>
</html>