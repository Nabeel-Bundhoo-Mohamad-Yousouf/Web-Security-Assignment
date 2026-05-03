<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Bibliohaha - About & Contact</title>

<link rel="stylesheet" href="contactstyle.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
.message-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.message-table th, .message-table td {
    border: 1px solid #ddd;
    padding: 8px;
}

.likeMessage {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.err-msg {
    color: red;
}
</style>
</head>

<body class="center-page">

<div class="container">

<!--UI display-->

<header>
    <h1>Bibliohaha</h1>
    <nav>
        <a href="index.php">Home</a> |
        <a href="contact&about.php">About & Contact</a>
    </nav>
</header>

<main>

<!-- ABOUT -->
<h2>About Bibliohaha</h2>
<p>Welcome to <strong>Bibliohaha</strong>, your bookstore for every reader.</p>

<ul>
    <li>Easy browsing</li>
    <li>Secure checkout</li>
    <li>Wide selection of books</li>
</ul>

<hr>

<!-- CONTACT FORM -->
<h2>Contact Us</h2>

<div id="response-area"></div>

<form id="contactForm">
    <fieldset>
        <legend>Send Message</legend>

        <label for="name">Name:</label><br>
        <input type="text" name="name" id="name" autocomplete="name"><br>
        <span class="err-msg" id="nameErr"></span><br>

        <label for="email">Email:</label><br>
        <input type="email" name="email" id="email" autocomplete="email"><br>
        <span class="err-msg" id="emailErr"></span><br>

        <label for="message">Message:</label><br>
        <textarea name="message" id="message"></textarea><br>
        <span class="err-msg" id="messageErr"></span><br>

        <button type="submit">Send Message</button>
    </fieldset>
</form>

<hr>

<!-- VIEW MESSAGES -->
<h2>Community Messages</h2>

<button id="loadMessagesBtn" type="button">Feedbacks</button>

<div id="messagesContainer"></div>

</main>
</div>

<script>
$(document).ready(function(){ //Ensures DOM is fully loaded before running scripts

    
    // CONTACT FORM 
    //JQUERY captures form data with id contactForm 
    $('#contactForm').on('submit', function(e){
        e.preventDefault(); //Enables Ajax flow

        $('.err-msg').text('');
        $('#response-area').html('');

        //AJAX Submission
        $.ajax({
            url: 'process_contact.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response){

                if(response.status === 'success'){
                    $('#response-area').html(
                        "<p style='color:green'>" + response.message + "</p>"
                    );
                    $('#contactForm')[0].reset(); //empty form fields
                } else {

                    if(response.errors.name){
                        $('#nameErr').text(response.errors.name);
                    }
                    if(response.errors.email){
                        $('#emailErr').text(response.errors.email);
                    }
                    if(response.errors.message){
                        $('#messageErr').text(response.errors.message);
                    }
                }
            },
            error: function(){
                $('#response-area').html(
                    "<p style='color:red'>Server error</p>"
                );
            }
        });
    });

    
    // LOAD MESSAGES + JSON SCHEMA CHECK
    
    //Jquery event handler for button with id loadMessagesBtn
    $("#loadMessagesBtn").click(function(){
        
    // Retrieves JSON data from viewMessageAjax.php 
      // & Builds dynamic HTML Table
        $.getJSON("viewMessageAjax.php", function(data){

            let table_str = "";

            // JSON schema validation 
            let valid = true;

            $.each(data, function(i, obj){ //Loops through every object

                if(
                   obj.message_ID == null ||
                   obj.sender_name == null ||
                   obj.message_text == null ||
                   obj.like_count == null
                ) 
                {
                    valid = false;
                }

            });
            
            if(!valid){
                $("#messagesContainer").html(     //error message displayed
                    "<p style='color:red'>Invalid JSON structure</p>"
                );
                return;
            }
           
            //Creates table if valid=true
            table_str += "<table class='message-table'>";
            table_str += "<tr><th>Name</th><th>Message</th><th>Likes</th><th>Like</th></tr>";

            $.each(data, function(i, obj){  //creates table row for each message object in JSON array

                table_str += "<tr>";

                table_str += "<td>" + obj.sender_name + "</td>";
                table_str += "<td>" + obj.message_text + "</td>";

                table_str += "<td id='like-count-" + obj.message_ID + "'>";
                table_str += obj.like_count;
                table_str += "</td>";

                table_str += "<td>";
                table_str += "<img src='images/heart.png' ";
                table_str += "class='likeMessage' ";
                table_str += "id='" + obj.message_ID + "'>";
                table_str += "</td>";

                table_str += "</tr>";
            });

            table_str += "</table>";

            $("#messagesContainer").html(table_str); //display table

        });

    });

   
    // "Like" click Handler
    
    $(document).on("click", ".likeMessage", function(){

        var messageId = $(this).attr("id");

        //API call --> Update like_count column in DB 
        $.post("update_like.php",
            { message_id: messageId },
            
            function(response){

                if(response.trim() === "success"){

                    let countCell = $("#like-count-" + messageId);
                    let current = parseInt(countCell.text());
                    countCell.text(current + 1);

                } else {
                    alert("Error liking message");
                }

            }
        );

    });

});
</script>

</body>
</html>