<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="css/external_style.css"> 
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> 

</head>
<body>
    <header class="sticky-top  navbar-expand-lg">
        <div class="container-fluid">
            <!--Logo-->
            <a href="index.php" class="logo">bibliohaha</a>

            <!--Links-->
            <nav class="navbar">
                <ul>
                    <li><a href="index.php" class="<?php echo ($activemenu=='home') ? 'active':''; ?>"> home </a></li>
                    <li><a href="contact&about.php" class="<?php echo ($activemenu=='contact&about') ? 'active':''; ?>">about & contact</a></li>
                </ul>
            </nav>
            <!--btns-->
            <div class="icons">
                <a class="owner_access " href="#admin_dashboard"><i class="bi bi-sliders"> owner access</i></a>
                <a href="login.php" class="<?php echo ($activemenu=='login') ? 'active':''; ?>" ><i class="bi bi-person"></i></a>
                <a href="cart.php" class="<?php echo ($activemenu=='cart') ? 'active':''; ?>"> <i class="bi bi-cart2"></i> </a>
            </div>
        </div>
    </header>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js" integrity="sha384-G/EV+4j2dNv+tEPo3++6LCgdCROaejBqfUeNjuKAiuXbjrxilcCdDz6ZAVfHWe1Y" crossorigin="anonymous"></script>

</body>
</html>