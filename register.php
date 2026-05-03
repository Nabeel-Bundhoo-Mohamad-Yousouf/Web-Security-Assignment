<!DOCTYPE html>
<html>
<head>
<title>Register</title>

<link rel="stylesheet" href="style.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<script>
$(document).ready(function(){    //Runs code when page is fully loaded

    //JQUERY CAPTURES FORM SUBMISSION
    $("#registerForm").submit(function(e){
        e.preventDefault(); //Enables AJAX flow & prevents page reload

        //Sends data using AJAX POST
        $.ajax({
            url: "api_register.php",
            type: "POST", //safer than GET for sensitive data
            data: $(this).serialize(), //sends txt_username, txt_password
            dataType: "json", //Expects JSON response

            //If the request succeeds, the server response is stored inside res.
            //res= Response
            success: function(res){
                $("#msg").text(res.message); //Displays success or error message from api_register.php

                //if successfull, wait 1 second then redirect to login page
                if(res.status === "success"){
                    setTimeout(() => window.location = "login.php", 1000);
                }
            }
        });
    });

});
</script>
</head>

<!--REGISTER FORM DISPLAY-->
<body class="center-page">
<div class="container">

<h2>Register</h2>

<!--Creates Registration form-->
<form id="registerForm">

<fieldset>
<legend>Register</legend>

Username:<br>
<input type="text" name="txt_username"><br><br>

Password:<br>
<input type="password" name="txt_password"><br><br>

<input type="submit" value="Register">

</fieldset>
</form>

<!--Msg that indicates sucessfull registration + username exists-->
<div id="msg"></div>

<p>Already have an account? <a href="login.php">Login</a></p>

</div>
</body>
</html>